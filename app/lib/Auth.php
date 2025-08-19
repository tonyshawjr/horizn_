<?php
/**
 * Authentication Manager
 * 
 * Handles user authentication, session management, and magic link authentication
 * with enhanced security features for analytics platform.
 */

class Auth
{
    private static $config = null;
    private static $current_user = null;
    
    /**
     * Load authentication configuration
     */
    private static function loadConfig(): void
    {
        if (self::$config === null) {
            self::$config = require CONFIG_PATH . '/app.php';
        }
    }
    
    /**
     * Start secure session
     */
    public static function startSession(): void
    {
        self::loadConfig();
        
        if (session_status() === PHP_SESSION_NONE) {
            $config = self::$config['security'];
            
            // Set secure session configuration
            ini_set('session.cookie_lifetime', $config['session_lifetime']);
            ini_set('session.cookie_secure', $config['session_secure']);
            ini_set('session.cookie_httponly', $config['session_httponly']);
            ini_set('session.cookie_samesite', $config['session_samesite']);
            ini_set('session.use_strict_mode', 1);
            
            session_name($config['session_name']);
            session_start();
            
            // Regenerate session ID for security
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
                $_SESSION['created'] = time();
            }
            
            // Check session timeout
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity'] > $config['session_lifetime'])) {
                self::logout();
                return;
            }
            
            $_SESSION['last_activity'] = time();
        }
    }
    
    /**
     * Authenticate user with email and password
     */
    public static function login(string $email, string $password): array
    {
        self::loadConfig();
        
        try {
            // Rate limiting check
            if (!self::checkRateLimit('login', $email)) {
                return [
                    'success' => false,
                    'error' => 'Too many login attempts. Please try again later.',
                    'code' => 'RATE_LIMITED'
                ];
            }
            
            // Find user by email
            $user = Database::selectOne(
                "SELECT id, username, email, password_hash, first_name, last_name, is_active, last_login 
                 FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user || !$user['is_active']) {
                self::logFailedAttempt('login', $email);
                return [
                    'success' => false,
                    'error' => 'Invalid credentials or inactive account.',
                    'code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                self::logFailedAttempt('login', $email);
                return [
                    'success' => false,
                    'error' => 'Invalid credentials.',
                    'code' => 'INVALID_CREDENTIALS'
                ];
            }
            
            // Check if password needs rehashing
            $password_config = self::$config['security'];
            if (password_needs_rehash($user['password_hash'], $password_config['password_hash_algo'])) {
                $new_hash = password_hash($password, $password_config['password_hash_algo']);
                Database::update(
                    "UPDATE users SET password_hash = ? WHERE id = ?",
                    [$new_hash, $user['id']]
                );
            }
            
            // Create session
            self::createUserSession($user);
            
            // Update last login
            Database::update(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Clear rate limit
            self::clearRateLimit('login', $email);
            
            return [
                'success' => true,
                'user' => self::sanitizeUser($user),
                'redirect' => '/dashboard'
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Login failed. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Generate and send magic login link (database-based)
     */
    public static function sendMagicLink(string $email): array
    {
        self::loadConfig();
        
        try {
            // Rate limiting check
            if (!self::checkRateLimit('magic_link', $email)) {
                return [
                    'success' => false,
                    'error' => 'Too many requests. Please try again later.',
                    'code' => 'RATE_LIMITED'
                ];
            }
            
            // Find user by email
            $user = Database::selectOne(
                "SELECT id, email, first_name, last_name, is_active, first_login FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if (!$user) {
                // Don't reveal if email exists for security
                return [
                    'success' => true,
                    'message' => 'If the email exists, a login link has been sent.'
                ];
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(64));
            $expires = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            
            // Store magic link in database
            $link_id = Database::insert(
                "INSERT INTO magic_links (user_id, token, email, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $user['id'],
                    $token,
                    $user['email'],
                    $expires,
                    self::getClientIP(),
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
            
            if (!$link_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to generate login link. Please try again.',
                    'code' => 'DATABASE_ERROR'
                ];
            }
            
            // Generate magic link URL
            $magic_link = self::$config['app']['url'] . '/auth/verify?token=' . $token;
            
            // Check if this is first login
            $isFirstLogin = $user['first_login'] ?? true;
            
            // Send email using Mail class
            $email_sent = Mail::sendMagicLink($user, $magic_link, $isFirstLogin);
            
            if ($email_sent) {
                return [
                    'success' => true,
                    'message' => 'Login link sent to your email address.'
                ];
            } else {
                // Clean up failed magic link
                Database::update("DELETE FROM magic_links WHERE id = ?", [$link_id]);
                return [
                    'success' => false,
                    'error' => 'Failed to send login link. Please try again.',
                    'code' => 'EMAIL_FAILED'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Magic link error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send login link. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Authenticate using magic link token (database-based)
     */
    public static function loginWithMagicToken(string $token): array
    {
        try {
            // Find magic link in database
            $magic_link = Database::selectOne(
                "SELECT ml.*, u.id as user_id, u.username, u.email, u.first_name, u.last_name, u.is_active, u.first_login 
                 FROM magic_links ml 
                 JOIN users u ON ml.user_id = u.id 
                 WHERE ml.token = ? AND ml.is_used = 0 AND ml.expires_at > NOW()",
                [$token]
            );
            
            if (!$magic_link) {
                // Clean up expired tokens
                self::cleanupExpiredTokens();
                return [
                    'success' => false,
                    'error' => 'Invalid or expired login link.',
                    'code' => 'INVALID_TOKEN'
                ];
            }
            
            if (!$magic_link['is_active']) {
                return [
                    'success' => false,
                    'error' => 'User account is not active.',
                    'code' => 'USER_INACTIVE'
                ];
            }
            
            // Mark magic link as used
            Database::update(
                "UPDATE magic_links SET is_used = 1, used_at = NOW() WHERE id = ?",
                [$magic_link['id']]
            );
            
            // Create user session
            $user = [
                'id' => $magic_link['user_id'],
                'username' => $magic_link['username'],
                'email' => $magic_link['email'],
                'first_name' => $magic_link['first_name'],
                'last_name' => $magic_link['last_name'],
                'is_active' => $magic_link['is_active']
            ];
            
            self::createUserSession($user);
            
            // Update user last login and clear first_login flag
            Database::update(
                "UPDATE users SET last_login = NOW(), first_login = 0 WHERE id = ?",
                [$magic_link['user_id']]
            );
            
            // Clean up old magic links for this user
            Database::update(
                "DELETE FROM magic_links WHERE user_id = ? AND (is_used = 1 OR expires_at < NOW())",
                [$magic_link['user_id']]
            );
            
            return [
                'success' => true,
                'user' => self::sanitizeUser($user),
                'redirect' => '/dashboard'
            ];
            
        } catch (Exception $e) {
            error_log("Magic token login error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Login failed. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_authenticated']);
    }
    
    /**
     * Get current authenticated user
     */
    public static function user(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        if (self::$current_user === null) {
            try {
                self::$current_user = Database::selectOne(
                    "SELECT id, username, email, first_name, last_name, created_at, last_login 
                     FROM users WHERE id = ? AND is_active = 1",
                    [$_SESSION['user_id']]
                );
            } catch (Exception $e) {
                error_log("Error fetching user: " . $e->getMessage());
                return null;
            }
        }
        
        return self::$current_user;
    }
    
    /**
     * Logout user and destroy session
     */
    public static function logout(): void
    {
        self::startSession();
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
        self::$current_user = null;
    }
    
    /**
     * Register new user (if registration is enabled)
     */
    public static function register(array $data): array
    {
        self::loadConfig();
        
        // Check if registration is enabled
        $registration_enabled = Database::selectOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'enable_user_registration'"
        );
        
        if (!$registration_enabled || !$registration_enabled['setting_value']) {
            return [
                'success' => false,
                'error' => 'User registration is currently disabled.',
                'code' => 'REGISTRATION_DISABLED'
            ];
        }
        
        // Validate input data
        $validation = self::validateRegistrationData($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'code' => 'VALIDATION_ERROR'
            ];
        }
        
        try {
            // Check if user already exists
            $existing_user = Database::selectOne(
                "SELECT id FROM users WHERE email = ? OR username = ?",
                [$data['email'], $data['username']]
            );
            
            if ($existing_user) {
                return [
                    'success' => false,
                    'error' => 'User with this email or username already exists.',
                    'code' => 'USER_EXISTS'
                ];
            }
            
            // Hash password
            $password_hash = password_hash(
                $data['password'],
                self::$config['security']['password_hash_algo']
            );
            
            // Insert user
            $user_id = Database::insert(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, is_active, created_at) 
                 VALUES (?, ?, ?, ?, ?, 1, NOW())",
                [
                    $data['username'],
                    $data['email'],
                    $password_hash,
                    $data['first_name'] ?? '',
                    $data['last_name'] ?? ''
                ]
            );
            
            if ($user_id) {
                return [
                    'success' => true,
                    'user_id' => $user_id,
                    'message' => 'Account created successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to create account.',
                    'code' => 'CREATION_FAILED'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Registration failed. Please try again.',
                'code' => 'SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Create user session after successful authentication
     */
    private static function createUserSession(array $user): void
    {
        self::startSession();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_authenticated'] = true;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set secure session fingerprint
        $_SESSION['session_fingerprint'] = self::generateSessionFingerprint();
    }
    
    /**
     * Generate secure session fingerprint
     */
    private static function generateSessionFingerprint(): string
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = self::getClientIP();
        
        return hash('sha256', $user_agent . $ip_address . session_id());
    }
    
    /**
     * Validate session fingerprint
     */
    public static function validateSessionFingerprint(): bool
    {
        if (!isset($_SESSION['session_fingerprint'])) {
            return false;
        }
        
        $current_fingerprint = self::generateSessionFingerprint();
        return hash_equals($_SESSION['session_fingerprint'], $current_fingerprint);
    }
    
    /**
     * Generate secure random token
     */
    private static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Hash IP address for privacy
     */
    public static function hashIP(string $ip): string
    {
        self::loadConfig();
        $salt = self::$config['security']['ip_salt'];
        return hash('sha256', $ip . $salt);
    }
    
    /**
     * Check rate limiting for actions
     */
    private static function checkRateLimit(string $action, string $identifier): bool
    {
        self::startSession();
        
        $key = "rate_limit_{$action}_" . hash('md5', $identifier);
        $max_attempts = ($action === 'magic_link') ? 3 : 5; // Lower limit for magic links
        $window = 300; // 5 minutes
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        $rate_data = $_SESSION[$key];
        
        // Reset if window has expired
        if (time() - $rate_data['first_attempt'] > $window) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($rate_data['attempts'] >= $max_attempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Log failed authentication attempt
     */
    private static function logFailedAttempt(string $type, string $identifier): void
    {
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        error_log("Failed {$type} attempt - IP: {$ip}, Identifier: {$identifier}, UA: {$user_agent}");
    }
    
    /**
     * Clear rate limit for successful authentication
     */
    private static function clearRateLimit(string $action, string $identifier): void
    {
        $key = "rate_limit_{$action}_" . hash('md5', $identifier);
        unset($_SESSION[$key]);
    }
    
    /**
     * Validate registration data
     */
    private static function validateRegistrationData(array $data): array
    {
        self::loadConfig();
        
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, hyphens, and underscores';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        } elseif (strlen($data['email']) > 255) {
            $errors[] = 'Email address is too long';
        }
        
        // Password validation
        $min_length = self::$config['security']['password_min_length'];
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < $min_length) {
            $errors[] = "Password must be at least {$min_length} characters long";
        }
        
        // Name validation (optional)
        if (!empty($data['first_name']) && strlen($data['first_name']) > 100) {
            $errors[] = 'First name is too long';
        }
        if (!empty($data['last_name']) && strlen($data['last_name']) > 100) {
            $errors[] = 'Last name is too long';
        }
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(', ', $errors)
        ];
    }
    
    /**
     * Sanitize user data for API responses
     */
    private static function sanitizeUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'last_login' => $user['last_login'] ?? null
        ];
    }
    
    /**
     * Check if setup is required (no users exist)
     */
    public static function setupRequired(): bool
    {
        try {
            $users_exist = Database::selectOne("SELECT COUNT(*) as count FROM users");
            return !$users_exist || $users_exist['count'] == 0;
        } catch (Exception $e) {
            error_log("Error checking setup status: " . $e->getMessage());
            return true; // Assume setup is required if we can't check
        }
    }
    
    /**
     * Clean up expired magic tokens from database
     */
    public static function cleanupExpiredTokens(): void
    {
        try {
            Database::update(
                "DELETE FROM magic_links WHERE expires_at < NOW() OR (is_used = 1 AND used_at < DATE_SUB(NOW(), INTERVAL 1 HOUR))"
            );
        } catch (Exception $e) {
            error_log("Error cleaning up expired magic links: " . $e->getMessage());
        }
    }
}
?>
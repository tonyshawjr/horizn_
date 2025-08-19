<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication, login, logout, and magic link functionality.
 */

class AuthController
{
    /**
     * Show login page
     */
    public function login()
    {
        // If user is already authenticated, redirect to dashboard
        if (Auth::isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }
        
        $error = null;
        $success = null;
        
        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $login_type = $_POST['login_type'] ?? 'password';
            
            if ($login_type === 'magic') {
                // Magic link login
                $result = $this->requestMagicLink($email);
                if ($result['success']) {
                    // Store email in session for the sent page
                    $_SESSION['magic_link_email'] = $email;
                    header('Location: /auth/magic-link-sent');
                    exit;
                } else {
                    $error = $result['error'];
                }
            } else {
                // Password login
                if (empty($email) || empty($password)) {
                    $error = 'Email and password are required.';
                } else {
                    $result = Auth::login($email, $password);
                    if ($result['success']) {
                        // Successful login - redirect
                        header('Location: ' . ($result['redirect'] ?? '/dashboard'));
                        exit;
                    } else {
                        $error = $result['error'];
                    }
                }
            }
        }
        
        // Load login view
        $data = [
            'error' => $error,
            'success' => $success,
            'page_title' => 'Login - horizn_ Analytics'
        ];
        
        $this->renderView('auth/login', $data);
    }
    
    /**
     * Handle magic link authentication
     */
    public function magic()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->renderView('auth/verify-magic-link', [
                'status' => 'error',
                'message' => 'Invalid magic link - token missing.'
            ]);
            return;
        }
        
        $result = Auth::loginWithMagicToken($token);
        
        if ($result['success']) {
            // Show success page before redirect
            $this->renderView('auth/verify-magic-link', [
                'status' => 'success',
                'message' => 'Login successful! Redirecting to dashboard...'
            ]);
        } else {
            $this->renderView('auth/verify-magic-link', [
                'status' => 'error',
                'message' => $result['error']
            ]);
        }
    }
    
    /**
     * Handle logout
     */
    public function logout()
    {
        Auth::logout();
        
        // Redirect to login page with success message
        header('Location: /auth/login?logged_out=1');
        exit;
    }
    
    /**
     * User registration (if enabled)
     */
    public function register()
    {
        // Check if registration is enabled
        $registration_enabled = Database::selectOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'enable_user_registration'"
        );
        
        if (!$registration_enabled || !$registration_enabled['setting_value']) {
            $this->redirectWithError('/auth/login', 'User registration is currently disabled.');
            return;
        }
        
        $error = null;
        $success = null;
        
        // Handle registration form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
            ];
            
            // Validate password confirmation
            if ($data['password'] !== $data['password_confirm']) {
                $error = 'Passwords do not match.';
            } else {
                // Attempt registration
                $result = Auth::register($data);
                
                if ($result['success']) {
                    $success = 'Account created successfully! You can now log in.';
                } else {
                    $error = $result['error'];
                }
            }
        }
        
        // Load registration view
        $view_data = [
            'error' => $error,
            'success' => $success,
            'page_title' => 'Register - horizn_ Analytics'
        ];
        
        $this->renderView('auth/register', $view_data);
    }
    
    /**
     * Show forgot password page
     */
    public function forgot()
    {
        $error = null;
        $success = null;
        
        // Handle forgot password form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            
            if (empty($email)) {
                $error = 'Email address is required.';
            } else {
                // Send magic link for password reset
                $result = Auth::sendMagicLink($email);
                
                if ($result['success']) {
                    $success = 'If an account with that email exists, a login link has been sent.';
                } else {
                    // Don't reveal if email exists for security
                    $success = 'If an account with that email exists, a login link has been sent.';
                }
            }
        }
        
        // Load forgot password view
        $data = [
            'error' => $error,
            'success' => $success,
            'page_title' => 'Forgot Password - horizn_ Analytics'
        ];
        
        $this->renderView('auth/forgot', $data);
    }
    
    /**
     * Handle password reset
     */
    public function reset()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->redirectWithError('/auth/login', 'Invalid reset link.');
            return;
        }
        
        $error = null;
        $success = null;
        
        // Handle password reset form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if (empty($password) || empty($password_confirm)) {
                $error = 'Both password fields are required.';
            } elseif ($password !== $password_confirm) {
                $error = 'Passwords do not match.';
            } else {
                // Validate password strength
                $config = require CONFIG_PATH . '/app.php';
                $min_length = $config['security']['password_min_length'];
                
                if (strlen($password) < $min_length) {
                    $error = "Password must be at least {$min_length} characters long.";
                } else {
                    // Use magic link token to authenticate and reset password
                    $result = Auth::loginWithMagicToken($token);
                    
                    if ($result['success']) {
                        // User is now authenticated, change their password
                        $user = Auth::user();
                        $reset_result = User::resetPassword($user['id'], $password);
                        
                        if ($reset_result['success']) {
                            $success = 'Password reset successfully. You can now use your new password to log in.';
                        } else {
                            $error = $reset_result['error'];
                        }
                        
                        // Log out after password reset for security
                        Auth::logout();
                    } else {
                        $error = $result['error'];
                    }
                }
            }
        }
        
        // Load reset password view
        $data = [
            'token' => $token,
            'error' => $error,
            'success' => $success,
            'page_title' => 'Reset Password - horizn_ Analytics'
        ];
        
        $this->renderView('auth/reset', $data);
    }
    
    /**
     * User profile management
     */
    public function profile()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            $this->redirectWithError('/auth/login', 'Please log in to access your profile.');
            return;
        }
        
        $user = Auth::user();
        $error = null;
        $success = null;
        
        // Handle profile update form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $data = [
                    'first_name' => trim($_POST['first_name'] ?? ''),
                    'last_name' => trim($_POST['last_name'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'username' => trim($_POST['username'] ?? ''),
                ];
                
                $result = User::update($user['id'], $data);
                
                if ($result['success']) {
                    $success = $result['message'];
                    // Refresh user data
                    $user = Auth::user();
                } else {
                    $error = $result['error'];
                }
                
            } elseif ($action === 'change_password') {
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    $result = User::changePassword($user['id'], $current_password, $new_password);
                    
                    if ($result['success']) {
                        $success = $result['message'];
                    } else {
                        $error = $result['error'];
                    }
                }
            }
        }
        
        // Load profile view
        $data = [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'page_title' => 'Profile - horizn_ Analytics'
        ];
        
        $this->renderView('auth/profile', $data);
    }
    
    /**
     * API endpoint for authentication status
     */
    public function status()
    {
        header('Content-Type: application/json');
        
        $authenticated = Auth::isAuthenticated();
        $user = $authenticated ? Auth::user() : null;
        
        echo json_encode([
            'authenticated' => $authenticated,
            'user' => $user
        ]);
    }
    
    /**
     * API endpoint for login
     */
    public function loginApi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $login_type = $input['type'] ?? 'password';
        
        if ($login_type === 'magic') {
            $result = Auth::sendMagicLink($email);
        } else {
            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }
            
            $result = Auth::login($email, $password);
        }
        
        if (!$result['success']) {
            http_response_code(401);
        }
        
        echo json_encode($result);
    }
    
    /**
     * Render view with layout
     */
    private function renderView(string $view, array $data = [])
    {
        // Extract data for view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $view_file = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<h1>Error</h1><p>View not found: {$view}</p>";
        }
        
        // Get the view content
        $content = ob_get_clean();
        
        // Include the layout
        include APP_PATH . '/views/layout.php';
    }
    
    /**
     * Redirect with error message in session
     */
    private function redirectWithError(string $url, string $error)
    {
        Auth::startSession();
        $_SESSION['flash_error'] = $error;
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Show magic link sent page
     */
    public function magicLinkSent()
    {
        // Render the magic link sent view
        $this->renderView('auth/magic-link-sent');
    }
    
    /**
     * Verify magic link token
     */
    public function verify()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            header('Location: /auth/login?error=invalid_token');
            exit;
        }
        
        // Show processing page first
        $this->renderView('auth/verify-magic-link', ['status' => 'processing']);
        
        // Process the magic link in the background via JavaScript redirect
        echo '<script>setTimeout(() => { window.location.href = "/auth/magic?token=' . urlencode($token) . '"; }, 1500);</script>';
    }
    
    /**
     * Admin account setup
     */
    public function setup()
    {
        // Check if any users exist
        $users_exist = Database::selectOne("SELECT COUNT(*) as count FROM users");
        if ($users_exist && $users_exist['count'] > 0) {
            header('Location: /auth/login');
            exit;
        }
        
        $error = null;
        $success = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->setupAdmin([
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'username' => trim($_POST['username'] ?? '')
            ]);
            
            if ($result['success']) {
                // Store email and redirect to magic link sent
                $_SESSION['magic_link_email'] = $_POST['email'];
                header('Location: /auth/magic-link-sent');
                exit;
            } else {
                $error = $result['error'];
            }
        }
        
        $this->renderView('auth/setup', [
            'error' => $error,
            'success' => $success,
            'page_title' => 'Setup - horizn_ Analytics'
        ]);
    }
    
    /**
     * Request magic link
     */
    private function requestMagicLink(string $email): array
    {
        if (empty($email)) {
            return [
                'success' => false,
                'error' => 'Email address is required.'
            ];
        }
        
        // Rate limiting check
        if (!$this->checkRateLimit('magic_link', $email)) {
            return [
                'success' => false,
                'error' => 'Too many requests. Please wait before requesting another link.'
            ];
        }
        
        // Find user by email
        $user = Database::selectOne(
            "SELECT id, email, first_name, last_name, is_active FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            // Don't reveal if email exists for security, but still return success
            return [
                'success' => true,
                'message' => 'If an account with that email exists, a login link has been sent.'
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
                Auth::getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
        
        if (!$link_id) {
            return [
                'success' => false,
                'error' => 'Failed to generate login link. Please try again.'
            ];
        }
        
        // Generate magic link URL
        $config = require CONFIG_PATH . '/app.php';
        $magic_link = ($config['app']['url'] ?? 'http://localhost') . '/auth/verify?token=' . $token;
        
        // Check if this is first login
        $isFirstLogin = $user['first_login'] ?? true;
        
        // Send email
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
                'error' => 'Failed to send login link. Please try again.'
            ];
        }
    }
    
    /**
     * Verify magic link token
     */
    public function verifyMagicLink(string $token): array
    {
        if (empty($token)) {
            return [
                'success' => false,
                'error' => 'Invalid token.'
            ];
        }
        
        // Find magic link
        $magic_link = Database::selectOne(
            "SELECT ml.*, u.id as user_id, u.username, u.email, u.first_name, u.last_name, u.is_active, u.first_login 
             FROM magic_links ml 
             JOIN users u ON ml.user_id = u.id 
             WHERE ml.token = ? AND ml.is_used = 0 AND ml.expires_at > NOW()",
            [$token]
        );
        
        if (!$magic_link) {
            return [
                'success' => false,
                'error' => 'Invalid or expired login link.'
            ];
        }
        
        if (!$magic_link['is_active']) {
            return [
                'success' => false,
                'error' => 'Account is not active.'
            ];
        }
        
        // Mark magic link as used
        Database::update(
            "UPDATE magic_links SET is_used = 1, used_at = NOW() WHERE id = ?",
            [$magic_link['id']]
        );
        
        // Create user session
        Auth::startSession();
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $magic_link['user_id'];
        $_SESSION['user_authenticated'] = true;
        $_SESSION['user_email'] = $magic_link['email'];
        $_SESSION['user_username'] = $magic_link['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
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
            'user' => [
                'id' => $magic_link['user_id'],
                'username' => $magic_link['username'],
                'email' => $magic_link['email'],
                'first_name' => $magic_link['first_name'],
                'last_name' => $magic_link['last_name']
            ],
            'redirect' => '/dashboard'
        ];
    }
    
    /**
     * Setup admin account
     */
    private function setupAdmin(array $data): array
    {
        // Validate required fields
        $required = ['first_name', 'last_name', 'email', 'username'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
                ];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'Invalid email address.'
            ];
        }
        
        // Validate username
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username']) || strlen($data['username']) < 3) {
            return [
                'success' => false,
                'error' => 'Username must be at least 3 characters and contain only letters, numbers, hyphens, and underscores.'
            ];
        }
        
        try {
            // Check if any users already exist
            $existing_users = Database::selectOne("SELECT COUNT(*) as count FROM users");
            if ($existing_users && $existing_users['count'] > 0) {
                return [
                    'success' => false,
                    'error' => 'Setup has already been completed.'
                ];
            }
            
            // Create admin user
            $user_id = Database::insert(
                "INSERT INTO users (username, email, password_hash, first_name, last_name, is_active, first_login, created_at) 
                 VALUES (?, ?, ?, ?, ?, 1, 1, NOW())",
                [
                    $data['username'],
                    $data['email'],
                    password_hash('temp_password_' . bin2hex(random_bytes(16)), PASSWORD_DEFAULT), // Temp password, won't be used
                    $data['first_name'],
                    $data['last_name']
                ]
            );
            
            if (!$user_id) {
                return [
                    'success' => false,
                    'error' => 'Failed to create admin account.'
                ];
            }
            
            // Send magic link for first login
            $user = [
                'id' => $user_id,
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'first_login' => true
            ];
            
            // Generate magic link
            $token = bin2hex(random_bytes(64));
            $expires = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            
            Database::insert(
                "INSERT INTO magic_links (user_id, token, email, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $user_id,
                    $token,
                    $data['email'],
                    $expires,
                    Auth::getClientIP(),
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]
            );
            
            // Send setup email
            $config = require CONFIG_PATH . '/app.php';
            $magic_link = ($config['app']['url'] ?? 'http://localhost') . '/auth/verify?token=' . $token;
            
            $email_sent = Mail::sendMagicLink($user, $magic_link, true);
            
            if (!$email_sent) {
                return [
                    'success' => false,
                    'error' => 'Account created but failed to send setup email. Please contact support.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Admin account created! Check your email for the setup link.'
            ];
            
        } catch (Exception $e) {
            error_log("Setup error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create admin account. Please try again.'
            ];
        }
    }
    
    /**
     * Rate limiting for actions
     */
    private function checkRateLimit(string $action, string $identifier): bool
    {
        $key = "rate_limit_{$action}_" . hash('md5', $identifier);
        $max_attempts = 3; // Lower for magic links
        $window = 300; // 5 minutes
        
        Auth::startSession();
        
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
     * Get flash messages from session
     */
    private function getFlashMessages(): array
    {
        Auth::startSession();
        
        $messages = [];
        
        if (isset($_SESSION['flash_error'])) {
            $messages['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }
        
        if (isset($_SESSION['flash_success'])) {
            $messages['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        
        return $messages;
    }
}
?>
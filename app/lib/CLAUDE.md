# Library Folder - Core Utilities

## Purpose
Contains core library classes and utility functions for the horizn_ analytics platform including database connections, authentication, and common helper functions.

## Rules
- **Reusable Code**: All shared functionality goes here
- **Single Responsibility**: Each class/function has one clear purpose
- **Documentation**: Comprehensive PHPDoc comments
- **Error Handling**: Robust error handling and logging
- **Testing**: Unit testable functions and classes

## Core Library Files
```
Database.php         # Database connection and query builder
Auth.php            # Authentication and session management
Validator.php       # Input validation utilities
Security.php        # Security-related functions
Analytics.php       # Analytics calculation utilities
Config.php          # Configuration management
Logger.php          # Error and event logging
Cache.php           # Caching layer (if implemented)
```

## Database Class Pattern
```php
<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = Config::get('database');
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        
        $this->connection = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }
}
```

## Authentication Class Pattern
```php
<?php
class Auth {
    private static $sessionStarted = false;
    
    public static function login(string $username, string $password): bool {
        self::ensureSession();
        
        $user = UserModel::getByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Update last login
            UserModel::updateLastLogin($user['id']);
            
            return true;
        }
        
        return false;
    }
    
    public static function isLoggedIn(): bool {
        self::ensureSession();
        return isset($_SESSION['user_id']);
    }
    
    public static function logout(): void {
        self::ensureSession();
        session_destroy();
    }
    
    private static function ensureSession(): void {
        if (!self::$sessionStarted) {
            session_start();
            self::$sessionStarted = true;
        }
    }
}
```

## Security Utilities
```php
<?php
class Security {
    /**
     * Hash an IP address for privacy compliance
     */
    public static function hashIp(string $ip): string {
        $salt = Config::get('security.ip_salt');
        return hash('sha256', $ip . $salt);
    }
    
    /**
     * Generate a secure session ID
     */
    public static function generateSessionId(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate tracking code for sites
     */
    public static function generateTrackingCode(): string {
        return 'hz_' . bin2hex(random_bytes(12));
    }
}
```

## Analytics Utilities
```php
<?php
class Analytics {
    /**
     * Calculate bounce rate from session data
     */
    public static function calculateBounceRate(array $sessions): float {
        if (empty($sessions)) {
            return 0.0;
        }
        
        $bounces = array_filter($sessions, fn($session) => $session['is_bounce']);
        return (count($bounces) / count($sessions)) * 100;
    }
    
    /**
     * Parse user agent for browser/OS detection
     */
    public static function parseUserAgent(string $userAgent): array {
        // Implementation for browser/OS detection
        // Return array with browser, os, device_type
        return [
            'browser' => 'Chrome', // Detected browser
            'os' => 'Windows',     // Detected OS
            'device_type' => 'desktop' // desktop/mobile/tablet
        ];
    }
    
    /**
     * Anonymize referrer URL for privacy
     */
    public static function anonymizeReferrer(string $referrer): string {
        $parsed = parse_url($referrer);
        return $parsed['host'] ?? 'direct';
    }
}
```

## Validator Class
```php
<?php
class Validator {
    /**
     * Validate email address
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate domain name
     */
    public static function domain(string $domain): bool {
        return filter_var($domain, FILTER_VALIDATE_DOMAIN) !== false;
    }
    
    /**
     * Validate URL
     */
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate tracking data
     */
    public static function trackingData(array $data): bool {
        $required = ['site_id', 'page_url', 'session_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}
```

## Logger Class
```php
<?php
class Logger {
    private static $logFile = null;
    
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    private static function log(string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message} {$contextStr}" . PHP_EOL;
        
        if (self::$logFile === null) {
            self::$logFile = APP_ROOT . '/logs/app.log';
        }
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
```

## Primary Agents
- backend-architect
- rapid-prototyper
- test-writer-fixer
- infrastructure-maintainer

## Testing Requirements
- Unit tests for all utility functions
- Mock external dependencies
- Test edge cases and error conditions
- Validate security functions thoroughly
- Performance testing for database operations

## Performance Considerations
- Use static methods for stateless utilities
- Implement caching where appropriate
- Optimize database connection management
- Monitor memory usage for large operations
- Profile critical path functions
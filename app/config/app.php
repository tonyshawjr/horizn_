<?php
/**
 * horizn_ Analytics Platform - Application Configuration
 */

return [
    
    'security' => [
        'session_lifetime' => 1800, // 30 minutes
        'session_name' => 'horizn_session',
        'csrf_token_name' => '_token',
        'password_hash_algo' => PASSWORD_ARGON2ID,
        'password_min_length' => 8,
        'ip_salt' => $_ENV['IP_SALT'] ?? 'change-this-salt-in-production',
        'session_secure' => filter_var($_ENV['SESSION_SECURE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'session_httponly' => true,
        'session_samesite' => 'Strict',
    ],
    
    'analytics' => [
        'session_timeout' => 1800, // 30 minutes
        'max_events_per_session' => 1000,
        'enable_realtime' => true,
        'realtime_cleanup_interval' => 300, // 5 minutes
        'data_retention_days' => 365,
        'enable_geolocation' => true,
        'bounce_threshold_seconds' => 10,
    ],
    
    'tracking' => [
        'script_name' => 'data.js',
        'pixel_name' => 'pixel.png',
        'batch_endpoint' => 'analytics.css',
        'max_batch_size' => 50,
        'tracking_timeout' => 5000, // 5 seconds
    ],
    
    'ui' => [
        'theme' => 'dark', // dark or light
        'items_per_page' => 25,
        'chart_colors' => [
            'primary' => '#3b82f6',
            'secondary' => '#8b5cf6',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
        ],
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'datetime_format' => 'Y-m-d H:i:s',
    ],
    
    'cache' => [
        'enabled' => filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file', // file, redis, memcached
        'ttl' => 3600, // 1 hour default
        'prefix' => 'horizn_',
    ],
    
    'mail' => [
        'method' => $_ENV['MAIL_METHOD'] ?? 'mail', // mail, smtp, sendmail
        'smtp' => [
            'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // tls, ssl, none
        ],
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@localhost',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'horizn_ Analytics',
    ],
    
    'app' => [
        'name' => 'horizn_',
        'version' => '0.1.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'timezone' => 'UTC',
        'url' => $_ENV['APP_URL'] ?? 'https://localhost',
        'email' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@localhost',
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => $_ENV['LOG_LEVEL'] ?? 'INFO', // DEBUG, INFO, WARNING, ERROR
        'path' => APP_ROOT . '/logs',
        'max_files' => 30,
        'max_file_size' => 10485760, // 10MB
    ],
    
    'api' => [
        'rate_limit' => [
            'tracking' => 1000, // requests per minute
            'dashboard' => 60,   // requests per minute
            'auth' => 10,        // requests per minute
        ],
        'timeout' => 30, // seconds
        'max_payload_size' => 1048576, // 1MB
    ],
];
?>
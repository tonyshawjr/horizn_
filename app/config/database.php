<?php
/**
 * horizn_ Analytics Platform - Database Configuration
 */

return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'horizn_analytics',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"',
            ],
        ],
        
        // Read replica configuration (if needed)
        'mysql_read' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_READ_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_READ_PORT'] ?? $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'] ?? 'horizn_analytics',
            'username' => $_ENV['DB_READ_USER'] ?? $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_READ_PASS'] ?? $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ],
        ],
    ],
    
    'pool' => [
        'enabled' => filter_var($_ENV['DB_POOL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'min_connections' => $_ENV['DB_POOL_MIN'] ?? 5,
        'max_connections' => $_ENV['DB_POOL_MAX'] ?? 20,
        'idle_timeout' => $_ENV['DB_POOL_IDLE_TIMEOUT'] ?? 3600, // 1 hour
    ],
    
    'performance' => [
        'query_cache' => filter_var($_ENV['DB_QUERY_CACHE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'slow_query_log' => filter_var($_ENV['DB_SLOW_LOG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'slow_query_threshold' => $_ENV['DB_SLOW_THRESHOLD'] ?? 2.0, // seconds
        'max_connections' => $_ENV['DB_MAX_CONNECTIONS'] ?? 100,
    ],
    
    'backup' => [
        'enabled' => filter_var($_ENV['DB_BACKUP_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'path' => APP_ROOT . '/backups',
        'frequency' => $_ENV['DB_BACKUP_FREQUENCY'] ?? 'daily', // hourly, daily, weekly
        'retention_days' => $_ENV['DB_BACKUP_RETENTION'] ?? 30,
        'compress' => filter_var($_ENV['DB_BACKUP_COMPRESS'] ?? true, FILTER_VALIDATE_BOOLEAN),
    ],
    
    'monitoring' => [
        'enabled' => filter_var($_ENV['DB_MONITORING'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'log_queries' => filter_var($_ENV['DB_LOG_QUERIES'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'alert_slow_queries' => filter_var($_ENV['DB_ALERT_SLOW'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'alert_connection_threshold' => $_ENV['DB_ALERT_CONNECTIONS'] ?? 80, // percentage
    ],
];
?>
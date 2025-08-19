<?php
/**
 * Database Migration Script
 * Run this to apply database migrations for magic link authentication
 */

// Define paths
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_PATH . '/config');

// Load configuration
require_once CONFIG_PATH . '/database.php';

// Database connection
try {
    $db_config = require CONFIG_PATH . '/database.php';
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    echo "✓ Connected to database\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if migrations table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'migrations'");
    if ($result->rowCount() === 0) {
        echo "Creating migrations table...\n";
        $pdo->exec("
            CREATE TABLE migrations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                version VARCHAR(20) NOT NULL UNIQUE,
                description TEXT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_version (version)
            )
        ");
        echo "✓ Migrations table created\n";
    }
} catch (PDOException $e) {
    echo "✗ Error creating migrations table: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if migration 002_magic_links has been applied
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM migrations WHERE version = ?");
    $stmt->execute(['002_magic_links']);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "✓ Magic links migration already applied\n";
    } else {
        echo "Applying magic links migration...\n";
        
        // Create magic_links table
        $pdo->exec("
            CREATE TABLE magic_links (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                token VARCHAR(128) UNIQUE NOT NULL,
                email VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                used_at TIMESTAMP NULL,
                is_used BOOLEAN DEFAULT FALSE,
                ip_address VARCHAR(45),
                user_agent TEXT,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_email (email),
                INDEX idx_expires_at (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        
        // Add first_login flag to users table
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN first_login BOOLEAN DEFAULT TRUE AFTER is_active");
        } catch (PDOException $e) {
            // Column might already exist, check if it's a duplicate column error
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
            echo "  - first_login column already exists\n";
        }
        
        // Record migration
        $stmt = $pdo->prepare("INSERT INTO migrations (version, description) VALUES (?, ?)");
        $stmt->execute(['002_magic_links', 'Add magic links table and first_login flag for secure authentication']);
        
        echo "✓ Magic links migration applied successfully\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error applying migration: " . $e->getMessage() . "\n";
    exit(1);
}

// Clean up old magic links
try {
    $pdo->exec("DELETE FROM magic_links WHERE expires_at < NOW()");
    echo "✓ Cleaned up expired magic links\n";
} catch (PDOException $e) {
    echo "  - Note: Could not clean up magic links (table might not exist yet): " . $e->getMessage() . "\n";
}

// Check if funnel migration 003_funnels has been applied
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM migrations WHERE version = ?");
    $stmt->execute(['003_funnels']);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "✓ Funnels migration already applied\n";
    } else {
        echo "Applying funnels migration...\n";
        
        // Read and execute funnel migration SQL
        $migrationFile = __DIR__ . '/migrations/001_create_funnels_tables.sql';
        if (file_exists($migrationFile)) {
            $sql = file_get_contents($migrationFile);
            
            // Split by delimiter to handle stored procedures
            $statements = preg_split('/DELIMITER\s*\/\//i', $sql);
            
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    // Handle regular SQL vs procedure definitions
                    if (strpos($statement, 'CREATE PROCEDURE') !== false) {
                        // For stored procedures, we need to change delimiter
                        $pdo->exec("DELIMITER //");
                        $pdo->exec(trim($statement));
                        $pdo->exec("DELIMITER ;");
                    } else {
                        // Split by semicolon for regular statements
                        $subStatements = array_filter(array_map('trim', explode(';', $statement)));
                        foreach ($subStatements as $subStatement) {
                            if (!empty($subStatement) && !preg_match('/^\s*(DELIMITER|--)/i', $subStatement)) {
                                $pdo->exec($subStatement);
                            }
                        }
                    }
                }
            }
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (version, description) VALUES (?, ?)");
            $stmt->execute(['003_funnels', 'Add funnel analysis tables for conversion tracking']);
            
            echo "✓ Funnels migration applied successfully\n";
        } else {
            echo "✗ Funnel migration file not found: {$migrationFile}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error applying funnel migration: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if custom dashboards migration 004_custom_dashboards has been applied
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM migrations WHERE version = ?");
    $stmt->execute(['004_custom_dashboards']);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "✓ Custom dashboards migration already applied\n";
    } else {
        echo "Applying custom dashboards migration...\n";
        
        // Read and execute custom dashboards migration SQL
        $migrationFile = __DIR__ . '/migrations/002_create_custom_dashboards.sql';
        if (file_exists($migrationFile)) {
            $sql = file_get_contents($migrationFile);
            
            // Split SQL statements and execute them
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^\s*(--|#)/i', $statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Skip if table already exists or other non-critical errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "  - Warning: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
            
            // Record migration
            $stmt = $pdo->prepare("INSERT INTO migrations (version, description) VALUES (?, ?)");
            $stmt->execute(['004_custom_dashboards', 'Add custom dashboard builder functionality with widgets and sharing']);
            
            echo "✓ Custom dashboards migration applied successfully\n";
        } else {
            echo "✗ Custom dashboards migration file not found: {$migrationFile}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error applying custom dashboards migration: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Database migration completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Visit your application to complete the admin setup\n";
echo "2. Configure email settings in your environment variables:\n";
echo "   - MAIL_METHOD (mail, smtp, or sendmail)\n";
echo "   - MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD (for SMTP)\n";
echo "   - MAIL_FROM_ADDRESS and MAIL_FROM_NAME\n";
echo "3. Set APP_URL to your domain for magic link generation\n";
echo "4. Funnel analysis system is now available in the dashboard\n";
echo "5. Custom dashboard builder is now available at /dashboard/custom\n\n";

?>
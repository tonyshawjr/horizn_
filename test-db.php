<?php
/**
 * Database Connection Test for horizn_
 * Upload this to your Hostinger and visit it to test the connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>horizn_ Database Connection Test</h1>";

// Load .env file
$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    die("❌ .env file not found at: " . $envPath);
}

echo "✅ .env file found<br><br>";

// Parse .env
$env = parse_ini_file($envPath);
$_ENV = array_merge($_ENV, $env);

// Show connection details (hide password)
echo "<h2>Connection Details:</h2>";
echo "Host: " . ($_ENV['DB_HOST'] ?? 'not set') . "<br>";
echo "Database: " . ($_ENV['DB_NAME'] ?? 'not set') . "<br>";
echo "Username: " . ($_ENV['DB_USER'] ?? 'not set') . "<br>";
echo "Password: " . (isset($_ENV['DB_PASS']) ? '***hidden***' : 'not set') . "<br><br>";

// Try to connect
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? ''
    );
    
    echo "Connecting with DSN: $dsn<br><br>";
    
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USER'] ?? '',
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>Database connected successfully!</strong><br><br>";
    
    // Check for tables
    echo "<h2>Database Tables:</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Found " . count($tables) . " tables:<br>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Check for users
        try {
            $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
            echo "<br>Users in database: " . $users['count'] . "<br>";
            
            if ($users['count'] == 0) {
                echo "⚠️ No users found - setup will be required<br>";
            } else {
                echo "✅ Users exist - setup already complete<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ Could not check users table: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "⚠️ No tables found. You need to import database/schema.sql<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Database connection failed!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    
    echo "<h2>Common Issues:</h2>";
    echo "<ul>";
    echo "<li>Check your database credentials in .env</li>";
    echo "<li>Make sure the database exists in Hostinger</li>";
    echo "<li>Verify the database user has permissions</li>";
    echo "<li>Check if DB_HOST should be 'localhost' or something else</li>";
    echo "</ul>";
}

echo "<br><hr><br>";
echo "<strong>Next Steps:</strong><br>";
echo "1. If connection failed, fix your .env file<br>";
echo "2. If no tables, import database/schema.sql in phpMyAdmin<br>";
echo "3. Once everything passes, delete this test file<br>";
echo "4. Visit your site to see the setup page<br>";
?>
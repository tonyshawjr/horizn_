<?php
/**
 * Auth Test for horizn_
 * Tests the authentication system and setup check
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bootstrap the application
define('APP_ROOT', __DIR__);
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_PATH . '/config');

// Load .env
$envPath = APP_ROOT . '/.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Load classes
require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Auth.php';

echo "<h1>horizn_ Auth System Test</h1>";

// Test Auth::setupRequired()
echo "<h2>Setup Check:</h2>";
try {
    $setupRequired = Auth::setupRequired();
    if ($setupRequired) {
        echo "✅ Setup is required (no users exist)<br>";
        echo "You should be redirected to /auth/setup<br>";
    } else {
        echo "❌ Setup NOT required (users exist)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking setup: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Check current URL parsing
echo "<h2>URL Routing Test:</h2>";
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$base_path = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($base_path, '', $request_uri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

echo "Request URI: " . htmlspecialchars($request_uri) . "<br>";
echo "Base Path: " . htmlspecialchars($base_path) . "<br>";
echo "Parsed Path: " . htmlspecialchars($path) . "<br>";

$segments = $path ? explode('/', $path) : [];
$controller = $segments[0] ?? 'dashboard';
$action = $segments[1] ?? 'index';

echo "Controller: " . htmlspecialchars($controller) . "<br>";
echo "Action: " . htmlspecialchars($action) . "<br>";

$current_path = $controller . '/' . $action;
echo "Current Path: " . htmlspecialchars($current_path) . "<br><br>";

// Check if this would cause redirect
if ($current_path !== 'auth/setup' && $setupRequired) {
    echo "⚠️ This would trigger redirect to /auth/setup<br>";
} else {
    echo "✅ No redirect would occur<br>";
}

echo "<br><hr><br>";
echo "<strong>Test different URLs:</strong><br>";
echo "<a href='/auth/setup'>/auth/setup</a> - Should NOT redirect<br>";
echo "<a href='/dashboard'>/dashboard</a> - Should redirect if no users<br>";
echo "<a href='/'>/</a> - Should redirect if no users<br>";
?>
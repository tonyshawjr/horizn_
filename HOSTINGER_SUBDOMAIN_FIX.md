# 🔧 Hostinger Subdomain Setup (Everything in public_html/horizn/)

## The Problem
Hostinger subdomains require ALL files inside `public_html/horizn/`, so we need to adjust the structure.

## The Solution: Put Everything Inside and Fix Paths

### Step 1: Upload Structure
```
public_html/
└── horizn/                    (your subdomain root)
    ├── app/                   (upload entire /app/ folder here)
    ├── database/              (upload entire /database/ folder here)
    ├── assets/                (from /public/assets/)
    ├── .env                   (create this)
    ├── .htaccess              (from /public/)
    ├── index.php              (modified - see below)
    ├── h.js                   (from /public/)
    ├── i.php                  (modified - see below)
    └── data.js                (from /public/)
```

### Step 2: Fix index.php
**Create new `/public_html/horizn/index.php`:**
```php
<?php
session_start();

// Fix paths for subdomain structure
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

// Load configuration
require_once APP_PATH . '/config/app.php';
require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Auth.php';

// Get the route
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Parse route parts
$parts = explode('/', $route);
$section = $parts[0] ?? '';

// Redirect to setup if needed
$auth = new Auth();
if (!$auth->setupComplete() && $route !== 'auth/setup') {
    header('Location: /auth/setup');
    exit;
}

// Public routes (no auth required)
$publicRoutes = ['auth', 'api/live', 'api/stats'];
$isPublicRoute = in_array($section, $publicRoutes) || in_array($route, ['', 'auth/login']);

// Check authentication for protected routes
if (!$isPublicRoute && !$auth->isLoggedIn()) {
    header('Location: /auth/login');
    exit;
}

// Route to appropriate controller
switch ($section) {
    case '':
    case 'dashboard':
        require_once APP_PATH . '/controllers/DashboardController.php';
        $controller = new DashboardController();
        
        if (isset($parts[1])) {
            $method = $parts[1];
            if (method_exists($controller, $method)) {
                $controller->$method();
            } else {
                $controller->index();
            }
        } else {
            $controller->index();
        }
        break;
        
    case 'auth':
        require_once APP_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $action = $parts[1] ?? 'login';
        
        switch ($action) {
            case 'setup':
                $controller->setup();
                break;
            case 'login':
                $controller->login();
                break;
            case 'logout':
                $controller->logout();
                break;
            case 'magic-link':
                $controller->requestMagicLink();
                break;
            case 'verify':
                $controller->verify();
                break;
            default:
                $controller->login();
        }
        break;
        
    case 'sites':
        require_once APP_PATH . '/controllers/SiteController.php';
        $controller = new SiteController();
        $controller->index();
        break;
        
    case 'journey':
        require_once APP_PATH . '/controllers/JourneyController.php';
        $controller = new JourneyController();
        $action = $parts[1] ?? 'index';
        $controller->$action($parts[2] ?? null);
        break;
        
    case 'funnels':
        require_once APP_PATH . '/controllers/FunnelController.php';
        $controller = new FunnelController();
        $action = $parts[1] ?? 'index';
        $controller->$action();
        break;
        
    case 'api':
        header('Content-Type: application/json');
        
        $apiSection = $parts[1] ?? '';
        $apiAction = $parts[2] ?? '';
        
        switch ($apiSection) {
            case 'live':
                require_once APP_PATH . '/controllers/ApiLiveController.php';
                $controller = new ApiLiveController();
                $controller->handle($apiAction);
                break;
                
            case 'stats':
                require_once APP_PATH . '/controllers/ApiStatsController.php';
                $controller = new ApiStatsController();
                $controller->handle($apiAction);
                break;
        }
        break;
        
    default:
        // 404
        http_response_code(404);
        echo "Page not found";
}
?>
```

### Step 3: Fix i.php (Ingest Endpoint)
**Create new `/public_html/horizn/i.php`:**
```php
<?php
// Fix paths for subdomain
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Tracker.php';

// Handle CORS for tracking
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get tracking data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Fallback for pixel tracking
if (!$data && isset($_GET['ns'])) {
    $data = [
        'k' => $_GET['k'] ?? null,
        'u' => $_COOKIE['h_uid'] ?? null,
        't' => $_GET['t'] ?? 'pageview',
        'url' => $_SERVER['HTTP_REFERER'] ?? null,
        'p' => parse_url($_SERVER['HTTP_REFERER'] ?? '/', PHP_URL_PATH)
    ];
}

// Process tracking event
if ($data && !empty($data['k'])) {
    $tracker = new Tracker();
    $tracker->track($data);
}

// Return empty response
http_response_code(204);
?>
```

### Step 4: Fix All Controller Paths
**Create `/public_html/horizn/app/config/bootstrap.php`:**
```php
<?php
// Central bootstrap file to fix all paths
define('BASE_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');

// Autoload function for classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/lib/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load environment
$envPath = BASE_PATH . '/.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        if (!defined($key)) {
            define($key, $value);
        }
    }
}
?>
```

### Step 5: Update .env
**Create `/public_html/horizn/.env`:**
```env
# IMPORTANT: Update APP_URL to your subdomain
APP_URL=https://horizn.yourdomain.com
APP_ENV=production
APP_DEBUG=false

# Database (from Hostinger panel)
DB_HOST=localhost
DB_DATABASE=u123456789_horizn
DB_USERNAME=u123456789_horizn
DB_PASSWORD=your-password

# Email
MAIL_DRIVER=mail
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="horizn_ Analytics"

# Paths (important for subdomain)
BASE_PATH=/home/u123456789/domains/yourdomain.com/public_html/horizn
PUBLIC_PATH=/home/u123456789/domains/yourdomain.com/public_html/horizn
```

### Step 6: Fix View Paths
**Update `/app/views/layout.php`** (and all views):
```php
<!-- Change asset paths from -->
<link href="/assets/css/main.css" rel="stylesheet">

<!-- To -->
<link href="<?= $_ENV['APP_URL'] ?>/assets/css/main.css" rel="stylesheet">
```

### Step 7: Update Tracking Script
When you generate tracking code, it should use full URLs:
```javascript
<script>
!function(){
  var siteKey = "YOUR-SITE-KEY";
  var ep = "https://horizn.yourdomain.com/i.php"; // Full URL to subdomain
  
  // ... rest of tracking script
}();
</script>
```

---

## Quick One-File Test

Want to test if it works? Create this single file:

**`/public_html/horizn/test.php`:**
```php
<?php
// Quick test file
echo "<h1>horizn_ Subdomain Test</h1>";

// Test environment
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✅ .env found<br>";
    $env = parse_ini_file($envPath);
    
    // Test database
    try {
        $pdo = new PDO(
            "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']}", 
            $env['DB_USERNAME'], 
            $env['DB_PASSWORD']
        );
        echo "✅ Database connected<br>";
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ .env not found<br>";
}

// Test file structure
$checks = [
    '/app/config/app.php' => 'App config',
    '/app/lib/Database.php' => 'Database lib',
    '/assets/css/main.css' => 'CSS file',
    '/h.js' => 'Tracking script'
];

foreach ($checks as $file => $name) {
    if (file_exists(__DIR__ . $file)) {
        echo "✅ $name found<br>";
    } else {
        echo "❌ $name missing at " . __DIR__ . $file . "<br>";
    }
}
?>
```

Visit `https://horizn.yourdomain.com/test.php` to verify everything is in place.

---

## Alternative: Simple Symlink Solution

If your Hostinger allows SSH:

```bash
# SSH into your server
cd ~/domains/yourdomain.com/

# Put app files outside public_html
mkdir horizn_app
cp -r /path/to/app horizn_app/
cp -r /path/to/database horizn_app/

# Create symlinks in public_html/horizn/
cd public_html/horizn/
ln -s ../../horizn_app/app app
ln -s ../../horizn_app/database database
```

This keeps sensitive files outside the web root!

---

## Summary

The key changes for subdomain deployment:
1. Everything goes inside `/public_html/horizn/`
2. Use `define()` constants for paths
3. Update all asset URLs to use full paths
4. Fix require_once paths to use `__DIR__`
5. Update .env with correct APP_URL

This structure will work perfectly on Hostinger subdomains!
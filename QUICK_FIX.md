# 🔧 Quick Fixes for Immediate Deployment

## Critical Path Fixes

Since you want to deploy RIGHT NOW, here are the essential fixes needed:

### 1. Fix Database Connection Path
The app expects the database config to find the .env file correctly.

**Edit `/app/config/database.php`** line 3-10:
```php
// Update the base path reference
$envPath = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}
```

### 2. Fix Include Paths in Controllers
Since Hostinger's structure is different, update these files:

**Edit `/app/controllers/AuthController.php`** line 2-4:
```php
require_once dirname(__DIR__) . '/lib/Auth.php';
require_once dirname(__DIR__) . '/lib/Database.php';
require_once dirname(__DIR__) . '/models/User.php';
```

### 3. Fix Public Index Router
**Edit `/public/index.php`** line 5:
```php
require_once dirname(__DIR__) . '/app/config/app.php';
```

### 4. Create Simple Installer
**Create `/public/install.php`**:
```php
<?php
// One-time installer for Hostinger
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>horizn_ Quick Installer</h1>";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("❌ PHP 7.4+ required. You have: " . PHP_VERSION);
}

// Check for .env
$envPath = dirname(__DIR__) . '/.env';
if (!file_exists($envPath)) {
    echo "❌ .env file not found. Create it from .env.example<br>";
    echo "Path checked: " . $envPath . "<br>";
} else {
    echo "✅ .env file found<br>";
}

// Test database connection
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    try {
        $pdo = new PDO(
            "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']}", 
            $env['DB_USERNAME'], 
            $env['DB_PASSWORD']
        );
        echo "✅ Database connected<br>";
        
        // Check tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Tables found: " . count($tables) . "<br>";
        
        if (count($tables) == 0) {
            echo "⚠️ No tables found. Import schema.sql in phpMyAdmin<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
}

// Check file structure
$required = [
    '../app/config/app.php' => 'App config',
    '../app/lib/Database.php' => 'Database class',
    '../app/controllers/AuthController.php' => 'Auth controller',
    'assets/css/main.css' => 'CSS assets',
    'h.js' => 'Tracking script',
    'i.php' => 'Ingest endpoint'
];

foreach ($required as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ $name found<br>";
    } else {
        echo "❌ $name missing<br>";
    }
}

echo "<br><strong>If all checks pass, delete this file and visit your site!</strong>";
?>
```

### 5. Simplified .htaccess
**Replace `/public/.htaccess`** with:
```apache
# Basic horizn_ htaccess for Hostinger
RewriteEngine On

# Security headers
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

# Ad-blocker resistant endpoints
RewriteRule ^assets/data\.css$ /i.php [L]
RewriteRule ^wp-content/themes/assets/app\.js$ /i.php [L]
RewriteRule ^static/pixel\.png$ /i.php [L]

# Route all non-file requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Cache static assets
<FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js)$">
    Header set Cache-Control "max-age=604800, public"
</FilesMatch>

# Prevent .env access
<Files .env>
    Order allow,deny
    Deny from all
</Files>
```

---

## Super Quick Deployment (5 minutes)

### If you just want it working NOW:

1. **Upload these folders to Hostinger:**
   - `/app/` → to your domain root (not public_html)
   - `/database/` → to your domain root
   - Contents of `/public/` → INTO public_html

2. **Create database in Hostinger panel**

3. **Import `schema.sql` in phpMyAdmin**

4. **Create `.env` file** with your database info

5. **Visit `https://yourdomain.com/install.php`** to verify

6. **Delete install.php**

7. **Visit your site** - you'll see the setup page!

---

## Even Quicker: Minimal Test

Just to see if it works:

1. Upload ONLY:
   - `/public/h.js` 
   - `/public/i.php`
   - Edit `i.php` to just log to a file:

```php
<?php
// Super simple test tracker
$data = file_get_contents('php://input');
file_put_contents('tracking.log', date('Y-m-d H:i:s') . ' - ' . $data . "\n", FILE_APPEND);
http_response_code(204);
?>
```

2. Add to any website:
```html
<script src="https://yourdomain.com/h.js"></script>
```

3. Check if `tracking.log` gets created with data!

This proves the tracking works, then you can set up the full dashboard.
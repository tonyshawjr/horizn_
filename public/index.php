<?php
/**
 * horizn_ Analytics Platform
 * Main Entry Point
 * 
 * First-party, ad-blocker resistant analytics platform
 * with crypto/saas aesthetic and dark mode first design.
 */

// Start session and output buffering
session_start();
ob_start();

// Define application constants - works both with app beside or above public
if (file_exists(__DIR__ . '/app')) {
    // Hostinger subdomain setup - everything at same level
    define('APP_ROOT', __DIR__);
} else {
    // Standard setup - app folder one level up
    define('APP_ROOT', dirname(__DIR__));
}
define('PUBLIC_ROOT', __DIR__);
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_PATH . '/config');

// Load configuration
require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';

// Auto-loader for classes
spl_autoload_register(function ($class_name) {
    $directories = [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/lib/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Basic routing
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = str_replace(dirname($script_name), '', $request_uri);
$path = trim($path, '/');

// Parse the path
$segments = explode('/', $path);
$controller = !empty($segments[0]) ? $segments[0] : 'dashboard';
$action = !empty($segments[1]) ? $segments[1] : 'index';

// Handle API routes
if ($controller === 'api') {
    header('Content-Type: application/json');
    $api_controller = !empty($segments[1]) ? $segments[1] : 'stats';
    $api_action = !empty($segments[2]) ? $segments[2] : 'overview';
    
    // Handle special agency-stats route
    if ($api_controller === 'live' && $api_action === 'agency-stats') {
        $api_action = 'agencyStats';
    }
    
    // Handle journey API routes
    if ($api_controller === 'journeys') {
        $journey_controller = new JourneyController();
        if ($api_action === 'live') {
            $journey_controller->api_live();
        } elseif ($api_action === 'detail' && !empty($segments[3])) {
            $journey_controller->api_detail($segments[3]);
        } elseif ($api_action === 'export' && !empty($segments[3])) {
            $journey_controller->export($segments[3]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Journey API endpoint not found']);
        }
        exit;
    }
    
    // Handle funnel API routes
    if ($api_controller === 'funnels') {
        $funnel_controller = new FunnelController();
        if ($api_action === 'save') {
            $funnel_controller->save();
        } elseif ($api_action === 'create') {
            $funnel_controller->create();
        } elseif ($api_action === 'update') {
            $funnel_controller->update();
        } elseif ($api_action === 'delete') {
            $funnel_controller->delete();
        } elseif ($api_action === 'analyze' && !empty($segments[3])) {
            $_GET['id'] = $segments[3];
            $funnel_controller->analyze();
        } elseif ($api_action === 'performance' && !empty($segments[3])) {
            $_GET['id'] = $segments[3];
            $funnel_controller->performance();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Funnel API endpoint not found']);
        }
        exit;
    }
    
    $controller_class = 'Api' . ucfirst($api_controller) . 'Controller';
    
    if (class_exists($controller_class)) {
        $controller_instance = new $controller_class();
        if (method_exists($controller_instance, $api_action)) {
            $controller_instance->$api_action();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API controller not found']);
    }
    exit;
}

// Handle tracking routes
if ($controller === 'track') {
    $tracking_controller = new TrackingController();
    
    switch ($action) {
        case 'pageview':
            $tracking_controller->pageview();
            break;
        case 'event':
            $tracking_controller->event();
            break;
        case 'pixel':
            $tracking_controller->pixel();
            break;
        case 'batch':
            $tracking_controller->batch();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Tracking endpoint not found']);
    }
    exit;
}

// Handle setup redirect - check if setup is required
if ($controller !== 'auth' && Auth::setupRequired()) {
    header('Location: /auth/setup');
    exit;
}

// Handle special auth routes
if ($controller === 'auth') {
    $auth_controller = new AuthController();
    
    switch ($action) {
        case 'login':
            $auth_controller->login();
            break;
        case 'logout':
            $auth_controller->logout();
            break;
        case 'setup':
            $auth_controller->setup();
            break;
        case 'magic':
            $auth_controller->magic();
            break;
        case 'verify':
            $auth_controller->verify();
            break;
        case 'magic-link-sent':
            $auth_controller->magicLinkSent();
            break;
        case 'profile':
            $auth_controller->profile();
            break;
        case 'status':
            $auth_controller->status();
            break;
        default:
            // Default to login for auth controller
            $auth_controller->login();
            break;
    }
    exit;
}

// Require authentication for all non-auth pages (except setup)
if (!Auth::isAuthenticated() && $controller !== 'auth') {
    header('Location: /auth/login');
    exit;
}

// Handle special journey routes
if ($controller === 'journey' || $controller === 'journeys') {
    $journey_controller = new JourneyController();
    
    if ($action === 'detail' && !empty($segments[2])) {
        // Handle /journey/detail/{person_id}
        $journey_controller->detail($segments[2]);
    } elseif ($action === 'merge') {
        // Handle /journey/merge (POST)
        $journey_controller->merge();
    } elseif ($action === 'export' && !empty($segments[2])) {
        // Handle /journey/export/{person_id}
        $journey_controller->export($segments[2]);
    } else {
        // Default to journey index
        $journey_controller->index();
    }
    exit;
}

// Handle funnel routes
if ($controller === 'funnel' || $controller === 'funnels') {
    $funnel_controller = new FunnelController();
    
    if ($action === 'builder') {
        // Handle /funnel/builder or /funnels/builder
        $funnel_controller->builder();
    } else {
        // Default to funnel index
        $funnel_controller->index();
    }
    exit;
}

// Handle custom dashboard routes
if ($controller === 'dashboard' && $action === 'custom') {
    $custom_dashboard_controller = new CustomDashboardController();
    
    // Get the third segment for sub-actions
    $sub_action = !empty($segments[2]) ? $segments[2] : 'index';
    
    switch ($sub_action) {
        case 'builder':
            $custom_dashboard_controller->builder();
            break;
        case 'save':
            $custom_dashboard_controller->save();
            break;
        case 'load':
            $custom_dashboard_controller->load();
            break;
        case 'view':
            $custom_dashboard_controller->view();
            break;
        case 'delete':
            $custom_dashboard_controller->delete();
            break;
        case 'share':
            $custom_dashboard_controller->share();
            break;
        case 'widget-data':
            $custom_dashboard_controller->widgetData();
            break;
        default:
            $custom_dashboard_controller->index();
            break;
    }
    exit;
}

// Handle regular page controllers
$controller_class = ucfirst($controller) . 'Controller';

if (class_exists($controller_class)) {
    $controller_instance = new $controller_class();
    if (method_exists($controller_instance, $action)) {
        $controller_instance->$action();
    } else {
        // Default to index if action doesn't exist
        if (method_exists($controller_instance, 'index')) {
            $controller_instance->index();
        } else {
            // 404 error
            http_response_code(404);
            include APP_PATH . '/views/errors/404.php';
        }
    }
} else {
    // Default to dashboard if controller doesn't exist
    if (class_exists('DashboardController')) {
        $controller_instance = new DashboardController();
        $controller_instance->index();
    } else {
        // 404 error
        http_response_code(404);
        include APP_PATH . '/views/errors/404.php';
    }
}

// End output buffering
ob_end_flush();
?>
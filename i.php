<?php
/**
 * Primary Ingest Endpoint
 * 
 * Handles tracking requests with multiple fallback methods
 * to ensure ad-blocker resistance.
 */

// Minimal bootstrap for performance
error_reporting(0);
ini_set('display_errors', 0);

// CORS headers for cross-domain requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Security headers
header('X-Robots-Tag: noindex');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Define paths
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_PATH . '/config');

// Load minimal required files
require_once CONFIG_PATH . '/database.php';
require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Auth.php';
require_once APP_PATH . '/lib/Tracker.php';

// Initialize response
$response = ['success' => false, 'error' => 'Invalid request'];

try {
    $request_method = $_SERVER['REQUEST_METHOD'];
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Parse incoming data based on request method and content type
    $data = null;
    
    if ($request_method === 'POST') {
        if (strpos($content_type, 'application/json') !== false) {
            // JSON request
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);
        } else {
            // Form data
            $data = $_POST;
        }
    } elseif ($request_method === 'GET') {
        // GET request (pixel tracking)
        $data = $_GET;
        
        // For pixel requests, return 1x1 transparent GIF
        if (empty($data['json'])) {
            header('Content-Type: image/gif');
            header('Content-Length: 43');
            
            // Process tracking data from query parameters
            processPixelRequest($data);
            
            // Return 1x1 transparent GIF
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            exit;
        } else {
            // JSON data in GET parameter
            $data = json_decode($data['json'], true);
        }
    }
    
    if (!$data || !is_array($data)) {
        throw new Exception('Invalid data format');
    }
    
    // Validate required fields
    if (empty($data['site_id']) && empty($data['tracking_code'])) {
        throw new Exception('Site identification required');
    }
    
    // Get site information
    $site = null;
    if (!empty($data['tracking_code'])) {
        $site = Tracker::getSiteByTrackingCode($data['tracking_code']);
    } elseif (!empty($data['site_id']) && is_numeric($data['site_id'])) {
        $site = Database::selectOne(
            "SELECT id, tracking_code FROM sites WHERE id = ? AND is_active = 1",
            [$data['site_id']]
        );
    }
    
    if (!$site) {
        throw new Exception('Invalid site');
    }
    
    // Add site_id to data
    $data['site_id'] = $site['id'];
    
    // Process based on request type
    $type = $data['type'] ?? 'pageview';
    
    switch ($type) {
        case 'pageview':
            $response = processPageview($data);
            break;
            
        case 'event':
            $response = processEvent($data);
            break;
            
        case 'batch':
            $response = processBatch($data);
            break;
            
        default:
            throw new Exception('Unknown request type');
    }
    
} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
    $response = ['success' => false, 'error' => 'Processing failed'];
}

// Return JSON response for AJAX requests
if ($request_method === 'POST' || (!empty($_GET['json']))) {
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // For GET requests without JSON, return minimal response
    header('Content-Type: text/plain');
    echo $response['success'] ? 'OK' : 'ERR';
}

/**
 * Process pageview tracking
 */
function processPageview($data) {
    try {
        // Sanitize and validate pageview data
        $pageview_data = [
            'site_id' => $data['site_id'],
            'session_id' => $data['session_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'page_url' => $data['url'] ?? $data['page_url'] ?? '',
            'page_title' => $data['title'] ?? $data['page_title'] ?? '',
            'referrer' => $data['referrer'] ?? '',
            'load_time' => isset($data['load_time']) ? (int)$data['load_time'] : null,
        ];
        
        // Track the pageview
        $result = Tracker::trackPageview($pageview_data);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Pageview processing error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Pageview tracking failed'];
    }
}

/**
 * Process event tracking
 */
function processEvent($data) {
    try {
        // Sanitize and validate event data
        $event_data = [
            'site_id' => $data['site_id'],
            'session_id' => $data['session_id'],
            'event_name' => $data['event']['name'] ?? $data['name'] ?? '',
            'event_category' => $data['event']['category'] ?? $data['category'] ?? null,
            'event_action' => $data['event']['action'] ?? $data['action'] ?? null,
            'event_label' => $data['event']['label'] ?? $data['label'] ?? null,
            'event_value' => isset($data['event']['value']) ? (int)$data['event']['value'] : 
                           (isset($data['value']) ? (int)$data['value'] : null),
            'event_data' => $data['event']['data'] ?? $data['data'] ?? null,
            'page_url' => $data['url'] ?? $data['page_url'] ?? '',
        ];
        
        // Track the event
        $result = Tracker::trackEvent($event_data);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Event processing error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Event tracking failed'];
    }
}

/**
 * Process batch tracking
 */
function processBatch($data) {
    try {
        if (!isset($data['batch']) || !is_array($data['batch'])) {
            throw new Exception('Invalid batch data');
        }
        
        // Process each item in the batch
        $batch_data = [];
        foreach ($data['batch'] as $item) {
            // Add common data to each item
            $item['site_id'] = $data['site_id'];
            $item['session_id'] = $data['session_id'] ?? null;
            $item['user_id'] = $data['user_id'] ?? null;
            
            $batch_data[] = $item;
        }
        
        // Track the batch
        $result = Tracker::trackBatch($batch_data);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Batch processing error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Batch tracking failed'];
    }
}

/**
 * Process pixel request (GET with query parameters)
 */
function processPixelRequest($data) {
    try {
        // Basic pixel tracking - just record the hit
        if (!empty($data['s'])) { // 's' for site/tracking code
            $site = Tracker::getSiteByTrackingCode($data['s']);
            if ($site) {
                $pageview_data = [
                    'site_id' => $site['id'],
                    'page_url' => $data['u'] ?? '', // 'u' for URL
                    'page_title' => $data['t'] ?? '', // 't' for title
                    'referrer' => $data['r'] ?? '', // 'r' for referrer
                ];
                
                Tracker::trackPageview($pageview_data);
            }
        }
    } catch (Exception $e) {
        // Silently fail for pixel requests to avoid breaking the image
        error_log("Pixel request error: " . $e->getMessage());
    }
}
?>
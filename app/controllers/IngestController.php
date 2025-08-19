<?php
/**
 * Ingest Controller
 * 
 * Processes tracking requests from the JavaScript SDK and other sources.
 * This is the fallback controller for the main i.php endpoint.
 */

class IngestController
{
    /**
     * Handle tracking pageview requests
     */
    public function pageview()
    {
        $this->processTrackingRequest('pageview');
    }
    
    /**
     * Handle tracking event requests
     */
    public function event()
    {
        $this->processTrackingRequest('event');
    }
    
    /**
     * Handle pixel tracking requests
     */
    public function pixel()
    {
        $this->processTrackingRequest('pixel');
    }
    
    /**
     * Handle batch tracking requests
     */
    public function batch()
    {
        $this->processTrackingRequest('batch');
    }
    
    /**
     * Process any type of tracking request
     */
    private function processTrackingRequest(string $type)
    {
        // Set CORS headers
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
        
        try {
            // Parse request data
            $data = $this->parseRequestData();
            
            if (!$data) {
                throw new Exception('Invalid request data');
            }
            
            // Add request type if not specified
            if (!isset($data['type'])) {
                $data['type'] = $type;
            }
            
            // Validate site identification
            if (empty($data['site_id']) && empty($data['tracking_code'])) {
                throw new Exception('Site identification required');
            }
            
            // Get site information
            $site = $this->getSiteInfo($data);
            if (!$site) {
                throw new Exception('Invalid site');
            }
            
            // Add site_id to data
            $data['site_id'] = $site['id'];
            
            // Process based on request type
            $response = $this->processTrackingData($data);
            
            // Return response
            $this->sendResponse($response, $type);
            
        } catch (Exception $e) {
            error_log("Tracking error ({$type}): " . $e->getMessage());
            $this->sendErrorResponse($type);
        }
    }
    
    /**
     * Parse request data from various sources
     */
    private function parseRequestData(): ?array
    {
        $request_method = $_SERVER['REQUEST_METHOD'];
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if ($request_method === 'POST') {
            if (strpos($content_type, 'application/json') !== false) {
                // JSON request
                $json_input = file_get_contents('php://input');
                return json_decode($json_input, true);
            } else {
                // Form data
                return $_POST;
            }
        } elseif ($request_method === 'GET') {
            // GET request
            if (!empty($_GET['json'])) {
                // JSON data in GET parameter
                return json_decode($_GET['json'], true);
            } else {
                // Query parameters
                return $_GET;
            }
        }
        
        return null;
    }
    
    /**
     * Get site information from tracking data
     */
    private function getSiteInfo(array $data): ?array
    {
        if (!empty($data['tracking_code'])) {
            return Tracker::getSiteByTrackingCode($data['tracking_code']);
        } elseif (!empty($data['site_id']) && is_numeric($data['site_id'])) {
            return Database::selectOne(
                "SELECT id, tracking_code, domain, name FROM sites WHERE id = ? AND is_active = 1",
                [$data['site_id']]
            );
        }
        
        return null;
    }
    
    /**
     * Process tracking data based on type
     */
    private function processTrackingData(array $data): array
    {
        $type = $data['type'] ?? 'pageview';
        
        switch ($type) {
            case 'pageview':
                return $this->processPageview($data);
                
            case 'event':
                return $this->processEvent($data);
                
            case 'batch':
                return $this->processBatch($data);
                
            case 'pixel':
                return $this->processPixel($data);
                
            default:
                throw new Exception('Unknown request type');
        }
    }
    
    /**
     * Process pageview tracking
     */
    private function processPageview(array $data): array
    {
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
        
        return Tracker::trackPageview($pageview_data);
    }
    
    /**
     * Process event tracking
     */
    private function processEvent(array $data): array
    {
        // Handle different event data structures
        $event_info = $data['event'] ?? $data;
        
        $event_data = [
            'site_id' => $data['site_id'],
            'session_id' => $data['session_id'] ?? null,
            'event_name' => $event_info['name'] ?? $data['name'] ?? '',
            'event_category' => $event_info['category'] ?? $data['category'] ?? null,
            'event_action' => $event_info['action'] ?? $data['action'] ?? null,
            'event_label' => $event_info['label'] ?? $data['label'] ?? null,
            'event_value' => isset($event_info['value']) ? (int)$event_info['value'] : 
                           (isset($data['value']) ? (int)$data['value'] : null),
            'event_data' => $event_info['data'] ?? $data['data'] ?? null,
            'page_url' => $data['url'] ?? $data['page_url'] ?? '',
        ];
        
        return Tracker::trackEvent($event_data);
    }
    
    /**
     * Process batch tracking
     */
    private function processBatch(array $data): array
    {
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
        
        return Tracker::trackBatch($batch_data);
    }
    
    /**
     * Process pixel tracking (minimal data)
     */
    private function processPixel(array $data): array
    {
        // Basic pixel tracking - minimal data
        $pageview_data = [
            'site_id' => $data['site_id'],
            'page_url' => $data['u'] ?? $data['url'] ?? '', // 'u' for URL
            'page_title' => $data['t'] ?? $data['title'] ?? '', // 't' for title
            'referrer' => $data['r'] ?? $data['referrer'] ?? '', // 'r' for referrer
        ];
        
        return Tracker::trackPageview($pageview_data);
    }
    
    /**
     * Send response based on request type
     */
    private function sendResponse(array $response, string $type)
    {
        if ($type === 'pixel' || !empty($_GET['img'])) {
            // Return 1x1 transparent GIF for pixel requests
            header('Content-Type: image/gif');
            header('Content-Length: 43');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['json'])) {
            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            // Return minimal text response
            header('Content-Type: text/plain');
            echo $response['success'] ? 'OK' : 'ERR';
        }
    }
    
    /**
     * Send error response
     */
    private function sendErrorResponse(string $type)
    {
        if ($type === 'pixel' || !empty($_GET['img'])) {
            // Return 1x1 transparent GIF even for errors
            header('Content-Type: image/gif');
            header('Content-Length: 43');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['json'])) {
            // Return JSON error
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Processing failed']);
        } else {
            // Return error text
            header('Content-Type: text/plain');
            http_response_code(400);
            echo 'ERR';
        }
    }
    
    /**
     * Handle CORS preflight requests
     */
    public function options()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400');
        http_response_code(204);
        exit;
    }
    
    /**
     * Health check endpoint
     */
    public function health()
    {
        header('Content-Type: application/json');
        
        $health = [
            'status' => 'ok',
            'timestamp' => time(),
            'version' => '0.1.0',
            'database' => Database::testConnection() ? 'connected' : 'error'
        ];
        
        if ($health['database'] === 'error') {
            http_response_code(503);
            $health['status'] = 'error';
        }
        
        echo json_encode($health);
    }
    
    /**
     * Get tracking statistics
     */
    public function stats()
    {
        header('Content-Type: application/json');
        
        try {
            // Basic tracking statistics
            $stats = Database::selectOne(
                "SELECT 
                    COUNT(DISTINCT s.site_id) as total_sites,
                    COUNT(s.id) as total_sessions,
                    COUNT(p.id) as total_pageviews,
                    COUNT(e.id) as total_events,
                    COUNT(rv.id) as active_visitors
                 FROM sessions s
                 LEFT JOIN pageviews p ON s.id = p.session_id
                 LEFT JOIN events e ON s.id = e.session_id
                 LEFT JOIN realtime_visitors rv ON rv.session_id = s.id 
                     AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 WHERE s.first_visit >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            echo json_encode($stats ?: []);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Stats unavailable']);
        }
    }
}
?>
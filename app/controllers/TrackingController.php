<?php
/**
 * Tracking Controller
 * 
 * Handles tracking endpoints for pageviews, events, and funnel processing
 */

class TrackingController
{
    /**
     * Track pageview
     */
    public function pageview(): void
    {
        header('Content-Type: application/json');
        
        try {
            $data = $this->getTrackingData();
            
            // Validate required fields
            if (!isset($data['site_id']) || !isset($data['page_path'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Process pageview using Tracker
            $tracker = new Tracker();
            $session_id = $tracker->trackPageview($data);
            
            // Process funnel data if session exists
            if ($session_id) {
                $funnel_controller = new FunnelController();
                $funnel_controller->processFunnelData($session_id, array_merge($data, [
                    'event_type' => 'pageview',
                    'page_path' => $data['page_path']
                ]));
            }
            
            echo json_encode(['success' => true, 'session_id' => $session_id]);
            
        } catch (Exception $e) {
            error_log("Pageview tracking error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Tracking failed']);
        }
    }
    
    /**
     * Track event
     */
    public function event(): void
    {
        header('Content-Type: application/json');
        
        try {
            $data = $this->getTrackingData();
            
            // Validate required fields
            if (!isset($data['site_id']) || !isset($data['event_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Process event using Tracker
            $tracker = new Tracker();
            $session_id = $tracker->trackEvent($data);
            
            // Process funnel data if session exists
            if ($session_id) {
                $funnel_controller = new FunnelController();
                $funnel_controller->processFunnelData($session_id, array_merge($data, [
                    'event_type' => 'event',
                    'event_name' => $data['event_name']
                ]));
            }
            
            echo json_encode(['success' => true, 'session_id' => $session_id]);
            
        } catch (Exception $e) {
            error_log("Event tracking error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Tracking failed']);
        }
    }
    
    /**
     * Track via pixel (1x1 transparent gif)
     */
    public function pixel(): void
    {
        // Set pixel headers
        header('Content-Type: image/gif');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        try {
            $data = $this->getTrackingData();
            
            if (isset($data['site_id'])) {
                $tracker = new Tracker();
                $session_id = $tracker->trackPageview($data);
                
                // Process funnel data
                if ($session_id) {
                    $funnel_controller = new FunnelController();
                    $funnel_controller->processFunnelData($session_id, array_merge($data, [
                        'event_type' => 'pageview'
                    ]));
                }
            }
        } catch (Exception $e) {
            error_log("Pixel tracking error: " . $e->getMessage());
        }
        
        // Output 1x1 transparent GIF
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
    
    /**
     * Batch tracking endpoint
     */
    public function batch(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['events']) || !is_array($input['events'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid batch data']);
                return;
            }
            
            $tracker = new Tracker();
            $funnel_controller = new FunnelController();
            $processed = 0;
            $session_id = null;
            
            foreach ($input['events'] as $event_data) {
                try {
                    if (isset($event_data['type']) && $event_data['type'] === 'event') {
                        $session_id = $tracker->trackEvent($event_data);
                    } else {
                        $session_id = $tracker->trackPageview($event_data);
                    }
                    
                    // Process funnel data
                    if ($session_id) {
                        $funnel_controller->processFunnelData($session_id, $event_data);
                    }
                    
                    $processed++;
                } catch (Exception $e) {
                    error_log("Batch event error: " . $e->getMessage());
                }
            }
            
            echo json_encode([
                'success' => true,
                'processed' => $processed,
                'total' => count($input['events'])
            ]);
            
        } catch (Exception $e) {
            error_log("Batch tracking error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Batch tracking failed']);
        }
    }
    
    /**
     * Get tracking data from request
     */
    private function getTrackingData(): array
    {
        $data = [];
        
        // Handle POST data
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = file_get_contents('php://input');
            $json_data = json_decode($input, true);
            
            if ($json_data) {
                $data = array_merge($data, $json_data);
            } else {
                $data = array_merge($data, $_POST);
            }
        }
        
        // Handle GET parameters
        $data = array_merge($data, $_GET);
        
        // Add server data
        $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $data['ip_address'] = $this->getClientIP();
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Add referrer
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $data['referrer'] = $_SERVER['HTTP_REFERER'];
        }
        
        return $data;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP(): string
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>
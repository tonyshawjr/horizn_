<?php
/**
 * Live API Controller
 * 
 * Provides real-time analytics data endpoints.
 */

class ApiLiveController
{
    /**
     * Get live visitor count
     */
    public function visitors()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            $count = Tracker::getLiveVisitorCount($site_id);
            
            $this->jsonResponse([
                'active_visitors' => $count,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Live visitors API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch live visitor count');
        }
    }
    
    /**
     * Get current active visitors with details
     */
    public function active()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            $active_visitors = Person::getActiveVisitors($site_id);
            
            $this->jsonResponse([
                'active_visitors' => $active_visitors,
                'count' => count($active_visitors),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Active visitors API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch active visitors');
        }
    }
    
    /**
     * Get recent pageviews
     */
    public function pageviews()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $recent_pageviews = Database::select(
                "SELECT 
                    p.page_url,
                    p.page_title,
                    p.timestamp,
                    s.device_type,
                    s.browser,
                    s.country_code
                 FROM pageviews p
                 JOIN sessions s ON p.session_id = s.id
                 WHERE p.site_id = ?
                 AND p.timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 ORDER BY p.timestamp DESC
                 LIMIT ?",
                [$site_id, $limit]
            );
            
            $this->jsonResponse([
                'recent_pageviews' => $recent_pageviews,
                'count' => count($recent_pageviews),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Recent pageviews API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch recent pageviews');
        }
    }
    
    /**
     * Get recent events
     */
    public function events()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $recent_events = Event::getRealtimeEvents($site_id, $limit);
            
            $this->jsonResponse([
                'recent_events' => $recent_events,
                'count' => count($recent_events),
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Recent events API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch recent events');
        }
    }
    
    /**
     * Get real-time overview stats
     */
    public function overview()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            // Get real-time stats
            $stats = Site::getRealtimeStats($site_id);
            
            // Get recent activity
            $recent_activity = Database::selectOne(
                "SELECT 
                    COUNT(DISTINCT p.id) as pageviews_last_hour,
                    COUNT(DISTINCT e.id) as events_last_hour
                 FROM pageviews p
                 LEFT JOIN events e ON e.site_id = p.site_id 
                     AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 WHERE p.site_id = ?
                 AND p.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                [$site_id]
            );
            
            // Get top current pages
            $top_current_pages = Database::select(
                "SELECT 
                    rv.page_url,
                    rv.page_title,
                    COUNT(*) as active_visitors
                 FROM realtime_visitors rv
                 WHERE rv.site_id = ?
                 AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 GROUP BY rv.page_url, rv.page_title
                 ORDER BY active_visitors DESC
                 LIMIT 5",
                [$site_id]
            );
            
            $response = array_merge($stats, $recent_activity ?: [], [
                'top_current_pages' => $top_current_pages,
                'timestamp' => time()
            ]);
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Real-time overview API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch real-time overview');
        }
    }
    
    /**
     * Get live traffic chart data (last 30 minutes)
     */
    public function traffic()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            // Get pageviews by minute for the last 30 minutes
            $traffic_data = Database::select(
                "SELECT 
                    DATE_FORMAT(timestamp, '%H:%i') as time_label,
                    UNIX_TIMESTAMP(DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:00')) as timestamp,
                    COUNT(*) as pageviews
                 FROM pageviews
                 WHERE site_id = ?
                 AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                 GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i')
                 ORDER BY timestamp ASC",
                [$site_id]
            );
            
            // Fill in missing minutes with zero values
            $filled_data = $this->fillMissingMinutes($traffic_data, 30);
            
            $this->jsonResponse([
                'traffic_data' => $filled_data,
                'period' => '30 minutes',
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Live traffic API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch live traffic data');
        }
    }
    
    /**
     * Get current top referrers
     */
    public function referrers()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            $top_referrers = Database::select(
                "SELECT 
                    COALESCE(s.referrer_domain, '(direct)') as referrer_domain,
                    COUNT(DISTINCT s.id) as sessions,
                    COUNT(DISTINCT rv.session_id) as active_visitors
                 FROM sessions s
                 LEFT JOIN realtime_visitors rv ON s.id = rv.session_id 
                     AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 WHERE s.site_id = ?
                 AND s.first_visit >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 GROUP BY s.referrer_domain
                 ORDER BY active_visitors DESC, sessions DESC
                 LIMIT 10",
                [$site_id]
            );
            
            $this->jsonResponse([
                'top_referrers' => $top_referrers,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Top referrers API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch top referrers');
        }
    }
    
    /**
     * Get device/browser breakdown for current visitors
     */
    public function devices()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        try {
            $device_breakdown = Database::select(
                "SELECT 
                    s.device_type,
                    s.browser,
                    COUNT(DISTINCT rv.session_id) as active_visitors
                 FROM realtime_visitors rv
                 JOIN sessions s ON rv.session_id = s.id
                 WHERE rv.site_id = ?
                 AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                 GROUP BY s.device_type, s.browser
                 ORDER BY active_visitors DESC",
                [$site_id]
            );
            
            // Group by device type
            $devices = [];
            $browsers = [];
            
            foreach ($device_breakdown as $item) {
                $devices[$item['device_type']] = ($devices[$item['device_type']] ?? 0) + $item['active_visitors'];
                $browsers[$item['browser']] = ($browsers[$item['browser']] ?? 0) + $item['active_visitors'];
            }
            
            $this->jsonResponse([
                'devices' => $devices,
                'browsers' => $browsers,
                'detailed_breakdown' => $device_breakdown,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Device breakdown API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch device breakdown');
        }
    }
    
    /**
     * Get agency-wide stats for all user sites
     */
    public function agencyStats()
    {
        if (!$this->authenticate()) return;
        
        try {
            $user = Auth::user();
            $user_sites = Site::getUserSites($user['id']);
            $site_ids = array_column($user_sites, 'id');
            
            if (empty($site_ids)) {
                $this->jsonResponse([
                    'success' => true,
                    'total_live_visitors' => 0,
                    'total_pageviews_today' => 0,
                    'sites' => [],
                    'timestamp' => time()
                ]);
                return;
            }
            
            $placeholders = implode(',', array_fill(0, count($site_ids), '?'));
            
            // Get total live visitors across all sites
            $live_visitors = Database::selectOne(
                "SELECT COUNT(DISTINCT session_id) as total_live_visitors 
                 FROM realtime_visitors 
                 WHERE site_id IN ($placeholders) 
                 AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
                $site_ids
            );
            
            // Get total pageviews today across all sites
            $today_pageviews = Database::selectOne(
                "SELECT COUNT(*) as total_pageviews 
                 FROM pageviews 
                 WHERE site_id IN ($placeholders) 
                 AND DATE(timestamp) = CURDATE()",
                $site_ids
            );
            
            // Get live visitors per site
            $sites_live_data = [];
            foreach ($user_sites as $site) {
                $site_live = Database::selectOne(
                    "SELECT COUNT(DISTINCT session_id) as live_visitors 
                     FROM realtime_visitors 
                     WHERE site_id = ? 
                     AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
                    [$site['id']]
                );
                
                $sites_live_data[] = [
                    'id' => $site['id'],
                    'name' => $site['name'],
                    'live_visitors' => $site_live['live_visitors'] ?? 0
                ];
            }
            
            $this->jsonResponse([
                'success' => true,
                'total_live_visitors' => $live_visitors['total_live_visitors'] ?? 0,
                'total_pageviews_today' => $today_pageviews['total_pageviews'] ?? 0,
                'sites' => $sites_live_data,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            error_log("Agency stats API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch agency stats');
        }
    }
    
    /**
     * Fill missing minutes with zero values
     */
    private function fillMissingMinutes(array $data, int $minutes): array
    {
        $filled = [];
        $now = time();
        
        for ($i = $minutes - 1; $i >= 0; $i--) {
            $minute_timestamp = $now - ($i * 60);
            $minute_timestamp = floor($minute_timestamp / 60) * 60; // Round to minute
            $time_label = date('H:i', $minute_timestamp);
            
            // Find matching data point
            $pageviews = 0;
            foreach ($data as $point) {
                if ($point['timestamp'] == $minute_timestamp) {
                    $pageviews = $point['pageviews'];
                    break;
                }
            }
            
            $filled[] = [
                'time_label' => $time_label,
                'timestamp' => $minute_timestamp,
                'pageviews' => $pageviews
            ];
        }
        
        return $filled;
    }
    
    /**
     * Authenticate request
     */
    private function authenticate(): bool
    {
        if (!Auth::isAuthenticated()) {
            $this->jsonError('Authentication required', 401);
            return false;
        }
        return true;
    }
    
    /**
     * Get site ID from request
     */
    private function getSiteId(): ?int
    {
        $site_id = $_GET['site_id'] ?? $_GET['site'] ?? null;
        
        if (!$site_id || !is_numeric($site_id)) {
            return null;
        }
        
        // Verify user has access to this site
        $user = Auth::user();
        $site = Site::getById((int)$site_id, $user['id']);
        
        return $site ? (int)$site_id : null;
    }
    
    /**
     * Send JSON response
     */
    private function jsonResponse(array $data, int $status = 200)
    {
        http_response_code($status);
        echo json_encode($data);
    }
    
    /**
     * Send JSON error response
     */
    private function jsonError(string $message, int $status = 500)
    {
        http_response_code($status);
        echo json_encode(['error' => $message]);
    }
}
?>
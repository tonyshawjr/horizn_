<?php
/**
 * Stats API Controller
 * 
 * Provides main analytics statistics endpoints.
 */

class ApiStatsController
{
    /**
     * Get main analytics overview
     */
    public function overview()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $overview = Site::getAnalyticsOverview($site_id, $date_range['start'], $date_range['end']);
            
            // Add comparison with previous period
            $comparison = $this->getComparisonData($site_id, $date_range);
            
            $response = [
                'overview' => $overview,
                'comparison' => $comparison,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Stats overview API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch overview stats');
        }
    }
    
    /**
     * Get traffic trends data
     */
    public function trends()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $trends = Site::getTrafficTrends($site_id, $date_range['start'], $date_range['end']);
            
            $response = [
                'trends' => $trends,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Traffic trends API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch traffic trends');
        }
    }
    
    /**
     * Get visitor statistics
     */
    public function visitors()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $visitor_stats = [
                'overview' => Person::getVisitorStats($site_id, $date_range['start'], $date_range['end']),
                'new_vs_returning' => Person::getNewVsReturningVisitors($site_id, $date_range['start'], $date_range['end']),
                'loyalty_segments' => Person::getVisitorLoyalty($site_id, $date_range['start'], $date_range['end']),
                'engagement_levels' => Person::getVisitorEngagement($site_id, $date_range['start'], $date_range['end']),
                'device_breakdown' => Person::getVisitorBehaviorByDevice($site_id, $date_range['start'], $date_range['end']),
                'browser_breakdown' => Person::getVisitorBehaviorByBrowser($site_id, $date_range['start'], $date_range['end']),
                'geography' => Person::getVisitorGeography($site_id, $date_range['start'], $date_range['end'])
            ];
            
            $response = [
                'visitor_stats' => $visitor_stats,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Visitor stats API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch visitor statistics');
        }
    }
    
    /**
     * Get top pages
     */
    public function pages()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $top_pages = Site::getTopPages($site_id, $date_range['start'], $date_range['end'], $limit);
            $entry_pages = Person::getTopEntryPages($site_id, $date_range['start'], $date_range['end'], $limit);
            $exit_pages = Person::getTopExitPages($site_id, $date_range['start'], $date_range['end'], $limit);
            
            $response = [
                'top_pages' => $top_pages,
                'entry_pages' => $entry_pages,
                'exit_pages' => $exit_pages,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Top pages API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch top pages');
        }
    }
    
    /**
     * Get referrer sources
     */
    public function referrers()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        
        try {
            $top_referrers = Site::getTopReferrers($site_id, $date_range['start'], $date_range['end'], $limit);
            
            // Categorize referrers
            $categorized = $this->categorizeReferrers($top_referrers);
            
            $response = [
                'top_referrers' => $top_referrers,
                'categorized' => $categorized,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Referrers API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch referrer data');
        }
    }
    
    /**
     * Get events analytics
     */
    public function events()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $events_data = [
                'overview' => Event::getEventStats($site_id, $date_range['start'], $date_range['end']),
                'by_name' => Event::getEventCountsByName($site_id, $date_range['start'], $date_range['end']),
                'by_category' => Event::getEventCountsByCategory($site_id, $date_range['start'], $date_range['end']),
                'timeline' => Event::getEventsTimeline($site_id, $date_range['start'], $date_range['end']),
                'by_page' => Event::getEventsByPage($site_id, $date_range['start'], $date_range['end']),
                'by_device' => Event::getEventsByDevice($site_id, $date_range['start'], $date_range['end']),
                'conversions' => Event::getConversionEvents($site_id, $date_range['start'], $date_range['end'])
            ];
            
            $response = [
                'events' => $events_data,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Events API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch events data');
        }
    }
    
    /**
     * Get behavior flow analysis
     */
    public function behavior()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $behavior_data = [
                'visitor_flow' => Person::getVisitorFlow($site_id, $date_range['start'], $date_range['end']),
                'session_duration_distribution' => Person::getSessionDurationDistribution($site_id, $date_range['start'], $date_range['end']),
                'pageviews_distribution' => Person::getPageViewsDistribution($site_id, $date_range['start'], $date_range['end']),
                'timeline' => Person::getVisitorTimeline($site_id, $date_range['start'], $date_range['end'])
            ];
            
            $response = [
                'behavior' => $behavior_data,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Behavior API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch behavior data');
        }
    }
    
    /**
     * Get performance metrics
     */
    public function performance()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            $performance_data = Site::getPerformanceMetrics($site_id, $date_range['start'], $date_range['end']);
            
            // Calculate performance insights
            $insights = $this->calculatePerformanceInsights($performance_data);
            
            $response = [
                'performance_data' => $performance_data,
                'insights' => $insights,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Performance API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch performance data');
        }
    }
    
    /**
     * Get custom dashboard data
     */
    public function dashboard()
    {
        if (!$this->authenticate()) return;
        
        $site_id = $this->getSiteId();
        if (!$site_id) {
            $this->jsonError('Site ID required', 400);
            return;
        }
        
        $date_range = $this->getDateRange();
        
        try {
            // Get comprehensive dashboard data
            $dashboard_data = [
                'overview' => Site::getAnalyticsOverview($site_id, $date_range['start'], $date_range['end']),
                'trends' => Site::getTrafficTrends($site_id, $date_range['start'], $date_range['end']),
                'top_pages' => Site::getTopPages($site_id, $date_range['start'], $date_range['end'], 10),
                'top_referrers' => Site::getTopReferrers($site_id, $date_range['start'], $date_range['end'], 10),
                'visitor_overview' => Person::getVisitorStats($site_id, $date_range['start'], $date_range['end']),
                'device_breakdown' => Person::getVisitorBehaviorByDevice($site_id, $date_range['start'], $date_range['end']),
                'events_overview' => Event::getEventStats($site_id, $date_range['start'], $date_range['end']),
                'realtime_stats' => Site::getRealtimeStats($site_id)
            ];
            
            // Add comparison data
            $comparison = $this->getComparisonData($site_id, $date_range);
            
            $response = [
                'dashboard' => $dashboard_data,
                'comparison' => $comparison,
                'date_range' => $date_range,
                'timestamp' => time()
            ];
            
            $this->jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Dashboard API error: " . $e->getMessage());
            $this->jsonError('Failed to fetch dashboard data');
        }
    }
    
    /**
     * Get comparison data for previous period
     */
    private function getComparisonData(int $site_id, array $date_range): array
    {
        try {
            // Calculate previous period dates
            $start_date = new DateTime($date_range['start']);
            $end_date = new DateTime($date_range['end']);
            $days_diff = $start_date->diff($end_date)->days + 1;
            
            $prev_end = clone $start_date;
            $prev_end->modify('-1 day');
            $prev_start = clone $prev_end;
            $prev_start->modify("-{$days_diff} days");
            
            $prev_overview = Site::getAnalyticsOverview(
                $site_id, 
                $prev_start->format('Y-m-d'), 
                $prev_end->format('Y-m-d')
            );
            
            return $prev_overview;
            
        } catch (Exception $e) {
            error_log("Comparison data error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Categorize referrers by type
     */
    private function categorizeReferrers(array $referrers): array
    {
        $search_engines = ['google.com', 'bing.com', 'yahoo.com', 'duckduckgo.com'];
        $social_media = ['facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com', 'reddit.com'];
        
        $categories = [
            'direct' => 0,
            'search' => 0,
            'social' => 0,
            'referral' => 0
        ];
        
        foreach ($referrers as $referrer) {
            $domain = $referrer['referrer_domain'];
            $sessions = $referrer['sessions'];
            
            if ($domain === '(direct)') {
                $categories['direct'] += $sessions;
            } elseif (in_array($domain, $search_engines)) {
                $categories['search'] += $sessions;
            } elseif (in_array($domain, $social_media)) {
                $categories['social'] += $sessions;
            } else {
                $categories['referral'] += $sessions;
            }
        }
        
        return $categories;
    }
    
    /**
     * Calculate performance insights
     */
    private function calculatePerformanceInsights(array $performance_data): array
    {
        if (empty($performance_data)) {
            return [];
        }
        
        $load_times = array_column($performance_data, 'avg_load_time');
        $load_times = array_filter($load_times, function($time) { return $time > 0; });
        
        if (empty($load_times)) {
            return [];
        }
        
        $avg_load_time = array_sum($load_times) / count($load_times);
        $median_load_time = $load_times[floor(count($load_times) / 2)];
        $max_load_time = max($load_times);
        $min_load_time = min($load_times);
        
        // Performance rating
        $rating = 'excellent';
        if ($avg_load_time > 3000) {
            $rating = 'poor';
        } elseif ($avg_load_time > 1000) {
            $rating = 'good';
        }
        
        return [
            'avg_load_time' => round($avg_load_time),
            'median_load_time' => round($median_load_time),
            'max_load_time' => round($max_load_time),
            'min_load_time' => round($min_load_time),
            'performance_rating' => $rating
        ];
    }
    
    /**
     * Get date range from request parameters
     */
    private function getDateRange(): array
    {
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        $period = $_GET['period'] ?? '30d';
        
        // If specific dates provided, use them
        if ($start_date && $end_date) {
            if (DateTime::createFromFormat('Y-m-d', $start_date) && 
                DateTime::createFromFormat('Y-m-d', $end_date)) {
                return [
                    'start' => $start_date,
                    'end' => $end_date,
                    'period' => 'custom'
                ];
            }
        }
        
        // Use predefined period
        switch ($period) {
            case '1d':
                $start = date('Y-m-d');
                $end = date('Y-m-d');
                break;
            case '7d':
                $start = date('Y-m-d', strtotime('-6 days'));
                $end = date('Y-m-d');
                break;
            case '30d':
            default:
                $start = date('Y-m-d', strtotime('-29 days'));
                $end = date('Y-m-d');
                $period = '30d';
                break;
            case '90d':
                $start = date('Y-m-d', strtotime('-89 days'));
                $end = date('Y-m-d');
                break;
        }
        
        return [
            'start' => $start,
            'end' => $end,
            'period' => $period
        ];
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
     * Get site ID from request and verify access
     */
    private function getSiteId(): ?int
    {
        $site_id = $_GET['site_id'] ?? null;
        
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
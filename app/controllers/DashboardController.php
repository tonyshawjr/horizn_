<?php
/**
 * Dashboard Controller
 * 
 * Handles the main analytics dashboard and overview pages.
 */

class DashboardController
{
    /**
     * Main dashboard page - redirects to agency dashboard
     */
    public function index()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        // Redirect to agency dashboard by default
        header('Location: /dashboard/agency');
        exit;
    }
    
    /**
     * Agency dashboard - overview of all user sites
     */
    public function agency()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        
        // Get date range
        $date_range = $this->getDateRange();
        $start_date = $date_range['start'];
        $end_date = $date_range['end'];
        
        // Get all user sites
        $user_sites = Site::getUserSites($user['id']);
        
        // If no sites, redirect to add site
        if (empty($user_sites)) {
            header('Location: /sites/add');
            exit;
        }
        
        // Get site IDs for aggregate queries
        $site_ids = array_column($user_sites, 'id');
        
        // Get agency-level stats using Analytics helper
        require_once APP_PATH . '/lib/Analytics.php';
        $analytics = new Analytics(new Database());
        $agency_stats = $analytics->getAgencyStats($site_ids, $date_range);
        
        // Enhance each site with analytics data
        foreach ($user_sites as &$site) {
            // Get live visitors
            $site['live_visitors'] = $analytics->getLiveVisitors($site['id']);
            
            // Get basic stats
            $site['stats'] = $analytics->getSiteOverview($site['id'], $date_range);
            
            // Get sparkline data
            $site['sparkline_data'] = $analytics->getSparklineData($site['id'], 7);
            
            // Get last activity
            $site['last_activity'] = $analytics->getLastActivity($site['id']);
        }
        
        // Prepare data for view
        $data = [
            'user' => $user,
            'user_sites' => $user_sites,
            'agency_stats' => $agency_stats,
            'date_range' => $date_range,
            'page_title' => 'Agency Dashboard - horizn_ Analytics'
        ];
        
        $this->renderView('dashboard/agency', $data);
    }
    
    /**
     * Real-time dashboard page
     */
    public function realtime()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        // Get real-time data
        $realtime_data = [
            'site' => Site::getById($site_id, $user['id']),
            'active_visitors' => Person::getActiveVisitors($site_id),
            'recent_pageviews' => $this->getRecentPageviews($site_id),
            'recent_events' => Event::getRealtimeEvents($site_id),
            'stats' => Site::getRealtimeStats($site_id)
        ];
        
        // Load realtime view
        $data = [
            'user' => $user,
            'site_id' => $site_id,
            'realtime' => $realtime_data,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => 'Real-time Analytics - horizn_'
        ];
        
        $this->renderView('dashboard/realtime', $data);
    }
    
    /**
     * Site analytics page
     */
    public function site()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        $date_range = $this->getDateRange();
        $start_date = $date_range['start'];
        $end_date = $date_range['end'];
        
        // Get comprehensive site analytics
        $analytics_data = [
            'site' => Site::getById($site_id, $user['id']),
            'overview' => Site::getAnalyticsOverview($site_id, $start_date, $end_date),
            'traffic_trends' => Site::getTrafficTrends($site_id, $start_date, $end_date),
            'visitor_stats' => Person::getVisitorStats($site_id, $start_date, $end_date),
            'visitor_behavior' => [
                'device_breakdown' => Person::getVisitorBehaviorByDevice($site_id, $start_date, $end_date),
                'browser_breakdown' => Person::getVisitorBehaviorByBrowser($site_id, $start_date, $end_date),
                'new_vs_returning' => Person::getNewVsReturningVisitors($site_id, $start_date, $end_date),
                'loyalty_segments' => Person::getVisitorLoyalty($site_id, $start_date, $end_date),
                'engagement_levels' => Person::getVisitorEngagement($site_id, $start_date, $end_date)
            ],
            'top_pages' => Site::getTopPages($site_id, $start_date, $end_date, 20),
            'top_referrers' => Site::getTopReferrers($site_id, $start_date, $end_date, 20),
            'top_entry_pages' => Person::getTopEntryPages($site_id, $start_date, $end_date),
            'top_exit_pages' => Person::getTopExitPages($site_id, $start_date, $end_date),
            'events_overview' => Event::getEventStats($site_id, $start_date, $end_date),
            'popular_events' => Event::getPopularEvents($site_id, $start_date, $end_date)
        ];
        
        // Load site analytics view
        $data = [
            'user' => $user,
            'site_id' => $site_id,
            'date_range' => $date_range,
            'analytics' => $analytics_data,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => 'Site Analytics - horizn_'
        ];
        
        $this->renderView('dashboard/site', $data);
    }
    
    /**
     * Events analytics page
     */
    public function events()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        $date_range = $this->getDateRange();
        $start_date = $date_range['start'];
        $end_date = $date_range['end'];
        
        // Get events analytics
        $events_data = [
            'site' => Site::getById($site_id, $user['id']),
            'overview' => Event::getEventStats($site_id, $start_date, $end_date),
            'event_counts_by_name' => Event::getEventCountsByName($site_id, $start_date, $end_date),
            'event_counts_by_category' => Event::getEventCountsByCategory($site_id, $start_date, $end_date),
            'events_timeline' => Event::getEventsTimeline($site_id, $start_date, $end_date),
            'events_by_page' => Event::getEventsByPage($site_id, $start_date, $end_date),
            'events_by_device' => Event::getEventsByDevice($site_id, $start_date, $end_date),
            'conversion_events' => Event::getConversionEvents($site_id, $start_date, $end_date),
            'recent_events' => Event::getRealtimeEvents($site_id, 50)
        ];
        
        // Load events analytics view
        $data = [
            'user' => $user,
            'site_id' => $site_id,
            'date_range' => $date_range,
            'events' => $events_data,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => 'Events Analytics - horizn_'
        ];
        
        $this->renderView('dashboard/events', $data);
    }
    
    /**
     * User journeys analytics page
     */
    public function journeys()
    {
        // Redirect to the dedicated JourneyController
        $site_param = !empty($_GET['site']) ? '?site=' . $_GET['site'] : '';
        header('Location: /journey' . $site_param);
        exit;
    }
    
    /**
     * Conversion funnels analytics page
     */
    public function funnels()
    {
        // Redirect to the dedicated FunnelController
        $site_param = !empty($_GET['site']) ? '?site=' . $_GET['site'] : '';
        $period_param = !empty($_GET['period']) ? '&period=' . $_GET['period'] : '';
        header('Location: /funnels' . $site_param . $period_param);
        exit;
    }
    
    /**
     * User behavior analytics page
     */
    public function behavior()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        $date_range = $this->getDateRange();
        $start_date = $date_range['start'];
        $end_date = $date_range['end'];
        
        // Get behavior analytics
        $behavior_data = [
            'site' => Site::getById($site_id, $user['id']),
            'visitor_flow' => Person::getVisitorFlow($site_id, $start_date, $end_date),
            'session_duration_distribution' => Person::getSessionDurationDistribution($site_id, $start_date, $end_date),
            'pageviews_distribution' => Person::getPageViewsDistribution($site_id, $start_date, $end_date),
            'visitor_geography' => Person::getVisitorGeography($site_id, $start_date, $end_date),
            'visitor_timeline' => Person::getVisitorTimeline($site_id, $start_date, $end_date)
        ];
        
        // Load behavior analytics view
        $data = [
            'user' => $user,
            'site_id' => $site_id,
            'date_range' => $date_range,
            'behavior' => $behavior_data,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => 'User Behavior - horizn_'
        ];
        
        $this->renderView('dashboard/behavior', $data);
    }
    
    /**
     * Get current site ID from session or URL parameter
     */
    private function getCurrentSiteId(): ?int
    {
        $user = Auth::user();
        if (!$user) return null;
        
        // Check URL parameter first
        if (!empty($_GET['site']) && is_numeric($_GET['site'])) {
            $site_id = (int)$_GET['site'];
            
            // Verify user has access to this site
            $site = Site::getById($site_id, $user['id']);
            if ($site) {
                // Store in session for future requests
                Auth::startSession();
                $_SESSION['current_site_id'] = $site_id;
                return $site_id;
            }
        }
        
        // Check session
        Auth::startSession();
        if (!empty($_SESSION['current_site_id'])) {
            $site_id = (int)$_SESSION['current_site_id'];
            
            // Verify site still exists and user has access
            $site = Site::getById($site_id, $user['id']);
            if ($site) {
                return $site_id;
            } else {
                // Site no longer exists, clear from session
                unset($_SESSION['current_site_id']);
            }
        }
        
        // No valid site ID found
        return null;
    }
    
    /**
     * Get date range from request parameters or use default
     */
    private function getDateRange(): array
    {
        $start_date = $_GET['start_date'] ?? null;
        $end_date = $_GET['end_date'] ?? null;
        $period = $_GET['period'] ?? '30d';
        
        // If specific dates provided, use them
        if ($start_date && $end_date) {
            // Validate date format
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
     * Get comprehensive dashboard data
     */
    private function getDashboardData(int $site_id, string $start_date, string $end_date): array
    {
        $user = Auth::user();
        
        return [
            'site' => Site::getById($site_id, $user['id']),
            'overview' => Site::getAnalyticsOverview($site_id, $start_date, $end_date),
            'traffic_trends' => Site::getTrafficTrends($site_id, $start_date, $end_date),
            'top_pages' => Site::getTopPages($site_id, $start_date, $end_date, 10),
            'top_referrers' => Site::getTopReferrers($site_id, $start_date, $end_date, 10),
            'realtime_stats' => Site::getRealtimeStats($site_id)
        ];
    }
    
    /**
     * Get recent pageviews for real-time dashboard
     */
    private function getRecentPageviews(int $site_id, int $limit = 20): array
    {
        return Database::select(
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
             AND p.timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             ORDER BY p.timestamp DESC
             LIMIT ?",
            [$site_id, $limit]
        );
    }
    
    /**
     * Render view with layout
     */
    private function renderView(string $view, array $data = [])
    {
        // Extract data for view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $view_file = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<h1>Error</h1><p>View not found: {$view}</p>";
        }
        
        // Get the view content
        $content = ob_get_clean();
        
        // Include the layout
        include APP_PATH . '/views/layout.php';
    }
}
?>
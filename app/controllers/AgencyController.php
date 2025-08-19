<?php
/**
 * Agency Controller - Multi-tenant analytics dashboard
 * Handles agency-level views and API endpoints for multiple sites
 */

require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Analytics.php';
require_once APP_PATH . '/lib/Auth.php';
require_once APP_PATH . '/models/Site.php';
require_once APP_PATH . '/models/User.php';

class AgencyController {
    
    private $db;
    private $analytics;
    private $auth;
    private $site;
    private $user;
    
    public function __construct() {
        $this->db = new Database();
        $this->analytics = new Analytics($this->db);
        $this->auth = new Auth($this->db);
        $this->site = new Site($this->db);
        $this->user = new User($this->db);
    }
    
    /**
     * Display agency dashboard with all client sites
     */
    public function index() {
        // Check authentication
        $currentUser = $this->auth->getCurrentUser();
        if (!$currentUser) {
            header('Location: /auth/login');
            exit;
        }
        
        // Get date range from request
        $period = $_GET['period'] ?? '30d';
        $dateRange = $this->analytics->getDateRange($period);
        
        // Get all user sites
        $userSites = $this->site->getUserSites($currentUser['id']);
        
        // Get site IDs for batch processing
        $siteIds = array_column($userSites, 'id');
        
        // Get agency-level stats
        $agencyStats = $this->analytics->getAgencyStats($siteIds, $dateRange);
        
        // Enhance each site with real-time data and sparklines
        foreach ($userSites as &$site) {
            // Get live visitors for this site
            $site['live_visitors'] = $this->analytics->getLiveVisitors($site['id']);
            
            // Get basic stats for this site
            $site['stats'] = $this->analytics->getSiteOverview($site['id'], $dateRange);
            
            // Get sparkline data (7 days of hourly data)
            $site['sparkline_data'] = $this->analytics->getSparklineData($site['id'], 7);
            
            // Get last activity
            $site['last_activity'] = $this->analytics->getLastActivity($site['id']);
        }
        
        // Prepare data for view
        $data = [
            'user_sites' => $userSites,
            'agency_stats' => $agencyStats,
            'period' => $period,
            'date_range' => $dateRange
        ];
        
        // Load view
        $this->loadView('dashboard/agency', $data);
    }
    
    /**
     * AJAX endpoint for real-time agency stats
     */
    public function getSiteStats() {
        header('Content-Type: application/json');
        
        // Check authentication
        $currentUser = $this->auth->getCurrentUser();
        if (!$currentUser) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        try {
            // Get all user sites
            $userSites = $this->site->getUserSites($currentUser['id']);
            $siteIds = array_column($userSites, 'id');
            
            // Get live stats
            $liveStats = [];
            $totalLiveVisitors = 0;
            
            foreach ($userSites as $site) {
                $liveVisitors = $this->analytics->getLiveVisitors($site['id']);
                $totalLiveVisitors += $liveVisitors;
                
                $liveStats[] = [
                    'id' => $site['id'],
                    'name' => $site['name'],
                    'live_visitors' => $liveVisitors
                ];
            }
            
            // Get total pageviews today
            $todayStats = $this->analytics->getTodayStats($siteIds);
            
            echo json_encode([
                'success' => true,
                'total_live_visitors' => $totalLiveVisitors,
                'total_pageviews_today' => $todayStats['pageviews'],
                'sites' => $liveStats,
                'timestamp' => time()
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch stats: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Load view with layout
     */
    private function loadView($viewPath, $data = []) {
        // Extract data array to individual variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include APP_PATH . "/views/$viewPath.php";
        
        // Get the view content
        $content = ob_get_clean();
        
        // Include the layout
        include APP_PATH . '/views/layout.php';
    }
}
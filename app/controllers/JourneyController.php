<?php
/**
 * Journey Controller
 * 
 * Handles user journey tracking and identity merging functionality
 */

require_once APP_PATH . '/lib/Journey.php';

class JourneyController
{
    /**
     * Journey dashboard - list of all user journeys
     */
    public function index()
    {
        // Require authentication
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
        
        // Get journey filters
        $filters = $this->getJourneyFilters();
        
        // Initialize Journey analyzer
        $journey = new Journey();
        
        // Get journey statistics
        $journey_stats = $journey->getJourneyStats($site_id, $start_date, $end_date, $filters);
        
        // Get popular journey paths
        $popular_paths = $journey->getPopularPaths($site_id, $start_date, $end_date, 10);
        
        // Get live journeys
        $live_journeys = $journey->getLiveJourneys($site_id);
        
        // Get recent completed journeys with full timeline data
        $recent_journeys = $journey->getRecentJourneys($site_id, $start_date, $end_date, 10);
        
        // Prepare data for view
        $data = [
            'user' => $user,
            'current_site_id' => $site_id,
            'user_sites' => Site::getUserSites($user['id']),
            'date_range' => $date_range,
            'journey_stats' => $journey_stats,
            'popular_paths' => $popular_paths,
            'live_journeys' => $live_journeys,
            'recent_journeys' => $recent_journeys,
            'filters' => $filters,
            'page_title' => 'User Journeys - horizn_ Analytics'
        ];
        
        $this->renderView('dashboard/journeys', $data);
    }
    
    /**
     * Individual journey detail view
     */
    public function detail($person_id)
    {
        // Require authentication
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
        
        // Initialize Journey analyzer
        $journey_analyzer = new Journey();
        
        // Get complete journey data for this person
        $journey = $journey_analyzer->getPersonJourney($person_id, $site_id);
        
        if (!$journey) {
            header('Location: /dashboard/journeys?site=' . $site_id);
            exit;
        }
        
        // Prepare data for view
        $data = [
            'user' => $user,
            'current_site_id' => $site_id,
            'user_sites' => Site::getUserSites($user['id']),
            'journey' => $journey,
            'page_title' => 'Journey Details - horizn_ Analytics'
        ];
        
        $this->renderView('dashboard/journey-detail', $data);
    }
    
    /**
     * Handle identity merging
     */
    public function merge()
    {
        // Require authentication and POST request
        if (!Auth::isAuthenticated() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        
        if (!$site_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No site selected']);
            exit;
        }
        
        // Get merge parameters
        $input = json_decode(file_get_contents('php://input'), true);
        $primary_person_id = $input['primary_person_id'] ?? null;
        $secondary_person_id = $input['secondary_person_id'] ?? null;
        $merge_reason = $input['reason'] ?? 'manual';
        
        if (!$primary_person_id || !$secondary_person_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing person IDs']);
            exit;
        }
        
        // Perform identity merge
        $result = Person::mergeIdentities($primary_person_id, $secondary_person_id, $site_id, $merge_reason);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Export journey data
     */
    public function export($person_id)
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = $this->getCurrentSiteId();
        $format = $_GET['format'] ?? 'json';
        
        if (!$site_id) {
            http_response_code(400);
            exit;
        }
        
        // Initialize Journey analyzer
        $journey_analyzer = new Journey();
        
        // Get complete journey data
        $journey = $journey_analyzer->getPersonJourney($person_id, $site_id);
        
        if (!$journey) {
            http_response_code(404);
            exit;
        }
        
        // Export based on format
        switch ($format) {
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="journey_' . $person_id . '_' . date('Y-m-d') . '.json"');
                echo json_encode($journey, JSON_PRETTY_PRINT);
                break;
                
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="journey_' . $person_id . '_' . date('Y-m-d') . '.csv"');
                $this->exportJourneyAsCSV($journey);
                break;
                
            default:
                http_response_code(400);
                echo 'Unsupported format';
        }
    }
    
    /**
     * API endpoint for live journey updates
     */
    public function api_live()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            exit;
        }
        
        $user = Auth::user();
        $site_id = $_GET['site'] ?? null;
        
        if (!$site_id || !Site::getById($site_id, $user['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid site']);
            exit;
        }
        
        // Initialize Journey analyzer
        $journey = new Journey();
        
        // Get live data
        $live_journeys = $journey->getLiveJourneys($site_id);
        $stats = $journey->getLiveJourneyStats($site_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'live_journeys' => $live_journeys,
            'stats' => $stats,
            'timestamp' => time()
        ]);
    }
    
    /**
     * API endpoint for journey detail updates
     */
    public function api_detail($person_id)
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            exit;
        }
        
        $user = Auth::user();
        $site_id = $_GET['site'] ?? null;
        
        if (!$site_id || !Site::getById($site_id, $user['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid site']);
            exit;
        }
        
        // Initialize Journey analyzer
        $journey_analyzer = new Journey();
        
        // Get updated journey data
        $journey = $journey_analyzer->getPersonJourney($person_id, $site_id);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'journey' => $journey,
            'timestamp' => time()
        ]);
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
        $period = $_GET['period'] ?? '7d';
        
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
            default:
                $start = date('Y-m-d', strtotime('-6 days'));
                $end = date('Y-m-d');
                $period = '7d';
                break;
            case '30d':
                $start = date('Y-m-d', strtotime('-29 days'));
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
     * Get journey filters from request parameters
     */
    private function getJourneyFilters(): array
    {
        return [
            'journey_length' => $_GET['journey_length'] ?? null,
            'entry_page' => $_GET['entry_page'] ?? null,
            'device_type' => $_GET['device_type'] ?? null,
            'source' => $_GET['source'] ?? null,
            'has_events' => isset($_GET['has_events']) ? (bool)$_GET['has_events'] : null,
            'min_duration' => isset($_GET['min_duration']) ? (int)$_GET['min_duration'] : null,
            'max_duration' => isset($_GET['max_duration']) ? (int)$_GET['max_duration'] : null
        ];
    }
    
    /**
     * Export journey data as CSV
     */
    private function exportJourneyAsCSV($journey): void
    {
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'Timestamp',
            'Type',
            'Session ID',
            'Page URL',
            'Page Title',
            'Event Name',
            'Event Category',
            'Event Value',
            'Device Type',
            'Browser',
            'Country',
            'Referrer'
        ]);
        
        // Export timeline data
        foreach ($journey['timeline'] as $item) {
            fputcsv($output, [
                $item['timestamp'],
                $item['type'],
                $item['session_id'] ?? '',
                $item['page_url'] ?? '',
                $item['page_title'] ?? '',
                $item['event_name'] ?? '',
                $item['event_category'] ?? '',
                $item['event_value'] ?? '',
                $item['device_type'] ?? '',
                $item['browser'] ?? '',
                $item['country_code'] ?? '',
                $item['referrer'] ?? ''
            ]);
        }
        
        fclose($output);
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
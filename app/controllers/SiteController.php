<?php
/**
 * Site Controller
 * 
 * Handles site management functionality including creation, editing, 
 * settings, and analytics configuration.
 */

class SiteController
{
    /**
     * List all sites for the current user
     */
    public function index()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $sites = Site::getUserSites($user['id']);
        
        // Get quick stats for each site
        foreach ($sites as &$site) {
            $site['stats'] = Site::getRealtimeStats($site['id']);
        }
        
        // Load sites list view
        $data = [
            'user' => $user,
            'sites' => $sites,
            'page_title' => 'My Sites - horizn_ Analytics'
        ];
        
        $this->renderView('sites/index', $data);
    }
    
    /**
     * Show add site form
     */
    public function add()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $error = null;
        $success = null;
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id' => $user['id'],
                'domain' => trim($_POST['domain'] ?? ''),
                'name' => trim($_POST['name'] ?? ''),
                'timezone' => trim($_POST['timezone'] ?? 'UTC'),
                'settings' => [
                    'enable_realtime' => !empty($_POST['enable_realtime']),
                    'track_outbound_links' => !empty($_POST['track_outbound_links']),
                    'respect_dnt' => !empty($_POST['respect_dnt'])
                ]
            ];
            
            $result = Site::create($data);
            
            if ($result['success']) {
                // Redirect to sites list with success message
                Auth::startSession();
                $_SESSION['flash_success'] = 'Site created successfully! Your tracking code is: ' . $result['tracking_code'];
                header('Location: /sites');
                exit;
            } else {
                $error = $result['error'];
            }
        }
        
        // Get timezone list
        $timezones = timezone_identifiers_list();
        
        // Load add site view
        $data = [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'timezones' => $timezones,
            'page_title' => 'Add Site - horizn_ Analytics'
        ];
        
        $this->renderView('sites/add', $data);
    }
    
    /**
     * Show edit site form
     */
    public function edit()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Get site
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            $this->redirectWithError('/sites', 'Site not found or access denied.');
            return;
        }
        
        $error = null;
        $success = null;
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'domain' => trim($_POST['domain'] ?? ''),
                'timezone' => trim($_POST['timezone'] ?? 'UTC'),
                'settings' => [
                    'enable_realtime' => !empty($_POST['enable_realtime']),
                    'track_outbound_links' => !empty($_POST['track_outbound_links']),
                    'respect_dnt' => !empty($_POST['respect_dnt']),
                    'exclude_ips' => array_filter(explode("\n", $_POST['exclude_ips'] ?? ''))
                ]
            ];
            
            $result = Site::update($site_id, $data, $user['id']);
            
            if ($result['success']) {
                $success = $result['message'];
                // Refresh site data
                $site = Site::getById($site_id, $user['id']);
            } else {
                $error = $result['error'];
            }
        }
        
        // Parse current settings
        $settings = json_decode($site['settings'] ?? '{}', true);
        $site['parsed_settings'] = $settings;
        
        // Get timezone list
        $timezones = timezone_identifiers_list();
        
        // Load edit site view
        $data = [
            'user' => $user,
            'site' => $site,
            'error' => $error,
            'success' => $success,
            'timezones' => $timezones,
            'page_title' => "Edit {$site['name']} - horizn_ Analytics"
        ];
        
        $this->renderView('sites/edit', $data);
    }
    
    /**
     * Delete site
     */
    public function delete()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Verify site ownership
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            $this->redirectWithError('/sites', 'Site not found or access denied.');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle deletion confirmation
            $result = Site::delete($site_id, $user['id']);
            
            if ($result['success']) {
                $this->redirectWithSuccess('/sites', 'Site deleted successfully.');
            } else {
                $this->redirectWithError('/sites', $result['error']);
            }
            return;
        }
        
        // Show confirmation page
        $data = [
            'user' => $user,
            'site' => $site,
            'page_title' => "Delete {$site['name']} - horizn_ Analytics"
        ];
        
        $this->renderView('sites/delete', $data);
    }
    
    /**
     * Show tracking installation guide
     */
    public function install()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Get site
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            $this->redirectWithError('/sites', 'Site not found or access denied.');
            return;
        }
        
        // Generate tracking script
        $tracking_script = $this->generateTrackingScript($site);
        
        // Load installation guide view
        $data = [
            'user' => $user,
            'site' => $site,
            'tracking_script' => $tracking_script,
            'page_title' => "Install Tracking - {$site['name']}"
        ];
        
        $this->renderView('sites/install', $data);
    }
    
    /**
     * Export site data
     */
    public function export()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Get site
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            $this->redirectWithError('/sites', 'Site not found or access denied.');
            return;
        }
        
        // Get date range
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        $format = $_GET['format'] ?? 'csv';
        
        // Export site data
        $result = Site::exportSiteData($site_id, $user['id'], $start_date, $end_date, $format);
        
        if ($result['success']) {
            // Send file download
            header('Content-Type: ' . $result['content_type']);
            header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
            header('Content-Length: ' . strlen($result['data']));
            echo $result['data'];
            exit;
        } else {
            $this->redirectWithError('/sites', $result['error']);
        }
    }
    
    /**
     * Site settings page
     */
    public function settings()
    {
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Get site
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            $this->redirectWithError('/sites', 'Site not found or access denied.');
            return;
        }
        
        $error = null;
        $success = null;
        
        // Handle settings update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'enable_realtime' => !empty($_POST['enable_realtime']),
                'track_outbound_links' => !empty($_POST['track_outbound_links']),
                'respect_dnt' => !empty($_POST['respect_dnt']),
                'track_file_downloads' => !empty($_POST['track_file_downloads']),
                'track_email_clicks' => !empty($_POST['track_email_clicks']),
                'exclude_ips' => array_filter(array_map('trim', explode("\n", $_POST['exclude_ips'] ?? ''))),
                'exclude_user_agents' => array_filter(array_map('trim', explode("\n", $_POST['exclude_user_agents'] ?? ''))),
                'exclude_parameters' => array_filter(array_map('trim', explode("\n", $_POST['exclude_parameters'] ?? ''))),
                'custom_dimensions' => json_decode($_POST['custom_dimensions'] ?? '[]', true) ?: []
            ];
            
            $result = Site::updateSettings($site_id, $user['id'], $settings);
            
            if ($result['success']) {
                $success = 'Settings updated successfully.';
                $site['settings'] = json_encode($result['settings']);
            } else {
                $error = $result['error'];
            }
        }
        
        // Parse current settings
        $settings = json_decode($site['settings'] ?? '{}', true);
        
        // Load settings view
        $data = [
            'user' => $user,
            'site' => $site,
            'settings' => $settings,
            'error' => $error,
            'success' => $success,
            'page_title' => "Settings - {$site['name']}"
        ];
        
        $this->renderView('sites/settings', $data);
    }
    
    /**
     * API endpoint to get site analytics data
     */
    public function analyticsApi()
    {
        header('Content-Type: application/json');
        
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            return;
        }
        
        $user = Auth::user();
        $site_id = (int)($_GET['id'] ?? 0);
        
        // Verify site access
        $site = Site::getById($site_id, $user['id']);
        if (!$site) {
            http_response_code(404);
            echo json_encode(['error' => 'Site not found']);
            return;
        }
        
        // Get date range
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        try {
            // Get analytics data
            $analytics_data = [
                'overview' => Site::getAnalyticsOverview($site_id, $start_date, $end_date),
                'traffic_trends' => Site::getTrafficTrends($site_id, $start_date, $end_date),
                'top_pages' => Site::getTopPages($site_id, $start_date, $end_date),
                'top_referrers' => Site::getTopReferrers($site_id, $start_date, $end_date),
                'realtime_stats' => Site::getRealtimeStats($site_id)
            ];
            
            echo json_encode($analytics_data);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch analytics data']);
        }
    }
    
    /**
     * Generate tracking script for a site
     */
    private function generateTrackingScript(array $site): array
    {
        $tracking_code = $site['tracking_code'];
        $domain = $site['domain'];
        
        // Basic tracking script
        $basic_script = "<script>
!function(h,o,r,i,z,n){
    h.horizn=h.horizn||function(){(h.horizn.q=h.horizn.q||[]).push(arguments)};
    n=o.createElement('script');z=o.getElementsByTagName('script')[0];
    n.async=1;n.src=r;z.parentNode.insertBefore(n,z)
}(window,document,'/h.js');
horizn('create', '{$tracking_code}');
horizn('page');
</script>";
        
        // WordPress shortcode
        $wordpress_shortcode = "[horizn_analytics code=\"{$tracking_code}\"]";
        
        // Google Tag Manager style
        $gtm_style = "<!-- horizn_ Analytics -->
<script async src=\"/h.js\" data-site=\"{$tracking_code}\"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function horizn(){dataLayer.push(arguments);}
  horizn('js', new Date());
  horizn('config', '{$tracking_code}');
</script>";
        
        return [
            'basic' => $basic_script,
            'wordpress' => $wordpress_shortcode,
            'gtm_style' => $gtm_style,
            'tracking_code' => $tracking_code
        ];
    }
    
    /**
     * Render view with layout
     */
    private function renderView(string $view, array $data = [])
    {
        // Add flash messages to data
        $data = array_merge($data, $this->getFlashMessages());
        
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
    
    /**
     * Redirect with error message
     */
    private function redirectWithError(string $url, string $error)
    {
        Auth::startSession();
        $_SESSION['flash_error'] = $error;
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redirect with success message
     */
    private function redirectWithSuccess(string $url, string $success)
    {
        Auth::startSession();
        $_SESSION['flash_success'] = $success;
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Get flash messages from session
     */
    private function getFlashMessages(): array
    {
        Auth::startSession();
        
        $messages = [];
        
        if (isset($_SESSION['flash_error'])) {
            $messages['error'] = $_SESSION['flash_error'];
            unset($_SESSION['flash_error']);
        }
        
        if (isset($_SESSION['flash_success'])) {
            $messages['success'] = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }
        
        return $messages;
    }
}
?>
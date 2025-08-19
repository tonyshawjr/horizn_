<?php
/**
 * Custom Dashboard Controller
 * 
 * Handles custom dashboard creation, management, and rendering
 */

require_once APP_PATH . '/lib/DashboardBuilder.php';

class CustomDashboardController
{
    private $dashboardBuilder;
    
    public function __construct()
    {
        $this->dashboardBuilder = new DashboardBuilder();
    }
    
    /**
     * List all saved dashboards
     */
    public function index()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        
        // Get user's custom dashboards
        $dashboards = $this->dashboardBuilder->getUserDashboards($user['id']);
        
        // Get shared dashboards
        $sharedDashboards = $this->dashboardBuilder->getSharedDashboards($user['id']);
        
        // Get usage analytics for each dashboard
        foreach ($dashboards as &$dashboard) {
            $dashboard['usage_stats'] = $this->dashboardBuilder->getDashboardUsage($dashboard['id']);
        }
        
        $data = [
            'user' => $user,
            'dashboards' => $dashboards,
            'shared_dashboards' => $sharedDashboards,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => 'Custom Dashboards - horizn_',
            'currentPage' => 'custom-dashboards'
        ];
        
        $this->renderView('dashboard/custom-dashboards', $data);
    }
    
    /**
     * Dashboard builder interface
     */
    public function builder()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $dashboard_id = $_GET['id'] ?? null;
        
        // If editing existing dashboard, load it
        $dashboard = null;
        if ($dashboard_id) {
            $dashboard = $this->dashboardBuilder->getDashboard($dashboard_id, $user['id']);
            if (!$dashboard) {
                header('Location: /dashboard/custom');
                exit;
            }
        }
        
        // Get available widgets and data sources
        $availableWidgets = $this->dashboardBuilder->getAvailableWidgets();
        $dataSources = $this->dashboardBuilder->getDataSources($user['id']);
        
        $data = [
            'user' => $user,
            'dashboard' => $dashboard,
            'available_widgets' => $availableWidgets,
            'data_sources' => $dataSources,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => ($dashboard ? 'Edit Dashboard' : 'Create Dashboard') . ' - horizn_',
            'currentPage' => 'dashboard-builder'
        ];
        
        $this->renderView('dashboard/dashboard-builder', $data);
    }
    
    /**
     * Save dashboard configuration
     */
    public function save()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Only handle POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $user = Auth::user();
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!$input || !isset($input['name']) || !isset($input['layout'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            exit;
        }
        
        try {
            $dashboard_id = $this->dashboardBuilder->saveDashboard([
                'id' => $input['id'] ?? null,
                'user_id' => $user['id'],
                'name' => $input['name'],
                'description' => $input['description'] ?? '',
                'layout' => $input['layout'],
                'widgets' => $input['widgets'] ?? [],
                'settings' => $input['settings'] ?? [],
                'is_shared' => $input['is_shared'] ?? false
            ]);
            
            echo json_encode([
                'success' => true,
                'dashboard_id' => $dashboard_id,
                'message' => 'Dashboard saved successfully'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save dashboard: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Load saved dashboard
     */
    public function load()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $user = Auth::user();
        $dashboard_id = $_GET['id'] ?? null;
        
        if (!$dashboard_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Dashboard ID required']);
            exit;
        }
        
        try {
            $dashboard = $this->dashboardBuilder->getDashboard($dashboard_id, $user['id']);
            
            if (!$dashboard) {
                http_response_code(404);
                echo json_encode(['error' => 'Dashboard not found']);
                exit;
            }
            
            // Get live data for widgets
            $widgetData = $this->dashboardBuilder->getWidgetData($dashboard['widgets'], $user['id']);
            
            echo json_encode([
                'success' => true,
                'dashboard' => $dashboard,
                'widget_data' => $widgetData
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load dashboard: ' . $e->getMessage()]);
        }
    }
    
    /**
     * View saved dashboard
     */
    public function view()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        $user = Auth::user();
        $dashboard_id = $_GET['id'] ?? null;
        
        if (!$dashboard_id) {
            header('Location: /dashboard/custom');
            exit;
        }
        
        $dashboard = $this->dashboardBuilder->getDashboard($dashboard_id, $user['id']);
        
        if (!$dashboard) {
            header('Location: /dashboard/custom');
            exit;
        }
        
        // Track dashboard view
        $this->dashboardBuilder->trackDashboardView($dashboard_id, $user['id']);
        
        // Get widget data
        $widgetData = $this->dashboardBuilder->getWidgetData($dashboard['widgets'], $user['id']);
        
        $data = [
            'user' => $user,
            'dashboard' => $dashboard,
            'widget_data' => $widgetData,
            'user_sites' => Site::getUserSites($user['id']),
            'page_title' => $dashboard['name'] . ' - Custom Dashboard - horizn_',
            'currentPage' => 'custom-dashboard-view'
        ];
        
        $this->renderView('dashboard/custom-dashboard-view', $data);
    }
    
    /**
     * Delete dashboard
     */
    public function delete()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Only handle DELETE requests
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $user = Auth::user();
        $dashboard_id = $_GET['id'] ?? null;
        
        if (!$dashboard_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Dashboard ID required']);
            exit;
        }
        
        try {
            $success = $this->dashboardBuilder->deleteDashboard($dashboard_id, $user['id']);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Dashboard deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Dashboard not found']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete dashboard: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Share dashboard with organization
     */
    public function share()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Only handle POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $user = Auth::user();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['dashboard_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Dashboard ID required']);
            exit;
        }
        
        try {
            $success = $this->dashboardBuilder->shareDashboard(
                $input['dashboard_id'],
                $user['id'],
                $input['share_with'] ?? 'organization',
                $input['permissions'] ?? 'view'
            );
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Dashboard shared successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Dashboard not found or access denied']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to share dashboard: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get widget data for AJAX updates
     */
    public function widgetData()
    {
        // Require authentication
        if (!Auth::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $user = Auth::user();
        $widget_id = $_GET['widget_id'] ?? null;
        $widget_type = $_GET['widget_type'] ?? null;
        $site_id = $_GET['site_id'] ?? null;
        
        if (!$widget_type) {
            http_response_code(400);
            echo json_encode(['error' => 'Widget type required']);
            exit;
        }
        
        try {
            $data = $this->dashboardBuilder->getSingleWidgetData($widget_type, [
                'site_id' => $site_id,
                'user_id' => $user['id'],
                'widget_id' => $widget_id
            ]);
            
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load widget data: ' . $e->getMessage()]);
        }
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
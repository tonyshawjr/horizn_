<?php
/**
 * Funnel Controller
 * 
 * Handles funnel creation, management, and analysis
 */

class FunnelController
{
    /**
     * Display funnel list and overview
     */
    public function index(): void
    {
        // Get current site
        $site_id = $_GET['site'] ?? Auth::getCurrentSiteId();
        $period = $_GET['period'] ?? '30d';
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        // Validate site access
        if (!Auth::canAccessSite($site_id)) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        // Get user sites for selector
        $user_sites = Auth::getUserSites();
        
        // Get date range
        $date_range = $this->getDateRange($period);
        
        // Get funnels for this site
        $funnels = Funnel::getFunnelsBySite($site_id, $date_range['start'], $date_range['end']);
        
        // Get funnel overview stats
        $funnel_stats = Funnel::getFunnelStats($site_id, $date_range['start'], $date_range['end']);
        
        // Prepare funnel data with charts and insights
        foreach ($funnels as &$funnel) {
            $funnel['steps'] = Funnel::getFunnelSteps($funnel['id'], $date_range['start'], $date_range['end']);
            $funnel['chart_data'] = $this->prepareChartData($funnel['steps']);
            $funnel['insights'] = Funnel::generateFunnelInsights($funnel['id'], $date_range['start'], $date_range['end']);
        }
        
        // Load view
        $pageData = [
            'funnels' => $funnels,
            'funnel_stats' => $funnel_stats,
            'user_sites' => $user_sites,
            'current_site_id' => $site_id,
            'period' => $period
        ];
        
        include APP_ROOT . '/app/views/layout.php';
    }
    
    /**
     * Display funnel builder interface
     */
    public function builder(): void
    {
        $site_id = $_GET['site'] ?? Auth::getCurrentSiteId();
        $funnel_id = $_GET['id'] ?? null;
        
        if (!$site_id) {
            header('Location: /dashboard');
            exit;
        }
        
        if (!Auth::canAccessSite($site_id)) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
        
        $funnel = null;
        $funnel_steps = [];
        
        // If editing existing funnel
        if ($funnel_id) {
            $funnel = Funnel::getFunnelById($funnel_id);
            if (!$funnel || $funnel['site_id'] != $site_id) {
                http_response_code(404);
                echo "Funnel not found";
                exit;
            }
            $funnel_steps = Funnel::getFunnelStepsById($funnel_id);
        }
        
        // Get available events and pages for this site
        $available_events = Funnel::getAvailableEvents($site_id);
        $popular_pages = Funnel::getPopularPages($site_id);
        
        $pageData = [
            'funnel' => $funnel,
            'funnel_steps' => $funnel_steps,
            'available_events' => $available_events,
            'popular_pages' => $popular_pages,
            'site_id' => $site_id
        ];
        
        $currentPage = 'funnels';
        $pageTitle = ($funnel_id ? 'Edit' : 'Create') . ' Funnel - horizn_';
        $content = APP_ROOT . '/app/views/dashboard/funnel-builder.php';
        
        include APP_ROOT . '/app/views/layout.php';
    }
    
    /**
     * Create new funnel
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }
        
        $site_id = $input['site_id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $steps = $input['steps'] ?? [];
        
        // Validation
        if (!$site_id || !Auth::canAccessSite($site_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel name is required']);
            exit;
        }
        
        if (count($steps) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel must have at least 2 steps']);
            exit;
        }
        
        try {
            $funnel_id = Funnel::createFunnel([
                'site_id' => $site_id,
                'name' => $name,
                'description' => $description,
                'created_by' => Auth::getUserId(),
                'steps' => $steps
            ]);
            
            echo json_encode([
                'success' => true,
                'funnel_id' => $funnel_id,
                'message' => 'Funnel created successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Funnel creation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create funnel']);
        }
    }
    
    /**
     * Update existing funnel
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }
        
        $funnel_id = $input['funnel_id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $steps = $input['steps'] ?? [];
        
        if (!$funnel_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel ID is required']);
            exit;
        }
        
        // Get existing funnel to verify ownership
        $existing_funnel = Funnel::getFunnelById($funnel_id);
        if (!$existing_funnel || !Auth::canAccessSite($existing_funnel['site_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        // Validation
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel name is required']);
            exit;
        }
        
        if (count($steps) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel must have at least 2 steps']);
            exit;
        }
        
        try {
            Funnel::updateFunnel($funnel_id, [
                'name' => $name,
                'description' => $description,
                'steps' => $steps
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Funnel updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Funnel update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update funnel']);
        }
    }
    
    /**
     * Delete funnel
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $funnel_id = $_GET['id'] ?? null;
        
        if (!$funnel_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel ID is required']);
            exit;
        }
        
        // Get existing funnel to verify ownership
        $funnel = Funnel::getFunnelById($funnel_id);
        if (!$funnel || !Auth::canAccessSite($funnel['site_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        try {
            Funnel::deleteFunnel($funnel_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Funnel deleted successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Funnel deletion error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete funnel']);
        }
    }
    
    /**
     * Analyze funnel performance
     */
    public function analyze(): void
    {
        $funnel_id = $_GET['id'] ?? null;
        $period = $_GET['period'] ?? '30d';
        
        if (!$funnel_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel ID is required']);
            exit;
        }
        
        // Get funnel and verify access
        $funnel = Funnel::getFunnelById($funnel_id);
        if (!$funnel || !Auth::canAccessSite($funnel['site_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $date_range = $this->getDateRange($period);
        
        try {
            $analysis = Funnel::analyzeFunnel($funnel_id, $date_range['start'], $date_range['end']);
            
            echo json_encode([
                'success' => true,
                'analysis' => $analysis
            ]);
            
        } catch (Exception $e) {
            error_log("Funnel analysis error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to analyze funnel']);
        }
    }
    
    /**
     * Get funnel performance data for charts
     */
    public function performance(): void
    {
        $funnel_id = $_GET['id'] ?? null;
        $period = $_GET['period'] ?? '30d';
        
        if (!$funnel_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Funnel ID is required']);
            exit;
        }
        
        // Get funnel and verify access
        $funnel = Funnel::getFunnelById($funnel_id);
        if (!$funnel || !Auth::canAccessSite($funnel['site_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
        
        $date_range = $this->getDateRange($period);
        
        try {
            $performance = Funnel::getFunnelPerformance($funnel_id, $date_range['start'], $date_range['end']);
            
            echo json_encode([
                'success' => true,
                'performance' => $performance
            ]);
            
        } catch (Exception $e) {
            error_log("Funnel performance error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get funnel performance']);
        }
    }
    
    /**
     * Save funnel configuration
     */
    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }
        
        $funnel_id = $input['funnel_id'] ?? null;
        
        if ($funnel_id) {
            // Update existing funnel
            $this->update();
        } else {
            // Create new funnel
            $this->create();
        }
    }
    
    /**
     * Get date range based on period
     */
    private function getDateRange(string $period): array
    {
        switch ($period) {
            case '7d':
                return [
                    'start' => date('Y-m-d', strtotime('-7 days')),
                    'end' => date('Y-m-d')
                ];
            case '90d':
                return [
                    'start' => date('Y-m-d', strtotime('-90 days')),
                    'end' => date('Y-m-d')
                ];
            case '30d':
            default:
                return [
                    'start' => date('Y-m-d', strtotime('-30 days')),
                    'end' => date('Y-m-d')
                ];
        }
    }
    
    /**
     * Prepare chart data for funnel visualization
     */
    private function prepareChartData(array $steps): array
    {
        $chart_data = [];
        
        foreach ($steps as $step) {
            $chart_data[] = [
                'name' => $step['name'],
                'user_count' => $step['user_count'],
                'conversion_rate' => round($step['conversion_rate'], 1),
                'dropoff_rate' => round($step['dropoff_rate'], 1)
            ];
        }
        
        return $chart_data;
    }
    
    /**
     * Process funnel data for a session (called by tracking)
     */
    public function processFunnelData(string $session_id, array $event_data): void
    {
        try {
            // Get all active funnels for this site
            $site_id = $event_data['site_id'] ?? null;
            if (!$site_id) return;
            
            $active_funnels = Funnel::getActiveFunnelsBySite($site_id);
            
            foreach ($active_funnels as $funnel) {
                Funnel::processSessionForFunnel($funnel['id'], $session_id, $event_data);
            }
            
        } catch (Exception $e) {
            error_log("Funnel processing error: " . $e->getMessage());
        }
    }
}
?>
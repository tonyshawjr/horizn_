<?php
/**
 * Dashboard Builder Library
 * 
 * Handles custom dashboard creation, widget management, and data processing
 */

require_once APP_PATH . '/lib/Database.php';
require_once APP_PATH . '/lib/Analytics.php';

class DashboardBuilder
{
    private $db;
    private $analytics;
    
    public function __construct()
    {
        $this->db = new Database();
        $this->analytics = new Analytics(new Database());
    }
    
    /**
     * Get user's custom dashboards
     */
    public function getUserDashboards(int $user_id): array
    {
        return $this->db->select(
            "SELECT 
                d.*,
                COUNT(dv.id) as total_views,
                MAX(dv.viewed_at) as last_viewed
            FROM custom_dashboards d
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            WHERE d.user_id = ? AND d.deleted_at IS NULL
            GROUP BY d.id
            ORDER BY d.updated_at DESC",
            [$user_id]
        );
    }
    
    /**
     * Get dashboards shared with user
     */
    public function getSharedDashboards(int $user_id): array
    {
        return $this->db->select(
            "SELECT 
                d.*,
                ds.permissions,
                ds.shared_at,
                u.name as owner_name,
                COUNT(dv.id) as total_views
            FROM custom_dashboards d
            JOIN dashboard_shares ds ON d.id = ds.dashboard_id
            JOIN users u ON d.user_id = u.id
            LEFT JOIN dashboard_views dv ON d.id = dv.dashboard_id
            WHERE ds.shared_with_user_id = ? 
            AND d.deleted_at IS NULL
            GROUP BY d.id
            ORDER BY ds.shared_at DESC",
            [$user_id]
        );
    }
    
    /**
     * Get dashboard by ID (with access check)
     */
    public function getDashboard(int $dashboard_id, int $user_id): ?array
    {
        // First check if user owns the dashboard
        $dashboard = $this->db->selectOne(
            "SELECT * FROM custom_dashboards 
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL",
            [$dashboard_id, $user_id]
        );
        
        // If not owned, check if shared
        if (!$dashboard) {
            $dashboard = $this->db->selectOne(
                "SELECT d.*, ds.permissions
                FROM custom_dashboards d
                JOIN dashboard_shares ds ON d.id = ds.dashboard_id
                WHERE d.id = ? AND ds.shared_with_user_id = ? 
                AND d.deleted_at IS NULL",
                [$dashboard_id, $user_id]
            );
        }
        
        if ($dashboard) {
            // Decode JSON fields
            $dashboard['layout'] = json_decode($dashboard['layout'], true);
            $dashboard['widgets'] = json_decode($dashboard['widgets'], true);
            $dashboard['settings'] = json_decode($dashboard['settings'], true);
        }
        
        return $dashboard;
    }
    
    /**
     * Save dashboard configuration
     */
    public function saveDashboard(array $data): int
    {
        $dashboard_id = $data['id'] ?? null;
        
        $dashboardData = [
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'layout' => json_encode($data['layout']),
            'widgets' => json_encode($data['widgets']),
            'settings' => json_encode($data['settings']),
            'is_shared' => $data['is_shared'] ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($dashboard_id) {
            // Update existing dashboard
            $this->db->update('custom_dashboards', $dashboardData, [
                'id' => $dashboard_id,
                'user_id' => $data['user_id'] // Ensure user can only update their own
            ]);
            return $dashboard_id;
        } else {
            // Create new dashboard
            $dashboardData['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('custom_dashboards', $dashboardData);
        }
    }
    
    /**
     * Delete dashboard
     */
    public function deleteDashboard(int $dashboard_id, int $user_id): bool
    {
        // Soft delete - set deleted_at timestamp
        $result = $this->db->update(
            'custom_dashboards',
            ['deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $dashboard_id, 'user_id' => $user_id]
        );
        
        return $result > 0;
    }
    
    /**
     * Share dashboard with organization
     */
    public function shareDashboard(int $dashboard_id, int $user_id, string $share_with = 'organization', string $permissions = 'view'): bool
    {
        // Verify user owns the dashboard
        $dashboard = $this->db->selectOne(
            "SELECT id FROM custom_dashboards WHERE id = ? AND user_id = ?",
            [$dashboard_id, $user_id]
        );
        
        if (!$dashboard) {
            return false;
        }
        
        // For now, implement organization-wide sharing
        // Get all users in the same organization (simplified - all users)
        $users = $this->db->select("SELECT id FROM users WHERE id != ?", [$user_id]);
        
        foreach ($users as $user) {
            // Insert or update share record
            $this->db->query(
                "INSERT INTO dashboard_shares (dashboard_id, shared_with_user_id, permissions, shared_at)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE permissions = VALUES(permissions), shared_at = VALUES(shared_at)",
                [$dashboard_id, $user['id'], $permissions, date('Y-m-d H:i:s')]
            );
        }
        
        return true;
    }
    
    /**
     * Track dashboard view
     */
    public function trackDashboardView(int $dashboard_id, int $user_id): void
    {
        $this->db->insert('dashboard_views', [
            'dashboard_id' => $dashboard_id,
            'user_id' => $user_id,
            'viewed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get dashboard usage statistics
     */
    public function getDashboardUsage(int $dashboard_id): array
    {
        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT user_id) as unique_viewers,
                MAX(viewed_at) as last_viewed,
                COUNT(CASE WHEN viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as views_last_7_days
            FROM dashboard_views 
            WHERE dashboard_id = ?",
            [$dashboard_id]
        );
        
        return $stats ?: [
            'total_views' => 0,
            'unique_viewers' => 0,
            'last_viewed' => null,
            'views_last_7_days' => 0
        ];
    }
    
    /**
     * Get available widget types
     */
    public function getAvailableWidgets(): array
    {
        return [
            'metric' => [
                'name' => 'Metric Card',
                'description' => 'Display a single metric with trend',
                'icon' => 'chart-bar',
                'category' => 'metrics',
                'settings' => [
                    'metric_type' => ['pageviews', 'unique_visitors', 'bounce_rate', 'avg_session_duration'],
                    'time_period' => ['1d', '7d', '30d', '90d'],
                    'comparison' => ['previous_period', 'previous_year', 'none']
                ]
            ],
            'chart' => [
                'name' => 'Chart Widget',
                'description' => 'Line, bar, or pie charts',
                'icon' => 'chart-line',
                'category' => 'charts',
                'settings' => [
                    'chart_type' => ['line', 'bar', 'pie', 'area'],
                    'data_source' => ['pageviews', 'visitors', 'events', 'referrers'],
                    'time_period' => ['1d', '7d', '30d', '90d'],
                    'grouping' => ['hour', 'day', 'week', 'month']
                ]
            ],
            'list' => [
                'name' => 'Data List',
                'description' => 'Top pages, referrers, or events',
                'icon' => 'list-bullet',
                'category' => 'data',
                'settings' => [
                    'list_type' => ['top_pages', 'top_referrers', 'top_events', 'recent_events'],
                    'limit' => [5, 10, 20, 50],
                    'time_period' => ['1d', '7d', '30d', '90d']
                ]
            ],
            'map' => [
                'name' => 'Geographic Map',
                'description' => 'Visitor locations on world map',
                'icon' => 'globe-americas',
                'category' => 'geo',
                'settings' => [
                    'map_type' => ['world', 'country'],
                    'metric' => ['visitors', 'pageviews', 'sessions'],
                    'time_period' => ['1d', '7d', '30d', '90d']
                ]
            ],
            'funnel' => [
                'name' => 'Conversion Funnel',
                'description' => 'Step-by-step conversion analysis',
                'icon' => 'funnel',
                'category' => 'conversion',
                'settings' => [
                    'funnel_id' => 'select', // Will be populated with user's funnels
                    'time_period' => ['1d', '7d', '30d', '90d']
                ]
            ],
            'realtime' => [
                'name' => 'Real-time Feed',
                'description' => 'Live visitor activity',
                'icon' => 'signal',
                'category' => 'realtime',
                'settings' => [
                    'feed_type' => ['active_visitors', 'recent_pageviews', 'recent_events'],
                    'refresh_interval' => [5, 10, 30, 60]
                ]
            ]
        ];
    }
    
    /**
     * Get data sources for widgets
     */
    public function getDataSources(int $user_id): array
    {
        // Get user's sites
        $sites = Site::getUserSites($user_id);
        
        // Get user's funnels
        require_once APP_PATH . '/lib/Funnel.php';
        $funnel = new Funnel();
        $funnels = $funnel->getUserFunnels($user_id);
        
        return [
            'sites' => $sites,
            'funnels' => $funnels,
            'metrics' => [
                'pageviews' => 'Page Views',
                'unique_visitors' => 'Unique Visitors',
                'sessions' => 'Sessions',
                'bounce_rate' => 'Bounce Rate',
                'avg_session_duration' => 'Avg. Session Duration',
                'conversion_rate' => 'Conversion Rate'
            ],
            'time_periods' => [
                '1d' => 'Today',
                '7d' => 'Last 7 days',
                '30d' => 'Last 30 days',
                '90d' => 'Last 90 days'
            ]
        ];
    }
    
    /**
     * Get data for all widgets in a dashboard
     */
    public function getWidgetData(array $widgets, int $user_id): array
    {
        $widgetData = [];
        
        foreach ($widgets as $widget) {
            $widgetData[$widget['id']] = $this->getSingleWidgetData($widget['type'], [
                'settings' => $widget['settings'] ?? [],
                'user_id' => $user_id,
                'widget_id' => $widget['id']
            ]);
        }
        
        return $widgetData;
    }
    
    /**
     * Get data for a single widget
     */
    public function getSingleWidgetData(string $widget_type, array $params): array
    {
        $settings = $params['settings'] ?? [];
        $user_id = $params['user_id'];
        $site_id = $params['site_id'] ?? ($settings['site_id'] ?? null);
        
        // Get date range
        $time_period = $settings['time_period'] ?? '30d';
        $date_range = $this->getDateRangeFromPeriod($time_period);
        
        switch ($widget_type) {
            case 'metric':
                return $this->getMetricWidgetData($settings, $site_id, $date_range);
                
            case 'chart':
                return $this->getChartWidgetData($settings, $site_id, $date_range);
                
            case 'list':
                return $this->getListWidgetData($settings, $site_id, $date_range);
                
            case 'map':
                return $this->getMapWidgetData($settings, $site_id, $date_range);
                
            case 'funnel':
                return $this->getFunnelWidgetData($settings, $user_id, $date_range);
                
            case 'realtime':
                return $this->getRealtimeWidgetData($settings, $site_id);
                
            default:
                return ['error' => 'Unknown widget type'];
        }
    }
    
    /**
     * Get metric widget data
     */
    private function getMetricWidgetData(array $settings, ?int $site_id, array $date_range): array
    {
        if (!$site_id) {
            return ['error' => 'Site ID required'];
        }
        
        $metric_type = $settings['metric_type'] ?? 'pageviews';
        $comparison = $settings['comparison'] ?? 'previous_period';
        
        // Get current period data
        $current_data = $this->analytics->getSiteOverview($site_id, $date_range);
        
        // Get comparison data
        $comparison_data = null;
        if ($comparison === 'previous_period') {
            $days_diff = (strtotime($date_range['end']) - strtotime($date_range['start'])) / (24 * 60 * 60) + 1;
            $prev_start = date('Y-m-d', strtotime($date_range['start'] . " -{$days_diff} days"));
            $prev_end = date('Y-m-d', strtotime($date_range['start'] . ' -1 day'));
            
            $comparison_data = $this->analytics->getSiteOverview($site_id, [
                'start' => $prev_start,
                'end' => $prev_end
            ]);
        }
        
        $current_value = $current_data[$metric_type] ?? 0;
        $previous_value = $comparison_data[$metric_type] ?? 0;
        
        $change = 0;
        $change_percent = 0;
        
        if ($previous_value > 0) {
            $change = $current_value - $previous_value;
            $change_percent = (($current_value - $previous_value) / $previous_value) * 100;
        }
        
        return [
            'current_value' => $current_value,
            'previous_value' => $previous_value,
            'change' => $change,
            'change_percent' => round($change_percent, 1),
            'trend' => $change >= 0 ? 'up' : 'down',
            'metric_type' => $metric_type,
            'date_range' => $date_range
        ];
    }
    
    /**
     * Get chart widget data
     */
    private function getChartWidgetData(array $settings, ?int $site_id, array $date_range): array
    {
        if (!$site_id) {
            return ['error' => 'Site ID required'];
        }
        
        $chart_type = $settings['chart_type'] ?? 'line';
        $data_source = $settings['data_source'] ?? 'pageviews';
        $grouping = $settings['grouping'] ?? 'day';
        
        switch ($data_source) {
            case 'pageviews':
                $data = $this->analytics->getPageviewTrends($site_id, $date_range, $grouping);
                break;
            case 'visitors':
                $data = $this->analytics->getVisitorTrends($site_id, $date_range, $grouping);
                break;
            default:
                $data = [];
        }
        
        return [
            'chart_type' => $chart_type,
            'data_source' => $data_source,
            'data' => $data,
            'date_range' => $date_range
        ];
    }
    
    /**
     * Get list widget data
     */
    private function getListWidgetData(array $settings, ?int $site_id, array $date_range): array
    {
        if (!$site_id) {
            return ['error' => 'Site ID required'];
        }
        
        $list_type = $settings['list_type'] ?? 'top_pages';
        $limit = $settings['limit'] ?? 10;
        
        switch ($list_type) {
            case 'top_pages':
                $data = Site::getTopPages($site_id, $date_range['start'], $date_range['end'], $limit);
                break;
            case 'top_referrers':
                $data = Site::getTopReferrers($site_id, $date_range['start'], $date_range['end'], $limit);
                break;
            case 'recent_events':
                $data = Event::getRealtimeEvents($site_id, $limit);
                break;
            default:
                $data = [];
        }
        
        return [
            'list_type' => $list_type,
            'data' => $data,
            'limit' => $limit,
            'date_range' => $date_range
        ];
    }
    
    /**
     * Get map widget data
     */
    private function getMapWidgetData(array $settings, ?int $site_id, array $date_range): array
    {
        if (!$site_id) {
            return ['error' => 'Site ID required'];
        }
        
        $metric = $settings['metric'] ?? 'visitors';
        $map_type = $settings['map_type'] ?? 'world';
        
        // Get geographic data
        $data = Person::getVisitorGeography($site_id, $date_range['start'], $date_range['end']);
        
        return [
            'map_type' => $map_type,
            'metric' => $metric,
            'data' => $data,
            'date_range' => $date_range
        ];
    }
    
    /**
     * Get funnel widget data
     */
    private function getFunnelWidgetData(array $settings, int $user_id, array $date_range): array
    {
        $funnel_id = $settings['funnel_id'] ?? null;
        
        if (!$funnel_id) {
            return ['error' => 'Funnel ID required'];
        }
        
        require_once APP_PATH . '/lib/Funnel.php';
        $funnel = new Funnel();
        
        $funnel_data = $funnel->getFunnelAnalysis($funnel_id, $user_id, $date_range);
        
        return [
            'funnel_id' => $funnel_id,
            'data' => $funnel_data,
            'date_range' => $date_range
        ];
    }
    
    /**
     * Get realtime widget data
     */
    private function getRealtimeWidgetData(array $settings, ?int $site_id): array
    {
        if (!$site_id) {
            return ['error' => 'Site ID required'];
        }
        
        $feed_type = $settings['feed_type'] ?? 'active_visitors';
        
        switch ($feed_type) {
            case 'active_visitors':
                $data = $this->analytics->getLiveVisitors($site_id);
                break;
            case 'recent_pageviews':
                $data = $this->getRecentPageviews($site_id, 20);
                break;
            case 'recent_events':
                $data = Event::getRealtimeEvents($site_id, 20);
                break;
            default:
                $data = [];
        }
        
        return [
            'feed_type' => $feed_type,
            'data' => $data,
            'last_updated' => time()
        ];
    }
    
    /**
     * Get recent pageviews for real-time widgets
     */
    private function getRecentPageviews(int $site_id, int $limit = 20): array
    {
        return $this->db->select(
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
     * Convert time period to date range
     */
    private function getDateRangeFromPeriod(string $period): array
    {
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
                break;
            case '90d':
                $start = date('Y-m-d', strtotime('-89 days'));
                $end = date('Y-m-d');
                break;
        }
        
        return ['start' => $start, 'end' => $end];
    }
}
?>
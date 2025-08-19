<?php
/**
 * Funnel Library
 * 
 * Handles funnel analysis calculations, conversion rate calculations, and drop-off analysis
 */

class Funnel
{
    /**
     * Get all funnels for a site with analytics
     */
    public static function getFunnelsBySite(int $site_id, string $start_date, string $end_date): array
    {
        $funnels = Database::select(
            "SELECT f.*, 
                    COALESCE(AVG(fa.overall_conversion_rate), 0) as conversion_rate,
                    COALESCE(SUM(fa.total_conversions), 0) as total_conversions
             FROM funnels f
             LEFT JOIN funnel_analytics fa ON f.id = fa.funnel_id 
                AND fa.date BETWEEN ? AND ?
             WHERE f.site_id = ? AND f.status = 'active'
             GROUP BY f.id
             ORDER BY f.created_at DESC",
            [$start_date, $end_date, $site_id]
        );
        
        return $funnels ?: [];
    }
    
    /**
     * Get funnel by ID
     */
    public static function getFunnelById(int $funnel_id): ?array
    {
        return Database::selectOne(
            "SELECT * FROM funnels WHERE id = ?",
            [$funnel_id]
        );
    }
    
    /**
     * Get funnel steps with performance data
     */
    public static function getFunnelSteps(int $funnel_id, string $start_date, string $end_date): array
    {
        $steps = Database::select(
            "SELECT fs.*,
                    COALESCE(AVG(fsp.users_entered), 0) as user_count,
                    COALESCE(AVG(fsp.conversion_rate), 0) as conversion_rate,
                    COALESCE(AVG(fsp.drop_off_rate), 0) as dropoff_rate,
                    COALESCE(AVG(fsp.avg_time_on_step), 0) as avg_time_on_step
             FROM funnel_steps fs
             LEFT JOIN funnel_step_performance fsp ON fs.id = fsp.step_id 
                AND fsp.date BETWEEN ? AND ?
             WHERE fs.funnel_id = ?
             GROUP BY fs.id
             ORDER BY fs.step_order",
            [$start_date, $end_date, $funnel_id]
        );
        
        // Add critical drop-off detection
        foreach ($steps as &$step) {
            $step['conditions'] = json_decode($step['conditions'], true);
            $step['is_critical_dropoff'] = $step['dropoff_rate'] > 50;
        }
        
        return $steps ?: [];
    }
    
    /**
     * Get funnel steps by ID (for editing)
     */
    public static function getFunnelStepsById(int $funnel_id): array
    {
        $steps = Database::select(
            "SELECT * FROM funnel_steps WHERE funnel_id = ? ORDER BY step_order",
            [$funnel_id]
        );
        
        foreach ($steps as &$step) {
            $step['conditions'] = json_decode($step['conditions'], true);
        }
        
        return $steps ?: [];
    }
    
    /**
     * Get funnel overview stats
     */
    public static function getFunnelStats(int $site_id, string $start_date, string $end_date): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(DISTINCT f.id) as total_funnels,
                COALESCE(AVG(fa.overall_conversion_rate), 0) as avg_conversion_rate,
                COALESCE(SUM(fa.total_conversions), 0) as total_conversions,
                COALESCE(AVG(fsp.drop_off_rate), 0) as avg_dropoff_rate
             FROM funnels f
             LEFT JOIN funnel_analytics fa ON f.id = fa.funnel_id 
                AND fa.date BETWEEN ? AND ?
             LEFT JOIN funnel_step_performance fsp ON f.id = fsp.funnel_id 
                AND fsp.date BETWEEN ? AND ?
             WHERE f.site_id = ? AND f.status = 'active'",
            [$start_date, $end_date, $start_date, $end_date, $site_id]
        );
        
        return $stats ?: [
            'total_funnels' => 0,
            'avg_conversion_rate' => 0,
            'total_conversions' => 0,
            'avg_dropoff_rate' => 0
        ];
    }
    
    /**
     * Create new funnel
     */
    public static function createFunnel(array $data): int
    {
        Database::beginTransaction();
        
        try {
            // Create funnel record
            $funnel_id = Database::insert(
                "INSERT INTO funnels (site_id, name, description, created_by, status, settings)
                 VALUES (?, ?, ?, ?, 'active', ?)",
                [
                    $data['site_id'],
                    $data['name'],
                    $data['description'],
                    $data['created_by'],
                    json_encode($data['settings'] ?? [])
                ]
            );
            
            // Create funnel steps
            foreach ($data['steps'] as $index => $step) {
                Database::insert(
                    "INSERT INTO funnel_steps (funnel_id, step_order, name, step_type, conditions, is_required)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $funnel_id,
                        $index + 1,
                        $step['name'],
                        $step['type'],
                        json_encode($step['conditions']),
                        $step['is_required'] ?? true
                    ]
                );
            }
            
            Database::commit();
            return $funnel_id;
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
    
    /**
     * Update existing funnel
     */
    public static function updateFunnel(int $funnel_id, array $data): void
    {
        Database::beginTransaction();
        
        try {
            // Update funnel record
            Database::update(
                "UPDATE funnels SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
                 WHERE id = ?",
                [$data['name'], $data['description'], $funnel_id]
            );
            
            // Delete existing steps
            Database::delete("DELETE FROM funnel_steps WHERE funnel_id = ?", [$funnel_id]);
            
            // Create new steps
            foreach ($data['steps'] as $index => $step) {
                Database::insert(
                    "INSERT INTO funnel_steps (funnel_id, step_order, name, step_type, conditions, is_required)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $funnel_id,
                        $index + 1,
                        $step['name'],
                        $step['type'],
                        json_encode($step['conditions']),
                        $step['is_required'] ?? true
                    ]
                );
            }
            
            Database::commit();
            
        } catch (Exception $e) {
            Database::rollback();
            throw $e;
        }
    }
    
    /**
     * Delete funnel
     */
    public static function deleteFunnel(int $funnel_id): void
    {
        // Cascade delete will handle related records
        Database::delete("DELETE FROM funnels WHERE id = ?", [$funnel_id]);
    }
    
    /**
     * Analyze funnel performance
     */
    public static function analyzeFunnel(int $funnel_id, string $start_date, string $end_date): array
    {
        $analysis = [];
        
        // Get funnel details
        $funnel = self::getFunnelById($funnel_id);
        $steps = self::getFunnelSteps($funnel_id, $start_date, $end_date);
        
        // Overall metrics
        $overall_stats = Database::selectOne(
            "SELECT 
                AVG(overall_conversion_rate) as avg_conversion_rate,
                SUM(total_conversions) as total_conversions,
                AVG(avg_time_to_convert) as avg_time_to_convert
             FROM funnel_analytics 
             WHERE funnel_id = ? AND date BETWEEN ? AND ?",
            [$funnel_id, $start_date, $end_date]
        );
        
        // Step-by-step analysis
        $step_analysis = [];
        foreach ($steps as $step) {
            $step_analysis[] = [
                'step_name' => $step['name'],
                'step_order' => $step['step_order'],
                'user_count' => $step['user_count'],
                'conversion_rate' => $step['conversion_rate'],
                'dropoff_rate' => $step['dropoff_rate'],
                'avg_time_on_step' => $step['avg_time_on_step'],
                'is_critical_dropoff' => $step['is_critical_dropoff']
            ];
        }
        
        // Time-series data
        $time_series = Database::select(
            "SELECT date, overall_conversion_rate, total_conversions
             FROM funnel_analytics 
             WHERE funnel_id = ? AND date BETWEEN ? AND ?
             ORDER BY date",
            [$funnel_id, $start_date, $end_date]
        );
        
        return [
            'funnel' => $funnel,
            'overall_stats' => $overall_stats ?: ['avg_conversion_rate' => 0, 'total_conversions' => 0, 'avg_time_to_convert' => 0],
            'step_analysis' => $step_analysis,
            'time_series' => $time_series ?: [],
            'insights' => self::generateFunnelInsights($funnel_id, $start_date, $end_date)
        ];
    }
    
    /**
     * Get funnel performance over time
     */
    public static function getFunnelPerformance(int $funnel_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                date,
                overall_conversion_rate,
                total_conversions,
                step_1_users,
                step_2_users,
                step_3_users,
                step_4_users,
                step_5_users,
                avg_time_to_convert
             FROM funnel_analytics 
             WHERE funnel_id = ? AND date BETWEEN ? AND ?
             ORDER BY date",
            [$funnel_id, $start_date, $end_date]
        ) ?: [];
    }
    
    /**
     * Generate funnel insights and recommendations
     */
    public static function generateFunnelInsights(int $funnel_id, string $start_date, string $end_date): array
    {
        $insights = [];
        $steps = self::getFunnelSteps($funnel_id, $start_date, $end_date);
        
        if (empty($steps)) {
            return $insights;
        }
        
        // Find biggest drop-off point
        $max_dropoff = 0;
        $max_dropoff_step = null;
        
        foreach ($steps as $step) {
            if ($step['dropoff_rate'] > $max_dropoff) {
                $max_dropoff = $step['dropoff_rate'];
                $max_dropoff_step = $step;
            }
        }
        
        if ($max_dropoff_step && $max_dropoff > 40) {
            $insights[] = "Highest drop-off at step '{$max_dropoff_step['name']}' ({$max_dropoff}%). Consider optimizing this step.";
        }
        
        // Check for time bottlenecks
        $avg_time = 0;
        $time_count = 0;
        foreach ($steps as $step) {
            if ($step['avg_time_on_step'] > 0) {
                $avg_time += $step['avg_time_on_step'];
                $time_count++;
            }
        }
        
        if ($time_count > 0) {
            $avg_time = $avg_time / $time_count;
            foreach ($steps as $step) {
                if ($step['avg_time_on_step'] > $avg_time * 2) {
                    $insights[] = "Users spend unusually long time on step '{$step['name']}'. This might indicate confusion or technical issues.";
                }
            }
        }
        
        // Overall conversion rate analysis
        $overall_conversion = Database::selectOne(
            "SELECT AVG(overall_conversion_rate) as rate
             FROM funnel_analytics 
             WHERE funnel_id = ? AND date BETWEEN ? AND ?",
            [$funnel_id, $start_date, $end_date]
        );
        
        if ($overall_conversion && $overall_conversion['rate'] > 0) {
            $rate = $overall_conversion['rate'];
            if ($rate < 10) {
                $insights[] = "Overall conversion rate is low ({$rate}%). Consider simplifying the funnel or improving user experience.";
            } elseif ($rate > 50) {
                $insights[] = "Excellent conversion rate ({$rate}%)! This funnel is performing very well.";
            }
        }
        
        // Check for step order issues
        $prev_conversion = 100;
        foreach ($steps as $step) {
            if ($step['conversion_rate'] > $prev_conversion) {
                $insights[] = "Step '{$step['name']}' has higher conversion than previous step. Check if steps are in optimal order.";
            }
            $prev_conversion = $step['conversion_rate'];
        }
        
        return $insights;
    }
    
    /**
     * Get available events for funnel building
     */
    public static function getAvailableEvents(int $site_id): array
    {
        return Database::select(
            "SELECT DISTINCT event_name, event_category, COUNT(*) as event_count
             FROM events 
             WHERE site_id = ? 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY event_name, event_category
             ORDER BY event_count DESC
             LIMIT 20",
            [$site_id]
        ) ?: [];
    }
    
    /**
     * Get popular pages for funnel building
     */
    public static function getPopularPages(int $site_id): array
    {
        return Database::select(
            "SELECT DISTINCT page_path, page_title, COUNT(*) as pageview_count
             FROM pageviews 
             WHERE site_id = ? 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY page_path, page_title
             ORDER BY pageview_count DESC
             LIMIT 20",
            [$site_id]
        ) ?: [];
    }
    
    /**
     * Get active funnels for a site
     */
    public static function getActiveFunnelsBySite(int $site_id): array
    {
        return Database::select(
            "SELECT f.*, 
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', fs.id,
                            'step_order', fs.step_order,
                            'name', fs.name,
                            'step_type', fs.step_type,
                            'conditions', fs.conditions,
                            'is_required', fs.is_required
                        )
                        ORDER BY fs.step_order
                    ) as steps
             FROM funnels f
             LEFT JOIN funnel_steps fs ON f.id = fs.funnel_id
             WHERE f.site_id = ? AND f.status = 'active'
             GROUP BY f.id
             ORDER BY f.created_at DESC",
            [$site_id]
        ) ?: [];
    }
    
    /**
     * Process session data for funnel tracking
     */
    public static function processSessionForFunnel(int $funnel_id, string $session_id, array $event_data): void
    {
        // Get funnel steps
        $steps = Database::select(
            "SELECT * FROM funnel_steps WHERE funnel_id = ? ORDER BY step_order",
            [$funnel_id]
        );
        
        if (empty($steps)) {
            return;
        }
        
        // Get or create funnel user session
        $funnel_session = Database::selectOne(
            "SELECT * FROM funnel_user_sessions WHERE funnel_id = ? AND session_id = ?",
            [$funnel_id, $session_id]
        );
        
        if (!$funnel_session) {
            // Create new funnel session
            $session_data = Database::selectOne(
                "SELECT user_hash FROM sessions WHERE id = ?",
                [$session_id]
            );
            
            if ($session_data) {
                Database::insert(
                    "INSERT INTO funnel_user_sessions 
                     (funnel_id, session_id, user_hash, started_at, date, steps_data)
                     VALUES (?, ?, ?, NOW(), CURDATE(), ?)",
                    [
                        $funnel_id,
                        $session_id,
                        $session_data['user_hash'],
                        json_encode([])
                    ]
                );
                
                $funnel_session = [
                    'funnel_id' => $funnel_id,
                    'session_id' => $session_id,
                    'user_hash' => $session_data['user_hash'],
                    'last_step_reached' => 0,
                    'is_converted' => false,
                    'steps_data' => []
                ];
            } else {
                return;
            }
        } else {
            $funnel_session['steps_data'] = json_decode($funnel_session['steps_data'], true) ?: [];
        }
        
        // Check if this event matches any funnel step
        $current_step = $funnel_session['last_step_reached'];
        
        foreach ($steps as $step) {
            if ($step['step_order'] <= $current_step) {
                continue; // Already completed this step
            }
            
            $conditions = json_decode($step['conditions'], true);
            
            if (self::doesEventMatchStep($event_data, $conditions, $step['step_type'])) {
                // Update funnel session
                $funnel_session['steps_data'][$step['step_order']] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'event_data' => $event_data
                ];
                
                $is_final_step = $step['step_order'] == count($steps);
                
                Database::update(
                    "UPDATE funnel_user_sessions 
                     SET last_step_reached = ?, 
                         steps_data = ?,
                         is_converted = ?,
                         completed_at = ?,
                         conversion_time = ?
                     WHERE funnel_id = ? AND session_id = ?",
                    [
                        $step['step_order'],
                        json_encode($funnel_session['steps_data']),
                        $is_final_step ? 1 : 0,
                        $is_final_step ? date('Y-m-d H:i:s') : null,
                        $is_final_step ? self::calculateConversionTime($funnel_session['steps_data']) : null,
                        $funnel_id,
                        $session_id
                    ]
                );
                
                // Only process the next step in sequence
                break;
            }
        }
    }
    
    /**
     * Check if event matches step conditions
     */
    private static function doesEventMatchStep(array $event_data, array $conditions, string $step_type): bool
    {
        switch ($step_type) {
            case 'pageview':
                if (isset($event_data['page_path']) && isset($conditions['page_path'])) {
                    return self::matchesPattern($event_data['page_path'], $conditions['page_path']);
                }
                return false;
                
            case 'event':
                if (isset($event_data['event_name']) && isset($conditions['event_name'])) {
                    $event_match = $event_data['event_name'] === $conditions['event_name'];
                    
                    // Optional category filter
                    if (isset($conditions['event_category']) && !empty($conditions['event_category'])) {
                        $category_match = isset($event_data['event_category']) &&
                                        $event_data['event_category'] === $conditions['event_category'];
                        return $event_match && $category_match;
                    }
                    
                    return $event_match;
                }
                return false;
                
            case 'custom':
                // Custom conditions can be more complex
                return self::evaluateCustomConditions($event_data, $conditions);
                
            default:
                return false;
        }
    }
    
    /**
     * Match pattern (supports wildcards)
     */
    private static function matchesPattern(string $value, string $pattern): bool
    {
        if ($pattern === $value) {
            return true;
        }
        
        // Convert wildcard pattern to regex
        $regex = str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/'));
        return preg_match("/^{$regex}$/", $value);
    }
    
    /**
     * Evaluate custom conditions
     */
    private static function evaluateCustomConditions(array $event_data, array $conditions): bool
    {
        // This is a simplified implementation
        // In a real system, you might want to use a more sophisticated rule engine
        
        foreach ($conditions as $key => $expected_value) {
            if (!isset($event_data[$key])) {
                return false;
            }
            
            if (is_array($expected_value)) {
                if (!in_array($event_data[$key], $expected_value)) {
                    return false;
                }
            } else {
                if ($event_data[$key] != $expected_value) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Calculate conversion time from steps data
     */
    private static function calculateConversionTime(array $steps_data): ?int
    {
        if (empty($steps_data)) {
            return null;
        }
        
        $first_step_time = null;
        $last_step_time = null;
        
        foreach ($steps_data as $step_data) {
            $timestamp = strtotime($step_data['timestamp']);
            if ($first_step_time === null || $timestamp < $first_step_time) {
                $first_step_time = $timestamp;
            }
            if ($last_step_time === null || $timestamp > $last_step_time) {
                $last_step_time = $timestamp;
            }
        }
        
        return $first_step_time && $last_step_time ? ($last_step_time - $first_step_time) : null;
    }
    
    /**
     * Calculate daily funnel analytics (to be run by cron job)
     */
    public static function calculateDailyAnalytics(int $funnel_id, string $date): void
    {
        Database::query(
            "CALL CalculateFunnelAnalytics(?, ?)",
            [$funnel_id, $date]
        );
    }
}
?>
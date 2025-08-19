<?php
/**
 * Site Model
 * 
 * Manages website tracking configuration and analytics data.
 */

class Site
{
    /**
     * Get all sites for a user
     */
    public static function getUserSites(int $user_id): array
    {
        return Database::select(
            "SELECT id, domain, name, tracking_code, timezone, created_at, is_active, settings
             FROM sites 
             WHERE user_id = ? AND is_active = 1
             ORDER BY name ASC",
            [$user_id]
        );
    }
    
    /**
     * Get site by ID
     */
    public static function getById(int $site_id, int $user_id = null): ?array
    {
        $query = "SELECT s.*, u.email as owner_email, u.username as owner_username
                  FROM sites s 
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.id = ?";
        $params = [$site_id];
        
        if ($user_id !== null) {
            $query .= " AND s.user_id = ?";
            $params[] = $user_id;
        }
        
        return Database::selectOne($query, $params);
    }
    
    /**
     * Get site by tracking code
     */
    public static function getByTrackingCode(string $tracking_code): ?array
    {
        return Database::selectOne(
            "SELECT id, user_id, domain, name, tracking_code, timezone, is_active, settings
             FROM sites 
             WHERE tracking_code = ? AND is_active = 1",
            [$tracking_code]
        );
    }
    
    /**
     * Create new site
     */
    public static function create(array $data): array
    {
        try {
            // Validate required fields
            $validation = self::validateSiteData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Check site limit for user
            $site_limit = self::getUserSiteLimit($data['user_id']);
            $current_sites = Database::selectOne(
                "SELECT COUNT(*) as count FROM sites WHERE user_id = ? AND is_active = 1",
                [$data['user_id']]
            );
            
            if ($current_sites['count'] >= $site_limit) {
                return [
                    'success' => false,
                    'error' => "Site limit reached. Maximum {$site_limit} sites allowed."
                ];
            }
            
            // Check if domain already exists
            $existing_site = Database::selectOne(
                "SELECT id FROM sites WHERE domain = ? AND is_active = 1",
                [$data['domain']]
            );
            
            if ($existing_site) {
                return [
                    'success' => false,
                    'error' => 'A site with this domain already exists.'
                ];
            }
            
            // Generate unique tracking code
            $tracking_code = self::generateTrackingCode();
            while (self::getByTrackingCode($tracking_code)) {
                $tracking_code = self::generateTrackingCode();
            }
            
            // Default settings
            $default_settings = [
                'enable_realtime' => true,
                'track_outbound_links' => true,
                'respect_dnt' => false,
                'exclude_ips' => [],
                'exclude_user_agents' => []
            ];
            
            $settings = array_merge($default_settings, $data['settings'] ?? []);
            
            // Insert site
            $site_id = Database::insert(
                "INSERT INTO sites (user_id, domain, name, tracking_code, timezone, is_active, settings, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())",
                [
                    $data['user_id'],
                    $data['domain'],
                    $data['name'],
                    $tracking_code,
                    $data['timezone'] ?? 'UTC',
                    json_encode($settings)
                ]
            );
            
            if ($site_id) {
                return [
                    'success' => true,
                    'site_id' => $site_id,
                    'tracking_code' => $tracking_code
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to create site.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Site creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Site creation failed.'
            ];
        }
    }
    
    /**
     * Update site
     */
    public static function update(int $site_id, array $data, int $user_id): array
    {
        try {
            // Verify ownership
            $site = self::getById($site_id, $user_id);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found or access denied.'
                ];
            }
            
            // Validate data
            if (!empty($data['domain'])) {
                // Check if domain is taken by another site
                $existing_site = Database::selectOne(
                    "SELECT id FROM sites WHERE domain = ? AND id != ? AND is_active = 1",
                    [$data['domain'], $site_id]
                );
                
                if ($existing_site) {
                    return [
                        'success' => false,
                        'error' => 'Domain is already taken by another site.'
                    ];
                }
            }
            
            // Build update query
            $update_fields = [];
            $params = [];
            
            if (!empty($data['name'])) {
                $update_fields[] = 'name = ?';
                $params[] = $data['name'];
            }
            
            if (!empty($data['domain'])) {
                $update_fields[] = 'domain = ?';
                $params[] = $data['domain'];
            }
            
            if (!empty($data['timezone'])) {
                $update_fields[] = 'timezone = ?';
                $params[] = $data['timezone'];
            }
            
            if (isset($data['settings'])) {
                $current_settings = json_decode($site['settings'] ?? '{}', true);
                $new_settings = array_merge($current_settings, $data['settings']);
                $update_fields[] = 'settings = ?';
                $params[] = json_encode($new_settings);
            }
            
            if (empty($update_fields)) {
                return [
                    'success' => false,
                    'error' => 'No fields to update.'
                ];
            }
            
            $update_fields[] = 'updated_at = NOW()';
            $params[] = $site_id;
            
            $affected_rows = Database::update(
                "UPDATE sites SET " . implode(', ', $update_fields) . " WHERE id = ?",
                $params
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Site updated successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No changes were made.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Site update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Site update failed.'
            ];
        }
    }
    
    /**
     * Delete site (soft delete)
     */
    public static function delete(int $site_id, int $user_id): array
    {
        try {
            // Verify ownership
            $site = self::getById($site_id, $user_id);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found or access denied.'
                ];
            }
            
            $affected_rows = Database::update(
                "UPDATE sites SET is_active = 0, updated_at = NOW() WHERE id = ?",
                [$site_id]
            );
            
            if ($affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Site deleted successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to delete site.'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Site deletion error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Site deletion failed.'
            ];
        }
    }
    
    /**
     * Get site analytics overview
     */
    public static function getAnalyticsOverview(int $site_id, string $start_date, string $end_date): array
    {
        $overview = Database::selectOne(
            "SELECT 
                COUNT(p.id) as total_pageviews,
                COUNT(DISTINCT s.user_hash) as unique_visitors,
                COUNT(DISTINCT s.id) as total_sessions,
                ROUND(AVG(s.page_count), 1) as avg_pages_per_session,
                ROUND(AVG(TIMESTAMPDIFF(SECOND, s.first_visit, s.last_activity)), 0) as avg_session_duration,
                ROUND(AVG(CASE WHEN s.is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate,
                COUNT(e.id) as total_events
             FROM sessions s
             LEFT JOIN pageviews p ON s.id = p.session_id
             LEFT JOIN events e ON s.id = e.session_id
             WHERE s.site_id = ? 
             AND DATE(s.first_visit) BETWEEN ? AND ?",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
        
        return $overview ?: [];
    }
    
    /**
     * Get site traffic trends
     */
    public static function getTrafficTrends(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                DATE(s.first_visit) as date,
                COUNT(p.id) as pageviews,
                COUNT(DISTINCT s.user_hash) as unique_visitors,
                COUNT(DISTINCT s.id) as sessions,
                ROUND(AVG(CASE WHEN s.is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate
             FROM sessions s
             LEFT JOIN pageviews p ON s.id = p.session_id
             WHERE s.site_id = ? 
             AND DATE(s.first_visit) BETWEEN ? AND ?
             GROUP BY DATE(s.first_visit)
             ORDER BY date ASC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get top pages for site
     */
    public static function getTopPages(int $site_id, string $start_date, string $end_date, int $limit = 20): array
    {
        return Database::select(
            "SELECT 
                p.page_path,
                COUNT(p.id) as pageviews,
                COUNT(DISTINCT p.session_id) as unique_sessions,
                ROUND(AVG(p.load_time), 0) as avg_load_time
             FROM pageviews p
             JOIN sessions s ON p.session_id = s.id
             WHERE p.site_id = ? 
             AND DATE(p.timestamp) BETWEEN ? AND ?
             GROUP BY p.page_path
             ORDER BY pageviews DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get referrer sources for site
     */
    public static function getTopReferrers(int $site_id, string $start_date, string $end_date, int $limit = 20): array
    {
        return Database::select(
            "SELECT 
                COALESCE(s.referrer_domain, '(direct)') as referrer_domain,
                COUNT(s.id) as sessions,
                COUNT(DISTINCT s.user_hash) as unique_visitors,
                ROUND(AVG(s.page_count), 1) as avg_pages_per_session
             FROM sessions s
             WHERE s.site_id = ? 
             AND DATE(s.first_visit) BETWEEN ? AND ?
             GROUP BY s.referrer_domain
             ORDER BY sessions DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get real-time stats for site
     */
    public static function getRealtimeStats(int $site_id): array
    {
        $stats = [];
        
        // Active visitors (last 5 minutes)
        $active_visitors = Database::selectOne(
            "SELECT COUNT(DISTINCT session_id) as count 
             FROM realtime_visitors 
             WHERE site_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$site_id]
        );
        $stats['active_visitors'] = $active_visitors['count'] ?? 0;
        
        // Today's stats
        $today_stats = Database::selectOne(
            "SELECT 
                COUNT(DISTINCT s.user_hash) as unique_visitors_today,
                COUNT(p.id) as pageviews_today,
                COUNT(DISTINCT s.id) as sessions_today
             FROM sessions s
             LEFT JOIN pageviews p ON s.id = p.session_id
             WHERE s.site_id = ? 
             AND DATE(s.first_visit) = CURDATE()",
            [$site_id]
        );
        
        $stats = array_merge($stats, $today_stats ?: []);
        
        // Recent pageviews (last hour)
        $recent_pageviews = Database::selectOne(
            "SELECT COUNT(*) as count 
             FROM pageviews 
             WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$site_id]
        );
        $stats['pageviews_last_hour'] = $recent_pageviews['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Generate unique tracking code
     */
    private static function generateTrackingCode(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Validate site data
     */
    private static function validateSiteData(array $data): array
    {
        $errors = [];
        
        // User ID validation
        if (empty($data['user_id'])) {
            $errors[] = 'User ID is required';
        }
        
        // Domain validation
        if (empty($data['domain'])) {
            $errors[] = 'Domain is required';
        } elseif (!self::isValidDomain($data['domain'])) {
            $errors[] = 'Invalid domain format';
        }
        
        // Name validation
        if (empty($data['name'])) {
            $errors[] = 'Site name is required';
        } elseif (strlen($data['name']) > 255) {
            $errors[] = 'Site name is too long';
        }
        
        // Timezone validation
        if (!empty($data['timezone']) && !in_array($data['timezone'], timezone_identifiers_list())) {
            $errors[] = 'Invalid timezone';
        }
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(', ', $errors)
        ];
    }
    
    /**
     * Validate domain format
     */
    private static function isValidDomain(string $domain): bool
    {
        // Remove protocol if present
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Basic domain validation
        return preg_match('/^[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z]{2,}$/', $domain);
    }
    
    /**
     * Get user site limit
     */
    private static function getUserSiteLimit(int $user_id): int
    {
        $setting = Database::selectOne(
            "SELECT setting_value FROM settings WHERE setting_key = 'max_sites_per_user'"
        );
        
        return $setting ? (int)$setting['setting_value'] : 10;
    }
    
    /**
     * Get tracking script for site
     */
    public static function getTrackingScript(int $site_id): string
    {
        $site = self::getById($site_id);
        if (!$site || !$site['is_active']) {
            return '';
        }
        
        $tracking_code = $site['tracking_code'];
        $settings = json_decode($site['settings'] ?? '{}', true);
        
        return Tracker::getTrackingScript($tracking_code);
    }
    
    /**
     * Get site performance metrics
     */
    public static function getPerformanceMetrics(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                DATE(p.timestamp) as date,
                ROUND(AVG(p.load_time), 0) as avg_load_time,
                ROUND(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY p.load_time), 0) as median_load_time,
                ROUND(PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY p.load_time), 0) as p95_load_time,
                COUNT(p.id) as total_pageviews
             FROM pageviews p
             WHERE p.site_id = ? 
             AND p.load_time IS NOT NULL
             AND DATE(p.timestamp) BETWEEN ? AND ?
             GROUP BY DATE(p.timestamp)
             ORDER BY date ASC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Export site data
     */
    public static function exportSiteData(int $site_id, int $user_id, string $start_date, string $end_date, string $format = 'csv'): array
    {
        // Verify ownership
        $site = self::getById($site_id, $user_id);
        if (!$site) {
            return [
                'success' => false,
                'error' => 'Site not found or access denied.'
            ];
        }
        
        try {
            // Get comprehensive analytics data
            $data = [
                'site_info' => $site,
                'overview' => self::getAnalyticsOverview($site_id, $start_date, $end_date),
                'traffic_trends' => self::getTrafficTrends($site_id, $start_date, $end_date),
                'top_pages' => self::getTopPages($site_id, $start_date, $end_date, 50),
                'top_referrers' => self::getTopReferrers($site_id, $start_date, $end_date, 50),
            ];
            
            // Format data based on requested format
            if ($format === 'json') {
                $exported_data = json_encode($data, JSON_PRETTY_PRINT);
                $filename = "site_{$site_id}_analytics_{$start_date}_{$end_date}.json";
            } else {
                // CSV format - flatten the data
                $exported_data = self::formatDataAsCsv($data);
                $filename = "site_{$site_id}_analytics_{$start_date}_{$end_date}.csv";
            }
            
            return [
                'success' => true,
                'data' => $exported_data,
                'filename' => $filename,
                'content_type' => $format === 'json' ? 'application/json' : 'text/csv'
            ];
            
        } catch (Exception $e) {
            error_log("Site export error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Export failed.'
            ];
        }
    }
    
    /**
     * Format analytics data as CSV
     */
    private static function formatDataAsCsv(array $data): string
    {
        $csv = "Site Analytics Export\n\n";
        
        // Site info
        $csv .= "Site Information\n";
        $csv .= "Name,Domain,Tracking Code,Created\n";
        $csv .= "\"{$data['site_info']['name']}\",\"{$data['site_info']['domain']}\",\"{$data['site_info']['tracking_code']}\",\"{$data['site_info']['created_at']}\"\n\n";
        
        // Overview
        if (!empty($data['overview'])) {
            $csv .= "Analytics Overview\n";
            $csv .= "Metric,Value\n";
            foreach ($data['overview'] as $key => $value) {
                $csv .= "\"{$key}\",\"{$value}\"\n";
            }
            $csv .= "\n";
        }
        
        // Traffic trends
        if (!empty($data['traffic_trends'])) {
            $csv .= "Traffic Trends\n";
            $csv .= "Date,Pageviews,Unique Visitors,Sessions,Bounce Rate\n";
            foreach ($data['traffic_trends'] as $trend) {
                $csv .= "\"{$trend['date']}\",\"{$trend['pageviews']}\",\"{$trend['unique_visitors']}\",\"{$trend['sessions']}\",\"{$trend['bounce_rate']}\"\n";
            }
        }
        
        return $csv;
    }
    
    /**
     * Get site settings
     */
    public static function getSettings(int $site_id, int $user_id): array
    {
        $site = self::getById($site_id, $user_id);
        if (!$site) {
            return [];
        }
        
        return json_decode($site['settings'] ?? '{}', true);
    }
    
    /**
     * Update site settings
     */
    public static function updateSettings(int $site_id, int $user_id, array $settings): array
    {
        try {
            $site = self::getById($site_id, $user_id);
            if (!$site) {
                return [
                    'success' => false,
                    'error' => 'Site not found or access denied.'
                ];
            }
            
            $current_settings = json_decode($site['settings'] ?? '{}', true);
            $new_settings = array_merge($current_settings, $settings);
            
            Database::update(
                "UPDATE sites SET settings = ?, updated_at = NOW() WHERE id = ?",
                [json_encode($new_settings), $site_id]
            );
            
            return [
                'success' => true,
                'settings' => $new_settings
            ];
            
        } catch (Exception $e) {
            error_log("Settings update error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update settings.'
            ];
        }
    }
}
?>
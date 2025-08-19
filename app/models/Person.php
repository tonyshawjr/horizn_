<?php
/**
 * Person Model
 * 
 * Manages visitor identity tracking and user behavior analysis.
 * Handles both anonymous and identified users.
 */

class Person
{
    /**
     * Get visitor statistics for a site
     */
    public static function getVisitorStats(int $site_id, string $start_date, string $end_date): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(DISTINCT user_hash) as unique_visitors,
                COUNT(*) as total_sessions,
                ROUND(AVG(page_count), 1) as avg_pages_per_session,
                ROUND(AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)), 0) as avg_session_duration,
                ROUND(AVG(CASE WHEN is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
        
        return $stats ?: [];
    }
    
    /**
     * Get new vs returning visitors
     */
    public static function getNewVsReturningVisitors(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                CASE 
                    WHEN visitor_session_count = 1 THEN 'new'
                    ELSE 'returning'
                END as visitor_type,
                COUNT(*) as session_count,
                COUNT(DISTINCT user_hash) as unique_visitors
             FROM (
                SELECT 
                    user_hash,
                    COUNT(*) OVER (PARTITION BY user_hash) as visitor_session_count
                FROM sessions
                WHERE site_id = ? 
                AND DATE(first_visit) BETWEEN ? AND ?
             ) visitor_sessions
             GROUP BY visitor_type",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get visitor loyalty (session count distribution)
     */
    public static function getVisitorLoyalty(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                CASE 
                    WHEN session_count = 1 THEN '1 session'
                    WHEN session_count BETWEEN 2 AND 3 THEN '2-3 sessions'
                    WHEN session_count BETWEEN 4 AND 9 THEN '4-9 sessions'
                    WHEN session_count BETWEEN 10 AND 19 THEN '10-19 sessions'
                    ELSE '20+ sessions'
                END as loyalty_segment,
                COUNT(*) as visitor_count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
             FROM (
                SELECT 
                    user_hash,
                    COUNT(*) as session_count
                FROM sessions
                WHERE site_id = ? 
                AND DATE(first_visit) BETWEEN ? AND ?
                GROUP BY user_hash
             ) visitor_sessions
             GROUP BY loyalty_segment
             ORDER BY MIN(session_count)",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get visitor engagement levels
     */
    public static function getVisitorEngagement(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                CASE 
                    WHEN avg_pages_per_session = 1 AND avg_duration < 30 THEN 'Bounced'
                    WHEN avg_pages_per_session <= 2 AND avg_duration < 60 THEN 'Low engagement'
                    WHEN avg_pages_per_session <= 5 AND avg_duration < 300 THEN 'Medium engagement'
                    ELSE 'High engagement'
                END as engagement_level,
                COUNT(*) as visitor_count,
                ROUND(AVG(avg_pages_per_session), 1) as avg_pages,
                ROUND(AVG(avg_duration), 0) as avg_duration_seconds
             FROM (
                SELECT 
                    user_hash,
                    AVG(page_count) as avg_pages_per_session,
                    AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)) as avg_duration
                FROM sessions
                WHERE site_id = ? 
                AND DATE(first_visit) BETWEEN ? AND ?
                GROUP BY user_hash
             ) visitor_metrics
             GROUP BY engagement_level
             ORDER BY MIN(avg_pages_per_session)",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get visitor behavior by device type
     */
    public static function getVisitorBehaviorByDevice(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                device_type,
                COUNT(DISTINCT user_hash) as unique_visitors,
                COUNT(*) as total_sessions,
                ROUND(AVG(page_count), 1) as avg_pages_per_session,
                ROUND(AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)), 0) as avg_session_duration,
                ROUND(AVG(CASE WHEN is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             GROUP BY device_type
             ORDER BY unique_visitors DESC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get visitor behavior by browser
     */
    public static function getVisitorBehaviorByBrowser(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                browser,
                COUNT(DISTINCT user_hash) as unique_visitors,
                COUNT(*) as total_sessions,
                ROUND(AVG(page_count), 1) as avg_pages_per_session,
                ROUND(AVG(CASE WHEN is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             GROUP BY browser
             HAVING unique_visitors >= 5
             ORDER BY unique_visitors DESC
             LIMIT 10",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get visitor flow (common session paths)
     */
    public static function getVisitorFlow(int $site_id, string $start_date, string $end_date, int $limit = 10): array
    {
        return Database::select(
            "SELECT 
                entry_page,
                exit_page,
                COUNT(*) as session_count,
                ROUND(AVG(page_count), 1) as avg_page_views,
                ROUND(AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)), 0) as avg_duration
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             AND entry_page IS NOT NULL
             AND exit_page IS NOT NULL
             GROUP BY entry_page, exit_page
             HAVING session_count >= 3
             ORDER BY session_count DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get visitor geographic distribution
     */
    public static function getVisitorGeography(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                country_code,
                COUNT(DISTINCT user_hash) as unique_visitors,
                COUNT(*) as total_sessions,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             AND country_code IS NOT NULL
             GROUP BY country_code
             ORDER BY unique_visitors DESC
             LIMIT 20",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get session duration distribution
     */
    public static function getSessionDurationDistribution(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                CASE 
                    WHEN duration_seconds < 10 THEN '0-10s'
                    WHEN duration_seconds < 30 THEN '10-30s'
                    WHEN duration_seconds < 60 THEN '30-60s'
                    WHEN duration_seconds < 180 THEN '1-3m'
                    WHEN duration_seconds < 600 THEN '3-10m'
                    WHEN duration_seconds < 1800 THEN '10-30m'
                    ELSE '30m+'
                END as duration_bucket,
                COUNT(*) as session_count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
             FROM (
                SELECT TIMESTAMPDIFF(SECOND, first_visit, last_activity) as duration_seconds
                FROM sessions
                WHERE site_id = ? 
                AND DATE(first_visit) BETWEEN ? AND ?
                AND last_activity > first_visit
             ) session_durations
             GROUP BY duration_bucket
             ORDER BY MIN(duration_seconds)",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get page views per session distribution
     */
    public static function getPageViewsDistribution(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                CASE 
                    WHEN page_count = 1 THEN '1 page'
                    WHEN page_count BETWEEN 2 AND 3 THEN '2-3 pages'
                    WHEN page_count BETWEEN 4 AND 10 THEN '4-10 pages'
                    WHEN page_count BETWEEN 11 AND 20 THEN '11-20 pages'
                    ELSE '20+ pages'
                END as pageview_bucket,
                COUNT(*) as session_count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             GROUP BY pageview_bucket
             ORDER BY MIN(page_count)",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get top entry pages
     */
    public static function getTopEntryPages(int $site_id, string $start_date, string $end_date, int $limit = 20): array
    {
        return Database::select(
            "SELECT 
                entry_page,
                COUNT(*) as session_count,
                COUNT(DISTINCT user_hash) as unique_visitors,
                ROUND(AVG(page_count), 1) as avg_pages_per_session,
                ROUND(AVG(CASE WHEN is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as percentage
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             AND entry_page IS NOT NULL
             GROUP BY entry_page
             ORDER BY session_count DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get top exit pages
     */
    public static function getTopExitPages(int $site_id, string $start_date, string $end_date, int $limit = 20): array
    {
        return Database::select(
            "SELECT 
                exit_page,
                COUNT(*) as exit_count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) as exit_rate
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             AND exit_page IS NOT NULL
             AND page_count > 1  -- Exclude single-page sessions
             GROUP BY exit_page
             ORDER BY exit_count DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get visitor cohort analysis (visitors by first visit month)
     */
    public static function getVisitorCohorts(int $site_id, int $months = 6): array
    {
        return Database::select(
            "SELECT 
                DATE_FORMAT(first_visit_date, '%Y-%m') as cohort_month,
                cohort_visitors,
                COALESCE(returning_visitors, 0) as returning_visitors,
                ROUND(COALESCE(returning_visitors, 0) * 100.0 / cohort_visitors, 2) as retention_rate
             FROM (
                SELECT 
                    DATE(first_visit) as first_visit_date,
                    COUNT(DISTINCT user_hash) as cohort_visitors
                FROM sessions s1
                WHERE site_id = ? 
                AND first_visit >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE(first_visit)
             ) cohorts
             LEFT JOIN (
                SELECT 
                    DATE(MIN(first_visit)) as first_visit_date,
                    COUNT(DISTINCT user_hash) as returning_visitors
                FROM sessions
                WHERE site_id = ?
                AND first_visit >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY user_hash
                HAVING COUNT(*) > 1
             ) returning ON cohorts.first_visit_date = returning.first_visit_date
             ORDER BY cohort_month DESC",
            [$site_id, $months, $site_id, $months],
            true, // use cache
            1800  // 30 minutes
        );
    }
    
    /**
     * Identify person by multiple data points (identity merging)
     */
    public static function identifyPerson(string $user_hash, array $identity_data): bool
    {
        try {
            Database::beginTransaction();
            
            // Check if this identity already exists
            $existing_identity = Database::selectOne(
                "SELECT user_hash FROM sessions WHERE user_hash = ? LIMIT 1",
                [$user_hash]
            );
            
            if (!$existing_identity) {
                Database::rollback();
                return false;
            }
            
            // Update sessions with additional identity information
            // This is a simplified version - in production you might want
            // to create a separate user_identities table
            
            Database::commit();
            return true;
            
        } catch (Exception $e) {
            Database::rollback();
            error_log("Person identification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get real-time active visitors
     */
    public static function getActiveVisitors(int $site_id): array
    {
        return Database::select(
            "SELECT 
                rv.session_id,
                rv.page_url,
                rv.page_title,
                rv.last_seen,
                s.device_type,
                s.browser,
                s.country_code,
                TIMESTAMPDIFF(SECOND, s.first_visit, rv.last_seen) as session_duration
             FROM realtime_visitors rv
             JOIN sessions s ON rv.session_id = s.id
             WHERE rv.site_id = ? 
             AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY rv.last_seen DESC",
            [$site_id]
        );
    }
    
    /**
     * Get visitor activity timeline
     */
    public static function getVisitorTimeline(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                DATE(first_visit) as date,
                HOUR(first_visit) as hour,
                COUNT(DISTINCT user_hash) as unique_visitors,
                COUNT(*) as total_sessions
             FROM sessions
             WHERE site_id = ? 
             AND DATE(first_visit) BETWEEN ? AND ?
             GROUP BY DATE(first_visit), HOUR(first_visit)
             ORDER BY date ASC, hour ASC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Delete old visitor data for privacy compliance
     */
    public static function deleteOldVisitorData(int $days): array
    {
        $deleted_sessions = Database::delete(
            "DELETE FROM sessions WHERE first_visit < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        $deleted_pageviews = Database::delete(
            "DELETE FROM pageviews WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        return [
            'deleted_sessions' => $deleted_sessions,
            'deleted_pageviews' => $deleted_pageviews
        ];
    }
    
    /**
     * Get complete journey data for a person
     */
    public static function getJourney(string $person_id, int $site_id): ?array
    {
        require_once APP_PATH . '/lib/Journey.php';
        $journey = new Journey();
        return $journey->getPersonJourney($person_id, $site_id);
    }
    
    /**
     * Merge two person identities
     */
    public static function mergeIdentities(string $primary_person_id, string $secondary_person_id, int $site_id, string $reason = 'manual'): array
    {
        try {
            Database::beginTransaction();
            
            // Verify both persons exist for this site
            $primary_sessions = Database::select(
                "SELECT COUNT(*) as count FROM sessions WHERE user_hash = ? AND site_id = ?",
                [$primary_person_id, $site_id]
            );
            
            $secondary_sessions = Database::select(
                "SELECT COUNT(*) as count FROM sessions WHERE user_hash = ? AND site_id = ?",
                [$secondary_person_id, $site_id]
            );
            
            if ($primary_sessions[0]['count'] == 0 || $secondary_sessions[0]['count'] == 0) {
                Database::rollback();
                return [
                    'success' => false,
                    'error' => 'One or both person IDs not found for this site'
                ];
            }
            
            // Update all sessions from secondary to primary
            $updated_sessions = Database::update(
                "UPDATE sessions SET user_hash = ? WHERE user_hash = ? AND site_id = ?",
                [$primary_person_id, $secondary_person_id, $site_id]
            );
            
            // Update pageviews indirectly through session_id (they should update automatically via FK)
            // But let's be explicit for data integrity
            Database::execute(
                "UPDATE pageviews p 
                 JOIN sessions s ON p.session_id = s.id 
                 SET p.additional_data = JSON_SET(COALESCE(p.additional_data, '{}'), '$.merged_from', ?)
                 WHERE s.user_hash = ? AND s.site_id = ?",
                [$secondary_person_id, $primary_person_id, $site_id]
            );
            
            // Update events indirectly through session_id
            Database::execute(
                "UPDATE events e 
                 JOIN sessions s ON e.session_id = s.id 
                 SET e.event_data = JSON_SET(COALESCE(e.event_data, '{}'), '$.merged_from', ?)
                 WHERE s.user_hash = ? AND s.site_id = ?",
                [$secondary_person_id, $primary_person_id, $site_id]
            );
            
            // Log the merge operation (you might want to create a separate identity_merges table)
            Database::insert(
                "INSERT INTO events (site_id, session_id, event_name, event_category, event_data, timestamp)
                 VALUES (?, 
                         (SELECT id FROM sessions WHERE user_hash = ? AND site_id = ? ORDER BY first_visit DESC LIMIT 1),
                         'identity_merge', 
                         'system',
                         ?,
                         NOW())",
                [
                    $site_id,
                    $primary_person_id,
                    $site_id,
                    json_encode([
                        'primary_person_id' => $primary_person_id,
                        'secondary_person_id' => $secondary_person_id,
                        'reason' => $reason,
                        'timestamp' => date('Y-m-d H:i:s')
                    ])
                ]
            );
            
            Database::commit();
            
            return [
                'success' => true,
                'merged_sessions' => $updated_sessions,
                'primary_person_id' => $primary_person_id,
                'secondary_person_id' => $secondary_person_id,
                'reason' => $reason
            ];
            
        } catch (Exception $e) {
            Database::rollback();
            error_log("Identity merge error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error during merge: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all known identities for a person
     */
    public static function getIdentities(string $person_id, int $site_id): array
    {
        // Get identity information from various sources
        $identities = [
            'cookie' => $person_id  // The user_hash itself is a cookie-based identity
        ];
        
        // Check for email identification from events
        $identity_events = Database::select(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.email')) as email,
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.user_id')) as user_id,
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.phone')) as phone,
                event_name,
                timestamp
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE s.user_hash = ? AND e.site_id = ?
             AND e.event_name IN ('identify', 'sign_up', 'sign_in', 'user_identify')
             AND (
                 JSON_EXTRACT(event_data, '$.email') IS NOT NULL OR
                 JSON_EXTRACT(event_data, '$.user_id') IS NOT NULL OR
                 JSON_EXTRACT(event_data, '$.phone') IS NOT NULL
             )
             ORDER BY timestamp DESC",
            [$person_id, $site_id]
        );
        
        // Add identified data
        foreach ($identity_events as $event) {
            if (!empty($event['email'])) {
                $identities['email'] = $event['email'];
            }
            if (!empty($event['user_id'])) {
                $identities['user_id'] = $event['user_id'];
            }
            if (!empty($event['phone'])) {
                $identities['phone'] = $event['phone'];
            }
        }
        
        // Check for merged identities
        $merge_events = Database::select(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.secondary_person_id')) as merged_from
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE s.user_hash = ? AND e.site_id = ?
             AND e.event_name = 'identity_merge'
             ORDER BY timestamp DESC",
            [$person_id, $site_id]
        );
        
        if (!empty($merge_events)) {
            $identities['merged_identities'] = array_column($merge_events, 'merged_from');
        }
        
        return array_filter($identities); // Remove empty values
    }
    
    /**
     * Find potential duplicate identities for merging
     */
    public static function findPotentialDuplicates(int $site_id, string $start_date, string $end_date): array
    {
        // Find users with same email in events but different user_hash
        $email_duplicates = Database::select(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(e1.event_data, '$.email')) as email,
                GROUP_CONCAT(DISTINCT s1.user_hash) as person_ids,
                COUNT(DISTINCT s1.user_hash) as identity_count
             FROM events e1
             JOIN sessions s1 ON e1.session_id = s1.id
             WHERE e1.site_id = ? 
             AND DATE(e1.timestamp) BETWEEN ? AND ?
             AND e1.event_name IN ('identify', 'sign_up', 'sign_in')
             AND JSON_EXTRACT(e1.event_data, '$.email') IS NOT NULL
             GROUP BY JSON_UNQUOTE(JSON_EXTRACT(e1.event_data, '$.email'))
             HAVING identity_count > 1
             ORDER BY identity_count DESC",
            [$site_id, $start_date, $end_date]
        );
        
        // Find users with same user_id but different user_hash
        $userid_duplicates = Database::select(
            "SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(e1.event_data, '$.user_id')) as user_id,
                GROUP_CONCAT(DISTINCT s1.user_hash) as person_ids,
                COUNT(DISTINCT s1.user_hash) as identity_count
             FROM events e1
             JOIN sessions s1 ON e1.session_id = s1.id
             WHERE e1.site_id = ? 
             AND DATE(e1.timestamp) BETWEEN ? AND ?
             AND e1.event_name IN ('identify', 'sign_up', 'sign_in')
             AND JSON_EXTRACT(e1.event_data, '$.user_id') IS NOT NULL
             GROUP BY JSON_UNQUOTE(JSON_EXTRACT(e1.event_data, '$.user_id'))
             HAVING identity_count > 1
             ORDER BY identity_count DESC",
            [$site_id, $start_date, $end_date]
        );
        
        return [
            'email_duplicates' => $email_duplicates,
            'userid_duplicates' => $userid_duplicates
        ];
    }
    
    /**
     * Auto-merge identities based on matching email or user_id
     */
    public static function autoMergeIdentities(int $site_id): array
    {
        $merged_count = 0;
        $errors = [];
        
        // Get potential duplicates for the last 30 days
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        $duplicates = self::findPotentialDuplicates($site_id, $start_date, $end_date);
        
        // Auto-merge email duplicates
        foreach ($duplicates['email_duplicates'] as $duplicate) {
            $person_ids = explode(',', $duplicate['person_ids']);
            if (count($person_ids) > 1) {
                // Use the first (oldest) as primary
                $primary = trim($person_ids[0]);
                for ($i = 1; $i < count($person_ids); $i++) {
                    $secondary = trim($person_ids[$i]);
                    $result = self::mergeIdentities($primary, $secondary, $site_id, 'auto_email_match');
                    if ($result['success']) {
                        $merged_count++;
                    } else {
                        $errors[] = "Failed to merge {$secondary} into {$primary}: " . $result['error'];
                    }
                }
            }
        }
        
        // Auto-merge user_id duplicates
        foreach ($duplicates['userid_duplicates'] as $duplicate) {
            $person_ids = explode(',', $duplicate['person_ids']);
            if (count($person_ids) > 1) {
                // Use the first (oldest) as primary
                $primary = trim($person_ids[0]);
                for ($i = 1; $i < count($person_ids); $i++) {
                    $secondary = trim($person_ids[$i]);
                    $result = self::mergeIdentities($primary, $secondary, $site_id, 'auto_userid_match');
                    if ($result['success']) {
                        $merged_count++;
                    } else {
                        $errors[] = "Failed to merge {$secondary} into {$primary}: " . $result['error'];
                    }
                }
            }
        }
        
        return [
            'merged_count' => $merged_count,
            'errors' => $errors
        ];
    }
}
?>
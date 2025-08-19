<?php
/**
 * Journey Analysis Library
 * 
 * Handles user journey reconstruction, analysis, and visualization
 * Manages identity merging and session tracking
 */

class Journey
{
    /**
     * Get journey statistics for a site and date range
     */
    public function getJourneyStats(int $site_id, string $start_date, string $end_date, array $filters = []): array
    {
        // Base query for journey stats
        $where_conditions = ["site_id = ?"];
        $params = [$site_id];
        
        // Add date range
        $where_conditions[] = "DATE(first_visit) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        
        // Apply filters
        if (!empty($filters['device_type'])) {
            $where_conditions[] = "device_type = ?";
            $params[] = $filters['device_type'];
        }
        
        if (!empty($filters['entry_page'])) {
            $where_conditions[] = "entry_page = ?";
            $params[] = $filters['entry_page'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get basic journey statistics
        $stats = Database::selectOne(
            "SELECT 
                COUNT(DISTINCT user_hash) as total_journeys,
                COUNT(DISTINCT CASE WHEN last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN user_hash END) as active_journeys,
                AVG(page_count) as avg_pages_per_journey,
                AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)) as avg_journey_duration,
                COUNT(DISTINCT CASE WHEN event_count > 0 THEN user_hash END) * 100.0 / COUNT(DISTINCT user_hash) as conversion_rate
             FROM sessions 
             WHERE {$where_clause}",
            $params,
            true,
            300
        );
        
        return [
            'total_journeys' => $stats['total_journeys'] ?? 0,
            'active_journeys' => $stats['active_journeys'] ?? 0,
            'avg_pages_per_journey' => round($stats['avg_pages_per_journey'] ?? 0, 1),
            'avg_journey_duration' => round($stats['avg_journey_duration'] ?? 0),
            'conversion_rate' => round($stats['conversion_rate'] ?? 0, 1)
        ];
    }
    
    /**
     * Get popular journey paths for a site
     */
    public function getPopularPaths(int $site_id, string $start_date, string $end_date, int $limit = 10): array
    {
        // Get common page sequences
        $paths = Database::select(
            "SELECT 
                p1.page_path as page1,
                p2.page_path as page2,
                p3.page_path as page3,
                COUNT(*) as frequency,
                COUNT(*) * 100.0 / (
                    SELECT COUNT(DISTINCT session_id) 
                    FROM pageviews 
                    WHERE site_id = ? AND DATE(timestamp) BETWEEN ? AND ?
                ) as percentage
             FROM pageviews p1
             LEFT JOIN pageviews p2 ON p1.session_id = p2.session_id AND p2.id = (
                 SELECT MIN(id) FROM pageviews WHERE session_id = p1.session_id AND id > p1.id
             )
             LEFT JOIN pageviews p3 ON p2.session_id = p3.session_id AND p3.id = (
                 SELECT MIN(id) FROM pageviews WHERE session_id = p2.session_id AND id > p2.id
             )
             WHERE p1.site_id = ? 
             AND DATE(p1.timestamp) BETWEEN ? AND ?
             AND p1.id = (
                 SELECT MIN(id) FROM pageviews WHERE session_id = p1.session_id
             )
             GROUP BY p1.page_path, p2.page_path, p3.page_path
             HAVING frequency >= 3
             ORDER BY frequency DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $site_id, $start_date, $end_date, $limit],
            true,
            600
        );
        
        // Format paths for display
        $formatted_paths = [];
        foreach ($paths as $path) {
            $pages = [];
            if ($path['page1']) $pages[] = ['url' => $path['page1'], 'title' => basename($path['page1'])];
            if ($path['page2']) $pages[] = ['url' => $path['page2'], 'title' => basename($path['page2'])];
            if ($path['page3']) $pages[] = ['url' => $path['page3'], 'title' => basename($path['page3'])];
            
            $formatted_paths[] = [
                'pages' => $pages,
                'count' => $path['frequency'],
                'percentage' => round($path['percentage'], 1)
            ];
        }
        
        return $formatted_paths;
    }
    
    /**
     * Get live/active journeys
     */
    public function getLiveJourneys(int $site_id): array
    {
        return Database::select(
            "SELECT 
                rv.session_id,
                s.user_hash,
                s.device_type,
                s.browser,
                s.country_code,
                s.first_visit as started_at,
                rv.page_url as current_page,
                rv.page_title,
                rv.last_seen,
                s.page_count as pages_visited,
                COALESCE(s.country_code, 'Unknown') as location
             FROM realtime_visitors rv
             JOIN sessions s ON rv.session_id = s.id
             WHERE rv.site_id = ? 
             AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY rv.last_seen DESC
             LIMIT 20",
            [$site_id]
        );
    }
    
    /**
     * Get recent completed journeys with timeline data
     */
    public function getRecentJourneys(int $site_id, string $start_date, string $end_date, int $limit = 10): array
    {
        // Get recent journeys (persons with multiple activities)
        $recent_persons = Database::select(
            "SELECT 
                user_hash as person_id,
                MIN(first_visit) as first_visit,
                MAX(last_activity) as last_activity,
                COUNT(*) as session_count,
                SUM(page_count) as total_pages,
                SUM(event_count) as total_events,
                SUM(TIMESTAMPDIFF(SECOND, first_visit, last_activity)) as total_duration,
                AVG(CASE WHEN is_bounce THEN 1 ELSE 0 END) * 100 as bounce_rate
             FROM sessions
             WHERE site_id = ?
             AND DATE(first_visit) BETWEEN ? AND ?
             AND last_activity < DATE_SUB(NOW(), INTERVAL 10 MINUTE)  -- Completed journeys only
             GROUP BY user_hash
             HAVING session_count >= 1
             ORDER BY last_activity DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit]
        );
        
        $journeys = [];
        foreach ($recent_persons as $person) {
            $journey = $this->getPersonJourney($person['person_id'], $site_id);
            if ($journey) {
                $journeys[] = $journey;
            }
        }
        
        return $journeys;
    }
    
    /**
     * Get complete journey data for a specific person
     */
    public function getPersonJourney(string $person_id, int $site_id): ?array
    {
        // Get all sessions for this person
        $sessions = Database::select(
            "SELECT * FROM sessions 
             WHERE user_hash = ? AND site_id = ? 
             ORDER BY first_visit ASC",
            [$person_id, $site_id]
        );
        
        if (empty($sessions)) {
            return null;
        }
        
        // Get all pageviews for this person
        $pageviews = Database::select(
            "SELECT p.* FROM pageviews p
             JOIN sessions s ON p.session_id = s.id
             WHERE s.user_hash = ? AND p.site_id = ?
             ORDER BY p.timestamp ASC",
            [$person_id, $site_id]
        );
        
        // Get all events for this person
        $events = Database::select(
            "SELECT e.* FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE s.user_hash = ? AND e.site_id = ?
             ORDER BY e.timestamp ASC",
            [$person_id, $site_id]
        );
        
        // Get merged identities
        $identities = $this->getPersonIdentities($person_id, $site_id);
        
        // Build complete timeline
        $timeline = $this->buildJourneyTimeline($sessions, $pageviews, $events);
        
        // Calculate journey metrics
        $first_visit = min(array_column($sessions, 'first_visit'));
        $last_activity = max(array_column($sessions, 'last_activity'));
        $total_duration = strtotime($last_activity) - strtotime($first_visit);
        $avg_session_duration = $total_duration / count($sessions);
        
        // Device and browser summary
        $device_summary = [];
        foreach ($sessions as $session) {
            $device_summary[$session['device_type']] = ($device_summary[$session['device_type']] ?? 0) + 1;
        }
        
        return [
            'person_id' => $person_id,
            'site_id' => $site_id,
            'identities' => $identities,
            'sessions' => $sessions,
            'pageviews' => $pageviews,
            'events' => $events,
            'timeline' => $timeline,
            'first_visit' => $first_visit,
            'last_activity' => $last_activity,
            'total_duration' => $total_duration,
            'avg_session_duration' => $avg_session_duration,
            'bounce_rate' => array_sum(array_column($sessions, 'is_bounce')) / count($sessions) * 100,
            'device_summary' => $device_summary,
            'country_code' => $sessions[0]['country_code'] ?? null
        ];
    }
    
    /**
     * Get merged identities for a person
     */
    private function getPersonIdentities(string $person_id, int $site_id): array
    {
        // This is a simplified version - in production, you might have a separate identities table
        // For now, we'll extract identity information from session data and events
        
        $identities = [
            'cookie' => $person_id  // The user_hash is essentially a cookie-based identity
        ];
        
        // Check if we have email identification from events
        $email_events = Database::select(
            "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.email')) as email
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE s.user_hash = ? AND e.site_id = ?
             AND event_name IN ('identify', 'sign_up', 'sign_in')
             AND JSON_EXTRACT(event_data, '$.email') IS NOT NULL",
            [$person_id, $site_id]
        );
        
        if (!empty($email_events)) {
            $identities['email'] = $email_events[0]['email'];
        }
        
        // Check if we have user ID identification
        $user_events = Database::select(
            "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.user_id')) as user_id
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE s.user_hash = ? AND e.site_id = ?
             AND event_name IN ('identify', 'sign_up', 'sign_in')
             AND JSON_EXTRACT(event_data, '$.user_id') IS NOT NULL",
            [$person_id, $site_id]
        );
        
        if (!empty($user_events)) {
            $identities['user_id'] = $user_events[0]['user_id'];
        }
        
        return array_filter($identities); // Remove empty values
    }
    
    /**
     * Build chronological timeline from sessions, pageviews, and events
     */
    private function buildJourneyTimeline(array $sessions, array $pageviews, array $events): array
    {
        $timeline = [];
        
        // Add session start/end events
        foreach ($sessions as $session) {
            $timeline[] = [
                'type' => 'session_start',
                'timestamp' => $session['first_visit'],
                'session_id' => $session['id'],
                'device_type' => $session['device_type'],
                'browser' => $session['browser'],
                'country_code' => $session['country_code'],
                'referrer' => $session['referrer'],
                'referrer_domain' => $session['referrer_domain']
            ];
            
            $timeline[] = [
                'type' => 'session_end',
                'timestamp' => $session['last_activity'],
                'session_id' => $session['id'],
                'page_count' => $session['page_count'],
                'event_count' => $session['event_count'],
                'is_bounce' => $session['is_bounce']
            ];
        }
        
        // Add pageviews
        foreach ($pageviews as $pv) {
            $timeline[] = [
                'type' => 'pageview',
                'timestamp' => $pv['timestamp'],
                'session_id' => $pv['session_id'],
                'page_url' => $pv['page_url'],
                'page_path' => $pv['page_path'],
                'page_title' => $pv['page_title'],
                'referrer' => $pv['referrer'],
                'load_time' => $pv['load_time']
            ];
        }
        
        // Add events
        foreach ($events as $event) {
            $timeline[] = [
                'type' => 'event',
                'timestamp' => $event['timestamp'],
                'session_id' => $event['session_id'],
                'event_name' => $event['event_name'],
                'event_category' => $event['event_category'],
                'event_action' => $event['event_action'],
                'event_label' => $event['event_label'],
                'event_value' => $event['event_value'],
                'event_data' => json_decode($event['event_data'], true),
                'page_url' => $event['page_url'],
                'page_path' => $event['page_path']
            ];
        }
        
        // Sort timeline by timestamp
        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });
        
        return $timeline;
    }
    
    /**
     * Get live journey statistics
     */
    public function getLiveJourneyStats(int $site_id): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(DISTINCT rv.session_id) as active_journeys,
                AVG(s.page_count) as avg_pages,
                AVG(TIMESTAMPDIFF(SECOND, s.first_visit, rv.last_seen)) as avg_duration
             FROM realtime_visitors rv
             JOIN sessions s ON rv.session_id = s.id
             WHERE rv.site_id = ? 
             AND rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$site_id]
        );
        
        return [
            'active_journeys' => $stats['active_journeys'] ?? 0,
            'avg_pages' => round($stats['avg_pages'] ?? 0, 1),
            'avg_duration' => round($stats['avg_duration'] ?? 0)
        ];
    }
    
    /**
     * Analyze journey conversion points
     */
    public function getJourneyConversions(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                e.event_name,
                e.event_category,
                COUNT(DISTINCT s.user_hash) as unique_users,
                COUNT(*) as total_events,
                AVG(TIMESTAMPDIFF(SECOND, s.first_visit, e.timestamp)) as avg_time_to_convert
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE e.site_id = ? 
             AND DATE(e.timestamp) BETWEEN ? AND ?
             AND e.event_category IN ('conversion', 'purchase', 'signup', 'contact')
             GROUP BY e.event_name, e.event_category
             ORDER BY unique_users DESC",
            [$site_id, $start_date, $end_date],
            true,
            600
        );
    }
    
    /**
     * Get journey drop-off points
     */
    public function getJourneyDropOffs(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                exit_page,
                COUNT(*) as exit_count,
                AVG(page_count) as avg_pages_before_exit,
                AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)) as avg_time_before_exit
             FROM sessions
             WHERE site_id = ?
             AND DATE(first_visit) BETWEEN ? AND ?
             AND exit_page IS NOT NULL
             AND is_bounce = FALSE  -- Exclude bounces to focus on drop-offs
             GROUP BY exit_page
             ORDER BY exit_count DESC
             LIMIT 20",
            [$site_id, $start_date, $end_date],
            true,
            600
        );
    }
}
?>
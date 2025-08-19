<?php
/**
 * Event Model
 * 
 * Manages custom event tracking data and analytics.
 */

class Event
{
    /**
     * Get events for a site within date range
     */
    public static function getEventsBySite(int $site_id, string $start_date, string $end_date, int $limit = 100): array
    {
        return Database::select(
            "SELECT e.*, s.user_hash, s.device_type, s.browser, s.os
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE e.site_id = ? 
             AND DATE(e.timestamp) BETWEEN ? AND ?
             ORDER BY e.timestamp DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $limit]
        );
    }
    
    /**
     * Get event counts by name for a site
     */
    public static function getEventCountsByName(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT event_name, COUNT(*) as event_count
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             GROUP BY event_name
             ORDER BY event_count DESC
             LIMIT 20",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get event counts by category for a site
     */
    public static function getEventCountsByCategory(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT event_category, COUNT(*) as event_count
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             AND event_category IS NOT NULL
             GROUP BY event_category
             ORDER BY event_count DESC
             LIMIT 20",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get events timeline (hourly breakdown)
     */
    public static function getEventsTimeline(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                DATE(timestamp) as date,
                HOUR(timestamp) as hour,
                COUNT(*) as event_count
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             GROUP BY DATE(timestamp), HOUR(timestamp)
             ORDER BY date ASC, hour ASC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get conversion events (events with values)
     */
    public static function getConversionEvents(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                event_name,
                event_category,
                COUNT(*) as conversion_count,
                SUM(COALESCE(event_value, 0)) as total_value,
                AVG(COALESCE(event_value, 0)) as avg_value
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             AND event_value IS NOT NULL
             GROUP BY event_name, event_category
             ORDER BY conversion_count DESC
             LIMIT 10",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get events by page
     */
    public static function getEventsByPage(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                page_path,
                COUNT(*) as event_count,
                COUNT(DISTINCT event_name) as unique_events,
                COUNT(DISTINCT session_id) as unique_sessions
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             AND page_path IS NOT NULL
             GROUP BY page_path
             ORDER BY event_count DESC
             LIMIT 20",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get event funnel analysis
     */
    public static function getEventFunnel(int $site_id, array $event_sequence, string $start_date, string $end_date): array
    {
        if (empty($event_sequence)) {
            return [];
        }
        
        $funnel_results = [];
        
        // Get sessions that triggered the first event
        $first_event = $event_sequence[0];
        $sessions_with_first = Database::select(
            "SELECT DISTINCT session_id
             FROM events
             WHERE site_id = ? 
             AND event_name = ?
             AND DATE(timestamp) BETWEEN ? AND ?",
            [$site_id, $first_event, $start_date, $end_date]
        );
        
        $session_ids = array_column($sessions_with_first, 'session_id');
        $funnel_results[] = [
            'step' => 1,
            'event_name' => $first_event,
            'sessions' => count($session_ids),
            'conversion_rate' => 100.0
        ];
        
        $total_sessions = count($session_ids);
        
        // Analyze each subsequent step
        for ($i = 1; $i < count($event_sequence); $i++) {
            $current_event = $event_sequence[$i];
            
            if (empty($session_ids)) {
                break;
            }
            
            // Find sessions that also triggered the current event
            $placeholders = str_repeat('?,', count($session_ids) - 1) . '?';
            $params = array_merge([$site_id, $current_event, $start_date, $end_date], $session_ids);
            
            $sessions_with_current = Database::select(
                "SELECT DISTINCT session_id
                 FROM events
                 WHERE site_id = ? 
                 AND event_name = ?
                 AND DATE(timestamp) BETWEEN ? AND ?
                 AND session_id IN ({$placeholders})",
                $params
            );
            
            $session_ids = array_column($sessions_with_current, 'session_id');
            $conversion_rate = $total_sessions > 0 ? (count($session_ids) / $total_sessions) * 100 : 0;
            
            $funnel_results[] = [
                'step' => $i + 1,
                'event_name' => $current_event,
                'sessions' => count($session_ids),
                'conversion_rate' => round($conversion_rate, 2)
            ];
        }
        
        return $funnel_results;
    }
    
    /**
     * Get real-time events (last 5 minutes)
     */
    public static function getRealtimeEvents(int $site_id, int $limit = 20): array
    {
        return Database::select(
            "SELECT 
                e.event_name,
                e.event_category,
                e.event_action,
                e.event_label,
                e.page_path,
                e.timestamp,
                s.device_type,
                s.browser,
                s.country_code
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE e.site_id = ? 
             AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY e.timestamp DESC
             LIMIT ?",
            [$site_id, $limit]
        );
    }
    
    /**
     * Get event performance over time
     */
    public static function getEventPerformance(int $site_id, string $event_name, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                DATE(timestamp) as date,
                COUNT(*) as event_count,
                COUNT(DISTINCT session_id) as unique_sessions,
                COALESCE(AVG(event_value), 0) as avg_value,
                COALESCE(SUM(event_value), 0) as total_value
             FROM events
             WHERE site_id = ? 
             AND event_name = ?
             AND DATE(timestamp) BETWEEN ? AND ?
             GROUP BY DATE(timestamp)
             ORDER BY date ASC",
            [$site_id, $event_name, $start_date, $end_date],
            true, // use cache
            600   // 10 minutes
        );
    }
    
    /**
     * Get events by device type
     */
    public static function getEventsByDevice(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                s.device_type,
                COUNT(e.id) as event_count,
                COUNT(DISTINCT e.event_name) as unique_events,
                COUNT(DISTINCT e.session_id) as unique_sessions
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE e.site_id = ? 
             AND DATE(e.timestamp) BETWEEN ? AND ?
             GROUP BY s.device_type
             ORDER BY event_count DESC",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Get events by browser
     */
    public static function getEventsByBrowser(int $site_id, string $start_date, string $end_date): array
    {
        return Database::select(
            "SELECT 
                s.browser,
                COUNT(e.id) as event_count,
                COUNT(DISTINCT e.event_name) as unique_events
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE e.site_id = ? 
             AND DATE(e.timestamp) BETWEEN ? AND ?
             GROUP BY s.browser
             ORDER BY event_count DESC
             LIMIT 10",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
    }
    
    /**
     * Create new event
     */
    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO events (
                site_id, session_id, event_name, event_category, event_action,
                event_label, event_value, event_data, page_url, page_path, timestamp
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['site_id'],
                $data['session_id'],
                $data['event_name'],
                $data['event_category'] ?? null,
                $data['event_action'] ?? null,
                $data['event_label'] ?? null,
                $data['event_value'] ?? null,
                isset($data['event_data']) ? json_encode($data['event_data']) : null,
                $data['page_url'] ?? null,
                $data['page_path'] ?? null
            ]
        );
    }
    
    /**
     * Delete old events (data retention)
     */
    public static function deleteOldEvents(int $days): int
    {
        return Database::delete(
            "DELETE FROM events WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
    }
    
    /**
     * Get event statistics for a site
     */
    public static function getEventStats(int $site_id, string $start_date, string $end_date): array
    {
        $stats = Database::selectOne(
            "SELECT 
                COUNT(*) as total_events,
                COUNT(DISTINCT event_name) as unique_event_types,
                COUNT(DISTINCT session_id) as sessions_with_events,
                COALESCE(AVG(event_value), 0) as avg_event_value,
                COALESCE(SUM(event_value), 0) as total_event_value
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?",
            [$site_id, $start_date, $end_date],
            true, // use cache
            300   // 5 minutes
        );
        
        return $stats ?: [];
    }
    
    /**
     * Search events by criteria
     */
    public static function searchEvents(int $site_id, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $where_conditions = ['e.site_id = ?'];
        $params = [$site_id];
        
        // Add date filter
        if (!empty($filters['start_date'])) {
            $where_conditions[] = 'DATE(e.timestamp) >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where_conditions[] = 'DATE(e.timestamp) <= ?';
            $params[] = $filters['end_date'];
        }
        
        // Add event name filter
        if (!empty($filters['event_name'])) {
            $where_conditions[] = 'e.event_name LIKE ?';
            $params[] = '%' . $filters['event_name'] . '%';
        }
        
        // Add event category filter
        if (!empty($filters['event_category'])) {
            $where_conditions[] = 'e.event_category = ?';
            $params[] = $filters['event_category'];
        }
        
        // Add page filter
        if (!empty($filters['page_path'])) {
            $where_conditions[] = 'e.page_path LIKE ?';
            $params[] = '%' . $filters['page_path'] . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $params[] = $limit;
        $params[] = $offset;
        
        return Database::select(
            "SELECT 
                e.*,
                s.device_type,
                s.browser,
                s.os,
                s.country_code
             FROM events e
             JOIN sessions s ON e.session_id = s.id
             WHERE {$where_clause}
             ORDER BY e.timestamp DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }
    
    /**
     * Get most popular events
     */
    public static function getPopularEvents(int $site_id, string $start_date, string $end_date, int $limit = 10): array
    {
        return Database::select(
            "SELECT 
                event_name,
                event_category,
                COUNT(*) as event_count,
                COUNT(DISTINCT session_id) as unique_sessions,
                ROUND(COUNT(*) * 100.0 / (
                    SELECT COUNT(*) FROM events WHERE site_id = ? AND DATE(timestamp) BETWEEN ? AND ?
                ), 2) as percentage
             FROM events
             WHERE site_id = ? 
             AND DATE(timestamp) BETWEEN ? AND ?
             GROUP BY event_name, event_category
             ORDER BY event_count DESC
             LIMIT ?",
            [$site_id, $start_date, $end_date, $site_id, $start_date, $end_date, $limit],
            true, // use cache
            300   // 5 minutes
        );
    }
}
?>
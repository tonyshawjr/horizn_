<?php
/**
 * Analytics Helper Library
 * Provides methods for calculating analytics metrics and data
 */

class Analytics {
    
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get date range array from period string
     */
    public function getDateRange($period = '30d') {
        $end = new DateTime();
        $start = new DateTime();
        
        switch ($period) {
            case '1d':
                $start->modify('-1 day');
                break;
            case '7d':
                $start->modify('-7 days');
                break;
            case '30d':
                $start->modify('-30 days');
                break;
            case '90d':
                $start->modify('-90 days');
                break;
            default:
                $start->modify('-30 days');
        }
        
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'period' => $period
        ];
    }
    
    /**
     * Get live visitors count for a site
     */
    public function getLiveVisitors($siteId) {
        $sql = "SELECT COUNT(DISTINCT session_id) as live_visitors 
                FROM realtime_visitors 
                WHERE site_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $result = $this->db->query($sql, [$siteId]);
        return $result[0]['live_visitors'] ?? 0;
    }
    
    /**
     * Get total pageviews for date range
     */
    public function getPageviews($siteId, $dateRange) {
        $sql = "SELECT COUNT(*) as pageviews 
                FROM pageviews 
                WHERE site_id = ? AND DATE(timestamp) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
        return $result[0]['pageviews'] ?? 0;
    }
    
    /**
     * Get unique visitors for date range
     */
    public function getUniqueVisitors($siteId, $dateRange) {
        $sql = "SELECT COUNT(DISTINCT user_hash) as visitors 
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
        return $result[0]['visitors'] ?? 0;
    }
    
    /**
     * Get bounce rate for date range
     */
    public function getBounceRate($siteId, $dateRange) {
        $sql = "SELECT 
                    COUNT(CASE WHEN is_bounce = TRUE THEN 1 END) * 100.0 / COUNT(*) as bounce_rate
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
        return $result[0]['bounce_rate'] ?? 0;
    }
    
    /**
     * Get average session duration
     */
    public function getAvgSessionDuration($siteId, $dateRange) {
        $sql = "SELECT AVG(TIMESTAMPDIFF(SECOND, first_visit, last_activity)) as avg_duration
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?
                AND is_bounce = FALSE";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
        return $result[0]['avg_duration'] ?? 0;
    }
    
    /**
     * Get top pages for a site
     */
    public function getTopPages($siteId, $dateRange, $limit = 10) {
        $sql = "SELECT 
                    page_path,
                    page_title,
                    COUNT(*) as pageviews,
                    COUNT(DISTINCT session_id) as unique_visitors,
                    AVG(load_time) as avg_load_time
                FROM pageviews 
                WHERE site_id = ? AND DATE(timestamp) BETWEEN ? AND ?
                GROUP BY page_path, page_title
                ORDER BY pageviews DESC
                LIMIT ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end'], $limit]);
        
        // Calculate additional metrics for each page
        foreach ($result as &$page) {
            $page['bounce_rate'] = $this->getPageBounceRate($siteId, $page['page_path'], $dateRange);
            $page['avg_time_on_page'] = $this->getAvgTimeOnPage($siteId, $page['page_path'], $dateRange);
        }
        
        return $result;
    }
    
    /**
     * Get top referrers for a site
     */
    public function getTopReferrers($siteId, $dateRange, $limit = 10) {
        $sql = "SELECT 
                    referrer_domain,
                    COUNT(*) as sessions,
                    COUNT(DISTINCT user_hash) as unique_visitors
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?
                AND referrer_domain IS NOT NULL
                AND referrer_domain != ''
                GROUP BY referrer_domain
                ORDER BY sessions DESC
                LIMIT ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end'], $limit]);
        
        // Add percentage change calculation
        $previousRange = $this->getPreviousDateRange($dateRange);
        
        foreach ($result as &$referrer) {
            $previousSessions = $this->getReferrerSessions($siteId, $referrer['referrer_domain'], $previousRange);
            $referrer['change'] = $this->calculatePercentageChange($previousSessions, $referrer['sessions']);
        }
        
        return $result;
    }
    
    /**
     * Get country breakdown for a site
     */
    public function getCountryBreakdown($siteId, $dateRange, $limit = 10) {
        $sql = "SELECT 
                    country_code,
                    COUNT(DISTINCT user_hash) as visitors,
                    COUNT(*) as sessions
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?
                AND country_code IS NOT NULL
                GROUP BY country_code
                ORDER BY visitors DESC
                LIMIT ?";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end'], $limit]);
        
        // Enhance with country names and flags
        $countries = $this->getCountryData();
        $totalVisitors = array_sum(array_column($result, 'visitors'));
        
        foreach ($result as &$country) {
            $code = strtoupper($country['country_code']);
            $country['name'] = $countries[$code]['name'] ?? $code;
            $country['flag'] = $countries[$code]['flag'] ?? '🌍';
            $country['percentage'] = $totalVisitors > 0 ? ($country['visitors'] / $totalVisitors) * 100 : 0;
        }
        
        return $result;
    }
    
    /**
     * Get device breakdown for a site
     */
    public function getDeviceBreakdown($siteId, $dateRange) {
        $sql = "SELECT 
                    device_type,
                    COUNT(DISTINCT user_hash) as visitors,
                    COUNT(*) as sessions
                FROM sessions 
                WHERE site_id = ? AND DATE(first_visit) BETWEEN ? AND ?
                GROUP BY device_type
                ORDER BY visitors DESC";
        
        $result = $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
        
        // Calculate percentages
        $totalVisitors = array_sum(array_column($result, 'visitors'));
        
        foreach ($result as &$device) {
            $device['percentage'] = $totalVisitors > 0 ? ($device['visitors'] / $totalVisitors) * 100 : 0;
        }
        
        return $result;
    }
    
    /**
     * Get hourly traffic data for charts
     */
    public function getHourlyTraffic($siteId, $dateRange) {
        $sql = "SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as pageviews,
                    COUNT(DISTINCT session_id) as sessions
                FROM pageviews 
                WHERE site_id = ? AND DATE(timestamp) BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00')
                ORDER BY hour";
        
        return $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
    }
    
    /**
     * Get daily traffic data for charts
     */
    public function getDailyTraffic($siteId, $dateRange) {
        $sql = "SELECT 
                    date,
                    pageviews,
                    unique_visitors as visitors,
                    sessions,
                    bounce_rate
                FROM daily_stats 
                WHERE site_id = ? AND date BETWEEN ? AND ?
                ORDER BY date";
        
        return $this->db->query($sql, [$siteId, $dateRange['start'], $dateRange['end']]);
    }
    
    /**
     * Get sparkline data (simplified 7-day hourly data)
     */
    public function getSparklineData($siteId, $days = 7) {
        $sql = "SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as pageviews
                FROM pageviews 
                WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00')
                ORDER BY hour
                LIMIT 24";
        
        $result = $this->db->query($sql, [$siteId, $days]);
        return array_column($result, 'pageviews');
    }
    
    /**
     * Get site overview stats with comparisons
     */
    public function getSiteOverview($siteId, $dateRange) {
        $current = [
            'pageviews' => $this->getPageviews($siteId, $dateRange),
            'visitors' => $this->getUniqueVisitors($siteId, $dateRange),
            'bounce_rate' => $this->getBounceRate($siteId, $dateRange),
            'avg_session_duration' => $this->getAvgSessionDuration($siteId, $dateRange)
        ];
        
        // Get previous period for comparison
        $previousRange = $this->getPreviousDateRange($dateRange);
        $previous = [
            'pageviews' => $this->getPageviews($siteId, $previousRange),
            'visitors' => $this->getUniqueVisitors($siteId, $previousRange)
        ];
        
        // Calculate percentage changes
        $current['pageviews_change'] = $this->calculatePercentageChange($previous['pageviews'], $current['pageviews']);
        $current['visitors_change'] = $this->calculatePercentageChange($previous['visitors'], $current['visitors']);
        
        return $current;
    }
    
    /**
     * Get agency-level aggregated stats
     */
    public function getAgencyStats($siteIds, $dateRange) {
        if (empty($siteIds)) {
            return [
                'total_pageviews' => 0,
                'total_visitors' => 0,
                'total_live_visitors' => 0,
                'total_events' => 0
            ];
        }
        
        $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
        
        // Total pageviews
        $sql = "SELECT COUNT(*) as total_pageviews 
                FROM pageviews 
                WHERE site_id IN ($placeholders) AND DATE(timestamp) BETWEEN ? AND ?";
        $params = array_merge($siteIds, [$dateRange['start'], $dateRange['end']]);
        $pageviews = $this->db->query($sql, $params);
        
        // Total unique visitors
        $sql = "SELECT COUNT(DISTINCT user_hash) as total_visitors 
                FROM sessions 
                WHERE site_id IN ($placeholders) AND DATE(first_visit) BETWEEN ? AND ?";
        $visitors = $this->db->query($sql, $params);
        
        // Total live visitors
        $sql = "SELECT COUNT(DISTINCT session_id) as total_live_visitors 
                FROM realtime_visitors 
                WHERE site_id IN ($placeholders) AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $liveVisitors = $this->db->query($sql, $siteIds);
        
        // Total events today
        $sql = "SELECT COUNT(*) as total_events 
                FROM events 
                WHERE site_id IN ($placeholders) AND DATE(timestamp) = CURDATE()";
        $events = $this->db->query($sql, $siteIds);
        
        return [
            'total_pageviews' => $pageviews[0]['total_pageviews'] ?? 0,
            'total_visitors' => $visitors[0]['total_visitors'] ?? 0,
            'total_live_visitors' => $liveVisitors[0]['total_live_visitors'] ?? 0,
            'total_events' => $events[0]['total_events'] ?? 0
        ];
    }
    
    /**
     * Get today's stats for multiple sites
     */
    public function getTodayStats($siteIds) {
        if (empty($siteIds)) {
            return ['pageviews' => 0, 'visitors' => 0];
        }
        
        $placeholders = implode(',', array_fill(0, count($siteIds), '?'));
        
        $sql = "SELECT 
                    COUNT(*) as pageviews,
                    COUNT(DISTINCT session_id) as visitors
                FROM pageviews 
                WHERE site_id IN ($placeholders) AND DATE(timestamp) = CURDATE()";
        
        $result = $this->db->query($sql, $siteIds);
        return $result[0] ?? ['pageviews' => 0, 'visitors' => 0];
    }
    
    /**
     * Get last activity timestamp for a site
     */
    public function getLastActivity($siteId) {
        $sql = "SELECT MAX(timestamp) as last_activity 
                FROM pageviews 
                WHERE site_id = ?";
        
        $result = $this->db->query($sql, [$siteId]);
        return $result[0]['last_activity'] ?? null;
    }
    
    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange($oldValue, $newValue) {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return (($newValue - $oldValue) / $oldValue) * 100;
    }
    
    /**
     * Get previous date range for comparison
     */
    private function getPreviousDateRange($dateRange) {
        $start = new DateTime($dateRange['start']);
        $end = new DateTime($dateRange['end']);
        $diff = $start->diff($end)->days;
        
        $previousEnd = clone $start;
        $previousEnd->modify('-1 day');
        
        $previousStart = clone $previousEnd;
        $previousStart->modify("-{$diff} days");
        
        return [
            'start' => $previousStart->format('Y-m-d'),
            'end' => $previousEnd->format('Y-m-d')
        ];
    }
    
    /**
     * Get sessions for a specific referrer in a date range
     */
    private function getReferrerSessions($siteId, $referrerDomain, $dateRange) {
        $sql = "SELECT COUNT(*) as sessions 
                FROM sessions 
                WHERE site_id = ? AND referrer_domain = ? 
                AND DATE(first_visit) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $referrerDomain, $dateRange['start'], $dateRange['end']]);
        return $result[0]['sessions'] ?? 0;
    }
    
    /**
     * Get bounce rate for a specific page
     */
    private function getPageBounceRate($siteId, $pagePath, $dateRange) {
        $sql = "SELECT 
                    COUNT(CASE WHEN s.is_bounce = TRUE THEN 1 END) * 100.0 / COUNT(*) as bounce_rate
                FROM sessions s
                WHERE s.site_id = ? AND s.entry_page = ?
                AND DATE(s.first_visit) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $pagePath, $dateRange['start'], $dateRange['end']]);
        return $result[0]['bounce_rate'] ?? 0;
    }
    
    /**
     * Get average time on page
     */
    private function getAvgTimeOnPage($siteId, $pagePath, $dateRange) {
        // This is a simplified calculation - in practice you'd track exit times
        $sql = "SELECT AVG(load_time) as avg_time 
                FROM pageviews 
                WHERE site_id = ? AND page_path = ?
                AND DATE(timestamp) BETWEEN ? AND ?";
        
        $result = $this->db->query($sql, [$siteId, $pagePath, $dateRange['start'], $dateRange['end']]);
        return $result[0]['avg_time'] ?? 0;
    }
    
    /**
     * Get country data for display
     */
    private function getCountryData() {
        return [
            'US' => ['name' => 'United States', 'flag' => '🇺🇸'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦'],
            'GB' => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪'],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷'],
            'ES' => ['name' => 'Spain', 'flag' => '🇪🇸'],
            'IT' => ['name' => 'Italy', 'flag' => '🇮🇹'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵'],
            'BR' => ['name' => 'Brazil', 'flag' => '🇧🇷'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳'],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳'],
            'RU' => ['name' => 'Russia', 'flag' => '🇷🇺'],
            'MX' => ['name' => 'Mexico', 'flag' => '🇲🇽'],
            'NL' => ['name' => 'Netherlands', 'flag' => '🇳🇱'],
        ];
    }
}
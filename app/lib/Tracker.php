<?php
/**
 * Core Tracking Logic
 * 
 * Handles analytics data collection, session management, and event tracking
 * optimized for ad-blocker resistance and privacy compliance.
 */

class Tracker
{
    private static $config = null;
    
    /**
     * Load tracking configuration
     */
    private static function loadConfig(): void
    {
        if (self::$config === null) {
            self::$config = require CONFIG_PATH . '/app.php';
        }
    }
    
    /**
     * Track page view
     */
    public static function trackPageview(array $data): array
    {
        self::loadConfig();
        
        try {
            // Validate required data
            $validation = self::validatePageviewData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Get or create session
            $session_data = self::getOrCreateSession($data);
            if (!$session_data['success']) {
                return $session_data;
            }
            
            $session_id = $session_data['session_id'];
            
            // Process page data
            $page_url = self::sanitizeUrl($data['page_url']);
            $page_path = self::extractPath($page_url);
            $page_title = self::sanitizeString($data['page_title'] ?? '', 255);
            $referrer = self::sanitizeUrl($data['referrer'] ?? '');
            $load_time = isset($data['load_time']) ? (int)$data['load_time'] : null;
            
            // Hash IP for privacy
            $ip_hash = Auth::hashIP(Auth::getClientIP());
            
            // Insert pageview
            $pageview_id = Database::insert(
                "INSERT INTO pageviews (
                    site_id, session_id, page_url, page_path, page_title,
                    referrer, user_agent, ip_hash, load_time, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $data['site_id'],
                    $session_id,
                    $page_url,
                    $page_path,
                    $page_title,
                    $referrer,
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $ip_hash,
                    $load_time
                ]
            );
            
            // Update session
            self::updateSessionActivity($session_id, $page_path);
            
            // Update realtime visitors
            self::updateRealtimeVisitor($data['site_id'], $session_id, $page_url, $page_title);
            
            return [
                'success' => true,
                'pageview_id' => $pageview_id,
                'session_id' => $session_id
            ];
            
        } catch (Exception $e) {
            error_log("Pageview tracking error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Tracking failed'
            ];
        }
    }
    
    /**
     * Track custom event
     */
    public static function trackEvent(array $data): array
    {
        self::loadConfig();
        
        try {
            // Validate required data
            $validation = self::validateEventData($data);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => $validation['error']
                ];
            }
            
            // Get session
            $session = self::getSessionById($data['session_id']);
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Invalid session'
                ];
            }
            
            // Check event rate limiting
            if (!self::checkEventRateLimit($data['session_id'])) {
                return [
                    'success' => false,
                    'error' => 'Event rate limit exceeded'
                ];
            }
            
            // Process event data
            $event_name = self::sanitizeString($data['event_name'], 100);
            $event_category = self::sanitizeString($data['event_category'] ?? '', 100);
            $event_action = self::sanitizeString($data['event_action'] ?? '', 100);
            $event_label = self::sanitizeString($data['event_label'] ?? '', 255);
            $event_value = isset($data['event_value']) ? (int)$data['event_value'] : null;
            $event_data = isset($data['event_data']) ? json_encode($data['event_data']) : null;
            
            $page_url = self::sanitizeUrl($data['page_url'] ?? '');
            $page_path = self::extractPath($page_url);
            
            // Insert event
            $event_id = Database::insert(
                "INSERT INTO events (
                    site_id, session_id, event_name, event_category, event_action,
                    event_label, event_value, event_data, page_url, page_path, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $session['site_id'],
                    $data['session_id'],
                    $event_name,
                    $event_category,
                    $event_action,
                    $event_label,
                    $event_value,
                    $event_data,
                    $page_url,
                    $page_path
                ]
            );
            
            // Update session event count
            Database::update(
                "UPDATE sessions SET event_count = event_count + 1, updated_at = NOW() WHERE id = ?",
                [$data['session_id']]
            );
            
            return [
                'success' => true,
                'event_id' => $event_id
            ];
            
        } catch (Exception $e) {
            error_log("Event tracking error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Event tracking failed'
            ];
        }
    }
    
    /**
     * Track batch of events/pageviews
     */
    public static function trackBatch(array $batch_data): array
    {
        self::loadConfig();
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        // Check batch size limit
        $max_batch_size = self::$config['tracking']['max_batch_size'];
        if (count($batch_data) > $max_batch_size) {
            return [
                'success' => false,
                'error' => "Batch size exceeds limit of {$max_batch_size}"
            ];
        }
        
        Database::beginTransaction();
        
        try {
            foreach ($batch_data as $index => $item) {
                $item_type = $item['type'] ?? 'pageview';
                
                if ($item_type === 'pageview') {
                    $result = self::trackPageview($item);
                } elseif ($item_type === 'event') {
                    $result = self::trackEvent($item);
                } else {
                    $result = [
                        'success' => false,
                        'error' => 'Unknown item type'
                    ];
                }
                
                $results[$index] = $result;
                
                if ($result['success']) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
            
            Database::commit();
            
            return [
                'success' => true,
                'processed' => count($batch_data),
                'successful' => $success_count,
                'errors' => $error_count,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            Database::rollback();
            error_log("Batch tracking error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Batch tracking failed',
                'processed' => 0,
                'successful' => 0,
                'errors' => count($batch_data)
            ];
        }
    }
    
    /**
     * Get or create user session
     */
    private static function getOrCreateSession(array $data): array
    {
        self::loadConfig();
        
        $session_timeout = self::$config['analytics']['session_timeout'];
        $session_id = $data['session_id'] ?? null;
        
        // If session ID provided, try to find existing session
        if ($session_id) {
            $session = Database::selectOne(
                "SELECT id, site_id, user_hash, first_visit, last_activity, page_count, is_bounce
                 FROM sessions 
                 WHERE id = ? AND site_id = ? 
                 AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)",
                [$session_id, $data['site_id'], $session_timeout]
            );
            
            if ($session) {
                return [
                    'success' => true,
                    'session_id' => $session_id,
                    'is_new' => false
                ];
            }
        }
        
        // Create new session
        $new_session_id = self::generateSessionId();
        $user_hash = self::getUserHash($data);
        $ip_hash = Auth::hashIP(Auth::getClientIP());
        
        // Parse referrer
        $referrer = self::sanitizeUrl($data['referrer'] ?? '');
        $referrer_domain = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;
        
        // Parse user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_info = self::parseUserAgent($user_agent);
        
        // Entry page
        $entry_page = self::extractPath($data['page_url']);
        
        try {
            Database::insert(
                "INSERT INTO sessions (
                    id, site_id, user_hash, first_visit, last_activity,
                    page_count, event_count, is_bounce, referrer, referrer_domain,
                    entry_page, exit_page, user_agent, browser, os, device_type,
                    ip_hash, country_code, created_at, updated_at
                ) VALUES (?, ?, ?, NOW(), NOW(), 0, 0, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $new_session_id,
                    $data['site_id'],
                    $user_hash,
                    $referrer,
                    $referrer_domain,
                    $entry_page,
                    $entry_page,
                    $user_agent,
                    $device_info['browser'],
                    $device_info['os'],
                    $device_info['device_type'],
                    $ip_hash,
                    'US' // TODO: Implement geolocation
                ]
            );
            
            return [
                'success' => true,
                'session_id' => $new_session_id,
                'is_new' => true
            ];
            
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create session'
            ];
        }
    }
    
    /**
     * Update session activity
     */
    private static function updateSessionActivity(string $session_id, string $page_path): void
    {
        // Update last activity, page count, and bounce status
        Database::update(
            "UPDATE sessions 
             SET last_activity = NOW(), 
                 page_count = page_count + 1,
                 is_bounce = CASE WHEN page_count = 0 THEN 1 ELSE 0 END,
                 exit_page = ?,
                 updated_at = NOW()
             WHERE id = ?",
            [$page_path, $session_id]
        );
    }
    
    /**
     * Update realtime visitor tracking
     */
    private static function updateRealtimeVisitor(int $site_id, string $session_id, string $page_url, string $page_title): void
    {
        $ip_hash = Auth::hashIP(Auth::getClientIP());
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Insert or update realtime visitor
        Database::raw(
            "INSERT INTO realtime_visitors (site_id, session_id, page_url, page_title, last_seen, user_agent, ip_hash)
             VALUES (?, ?, ?, ?, NOW(), ?, ?)
             ON DUPLICATE KEY UPDATE 
             page_url = VALUES(page_url),
             page_title = VALUES(page_title),
             last_seen = NOW()",
            [$site_id, $session_id, $page_url, $page_title, $user_agent, $ip_hash]
        );
    }
    
    /**
     * Generate unique session ID
     */
    private static function generateSessionId(): string
    {
        return uniqid('sess_', true) . '_' . bin2hex(random_bytes(8));
    }
    
    /**
     * Generate user hash for anonymous tracking
     */
    private static function getUserHash(array $data): string
    {
        $ip = Auth::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $user_id = $data['user_id'] ?? '';
        
        // If user is identified, use user ID
        if ($user_id) {
            return hash('sha256', "user_{$user_id}");
        }
        
        // For anonymous users, use IP + User Agent + daily salt
        $daily_salt = date('Y-m-d');
        return hash('sha256', $ip . $user_agent . $daily_salt);
    }
    
    /**
     * Parse user agent for device information
     */
    private static function parseUserAgent(string $user_agent): array
    {
        $device_type = 'desktop';
        $browser = 'Unknown';
        $os = 'Unknown';
        
        // Detect device type
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            if (preg_match('/iPad/', $user_agent)) {
                $device_type = 'tablet';
            } else {
                $device_type = 'mobile';
            }
        } elseif (preg_match('/Tablet/', $user_agent)) {
            $device_type = 'tablet';
        }
        
        // Detect browser
        if (preg_match('/Chrome\/[\d.]+/', $user_agent) && !preg_match('/Edge/', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/', $user_agent) && !preg_match('/Chrome/', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/[\d.]+/', $user_agent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Opera|OPR/', $user_agent)) {
            $browser = 'Opera';
        }
        
        // Detect OS
        if (preg_match('/Windows NT [\d.]+/', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X [\d_.]+/', $user_agent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $user_agent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android [\d.]+/', $user_agent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone OS [\d_.]+/', $user_agent)) {
            $os = 'iOS';
        }
        
        return [
            'device_type' => $device_type,
            'browser' => $browser,
            'os' => $os
        ];
    }
    
    /**
     * Validate pageview data
     */
    private static function validatePageviewData(array $data): array
    {
        $errors = [];
        
        if (empty($data['site_id'])) {
            $errors[] = 'Site ID is required';
        }
        
        if (empty($data['page_url'])) {
            $errors[] = 'Page URL is required';
        } elseif (!filter_var($data['page_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid page URL';
        }
        
        // Load time validation
        if (isset($data['load_time']) && (!is_numeric($data['load_time']) || $data['load_time'] < 0)) {
            $errors[] = 'Invalid load time';
        }
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(', ', $errors)
        ];
    }
    
    /**
     * Validate event data
     */
    private static function validateEventData(array $data): array
    {
        $errors = [];
        
        if (empty($data['session_id'])) {
            $errors[] = 'Session ID is required';
        }
        
        if (empty($data['event_name'])) {
            $errors[] = 'Event name is required';
        }
        
        // Event value validation
        if (isset($data['event_value']) && !is_numeric($data['event_value'])) {
            $errors[] = 'Event value must be numeric';
        }
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(', ', $errors)
        ];
    }
    
    /**
     * Check event rate limiting
     */
    private static function checkEventRateLimit(string $session_id): bool
    {
        $max_events = self::$config['analytics']['max_events_per_session'];
        
        $event_count = Database::selectOne(
            "SELECT event_count FROM sessions WHERE id = ?",
            [$session_id]
        );
        
        return $event_count && $event_count['event_count'] < $max_events;
    }
    
    /**
     * Get session by ID
     */
    private static function getSessionById(string $session_id): ?array
    {
        return Database::selectOne(
            "SELECT id, site_id, user_hash, first_visit, last_activity 
             FROM sessions WHERE id = ?",
            [$session_id]
        );
    }
    
    /**
     * Sanitize URL
     */
    private static function sanitizeUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        
        // Remove any dangerous characters
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Limit length
        return substr($url, 0, 512);
    }
    
    /**
     * Extract path from URL
     */
    private static function extractPath(string $url): string
    {
        if (empty($url)) {
            return '/';
        }
        
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        // Include query string for tracking
        if (!empty($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }
        
        return substr($path, 0, 512);
    }
    
    /**
     * Sanitize string input
     */
    private static function sanitizeString(string $value, int $max_length = 255): string
    {
        $value = trim($value);
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return substr($value, 0, $max_length);
    }
    
    /**
     * Get site information by tracking code
     */
    public static function getSiteByTrackingCode(string $tracking_code): ?array
    {
        return Database::selectOne(
            "SELECT id, user_id, domain, name, tracking_code, timezone, is_active, settings
             FROM sites WHERE tracking_code = ? AND is_active = 1",
            [$tracking_code]
        );
    }
    
    /**
     * Get live visitor count for a site
     */
    public static function getLiveVisitorCount(int $site_id): int
    {
        $result = Database::selectOne(
            "SELECT COUNT(DISTINCT session_id) as count 
             FROM realtime_visitors 
             WHERE site_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            [$site_id]
        );
        
        return $result['count'] ?? 0;
    }
    
    /**
     * Clean up old realtime visitor records
     */
    public static function cleanupRealtimeVisitors(): int
    {
        return Database::delete(
            "DELETE FROM realtime_visitors WHERE last_seen < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
        );
    }
    
    /**
     * Get tracking script for a site
     */
    public static function getTrackingScript(string $tracking_code): string
    {
        $site = self::getSiteByTrackingCode($tracking_code);
        if (!$site) {
            return '';
        }
        
        $settings = json_decode($site['settings'] ?? '{}', true);
        $app_config = require CONFIG_PATH . '/app.php';
        $tracking_config = $app_config['tracking'];
        
        // Generate tracking script with disguised endpoints
        return self::generateTrackingScript($site, $settings, $tracking_config);
    }
    
    /**
     * Generate tracking JavaScript
     */
    private static function generateTrackingScript(array $site, array $settings, array $config): string
    {
        $tracking_code = $site['tracking_code'];
        $domain = $site['domain'];
        
        $script = "(function(){";
        $script .= "var h='{$tracking_code}',d='{$domain}',";
        $script .= "s=document.createElement('script');";
        $script .= "s.src='/data.js?t='+Date.now();";
        $script .= "s.async=1;document.head.appendChild(s);";
        $script .= "})();";
        
        return $script;
    }
}
?>
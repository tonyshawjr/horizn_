/**
 * horizn_ Analytics Tracking Script Server
 * 
 * Serves the tracking JavaScript with proper cache headers
 * and handles script customization based on site settings.
 */

<?php
// Set appropriate headers
header('Content-Type: application/javascript');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
header('Vary: Accept-Encoding');

// Get site tracking code from query parameter
$tracking_code = $_GET['t'] ?? $_GET['site'] ?? '';
$callback = $_GET['callback'] ?? ''; // For JSONP support

// Basic analytics configuration
$config = [
    'endpoints' => ['/data.css', '/pixel.png', '/i.php'],
    'batchSize' => 10,
    'batchTimeout' => 2000,
    'sessionTimeout' => 1800000, // 30 minutes
];

// If specific tracking code provided, try to get site-specific settings
if ($tracking_code) {
    try {
        // Minimal database connection for settings lookup
        require_once '../app/config/database.php';
        $db_config = require '../app/config/database.php';
        $pdo = new PDO(
            "mysql:host={$db_config['connections']['mysql']['host']};dbname={$db_config['connections']['mysql']['database']};charset=utf8mb4",
            $db_config['connections']['mysql']['username'],
            $db_config['connections']['mysql']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]
        );
        
        $stmt = $pdo->prepare("SELECT settings FROM sites WHERE tracking_code = ? AND is_active = 1");
        $stmt->execute([$tracking_code]);
        $site = $stmt->fetch();
        
        if ($site && $site['settings']) {
            $site_settings = json_decode($site['settings'], true);
            if ($site_settings) {
                // Merge site-specific settings
                if (isset($site_settings['batch_size'])) {
                    $config['batchSize'] = $site_settings['batch_size'];
                }
                if (isset($site_settings['batch_timeout'])) {
                    $config['batchTimeout'] = $site_settings['batch_timeout'];
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail and use default config
        error_log("Analytics script config error: " . $e->getMessage());
    }
}

// Generate the tracking script
$script = generateTrackingScript($config, $tracking_code);

// Handle JSONP callback
if ($callback) {
    echo $callback . '(' . json_encode($script) . ');';
} else {
    echo $script;
}

/**
 * Generate the complete tracking script
 */
function generateTrackingScript($config, $tracking_code = '') {
    $endpoints = json_encode($config['endpoints']);
    $batch_size = $config['batchSize'];
    $batch_timeout = $config['batchTimeout'];
    $session_timeout = $config['sessionTimeout'];
    
    return <<<SCRIPT
/**
 * horizn_ Analytics Tracking Library
 * Lightweight, ad-blocker resistant analytics tracking
 */
(function(window, document, undefined) {
    'use strict';
    
    var h = window.horizn = window.horizn || {};
    var d = document;
    var w = window;
    var n = navigator;
    var l = location;
    var s = screen;
    var c = d.cookie;
    var ls = localStorage;
    var ss = sessionStorage;
    
    // Configuration
    var config = {
        endpoints: {$endpoints},
        batchSize: {$batch_size},
        batchTimeout: {$batch_timeout},
        sessionTimeout: {$session_timeout},
        trackingCode: '{$tracking_code}'
    };
    
    // State variables
    var initialized = false;
    var currentEndpoint = 0;
    var batch = [];
    var batchTimer = null;
    var userId = null;
    var sessionId = null;
    var pageStartTime = performance.now();
    var scrollTracked = {25: false, 50: false, 75: false, 90: false};
    
    // Bot detection
    var isBot = /bot|crawler|spider|scraper|facebookexternalhit|twitterbot|linkedinbot|whatsapp/i.test(n.userAgent);
    if (isBot) return;
    
    // Utility functions
    function hash(str) {
        var h = 0, i, chr;
        if (str.length === 0) return h;
        for (i = 0; i < str.length; i++) {
            chr = str.charCodeAt(i);
            h = ((h << 5) - h) + chr;
            h |= 0;
        }
        return h;
    }
    
    function uuid() {
        return 'xxxx-xxxx-4xxx-yxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    function getCookie(name) {
        var value = c.match('(^|;)\\\\s*' + name + '\\\\s*=\\\\s*([^;]+)');
        return value ? decodeURIComponent(value.pop()) : '';
    }
    
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        d.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; samesite=lax';
    }
    
    function getFingerprint() {
        var fp = n.userAgent + (s.width + 'x' + s.height) + n.language + 
                (n.platform || '') + (n.cookieEnabled ? '1' : '0') + 
                (typeof w.localStorage !== 'undefined' ? '1' : '0');
        return hash(fp).toString(36);
    }
    
    function generateUserId() {
        var stored = getCookie('h_uid');
        if (!stored) {
            stored = getFingerprint() + '_' + uuid();
            setCookie('h_uid', stored, 365);
        }
        return stored;
    }
    
    function generateSessionId() {
        var stored = ss.getItem('h_sid');
        if (stored) {
            var data = JSON.parse(stored);
            if (Date.now() - data.t < config.sessionTimeout) {
                return data.id;
            }
        }
        var newId = 's_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        ss.setItem('h_sid', JSON.stringify({id: newId, t: Date.now()}));
        return newId;
    }
    
    function getPageInfo() {
        return {
            url: l.href,
            path: l.pathname + l.search,
            title: d.title || '',
            referrer: d.referrer || ''
        };
    }
    
    function sendData(data, callback) {
        var endpoint = config.endpoints[currentEndpoint++ % config.endpoints.length];
        
        // Try sendBeacon first (most reliable)
        if (n.sendBeacon && Math.random() < 0.7) {
            try {
                var success = n.sendBeacon(endpoint, JSON.stringify(data));
                if (success && callback) callback(true);
                if (success) return;
            } catch (e) {}
        }
        
        // Try fetch with keepalive
        if (typeof fetch !== 'undefined' && Math.random() < 0.8) {
            try {
                fetch(endpoint, {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: {'Content-Type': 'application/json'},
                    keepalive: true
                }).then(function(response) {
                    if (callback) callback(response.ok);
                }).catch(function() {
                    fallbackXHR(endpoint, data, callback);
                });
                return;
            } catch (e) {}
        }
        
        // Fallback to XHR
        fallbackXHR(endpoint, data, callback);
    }
    
    function fallbackXHR(endpoint, data, callback, attempt) {
        attempt = attempt || 0;
        if (attempt >= 3) {
            if (callback) callback(false);
            return;
        }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', endpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.timeout = 5000;
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    if (callback) callback(true);
                } else {
                    fallbackXHR(endpoint, data, callback, attempt + 1);
                }
            }
        };
        
        xhr.onerror = xhr.ontimeout = function() {
            fallbackXHR(endpoint, data, callback, attempt + 1);
        };
        
        try {
            xhr.send(JSON.stringify(data));
        } catch (e) {
            fallbackXHR(endpoint, data, callback, attempt + 1);
        }
    }
    
    function addToBatch(item) {
        batch.push(item);
        
        if (batch.length >= config.batchSize) {
            flushBatch();
        } else {
            clearTimeout(batchTimer);
            batchTimer = setTimeout(flushBatch, config.batchTimeout);
        }
    }
    
    function flushBatch() {
        if (batch.length === 0) return;
        
        var data = {
            type: 'batch',
            site_id: config.trackingCode,
            session_id: sessionId,
            user_id: userId,
            batch: batch.slice(),
            timestamp: Date.now()
        };
        
        batch = [];
        clearTimeout(batchTimer);
        batchTimer = null;
        
        sendData(data);
    }
    
    function trackPageview(customData) {
        var pageInfo = getPageInfo();
        var loadTime = Math.floor(performance.now() - pageStartTime);
        
        var data = {
            type: 'pageview',
            site_id: config.trackingCode,
            session_id: sessionId,
            user_id: userId,
            url: pageInfo.url,
            path: pageInfo.path,
            title: pageInfo.title,
            referrer: pageInfo.referrer,
            load_time: loadTime,
            timestamp: Date.now()
        };
        
        if (customData) {
            Object.assign(data, customData);
        }
        
        sendData(data);
    }
    
    function trackEvent(eventData) {
        if (!eventData || !eventData.name) return;
        
        var pageInfo = getPageInfo();
        
        var data = {
            type: 'event',
            site_id: config.trackingCode,
            session_id: sessionId,
            user_id: userId,
            event: {
                name: eventData.name,
                category: eventData.category || null,
                action: eventData.action || null,
                label: eventData.label || null,
                value: eventData.value || null,
                data: eventData.data || null
            },
            url: pageInfo.url,
            path: pageInfo.path,
            timestamp: Date.now()
        };
        
        sendData(data);
    }
    
    // Event listeners
    function setupEventListeners() {
        // Click tracking
        d.addEventListener('click', function(e) {
            var target = e.target;
            var tagName = target.tagName.toLowerCase();
            
            if (tagName === 'a') {
                var href = target.href;
                if (href) {
                    var isOutbound = href.indexOf('://') > -1 && href.indexOf(l.hostname) === -1;
                    trackEvent({
                        name: 'click',
                        category: 'navigation',
                        action: isOutbound ? 'outbound' : 'internal',
                        label: href,
                        value: isOutbound ? 1 : 0
                    });
                }
            } else if (tagName === 'button' || target.type === 'submit') {
                trackEvent({
                    name: 'click',
                    category: 'interaction',
                    action: tagName,
                    label: target.textContent || target.value || ''
                });
            }
        }, true);
        
        // Scroll tracking
        var scrollHandler = function() {
            var scrolled = Math.floor((w.scrollY || d.documentElement.scrollTop) / 
                          (d.documentElement.scrollHeight - w.innerHeight) * 100);
            
            [25, 50, 75, 90].forEach(function(percent) {
                if (scrolled >= percent && !scrollTracked[percent]) {
                    scrollTracked[percent] = true;
                    trackEvent({
                        name: 'scroll',
                        category: 'engagement',
                        action: percent + '%',
                        value: percent
                    });
                }
            });
        };
        
        w.addEventListener('scroll', scrollHandler, {passive: true});
        
        // Page visibility
        d.addEventListener('visibilitychange', function() {
            if (d.hidden) {
                h._hidden = Date.now();
            } else if (h._hidden) {
                var hiddenTime = Date.now() - h._hidden;
                if (hiddenTime > 5000) {
                    trackEvent({
                        name: 'visibility',
                        category: 'engagement',
                        action: 'return',
                        value: Math.floor(hiddenTime / 1000)
                    });
                }
                h._hidden = null;
            }
        });
        
        // Page unload
        w.addEventListener('beforeunload', function() {
            flushBatch();
        });
    }
    
    // Public API
    function track() {
        var args = Array.prototype.slice.call(arguments);
        var command = args.shift();
        
        switch (command) {
            case 'page':
            case 'pageview':
                if (!initialized) init();
                else trackPageview(args[0]);
                break;
                
            case 'event':
                if (!initialized) init();
                trackEvent(args[0]);
                break;
                
            case 'set':
                var key = args[0], value = args[1];
                if (key === 'userId') userId = value;
                break;
                
            case 'identify':
                userId = args[0];
                break;
                
            case 'init':
            case 'create':
                config.trackingCode = args[0] || config.trackingCode;
                init();
                break;
        }
    }
    
    function init() {
        if (initialized || !config.trackingCode) return;
        
        initialized = true;
        userId = generateUserId();
        sessionId = generateSessionId();
        
        setupEventListeners();
        trackPageview();
    }
    
    // Set up public interface
    h.track = track;
    h.page = function(data) { track('page', data); };
    h.event = function(data) { track('event', data); };
    h.identify = function(id) { track('identify', id); };
    
    // Process queued calls
    if (h.q) {
        for (var i = 0; i < h.q.length; i++) {
            track.apply(null, h.q[i]);
        }
        h.q = [];
    }
    
    // Auto-initialize if tracking code is provided
    if (config.trackingCode) {
        if (d.readyState === 'loading') {
            d.addEventListener('DOMContentLoaded', init);
        } else {
            setTimeout(init, 100);
        }
    }
    
    // Expose utilities for debugging
    h._config = config;
    h._sessionId = function() { return sessionId; };
    h._userId = function() { return userId; };
    
})(window, document);
SCRIPT;
}
?>
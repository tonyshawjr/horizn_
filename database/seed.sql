-- horizn_ Analytics Platform - Seed Data
-- Creates initial admin user and sample data for testing

USE horizn_analytics;

-- Create initial admin user
-- Password is 'admin123' (change this in production!)
INSERT INTO users (username, email, password_hash, first_name, last_name, is_active) VALUES
('admin', 'admin@horizn.local', '$argon2id$v=19$m=65536,t=4,p=3$YWRtaW4xMjM$RdescudvJCsgt1kP9J8Wv4mGCfjP6Q2MNIg3L7tZFrY', 'Admin', 'User', TRUE);

-- Get the admin user ID for reference
SET @admin_id = LAST_INSERT_ID();

-- Create sample site for testing
INSERT INTO sites (user_id, domain, name, tracking_code, timezone, is_active, settings) VALUES
(@admin_id, 'example.com', 'Example Website', SUBSTRING(MD5(RAND()), 1, 16), 'America/New_York', TRUE, '{"enable_realtime": true, "track_outbound_links": true, "respect_dnt": false}'),
(@admin_id, 'testsite.org', 'Test Site', SUBSTRING(MD5(RAND()), 1, 16), 'UTC', TRUE, '{"enable_realtime": true, "track_outbound_links": false, "respect_dnt": true}');

-- Get site IDs for sample data
SET @site1_id = LAST_INSERT_ID() - 1;
SET @site2_id = LAST_INSERT_ID();

-- Sample session data (last 30 days)
-- We'll create realistic session patterns
SET @base_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY);

-- Helper variables for generating realistic data
SET @session_counter = 0;

-- Generate sample sessions and pageviews for the last 30 days
-- This creates a loop to generate data day by day
DELIMITER //

CREATE PROCEDURE GenerateSampleData()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE day_offset INT DEFAULT 0;
    DECLARE current_date DATE;
    DECLARE daily_sessions INT;
    DECLARE session_count INT DEFAULT 0;
    DECLARE pv_count BIGINT DEFAULT 1;
    
    -- Loop through 30 days
    WHILE day_offset < 30 DO
        SET current_date = DATE_ADD(@base_date, INTERVAL day_offset DAY);
        
        -- Vary sessions by day (more on weekends, peak midweek)
        SET daily_sessions = CASE 
            WHEN DAYOFWEEK(current_date) IN (1, 7) THEN FLOOR(50 + RAND() * 30)  -- Weekend: 50-80 sessions
            WHEN DAYOFWEEK(current_date) IN (3, 4, 5) THEN FLOOR(100 + RAND() * 50) -- Mid-week: 100-150 sessions
            ELSE FLOOR(75 + RAND() * 40) -- Other days: 75-115 sessions
        END;
        
        SET session_count = 0;
        
        -- Generate sessions for this day
        WHILE session_count < daily_sessions DO
            SET @session_id = CONCAT(
                HEX(UNIX_TIMESTAMP(current_date)), 
                '_', 
                LPAD(session_count, 4, '0'),
                '_',
                SUBSTRING(MD5(RAND()), 1, 8)
            );
            
            SET @user_hash = SHA2(CONCAT('user_', session_count, '_', day_offset, RAND()), 256);
            SET @ip_hash = SHA2(CONCAT('192.168.', FLOOR(RAND() * 255), '.', FLOOR(RAND() * 255)), 256);
            
            -- Random session start time during the day
            SET @session_start = ADDTIME(current_date, SEC_TO_TIME(FLOOR(RAND() * 86400)));
            
            -- Session duration (1-30 minutes, weighted toward shorter)
            SET @session_duration = CASE 
                WHEN RAND() < 0.4 THEN FLOOR(60 + RAND() * 180)  -- 1-4 minutes (40% - bounces)
                WHEN RAND() < 0.8 THEN FLOOR(240 + RAND() * 600)  -- 4-14 minutes (40%)
                ELSE FLOOR(840 + RAND() * 1200) -- 14-34 minutes (20%)
            END;
            
            SET @session_end = DATE_ADD(@session_start, INTERVAL @session_duration SECOND);
            
            -- Number of pages in session (1-10, weighted toward fewer)
            SET @page_count = CASE 
                WHEN RAND() < 0.35 THEN 1  -- Single page view (35% - bounces)
                WHEN RAND() < 0.65 THEN 2 + FLOOR(RAND() * 2)  -- 2-3 pages (30%)
                WHEN RAND() < 0.85 THEN 4 + FLOOR(RAND() * 3)  -- 4-6 pages (20%)
                ELSE 7 + FLOOR(RAND() * 4) -- 7-10 pages (15%)
            END;
            
            SET @is_bounce = IF(@page_count = 1 AND @session_duration < 30, TRUE, FALSE);
            
            -- Random referrer
            SET @referrer_choice = RAND();
            SET @referrer = CASE 
                WHEN @referrer_choice < 0.3 THEN NULL -- Direct traffic (30%)
                WHEN @referrer_choice < 0.5 THEN 'https://google.com' -- Google (20%)
                WHEN @referrer_choice < 0.6 THEN 'https://bing.com' -- Bing (10%)
                WHEN @referrer_choice < 0.7 THEN 'https://facebook.com' -- Facebook (10%)
                WHEN @referrer_choice < 0.75 THEN 'https://twitter.com' -- Twitter (5%)
                WHEN @referrer_choice < 0.8 THEN 'https://linkedin.com' -- LinkedIn (5%)
                WHEN @referrer_choice < 0.85 THEN 'https://reddit.com' -- Reddit (5%)
                WHEN @referrer_choice < 0.9 THEN 'https://github.com' -- GitHub (5%)
                ELSE CONCAT('https://referrer', FLOOR(RAND() * 20), '.com') -- Other sites (10%)
            END;
            
            SET @referrer_domain = CASE 
                WHEN @referrer IS NULL THEN NULL
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(@referrer, '//', -1), '/', 1)
            END;
            
            -- Sample pages to visit
            SET @sample_pages = JSON_ARRAY(
                '/',
                '/about',
                '/contact',
                '/blog',
                '/products',
                '/services',
                '/pricing',
                '/features',
                '/documentation',
                '/support',
                '/blog/post-1',
                '/blog/post-2',
                '/blog/post-3',
                '/products/item-a',
                '/products/item-b'
            );
            
            SET @entry_page = JSON_UNQUOTE(JSON_EXTRACT(@sample_pages, CONCAT('$[', FLOOR(RAND() * JSON_LENGTH(@sample_pages)), ']')));
            
            -- Random user agent
            SET @user_agent_choice = RAND();
            SET @user_agent = CASE 
                WHEN @user_agent_choice < 0.4 THEN 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                WHEN @user_agent_choice < 0.7 THEN 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                WHEN @user_agent_choice < 0.85 THEN 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0'
                WHEN @user_agent_choice < 0.95 THEN 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15'
                ELSE 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            END;
            
            -- Determine device info from user agent
            SET @device_type = CASE 
                WHEN @user_agent LIKE '%Mobile%' OR @user_agent LIKE '%Android%' THEN 'mobile'
                WHEN @user_agent LIKE '%iPad%' OR @user_agent LIKE '%Tablet%' THEN 'tablet'
                ELSE 'desktop'
            END;
            
            SET @browser = CASE 
                WHEN @user_agent LIKE '%Chrome/%' AND @user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                WHEN @user_agent LIKE '%Firefox/%' THEN 'Firefox'
                WHEN @user_agent LIKE '%Safari/%' AND @user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                WHEN @user_agent LIKE '%Edge/%' THEN 'Edge'
                ELSE 'Other'
            END;
            
            SET @os = CASE 
                WHEN @user_agent LIKE '%Windows NT%' THEN 'Windows'
                WHEN @user_agent LIKE '%Mac OS X%' THEN 'macOS'
                WHEN @user_agent LIKE '%Linux%' THEN 'Linux'
                WHEN @user_agent LIKE '%Android%' THEN 'Android'
                WHEN @user_agent LIKE '%iOS%' THEN 'iOS'
                ELSE 'Other'
            END;
            
            -- Insert session
            INSERT INTO sessions (
                id, site_id, user_hash, first_visit, last_activity,
                page_count, event_count, is_bounce, referrer, referrer_domain,
                entry_page, exit_page, user_agent, browser, os, device_type,
                ip_hash, country_code, created_at, updated_at
            ) VALUES (
                @session_id, @site1_id, @user_hash, @session_start, @session_end,
                @page_count, 0, @is_bounce, @referrer, @referrer_domain,
                @entry_page, @entry_page, @user_agent, @browser, @os, @device_type,
                @ip_hash, 'US', @session_start, @session_end
            );
            
            -- Generate pageviews for this session
            SET @pv_counter = 0;
            SET @current_page = @entry_page;
            SET @pv_timestamp = @session_start;
            
            WHILE @pv_counter < @page_count DO
                -- If not the first pageview, pick a random page
                IF @pv_counter > 0 THEN
                    SET @current_page = JSON_UNQUOTE(JSON_EXTRACT(@sample_pages, CONCAT('$[', FLOOR(RAND() * JSON_LENGTH(@sample_pages)), ']')));
                    -- Add some time between pageviews
                    SET @pv_timestamp = DATE_ADD(@pv_timestamp, INTERVAL FLOOR(30 + RAND() * 120) SECOND);
                END IF;
                
                -- Page load time (100ms - 3000ms, weighted toward faster)
                SET @load_time = CASE 
                    WHEN RAND() < 0.7 THEN FLOOR(100 + RAND() * 500)  -- 100-600ms (70%)
                    WHEN RAND() < 0.9 THEN FLOOR(600 + RAND() * 800)  -- 600-1400ms (20%)
                    ELSE FLOOR(1400 + RAND() * 1600) -- 1400-3000ms (10%)
                END;
                
                INSERT INTO pageviews (
                    site_id, session_id, page_url, page_path,
                    page_title, referrer, user_agent, ip_hash,
                    load_time, timestamp
                ) VALUES (
                    @site1_id, @session_id, 
                    CONCAT('https://example.com', @current_page),
                    @current_page,
                    CONCAT(UPPER(SUBSTRING(@current_page, 2, 1)), SUBSTRING(@current_page, 3), ' | Example'),
                    IF(@pv_counter = 0, @referrer, CONCAT('https://example.com', @entry_page)),
                    @user_agent, @ip_hash,
                    @load_time, @pv_timestamp
                );
                
                SET @pv_counter = @pv_counter + 1;
                SET pv_count = pv_count + 1;
            END WHILE;
            
            -- Update exit page
            UPDATE sessions SET exit_page = @current_page WHERE id = @session_id;
            
            SET session_count = session_count + 1;
        END WHILE;
        
        SET day_offset = day_offset + 1;
    END WHILE;
    
    -- Generate some sample events
    INSERT INTO events (site_id, session_id, event_name, event_category, event_action, event_label, page_url, page_path, timestamp)
    SELECT 
        @site1_id,
        s.id,
        CASE FLOOR(RAND() * 6)
            WHEN 0 THEN 'click'
            WHEN 1 THEN 'form_submit'  
            WHEN 2 THEN 'download'
            WHEN 3 THEN 'video_play'
            WHEN 4 THEN 'scroll_75'
            ELSE 'outbound_link'
        END,
        'engagement',
        'user_interaction',
        CASE FLOOR(RAND() * 4)
            WHEN 0 THEN 'header_button'
            WHEN 1 THEN 'footer_link'
            WHEN 2 THEN 'sidebar_widget'
            ELSE 'main_content'
        END,
        CONCAT('https://example.com', s.entry_page),
        s.entry_page,
        DATE_ADD(s.first_visit, INTERVAL FLOOR(RAND() * 600) SECOND)
    FROM sessions s 
    WHERE s.site_id = @site1_id 
    AND RAND() < 0.3  -- 30% of sessions have events
    LIMIT 200;
    
    -- Update event counts in sessions
    UPDATE sessions s SET event_count = (
        SELECT COUNT(*) FROM events e WHERE e.session_id = s.id
    ) WHERE s.site_id = @site1_id;
    
END //

DELIMITER ;

-- Run the procedure to generate sample data
CALL GenerateSampleData();

-- Clean up the procedure
DROP PROCEDURE GenerateSampleData;

-- Generate daily aggregation data
INSERT INTO daily_stats (site_id, date, pageviews, unique_visitors, sessions, bounce_rate, avg_session_duration)
SELECT 
    s.site_id,
    DATE(s.first_visit) as date,
    COUNT(p.id) as pageviews,
    COUNT(DISTINCT s.user_hash) as unique_visitors,
    COUNT(s.id) as sessions,
    ROUND(AVG(CASE WHEN s.is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate,
    ROUND(AVG(TIMESTAMPDIFF(SECOND, s.first_visit, s.last_activity)), 0) as avg_session_duration
FROM sessions s
LEFT JOIN pageviews p ON s.id = p.session_id
WHERE s.site_id = @site1_id
GROUP BY s.site_id, DATE(s.first_visit)
ORDER BY date;

-- Generate page stats aggregation
INSERT INTO page_stats (site_id, page_path, date, pageviews, unique_visitors, bounce_rate)
SELECT 
    p.site_id,
    p.page_path,
    DATE(p.timestamp) as date,
    COUNT(p.id) as pageviews,
    COUNT(DISTINCT s.user_hash) as unique_visitors,
    ROUND(AVG(CASE WHEN s.is_bounce THEN 1 ELSE 0 END) * 100, 2) as bounce_rate
FROM pageviews p
JOIN sessions s ON p.session_id = s.id
WHERE p.site_id = @site1_id
GROUP BY p.site_id, p.page_path, DATE(p.timestamp)
ORDER BY date, pageviews DESC;

-- Generate referrer stats aggregation  
INSERT INTO referrer_stats (site_id, referrer_domain, date, sessions, pageviews, unique_visitors)
SELECT 
    s.site_id,
    COALESCE(s.referrer_domain, '(direct)') as referrer_domain,
    DATE(s.first_visit) as date,
    COUNT(s.id) as sessions,
    COUNT(p.id) as pageviews,
    COUNT(DISTINCT s.user_hash) as unique_visitors
FROM sessions s
LEFT JOIN pageviews p ON s.id = p.session_id
WHERE s.site_id = @site1_id
GROUP BY s.site_id, s.referrer_domain, DATE(s.first_visit)
ORDER BY date, sessions DESC;

-- Add some realtime visitors (current)
INSERT INTO realtime_visitors (site_id, session_id, page_url, page_title, last_seen, user_agent, ip_hash)
SELECT 
    @site1_id,
    CONCAT('realtime_', FLOOR(RAND() * 1000000)),
    CONCAT('https://example.com', 
        CASE FLOOR(RAND() * 5)
            WHEN 0 THEN '/'
            WHEN 1 THEN '/about'
            WHEN 2 THEN '/blog'
            WHEN 3 THEN '/products'
            ELSE '/contact'
        END
    ),
    'Current Page Title',
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 300) SECOND), -- Last 5 minutes
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    SHA2(CONCAT('realtime_ip_', FLOOR(RAND() * 1000)), 256)
FROM 
    (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1,
    (SELECT 1 UNION SELECT 2 UNION SELECT 3) t2
LIMIT FLOOR(3 + RAND() * 8); -- 3-10 current visitors

-- Insert system settings (if they don't exist)
INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
('admin_email', 'admin@horizn.local', 'Administrator email address'),
('site_title', 'horizn_ Analytics', 'Main site title'),
('default_timezone', 'UTC', 'Default timezone for new sites'),
('max_sites_per_user', '10', 'Maximum number of sites per user'),
('enable_user_registration', '0', 'Allow new user registration'),
('tracking_code_length', '16', 'Length of generated tracking codes');

-- Display summary of generated data
SELECT 'Sample data generation completed!' as status;

SELECT 
    'Total users created:' as metric,
    COUNT(*) as value
FROM users;

SELECT 
    'Total sites created:' as metric,
    COUNT(*) as value  
FROM sites;

SELECT 
    'Total sessions generated:' as metric,
    COUNT(*) as value
FROM sessions;

SELECT 
    'Total pageviews generated:' as metric,
    COUNT(*) as value
FROM pageviews;

SELECT 
    'Total events generated:' as metric,
    COUNT(*) as value
FROM events;

SELECT 
    'Current realtime visitors:' as metric,
    COUNT(*) as value
FROM realtime_visitors 
WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);

-- Show date range of generated data
SELECT 
    MIN(DATE(first_visit)) as data_start_date,
    MAX(DATE(first_visit)) as data_end_date,
    DATEDIFF(MAX(DATE(first_visit)), MIN(DATE(first_visit))) + 1 as days_of_data
FROM sessions;
-- horizn_ Analytics Platform Database Schema
-- First-party, ad-blocker resistant analytics platform
-- Optimized for performance with proper indexing

-- Create database
CREATE DATABASE IF NOT EXISTS horizn_analytics;
USE horizn_analytics;

-- Users table for platform authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username)
);

-- Sites table for tracking multiple websites
CREATE TABLE sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    tracking_code VARCHAR(32) UNIQUE NOT NULL,
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON,
    INDEX idx_domain (domain),
    INDEX idx_tracking_code (tracking_code),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sessions table for tracking user sessions
CREATE TABLE sessions (
    id VARCHAR(64) PRIMARY KEY,
    site_id INT NOT NULL,
    user_hash VARCHAR(64) NOT NULL,
    first_visit TIMESTAMP NOT NULL,
    last_activity TIMESTAMP NOT NULL,
    page_count INT DEFAULT 1,
    event_count INT DEFAULT 0,
    is_bounce BOOLEAN DEFAULT TRUE,
    referrer VARCHAR(512),
    referrer_domain VARCHAR(255),
    entry_page VARCHAR(512),
    exit_page VARCHAR(512),
    user_agent TEXT,
    browser VARCHAR(100),
    os VARCHAR(100),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    ip_hash VARCHAR(64),
    country_code CHAR(2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_first_visit (site_id, first_visit),
    INDEX idx_site_last_activity (site_id, last_activity),
    INDEX idx_user_hash (user_hash),
    INDEX idx_referrer_domain (referrer_domain),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Page views table for tracking page visits
CREATE TABLE pageviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    page_url VARCHAR(512) NOT NULL,
    page_path VARCHAR(512) NOT NULL,
    page_title VARCHAR(255),
    referrer VARCHAR(512),
    user_agent TEXT,
    ip_hash VARCHAR(64) NOT NULL,
    load_time INT, -- Page load time in milliseconds
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    additional_data JSON,
    INDEX idx_site_timestamp (site_id, timestamp),
    INDEX idx_site_path (site_id, page_path),
    INDEX idx_session (session_id),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- Events table for custom event tracking
CREATE TABLE events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_category VARCHAR(100),
    event_action VARCHAR(100),
    event_label VARCHAR(255),
    event_value INT,
    event_data JSON,
    page_url VARCHAR(512),
    page_path VARCHAR(512),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_event_timestamp (site_id, event_name, timestamp),
    INDEX idx_site_category_timestamp (site_id, event_category, timestamp),
    INDEX idx_session (session_id),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- Real-time visitors table for live tracking
CREATE TABLE realtime_visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    page_url VARCHAR(512) NOT NULL,
    page_title VARCHAR(255),
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_agent TEXT,
    ip_hash VARCHAR(64),
    INDEX idx_site_last_seen (site_id, last_seen),
    INDEX idx_session (session_id),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- Daily aggregation table for performance
CREATE TABLE daily_stats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    date DATE NOT NULL,
    pageviews INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    sessions INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    avg_session_duration INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_site_date (site_id, date),
    INDEX idx_site_date (site_id, date),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Page stats aggregation table
CREATE TABLE page_stats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    page_path VARCHAR(512) NOT NULL,
    date DATE NOT NULL,
    pageviews INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    avg_time_on_page INT DEFAULT 0, -- in seconds
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    exit_rate DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_site_path_date (site_id, page_path, date),
    INDEX idx_site_date (site_id, date),
    INDEX idx_site_path (site_id, page_path),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- Referrer stats aggregation table
CREATE TABLE referrer_stats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    referrer_domain VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    sessions INT DEFAULT 0,
    pageviews INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_site_referrer_date (site_id, referrer_domain, date),
    INDEX idx_site_date (site_id, date),
    INDEX idx_referrer_domain (referrer_domain),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

-- System settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('data_retention_days', '365', 'Number of days to retain raw analytics data'),
('session_timeout', '1800', 'Session timeout in seconds (30 minutes)'),
('realtime_cleanup_interval', '300', 'Interval to cleanup old realtime visitor records (5 minutes)'),
('enable_geolocation', '1', 'Enable IP-based geolocation tracking'),
('enable_realtime', '1', 'Enable real-time visitor tracking'),
('max_events_per_session', '1000', 'Maximum events allowed per session');

-- Views for common queries

-- Real-time visitors view
CREATE VIEW realtime_active_visitors AS
SELECT 
    rv.site_id,
    s.name as site_name,
    COUNT(DISTINCT rv.session_id) as active_visitors,
    COUNT(*) as active_pageviews
FROM realtime_visitors rv
JOIN sites s ON rv.site_id = s.id
WHERE rv.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
GROUP BY rv.site_id, s.name;

-- Daily overview view
CREATE VIEW daily_overview AS
SELECT 
    ds.site_id,
    s.name as site_name,
    ds.date,
    ds.pageviews,
    ds.unique_visitors,
    ds.sessions,
    ds.bounce_rate,
    ds.avg_session_duration
FROM daily_stats ds
JOIN sites s ON ds.site_id = s.id
ORDER BY ds.site_id, ds.date DESC;

-- Top pages view
CREATE VIEW top_pages_today AS
SELECT 
    ps.site_id,
    s.name as site_name,
    ps.page_path,
    ps.pageviews,
    ps.unique_visitors,
    ps.avg_time_on_page,
    ps.bounce_rate
FROM page_stats ps
JOIN sites s ON ps.site_id = s.id
WHERE ps.date = CURDATE()
ORDER BY ps.site_id, ps.pageviews DESC;

-- Create indexes for better performance on large datasets
ALTER TABLE pageviews ADD INDEX idx_site_timestamp_path (site_id, timestamp, page_path);
ALTER TABLE events ADD INDEX idx_site_timestamp_name (site_id, timestamp, event_name);
ALTER TABLE sessions ADD INDEX idx_site_created_bounce (site_id, created_at, is_bounce);

-- Partition tables by month for better performance (optional, for large datasets)
-- ALTER TABLE pageviews PARTITION BY RANGE(YEAR(timestamp)*100 + MONTH(timestamp))
-- (PARTITION p202501 VALUES LESS THAN (202502),
--  PARTITION p202502 VALUES LESS THAN (202503),
--  ...
--  PARTITION p_future VALUES LESS THAN MAXVALUE);

-- Create stored procedures for common operations

DELIMITER //

-- Procedure to calculate bounce rate for a site and date range
CREATE PROCEDURE CalculateBounceRate(
    IN p_site_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        COUNT(CASE WHEN is_bounce = TRUE THEN 1 END) * 100.0 / COUNT(*) as bounce_rate
    FROM sessions 
    WHERE site_id = p_site_id 
    AND DATE(first_visit) BETWEEN p_start_date AND p_end_date;
END //

-- Procedure to get top pages for a site and date range
CREATE PROCEDURE GetTopPages(
    IN p_site_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_limit INT
)
BEGIN
    SELECT 
        page_path,
        COUNT(*) as pageviews,
        COUNT(DISTINCT session_id) as unique_visitors,
        AVG(load_time) as avg_load_time
    FROM pageviews 
    WHERE site_id = p_site_id 
    AND DATE(timestamp) BETWEEN p_start_date AND p_end_date
    GROUP BY page_path
    ORDER BY pageviews DESC
    LIMIT p_limit;
END //

-- Procedure to clean up old realtime visitor records
CREATE PROCEDURE CleanupRealtimeVisitors()
BEGIN
    DELETE FROM realtime_visitors 
    WHERE last_seen < DATE_SUB(NOW(), INTERVAL 10 MINUTE);
END //

DELIMITER ;

-- Create events for automatic cleanup (requires EVENT scheduler to be enabled)
-- SET GLOBAL event_scheduler = ON;

-- CREATE EVENT IF NOT EXISTS cleanup_realtime_visitors
-- ON SCHEDULE EVERY 5 MINUTE
-- DO CALL CleanupRealtimeVisitors();

-- Performance notes:
-- 1. Consider partitioning large tables (pageviews, events) by month
-- 2. Archive old data regularly to maintain performance  
-- 3. Use the aggregation tables for dashboard queries
-- 4. Monitor query performance and add indexes as needed
-- 5. Consider read replicas for high-traffic deployments
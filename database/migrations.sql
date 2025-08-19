-- horizn_ Analytics Platform Database Migrations
-- Version-controlled database schema changes

-- Migration tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_version (version)
);

-- Record initial schema creation
INSERT INTO migrations (version, description) VALUES 
('001_initial_schema', 'Create initial database schema with all core tables');

-- Migration 002: Add magic links authentication
INSERT INTO migrations (version, description) VALUES 
('002_magic_links', 'Add magic links table and first_login flag for secure authentication');

-- Create magic links table
CREATE TABLE IF NOT EXISTS magic_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(128) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    is_used BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_email (email),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add first_login flag to users table
ALTER TABLE users ADD COLUMN first_login BOOLEAN DEFAULT TRUE AFTER is_active;

-- Migration 002: Add browser and device detection columns
-- INSERT INTO migrations (version, description) VALUES 
-- ('002_browser_detection', 'Add browser and device detection to sessions table');

-- Example future migration:
-- ALTER TABLE sessions 
-- ADD COLUMN browser VARCHAR(100) AFTER user_agent,
-- ADD COLUMN os VARCHAR(100) AFTER browser,
-- ADD COLUMN device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop' AFTER os;

-- Migration 003: Add performance tracking
-- INSERT INTO migrations (version, description) VALUES 
-- ('003_performance_tracking', 'Add performance metrics to pageviews table');

-- ALTER TABLE pageviews 
-- ADD COLUMN load_time INT AFTER ip_hash,
-- ADD COLUMN dom_ready_time INT AFTER load_time,
-- ADD COLUMN first_paint_time INT AFTER dom_ready_time;

-- Migration 004: Add geolocation support
-- INSERT INTO migrations (version, description) VALUES 
-- ('004_geolocation_support', 'Add country tracking to sessions table');

-- ALTER TABLE sessions 
-- ADD COLUMN country_code CHAR(2) AFTER ip_hash,
-- ADD COLUMN region VARCHAR(100) AFTER country_code,
-- ADD COLUMN city VARCHAR(100) AFTER region;

-- Migration 005: Add event categories and labels
-- INSERT INTO migrations (version, description) VALUES 
-- ('005_event_categories', 'Add category and label fields to events table');

-- ALTER TABLE events 
-- ADD COLUMN event_category VARCHAR(100) AFTER event_name,
-- ADD COLUMN event_action VARCHAR(100) AFTER event_category,
-- ADD COLUMN event_label VARCHAR(255) AFTER event_action,
-- ADD COLUMN event_value INT AFTER event_label;

-- Migration 006: Add daily aggregation tables
-- INSERT INTO migrations (version, description) VALUES 
-- ('006_daily_aggregation', 'Create daily stats aggregation tables for performance');

-- Daily stats, page stats, and referrer stats tables are already in initial schema

-- Migration 007: Add user timezone support
-- INSERT INTO migrations (version, description) VALUES 
-- ('007_user_timezones', 'Add timezone support to sites table');

-- ALTER TABLE sites 
-- ADD COLUMN timezone VARCHAR(50) DEFAULT 'UTC' AFTER name;

-- Migration 008: Add real-time visitors table
-- INSERT INTO migrations (version, description) VALUES 
-- ('008_realtime_visitors', 'Create real-time visitors tracking table');

-- Realtime visitors table is already in initial schema

-- Migration 009: Add settings table
-- INSERT INTO migrations (version, description) VALUES 
-- ('009_settings_table', 'Create system settings table');

-- Settings table is already in initial schema

-- Migration 010: Add indexes for performance
-- INSERT INTO migrations (version, description) VALUES 
-- ('010_performance_indexes', 'Add additional indexes for query performance');

-- Additional performance indexes are already in initial schema

-- Function to check if migration has been executed
DELIMITER //

CREATE FUNCTION MigrationExecuted(migration_version VARCHAR(20)) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE migration_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO migration_count
    FROM migrations 
    WHERE version = migration_version;
    
    RETURN migration_count > 0;
END //

DELIMITER ;

-- Procedure to execute migration if not already done
DELIMITER //

CREATE PROCEDURE ExecuteMigration(
    IN migration_version VARCHAR(20),
    IN migration_description TEXT,
    IN migration_sql TEXT
)
BEGIN
    DECLARE migration_exists INT DEFAULT 0;
    
    -- Check if migration already executed
    SELECT COUNT(*) INTO migration_exists
    FROM migrations 
    WHERE version = migration_version;
    
    -- Execute migration if not already done
    IF migration_exists = 0 THEN
        -- Execute the migration SQL
        SET @sql = migration_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Record migration as executed
        INSERT INTO migrations (version, description) 
        VALUES (migration_version, migration_description);
        
        SELECT CONCAT('Migration ', migration_version, ' executed successfully') as result;
    ELSE
        SELECT CONCAT('Migration ', migration_version, ' already executed') as result;
    END IF;
END //

DELIMITER ;

-- Example of how to use the migration system:
-- CALL ExecuteMigration(
--     '011_new_feature', 
--     'Add new feature to the system',
--     'ALTER TABLE pageviews ADD COLUMN new_column VARCHAR(255);'
-- );

-- Rollback procedures (use with caution)
DELIMITER //

CREATE PROCEDURE RollbackMigration(
    IN migration_version VARCHAR(20)
)
BEGIN
    -- This is a placeholder - implement specific rollback logic per migration
    SELECT CONCAT('Rollback for migration ', migration_version, ' must be implemented manually') as warning;
    
    -- Example rollback (uncomment and modify as needed):
    -- DELETE FROM migrations WHERE version = migration_version;
    
END //

DELIMITER ;

-- Get current database version
DELIMITER //

CREATE PROCEDURE GetDatabaseVersion()
BEGIN
    SELECT 
        version,
        description,
        executed_at
    FROM migrations 
    ORDER BY executed_at DESC 
    LIMIT 1;
END //

DELIMITER ;

-- List all executed migrations
DELIMITER //

CREATE PROCEDURE ListMigrations()
BEGIN
    SELECT 
        version,
        description,
        executed_at
    FROM migrations 
    ORDER BY executed_at ASC;
END //

DELIMITER ;
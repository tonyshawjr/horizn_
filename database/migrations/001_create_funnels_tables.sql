-- Funnel Analysis Tables Migration
-- Creates tables for storing and analyzing conversion funnels

-- Funnels table - stores funnel configurations
CREATE TABLE funnels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'paused', 'archived') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    settings JSON DEFAULT NULL,
    INDEX idx_site_id (site_id),
    INDEX idx_site_status (site_id, status),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Funnel steps table - defines the steps in each funnel
CREATE TABLE funnel_steps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    funnel_id INT NOT NULL,
    step_order INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    step_type ENUM('pageview', 'event', 'custom') NOT NULL,
    conditions JSON NOT NULL, -- Conditions for this step (page_path, event_name, etc.)
    is_required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_funnel_id_order (funnel_id, step_order),
    INDEX idx_funnel_id (funnel_id),
    FOREIGN KEY (funnel_id) REFERENCES funnels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_funnel_step_order (funnel_id, step_order)
);

-- Funnel analytics table - stores calculated funnel metrics
CREATE TABLE funnel_analytics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    funnel_id INT NOT NULL,
    date DATE NOT NULL,
    step_1_users INT DEFAULT 0,
    step_2_users INT DEFAULT 0,
    step_3_users INT DEFAULT 0,
    step_4_users INT DEFAULT 0,
    step_5_users INT DEFAULT 0,
    step_6_users INT DEFAULT 0,
    step_7_users INT DEFAULT 0,
    step_8_users INT DEFAULT 0,
    step_9_users INT DEFAULT 0,
    step_10_users INT DEFAULT 0,
    total_conversions INT DEFAULT 0,
    overall_conversion_rate DECIMAL(5,2) DEFAULT 0,
    avg_time_to_convert INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_funnel_date (funnel_id, date),
    INDEX idx_date (date),
    UNIQUE KEY unique_funnel_date (funnel_id, date),
    FOREIGN KEY (funnel_id) REFERENCES funnels(id) ON DELETE CASCADE
);

-- Funnel user sessions table - tracks individual user journeys through funnels
CREATE TABLE funnel_user_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    funnel_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    user_hash VARCHAR(64) NOT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    last_step_reached INT DEFAULT 1,
    conversion_time INT DEFAULT NULL, -- time in seconds to complete funnel
    steps_data JSON DEFAULT NULL, -- timestamps and metadata for each step
    is_converted BOOLEAN DEFAULT FALSE,
    date DATE NOT NULL,
    INDEX idx_funnel_session (funnel_id, session_id),
    INDEX idx_funnel_date (funnel_id, date),
    INDEX idx_session (session_id),
    INDEX idx_user_hash (user_hash),
    INDEX idx_converted (funnel_id, is_converted),
    FOREIGN KEY (funnel_id) REFERENCES funnels(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
);

-- Funnel step performance table - detailed metrics for each step
CREATE TABLE funnel_step_performance (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    funnel_id INT NOT NULL,
    step_id INT NOT NULL,
    date DATE NOT NULL,
    users_entered INT DEFAULT 0,
    users_completed INT DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    drop_off_rate DECIMAL(5,2) DEFAULT 0,
    avg_time_on_step INT DEFAULT 0, -- in seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_funnel_step_date (funnel_id, step_id, date),
    INDEX idx_step_date (step_id, date),
    UNIQUE KEY unique_funnel_step_date (funnel_id, step_id, date),
    FOREIGN KEY (funnel_id) REFERENCES funnels(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id) REFERENCES funnel_steps(id) ON DELETE CASCADE
);

-- Create view for funnel overview
CREATE VIEW funnel_overview AS
SELECT 
    f.id,
    f.site_id,
    f.name,
    f.description,
    f.status,
    f.created_at,
    COUNT(fs.id) as total_steps,
    COALESCE(AVG(fa.overall_conversion_rate), 0) as avg_conversion_rate,
    COALESCE(SUM(fa.total_conversions), 0) as total_conversions,
    COALESCE(AVG(fa.avg_time_to_convert), 0) as avg_time_to_convert
FROM funnels f
LEFT JOIN funnel_steps fs ON f.id = fs.funnel_id
LEFT JOIN funnel_analytics fa ON f.id = fa.funnel_id 
    AND fa.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
WHERE f.status = 'active'
GROUP BY f.id, f.site_id, f.name, f.description, f.status, f.created_at;

-- Stored procedure for calculating funnel analytics
DELIMITER //

CREATE PROCEDURE CalculateFunnelAnalytics(
    IN p_funnel_id INT,
    IN p_date DATE
)
BEGIN
    DECLARE v_step_count INT DEFAULT 0;
    DECLARE v_step_id INT;
    DECLARE v_step_order INT DEFAULT 1;
    DECLARE v_users_at_step INT DEFAULT 0;
    DECLARE v_prev_users INT DEFAULT 0;
    DECLARE done INT DEFAULT FALSE;
    
    -- Declare cursor for funnel steps
    DECLARE step_cursor CURSOR FOR 
        SELECT id, step_order 
        FROM funnel_steps 
        WHERE funnel_id = p_funnel_id 
        ORDER BY step_order;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Get total number of steps
    SELECT COUNT(*) INTO v_step_count 
    FROM funnel_steps 
    WHERE funnel_id = p_funnel_id;
    
    -- Initialize analytics record
    INSERT INTO funnel_analytics (funnel_id, date)
    VALUES (p_funnel_id, p_date)
    ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
    
    -- Calculate metrics for each step
    OPEN step_cursor;
    read_loop: LOOP
        FETCH step_cursor INTO v_step_id, v_step_order;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Calculate users at this step
        SELECT COUNT(DISTINCT session_id) INTO v_users_at_step
        FROM funnel_user_sessions
        WHERE funnel_id = p_funnel_id 
        AND date = p_date
        AND last_step_reached >= v_step_order;
        
        -- Update step-specific column in funnel_analytics
        SET @sql = CONCAT('UPDATE funnel_analytics SET step_', v_step_order, '_users = ', v_users_at_step, 
                         ' WHERE funnel_id = ', p_funnel_id, ' AND date = ''', p_date, '''');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        
        -- Calculate step performance
        INSERT INTO funnel_step_performance (
            funnel_id, step_id, date, users_entered, users_completed,
            conversion_rate, drop_off_rate
        )
        SELECT 
            p_funnel_id,
            v_step_id,
            p_date,
            CASE WHEN v_step_order = 1 THEN v_users_at_step ELSE v_prev_users END as users_entered,
            v_users_at_step as users_completed,
            CASE 
                WHEN v_step_order = 1 THEN 100.0
                WHEN v_prev_users > 0 THEN (v_users_at_step * 100.0 / v_prev_users)
                ELSE 0
            END as conversion_rate,
            CASE 
                WHEN v_step_order = 1 THEN 0
                WHEN v_prev_users > 0 THEN ((v_prev_users - v_users_at_step) * 100.0 / v_prev_users)
                ELSE 0
            END as drop_off_rate
        ON DUPLICATE KEY UPDATE
            users_entered = VALUES(users_entered),
            users_completed = VALUES(users_completed),
            conversion_rate = VALUES(conversion_rate),
            drop_off_rate = VALUES(drop_off_rate),
            updated_at = CURRENT_TIMESTAMP;
            
        SET v_prev_users = v_users_at_step;
    END LOOP;
    CLOSE step_cursor;
    
    -- Update overall funnel metrics
    UPDATE funnel_analytics fa
    SET 
        total_conversions = (
            SELECT COUNT(*) 
            FROM funnel_user_sessions 
            WHERE funnel_id = p_funnel_id 
            AND date = p_date 
            AND is_converted = TRUE
        ),
        overall_conversion_rate = (
            SELECT 
                CASE 
                    WHEN step_1_users > 0 THEN (
                        SELECT COUNT(*) 
                        FROM funnel_user_sessions 
                        WHERE funnel_id = p_funnel_id 
                        AND date = p_date 
                        AND is_converted = TRUE
                    ) * 100.0 / step_1_users
                    ELSE 0 
                END
        ),
        avg_time_to_convert = (
            SELECT COALESCE(AVG(conversion_time), 0)
            FROM funnel_user_sessions
            WHERE funnel_id = p_funnel_id 
            AND date = p_date
            AND is_converted = TRUE
        )
    WHERE funnel_id = p_funnel_id AND date = p_date;
    
END //

DELIMITER ;
-- Custom Dashboards Migration
-- Creates tables for custom dashboard functionality

-- Custom dashboards table
CREATE TABLE IF NOT EXISTS custom_dashboards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    layout JSON,
    widgets JSON,
    settings JSON,
    is_shared BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_shared (is_shared),
    INDEX idx_created_at (created_at),
    INDEX idx_deleted_at (deleted_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dashboard shares table (for sharing dashboards with other users)
CREATE TABLE IF NOT EXISTS dashboard_shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dashboard_id INT NOT NULL,
    shared_with_user_id INT NOT NULL,
    permissions ENUM('view', 'edit') DEFAULT 'view',
    shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_shared_with_user_id (shared_with_user_id),
    INDEX idx_shared_at (shared_at),
    UNIQUE KEY unique_dashboard_user (dashboard_id, shared_with_user_id),
    FOREIGN KEY (dashboard_id) REFERENCES custom_dashboards(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Dashboard views table (for tracking usage analytics)
CREATE TABLE IF NOT EXISTS dashboard_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dashboard_id INT NOT NULL,
    user_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_duration INT DEFAULT 0,
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at),
    FOREIGN KEY (dashboard_id) REFERENCES custom_dashboards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Widget configurations table (for storing individual widget settings)
CREATE TABLE IF NOT EXISTS widget_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dashboard_id INT NOT NULL,
    widget_id VARCHAR(255) NOT NULL,
    widget_type VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    settings JSON,
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    width INT DEFAULT 4,
    height INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_widget_type (widget_type),
    INDEX idx_position (position_x, position_y),
    UNIQUE KEY unique_dashboard_widget (dashboard_id, widget_id),
    FOREIGN KEY (dashboard_id) REFERENCES custom_dashboards(id) ON DELETE CASCADE
);

-- Dashboard templates table (for pre-built dashboard templates)
CREATE TABLE IF NOT EXISTS dashboard_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    layout JSON,
    widgets JSON,
    settings JSON,
    preview_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default dashboard templates
INSERT INTO dashboard_templates (name, description, category, layout, widgets, settings, is_active) VALUES
('Analytics Overview', 'A comprehensive overview of your website analytics', 'general', 
 '[{"id":"widget_overview_1","x":0,"y":0,"w":3,"h":2},{"id":"widget_overview_2","x":3,"y":0,"w":3,"h":2},{"id":"widget_overview_3","x":6,"y":0,"w":3,"h":2},{"id":"widget_overview_4","x":9,"y":0,"w":3,"h":2},{"id":"widget_chart_1","x":0,"y":2,"w":8,"h":4},{"id":"widget_list_1","x":8,"y":2,"w":4,"h":4}]',
 '[{"id":"widget_overview_1","type":"metric","title":"Total Visitors","settings":{"metric_type":"unique_visitors","time_period":"30d"}},{"id":"widget_overview_2","type":"metric","title":"Page Views","settings":{"metric_type":"pageviews","time_period":"30d"}},{"id":"widget_overview_3","type":"metric","title":"Bounce Rate","settings":{"metric_type":"bounce_rate","time_period":"30d"}},{"id":"widget_overview_4","type":"metric","title":"Avg. Session","settings":{"metric_type":"avg_session_duration","time_period":"30d"}},{"id":"widget_chart_1","type":"chart","title":"Traffic Trends","settings":{"chart_type":"line","data_source":"pageviews","time_period":"30d","grouping":"day"}},{"id":"widget_list_1","type":"list","title":"Top Pages","settings":{"list_type":"top_pages","limit":10,"time_period":"30d"}}]',
 '{"columns":12,"auto_refresh":false}', TRUE),

('Real-time Dashboard', 'Monitor your website activity in real-time', 'realtime',
 '[{"id":"widget_realtime_1","x":0,"y":0,"w":4,"h":2},{"id":"widget_realtime_2","x":4,"y":0,"w":4,"h":2},{"id":"widget_realtime_3","x":8,"y":0,"w":4,"h":2},{"id":"widget_map_1","x":0,"y":2,"w":6,"h":4},{"id":"widget_list_2","x":6,"y":2,"w":6,"h":4}]',
 '[{"id":"widget_realtime_1","type":"metric","title":"Active Visitors","settings":{"metric_type":"active_visitors","time_period":"1d","auto_refresh":true}},{"id":"widget_realtime_2","type":"metric","title":"Today\'s Views","settings":{"metric_type":"pageviews","time_period":"1d","auto_refresh":true}},{"id":"widget_realtime_3","type":"metric","title":"Live Events","settings":{"metric_type":"events","time_period":"1d","auto_refresh":true}},{"id":"widget_map_1","type":"map","title":"Visitor Locations","settings":{"map_type":"world","metric":"visitors","time_period":"1d"}},{"id":"widget_list_2","type":"list","title":"Recent Activity","settings":{"list_type":"recent_events","limit":20,"auto_refresh":true}}]',
 '{"columns":12,"auto_refresh":true,"refresh_interval":30}', TRUE),

('Conversion Tracking', 'Track your conversion funnels and goals', 'conversion',
 '[{"id":"widget_funnel_1","x":0,"y":0,"w":12,"h":4},{"id":"widget_metric_1","x":0,"y":4,"w":3,"h":2},{"id":"widget_metric_2","x":3,"y":4,"w":3,"h":2},{"id":"widget_metric_3","x":6,"y":4,"w":3,"h":2},{"id":"widget_metric_4","x":9,"y":4,"w":3,"h":2}]',
 '[{"id":"widget_funnel_1","type":"funnel","title":"Main Conversion Funnel","settings":{"time_period":"30d"}},{"id":"widget_metric_1","type":"metric","title":"Conversion Rate","settings":{"metric_type":"conversion_rate","time_period":"30d"}},{"id":"widget_metric_2","type":"metric","title":"Goal Completions","settings":{"metric_type":"goal_completions","time_period":"30d"}},{"id":"widget_metric_3","type":"metric","title":"Revenue","settings":{"metric_type":"revenue","time_period":"30d"}},{"id":"widget_metric_4","type":"metric","title":"AOV","settings":{"metric_type":"average_order_value","time_period":"30d"}}]',
 '{"columns":12,"auto_refresh":false}', TRUE);

-- Create indexes for better performance
CREATE INDEX idx_custom_dashboards_user_shared ON custom_dashboards(user_id, is_shared);
CREATE INDEX idx_dashboard_views_dashboard_date ON dashboard_views(dashboard_id, viewed_at);
CREATE INDEX idx_widget_configurations_dashboard_type ON widget_configurations(dashboard_id, widget_type);
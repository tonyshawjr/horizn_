# horizn_ Analytics Platform - Technical Architecture

## System Architecture

### Overview
horizn_ uses a modern PHP MVC architecture with first-party tracking to ensure ad-blocker resistance. The system consists of a web dashboard, tracking API, and WordPress plugin.

### Core Components

#### 1. Web Dashboard (`/public/`)
- **Entry Point**: `index.php` - Main application entry
- **Frontend**: Preline UI components (heavily customized)
- **Authentication**: Session-based auth with secure cookies
- **Real-time Updates**: JavaScript polling for live data

#### 2. Application Layer (`/app/`)
```
/app/
├── config/           # Configuration files
├── controllers/      # MVC Controllers
├── models/          # Data models and database interaction
├── views/           # HTML templates and UI components
└── lib/             # Core library and utilities
```

#### 3. Database Layer (`/database/`)
- **Schema**: MySQL 8.0+ with optimized indexes
- **Migrations**: Version-controlled database changes
- **Data Types**: JSON columns for flexible event data

#### 4. WordPress Plugin (`/horizn-wp-plugin/`)
- **Auto-tracking**: Seamless integration with WordPress
- **Admin Interface**: Settings page in WordPress admin
- **Hooks**: WordPress action/filter integration

## Ad-blocker Resistance Strategy

### 1. First-party Endpoints
```
# Instead of: analytics.google.com/collect
# We use: yoursite.com/assets/data.js
# Or: yoursite.com/img/pixel.php
# Or: yoursite.com/css/track.css
```

### 2. Disguised Requests
- Tracking endpoints mimic common web resources
- Use multiple endpoint patterns with fallbacks
- Implement beacon API for modern browsers

### 3. Custom JavaScript
```javascript
// No external libraries - pure vanilla JS
// Minified and obfuscated tracking code
// Multiple collection methods with fallbacks
```

## Database Schema

### Core Tables

#### `sites`
```sql
CREATE TABLE sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    domain VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    tracking_code VARCHAR(32) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    settings JSON,
    INDEX idx_domain (domain),
    INDEX idx_tracking_code (tracking_code)
);
```

#### `pageviews`
```sql
CREATE TABLE pageviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    page_url VARCHAR(512) NOT NULL,
    page_title VARCHAR(255),
    referrer VARCHAR(512),
    user_agent TEXT,
    ip_hash VARCHAR(64) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    additional_data JSON,
    INDEX idx_site_timestamp (site_id, timestamp),
    INDEX idx_session (session_id),
    FOREIGN KEY (site_id) REFERENCES sites(id)
);
```

#### `events`
```sql
CREATE TABLE events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_data JSON,
    page_url VARCHAR(512),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_event_timestamp (site_id, event_name, timestamp),
    INDEX idx_session (session_id),
    FOREIGN KEY (site_id) REFERENCES sites(id)
);
```

#### `sessions`
```sql
CREATE TABLE sessions (
    id VARCHAR(64) PRIMARY KEY,
    site_id INT NOT NULL,
    user_hash VARCHAR(64) NOT NULL,
    first_visit TIMESTAMP NOT NULL,
    last_activity TIMESTAMP NOT NULL,
    page_count INT DEFAULT 1,
    is_bounce BOOLEAN DEFAULT TRUE,
    referrer VARCHAR(512),
    entry_page VARCHAR(512),
    exit_page VARCHAR(512),
    user_agent TEXT,
    ip_hash VARCHAR(64),
    INDEX idx_site_first_visit (site_id, first_visit),
    INDEX idx_user_hash (user_hash),
    FOREIGN KEY (site_id) REFERENCES sites(id)
);
```

## API Endpoints

### Tracking API
```
POST /track/pageview     # Record page view
POST /track/event        # Record custom event
GET  /track/pixel        # Fallback pixel tracking
POST /track/batch        # Batch multiple events
```

### Dashboard API
```
GET  /api/stats/overview     # Dashboard overview data
GET  /api/stats/realtime     # Real-time visitor data
GET  /api/stats/pages        # Page performance data
GET  /api/stats/referrers    # Referrer data
GET  /api/stats/events       # Custom events data
POST /api/sites              # Create new site
GET  /api/sites              # List user's sites
```

## Security Architecture

### Data Privacy
- **IP Hashing**: SHA-256 hash with salt for IP addresses
- **Session IDs**: Cryptographically secure random strings
- **No PII**: No personal identifiable information stored
- **Data Retention**: Configurable automatic data purging

### Authentication
- **Secure Sessions**: HttpOnly, Secure, SameSite cookies
- **CSRF Protection**: Token-based CSRF protection
- **Rate Limiting**: API rate limiting to prevent abuse
- **Input Validation**: Strict input validation on all endpoints

## Performance Optimization

### Database Optimization
- **Partitioning**: Partition large tables by date
- **Indexes**: Strategic indexing for common queries
- **Connection Pooling**: Efficient database connection management
- **Query Caching**: Cache frequently accessed aggregated data

### Frontend Optimization
- **Minification**: Minified CSS and JavaScript
- **Compression**: Gzip compression for all assets
- **CDN Ready**: Asset structure ready for CDN deployment
- **Lazy Loading**: Progressive loading of dashboard components

## Deployment Architecture

### Directory Structure
```
/public/              # Web root
├── index.php         # Main entry point
├── .htaccess         # Apache rewrite rules
└── /assets/          # Static assets
    ├── /css/         # Stylesheets
    ├── /js/          # JavaScript files
    └── /img/         # Images and icons

/app/                 # Application code
├── /config/          # Configuration files
├── /controllers/     # MVC Controllers
├── /models/          # Data models
├── /views/           # HTML templates
└── /lib/             # Core libraries

/database/            # Database files
├── schema.sql        # Database schema
└── migrations.sql    # Database migrations

/horizn-wp-plugin/    # WordPress plugin
├── horizn-analytics.php
├── /includes/
└── /admin/
```

### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0+ with extensions (mysqli, json, curl, openssl)
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **SSL**: HTTPS required for secure tracking
- **Memory**: 256MB+ PHP memory limit

## Integration Patterns

### WordPress Plugin Integration
```php
// Automatic tracking injection
add_action('wp_footer', 'horizn_inject_tracking');

// Custom event tracking
do_action('horizn_track_event', 'purchase', $data);

// Admin dashboard widget
add_action('wp_dashboard_setup', 'horizn_dashboard_widget');
```

### JavaScript Tracking
```javascript
// Asynchronous loading
(function(h,o,r,i,z,n){
  // Tracking code injection
  // Multiple fallback methods
  // Event listener attachment
})();
```

This architecture ensures maximum compatibility, performance, and ad-blocker resistance while maintaining a clean, maintainable codebase.
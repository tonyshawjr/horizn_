# Models Folder - Data Layer

## Purpose
Contains all data models for database interaction, business logic, and data processing for the horizn_ analytics platform.

## Rules
- **Single Responsibility**: Each model handles one main entity
- **Database Abstraction**: Use prepared statements exclusively
- **Data Validation**: Validate all data before database operations
- **Error Handling**: Proper exception handling for database errors
- **Performance**: Optimize queries with proper indexing

## Model Patterns
```php
<?php
class SiteModel {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO sites (user_id, domain, name, tracking_code) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['user_id'],
            $data['domain'],
            $data['name'],
            $this->generateTrackingCode()
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function getByTrackingCode(string $code): ?array {
        $sql = "SELECT * FROM sites WHERE tracking_code = ? AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function generateTrackingCode(): string {
        return 'hz_' . bin2hex(random_bytes(12));
    }
}
```

## Required Models
```
UserModel.php         # User management and authentication
SiteModel.php         # Website/domain management
SessionModel.php      # User session tracking
PageviewModel.php     # Page view analytics
EventModel.php        # Custom event tracking
StatsModel.php        # Analytics aggregation and reporting
RealtimeModel.php     # Real-time visitor tracking
```

## Analytics Models Special Requirements

### StatsModel
- **Aggregation**: Efficient data aggregation queries
- **Caching**: Cache frequently accessed statistics
- **Date Ranges**: Flexible date range filtering
- **Performance**: Use aggregation tables for large datasets

### TrackingModels (Session, Pageview, Event)
- **High Performance**: Optimized for high-volume inserts
- **Data Validation**: Strict validation of tracking data
- **IP Anonymization**: Hash IP addresses for privacy
- **Batch Processing**: Support batch data insertion

## Database Interaction Patterns
```php
// Use transactions for data consistency
$this->db->beginTransaction();
try {
    // Multiple related operations
    $sessionId = $this->sessionModel->create($sessionData);
    $this->pageviewModel->create($pageviewData, $sessionId);
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}

// Use prepared statements exclusively
$sql = "SELECT * FROM pageviews WHERE site_id = ? AND timestamp >= ?";
$stmt = $this->db->prepare($sql);
$stmt->execute([$siteId, $startDate]);
```

## Data Privacy Implementation
- **IP Hashing**: Hash IP addresses with salt
- **Session Anonymization**: Generate secure session IDs
- **Data Retention**: Implement automatic data cleanup
- **GDPR Compliance**: Provide data export/deletion methods

## Primary Agents
- backend-architect
- rapid-prototyper
- test-writer-fixer
- analytics-reporter

## Performance Optimization
- Use indexed queries for frequent lookups
- Implement data aggregation for dashboard queries
- Cache statistical calculations
- Use batch inserts for high-volume data
- Monitor query performance and optimize as needed

## Error Handling
- Catch and log all database exceptions
- Provide meaningful error messages
- Implement retry logic for transient failures
- Use proper HTTP status codes for API responses
- Log performance metrics for monitoring
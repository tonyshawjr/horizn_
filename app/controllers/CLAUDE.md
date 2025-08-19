# Controllers Folder - MVC Controllers

## Purpose
Contains all MVC controller classes that handle HTTP requests, process data, and coordinate between models and views.

## Rules
- **Single Responsibility**: Each controller handles one main entity/feature
- **RESTful Methods**: Follow REST conventions where applicable
- **Input Validation**: Validate all inputs before processing
- **Error Handling**: Proper error responses and logging
- **Authentication**: Check user permissions for protected actions

## Controller Patterns
```php
<?php
class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index(): void {
        // Check authentication
        if (!$this->isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
        
        // Get data from models
        $stats = new StatsModel($this->db);
        $data = $stats->getOverview();
        
        // Render view
        $this->render('dashboard/index', $data);
    }
    
    private function render(string $view, array $data = []): void {
        extract($data);
        include APP_PATH . '/views/' . $view . '.php';
    }
}
```

## Required Controllers
```
AuthController.php        # Login/logout functionality
DashboardController.php   # Main analytics dashboard
SitesController.php       # Site management
SettingsController.php    # User/application settings
TrackingController.php    # Analytics tracking endpoints
ApiStatsController.php    # API endpoints for analytics data
```

## Tracking Controller Special Requirements
- **Ad-blocker Resistance**: Disguise endpoints as assets
- **Performance**: Minimal processing time
- **Validation**: Strict input validation for tracking data
- **Rate Limiting**: Prevent abuse and spam
- **Fallbacks**: Multiple collection methods

## API Controllers
- **JSON Responses**: Always return JSON for API endpoints
- **HTTP Status Codes**: Use proper status codes
- **Error Messages**: Consistent error response format
- **Rate Limiting**: Implement API rate limiting
- **Authentication**: API key or session-based auth

## Primary Agents
- backend-architect
- api-tester
- rapid-prototyper
- test-writer-fixer

## Security Requirements
- CSRF protection on all forms
- Input sanitization and validation
- SQL injection prevention
- XSS prevention in output
- Authentication checks on protected routes
- Rate limiting on public endpoints

## Performance Considerations
- Cache frequently accessed data
- Minimize database queries per request
- Use prepared statements for database access
- Implement proper session management
- Log performance metrics for optimization
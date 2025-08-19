# horizn_ WordPress Plugin - Includes Folder

## Purpose
Core WordPress plugin classes and functionality

## Rules
- **WordPress Standards**: Follow WordPress coding standards strictly
- **Object-Oriented**: Use classes with proper namespacing
- **Security First**: All user input must be sanitized and validated
- **Performance**: Minimize database queries and optimize for speed
- **Hooks**: Use WordPress hooks properly (actions/filters)

## File Structure
- `class-horizn-tracker.php` - Tracking code injection and event handling
- `class-horizn-admin.php` - Admin interface and settings management
- `class-horizn-api.php` - API communication with analytics backend
- `class-horizn-settings.php` - Settings validation and storage

## Code Standards
```php
// Proper WordPress class structure
class Horizn_Class_Name {
    private $property;
    
    public function __construct() {
        // Initialize
    }
    
    public function init() {
        // WordPress hooks
        add_action('wp_footer', [$this, 'method_name']);
    }
}
```

## Security Requirements
- Always check `current_user_can()` for admin functions
- Use `wp_verify_nonce()` for form submissions
- Sanitize with `sanitize_text_field()`, `esc_url_raw()`, etc.
- Escape output with `esc_html()`, `esc_attr()`, etc.

## Primary Agents
- backend-architect
- frontend-developer
- test-writer-fixer
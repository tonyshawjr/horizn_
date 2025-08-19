# horizn_ WordPress Plugin Folder

## Purpose
WordPress plugin for seamless integration of horizn_ analytics tracking with WordPress websites.

## Rules
- **WordPress Standards**: Follow WordPress plugin development standards
- **PHP Compatibility**: Support PHP 7.4+ for WordPress compatibility  
- **Security**: WordPress nonces, capability checks, data sanitization
- **Performance**: Minimal impact on site loading speed
- **User Experience**: Simple installation and configuration

## Plugin Structure
```
horizn-analytics.php     # Main plugin file
readme.txt              # WordPress plugin directory readme
/includes/
  class-horizn-admin.php     # Admin interface
  class-horizn-tracker.php   # Tracking code injection
  class-horizn-settings.php  # Plugin settings
  class-horizn-api.php       # API communication
/admin/
  settings-page.php          # Settings page template
  dashboard-widget.php       # WordPress dashboard widget
/assets/
  admin.css                  # Admin styles
  admin.js                   # Admin JavaScript
/languages/
  horizn-analytics.pot       # Translation template
```

## Main Plugin File Template
```php
<?php
/**
 * Plugin Name: horizn_ Analytics
 * Plugin URI: https://github.com/tonyshawjr/horizn_
 * Description: First-party, ad-blocker resistant analytics platform with crypto/saas aesthetic.
 * Version: 1.0.0
 * Author: Tony Shaw Jr
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HORIZN_VERSION', '1.0.0');
define('HORIZN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HORIZN_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Main plugin class
class HoriznAnalytics {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_footer', [$this, 'inject_tracking_code']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function init() {
        // Plugin initialization
    }
    
    public function inject_tracking_code() {
        // Inject ad-blocker resistant tracking code
    }
}

new HoriznAnalytics();
```

## WordPress Integration Features

### Automatic Tracking
- **Page Views**: Track all page visits automatically
- **Post Types**: Track custom post types and pages
- **WooCommerce**: E-commerce event tracking (if WooCommerce active)
- **User Interactions**: Track form submissions, downloads, outbound links

### Admin Interface
- **Settings Page**: Configure tracking options
- **Dashboard Widget**: Show analytics in WordPress admin
- **Site Connection**: Easy connection to horizn_ platform
- **Tracking Code**: Automatic tracking code generation and injection

### WordPress Hooks Integration
```php
// Track WooCommerce events
add_action('woocommerce_thankyou', 'horizn_track_purchase');

// Track form submissions
add_action('gform_after_submission', 'horizn_track_form_submission');

// Track user registrations
add_action('user_register', 'horizn_track_user_registration');

// Track post views
add_action('wp_head', 'horizn_track_post_view');
```

## Plugin Settings
```php
// Plugin options
$horizn_options = [
    'api_endpoint' => '',      // horizn_ platform URL
    'site_id' => '',          // Site ID from horizn_ platform
    'tracking_code' => '',     // Unique tracking code
    'track_admin' => false,    // Track admin users
    'track_logged_in' => true, // Track logged-in users
    'enable_ecommerce' => true, // Enable e-commerce tracking
    'custom_events' => [],     // Custom event definitions
];
```

## Security Implementation
```php
// WordPress nonce verification
if (!wp_verify_nonce($_POST['horizn_nonce'], 'horizn_settings')) {
    wp_die('Security check failed');
}

// Capability checks
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page');
}

// Data sanitization
$site_id = sanitize_text_field($_POST['site_id']);
$api_endpoint = esc_url_raw($_POST['api_endpoint']);
```

## Ad-blocker Resistant Implementation
```php
public function inject_tracking_code() {
    $tracking_code = get_option('horizn_tracking_code');
    if (empty($tracking_code)) {
        return;
    }
    
    // Multiple disguised endpoints for fallbacks
    $endpoints = [
        home_url('/wp-content/themes/assets/data.js'),
        home_url('/wp-includes/js/analytics.js'),
        home_url('/wp-content/uploads/pixel.png')
    ];
    
    // Inject tracking script with fallbacks
    echo '<script>';
    echo $this->generate_tracking_script($tracking_code, $endpoints);
    echo '</script>';
}
```

## WordPress Dashboard Widget
```php
public function dashboard_widget() {
    // Get analytics data from horizn_ API
    $stats = $this->get_analytics_data();
    
    if ($stats) {
        echo '<div class="horizn-dashboard-widget">';
        echo '<h3>horizn_ Analytics</h3>';
        echo '<div class="horizn-stat">';
        echo '<span class="stat-number">' . number_format($stats['pageviews']) . '</span>';
        echo '<span class="stat-label">Page Views (7 days)</span>';
        echo '</div>';
        echo '<a href="' . admin_url('options-general.php?page=horizn-settings') . '">View Full Analytics</a>';
        echo '</div>';
    }
}
```

## WooCommerce Integration
```php
public function track_woocommerce_events() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Track purchase completions
    add_action('woocommerce_thankyou', function($order_id) {
        $order = wc_get_order($order_id);
        $this->track_event('purchase', [
            'value' => $order->get_total(),
            'currency' => $order->get_currency(),
            'order_id' => $order_id
        ]);
    });
    
    // Track add to cart events
    add_action('woocommerce_add_to_cart', function($cart_item_key, $product_id) {
        $product = wc_get_product($product_id);
        $this->track_event('add_to_cart', [
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'price' => $product->get_price()
        ]);
    }, 10, 2);
}
```

## Plugin Activation/Deactivation
```php
register_activation_hook(__FILE__, 'horizn_activate');
register_deactivation_hook(__FILE__, 'horizn_deactivate');

function horizn_activate() {
    // Create default options
    add_option('horizn_settings', [
        'api_endpoint' => '',
        'tracking_enabled' => true,
        'track_admin' => false
    ]);
    
    // Schedule cleanup cron job
    wp_schedule_event(time(), 'daily', 'horizn_cleanup_data');
}

function horizn_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('horizn_cleanup_data');
}
```

## Primary Agents
- mobile-app-builder (WordPress is mobile-heavy)
- frontend-developer
- backend-architect
- test-writer-fixer

## WordPress Specific Requirements
- **Plugin Standards**: Follow WordPress coding standards
- **Translation Ready**: Use WordPress translation functions
- **Multisite Compatible**: Support WordPress multisite installations
- **Performance**: Minimal database queries and resource usage
- **Compatibility**: Test with popular WordPress themes and plugins

## Testing Strategy
- Test with popular WordPress themes
- Test with WooCommerce and other major plugins
- Test on WordPress multisite installations
- Test plugin activation/deactivation processes
- Test with various PHP versions (7.4+)
- Validate WordPress security best practices
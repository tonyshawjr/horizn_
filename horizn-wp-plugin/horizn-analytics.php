<?php
/**
 * Plugin Name: horizn_ Analytics
 * Plugin URI: https://github.com/tonyshawjr/horizn_
 * Description: First-party, ad-blocker resistant analytics platform with crypto/saas aesthetic. Track users without external dependencies.
 * Version: 1.0.0
 * Author: Tony Shaw Jr
 * Author URI: https://tonyshaw.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: horizn-analytics
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HORIZN_VERSION', '1.0.0');
define('HORIZN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HORIZN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HORIZN_PLUGIN_FILE', __FILE__);

/**
 * Main HoriznAnalytics Plugin Class
 */
class HoriznAnalytics {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Tracker instance
     */
    private $tracker;
    
    /**
     * Admin instance
     */
    private $admin;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        
        // Plugin activation/deactivation hooks
        register_activation_hook(HORIZN_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(HORIZN_PLUGIN_FILE, [$this, 'deactivate']);
        
        // Login hook for auto-identify
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once HORIZN_PLUGIN_PATH . 'includes/class-horizn-tracker.php';
        require_once HORIZN_PLUGIN_PATH . 'includes/class-horizn-admin.php';
        
        $this->tracker = new Horizn_Tracker();
        $this->admin = new Horizn_Admin();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('horizn-analytics', false, dirname(plugin_basename(HORIZN_PLUGIN_FILE)) . '/languages');
        
        // Initialize components
        if ($this->tracker) {
            $this->tracker->init();
        }
        
        if ($this->admin && is_admin()) {
            $this->admin->init();
        }
        
        // Hook into WooCommerce if available
        if (class_exists('WooCommerce')) {
            $this->init_woocommerce_tracking();
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('horizn-analytics', false, dirname(plugin_basename(HORIZN_PLUGIN_FILE)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        $default_options = [
            'api_endpoint' => home_url(),
            'site_key' => $this->generate_site_key(),
            'tracking_enabled' => true,
            'track_admin' => false,
            'track_logged_in' => true,
            'enable_ecommerce' => true,
            'custom_endpoints' => [
                '/wp-content/themes/assets/data.js',
                '/wp-includes/js/analytics.js',
                '/wp-content/uploads/pixel.png'
            ]
        ];
        
        if (!get_option('horizn_settings')) {
            add_option('horizn_settings', $default_options);
        }
        
        // Schedule cleanup cron job
        if (!wp_next_scheduled('horizn_cleanup_data')) {
            wp_schedule_event(time(), 'daily', 'horizn_cleanup_data');
        }
        
        // Flush rewrite rules for custom endpoints
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('horizn_cleanup_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Generate a unique site key
     */
    private function generate_site_key() {
        return 'hzn_' . wp_generate_uuid4();
    }
    
    /**
     * Handle user login for auto-identify
     */
    public function on_user_login($user_login, $user) {
        if ($this->tracker && get_option('horizn_settings')['track_logged_in']) {
            // Store user ID in session for identification
            if (!session_id()) {
                session_start();
            }
            $_SESSION['horizn_user_id'] = $user->ID;
            $_SESSION['horizn_user_email'] = $user->user_email;
        }
    }
    
    /**
     * Initialize WooCommerce tracking
     */
    private function init_woocommerce_tracking() {
        // Track purchase completions
        add_action('woocommerce_thankyou', [$this, 'track_woocommerce_purchase'], 10, 1);
        
        // Track add to cart
        add_action('woocommerce_add_to_cart', [$this, 'track_woocommerce_add_to_cart'], 10, 6);
    }
    
    /**
     * Track WooCommerce purchase
     */
    public function track_woocommerce_purchase($order_id) {
        if (!$order_id) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $purchase_data = [
            'name' => 'purchase',
            'category' => 'ecommerce',
            'action' => 'purchase_complete',
            'value' => floatval($order->get_total()),
            'data' => [
                'order_id' => $order_id,
                'currency' => $order->get_currency(),
                'items' => $order->get_item_count(),
                'payment_method' => $order->get_payment_method()
            ]
        ];
        
        // Add tracking script to footer
        add_action('wp_footer', function() use ($purchase_data) {
            echo '<script>
                if (typeof horizn !== "undefined") {
                    horizn.event(' . wp_json_encode($purchase_data) . ');
                }
            </script>';
        });
    }
    
    /**
     * Track WooCommerce add to cart
     */
    public function track_woocommerce_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $cart_data = [
            'name' => 'add_to_cart',
            'category' => 'ecommerce',
            'action' => 'add_to_cart',
            'value' => floatval($product->get_price() * $quantity),
            'data' => [
                'product_id' => $product_id,
                'product_name' => $product->get_name(),
                'quantity' => $quantity,
                'price' => floatval($product->get_price())
            ]
        ];
        
        // Store in session to track on next page load
        if (!session_id()) {
            session_start();
        }
        $_SESSION['horizn_pending_events'][] = $cart_data;
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'horizn_analytics_widget',
                __('horizn_ Analytics', 'horizn-analytics'),
                [$this, 'dashboard_widget_content']
            );
        }
    }
    
    /**
     * Dashboard widget content
     */
    public function dashboard_widget_content() {
        $settings = get_option('horizn_settings', []);
        
        if (empty($settings['site_key'])) {
            echo '<p>' . __('Configure horizn_ Analytics to see stats.', 'horizn-analytics') . '</p>';
            echo '<a href="' . admin_url('options-general.php?page=horizn-settings') . '" class="button">';
            echo __('Configure Now', 'horizn-analytics') . '</a>';
            return;
        }
        
        echo '<div class="horizn-dashboard-widget">';
        echo '<style>
            .horizn-dashboard-widget { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
            .horizn-stat { margin: 10px 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; text-align: center; }
            .stat-number { display: block; font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .stat-label { font-size: 12px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
            .horizn-widget-actions { margin-top: 15px; }
            .horizn-widget-actions .button { margin-right: 10px; }
        </style>';
        
        // Mock data for now - in production this would fetch from API
        echo '<div class="horizn-stat">';
        echo '<span class="stat-number">-</span>';
        echo '<span class="stat-label">' . __('Live Visitors', 'horizn-analytics') . '</span>';
        echo '</div>';
        
        echo '<div class="horizn-widget-actions">';
        echo '<a href="' . admin_url('options-general.php?page=horizn-settings') . '" class="button">';
        echo __('View Settings', 'horizn-analytics') . '</a>';
        echo '<a href="#" class="button button-primary" onclick="horiznTestTracking(); return false;">';
        echo __('Test Tracking', 'horizn-analytics') . '</a>';
        echo '</div>';
        
        echo '<script>
            function horiznTestTracking() {
                if (typeof horizn !== "undefined") {
                    horizn.event({
                        name: "admin_test",
                        category: "admin",
                        action: "test_tracking",
                        value: 1
                    });
                    alert("' . __('Test event sent! Check your analytics dashboard.', 'horizn-analytics') . '");
                } else {
                    alert("' . __('Tracking script not loaded. Check your configuration.', 'horizn-analytics') . '");
                }
            }
        </script>';
        
        echo '</div>';
    }
    
    /**
     * Get tracker instance
     */
    public function get_tracker() {
        return $this->tracker;
    }
    
    /**
     * Get admin instance
     */
    public function get_admin() {
        return $this->admin;
    }
}

/**
 * Initialize the plugin
 */
function horizn_analytics() {
    return HoriznAnalytics::get_instance();
}

// Start the plugin
horizn_analytics();

/**
 * Helper functions for template developers
 */

/**
 * Track custom event
 */
function horizn_track_event($event_data) {
    $tracker = horizn_analytics()->get_tracker();
    if ($tracker) {
        $tracker->add_custom_event($event_data);
    }
}

/**
 * Identify user
 */
function horizn_identify_user($user_id, $traits = []) {
    $tracker = horizn_analytics()->get_tracker();
    if ($tracker) {
        $tracker->identify_user($user_id, $traits);
    }
}

/**
 * Track page view with custom data
 */
function horizn_track_page($data = []) {
    $tracker = horizn_analytics()->get_tracker();
    if ($tracker) {
        $tracker->add_page_data($data);
    }
}
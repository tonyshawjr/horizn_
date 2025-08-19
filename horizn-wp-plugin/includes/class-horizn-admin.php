<?php
/**
 * Horizn Analytics Admin Class
 * 
 * Handles the admin interface and settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Horizn_Admin {
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('horizn_settings', []);
    }
    
    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(HORIZN_PLUGIN_FILE), [$this, 'add_plugin_action_links']);
        
        // AJAX handlers
        add_action('wp_ajax_horizn_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_horizn_generate_site_key', [$this, 'ajax_generate_site_key']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('horizn_ Analytics Settings', 'horizn-analytics'),
            __('horizn_ Analytics', 'horizn-analytics'),
            'manage_options',
            'horizn-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('horizn_settings_group', 'horizn_settings', [$this, 'validate_settings']);
        
        // General Settings Section
        add_settings_section(
            'horizn_general_section',
            __('General Settings', 'horizn-analytics'),
            [$this, 'general_section_callback'],
            'horizn-settings'
        );
        
        add_settings_field(
            'api_endpoint',
            __('API Endpoint', 'horizn-analytics'),
            [$this, 'api_endpoint_field'],
            'horizn-settings',
            'horizn_general_section'
        );
        
        add_settings_field(
            'site_key',
            __('Site Key', 'horizn-analytics'),
            [$this, 'site_key_field'],
            'horizn-settings',
            'horizn_general_section'
        );
        
        add_settings_field(
            'tracking_enabled',
            __('Enable Tracking', 'horizn-analytics'),
            [$this, 'tracking_enabled_field'],
            'horizn-settings',
            'horizn_general_section'
        );
        
        // Privacy Settings Section
        add_settings_section(
            'horizn_privacy_section',
            __('Privacy Settings', 'horizn-analytics'),
            [$this, 'privacy_section_callback'],
            'horizn-settings'
        );
        
        add_settings_field(
            'track_admin',
            __('Track Admin Users', 'horizn-analytics'),
            [$this, 'track_admin_field'],
            'horizn-settings',
            'horizn_privacy_section'
        );
        
        add_settings_field(
            'track_logged_in',
            __('Track Logged-in Users', 'horizn-analytics'),
            [$this, 'track_logged_in_field'],
            'horizn-settings',
            'horizn_privacy_section'
        );
        
        // E-commerce Settings Section
        add_settings_section(
            'horizn_ecommerce_section',
            __('E-commerce Settings', 'horizn-analytics'),
            [$this, 'ecommerce_section_callback'],
            'horizn-settings'
        );
        
        add_settings_field(
            'enable_ecommerce',
            __('Enable E-commerce Tracking', 'horizn-analytics'),
            [$this, 'enable_ecommerce_field'],
            'horizn-settings',
            'horizn_ecommerce_section'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'horizn_advanced_section',
            __('Advanced Settings', 'horizn-analytics'),
            [$this, 'advanced_section_callback'],
            'horizn-settings'
        );
        
        add_settings_field(
            'custom_endpoints',
            __('Custom Endpoints', 'horizn-analytics'),
            [$this, 'custom_endpoints_field'],
            'horizn-settings',
            'horizn_advanced_section'
        );
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = [];
        
        // Validate API endpoint
        if (!empty($input['api_endpoint'])) {
            $validated['api_endpoint'] = esc_url_raw($input['api_endpoint']);
        } else {
            $validated['api_endpoint'] = home_url();
        }
        
        // Validate site key
        $validated['site_key'] = sanitize_text_field($input['site_key'] ?? '');
        
        // Validate boolean settings
        $validated['tracking_enabled'] = !empty($input['tracking_enabled']);
        $validated['track_admin'] = !empty($input['track_admin']);
        $validated['track_logged_in'] = !empty($input['track_logged_in']);
        $validated['enable_ecommerce'] = !empty($input['enable_ecommerce']);
        
        // Validate custom endpoints
        if (!empty($input['custom_endpoints'])) {
            $endpoints = explode("\n", $input['custom_endpoints']);
            $validated['custom_endpoints'] = array_map('trim', $endpoints);
            $validated['custom_endpoints'] = array_filter($validated['custom_endpoints']);
        } else {
            $validated['custom_endpoints'] = ['/data.css', '/pixel.png', '/i.php'];
        }
        
        return $validated;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_horizn-settings') {
            return;
        }
        
        wp_enqueue_style(
            'horizn-admin',
            HORIZN_PLUGIN_URL . 'assets/admin.css',
            [],
            HORIZN_VERSION
        );
        
        wp_enqueue_script(
            'horizn-admin',
            HORIZN_PLUGIN_URL . 'assets/admin.js',
            ['jquery'],
            HORIZN_VERSION,
            true
        );
        
        wp_localize_script('horizn-admin', 'horizn_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('horizn_admin_nonce'),
            'strings' => [
                'test_success' => __('Connection test successful!', 'horizn-analytics'),
                'test_failed' => __('Connection test failed. Please check your settings.', 'horizn-analytics'),
                'generating' => __('Generating new site key...', 'horizn-analytics'),
                'generated' => __('New site key generated successfully!', 'horizn-analytics')
            ]
        ]);
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (empty($this->settings['site_key'])) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>';
            echo sprintf(
                __('horizn_ Analytics is not configured yet. <a href="%s">Configure it now</a> to start tracking your website analytics.', 'horizn-analytics'),
                admin_url('options-general.php?page=horizn-settings')
            );
            echo '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=horizn-settings') . '">' . __('Settings', 'horizn-analytics') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'horizn-analytics'));
        }
        ?>
        <div class="wrap horizn-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="horizn-admin-header">
                <div class="horizn-logo">
                    <h2>horizn_</h2>
                    <p><?php _e('First-party, ad-blocker resistant analytics', 'horizn-analytics'); ?></p>
                </div>
                <div class="horizn-status">
                    <?php if (!empty($this->settings['site_key'])): ?>
                        <span class="status-indicator status-active"></span>
                        <span><?php _e('Active', 'horizn-analytics'); ?></span>
                    <?php else: ?>
                        <span class="status-indicator status-inactive"></span>
                        <span><?php _e('Not Configured', 'horizn-analytics'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="horizn-admin-content">
                <div class="horizn-main-content">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('horizn_settings_group');
                        do_settings_sections('horizn-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="horizn-sidebar">
                    <div class="horizn-card">
                        <h3><?php _e('Quick Actions', 'horizn-analytics'); ?></h3>
                        <p>
                            <button type="button" class="button" id="horizn-test-connection">
                                <?php _e('Test Connection', 'horizn-analytics'); ?>
                            </button>
                        </p>
                        <p>
                            <button type="button" class="button" id="horizn-generate-key">
                                <?php _e('Generate New Site Key', 'horizn-analytics'); ?>
                            </button>
                        </p>
                        <div id="horizn-test-results"></div>
                    </div>
                    
                    <div class="horizn-card">
                        <h3><?php _e('Tracking Status', 'horizn-analytics'); ?></h3>
                        <?php if (!empty($this->settings['tracking_enabled'])): ?>
                            <p class="status-good">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Tracking is enabled', 'horizn-analytics'); ?>
                            </p>
                        <?php else: ?>
                            <p class="status-warning">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Tracking is disabled', 'horizn-analytics'); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (class_exists('WooCommerce') && !empty($this->settings['enable_ecommerce'])): ?>
                            <p class="status-good">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('WooCommerce tracking enabled', 'horizn-analytics'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="horizn-card">
                        <h3><?php _e('Support', 'horizn-analytics'); ?></h3>
                        <p><?php _e('Need help setting up horizn_ Analytics?', 'horizn-analytics'); ?></p>
                        <p>
                            <a href="https://github.com/tonyshawjr/horizn_" target="_blank" class="button button-secondary">
                                <?php _e('Documentation', 'horizn-analytics'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure your basic horizn_ Analytics settings.', 'horizn-analytics') . '</p>';
    }
    
    public function privacy_section_callback() {
        echo '<p>' . __('Control what data is tracked and who is tracked.', 'horizn-analytics') . '</p>';
    }
    
    public function ecommerce_section_callback() {
        echo '<p>' . __('Enable tracking for e-commerce events.', 'horizn-analytics') . '</p>';
        if (!class_exists('WooCommerce')) {
            echo '<p class="description">' . __('WooCommerce is not installed. Install WooCommerce to enable e-commerce tracking.', 'horizn-analytics') . '</p>';
        }
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced configuration options for power users.', 'horizn-analytics') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function api_endpoint_field() {
        $value = $this->settings['api_endpoint'] ?? home_url();
        echo '<input type="url" name="horizn_settings[api_endpoint]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('The base URL for your horizn_ analytics endpoint. Usually your website URL.', 'horizn-analytics') . '</p>';
    }
    
    public function site_key_field() {
        $value = $this->settings['site_key'] ?? '';
        echo '<input type="text" name="horizn_settings[site_key]" value="' . esc_attr($value) . '" class="regular-text" id="horizn-site-key" />';
        echo '<p class="description">' . __('Unique identifier for your website. Generate one if you don\'t have it.', 'horizn-analytics') . '</p>';
    }
    
    public function tracking_enabled_field() {
        $checked = !empty($this->settings['tracking_enabled']) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="horizn_settings[tracking_enabled]" value="1" ' . $checked . ' />';
        echo ' ' . __('Enable analytics tracking', 'horizn-analytics');
        echo '</label>';
    }
    
    public function track_admin_field() {
        $checked = !empty($this->settings['track_admin']) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="horizn_settings[track_admin]" value="1" ' . $checked . ' />';
        echo ' ' . __('Track admin users', 'horizn-analytics');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, admin users will be tracked in analytics.', 'horizn-analytics') . '</p>';
    }
    
    public function track_logged_in_field() {
        $checked = !empty($this->settings['track_logged_in']) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="horizn_settings[track_logged_in]" value="1" ' . $checked . ' />';
        echo ' ' . __('Track logged-in users', 'horizn-analytics');
        echo '</label>';
        echo '<p class="description">' . __('When enabled, logged-in users will be identified in analytics.', 'horizn-analytics') . '</p>';
    }
    
    public function enable_ecommerce_field() {
        $checked = !empty($this->settings['enable_ecommerce']) ? 'checked' : '';
        echo '<label>';
        echo '<input type="checkbox" name="horizn_settings[enable_ecommerce]" value="1" ' . $checked . ' />';
        echo ' ' . __('Track e-commerce events', 'horizn-analytics');
        echo '</label>';
        echo '<p class="description">' . __('Track purchases, add to cart, and other e-commerce events.', 'horizn-analytics') . '</p>';
    }
    
    public function custom_endpoints_field() {
        $value = is_array($this->settings['custom_endpoints']) ? implode("\n", $this->settings['custom_endpoints']) : '';
        echo '<textarea name="horizn_settings[custom_endpoints]" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . __('One endpoint per line. These disguised endpoints help avoid ad blockers.', 'horizn-analytics') . '</p>';
    }
    
    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('horizn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $api_endpoint = sanitize_url($_POST['api_endpoint'] ?? '');
        $site_key = sanitize_text_field($_POST['site_key'] ?? '');
        
        if (empty($api_endpoint) || empty($site_key)) {
            wp_send_json_error(['message' => __('Missing API endpoint or site key.', 'horizn-analytics')]);
        }
        
        // Test the connection
        $test_data = [
            'type' => 'test',
            'site_id' => $site_key,
            'timestamp' => time()
        ];
        
        $response = wp_remote_post($api_endpoint . '/data.css', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($test_data),
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            wp_send_json_success(['message' => __('Connection test successful!', 'horizn-analytics')]);
        } else {
            wp_send_json_error(['message' => sprintf(__('Connection failed with status code: %d', 'horizn-analytics'), $code)]);
        }
    }
    
    /**
     * AJAX: Generate new site key
     */
    public function ajax_generate_site_key() {
        check_ajax_referer('horizn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $new_key = 'hzn_' . wp_generate_uuid4();
        
        wp_send_json_success(['site_key' => $new_key]);
    }
}
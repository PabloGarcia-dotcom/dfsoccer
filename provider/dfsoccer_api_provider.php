<?php
/**
 * DFSoccer API Provider
 * 
 * A comprehensive API management solution for DFSoccer with key management,
 * usage tracking, and rate limiting.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DFSoccer_API_Provider {
    // Database tables
    private static $keys_table;
    private static $usage_table;
    
    // Initialize the provider
    public static function init() {
        global $wpdb;
        self::$keys_table = $wpdb->prefix . 'dfsoccer_api_keys';
        self::$usage_table = $wpdb->prefix . 'dfsoccer_api_usage';
        
        // Register REST API endpoints
        add_action('rest_api_init', array(__CLASS__, 'register_api_endpoints'));
        
        // Admin interface
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_admin_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    // Plugin activation setup (called from main plugin file)
    public static function activate() {
        self::create_database_tables();
        self::maybe_add_default_key();
    }
    
    // Create required database tables
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // API Keys table
        $sql = "CREATE TABLE IF NOT EXISTS " . self::$keys_table . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            api_key varchar(64) NOT NULL,
            description varchar(255) NOT NULL,
            daily_limit int(11) NOT NULL DEFAULT 100,
            created_at datetime NOT NULL,
            last_used datetime DEFAULT NULL,
            consumer_url varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY api_key (api_key)
        ) $charset_collate;";
        
        // API Usage table
        $sql .= "CREATE TABLE IF NOT EXISTS " . self::$usage_table . " (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            api_key_id mediumint(9) NOT NULL,
            date date NOT NULL,
            ip_address varchar(45) NOT NULL,
            count int(11) NOT NULL DEFAULT 0,
            last_request_time datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY api_key_date_ip (api_key_id, date, ip_address),
            KEY api_key_id (api_key_id),
            KEY date (date),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Add default API key if none exists
    private static function maybe_add_default_key() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM " . self::$keys_table);
        
        if ($count == 0) {
            $api_key = bin2hex(random_bytes(16));
            $wpdb->insert(
                self::$keys_table,
                array(
                    'api_key' => $api_key,
                    'description' => 'Default API Key',
                    'daily_limit' => 100,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
    }
    
    // Register REST API endpoints
    public static function register_api_endpoints() {
        // Main data endpoint
        register_rest_route('dfsoccer/v1', '/data', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'handle_data_request'),
            'permission_callback' => array(__CLASS__, 'check_api_key')
        ));
        
        // Additional endpoints can be added here
    }
    
    // API key validation and rate limiting
    public static function check_api_key($request) {
        global $wpdb;
        
        $provided_key = $request->get_header('X-API-KEY');
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $current_date = current_time('Y-m-d');
        
        if (empty($provided_key)) {
            return new WP_Error(
                'rest_forbidden',
                'API key is required',
                array('status' => 401)
            );
        }
        
        // Check if key exists and is active
        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$keys_table . " WHERE api_key = %s AND status = 'active'",
            $provided_key
        ));
        
        if (!$key_data) {
            return new WP_Error(
                'rest_forbidden',
                'Invalid API key',
                array('status' => 403)
            );
        }
        
        // Check usage
        $usage = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$usage_table . " 
            WHERE api_key_id = %d AND date = %s AND ip_address = %s",
            $key_data->id, $current_date, $ip_address
        ));
        
        // Initialize usage record if none exists
        if (!$usage) {
            $wpdb->insert(
                self::$usage_table,
                array(
                    'api_key_id' => $key_data->id,
                    'date' => $current_date,
                    'ip_address' => $ip_address,
                    'count' => 0,
                    'last_request_time' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
            $usage_count = 0;
        } else {
            $usage_count = $usage->count;
        }
        
        // Check rate limit
        if ($usage_count >= $key_data->daily_limit) {
            return new WP_Error(
                'rest_forbidden',
                'Daily API limit exceeded',
                array('status' => 429)
            );
        }
        
        // Update usage
        $wpdb->update(
            self::$usage_table,
            array(
                'count' => $usage_count + 1,
                'last_request_time' => current_time('mysql')
            ),
            array(
                'api_key_id' => $key_data->id,
                'date' => $current_date,
                'ip_address' => $ip_address
            ),
            array('%d', '%s'),
            array('%d', '%s', '%s')
        );
        
        // Update last used timestamp
        $wpdb->update(
            self::$keys_table,
            array('last_used' => current_time('mysql')),
            array('id' => $key_data->id),
            array('%s'),
            array('%d')
        );
        
        return true;
    }
    
    // Handle data requests
    public static function handle_data_request($request) {
        // Your data processing logic here
        $response_data = array(
            'status' => 'success',
            'data' => array(
                // Sample data - replace with actual DFSoccer data
                'matches' => array(),
                'stats' => array(),
                'predictions' => array()
            ),
            'timestamp' => current_time('mysql')
        );
        
        return rest_ensure_response($response_data);
    }
    
    // Admin interface
    public static function add_admin_menu() {
        add_menu_page(
            'DFSoccer API',
            'DFSoccer API',
            'manage_options',
            'dfsoccer-api',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-rest-api',
            80
        );
    }
    
    public static function register_admin_settings() {
        register_setting('dfsoccer_api_settings', 'dfsoccer_api_options');
    }
    
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_dfsoccer-api') {
            return;
        }
        
        wp_enqueue_style(
            'dfsoccer-api-admin',
            plugins_url('css/admin.css', __FILE__)
        );
        
        wp_enqueue_script(
            'dfsoccer-api-admin',
            plugins_url('js/admin.js', __FILE__),
            array('jquery'),
            '1.0',
            true
        );
    }
    
    public static function render_admin_page() {
        global $wpdb;
        
        // Handle form submissions
        if (isset($_POST['action'])) {
            self::handle_admin_actions();
        }
        
        // Get all API keys
        $keys = $wpdb->get_results("SELECT * FROM " . self::$keys_table . " ORDER BY created_at DESC");
        
        // Get usage statistics
        $usage_stats = $wpdb->get_results(
            "SELECT k.id, k.description, k.api_key, k.daily_limit, 
                    SUM(u.count) as total_usage, MAX(u.last_request_time) as last_used
             FROM " . self::$keys_table . " k
             LEFT JOIN " . self::$usage_table . " u ON k.id = u.api_key_id
             GROUP BY k.id"
        );
        
        ?>
        <div class="wrap dfsoccer-api-admin">
            <h1><span class="dashicons dashicons-rest-api"></span> DFSoccer API Management</h1>
            
            <div class="dfsoccer-api-container">
                <div class="dfsoccer-api-col">
                    <div class="card">
                        <h2>Add New API Key</h2>
                        <form method="post">
                            <input type="hidden" name="action" value="add_key">
                            <?php wp_nonce_field('dfsoccer_api_actions'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th><label for="description">Description</label></th>
                                    <td>
                                        <input type="text" name="description" id="description" class="regular-text" required>
                                        <p class="description">Identify the consumer of this API key</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="daily_limit">Daily Limit</label></th>
                                    <td>
                                        <input type="number" name="daily_limit" id="daily_limit" value="100" min="1">
                                        <p class="description">Maximum requests per day</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="consumer_url">Consumer URL</label></th>
                                    <td>
                                        <input type="url" name="consumer_url" id="consumer_url" class="regular-text">
                                        <p class="description">Optional website URL of the consumer</p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button('Generate API Key'); ?>
                        </form>
                    </div>
                </div>
                
                <div class="dfsoccer-api-col">
                    <div class="card">
                        <h2>API Keys</h2>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>API Key</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($keys as $key): ?>
                                <tr>
                                    <td><?php echo esc_html($key->description); ?></td>
                                    <td>
                                        <code class="api-key"><?php echo esc_html($key->api_key); ?></code>
                                        <button class="copy-api-key button button-small" data-key="<?php echo esc_attr($key->api_key); ?>">
                                            Copy
                                        </button>
                                    </td>
                                    <td>
                                        <?php 
                                        $usage = 0;
                                        foreach ($usage_stats as $stat) {
                                            if ($stat->id == $key->id) {
                                                $usage = $stat->total_usage ?: 0;
                                                break;
                                            }
                                        }
                                        echo esc_html($usage) . ' / ' . esc_html($key->daily_limit);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($key->status); ?>">
                                            <?php echo esc_html(ucfirst($key->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_key">
                                            <input type="hidden" name="key_id" value="<?php echo esc_attr($key->id); ?>">
                                            <?php wp_nonce_field('dfsoccer_api_actions'); ?>
                                            <button type="submit" class="button button-small">
                                                <?php echo $key->status == 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_key">
                                            <input type="hidden" name="key_id" value="<?php echo esc_attr($key->id); ?>">
                                            <?php wp_nonce_field('dfsoccer_api_actions'); ?>
                                            <button type="submit" class="button button-small button-link-delete">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>API Documentation</h2>
                <p>Endpoint URL: <code><?php echo esc_url(rest_url('dfsoccer/v1/data')); ?></code></p>
                <p>Required header: <code>X-API-KEY: your_api_key</code></p>
                
                <h3>Example Request</h3>
                <pre><code>curl -X GET \
  -H "X-API-KEY: your_api_key_here" \
  "<?php echo esc_url(rest_url('dfsoccer/v1/data')); ?>"</code></pre>
            </div>
        </div>
        <?php
    }
    
    // Handle admin actions
    private static function handle_admin_actions() {
        global $wpdb;
        
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'dfsoccer_api_actions')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions');
        }
        
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_key':
                $api_key = bin2hex(random_bytes(16));
                $wpdb->insert(
                    self::$keys_table,
                    array(
                        'api_key' => $api_key,
                        'description' => sanitize_text_field($_POST['description']),
                        'daily_limit' => absint($_POST['daily_limit']),
                        'consumer_url' => esc_url_raw($_POST['consumer_url']),
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s', '%d', '%s', '%s')
                );
                
                add_settings_error(
                    'dfsoccer_api_messages',
                    'dfsoccer_api_message',
                    'API key generated successfully',
                    'success'
                );
                break;
                
            case 'toggle_key':
                $key_id = absint($_POST['key_id']);
                $current_status = $wpdb->get_var($wpdb->prepare(
                    "SELECT status FROM " . self::$keys_table . " WHERE id = %d",
                    $key_id
                ));
                
                $new_status = ($current_status == 'active') ? 'inactive' : 'active';
                
                $wpdb->update(
                    self::$keys_table,
                    array('status' => $new_status),
                    array('id' => $key_id),
                    array('%s'),
                    array('%d')
                );
                break;
                
            case 'delete_key':
                $key_id = absint($_POST['key_id']);
                $wpdb->delete(
                    self::$keys_table,
                    array('id' => $key_id),
                    array('%d')
                );
                break;
        }
    }
    
    // Helper methods
    public static function get_keys_table() {
        global $wpdb;
        return $wpdb->prefix . 'dfsoccer_api_keys';
    }
    
    public static function get_usage_table() {
        global $wpdb;
        return $wpdb->prefix . 'dfsoccer_api_usage';
    }
}

// Initialize the plugin
DFSoccer_API_Provider::init();
<?php

/**
 * Flipbook Management Class (FIXED - Auto Accounts Support)
 * File: includes/class-pz-flipbooks.php
 */

if (!defined('ABSPATH')) exit;

class PZ_Flipbooks
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // AJAX handlers for admin
        add_action('wp_ajax_pz_save_flipbook', array($this, 'save_flipbook'));
        add_action('wp_ajax_pz_delete_flipbook', array($this, 'delete_flipbook'));

        // AJAX handler for secure flipbook loading (for logged-in users)
        add_action('wp_ajax_pz_load_flipbook', array($this, 'load_flipbook_content'));

        // WooCommerce My Account tab
        add_filter('woocommerce_account_menu_items', array($this, 'add_flipbooks_tab'), 40);
        add_action('init', array($this, 'add_flipbooks_endpoint'));
        add_action('woocommerce_account_flipbooks_endpoint', array($this, 'flipbooks_tab_content'));

        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Create flipbooks table
     */
    public static function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pz_flipbooks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            flipbook_url text NOT NULL,
            access_type varchar(20) DEFAULT 'school',
            status varchar(20) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Flipbooks',
            'Flipbooks',
            'manage_options',
            'pz-flipbooks',
            array($this, 'render_admin_page'),
            'dashicons-book',
            25
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        $flipbooks = $this->get_all_flipbooks();
?>
        <div class="wrap">
            <h1>HTML5 Flipbooks Management</h1>

            <div class="pz-flipbooks-admin">
                <div class="pz-add-flipbook-section" style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2>Add New Flipbook</h2>
                    <form id="pz-add-flipbook-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="flipbook_title">Title *</label></th>
                                <td>
                                    <input type="text" id="flipbook_title" name="title" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="flipbook_description">Description</label></th>
                                <td>
                                    <textarea id="flipbook_description" name="description" rows="3" class="large-text"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="flipbook_url">Flipbook URL/Embed Code *</label></th>
                                <td>
                                    <textarea id="flipbook_url" name="flipbook_url" rows="5" class="large-text" required placeholder="Paste your HTML5 flipbook URL or full embed code here"></textarea>
                                    <p class="description">You can paste either a URL (starts with http:// or https://) or the complete HTML embed code from your flipbook service (e.g., FlipHTML5, Issuu, etc.)</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="access_type">Access Type</label></th>
                                <td>
                                    <select id="access_type" name="access_type">
                                        <option value="school">School License Only</option>
                                        <option value="all">All Licensed Users (School + Student)</option>
                                    </select>
                                    <p class="description">
                                        <strong>School License Only:</strong> Only users who purchased School License can see this.<br>
                                        <strong>All Licensed Users:</strong> Both School License and Student Package buyers can see this.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sort_order">Sort Order</label></th>
                                <td>
                                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                                    <p class="description">Lower numbers appear first</p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary button-large">Add Flipbook</button>
                        </p>
                    </form>
                </div>

                <div class="pz-flipbooks-list" style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h2>Existing Flipbooks</h2>

                    <?php if (empty($flipbooks)): ?>
                        <p style="color: #666; font-style: italic;">No flipbooks added yet.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Access Type</th>
                                    <th style="width: 100px;">Sort Order</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flipbooks as $flipbook): ?>
                                    <tr>
                                        <td><?php echo esc_html($flipbook->id); ?></td>
                                        <td><strong><?php echo esc_html($flipbook->title); ?></strong></td>
                                        <td><?php echo esc_html(substr($flipbook->description, 0, 100)); ?></td>
                                        <td>
                                            <span class="dashicons dashicons-<?php echo $flipbook->access_type === 'school' ? 'building' : 'groups'; ?>"></span>
                                            <?php echo $flipbook->access_type === 'school' ? 'School Only' : 'All Users'; ?>
                                        </td>
                                        <td><?php echo esc_html($flipbook->sort_order); ?></td>
                                        <td>
                                            <?php if ($flipbook->status === 'active'): ?>
                                                <span style="color: green;">● Active</span>
                                            <?php else: ?>
                                                <span style="color: #999;">○ Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="button button-small pz-delete-flipbook" data-id="<?php echo esc_attr($flipbook->id); ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_pz-flipbooks') {
            return;
        }

        wp_enqueue_script('pz-flipbooks-admin', PZ_LICENSE_URL . 'assets/js/flipbooks-admin.js', array('jquery'), PZ_LICENSE_VERSION, true);
        wp_localize_script('pz-flipbooks-admin', 'pzFlipbooks', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pz_flipbooks_nonce')
        ));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        if (is_account_page()) {
            wp_enqueue_style('pz-flipbooks-frontend', PZ_LICENSE_URL . 'assets/css/flipbooks.css', array(), PZ_LICENSE_VERSION);
            wp_enqueue_script('pz-flipbooks-frontend', PZ_LICENSE_URL . 'assets/js/flipbooks-frontend.js', array('jquery'), PZ_LICENSE_VERSION, true);
            wp_localize_script('pz-flipbooks-frontend', 'pzFlipbooks', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pz_flipbooks_view_nonce')
            ));
        }
    }

    /**
     * Save flipbook
     */
    public function save_flipbook()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pz_flipbooks_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }

        // Validate required fields
        if (empty($_POST['title'])) {
            wp_send_json_error(array('message' => 'Title is required'));
            return;
        }

        if (empty($_POST['flipbook_url'])) {
            wp_send_json_error(array('message' => 'Flipbook URL/Embed Code is required'));
            return;
        }

        global $wpdb;

        // Check if table exists
        $table_name = $wpdb->prefix . 'pz_flipbooks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        if ($table_exists != $table_name) {
            $this->create_table_if_not_exists();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            if ($table_exists != $table_name) {
                wp_send_json_error(array(
                    'message' => 'Database table does not exist. Please deactivate and reactivate the plugin.'
                ));
                return;
            }
        }

        // Sanitize and prepare data
        $title = sanitize_text_field($_POST['title']);
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $flipbook_url = wp_kses_post(stripslashes($_POST['flipbook_url']));
        $access_type = isset($_POST['access_type']) ? sanitize_text_field($_POST['access_type']) : 'school';
        $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;

        if (!in_array($access_type, array('school', 'all'))) {
            $access_type = 'school';
        }

        error_log('PZ Flipbooks: Attempting to insert - Title: ' . $title);

        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'description' => $description,
                'flipbook_url' => $flipbook_url,
                'access_type' => $access_type,
                'sort_order' => $sort_order,
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result === false) {
            $error_message = $wpdb->last_error;
            error_log('PZ Flipbooks: Database insert failed - ' . $error_message);
            wp_send_json_error(array(
                'message' => 'Database error: ' . ($error_message ? $error_message : 'Unknown error')
            ));
            return;
        }

        $insert_id = $wpdb->insert_id;
        error_log('PZ Flipbooks: Successfully inserted ID: ' . $insert_id);

        wp_send_json_success(array(
            'message' => 'Flipbook added successfully',
            'id' => $insert_id
        ));
    }

    /**
     * Create table if it doesn't exist
     */
    private function create_table_if_not_exists()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pz_flipbooks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        flipbook_url longtext NOT NULL,
        access_type varchar(20) DEFAULT 'school',
        status varchar(20) DEFAULT 'active',
        sort_order int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        error_log('PZ Flipbooks: Attempted to create table');
    }

    /**
     * Delete flipbook
     */
    public function delete_flipbook()
    {
        check_ajax_referer('pz_flipbooks_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $id = intval($_POST['id']);

        $result = $wpdb->delete(
            $wpdb->prefix . 'pz_flipbooks',
            array('id' => $id),
            array('%d')
        );

        if ($result) {
            wp_send_json_success(array('message' => 'Flipbook deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete flipbook'));
        }
    }

    /**
     * Get all flipbooks
     */
    public function get_all_flipbooks()
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
            WHERE status = 'active' 
            ORDER BY sort_order ASC, id DESC"
        );
    }

    /**
     * FIXED: Check if user has valid license access
     * Supports both regular purchases and auto-created accounts
     */
    private function user_has_valid_license($user_id)
    {
        if (!$user_id) {
            return false;
        }

        // Check if this is an auto-created account
        $is_auto_account = get_user_meta($user_id, 'pz_auto_account', true);

        if ($is_auto_account) {
            // Check if license is still valid
            $expiry = get_user_meta($user_id, 'pz_license_expiry', true);
            if ($expiry && strtotime($expiry) > time()) {
                error_log('PZ Flipbooks: Auto account ' . $user_id . ' has valid license until ' . $expiry);
                return true;
            }
            error_log('PZ Flipbooks: Auto account ' . $user_id . ' license expired');
            return false;
        }

        // For regular accounts, check WooCommerce orders
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $product_id = $product->get_id();
                    if ($product_id == $school_product_id || $product_id == $student_product_id) {
                        error_log('PZ Flipbooks: Regular user ' . $user_id . ' has valid purchase');
                        return true;
                    }
                }
            }
        }

        error_log('PZ Flipbooks: User ' . $user_id . ' has no valid license');
        return false;
    }

    /**
     * FIXED: Check if user is school license holder (including auto-accounts)
     */
    private function user_has_school_access($user_id)
    {
        if (!$user_id) {
            return false;
        }

        // Check if auto-created teacher or student account
        $is_auto_account = get_user_meta($user_id, 'pz_auto_account', true);
        if ($is_auto_account) {
            // Auto accounts always have school-level access if license is valid
            $expiry = get_user_meta($user_id, 'pz_license_expiry', true);
            if ($expiry && strtotime($expiry) > time()) {
                error_log('PZ Flipbooks: Auto account ' . $user_id . ' has school access');
                return true;
            }
            return false;
        }

        // Check if user purchased school product
        $school_product_id = get_option('pz_school_product_id');
        
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && $product->get_id() == $school_product_id) {
                    error_log('PZ Flipbooks: User ' . $user_id . ' purchased school license');
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * FIXED: Get flipbooks for user based on license type
     */
    public function get_flipbooks_for_user($user_id)
    {
        if (!$user_id) {
            error_log('PZ Flipbooks: No user ID provided');
            return array();
        }

        global $wpdb;

        // Check table exists
        $table_name = $wpdb->prefix . 'pz_flipbooks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        if ($table_exists != $table_name) {
            error_log('PZ Flipbooks: Table does not exist');
            return array();
        }

        // Check if user has school-level access (purchased school or is auto-account)
        $has_school_access = $this->user_has_school_access($user_id);

        if ($has_school_access) {
            // School license holders and auto-accounts get ALL flipbooks
            $flipbooks = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
                WHERE status = 'active' 
                ORDER BY sort_order ASC, id DESC"
            );
            error_log('PZ Flipbooks: User ' . $user_id . ' with school access - Found ' . count($flipbooks) . ' flipbooks');
            return $flipbooks;
        }

        // Check if user has valid student license
        if ($this->user_has_valid_license($user_id)) {
            // Student license holders only get 'all' access flipbooks
            $flipbooks = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
                WHERE status = 'active' AND access_type = 'all'
                ORDER BY sort_order ASC, id DESC"
            );
            error_log('PZ Flipbooks: Student user ' . $user_id . ' - Found ' . count($flipbooks) . ' flipbooks');
            return $flipbooks;
        }

        error_log('PZ Flipbooks: User ' . $user_id . ' has no valid license');
        return array();
    }

    /**
     * Load flipbook content (AJAX - protected)
     */
    public function load_flipbook_content()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pz_flipbooks_view_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please log in to view flipbooks'));
            return;
        }

        if (!isset($_POST['flipbook_id']) || empty($_POST['flipbook_id'])) {
            wp_send_json_error(array('message' => 'Invalid flipbook ID'));
            return;
        }

        $flipbook_id = intval($_POST['flipbook_id']);
        $user_id = get_current_user_id();

        if (!$this->user_can_access_flipbook($user_id, $flipbook_id)) {
            wp_send_json_error(array('message' => 'You do not have access to this flipbook'));
            return;
        }

        global $wpdb;
        $flipbook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_flipbooks WHERE id = %d AND status = 'active'",
            $flipbook_id
        ));

        if (!$flipbook) {
            wp_send_json_error(array('message' => 'Flipbook not found'));
            return;
        }

        wp_send_json_success(array(
            'content' => $flipbook->flipbook_url,
            'title' => $flipbook->title
        ));
    }

    /**
     * FIXED: Check if user can access specific flipbook
     */
    private function user_can_access_flipbook($user_id, $flipbook_id)
    {
        global $wpdb;

        $flipbook = $wpdb->get_row($wpdb->prepare(
            "SELECT access_type FROM {$wpdb->prefix}pz_flipbooks WHERE id = %d",
            $flipbook_id
        ));

        if (!$flipbook) {
            return false;
        }

        // Check if user has school-level access
        $has_school_access = $this->user_has_school_access($user_id);

        if ($has_school_access) {
            return true; // School license holders can access everything
        }

        // Check if user has any valid license and flipbook allows 'all'
        if ($flipbook->access_type === 'all' && $this->user_has_valid_license($user_id)) {
            return true;
        }

        return false;
    }

    /**
     * Add WooCommerce My Account endpoint
     */
    public function add_flipbooks_endpoint()
    {
        add_rewrite_endpoint('flipbooks', EP_ROOT | EP_PAGES);
    }

    /**
     * Add flipbooks tab to My Account menu (based on license)
     */
    public function add_flipbooks_tab($items)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $items;
        }

        // Show tab if user has any valid license
        if ($this->user_has_valid_license($user_id)) {
            if (isset($items['customer-logout'])) {
                $logout = $items['customer-logout'];
                unset($items['customer-logout']);
                $items['flipbooks'] = 'Study Materials';
                $items['customer-logout'] = $logout;
            } else {
                $items['flipbooks'] = 'Study Materials';
            }
        }

        return $items;
    }

    /**
     * Flipbooks tab content
     */
    public function flipbooks_tab_content()
    {
        $user_id = get_current_user_id();

        // Debug info for admins
        if (current_user_can('manage_options')) {
            $is_auto = get_user_meta($user_id, 'pz_auto_account', true);
            $expiry = get_user_meta($user_id, 'pz_license_expiry', true);
            
            echo '<!-- DEBUG INFO:';
            echo "\nUser ID: " . $user_id;
            echo "\nIs Auto Account: " . ($is_auto ? 'YES' : 'NO');
            echo "\nLicense Expiry: " . ($expiry ? $expiry : 'N/A');
            echo "\nHas Valid License: " . ($this->user_has_valid_license($user_id) ? 'YES' : 'NO');
            echo "\nHas School Access: " . ($this->user_has_school_access($user_id) ? 'YES' : 'NO');
            echo "\n-->";
        }

        $flipbooks = $this->get_flipbooks_for_user($user_id);

        if (current_user_can('manage_options')) {
            echo '<!-- Flipbooks found: ' . count($flipbooks) . ' -->';
        }

        include PZ_LICENSE_PATH . 'templates/flipbooks-tab.php';
    }
}

// Initialize
function pz_flipbooks()
{
    return PZ_Flipbooks::get_instance();
}
add_action('plugins_loaded', 'pz_flipbooks');
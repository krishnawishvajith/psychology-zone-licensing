<?php

/**
 * Flipbook Management Class (ENHANCED - Shows School Credentials in My Account)
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

        // WooCommerce My Account tabs
        add_filter('woocommerce_account_menu_items', array($this, 'add_flipbooks_tab'), 40);
        add_action('init', array($this, 'add_flipbooks_endpoint'));
        add_action('woocommerce_account_flipbooks_endpoint', array($this, 'flipbooks_tab_content'));

        // NEW: Add School Dashboard tab for school license holders
        add_filter('woocommerce_account_menu_items', array($this, 'add_school_dashboard_tab'), 41);
        add_action('init', array($this, 'add_school_dashboard_endpoint'));
        add_action('woocommerce_account_school-dashboard_endpoint', array($this, 'school_dashboard_content'));

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
            
            // Add inline styles for school dashboard
            wp_add_inline_style('pz-flipbooks-frontend', $this->get_school_dashboard_styles());
        }
    }

    /**
     * Get school dashboard styles
     */
    private function get_school_dashboard_styles()
    {
        return "
        .pz-school-credentials {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .pz-credential-card {
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4A90E2;
        }
        .pz-credential-card h3 {
            margin-top: 0;
            color: #333;
        }
        .pz-credential-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .pz-credential-label {
            font-weight: 600;
            color: #333;
            min-width: 100px;
        }
        .pz-credential-value {
            flex: 1;
            margin: 0 15px;
        }
        .pz-credential-value code {
            background: #f5f5f5;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            color: #E94B3C;
            display: block;
        }
        .pz-copy-btn {
            background: #4A90E2;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        .pz-copy-btn:hover {
            background: #357ABD;
        }
        .pz-info-notice {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .pz-info-notice p {
            margin: 5px 0;
            font-size: 14px;
            color: #0c5460;
        }
        .pz-license-info {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .pz-license-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .pz-license-info-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
        }
        .pz-license-info-item strong {
            display: block;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .pz-license-info-item span {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }
        ";
    }

    /**
     * Save flipbook
     */
    public function save_flipbook()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pz_flipbooks_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized - Admin access required'));
            return;
        }

        if (empty($_POST['title'])) {
            wp_send_json_error(array('message' => 'Title is required'));
            return;
        }

        if (empty($_POST['flipbook_url'])) {
            wp_send_json_error(array('message' => 'Flipbook URL/Embed Code is required'));
            return;
        }

        global $wpdb;

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

    public function get_all_flipbooks()
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
            WHERE status = 'active' 
            ORDER BY sort_order ASC, id DESC"
        );
    }

    private function user_has_school_license($user_id)
    {
        if (!$user_id) {
            return false;
        }

        global $wpdb;

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_licenses 
            WHERE user_id = %d 
            AND status = 'active' 
            AND end_date > NOW()
            LIMIT 1",
            $user_id
        ));

        return $license ? true : false;
    }

    private function user_has_purchased_product($user_id, $product_id)
    {
        if (!$user_id || !$product_id) {
            return false;
        }

        if ($this->user_has_school_license($user_id)) {
            error_log('PZ Flipbooks: User ' . $user_id . ' is school administrator');
            return true;
        }

        $is_auto_account = get_user_meta($user_id, 'pz_auto_account', true);

        if ($is_auto_account) {
            $expiry = get_user_meta($user_id, 'pz_license_expiry', true);
            if ($expiry && strtotime($expiry) > time()) {
                return true;
            }
            return false;
        }

        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1,
        ));

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && $product->get_id() == $product_id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_flipbooks_for_user($user_id)
    {
        if (!$user_id) {
            error_log('PZ Flipbooks: No user ID provided');
            return array();
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'pz_flipbooks';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        if ($table_exists != $table_name) {
            error_log('PZ Flipbooks: Table does not exist');
            return array();
        }

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        error_log('PZ Flipbooks: School Product ID: ' . $school_product_id);
        error_log('PZ Flipbooks: Student Product ID: ' . $student_product_id);

        $has_school = $this->user_has_school_license($user_id) || $this->user_has_purchased_product($user_id, $school_product_id);
        $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

        error_log('PZ Flipbooks: User ' . $user_id . ' - School: ' . ($has_school ? 'YES' : 'NO') . ', Student: ' . ($has_student ? 'YES' : 'NO'));

        if ($has_school) {
            $flipbooks = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
                WHERE status = 'active' 
                ORDER BY sort_order ASC, id DESC"
            );
            error_log('PZ Flipbooks: School user - Found ' . count($flipbooks) . ' flipbooks');
            return $flipbooks;
        }

        if ($has_student) {
            $flipbooks = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
                WHERE status = 'active' AND access_type = 'all'
                ORDER BY sort_order ASC, id DESC"
            );
            error_log('PZ Flipbooks: Student user - Found ' . count($flipbooks) . ' flipbooks');
            return $flipbooks;
        }

        error_log('PZ Flipbooks: User has no valid purchases');
        return array();
    }

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

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        $has_school = $this->user_has_school_license($user_id) || $this->user_has_purchased_product($user_id, $school_product_id);

        if ($has_school) {
            return true;
        }

        $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

        if ($has_student && $flipbook->access_type === 'all') {
            return true;
        }

        return false;
    }

    public function add_flipbooks_endpoint()
    {
        add_rewrite_endpoint('flipbooks', EP_ROOT | EP_PAGES);
    }

    // NEW: Add School Dashboard endpoint
    public function add_school_dashboard_endpoint()
    {
        add_rewrite_endpoint('school-dashboard', EP_ROOT | EP_PAGES);
    }

    public function add_flipbooks_tab($items)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $items;
        }

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        $has_school = $this->user_has_school_license($user_id) || $this->user_has_purchased_product($user_id, $school_product_id);
        $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

        error_log('PZ Flipbooks Tab: User ' . $user_id . ' - School: ' . ($has_school ? 'YES' : 'NO') . ', Student: ' . ($has_student ? 'YES' : 'NO'));

        if ($has_school || $has_student) {
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

    // NEW: Add School Dashboard tab (only for school license purchasers)
    public function add_school_dashboard_tab($items)
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return $items;
        }

        // Only show for actual school license purchasers (not auto-created accounts)
        if ($this->user_has_school_license($user_id)) {
            if (isset($items['customer-logout'])) {
                $logout = $items['customer-logout'];
                unset($items['customer-logout']);
                $items['school-dashboard'] = 'School Dashboard';
                $items['customer-logout'] = $logout;
            } else {
                $items['school-dashboard'] = 'School Dashboard';
            }
        }

        return $items;
    }

    public function flipbooks_tab_content()
    {
        $user_id = get_current_user_id();

        if (current_user_can('manage_options')) {
            $school_product_id = get_option('pz_school_product_id');
            $student_product_id = get_option('pz_student_product_id');
            $has_school_license = $this->user_has_school_license($user_id);
            $has_school = $this->user_has_purchased_product($user_id, $school_product_id);
            $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

            echo '<!-- DEBUG INFO:';
            echo "\nUser ID: " . $user_id;
            echo "\nSchool Product ID: " . $school_product_id;
            echo "\nStudent Product ID: " . $student_product_id;
            echo "\nIs School Administrator: " . ($has_school_license ? 'YES' : 'NO');
            echo "\nHas Purchased School: " . ($has_school ? 'YES' : 'NO');
            echo "\nHas Purchased Student: " . ($has_student ? 'YES' : 'NO');
            echo "\n-->";
        }

        $flipbooks = $this->get_flipbooks_for_user($user_id);

        if (current_user_can('manage_options')) {
            echo '<!-- Flipbooks found: ' . count($flipbooks) . ' -->';
        }

        include PZ_LICENSE_PATH . 'templates/flipbooks-tab.php';
    }

    // NEW: School Dashboard content
    public function school_dashboard_content()
    {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            echo '<p>Please log in to view this page.</p>';
            return;
        }

        global $wpdb;
        $school_license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_licenses 
            WHERE user_id = %d 
            AND status = 'active' 
            AND end_date > NOW()
            ORDER BY id DESC
            LIMIT 1",
            $user_id
        ));

        if (!$school_license) {
            echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 30px; border-radius: 8px; text-align: center;">';
            echo '<h3 style="color: #721c24; margin-top: 0;">No Active School License</h3>';
            echo '<p style="color: #721c24;">You don\'t have an active school license.</p>';
            echo '</div>';
            return;
        }

        $days_remaining = floor((strtotime($school_license->end_date) - time()) / (60 * 60 * 24));

        // Get credentials
        $teacher_username = '';
        $teacher_password = '';
        $student_username = '';
        $student_password = '';
        
        if ($school_license->teacher_user_id) {
            $teacher_user = get_user_by('id', $school_license->teacher_user_id);
            if ($teacher_user) {
                $teacher_username = $teacher_user->user_login;
                $teacher_password = get_user_meta($school_license->teacher_user_id, 'pz_original_password', true);
            }
        }
        
        if ($school_license->student_user_id) {
            $student_user = get_user_by('id', $school_license->student_user_id);
            if ($student_user) {
                $student_username = $student_user->user_login;
                $student_password = get_user_meta($school_license->student_user_id, 'pz_original_password', true);
            }
        }
        
        // Fallback to order meta
        if (empty($teacher_username) || empty($teacher_password) || empty($student_username) || empty($student_password)) {
            $order = wc_get_order($school_license->order_id);
            if ($order) {
                if (empty($teacher_username)) $teacher_username = $order->get_meta('_pz_teacher_username');
                if (empty($teacher_password)) $teacher_password = $order->get_meta('_pz_teacher_password');
                if (empty($student_username)) $student_username = $order->get_meta('_pz_student_username');
                if (empty($student_password)) $student_password = $order->get_meta('_pz_student_password');
            }
        }

        include PZ_LICENSE_PATH . 'templates/school-dashboard-tab.php';
    }
}

function pz_flipbooks()
{
    return PZ_Flipbooks::get_instance();
}
add_action('plugins_loaded', 'pz_flipbooks');
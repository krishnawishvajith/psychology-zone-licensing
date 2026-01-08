<?php

/**
 * Flipbook Management Class (Simplified - Order-Based Access Only)
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
                        <?php wp_nonce_field('pz_flipbooks_nonce', 'pz_flipbooks_nonce'); ?>

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
                                <th><label for="flipbook_url">HTML5 Flipbook URL *</label></th>
                                <td>
                                    <textarea id="flipbook_url" name="flipbook_url" rows="4" class="large-text" required placeholder="https://example.com/flipbook-embed-code"></textarea>
                                    <p class="description">Full URL or embed code for the HTML5 flipbook</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="access_type">Access Type</label></th>
                                <td>
                                    <select id="access_type" name="access_type">
                                        <option value="school">School License Only</option>
                                        <option value="all">All Users (School + Student)</option>
                                    </select>
                                    <p class="description">
                                        <strong>School License Only:</strong> Only accessible by school license purchasers<br>
                                        <strong>All Users:</strong> Accessible by both school and student package purchasers
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sort_order">Sort Order</label></th>
                                <td>
                                    <input type="number" id="sort_order" name="sort_order" value="0" min="0" style="width: 100px;">
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
                        <p style="color: #666;">No flipbooks added yet.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">Order</th>
                                    <th style="width: 30%;">Title</th>
                                    <th style="width: 35%;">Description</th>
                                    <th style="width: 15%;">Access Type</th>
                                    <th style="width: 15%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($flipbooks as $flipbook): ?>
                                    <tr>
                                        <td><?php echo esc_html($flipbook->sort_order); ?></td>
                                        <td><strong><?php echo esc_html($flipbook->title); ?></strong></td>
                                        <td><?php echo esc_html($flipbook->description); ?></td>
                                        <td>
                                            <span class="pz-badge pz-badge-<?php echo $flipbook->access_type; ?>">
                                                <?php echo $flipbook->access_type === 'school' ? 'School Only' : 'All Users'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="button button-small pz-delete-flipbook" data-id="<?php echo $flipbook->id; ?>">Delete</button>
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
     * Save flipbook (AJAX)
     */
    public function save_flipbook()
    {
        check_ajax_referer('pz_flipbooks_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;

        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $flipbook_url = wp_kses_post($_POST['flipbook_url']);
        $access_type = sanitize_text_field($_POST['access_type']);
        $sort_order = intval($_POST['sort_order']);

        if (empty($title) || empty($flipbook_url)) {
            wp_send_json_error(array('message' => 'Title and URL are required'));
        }

        $this->create_table_if_not_exists();

        $result = $wpdb->insert(
            $wpdb->prefix . 'pz_flipbooks',
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
            wp_send_json_error(array(
                'message' => 'Database error: ' . $wpdb->last_error
            ));
            return;
        }

        wp_send_json_success(array(
            'message' => 'Flipbook added successfully',
            'id' => $wpdb->insert_id
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
     * Check if user purchased a specific product (SIMPLIFIED - No expiration check)
     */
    private function user_has_purchased_product($user_id, $product_id)
    {
        if (!$user_id || !$product_id) {
            return false;
        }

        // Check orders for this user
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

    /**
     * Get flipbooks for user based on purchased products
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

        // Get product IDs
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        error_log('PZ Flipbooks: School Product ID: ' . $school_product_id);
        error_log('PZ Flipbooks: Student Product ID: ' . $student_product_id);

        // Check if user purchased school license
        $has_school = $this->user_has_purchased_product($user_id, $school_product_id);

        // Check if user purchased student package
        $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

        error_log('PZ Flipbooks: User ' . $user_id . ' - School: ' . ($has_school ? 'YES' : 'NO') . ', Student: ' . ($has_student ? 'YES' : 'NO'));

        if ($has_school) {
            // School license buyers get ALL flipbooks
            $flipbooks = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}pz_flipbooks 
                WHERE status = 'active' 
                ORDER BY sort_order ASC, id DESC"
            );
            error_log('PZ Flipbooks: School user - Found ' . count($flipbooks) . ' flipbooks');
            return $flipbooks;
        }

        if ($has_student) {
            // Student package buyers only get 'all' access flipbooks
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
     * Check if user can access flipbook based on purchases
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

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        $has_school = $this->user_has_purchased_product($user_id, $school_product_id);
        $has_student = $this->user_has_purchased_product($user_id, $student_product_id);

        // School buyers can access everything
        if ($has_school) {
            return true;
        }

        // Student buyers can only access 'all' flipbooks
        if ($has_student && $flipbook->access_type === 'all') {
            return true;
        }

        return false;
    }

    /**
     * Add flipbooks tab to My Account
     */
    public function add_flipbooks_tab($items)
    {
        $new_items = array();
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'orders') {
                $new_items['flipbooks'] = 'Study Materials';
            }
        }
        return $new_items;
    }

    /**
     * Add flipbooks endpoint
     */
    public function add_flipbooks_endpoint()
    {
        add_rewrite_endpoint('flipbooks', EP_ROOT | EP_PAGES);
    }

    /**
     * Flipbooks tab content
     */
    public function flipbooks_tab_content()
    {
        if (!is_user_logged_in()) {
            echo '<p>Please log in to view your flipbooks.</p>';
            return;
        }

        $user_id = get_current_user_id();
        $flipbooks = $this->get_flipbooks_for_user($user_id);

        include PZ_LICENSE_PATH . 'templates/flipbooks-tab.php';
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_pz-flipbooks') {
            return;
        }

        wp_enqueue_style('pz-flipbooks-admin', PZ_LICENSE_URL . 'assets/css/flipbooks.css', array(), PZ_LICENSE_VERSION);
        wp_enqueue_script('pz-flipbooks-admin', PZ_LICENSE_URL . 'assets/js/flipbooks-admin.js', array('jquery'), PZ_LICENSE_VERSION, true);

        wp_localize_script('pz-flipbooks-admin', 'pzFlipbooksAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pz_flipbooks_nonce')
        ));
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        if (!is_account_page()) {
            return;
        }

        wp_enqueue_style('pz-flipbooks-frontend', PZ_LICENSE_URL . 'assets/css/flipbooks.css', array(), PZ_LICENSE_VERSION);
        wp_enqueue_script('pz-flipbooks-frontend', PZ_LICENSE_URL . 'assets/js/flipbooks-frontend.js', array('jquery'), PZ_LICENSE_VERSION, true);

        wp_localize_script('pz-flipbooks-frontend', 'pzFlipbooks', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pz_flipbooks_view_nonce')
        ));
    }
}
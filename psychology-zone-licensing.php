<?php
/**
 * Plugin Name: Psychology Zone Licensing System
 * Description: Student and School licensing management system with WooCommerce integration
 * Version: 2.0.0
 * Author: Your Name
 * Requires: WooCommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PZ_LICENSE_VERSION', '2.0.0');
define('PZ_LICENSE_PATH', plugin_dir_path(__FILE__));
define('PZ_LICENSE_URL', plugin_dir_url(__FILE__));

// Include flipbooks class
require_once PZ_LICENSE_PATH . 'includes/class-pz-flipbooks.php';

/**
 * Main Plugin Class
 */
class PZ_License_System {
    
    private static $instance = null;
    private $school_product_id = null;
    private $student_product_id = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Check if WooCommerce is active
        add_action('admin_notices', array($this, 'check_woocommerce'));
        add_action('admin_notices', array($this, 'products_setup_notice'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register shortcode for package display
        add_shortcode('pz_license_packages', array($this, 'render_packages'));
        
        // Create custom database tables
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Handle manual product creation
        add_action('admin_init', array($this, 'handle_product_creation'));
        
        // Handle enroll button clicks
        add_action('template_redirect', array($this, 'handle_enroll_redirect'));
        
        // WooCommerce hooks
        add_action('woocommerce_thankyou', array($this, 'handle_order_thankyou'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'activate_license_on_payment'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'activate_license_on_payment'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'activate_license_on_payment'), 10, 1);
        
        // Add license info to order meta
        add_action('woocommerce_checkout_create_order', array($this, 'add_license_meta_to_order'), 10, 2);
        
        // Admin menu for school license management
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_notices', array($this, 'school_license_dashboard_notice'));
        
        // Custom user roles
        add_action('init', array($this, 'register_user_roles'));
    }
    
    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            ?>
            <div class="notice notice-error">
                <p><strong>Psychology Zone Licensing:</strong> This plugin requires WooCommerce to be installed and active.</p>
            </div>
            <?php
        }
    }
    
    /**
     * Show notice if products are not created
     */
    public function products_setup_notice() {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');
        
        $school_exists = $school_product_id && get_post($school_product_id);
        $student_exists = $student_product_id && get_post($student_product_id);
        
        if (!$school_exists || !$student_exists) {
            ?>
            <div class="notice notice-warning">
                <p><strong>Psychology Zone Licensing:</strong> Products need to be created.</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=pz-setup-products'); ?>" class="button button-primary">
                        Create License Products Now
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Handle manual product creation
     */
    public function handle_product_creation() {
        // Only proceed if we're on the setup page and action is set
        if (!isset($_GET['page']) || $_GET['page'] !== 'pz-setup-products') {
            return;
        }
        
        // Only create products if the action parameter is set
        if (!isset($_GET['action']) || $_GET['action'] !== 'create') {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Create products
        $this->create_products();
        
        // Redirect with success message (remove the action parameter)
        wp_redirect(admin_url('admin.php?page=pz-setup-products&created=1'));
        exit;
    }
    
    public function init() {
        $this->register_user_roles();
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // School licenses table
        $table_school = $wpdb->prefix . 'pz_school_licenses';
        $sql1 = "CREATE TABLE IF NOT EXISTS $table_school (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            school_name varchar(255) NOT NULL,
            license_key varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NOT NULL,
            max_students int(11) DEFAULT 9999,
            max_teachers int(11) DEFAULT 9999,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // Student licenses table
        $table_student = $wpdb->prefix . 'pz_student_licenses';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_student (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            license_key varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY license_key (license_key),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // School members table
        $table_members = $wpdb->prefix . 'pz_school_members';
        $sql3 = "CREATE TABLE IF NOT EXISTS $table_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            school_license_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            member_type varchar(20) NOT NULL,
            email varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active',
            invited_at datetime DEFAULT CURRENT_TIMESTAMP,
            joined_at datetime,
            PRIMARY KEY (id),
            KEY school_license_id (school_license_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        
        PZ_Flipbooks::create_table();

        flush_rewrite_rules();
    }
    
    /**
     * Create WooCommerce products on activation
     */
    public function create_products() {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        $created = false;
        
        // Check if products already exist
        $existing_school = get_option('pz_school_product_id');
        $existing_student = get_option('pz_student_product_id');
        
        // Create School License Product
        if (!$existing_school || !get_post($existing_school)) {
            $school_product = new WC_Product_Simple();
            $school_product->set_name('School Licence - 1 Year Subscription');
            $school_product->set_status('publish');
            $school_product->set_catalog_visibility('visible');
            $school_product->set_description('Complete school license with unlimited students and teachers for 1 year.');
            $school_product->set_short_description('Unlimited students, unlimited teachers, 12 months access');
            $school_product->set_regular_price('199.00');
            $school_product->set_manage_stock(false);
            $school_product->set_sold_individually(true);
            $school_product->set_virtual(true);
            
            $school_id = $school_product->save();
            
            if ($school_id) {
                update_option('pz_school_product_id', $school_id);
                update_post_meta($school_id, '_pz_license_type', 'school');
                $created = true;
            }
        }
        
        // Create Student Package Product
        if (!$existing_student || !get_post($existing_student)) {
            $student_product = new WC_Product_Simple();
            $student_product->set_name('Student Package - 1 Year Subscription');
            $student_product->set_status('publish');
            $student_product->set_catalog_visibility('visible');
            $student_product->set_description('Individual student package with full access to all materials for 1 year.');
            $student_product->set_short_description('Access to all study materials, 12 months access');
            $student_product->set_regular_price('49.99');
            $student_product->set_manage_stock(false);
            $student_product->set_sold_individually(true);
            $student_product->set_virtual(true);
            
            $student_id = $student_product->save();
            
            if ($student_id) {
                update_option('pz_student_product_id', $student_id);
                update_post_meta($student_id, '_pz_license_type', 'student');
                $created = true;
            }
        }
        
        return $created;
    }
    
    /**
     * Register user roles
     */
    public function register_user_roles() {
        if (!get_role('pz_school_admin')) {
            add_role('pz_school_admin', 'School Administrator', array(
                'read' => true,
                'manage_school_license' => true,
            ));
        }
        
        if (!get_role('pz_teacher')) {
            add_role('pz_teacher', 'Teacher', array(
                'read' => true,
                'pz_access_materials' => true,
            ));
        }
        
        if (!get_role('pz_student')) {
            add_role('pz_student', 'Student', array(
                'read' => true,
                'pz_access_materials' => true,
            ));
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('pz-license-styles', PZ_LICENSE_URL . 'assets/css/styles.css', array(), PZ_LICENSE_VERSION);
        wp_enqueue_script('pz-license-scripts', PZ_LICENSE_URL . 'assets/js/scripts.js', array('jquery'), PZ_LICENSE_VERSION, true);
        
        wp_localize_script('pz-license-scripts', 'pzLicense', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pz_license_nonce'),
            'my_account_url' => wc_get_page_permalink('myaccount'),
            'is_logged_in' => is_user_logged_in(),
        ));
    }
    
    /**
     * Handle enroll button redirects
     */
    public function handle_enroll_redirect() {
        // Check if the pz_enroll parameter exists
        if (!isset($_GET['pz_enroll'])) {
            return;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_die('WooCommerce is required for this functionality.');
        }
        
        $package = sanitize_text_field($_GET['pz_enroll']);
        
        // Validate package type
        if (!in_array($package, array('school', 'student'))) {
            wp_die('Invalid package type.');
        }
        
        // Get product IDs
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');
        
        $product_id = ($package === 'school') ? $school_product_id : $student_product_id;
        
        if (!$product_id || !get_post($product_id)) {
            wp_die('Product not found. Please contact administrator. Product ID: ' . $product_id);
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            // Store intended action in session
            if (WC()->session) {
                WC()->session->set('pz_enroll_package', $package);
                WC()->session->set('pz_enroll_product_id', $product_id);
            }
            
            // Redirect to my account page (login/register)
            $my_account_url = wc_get_page_permalink('myaccount');
            wp_safe_redirect($my_account_url);
            exit;
        }
        
        // User is logged in - add product to cart and redirect to checkout
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($product_id);
        
        // Store package type in session
        if (WC()->session) {
            if ($package === 'school') {
                WC()->session->set('pz_package_type', 'school');
            } else {
                WC()->session->set('pz_package_type', 'student');
            }
        }
        
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
    
    /**
     * After user logs in via My Account, check if they need to be redirected to checkout
     */
    public function __construct_additional_hooks() {
        add_action('woocommerce_login_redirect', array($this, 'redirect_after_login'), 10, 2);
    }
    
    public function redirect_after_login($redirect, $user) {
        // Check if there's a pending enrollment
        $package = WC()->session->get('pz_enroll_package');
        $product_id = WC()->session->get('pz_enroll_product_id');
        
        if ($package && $product_id) {
            // Clear the session
            WC()->session->set('pz_enroll_package', null);
            WC()->session->set('pz_enroll_product_id', null);
            
            // Add to cart and redirect to checkout
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id);
            
            if ($package === 'school') {
                WC()->session->set('pz_package_type', 'school');
            } else {
                WC()->session->set('pz_package_type', 'student');
            }
            
            return wc_get_checkout_url();
        }
        
        return $redirect;
    }
    
    /**
     * Add license metadata to order
     */
    public function add_license_meta_to_order($order, $data) {
        $package_type = WC()->session->get('pz_package_type');
        
        if ($package_type) {
            $order->update_meta_data('_pz_package_type', $package_type);
            
            // Generate license key
            $license_key = $this->generate_license_key($package_type === 'school' ? 'SCH' : 'STU');
            $order->update_meta_data('_pz_license_key', $license_key);
            
            // For school packages, we'll ask for school name in checkout
            if ($package_type === 'school') {
                // We can add a custom field to checkout for school name
                // For now, we'll use billing company name
                $school_name = !empty($data['billing_company']) ? $data['billing_company'] : 'School';
                $order->update_meta_data('_pz_school_name', $school_name);
            }
        }
    }
    
    /**
     * Activate license when payment is complete
     */
    public function activate_license_on_payment($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        // Check if license already activated
        $license_activated = $order->get_meta('_pz_license_activated');
        if ($license_activated) return;
        
        $package_type = $order->get_meta('_pz_package_type');
        $license_key = $order->get_meta('_pz_license_key');
        $user_id = $order->get_customer_id();
        
        if (!$package_type || !$license_key || !$user_id) return;
        
        global $wpdb;
        
        $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        if ($package_type === 'school') {
            $school_name = $order->get_meta('_pz_school_name');
            if (!$school_name) {
                $school_name = $order->get_billing_company();
            }
            if (!$school_name) {
                $school_name = 'School License';
            }
            
            // Create school license
            $wpdb->insert(
                $wpdb->prefix . 'pz_school_licenses',
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'school_name' => $school_name,
                    'license_key' => $license_key,
                    'status' => 'active',
                    'end_date' => $end_date,
                    'start_date' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            $license_id = $wpdb->insert_id;
            
            // Update user meta
            update_user_meta($user_id, 'has_school_license', true);
            update_user_meta($user_id, 'active_school_license_id', $license_id);
            
            // Grant capabilities
            $user = new WP_User($user_id);
            $user->add_cap('manage_school_license');
            
        } else {
            // Create student license
            $wpdb->insert(
                $wpdb->prefix . 'pz_student_licenses',
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'license_key' => $license_key,
                    'status' => 'active',
                    'end_date' => $end_date,
                    'start_date' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s')
            );
            
            // Update user role to student
            $user = new WP_User($user_id);
            $user->set_role('pz_student');
        }
        
        // Mark license as activated
        $order->update_meta_data('_pz_license_activated', true);
        $order->save();
        
        // Send welcome email
        $this->send_welcome_email($user_id, $package_type, $license_key);
        
        // Clear session
        WC()->session->set('pz_package_type', null);
    }
    
    /**
     * Handle thank you page
     */
    public function handle_order_thankyou($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $package_type = $order->get_meta('_pz_package_type');
        $license_key = $order->get_meta('_pz_license_key');
        
        if ($package_type && $license_key) {
            ?>
            <div class="pz-thankyou-message" style="background: #f0f7ff; border: 2px solid #4A90E2; border-radius: 8px; padding: 30px; margin: 30px 0;">
                <h2 style="color: #4A90E2; margin-top: 0;">🎉 Welcome to Psychology Zone!</h2>
                <p style="font-size: 18px; margin: 20px 0;">
                    Your <?php echo $package_type === 'school' ? 'School Licence' : 'Student Package'; ?> has been activated!
                </p>
                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <strong>Your License Key:</strong><br>
                    <code style="font-size: 20px; color: #E94B3C; background: #f5f5f5; padding: 10px 20px; border-radius: 4px; display: inline-block; margin-top: 10px;">
                        <?php echo esc_html($license_key); ?>
                    </code>
                </div>
                <p style="margin: 20px 0;">
                    A confirmation email has been sent to your email address with all the details.
                </p>
                <div style="margin-top: 30px;">
                    <a href="<?php echo wc_get_page_permalink('myaccount'); ?>" 
                       style="display: inline-block; background: #4A90E2; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 18px;">
                        Go to My Account Dashboard
                    </a>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Generate license key
     */
    private function generate_license_key($prefix = 'PZ') {
        return $prefix . '-' . strtoupper(wp_generate_password(16, false, false));
    }
    
    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id, $package_type, $license_key) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $subject = 'Welcome to Psychology Zone - Your License is Active!';
        $dashboard_url = wc_get_page_permalink('myaccount');
        
        if ($package_type === 'school') {
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your School Licence has been activated successfully.</p>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>License Key:</strong> <code>{$license_key}</code></p>
                    <p><strong>Valid Until:</strong> " . date('F j, Y', strtotime('+1 year')) . "</p>
                </div>
                <p>You can now manage your school license from your account dashboard.</p>
                <p><a href='{$dashboard_url}' style='background: #E94B3C; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Access Your Dashboard</a></p>
            ";
        } else {
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your Student Package has been activated successfully.</p>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>License Key:</strong> <code>{$license_key}</code></p>
                    <p><strong>Valid Until:</strong> " . date('F j, Y', strtotime('+1 year')) . "</p>
                </div>
                <p>You now have full access to all study materials and resources.</p>
                <p><a href='{$dashboard_url}' style='background: #4A90E2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Access Your Dashboard</a></p>
            ";
        }
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Render packages shortcode
     */
    public function render_packages($atts) {
        ob_start();
        
        // Get product IDs
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');
        
        include PZ_LICENSE_PATH . 'templates/packages.php';
        return ob_get_clean();
    }
    
    /**
     * Get user's school license
     */
    public function get_user_school_license($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) return false;
        
        global $wpdb;
        
        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_licenses 
            WHERE user_id = %d 
            AND status = 'active' 
            AND end_date > NOW()
            ORDER BY id DESC
            LIMIT 1",
            $user_id
        ));
        
        return $license ? $license : false;
    }
    
    /**
     * Admin menu for school license
     */
    public function add_admin_menu() {
        // Setup products page
        add_menu_page(
            'PZ License Setup',
            'PZ License Setup',
            'manage_options',
            'pz-setup-products',
            array($this, 'render_setup_page'),
            'dashicons-admin-plugins',
            100
        );
        
        // School license menu (only for users with active license)
        $user_id = get_current_user_id();
        $school_license = $this->get_user_school_license($user_id);
        
        if ($school_license) {
            add_menu_page(
                'School License',
                'School License',
                'read',
                'pz-school-license',
                array($this, 'render_school_admin_page'),
                'dashicons-welcome-learn-more',
                30
            );
        }
    }
    
    /**
     * Render setup page
     */
    public function render_setup_page() {
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');
        
        $school_exists = $school_product_id && get_post($school_product_id);
        $student_exists = $student_product_id && get_post($student_product_id);
        
        ?>
        <div class="wrap">
            <h1>Psychology Zone License Setup</h1>
            
            <?php if (isset($_GET['created'])): ?>
                <div class="notice notice-success">
                    <p><strong>Success!</strong> License products have been created.</p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Product Status</h2>
                
                <table class="widefat">
                    <tr>
                        <th style="width: 200px;">School License Product</th>
                        <td>
                            <?php if ($school_exists): ?>
                                <span style="color: green;">✓ Created</span>
                                - <a href="<?php echo admin_url('post.php?post=' . $school_product_id . '&action=edit'); ?>" target="_blank">View Product</a>
                            <?php else: ?>
                                <span style="color: red;">✗ Not Created</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Student Package Product</th>
                        <td>
                            <?php if ($student_exists): ?>
                                <span style="color: green;">✓ Created</span>
                                - <a href="<?php echo admin_url('post.php?post=' . $student_product_id . '&action=edit'); ?>" target="_blank">View Product</a>
                            <?php else: ?>
                                <span style="color: red;">✗ Not Created</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php if (!$school_exists || !$student_exists): ?>
                    <p style="margin-top: 20px;">
                        <a href="<?php echo admin_url('admin.php?page=pz-setup-products&action=create'); ?>" class="button button-primary button-large">
                            Create Products Now
                        </a>
                    </p>
                <?php else: ?>
                    <div style="margin-top: 20px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
                        <strong>✓ Setup Complete!</strong>
                        <p>All products are created. You can now use the shortcode <code>[pz_license_packages]</code> on any page.</p>
                        <p>
                            <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button">Create a Page</a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <hr style="margin: 30px 0;">
                
                <h3>Quick Guide</h3>
                <ol>
                    <li>Make sure WooCommerce is installed and active</li>
                    <li>Click "Create Products Now" button above</li>
                    <li>Create a new page and add the shortcode: <code>[pz_license_packages]</code></li>
                    <li>Configure your WooCommerce payment gateways (PayPal, Stripe, etc.)</li>
                    <li>Test the enrollment process!</li>
                </ol>
                
                <h3>Troubleshooting</h3>
                <ul>
                    <li>If the Enroll button shows 404, go to <strong>Settings → Permalinks</strong> and click Save Changes</li>
                    <li>Make sure WooCommerce is properly configured with payment methods</li>
                    <li>Check that the products are published (not draft)</li>
                </ul>
                
                <h3>Debug Information</h3>
                <table class="widefat">
                    <tr>
                        <th style="width: 200px;">WooCommerce Active</th>
                        <td><?php echo class_exists('WooCommerce') ? '<span style="color: green;">✓ Yes</span>' : '<span style="color: red;">✗ No</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>School Product ID</th>
                        <td><?php echo $school_product_id ? $school_product_id : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <th>Student Product ID</th>
                        <td><?php echo $student_product_id ? $student_product_id : 'Not set'; ?></td>
                    </tr>
                    <tr>
                        <th>Test Enroll URL</th>
                        <td>
                            <a href="<?php echo home_url('/?pz_enroll=student'); ?>" target="_blank">
                                <?php echo home_url('/?pz_enroll=student'); ?>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render school admin page
     */
    public function render_school_admin_page() {
        $user_id = get_current_user_id();
        $school_license = $this->get_user_school_license($user_id);
        
        if (!$school_license) {
            echo '<div class="wrap"><h1>No Active License</h1><p>You do not have an active school license.</p></div>';
            return;
        }
        
        $days_remaining = floor((strtotime($school_license->end_date) - time()) / (60 * 60 * 24));
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($school_license->school_name); ?> - Dashboard</h1>
            
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 30px;">
                <h2>License Information</h2>
                <table class="widefat" style="margin-top: 20px;">
                    <tr>
                        <th style="width: 200px;">School Name</th>
                        <td><?php echo esc_html($school_license->school_name); ?></td>
                    </tr>
                    <tr>
                        <th>License Key</th>
                        <td><code><?php echo esc_html($school_license->license_key); ?></code></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span style="display: inline-block; padding: 5px 15px; background: #5cb85c; color: white; border-radius: 4px; font-size: 12px; font-weight: bold;">ACTIVE</span></td>
                    </tr>
                    <tr>
                        <th>Days Remaining</th>
                        <td><strong><?php echo $days_remaining; ?> days</strong></td>
                    </tr>
                    <tr>
                        <th>Expires On</th>
                        <td><?php echo date('F j, Y', strtotime($school_license->end_date)); ?></td>
                    </tr>
                </table>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f0f7ff; border-left: 4px solid #4A90E2; border-radius: 4px;">
                <h3 style="margin-top: 0;">🚀 Coming Soon</h3>
                <p>Full teacher and student management features will be available here.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * School license notice
     */
    public function school_license_dashboard_notice() {
        $user_id = get_current_user_id();
        $school_license = $this->get_user_school_license($user_id);
        
        if ($school_license && is_admin() && get_current_screen()->id === 'dashboard') {
            $days_remaining = floor((strtotime($school_license->end_date) - time()) / (60 * 60 * 24));
            $status_color = $days_remaining < 30 ? '#f0ad4e' : '#5cb85c';
            ?>
            <div class="notice notice-info" style="border-left-color: <?php echo $status_color; ?>;">
                <h3 style="margin: 10px 0;">School License Active</h3>
                <p>
                    <strong><?php echo esc_html($school_license->school_name); ?></strong><br>
                    License expires in <strong><?php echo $days_remaining; ?> days</strong>
                </p>
            </div>
            <?php
        }
    }
}

// Initialize the plugin
function pz_license_system() {
    return PZ_License_System::get_instance();
}
add_action('plugins_loaded', 'pz_license_system');

// Also hook the redirect after login
add_action('woocommerce_login_redirect', function($redirect, $user) {
    $pz = pz_license_system();
    return $pz->redirect_after_login($redirect, $user);
}, 10, 2);
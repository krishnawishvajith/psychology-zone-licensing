<?php

/**
 * Plugin Name: Psychology Zone Licensing System
 * Description: Student and School licensing management system with WooCommerce integration (Simplified - No License Keys)
 * Version: 2.1.0
 * Author: Your Name
 * Requires: WooCommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PZ_LICENSE_VERSION', '2.1.0');
define('PZ_LICENSE_PATH', plugin_dir_path(__FILE__));
define('PZ_LICENSE_URL', plugin_dir_url(__FILE__));

// Include flipbooks class
require_once PZ_LICENSE_PATH . 'includes/class-pz-flipbooks.php';

/**
 * Main Plugin Class
 */
class PZ_License_System
{

    private static $instance = null;
    private $school_product_id = null;
    private $student_product_id = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
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
        add_action('woocommerce_payment_complete', array($this, 'grant_access_on_payment'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'grant_access_on_payment'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'grant_access_on_payment'), 10, 1);

        // Add package type to order meta
        add_action('woocommerce_checkout_create_order', array($this, 'add_package_meta_to_order'), 10, 2);

        // Admin menu for school license management
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Custom user roles
        add_action('init', array($this, 'register_user_roles'));
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce()
    {
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
    public function products_setup_notice()
    {
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
    public function handle_product_creation()
    {
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

    public function init()
    {
        $this->register_user_roles();
    }

    /**
     * Create database tables
     */
    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // School licenses table - simplified version
        $table_school = $wpdb->prefix . 'pz_school_licenses';
        $sql1 = "CREATE TABLE IF NOT EXISTS $table_school (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            school_name varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        // Student licenses table - simplified version
        $table_student = $wpdb->prefix . 'pz_student_licenses';
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_student (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_id (order_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);

        PZ_Flipbooks::create_table();

        flush_rewrite_rules();
    }

    /**
     * Create WooCommerce products on activation
     */
    public function create_products()
    {
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
    public function register_user_roles()
    {
        if (!get_role('pz_school_admin')) {
            add_role('pz_school_admin', 'School Administrator', array(
                'read' => true,
                'manage_school_license' => true,
            ));
        }

        if (!get_role('pz_teacher')) {
            add_role('pz_teacher', 'Teacher', array(
                'read' => true,
            ));
        }

        if (!get_role('pz_student')) {
            add_role('pz_student', 'Student', array(
                'read' => true,
            ));
        }
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts()
    {
        wp_enqueue_style('pz-license-styles', PZ_LICENSE_URL . 'assets/css/styles.css', array(), PZ_LICENSE_VERSION);
        wp_enqueue_script('pz-license-scripts', PZ_LICENSE_URL . 'assets/js/scripts.js', array('jquery'), PZ_LICENSE_VERSION, true);

        wp_localize_script('pz-license-scripts', 'pzLicense', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pz_license_nonce')
        ));
    }

    /**
     * Handle enroll redirect
     */
    public function handle_enroll_redirect()
    {
        if (!isset($_GET['pz_enroll']) || !isset($_GET['package'])) {
            return;
        }

        $package = sanitize_text_field($_GET['package']);

        // Get product ID
        if ($package === 'school') {
            $product_id = get_option('pz_school_product_id');
        } elseif ($package === 'student') {
            $product_id = get_option('pz_student_product_id');
        } else {
            wp_die('Invalid package type');
        }

        if (!$product_id) {
            wp_die('Product not found. Please contact administrator.');
        }

        // If user not logged in, store package info and redirect to login
        if (!is_user_logged_in()) {
            WC()->session->set('pz_enroll_package', $package);
            WC()->session->set('pz_enroll_product_id', $product_id);

            $login_url = wp_login_url(wc_get_checkout_url());
            wp_redirect($login_url);
            exit;
        }

        // User is logged in, add to cart and redirect to checkout
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($product_id);

        // Store package type in session
        WC()->session->set('pz_package_type', $package);

        wp_redirect(wc_get_checkout_url());
        exit;
    }

    /**
     * Add package metadata to order
     */
    public function add_package_meta_to_order($order, $data)
    {
        $package_type = WC()->session->get('pz_package_type');

        if ($package_type) {
            $order->update_meta_data('_pz_package_type', $package_type);

            // For school packages, save school name
            if ($package_type === 'school') {
                $school_name = !empty($data['billing_company']) ? $data['billing_company'] : 'School';
                $order->update_meta_data('_pz_school_name', $school_name);
            }
        }
    }

    /**
     * Grant access when payment is complete
     */
    public function grant_access_on_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        // Check if access already granted
        $access_granted = $order->get_meta('_pz_access_granted');
        if ($access_granted) return;

        $package_type = $order->get_meta('_pz_package_type');
        $user_id = $order->get_customer_id();

        if (!$package_type || !$user_id) return;

        global $wpdb;

        if ($package_type === 'school') {
            $school_name = $order->get_meta('_pz_school_name');
            if (!$school_name) {
                $school_name = $order->get_billing_company();
            }
            if (!$school_name) {
                $school_name = 'School License';
            }

            // Create school license record
            $wpdb->insert(
                $wpdb->prefix . 'pz_school_licenses',
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'school_name' => $school_name,
                    'status' => 'active'
                ),
                array('%d', '%d', '%s', '%s')
            );

            // Update user meta
            update_user_meta($user_id, 'has_school_license', true);

            // Grant capabilities
            $user = new WP_User($user_id);
            $user->add_cap('manage_school_license');

        } else {
            // Create student license record
            $wpdb->insert(
                $wpdb->prefix . 'pz_student_licenses',
                array(
                    'user_id' => $user_id,
                    'order_id' => $order_id,
                    'status' => 'active'
                ),
                array('%d', '%d', '%s')
            );

            // Update user meta
            update_user_meta($user_id, 'has_student_license', true);
        }

        // Mark access as granted
        $order->update_meta_data('_pz_access_granted', true);
        $order->save();

        // Send welcome email
        $this->send_welcome_email($user_id, $package_type);

        // Clear session
        WC()->session->set('pz_package_type', null);
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id, $package_type)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) return;

        $subject = 'Welcome to Psychology Zone - Your Access is Active!';
        $dashboard_url = wc_get_page_permalink('myaccount');

        if ($package_type === 'school') {
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your School Licence has been activated successfully.</p>
                <p>You now have full access to all study materials in your account dashboard.</p>
                <p><a href='{$dashboard_url}' style='background: #E94B3C; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Access Your Dashboard</a></p>
            ";
        } else {
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your Student Package has been activated successfully.</p>
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
    public function render_packages($atts)
    {
        ob_start();

        // Get product IDs
        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        include PZ_LICENSE_PATH . 'templates/packages.php';
        return ob_get_clean();
    }

    /**
     * Check if user has active school license
     */
    public function user_has_school_license($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) return false;

        global $wpdb;

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_licenses 
            WHERE user_id = %d 
            AND status = 'active'
            ORDER BY id DESC
            LIMIT 1",
            $user_id
        ));

        return $license ? true : false;
    }

    /**
     * Check if user has active student license
     */
    public function user_has_student_license($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) return false;

        global $wpdb;

        $license = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_student_licenses 
            WHERE user_id = %d 
            AND status = 'active'
            ORDER BY id DESC
            LIMIT 1",
            $user_id
        ));

        return $license ? true : false;
    }

    /**
     * Admin menu for school license
     */
    public function add_admin_menu()
    {
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
        if ($this->user_has_school_license()) {
            add_menu_page(
                'My School Access',
                'My School Access',
                'read',
                'pz-school-access',
                array($this, 'render_school_dashboard'),
                'dashicons-welcome-learn-more',
                30
            );
        }
    }

    /**
     * Render setup page
     */
    public function render_setup_page()
    {
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
            </div>
        </div>
        <?php
    }

    /**
     * Render school dashboard
     */
    public function render_school_dashboard()
    {
        if (!$this->user_has_school_license()) {
            echo '<div class="wrap"><h1>Access Denied</h1><p>You do not have an active school license.</p></div>';
            return;
        }

        include PZ_LICENSE_PATH . 'templates/school-dashboard.php';
    }

    /**
     * Handle thank you page
     */
    public function handle_order_thankyou($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $package_type = $order->get_meta('_pz_package_type');

        if ($package_type) {
        ?>
            <div class="pz-thankyou-message" style="background: #f0f7ff; border: 2px solid #4A90E2; border-radius: 8px; padding: 30px; margin: 30px 0;">
                <h2 style="color: #4A90E2; margin-top: 0;">🎉 Welcome to Psychology Zone!</h2>
                <p style="font-size: 18px; margin: 20px 0;">
                    Your <?php echo $package_type === 'school' ? 'School Licence' : 'Student Package'; ?> has been activated!
                </p>

                <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p><strong>✓ Access Granted</strong></p>
                    <p>You now have full access to all study materials in your account dashboard.</p>
                </div>

                <p style="margin: 20px 0;">
                    A confirmation email has been sent to your email address.
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
}

// Initialize the plugin
function pz_license_system_init()
{
    return PZ_License_System::get_instance();
}

add_action('plugins_loaded', 'pz_license_system_init');

// Initialize Flipbooks
add_action('plugins_loaded', array('PZ_Flipbooks', 'get_instance'));
<?php

/**
 * Plugin Name: Psychology Zone Licensing System
 * Description: Student and School licensing management system with WooCommerce integration
 * Version: 3.0.0
 * Author: Your Name
 * Requires: WooCommerce
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PZ_LICENSE_VERSION', '3.0.0');
define('PZ_LICENSE_PATH', plugin_dir_path(__FILE__));
define('PZ_LICENSE_URL', plugin_dir_url(__FILE__));

// Include required classes
require_once PZ_LICENSE_PATH . 'includes/class-pz-database.php';
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

        // AJAX handlers for member assignment
        add_action('wp_ajax_pz_assign_member', array($this, 'ajax_assign_member'));
        add_action('wp_ajax_pz_remove_member', array($this, 'ajax_remove_member'));
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
        if (!isset($_GET['page']) || $_GET['page'] !== 'pz-setup-products') {
            return;
        }

        if (!isset($_GET['action']) || $_GET['action'] !== 'create') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $this->create_products();
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
        PZ_Database::create_tables();
        PZ_Database::migrate_existing_data();
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

        $existing_school = get_option('pz_school_product_id');
        $existing_student = get_option('pz_student_product_id');

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
    public function enqueue_scripts()
    {
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
    public function handle_enroll_redirect()
    {
        if (!isset($_GET['pz_enroll'])) {
            return;
        }

        if (!class_exists('WooCommerce')) {
            wp_die('WooCommerce is required for this functionality.');
        }

        $package = sanitize_text_field($_GET['pz_enroll']);

        if (!in_array($package, array('school', 'student'))) {
            wp_die('Invalid package type.');
        }

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        $product_id = ($package === 'school') ? $school_product_id : $student_product_id;

        if (!$product_id || !get_post($product_id)) {
            wp_die('Product not found. Please contact administrator. Product ID: ' . $product_id);
        }

        if (!is_user_logged_in()) {
            if (WC()->session) {
                WC()->session->set('pz_enroll_package', $package);
                WC()->session->set('pz_enroll_product_id', $product_id);
            }

            $my_account_url = wc_get_page_permalink('myaccount');
            wp_safe_redirect($my_account_url);
            exit;
        }

        WC()->cart->empty_cart();
        WC()->cart->add_to_cart($product_id);

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

    public function redirect_after_login($redirect, $user)
    {
        $package = WC()->session->get('pz_enroll_package');
        $product_id = WC()->session->get('pz_enroll_product_id');

        if ($package && $product_id) {
            WC()->session->set('pz_enroll_package', null);
            WC()->session->set('pz_enroll_product_id', null);

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
    public function add_license_meta_to_order($order, $data)
    {
        $package_type = WC()->session->get('pz_package_type');

        if ($package_type) {
            $order->update_meta_data('_pz_package_type', $package_type);

            $license_key = $this->generate_license_key($package_type === 'school' ? 'SCH' : 'STU');
            $order->update_meta_data('_pz_license_key', $license_key);

            if ($package_type === 'school') {
                $school_name = !empty($data['billing_company']) ? $data['billing_company'] : 'School';
                $order->update_meta_data('_pz_school_name', $school_name);
            }
        }
    }

    /**
     * Activate license when payment is complete
     */
    public function activate_license_on_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

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

            // Create school license (NO AUTO-ACCOUNTS)
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

            update_user_meta($user_id, 'has_school_license', true);
            update_user_meta($user_id, 'active_school_license_id', $license_id);

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

            $user = new WP_User($user_id);
            $user->set_role('pz_student');
        }

        $order->update_meta_data('_pz_license_activated', true);
        $order->save();

        $this->send_welcome_email($user_id, $package_type, $license_key);

        WC()->session->set('pz_package_type', null);
    }

    /**
     * Handle thank you page
     */
    public function handle_order_thankyou($order_id)
    {
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

                <?php if ($package_type === 'school'): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="margin-top: 0;">📋 Next Steps for School License:</h3>
                        <ol style="margin: 15px 0; padding-left: 25px;">
                            <li>Go to your <strong>School Dashboard</strong></li>
                            <li>Assign teachers by entering their registered email addresses</li>
                            <li>Assign students by entering their registered email addresses</li>
                            <li>Assigned users will automatically get access to study materials</li>
                        </ol>
                        <p style="margin: 10px 0;"><strong>Note:</strong> Users must create an account on the site first before you can assign them.</p>
                    </div>
                <?php endif; ?>

                <p style="margin: 20px 0;">
                    A confirmation email has been sent to your email address with all the details.
                </p>
                <div style="margin-top: 30px;">
                    <a href="<?php echo admin_url('admin.php?page=pz-school-license'); ?>"
                        style="display: inline-block; background: #4A90E2; color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 18px;">
                        Go to School Dashboard
                    </a>
                </div>
            </div>
        <?php
        }
    }

    /**
     * Generate license key
     */
    private function generate_license_key($prefix = 'PZ')
    {
        return $prefix . '-' . strtoupper(wp_generate_password(16, false, false));
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id, $package_type, $license_key)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) return;

        $subject = 'Welcome to Psychology Zone - Your License is Active!';
        $dashboard_url = admin_url('admin.php?page=pz-school-license');

        if ($package_type === 'school') {
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your School Licence has been activated successfully.</p>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>License Key:</strong> <code>{$license_key}</code></p>
                    <p><strong>Valid Until:</strong> " . date('F j, Y', strtotime('+1 year')) . "</p>
                </div>
                <h3>Next Steps:</h3>
                <ol>
                    <li>Go to your School Dashboard</li>
                    <li>Assign teachers and students using their registered email addresses</li>
                    <li>Users must have a site account before you can assign them</li>
                </ol>
                <p><a href='{$dashboard_url}' style='background: #E94B3C; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Access Your Dashboard</a></p>
            ";
        } else {
            $my_account_url = wc_get_page_permalink('myaccount');
            $message = "
                <h2>🎉 Welcome to Psychology Zone!</h2>
                <p>Your Student Package has been activated successfully.</p>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>License Key:</strong> <code>{$license_key}</code></p>
                    <p><strong>Valid Until:</strong> " . date('F j, Y', strtotime('+1 year')) . "</p>
                </div>
                <p>You now have full access to all study materials and resources.</p>
                <p><a href='{$my_account_url}' style='background: #4A90E2; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Access Your Dashboard</a></p>
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

        $school_product_id = get_option('pz_school_product_id');
        $student_product_id = get_option('pz_student_product_id');

        include PZ_LICENSE_PATH . 'templates/packages.php';
        return ob_get_clean();
    }

    /**
     * Get user's school license
     */
    public function get_user_school_license($user_id = null)
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
    public function add_admin_menu()
    {
        add_menu_page(
            'PZ License Setup',
            'PZ License Setup',
            'manage_options',
            'pz-setup-products',
            array($this, 'render_setup_page'),
            'dashicons-admin-plugins',
            100
        );

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
    public function render_setup_page()
    {
        include PZ_LICENSE_PATH . 'templates/admin-setup-page.php';
    }

    /**
     * Render school admin page
     */
    public function render_school_admin_page()
    {
        include PZ_LICENSE_PATH . 'templates/admin-school-dashboard.php';
    }

    /**
     * AJAX: Assign member (teacher/student)
     */
    public function ajax_assign_member()
    {
        check_ajax_referer('pz_school_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $member_type = sanitize_text_field($_POST['member_type']);
        $license_id = intval($_POST['license_id']);

        if (!in_array($member_type, array('teacher', 'student'))) {
            wp_send_json_error(array('message' => 'Invalid member type'));
        }

        // Check if user exists
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(array('message' => 'No user found with this email. Please ask them to create an account first.'));
        }

        // Check if already assigned
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_members 
            WHERE school_license_id = %d AND assigned_user_id = %d AND member_type = %s AND status = 'active'",
            $license_id,
            $user->ID,
            $member_type
        ));

        if ($existing) {
            wp_send_json_error(array('message' => 'This user is already assigned as a ' . $member_type));
        }

        // Assign member
        $result = $wpdb->insert(
            $wpdb->prefix . 'pz_school_members',
            array(
                'school_license_id' => $license_id,
                'assigned_user_id' => $user->ID,
                'member_type' => $member_type,
                'status' => 'active',
                'assigned_by' => get_current_user_id()
            ),
            array('%d', '%d', '%s', '%s', '%d')
        );

        if ($result) {
            // Update user meta
            update_user_meta($user->ID, 'pz_assigned_as_' . $member_type, true);
            update_user_meta($user->ID, 'pz_school_license_id', $license_id);

            wp_send_json_success(array(
                'message' => ucfirst($member_type) . ' assigned successfully!',
                'user_name' => $user->display_name,
                'user_email' => $user->user_email
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to assign member'));
        }
    }

    /**
     * AJAX: Remove member
     */
    public function ajax_remove_member()
    {
        check_ajax_referer('pz_school_nonce', 'nonce');

        $member_id = intval($_POST['member_id']);

        global $wpdb;

        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pz_school_members WHERE id = %d",
            $member_id
        ));

        if (!$member) {
            wp_send_json_error(array('message' => 'Member not found'));
        }

        // Remove assignment
        $result = $wpdb->update(
            $wpdb->prefix . 'pz_school_members',
            array('status' => 'removed', 'removed_at' => current_time('mysql')),
            array('id' => $member_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            // Remove user meta
            delete_user_meta($member->assigned_user_id, 'pz_assigned_as_' . $member->member_type);
            delete_user_meta($member->assigned_user_id, 'pz_school_license_id');

            wp_send_json_success(array('message' => 'Member removed successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to remove member'));
        }
    }

    /**
     * School license notice
     */
    public function school_license_dashboard_notice()
    {
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
function pz_license_system()
{
    return PZ_License_System::get_instance();
}
add_action('plugins_loaded', 'pz_license_system');

add_action('woocommerce_login_redirect', function ($redirect, $user) {
    $pz = pz_license_system();
    return $pz->redirect_after_login($redirect, $user);
}, 10, 2);
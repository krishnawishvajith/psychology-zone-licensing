<?php
/**
 * Template: School Dashboard (Basic Version)
 * File: templates/school-dashboard.php
 * 
 * This redirects school license holders to WordPress admin dashboard
 * where they can access the School License menu
 */

if (!defined('ABSPATH')) exit;

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/school-dashboard/')));
    exit;
}

$user_id = get_current_user_id();
$pz_system = PZ_License_System::get_instance();
$school_license = $pz_system->get_user_school_license($user_id);

// If user doesn't have school license, show error message
if (!$school_license) {
    get_header();
    ?>
    <div style="max-width: 800px; margin: 100px auto; padding: 40px; text-align: center; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div style="font-size: 64px; margin-bottom: 20px;">🔒</div>
        <h1 style="color: #E94B3C; margin-bottom: 20px;">No Active School License</h1>
        <p style="font-size: 18px; color: #666; margin-bottom: 10px;">You don't have an active school license yet.</p>
        <p style="font-size: 16px; color: #999; margin-bottom: 30px;">Purchase a school license to access this dashboard.</p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="<?php echo home_url(); ?>" style="display: inline-block; padding: 15px 40px; background: #4A90E2; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Go to Homepage</a>
            <a href="<?php echo home_url('/pz-checkout/?package=school'); ?>" style="display: inline-block; padding: 15px 40px; background: #E94B3C; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Buy School License</a>
        </div>
    </div>
    <?php
    get_footer();
    exit;
}

// User has valid school license - redirect to WordPress admin
// They can access the School License menu there
wp_redirect(admin_url('admin.php?page=pz-school-license'));
exit;
?>
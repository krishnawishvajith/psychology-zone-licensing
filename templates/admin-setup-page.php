<?php
/**
 * Template: Admin Setup Page
 * File: templates/admin-setup-page.php
 */

if (!defined('ABSPATH')) exit;

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

        <h3>New Features (Version 3.0)</h3>
        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin: 15px 0;">
            <strong>🎉 Manual Member Assignment</strong>
            <ul>
                <li>No more auto-created accounts</li>
                <li>School license owners can manually assign teachers and students</li>
                <li>Users must register on the site first</li>
                <li>Assigned users get instant access to materials</li>
                <li>Better control and management for schools</li>
            </ul>
        </div>

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
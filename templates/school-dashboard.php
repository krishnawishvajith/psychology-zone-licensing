<?php
/**
 * Template: School Dashboard Tab in WooCommerce My Account
 * File: templates/school-dashboard-tab.php
 * 
 * This displays the teacher and student credentials for school license purchasers
 */

if (!defined('ABSPATH')) exit;
?>

<div class="pz-school-dashboard-tab">
    <h2>School License Dashboard</h2>
    
    <!-- License Information -->
    <div class="pz-license-info">
        <h3 style="margin-top: 0;">📋 License Information</h3>
        <div class="pz-license-info-grid">
            <div class="pz-license-info-item">
                <strong>School Name</strong>
                <span><?php echo esc_html($school_license->school_name); ?></span>
            </div>
            <div class="pz-license-info-item">
                <strong>License Key</strong>
                <span><?php echo esc_html($school_license->license_key); ?></span>
            </div>
            <div class="pz-license-info-item">
                <strong>Days Remaining</strong>
                <span style="color: <?php echo $days_remaining < 30 ? '#f0ad4e' : '#5cb85c'; ?>">
                    <?php echo $days_remaining; ?> days
                </span>
            </div>
            <div class="pz-license-info-item">
                <strong>Expires On</strong>
                <span><?php echo date('M j, Y', strtotime($school_license->end_date)); ?></span>
            </div>
        </div>
    </div>

    <!-- Credentials Section -->
    <div class="pz-school-credentials">
        <h3>🔑 Auto-Created Login Accounts</h3>
        
        <div class="pz-info-notice">
            <p><strong>ℹ️ About These Accounts:</strong></p>
            <p>Two accounts were automatically created when you purchased your school license. Share these credentials with your teachers and students so they can access all study materials.</p>
            <p><strong>Login URL:</strong> <a href="<?php echo home_url('/my-account/'); ?>" target="_blank"><?php echo home_url('/my-account/'); ?></a></p>
        </div>

        <?php if (empty($teacher_username) && empty($student_username)): ?>
            
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 6px; margin: 20px 0;">
                <p style="margin: 0; color: #721c24;"><strong>⚠️ Credentials Not Found</strong></p>
                <p style="margin: 10px 0 0; color: #721c24;">
                    Unable to retrieve account credentials. Please contact support with your Order ID: #<?php echo $school_license->order_id; ?>
                </p>
            </div>

        <?php else: ?>

        <!-- Teacher Account -->
        <?php if (!empty($teacher_username)): ?>
        <div class="pz-credential-card" style="border-left-color: #4A90E2;">
            <h3 style="color: #4A90E2; margin-top: 0;">👨‍🏫 Teacher Account Credentials</h3>
            <p style="color: #666; margin-bottom: 20px;">Share these credentials with your teachers to access all study materials.</p>
            
            <div class="pz-credential-row">
                <span class="pz-credential-label">Username:</span>
                <div class="pz-credential-value">
                    <code id="teacher-username"><?php echo esc_html($teacher_username); ?></code>
                </div>
                <button class="pz-copy-btn" onclick="copyCredential('teacher-username')">📋 Copy</button>
            </div>
            
            <?php if (!empty($teacher_password)): ?>
            <div class="pz-credential-row">
                <span class="pz-credential-label">Password:</span>
                <div class="pz-credential-value">
                    <code id="teacher-password"><?php echo esc_html($teacher_password); ?></code>
                </div>
                <button class="pz-copy-btn" onclick="copyCredential('teacher-password')">📋 Copy</button>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; padding: 15px; background: #f0f7ff; border-radius: 6px;">
                <p style="margin: 0; font-size: 14px; color: #666;">
                    <strong>How to use:</strong> Teachers can login at <a href="<?php echo home_url('/my-account/'); ?>" target="_blank">My Account</a> using these credentials and access all study materials from the "Study Materials" tab.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Student Account -->
        <?php if (!empty($student_username)): ?>
        <div class="pz-credential-card" style="border-left-color: #E94B3C; margin-top: 20px;">
            <h3 style="color: #E94B3C; margin-top: 0;">👨‍🎓 Student Account Credentials</h3>
            <p style="color: #666; margin-bottom: 20px;">Share these credentials with your students to access study materials.</p>
            
            <div class="pz-credential-row">
                <span class="pz-credential-label">Username:</span>
                <div class="pz-credential-value">
                    <code id="student-username"><?php echo esc_html($student_username); ?></code>
                </div>
                <button class="pz-copy-btn" onclick="copyCredential('student-username')">📋 Copy</button>
            </div>
            
            <?php if (!empty($student_password)): ?>
            <div class="pz-credential-row">
                <span class="pz-credential-label">Password:</span>
                <div class="pz-credential-value">
                    <code id="student-password"><?php echo esc_html($student_password); ?></code>
                </div>
                <button class="pz-copy-btn" onclick="copyCredential('student-password')">📋 Copy</button>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 15px; padding: 15px; background: #f0f7ff; border-radius: 6px;">
                <p style="margin: 0; font-size: 14px; color: #666;">
                    <strong>How to use:</strong> Students can login at <a href="<?php echo home_url('/my-account/'); ?>" target="_blank">My Account</a> using these credentials and access study materials from the "Study Materials" tab.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <!-- Important Notes -->
        <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 6px; margin-top: 30px;">
            <h4 style="margin-top: 0; color: #856404;">⚠️ Important Information</h4>
            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                <li>These credentials are permanent and cannot be changed</li>
                <li>Multiple teachers and students can share the same login</li>
                <li>All accounts have access until your license expires (<?php echo date('F j, Y', strtotime($school_license->end_date)); ?>)</li>
                <li>Save these credentials in a secure location</li>
                <li>If you lose these credentials, you can always view them here in your dashboard</li>
            </ul>
        </div>

    </div>
</div>

<script>
function copyCredential(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        // Show feedback
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.background = '#5cb85c';
        
        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = '#4A90E2';
        }, 2000);
    }).catch(err => {
        alert('Failed to copy. Please select and copy manually.');
    });
}
</script>
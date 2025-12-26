<?php
/**
 * Template: School Dashboard
 * File: templates/school-dashboard.php
 * 
 * Displays school license information with teacher and student credentials
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

error_log('PZ License Dashboard - User ID: ' . $user_id);
if ($school_license) {
    error_log('PZ License Dashboard - License ID: ' . $school_license->id);
    error_log('PZ License Dashboard - Teacher Username: ' . ($school_license->teacher_username ?? 'NULL'));
    error_log('PZ License Dashboard - Student Username: ' . ($school_license->student_username ?? 'NULL'));
}

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

$has_credentials = !empty($school_license->teacher_username) && !empty($school_license->student_username);

get_header();
?>

<div class="pz-school-dashboard-container" style="max-width: 1200px; margin: 40px auto; padding: 20px;">
    
    <!-- Dashboard Header -->
    <div class="pz-dashboard-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h1 style="margin: 0 0 10px 0; font-size: 32px;">School License Dashboard</h1>
        <p style="margin: 0; font-size: 18px; opacity: 0.9;"><?php echo esc_html($school_license->school_name); ?></p>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.3);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <div style="font-size: 14px; opacity: 0.8;">License Key</div>
                    <div style="font-size: 18px; font-weight: 600; font-family: monospace;"><?php echo esc_html($school_license->license_key); ?></div>
                </div>
                <div>
                    <div style="font-size: 14px; opacity: 0.8;">Status</div>
                    <div style="font-size: 18px; font-weight: 600;">✓ Active</div>
                </div>
                <div>
                    <div style="font-size: 14px; opacity: 0.8;">Valid Until</div>
                    <div style="font-size: 18px; font-weight: 600;"><?php echo date('F j, Y', strtotime($school_license->end_date)); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Study Materials Section -->
    <div class="pz-study-materials" style="background: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; color: #333;">Study Materials</h2>
        <p style="color: #666; margin-bottom: 20px;">Access all restricted study materials and resources</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <a href="#" style="display: block; padding: 20px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 2px solid #e9ecef; transition: all 0.3s;">
                <div style="font-size: 32px; margin-bottom: 10px;">📚</div>
                <h3 style="margin: 0 0 5px 0; color: #333;">Course Materials</h3>
                <p style="margin: 0; color: #666; font-size: 14px;">Access all course content</p>
            </a>
            
            <a href="#" style="display: block; padding: 20px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 2px solid #e9ecef; transition: all 0.3s;">
                <div style="font-size: 32px; margin-bottom: 10px;">📖</div>
                <h3 style="margin: 0 0 5px 0; color: #333;">Flipbooks</h3>
                <p style="margin: 0; color: #666; font-size: 14px;">Interactive study materials</p>
            </a>
            
            <a href="#" style="display: block; padding: 20px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 2px solid #e9ecef; transition: all 0.3s;">
                <div style="font-size: 32px; margin-bottom: 10px;">🎥</div>
                <h3 style="margin: 0 0 5px 0; color: #333;">Video Lectures</h3>
                <p style="margin: 0; color: #666; font-size: 14px;">Exclusive video content</p>
            </a>
            
            <a href="#" style="display: block; padding: 20px; background: #f8f9fa; border-radius: 8px; text-decoration: none; border: 2px solid #e9ecef; transition: all 0.3s;">
                <div style="font-size: 32px; margin-bottom: 10px;">📝</div>
                <h3 style="margin: 0 0 5px 0; color: #333;">Practice Tests</h3>
                <p style="margin: 0; color: #666; font-size: 14px;">Test your knowledge</p>
            </a>
        </div>
    </div>

    <!-- Account Credentials Section -->
    <div class="pz-credentials-section" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; color: #333;">Account Credentials</h2>
        <p style="color: #666; margin-bottom: 20px;">Teacher and Student accounts are automatically created for your school license. Use these credentials to login and access all materials.</p>
        
        <?php if (!$has_credentials): ?>
            <!-- Show warning if credentials are not available yet -->
            <div style="padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Credentials Not Available</h3>
                <p style="color: #856404; margin-bottom: 10px;">Your teacher and student accounts are being set up. This usually takes a few moments.</p>
                <p style="color: #856404; margin: 0;"><strong>Please refresh this page in a few seconds.</strong></p>
                <button onclick="location.reload()" style="margin-top: 15px; padding: 12px 24px; background: #ffc107; color: #000; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Refresh Page</button>
            </div>
        <?php else: ?>
        
        <!-- Tabs -->
        <div class="pz-credentials-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e9ecef;">
            <button class="pz-tab-btn active" onclick="switchTab('teacher')" id="teacher-tab" style="padding: 15px 30px; background: none; border: none; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s;">
                👨‍🏫 Teacher Account
            </button>
            <button class="pz-tab-btn" onclick="switchTab('student')" id="student-tab" style="padding: 15px 30px; background: none; border: none; cursor: pointer; font-size: 16px; font-weight: 600; color: #666; border-bottom: 3px solid transparent; transition: all 0.3s;">
                👨‍🎓 Student Account
            </button>
        </div>
        
        <!-- Teacher Credentials Tab -->
        <div id="teacher-content" class="pz-tab-content" style="display: block;">
            <div style="background: #f0f7ff; border: 2px solid #4A90E2; border-radius: 8px; padding: 30px;">
                <h3 style="margin-top: 0; color: #4A90E2;">Teacher Login Credentials</h3>
                <p style="color: #666; margin-bottom: 20px;">Use these credentials to login as a teacher. Teachers have full access to all study materials.</p>
                
                <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Username</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" value="<?php echo esc_attr($school_license->teacher_username); ?>" readonly style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px; font-family: monospace; font-size: 16px; background: #f8f9fa;">
                            <button onclick="copyToClipboard('<?php echo esc_js($school_license->teacher_username); ?>', 'teacher-username')" style="padding: 12px 20px; background: #4A90E2; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Copy</button>
                        </div>
                        <span id="teacher-username-copied" style="display: none; color: #28a745; font-size: 14px; margin-top: 5px;">✓ Copied!</span>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Password</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" value="<?php echo esc_attr($school_license->teacher_password); ?>" readonly style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px; font-family: monospace; font-size: 16px; background: #f8f9fa;">
                            <button onclick="copyToClipboard('<?php echo esc_js($school_license->teacher_password); ?>', 'teacher-password')" style="padding: 12px 20px; background: #4A90E2; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Copy</button>
                        </div>
                        <span id="teacher-password-copied" style="display: none; color: #28a745; font-size: 14px; margin-top: 5px;">✓ Copied!</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <a href="<?php echo wp_login_url(); ?>" target="_blank" style="display: inline-block; padding: 15px 30px; background: #4A90E2; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        Login as Teacher
                    </a>
                    <button onclick="printCredentials('teacher')" style="padding: 15px 30px; background: white; color: #4A90E2; border: 2px solid #4A90E2; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Print Credentials
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Student Credentials Tab -->
        <div id="student-content" class="pz-tab-content" style="display: none;">
            <div style="background: #fff3f0; border: 2px solid #E94B3C; border-radius: 8px; padding: 30px;">
                <h3 style="margin-top: 0; color: #E94B3C;">Student Login Credentials</h3>
                <p style="color: #666; margin-bottom: 20px;">Use these credentials to login as a student. Students have full access to all study materials.</p>
                
                <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 15px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Username</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" value="<?php echo esc_attr($school_license->student_username); ?>" readonly style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px; font-family: monospace; font-size: 16px; background: #f8f9fa;">
                            <button onclick="copyToClipboard('<?php echo esc_js($school_license->student_username); ?>', 'student-username')" style="padding: 12px 20px; background: #E94B3C; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Copy</button>
                        </div>
                        <span id="student-username-copied" style="display: none; color: #28a745; font-size: 14px; margin-top: 5px;">✓ Copied!</span>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 8px;">Password</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" value="<?php echo esc_attr($school_license->student_password); ?>" readonly style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 6px; font-family: monospace; font-size: 16px; background: #f8f9fa;">
                            <button onclick="copyToClipboard('<?php echo esc_js($school_license->student_password); ?>', 'student-password')" style="padding: 12px 20px; background: #E94B3C; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Copy</button>
                        </div>
                        <span id="student-password-copied" style="display: none; color: #28a745; font-size: 14px; margin-top: 5px;">✓ Copied!</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <a href="<?php echo wp_login_url(); ?>" target="_blank" style="display: inline-block; padding: 15px 30px; background: #E94B3C; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                        Login as Student
                    </a>
                    <button onclick="printCredentials('student')" style="padding: 15px 30px; background: white; color: #E94B3C; border: 2px solid #E94B3C; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        Print Credentials
                    </button>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fff9e6; border-left: 4px solid #ffc107; border-radius: 4px;">
            <strong style="color: #856404;">💡 Important:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #856404;">
                <li>Keep these credentials secure and share only with authorized users</li>
                <li>Both teacher and student accounts have the same access level as your school license</li>
                <li>You can login with any of these accounts to access all restricted materials</li>
                <li>Credentials are stored securely and can be accessed anytime from this dashboard</li>
            </ul>
        </div>
        
        <?php endif; ?>
    </div>

</div>

<script>
function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.pz-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.color = '#666';
        btn.style.borderBottomColor = 'transparent';
    });
    
    document.getElementById(tab + '-tab').classList.add('active');
    document.getElementById(tab + '-tab').style.color = tab === 'teacher' ? '#4A90E2' : '#E94B3C';
    document.getElementById(tab + '-tab').style.borderBottomColor = tab === 'teacher' ? '#4A90E2' : '#E94B3C';
    
    // Update tab content
    document.querySelectorAll('.pz-tab-content').forEach(content => {
        content.style.display = 'none';
    });
    
    document.getElementById(tab + '-content').style.display = 'block';
}

function copyToClipboard(text, fieldId) {
    navigator.clipboard.writeText(text).then(() => {
        const copiedMsg = document.getElementById(fieldId + '-copied');
        copiedMsg.style.display = 'inline';
        setTimeout(() => {
            copiedMsg.style.display = 'none';
        }, 2000);
    });
}

function printCredentials(type) {
    const username = type === 'teacher' ? '<?php echo esc_js($school_license->teacher_username ?? ''); ?>' : '<?php echo esc_js($school_license->student_username ?? ''); ?>';
    const password = type === 'teacher' ? '<?php echo esc_js($school_license->teacher_password ?? ''); ?>' : '<?php echo esc_js($school_license->student_password ?? ''); ?>';
    const schoolName = '<?php echo esc_js($school_license->school_name); ?>';
    const accountType = type === 'teacher' ? 'Teacher' : 'Student';
    
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>' + accountType + ' Credentials</title>');
    printWindow.document.write('<style>body { font-family: Arial, sans-serif; padding: 40px; } h1 { color: #333; } .credential { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; } label { font-weight: 600; display: block; margin-bottom: 5px; } .value { font-family: monospace; font-size: 18px; color: #E94B3C; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h1>Psychology Zone - ' + accountType + ' Account</h1>');
    printWindow.document.write('<p><strong>School:</strong> ' + schoolName + '</p>');
    printWindow.document.write('<div class="credential"><label>Username:</label><div class="value">' + username + '</div></div>');
    printWindow.document.write('<div class="credential"><label>Password:</label><div class="value">' + password + '</div></div>');
    printWindow.document.write('<p><small>Keep these credentials secure. Login at: ' + window.location.origin + '</small></p>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<style>
.pz-tab-btn.active {
    color: #4A90E2 !important;
    border-bottom-color: #4A90E2 !important;
}

.pz-tab-btn:hover {
    color: #4A90E2 !important;
}

#student-tab.active {
    color: #E94B3C !important;
    border-bottom-color: #E94B3C !important;
}

#student-tab:hover {
    color: #E94B3C !important;
}
</style>

<?php get_footer(); ?>

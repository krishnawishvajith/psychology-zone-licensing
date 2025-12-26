<?php
/**
 * Template: School Admin Dashboard (Manual Assignment)
 * File: templates/admin-school-dashboard.php
 */

if (!defined('ABSPATH')) exit;

$user_id = get_current_user_id();
$pz_system = PZ_License_System::get_instance();
$school_license = $pz_system->get_user_school_license($user_id);

if (!$school_license) {
    echo '<div class="wrap"><h1>No Active License</h1><p>You do not have an active school license.</p></div>';
    return;
}

$days_remaining = floor((strtotime($school_license->end_date) - time()) / (60 * 60 * 24));

// Get assigned members
global $wpdb;
$teachers = $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, u.display_name, u.user_email, u.user_login 
    FROM {$wpdb->prefix}pz_school_members m
    JOIN {$wpdb->prefix}users u ON m.assigned_user_id = u.ID
    WHERE m.school_license_id = %d AND m.member_type = 'teacher' AND m.status = 'active'
    ORDER BY m.assigned_at DESC",
    $school_license->id
));

$students = $wpdb->get_results($wpdb->prepare(
    "SELECT m.*, u.display_name, u.user_email, u.user_login 
    FROM {$wpdb->prefix}pz_school_members m
    JOIN {$wpdb->prefix}users u ON m.assigned_user_id = u.ID
    WHERE m.school_license_id = %d AND m.member_type = 'student' AND m.status = 'active'
    ORDER BY m.assigned_at DESC",
    $school_license->id
));
?>

<style>
    .pz-admin-wrap {
        max-width: 1400px;
        margin: 20px;
    }
    
    .pz-license-card {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .pz-tabs {
        display: flex;
        gap: 10px;
        border-bottom: 2px solid #ddd;
        margin: 30px 0 0;
    }
    
    .pz-tab-button {
        padding: 15px 30px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        color: #666;
        transition: all 0.3s;
    }
    
    .pz-tab-button:hover {
        color: #4A90E2;
    }
    
    .pz-tab-button.active {
        color: #4A90E2;
        border-bottom-color: #4A90E2;
    }
    
    .pz-tab-content {
        display: none;
        padding: 30px;
        background: white;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .pz-tab-content.active {
        display: block;
    }
    
    .pz-assign-form {
        background: #f9f9f9;
        padding: 25px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    
    .pz-form-row {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    
    .pz-form-group {
        flex: 1;
    }
    
    .pz-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    
    .pz-form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 15px;
    }
    
    .pz-form-group input:focus {
        outline: none;
        border-color: #4A90E2;
    }
    
    .pz-assign-btn {
        padding: 12px 30px;
        background: #4A90E2;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .pz-assign-btn:hover {
        background: #357ABD;
        transform: translateY(-2px);
    }
    
    .pz-members-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .pz-members-table th {
        background: #f8f9fa;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #dee2e6;
    }
    
    .pz-members-table td {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .pz-members-table tr:hover {
        background: #f8f9fa;
    }
    
    .pz-remove-btn {
        padding: 6px 15px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .pz-remove-btn:hover {
        background: #c82333;
    }
    
    .pz-alert {
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .pz-alert-info {
        background: #d1ecf1;
        border: 1px solid #bee5eb;
        color: #0c5460;
    }
    
    .pz-alert-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
    }
    
    .pz-no-members {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    .pz-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .pz-badge-success {
        background: #d4edda;
        color: #155724;
    }
</style>

<div class="wrap pz-admin-wrap">
    <h1><?php echo esc_html($school_license->school_name); ?> - School Dashboard</h1>

    <!-- License Info Section -->
    <div class="pz-license-card">
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
                <td><span class="pz-badge pz-badge-success">ACTIVE</span></td>
            </tr>
            <tr>
                <th>Days Remaining</th>
                <td><strong><?php echo $days_remaining; ?> days</strong></td>
            </tr>
            <tr>
                <th>Expires On</th>
                <td><?php echo date('F j, Y', strtotime($school_license->end_date)); ?></td>
            </tr>
            <tr>
                <th>Assigned Teachers</th>
                <td><strong><?php echo count($teachers); ?></strong> / Unlimited</td>
            </tr>
            <tr>
                <th>Assigned Students</th>
                <td><strong><?php echo count($students); ?></strong> / Unlimited</td>
            </tr>
        </table>
    </div>

    <!-- Member Management Tabs -->
    <div style="margin-top: 30px;">
        <h2>Member Management</h2>

        <div class="pz-alert pz-alert-info">
            <strong>ℹ️ How to assign members:</strong>
            <ol style="margin: 10px 0 0; padding-left: 20px;">
                <li>Users must create an account on the website first</li>
                <li>Enter their registered email address below</li>
                <li>They will automatically get access to study materials</li>
                <li>You can remove members at any time</li>
            </ol>
        </div>

        <div class="pz-tabs">
            <button class="pz-tab-button active" onclick="switchTab('teachers')">
                👨‍🏫 Teachers (<?php echo count($teachers); ?>)
            </button>
            <button class="pz-tab-button" onclick="switchTab('students')">
                👨‍🎓 Students (<?php echo count($students); ?>)
            </button>
        </div>

        <!-- Teachers Tab -->
        <div id="teachers-tab" class="pz-tab-content active">
            <div class="pz-assign-form">
                <h3 style="margin-top: 0;">Assign New Teacher</h3>
                <form id="assign-teacher-form">
                    <div class="pz-form-row">
                        <div class="pz-form-group">
                            <label for="teacher-email">Teacher's Registered Email *</label>
                            <input type="email" id="teacher-email" placeholder="teacher@example.com" required>
                        </div>
                        <div>
                            <button type="submit" class="pz-assign-btn">Assign Teacher</button>
                        </div>
                    </div>
                </form>
                <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                    <strong>Note:</strong> The teacher must have a registered account on this website.
                </p>
            </div>

            <?php if (empty($teachers)): ?>
                <div class="pz-no-members">
                    <p style="font-size: 18px; color: #999;">No teachers assigned yet</p>
                    <p style="font-size: 14px; color: #bbb;">Assign teachers using the form above</p>
                </div>
            <?php else: ?>
                <table class="pz-members-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Assigned On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr data-member-id="<?php echo $teacher->id; ?>">
                                <td><?php echo esc_html($teacher->display_name); ?></td>
                                <td><?php echo esc_html($teacher->user_email); ?></td>
                                <td><code><?php echo esc_html($teacher->user_login); ?></code></td>
                                <td><?php echo date('M j, Y', strtotime($teacher->assigned_at)); ?></td>
                                <td>
                                    <button class="pz-remove-btn remove-member" data-id="<?php echo $teacher->id; ?>" data-type="teacher">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Students Tab -->
        <div id="students-tab" class="pz-tab-content">
            <div class="pz-assign-form">
                <h3 style="margin-top: 0;">Assign New Student</h3>
                <form id="assign-student-form">
                    <div class="pz-form-row">
                        <div class="pz-form-group">
                            <label for="student-email">Student's Registered Email *</label>
                            <input type="email" id="student-email" placeholder="student@example.com" required>
                        </div>
                        <div>
                            <button type="submit" class="pz-assign-btn">Assign Student</button>
                        </div>
                    </div>
                </form>
                <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                    <strong>Note:</strong> The student must have a registered account on this website.
                </p>
            </div>

            <?php if (empty($students)): ?>
                <div class="pz-no-members">
                    <p style="font-size: 18px; color: #999;">No students assigned yet</p>
                    <p style="font-size: 14px; color: #bbb;">Assign students using the form above</p>
                </div>
            <?php else: ?>
                <table class="pz-members-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Assigned On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-member-id="<?php echo $student->id; ?>">
                                <td><?php echo esc_html($student->display_name); ?></td>
                                <td><?php echo esc_html($student->user_email); ?></td>
                                <td><code><?php echo esc_html($student->user_login); ?></code></td>
                                <td><?php echo date('M j, Y', strtotime($student->assigned_at)); ?></td>
                                <td>
                                    <button class="pz-remove-btn remove-member" data-id="<?php echo $student->id; ?>" data-type="student">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Assign Teacher
    $('#assign-teacher-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#teacher-email').val().trim();
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.text();
        
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        
        btn.text('Assigning...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_assign_member',
                nonce: '<?php echo wp_create_nonce('pz_school_nonce'); ?>',
                email: email,
                member_type: 'teacher',
                license_id: <?php echo $school_license->id; ?>
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                btn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Assign Student
    $('#assign-student-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#student-email').val().trim();
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.text();
        
        if (!email) {
            alert('Please enter an email address');
            return;
        }
        
        btn.text('Assigning...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_assign_member',
                nonce: '<?php echo wp_create_nonce('pz_school_nonce'); ?>',
                email: email,
                member_type: 'student',
                license_id: <?php echo $school_license->id; ?>
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ ' + response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                btn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Remove Member
    $('.remove-member').on('click', function() {
        var memberId = $(this).data('id');
        var memberType = $(this).data('type');
        var row = $(this).closest('tr');
        
        if (!confirm('Are you sure you want to remove this ' + memberType + '?')) {
            return;
        }
        
        var btn = $(this);
        var originalText = btn.text();
        btn.text('Removing...').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_remove_member',
                nonce: '<?php echo wp_create_nonce('pz_school_nonce'); ?>',
                member_id: memberId
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        var table = row.closest('table');
                        if (table.find('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Error: ' + response.data.message);
                    btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                btn.text(originalText).prop('disabled', false);
            }
        });
    });
});

function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.pz-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.pz-tab-button').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}
</script>
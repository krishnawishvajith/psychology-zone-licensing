<?php
/**
 * Template: Checkout Page
 * File: templates/checkout.php
 */

if (!defined('ABSPATH')) exit;

get_header();

$package = isset($_GET['package']) ? sanitize_text_field($_GET['package']) : 'student';
$is_school = ($package === 'school');
$price = $is_school ? '199.00' : '49.99';
$package_name = $is_school ? 'School Licence' : 'Student Package';

?>

<div class="pz-checkout-container">
    <div class="pz-checkout-header">
        <h1>Checkout</h1>
        <p>Complete your purchase for <?php echo esc_html($package_name); ?></p>
    </div>
    
    <div class="pz-checkout-grid">
        <div class="pz-checkout-left">
            
            <?php if ($is_school): ?>
            <!-- School Package Checkout -->
            <h2>School Account Information</h2>
            
            <div class="pz-form-tabs">
                <button class="pz-tab-btn active" data-tab="new-account">Create New Account</button>
                <button class="pz-tab-btn" data-tab="existing-account">I Have an Account</button>
            </div>
            
            <form id="pz-school-checkout-form" method="post">
                <input type="hidden" name="package_type" value="school">
                <input type="hidden" name="pz_nonce" value="<?php echo wp_create_nonce('pz_checkout'); ?>">
                
                <!-- New Account Tab -->
                <div class="pz-tab-content active" id="new-account">
                    <div class="pz-form-group">
                        <label for="school_name">School Name *</label>
                        <input type="text" id="school_name" name="school_name" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="school_email">School Email Address *</label>
                        <input type="email" id="school_email" name="school_email" required>
                        <small>This will be your login email</small>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="school_password">Password *</label>
                        <input type="password" id="school_password" name="school_password" required minlength="8">
                        <small>Minimum 8 characters</small>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="school_password_confirm">Confirm Password *</label>
                        <input type="password" id="school_password_confirm" name="school_password_confirm" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="contact_person">Contact Person Name *</label>
                        <input type="text" id="contact_person" name="contact_person" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                
                <!-- Existing Account Tab -->
                <div class="pz-tab-content" id="existing-account">
                    <div class="pz-form-group">
                        <label for="existing_email">Email Address *</label>
                        <input type="email" id="existing_email" name="existing_email">
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="existing_password">Password *</label>
                        <input type="password" id="existing_password" name="existing_password">
                    </div>
                    
                    <div class="pz-form-group">
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="pz-forgot-password">Forgot password?</a>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="assign_school_name">School Name for This Licence *</label>
                        <input type="text" id="assign_school_name" name="assign_school_name">
                        <small>You can manage multiple schools from one account</small>
                    </div>
                </div>
                
            <?php else: ?>
            <!-- Student Package Checkout -->
            <h2>Student Account Information</h2>
            
            <div class="pz-form-tabs">
                <button class="pz-tab-btn active" data-tab="new-account">Create New Account</button>
                <button class="pz-tab-btn" data-tab="existing-account">I Have an Account</button>
            </div>
            
            <form id="pz-student-checkout-form" method="post">
                <input type="hidden" name="package_type" value="student">
                <input type="hidden" name="pz_nonce" value="<?php echo wp_create_nonce('pz_checkout'); ?>">
                
                <!-- New Account Tab -->
                <div class="pz-tab-content active" id="new-account">
                    <div class="pz-form-group">
                        <label for="student_email">Email Address *</label>
                        <input type="email" id="student_email" name="student_email" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="student_password">Password *</label>
                        <input type="password" id="student_password" name="student_password" required minlength="8">
                        <small>Minimum 8 characters</small>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="student_password_confirm">Confirm Password *</label>
                        <input type="password" id="student_password_confirm" name="student_password_confirm" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <!-- Existing Account Tab -->
                <div class="pz-tab-content" id="existing-account">
                    <div class="pz-form-group">
                        <label for="existing_email">Email Address *</label>
                        <input type="email" id="existing_email" name="existing_email">
                    </div>
                    
                    <div class="pz-form-group">
                        <label for="existing_password">Password *</label>
                        <input type="password" id="existing_password" name="existing_password">
                    </div>
                    
                    <div class="pz-form-group">
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="pz-forgot-password">Forgot password?</a>
                    </div>
                </div>
                
            <?php endif; ?>
                
                <h3 style="margin-top: 40px; margin-bottom: 20px;">Payment Method</h3>
                
                <div class="pz-payment-methods">
                    <div class="pz-payment-option" data-method="paypal">
                        <input type="radio" name="payment_method" value="paypal" id="payment_paypal" required>
                        <label for="payment_paypal">
                            <strong>PayPal</strong><br>
                            <small>Pay securely with PayPal or credit card</small>
                        </label>
                    </div>
                    
                    <div class="pz-payment-option" data-method="stripe">
                        <input type="radio" name="payment_method" value="stripe" id="payment_stripe">
                        <label for="payment_stripe">
                            <strong>Credit/Debit Card (Stripe)</strong><br>
                            <small>Pay with Visa, Mastercard, or Amex</small>
                        </label>
                    </div>
                </div>
                
                <div class="pz-form-group" style="margin-top: 30px;">
                    <label>
                        <input type="checkbox" name="terms" required>
                        I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="pz-submit-btn">
                    Complete Purchase - £<?php echo esc_html($price); ?>
                </button>
            </form>
            
        </div>
        
        <div class="pz-checkout-right">
            <div class="pz-order-summary">
                <h3>Order Summary</h3>
                
                <div class="pz-order-item">
                    <span><?php echo esc_html($package_name); ?></span>
                    <span>£<?php echo esc_html($price); ?></span>
                </div>
                
                <?php if ($is_school): ?>
                <div class="pz-order-item">
                    <span>Unlimited Students</span>
                    <span>✓</span>
                </div>
                <div class="pz-order-item">
                    <span>Unlimited Teachers</span>
                    <span>✓</span>
                </div>
                <?php endif; ?>
                
                <div class="pz-order-item">
                    <span>12 Months Access</span>
                    <span>✓</span>
                </div>
                
                <div class="pz-order-total">
                    <span>Total</span>
                    <span>£<?php echo esc_html($price); ?></span>
                </div>
            </div>
            
            <div class="pz-security-badges" style="text-align: center; padding: 20px;">
                <p style="color: #666; font-size: 14px;">
                    <svg style="width: 20px; height: 20px; vertical-align: middle;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Secure payment processing
                </p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.pz-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        $('.pz-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.pz-tab-content').removeClass('active');
        $('#' + tab).addClass('active');
    });
    
    // Payment method selection
    $('.pz-payment-option').on('click', function() {
        $('.pz-payment-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    // Form submission
    $('#pz-school-checkout-form, #pz-student-checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitBtn = $(this).find('.pz-submit-btn');
        var originalText = submitBtn.text();
        
        submitBtn.text('Processing...').prop('disabled', true);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData + '&action=pz_process_checkout',
            success: function(response) {
                if (response.success) {
                    if (response.data.payment_url) {
                        window.location.href = response.data.payment_url;
                    } else {
                        alert('Payment processing initiated');
                    }
                } else {
                    alert('Error: ' + response.data.message);
                    submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>
/**
 * Psychology Zone Licensing Plugin Scripts
 * File: assets/js/scripts.js
 */

jQuery(document).ready(function($) {
    
    // Package selection - Enroll buttons
    $('.pz-enroll-btn').on('click', function(e) {
        e.preventDefault();
        var package = $(this).data('package');
        var checkoutUrl = pzLicense.checkout_url + '?package=' + package;
        window.location.href = checkoutUrl;
    });
    
    // Checkout page - Tab switching
    $('.pz-tab-btn').on('click', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        $('.pz-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.pz-tab-content').removeClass('active');
        $('#' + tab).addClass('active');
        
        // Update required fields based on active tab
        updateRequiredFields(tab);
    });
    
    // Payment method selection
    $('.pz-payment-option').on('click', function() {
        $('.pz-payment-option').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
    
    // Update required fields based on tab
    function updateRequiredFields(tab) {
        if (tab === 'new-account') {
            $('#new-account input').prop('required', true);
            $('#existing-account input').prop('required', false);
        } else {
            $('#new-account input').prop('required', false);
            $('#existing-account input').prop('required', true);
        }
    }
    
    // Form validation
    function validateForm($form) {
        var isValid = true;
        var errorMessage = '';
        
        // Get active tab
        var activeTab = $form.find('.pz-tab-content.active').attr('id');
        
        if (activeTab === 'new-account') {
            // Validate new account
            var password = $form.find('#school_password, #student_password').val();
            var confirmPassword = $form.find('#school_password_confirm, #student_password_confirm').val();
            
            if (password && confirmPassword && password !== confirmPassword) {
                isValid = false;
                errorMessage = 'Passwords do not match!';
            }
            
            if (password && password.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters long!';
            }
        }
        
        // Validate payment method
        if (!$form.find('input[name="payment_method"]:checked').length) {
            isValid = false;
            errorMessage = 'Please select a payment method!';
        }
        
        // Validate terms
        if (!$form.find('input[name="terms"]').is(':checked')) {
            isValid = false;
            errorMessage = 'Please agree to the terms and conditions!';
        }
        
        if (!isValid) {
            alert(errorMessage);
        }
        
        return isValid;
    }
    
    // Form submission handler
    $('#pz-school-checkout-form, #pz-student-checkout-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        
        // Validate form
        if (!validateForm($form)) {
            return false;
        }
        
        // Get the active tab to send to server
        var activeTab = $form.find('.pz-tab-content.active').attr('id');
        
        var formData = $form.serialize() + '&active_tab=' + activeTab;
        var submitBtn = $form.find('.pz-submit-btn');
        var originalText = submitBtn.text();
        
        // Disable button and show processing state
        submitBtn.text('Processing...').prop('disabled', true);
        
        // Submit via AJAX
        $.ajax({
            url: pzLicense.ajaxurl,
            type: 'POST',
            data: formData + '&action=pz_process_checkout',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    if (response.data.message) {
                        submitBtn.text(response.data.message);
                    }
                    
                    // Redirect to payment
                    if (response.data.payment_url) {
                        setTimeout(function() {
                            window.location.href = response.data.payment_url;
                        }, 500);
                    }
                } else {
                    // Show error
                    alert('Error: ' + (response.data.message || 'Something went wrong'));
                    submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                alert('An error occurred. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
            }
        });
        
        return false;
    });
    
    // Auto-dismiss success messages
    setTimeout(function() {
        $('.pz-success-message').fadeOut();
    }, 5000);
    
});
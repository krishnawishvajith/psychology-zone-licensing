/**
 * Flipbooks Admin Scripts (FIXED)
 * File: assets/js/flipbooks-admin.js
 */

jQuery(document).ready(function($) {
    
    // Add flipbook form submission
    $('#pz-add-flipbook-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Get form values
        var title = $('#flipbook_title').val().trim();
        var description = $('#flipbook_description').val().trim();
        var flipbook_url = $('#flipbook_url').val().trim();
        var access_type = $('#access_type').val();
        var sort_order = $('#sort_order').val();
        
        // Validate required fields
        if (!title) {
            alert('Please enter a title');
            return;
        }
        
        if (!flipbook_url) {
            alert('Please enter a flipbook URL or embed code');
            return;
        }
        
        // Disable button
        $submitBtn.text('Adding...').prop('disabled', true);
        
        // Debug log
        console.log('Submitting flipbook:', {
            title: title,
            description: description,
            flipbook_url: flipbook_url,
            access_type: access_type,
            sort_order: sort_order
        });
        
        $.ajax({
            url: pzFlipbooks.ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_save_flipbook',
                nonce: pzFlipbooks.nonce,
                title: title,
                description: description,
                flipbook_url: flipbook_url,
                access_type: access_type,
                sort_order: sort_order
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                
                if (response.success) {
                    alert('✓ Flipbook added successfully!');
                    location.reload();
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred';
                    alert('Error: ' + errorMsg);
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                
                // Try to parse error message
                var errorMsg = 'An error occurred. Please try again.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                } catch(e) {
                    // Use default error message
                }
                
                alert(errorMsg);
                $submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Delete flipbook
    $('.pz-delete-flipbook').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this flipbook?')) {
            return;
        }
        
        var $btn = $(this);
        var flipbookId = $btn.data('id');
        var originalText = $btn.text();
        
        $btn.text('Deleting...').prop('disabled', true);
        
        $.ajax({
            url: pzFlipbooks.ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_delete_flipbook',
                nonce: pzFlipbooks.nonce,
                id: flipbookId
            },
            success: function(response) {
                if (response.success) {
                    $btn.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                    alert('✓ Flipbook deleted successfully!');
                } else {
                    alert('Error: ' + response.data.message);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('An error occurred. Please try again.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
    
});
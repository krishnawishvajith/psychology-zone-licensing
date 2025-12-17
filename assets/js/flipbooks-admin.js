/**
 * Flipbooks Admin Scripts
 * File: assets/js/flipbooks-admin.js
 */

jQuery(document).ready(function($) {
    
    // Add flipbook form submission
    $('#pz-add-flipbook-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        // Disable button
        $submitBtn.text('Adding...').prop('disabled', true);
        
        $.ajax({
            url: pzFlipbooks.ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_save_flipbook',
                nonce: pzFlipbooks.nonce,
                title: $('#flipbook_title').val(),
                description: $('#flipbook_description').val(),
                flipbook_url: $('#flipbook_url').val(),
                access_type: $('#access_type').val(),
                sort_order: $('#sort_order').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('✓ Flipbook added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                alert('An error occurred. Please try again.');
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
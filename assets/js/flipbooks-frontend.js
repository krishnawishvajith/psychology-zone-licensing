/**
 * Flipbooks Frontend Scripts (Protected Loading)
 * File: assets/js/flipbooks-frontend.js
 */

jQuery(document).ready(function($) {
    
    // Open flipbook modal
    $('.pz-open-flipbook-btn').on('click', function(e) {
        e.preventDefault();
        
        var flipbookId = $(this).data('id');
        var flipbookTitle = $(this).closest('.pz-flipbook-card').find('h3').text();
        
        // Show modal
        $('#pz-flipbook-modal').fadeIn(300);
        $('#pz-flipbook-modal-title').text(flipbookTitle);
        $('#pz-flipbook-loading').show();
        $('#pz-flipbook-content').hide().html('');
        
        // Disable body scroll
        $('body').css('overflow', 'hidden');
        
        // Load flipbook content via AJAX (protected)
        $.ajax({
            url: pzFlipbooks.ajaxurl,
            type: 'POST',
            data: {
                action: 'pz_load_flipbook',
                nonce: pzFlipbooks.nonce,
                flipbook_id: flipbookId
            },
            success: function(response) {
                $('#pz-flipbook-loading').hide();
                
                if (response.success) {
                    var content = response.data.content;
                    
                    // Check if content is a URL or embed code
                    if (content.startsWith('http://') || content.startsWith('https://')) {
                        // It's a URL - create iframe
                        var iframe = '<iframe src="' + content + '" frameborder="0" allowfullscreen="true" style="width: 100%; height: 100%; border: none;"></iframe>';
                        $('#pz-flipbook-content').html(iframe);
                    } else {
                        // It's embed code - use as is
                        $('#pz-flipbook-content').html(content);
                    }
                    
                    $('#pz-flipbook-content').fadeIn(300);
                    
                    // Add anti-download protection
                    protectFlipbookContent();
                    
                } else {
                    $('#pz-flipbook-content').html(
                        '<div style="text-align: center; padding: 60px; color: #999;">' +
                        '<svg style="width: 64px; height: 64px; margin-bottom: 20px;" fill="currentColor" viewBox="0 0 20 20">' +
                        '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>' +
                        '</svg>' +
                        '<h3>Access Denied</h3>' +
                        '<p>' + response.data.message + '</p>' +
                        '</div>'
                    ).fadeIn(300);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#pz-flipbook-loading').hide();
                $('#pz-flipbook-content').html(
                    '<div style="text-align: center; padding: 60px; color: #999;">' +
                    '<h3>Error Loading Flipbook</h3>' +
                    '<p>Please try again or contact support.</p>' +
                    '</div>'
                ).fadeIn(300);
            }
        });
    });
    
    // Close modal
    $('.pz-modal-close, .pz-modal-overlay').on('click', function() {
        $('#pz-flipbook-modal').fadeOut(300);
        $('#pz-flipbook-content').html('');
        $('body').css('overflow', '');
    });
    
    // Close on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#pz-flipbook-modal').is(':visible')) {
            $('#pz-flipbook-modal').fadeOut(300);
            $('#pz-flipbook-content').html('');
            $('body').css('overflow', '');
        }
    });
    
    /**
     * Anti-download protection for flipbook content
     */
    function protectFlipbookContent() {
        var $content = $('#pz-flipbook-content');
        
        // Disable right-click
        $content.on('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable text selection
        $content.css({
            '-webkit-user-select': 'none',
            '-moz-user-select': 'none',
            '-ms-user-select': 'none',
            'user-select': 'none'
        });
        
        // Disable drag
        $content.on('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable keyboard shortcuts (Ctrl+S, Ctrl+P, etc.)
        $content.on('keydown', function(e) {
            // Ctrl+S (Save)
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                return false;
            }
            // Ctrl+P (Print)
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                return false;
            }
            // F12 (DevTools)
            if (e.key === 'F12') {
                e.preventDefault();
                return false;
            }
            // Ctrl+U (View Source)
            if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                return false;
            }
        });
        
        // Apply same protections to iframes
        $content.find('iframe').on('load', function() {
            try {
                var iframe = this;
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                
                // Try to apply protections to iframe content
                $(iframeDoc).on('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
            } catch(e) {
                // Cross-origin iframes will throw error, which is fine
                // The iframe's own domain should handle protection
                console.log('Iframe is from different origin, protection applied at iframe level');
            }
        });
    }
    
});
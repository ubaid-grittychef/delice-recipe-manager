/**
 * Clean Empty Notices - Proper Solution
 * Only removes notices with no visible content
 */
(function($) {
    'use strict';
    
    function cleanEmptyNotices() {
        // Find all WordPress notice divs
        $('.notice, .updated, .error').each(function() {
            var $notice = $(this);
            
            // Skip our own notices
            if ($notice.hasClass('delice-notice') || 
                $notice.hasClass('delice-notification') || 
                $notice.hasClass('delice-admin-notice')) {
                return;
            }
            
            // Get text content (trimmed)
            var text = $.trim($notice.text());
            
            // Check if it has any content
            var hasImages = $notice.find('img, svg, .dashicons').length > 0;
            var hasButtons = $notice.find('button, a').length > 0;
            
            // Only remove if completely empty
            if (text.length === 0 && !hasImages && !hasButtons) {
                $notice.remove();
            }
        });
    }
    
    // Run on DOM ready
    $(document).ready(function() {
        cleanEmptyNotices();
        
        // Run again after a short delay to catch late additions
        setTimeout(cleanEmptyNotices, 500);
    });
    
})(jQuery);

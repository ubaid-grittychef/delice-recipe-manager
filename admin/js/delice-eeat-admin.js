/**
 * E-E-A-T Admin JavaScript
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Auto-refresh stats on E-E-A-T dashboard
        if ($('.delice-eeat-dashboard').length) {
            refreshStats();
        }
        
        // Handle AJAX responses
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.data && settings.data.indexOf('delice_') !== -1) {
                if (xhr.responseJSON && xhr.responseJSON.success) {
                    showNotice('success', xhr.responseJSON.data.message || 'Success!');
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    showNotice('error', xhr.responseJSON.data.message || 'Error occurred');
                }
            }
        });
    });
    
    /**
     * Refresh dashboard statistics
     */
    function refreshStats() {
        $.post(ajaxurl, {
            action: 'delice_get_eeat_stats',
            nonce: deliceEEAT.nonce
        }, function(response) {
            if (response.success && response.data) {
                updateStatCards(response.data);
            }
        });
    }
    
    /**
     * Update stat cards with fresh data
     */
    function updateStatCards(stats) {
        $('.eeat-stat-card').each(function() {
            var $card = $(this);
            var statKey = $card.data('stat');
            if (stats[statKey] !== undefined) {
                $card.find('.eeat-stat-value').text(stats[statKey]);
            }
        });
    }
    
    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        var $notice = $('<div>', {
            'class': 'notice notice-' + type + ' is-dismissible',
            html: '<p>' + message + '</p>'
        });
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Make functions globally available
    window.deliceEEAT = window.deliceEEAT || {};
    window.deliceEEAT.refreshStats = refreshStats;
    window.deliceEEAT.showNotice = showNotice;
    
})(jQuery);

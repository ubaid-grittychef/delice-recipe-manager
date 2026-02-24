/**
 * Delice Recipe Manager - Modern Admin JavaScript
 * Handles all admin dashboard interactions
 */

(function($) {
    'use strict';
    
    const DeliceAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.tabSwitching();
            this.toggleSwitches();
            this.languageCards();
            this.saveSettings();
            this.loadDashboardStats();
            this.reviewToggle();
        },
        
        /**
         * Tab Switching
         */
        tabSwitching: function() {
            $('.delice-admin-tab').on('click', function() {
                const targetPane = $(this).data('pane');
                
                // Update tabs
                $('.delice-admin-tab').removeClass('active');
                $(this).addClass('active');
                
                // Update panes
                $('.delice-admin-pane').removeClass('active');
                $('#' + targetPane).addClass('active');
                
                // Update URL hash without scrolling
                if (history.pushState) {
                    history.pushState(null, null, '#' + targetPane);
                }
            });
            
            // Load initial tab from hash
            const hash = window.location.hash.substring(1);
            if (hash) {
                $('.delice-admin-tab[data-pane="' + hash + '"]').trigger('click');
            }
        },
        
        /**
         * Toggle Switches
         */
        toggleSwitches: function() {
            $(document).on('click', '.delice-toggle', function() {
                $(this).toggleClass('active');
                const isActive = $(this).hasClass('active');
                const settingName = $(this).data('setting');
                
                if (settingName) {
                    // Auto-save toggle state
                    DeliceAdmin.saveToggleSetting(settingName, isActive);
                }
            });
        },
        
        /**
         * Language Cards Selection
         */
        languageCards: function() {
            $(document).on('click', '.delice-lang-card', function() {
                $(this).toggleClass('selected');
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', $(this).hasClass('selected'));
            });
        },
        
        /**
         * Save Settings
         */
        saveSettings: function() {
            $('.delice-save-settings').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const originalText = $button.text();
                const section = $button.data('section');
                
                // Show loading
                $button.html('<span class="delice-loading"></span> Saving...').prop('disabled', true);
                
                // Gather all form data from the section
                const formData = {};
                $('#' + section + ' input, #' + section + ' select, #' + section + ' textarea').each(function() {
                    const $field = $(this);
                    const name = $field.attr('name');
                    
                    if (name) {
                        if ($field.is(':checkbox')) {
                            formData[name] = $field.is(':checked') ? 1 : 0;
                        } else {
                            formData[name] = $field.val();
                        }
                    }
                });
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delice_save_settings',
                        nonce: deliceAdmin.nonce,
                        section: section,
                        settings: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            DeliceAdmin.showNotice('success', response.data.message || 'Settings saved successfully!');
                        } else {
                            DeliceAdmin.showNotice('error', response.data.message || 'Failed to save settings.');
                        }
                    },
                    error: function() {
                        DeliceAdmin.showNotice('error', 'An error occurred. Please try again.');
                    },
                    complete: function() {
                        $button.html(originalText).prop('disabled', false);
                    }
                });
            });
        },
        
        /**
         * Save Toggle Setting
         */
        saveToggleSetting: function(settingName, value) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_save_toggle',
                    nonce: deliceAdmin.nonce,
                    setting: settingName,
                    value: value ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                    }
                }
            });
        },
        
        /**
         * Load Dashboard Stats
         */
        loadDashboardStats: function() {
            // Load real stats from backend
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_get_dashboard_stats',
                    nonce: deliceAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const stats = response.data;
                        
                        // Update stat cards
                        $('.delice-stat-total').text(stats.total || 0);
                        $('.delice-stat-published').text(stats.published || 0);
                        $('.delice-stat-drafts').text(stats.drafts || 0);
                        $('.delice-stat-views').text(stats.views || 0);
                        
                        // Update trends
                        if (stats.trends) {
                            $('.delice-trend-total').text(stats.trends.total || '');
                            $('.delice-trend-published').text(stats.trends.published || '');
                        }
                    }
                }
            });
        },
        
        /**
         * Review System Toggle
         */
        reviewToggle: function() {
            $('#reviews-toggle').on('change', function() {
                const isEnabled = $(this).is(':checked');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delice_toggle_reviews',
                        nonce: deliceAdmin.nonce,
                        enabled: isEnabled ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            DeliceAdmin.showNotice('success', 'Review system ' + (isEnabled ? 'enabled' : 'disabled'));
                        }
                    }
                });
            });
        },
        
        /**
         * Show Notice
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="delice-notice delice-notice-' + type + '">' + message + '</div>');
            $('.delice-admin-content').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.delice-admin-wrap').length) {
            DeliceAdmin.init();
        }
    });
    
})(jQuery);

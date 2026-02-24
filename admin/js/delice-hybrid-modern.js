/**
 * Delice Recipe Manager - Hybrid Modern JavaScript
 * Handles all interactions, notifications, and AJAX
 */

(function($) {
    'use strict';
    
    const DeliceHybrid = {
        
        /**
         * Initialize
         */
        init: function() {
            this.notifications();
            this.aiGenerator();
            this.settingsHub();
        },
        
        /**
         * Notification System
         */
        notifications: function() {
            // Create notification container if it doesn't exist (hidden by default)
            if ($('#delice-notification').length === 0) {
                $('body').append(`
                    <div class="delice-notification" id="delice-notification" style="display: none;">
                        <div class="delice-notification-icon"></div>
                        <div class="delice-notification-content">
                            <div class="delice-notification-title"></div>
                            <div class="delice-notification-message"></div>
                        </div>
                        <div class="delice-notification-close">×</div>
                    </div>
                `);
            }
            
            // Close button
            $(document).on('click', '.delice-notification-close', function() {
                DeliceHybrid.hideNotification();
            });
        },
        
        /**
         * Show Notification
         */
        showNotification: function(message, type = 'success') {
            const $notification = $('#delice-notification');
            const $icon = $notification.find('.delice-notification-icon');
            const $title = $notification.find('.delice-notification-title');
            const $message = $notification.find('.delice-notification-message');
            
            // Set content
            if (type === 'success') {
                $icon.text('✅');
                $title.text('Success!');
                $notification.removeClass('error');
            } else {
                $icon.text('❌');
                $title.text('Error!');
                $notification.addClass('error');
            }
            $message.text(message);
            
            // Show notification
            $notification.css('display', 'block');
            $notification.addClass('show');
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                DeliceHybrid.hideNotification();
            }, 3000);
        },
        
        /**
         * Hide Notification
         */
        hideNotification: function() {
            const $notification = $('#delice-notification');
            $notification.css('animation', 'deliceSlideOut 0.3s ease-out');
            setTimeout(function() {
                $notification.removeClass('show');
                $notification.css('animation', '');
                $notification.css('display', 'none');
            }, 300);
        },
        
        /**
         * AI Generator Page
         */
        aiGenerator: function() {
            // Mode switching
            $('.delice-mode-tab').on('click', function() {
                const mode = $(this).data('mode');
                
                // Update tabs
                $('.delice-mode-tab').removeClass('active');
                $(this).addClass('active');
                
                // Update content
                $('.delice-mode-content').hide();
                $('#' + mode + '-mode').show();
            });
            
            // Tag input
            $('#keyword-input').on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const value = $(this).val().trim();
                    if (value) {
                        DeliceHybrid.addTag(value);
                        $(this).val('');
                    }
                }
            });
            
            // Click on tags container to focus input
            $('#keywords-tags').on('click', function(e) {
                if (e.target === this) {
                    $('#keyword-input').focus();
                }
            });
            
            // Form submission
            $('#delice-ai-form').on('submit', function(e) {
                e.preventDefault();
                DeliceHybrid.generateRecipe($(this));
            });
        },
        
        /**
         * Add Tag
         */
        addTag: function(text) {
            const $container = $('#keywords-tags');
            const $tag = $('<span class="delice-tag">' + text + ' <span class="delice-tag-remove">×</span></span>');
            
            // Remove handler
            $tag.find('.delice-tag-remove').on('click', function() {
                $tag.remove();
                DeliceHybrid.updateKeywordsHidden();
            });
            
            // Insert before input
            $container.find('#keyword-input').before($tag);
            DeliceHybrid.updateKeywordsHidden();
        },
        
        /**
         * Update hidden keywords field
         */
        updateKeywordsHidden: function() {
            const keywords = [];
            $('#keywords-tags .delice-tag').each(function() {
                const text = $(this).text().replace('×', '').trim();
                keywords.push(text);
            });
            $('#keywords-hidden').val(keywords.join(','));
        },
        
        /**
         * Generate Recipe
         */
        generateRecipe: function($form) {
            const formData = new FormData($form[0]);
            formData.append('action', 'delice_generate_recipe');
            formData.append('nonce', deliceHybridData.nonce);
            
            // Show progress
            $('#empty-state').hide();
            $('#progress-container').show();
            
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        DeliceHybrid.showRecipeResult(response.data);
                        DeliceHybrid.showNotification('Recipe generated successfully! 🎉', 'success');
                    } else {
                        DeliceHybrid.showNotification(response.data.message || 'Failed to generate recipe', 'error');
                        $('#progress-container').hide();
                        $('#empty-state').show();
                    }
                },
                error: function() {
                    DeliceHybrid.showNotification('An error occurred. Please try again.', 'error');
                    $('#progress-container').hide();
                    $('#empty-state').show();
                }
            });
        },
        
        /**
         * Show Recipe Result
         */
        showRecipeResult: function(recipe) {
            $('#progress-container').hide();
            
            const $card = $(`
                <div class="delice-recipe-card">
                    <div class="delice-recipe-header">
                        <h3 class="delice-recipe-title">${recipe.title}</h3>
                        <div class="delice-recipe-actions">
                            <button class="delice-btn delice-btn-success delice-btn-sm delice-view-recipe" data-id="${recipe.id}" data-title="${recipe.title}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                View
                            </button>
                            <button class="delice-btn delice-btn-primary delice-btn-sm delice-edit-recipe" data-id="${recipe.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                Edit
                            </button>
                            <button class="delice-btn delice-btn-warning delice-btn-sm delice-migrate-recipe" data-id="${recipe.id}" data-title="${recipe.title}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                </svg>
                                Migrate
                            </button>
                        </div>
                    </div>
                    <div class="delice-recipe-content">
                        ${recipe.description || ''}
                    </div>
                </div>
            `);
            
            $('#recipe-results').html($card).show();
            
            // Attach click handlers to the newly created buttons
            $('.delice-view-recipe').on('click', function(e) {
                e.preventDefault();
                const recipeId = $(this).data('id');
                const recipeTitle = $(this).data('title');
                DeliceHybrid.viewRecipe(recipeId, recipeTitle);
            });
            
            $('.delice-edit-recipe').on('click', function(e) {
                e.preventDefault();
                const recipeId = $(this).data('id');
                DeliceHybrid.editRecipe(recipeId);
            });
            
            $('.delice-migrate-recipe').on('click', function(e) {
                e.preventDefault();
                const recipeId = $(this).data('id');
                const recipeTitle = $(this).data('title');
                DeliceHybrid.migrateRecipe(recipeId, recipeTitle);
            });
        },
        
        /**
         * View Recipe (opens public view in new tab)
         */
        viewRecipe: function(recipeId, recipeTitle) {
            if (!recipeId) {
                DeliceHybrid.showNotification('Recipe ID not found', 'error');
                return;
            }
            
            DeliceHybrid.showNotification('Opening recipe preview...', 'success');
            
            // Use WordPress preview URL (works for both draft and published)
            const previewUrl = deliceHybridData.adminUrl + '../?p=' + recipeId + '&post_type=delice_recipe&preview=true';
            
            setTimeout(function() {
                window.open(previewUrl, '_blank');
            }, 300);
        },
        
        /**
         * Save Recipe (deprecated - now using View)
         */
        saveRecipe: function(recipeId) {
            // Redirect to edit
            this.editRecipe(recipeId);
        },
        
        /**
         * Edit Recipe
         */
        editRecipe: function(recipeId) {
            if (!recipeId) {
                DeliceHybrid.showNotification('Recipe ID not found', 'error');
                return;
            }
            
            // Open recipe in WordPress editor
            const editUrl = deliceHybridData.adminUrl + 'post.php?post=' + recipeId + '&action=edit';
            DeliceHybrid.showNotification('Opening editor...', 'success');
            
            setTimeout(function() {
                window.open(editUrl, '_blank');
            }, 300);
        },
        
        /**
         * Migrate Recipe
         */
        migrateRecipe: function(recipeId, recipeTitle) {
            if (!recipeId) {
                DeliceHybrid.showNotification('Recipe ID not found', 'error');
                return;
            }
            
            if (!confirm('Migrate "' + recipeTitle + '" to WordPress post? This will create a standard WordPress post with all recipe data.')) {
                return;
            }
            
            DeliceHybrid.showNotification('Migrating recipe...', 'success');
            
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_migrate_single_recipe',
                    recipe_id: recipeId,
                    nonce: deliceHybridData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeliceHybrid.showNotification('Recipe migrated successfully! Opening migrated post...', 'success');
                        
                        // Open migrated post in editor
                        if (response.data.new_post_id) {
                            setTimeout(function() {
                                window.open(deliceHybridData.adminUrl + 'post.php?post=' + response.data.new_post_id + '&action=edit', '_blank');
                            }, 1000);
                        }
                    } else {
                        DeliceHybrid.showNotification(response.data.message || 'Migration failed', 'error');
                    }
                },
                error: function() {
                    DeliceHybrid.showNotification('An error occurred during migration', 'error');
                }
            });
        },
        
        /**
         * Settings Hub Page
         */
        settingsHub: function() {
            // Navigation
            $('.delice-nav-item').on('click', function(e) {
                e.preventDefault();
                const section = $(this).data('section');
                
                // Update nav
                $('.delice-nav-item').removeClass('active');
                $(this).addClass('active');
                
                // Update content
                $('.delice-section-content').hide();
                $('#' + section + '-section').show();
                
                // Update URL hash
                if (history.pushState) {
                    history.pushState(null, null, '#' + section);
                }
            });
            
            // Load section from hash
            const hash = window.location.hash.substring(1);
            if (hash) {
                $('.delice-nav-item[data-section="' + hash + '"]').trigger('click');
            }
            
            // Toggle switches
            $(document).on('click', '.delice-toggle', function() {
                $(this).toggleClass('active');
                const setting = $(this).data('setting');
                const value = $(this).hasClass('active') ? 1 : 0;
                
                if (setting) {
                    DeliceHybrid.saveSetting(setting, value);
                }
            });
            
            // Language radio selection
            $('.delice-lang-radio-card').on('click', function() {
                // Remove selected from all
                $('.delice-lang-radio-card').removeClass('selected');
                
                // Add selected to this
                $(this).addClass('selected');
                
                // Check radio
                $(this).find('input[type="radio"]').prop('checked', true);
                
                // Update translation header
                const langName = $(this).find('.delice-lang-radio-label > div').first().text();
                $('.delice-translation-header').text(langName + ' - Text Labels');
                
                // Load translations for this language
                const langCode = $(this).find('input[type="radio"]').val();
                DeliceHybrid.loadTranslations(langCode);
            });
            
            // Save all button
            $('.delice-btn-save-all').on('click', function(e) {
                e.preventDefault();
                DeliceHybrid.saveAllSettings();
            });
            
            // Save individual sections
            $('.delice-save-section').on('click', function(e) {
                e.preventDefault();
                const section = $(this).data('section');
                DeliceHybrid.saveSection(section);
            });
        },
        
        /**
         * Save Single Setting
         */
        saveSetting: function(setting, value) {
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_save_setting',
                    setting: setting,
                    value: value,
                    nonce: deliceHybridData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Silent save - no notification for toggles
                    }
                }
            });
        },
        
        /**
         * Save All Settings
         */
        saveAllSettings: function() {
            const settings = {};
            
            // Gather all form data
            $('.delice-main-content input, .delice-main-content select, .delice-main-content textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    if ($(this).is(':checkbox') || $(this).is(':radio')) {
                        if ($(this).is(':checked')) {
                            settings[name] = $(this).val();
                        }
                    } else {
                        settings[name] = $(this).val();
                    }
                }
            });
            
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_save_all_settings',
                    settings: settings,
                    nonce: deliceHybridData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeliceHybrid.showNotification('All settings saved successfully!', 'success');
                    } else {
                        DeliceHybrid.showNotification(response.data.message || 'Failed to save settings', 'error');
                    }
                },
                error: function() {
                    DeliceHybrid.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        /**
         * Save Section
         */
        saveSection: function(section) {
            const settings = {};
            
            // Gather section data
            $('#' + section + '-section input, #' + section + '-section select, #' + section + '-section textarea').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    if ($(this).is(':checkbox') || $(this).is(':radio')) {
                        if ($(this).is(':checked')) {
                            settings[name] = $(this).val();
                        }
                    } else {
                        settings[name] = $(this).val();
                    }
                }
            });
            
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_save_section',
                    section: section,
                    settings: settings,
                    nonce: deliceHybridData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        DeliceHybrid.showNotification('Settings saved successfully!', 'success');
                    } else {
                        DeliceHybrid.showNotification(response.data.message || 'Failed to save settings', 'error');
                    }
                },
                error: function() {
                    DeliceHybrid.showNotification('An error occurred. Please try again.', 'error');
                }
            });
        },
        
        /**
         * Load Translations
         */
        loadTranslations: function(langCode) {
            $.ajax({
                url: deliceHybridData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_get_translations',
                    language: langCode,
                    nonce: deliceHybridData.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Populate translation fields
                        $.each(response.data, function(key, value) {
                            $('.delice-translation-field input[name="translations[' + key + ']"]').val(value);
                        });
                    }
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        DeliceHybrid.init();
    });
    
    // Make available globally
    window.DeliceHybrid = DeliceHybrid;
    
})(jQuery);

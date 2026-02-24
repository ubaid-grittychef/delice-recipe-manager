
/**
 * Admin JavaScript for Delice Recipe Manager
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Initialize dynamic ingredient rows
        initIngredientRows();
        
        // Initialize dynamic instruction rows
        initInstructionRows();
        
        // Initialize AI Recipe Generator
        initAiGenerator();
        
        // Initialize Reviews Toggle
        initReviewsToggle();
    });

    /**
     * Initialize dynamic ingredient rows
     */
    function initIngredientRows() {
        const container = $('#delice-recipe-ingredients-container');
        
        if (!container.length) {
            return;
        }
        
        // Add ingredient button
        $('#add-ingredient').on('click', function() {
            const index = container.children().length;
            const newRow = `
                <div class="delice-recipe-ingredient-row">
                    <input type="text" class="ingredient-name" name="delice_recipe_ingredients[${index}][name]" 
                           placeholder="${deliceRecipe.ingredientPlaceholder || 'Ingrédient'}">
                    
                    <input type="text" class="ingredient-amount" name="delice_recipe_ingredients[${index}][amount]" 
                           placeholder="${deliceRecipe.amountPlaceholder || 'Quantité'}">
                    
                    <input type="text" class="ingredient-unit" name="delice_recipe_ingredients[${index}][unit]" 
                           placeholder="${deliceRecipe.unitPlaceholder || 'Unité'}">
                    
                    <button type="button" class="button remove-ingredient">${deliceRecipe.removeText || 'Supprimer'}</button>
                </div>
            `;
            
            container.append(newRow);
        });
        
        // Remove ingredient button (delegated event)
        container.on('click', '.remove-ingredient', function() {
            $(this).closest('.delice-recipe-ingredient-row').remove();
        });
    }

    /**
     * Initialize dynamic instruction rows
     */
    function initInstructionRows() {
        const container = $('#delice-recipe-instructions-container');
        
        if (!container.length) {
            return;
        }
        
        // Add instruction button
        $('#add-instruction').on('click', function() {
            const nextStep = container.children().length + 1;
            const newRow = `
                <div class="delice-recipe-instruction-row">
                    <span class="instruction-step">${nextStep}</span>
                    
                    <textarea class="instruction-text" name="delice_recipe_instructions[${nextStep}][text]" 
                              placeholder="${deliceRecipe.instructionPlaceholder || 'Instruction'}"></textarea>
                    
                    <input type="hidden" name="delice_recipe_instructions[${nextStep}][step]" value="${nextStep}">
                    
                    <button type="button" class="button remove-instruction">${deliceRecipe.removeText || 'Supprimer'}</button>
                </div>
            `;
            
            container.append(newRow);
        });
        
        // Remove instruction button (delegated event)
        container.on('click', '.remove-instruction', function() {
            $(this).closest('.delice-recipe-instruction-row').remove();
            
            // Renumber steps
            renumberInstructionSteps();
        });
    }

    /**
     * Renumber instruction steps
     */
    function renumberInstructionSteps() {
        const rows = $('#delice-recipe-instructions-container .delice-recipe-instruction-row');
        
        rows.each(function(index) {
            const step = index + 1;
            $(this).find('.instruction-step').text(step);
            $(this).find('input[type="hidden"]').val(step).attr('name', `delice_recipe_instructions[${step}][step]`);
            $(this).find('.instruction-text').attr('name', `delice_recipe_instructions[${step}][text]`);
        });
    }

    /**
     * Initialize AI Recipe Generator
     */
    function initAiGenerator() {
        const form = $('#delice-recipe-ai-form');
        
        if (!form.length) {
            return;
        }
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            const generationMode = $('#generation_mode').val();
            
            if (generationMode === 'single') {
                generateSingleRecipe();
            } else {
                generateBulkRecipes();
            }
        });
        
        // Regenerate button
        $('#delice-recipe-regenerate').on('click', function() {
            generateSingleRecipe();
        });
        
        /**
         * Generate a single recipe
         */
        function generateSingleRecipe() {
            // Get keyword and trim whitespace
            const keywordInput = $('#keyword');
            const keywordRaw = keywordInput.val();
            const keyword = $.trim(keywordRaw);
            
            // Extra debugging
            
            // Validate keyword input
            if (!keyword || keyword === '') {
                alert(deliceRecipe.missingKeyword || 'Please enter a recipe keyword.');
                // Set focus back to keyword input
                keywordInput.focus();
                return;
            }
            
            // Show generating indicator
            $('#generate-recipe-button').prop('disabled', true);
            $('#delice-recipe-generating').show();
            $('#generation-status').text(deliceRecipe.generatingText || 'Generating...');
            $('#delice-recipe-result').hide();
            $('#delice-recipe-bulk-results').hide();
            
            // Get form data
            const formData = form.serialize();
            
            // Log detailed information about the form and request
            form.find('input, select, textarea').each(function() {
            });
            
            // Send AJAX request
            $.ajax({
                url: deliceRecipe.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delice_generate_recipe',
                    nonce: deliceRecipe.nonce,
                    form_data: formData
                },
                success: function(response) {
                    // Hide generating indicator
                    $('#generate-recipe-button').prop('disabled', false);
                    $('#delice-recipe-generating').hide();
                    
                    if (response.success) {
                        // Show the generated recipe
                        $('#delice-recipe-preview').html(response.data.preview);
                        $('#delice-recipe-result').show();
                        
                        // Update edit link
                        $('#delice-recipe-edit').attr('href', response.data.edit_url);
                    } else {
                        // Show error message
                        alert(response.data.message || 'An error occurred. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Recipe generation AJAX Error:', xhr, status, error);
                    
                    // Hide generating indicator
                    $('#generate-recipe-button').prop('disabled', false);
                    $('#delice-recipe-generating').hide();
                    
                    // Show error message with more details
                    let errorMessage = 'A connection error occurred: ' + status;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage += '\n\nDetails: ' + xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        errorMessage += '\n\nDetails: ' + xhr.responseText.substring(0, 200) + '...';
                    } else if (error) {
                        errorMessage += '\n\nDetails: ' + error;
                    }
                    
                    alert(errorMessage);
                }
            });
        }
        
        /**
         * Generate bulk recipes
         */
        function generateBulkRecipes() {
            // Get and validate bulk keywords
            const bulkKeywords = $.trim($('#bulk_keywords').val());
            if (!bulkKeywords) {
                alert(deliceRecipe.missingKeywords || 'Please enter at least one keyword.');
                return;
            }
            
            // Parse keywords into array
            let keywords = bulkKeywords.split('\n')
                .map(keyword => keyword.trim())
                .filter(keyword => keyword.length > 0);
            
            // Check number of keywords
            const limit = parseInt($('#bulk_limit').val(), 10) || 10;
            if (keywords.length > 100) {
                alert(deliceRecipe.tooManyKeywords || 'Maximum 100 keywords allowed.');
                return;
            }
            
            // Limit to batch size
            const batchKeywords = keywords.slice(0, limit);
            
            // Show bulk generation UI
            $('#generate-recipe-button').prop('disabled', true);
            $('#delice-recipe-generating').show();
            $('#generation-status').text(deliceRecipe.preparingBulkText || 'Preparing bulk generation...');
            $('#delice-recipe-result').hide();
            
            // Set up bulk results
            $('#bulk-results-table').empty();
            $('#recipes-total').text(batchKeywords.length);
            $('#recipes-completed').text('0');
            $('.progress-fill').css('width', '0%');
            $('#delice-recipe-bulk-results').show();
            
            // Process each keyword sequentially
            processBulkKeywords(batchKeywords, 0);
        }
        
        /**
         * Process bulk keywords recursively
         */
        function processBulkKeywords(keywords, index) {
            if (index >= keywords.length) {
                // All done
                $('#delice-recipe-generating').hide();
                $('#generate-recipe-button').prop('disabled', false);
                return;
            }
            
            const keyword = keywords[index];
            const targetLanguage = $('#target_language').val();
            const autoPublish = $('input[name="auto_publish"]').is(':checked') ? '1' : '0';
            
            // Update status
            $('#generation-status').text(
                (deliceRecipe.generatingRecipeText || 'Generating recipe') + 
                ' ' + (index + 1) + '/' + keywords.length + ': ' + keyword
            );
            
            // Add placeholder row
            const rowId = 'bulk-row-' + index;
            $('#bulk-results-table').append(`
                <tr id="${rowId}">
                    <td>${keyword}</td>
                    <td><span class="spinner is-active"></span> ${deliceRecipe.generatingText || 'Generating...'}</td>
                    <td>${deliceRecipe.pendingText || 'Pending'}</td>
                    <td>-</td>
                </tr>
            `);
            
            
            // Send AJAX request
            $.ajax({
                url: deliceRecipe.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'delice_generate_bulk_recipes',
                    nonce: deliceRecipe.nonce,
                    keyword: keyword,
                    target_language: targetLanguage,
                    auto_publish: autoPublish
                },
                success: function(response) {
                    
                    if (response.success) {
                        // Update row with success
                        $('#' + rowId).html(`
                            <td>${keyword}</td>
                            <td>${response.data.title}</td>
                            <td><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ${deliceRecipe.successText || 'Success'}</td>
                            <td>
                                <a href="${response.data.edit_url}" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span> ${deliceRecipe.editText || 'Edit'}
                                </a>
                                <a href="${response.data.view_url}" class="button button-small" target="_blank">
                                    <span class="dashicons dashicons-visibility"></span> ${deliceRecipe.viewText || 'View'}
                                </a>
                            </td>
                        `);
                    } else {
                        // Update row with error
                        $('#' + rowId).html(`
                            <td>${keyword}</td>
                            <td>-</td>
                            <td><span class="dashicons dashicons-no-alt" style="color: red;"></span> ${deliceRecipe.errorText || 'Error'}</td>
                            <td>${response.data.message || 'Failed to generate recipe'}</td>
                        `);
                    }
                    
                    // Update progress
                    const completed = index + 1;
                    $('#recipes-completed').text(completed);
                    $('.progress-fill').css('width', (completed / keywords.length * 100) + '%');
                    
                    // Process next keyword
                    setTimeout(function() {
                        processBulkKeywords(keywords, index + 1);
                    }, 1000); // Add a small delay to prevent API rate limiting
                },
                error: function(xhr, status, error) {
                    console.error('Bulk recipe AJAX Error for ' + keyword + ':', xhr, status, error);
                    
                    // Update row with error
                    let errorMessage = 'Connection error: ' + status;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText.substring(0, 100) + '...';
                    }
                    
                    $('#' + rowId).html(`
                        <td>${keyword}</td>
                        <td>-</td>
                        <td><span class="dashicons dashicons-no-alt" style="color: red;"></span> ${deliceRecipe.errorText || 'Error'}</td>
                        <td>${errorMessage}</td>
                    `);
                    
                    // Update progress
                    const completed = index + 1;
                    $('#recipes-completed').text(completed);
                    $('.progress-fill').css('width', (completed / keywords.length * 100) + '%');
                    
                    // Process next keyword
                    setTimeout(function() {
                        processBulkKeywords(keywords, index + 1);
                    }, 1000);
                }
            });
        }
    }
    
    /**
     * Initialize Reviews Toggle
     */
    function initReviewsToggle() {
        $('#reviews-toggle').on('change', function() {
            const isEnabled = $(this).is(':checked');
            const $toggleText = $(this).siblings('.delice-recipe-toggle-text');
            
            // Update toggle text immediately
            $toggleText.text(isEnabled ? 'Enabled' : 'Disabled');
            
            // Show confirmation for disable action
            if (!isEnabled) {
                if (!confirm('Are you sure you want to disable reviews? This will completely hide all review sections from your recipe pages.')) {
                    // Revert the toggle
                    $(this).prop('checked', true);
                    $toggleText.text('Enabled');
                    return;
                }
            }
            
            // Send AJAX request to update setting
            $.post(ajaxurl, {
                action: 'delice_update_reviews_setting',
                setting_value: isEnabled ? '1' : '0',
                _wpnonce: deliceRecipe.nonce
            }, function(response) {
                // Show feedback
                if (response.success) {
                    $('<div class="notice notice-success is-dismissible"><p>Reviews setting updated successfully!</p></div>')
                        .insertAfter('.delice-recipe-welcome')
                        .delay(3000)
                        .fadeOut();
                } else {
                    alert('Failed to update setting. Please try again.');
                    // Revert on failure
                    $('#reviews-toggle').prop('checked', !isEnabled);
                    $toggleText.text(!isEnabled ? 'Enabled' : 'Disabled');
                }
            }).fail(function() {
                alert('Connection error. Please refresh the page and try again.');
                // Revert on failure
                $('#reviews-toggle').prop('checked', !isEnabled);
                $toggleText.text(!isEnabled ? 'Enabled' : 'Disabled');
            });
        });
    }

})(jQuery);

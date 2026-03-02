
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

        // Initialize equipment rows + AI extract
        initEquipmentRows();

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
            
            // Escape a string for safe HTML insertion.
            function escBulkHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // Add placeholder row
            const rowId = 'bulk-row-' + index;
            const safeKeyword = escBulkHtml(keyword);
            $('#bulk-results-table').append(
                '<tr id="' + rowId + '">' +
                    '<td>' + safeKeyword + '</td>' +
                    '<td><span class="spinner is-active"></span> ' + escBulkHtml(deliceRecipe.generatingText || 'Generating...') + '</td>' +
                    '<td>' + escBulkHtml(deliceRecipe.pendingText || 'Pending') + '</td>' +
                    '<td>-</td>' +
                '</tr>'
            );


            // Send AJAX request
            $.ajax({
                url: deliceRecipe.ajaxUrl,
                type: 'POST',
                timeout: 120000,
                data: {
                    action: 'delice_generate_bulk_recipes',
                    nonce: deliceRecipe.nonce,
                    keyword: keyword,
                    target_language: targetLanguage,
                    auto_publish: autoPublish
                },
                success: function(response) {

                    if (response.success) {
                        var editUrl  = escBulkHtml(response.data.edit_url  || '');
                        var viewUrl  = escBulkHtml(response.data.view_url  || '');
                        var title    = escBulkHtml(response.data.title     || '');
                        var editText = escBulkHtml(deliceRecipe.editText   || 'Edit');
                        var viewText = escBulkHtml(deliceRecipe.viewText   || 'View');
                        // Update row with success
                        $('#' + rowId).html(
                            '<td>' + safeKeyword + '</td>' +
                            '<td>' + title + '</td>' +
                            '<td><span class="dashicons dashicons-yes-alt" style="color:green;"></span> ' + escBulkHtml(deliceRecipe.successText || 'Success') + '</td>' +
                            '<td>' +
                                '<a href="' + editUrl + '" class="button button-small"><span class="dashicons dashicons-edit"></span> ' + editText + '</a> ' +
                                '<a href="' + viewUrl + '" class="button button-small" target="_blank"><span class="dashicons dashicons-visibility"></span> ' + viewText + '</a>' +
                            '</td>'
                        );
                    } else {
                        // Update row with error
                        var errMsg = escBulkHtml((response.data && response.data.message) ? response.data.message : 'Failed to generate recipe');
                        $('#' + rowId).html(
                            '<td>' + safeKeyword + '</td>' +
                            '<td>-</td>' +
                            '<td><span class="dashicons dashicons-no-alt" style="color:red;"></span> ' + escBulkHtml(deliceRecipe.errorText || 'Error') + '</td>' +
                            '<td>' + errMsg + '</td>'
                        );
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

                    // Update row with error — escape all dynamic content before injection
                    var errorMessage = 'Connection error: ' + escBulkHtml(status);
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = escBulkHtml(xhr.responseJSON.data.message);
                    }

                    $('#' + rowId).html(
                        '<td>' + safeKeyword + '</td>' +
                        '<td>-</td>' +
                        '<td><span class="dashicons dashicons-no-alt" style="color:red;"></span> ' + escBulkHtml(deliceRecipe.errorText || 'Error') + '</td>' +
                        '<td>' + errorMessage + '</td>'
                    );

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

    // ── Equipment meta box ────────────────────────────────────────────────────

    function escEqAttr( s ) {
        return String( s )
            .replace( /&/g, '&amp;' ).replace( /"/g, '&quot;' )
            .replace( /</g, '&lt;'  ).replace( />/g, '&gt;'  );
    }

    function equipmentRowHtml( index, data ) {
        data = data || {};
        var name       = data.name        || '';
        var notes      = data.notes       || '';
        var productUrl = data.product_url || '';
        var required   = data.required !== false;
        return (
            '<div class="delice-recipe-equipment-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;flex-wrap:wrap;">' +
            '<input type="text" name="delice_recipe_equipment[' + index + '][name]" value="' + escEqAttr( name ) + '" placeholder="Equipment name (e.g. Stand Mixer)" style="flex:2;min-width:160px;">' +
            '<input type="text" name="delice_recipe_equipment[' + index + '][notes]" value="' + escEqAttr( notes ) + '" placeholder="Notes (optional)" style="flex:2;min-width:120px;">' +
            '<input type="url" name="delice_recipe_equipment[' + index + '][product_url]" value="' + escEqAttr( productUrl ) + '" placeholder="Amazon product URL (optional)" style="flex:3;min-width:200px;">' +
            '<label style="white-space:nowrap;font-size:12px;display:flex;align-items:center;gap:4px;">' +
            '<input type="checkbox" name="delice_recipe_equipment[' + index + '][required]" value="1"' + ( required ? ' checked' : '' ) + '> Required' +
            '</label>' +
            '<button type="button" class="button button-small remove-equipment">Remove</button>' +
            '</div>'
        );
    }

    function reindexEquipment() {
        $( '#delice-recipe-equipment-container .delice-recipe-equipment-row' ).each( function ( i ) {
            $( this ).find( '[name]' ).each( function () {
                var n = $( this ).attr( 'name' );
                if ( n ) $( this ).attr( 'name', n.replace( /\[\d+\]/, '[' + i + ']' ) );
            } );
        } );
    }

    function initEquipmentRows() {
        var $container = $( '#delice-recipe-equipment-container' );
        if ( ! $container.length ) return;

        // Add row
        $( '#add-equipment' ).on( 'click', function () {
            var index = $container.children( '.delice-recipe-equipment-row' ).length;
            $container.append( equipmentRowHtml( index, {} ) );
        } );

        // Remove row (delegated)
        $container.on( 'click', '.remove-equipment', function () {
            $( this ).closest( '.delice-recipe-equipment-row' ).remove();
            reindexEquipment();
        } );

        // AI extract
        $( '#extract-equipment-ai' ).on( 'click', function () {
            var $btn    = $( this );
            var $status = $( '#extract-equipment-status' );
            var postId  = ( typeof deliceRecipe !== 'undefined' ) ? parseInt( deliceRecipe.postId, 10 ) : 0;
            var nonce   = ( typeof deliceRecipe !== 'undefined' ) ? deliceRecipe.nonce : '';
            var ajaxUrl = ( typeof deliceRecipe !== 'undefined' ) ? deliceRecipe.ajaxUrl : '';

            if ( ! postId ) {
                $status.text( 'Please save the post first, then try AI extraction.' );
                return;
            }

            $btn.prop( 'disabled', true );
            $status.text( 'Extracting equipment from instructions\u2026' );

            $.post( ajaxUrl, {
                action:  'delice_extract_equipment',
                nonce:   nonce,
                post_id: postId,
            }, function ( res ) {
                $btn.prop( 'disabled', false );
                if ( res && res.success && res.data.equipment ) {
                    $container.empty();
                    $.each( res.data.equipment, function ( i, item ) {
                        $container.append( equipmentRowHtml( i, item ) );
                    } );
                    $status.text( res.data.equipment.length + ' item(s) found. Review and save the post.' );
                } else {
                    $status.text( ( res && res.data ) ? res.data : 'Extraction failed.' );
                }
            } ).fail( function () {
                $btn.prop( 'disabled', false );
                $status.text( 'Network error. Please try again.' );
            } );
        } );
    }

})(jQuery);

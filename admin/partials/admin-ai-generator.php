
<div class="wrap delice-ai-generator">
    <h1><?php _e('AI Recipe Generator', 'delice-recipe-manager'); ?></h1>
    
    <?php
    // Check if API key is configured
    $api_key = get_option('delice_recipe_ai_api_key', '');
    if (empty($api_key)) :
    ?>
    <div class="notice notice-error">
        <p>
            <?php _e('OpenAI API key is not configured. Please configure it in settings before using the AI generator.', 'delice-recipe-manager'); ?>
            <a href="<?php echo admin_url('admin.php?page=delice-recipe-settings'); ?>" class="button button-small"><?php _e('Configure', 'delice-recipe-manager'); ?></a>
        </p>
    </div>
    <?php else : ?>
    
    <div id="delice-recipe-ai-root" class="delice-recipe-ai-app">
        <div class="delice-recipe-generator-loading">
            <span class="spinner is-active"></span> <?php _e('Loading AI Generator...', 'delice-recipe-manager'); ?>
        </div>
    </div>
    
    <template id="delice-recipe-generator-template">
        <div class="delice-recipe-generator-wrapper">
            <div class="delice-recipe-generator-form">
                <div class="delice-recipe-generator-intro">
                    <p><?php _e('Enter one or more keywords, and our AI will create complete recipes for you.', 'delice-recipe-manager'); ?></p>
                </div>
                
                <form id="delice-recipe-ai-form">
                    <?php wp_nonce_field('delice_recipe_nonce', 'delice_recipe_ai_nonce'); ?>
                    
                    <div class="delice-recipe-generator-fields">
                        <div class="delice-recipe-generator-field">
                            <label for="generation_mode"><?php _e('Generation Mode', 'delice-recipe-manager'); ?></label>
                            <select id="generation_mode" name="generation_mode">
                                <option value="single"><?php _e('Single Recipe', 'delice-recipe-manager'); ?></option>
                                <option value="bulk"><?php _e('Bulk Generation', 'delice-recipe-manager'); ?></option>
                            </select>
                        </div>
                        
                        <div id="single-mode-container">
                            <div class="delice-recipe-keyword-field">
                                <label for="keyword_input"><?php _e('Recipe Keywords *', 'delice-recipe-manager'); ?></label>
                                <div class="delice-recipe-tag-input-wrapper">
                                    <div class="delice-recipe-tag-container"></div>
                                    <input type="text" id="keyword_input" 
                                           placeholder="<?php _e('Type a keyword and press Enter...', 'delice-recipe-manager'); ?>">
                                    <input type="hidden" id="keyword" name="keyword" required>
                                </div>
                            </div>
                            
                            <div class="delice-recipe-variations-field">
                                <label><?php _e('Variations (optional)', 'delice-recipe-manager'); ?></label>
                                <div class="delice-recipe-variations">
                                    <label class="delice-recipe-variation-option">
                                        <input type="checkbox" name="variations[]" value="Make it vegan.">
                                        <?php _e('Make it vegan', 'delice-recipe-manager'); ?>
                                    </label>
                                    <label class="delice-recipe-variation-option">
                                        <input type="checkbox" name="variations[]" value="Focus on weeknight dinners.">
                                        <?php _e('Quick weeknight dinner', 'delice-recipe-manager'); ?>
                                    </label>
                                    <label class="delice-recipe-variation-option">
                                        <input type="checkbox" name="variations[]" value="Make it gluten-free.">
                                        <?php _e('Make it gluten-free', 'delice-recipe-manager'); ?>
                                    </label>
                                    <label class="delice-recipe-variation-option">
                                        <input type="checkbox" name="variations[]" value="Low calorie option.">
                                        <?php _e('Low calorie', 'delice-recipe-manager'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="bulk-mode-container" style="display: none;">
                            <div class="delice-recipe-generator-field">
                                <label for="bulk_keywords"><?php _e('Recipe Keywords (one per line, max 100) *', 'delice-recipe-manager'); ?></label>
                                <textarea id="bulk_keywords" name="bulk_keywords" rows="10" 
                                          placeholder="<?php _e('Enter up to 100 keywords, one per line. E.g.:\nStrawberry\nChocolate Cake\nGrilled Salmon', 'delice-recipe-manager'); ?>"></textarea>
                                <p class="description"><?php _e('Each keyword will generate a separate recipe. Limit: 100 recipes per batch.', 'delice-recipe-manager'); ?></p>
                            </div>
                            
                            <div class="delice-recipe-generator-field">
                                <label for="bulk_limit"><?php _e('Batch Size', 'delice-recipe-manager'); ?></label>
                                <select id="bulk_limit" name="bulk_limit">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <p class="description"><?php _e('Number of recipes to generate in one batch.', 'delice-recipe-manager'); ?></p>
                            </div>
                        </div>
                        
                        <div class="delice-recipe-generator-field">
                            <label for="target_language"><?php _e('Target Language', 'delice-recipe-manager'); ?></label>
                            <select id="target_language" name="target_language">
                                <option value="english"><?php _e('English', 'delice-recipe-manager'); ?></option>
                                <option value="french"><?php _e('French', 'delice-recipe-manager'); ?></option>
                                <option value="spanish"><?php _e('Spanish', 'delice-recipe-manager'); ?></option>
                                <option value="italian"><?php _e('Italian', 'delice-recipe-manager'); ?></option>
                                <option value="german"><?php _e('German', 'delice-recipe-manager'); ?></option>
                                <option value="chinese"><?php _e('Chinese', 'delice-recipe-manager'); ?></option>
                                <option value="japanese"><?php _e('Japanese', 'delice-recipe-manager'); ?></option>
                                <option value="hindi"><?php _e('Hindi', 'delice-recipe-manager'); ?></option>
                            </select>
                        </div>
                        
                        <div class="delice-recipe-generator-options">
                            <label>
                                <input type="checkbox" name="auto_publish" value="1">
                                <?php _e('Publish automatically', 'delice-recipe-manager'); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="delice-recipe-generator-submit">
                        <button type="submit" id="generate-recipe-button" class="button button-primary button-large">
                            <span class="dashicons dashicons-admin-customizer"></span> <?php _e('Generate Recipe', 'delice-recipe-manager'); ?>
                        </button>
                        <div id="delice-recipe-generating" class="delice-recipe-generating" style="display: none;">
                            <span class="spinner is-active"></span>
                            <span id="generation-status"><?php _e('Generating...', 'delice-recipe-manager'); ?></span>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="delice-recipe-result-container">
                <div id="delice-recipe-result" class="delice-recipe-result" style="display: none;">
                    <h2><?php _e('Generated Recipe', 'delice-recipe-manager'); ?></h2>
                    
                    <div class="delice-recipe-approval">
                        <label>
                            <input type="checkbox" id="approve-recipe">
                            <?php _e('Approve this recipe', 'delice-recipe-manager'); ?>
                        </label>
                        <p class="description"><?php _e('Review and approve this recipe before saving.', 'delice-recipe-manager'); ?></p>
                    </div>
                    
                    <div id="delice-recipe-preview" class="delice-recipe-preview">
                        <!-- Preview content will be inserted here -->
                        <div class="delice-recipe-skeleton-loading">
                            <div class="delice-skeleton delice-skeleton-title"></div>
                            <div class="delice-skeleton delice-skeleton-meta"></div>
                            <div class="delice-skeleton-grid">
                                <div class="delice-skeleton delice-skeleton-ingredients"></div>
                                <div class="delice-skeleton delice-skeleton-instructions"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="delice-recipe-result-actions">
                        <a href="#" id="delice-recipe-edit" class="button button-primary">
                            <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'delice-recipe-manager'); ?>
                        </a>
                        <button id="delice-recipe-regenerate" class="button">
                            <span class="dashicons dashicons-update"></span> <?php _e('Regenerate', 'delice-recipe-manager'); ?>
                        </button>
                    </div>
                </div>
                
                <div id="delice-recipe-bulk-results" class="delice-recipe-bulk-results" style="display: none;">
                    <h2><?php _e('Bulk Generation Results', 'delice-recipe-manager'); ?></h2>
                    <div class="delice-recipe-bulk-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                        <div class="progress-status">
                            <span id="recipes-completed">0</span> / <span id="recipes-total">0</span> <?php _e('recipes generated', 'delice-recipe-manager'); ?>
                        </div>
                    </div>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Keyword', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Recipe Title', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Status', 'delice-recipe-manager'); ?></th>
                                <th><?php _e('Actions', 'delice-recipe-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="bulk-results-table">
                            <!-- Results will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
    
    <style>
    .delice-recipe-generator-wrapper {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    @media screen and (max-width: 1200px) {
        .delice-recipe-generator-wrapper {
            grid-template-columns: 1fr;
        }
    }
    .delice-recipe-tag-input-wrapper {
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 5px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        align-items: center;
        min-height: 40px;
    }
    .delice-recipe-tag-container {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .delice-recipe-tag {
        background: #f0f0f1;
        border-radius: 3px;
        padding: 4px 8px;
        margin: 2px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .delice-recipe-tag-remove {
        cursor: pointer;
        color: #999;
    }
    .delice-recipe-tag-remove:hover {
        color: #d63638;
    }
    #keyword_input {
        flex: 1;
        min-width: 100px;
        border: none;
        outline: none;
        background: transparent;
        padding: 5px;
    }
    .delice-recipe-variations {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .delice-recipe-variation-option {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .delice-recipe-approval {
        background: #f0f7ff;
        padding: 15px;
        border-left: 4px solid #2271b1;
        margin-bottom: 20px;
    }
    .delice-recipe-skeleton-loading {
        padding: 20px;
    }
    .delice-skeleton {
        background: #f0f0f0;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    .delice-skeleton-title {
        height: 38px;
        width: 60%;
    }
    .delice-skeleton-meta {
        height: 80px;
        width: 100%;
    }
    .delice-skeleton-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 30px;
    }
    .delice-skeleton-ingredients,
    .delice-skeleton-instructions {
        height: 300px;
    }
    </style>
    
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Check if we're in the AI generator page
    if ($('#delice-recipe-ai-root').length === 0) {
        return;
    }
    
    // Initialize the React-like UI
    initReactUI();
    
    function initReactUI() {
        // Render the template
        const template = document.getElementById('delice-recipe-generator-template');
        const root = document.getElementById('delice-recipe-ai-root');
        
        if (template && root) {
            root.innerHTML = template.innerHTML;
            
            // Initialize tag input
            initTagInput();
            
            // Initialize AI recipe generator
            initAiGenerator();
        }
    }
    
    /**
     * Initialize tag-based keyword input
     */
    function initTagInput() {
        const container = $('.delice-recipe-tag-container');
        const input = $('#keyword_input');
        const hiddenInput = $('#keyword');
        
        if (!container.length || !input.length) {
            return;
        }
        
        // Add tag when Enter is pressed
        input.on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                
                const value = input.val().trim();
                if (value) {
                    addTag(value);
                    input.val('');
                    updateHiddenInput();
                }
            }
        });
        
        // Remove tag when clicked
        container.on('click', '.delice-recipe-tag-remove', function() {
            $(this).parent('.delice-recipe-tag').remove();
            updateHiddenInput();
        });
        
        /**
         * Add a tag to the container
         */
        function addTag(text) {
            const tag = $('<div class="delice-recipe-tag"></div>');
            tag.text(text);
            tag.append('<span class="delice-recipe-tag-remove dashicons dashicons-no-alt"></span>');
            container.append(tag);
        }
        
        /**
         * Update hidden input with comma-separated tags
         */
        function updateHiddenInput() {
            const tags = [];
            $('.delice-recipe-tag').each(function() {
                tags.push($(this).text().trim());
            });
            hiddenInput.val(tags.join(', '));
        }
    }
    
    /**
     * Initialize AI recipe generator
     */
    function initAiGenerator() {
        const form = $('#delice-recipe-ai-form');

        if (!form.length) {
            return;
        }

        // Cache skeleton HTML once so it survives being replaced by preview content.
        const skeletonHtml = $('#delice-recipe-preview .delice-recipe-skeleton-loading').clone();
        
        // Toggle between single and bulk mode
        $('#generation_mode').on('change', function() {
            if ($(this).val() === 'bulk') {
                $('#single-mode-container').hide();
                $('#bulk-mode-container').show();
            } else {
                $('#single-mode-container').show();
                $('#bulk-mode-container').hide();
            }
        });
        
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
        
        // Approval checkbox
        $('#approve-recipe').on('change', function() {
            const approved = $(this).is(':checked');
            $('#delice-recipe-edit').prop('disabled', !approved);
        });
        
        /**
         * Generate a single recipe
         */
        function generateSingleRecipe() {
            // If the user typed text without pressing Enter, treat it as a keyword now.
            const pendingText = $('#keyword_input').val().trim();
            if (pendingText) {
                const existing = $('#keyword').val();
                $('#keyword').val(existing ? existing + ', ' + pendingText : pendingText);
                $('#keyword_input').val('');
            }

            // Get tags
            const keyword = $('#keyword').val();
            if (!keyword) {
                alert(deliceRecipe.missingKeyword || 'Please enter at least one recipe keyword.');
                return;
            }

            // Show generating indicator
            $('#generate-recipe-button').prop('disabled', true);
            $('#delice-recipe-generating').show();
            $('#generation-status').text(deliceRecipe.generatingText || 'Generating\u2026');
            $('#delice-recipe-result').show();
            $('#delice-recipe-bulk-results').hide();

            // Show skeleton loading (use cached clone so it works on every regeneration)
            $('#delice-recipe-preview').html(skeletonHtml.clone());

            // Reset approval
            $('#approve-recipe').prop('checked', false);
            $('#delice-recipe-edit').prop('disabled', true);

            const nonce = $('#delice_recipe_ai_nonce').val();
            const variations = [];
            form.find('[name="variations[]"]:checked').each(function() {
                variations.push($(this).val());
            });

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delice_generate_recipe',
                    nonce: nonce,
                    keywords: keyword,
                    target_language: form.find('#target_language').val(),
                    auto_publish: form.find('[name="auto_publish"]').is(':checked') ? '1' : '0',
                    variations: variations
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
                        $('#delice-recipe-preview').html('<div class="notice notice-error"><p>' + (response.data.message || 'An error occurred. Please try again.') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide generating indicator
                    $('#generate-recipe-button').prop('disabled', false);
                    $('#delice-recipe-generating').hide();
                    
                    // Show error message
                    $('#delice-recipe-preview').html('<div class="notice notice-error"><p>An error occurred while generating the recipe. Please try again.</p></div>');
                }
            });
        }
        
        /**
         * Generate bulk recipes
         */
        function generateBulkRecipes() {
            // Get and validate bulk keywords
            const bulkKeywords = $('#bulk_keywords').val();
            if (!bulkKeywords) {
                alert(deliceRecipe.missingKeywords || 'Please enter at least one keyword.');
                return;
            }
            
            // Parse keywords into array (handle both Windows and Unix line endings)
            let keywords = bulkKeywords.split(/\r?\n/)
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
            const nonce = $('#delice_recipe_ai_nonce').val();
            
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
            
            // Process each keyword sequentially with a maximum concurrency of 5
            processBulkKeywordsBatch(batchKeywords, nonce, 5);
        }
        
        /**
         * Process bulk keywords with concurrency limit
         */
        function processBulkKeywordsBatch(keywords, nonce, concurrency) {
            const queue = [...keywords];
            let active = 0;
            let completed = 0;
            const total = keywords.length;
            
            function processNext() {
                if (queue.length === 0 || active >= concurrency) return;
                
                const keyword = queue.shift();
                active++;
                
                processSingleKeyword(keyword, nonce)
                    .then(() => {
                        active--;
                        completed++;
                        
                        // Update progress
                        $('#recipes-completed').text(completed);
                        $('.progress-fill').css('width', (completed / total * 100) + '%');
                        
                        // If all done
                        if (completed === total) {
                            $('#delice-recipe-generating').hide();
                            $('#generate-recipe-button').prop('disabled', false);
                        } else {
                            // Process next
                            processNext();
                        }
                    });
                
                // Try to process another one immediately
                processNext();
            }
            
            // Kick off initial batch
            for (let i = 0; i < Math.min(concurrency, total); i++) {
                processNext();
            }
        }
        
        /**
         * Process a single keyword and return a promise
         */
        function processSingleKeyword(keyword, nonce) {
            return new Promise((resolve) => {
                // Update status
                $('#generation-status').text(
                    (deliceRecipe.generatingRecipeText || 'Generating recipe') + 
                    ': ' + keyword
                );
                
                // Add placeholder row
                const rowId = 'bulk-row-' + keyword.replace(/\s+/g, '-').toLowerCase();
                $('#bulk-results-table').append(`
                    <tr id="${rowId}">
                        <td>${keyword}</td>
                        <td><span class="spinner is-active"></span> ${deliceRecipe.generatingText || 'Generating...'}</td>
                        <td>${deliceRecipe.pendingText || 'Pending'}</td>
                        <td>-</td>
                    </tr>
                `);
                
                const targetLanguage = $('#target_language').val();
                const autoPublish = $('input[name="auto_publish"]').is(':checked') ? '1' : '0';
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delice_generate_bulk_recipes',
                        nonce: nonce,
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
                        
                        // Always resolve the promise
                        setTimeout(resolve, 500);
                    },
                    error: function(xhr, status, error) {
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
                        
                        // Always resolve the promise
                        setTimeout(resolve, 500);
                    }
                });
            });
        }
    }
});
</script>

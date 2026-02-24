
/**
 * Public JavaScript for Délice Recipe Manager
 */
(function ($) {
    'use strict';

    console.log('Delice Recipe Public JS loaded');

    // Wait for document to be fully ready
    $(document).ready(function () {
        console.log('Public JS DOM ready');
        
        // Enhanced recipe detection for both custom post type and migrated recipes
        if (isRecipePage()) {
            console.log('Recipe detected on page - initializing recipe components');
            
            // Add classes to meta items for styling
            $('.delice-recipe-meta-item').each(function() {
                const label = $(this).find('.delice-recipe-meta-label').text().toLowerCase().trim();
                if (label.includes('prep')) {
                    $(this).addClass('prep-time');
                } else if (label.includes('cook')) {
                    $(this).addClass('cook-time');
                } else if (label.includes('total')) {
                    $(this).addClass('total-time');
                } else if (label.includes('servings') || label.includes('portions')) {
                    $(this).addClass('servings');
                } else if (label.includes('calories') || label.includes('cal')) {
                    $(this).addClass('calories');
                }
            });
            
            // Handle recipe language text
            initializeLanguageText();
        }
    });

    /**
     * Enhanced recipe page detection
     */
    function isRecipePage() {
        // Check for recipe containers
        if ($('.delice-recipe-container, .delice-recipe-modern, .delice-recipe-card, .delice-recipe-shortcode-wrapper').length > 0) {
            return true;
        }
        
        // Check for recipe metadata indicators
        if ($('meta[property="recipe:ingredients"], meta[property="recipe:instructions"]').length > 0) {
            return true;
        }
        
        // Check for recipe schema
        if ($('script[type="application/ld+json"]').length > 0) {
            var hasRecipeSchema = false;
            $('script[type="application/ld+json"]').each(function() {
                try {
                    var data = JSON.parse($(this).text());
                    if (data['@type'] === 'Recipe' || (data['@graph'] && data['@graph'].some(item => item['@type'] === 'Recipe'))) {
                        hasRecipeSchema = true;
                        return false; // break
                    }
                } catch (e) {
                    // Ignore JSON parse errors
                }
            });
            if (hasRecipeSchema) return true;
        }
        
        // Check body classes for recipe indicators
        if ($('body').hasClass('single-delice_recipe') || $('body').hasClass('recipe-post')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Initialize language-specific text
     */
    function initializeLanguageText() {
        if (typeof deliceRecipe === 'undefined' || !deliceRecipe.texts) {
            console.log('deliceRecipe language data not available');
            return;
        }
        
        // Update print button text
        $('.delice-recipe-print-btn .delice-recipe-action-button-text, .print-recipe span').each(function() {
            if (deliceRecipe.texts.print) {
                $(this).text(deliceRecipe.texts.print);
            }
        });
        
        // Update copy ingredients button text
        $('.delice-recipe-copy-ingredients, .copy-ingredients span').each(function() {
            if (deliceRecipe.texts.copy) {
                $(this).text(deliceRecipe.texts.copy);
            }
        });
        
        // Update rate button text
        $('.delice-recipe-rate-btn .delice-recipe-action-button-text').each(function() {
            if (deliceRecipe.texts.rate) {
                $(this).text(deliceRecipe.texts.rate);
            }
        });
        
        // Update share button text
        $('.delice-recipe-share-btn .delice-recipe-action-button-text').each(function() {
            if (deliceRecipe.texts.share) {
                $(this).text(deliceRecipe.texts.share);
            }
        });
        
        // Update meta labels
        updateMetaLabels();
    }
    
    /**
     * Update meta labels with language texts
     */
    function updateMetaLabels() {
        if (typeof deliceRecipe === 'undefined' || !deliceRecipe.texts) {
            return;
        }
        
        $('.delice-recipe-meta-item').each(function() {
            var $item = $(this);
            var $label = $item.find('.delice-recipe-meta-label');
            
            if ($label.length) {
                if ($item.hasClass('servings') && deliceRecipe.texts.servings) {
                    $label.text(deliceRecipe.texts.servings);
                } else if ($item.hasClass('prep-time') && deliceRecipe.texts.prep_time) {
                    $label.text(deliceRecipe.texts.prep_time);
                } else if ($item.hasClass('cook-time') && deliceRecipe.texts.cook_time) {
                    $label.text(deliceRecipe.texts.cook_time);
                } else if ($item.hasClass('total-time') && deliceRecipe.texts.total_time) {
                    $label.text(deliceRecipe.texts.total_time);
                } else if ($item.hasClass('calories') && deliceRecipe.texts.calories) {
                    $label.text(deliceRecipe.texts.calories);
                } else if ($item.find('.delice-recipe-meta-label').text().includes('Difficulty') && deliceRecipe.texts.difficulty) {
                    $label.text(deliceRecipe.texts.difficulty);
                }
            }
        });
    }

})(jQuery);


(function($) {
    'use strict';

    console.log('Recipe Card module loaded');

    class RecipeCard {
        constructor() {
            console.log('Initializing Recipe Card');
            this.initializeRecipeCard();
            this.setupPrintButton();
            this.handleResponsiveness();
            this.setupCheckboxes();
            this.setupCopyIngredientsButton();
            this.initializeLanguageSupport();
        }

        initializeRecipeCard() {
            const recipeContainers = $('.delice-recipe-container, .delice-recipe-modern, .delice-recipe-elegant');
            
            if (recipeContainers.length === 0) {
                console.log('No recipe containers found on page');
                return;
            }

            console.log('Found ' + recipeContainers.length + ' recipe container(s)');

            // Fix recipe sections display
            $('.delice-recipe-ingredients, .delice-recipe-instructions').each(function() {
                $(this).find('ul, ol, li').css({
                    'list-style-position': 'outside',
                    'margin-left': '1.5em',
                    'display': 'list-item'
                });
            });
        }

        setupCheckboxes() {
            // Make checkboxes interactive
            $('.delice-recipe-ingredient-checkbox').on('click', function() {
                console.log('Ingredient checkbox clicked');
                // Allow default browser behavior for checkbox
            });
        }

        setupPrintButton() {
            $('.delice-recipe-print-button, .delice-recipe-modern-print, .delice-recipe-print').on('click', function(e) {
                console.log('Print button clicked');
                e.preventDefault();
                window.print();
            });
        }

        setupCopyIngredientsButton() {
            $('.delice-recipe-copy-ingredients').on('click', function(e) {
                console.log('Copy ingredients button clicked');
                e.preventDefault();
                
                // Build ingredients list
                let ingredientsText = '';
                $('.delice-recipe-modern-ingredients li, .delice-recipe-ingredients-list li').each(function() {
                    const name = $(this).find('.delice-recipe-modern-ingredient-name, .delice-recipe-ingredient-name').text().trim();
                    const quantity = $(this).find('.delice-recipe-modern-ingredient-quantity, .delice-recipe-ingredient-quantity').text().trim();
                    ingredientsText += name + (quantity ? ' - ' + quantity : '') + '\n';
                });
                
                // Use clipboard API if available
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(ingredientsText)
                        .then(() => {
                            console.log('Ingredients copied to clipboard');
                            RecipeCard.showCopyMessage();
                        })
                        .catch(err => {
                            console.error('Failed to copy: ', err);
                            // Fallback
                            RecipeCard.fallbackCopyTextToClipboard(ingredientsText);
                        });
                } else {
                    // Fallback for older browsers
                    RecipeCard.fallbackCopyTextToClipboard(ingredientsText);
                }
            });
        }

        static fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('Fallback: Ingredients copied to clipboard');
                    RecipeCard.showCopyMessage();
                } else {
                    console.error('Fallback: Copy command was unsuccessful');
                }
            } catch (err) {
                console.error('Fallback: Could not copy text: ', err);
            }
            
            document.body.removeChild(textArea);
        }

        static showCopyMessage() {
            const $message = $('#delice-copy-message');
            
            // FIXED: Get the display message based on current language
            const displayText = (window.deliceRecipe && window.deliceRecipe.ingredientsCopiedText) || 'Ingredients copied!';
            
            // If message element doesn't exist, create it
            if ($message.length === 0) {
                $('body').append('<div class="delice-recipe-copy-message" id="delice-copy-message">' + displayText + '</div>');
                const $newMessage = $('#delice-copy-message');
                $newMessage.css('display', 'block');
                
                // Hide after 3 seconds
                setTimeout(() => {
                    $newMessage.css('display', 'none');
                }, 3000);
            } else {
                // Update existing message text
                $message.text(displayText);
                // Show existing message
                $message.css('display', 'block');
                
                // Hide after 3 seconds
                setTimeout(() => {
                    $message.css('display', 'none');
                }, 3000);
            }
        }

        handleResponsiveness() {
            const handleResize = () => {
                if (window.innerWidth < 768) {
                    this.applyMobileStyles();
                } else {
                    this.resetMobileStyles();
                }
            };

            // Initial check
            handleResize();
            
            // Listen for resize
            $(window).on('resize', handleResize);
        }

        applyMobileStyles() {
            $('.delice-recipe-meta').addClass('delice-recipe-meta-mobile');
            $('.delice-recipe-meta-item').addClass('delice-recipe-meta-item-mobile');
            $('.delice-recipe-section-container').addClass('delice-recipe-section-container-mobile');
            $('.delice-recipe-modern-body, .delice-recipe-elegant-body').addClass('delice-recipe-body-mobile');
            $('.delice-recipe-modern-sidebar, .delice-recipe-elegant-sidebar, .delice-recipe-modern-main, .delice-recipe-elegant-main').addClass('delice-recipe-sidebar-mobile');
        }

        resetMobileStyles() {
            $('.delice-recipe-meta').removeClass('delice-recipe-meta-mobile');
            $('.delice-recipe-meta-item').removeClass('delice-recipe-meta-item-mobile');
            $('.delice-recipe-section-container').removeClass('delice-recipe-section-container-mobile');
            $('.delice-recipe-modern-body, .delice-recipe-elegant-body').removeClass('delice-recipe-body-mobile');
            $('.delice-recipe-modern-sidebar, .delice-recipe-elegant-sidebar, .delice-recipe-modern-main, .delice-recipe-elegant-main').removeClass('delice-recipe-sidebar-mobile');
        }

        /**
         * FIXED: Enhanced language support initialization
         */
        initializeLanguageSupport() {
            if (typeof window.deliceRecipe === 'undefined') {
                console.log('deliceRecipe global variable not found');
                return;
            }
            
            // Update button text on initialization
            this.updateButtonText();
            
            // Watch for dynamic content changes
            const observer = new MutationObserver(() => {
                this.updateButtonText();
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        updateButtonText() {
            // Update print buttons
            $('.delice-recipe-print-button, .delice-recipe-modern-print, .delice-recipe-print').each(function() {
                const $button = $(this);
                const printText = window.deliceRecipe.printText || 'Print Recipe';
                
                if ($button.find('svg, .dashicons').length) {
                    const icon = $button.find('svg, .dashicons')[0].outerHTML;
                    $button.html(icon + ' ' + printText);
                } else {
                    $button.text(printText);
                }
            });
            
            // Update copy buttons
            $('.delice-recipe-copy-ingredients').each(function() {
                const $button = $(this);
                const copyText = window.deliceRecipe.copyText || 'Copy Ingredients';
                
                if ($button.find('svg, .dashicons').length) {
                    const icon = $button.find('svg, .dashicons')[0].outerHTML;
                    $button.html(icon + ' ' + copyText);
                } else {
                    $button.text(copyText);
                }
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        console.log('Document ready, initializing RecipeCard');
        new RecipeCard();
    });

})(jQuery);

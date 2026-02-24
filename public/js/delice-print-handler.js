/**
 * Print Handler - Completely different approach
 */
(function() {
    'use strict';
    
    function initPrint() {
        // Find all print buttons
        var printButtons = document.querySelectorAll('.delice-recipe-print-btn');
        
        if (printButtons.length === 0) {
            return;
        }
        
        
        printButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get recipe content
                var recipeCard = document.querySelector('.delice-recipe-card');
                if (!recipeCard) {
                    recipeCard = document.querySelector('.delice-recipe-container');
                }
                
                if (!recipeCard) {
                    alert('Recipe content not found');
                    return;
                }
                
                // Clone the recipe
                var recipeClone = recipeCard.cloneNode(true);
                
                // Remove unwanted elements from clone
                var toRemove = recipeClone.querySelectorAll(
                    '.delice-recipe-action-buttons, ' +
                    '.delice-recipe-bottom-actions, ' +
                    '.delice-recipe-copy-ingredients, ' +
                    '.delice-recipe-ingredient-checkbox, ' +
                    '.delice-recipe-review-section, ' +
                    '.delice-recipe-footer, ' +
                    'button'
                );
                
                toRemove.forEach(function(el) {
                    el.remove();
                });
                
                // Create print container
                var printDiv = document.createElement('div');
                printDiv.id = 'recipe-print-container';
                printDiv.style.display = 'none';
                printDiv.appendChild(recipeClone);
                
                // Add to body
                document.body.appendChild(printDiv);
                
                // Add print styles
                var style = document.createElement('style');
                style.id = 'recipe-print-styles';
                style.textContent = `
                    @media print {
                        body * {
                            visibility: hidden;
                        }
                        #recipe-print-container,
                        #recipe-print-container * {
                            visibility: visible;
                        }
                        #recipe-print-container {
                            position: absolute;
                            left: 0;
                            top: 0;
                            width: 100%;
                            display: block !important;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                // Print
                window.print();
                
                // Cleanup
                setTimeout(function() {
                    printDiv.remove();
                    style.remove();
                }, 1000);
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPrint);
    } else {
        initPrint();
    }
    
    // Also try after delay
    setTimeout(initPrint, 1000);
})();

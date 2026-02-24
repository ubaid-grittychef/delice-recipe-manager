
/**
 * Recipe Action Buttons functionality
 */
(function($) {
    'use strict';
    
    // When document is ready
    $(document).ready(function() {
        initActionButtons();
    });
    
    /**
     * Initialize action buttons
     */
    function initActionButtons() {
        // Print button
        $('.delice-recipe-print-btn').on('click', function(e) {
            e.preventDefault();
            window.print();
        });
        
        // Share button
        $('.delice-recipe-share-btn').on('click', function(e) {
            e.preventDefault();
            toggleShareMenu($(this));
        });
        
        // Rate button
        $('.delice-recipe-rate-btn').on('click', function(e) {
            e.preventDefault();
            openRatingModal($(this));
        });
        
        // Close modals when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.delice-recipe-share-dropdown').length) {
                $('.delice-recipe-share-menu').removeClass('active');
            }
            
            if ($(e.target).hasClass('delice-recipe-rating-modal')) {
                closeRatingModal();
            }
        });
        
        // Rating modal interactions
        $(document).on('click', '.delice-recipe-rating-star-large', function() {
            handleStarClick($(this));
        });
        
        $(document).on('click', '.delice-recipe-rating-submit', function() {
            submitRating();
        });
        
        $(document).on('click', '.delice-recipe-rating-cancel', function() {
            closeRatingModal();
        });
        
        // Share menu items
        $(document).on('click', '.delice-recipe-share-item', function(e) {
            e.preventDefault();
            const platform = $(this).data('platform');
            shareRecipe(platform);
            $('.delice-recipe-share-menu').removeClass('active');
        });
    }
    
    /**
     * Toggle share menu
     */
    function toggleShareMenu(button) {
        const menu = button.siblings('.delice-recipe-share-menu');
        $('.delice-recipe-share-menu').not(menu).removeClass('active');
        menu.toggleClass('active');
    }
    
    /**
     * Share recipe on social platforms
     */
    function shareRecipe(platform) {
        const recipeTitle = document.title;
        const recipeUrl = window.location.href;
        const recipeDescription = $('meta[name="description"]').attr('content') || recipeTitle;
        const recipeImage = $('meta[property="og:image"]').attr('content') || '';
        
        let shareUrl = '';
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(recipeUrl)}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(recipeTitle)}&url=${encodeURIComponent(recipeUrl)}`;
                break;
            case 'pinterest':
                shareUrl = `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(recipeUrl)}&media=${encodeURIComponent(recipeImage)}&description=${encodeURIComponent(recipeDescription)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(recipeTitle + ' - ' + recipeUrl)}`;
                break;
            case 'email':
                shareUrl = `mailto:?subject=${encodeURIComponent(recipeTitle)}&body=${encodeURIComponent(recipeDescription + '\n\n' + recipeUrl)}`;
                break;
        }
        
        if (shareUrl) {
            if (platform === 'email') {
                window.location.href = shareUrl;
            } else {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }
    }
    
    /**
     * Open rating modal
     */
    function openRatingModal(button) {
        const recipeContainer = button.closest('[data-recipe-id]');
        const recipeId = recipeContainer.data('recipe-id');
        const recipeTitle = document.title.split(' - ')[0] || 'this recipe';
        
        // Create modal if it doesn't exist
        if ($('.delice-recipe-rating-modal').length === 0) {
            const modalHtml = `
                <div class="delice-recipe-rating-modal">
                    <div class="delice-recipe-rating-modal-content">
                        <h3 class="delice-recipe-rating-modal-title">Rate ${recipeTitle}</h3>
                        <p>How would you rate this recipe?</p>
                        <div class="delice-recipe-rating-stars-large">
                            <svg class="delice-recipe-rating-star-large" data-rating="1" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <svg class="delice-recipe-rating-star-large" data-rating="2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <svg class="delice-recipe-rating-star-large" data-rating="3" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <svg class="delice-recipe-rating-star-large" data-rating="4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <svg class="delice-recipe-rating-star-large" data-rating="5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <div class="delice-recipe-rating-actions">
                            <button class="delice-recipe-rating-submit">Submit Rating</button>
                            <button class="delice-recipe-rating-cancel">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
        }
        
        // Store recipe ID and show modal
        $('.delice-recipe-rating-modal').data('recipe-id', recipeId).addClass('active');
        
        // Reset stars
        $('.delice-recipe-rating-star-large').removeClass('active');
        $('.delice-recipe-rating-modal').removeData('selected-rating');
    }
    
    /**
     * Close rating modal
     */
    function closeRatingModal() {
        $('.delice-recipe-rating-modal').removeClass('active');
    }
    
    /**
     * Handle star click in rating modal
     */
    function handleStarClick(star) {
        const rating = star.data('rating');
        
        // Remove active class from all stars
        $('.delice-recipe-rating-star-large').removeClass('active');
        
        // Add active class to clicked star and all previous stars
        $('.delice-recipe-rating-star-large').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('active');
            }
        });
        
        // Store selected rating
        $('.delice-recipe-rating-modal').data('selected-rating', rating);
    }
    
    /**
     * Submit rating
     */
    function submitRating() {
        const modal = $('.delice-recipe-rating-modal');
        const recipeId = modal.data('recipe-id');
        const rating = modal.data('selected-rating');
        
        if (!rating) {
            alert('Please select a rating');
            return;
        }
        
        // Check if rating functionality exists
        if (typeof deliceRecipeData !== 'undefined') {
            // Use existing rating system
            $.ajax({
                url: deliceRecipeData.ajaxurl,
                method: 'POST',
                data: {
                    action: 'delice_save_rating',
                    nonce: deliceRecipeData.nonce,
                    recipe_id: recipeId,
                    rating: rating
                },
                success: function(response) {
                    if (response.success) {
                        alert('Thank you for your rating!');
                        closeRatingModal();
                        // Update existing rating display if present
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to save rating');
                    }
                },
                error: function() {
                    alert('Failed to save rating');
                }
            });
        } else {
            // Fallback - just close modal
            alert('Thank you for your rating!');
            closeRatingModal();
        }
    }
    
})(jQuery);

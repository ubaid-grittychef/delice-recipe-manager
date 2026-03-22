
/**
 * Recipe Rating and Review System - Fixed version with proper timing
 */
(function($) {
    'use strict';
    
    // Prevent multiple initializations
    window.Delice = window.Delice || {};
    if (window.Delice.ratingLoaded) {
        return;
    }
    window.Delice.ratingLoaded = true;
    
    var DELICE_DEBUG = (typeof deliceRecipeData !== 'undefined' && deliceRecipeData.debug);
    var previousFocusElement = null;

    $(document).ready(function() {
        // Initialize rating system
        initRatingModal();
        initScrollToReviews();
        initReviewForm();
        initImagePreview();
        loadExistingReviews();
    });
    
    /**
     * Initialize rating modal functionality
     */
    function initRatingModal() {
        // Open rating modal
        $(document).on('click', '[data-action="open-rating-modal"]', function(e) {
            e.preventDefault();
            const recipeId = $(this).data('recipe-id');
            openRatingModal(recipeId);
        });
        
        // Close rating modal
        $(document).on('click', '.delice-recipe-rating-cancel, .delice-recipe-rating-modal', function(e) {
            if (e.target === this) {
                closeRatingModal();
            }
        });

        // Keyboard: Escape to close, Tab trap within modal
        $(document).on('keydown', '.delice-recipe-rating-modal', function(e) {
            if (!$(this).hasClass('active')) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                closeRatingModal();
                return;
            }

            if (e.key === 'Tab') {
                var $focusable = $(this).find('button, [tabindex="0"], input, textarea, select, a[href]').filter(':visible');
                if (!$focusable.length) return;
                var first = $focusable.first()[0];
                var last  = $focusable.last()[0];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
        });

        // Touch: swipe down to dismiss modal
        var touchStartY = 0;
        $(document).on('touchstart', '.delice-recipe-rating-modal-content', function(e) {
            touchStartY = e.originalEvent.touches[0].clientY;
        });
        $(document).on('touchend', '.delice-recipe-rating-modal-content', function(e) {
            var deltaY = e.originalEvent.changedTouches[0].clientY - touchStartY;
            if (deltaY > 100) {
                closeRatingModal();
            }
        });

        // Modal rating stars click
        $(document).on('click', '.delice-recipe-rating-modal .delice-rating-star', function() {
            const rating = $(this).data('rating');
            const $modal = $(this).closest('.delice-recipe-rating-modal');
            
            // Update modal stars visual state
            $modal.find('.delice-rating-star').each(function(index) {
                if ((index + 1) <= rating) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
            
            // Store rating
            $modal.data('selected-rating', rating);
            
            // Show submit button if not already visible
            let $submitBtn = $modal.find('.delice-recipe-rating-submit');
            if (!$submitBtn.length) {
                $modal.find('.delice-recipe-rating-buttons').prepend(
                    '<button type="button" class="delice-recipe-rating-submit">Submit Rating</button>'
                );
            }
        });
        
        // Submit rating button click - FIXED: Don't scroll immediately
        $(document).on('click', '.delice-recipe-rating-submit', function(e) {
            e.preventDefault();
            const $btn = $(this);
            // Prevent double-submission: ignore click if already in flight.
            if ($btn.prop('disabled')) return;

            const $modal = $btn.closest('.delice-recipe-rating-modal');
            const recipeId = $modal.data('recipe-id');
            const rating = $modal.data('selected-rating');

            if (!rating) {
                showModalMessage('Please select a rating first.', 'error');
                return;
            }

            // Show loading state in modal
            const originalText = $btn.text();
            $btn.text('Submitting...').prop('disabled', true);

            // Submit rating first, THEN scroll on success
            submitModalRating(recipeId, rating, $btn, originalText);
        });
    }
    
    /**
     * Open rating modal
     */
    function openRatingModal(recipeId) {
        // Store the element that triggered the modal for focus restoration
        previousFocusElement = document.activeElement;

        let $modal = $('.delice-recipe-rating-modal');

        if (!$modal.length) {
            // Create modal if it doesn't exist
            const modalHtml = `
                <div class="delice-recipe-rating-modal" role="dialog" aria-modal="true" aria-labelledby="delice-rating-modal-title" data-recipe-id="${recipeId}">
                    <div class="delice-recipe-rating-modal-content">
                        <div class="delice-modal-message"></div>
                        <h3 id="delice-rating-modal-title">Rate this Recipe</h3>
                        <p>Please rate this recipe from 1 to 5 stars</p>
                        <div class="delice-recipe-rating-stars" data-recipe-id="${recipeId}">
                            <span class="delice-rating-star" data-rating="1" tabindex="0" role="button" aria-label="1 star"><i class="fas fa-star"></i></span>
                            <span class="delice-rating-star" data-rating="2" tabindex="0" role="button" aria-label="2 stars"><i class="fas fa-star"></i></span>
                            <span class="delice-rating-star" data-rating="3" tabindex="0" role="button" aria-label="3 stars"><i class="fas fa-star"></i></span>
                            <span class="delice-rating-star" data-rating="4" tabindex="0" role="button" aria-label="4 stars"><i class="fas fa-star"></i></span>
                            <span class="delice-rating-star" data-rating="5" tabindex="0" role="button" aria-label="5 stars"><i class="fas fa-star"></i></span>
                        </div>
                        <div class="delice-recipe-rating-buttons">
                            <button type="button" class="delice-recipe-rating-cancel">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);
            $modal = $('.delice-recipe-rating-modal');
        } else {
            $modal.attr('data-recipe-id', recipeId);
            $modal.find('.delice-recipe-rating-stars').attr('data-recipe-id', recipeId);
        }

        // Show modal
        $modal.addClass('active');
        $('body').addClass('modal-open');

        // Reset modal state
        $modal.find('.delice-rating-star').removeClass('selected hover');
        $modal.find('.delice-recipe-rating-submit').remove();
        $modal.removeData('selected-rating');
        hideModalMessage();

        // Focus the first star for keyboard accessibility
        setTimeout(function() {
            $modal.find('.delice-rating-star').first().trigger('focus');
        }, 100);
    }
    
    /**
     * Close rating modal and restore focus to the triggering element
     */
    function closeRatingModal() {
        $('.delice-recipe-rating-modal').removeClass('active');
        $('body').removeClass('modal-open');
        if (previousFocusElement && typeof previousFocusElement.focus === 'function') {
            previousFocusElement.focus();
        }
        previousFocusElement = null;
    }

    /**
     * Submit rating from modal - FIXED: proper timing and error handling
     */
    function submitModalRating(recipeId, rating, $btn, originalText) {
        $.ajax({
            url: deliceRecipeData.ajaxurl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'delice_save_rating',
                recipe_id: recipeId,
                rating: rating,
                nonce: deliceRecipeData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Store rating for comment section
                    sessionStorage.setItem('delice_rating_' + recipeId, rating);
                    
                    // Update comment section rating display
                    updateCommentSectionRating(recipeId, rating);
                    
                    // NOW scroll to comment section
                    scrollToCommentSection(recipeId);
                    
                    // Close modal after scroll starts (don't restore focus since we scrolled)
                    setTimeout(function() {
                        $('.delice-recipe-rating-modal').removeClass('active');
                        $('body').removeClass('modal-open');
                        previousFocusElement = null;
                    }, 300);
                    
                } else {
                    // Show error in modal, don't scroll
                    showModalMessage(response.data.message || 'Error saving rating.', 'error');
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                if (DELICE_DEBUG) console.error('Modal rating error:', error);
                showModalMessage('An error occurred. Please try again.', 'error');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    }
    
    /**
     * Update comment section with selected rating (read-only)
     */
    function updateCommentSectionRating(recipeId, rating) {
        const $reviewSection = $('#reviewSection-' + recipeId);
        const $ratingDisplay = $reviewSection.find('.delice-selected-rating-display');
        
        if ($ratingDisplay.length) {
            // Create stars HTML
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                const activeClass = i <= rating ? ' selected' : '';
                starsHtml += '<span class="delice-display-star' + activeClass + '"><i class="fas fa-star"></i></span>';
            }
            
            $ratingDisplay.html(`
                <div class="delice-rating-selected-info">
                    <span class="delice-rating-label">Your Rating:</span>
                    <div class="delice-rating-stars-display">${starsHtml}</div>
                    <span class="delice-rating-text">${rating}/5 stars</span>
                </div>
            `).show();
        }
        
        // Focus on comment textarea
        setTimeout(function() {
            $reviewSection.find('textarea[name="comment"]').focus();
        }, 500);
    }
    
    /**
     * Show message in modal
     */
    function showModalMessage(message, type) {
        const $messageEl = $('.delice-modal-message');
        $messageEl
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .show();
    }
    
    /**
     * Hide modal message
     */
    function hideModalMessage() {
        $('.delice-modal-message').hide();
    }
    
    /**
     * Scroll to comment section
     */
    function scrollToCommentSection(recipeId) {
        const $reviewSection = $('#reviewSection-' + recipeId);
        
        if ($reviewSection.length) {
            // Smooth scroll to review section
            $('html, body').animate({
                scrollTop: $reviewSection.offset().top - 80
            }, 800, function() {
                // Highlight the section briefly
                $reviewSection.addClass('delice-highlight-section');
                
                // Remove highlight after 2 seconds
                setTimeout(function() {
                    $reviewSection.removeClass('delice-highlight-section');
                }, 2000);
            });
        }
    }
    
    /**
     * Initialize scroll to reviews functionality
     */
    function initScrollToReviews() {
        $(document).on('click', '[data-action="scroll-to-reviews"]', function(e) {
            e.preventDefault();
            const recipeId = $(this).data('recipe-id');
            scrollToCommentSection(recipeId);
        });
    }
    
    /**
     * Initialize image preview functionality - IMPROVED
     */
    function initImagePreview() {
        $(document).on('change', 'input[name="review_image"]', function() {
            const file = this.files[0];
            const $container = $(this).closest('.delice-recipe-review-image');
            
            // Remove existing preview
            $container.find('.delice-image-preview').remove();
            
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    $(this).val('');
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, or GIF)');
                    $(this).val('');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewHtml = `
                        <div class="delice-image-preview">
                            <img src="${e.target.result}" alt="Review image preview" />
                            <button type="button" class="delice-remove-image" title="Remove image">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    $container.append(previewHtml);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Remove image preview
        $(document).on('click', '.delice-remove-image', function() {
            const $container = $(this).closest('.delice-recipe-review-image');
            $container.find('input[name="review_image"]').val('');
            $(this).closest('.delice-image-preview').remove();
        });
    }
    
    /**
     * Initialize review form submission
     */
    function initReviewForm() {
        $(document).on('submit', '.delice-recipe-review-form', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const recipeId = $form.data('recipe-id');
            const comment = $form.find('textarea[name="comment"]').val().trim();
            
            if (!comment) {
                showMessage(recipeId, 'Please enter a comment.', 'error');
                return;
            }
            
            // Get rating from session storage
            const selectedRating = sessionStorage.getItem('delice_rating_' + recipeId);
            
            if (!selectedRating) {
                showMessage(recipeId, 'Please rate the recipe first using the popup.', 'error');
                return;
            }
            
            // Create FormData for file upload
            const formData = new FormData();
            formData.append('action', 'delice_save_review');
            formData.append('recipe_id', recipeId);
            formData.append('comment', comment);
            formData.append('rating', selectedRating);
            formData.append('nonce', deliceRecipeData.nonce);
            
            // Add image if selected
            const imageFile = $form.find('input[type="file"]')[0].files[0];
            if (imageFile) {
                formData.append('review_image', imageFile);
            }
            
            // Show loading state
            const $submitBtn = $form.find('.delice-recipe-review-submit');
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            $submitBtn.prop('disabled', true);
            
            $.ajax({
                url: deliceRecipeData.ajaxurl,
                type: 'POST',
                timeout: 15000,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showSuccessMessage(recipeId);
                        
                        // Clear form
                        $form[0].reset();
                        $form.find('.delice-image-preview').remove();
                        
                        // Clear stored rating and rating display
                        sessionStorage.removeItem('delice_rating_' + recipeId);
                        $('#reviewSection-' + recipeId + ' .delice-selected-rating-display').hide();
                        
                        // Reload reviews
                        loadReviewsForRecipe(recipeId);
                        
                    } else {
                        showMessage(recipeId, response.data.message || 'Error submitting review.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    if (DELICE_DEBUG) console.error('Review submission error:', error);
                    showMessage(recipeId, 'An error occurred. Please try again.', 'error');
                },
                complete: function() {
                    // Reset button state
                    $submitBtn.html(originalText);
                    $submitBtn.prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(recipeId) {
        const $successDiv = $('#reviewSection-' + recipeId + ' .delice-recipe-review-success');
        $successDiv.slideDown(300);
        
        // Hide after 5 seconds
        setTimeout(function() {
            $successDiv.slideUp(300);
        }, 5000);
    }
    
    function showMessage(recipeId, message, type) {
        // Create or update message element
        let $messageEl = $('#reviewSection-' + recipeId + ' .delice-recipe-message');
        
        if (!$messageEl.length) {
            $messageEl = $('<div class="delice-recipe-message"></div>');
            $('#reviewSection-' + recipeId).prepend($messageEl);
        }
        
        $messageEl
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .slideDown(300);
        
        // Auto-hide after 4 seconds
        setTimeout(function() {
            $messageEl.slideUp(300);
        }, 4000);
    }
    
    function loadExistingReviews() {
        $('.delice-recipe-container').each(function() {
            const recipeId = $(this).data('recipe-id');
            if (recipeId) {
                loadReviewsForRecipe(recipeId);
            }
        });
    }
    
    function loadReviewsForRecipe(recipeId, retryCount) {
        retryCount = retryCount || 0;

        // Show loading state on first attempt
        var $display = $('#reviewsDisplay-' + recipeId);
        if ($display.length && retryCount === 0) {
            $display.html(
                '<div class="delice-reviews-loading">' +
                '<span class="delice-spinner"></span> Loading reviews&hellip;' +
                '</div>'
            ).show();
        }

        $.ajax({
            url: deliceRecipeData.ajaxurl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'delice_get_reviews',
                recipe_id: recipeId
            },
            success: function(response) {
                if (response.success && response.data.reviews.length > 0) {
                    displayReviews(recipeId, response.data.reviews);
                } else if ($display.length) {
                    $display.empty().hide();
                }
            },
            error: function(xhr, status, error) {
                if (DELICE_DEBUG) console.error('Error loading reviews:', error);
                // Retry once after 2 seconds
                if (retryCount < 1) {
                    setTimeout(function() {
                        loadReviewsForRecipe(recipeId, retryCount + 1);
                    }, 2000);
                } else if ($display.length) {
                    $display.html(
                        '<p class="delice-reviews-error">Could not load reviews. Please refresh to try again.</p>'
                    ).show();
                }
            }
        });
    }
    
    /**
     * Escape a string for safe insertion into HTML.
     */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function displayReviews(recipeId, reviews) {
        const $reviewsDisplay = $('#reviewsDisplay-' + recipeId);

        if (!$reviewsDisplay.length) {
            return;
        }

        let reviewsHtml = '<h3>Customer Reviews (' + parseInt(reviews.length, 10) + ')</h3><div class="delice-reviews-list">';

        reviews.forEach(function(review) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                const activeClass = i <= review.rating ? ' active' : '';
                starsHtml += '<i class="fas fa-star' + activeClass + '"></i>';
            }

            let imageHtml = '';
            if (review.image_url) {
                imageHtml = '<div class="delice-review-image"><img src="' + escHtml(review.image_url) + '" alt="Review image" loading="lazy" /></div>';
            }

            reviewsHtml +=
                '<div class="delice-review-item">' +
                    '<div class="delice-review-header">' +
                        '<span class="delice-review-author">' + escHtml(review.user_name) + '</span>' +
                        '<div class="delice-review-rating">' + starsHtml + '</div>' +
                        '<span class="delice-review-date">' + escHtml(review.date) + '</span>' +
                    '</div>' +
                    '<div class="delice-review-comment">' + escHtml(review.comment) + '</div>' +
                    imageHtml +
                '</div>';
        });

        reviewsHtml += '</div>';

        $reviewsDisplay.html(reviewsHtml).show();
    }
    
})(jQuery);

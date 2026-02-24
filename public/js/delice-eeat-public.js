/**
 * E-E-A-T Public JavaScript
 * 
 * @package Delice_Recipe_Manager
 * @since 1.1.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Handle "I Made This" button click
        $('.delice-submit-cook-button').on('click', function() {
            var recipeId = $(this).data('recipe-id');
            showSubmitCookModal(recipeId);
        });
        
    });
    
    /**
     * Show "I Made This" submission modal
     */
    function showSubmitCookModal(recipeId) {
        var modalHTML = `
            <div id="delice-cook-modal" class="delice-modal">
                <div class="delice-modal-content">
                    <span class="delice-modal-close">&times;</span>
                    <h2>Share Your Cook!</h2>
                    <form id="delice-cook-form">
                        <input type="hidden" name="recipe_id" value="${recipeId}">
                        
                        <div class="form-field">
                            <label>Your Name *</label>
                            <input type="text" name="user_name" required>
                        </div>
                        
                        <div class="form-field">
                            <label>Email</label>
                            <input type="email" name="user_email">
                            <small>Optional - Won't be displayed publicly</small>
                        </div>
                        
                        <div class="form-field">
                            <label>How did it turn out?</label>
                            <div class="star-rating">
                                <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                                <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                                <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                                <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                                <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                            </div>
                        </div>
                        
                        <div class="form-field">
                            <label>Tell us about it</label>
                            <textarea name="modifications" rows="4" placeholder="Any changes you made or tips to share?"></textarea>
                        </div>
                        
                        <div class="form-field">
                            <label>
                                <input type="checkbox" name="would_recommend" value="1" checked>
                                I would make this again
                            </label>
                        </div>
                        
                        <button type="submit" class="delice-submit-btn">Submit</button>
                    </form>
                    <div id="delice-cook-message"></div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
        
        // Show modal
        $('#delice-cook-modal').fadeIn();
        
        // Close modal handlers
        $('.delice-modal-close, #delice-cook-modal').on('click', function(e) {
            if (e.target === this) {
                $('#delice-cook-modal').fadeOut(function() {
                    $(this).remove();
                });
            }
        });
        
        // Handle form submission
        $('#delice-cook-form').on('submit', function(e) {
            e.preventDefault();
            submitCook($(this));
        });
    }
    
    /**
     * Submit cook via AJAX
     */
    function submitCook($form) {
        var $button = $form.find('button[type="submit"]');
        var $message = $('#delice-cook-message');
        
        $button.prop('disabled', true).text('Submitting...');
        $message.empty();
        
        var formData = $form.serialize();
        formData += '&action=delice_submit_user_cook';
        formData += '&nonce=' + deliceEEATPublic.nonce;
        
        $.post(deliceEEATPublic.ajaxUrl, formData, function(response) {
            if (response.success) {
                $message.html('<p class="success">✓ ' + response.data.message + '</p>');
                $form[0].reset();
                
                setTimeout(function() {
                    $('#delice-cook-modal').fadeOut(function() {
                        $(this).remove();
                    });
                }, 2000);
            } else {
                $message.html('<p class="error">✗ ' + (response.data.message || 'Failed to submit') + '</p>');
                $button.prop('disabled', false).text('Submit');
            }
        }).fail(function() {
            $message.html('<p class="error">✗ Network error. Please try again.</p>');
            $button.prop('disabled', false).text('Submit');
        });
    }
    
})(jQuery);

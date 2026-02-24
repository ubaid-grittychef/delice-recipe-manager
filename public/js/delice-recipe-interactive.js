/**
 * Delice Recipe Interactive - Clean & Simple
 */
(function($) {
    'use strict';
    
    // FAQ Toggle
    $(document).on('click', '.delice-recipe-modern-faq-question', function(e) {
        e.preventDefault();
        const $item = $(this).closest('.delice-recipe-modern-faq-item');
        const isOpen = $item.hasClass('faq-open');
        
        // Close all
        $('.delice-recipe-modern-faq-item').removeClass('faq-open');
        
        // Toggle this one
        if (!isOpen) {
            $item.addClass('faq-open');
        }
    });
    
    // Copy Ingredients
    $('.delice-recipe-copy-ingredients').on('click', function() {
        const ingredients = [];
        $('.delice-recipe-ingredient-name').each(function() {
            ingredients.push($(this).text().trim());
        });
        
        const text = ingredients.join('\n');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showMessage('Copied!');
            });
        } else {
            const textarea = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            textarea.remove();
            showMessage('Copied!');
        }
    });
    
    // Save ingredient checkboxes
    $('.delice-recipe-ingredient-checkbox').on('change', function() {
        const id = $(this).attr('id');
        const checked = $(this).prop('checked');
        localStorage.setItem('ingredient_' + id, checked ? '1' : '0');
    });
    
    // Restore checkboxes
    $('.delice-recipe-ingredient-checkbox').each(function() {
        const id = $(this).attr('id');
        const saved = localStorage.getItem('ingredient_' + id);
        if (saved === '1') {
            $(this).prop('checked', true);
        }
    });
    
    // Show message
    function showMessage(text) {
        const $msg = $('<div class="delice-copy-message">' + text + '</div>');
        $('body').append($msg);
        setTimeout(() => $msg.fadeOut(() => $msg.remove()), 2000);
    }
    
})(jQuery);

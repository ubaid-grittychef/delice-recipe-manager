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

    // Copy Ingredients — delegated binding so it works on all rendered cards
    $(document).on('click', '.delice-recipe-copy-ingredients', function() {
        const $btn = $(this);
        // Scope ingredient collection to this specific recipe card only
        const $card = $btn.closest('[data-recipe-id]');
        const $source = $card.length ? $card : $(document);

        const ingredients = [];
        $source.find('.delice-recipe-ingredient-name').each(function() {
            ingredients.push($(this).text().trim());
        });

        const text = ingredients.join('\n');

        // Visual feedback on the button itself
        const originalHtml = $btn.html();
        $btn.prop('disabled', true);

        function onCopied() {
            $btn.html('<span>&#10003; Copied!</span>');
            setTimeout(function() {
                $btn.html(originalHtml).prop('disabled', false);
            }, 1800);
            showMessage('Copied!');
        }

        function onFailed() {
            $btn.prop('disabled', false);
            showMessage('Copy failed');
        }

        if (window.isSecureContext && navigator.clipboard) {
            navigator.clipboard.writeText(text).then(onCopied, onFailed);
        } else {
            // Fallback for HTTP or older browsers
            try {
                const $textarea = $('<textarea>').val(text).css({
                    position: 'fixed', top: '-9999px', left: '-9999px'
                }).appendTo('body');
                $textarea[0].select();
                document.execCommand('copy');
                $textarea.remove();
                onCopied();
            } catch (err) {
                onFailed();
            }
        }
    });

    // Save ingredient checkboxes
    $(document).on('change', '.delice-recipe-ingredient-checkbox', function() {
        const id = $(this).attr('id');
        const checked = $(this).prop('checked');
        localStorage.setItem('ingredient_' + id, checked ? '1' : '0');
    });

    // Restore checkboxes on page load
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
        setTimeout(function() { $msg.fadeOut(function() { $msg.remove(); }); }, 2000);
    }

})(jQuery);

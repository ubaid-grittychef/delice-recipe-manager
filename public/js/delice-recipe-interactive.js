/**
 * Delice Recipe Interactive
 *
 * Uses direct binding (not document delegation) so theme handlers that call
 * e.stopPropagation() on ancestor elements cannot swallow our events.
 *
 * Uses element.style.setProperty('display', '...', 'important') for FAQ
 * show/hide so that inline-style !important wins the CSS cascade regardless
 * of how many ID-chained selectors the active theme uses.
 */
(function ($) {
    'use strict';

    /* ── FAQ Accordion ─────────────────────────────────────────────────────── */

    function initFAQ() {
        var questions = document.querySelectorAll('.delice-recipe-modern-faq-question');
        if (!questions.length) return;

        // Force-hide every answer via inline style so the external CSS
        // max-height:0 / display:none approach can't be beaten by theme overrides.
        document.querySelectorAll('.delice-recipe-modern-faq-answer').forEach(function (ans) {
            ans.style.setProperty('display', 'none', 'important');
            ans.style.setProperty('overflow', 'hidden', 'important');
        });

        // Direct binding — no delegation so stopPropagation() on ancestors is irrelevant.
        questions.forEach(function (button) {
            button.addEventListener('click', function () {
                var faqItem = this.closest('.delice-recipe-modern-faq-item');
                if (!faqItem) return;

                var isOpen = faqItem.classList.contains('faq-open');

                // Close every open item on the page.
                document.querySelectorAll('.delice-recipe-modern-faq-item').forEach(function (item) {
                    item.classList.remove('faq-open');
                    var btn = item.querySelector('.delice-recipe-modern-faq-question');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                    var ans = item.querySelector('.delice-recipe-modern-faq-answer');
                    if (ans) {
                        ans.style.setProperty('display', 'none', 'important');
                        ans.style.setProperty('overflow', 'hidden', 'important');
                    }
                });

                // If this item was closed, open it now.
                if (!isOpen) {
                    faqItem.classList.add('faq-open');
                    this.setAttribute('aria-expanded', 'true');
                    var answer = faqItem.querySelector('.delice-recipe-modern-faq-answer');
                    if (answer) {
                        // Use inline style !important so it beats any theme rule
                        // (inline style specificity always wins the cascade).
                        answer.style.setProperty('display', 'block', 'important');
                        answer.style.setProperty('overflow', 'visible', 'important');
                    }
                }
            });
        });
    }

    /* ── Copy Ingredients ─────────────────────────────────────────────────── */

    function initCopyIngredients() {
        var buttons = document.querySelectorAll('.delice-recipe-copy-ingredients');
        if (!buttons.length) return;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var $btn  = $(this);
                // Scope ingredient lookup to THIS recipe card only.
                var $card = $btn.closest('[data-recipe-id]');
                var $root = $card.length ? $card : $(document);

                var ingredients = [];
                $root.find('.delice-recipe-ingredient-name').each(function () {
                    var t = $(this).text().trim();
                    if (t) ingredients.push(t);
                });

                var text        = ingredients.join('\n');
                var originalHtml = $btn.html();
                $btn.prop('disabled', true);

                function onCopied() {
                    $btn.html('<span>&#10003; Copied!</span>');
                    setTimeout(function () {
                        $btn.html(originalHtml).prop('disabled', false);
                    }, 1800);
                    showMessage('Copied!');
                }

                function execCommandFallback() {
                    try {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
                        document.body.appendChild(ta);
                        ta.focus();
                        ta.select();
                        var ok = document.execCommand('copy');
                        document.body.removeChild(ta);
                        if (ok) { onCopied(); } else { onFailed(); }
                    } catch (err) {
                        onFailed();
                    }
                }

                // Try the modern Clipboard API first; if it rejects (e.g. permission
                // denied when logged in, or clipboard blocked by browser policy) fall
                // back to the legacy execCommand approach rather than just showing an error.
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onCopied, execCommandFallback);
                } else {
                    execCommandFallback();
                }
            });
        });
    }

    /* ── Ingredient Checkboxes ────────────────────────────────────────────── */

    function initCheckboxes() {
        var checkboxes = document.querySelectorAll('.delice-recipe-ingredient-checkbox');
        if (!checkboxes.length) return;

        // Restore saved state.
        checkboxes.forEach(function (cb) {
            var id = cb.getAttribute('id');
            if (id && localStorage.getItem('ingredient_' + id) === '1') {
                cb.checked = true;
            }
        });

        // Persist state on change — direct binding.
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var id = this.getAttribute('id');
                if (id) {
                    try {
                        localStorage.setItem('ingredient_' + id, this.checked ? '1' : '0');
                    } catch (e) { /* storage unavailable (private browsing / quota) */ }
                }
            });
        });
    }

    /* ── Toast helper ─────────────────────────────────────────────────────── */

    function showMessage(text) {
        var $msg = $('<div class="delice-copy-message">' + text + '</div>');
        $('body').append($msg);
        setTimeout(function () { $msg.fadeOut(function () { $msg.remove(); }); }, 2000);
    }

    /* ── Boot ─────────────────────────────────────────────────────────────── */

    // Script is enqueued in the footer so the DOM is already parsed, but wrap
    // in $() to handle edge cases where it runs before DOMContentLoaded.
    $(function () {
        initFAQ();
        initCopyIngredients();
        initCheckboxes();
    });

})(jQuery);

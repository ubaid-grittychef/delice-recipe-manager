/**
 * Delice Recipe — Jump to Recipe Button
 * Auto-injects a floating button above the recipe card.
 * Hides when the recipe card enters the viewport (IntersectionObserver).
 * v3.6.0
 */
(function () {
    'use strict';

    var btn = null;

    function createButton(target) {
        var label = (typeof deliceRecipe !== 'undefined' && deliceRecipe.jumpToRecipe)
            ? deliceRecipe.jumpToRecipe
            : 'Jump to Recipe';

        btn = document.createElement('a');
        btn.href = '#' + target.id;
        btn.className = 'delice-jump-to-recipe-btn';
        btn.setAttribute('aria-label', label);
        btn.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" width="16" height="16">' +
            '<polyline points="6 9 12 15 18 9"/></svg>' +
            '<span>' + label + '</span>';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        document.body.appendChild(btn);
    }

    function init() {
        // Find the first recipe container on the page
        var container = document.querySelector('.delice-recipe-container[id]');
        if (!container) return;

        createButton(container);

        // Hide button once the recipe card is in view
        if (typeof IntersectionObserver !== 'undefined') {
            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (btn) {
                            btn.classList.toggle('delice-jump-btn-hidden', entry.isIntersecting);
                        }
                    });
                },
                { rootMargin: '0px 0px -40px 0px', threshold: 0.1 }
            );
            observer.observe(container);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

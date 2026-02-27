/**
 * Delice Recipe — Jump to Recipe Button
 * Inserts an inline button directly above the recipe card so it appears
 * naturally in the page flow without overlapping the admin bar or nav.
 * v3.6.1
 */
(function () {
    'use strict';

    function init() {
        // Find the first recipe container on the page.
        var container = document.querySelector('.delice-recipe-container[id]');
        if (!container) return;

        // Don't inject if one already exists (e.g. theme added its own).
        if (document.querySelector('.delice-jump-to-recipe-btn')) return;

        var label = (typeof deliceRecipe !== 'undefined' && deliceRecipe.jumpToRecipe)
            ? deliceRecipe.jumpToRecipe
            : 'Jump to Recipe';

        var btn = document.createElement('a');
        btn.href = '#' + container.id;
        btn.className = 'delice-jump-to-recipe-btn';
        btn.setAttribute('aria-label', label);
        btn.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" width="14" height="14">' +
            '<polyline points="6 9 12 15 18 9"/></svg>' +
            '<span>' + label + '</span>';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Insert inline, directly above the recipe card.
        container.parentNode.insertBefore(btn, container);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

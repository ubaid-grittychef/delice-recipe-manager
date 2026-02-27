/**
 * Delice Recipe — Ingredient Checklist Persistence
 * Saves/restores checkbox state in localStorage per recipe.
 * v3.6.0
 */
(function () {
    'use strict';

    function getStorageKey(recipeId) {
        return 'delice_checklist_' + recipeId;
    }

    function saveState(recipeId, checkboxes) {
        var state = {};
        checkboxes.forEach(function (cb) {
            state[cb.id] = cb.checked;
        });
        try {
            localStorage.setItem(getStorageKey(recipeId), JSON.stringify(state));
        } catch (e) { /* storage blocked */ }
    }

    function restoreState(recipeId, checkboxes) {
        var raw;
        try {
            raw = localStorage.getItem(getStorageKey(recipeId));
        } catch (e) { return; }
        if (!raw) return;
        var state;
        try { state = JSON.parse(raw); } catch (e) { return; }
        checkboxes.forEach(function (cb) {
            if (state[cb.id] !== undefined) {
                cb.checked = state[cb.id];
                // Reflect checked state visually via parent label class
                var label = cb.closest ? cb.closest('label') : null;
                if (label && cb.checked) label.classList.add('delice-ingredient-checked');
            }
        });
    }

    function initRecipe(container) {
        var recipeId = container.dataset.recipeId;
        if (!recipeId) return;

        var checkboxes = Array.from(
            container.querySelectorAll('.delice-recipe-ingredient-checkbox')
        );
        if (!checkboxes.length) return;

        restoreState(recipeId, checkboxes);

        container.addEventListener('change', function (e) {
            if (!e.target.classList.contains('delice-recipe-ingredient-checkbox')) return;
            var label = e.target.closest ? e.target.closest('label') : null;
            if (label) {
                if (e.target.checked) label.classList.add('delice-ingredient-checked');
                else label.classList.remove('delice-ingredient-checked');
            }
            saveState(recipeId, checkboxes);
        });
    }

    function init() {
        var containers = document.querySelectorAll(
            '.delice-recipe-container[data-recipe-id]'
        );
        containers.forEach(initRecipe);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

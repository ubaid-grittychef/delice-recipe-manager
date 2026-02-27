/**
 * Delice Recipe — Servings Scaler
 * Live-scales ingredient quantities when the user adjusts the servings count.
 * v3.6.0
 */
(function () {
    'use strict';

    var FRACTIONS = [
        [1, 4, '¼'],
        [1, 3, '⅓'],
        [1, 2, '½'],
        [2, 3, '⅔'],
        [3, 4, '¾'],
    ];

    function toFraction(val) {
        if (val === 0) return '0';
        var whole = Math.floor(val);
        var frac  = val - whole;
        if (frac < 0.01) return whole ? String(whole) : '';

        var best = null;
        var bestDiff = Infinity;
        FRACTIONS.forEach(function (f) {
            var diff = Math.abs(frac - f[0] / f[1]);
            if (diff < bestDiff) { bestDiff = diff; best = f; }
        });

        if (best && bestDiff < 0.07) {
            return (whole ? whole + ' ' : '') + best[2];
        }
        // Fallback: show 1 decimal if not whole
        return parseFloat(val.toFixed(1)).toString();
    }

    function scaleQty(baseAmount, baseServings, newServings) {
        if (!baseAmount || baseServings <= 0) return baseAmount;
        var base = parseFloat(baseAmount);
        if (isNaN(base)) return baseAmount; // text like "a pinch"
        var scaled = (base / baseServings) * newServings;
        return toFraction(scaled);
    }

    function updateIngredients(container, newServings, baseServings) {
        var qtyEls = container.querySelectorAll('[data-base-amount]');
        qtyEls.forEach(function (el) {
            var baseAmt  = el.dataset.baseAmount;
            var baseUnit = el.dataset.baseUnit || '';
            var newAmt   = scaleQty(baseAmt, baseServings, newServings);
            el.textContent = newAmt ? (newAmt + (baseUnit ? ' ' + baseUnit : '')) : baseUnit;
        });
    }

    function initContainer(ctrl) {
        var valueEl  = ctrl.querySelector('.delice-servings-value');
        var minusBtn = ctrl.querySelector('.delice-servings-minus');
        var plusBtn  = ctrl.querySelector('.delice-servings-plus');
        if (!valueEl || !minusBtn || !plusBtn) return;

        var recipeContainer = ctrl.closest('.delice-recipe-container');
        if (!recipeContainer) return;

        var baseServings = parseInt(valueEl.dataset.base, 10) || 1;
        var current      = baseServings;

        function update(newVal) {
            newVal = Math.max(1, Math.min(100, newVal));
            current = newVal;
            valueEl.textContent = newVal;
            minusBtn.disabled = newVal <= 1;
            plusBtn.disabled  = newVal >= 100;
            updateIngredients(recipeContainer, current, baseServings);

            // Update aria-live region
            var liveEl = ctrl.querySelector('.delice-servings-live');
            if (liveEl) liveEl.textContent = newVal;
        }

        minusBtn.addEventListener('click', function () { update(current - 1); });
        plusBtn.addEventListener('click',  function () { update(current + 1); });

        // Allow direct input on the value display
        valueEl.addEventListener('dblclick', function () {
            var input = document.createElement('input');
            input.type  = 'number';
            input.min   = '1';
            input.max   = '100';
            input.value = current;
            input.className = 'delice-servings-input';
            valueEl.replaceWith(input);
            input.focus();
            input.select();

            function commit() {
                var v = parseInt(input.value, 10);
                if (!isNaN(v)) update(v);
                input.replaceWith(valueEl);
                valueEl.textContent = current;
            }
            input.addEventListener('blur', commit);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') commit();
                if (e.key === 'Escape') input.replaceWith(valueEl);
            });
        });
    }

    function init() {
        document.querySelectorAll('.delice-servings-control').forEach(function (ctrl) {
            var container = ctrl.closest('.delice-recipe-container');
            if (container) initContainer(ctrl);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

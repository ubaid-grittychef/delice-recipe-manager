/**
 * Delice Recipe — Servings Scaler
 * Live-scales ingredient quantities when the user adjusts the servings count.
 * v3.7.0 — robust amount parsing, handles unicode fractions, slash fractions,
 *           mixed numbers, and plain decimals.
 */
(function () {
    'use strict';

    // Unicode fraction → decimal mapping
    var UNICODE_FRACS = {
        '\u00bd': 0.5,   // ½
        '\u2153': 0.333, // ⅓
        '\u2154': 0.667, // ⅔
        '\u00bc': 0.25,  // ¼
        '\u00be': 0.75,  // ¾
        '\u215b': 0.125, // ⅛
        '\u215c': 0.375, // ⅜
        '\u215d': 0.625, // ⅝
        '\u215e': 0.875  // ⅞
    };

    // Decimal → unicode fraction display table
    var FRAC_DISPLAY = [
        [1, 8,  '\u215b'], [1, 4,  '\u00bc'], [1, 3,  '\u2153'],
        [3, 8,  '\u215c'], [1, 2,  '\u00bd'], [5, 8,  '\u215d'],
        [2, 3,  '\u2154'], [3, 4,  '\u00be'], [7, 8,  '\u215e']
    ];

    /**
     * Parse a human-readable amount string into a float.
     * Handles: "2", "1.5", "1/2", "1 1/2", "1½", "½", "a pinch" (→ NaN)
     */
    function parseAmount(str) {
        if (str === null || str === undefined) return NaN;
        var s = String(str).trim();
        if (!s) return NaN;

        // Substitute unicode fractions
        Object.keys(UNICODE_FRACS).forEach(function (ch) {
            s = s.split(ch).join(String(UNICODE_FRACS[ch]));
        });

        // Mixed number: "1 1/2" or "1 0.5"
        var mixed = s.match(/^(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/);
        if (mixed) {
            return parseFloat(mixed[1]) + parseFloat(mixed[2]) / parseFloat(mixed[3]);
        }
        var mixed2 = s.match(/^(\d+(?:\.\d+)?)\s+(0\.\d+)$/);
        if (mixed2) {
            return parseFloat(mixed2[1]) + parseFloat(mixed2[2]);
        }

        // Plain fraction: "3/4"
        var frac = s.match(/^(\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/);
        if (frac) {
            return parseFloat(frac[1]) / parseFloat(frac[2]);
        }

        // Decimal or integer — parseFloat ignores trailing non-numeric chars
        var n = parseFloat(s);
        return n; // NaN if unparseable (e.g. "a pinch", "to taste")
    }

    /**
     * Convert a decimal to a display string using unicode fractions where tidy.
     */
    function toDisplay(val) {
        if (val === 0) return '0';
        var whole = Math.floor(val);
        var frac  = val - whole;

        if (frac < 0.015) return whole ? String(whole) : '';

        var best = null, bestDiff = Infinity;
        FRAC_DISPLAY.forEach(function (f) {
            var diff = Math.abs(frac - f[0] / f[1]);
            if (diff < bestDiff) { bestDiff = diff; best = f; }
        });

        if (best && bestDiff < 0.065) {
            return (whole ? whole + ' ' : '') + best[2];
        }
        return parseFloat(val.toFixed(1)).toString();
    }

    function scaleAmount(baseStr, baseServings, newServings) {
        var base = parseAmount(baseStr);
        if (isNaN(base) || base === 0 || baseServings <= 0) return baseStr;
        return toDisplay((base / baseServings) * newServings);
    }

    function updateIngredients(recipeContainer, newServings, baseServings) {
        recipeContainer.querySelectorAll('[data-base-amount]').forEach(function (el) {
            var baseAmt  = el.getAttribute('data-base-amount');
            var baseUnit = el.getAttribute('data-base-unit') || '';
            if (baseAmt === null || baseAmt === '') {
                return; // no amount, nothing to scale
            }
            var newAmt = scaleAmount(baseAmt, baseServings, newServings);
            // Use non-breaking space between amount and unit for cleaner layout
            el.textContent = newAmt
                ? (newAmt + (baseUnit ? '\u00a0' + baseUnit : ''))
                : baseUnit;
        });
    }

    function initControl(ctrl) {
        var valueEl  = ctrl.querySelector('.delice-servings-value');
        var minusBtn = ctrl.querySelector('.delice-servings-minus');
        var plusBtn  = ctrl.querySelector('.delice-servings-plus');
        if (!valueEl || !minusBtn || !plusBtn) return;

        var recipeContainer = ctrl.closest('.delice-recipe-container');
        if (!recipeContainer) return;

        var baseServings = parseInt(valueEl.getAttribute('data-base'), 10) || 1;
        var current = baseServings;

        function applyUpdate(newVal) {
            newVal = Math.max(1, Math.min(100, newVal));
            current = newVal;
            valueEl.textContent = newVal;
            minusBtn.disabled = (newVal <= 1);
            plusBtn.disabled  = (newVal >= 100);
            updateIngredients(recipeContainer, current, baseServings);
            var liveEl = ctrl.querySelector('.delice-servings-live');
            if (liveEl) liveEl.textContent = newVal;
        }

        minusBtn.addEventListener('click', function () { applyUpdate(current - 1); });
        plusBtn.addEventListener('click',  function () { applyUpdate(current + 1); });

        // Double-click the number to type a custom value
        valueEl.addEventListener('dblclick', function () {
            var input = document.createElement('input');
            input.type      = 'number';
            input.min       = '1';
            input.max       = '100';
            input.value     = current;
            input.className = 'delice-servings-input';
            valueEl.replaceWith(input);
            input.focus();
            input.select();

            function commit() {
                var v = parseInt(input.value, 10);
                if (!isNaN(v) && v >= 1) applyUpdate(v);
                input.replaceWith(valueEl);
                valueEl.textContent = current;
            }
            input.addEventListener('blur', commit);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter')  commit();
                if (e.key === 'Escape') { input.replaceWith(valueEl); }
            });
        });
    }

    function init() {
        document.querySelectorAll('.delice-servings-control').forEach(function (ctrl) {
            if (ctrl.closest('.delice-recipe-container')) {
                initControl(ctrl);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

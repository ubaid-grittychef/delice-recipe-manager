/**
 * Delice Recipe — Cook Mode (Screen Wake Lock API)
 * Keeps the screen awake while cooking.
 * Gracefully degrades on unsupported browsers.
 * v3.6.0
 */
(function () {
    'use strict';

    var wakeLock = null;
    var cookModeActive = false;

    function getLang(key, fallback) {
        return (typeof deliceRecipe !== 'undefined' && deliceRecipe[key])
            ? deliceRecipe[key]
            : fallback;
    }

    function updateButton(btn, active) {
        var startText = getLang('cookModeStart', 'Start Cooking');
        var stopText  = getLang('cookModeStop', 'Stop Cooking');
        var textEl    = btn.querySelector('.delice-cook-mode-label');
        var iconEl    = btn.querySelector('.delice-cook-mode-icon');

        if (textEl) textEl.textContent = active ? stopText : startText;
        btn.classList.toggle('delice-cook-mode-active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');

        // Swap icon: flame (off) → check (on)
        if (iconEl) {
            if (active) {
                iconEl.innerHTML =
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" width="16" height="16">' +
                    '<polyline points="20 6 9 17 4 12"/></svg>';
            } else {
                iconEl.innerHTML =
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" width="16" height="16">' +
                    '<path d="M12 2c0 0-4 4-4 8a4 4 0 0 0 8 0c0-4-4-8-4-8z"/>' +
                    '<path d="M12 10c0 0-2 2-2 4a2 2 0 0 0 4 0c0-2-2-4-2-4z"/></svg>';
            }
        }
    }

    async function requestWakeLock(btn) {
        if (!('wakeLock' in navigator)) {
            return false;
        }
        try {
            wakeLock = await navigator.wakeLock.request('screen');
            cookModeActive = true;
            updateButton(btn, true);

            wakeLock.addEventListener('release', function () {
                // After a release event wakeLock.released is always true, so we
                // preserve cookModeActive separately and null out the lock handle.
                wakeLock = null;
            });
            return true;
        } catch (err) {
            return false;
        }
    }

    function releaseWakeLock(btn) {
        cookModeActive = false;
        if (wakeLock && !wakeLock.released) {
            wakeLock.release();
        }
        wakeLock = null;
        updateButton(btn, false);
    }

    // Re-acquire lock when tab becomes visible again
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible' && cookModeActive && !wakeLock) {
            var btn = document.querySelector('.delice-cook-mode-btn');
            if (btn) requestWakeLock(btn);
        }
    });

    function initButton(btn) {
        // Hide if API not supported
        if (!('wakeLock' in navigator)) {
            btn.style.display = 'none';
            return;
        }

        btn.addEventListener('click', function () {
            if (cookModeActive) {
                releaseWakeLock(btn);
            } else {
                requestWakeLock(btn);
            }
        });
    }

    function init() {
        var buttons = document.querySelectorAll('.delice-cook-mode-btn');
        buttons.forEach(initButton);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

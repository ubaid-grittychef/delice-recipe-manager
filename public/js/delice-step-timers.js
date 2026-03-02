/**
 * Delice Recipe — Inline Step Timers
 * Detects time patterns in instruction steps and adds clickable countdown timers.
 * v3.6.0
 */
(function () {
    'use strict';

    var timers = [];
    var timerPanel = null;

    var TIME_PATTERN = /(\d+)\s*(?:to\s*\d+\s*)?(hours?|hrs?|minutes?|mins?|seconds?|secs?)/gi;

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getLang(key, fallback) {
        return (typeof deliceRecipe !== 'undefined' && deliceRecipe[key])
            ? deliceRecipe[key]
            : fallback;
    }

    function parseToSeconds(amount, unit) {
        var u = unit.toLowerCase();
        if (u.startsWith('h')) return amount * 3600;
        if (u.startsWith('m')) return amount * 60;
        return amount;
    }

    function formatTime(secs) {
        var h = Math.floor(secs / 3600);
        var m = Math.floor((secs % 3600) / 60);
        var s = secs % 60;
        var parts = [];
        if (h) parts.push(h + 'h');
        parts.push((m < 10 && h ? '0' : '') + m + 'm');
        parts.push((s < 10 ? '0' : '') + s + 's');
        return parts.join(' ');
    }

    function beep() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.4, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.8);
        } catch (e) { /* audio unavailable */ }
    }

    function ensurePanel() {
        if (timerPanel) return timerPanel;
        timerPanel = document.createElement('div');
        timerPanel.className = 'delice-timer-panel';
        timerPanel.setAttribute('role', 'status');
        timerPanel.setAttribute('aria-live', 'polite');
        document.body.appendChild(timerPanel);
        return timerPanel;
    }

    function removeTimer(timerObj) {
        clearInterval(timerObj.interval);
        if (timerObj.el && timerObj.el.parentNode) {
            timerObj.el.parentNode.removeChild(timerObj.el);
        }
        timers = timers.filter(function (t) { return t !== timerObj; });
        if (timers.length === 0 && timerPanel) {
            timerPanel.style.display = 'none';
        }
    }

    function startTimer(seconds, label) {
        ensurePanel();
        timerPanel.style.display = 'flex';

        var timerObj = { interval: null, el: null, remaining: seconds };

        var el = document.createElement('div');
        el.className = 'delice-timer-item';
        el.innerHTML =
            '<span class="delice-timer-label">' + escHtml(label) + '</span>' +
            '<span class="delice-timer-time">' + escHtml(formatTime(seconds)) + '</span>' +
            '<button class="delice-timer-cancel" aria-label="Cancel timer">&#x2715;</button>';

        el.querySelector('.delice-timer-cancel').addEventListener('click', function () {
            removeTimer(timerObj);
        });

        timerPanel.appendChild(el);
        timerObj.el = el;

        timerObj.interval = setInterval(function () {
            timerObj.remaining--;
            var timeEl = el.querySelector('.delice-timer-time');
            if (timerObj.remaining <= 0) {
                clearInterval(timerObj.interval);
                timeEl.textContent = getLang('timerDone', 'Timer done!');
                el.classList.add('delice-timer-done');
                beep();
                setTimeout(function () { removeTimer(timerObj); }, 4000);
            } else {
                timeEl.textContent = formatTime(timerObj.remaining);
                if (timerObj.remaining <= 10) el.classList.add('delice-timer-urgent');
            }
        }, 1000);

        timers.push(timerObj);
    }

    function injectTimerTriggers(container) {
        var stepTexts = container.querySelectorAll(
            '.delice-elegant-step-text, .delice-recipe-instruction-text, .delice-modern-step-text'
        );

        stepTexts.forEach(function (el, stepIdx) {
            var html = el.textContent || '';
            var match;
            var modified = false;
            TIME_PATTERN.lastIndex = 0;

            // Replace each time match with a clickable span
            var result = html.replace(TIME_PATTERN, function (full, amount, unit) {
                var secs = parseToSeconds(parseInt(amount, 10), unit);
                if (secs < 10) return full; // skip trivially small values
                var startLabel = getLang('startTimer', 'Start Timer');
                modified = true;
                return '<span class="delice-timer-trigger" data-seconds="' + secs + '" data-label="Step ' + (stepIdx + 1) + '" title="' + escHtml(startLabel) + '" role="button" tabindex="0">' +
                    escHtml(full) +
                    ' <span class="delice-timer-icon" aria-hidden="true">&#x23F1;</span></span>';
            });

            if (modified) {
                el.innerHTML = result;
                el.addEventListener('click', function (e) {
                    var trigger = e.target.closest('.delice-timer-trigger');
                    if (!trigger) return;
                    startTimer(parseInt(trigger.dataset.seconds, 10), trigger.dataset.label || '');
                });
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        var trigger = e.target.closest('.delice-timer-trigger');
                        if (!trigger) return;
                        e.preventDefault();
                        startTimer(parseInt(trigger.dataset.seconds, 10), trigger.dataset.label || '');
                    }
                });
            }
        });
    }

    function init() {
        document.querySelectorAll('.delice-recipe-container').forEach(injectTimerTriggers);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

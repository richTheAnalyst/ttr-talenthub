/* TalentHub: small progressive enhancements. No framework, no build step. */
(function () {
    'use strict';

    // ---- Service worker -------------------------------------------------------------------
    if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () { /* not fatal */ });
        });
    }

    // ---- Install prompt ---------------------------------------------------------------------
    var deferredPrompt = null;
    var banner = document.getElementById('install-banner');
    var dismissedKey = 'th-install-dismissed';

    function safeGet(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
    function safeSet(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* private mode */ } }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;
        if (banner && !safeGet(dismissedKey)) { banner.hidden = false; }
    });

    var installBtn = document.getElementById('install-accept');
    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) { return; }
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () { deferredPrompt = null; banner.hidden = true; });
        });
    }
    var installClose = document.getElementById('install-dismiss');
    if (installClose) {
        installClose.addEventListener('click', function () { banner.hidden = true; safeSet(dismissedKey, '1'); });
    }
    window.addEventListener('appinstalled', function () { if (banner) { banner.hidden = true; } });

    // ---- Offline banner -----------------------------------------------------------------------
    var offline = document.getElementById('offline-banner');
    function syncOnline() { if (offline) { offline.hidden = navigator.onLine; } }
    window.addEventListener('online', syncOnline);
    window.addEventListener('offline', syncOnline);
    syncOnline();

    // ---- Forms ------------------------------------------------------------------------------------
    // Stop double submits: disable the submit button once the form is sent.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-allow-resubmit')) { return; }
        var button = event.submitter || form.querySelector('[type="submit"]');
        if (button && !button.disabled) {
            window.setTimeout(function () {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            }, 0);
        }
    });

    // Confirm destructive actions: <form data-confirm="Delete this CV?">
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form instanceof HTMLElement ? form.getAttribute('data-confirm') : null;
        if (message && !window.confirm(message)) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);

    // Auto-submit selects that carry data-autosubmit (filters).
    document.addEventListener('change', function (event) {
        var el = event.target;
        if (el instanceof HTMLElement && el.hasAttribute('data-autosubmit') && el.form) { el.form.submit(); }
    });

    // ---- CV file picker: show the chosen file and catch oversize files early -------------------
    document.querySelectorAll('input[type="file"][data-max-kb]').forEach(function (input) {
        var out = document.getElementById(input.getAttribute('data-output') || '');
        var max = parseInt(input.getAttribute('data-max-kb'), 10) * 1024;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) { if (out) { out.textContent = ''; } return; }
            if (file.size > max) {
                input.value = '';
                if (out) { out.textContent = 'That file is too large. The limit is ' + Math.round(max / 1048576) + ' MB.'; out.className = 'error'; }
                return;
            }
            if (out) { out.textContent = file.name + ' (' + Math.max(1, Math.round(file.size / 1024)) + ' KB)'; out.className = 'hint'; }
        });
    });

    // ---- Toggle blocks: <button data-toggle="#id"> ---------------------------------------------
    document.addEventListener('click', function (event) {
        var trigger = event.target instanceof Element ? event.target.closest('[data-toggle]') : null;
        if (!trigger) { return; }
        var target = document.querySelector(trigger.getAttribute('data-toggle'));
        if (!target) { return; }
        target.hidden = !target.hidden;
        trigger.setAttribute('aria-expanded', String(!target.hidden));
    });

    // ---- Flash messages fade out ------------------------------------------------------------------
    document.querySelectorAll('[data-flash]').forEach(function (el) {
        window.setTimeout(function () { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; window.setTimeout(function () { el.remove(); }, 450); }, 6000);
    });
})();

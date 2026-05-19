(function () {
    'use strict';

    var STORAGE_KEY = 'mini_cc_consent';
    var VERSION     = (typeof miniCCData !== 'undefined') ? miniCCData.version : '1';
    var state       = null;

    // ── Persistence ───────────────────────────────────────────────────────────

    function defaultState() {
        return {
            v:           VERSION,
            ts:          Math.floor(Date.now() / 1000),
            necessary:   true,
            preferences: false,
            analytics:   false,
            marketing:   false
        };
    }

    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }

    function saveState(s) {
        s.ts = Math.floor(Date.now() / 1000);
        var json = JSON.stringify(s);
        try { localStorage.setItem(STORAGE_KEY, json); } catch (e) {}
        // Cookie for server-side script blocking (httponly=false, SameSite=Lax, 1 year)
        var expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = 'mini_cc_consent=' + encodeURIComponent(json) +
            '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    // ── Script activation ─────────────────────────────────────────────────────

    function activateScripts(category) {
        var scripts = document.querySelectorAll(
            'script[type="text/plain"][data-cc-category="' + category + '"]'
        );
        scripts.forEach(function (orig) {
            var clone = document.createElement('script');
            for (var i = 0; i < orig.attributes.length; i++) {
                var a = orig.attributes[i];
                if (a.name !== 'type') clone.setAttribute(a.name, a.value);
            }
            clone.type = 'text/javascript';
            if (!orig.src) clone.textContent = orig.textContent;
            orig.parentNode.replaceChild(clone, orig);
        });
    }

    function applyConsent(s) {
        ['preferences', 'analytics', 'marketing'].forEach(function (cat) {
            if (s[cat]) activateScripts(cat);
        });
    }

    // ── DOM helpers ───────────────────────────────────────────────────────────

    function el(id) { return document.getElementById(id); }

    function showBanner(full) {
        var banner = el('consent-banner');
        var layer  = el('black-layer');
        if (!banner) return;
        banner.hidden = false;
        if (full) {
            banner.classList.remove('mini');
            if (layer) layer.hidden = false;
            document.body.style.overflow = 'hidden';
        } else {
            banner.classList.add('mini');
            if (layer) layer.hidden = true;
            document.body.style.overflow = '';
        }
    }

    function hideBanner() { showBanner(false); }

    // ── Actions ───────────────────────────────────────────────────────────────

    function updateStatus() {
        var banner = el('consent-banner');
        if (!banner || !state) return;
        var statusEl = banner.querySelector('.consent-status');
        if (!statusEl) return;
        var cats = ['preferences', 'analytics', 'marketing'];
        var all  = cats.every(function (c) { return state[c]; });
        var none = cats.every(function (c) { return !state[c]; });
        statusEl.textContent = all ? 'Accettato' : (none ? 'Solo tecnici' : 'Parziale');
    }

    function acceptAll() {
        state = defaultState();
        state.preferences = state.analytics = state.marketing = true;
        saveState(state);
        applyConsent(state);
        hideBanner();
        closeModal();
        updateStatus();
    }

    function rejectAll() {
        state = defaultState();
        saveState(state);
        hideBanner();
        closeModal();
        updateStatus();
    }

    function openModal() {
        var modal = el('consent-modal');
        if (!modal) return;
        var s = state || defaultState();
        ['preferences', 'analytics', 'marketing'].forEach(function (cat) {
            var t = modal.querySelector('[data-cc-toggle="' + cat + '"]');
            if (t) t.checked = !!s[cat];
        });
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        var modal = el('consent-modal');
        if (modal) modal.hidden = true;
        // Only restore scroll if the banner is also not in full mode
        var banner = el('consent-banner');
        if (!banner || banner.classList.contains('mini')) {
            document.body.style.overflow = '';
        }
    }

    function savePreferences() {
        var modal = el('consent-modal');
        state = state ? JSON.parse(JSON.stringify(state)) : defaultState();
        state.v = VERSION;
        ['preferences', 'analytics', 'marketing'].forEach(function (cat) {
            var t = modal ? modal.querySelector('[data-cc-toggle="' + cat + '"]') : null;
            state[cat] = t ? t.checked : false;
        });
        saveState(state);
        applyConsent(state);
        hideBanner();
        closeModal();
        updateStatus();
    }

    // ── Event binding ─────────────────────────────────────────────────────────

    function bindBanner() {
        var banner = el('consent-banner');
        if (!banner) return;

        // Clicking the mini tab re-opens the full banner
        banner.addEventListener('click', function (e) {
            if (banner.classList.contains('mini') && !e.target.closest('button, a')) {
                showBanner(true);
            }
        });

        var closeBtn = banner.querySelector('.close-cookie-banner');
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                // If no choice has been made yet, treat close as reject
                if (!state) rejectAll();
                else hideBanner();
            });
        }

        var acceptBtn = banner.querySelector('.cc-accept-all');
        if (acceptBtn) acceptBtn.addEventListener('click', function (e) { e.stopPropagation(); acceptAll(); });

        var rejectBtn = banner.querySelector('.cc-reject');
        if (rejectBtn) rejectBtn.addEventListener('click', function (e) { e.stopPropagation(); rejectAll(); });

        var manageBtn = banner.querySelector('.cc-manage');
        if (manageBtn) manageBtn.addEventListener('click', function (e) { e.stopPropagation(); openModal(); });
    }

    function bindModal() {
        var modal = el('consent-modal');
        if (!modal) return;

        var overlay = modal.querySelector('.consent-modal-overlay');
        if (overlay) overlay.addEventListener('click', closeModal);

        var saveBtn = modal.querySelector('.cc-save-prefs');
        if (saveBtn) saveBtn.addEventListener('click', savePreferences);

        var acceptBtn = modal.querySelector('.cc-accept-all');
        if (acceptBtn) acceptBtn.addEventListener('click', acceptAll);
    }

    // ── Boot ──────────────────────────────────────────────────────────────────

    function init() {
        var stored = loadState();
        if (stored && stored.v === VERSION) {
            state = stored;
            applyConsent(state);
            showBanner(false); // mini — always visible for re-opening
            updateStatus();
        } else {
            // First visit or policy version changed
            state = null;
            showBanner(true);
        }
        bindBanner();
        bindModal();
    }

    // Public API — usable from footer links: onclick="miniCC.openModal()"
    window.miniCC = {
        acceptAll:       acceptAll,
        rejectAll:       rejectAll,
        openModal:       openModal,
        savePreferences: savePreferences,
        toggleBanner:    function () {
            var banner = el('consent-banner');
            if (banner && banner.classList.contains('mini')) showBanner(true);
            else hideBanner();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());

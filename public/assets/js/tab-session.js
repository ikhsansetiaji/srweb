/**
 * tab-session.js
 * Inject X-Tab-Token ke semua fetch/XHR request secara otomatis.
 * Include di layout/app.php PALING ATAS sebelum script lain.
 */
(function () {
    'use strict';

    // ── 1. INIT TOKEN ──────────────────────────────────────────────────────
    function generateToken() {
        var arr = new Uint8Array(32);
        window.crypto.getRandomValues(arr);
        return Array.from(arr).map(function (b) {
            return b.toString(16).padStart(2, '0');
        }).join('');
    }

    if (!sessionStorage.getItem('tabToken')) {
        sessionStorage.setItem('tabToken', generateToken());
    }

    window.getTabToken = function () {
        return sessionStorage.getItem('tabToken');
    };

    // ── 2. PATCH fetch() ──────────────────────────────────────────────────
    var _nativeFetch = window.fetch;

    window.fetch = function (url, options) {
        options = options || {};

        var urlStr = (typeof url === 'string') ? url : (url && url.url) ? url.url : '';
        var isSameOrigin = urlStr.startsWith('/') ||
            urlStr.startsWith(window.location.origin);

        if (isSameOrigin) {
            options.headers = options.headers || {};

            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-Tab-Token')) {
                    options.headers.set('X-Tab-Token', window.getTabToken());
                }
            } else {
                if (!options.headers['X-Tab-Token']) {
                    options.headers['X-Tab-Token'] = window.getTabToken();
                }
            }
        }

        return _nativeFetch.call(window, url, options).then(function (response) {
            // Auto redirect ke login jika 401
            if (response.status === 401) {
                response.clone().json().then(function (data) {
                    if (data && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '/auth/login';
                    }
                }).catch(function () {
                    window.location.href = '/auth/login';
                });
            }
            return response;
        });
    };

    // ── 3. PATCH XMLHttpRequest ────────────────────────────────────────────
    var _xhrOpen = XMLHttpRequest.prototype.open;
    var _xhrSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url) {
        this._tabUrl = url;
        return _xhrOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function () {
        try {
            var url = this._tabUrl || '';
            var isSameOrigin = (typeof url === 'string') &&
                (url.startsWith('/') || url.startsWith(window.location.origin));
            if (isSameOrigin) {
                this.setRequestHeader('X-Tab-Token', window.getTabToken());
            }
        } catch (e) { /* header sudah di-set, abaikan */ }
        return _xhrSend.apply(this, arguments);
    };

    // ── 4. HANDLE LOGOUT LINK ─────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href*="/auth/logout"]');
        if (!link) return;

        e.preventDefault();
        var token = window.getTabToken();
        // Hapus token dari sessionStorage tab ini dulu
        sessionStorage.removeItem('tabToken');
        // Navigasi dengan token di query param (GET request, tidak bisa pakai header)
        window.location.href = '/auth/logout?_tab_token=' + token;
    });

    // ── 5. INJECT _tab_token KE SEMUA LINK NAVIGASI ──────────────────────
    // Regular browser navigations (clicking <a> tags) cannot send custom headers.
    // So we append _tab_token as a query parameter on all internal links.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;

        var href = link.getAttribute('href');
        // Skip logout (handled above), external links, anchors, javascript: etc.
        if (!href || href.startsWith('#') || href.startsWith('javascript:') ||
            href.indexOf('/auth/logout') !== -1) return;

        // Only handle same-origin links
        var isSameOrigin = href.startsWith('/') || href.startsWith(window.location.origin);
        if (!isSameOrigin) return;

        // Don't add if already present
        if (href.indexOf('_tab_token=') !== -1) return;

        var sep = href.indexOf('?') !== -1 ? '&' : '?';
        link.setAttribute('href', href + sep + '_tab_token=' + window.getTabToken());
    }, true); // use capture phase so it runs before navigation

})();
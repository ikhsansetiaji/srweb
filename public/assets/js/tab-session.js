/**
 * tab-session.js
 * (Deprecated & Neutralized)
 * Standard session cookie is now used for security and clean URLs.
 * This script only cleans up legacy sessionStorage and residual URL parameters if present.
 */
(function () {
    'use strict';

    try {
        if (sessionStorage.getItem('tabToken')) {
            sessionStorage.removeItem('tabToken');
        }

        if (window.location.search && window.location.search.indexOf('_tab_token=') !== -1) {
            var url = new URL(window.location.href);
            url.searchParams.delete('_tab_token');
            window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
        }
    } catch (e) {
        // Ignore in unsupported environments
    }
})();
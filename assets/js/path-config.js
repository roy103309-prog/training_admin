/**
 * Centralized path configuration.
 * Update ROOT_PATH once when deploying to a different base path.
 * Examples:
 *  - 'auto' (recommended: detect from current URL)
 *  - '/TrainingAdmin' (manual override in subfolder)
 *  - '' (manual override for domain root)
 */
(function(window) {
    'use strict';

    window.__APP_CONFIG__ = window.__APP_CONFIG__ || {
        ROOT_PATH: 'auto',
        LOGIN_SEGMENT: '/login',
        ADMIN_SEGMENT: '/admin'
    };

    function trimSlashes(value) {
        return (value || '').replace(/^\/+|\/+$/g, '');
    }

    function normalizeRoot(value) {
        var cleaned = trimSlashes(value);
        return cleaned ? '/' + cleaned : '';
    }

    function detectRootFromPathname(pathname) {
        var path = pathname || '/';
        var lower = path.toLowerCase();
        var cutIndex = -1;
        var markers = [
            '/admin/',
            '/admin',
            '/login/',
            '/login',
            '/app/',
            '/app',
            '/index.html',
            '/app.html'
        ];

        for (var i = 0; i < markers.length; i++) {
            var idx = lower.indexOf(markers[i]);
            if (idx !== -1 && (cutIndex === -1 || idx < cutIndex)) {
                cutIndex = idx;
            }
        }

        if (cutIndex >= 0) {
            return normalizeRoot(path.slice(0, cutIndex));
        }

        return normalizeRoot(path.replace(/\/[^\/]*$/, ''));
    }

    function detectRootFromBaseTag() {
        var base = document.querySelector('base[href]');
        if (!base) return null;
        try {
            var url = new URL(base.getAttribute('href'), window.location.origin);
            return normalizeRoot(url.pathname);
        } catch (e) {
            return null;
        }
    }

    function resolveRoot(configRoot) {
        if (typeof configRoot === 'string' && configRoot.toLowerCase() !== 'auto') {
            return normalizeRoot(configRoot);
        }
        var fromBase = detectRootFromBaseTag();
        if (fromBase !== null) return fromBase;
        return detectRootFromPathname(window.location.pathname);
    }

    function normalizeSegment(value, fallback) {
        var cleaned = trimSlashes(value || fallback);
        return '/' + cleaned;
    }

    function join(root, path) {
        var p = path || '';
        if (p.charAt(0) !== '/') p = '/' + p;
        return (root + p).replace(/\/{2,}/g, '/');
    }

    var ROOT = resolveRoot(window.__APP_CONFIG__.ROOT_PATH);
    var LOGIN_SEGMENT = normalizeSegment(window.__APP_CONFIG__.LOGIN_SEGMENT, '/login');
    var ADMIN_SEGMENT = normalizeSegment(window.__APP_CONFIG__.ADMIN_SEGMENT, '/admin');

    window.APP_PATHS = {
        ROOT: ROOT,
        LOGIN_SEGMENT: LOGIN_SEGMENT,
        ADMIN_SEGMENT: ADMIN_SEGMENT,
        LOGIN_URL: join(ROOT, LOGIN_SEGMENT),
        ADMIN_URL: join(ROOT, ADMIN_SEGMENT),
        ADMIN_BASE: join(ROOT, ADMIN_SEGMENT),
        API_BASE: join(ROOT, '/api'),
        TEMPLATES_BASE: join(ROOT, '/templates'),
        INCLUDES_BASE: join(ROOT, '/includes'),
        url: function(path) {
            return join(ROOT, path || '/');
        }
    };
})(window);

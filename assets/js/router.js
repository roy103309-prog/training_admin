/**
 * Router - History API based SPA router
 */
var Router = {
    _initialized: false,
    _currentPath: null,

    _normalizePath: function(path) {
        if (!path) return path;
        var resolved = path;

        try {
            if (/^https?:\/\//i.test(path)) {
                resolved = new URL(path).pathname;
            } else if (path.charAt(0) !== '/') {
                resolved = new URL(path, document.baseURI || window.location.href).pathname;
            }
        } catch (e) {
            resolved = path;
        }

        resolved = resolved.replace(/\/+$/, '');
        return resolved || '/';
    },

    init: function() {
        if (this._initialized) return;
        this._initialized = true;
        var self = this;
        console.log('[Router] Initializing...');

        // Browser back / forward
        window.addEventListener('popstate', function() {
            self.load(location.pathname, false);
        });

        // Intercept clicks on [data-route] links
        $(document).on('click', '[data-route]', function(e) {
            var href = $(this).attr('href');
            var id = $(this).attr('id');

            // External link (website button)
            if (id === 'websiteLink') return;

            // Submenu toggle
            if ($(this).hasClass('has-arrow')) return;

            // Placeholder / under-development links
            if (!href || href === '#' || href === 'javascript:;') {
                e.preventDefault();
                if (window.HotelApp && window.HotelApp.showNotification) {
                    window.HotelApp.showNotification('This page is under development and will be available soon.');
                }
                return;
            }

            e.preventDefault();
            self.navigate(self._normalizePath(href));
        });

        // Determine initial route from current URL
        var path = location.pathname;
        var paths = window.APP_PATHS || {};
        var root = paths.ROOT || '';
        var adminBase = paths.ADMIN_BASE || (root + '/admin');
        var legacyAppBase = root + '/app';

        // Legacy alias support: /app/* -> /admin/*
        if (path.indexOf(legacyAppBase) === 0) {
            path = adminBase + path.substring(legacyAppBase.length);
        }

        // Normalize: if at shell root, go to default
        if (path === (root + '/app.html') || path === (root + '/admin.html') ||
            path === legacyAppBase || path === (legacyAppBase + '/') ||
            path === adminBase || path === (adminBase + '/') ||
            path === root || path === (root + '/')) {
            path = DEFAULT_ROUTE;
        }

        this.load(this._normalizePath(path), true);
    },

    /**
     * Navigate to a path (pushes state)
     */
    navigate: function(path) {
        if (path === this._currentPath) return;
        this.load(path, true);
    },

    /**
     * Load a route by pathname
     */
    load: function(path, pushState) {
        path = this._normalizePath(path);
        var route = ROUTES[path];

        if (!route) {
            console.warn('[Router] No route for:', path);
            PageLoader.showError('Page not found: <strong>' + path + '</strong>');
            return;
        }

        console.log('[Router] Loading:', path, '→', route.template);
        this._currentPath = path;

        // Update browser URL
        if (pushState && location.pathname !== path) {
            history.pushState({ path: path }, route.title, path);
        }

        // Update document title
        document.title = route.title + ' - Hotel 365';

        // Update page name for permission system
        if (window.HotelApp && window.HotelApp._setCurrentPage) {
            window.HotelApp._setCurrentPage(route.template);
        }

        // Load page content, then highlight menu
        PageLoader.load(route).then(function() {
            Menu.setActive(route.menu, route.title);
        }).catch(function(err) {
            console.error('[Router] Load error:', err);
        });
    },

    getCurrentPath: function() { return this._currentPath; },
    getCurrentRoute: function() { return ROUTES[this._currentPath] || null; }
};

/**
 * Page Loader - Handles template loading and injection for SPA
 * Supports both clean templates and legacy full-HTML pages
 */
var PageLoader = {
    _loadedScripts: {},
    _pageScriptEls: [],
    _pageStyleEls: [],
    _currentCleanup: null,

    // Core scripts loaded in shell — never reload from pages
    _coreScripts: [
        'bootstrap.bundle', 'jquery.min', 'simplebar', 'metisMenu',
        'toast.js', 'new-app.js', 'pace.min', 'perfect-scrollbar',
        'route-map.js', 'router.js', 'page-loader.js', 'menu.js'
    ],

    showLoading: function() {
        document.getElementById('spa-content').innerHTML =
            '<div class="d-flex justify-content-center align-items-center" style="min-height:60vh">' +
            '<div class="spinner-border text-primary" role="status"></div></div>';
    },

    showError: function(msg) {
        document.getElementById('spa-content').innerHTML =
            '<div class="text-center py-5">' +
            '<i class="bx bx-error-circle" style="font-size:64px;color:#dc3545"></i>' +
            '<h4 class="mt-3">Page Not Found</h4>' +
            '<p class="text-muted">' + (msg || 'The requested page could not be loaded.') + '</p></div>';
    },

    cleanup: function() {
        // Run previous page destroy
        if (this._currentCleanup) {
            try { this._currentCleanup(); } catch (e) { console.warn('[PageLoader] cleanup error:', e); }
            this._currentCleanup = null;
        }
        // Remove injected scripts
        this._pageScriptEls.forEach(function(el) { if (el.parentNode) el.parentNode.removeChild(el); });
        this._pageScriptEls = [];
        // Remove injected styles
        this._pageStyleEls.forEach(function(el) { if (el.parentNode) el.parentNode.removeChild(el); });
        this._pageStyleEls = [];
        // Clear Page global
        window.Page = null;
        // Clear modal / style containers
        var modals = document.getElementById('spa-modals');
        if (modals) modals.innerHTML = '';
        var styles = document.getElementById('spa-styles');
        if (styles) styles.innerHTML = '';
    },

    /**
     * Main entry: load a route's template
     * @param {object} route - from ROUTES
     * @returns {Promise}
     */
    load: function(route) {
        var self = this;
        this.showLoading();
        this.cleanup();

        return new Promise(function(resolve, reject) {
            $.ajax({
                url: route.template,
                method: 'GET',
                dataType: 'text',
                cache: false,
                success: function(html) {
                    try {
                        if (route.legacy) {
                            self._injectLegacy(html, route, resolve);
                        } else {
                            self._injectTemplate(html, route, resolve);
                        }
                    } catch (e) {
                        console.error('[PageLoader] Inject error:', e);
                        self.showError('Error rendering page');
                        reject(e);
                    }
                },
                error: function(xhr) {
                    console.error('[PageLoader] Fetch failed:', route.template, xhr.status);
                    self.showError('Could not load <strong>' + route.title + '</strong>');
                    reject(new Error('Fetch failed: ' + xhr.status));
                }
            });
        });
    },

    /* -------------------------------------------------------
     *  Clean template injection (content-only HTML)
     * ------------------------------------------------------- */
    _injectTemplate: function(html, route, done) {
        var self = this;
        var container = document.createElement('div');
        container.innerHTML = html;

        // Separate scripts
        var scriptData = [];
        var scripts = container.querySelectorAll('script');
        for (var i = 0; i < scripts.length; i++) {
            var s = scripts[i];
            if (s.src) {
                scriptData.push({ type: 'external', src: s.src });
            } else {
                scriptData.push({ type: 'inline', code: s.textContent });
            }
            s.parentNode.removeChild(s);
        }

        // Separate styles
        var styleEls = container.querySelectorAll('style');
        for (var j = 0; j < styleEls.length; j++) {
            var st = document.createElement('style');
            st.textContent = styleEls[j].textContent;
            document.getElementById('spa-styles').appendChild(st);
            self._pageStyleEls.push(st);
            styleEls[j].parentNode.removeChild(styleEls[j]);
        }

        // Inject content
        document.getElementById('spa-content').innerHTML = container.innerHTML;

        // Build external script list: route-level + template-level
        var extScripts = (route.scripts || []).slice();
        scriptData.forEach(function(sd) {
            if (sd.type === 'external') extScripts.push(sd.src);
        });
        var inlineScripts = scriptData.filter(function(sd) { return sd.type === 'inline'; });

        // Load external scripts, then run inline scripts
        self._loadScriptsSequential(extScripts, 0, function() {
            inlineScripts.forEach(function(sd) { self._executeScript(sd.code); });
            // Page.init lifecycle
            if (window.Page && typeof window.Page.init === 'function') {
                window.Page.init();
            }
            if (typeof window.applyUISettings === 'function') {
                window.applyUISettings();
            }
            // Auto-enhance native selects with Select2 (themed via ui-theme.css)
            if (typeof window.enhanceSelect2 === 'function') {
                window.enhanceSelect2(document);
                // Re-run after AJAX-populated <option>s settle
                setTimeout(function () { window.enhanceSelect2(document); }, 400);
                setTimeout(function () { window.enhanceSelect2(document); }, 1200);
            }
            if (window.Page && typeof window.Page.destroy === 'function') {
                self._currentCleanup = window.Page.destroy.bind(window.Page);
            }
            if (done) done();
        });
    },

    /* -------------------------------------------------------
     *  Legacy full-HTML injection (extracts .page-content)
     * ------------------------------------------------------- */
    _injectLegacy: function(html, route, done) {
        var self = this;
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        // Extract content
        var contentEl = doc.querySelector('.page-content')
                      || doc.querySelector('.page-wrapper')
                      || doc.querySelector('body');
        var pageHtml = contentEl ? contentEl.innerHTML : html;

        // Extract modals
        var modalsHtml = '';
        var modals = doc.querySelectorAll('.modal');
        for (var m = 0; m < modals.length; m++) modalsHtml += modals[m].outerHTML;

        // Extract styles
        var styleEls = doc.querySelectorAll('style');
        for (var j = 0; j < styleEls.length; j++) {
            var st = document.createElement('style');
            st.textContent = styleEls[j].textContent;
            document.getElementById('spa-styles').appendChild(st);
            self._pageStyleEls.push(st);
        }

        // External scripts (non-core)
        var extScripts = (route.scripts || []).slice();
        var allScripts = doc.querySelectorAll('script[src]');
        for (var i = 0; i < allScripts.length; i++) {
            var src = allScripts[i].getAttribute('src');
            var isCore = self._coreScripts.some(function(c) { return src.indexOf(c) !== -1; });
            if (!isCore) extScripts.push(src);
        }

        // Inline scripts
        var inlineScripts = [];
        var inlineEls = doc.querySelectorAll('script:not([src])');
        for (var k = 0; k < inlineEls.length; k++) {
            var code = inlineEls[k].textContent.trim();
            if (code) inlineScripts.push(code);
        }

        // Inject
        document.getElementById('spa-content').innerHTML = pageHtml;
        if (modalsHtml) document.getElementById('spa-modals').innerHTML = modalsHtml;

        // Load scripts
        self._loadScriptsSequential(extScripts, 0, function() {
            inlineScripts.forEach(function(code) { self._executeScript(code); });
            if (done) done();
        });
    },

    /* -------------------------------------------------------
     *  Script helpers
     * ------------------------------------------------------- */
    _loadScriptsSequential: function(scripts, index, callback) {
        var self = this;
        if (index >= scripts.length) { if (callback) callback(); return; }
        var src = scripts[index];

        if (self._loadedScripts[src]) {
            self._loadScriptsSequential(scripts, index + 1, callback);
            return;
        }

        var el = document.createElement('script');
        el.src = src;
        el.onload = function() {
            self._loadedScripts[src] = true;
            self._loadScriptsSequential(scripts, index + 1, callback);
        };
        el.onerror = function() {
            console.warn('[PageLoader] Script failed:', src);
            self._loadScriptsSequential(scripts, index + 1, callback);
        };
        document.body.appendChild(el);
        self._pageScriptEls.push(el);
    },

    _executeScript: function(code) {
        var el = document.createElement('script');
        el.textContent = code;
        document.body.appendChild(el);
        this._pageScriptEls.push(el);
    }
};

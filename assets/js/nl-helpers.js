/**
 * Helpers for the New Life module pages (students, certificates, practical, website pages in templates/).
 * Loaded per route from route-map.js; the TrainingAdmin core scripts stay unchanged.
 *
 *   NL.esc(value)          HTML-escape
 *   NL.media(path)         URL of an uploaded file (uploads/training_XXXXXX/...)
 *   NL.navigate(path)      open an admin page, query string kept: NL.navigate('students/view?id=5')
 *   NL.query(name)         value of a query string parameter of the current page
 *   NL.instituteName()     institute name shown in the header
 *   NL.can() / NL.hasPermission()  TrainingAdmin pages have no per-action rules, so always true
 *
 * Links with a query string use class "nl-link" (TrainingAdmin's router keeps only the path):
 *   <a href="admin/students/view?id=5" class="nl-link">...</a>
 */
(function (window, $) {
    'use strict';

    if (window.NL) return;

    var paths = window.APP_PATHS || {};
    var ROOT = paths.ROOT || '';
    var ADMIN = paths.ADMIN_BASE || (ROOT + '/admin');

    var NL = {
        esc: function (value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        },

        media: function (path) {
            if (!path) return '';
            path = String(path);
            if (/^(?:https?:)?\/\//i.test(path) || path.indexOf('data:') === 0) return path;
            return ROOT + '/' + path.replace(/^\/+/, '');
        },

        navigate: function (path) {
            var target = String(path || '').replace(/^\/+/, '');
            if (target.indexOf(ADMIN.replace(/^\/+/, '') + '/') === 0) target = target.substring(ADMIN.replace(/^\/+/, '').length + 1);
            target = target.replace(/^admin\//, '');
            var url = ADMIN + '/' + target;
            var pathOnly = url.split('?')[0].replace(/\/+$/, '');
            if ((location.pathname + location.search) !== url) {
                history.pushState({ path: url }, '', url);
            }
            if (window.Router && typeof Router.load === 'function') Router.load(pathOnly, false);
            else location.href = url;
        },

        query: function (name) {
            return new URLSearchParams(location.search).get(name);
        },

        instituteName: function () {
            var name = $.trim($('#hotelNameDisplay').text());
            return name === 'Hotel Management' ? '' : name;
        },

        can: function () { return true; },
        hasPermission: function () { return true; },

        money: function (value) {
            return Number(value || 0).toLocaleString();
        },

        today: function () {
            var d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        },

        // Active payment methods of the branch into a <select>
        loadPaymentMethods: function ($select) {
            $.get('api/payment/methods-list.php', { limit: 100 }, function (r) {
                if (!r.success) return;
                $select.html(r.data.items.filter(function (m) { return m.isactive == 1; }).reverse().map(function (m) {
                    return '<option value="' + m.id + '">' + NL.esc(m.name) + '</option>';
                }).join(''));
            });
        }
    };

    // Links that carry a query string (ctrl / cmd click still opens a new tab)
    $(document).on('click.nl', 'a.nl-link', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
        e.preventDefault();
        NL.navigate($(this).attr('href'));
    });

    window.NL = NL;
})(window, jQuery);

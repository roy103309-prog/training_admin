/* ============================================================
   UI Theme runtime
   - applyUISettings()       → reads localStorage and sets CSS vars
   - fetchAndApplyUISettings() → loads from server, caches in LS, applies
   Empty / unset values keep the CSS defaults (defined in ui-theme.css).
   ============================================================ */
(function (window) {
    'use strict';

    var KEYS = [
        'ui_root_primary',
        'ui_sidebar_bg', 'ui_sidebar_bg_2', 'ui_sidebar_gradient',
        'ui_sidebar_text', 'ui_sidebar_active', 'ui_sidebar_active_text',
        'ui_sidebar_submenu_bg',
        'ui_header_bg', 'ui_header_bg_2', 'ui_header_gradient',
        'ui_header_text', 'ui_header_border',
        'ui_btn_header_bg', 'ui_btn_header_hover_bg',
        'ui_btn_header_text', 'ui_btn_header_border',
        'ui_footer_bg', 'ui_footer_bg_2', 'ui_footer_gradient',
        'ui_footer_text', 'ui_footer_border', 'ui_footer_accent',
        'ui_report_header', 'ui_report_header_text', 'ui_report_title',
        'ui_report_summary_box', 'ui_report_summary_text',
        'ui_report_table_head', 'ui_report_table_head_text', 'ui_report_row_alt',
        'ui_form_border', 'ui_form_focus_border',
        'ui_table_header_bg', 'ui_table_header_text',
        'ui_table_border', 'ui_table_row_hover', 'ui_table_text'
    ];

    /* ------------------ helpers ------------------ */
    function ls(k)  { try { return localStorage.getItem(k) || ''; } catch (e) { return ''; } }
    function sls(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    function normalizeHex(hex) {
        var raw = (hex || '').trim();
        if (!raw) return '';
        var h = raw.charAt(0) === '#' ? raw : '#' + raw;
        if (/^#[0-9a-fA-F]{3}$/.test(h)) {
            h = '#' + h.slice(1).split('').map(function (c) { return c + c; }).join('');
        }
        return /^#[0-9a-fA-F]{6}$/.test(h) ? h.toUpperCase() : '';
    }

    function shadeHex(hex, percent) {
        var n = normalizeHex(hex);
        if (!n) return '';
        var h = n.replace('#', '');
        var p = Math.max(-100, Math.min(100, Number(percent) || 0)) / 100;
        var t = p < 0 ? 0 : 255;
        var k = Math.abs(p);
        var r = parseInt(h.substring(0, 2), 16);
        var g = parseInt(h.substring(2, 4), 16);
        var b = parseInt(h.substring(4, 6), 16);
        function hx(v) { return Math.round((t - v) * k + v).toString(16).padStart(2, '0'); }
        return ('#' + hx(r) + hx(g) + hx(b)).toUpperCase();
    }

    function hexToRgba(hex, alpha) {
        var n = normalizeHex(hex);
        if (!n) return '';
        var h = n.replace('#', '');
        var r = parseInt(h.substring(0, 2), 16);
        var g = parseInt(h.substring(2, 4), 16);
        var b = parseInt(h.substring(4, 6), 16);
        var a = Math.max(0, Math.min(1, Number(alpha) || 0));
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + a + ')';
    }

    function setVar(name, value) {
        if (value === undefined || value === null || value === '') return;
        document.documentElement.style.setProperty(name, value);
    }

    /* ------------------ applier ------------------ */
    window.applyUISettings = function () {
        var root = document.documentElement;

        /* Common primary */
        var primary = normalizeHex(ls('ui_root_primary'));
        if (primary) {
            var hover = shadeHex(primary, -14);
            setVar('--ui-root-primary',       primary);
            setVar('--ui-root-primary-hover', hover);
            setVar('--ui-root-primary-soft',  hexToRgba(primary, 0.10));
        }

        /* Sidebar */
        var sbBg  = ls('ui_sidebar_bg');
        var sbBg2 = ls('ui_sidebar_bg_2');
        var sbGrad = ls('ui_sidebar_gradient') === '1';
        if (sbBg) {
            var bg2 = sbBg2 || sbBg;
            setVar('--ui-sidebar-bg', sbGrad ? 'linear-gradient(180deg, ' + sbBg + ' 0%, ' + bg2 + ' 100%)' : sbBg);
        }
        setVar('--ui-sidebar-text',         normalizeHex(ls('ui_sidebar_text')));
        setVar('--ui-sidebar-active-bg',    normalizeHex(ls('ui_sidebar_active')));
        setVar('--ui-sidebar-active-text',  normalizeHex(ls('ui_sidebar_active_text')));
        setVar('--ui-sidebar-submenu-bg',   normalizeHex(ls('ui_sidebar_submenu_bg')));

        /* Header */
        var hBg  = ls('ui_header_bg');
        var hBg2 = ls('ui_header_bg_2');
        var hGrad = ls('ui_header_gradient') === '1';
        if (hBg) {
            var hb2 = hBg2 || hBg;
            setVar('--ui-header-bg', hGrad ? 'linear-gradient(90deg, ' + hBg + ' 0%, ' + hb2 + ' 100%)' : hBg);
        }
        setVar('--ui-header-text',   normalizeHex(ls('ui_header_text')));
        setVar('--ui-header-border', normalizeHex(ls('ui_header_border')));

        /* Header buttons */
        setVar('--ui-header-button-bg',       normalizeHex(ls('ui_btn_header_bg')));
        setVar('--ui-header-button-hover-bg', normalizeHex(ls('ui_btn_header_hover_bg')));
        setVar('--ui-header-button-text',     normalizeHex(ls('ui_btn_header_text')));
        setVar('--ui-header-button-border',   normalizeHex(ls('ui_btn_header_border')));

        /* Footer */
        var fBg  = ls('ui_footer_bg');
        var fBg2 = ls('ui_footer_bg_2');
        var fGrad = ls('ui_footer_gradient') === '1';
        if (fBg) {
            var fb2 = fBg2 || fBg;
            setVar('--ui-footer-bg', fGrad ? 'linear-gradient(90deg, ' + fBg + ' 0%, ' + fb2 + ' 100%)' : fBg);
        }
        setVar('--ui-footer-text',   normalizeHex(ls('ui_footer_text')));
        setVar('--ui-footer-border', normalizeHex(ls('ui_footer_border')));
        setVar('--ui-footer-accent', normalizeHex(ls('ui_footer_accent')));

        /* Forms — always follow primary; ignore stale color settings */
        setVar('--ui-form-border',       primary || '#8B0000');
        setVar('--ui-form-focus-border', primary || '#8B0000');
        setVar('--ui-form-focus-ring',   hexToRgba(primary || '#8B0000', 0.25));

        /* Tables — header/border follow primary; allow row-hover/text overrides */
        setVar('--ui-table-header-bg',   primary || '#8B0000');
        setVar('--ui-table-header-text', '#FFFFFF');
        setVar('--ui-table-border',      primary || '#8B0000');
        setVar('--ui-table-row-hover',   normalizeHex(ls('ui_table_row_hover')));
        setVar('--ui-table-text',        normalizeHex(ls('ui_table_text')));

        /* Reports */
        setVar('--ui-report-header-bg',       normalizeHex(ls('ui_report_header')));
        setVar('--ui-report-header-text',     normalizeHex(ls('ui_report_header_text')));
        setVar('--ui-report-title',           normalizeHex(ls('ui_report_title')));
        setVar('--ui-report-summary-box-bg',  normalizeHex(ls('ui_report_summary_box')));
        setVar('--ui-report-summary-text',    normalizeHex(ls('ui_report_summary_text')));
        setVar('--ui-report-table-head-bg',   normalizeHex(ls('ui_report_table_head')));
        setVar('--ui-report-table-head-text', normalizeHex(ls('ui_report_table_head_text')));
        setVar('--ui-report-row-alt',         normalizeHex(ls('ui_report_row_alt')));
    };

    /* ------------------ server fetch ------------------ */
    var _fetched = false;
    window.fetchAndApplyUISettings = function (force) {
        if (_fetched && !force) { window.applyUISettings(); return; }
        _fetched = true;

        if (typeof $ === 'undefined') { window.applyUISettings(); return; }

        var paths = window.APP_PATHS || {};
        var url   = (paths.API_BASE || '/api') + '/ui-settings/read.php';

        $.get(url)
            .done(function (r) {
                if (r && r.success && r.data) {
                    KEYS.forEach(function (k) {
                        if (Object.prototype.hasOwnProperty.call(r.data, k)) {
                            sls(k, r.data[k] || '');
                        }
                    });
                }
                window.applyUISettings();
            })
            .fail(function () { window.applyUISettings(); });
    };

    /* Apply cached values immediately on script load to minimize flash */
    if (document.readyState !== 'loading') {
        window.applyUISettings();
    } else {
        document.addEventListener('DOMContentLoaded', window.applyUISettings);
    }

})(window);

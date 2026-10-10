/* ============================================================
   Reports Module — shared engine
   - ReportEngine  : column selector + table renderer + formatters
   - ReportPage    : per-page bootstrapper used by every report-*.html
   - applyDatePreset / loadReportHeader / printReport / exportCSV
   ============================================================ */
(function (window) {
    'use strict';

    /* ──────────────────────────────────────────────────────────
       Internal state for the currently-active report page
    ────────────────────────────────────────────────────────── */
    var current = {
        config: null,        // page config provided to ReportPage.init
        columns: [],         // resolved column list
        rows: [],            // last-fetched data rows
        summary: {},         // last-fetched summary
        reportName: '',
        fileName: '',
        dateFrom: '',
        dateTo: ''
    };















    /* ──────────────────────────────────────────────────────────
       ReportEngine: column selector + table builder
    ────────────────────────────────────────────────────────── */
    var ReportEngine = {

        activeColumns: [],

        renderColumnSelector: function (columns) {
            var $box = $('#columnCheckboxes');
            $box.empty();
            columns.forEach(function (col) {
                var checked = col.default ? 'checked' : '';
                $box.append(
                    '<label class="form-check form-check-inline mb-0 me-3">' +
                        '<input class="form-check-input rep-col" type="checkbox" ' +
                               'id="col_' + col.key + '" value="' + col.key + '" ' + checked + '>' +
                        '<span class="form-check-label small">' + col.label + '</span>' +
                    '</label>'
                );
            });
            this.updateActiveColumns();
        },

        updateActiveColumns: function () {
            this.activeColumns = current.columns.filter(function (col) {
                var cb = document.getElementById('col_' + col.key);
                return cb && cb.checked;
            });
        },

        buildHeader: function () {
            return '<thead><tr>' + this.activeColumns.map(function (col) {
                return '<th class="text-' + (col.align || 'left') + '">' + col.label + '</th>';
            }).join('') + '</tr></thead>';
        },

        buildBody: function (rows) {
            var self = this;
            if (!rows || !rows.length) {
                return '<tbody><tr><td colspan="' + this.activeColumns.length +
                       '" class="text-center text-muted py-4">' +
                       'No data found for selected filters.</td></tr></tbody>';
            }
            return '<tbody>' + rows.map(function (row) {
                return '<tr>' + self.activeColumns.map(function (col) {
                    var raw = row[col.key];
                    var v = (raw === null || raw === undefined || raw === '') ? '—' : raw;
                    return '<td class="text-' + (col.align || 'left') + '">' +
                            self.formatValue(v, col.format) + '</td>';
                }).join('') + '</tr>';
            }).join('') + '</tbody>';
        },

        buildFooter: function (rows) {
            if (!rows || !rows.length) return '';
            var hasCurrency = this.activeColumns.some(function (c) { return c.format === 'currency'; });
            var hasNumber   = this.activeColumns.some(function (c) { return c.format === 'number'; });
            if (!hasCurrency && !hasNumber) return '';
            var self = this;
            return '<tfoot><tr>' + this.activeColumns.map(function (col, i) {
                if (i === 0) return '<td><strong>TOTAL</strong></td>';
                if (col.format === 'currency') {
                    var sumC = rows.reduce(function (s, r) { return s + (parseFloat(r[col.key]) || 0); }, 0);
                    return '<td class="text-' + (col.align || 'right') + '"><strong>' + self.currency(sumC) + '</strong></td>';
                }
                if (col.format === 'number' && col.sumFooter !== false) {
                    var sumN = rows.reduce(function (s, r) { return s + (parseFloat(r[col.key]) || 0); }, 0);
                    return '<td class="text-' + (col.align || 'right') + '"><strong>' + self.number(sumN) + '</strong></td>';
                }
                return '<td></td>';
            }).join('') + '</tr></tfoot>';
        },

        formatValue: function (val, format) {
            if (val === '—') return val;
            switch (format) {
                case 'currency': return this.currency(val);
                case 'date':     return this.formatDate(val);
                case 'datetime': return this.formatDateTime(val);
                case 'number':   return this.number(val);
                case 'percent':  return (parseFloat(val) || 0).toFixed(2) + '%';
                default:         return String(val);
            }
        },

        currency: function (val) {
            var n = parseFloat(val) || 0;
            return '৳' + n.toLocaleString('en', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        },

        number: function (val) {
            var n = parseFloat(val);
            if (isNaN(n)) return '—';
            // keep up to 3 decimals if non-integer
            if (n === Math.floor(n)) return n.toLocaleString();
            return n.toLocaleString('en', { maximumFractionDigits: 3 });
        },

        formatDate: function (val) {
            if (!val) return '—';
            var d = new Date(val);
            if (isNaN(d.getTime())) return val;
            return d.toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
            });
        },

        formatDateTime: function (val) {
            if (!val) return '—';
            var d = new Date(val.replace ? val.replace(' ', 'T') : val);
            if (isNaN(d.getTime())) return val;
            return d.toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric'
            }) + ' ' + d.toLocaleTimeString('en-GB', {
                hour: '2-digit', minute: '2-digit'
            });
        },

        buildSummaryCardsHTML: function (summaryCards, variant) {
            if (!summaryCards || !summaryCards.length) return '';
            var accents = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];
            var wrapCls = 'summary-cards-row ' + (variant || 'screen') + '-summary-cards';
            return '<div class="' + wrapCls + '">' + summaryCards.map(function (c, i) {
                var accent = accents[i % accents.length];
                return '<div class="summary-stat-card accent-' + accent + '">' +
                            '<div class="stat-label">' + c.label + '</div>' +
                            '<div class="stat-value">' + c.value + '</div>' +
                       '</div>';
            }).join('') + '</div>';
        },

        render: function (rows, summaryCards) {
            this.updateActiveColumns();
            current.rows = rows || [];

            // Summary cards — rendered once, below column selector
            var screenCardsHTML = this.buildSummaryCardsHTML(summaryCards, 'screen');
            $('#reportSummaryArea').html(screenCardsHTML);

            var tableHTML =
                '<div class="table-responsive">' +
                    '<table class="report-table">' +
                        this.buildHeader() +
                        this.buildBody(rows) +
                        this.buildFooter(rows) +
                    '</table>' +
                '</div>';

            $('#reportTableArea').html(tableHTML);
            $('#reportPreview').show();
        },

        reset: function () {
            current.columns.forEach(function (col) {
                var cb = document.getElementById('col_' + col.key);
                if (cb) cb.checked = !!col.default;
            });
            this.updateActiveColumns();
            // Re-render with current data
            if (current.rows.length) this.render(current.rows, current._lastCards || []);
        }
    };

    /* ──────────────────────────────────────────────────────────
       Date utilities (presets)
    ────────────────────────────────────────────────────────── */
    function fmt(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dd;
    }

    function applyDatePreset(preset) {
        var today = new Date();
        var from, to;
        var startOf = function (d) { d.setHours(0,0,0,0); return d; };
        switch (preset) {
            case 'today':
                from = to = new Date(today); break;
            case 'yesterday':
                from = new Date(today); from.setDate(from.getDate() - 1);
                to   = new Date(from); break;
            case 'this-week':
                from = new Date(today); from.setDate(from.getDate() - from.getDay());
                to   = new Date(today); break;
            case 'last-week':
                to   = new Date(today); to.setDate(to.getDate() - to.getDay() - 1);
                from = new Date(to);    from.setDate(from.getDate() - 6); break;
            case 'this-month':
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to   = new Date(today); break;
            case 'last-month':
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to   = new Date(today.getFullYear(), today.getMonth(),     0); break;
            case 'this-year':
                from = new Date(today.getFullYear(), 0, 1);
                to   = new Date(today); break;
            default:
                return;
        }
        $('#filterDateFrom').val(fmt(startOf(from)));
        $('#filterDateTo').val(fmt(startOf(to)));
        $('.date-preset-chip').removeClass('active');
        $('.date-preset-chip[data-preset="' + preset + '"]').addClass('active');
    }

    /* ──────────────────────────────────────────────────────────
       Report header (fetches settings_basic on demand)
    ────────────────────────────────────────────────────────── */
    var _settingsCache = null;
    function loadSettings() {
        if (_settingsCache) return $.Deferred().resolve(_settingsCache).promise();
        return $.get('api/settings/basic-get.php').then(function (r) {
            _settingsCache = (r && r.data) || {};
            return _settingsCache;
        });
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c];
        });
    }

    function loadReportHeader(reportName, dateFrom, dateTo) {
        return loadSettings().then(function (s) {
            var now = new Date();
            var generatedAt = now.toLocaleDateString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
            var fmtD = function (v) {
                if (!v) return '—';
                var d = new Date(v);
                return isNaN(d.getTime()) ? v : d.toLocaleDateString('en-GB', {
                    day: '2-digit', month: 'short', year: 'numeric'
                });
            };
            var firstChar = ((s.website_name || 'H')[0] || 'H').toUpperCase();
            var contacts  = [];
            if (s.phone) contacts.push('<span>📞 ' + escapeHtml(s.phone) + '</span>');
            if (s.email) contacts.push('<span>✉ '  + escapeHtml(s.email) + '</span>');

            var userName = (window.CURRENT_USER && window.CURRENT_USER.name)
                || $('#currentUserName').text() || '—';

            $('#reportHeader').html(
                '<div class="report-header">' +
                    '<div class="report-header-top">' +
                        '<div class="report-logo">' +
                            (s.logo
                                ? '<img src="' + escapeHtml(s.logo) + '" alt="Logo" onerror="this.style.display=\'none\'">'
                                : '<div class="logo-placeholder">' + firstChar + '</div>') +
                        '</div>' +
                        '<div class="report-business-info">' +
                            '<h2 class="business-name">' + escapeHtml(s.website_name || '') + '</h2>' +
                            '<div class="business-contacts">' + contacts.join('') + '</div>' +
                            (s.address ? '<div class="business-address">📍 ' + escapeHtml(s.address) + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<hr class="report-divider">' +
                    '<div class="report-identity">' +
                        '<div class="report-identity-content">' +
                            '<h3 class="report-name">' + escapeHtml(reportName) + '</h3>' +
                            '<div class="report-period">Period: ' + fmtD(dateFrom) + ' — ' + fmtD(dateTo) + '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('#footerDate').text(generatedAt);
            $('#footerUser').text(userName);
            return s;
        });
    }

    /* ──────────────────────────────────────────────────────────
       Print + CSV
    ────────────────────────────────────────────────────────── */
    function printReport() {
        var preview = document.getElementById('reportPreview');
        if (!preview || preview.style.display === 'none') {
            if (window.Toast) Toast.warning('Please generate the report first.');
            else alert('Please generate the report first.');
            return;
        }
        var title = current.reportName
                 || (current.config && (current.config.reportName || current.config.name))
                 || 'Report';

        // Clone preview verbatim — keeps .report-header (logo/brand/contacts/title/period)
        // and .report-table exactly as user sees them on screen.
        var $clone = $(preview).clone();
        $clone.find('.summary-cards, .summary-row, .summary-card, .summary-box, .report-summary-area, #reportSummaryArea, .print-summary-wrap, .screen-summary-cards').remove();
        $clone.find('.text-center:has(#footerDate)').remove();

        // Resolve admin logo URL to absolute (preview <img> may use relative path)
        // Admin logo only (not website_logo)
        var rawLogo = (_settingsCache && _settingsCache.logo) || '';
        if (rawLogo) {
            var origin  = location.origin || '';
            var appRoot = (window.APP_PATHS && APP_PATHS.ROOT) || '/TrainingAdmin';
            var abs;
            if (/^https?:\/\//i.test(rawLogo) || rawLogo.charAt(0) === '/') abs = rawLogo;
            else abs = origin + appRoot.replace(/\/+$/, '') + '/' + rawLogo.replace(/^\.?\/?/, '');
            $clone.find('.report-logo img').attr('src', abs);
        }

        var bodyHtml = $clone.html();

        // --- Absolute asset base so the print tab can load the SAME stylesheets ---
        var origin  = location.origin || '';
        var appRoot = (window.APP_PATHS && APP_PATHS.ROOT) || '/TrainingAdmin';
        var base    = origin + appRoot.replace(/\/+$/, '');
        var links   = [
            base + '/assets/css/bootstrap.min.css',
            base + '/assets/css/ui-theme.css',
            base + '/assets/css/report.css'
        ];

        // Carry the LIVE theme CSS variables (set inline on <html> by ui-theme.js)
        // into the print tab so report.css var() rules resolve to the same colors.
        var rootStyle = document.documentElement.style;
        var varsCss = '';
        for (var i = 0; i < rootStyle.length; i++) {
            var prop = rootStyle[i];
            if (prop.indexOf('--') === 0) {
                varsCss += prop + ':' + rootStyle.getPropertyValue(prop) + ';';
            }
        }

        // Resolve header color to a literal too (belt-and-suspenders: applies even
        // before the linked report.css finishes loading).
        function cssVar(name, fb) {
            try { var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim(); return v || fb; }
            catch (e) { return fb; }
        }
        var headBg   = cssVar('--ui-report-table-head-bg', '') || cssVar('--ui-root-primary', '#1a1a1a');
        var headText = cssVar('--ui-report-table-head-text', '#ffffff');

        // Only print-specific overrides on top of the real report.css.
        var css = [
            ':root{' + varsCss + '}',
            // report.css ships @page{size:A4 landscape}; reset so the print dialog controls orientation
            '@page{size:auto;}',
            'body{background:#fff;-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;}',
            '.pp-content{padding:0;background:#fff;box-shadow:none;}',
            '.report-preview{background:#fff;border:0;border-radius:0;box-shadow:none;margin:0;}',
            // Neutralize SPA wrapper padding carried in the clone
            '.p-3,.px-3,.pb-3,.py-2,.py-3{padding:0 !important;}',
            '.table-responsive{overflow:visible !important;}',
            // Hide the screen footer line (re-added by PrintPreview itself)
            '.report-preview .text-center small{display:none;}',
            // Force header color onto paper even if the linked CSS lags
            '.report-table thead tr,.report-table thead th{background:' + headBg + ' !important;color:' + headText + ' !important;-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;}'
        ].join('');

        if (window.PrintPreview) {
            PrintPreview.open({
                title:     title,
                html:      bodyHtml,
                css:       css,
                links:     links,
                paperSize: 'a4'
            });
        }
    }

    function exportCSV() {
        if (!current.rows.length) {
            if (window.Toast) Toast.warning('Please generate the report first.');
            else alert('Please generate the report first.');
            return;
        }
        ReportEngine.updateActiveColumns();
        var cols = ReportEngine.activeColumns;

        var header = cols.map(function (c) { return '"' + c.label.replace(/"/g, '""') + '"'; }).join(',');
        var rows = current.rows.map(function (row) {
            return cols.map(function (col) {
                var raw = row[col.key];
                var val = (raw === null || raw === undefined) ? '' : raw;
                // For currency/number export the raw value, not formatted string
                return '"' + String(val).replace(/"/g, '""') + '"';
            }).join(',');
        });
        var csv = [header].concat(rows).join('\n');
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = (current.fileName || 'report') + '_' +
                     (current.dateFrom || 'all') + '_to_' + (current.dateTo || 'all') + '.csv';
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    }

    /* ──────────────────────────────────────────────────────────
       ReportPage: high-level bootstrapper used by every page
       config = {
           apiUrl     : 'api/reports/sales.php',
           reportName : 'Sales Summary',
           fileName   : 'sales_summary',
           columns    : REPORT_COLUMNS,
           extraFilters: function ($container) { ... },   // build extra inputs (optional)
           collectFilters: function (params) { ... },     // mutate URLSearchParams (optional)
           summaryCards: function (summary) { return [...]; }, // optional
           defaultPreset: 'this-month' (optional)
       }
    ────────────────────────────────────────────────────────── */
    var ReportPage = {

        init: function (config) {
            var self = this;
            current.config     = config;
            current.columns    = config.columns || [];
            current.reportName = config.reportName || 'Report';
            current.fileName   = config.fileName   || 'report';
            current.rows       = [];
            current.summary    = {};

            this._mountShell();

            if (typeof config.extraFilters === 'function') {
                config.extraFilters($('#extraFilters'));
            }

            ReportEngine.renderColumnSelector(current.columns);
            applyDatePreset(config.defaultPreset || 'this-month');

            $(document).off('click.rep-preset').on('click.rep-preset', '.date-preset-chip', function () {
                applyDatePreset($(this).data('preset'));
            });
            $(document).off('change.rep-col').on('change.rep-col', '.rep-col', function () {
                ReportEngine.updateActiveColumns();
                if (current.rows.length) ReportEngine.render(current.rows, current._lastCards || []);
            });
            $('#btnPreview').off('click.rep').on('click.rep', function () { self.generate(); });
            $('#btnPrint')  .off('click.rep').on('click.rep', function () { printReport(); });
            $('#btnReset')  .off('click.rep').on('click.rep', function () { ReportEngine.reset(); });

            // Auto-load preview on open (unless caller opts out via autoLoad: false)
            if (config.autoLoad !== false) {
                setTimeout(function () { self.generate(); }, 50);
            }
        },

        _mountShell: function () {
            // The template HTML is expected to have a <div id="reportShell"></div>
            // We populate that shell with the standard filter + preview structure once.
            if ($('#reportShell').length === 0) return;
            $('#reportShell').html(
                // Filter Bar
                '<div class="card filter-bar mb-3"><div class="card-body py-2">' +
                    '<div class="d-flex flex-wrap gap-2 align-items-end">' +
                        '<div><label class="form-label small mb-1">Date From</label>' +
                            '<input type="date" class="form-control form-control-sm" id="filterDateFrom"></div>' +
                        '<div><label class="form-label small mb-1">Date To</label>' +
                            '<input type="date" class="form-control form-control-sm" id="filterDateTo"></div>' +
                        '<div id="extraFilters" class="d-flex flex-wrap gap-2 align-items-end"></div>' +
                        '<div class="ms-auto d-flex gap-1 align-items-end">' +
                            '<button class="btn btn-primary btn-sm"   id="btnPreview"><i class="bx bx-search"></i> Preview</button>' +
                            '<button class="btn btn-outline-secondary btn-sm" id="btnPrint"><i class="bx bx-printer"></i> Print</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-1 mt-2">' +
                        '<span class="text-muted small me-1">Quick:</span>' +
                        '<a class="date-preset-chip" data-preset="today">Today</a>' +
                        '<a class="date-preset-chip" data-preset="yesterday">Yesterday</a>' +
                        '<a class="date-preset-chip" data-preset="this-week">This Week</a>' +
                        '<a class="date-preset-chip" data-preset="last-week">Last Week</a>' +
                        '<a class="date-preset-chip" data-preset="this-month">This Month</a>' +
                        '<a class="date-preset-chip" data-preset="last-month">Last Month</a>' +
                        '<a class="date-preset-chip" data-preset="this-year">This Year</a>' +
                    '</div>' +
                '</div></div>' +
                // Column Selector
                '<div class="card column-selector mb-3"><div class="card-body py-2">' +
                    '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                        '<span class="text-muted small fw-semibold me-2">Show Columns:</span>' +
                        '<div id="columnCheckboxes" class="d-flex flex-wrap"></div>' +
                        '<button class="btn btn-outline-secondary btn-sm ms-auto" id="btnReset"><i class="bx bx-refresh"></i> Reset</button>' +
                    '</div>' +
                '</div></div>' +
                // Screen summary cards (hidden in print)
                '<div id="reportSummaryArea" class="report-summary-area mb-3"></div>' +
                // Preview
                '<div id="reportPreview" class="report-preview" style="display:none;">' +
                    '<div class="p-3 pb-0"><div id="reportHeader"></div></div>' +
                    '<div class="px-3 pb-3" id="reportTableArea"></div>' +
                    '<div class="px-3 pb-3 text-center">' +
                        '<small class="text-muted">' +
                            'Generated <span id="footerDate"></span> | Training Admin | naim islam | 01607025924' +
                        '</small>' +
                    '</div>' +
                '</div>'
            );
        },

        generate: function () {
            var self = this;
            var cfg  = current.config;
            var dateFrom = $('#filterDateFrom').val();
            var dateTo   = $('#filterDateTo').val();
            if (cfg.dateRequired !== false && (!dateFrom || !dateTo)) {
                if (window.Toast) Toast.warning('Please select a date range.');
                else alert('Please select a date range.');
                return;
            }
            current.dateFrom = dateFrom;
            current.dateTo   = dateTo;

            $('#reportTableArea').html(
                '<div class="text-center py-5 text-muted">' +
                    '<div class="spinner-border spinner-border-sm me-2"></div> Loading…' +
                '</div>'
            );
            $('#reportPreview').show();

            loadReportHeader(current.reportName, dateFrom, dateTo);

            var params = new URLSearchParams();
            if (dateFrom) params.set('date_from', dateFrom);
            if (dateTo)   params.set('date_to',   dateTo);
            if (typeof cfg.collectFilters === 'function') cfg.collectFilters(params);

            $.get(cfg.apiUrl + '?' + params.toString())
                .done(function (r) {
                    if (!r || r.success === false) {
                        $('#reportTableArea').html(
                            '<div class="alert alert-danger m-0">⚠️ ' +
                            ((r && r.message) || 'Failed to load report.') + '</div>'
                        );
                        return;
                    }
                    var data    = r.data || r;
                    var rows    = data.rows || [];
                    var summary = data.summary || {};
                    current.summary = summary;

                    var cards = [];
                    if (typeof cfg.summaryCards === 'function') cards = cfg.summaryCards(summary, rows) || [];
                    current._lastCards = cards;
                    ReportEngine.render(rows, cards);
                })
                .fail(function () {
                    $('#reportTableArea').html(
                        '<div class="alert alert-danger m-0">⚠️ Network error. Please try again.</div>'
                    );
                });
        }
    };

    /* ──────────────────────────────────────────────────────────
       Public exports
    ────────────────────────────────────────────────────────── */
    window.ReportEngine     = ReportEngine;
    window.ReportPage       = ReportPage;
    window.applyDatePreset  = applyDatePreset;
    window.loadReportHeader = loadReportHeader;
    window.printReport      = printReport;
    window.exportCSV        = exportCSV;

})(window);

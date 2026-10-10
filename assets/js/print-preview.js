/**
 * window.PrintPreview — unified print preview helper.
 *
 * Opens a new browser tab via Blob URL (real URL visible in address bar)
 * containing print-ready content with a sticky toolbar AND a bottom action bar.
 *
 *   PrintPreview.open({
 *       title: 'Receipt #123',
 *       html:  '<div>...</div>',
 *       css:   'body{...}',
 *       autoPrint: false,
 *       paperSize: 'a4' | 'a4-l' | 'letter' | 'thermal-58' | 'thermal-80'
 *   });
 */
(function (w) {
    'use strict';

    var DEFAULT_CSS = [
        '*,*::before,*::after{box-sizing:border-box;}',
        // Force backgrounds/colors onto paper (Chrome strips them by default)
        'html,body,*{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;color-adjust:exact !important;}',
        'body{font-family:Arial,Helvetica,sans-serif;padding:0;margin:0;color:#000;background:#f5f5f5;}',
        '.pp-actions-bottom{width:100%;margin:24px 0;padding:12px 20px;display:flex;gap:8px;justify-content:center;}',
        '.pp-btn{padding:9px 22px;border:0;border-radius:4px;cursor:pointer;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;}',
        '.pp-btn-print{background:#0d6efd;color:#fff;}',
        '.pp-btn-print:hover{background:#0b5ed7;}',
        '.pp-btn-pdf{background:#198754;color:#fff;}',
        '.pp-btn-pdf:hover{background:#157347;}',
        '.pp-btn-cancel{background:#6c757d;color:#fff;}',
        '.pp-btn-cancel:hover{background:#5c636a;}',
        '.pp-content{width:100%;max-width:none;margin:0;background:#fff;padding:24px 32px;box-shadow:none;min-height:60vh;}',
        '.pp-poweredby{text-align:center;font-size:11px;color:#888;margin-top:24px;padding-top:8px;border-top:1px dashed #ccc;}',
        '.pp-receipt{max-width:340px;font-family:"Courier New",monospace;font-size:12px;line-height:1.4;}',
        '.pp-receipt h2,.pp-receipt h3{text-align:center;margin:0 0 4px;}',
        '.pp-receipt hr{border:0;border-top:1px dashed #000;margin:8px 0;}',
        '.pp-receipt table{width:100%;border-collapse:collapse;}',
        '.pp-receipt td{padding:2px 0;}',
        'table{width:100%;border-collapse:collapse;}',
        'th,td{border:1px solid #000;padding:6px 8px;text-align:left;}',
        'th{background:#8b0000;font-weight:700;}',
        '.text-end,.text-right{text-align:right;}',
        '.text-center{text-align:center;}',
        '@media print{',
        '  html,body,*{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important;color-adjust:exact !important;}',
        '  body{background:#fff;padding:0;}',
        '  .pp-actions-bottom,.no-print{display:none !important;}',
        '  .pp-content{box-shadow:none;margin:0;padding:0;max-width:none;}',
        '}'
    ].join('');

    function _printedAt() {
        try {
            return new Date().toLocaleString('en-GB', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) {
            return new Date().toString();
        }
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Paper presets. Use `size: A4` (no orientation) so browser print dialog
    // can switch portrait/landscape freely. Set orientation only when caller
    // explicitly requests it via 'a4-l'.
    var PAPERS = {
        // No fixed size for A4/Letter so browser print dialog can rotate (portrait/landscape).
        'a4':         { page: '',             margin: '14mm 12mm', maxw: '100%' },
        'a4-l':       { page: 'A4 landscape', margin: '12mm 14mm', maxw: '100%' },
        'letter':     { page: '',             margin: '14mm 12mm', maxw: '100%' },
        'thermal-58': { page: '58mm auto',    margin: '0',         maxw: '220px' },
        'thermal-80': { page: '80mm auto',    margin: '0',         maxw: '300px' }
    };

    function open(opts) {
        opts = opts || {};
        var title     = opts.title     || 'Print Preview';
        var html      = opts.html      || '';
        var extraCss  = opts.css       || '';
        var autoPrint = opts.autoPrint === true;
        var bodyClass = opts.bodyClass || '';
        var links     = Array.isArray(opts.links) ? opts.links : []; // absolute stylesheet URLs
        var linkTags  = links.map(function (href) {
            return '<link rel="stylesheet" href="' + escapeHtml(href) + '">';
        }).join('');
        var paper     = PAPERS[opts.paperSize] || PAPERS['a4'];
        // @page size only emitted when paper preset specifies it. Omitting `size:`
        // lets the browser print dialog control orientation/page-size (Chrome/Edge).
        var paperCss = '@page{' + (paper.page ? 'size:' + paper.page + ';' : '') + 'margin:' + paper.margin + ';}' +
                       '.pp-content{max-width:' + paper.maxw + ';}';

        var doc = [
            '<!DOCTYPE html><html><head><meta charset="utf-8">',
            '<title>', escapeHtml(title), '</title>',
            '<meta name="viewport" content="width=device-width,initial-scale=1">',
            linkTags,
            '<style>', DEFAULT_CSS, paperCss, extraCss, '</style>',
            '</head><body class="', escapeHtml(bodyClass), '">',
            '<div class="pp-content">',
                html,
                '<div class="pp-poweredby">Printed: ', escapeHtml(_printedAt()), ' &nbsp;|&nbsp; Powered by Training Admin | naim islam | 01607025924</div>',
            '</div>',
            '<div class="pp-actions-bottom no-print">',
                '<button class="pp-btn pp-btn-cancel" onclick="window.close()">Cancel</button>',
                '<button class="pp-btn pp-btn-pdf" id="ppPdfBtn">',
                    '<span aria-hidden="true">&#128196;</span> Download PDF',
                '</button>',
                '<button class="pp-btn pp-btn-print" id="ppPrintBtn">',
                    '<span aria-hidden="true">&#128424;</span> Print',
                '</button>',
            '</div>',
            '<script>',
                'document.getElementById("ppPrintBtn").addEventListener("click",function(){window.print();});',
                // Save-as-PDF leverages browser print dialog ("Save as PDF" destination).
                'document.getElementById("ppPdfBtn").addEventListener("click",function(){window.print();});',
                autoPrint
                    ? 'window.addEventListener("load",function(){setTimeout(function(){window.print();},300);});'
                    : '',
            '</', 'script>',
            '</body></html>'
        ].join('');

        // Use Blob URL so new tab has real URL (not about:blank).
        var blob, url;
        try {
            blob = new Blob([doc], { type: 'text/html' });
            url  = URL.createObjectURL(blob);
        } catch (e) {
            blob = null; url = null;
        }

        if (url) {
            var w2 = window.open(url, '_blank');
            if (!w2) {
                alert('Popup blocked — allow popups to preview print.');
                URL.revokeObjectURL(url);
                return null;
            }
            // Free blob memory once new tab loads it
            setTimeout(function () { try { URL.revokeObjectURL(url); } catch (e) {} }, 60000);
            return w2;
        }

        // Fallback: doc.write into about:blank
        var w3 = window.open('', '_blank');
        if (!w3) { alert('Popup blocked'); return null; }
        w3.document.open(); w3.document.write(doc); w3.document.close();
        return w3;
    }

    w.PrintPreview = { open: open, escapeHtml: escapeHtml };
})(window);

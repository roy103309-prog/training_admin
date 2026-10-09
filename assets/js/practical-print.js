/**
 * Printing of the practical job pages (templates/practical-*.html).
 *
 *   PracticalPrint.prepare()                        open the print window in a click handler, pass it on after AJAX
 *   PracticalPrint.token(job, win?)                 80mm job token (thermal printer)
 *   PracticalPrint.register(title, items, summary, win?)  A4 job register (daily / monthly)
 *   PracticalPrint.csv(filename, items)             job register as CSV (opens in Excel)
 *   PracticalPrint.outSlip(job, win?)               80mm slip of an out-student job (out-get.php / out-create.php)
 *   PracticalPrint.table(title, columns, rows, opts?, win?)  any A4 list; columns = [{ t: 'Head', v: function(row){}, r: true }]
 *   PracticalPrint.csvTable(filename, columns, rows)        the same list as CSV
 *
 * job / items come from api/practical-jobs-get.php and jobs-list.php.
 */
(function (window) {
    'use strict';

    if (window.PracticalPrint) return;

    function esc(v) { return window.NL ? NL.esc(v) : String(v == null ? '' : v); }
    function money(v) { return '৳' + Number(v || 0).toLocaleString(); }
    function dmy(v) { var p = String(v || '').substring(0, 10).split('-'); return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : (v || ''); }
    function institute() { return (window.NL && NL.instituteName()) || ''; }

    // Print window opened during the click, before an AJAX call (pop-up blockers allow only that)
    function prepare() {
        var w = window.open('', '_blank');
        if (w) w.document.write('<p style="font-family:Arial;padding:20px">Preparing print...</p>');
        else if (window.Toast) Toast.error('Allow pop-ups to print');
        return w;
    }

    function openPrint(title, css, body, win) {
        var w = (win && !win.closed) ? win : window.open('', '_blank');
        if (!w) { if (window.Toast) Toast.error('Allow pop-ups to print'); return; }
        w.document.open();
        w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + esc(title) + '</title><style>' + css + '</style></head><body>' + body + '</body></html>');
        w.document.close();
        setTimeout(function () { w.focus(); w.print(); }, 400);
    }

    var TOKEN_CSS =
        '@page { size: 80mm auto; margin: 3mm; }' +
        'body { width: 72mm; margin: 0 auto; font-family: Arial, "Noto Sans Bengali", sans-serif; font-size: 12px; color: #000; }' +
        '.c { text-align: center; } .b { font-weight: bold; }' +
        '.inst { font-size: 15px; font-weight: bold; text-transform: uppercase; }' +
        '.ttl { margin: 4px 0; padding: 3px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; font-weight: bold; letter-spacing: 1px; }' +
        '.no { font-size: 22px; font-weight: bold; letter-spacing: 1px; margin: 4px 0; }' +
        'table { width: 100%; border-collapse: collapse; } td { padding: 2px 0; vertical-align: top; } td.r { text-align: right; }' +
        '.hr { border-top: 1px dashed #000; margin: 5px 0; }' +
        '.tot td { font-size: 14px; font-weight: bold; }' +
        '.note { border: 1px solid #000; padding: 4px; margin-top: 6px; text-align: center; font-size: 11px; }' +
        '.sig { margin-top: 26px; border-top: 1px solid #000; text-align: center; font-size: 11px; padding-top: 2px; }' +
        '.ft { margin-top: 6px; font-size: 10px; text-align: center; }';

    function token(job, win) {
        var limit = Number(job.category_max_jobs) > 0 ? job.category_job_no + ' of ' + job.category_max_jobs : '#' + job.category_job_no;
        var verify = job.verify_method === 'biometric' ? 'Bio Finger (বায়ো ফিঙ্গার)' : 'Supervisor (সুপারভাইজার)' + (job.verified_by_name ? ': ' + job.verified_by_name : '');
        var row = function (k, v, cls) { return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td>' + k + '</td><td class="r">' + v + '</td></tr>'; };
        var body =
            '<div class="c"><div class="inst">' + esc(institute()) + '</div>' +
            '<div class="ttl">PRACTICAL JOB TOKEN</div>' +
            '<div class="no">' + esc(job.job_no) + '</div>' +
            '<div>Date: ' + esc(dmy(job.job_date)) + '</div></div>' +
            '<div class="hr"></div>' +
            '<table>' +
            row('Student', '<span class="b">' + esc(job.student_name) + '</span>') +
            row('Student ID', esc(job.student_code)) +
            (job.course_name ? row('Course', esc(job.course_name)) : '') +
            row('Job Category', '<span class="b">' + esc(job.category_name) + '</span>') +
            row('Category Job', esc(limit)) +
            '</table><div class="hr"></div><table>' +
            row('Price', money(job.price)) +
            (Number(job.discount) > 0 ? row('Discount', '- ' + money(job.discount)) : '') +
            row('Payable', money(job.payable), 'tot') +
            row('Job Balance', money(job.job_balance)) +
            '</table>' +
            '<div class="note">Verification: ' + esc(verify) + '<br>বায়ো ফিঙ্গার অথবা সুপারভাইজার ভেরিফিকেশন</div>' +
            (job.remarks ? '<div class="ft">' + esc(job.remarks) + '</div>' : '') +
            '<div class="sig">Supervisor Signature</div>' +
            '<div class="ft">Issued by ' + esc(job.created_by_name || '') + ' · ' + esc(String(job.created_at || '').substring(0, 16)) + '</div>';
        openPrint('Token ' + job.job_no, TOKEN_CSS, body, win);
    }

    var REGISTER_CSS =
        '@page { size: A4; margin: 12mm; }' +
        'body { font-family: Arial, "Noto Sans Bengali", sans-serif; font-size: 12px; color: #000; }' +
        'h2, h3 { margin: 0; text-align: center; } h3 { font-weight: normal; margin: 4px 0 10px; }' +
        'table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #555; padding: 4px 6px; } th { background: #eee; }' +
        '.r { text-align: right; } tfoot td { font-weight: bold; }' +
        '.sum { margin-top: 10px; } .sum span { display: inline-block; margin-right: 18px; }';

    function register(title, items, summary, win) {
        var rows = items.map(function (j, i) {
            return '<tr><td>' + (i + 1) + '</td><td>' + esc(dmy(j.job_date)) + '</td><td>' + esc(j.job_no) + '</td><td>' + esc(j.student_name) + '</td>' +
                '<td>' + esc(j.student_code) + '</td><td>' + esc(j.category_name) + '</td><td class="r">' + money(j.price) + '</td>' +
                '<td class="r">' + money(j.discount) + '</td><td class="r">' + money(j.payable) + '</td></tr>';
        }).join('') || '<tr><td colspan="9" style="text-align:center">No jobs</td></tr>';
        var cats = (summary.by_category || []).map(function (c) { return '<span>' + esc(c.name) + ': <b>' + c.count + '</b></span>'; }).join('');
        var body = '<h2>' + esc(institute()) + '</h2><h3>' + esc(title) + '</h3>' +
            '<table><thead><tr><th>SL</th><th>Date</th><th>Job No</th><th>Trainee Name</th><th>Student ID</th><th>Job Category</th><th>Price</th><th>Discount</th><th>Payable</th></tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '<tfoot><tr><td colspan="6">Total: ' + summary.count + ' job(s)</td><td class="r">' + money(summary.price) + '</td><td class="r">' + money(summary.discount) + '</td><td class="r">' + money(summary.payable) + '</td></tr></tfoot></table>' +
            '<div class="sum">' + cats + '</div>';
        openPrint(title, REGISTER_CSS, body, win);
    }

    function csv(filename, items) {
        csvTable(filename, [
            { t: 'Date', v: function (j) { return j.job_date; } }, { t: 'Job No', v: function (j) { return j.job_no; } },
            { t: 'Trainee Name', v: function (j) { return j.student_name; } }, { t: 'Student ID', v: function (j) { return j.student_code; } },
            { t: 'Job Category', v: function (j) { return j.category_name; } }, { t: 'Price', v: function (j) { return j.price; } },
            { t: 'Discount', v: function (j) { return j.discount; } }, { t: 'Payable', v: function (j) { return j.payable; } }
        ], items);
    }

    // CSV with a leading SL column; UTF-8 BOM so Excel shows Bangla correctly
    function csvTable(filename, columns, rows) {
        var q = function (v) { return '"' + String(v == null ? '' : v).replace(/"/g, '""') + '"'; };
        var lines = [['SL'].concat(columns.map(function (c) { return c.t; })).map(q).join(',')];
        rows.forEach(function (row, i) {
            lines.push([i + 1].concat(columns.map(function (c) { return c.v(row); })).map(q).join(','));
        });
        var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
    }

    function outSlip(job, win) {
        var row = function (k, v, cls) { return '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td>' + k + '</td><td class="r">' + v + '</td></tr>'; };
        var due = Number(job.price) - Number(job.paid);
        var body =
            '<div class="c"><div class="inst">' + esc(institute()) + '</div>' +
            '<div class="ttl">OUT STUDENT JOB SLIP</div>' +
            '<div class="no">' + esc(job.token_no) + '</div>' +
            '<div>Date: ' + esc(dmy(job.job_date)) + '</div></div>' +
            '<div class="hr"></div>' +
            '<table>' +
            row('Candidate', '<span class="b">' + esc(job.candidate_name) + '</span>') +
            (job.mobile ? row('Mobile', esc(job.mobile)) : '') +
            (job.passport_number ? row('Passport', esc(job.passport_number)) : '') +
            (job.agent_reference ? row('Agent / Ref', esc(job.agent_reference)) : '') +
            row('Job Category', '<span class="b">' + esc(job.category_name) + '</span>') +
            '</table><div class="hr"></div><table>' +
            row('Price', money(job.price)) +
            row('Paid', money(job.paid), 'tot') +
            (due > 0 ? row('Due', money(due)) : '') +
            row('Paid To', esc(job.paid_to)) +
            (job.payment_method ? row('Method', esc(job.payment_method)) : '') +
            (job.payment_ref ? row('Payment Ref', esc(job.payment_ref)) : '') +
            (job.voucher_no ? row('Voucher', esc(job.voucher_no)) : '') +
            '</table>' +
            (job.remarks ? '<div class="ft">' + esc(job.remarks) + '</div>' : '') +
            '<div class="sig">Authorized Signature</div>' +
            '<div class="ft">Issued by ' + esc(job.created_by_name || '') + ' · ' + esc(String(job.created_at || '').substring(0, 16)) + '</div>';
        openPrint('Slip ' + job.token_no, TOKEN_CSS, body, win);
    }

    /**
     * A4 list. opts: { subtitle, foot: [cells html], footFrom: column index where foot cells start, summary: html }
     * Values from v() are escaped; return { html: '...' } for ready markup.
     */
    function table(title, columns, rows, opts, win) {
        opts = opts || {};
        var cell = function (c, row) { var v = c.v(row); return '<td' + (c.r ? ' class="r"' : '') + '>' + (v && v.html !== undefined ? v.html : esc(v)) + '</td>'; };
        var body = '<h2>' + esc(institute()) + '</h2><h3>' + esc(title) + (opts.subtitle ? '<br><small>' + esc(opts.subtitle) + '</small>' : '') + '</h3>' +
            '<table><thead><tr><th>SL</th>' + columns.map(function (c) { return '<th>' + esc(c.t) + '</th>'; }).join('') + '</tr></thead><tbody>' +
            (rows.map(function (row, i) { return '<tr><td>' + (i + 1) + '</td>' + columns.map(function (c) { return cell(c, row); }).join('') + '</tr>'; }).join('') ||
             '<tr><td colspan="' + (columns.length + 1) + '" style="text-align:center">No records</td></tr>') + '</tbody>';
        if (opts.foot) {
            var from = opts.footFrom || 1;
            body += '<tfoot><tr><td colspan="' + from + '">Total: ' + rows.length + '</td>' + opts.foot.map(function (h) { return '<td class="r">' + h + '</td>'; }).join('') +
                (columns.length + 1 - from - opts.foot.length > 0 ? '<td colspan="' + (columns.length + 1 - from - opts.foot.length) + '"></td>' : '') + '</tr></tfoot>';
        }
        body += '</table>' + (opts.summary ? '<div class="sum">' + opts.summary + '</div>' : '');
        openPrint(title, REGISTER_CSS, body, win);
    }

    window.PracticalPrint = { prepare: prepare, token: token, register: register, csv: csv, outSlip: outSlip, table: table, csvTable: csvTable, money: money, dmy: dmy };
})(window);

/* Notifications bell — polls /api/notifications/list.php and updates header dropdown. */
(function (w) {
    'use strict';

    var POLL_MS = 30000;
    var _timer = null;
    var _started = false;
    var _lastUnread = 0;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    function timeAgo(s) {
        if (!s) return '';
        var d = new Date(String(s).replace(' ', 'T'));
        if (isNaN(d.getTime())) return s;
        var sec = Math.floor((Date.now() - d.getTime()) / 1000);
        if (sec < 60)    return sec + 's ago';
        if (sec < 3600)  return Math.floor(sec/60) + 'm ago';
        if (sec < 86400) return Math.floor(sec/3600) + 'h ago';
        return Math.floor(sec/86400) + 'd ago';
    }

    function iconFor(type) {
        switch (type) {
            case 'booking':  return 'bx-calendar-check text-success';
            case 'order':    return 'bx-cart text-primary';
            case 'payment':  return 'bx-credit-card text-info';
            case 'warning':  return 'bx-error text-warning';
            default:         return 'bx-bell text-secondary';
        }
    }

    function render(items, unread) {
        var $count = $('.alert-count');
        if (unread > 0) {
            $count.text(unread > 99 ? '99+' : unread).show();
        } else {
            $count.text('0').show();
        }

        var $list = $('.header-notifications-list');
        if (!$list.length) return;

        if (!items.length) {
            $list.html(
                '<a class="dropdown-item" href="javascript:;">' +
                    '<div class="d-flex align-items-center">' +
                        '<div class="notify text-info"><i class="bx bx-info-circle"></i></div>' +
                        '<div class="flex-grow-1">' +
                            '<h6 class="msg-name">No new notifications</h6>' +
                            '<p class="msg-info">You are all caught up.</p>' +
                        '</div>' +
                    '</div>' +
                '</a>'
            );
            return;
        }

        var hasUnread = items.some(function (n) { return !parseInt(n.is_read, 10); });
        var actionBar = hasUnread
            ? '<div class="text-end px-3 py-2 border-bottom">' +
                '<a href="javascript:;" class="small text-primary" id="bellMarkAll">Mark all as read</a>' +
              '</div>'
            : '';

        var rows = items.map(function (n) {
            var unreadCls = parseInt(n.is_read, 10) ? '' : 'bg-light';
            var link = n.link ? esc(n.link) : 'javascript:;';
            var route = n.link ? ' data-route' : '';
            return '<a class="dropdown-item ' + unreadCls + ' bell-item" href="' + link + '"' + route + ' data-id="' + n.id + '">' +
                       '<div class="d-flex align-items-start">' +
                           '<div class="notify"><i class="bx ' + iconFor(n.type) + '"></i></div>' +
                           '<div class="flex-grow-1">' +
                               '<h6 class="msg-name">' + esc(n.title) +
                                   '<span class="msg-time float-end small text-muted">' + esc(timeAgo(n.created_at)) + '</span>' +
                               '</h6>' +
                               '<p class="msg-info">' + esc(n.message || '') + '</p>' +
                           '</div>' +
                       '</div>' +
                   '</a>';
        }).join('');

        $list.html(actionBar + rows);
    }

    function fetchOnce() {
        $.get('api/notifications/list.php').done(function (r) {
            if (!r || !r.success) return;
            var items = (r.data && r.data.items) || [];
            var unread = (r.data && r.data.unread) || 0;
            if (unread > _lastUnread && _lastUnread !== 0) {
                if (window.Toast) Toast.info('You have ' + unread + ' new notification(s)');
            }
            _lastUnread = unread;
            render(items, unread);
        });
    }

    function bind() {
        $(document).off('click.bell-item').on('click.bell-item', '.bell-item', function (e) {
            var id = $(this).data('id');
            if (!id) return;
            $.ajax({
                url: 'api/notifications/mark-read.php', method: 'POST',
                contentType: 'application/json', data: JSON.stringify({ id: id })
            }).done(function () { fetchOnce(); });
        });
        $(document).off('click.bell-all').on('click.bell-all', '#bellMarkAll', function (e) {
            e.preventDefault(); e.stopPropagation();
            $.ajax({
                url: 'api/notifications/mark-read.php', method: 'POST',
                contentType: 'application/json', data: JSON.stringify({ all: true })
            }).done(function () { fetchOnce(); });
        });
    }

    function start() {
        if (_started) { fetchOnce(); return; }
        _started = true;
        bind();
        fetchOnce();
        _timer = setInterval(fetchOnce, POLL_MS);
    }

    function stop() {
        if (_timer) { clearInterval(_timer); _timer = null; }
        _started = false;
    }

    w.NotificationsBell = { start: start, stop: stop, refresh: fetchOnce };
})(window);

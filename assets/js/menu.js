/**
 * Menu - Handles active menu state for MetisMenu sidebar
 */
var Menu = {
    _getMenuText: function($a) {
        if (!$a || !$a.length) return '';
        var titleText = $.trim($a.children('.menu-title').first().text());
        if (titleText) return titleText;

        var $clone = $a.clone();
        $clone.find('i').remove();
        return $.trim($clone.text());
    },

    _buildTrailFromTarget: function($target) {
        var trail = [];
        if (!$target || !$target.length) return trail;

        var $currentLi = $target.closest('li');
        while ($currentLi.length) {
            var $currentA = $currentLi.children('a').first();
            var text = this._getMenuText($currentA);
            if (text) trail.unshift(text);

            if ($currentLi.parent().is('#menu')) break;
            $currentLi = $currentLi.parent().closest('li');
        }

        // Remove duplicates while preserving order.
        var clean = [];
        for (var i = 0; i < trail.length; i++) {
            if (trail[i] && (clean.length === 0 || clean[clean.length - 1] !== trail[i])) {
                clean.push(trail[i]);
            }
        }
        // Keep only 1st and 2nd layer direction.
        return clean.slice(0, 2);
    },

    _renderBreadcrumb: function(trail, fallbackTitle) {
        var $content = $('#spa-content');
        if (!$content.length) return;

        var $crumb = $content.find('.page-breadcrumb').first();
        var hasTrail = trail && trail.length > 0;
        var pageTitle = fallbackTitle || (hasTrail ? trail[trail.length - 1] : 'Dashboard');
        var isDashboardTitle = (pageTitle || '').toLowerCase() === 'dashboard';

        // Ensure breadcrumb container exists on every template.
        if (!$crumb.length) {
            $crumb = $(
                '<div class="page-breadcrumb mb-3">' +
                    '<div class="d-flex flex-column">' +
                        '<div class="breadcrumb-title"></div>' +
                        '<nav aria-label="breadcrumb" class="mt-1"><ol class="breadcrumb mb-0 p-0"></ol></nav>' +
                    '</div>' +
                '</div>'
            );
            $content.prepend($crumb);
        }

        // Hide full breadcrumb area on Dashboard (title + direction path).
        if (isDashboardTitle) {
            $crumb.addClass('d-none');
            return;
        }
        $crumb.removeClass('d-none');

        var $titleEl = $crumb.find('.breadcrumb-title').first();
        $titleEl.text(pageTitle).removeClass('d-none');

        var $navWrap = $crumb.find('nav[aria-label="breadcrumb"]');
        if (!$navWrap.length) {
            $navWrap = $('<nav aria-label="breadcrumb" class="mt-1"><ol class="breadcrumb mb-0 p-0"></ol></nav>');
            $titleEl.after($navWrap);
        }

        var $ol = $navWrap.find('ol.breadcrumb');
        if (!$ol.length) {
            $navWrap.html('<ol class="breadcrumb mb-0 p-0"></ol>');
            $ol = $navWrap.find('ol.breadcrumb');
        }

        var items = hasTrail ? trail.slice(0, 2) : [];
        var html = '<li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>';
        if (items.length) {
            for (var i = 0; i < items.length; i++) {
                var isLast = i === items.length - 1;
                html += isLast
                    ? '<li class="breadcrumb-item active" aria-current="page">' + items[i] + '</li>'
                    : '<li class="breadcrumb-item">' + items[i] + '</li>';
            }
        }
        $ol.html(html);
    },

    setActive: function(menuKey, fallbackTitle, attempt) {
        attempt = attempt || 0;
        if (!menuKey) {
            this._renderBreadcrumb([], fallbackTitle);
            return;
        }
        var $menu = $('#menu');
        if (!$menu.length) {
            this._renderBreadcrumb([], fallbackTitle);
            if (attempt < 10) {
                var self = this;
                setTimeout(function() {
                    self.setActive(menuKey, fallbackTitle, attempt + 1);
                }, 80);
            }
            return;
        }

        // 1) Clear everything
        $menu.find('.mm-active').removeClass('mm-active');
        $menu.find('ul').each(function() {
            $(this)
                .removeClass('mm-show mm-collapsing')
                .addClass('mm-collapse')
                .attr('aria-expanded', 'false')
                .css({
                    display: '',
                    height: '',
                    overflow: ''
                });
        });
        $menu.find('[aria-expanded="true"]').attr('aria-expanded', 'false');

        // 2) Find matching link by data-menu attribute
        var $target = $menu.find('[data-menu="' + menuKey + '"]');
        if (!$target.length) {
            this._renderBreadcrumb([], fallbackTitle);
            return;
        }

        // 3) Mark the leaf <li>
        var $li = $target.closest('li');
        $li.addClass('mm-active');

        // 4) Walk up: mark parent <li> active and parent <ul> expanded
        $li.parentsUntil('#menu', 'li').addClass('mm-active');
        $li.parentsUntil('#menu', 'ul').removeClass('mm-collapse mm-collapsing').addClass('mm-show')
            .css('display', 'block')
            .attr('aria-expanded', 'true');

        // 5) Mark parent arrows as expanded
        $li.parentsUntil('#menu', 'li').each(function() {
            $(this).children('a.has-arrow').attr('aria-expanded', 'true');
        });

        // 6) Keep template breadcrumb synchronized with active menu path
        this._renderBreadcrumb(this._buildTrailFromTarget($target), fallbackTitle);
    }
};

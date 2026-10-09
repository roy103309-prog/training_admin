/**
 * Hotel Management System - SPA Core
 * Single Page Application with AJAX content loading
 */

(function($) {
    'use strict';

    var PATHS = window.APP_PATHS || {
        ROOT: '',
        LOGIN_URL: '/login',
        ADMIN_BASE: '/admin',
        API_BASE: '/api',
        INCLUDES_BASE: '/includes'
    };

    var currentUser = null;
    var currentLocation = null;
    var userMenus = [];
    var userPermissions = {};
    var currentPageName = '';
    var websiteUrl = '';
    var tenantId = null;
    var tenantDatabase = '';
    var currentHotelName = 'Hotel Management';
    // Old script tracking removed — now handled by PageLoader module

    /* =========================================================
     *  HELPERS
     * ========================================================= */

    function isSpaShell() {
        return window.location.pathname.indexOf(PATHS.ADMIN_BASE) === 0;
    }

    function redirectToLogin() {
        var path = window.location.pathname;
        if (path.indexOf('login') === -1 && path.indexOf('register') === -1) {
            window.location.href = PATHS.LOGIN_URL;
        }
    }

    function showNotification(message) {
        var toast = $('<div class="position-fixed top-0 end-0 p-3" style="z-index:11000">' +
            '<div class="toast show" role="alert"><div class="toast-header">' +
            '<i class="bx bx-info-circle me-2"></i><strong class="me-auto">Information</strong>' +
            '<button type="button" class="btn-close" data-bs-dismiss="toast"></button></div>' +
            '<div class="toast-body">' + message + '</div></div></div>');
        $('body').append(toast);
        setTimeout(function() { toast.fadeOut(function() { $(this).remove(); }); }, 3000);
    }

    /* =========================================================
     *  SESSION & INIT
     * ========================================================= */

    $(document).ready(function() {
        checkSession();
    });

    function checkSession() {
        console.log('[SPA] Checking session...');
        $.ajax({
            url: PATHS.API_BASE + '/auth/check-session.php',
            method: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    console.log('[SPA] Session valid');
                    currentUser = response.data.user;
                    currentLocation = response.data.current_location_id;
                    tenantId = response.data.tenant_id;
                    tenantDatabase = response.data.tenant_database || '';
                    if (typeof window.fetchAndApplyUISettings === 'function') {
                        window.fetchAndApplyUISettings();
                    }
                    loadUserMenus();
                } else {
                    console.warn('[SPA] Session invalid, redirecting');
                    redirectToLogin();
                }
            },
            error: function(xhr) {
                console.error('[SPA] Session check failed:', xhr.status);
                redirectToLogin();
            }
        });
    }

    function loadHotelName() {
        function applyHotelName(name) {
            currentHotelName = name || 'Hotel Management';
            $('#hotelNameDisplay').text(currentHotelName);
            $('#footerHotelName').text(currentHotelName);
        }

        $.ajax({
            url: PATHS.API_BASE + '/get-tenant-info.php',
            method: 'GET',
            success: function(r) {
                if (r.success && r.data && r.data.business_name) {
                    applyHotelName(r.data.business_name);
                } else {
                    applyHotelName('Hotel Management');
                }
            },
            error: function() {
                applyHotelName('Hotel Management');
            }
        });
    }

    function loadUserMenus() {
        console.log('[SPA] Loading user menus...');
        $.ajax({
            url: PATHS.API_BASE + '/menu/get-user-menu.php',
            method: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    userMenus = response.data.menus || [];
                    userPermissions = response.data.permissions || {};
                }
                // Render UI even if menus are empty
                renderSidebar();
                renderHeader();
                loadHotelName();
                renderFooter();
            },
            error: function(xhr) {
                console.warn('[SPA] Menu load failed, rendering with defaults:', xhr.status);
                // Still render UI so the app is usable
                renderSidebar();
                renderHeader();
                loadHotelName();
                renderFooter();
            }
        });
    }

    /* =========================================================
     *  SIDEBAR
     * ========================================================= */

    function renderSidebar() {
        console.log('[SPA] Rendering sidebar...');
        $.ajax({
            url: PATHS.INCLUDES_BASE + '/new-sidebar.html',
            method: 'GET',
            dataType: 'html',
            cache: false,
            success: function(html) {
                console.log('[SPA] Sidebar loaded, length:', html.length);
                $('#sidebar-container').html(html);

                // Block placeholder links before MetisMenu click handlers run
                bindComingSoonLinks();

                // Initialize MetisMenu with accordion behavior (only one submenu open at a time)
                try { $('#menu').metisMenu({ toggle: true }); } catch(e) { console.warn('[SPA] MetisMenu init error:', e); }

                // Institute ID in sidebar
                if (tenantDatabase) {
                    var serial = tenantDatabase.replace('hotel_', '');
                    $('#sidebarInstituteId').text('ID: ' + serial);
                }

                setupWebsiteLink();

                if (isSpaShell() && typeof Router !== 'undefined') {
                    console.log('[SPA] Handing off to Router...');
                    Router.init();
                } else if (!isSpaShell()) {
                    markActivePage();
                }
            },
            error: function(xhr) {
                console.error('[SPA] Failed to load sidebar:', xhr.status);
            }
        });
    }

    function markActivePage() {
        // For non-SPA pages — try to match by data-menu or href
        var page = window.location.pathname.split('/').pop();
        if (page && typeof Menu !== 'undefined') {
            Menu.setActive(page);
        }
    }

    /* =========================================================
     *  WEBSITE LINK
     * ========================================================= */

    function setupWebsiteLink() {
        // Public website module removed in this build; hide the link.
        $('#websiteLink').off('click.spa').closest('li').hide();
        $('#websiteLink').hide();
        websiteUrl = '';
    }

    function bindComingSoonLinks() {
        $('#menu a[data-coming-soon="true"], #menu a[data-route][href="#"], #menu a[data-route][href="javascript:;"]:not(.has-arrow)')
            .off('click.comingsoon')
            .on('click.comingsoon', function(e) {
                var $menu = $('#menu');
                var $clickedLink = $(this);
                var $clickedLi = $clickedLink.closest('li');

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                // Fully collapse any previously expanded branches
                collapseAllSidebarGroups($menu);

                // Highlight only the clicked no-page item
                $clickedLi.addClass('mm-active');

                showNotification('This page is under development and will be available soon.');
            });
    }

    function collapseAllSidebarGroups($menu) {
        $menu.find('li.mm-active').removeClass('mm-active');
        $menu.find('a.has-arrow').attr('aria-expanded', 'false');

        // Force-hide all submenu containers (including stale inline/block states)
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
    }

    /* =========================================================
     *  HEADER
     * ========================================================= */

    function renderHeader() {
        $.ajax({
            url: PATHS.INCLUDES_BASE + '/header.html',
            method: 'GET',
            dataType: 'html',
            success: function(html) {
                var userData  = JSON.parse(sessionStorage.getItem('user_data') || '{}');
                var locations = userData.locations || [];
                var userName = currentUser ? currentUser.name : 'User';
                var userRole = currentUser ? currentUser.role_name : '';
                var locationName = 'No location';
                var locOptions = '';

                if (locations.length > 0) {
                    locationName = locations[0].name;
                }

                for (var i = 0; i < locations.length; i++) {
                    if (locations[i].id == currentLocation) {
                        locationName = locations[i].name;
                        break;
                    }
                }

                for (var k = 0; k < locations.length; k++) {
                    var loc = locations[k];
                    locOptions += '<li><a class="dropdown-item location-item" href="javascript:;" data-location-id="' +
                        loc.id + '">' + loc.name +
                        (loc.id == currentLocation ? ' <i class="bx bx-check text-success ms-2"></i>' : '') +
                        '</a></li>';
                }

                $('#header-container').html(html);
                $('#userName').text(userName);
                $('#userRole').text(userRole);

                // Apply user avatar (base64) if available; fall back to bundled default
                var userAvatar = currentUser && currentUser.avatar ? currentUser.avatar : '';
                if (userAvatar) {
                    $('.user-box .user-img').attr('src', userAvatar);
                }
                $('#currentLocationName').text(locationName);
                $('#locationMenu').html(locOptions);

                // Sync dark-mode icon + html attrs with saved theme
                var savedTheme = '';
                try { savedTheme = localStorage.getItem('theme_style') || ''; } catch(e) {}
                if (savedTheme === 'dark-theme') {
                    $('html').addClass('dark-theme').attr('data-bs-theme', 'dark');
                    $('.dark-mode-icon i').attr('class', 'bx bx-sun');
                } else {
                    $('html').removeClass('dark-theme').attr('data-bs-theme', 'light');
                    $('.dark-mode-icon i').attr('class', 'bx bx-moon');
                }

                $(document).off('click.logout').on('click.logout', '#btnLogout, #logoutBtn', function(e) {
                    e.preventDefault();
                    logout();
                });
                $(document).off('click.location').on('click.location', '.location-item', function(e) {
                    e.preventDefault();
                    switchLocation($(this).data('location-id'));
                });
                $(document).off('click.mobiletoggle').on('click.mobiletoggle', '.mobile-toggle-menu', function() {
                    $('.wrapper').toggleClass('toggled');
                });

                if (window.NotificationsBell) NotificationsBell.start();
            },
            error: function(xhr) {
                console.error('[SPA] Failed to load header:', xhr.status);
            }
        });
    }

    /* =========================================================
     *  LOCATION SWITCH
     * ========================================================= */

    function switchLocation(locationId) {
        $.ajax({
            url: PATHS.API_BASE + '/auth/switch-location.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ location_id: locationId }),
            success: function(r) { if (r.success) window.location.reload(); else alert(r.message || 'Failed'); },
            error: function(xhr) { alert((xhr.responseJSON || {}).message || 'Failed to switch location'); }
        });
    }

    /* =========================================================
     *  FOOTER
     * ========================================================= */

    function renderFooter() {
        if ($('#footer-container').length) {
            $.ajax({
                url: PATHS.INCLUDES_BASE + '/footer.html',
                method: 'GET',
                dataType: 'html',
                success: function(html) {
                    $('#footer-container').html(html);
                    $('#footerHotelName').text(currentHotelName);
                },
                error: function(xhr) {
                    console.error('[SPA] Failed to load footer:', xhr.status);
                }
            });
            return;
        }

        if ($('.page-footer').length) {
            return;
        }

        $('.wrapper').append('<footer class="page-footer"><p class="mb-0">Copyright &copy; 2025. All right reserved.</p></footer>');
    }

    /* =========================================================
     *  LOGOUT
     * ========================================================= */

    function logout() {
        if (!confirm('Are you sure you want to logout?')) return;
        $.ajax({
            url: PATHS.API_BASE + '/auth/logout.php', method: 'POST',
            complete: function() { sessionStorage.clear(); window.location.href = PATHS.LOGIN_URL; }
        });
    }

    /* =========================================================
     *  PERMISSIONS
     * ========================================================= */

    function hasPermission(action) {
        var page = isSpaShell() ? currentPageName : window.location.pathname.split('/').pop();

        var findMenu = function(menus) {
            for (var i = 0; i < menus.length; i++) {
                if (menus[i].url === page) return menus[i].id;
                if (menus[i].children && menus[i].children.length) {
                    var f = findMenu(menus[i].children);
                    if (f) return f;
                }
            }
            return null;
        };

        var menuId = findMenu(userMenus);
        if (!menuId || !userPermissions[menuId]) return false;
        var p = userPermissions[menuId];
        switch (action) {
            case 'add':    return p.can_add;
            case 'edit':   return p.can_edit;
            case 'delete': return p.can_delete;
            case 'view':   return p.can_view;
            default:       return false;
        }
    }

    /* =========================================================
     *  PUBLIC API
     * ========================================================= */

    window.HotelApp = {
        checkSession:       checkSession,
        logout:             logout,
        hasPermission:      hasPermission,
        getCurrentUser:     function() { return currentUser; },
        getCurrentLocation: function() { return currentLocation; },
        navigate:           function(p) {
            if (isSpaShell() && typeof Router !== 'undefined') {
                Router.navigate(p);
            } else {
                window.location.href = p;
            }
        },
        showNotification:   showNotification,
        isSpa:              isSpaShell,
        getTenantId:        function() { return tenantId; },
        getTenantDatabase:  function() { return tenantDatabase; },
        refreshWebsiteLink: setupWebsiteLink,
        _setCurrentPage:    function(p) { currentPageName = p; }
    };

})(jQuery);

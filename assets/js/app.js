/* ============================================================
   Global Select2 enhancer — converts native .form-select into
   searchable dropdowns. Idempotent; skips .no-select2 + already-init.
   Re-runnable after AJAX populates options.
   ============================================================ */
(function (w) {
	'use strict';
	w.enhanceSelect2 = function (scope) {
		if (!w.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
		var $root = scope ? jQuery(scope) : jQuery(document);
		$root.find('.form-select:not(.no-select2), select.select2-enable').each(function () {
			var $el = jQuery(this);
			if ($el.data('select2')) return;          // already enhanced
			if ($el.attr('multiple') && $el.hasClass('no-select2')) return;
			try {
				$el.select2({
					width: '100%',
					dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : jQuery(document.body),
					minimumResultsForSearch: 8,
					placeholder: $el.data('placeholder') || undefined,
					allowClear: false
				});
			} catch (e) { /* ignore */ }
		});
	};

	// Enhance selects inside a modal when it opens (options usually loaded by then)
	jQuery(document).on('shown.bs.modal', '.modal', function () {
		w.enhanceSelect2(this);
	});
})(window);

$(function() {
	"use strict";

	var fallbackRoot = (function() {
		var path = window.location.pathname || '';
		var lower = path.toLowerCase();
		var markers = ['/login/', '/login', '/register/', '/register', '/admin/', '/admin', '/app/', '/app', '/index.html', '/app.html'];
		var cutIndex = -1;

		for (var i = 0; i < markers.length; i++) {
			var idx = lower.indexOf(markers[i]);
			if (idx !== -1 && (cutIndex === -1 || idx < cutIndex)) {
				cutIndex = idx;
			}
		}

		var root = (cutIndex >= 0 ? path.slice(0, cutIndex) : path.replace(/\/[^\/]*$/, '')).replace(/\/+$/, '');
		return root;
	})();

	var PATHS = window.APP_PATHS || {
		LOGIN_URL: (fallbackRoot ? fallbackRoot : '') + '/login',
		ADMIN_URL: (fallbackRoot ? fallbackRoot : '') + '/admin',
		API_BASE: (window.location.origin || '') ?
			(window.location.origin + (fallbackRoot ? fallbackRoot : '') + '/api') :
			((fallbackRoot ? fallbackRoot : '') + '/api')
	};

	try { new PerfectScrollbar(".app-container"); } catch(e) {}
	try { new PerfectScrollbar(".header-message-list"); } catch(e) {}
	try { new PerfectScrollbar(".header-notifications-list"); } catch(e) {}


	    $(".mobile-search-icon").on("click", function() {
			$(".search-bar").addClass("full-search-bar")
		}),

		$(".search-close").on("click", function() {
			$(".search-bar").removeClass("full-search-bar")
		}),

	// ========================
	// Theme Persistence Helper
	// ========================
	function saveTheme(key, val) {
		try { localStorage.setItem(key, val); } catch(e) {}
	}
	function getTheme(key) {
		try { return localStorage.getItem(key) || ''; } catch(e) { return ''; }
	}

	function applyFullTheme() {
		var themeStyle = getTheme('theme_style');
		var root = document.documentElement;
		var $html = $(root);
		// Dark-mode CSS vars (force-applied so they beat user's UI-settings inline overrides)
		var DARK_VARS = {
			'--ui-header-bg':     '#12181a',
			'--ui-header-border': 'rgba(237,237,237,0.12)',
			'--ui-header-text':   '#e4e5e6',
			'--ui-sidebar-bg':    '#0f1417',
			'--ui-sidebar-text':  '#c7c9cc',
			'--ui-sidebar-submenu-bg': '#12181a',
			'--bs-body-bg':       '#070d0e',
			'--bs-body-color':    '#e4e5e6'
		};
		var KEYS = Object.keys(DARK_VARS);
		if (themeStyle === 'dark-theme') {
			$html.addClass('dark-theme').attr('data-bs-theme', 'dark');
			KEYS.forEach(function (k) { root.style.setProperty(k, DARK_VARS[k], 'important'); });
			$('.dark-mode-icon i').attr('class', 'bx bx-sun');
		} else {
			$html.removeClass('dark-theme').attr('data-bs-theme', 'light');
			KEYS.forEach(function (k) { root.style.removeProperty(k); });
			// Re-apply user UI settings so light-mode customizations re-take effect
			if (typeof window.applyUISettings === 'function') window.applyUISettings();
			$('.dark-mode-icon i').attr('class', 'bx bx-moon');
		}
	}

	// Expose globally so other scripts (and event handlers) can call it without closure issues
	window.applyFullTheme = applyFullTheme;

	// Restore theme on page load
	applyFullTheme();

	// ========================
	// Dark Mode Toggle (header icon — delegated for dynamic header)
	// ========================
	$(document).on("click", ".dark-mode, .dark-mode-icon", function(e) {
		e.preventDefault();
		e.stopPropagation();
		var current = '';
		try { current = localStorage.getItem('theme_style') || ''; } catch (err) {}
		var next = current === 'dark-theme' ? 'light-theme' : 'dark-theme';
		try { localStorage.setItem('theme_style', next); } catch (err) {}
		if (typeof applyFullTheme === 'function') applyFullTheme();
		else if (typeof window.applyFullTheme === 'function') window.applyFullTheme();
	});

	// ========================
	// Sidebar Toggle
	// ========================
	$(document).on("click", ".toggle-icon", function() {
		$(".wrapper").hasClass("toggled") ? ($(".wrapper").removeClass("toggled"), $(".sidebar-wrapper").unbind("hover")) : ($(".wrapper").addClass("toggled"), $(".sidebar-wrapper").hover(function() {
			$(".wrapper").addClass("sidebar-hovered")
		}, function() {
			$(".wrapper").removeClass("sidebar-hovered")
		}));
	});

	// ========================
	// Back To Top
	// ========================
	$(document).ready(function() {
		$(window).on("scroll", function() {
			$(this).scrollTop() > 300 ? $(".back-to-top").fadeIn() : $(".back-to-top").fadeOut()
		}), $(".back-to-top").on("click", function() {
			return $("html, body").animate({
				scrollTop: 0
			}, 600), !1
		})
	}),

	$(function() {
		for (var e = window.location, o = $(".metismenu li a").filter(function() {
				return this.href == e
			}).addClass("").parent().addClass("mm-active"); o.is("li");) o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
	});


	$(function() {
		$("#menu").metisMenu({ toggle: true })
	});

	$(".chat-toggle-btn").on("click", function() {
		$(".chat-wrapper").toggleClass("chat-toggled")
	}), $(".chat-toggle-btn-mobile").on("click", function() {
		$(".chat-wrapper").removeClass("chat-toggled")
	}),

	$(".email-toggle-btn").on("click", function() {
		$(".email-wrapper").toggleClass("email-toggled")
	}), $(".email-toggle-btn-mobile").on("click", function() {
		$(".email-wrapper").removeClass("email-toggled")
	}), $(".compose-mail-btn").on("click", function() {
		$(".compose-mail-popup").show()
	}), $(".compose-mail-close").on("click", function() {
		$(".compose-mail-popup").hide()
	});

	function apiBaseUrl() {
		return PATHS.API_BASE;
	}

	function ownerToken() {
		return localStorage.getItem('owner_token') || '';
	}

	function setOwnerToken(token) {
		localStorage.setItem('owner_token', token);
	}

	function isAuthPage() {
		var p = (window.location.pathname || '').toLowerCase();
		return p.indexOf('auth-') !== -1 ||
			p.indexOf('index.html') !== -1 ||
			p.endsWith('/index') ||
			p.endsWith('/index/') ||
			p.endsWith('/login') ||
			p.endsWith('/login/') ||
			p.indexOf('register.html') !== -1 ||
			p.endsWith('/register') ||
			p.endsWith('/register/');
	}

	function requireAuth() {
		if (isAuthPage()) {
			return;
		}
		if (!ownerToken()) {
			window.location.href = PATHS.LOGIN_URL;
		}
	}

	function bindLogout() {
		$(document).on('click', 'i.bx-log-out-circle', function (e) {
			e.preventDefault();
			localStorage.removeItem('owner_token');
			window.location.href = PATHS.LOGIN_URL;
		});
	}

	function attachAjaxAuthHeader() {
		$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
			var t = ownerToken();
			if (t) {
				jqXHR.setRequestHeader('Authorization', 'Bearer ' + t);
			}
		});
	}

	function bindOwnerLogin() {
		var $form = $('form').first();
		if (!$form.length) {
			return;
		}
		$form.on('submit', function (e) {
			e.preventDefault();
			var mobile = ($('#inputEmailAddress').val() || '').toString().trim();
			var password = ($('#inputChoosePassword').val() || '').toString();
			if (!mobile || !password) {
				alert('Please enter mobile and password');
				return;
			}
			$.ajax({
				type: 'POST',
				url: apiBaseUrl() + '/auth/login',
				contentType: 'application/json',
				data: JSON.stringify({ mobile: mobile, password: password }),
				success: function (resp) {
					if (resp && resp.success === true && resp.data && resp.data.token) {
						setOwnerToken(resp.data.token);
						window.location.href = PATHS.ADMIN_URL;
						return;
					}
					alert((resp && resp.message) ? resp.message : 'Login failed');
				},
				error: function () {
					alert('Login failed');
				}
			});
		});
	}

	function bindOwnerRegister() {
		var $form = $('form').first();
		if (!$form.length) {
			return;
		}
		$form.on('submit', function (e) {
			e.preventDefault();
			var name = ($('#inputUsername').val() || '').toString().trim();
			var mobile = ($('#inputEmailAddress').val() || '').toString().trim();
			var password = ($('#inputChoosePassword').val() || '').toString();
			if (!name || !mobile || !password) {
				alert('Please fill all required fields');
				return;
			}
			$.ajax({
				type: 'POST',
				url: apiBaseUrl() + '/auth/register',
				contentType: 'application/json',
				data: JSON.stringify({ name: name, mobile: mobile, password: password }),
				success: function (resp) {
					if (resp && resp.success === true && resp.data && resp.data.token) {
						setOwnerToken(resp.data.token);
						window.location.href = PATHS.ADMIN_URL;
						return;
					}
					//alert((resp && resp.message) ? resp.message : 'Registration failed');
				},
				error: function () {
					//alert('Registration failed');
				}
			});
		});
	}

	function wireDashboardCards() {
		var p = (window.location.pathname || '').toLowerCase();
		if (p.indexOf('/admin/index.html') === -1 && (p.lastIndexOf('index.html') !== (p.length - 'index.html'.length))) {
			return;
		}
		if (!ownerToken()) {
			return;
		}
		$.ajax({
			type: 'GET',
			url: apiBaseUrl() + '/dashboard/stats.php',
			success: function (resp) {
				if (!resp || resp.success !== true || !resp.data) {
					return;
				}
				var d = resp.data;
				var $cards = $('.row.row-cols-1.row-cols-md-2.row-cols-xl-4 .card');
				if ($cards.length >= 4) {
					$($cards.get(0)).find('p.mb-0.text-secondary').first().text('Total Users');
					$($cards.get(0)).find('h4').first().text(d.total_users);
					$($cards.get(1)).find('p.mb-0.text-secondary').first().text('Total Employees');
					$($cards.get(1)).find('h4').first().text(d.total_employees);
					$($cards.get(2)).find('p.mb-0.text-secondary').first().text('Branches');
					$($cards.get(2)).find('h4').first().text(d.total_branches);
					$($cards.get(3)).find('p.mb-0.text-secondary').first().text('Active Roles');
					$($cards.get(3)).find('h4').first().text(d.total_roles);
				}
			}
		});
	}

	// requireAuth(); // Auth check disabled
	bindLogout();
	attachAjaxAuthHeader();

	var page = (window.location.pathname || '').toLowerCase();
	if (page.indexOf('index.html') !== -1 || page.endsWith('/index') || page.endsWith('/index/') || page.endsWith('/login') || page.endsWith('/login/') || page.indexOf('auth-cover-signin.html') !== -1 || page.indexOf('auth-basic-signin.html') !== -1 || page.indexOf('auth-header-footer-signin.html') !== -1) {
		bindOwnerLogin();
	}
	if (page.indexOf('register.html') !== -1 || page.endsWith('/register') || page.endsWith('/register/') || page.indexOf('auth-cover-signup.html') !== -1 || page.indexOf('auth-basic-signup.html') !== -1 || page.indexOf('auth-header-footer-signup.html') !== -1) {
		bindOwnerRegister();
	}
	if (!isAuthPage()) {
		wireDashboardCards();
	}


});

(function ($) {
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

	function getApiBaseUrl() {
		return PATHS.API_BASE;
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

	function getToken() {
		return localStorage.getItem('owner_token') || '';
	}

	function setToken(token) {
		localStorage.setItem('owner_token', token);
	}

	function requireAuth() {
		if (isAuthPage()) {
			return;
		}
		if (!getToken()) {
			window.location.href = PATHS.LOGIN_URL;
		}
	}

	function attachAjaxAuthHeader() {
		$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
			var token = getToken();
			if (token) {
				jqXHR.setRequestHeader('Authorization', 'Bearer ' + token);
			}
		});
	}

	function bindLogin() {
		var $form = $('#loginForm');
		if (!$form.length) {
			return;
		}

		$form.on('submit', function (e) {
			e.preventDefault();
			var mobile = ($('#inputMobileLogin').val() || '').toString().trim();
			var password = ($('#inputChoosePassword').val() || '').toString();
			if (!mobile || !password) {
				alert('Please enter mobile and password');
				return;
			}

			$.ajax({
				type: 'POST',
				url: getApiBaseUrl() + '/auth/login',
				contentType: 'application/json',
				data: JSON.stringify({ mobile: mobile, password: password }),
				success: function (resp) {
					if (resp && resp.success === true && resp.data && resp.data.token) {
						setToken(resp.data.token);
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

	function bindRegister() {
		var $form = $('#signupForm');
		if (!$form.length) {
			return;
		}

		$form.on('submit', function (e) {
			e.preventDefault();
			var name = ($('#inputName').val() || '').toString().trim();
			var mobile = ($('#inputMobile').val() || '').toString().trim();
			var password = ($('#inputChoosePassword').val() || '').toString();
			var confirmPassword = ($('#inputConfirmPassword').val() || '').toString();

			if (!name || !mobile || !password) {
				alert('Please fill all required fields');
				return;
			}
			if (password !== confirmPassword) {
				alert('Passwords do not match');
				return;
			}

			$.ajax({
				type: 'POST',
				url: getApiBaseUrl() + '/auth/register',
				contentType: 'application/json',
				data: JSON.stringify({ name: name, mobile: mobile, password: password }),
				success: function (resp) {
					if (resp && resp.success === true && resp.data && resp.data.token) {
						setToken(resp.data.token);
						window.location.href = PATHS.ADMIN_URL;
						return;
					}
					alert((resp && resp.message) ? resp.message : 'Registration failed');
				},
				error: function () {
					//alert('Registration failed');
				}
			});
		});
	}

	$(function () {
		// requireAuth(); // Auth check disabled
		attachAjaxAuthHeader();

		var p = (window.location.pathname || '').toLowerCase();
		if (p.indexOf('index.html') !== -1 || p.endsWith('/index') || p.endsWith('/index/') || p.endsWith('/login') || p.endsWith('/login/') || p.indexOf('auth-cover-signin.html') !== -1 || p.indexOf('auth-basic-signin.html') !== -1 || p.indexOf('auth-header-footer-signin.html') !== -1) {
			bindLogin();
		}
		if (p.indexOf('register.html') !== -1 || p.endsWith('/register') || p.endsWith('/register/') || p.indexOf('auth-cover-signup.html') !== -1 || p.indexOf('auth-basic-signup.html') !== -1 || p.indexOf('auth-header-footer-signup.html') !== -1) {
			bindRegister();
		}
	});
})(jQuery);

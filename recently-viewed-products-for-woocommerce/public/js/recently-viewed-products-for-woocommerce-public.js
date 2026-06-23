/**
 * Public JS for Recently Viewed Products (cache-friendly AJAX mode).
 *
 * Two responsibilities, both safe on fully-cached pages:
 *   1. Track the current product view by writing the cookie client-side
 *      (only for guests — logged-in users bypass full-page caches and are
 *      tracked authoritatively on the server).
 *   2. Fetch the visitor's recently viewed products via REST and inject them
 *      into each cacheable placeholder.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */
(function () {
	'use strict';

	var data = window.rvpwData || {};

	/**
	 * Read a cookie value by name.
	 *
	 * @param {string} name Cookie name.
	 * @return {string} Cookie value or empty string.
	 */
	function readCookie(name) {
		var prefix = name + '=';
		var parts = document.cookie ? document.cookie.split(';') : [];
		for (var i = 0; i < parts.length; i++) {
			var c = parts[i].replace(/^\s+/, '');
			if (c.indexOf(prefix) === 0) {
				return decodeURIComponent(c.substring(prefix.length));
			}
		}
		return '';
	}

	/**
	 * Write a cookie.
	 *
	 * @param {string} name  Cookie name.
	 * @param {string} value Value.
	 * @param {number} days  Days until expiry.
	 */
	function writeCookie(name, value, days) {
		var expires = '';
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
			expires = '; expires=' + date.toUTCString();
		}
		var secure = ('https:' === window.location.protocol) ? '; secure' : '';
		document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; samesite=lax' + secure;
	}

	/**
	 * Record the current product view in the cookie (guests only).
	 */
	function trackView() {
		var productId = parseInt(data.productId, 10);
		if (!productId || !data.enabled || !data.trackGuests) {
			return;
		}
		// Logged-in users bypass page caches and are tracked server-side; skip
		// client-side tracking for them to honour the logged-in tracking setting.
		if (document.body && document.body.classList.contains('logged-in')) {
			return;
		}

		var max = parseInt(data.maxStored, 10) || 20;
		var raw = readCookie(data.cookieName);
		var ids = raw ? raw.split('|') : [];

		ids = ids
			.map(function (id) { return parseInt(id, 10); })
			.filter(function (id) { return id && id !== productId; });
		ids.unshift(productId);
		ids = ids.slice(0, max);

		writeCookie(data.cookieName, ids.join('|'), parseInt(data.expiryDays, 10) || 30);
	}

	/**
	 * Fill one placeholder with the visitor's recently viewed products.
	 *
	 * @param {HTMLElement} node Placeholder element.
	 */
	function injectPlaceholder(node) {
		var context = node.getAttribute('data-rvpw-context') || '{}';
		var url = data.restUrl + (data.restUrl.indexOf('?') === -1 ? '?' : '&') + 'context=' + encodeURIComponent(context);

		fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
			.then(function (response) { return response.ok ? response.json() : null; })
			.then(function (payload) {
				node.innerHTML = (payload && typeof payload.html === 'string') ? payload.html : '';
				node.removeAttribute('aria-busy');
			})
			.catch(function () {
				node.removeAttribute('aria-busy');
			});
	}

	/**
	 * Inject all placeholders on the page.
	 */
	function injectAll() {
		if (!data.restUrl) {
			return;
		}
		var nodes = document.querySelectorAll('.rvpw-ajax');
		Array.prototype.forEach.call(nodes, injectPlaceholder);
	}

	function boot() {
		trackView();
		injectAll();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

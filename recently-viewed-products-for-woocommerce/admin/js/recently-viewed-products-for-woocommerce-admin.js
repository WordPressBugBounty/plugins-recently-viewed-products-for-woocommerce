/**
 * Admin behaviour: tabbed navigation, shortcode copy + live preview.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */
(function () {
	'use strict';

	var i18n = window.rvpwAdmin || {};

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('.rvpw-admin');

		if (!root) {
			return;
		}

		// Signal to CSS that JS is active so inactive panels can be hidden.
		root.classList.add('rvpw-has-js');

		initTabs(root);
		initCopyButton();
		initShortcodePreview();
	});

	/**
	 * Tabbed navigation following the WAI-ARIA tabs pattern.
	 *
	 * @param {HTMLElement} root Plugin admin wrapper.
	 */
	function initTabs(root) {
		var tabs = Array.prototype.slice.call(root.querySelectorAll('.rvpw-tab'));
		var panels = root.querySelectorAll('.rvpw-panel');
		var activeField = document.getElementById('rvpw-active-tab');

		if (!tabs.length) {
			return;
		}

		function activate(tab, setFocus) {
			var target = tab.getAttribute('data-tab');

			tabs.forEach(function (item) {
				var selected = item === tab;
				item.classList.toggle('is-active', selected);
				item.setAttribute('aria-selected', selected ? 'true' : 'false');
			});

			Array.prototype.forEach.call(panels, function (panel) {
				panel.classList.toggle('is-active', panel.id === 'rvpw-panel-' + target);
			});

			if (activeField) {
				activeField.value = target;
			}

			if (setFocus) {
				tab.focus();
			}

			// Keep the URL in sync without reloading so refresh/bookmark restores the tab.
			if (window.history && window.history.replaceState) {
				try {
					var url = new URL(window.location.href);
					url.searchParams.set('tab', target);
					url.searchParams.delete('updated');
					window.history.replaceState({}, '', url.toString());
				} catch (e) {
					/* No-op: older browsers without URL support. */
				}
			}
		}

		tabs.forEach(function (tab, index) {
			tab.addEventListener('click', function () {
				activate(tab, false);
			});

			tab.addEventListener('keydown', function (event) {
				var newIndex = null;

				switch (event.key) {
					case 'ArrowRight':
					case 'ArrowDown':
						newIndex = (index + 1) % tabs.length;
						break;
					case 'ArrowLeft':
					case 'ArrowUp':
						newIndex = (index - 1 + tabs.length) % tabs.length;
						break;
					case 'Home':
						newIndex = 0;
						break;
					case 'End':
						newIndex = tabs.length - 1;
						break;
					default:
						return;
				}

				event.preventDefault();
				activate(tabs[newIndex], true);
			});
		});
	}

	/**
	 * Copy the generated shortcode using the async Clipboard API with a fallback.
	 */
	function initCopyButton() {
		var copyButton = document.getElementById('rvpw-copy-shortcode');
		var shortcodeInput = document.getElementById('rvpw-generated-shortcode');

		if (!copyButton || !shortcodeInput) {
			return;
		}

		var label = copyButton.querySelector('.rvpw-copy-label');
		var resetTimer = null;

		function flash(message) {
			if (label) {
				var original = i18n.copy || label.textContent;
				label.textContent = message;
				window.clearTimeout(resetTimer);
				resetTimer = window.setTimeout(function () {
					label.textContent = original;
				}, 1500);
			}
		}

		copyButton.addEventListener('click', function () {
			var value = shortcodeInput.value;

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(value).then(
					function () {
						flash(i18n.copied || 'Copied!');
					},
					function () {
						legacyCopy(shortcodeInput);
						flash(i18n.copyFailed || 'Copy failed');
					}
				);
			} else {
				legacyCopy(shortcodeInput);
				flash(i18n.copied || 'Copied!');
			}
		});
	}

	/**
	 * Fallback copy for browsers without the Clipboard API.
	 *
	 * @param {HTMLInputElement} input Text input to copy.
	 */
	function legacyCopy(input) {
		input.removeAttribute('readonly');
		input.select();
		input.setSelectionRange(0, 99999);

		try {
			document.execCommand('copy');
		} catch (e) {
			/* No-op. */
		}

		input.setAttribute('readonly', 'readonly');
	}

	/**
	 * Live-update the shortcode example as related fields change.
	 */
	function initShortcodePreview() {
		var output = document.getElementById('rvpw-generated-shortcode');

		if (!output) {
			return;
		}

		var fields = {
			title: document.getElementById('rvpw-section_title'),
			limit: document.getElementById('rvpw-display_limit'),
			columns: document.getElementById('rvpw-desktop_columns'),
			layout: document.getElementById('rvpw-layout'),
			price: document.getElementById('rvpw-show_price'),
			cart: document.getElementById('rvpw-show_add_to_cart')
		};

		function escapeAttr(value) {
			return String(value).replace(/"/g, '&quot;');
		}

		function rebuild() {
			var title = fields.title ? escapeAttr(fields.title.value) : '';
			var limit = fields.limit ? parseInt(fields.limit.value, 10) || 1 : 4;
			var columns = fields.columns ? parseInt(fields.columns.value, 10) || 1 : 4;
			var layout = fields.layout ? fields.layout.value : 'grid';
			var price = fields.price && fields.price.checked ? 'yes' : 'no';
			var cart = fields.cart && fields.cart.checked ? 'yes' : 'no';

			output.value =
				'[rvpw_products title="' + title + '" limit="' + limit + '" columns="' + columns +
				'" layout="' + layout + '" show_price="' + price + '" show_cart="' + cart + '"]';
		}

		Object.keys(fields).forEach(function (key) {
			var field = fields[key];
			if (field) {
				field.addEventListener('input', rebuild);
				field.addEventListener('change', rebuild);
			}
		});
	}
})();

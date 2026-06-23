=== Recently Viewed Product for WooCommerce ===

Contributors: maheshpatel
Tags: woocommerce, recently viewed product, ecommerce
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 6.0
WC tested up to: 10.8.1
Stable tag: 2.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Show customers the products they recently viewed — lightweight,
cookie-based, no database tracking.

== Description ==

Recently Viewed Product for WooCommerce displays a personalized list
of products each visitor has viewed, helping them find their way back
to items they're interested in.

**How it works**

Product views are tracked in a small first-party browser cookie —
no database writes, no external services. The list updates
automatically as the visitor browses.

**Display options**

* Automatic placement on the single product page (position
  configurable).
* Anywhere on your site via the `[rvpw_products]` shortcode —
  pages, posts, widgets, or the block editor's Shortcode block.

**Key features**

* Grid and List layouts with per-device column control.
* Show/hide image, title, price, rating, and add-to-cart per item.
* Configurable cookie lifetime (1–365 days).
* Toggle guest and logged-in tracking independently.
* Shortcode generator with live preview and one-click copy.
* Optional: delete all plugin data on uninstall.
* HPOS compatible.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu.
3. Configure at WooCommerce > Recently Viewed Products.

== Shortcode ==

`[rvpw_products]`

Place this shortcode on any page, post, or widget area to display
the recently viewed list in that location.

== Privacy & Cookies ==

This plugin stores only WooCommerce product IDs in a single
first-party browser cookie (`rvpw_recently_viewed_{site_id}`).
No personal data (names, emails, IP addresses) is stored or
transmitted. Nothing is written to the database for tracking.

The cookie expires according to the "Cookie expiry (days)" setting
(default: 30 days). Tracking can be disabled for guests and/or
logged-in users from the General tab.

== Frequently Asked Questions ==

= Where are views recorded? =

Only on single product pages, subject to your guest / logged-in
tracking settings.

= Will a product show up in its own "recently viewed" list? =

No. The current product is always excluded from the list.

= Does it work with caching plugins? =

Display works normally. A product viewed on a fully cached page
may not be recorded until the cache is bypassed — this is inherent
to server-side cookie tracking.

= Does it store personal data? =

No. Only product IDs, in the visitor's own browser. Nothing is
sent off-site.

== Changelog ==

= 2.2.0 =
* Added: List layout alongside Grid.
* Added: multiple custom CSS classes support.
* Fixed: saving one settings tab no longer resets others.
* Fixed: responsive column settings respected for auto-placement.
* Fixed: product count resolved before display limit is applied.
* Fixed: empty-state message no longer shows on product pages
  with no history.
* Improved: redesigned accessible (WCAG) admin UI — tabbed
  settings, toggle switches, help text, sticky save bar.
* Improved: shortcode copy uses modern Clipboard API with
  live preview.
* Verified: zero errors against WordPress Plugin Check PHPCS
  rulesets.

= 2.1.0 =
* Added modern settings architecture, admin tabs, and shortcode
  generator UI.
* Improved tracking and rendering logic.
* Added uninstall cleanup option.

= 2.0.0 =
* Initial release.
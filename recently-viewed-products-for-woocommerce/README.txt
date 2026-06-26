=== Recently Viewed Products for WooCommerce - Carousel, Widget, Block & Email ===

Contributors: maheshpatel
Tags: recently viewed products, woocommerce recently viewed, product carousel, most viewed products, recently viewed
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 6.0
WC tested up to: 10.9
Stable tag: 2.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce recently viewed products in a grid, list or carousel via shortcode, widget, block, Elementor, plus most viewed and follow-up emails.

== Description ==

**Recently Viewed Products for WooCommerce** shows each customer the WooCommerce products they recently viewed — and turns that browsing interest into more sales. Display recently viewed products in a responsive grid, list, or product carousel automatically on single product pages, and anywhere else via the shortcode, sidebar widget, Gutenberg block, or Elementor widget.

If you want a fast, lightweight, cache-friendly recently viewed products plugin for WooCommerce that also surfaces your **most viewed products** store-wide and sends automated **follow-up emails**, this is it. It works as a lightweight product recommendations / cross-sell helper that is HPOS-compatible, translation-ready, RTL-friendly, multisite-aware, and passes the WordPress Plugin Check rulesets.

### 🚀 Why choose this recently viewed products plugin?

* **🖼️ Three layouts** — recently viewed products grid, list, or carousel slider.
* **⚡ No jQuery** — the carousel is dependency-free, lightweight, and fast.
* **🧭 Five display surfaces** — shortcode, automatic single-product placement, sidebar widget, Gutenberg block, and Elementor widget.
* **👥 Guest + logged-in tracking** — cookie tracking for guests, optional cross-device database history for signed-in customers.
* **🔥 Most viewed products** — surface your store's most popular products automatically.
* **✉️ Follow-up emails** — win back inactive customers with the products they viewed and a single-use coupon.
* **🧩 Cache friendly** — works with WP Rocket, W3 Total Cache, LiteSpeed Cache, and CDNs.
* **🛒 HPOS compatible** — fully tested with WooCommerce High-Performance Order Storage.

### 📍 Display recently viewed products anywhere

* **Single product pages** — automatic placement with a position you choose: before the product summary, after the product summary, or after the product tabs.
* **Shortcode** — drop `[rvpw_products]` into any page, post, or widget area to show WooCommerce recently viewed products on the shop, cart, checkout, or any other location.
* **Sidebar widget** — a recently viewed products widget that works in classic and block-based widget areas.
* **Gutenberg block** — a recently viewed products block with a live, server-side-rendered preview right in the editor.
* **Elementor widget** — a native Elementor recently viewed products widget that loads only when Elementor is active.

### 🎠 Carousel, grid & list layouts

* **Product carousel** — autoplay, configurable autoplay speed, navigation arrows, pagination dots, infinite loop, and responsive items-per-view, with no jQuery.
* **Grid layout** — clean, responsive recently viewed products grid.
* **List layout** — compact vertical list.
* **Compact variant** — add the `rvpw-compact` CSS class for a tighter style ideal for narrow sidebars.
* **Per-device columns** — mobile (1–2), tablet (1–4), and desktop (1–6).
* **Toggle card elements** — show or hide product image, title, price, star rating, and add-to-cart button.
* **Equal-height cards** — a modern, responsive card design that adapts to any theme or page builder.

### 🧠 Smart tracking engine

* **Cookie tracking for guests** — cache-friendly, stores only product IDs in a first-party cookie (`rvpw_recently_viewed_{site_id}`).
* **Cross-device history** — optional database history so recently viewed products follow signed-in customers across devices, merged from the guest cookie at login and pruned daily by a configurable retention period.
* **History sources** — automatic, browser cookie only, saved account history, or most viewed (store-wide popularity, cached top-N query).
* **Flexible sorting** — recently viewed, most viewed, price low-to-high, price high-to-low, newest products, or random.
* **Configurable limits** — cookie expiry from 1–365 days (default 30) and 1–100 max stored products.
* **Smart filters** — show in-stock only, exclude already-purchased products (signed-in customers), hide free products, and exclude specific categories.

### 📈 Marketing, analytics & follow-up emails

* **Analytics screen** — see your most viewed products and an orders proxy (from the WooCommerce Analytics order-product lookup table when available) with a 7/30/90-day attribution window.
* **Follow-up emails** — automatically email signed-in inactive customers the products they viewed, with an optional auto-generated single-use coupon (percentage or fixed cart discount), configurable validity, and a one-click unsubscribe link. Uses Action Scheduler when available, with a WP-Cron fallback, plus a Send-test-email button.
* **Dedicated page** — a "Recently Viewed" page is created automatically on activation.
* **Custom styling** — a Custom CSS box and a custom CSS class field to fine-tune styling without editing theme files.

### 🛠️ Built right

* WooCommerce dependency check and full HPOS compatibility.
* Conditional asset loading — CSS and JS load only on pages where the section renders.
* Cache-friendly and CDN-friendly (WP Rocket, W3 Total Cache, LiteSpeed Cache).
* Custom tables created via dbDelta with a versioned migration; no order data is touched.
* Translation-ready (text domain `recently-viewed-products-for-woocommerce`, `/languages`) and RTL-compatible CSS using logical properties.
* Multisite-aware cookie (scoped by site ID) and an accessible (WCAG), tabbed admin UI.
* Passes the WordPress Plugin Check (PHPCS) rulesets.

### 🔧 Shortcode

`[rvpw_products]`

**Shortcode attributes:** `title`, `limit`, `columns`, `layout` (grid|list|carousel), `sort` (recent|most_viewed|price_asc|price_desc|date|random), `source` (auto|cookie|db|most_viewed), `show_image`, `show_title`, `show_price`, `show_rating`, `show_cart`, `hide_current`, `class`.

**Example — recently viewed products carousel:**
`[rvpw_products layout="carousel" limit="8" columns="4" sort="recent"]`

**Example — most viewed products grid:**
`[rvpw_products layout="grid" source="most_viewed" limit="6"]`

== Installation ==

### Automatic installation

1. Go to **Plugins > Add New** in your WordPress dashboard.
2. Search for **Recently Viewed Products for WooCommerce**.
3. Click **Install Now**, then **Activate**.
4. Go to **Recently Viewed > Settings** to configure layout, placement, tracking engine, filters, and emails.
5. Open **Recently Viewed > Analytics** to view your most viewed products and the orders proxy.

### Manual installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Recently Viewed > Settings** to configure, and **Recently Viewed > Analytics** for reporting.

### Requirements

* WordPress 6.0 or higher.
* WooCommerce 6.0 or higher.
* PHP 7.4 or higher.

== Frequently Asked Questions ==

= How do I show recently viewed products in WooCommerce? =

Use the `[rvpw_products]` shortcode on any page or post, enable automatic placement on single product pages (choose before summary, after summary, or after tabs), or add the sidebar widget, Gutenberg block, or Elementor widget. Several can be combined at once.

= How do I display recently viewed products on the shop or homepage? =

Drop the `[rvpw_products]` shortcode into any page, post, or block — including the shop page, the homepage, cart, or checkout. Pick a grid, list, or carousel layout and the number of columns, and the section will render WooCommerce recently viewed products wherever you place it.

= How do I show recently viewed products in a sidebar or footer? =

Add the bundled sidebar widget to any widget area, including classic sidebars, footers, and block-based widget areas. Alternatively, place the `[rvpw_products]` shortcode in a footer or sidebar block. For narrow columns, add the `rvpw-compact` CSS class and use a list or carousel layout with one or two columns on mobile.

= How do I add a recently viewed products carousel? =

Set the layout to carousel — either in the settings for automatic placement, or with the shortcode: `[rvpw_products layout="carousel"]`. The carousel supports autoplay, configurable autoplay speed, arrows, pagination dots, infinite loop, and responsive items-per-view, with no jQuery dependency.

= What is the difference between recently viewed and most viewed products? =

Recently viewed products are personal to each visitor — the items that specific shopper browsed, tracked via cookie or saved account history. Most viewed products are store-wide: the items most popular across all customers, drawn from a cached top-N popularity query. Use recently viewed for personalised recommendations and most viewed as a store-wide cross-sell.

= Can I show most viewed (most popular) products instead? =

Yes. Set the source to most viewed to display your store's most popular products store-wide: `[rvpw_products source="most_viewed"]`. You can also sort any list by most viewed.

= My recently viewed products are not showing — how do I fix it? =

First, make sure WooCommerce is active and that you have viewed at least one product (the current product is excluded, and the cookie is written after the page loads, so a product only appears on later page views). If you placed the shortcode, confirm the layout and column settings, and check whether a smart filter (in-stock only, hide free, exclude categories, exclude purchased) is removing the products. On heavily cached pages, a view may not be recorded until the cache is bypassed.

= Where are recently viewed products displayed and tracked? =

They can appear on several surfaces at once: automatically on the single product page (in a position you choose), plus the `[rvpw_products]` shortcode, the sidebar widget, the Gutenberg block, and the Elementor widget. For tracking, the first-party cookie stores only product IDs; you can optionally enable logged-in tracking and cross-device database history for signed-in customers.

= Is this recently viewed products plugin free? =

Yes. Recently Viewed Products for WooCommerce is a free plugin you can download and use on your store. It includes all display surfaces (shortcode, automatic placement, sidebar widget, Gutenberg block, and Elementor widget), grid, list, and carousel layouts, most viewed products, smart filters, analytics, and follow-up emails.

= Will it slow down my site or hurt performance? =

No. The plugin is built to be fast and lightweight: the carousel is dependency-free with no jQuery, and CSS and JavaScript load only on pages where the section actually renders (conditional asset loading). It is also cache-friendly, working with WP Rocket, W3 Total Cache, LiteSpeed Cache, and CDNs.

= Does it work with caching plugins? =

Yes. Display works normally with WP Rocket, W3 Total Cache, LiteSpeed Cache, and CDNs. Because views are recorded server-side, a product viewed on a fully cached page may not be recorded until the cache is bypassed — this is inherent to cookie-based tracking.

= Does it work with any WordPress theme? =

Yes. The product cards use a modern, responsive, equal-height design that adapts to any theme, and the CSS uses logical properties so it is RTL-compatible. You can set per-device columns for mobile, tablet, and desktop, and add the `rvpw-compact` class for a tighter variant in narrow areas. A Custom CSS box and custom CSS class field let you match your theme exactly without editing theme files.

= Does it work with page builders like Elementor, Divi, Beaver Builder, Bricks, and Oxygen? =

Yes. A native Elementor widget is included and loads only when Elementor is active. For Divi, Beaver Builder, Bricks, Oxygen, and any other builder, drop the `[rvpw_products]` shortcode into a shortcode/text element, or place the sidebar widget in any widget area the builder exposes. The responsive card design adapts to whatever container the builder renders it in.

= Is it compatible with Gutenberg and block themes (FSE)? =

Yes. The plugin ships a dedicated Gutenberg block with a live, server-side-rendered preview right in the editor, so you see real products as you build. The sidebar widget also works in block-based widget areas, so it fits classic and modern block themes alike.

= Is it compatible with WooCommerce HPOS? =

Yes. The plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS). Custom tables are created via dbDelta with a versioned migration, and no order data is touched.

= Does it support all product types, including variable products? =

Yes. It displays your standard WooCommerce product cards (image, title, price, star rating, and add-to-cart button, each toggleable) for the products a customer has viewed, regardless of product type, including variable products. Smart filters let you show in-stock products only, hide free products, exclude already-purchased products for signed-in customers, and exclude specific categories.

= Is the plugin translation-ready and RTL-compatible? =

Yes. The plugin is fully translation-ready with the text domain `recently-viewed-products-for-woocommerce` and a bundled `/languages` folder, so you can translate every string. All front-end CSS uses logical properties, making the layouts RTL-compatible out of the box for right-to-left languages.

= Does it work on WordPress Multisite? =

Yes. Tracking is multisite-aware: the first-party cookie is scoped per site using the site ID (`rvpw_recently_viewed_{site_id}`), so each site in a network keeps its own separate recently viewed history.

= Is it GDPR-friendly and what personal data does it store? =

By default it stores only non-personal WooCommerce product IDs in a single first-party cookie, transmits nothing to third parties, and writes nothing to the database for guest tracking, so the core feature is generally outside the scope of GDPR personal-data processing. If you turn on optional database history or follow-up emails, the plugin additionally stores product IDs against existing account IDs only — no new personal data, with daily pruning by a configurable retention period and a one-click unsubscribe link. If you enable those options, review your privacy policy accordingly. See the Privacy & Cookies section below.

= Will a product appear in its own recently viewed list? =

No. The current product is excluded ("Hide current product" is on by default), and the cookie is written after the page is requested, so a product only appears on subsequent page views.

= Can recently viewed products follow customers across devices? =

Yes. Enable database history in the plugin settings so a signed-in customer's recently viewed products follow them across devices. The guest cookie history is merged in at login.

= How do I send follow-up emails to inactive customers? =

Enable follow-up emails in settings. The plugin emails signed-in inactive customers the products they viewed, with an optional auto-generated single-use coupon and a one-click unsubscribe link. It uses Action Scheduler when available, with a WP-Cron fallback, and includes a Send-test-email button.

= How do I customize the styling, colors, and columns? =

Go to **Recently Viewed > Settings** to toggle card elements (image, title, price, rating, add-to-cart) and set per-device columns for mobile (1–2), tablet (1–4), and desktop (1–6). Use the built-in Custom CSS box and the custom CSS class field to change colors and spacing without editing theme files, and add the `rvpw-compact` class for a denser layout in narrow sidebars.

= Where do I configure the plugin and how do I get support? =

After activating, go to the dedicated top-level **Recently Viewed** menu and open **Settings** to configure layout, placement, tracking engine, filters, and emails; the **Analytics** submenu shows your most viewed products and an orders proxy. For help, use the plugin's support forum on WordPress.org, where you can ask questions and report issues.

== Privacy & Cookies ==

This plugin remembers the products a visitor has viewed by storing their IDs in a single first-party cookie in the visitor's own browser.

* Cookie name: `rvpw_recently_viewed_{site_id}` (the site ID keeps multisite installs separate).
* Cookie contents: a list of WooCommerce product IDs only (for example `42|17|8`). No names, emails, IP addresses, or other personal data are stored.
* Lifetime: a configurable cookie expiry in the plugin settings (1–365 days; 30 by default).
* Scope: the cookie is read and written on your own site only. The plugin does **not** transmit any data to external services, does **not** include third-party trackers, and does **not** write to the database for guest tracking.
* Control: you can disable guest tracking and/or logged-in tracking in the plugin settings. Turning the plugin off stops all tracking.

Optional database storage (off by default): if you enable logged-in history storage in the plugin settings, the plugin stores rows of (user ID, product ID, timestamp) for signed-in customers so their history follows them across devices. This still contains only product IDs and the customer's existing account ID — no new personal data. Rows are pruned automatically after the configured retention period. A store-wide view counter records per-product totals (no user identifiers).

Optional follow-up emails (off by default): if enabled, the plugin emails signed-in inactive customers about products they viewed, with an optional single-use coupon, and every email includes a one-click unsubscribe link.

Because the core feature stores only non-personal product IDs, it is generally outside the scope of GDPR personal-data processing. If you enable database history or follow-up emails, review your privacy policy accordingly. If your site shows a cookie consent banner, you may also wish to list this functional cookie for completeness.

When the plugin is deleted, enable the uninstall cleanup option in the plugin settings to also drop the plugin's custom tables and options. Visitor cookies expire on their own according to the configured lifetime.

== Screenshots ==

1. Recently viewed products carousel on a single product page.
2. Recently viewed products grid layout.
3. Recently viewed products list layout.
4. Tabbed settings screen with layout, placement, and engine options.
5. Most viewed products analytics screen.
6. Gutenberg block with live preview in the editor.
7. Elementor widget settings.
8. Follow-up email with viewed products and a single-use coupon.

== Changelog ==

= 2.3.0 =

* New: Carousel layout with autoplay, arrows, pagination dots, loop, and responsive items-per-view (dependency-free).
* New: Sidebar widget (classic + block widget areas).
* New: Gutenberg block with live server-side preview.
* New: Elementor widget (loads only when Elementor is active).
* New: Sorting — recently viewed, most viewed, price, newest, or random.
* New: "Most viewed" store-wide popularity source, with a cached top-N query.
* New: Optional database-backed history for signed-in customers (cross-device), merged from the guest cookie at login, with daily pruning.
* New: Smart filters — in-stock only, exclude already-purchased, hide free products, exclude categories.
* New: Analytics screen with most-viewed products and an orders proxy (from WooCommerce Analytics when available).
* New: Optional follow-up emails to inactive customers with their viewed products and a single-use coupon, plus one-click unsubscribe (Action Scheduler when available, WP-Cron fallback).
* New: Dedicated "Recently Viewed" page created on activation, and a custom CSS box.
* Improved: All display surfaces (shortcode, placement, widget, block, Elementor) now share one renderer/provider, so output and behaviour are always consistent.
* Compatibility: Custom tables are created via dbDelta with a versioned migration; no order data is touched (HPOS-safe).
* Backward compatible: all existing settings, the shortcode, the cookie format, and placement hooks are unchanged; every new setting defaults to the previous behaviour.

= 2.2.0 =

* Fixed: saving one settings tab no longer resets the other tabs to defaults (all settings now submit together).
* Fixed: responsive column settings are now respected for automatic product-page placement (output is no longer re-filtered, which could strip the layout CSS variables).
* Fixed: the product count is now resolved before the display limit is applied, so hidden or removed products no longer shrink the visible list.
* Fixed: the empty-state message is no longer printed on product pages when a visitor has no history (shortcode behaviour unchanged).
* Added: List layout option alongside Grid.
* Added: support for multiple space-separated custom CSS classes.
* Added: "Requires Plugins: woocommerce" header and WooCommerce version headers.
* Improved: redesigned, accessible (WCAG) admin UI with single-page tabbed settings, toggle switches, help text, status sidebar, and a sticky save bar.
* Improved: shortcode copy now uses the modern Clipboard API with a live shortcode preview.
* Improved: front-end stylesheet is enqueued in the head for placed output to prevent a flash of unstyled content.
* Improved: centralized default/settings handling to a single source of truth.
* Fixed: removed UTF-8 byte order marks and normalized line endings in core files (a BOM before the opening PHP tag can cause "headers already sent" warnings on some servers).
* Verified: passes the WordPress Plugin Check PHPCS rulesets (plugin-check and plugin-review) with zero errors.
* Docs: added Privacy & Cookies and Frequently Asked Questions sections to the readme.

= 2.1.0 =

* Added modern settings architecture and admin tabs.
* Added shortcode generator UI.
* Added secure settings save flow (capability + nonce + sanitization).
* Improved tracking and rendering logic.
* Added uninstall conditional cleanup.

= 2.0.0 =

* Initial release.

== Upgrade Notice ==

= 2.3.0 =
Adds a no-jQuery carousel, sidebar widget, Gutenberg block, Elementor widget, most viewed products, cross-device history, and follow-up emails. Fully backward compatible — all existing settings and the shortcode are unchanged.

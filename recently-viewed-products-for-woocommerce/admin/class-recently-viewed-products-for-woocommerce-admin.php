<?php
/**
 * Admin functionality.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class RVPW_Recently_Viewed_Products_For_Woocommerce_Admin {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Top-level menu / settings page slug.
	 *
	 * Shared with the Analytics screen (which registers itself as a submenu of
	 * this page) so both stay in sync.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'rvpw-settings';

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	private $menu_slug = self::MENU_SLUG;

	/**
	 * Settings page hook suffix, captured on menu registration so assets can be
	 * scoped to exactly this screen without guessing the generated hook name.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version Version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the top-level menu and its Settings submenu.
	 *
	 * A dedicated top-level menu groups every plugin screen (Settings and
	 * Analytics) under one parent, instead of scattering flat items inside the
	 * WooCommerce menu. The Analytics screen attaches itself as a second submenu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		$this->page_hook = add_menu_page(
			esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Recently Viewed', 'recently-viewed-products-for-woocommerce' ),
			'manage_woocommerce',
			$this->menu_slug,
			array( $this, 'render_settings_page' ),
			'dashicons-visibility',
			58
		);

		// Re-add the parent slug as the first submenu so its label reads
		// "Settings" rather than repeating the top-level menu title.
		add_submenu_page(
			$this->menu_slug,
			esc_html__( 'Settings', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Settings', 'recently-viewed-products-for-woocommerce' ),
			'manage_woocommerce',
			$this->menu_slug,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets only on this plugin's settings screen.
	 *
	 * @param string $hook_suffix Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( '' === $this->page_hook || $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'css/recently-viewed-products-for-woocommerce-admin.css', array(), $this->version );
		wp_enqueue_script( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'js/recently-viewed-products-for-woocommerce-admin.js', array(), $this->version, true );

		wp_localize_script(
			$this->plugin_name . '-admin',
			'rvpwAdmin',
			array(
				'copied'      => esc_html__( 'Copied!', 'recently-viewed-products-for-woocommerce' ),
				'copy'        => esc_html__( 'Copy shortcode', 'recently-viewed-products-for-woocommerce' ),
				'copyFailed'  => esc_html__( 'Press Ctrl/Cmd + C to copy', 'recently-viewed-products-for-woocommerce' ),
				'unsavedWarn' => esc_html__( 'You have unsaved changes. Leave this page?', 'recently-viewed-products-for-woocommerce' ),
			)
		);
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this action.', 'recently-viewed-products-for-woocommerce' ) );
		}

		check_admin_referer( 'rvpw_save_settings_action', 'rvpw_nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce verified above; the raw array is fully sanitized field-by-field in Settings::sanitize() on the next line.
		$raw       = isset( $_POST['rvpw_settings'] ) && is_array( $_POST['rvpw_settings'] ) ? wp_unslash( $_POST['rvpw_settings'] ) : array();
		$sanitized = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize( $raw );

		update_option( RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::OPTION_KEY, $sanitized );

		$redirect_url = add_query_arg(
			array(
				'page'    => $this->menu_slug,
				'tab'     => isset( $_POST['rvpw_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['rvpw_active_tab'] ) ) : 'general',
				'updated' => 'true',
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * All tab panels render inside a single form. Tab switching is handled
	 * client-side which means every field is always submitted together. This
	 * is essential: settings are rebuilt from defaults on save, so a partial
	 * submission (one tab only) would reset every field on the other tabs.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$tabs     = $this->get_tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI state, no data mutation.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		if ( ! isset( $tabs[ $active_tab ] ) ) {
			$active_tab = 'general';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success flag set by our own redirect.
		$updated = isset( $_GET['updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['updated'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flag set by our own redirect.
		$test_result = isset( $_GET['rvpw_test'] ) ? sanitize_key( wp_unslash( $_GET['rvpw_test'] ) ) : '';
		?>
		<div class="wrap rvpw-admin">
			<div class="rvpw-header">
				<div class="rvpw-header__brand">
					<span class="rvpw-header__icon dashicons dashicons-visibility" aria-hidden="true"></span>
					<div class="rvpw-header__text">
						<h1 class="rvpw-header__title"><?php esc_html_e( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ); ?></h1>
						<p class="rvpw-header__subtitle"><?php esc_html_e( 'Show shoppers the products they recently viewed and bring them back to buy.', 'recently-viewed-products-for-woocommerce' ); ?></p>
					</div>
				</div>
				<span class="rvpw-badge"><?php echo esc_html( sprintf( /* translators: %s: plugin version. */ __( 'v%s', 'recently-viewed-products-for-woocommerce' ), $this->version ) ); ?></span>
			</div>

			<div class="rvpw-notices">
				<?php if ( $updated ) : ?>
					<div class="notice notice-success is-dismissible rvpw-notice"><p><?php esc_html_e( 'Settings saved.', 'recently-viewed-products-for-woocommerce' ); ?></p></div>
				<?php endif; ?>
				<?php if ( 'sent' === $test_result ) : ?>
					<div class="notice notice-success is-dismissible rvpw-notice"><p><?php esc_html_e( 'Test email sent to your account email address.', 'recently-viewed-products-for-woocommerce' ); ?></p></div>
				<?php elseif ( 'failed' === $test_result ) : ?>
					<div class="notice notice-error is-dismissible rvpw-notice"><p><?php esc_html_e( 'The test email could not be sent. Check your site’s email configuration.', 'recently-viewed-products-for-woocommerce' ); ?></p></div>
				<?php endif; ?>
				<?php if ( 'yes' !== $settings['enabled'] ) : ?>
					<div class="notice notice-warning rvpw-notice"><p><?php esc_html_e( 'Tracking is currently disabled. Enable it on the General tab to start recording recently viewed products.', 'recently-viewed-products-for-woocommerce' ); ?></p></div>
				<?php endif; ?>
			</div>

			<div class="rvpw-layout">
				<div class="rvpw-main">
					<nav class="rvpw-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'recently-viewed-products-for-woocommerce' ); ?>">
						<?php foreach ( $tabs as $tab_key => $tab ) : ?>
							<button
								type="button"
								class="rvpw-tab <?php echo $active_tab === $tab_key ? 'is-active' : ''; ?>"
								id="rvpw-tab-<?php echo esc_attr( $tab_key ); ?>"
								data-tab="<?php echo esc_attr( $tab_key ); ?>"
								role="tab"
								aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
								aria-controls="rvpw-panel-<?php echo esc_attr( $tab_key ); ?>"
							>
								<span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>" aria-hidden="true"></span>
								<span class="rvpw-tab__label"><?php echo esc_html( $tab['label'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</nav>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rvpw-form">
						<input type="hidden" name="action" value="rvpw_save_settings" />
						<input type="hidden" name="rvpw_active_tab" id="rvpw-active-tab" value="<?php echo esc_attr( $active_tab ); ?>" />
						<?php wp_nonce_field( 'rvpw_save_settings_action', 'rvpw_nonce' ); ?>

						<?php foreach ( $tabs as $tab_key => $tab ) : ?>
							<div class="rvpw-panel <?php echo $active_tab === $tab_key ? 'is-active' : ''; ?>" id="rvpw-panel-<?php echo esc_attr( $tab_key ); ?>" role="tabpanel" aria-labelledby="rvpw-tab-<?php echo esc_attr( $tab_key ); ?>" tabindex="0">
								<?php $this->render_tab_content( $tab_key, $settings ); ?>
							</div>
						<?php endforeach; ?>

						<div class="rvpw-actions">
							<?php submit_button( esc_html__( 'Save Changes', 'recently-viewed-products-for-woocommerce' ), 'primary', 'submit', false ); ?>
						</div>
					</form>
				</div>

				<aside class="rvpw-sidebar">
					<?php $this->render_sidebar( $settings ); ?>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Dispatch to the renderer for a given tab.
	 *
	 * @param string $tab_key  Tab key.
	 * @param array  $settings Settings.
	 * @return void
	 */
	private function render_tab_content( $tab_key, $settings ) {
		switch ( $tab_key ) {
			case 'display':
				$this->render_display_tab( $settings );
				break;
			case 'layout':
				$this->render_layout_tab( $settings );
				break;
			case 'engine':
				$this->render_engine_tab( $settings );
				break;
			case 'placement':
				$this->render_placement_tab( $settings );
				break;
			case 'premium':
				$this->render_premium_tab( $settings );
				break;
			case 'email':
				$this->render_email_tab( $settings );
				break;
			case 'shortcode':
				$this->render_shortcode_tab( $settings );
				break;
			case 'general':
			default:
				$this->render_general_tab( $settings );
				break;
		}
	}

	/**
	 * Sidebar widgets (shortcode quick copy + help).
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_sidebar( $settings ) {
		?>
		<div class="rvpw-card rvpw-card--sidebar">
			<h2 class="rvpw-card__title"><span class="dashicons dashicons-shortcode" aria-hidden="true"></span> <?php esc_html_e( 'Quick shortcode', 'recently-viewed-products-for-woocommerce' ); ?></h2>
			<p class="rvpw-card__intro"><?php esc_html_e( 'Drop this anywhere — pages, posts, or widgets.', 'recently-viewed-products-for-woocommerce' ); ?></p>
			<code class="rvpw-inline-code">[rvpw_products]</code>
		</div>

		<div class="rvpw-card rvpw-card--sidebar">
			<h2 class="rvpw-card__title"><span class="dashicons dashicons-info-outline" aria-hidden="true"></span> <?php esc_html_e( 'How it works', 'recently-viewed-products-for-woocommerce' ); ?></h2>
			<ul class="rvpw-list">
				<li><?php esc_html_e( 'Views are stored in a per-visitor cookie — no personal data leaves the browser.', 'recently-viewed-products-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Add the section automatically on product pages, or place it manually with the shortcode.', 'recently-viewed-products-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Assets load only where the section is shown, keeping pages fast.', 'recently-viewed-products-for-woocommerce' ); ?></li>
			</ul>
		</div>

		<div class="rvpw-card rvpw-card--sidebar rvpw-card--status">
			<h2 class="rvpw-card__title"><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span> <?php esc_html_e( 'Current status', 'recently-viewed-products-for-woocommerce' ); ?></h2>
			<ul class="rvpw-status">
				<li>
					<span class="rvpw-status__label"><?php esc_html_e( 'Tracking', 'recently-viewed-products-for-woocommerce' ); ?></span>
					<span class="rvpw-pill <?php echo 'yes' === $settings['enabled'] ? 'rvpw-pill--on' : 'rvpw-pill--off'; ?>"><?php echo 'yes' === $settings['enabled'] ? esc_html__( 'On', 'recently-viewed-products-for-woocommerce' ) : esc_html__( 'Off', 'recently-viewed-products-for-woocommerce' ); ?></span>
				</li>
				<li>
					<span class="rvpw-status__label"><?php esc_html_e( 'Auto-placement', 'recently-viewed-products-for-woocommerce' ); ?></span>
					<span class="rvpw-pill <?php echo ( 'yes' === $settings['show_on_product_page'] && 'disable' !== $settings['placement'] ) ? 'rvpw-pill--on' : 'rvpw-pill--off'; ?>"><?php echo ( 'yes' === $settings['show_on_product_page'] && 'disable' !== $settings['placement'] ) ? esc_html__( 'On', 'recently-viewed-products-for-woocommerce' ) : esc_html__( 'Off', 'recently-viewed-products-for-woocommerce' ); ?></span>
				</li>
				<li>
					<span class="rvpw-status__label"><?php esc_html_e( 'Products shown', 'recently-viewed-products-for-woocommerce' ); ?></span>
					<span class="rvpw-pill rvpw-pill--neutral"><?php echo esc_html( absint( $settings['display_limit'] ) ); ?></span>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * General tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_general_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Tracking', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Control whose product views are recorded and how long they are remembered.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'enabled', esc_html__( 'Enable plugin', 'recently-viewed-products-for-woocommerce' ), $settings['enabled'], esc_html__( 'Master switch. When off, no views are tracked and nothing is displayed.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'track_guests', esc_html__( 'Track guests', 'recently-viewed-products-for-woocommerce' ), $settings['track_guests'], esc_html__( 'Record product views for visitors who are not logged in.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'track_logged_in_users', esc_html__( 'Track logged-in users', 'recently-viewed-products-for-woocommerce' ), $settings['track_logged_in_users'], esc_html__( 'Record product views for signed-in customers.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Storage & data', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'How many products to remember and what happens to your data.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_number( 'cookie_expiry_days', esc_html__( 'Cookie expiry (days)', 'recently-viewed-products-for-woocommerce' ), $settings['cookie_expiry_days'], 1, 365, esc_html__( 'How long a visitor’s history is kept in their browser. 1–365 days.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'max_stored_products', esc_html__( 'Maximum products to store', 'recently-viewed-products-for-woocommerce' ), $settings['max_stored_products'], 1, 100, esc_html__( 'Upper limit of items kept in history per visitor. 1–100.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'delete_data_on_uninstall', esc_html__( 'Delete settings on uninstall', 'recently-viewed-products-for-woocommerce' ), $settings['delete_data_on_uninstall'], esc_html__( 'Remove all plugin options when the plugin is deleted. Leave off to keep your configuration.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();
	}

	/**
	 * Display tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_display_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Section', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Heading and how many products to display.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'show_on_product_page', esc_html__( 'Show on single product page', 'recently-viewed-products-for-woocommerce' ), $settings['show_on_product_page'], esc_html__( 'Automatically output the section on product pages (see the Placement tab for position).', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_text( 'section_title', esc_html__( 'Section title', 'recently-viewed-products-for-woocommerce' ), $settings['section_title'], esc_html__( 'Heading shown above the products. Leave blank to hide it.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'display_limit', esc_html__( 'Products to display', 'recently-viewed-products-for-woocommerce' ), $settings['display_limit'], 1, 24, esc_html__( 'Maximum number of products shown in the section. 1–24.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'hide_current_product', esc_html__( 'Hide current product', 'recently-viewed-products-for-woocommerce' ), $settings['hide_current_product'], esc_html__( 'Exclude the product currently being viewed from the list.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_select(
			'sort_order',
			esc_html__( 'Order products by', 'recently-viewed-products-for-woocommerce' ),
			$settings['sort_order'],
			$this->get_sort_options(),
			esc_html__( 'How products are ordered within the section.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->close_card();

		$this->open_card(
			esc_html__( 'Product card', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Choose which elements appear on each product.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'show_image', esc_html__( 'Show product image', 'recently-viewed-products-for-woocommerce' ), $settings['show_image'] );
		$this->field_toggle( 'show_title', esc_html__( 'Show product title', 'recently-viewed-products-for-woocommerce' ), $settings['show_title'] );
		$this->field_toggle( 'show_price', esc_html__( 'Show product price', 'recently-viewed-products-for-woocommerce' ), $settings['show_price'] );
		$this->field_toggle( 'show_rating', esc_html__( 'Show rating', 'recently-viewed-products-for-woocommerce' ), $settings['show_rating'], esc_html__( 'Displays star ratings for products that have reviews.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'show_add_to_cart', esc_html__( 'Show add-to-cart button', 'recently-viewed-products-for-woocommerce' ), $settings['show_add_to_cart'] );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Filters', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Optionally hide products from the section. All filters are off by default.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'filter_in_stock_only', esc_html__( 'In-stock products only', 'recently-viewed-products-for-woocommerce' ), $settings['filter_in_stock_only'], esc_html__( 'Hide products that are out of stock.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'filter_exclude_purchased', esc_html__( 'Exclude already-purchased products', 'recently-viewed-products-for-woocommerce' ), $settings['filter_exclude_purchased'], esc_html__( 'For signed-in customers, hide products they have already bought.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'filter_hide_free', esc_html__( 'Hide free products', 'recently-viewed-products-for-woocommerce' ), $settings['filter_hide_free'], esc_html__( 'Hide products with a zero price.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_category_multiselect( 'filter_exclude_categories', esc_html__( 'Exclude categories', 'recently-viewed-products-for-woocommerce' ), (array) $settings['filter_exclude_categories'], esc_html__( 'Products in the selected categories are never shown. Ctrl/Cmd-click to select multiple.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Empty state', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Shown by the shortcode when a visitor has no history yet.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_text( 'empty_message', esc_html__( 'Empty-state message', 'recently-viewed-products-for-woocommerce' ), $settings['empty_message'], esc_html__( 'Leave blank to output nothing when there is no history. Automatic product-page placement always stays hidden when empty.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();
	}

	/**
	 * Layout tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_layout_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Layout', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Pick a layout and how many columns to show per device.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_select(
			'layout',
			esc_html__( 'Layout', 'recently-viewed-products-for-woocommerce' ),
			$settings['layout'],
			array(
				'grid'     => esc_html__( 'Grid', 'recently-viewed-products-for-woocommerce' ),
				'list'     => esc_html__( 'List', 'recently-viewed-products-for-woocommerce' ),
				'carousel' => esc_html__( 'Carousel', 'recently-viewed-products-for-woocommerce' ),
			),
			esc_html__( 'Grid arranges products in responsive columns. List stacks them in compact rows. Carousel shows a sliding strip.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_number( 'mobile_columns', esc_html__( 'Mobile columns', 'recently-viewed-products-for-woocommerce' ), $settings['mobile_columns'], 1, 2, esc_html__( 'Items per view on phones (grid & carousel). 1–2.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'tablet_columns', esc_html__( 'Tablet columns', 'recently-viewed-products-for-woocommerce' ), $settings['tablet_columns'], 1, 4, esc_html__( 'Items per view on tablets (grid & carousel). 1–4.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'desktop_columns', esc_html__( 'Desktop columns', 'recently-viewed-products-for-woocommerce' ), $settings['desktop_columns'], 1, 6, esc_html__( 'Items per view on desktops (grid & carousel). 1–6.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_text( 'custom_css_class', esc_html__( 'Custom CSS class', 'recently-viewed-products-for-woocommerce' ), $settings['custom_css_class'], esc_html__( 'Optional. Add one or more space-separated classes to the wrapper for custom styling.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Carousel', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Options used only when the layout above is set to Carousel.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'carousel_autoplay', esc_html__( 'Autoplay', 'recently-viewed-products-for-woocommerce' ), $settings['carousel_autoplay'], esc_html__( 'Automatically advance the carousel. Pauses on hover, focus, or touch.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'carousel_autoplay_speed', esc_html__( 'Autoplay speed (ms)', 'recently-viewed-products-for-woocommerce' ), $settings['carousel_autoplay_speed'], 1000, 15000, esc_html__( 'Delay between automatic slides, in milliseconds. 1000–15000.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'carousel_loop', esc_html__( 'Loop', 'recently-viewed-products-for-woocommerce' ), $settings['carousel_loop'], esc_html__( 'Return to the start after the last slide.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_toggle( 'carousel_arrows', esc_html__( 'Show arrows', 'recently-viewed-products-for-woocommerce' ), $settings['carousel_arrows'] );
		$this->field_toggle( 'carousel_dots', esc_html__( 'Show pagination dots', 'recently-viewed-products-for-woocommerce' ), $settings['carousel_dots'] );
		$this->close_card();
	}

	/**
	 * Sort options for the Display tab dropdown.
	 *
	 * @return array
	 */
	private function get_sort_options() {
		return array(
			'recent'      => esc_html__( 'Recently viewed (default)', 'recently-viewed-products-for-woocommerce' ),
			'most_viewed' => esc_html__( 'Most viewed', 'recently-viewed-products-for-woocommerce' ),
			'price_asc'   => esc_html__( 'Price: low to high', 'recently-viewed-products-for-woocommerce' ),
			'price_desc'  => esc_html__( 'Price: high to low', 'recently-viewed-products-for-woocommerce' ),
			'date'        => esc_html__( 'Newest products', 'recently-viewed-products-for-woocommerce' ),
			'random'      => esc_html__( 'Random', 'recently-viewed-products-for-woocommerce' ),
		);
	}

	/**
	 * Engine tab (tracking storage, cross-device history, sources).
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_engine_tab( $settings ) {
		$this->open_card(
			esc_html__( 'History source', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Where the products shown in the section come from.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_select(
			'source',
			esc_html__( 'Show products from', 'recently-viewed-products-for-woocommerce' ),
			$settings['source'],
			$this->get_source_options(),
			esc_html__( 'Automatic uses saved account history for signed-in shoppers (when cross-device tracking is on) and the browser cookie otherwise.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->close_card();

		$this->open_card(
			esc_html__( 'Cross-device history', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Remember signed-in customers across their devices and browsers.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'track_db_for_logged_in', esc_html__( 'Store logged-in history in the database', 'recently-viewed-products-for-woocommerce' ), $settings['track_db_for_logged_in'], esc_html__( 'When on, a signed-in customer sees the same recently viewed products on any device. Guests always use the cookie. Requires “Track logged-in users” on the General tab.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'db_retention_days', esc_html__( 'History retention (days)', 'recently-viewed-products-for-woocommerce' ), $settings['db_retention_days'], 1, 730, esc_html__( 'How long stored account history is kept before it is pruned. 1–730 days.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();
	}

	/**
	 * Source options for the Engine tab.
	 *
	 * @return array
	 */
	private function get_source_options() {
		return array(
			'auto'        => esc_html__( 'Automatic (recommended)', 'recently-viewed-products-for-woocommerce' ),
			'cookie'      => esc_html__( 'Browser cookie only', 'recently-viewed-products-for-woocommerce' ),
			'db'          => esc_html__( 'Saved account history (logged-in users)', 'recently-viewed-products-for-woocommerce' ),
			'most_viewed' => esc_html__( 'Most viewed (store-wide popularity)', 'recently-viewed-products-for-woocommerce' ),
		);
	}

	/**
	 * Placement tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_placement_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Automatic placement', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Where the section appears on single product pages.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_select(
			'placement',
			esc_html__( 'Product page placement', 'recently-viewed-products-for-woocommerce' ),
			$settings['placement'],
			array(
				'disable'                                   => esc_html__( 'Disable automatic placement (shortcode only)', 'recently-viewed-products-for-woocommerce' ),
				'woocommerce_before_single_product_summary' => esc_html__( 'Before product summary', 'recently-viewed-products-for-woocommerce' ),
				'woocommerce_after_single_product_summary'  => esc_html__( 'After product summary', 'recently-viewed-products-for-woocommerce' ),
				'woocommerce_after_single_product'          => esc_html__( 'After product tabs', 'recently-viewed-products-for-woocommerce' ),
			),
			esc_html__( 'Requires “Show on single product page” to be enabled on the Display tab.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->close_card();
	}

	/**
	 * Premium tab (dedicated page + custom CSS).
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_premium_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Dedicated page', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'A standalone page that lists recently viewed products. Created automatically on activation.', 'recently-viewed-products-for-woocommerce' )
		);
		$page_id = class_exists( 'RVPW_Recently_Viewed_Products_For_Woocommerce_Page_Manager' ) ? RVPW_Recently_Viewed_Products_For_Woocommerce_Page_Manager::get_page_id() : 0;
		?>
		<div class="rvpw-fields">
			<?php if ( $page_id ) : ?>
				<p class="rvpw-field__desc">
					<?php esc_html_e( 'Your dedicated page is ready:', 'recently-viewed-products-for-woocommerce' ); ?>
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $page_id ) ); ?></a>
					&middot;
					<a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>"><?php esc_html_e( 'Edit', 'recently-viewed-products-for-woocommerce' ); ?></a>
				</p>
			<?php else : ?>
				<p class="rvpw-field__desc">
					<?php esc_html_e( 'No dedicated page is currently published. You can place the section on any page with the shortcode below:', 'recently-viewed-products-for-woocommerce' ); ?>
					<code class="rvpw-inline-code">[rvpw_products]</code>
				</p>
			<?php endif; ?>
		</div>
		<?php
		$this->close_card();

		$this->open_card(
			esc_html__( 'Custom CSS', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Fine-tune the appearance. Output in the site head; HTML and unsafe constructs are removed.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_textarea( 'custom_css', esc_html__( 'Custom CSS', 'recently-viewed-products-for-woocommerce' ), $settings['custom_css'], esc_html__( 'Example: .rvpw-products .price { font-weight: 700; }', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();
	}

	/**
	 * Textarea field row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param string $value Current value.
	 * @param string $desc  Optional help text.
	 * @return void
	 */
	private function field_textarea( $key, $label, $value, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';
		?>
		<div class="rvpw-field">
			<label class="rvpw-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="rvpw-field__control">
				<textarea id="<?php echo esc_attr( $id ); ?>" class="large-text code" rows="8" name="rvpw_settings[<?php echo esc_attr( $key ); ?>]" <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
			</div>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Follow-up emails tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_email_tab( $settings ) {
		$this->open_card(
			esc_html__( 'Follow-up email', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Email signed-in customers who went quiet, reminding them of products they viewed. Requires cross-device tracking (Engine tab) to have history to send.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'email_enabled', esc_html__( 'Enable follow-up emails', 'recently-viewed-products-for-woocommerce' ), $settings['email_enabled'], esc_html__( 'A daily background task emails eligible customers. Each customer is emailed at most once every 30 days.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'email_inactivity_days', esc_html__( 'Send after inactivity (days)', 'recently-viewed-products-for-woocommerce' ), $settings['email_inactivity_days'], 1, 90, esc_html__( 'Email a customer this many days after their last product view. 1–90.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_text( 'email_subject', esc_html__( 'Subject', 'recently-viewed-products-for-woocommerce' ), $settings['email_subject'] );
		$this->field_textarea( 'email_body', esc_html__( 'Body', 'recently-viewed-products-for-woocommerce' ), $settings['email_body'], esc_html__( 'Placeholders: {first_name}, {products}, {coupon}. Basic HTML is allowed.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Coupon', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Optionally attach a single-use coupon to each follow-up email.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_toggle( 'email_coupon_enabled', esc_html__( 'Include a coupon', 'recently-viewed-products-for-woocommerce' ), $settings['email_coupon_enabled'], esc_html__( 'Generates a unique, single-use coupon per email.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_select(
			'email_coupon_type',
			esc_html__( 'Discount type', 'recently-viewed-products-for-woocommerce' ),
			$settings['email_coupon_type'],
			array(
				'percent'    => esc_html__( 'Percentage discount', 'recently-viewed-products-for-woocommerce' ),
				'fixed_cart' => esc_html__( 'Fixed cart discount', 'recently-viewed-products-for-woocommerce' ),
			)
		);
		$this->field_number( 'email_coupon_amount', esc_html__( 'Amount', 'recently-viewed-products-for-woocommerce' ), $settings['email_coupon_amount'], 0, 100000, esc_html__( 'Percentage (capped at 100) or fixed cart amount.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'email_coupon_expiry_days', esc_html__( 'Coupon validity (days)', 'recently-viewed-products-for-woocommerce' ), $settings['email_coupon_expiry_days'], 1, 365, esc_html__( 'How long each generated coupon stays valid. 1–365.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();

		$this->open_card(
			esc_html__( 'Test', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Send yourself a test email using the saved settings. Save your changes first.', 'recently-viewed-products-for-woocommerce' )
		);
		$test_url = wp_nonce_url( admin_url( 'admin-post.php?action=rvpw_send_test_email' ), 'rvpw_send_test_email' );
		?>
		<p><a href="<?php echo esc_url( $test_url ); ?>" class="button button-secondary"><span class="dashicons dashicons-email-alt" aria-hidden="true" style="margin-top:4px;"></span> <?php esc_html_e( 'Send test email to me', 'recently-viewed-products-for-woocommerce' ); ?></a></p>
		<?php
		$this->close_card();
	}

	/**
	 * Shortcode tab.
	 *
	 * @param array $settings Settings.
	 * @return void
	 */
	private function render_shortcode_tab( $settings ) {
		$shortcode = $this->build_shortcode_string( $settings );
		$this->open_card(
			esc_html__( 'Shortcode generator', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Use this shortcode in pages, posts, widgets, or templates. It reflects your saved settings and the values below.', 'recently-viewed-products-for-woocommerce' )
		);
		?>
		<div class="rvpw-shortcode-box">
			<input id="rvpw-generated-shortcode" type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>" class="large-text code" aria-label="<?php esc_attr_e( 'Generated shortcode', 'recently-viewed-products-for-woocommerce' ); ?>" />
			<button type="button" class="button button-secondary" id="rvpw-copy-shortcode">
				<span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
				<span class="rvpw-copy-label"><?php esc_html_e( 'Copy shortcode', 'recently-viewed-products-for-woocommerce' ); ?></span>
			</button>
		</div>
		<p class="rvpw-field__desc"><?php esc_html_e( 'Tip: any attribute you omit falls back to your saved settings above.', 'recently-viewed-products-for-woocommerce' ); ?></p>
		<?php
		$this->close_card();
	}

	/**
	 * Build the example shortcode string from settings.
	 *
	 * @param array $settings Settings.
	 * @return string
	 */
	private function build_shortcode_string( $settings ) {
		return sprintf(
			'[rvpw_products title="%1$s" limit="%2$d" columns="%3$d" layout="%4$s" show_price="%5$s" show_cart="%6$s"]',
			esc_attr( $settings['section_title'] ),
			absint( $settings['display_limit'] ),
			absint( $settings['desktop_columns'] ),
			esc_attr( $settings['layout'] ),
			esc_attr( $settings['show_price'] ),
			esc_attr( $settings['show_add_to_cart'] )
		);
	}

	/**
	 * Tab definitions.
	 *
	 * @return array
	 */
	private function get_tabs() {
		return array(
			'general'   => array(
				'label' => esc_html__( 'General', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-admin-settings',
			),
			'engine'    => array(
				'label' => esc_html__( 'Engine', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-database',
			),
			'display'   => array(
				'label' => esc_html__( 'Display', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-visibility',
			),
			'layout'    => array(
				'label' => esc_html__( 'Layout', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-layout',
			),
			'placement' => array(
				'label' => esc_html__( 'Placement', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-align-center',
			),
			'premium'   => array(
				'label' => esc_html__( 'Page & CSS', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-admin-page',
			),
			'email'     => array(
				'label' => esc_html__( 'Follow-up emails', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-email',
			),
			'shortcode' => array(
				'label' => esc_html__( 'Shortcode', 'recently-viewed-products-for-woocommerce' ),
				'icon'  => 'dashicons-shortcode',
			),
		);
	}

	/**
	 * Open a settings card.
	 *
	 * @param string $title Card title.
	 * @param string $intro Optional intro text.
	 * @return void
	 */
	private function open_card( $title, $intro = '' ) {
		?>
		<div class="rvpw-card">
			<h2 class="rvpw-card__title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( '' !== $intro ) : ?>
				<p class="rvpw-card__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
			<div class="rvpw-fields">
		<?php
	}

	/**
	 * Close a settings card.
	 *
	 * @return void
	 */
	private function close_card() {
		?>
			</div>
		</div>
		<?php
	}

	/**
	 * Accessible toggle switch (backed by a native checkbox).
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param string $value Current value.
	 * @param string $desc  Optional help text.
	 * @return void
	 */
	private function field_toggle( $key, $label, $value, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';
		?>
		<div class="rvpw-field rvpw-field--toggle">
			<label class="rvpw-toggle" for="<?php echo esc_attr( $id ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" class="rvpw-toggle__input" name="rvpw_settings[<?php echo esc_attr( $key ); ?>]" value="yes" <?php checked( 'yes', $value ); ?> <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?> />
				<span class="rvpw-toggle__track" aria-hidden="true"><span class="rvpw-toggle__thumb"></span></span>
				<span class="rvpw-toggle__text"><?php echo esc_html( $label ); ?></span>
			</label>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Text field row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param string $value Current value.
	 * @param string $desc  Optional help text.
	 * @return void
	 */
	private function field_text( $key, $label, $value, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';
		?>
		<div class="rvpw-field">
			<label class="rvpw-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="rvpw-field__control">
				<input type="text" id="<?php echo esc_attr( $id ); ?>" class="regular-text" name="rvpw_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?> data-rvpw-field="<?php echo esc_attr( $key ); ?>" />
			</div>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Number field row.
	 *
	 * @param string $key   Setting key.
	 * @param string $label Label.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 * @param string $desc  Optional help text.
	 * @return void
	 */
	private function field_number( $key, $label, $value, $min, $max, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';
		?>
		<div class="rvpw-field">
			<label class="rvpw-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="rvpw-field__control">
				<input type="number" id="<?php echo esc_attr( $id ); ?>" class="small-text" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="1" name="rvpw_settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?> data-rvpw-field="<?php echo esc_attr( $key ); ?>" />
			</div>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Product-category multi-select field row.
	 *
	 * @param string $key      Setting key.
	 * @param string $label    Label.
	 * @param int[]  $selected Selected term IDs.
	 * @param string $desc     Optional help text.
	 * @return void
	 */
	private function field_category_multiselect( $key, $label, $selected, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 200,
			)
		);

		$selected = array_map( 'absint', (array) $selected );
		?>
		<div class="rvpw-field">
			<label class="rvpw-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="rvpw-field__control">
				<?php if ( is_wp_error( $terms ) || empty( $terms ) ) : ?>
					<p class="rvpw-field__desc"><?php esc_html_e( 'No product categories found.', 'recently-viewed-products-for-woocommerce' ); ?></p>
				<?php else : ?>
					<select id="<?php echo esc_attr( $id ); ?>" name="rvpw_settings[<?php echo esc_attr( $key ); ?>][]" multiple size="6" class="rvpw-multiselect" <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?>>
						<?php foreach ( $terms as $term ) : ?>
							<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( (int) $term->term_id, $selected, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Select field row.
	 *
	 * @param string $key     Setting key.
	 * @param string $label   Label.
	 * @param string $value   Current value.
	 * @param array  $options Options map.
	 * @param string $desc    Optional help text.
	 * @return void
	 */
	private function field_select( $key, $label, $value, $options, $desc = '' ) {
		$id      = 'rvpw-' . $key;
		$desc_id = $id . '-desc';
		?>
		<div class="rvpw-field">
			<label class="rvpw-field__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="rvpw-field__control">
				<select id="<?php echo esc_attr( $id ); ?>" name="rvpw_settings[<?php echo esc_attr( $key ); ?>]" <?php echo '' !== $desc ? 'aria-describedby="' . esc_attr( $desc_id ) . '"' : ''; ?> data-rvpw-field="<?php echo esc_attr( $key ); ?>">
					<?php foreach ( $options as $option_key => $option_label ) : ?>
						<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $value, $option_key ); ?>><?php echo esc_html( $option_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php if ( '' !== $desc ) : ?>
				<p class="rvpw-field__desc" id="<?php echo esc_attr( $desc_id ); ?>"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add a Settings link on the plugins list row.
	 *
	 * @param array $plugin_actions Existing action links.
	 * @return array
	 */
	public function rvpw_settings_link( $plugin_actions ) {
		$url           = add_query_arg( array( 'page' => $this->menu_slug ), admin_url( 'admin.php' ) );
		$settings_link = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'Settings', 'recently-viewed-products-for-woocommerce' ) );

		array_unshift( $plugin_actions, $settings_link );

		return $plugin_actions;
	}
}

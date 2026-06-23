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
	 * Menu slug.
	 *
	 * @var string
	 */
	private $menu_slug = 'rvpw-settings';

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
	 * Register menu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
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
		if ( 'woocommerce_page_' . $this->menu_slug !== $hook_suffix ) {
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

						<div class="rvpw-panel <?php echo 'general' === $active_tab ? 'is-active' : ''; ?>" id="rvpw-panel-general" role="tabpanel" aria-labelledby="rvpw-tab-general" tabindex="0">
							<?php $this->render_general_tab( $settings ); ?>
						</div>
						<div class="rvpw-panel <?php echo 'display' === $active_tab ? 'is-active' : ''; ?>" id="rvpw-panel-display" role="tabpanel" aria-labelledby="rvpw-tab-display" tabindex="0">
							<?php $this->render_display_tab( $settings ); ?>
						</div>
						<div class="rvpw-panel <?php echo 'layout' === $active_tab ? 'is-active' : ''; ?>" id="rvpw-panel-layout" role="tabpanel" aria-labelledby="rvpw-tab-layout" tabindex="0">
							<?php $this->render_layout_tab( $settings ); ?>
						</div>
						<div class="rvpw-panel <?php echo 'placement' === $active_tab ? 'is-active' : ''; ?>" id="rvpw-panel-placement" role="tabpanel" aria-labelledby="rvpw-tab-placement" tabindex="0">
							<?php $this->render_placement_tab( $settings ); ?>
						</div>
						<div class="rvpw-panel <?php echo 'shortcode' === $active_tab ? 'is-active' : ''; ?>" id="rvpw-panel-shortcode" role="tabpanel" aria-labelledby="rvpw-tab-shortcode" tabindex="0">
							<?php $this->render_shortcode_tab( $settings ); ?>
						</div>

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
		$this->field_toggle( 'load_via_ajax', esc_html__( 'Load via AJAX (cache friendly)', 'recently-viewed-products-for-woocommerce' ), $settings['load_via_ajax'], esc_html__( 'Recommended with full-page caching or a CDN. Outputs an empty placeholder that is filled per visitor after the page loads, and records views in the browser so the list stays accurate on cached pages.', 'recently-viewed-products-for-woocommerce' ) );
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
				'grid' => esc_html__( 'Grid', 'recently-viewed-products-for-woocommerce' ),
				'list' => esc_html__( 'List', 'recently-viewed-products-for-woocommerce' ),
			),
			esc_html__( 'Grid arranges products in responsive columns. List stacks them in compact rows.', 'recently-viewed-products-for-woocommerce' )
		);
		$this->field_number( 'mobile_columns', esc_html__( 'Mobile columns', 'recently-viewed-products-for-woocommerce' ), $settings['mobile_columns'], 1, 2, esc_html__( 'Columns on phones (grid layout). 1–2.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'tablet_columns', esc_html__( 'Tablet columns', 'recently-viewed-products-for-woocommerce' ), $settings['tablet_columns'], 1, 4, esc_html__( 'Columns on tablets (grid layout). 1–4.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_number( 'desktop_columns', esc_html__( 'Desktop columns', 'recently-viewed-products-for-woocommerce' ), $settings['desktop_columns'], 1, 6, esc_html__( 'Columns on desktops (grid layout). 1–6.', 'recently-viewed-products-for-woocommerce' ) );
		$this->field_text( 'custom_css_class', esc_html__( 'Custom CSS class', 'recently-viewed-products-for-woocommerce' ), $settings['custom_css_class'], esc_html__( 'Optional. Add one or more space-separated classes to the wrapper for custom styling.', 'recently-viewed-products-for-woocommerce' ) );
		$this->close_card();
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

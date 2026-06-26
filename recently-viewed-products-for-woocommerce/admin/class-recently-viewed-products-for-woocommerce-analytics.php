<?php
/**
 * Analytics / reporting screen.
 *
 * Read-only report built from the plugin's own view-count table, with an
 * optional "purchases" proxy taken from WooCommerce's order-product lookup
 * table when WooCommerce Analytics is available.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Analytics screen.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Analytics {

	/**
	 * Menu slug.
	 */
	const MENU_SLUG = 'rvpw-analytics';

	/**
	 * Plugin slug (asset handle base).
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Page hook suffix captured on registration, used to scope assets.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin slug.
	 * @param string $version Version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the submenu.
	 *
	 * @return void
	 */
	public function register_menu() {
		// Nest under the plugin's own top-level menu so Settings and Analytics
		// live together instead of as separate flat items in the WooCommerce menu.
		$this->page_hook = add_submenu_page(
			RVPW_Recently_Viewed_Products_For_Woocommerce_Admin::MENU_SLUG,
			esc_html__( 'Recently Viewed Analytics', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Analytics', 'recently-viewed-products-for-woocommerce' ),
			'manage_woocommerce',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue the shared admin stylesheet on this screen.
	 *
	 * @param string $hook_suffix Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( '' === $this->page_hook || $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( $this->plugin_name . '-admin', RVPW_PLUGIN_URL . 'admin/css/recently-viewed-products-for-woocommerce-admin.css', array(), $this->version );
	}

	/**
	 * Render the report.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter; no data mutation.
		$window  = isset( $_GET['window'] ) ? absint( wp_unslash( $_GET['window'] ) ) : 30;
		$window  = in_array( $window, array( 7, 30, 90 ), true ) ? $window : 30;
		$totals  = $this->get_totals();
		$top     = $this->get_top_viewed( 20 );
		$ids     = wp_list_pluck( $top, 'product_id' );
		$buys    = $this->get_purchase_counts( $ids, $window );
		$has_buy = ! empty( $buys ) || $this->purchase_table_exists();
		?>
		<div class="wrap rvpw-admin">
			<div class="rvpw-header">
				<div class="rvpw-header__brand">
					<span class="rvpw-header__icon dashicons dashicons-chart-bar" aria-hidden="true"></span>
					<div class="rvpw-header__text">
						<h1 class="rvpw-header__title"><?php esc_html_e( 'Recently Viewed Analytics', 'recently-viewed-products-for-woocommerce' ); ?></h1>
						<p class="rvpw-header__subtitle"><?php esc_html_e( 'Which products shoppers view the most, and how often those products sell.', 'recently-viewed-products-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="rvpw-layout">
				<div class="rvpw-main">
					<div class="rvpw-card">
						<h2 class="rvpw-card__title"><?php esc_html_e( 'Overview', 'recently-viewed-products-for-woocommerce' ); ?></h2>
						<ul class="rvpw-status">
							<li>
								<span class="rvpw-status__label"><?php esc_html_e( 'Total tracked views', 'recently-viewed-products-for-woocommerce' ); ?></span>
								<span class="rvpw-pill rvpw-pill--neutral"><?php echo esc_html( number_format_i18n( $totals['views'] ) ); ?></span>
							</li>
							<li>
								<span class="rvpw-status__label"><?php esc_html_e( 'Products tracked', 'recently-viewed-products-for-woocommerce' ); ?></span>
								<span class="rvpw-pill rvpw-pill--neutral"><?php echo esc_html( number_format_i18n( $totals['products'] ) ); ?></span>
							</li>
						</ul>
					</div>

					<div class="rvpw-card">
						<h2 class="rvpw-card__title"><?php esc_html_e( 'Most viewed products', 'recently-viewed-products-for-woocommerce' ); ?></h2>
						<?php if ( empty( $top ) ) : ?>
							<p class="rvpw-card__intro"><?php esc_html_e( 'No product views have been recorded yet.', 'recently-viewed-products-for-woocommerce' ); ?></p>
						<?php else : ?>
							<table class="widefat striped rvpw-report">
								<thead>
									<tr>
										<th><?php esc_html_e( '#', 'recently-viewed-products-for-woocommerce' ); ?></th>
										<th><?php esc_html_e( 'Product', 'recently-viewed-products-for-woocommerce' ); ?></th>
										<th><?php esc_html_e( 'Views', 'recently-viewed-products-for-woocommerce' ); ?></th>
										<th>
											<?php esc_html_e( 'Orders', 'recently-viewed-products-for-woocommerce' ); ?>
											<span class="rvpw-report__hint" title="<?php esc_attr_e( 'Distinct orders containing this product within the selected window.', 'recently-viewed-products-for-woocommerce' ); ?>">&#9432;</span>
										</th>
										<th><?php esc_html_e( 'Last viewed', 'recently-viewed-products-for-woocommerce' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $top as $index => $row ) : ?>
										<?php
										$product_id = absint( $row['product_id'] );
										$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
										$name       = $product ? $product->get_name() : sprintf( /* translators: %d: product ID. */ esc_html__( '#%d (removed)', 'recently-viewed-products-for-woocommerce' ), $product_id );
										$edit_link  = get_edit_post_link( $product_id );
										$orders     = isset( $buys[ $product_id ] ) ? (int) $buys[ $product_id ] : 0;
										?>
										<tr>
											<td><?php echo esc_html( $index + 1 ); ?></td>
											<td>
												<?php if ( $edit_link && $product ) : ?>
													<a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $name ); ?></a>
												<?php else : ?>
													<?php echo esc_html( $name ); ?>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( number_format_i18n( (int) $row['view_count'] ) ); ?></td>
											<td><?php echo $has_buy ? esc_html( number_format_i18n( $orders ) ) : '&mdash;'; ?></td>
											<td><?php echo esc_html( $this->format_date( $row['last_viewed'] ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<p class="rvpw-field__desc">
								<?php
								if ( $has_buy ) {
									printf(
										/* translators: %d: number of days. */
										esc_html__( 'Orders column counts distinct orders containing the product in the last %d days (from WooCommerce Analytics data). It is a proxy, not click-through attribution.', 'recently-viewed-products-for-woocommerce' ),
										absint( $window )
									);
								} else {
									esc_html_e( 'Order figures are unavailable because WooCommerce Analytics lookup tables were not found.', 'recently-viewed-products-for-woocommerce' );
								}
								?>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<aside class="rvpw-sidebar">
					<div class="rvpw-card rvpw-card--sidebar">
						<h2 class="rvpw-card__title"><?php esc_html_e( 'Attribution window', 'recently-viewed-products-for-woocommerce' ); ?></h2>
						<p class="rvpw-card__intro"><?php esc_html_e( 'Period used for the Orders column.', 'recently-viewed-products-for-woocommerce' ); ?></p>
						<ul class="rvpw-list">
							<?php foreach ( array( 7, 30, 90 ) as $option ) : ?>
								<li>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'window' => $option ), admin_url( 'admin.php' ) ) ); ?>" <?php echo $window === $option ? 'style="font-weight:600;"' : ''; ?>>
										<?php
										/* translators: %d: number of days. */
										echo esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $option, 'recently-viewed-products-for-woocommerce' ), $option ) );
										?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</aside>
			</div>
		</div>
		<?php
	}

	/**
	 * Totals: sum of views and product count.
	 *
	 * @return array{views:int,products:int}
	 */
	private function get_totals() {
		global $wpdb;

		$table = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_view_counts();

		// phpcs:disable WordPress.DB
		$views    = (int) $wpdb->get_var( "SELECT COALESCE(SUM(view_count),0) FROM {$table}" );
		$products = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		// phpcs:enable WordPress.DB

		return array(
			'views'    => $views,
			'products' => $products,
		);
	}

	/**
	 * Top viewed product rows.
	 *
	 * @param int $limit Limit.
	 * @return array<int,array>
	 */
	private function get_top_viewed( $limit ) {
		global $wpdb;

		$table = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_view_counts();

		// phpcs:disable WordPress.DB
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, view_count, last_viewed FROM {$table} ORDER BY view_count DESC, last_viewed DESC LIMIT %d",
				max( 1, absint( $limit ) )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Whether the WooCommerce order-product lookup table exists.
	 *
	 * @return bool
	 */
	private function purchase_table_exists() {
		global $wpdb;

		$lookup = $wpdb->prefix . 'wc_order_product_lookup';

		// phpcs:disable WordPress.DB
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lookup ) );
		// phpcs:enable WordPress.DB

		return $found === $lookup;
	}

	/**
	 * Distinct order counts per product within the window (proxy for purchases).
	 *
	 * @param int[] $product_ids Product IDs.
	 * @param int   $days        Window in days.
	 * @return array<int,int>
	 */
	private function get_purchase_counts( $product_ids, $days ) {
		global $wpdb;

		$product_ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
		if ( empty( $product_ids ) || ! $this->purchase_table_exists() ) {
			return array();
		}

		$lookup       = $wpdb->prefix . 'wc_order_product_lookup';
		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$cutoff       = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
		$args         = array_merge( $product_ids, array( $cutoff ) );

		// phpcs:disable WordPress.DB
		$query = "SELECT product_id, COUNT(DISTINCT order_id) AS orders FROM {$lookup} WHERE product_id IN ({$placeholders}) AND date_created >= %s GROUP BY product_id";
		$rows  = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
		// phpcs:enable WordPress.DB

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ absint( $row['product_id'] ) ] = (int) $row['orders'];
		}

		return $map;
	}

	/**
	 * Format a stored UTC datetime for display in the site timezone.
	 *
	 * @param string $datetime MySQL datetime (UTC).
	 * @return string
	 */
	private function format_date( $datetime ) {
		if ( empty( $datetime ) ) {
			return '&mdash;';
		}

		$timestamp = strtotime( $datetime . ' UTC' );
		if ( ! $timestamp ) {
			return '&mdash;';
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}

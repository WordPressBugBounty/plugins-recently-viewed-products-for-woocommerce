<?php
/**
 * Public functionality.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class RVPW_Recently_Viewed_Products_For_Woocommerce_Public {

	/**
	 * Plugin name.
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
	 * Cached settings for the current request.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Whether the front-end script has already been localized.
	 *
	 * @var bool
	 */
	private $localized = false;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		add_shortcode( 'rvpw_products', array( $this, 'shortcode' ) );
	}

	/**
	 * Register (and, for AJAX mode, enqueue) assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( $this->plugin_name . '-public', plugin_dir_url( __FILE__ ) . 'css/recently-viewed-products-for-woocommerce-public.css', array(), $this->version );
		wp_register_script( $this->plugin_name . '-public', plugin_dir_url( __FILE__ ) . 'js/recently-viewed-products-for-woocommerce-public.js', array(), $this->version, true );

		// In AJAX/cache mode, load the tracking script on product pages so views
		// are recorded even when the page itself is served from a full-page cache.
		$settings = $this->get_settings();
		if ( 'yes' === $settings['load_via_ajax'] && 'yes' === $settings['enabled'] && function_exists( 'is_product' ) && is_product() ) {
			$this->enqueue_front_assets();
		}
	}

	/**
	 * Register the REST route used to fetch a visitor's recently viewed products.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'rvpw/v1',
			'/recently-viewed',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_recently_viewed' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'context' => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * REST callback: render the current visitor's recently viewed products.
	 *
	 * The visitor's cookie is sent with the same-origin request, so the markup
	 * is built from their own history. The response is marked non-cacheable.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_recently_viewed( $request ) {
		$context = $this->sanitize_ajax_context( $request->get_param( 'context' ) );
		$html    = $this->build_products_markup( $context );

		$response = rest_ensure_response( array( 'html' => $html ) );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	/**
	 * Register automatic product-page placement.
	 *
	 * @return void
	 */
	public function register_product_page_placement() {
		$settings = $this->get_settings();
		if ( 'yes' !== $settings['enabled'] || 'yes' !== $settings['show_on_product_page'] || 'disable' === $settings['placement'] ) {
			return;
		}

		add_action( $settings['placement'], array( $this, 'render_on_product_page' ), 25 );

		// Enqueue the stylesheet in <head> for placed output to avoid a flash of unstyled content.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_style' ) );
	}

	/**
	 * Enqueue the public stylesheet.
	 *
	 * @return void
	 */
	public function enqueue_public_style() {
		wp_enqueue_style( $this->plugin_name . '-public' );
	}

	/**
	 * Track view (server-side, runs on cache misses and for logged-in users).
	 *
	 * @return void
	 */
	public function track_product_view() {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$settings = $this->get_settings();
		if ( 'yes' !== $settings['enabled'] ) {
			return;
		}

		if ( is_user_logged_in() && 'yes' !== $settings['track_logged_in_users'] ) {
			return;
		}

		if ( ! is_user_logged_in() && 'yes' !== $settings['track_guests'] ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id ) {
			return;
		}

		$product_ids = $this->get_cookie_product_ids();
		$product_ids = array_diff( $product_ids, array( $product_id ) );
		array_unshift( $product_ids, $product_id );
		$product_ids = array_slice( array_values( array_unique( array_map( 'absint', $product_ids ) ) ), 0, absint( $settings['max_stored_products'] ) );

		// Defensive: avoids a WP_DEBUG notice from wc_setcookie() if output already started.
		if ( headers_sent() ) {
			return;
		}

		wc_setcookie( $this->get_cookie_name(), implode( '|', $product_ids ), time() + ( DAY_IN_SECONDS * absint( $settings['cookie_expiry_days'] ) ) );
	}

	/**
	 * Render content on the single product page (automatic placement).
	 *
	 * @return void
	 */
	public function render_on_product_page() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped within build_products_markup()/the placeholder.
		echo $this->render( array(), true );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$settings = $this->get_settings();
		$defaults = array(
			'title'        => $settings['section_title'],
			'limit'        => $settings['display_limit'],
			'columns'      => $settings['desktop_columns'],
			'layout'       => $settings['layout'],
			'show_image'   => $settings['show_image'],
			'show_title'   => $settings['show_title'],
			'show_price'   => $settings['show_price'],
			'show_rating'  => $settings['show_rating'],
			'show_cart'    => $settings['show_add_to_cart'],
			'hide_current' => $settings['hide_current_product'],
			'class'        => $settings['custom_css_class'],
		);

		$atts = shortcode_atts( $defaults, $atts, 'rvpw_products' );

		$context = array(
			'section_title'        => sanitize_text_field( $atts['title'] ),
			'display_limit'        => max( 1, min( 24, absint( $atts['limit'] ) ) ),
			'desktop_columns'      => max( 1, min( 6, absint( $atts['columns'] ) ) ),
			'layout'               => in_array( $atts['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $atts['layout'] : 'grid',
			'show_image'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_image'] ),
			'show_title'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_title'] ),
			'show_price'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_price'] ),
			'show_rating'          => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_rating'] ),
			'show_add_to_cart'     => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_cart'] ),
			'hide_current_product' => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['hide_current'] ),
			'custom_css_class'     => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_css_classes( $atts['class'] ),
		);

		return $this->render( $context, false );
	}

	/**
	 * Decide between server-side markup and a cache-friendly AJAX placeholder.
	 *
	 * @param array $context      Display overrides.
	 * @param bool  $is_placement Whether this is automatic product-page output.
	 * @return string Escaped HTML.
	 */
	private function render( $context, $is_placement ) {
		$settings = wp_parse_args( $context, $this->get_settings() );

		// Resolve the product to hide here (the REST request is not is_singular()).
		$exclude_id = 0;
		if ( 'yes' === $settings['hide_current_product'] && is_singular( 'product' ) ) {
			$exclude_id = absint( get_queried_object_id() );
		}

		$context['exclude_id']   = $exclude_id;
		$context['is_placement'] = $is_placement;

		if ( 'yes' === $settings['load_via_ajax'] && 'yes' === $settings['enabled'] ) {
			return $this->ajax_placeholder( $context );
		}

		return $this->build_products_markup( $context );
	}

	/**
	 * Output a cacheable placeholder that JavaScript fills in per visitor.
	 *
	 * @param array $context Display overrides (carried to the REST request).
	 * @return string
	 */
	private function ajax_placeholder( $context ) {
		$this->enqueue_front_assets();

		return sprintf(
			'<div class="rvpw-ajax" data-rvpw-context="%s" aria-busy="true"></div>',
			esc_attr( wp_json_encode( $context ) )
		);
	}

	/**
	 * Build products markup.
	 *
	 * @param array $context Optional display overrides. Recognized control keys:
	 *                       'is_placement' (suppress empty message) and 'exclude_id'
	 *                       (product to hide; set by the caller because REST requests
	 *                       are not is_singular()).
	 * @return string Escaped HTML.
	 */
	private function build_products_markup( $context ) {
		$is_placement = ! empty( $context['is_placement'] );
		$exclude_id   = isset( $context['exclude_id'] ) ? absint( $context['exclude_id'] ) : 0;
		unset( $context['is_placement'], $context['exclude_id'] );

		$settings = wp_parse_args( $context, $this->get_settings() );
		$ids      = $this->get_cookie_product_ids();

		if ( ! $exclude_id && 'yes' === $settings['hide_current_product'] && is_singular( 'product' ) ) {
			$exclude_id = absint( get_queried_object_id() );
		}
		if ( $exclude_id ) {
			$ids = array_diff( $ids, array( $exclude_id ) );
		}

		$limit = absint( $settings['display_limit'] );

		// Resolve products first, then cap to the limit, so hidden/trashed
		// products do not silently shrink the displayed count.
		$products = array();
		foreach ( $ids as $product_id ) {
			if ( count( $products ) >= $limit ) {
				break;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}

			$products[] = $product;
		}

		if ( empty( $products ) ) {
			if ( ! $is_placement && '' !== trim( (string) $settings['empty_message'] ) ) {
				return '<p class="rvpw-empty">' . esc_html( $settings['empty_message'] ) . '</p>';
			}
			return '';
		}

		// Ensure styling is present for both shortcode and placement output.
		wp_enqueue_style( $this->plugin_name . '-public' );

		$layout       = in_array( $settings['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $settings['layout'] : 'grid';
		$custom_class = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_css_classes( $settings['custom_css_class'] );

		// Keep our own scoped wrapper only. Do NOT add the `woocommerce` class here:
		// it pulls WooCommerce's `ul.products` clearfix pseudo-elements and float
		// rules onto our grid, which inserts phantom grid cells and breaks the layout.
		$classes = trim( 'rvpw-products rvpw-layout-' . $layout . ' ' . $custom_class );
		$style   = sprintf(
			'--rvpw-mobile-columns:%1$d;--rvpw-tablet-columns:%2$d;--rvpw-desktop-columns:%3$d;',
			absint( $settings['mobile_columns'] ),
			absint( $settings['tablet_columns'] ),
			absint( $settings['desktop_columns'] )
		);

		ob_start();
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $settings['section_title'] ? $settings['section_title'] : __( 'Recently viewed products', 'recently-viewed-products-for-woocommerce' ) ); ?>">
			<?php if ( ! empty( $settings['section_title'] ) ) : ?>
				<h2 class="rvpw-heading"><?php echo esc_html( $settings['section_title'] ); ?></h2>
			<?php endif; ?>
			<ul class="rvpw-grid">
				<?php foreach ( $products as $product ) : ?>
					<li class="rvpw-product">
						<a class="rvpw-product__link" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
							<?php if ( 'yes' === $settings['show_image'] ) : ?>
								<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
							<?php endif; ?>
							<?php if ( 'yes' === $settings['show_title'] ) : ?>
								<h3 class="woocommerce-loop-product__title"><?php echo esc_html( $product->get_name() ); ?></h3>
							<?php endif; ?>
						</a>
						<?php if ( 'yes' === $settings['show_price'] && '' !== $product->get_price_html() ) : ?>
							<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_rating'] && $product->get_rating_count() > 0 ) : ?>
							<?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating() ) ); ?>
						<?php endif; ?>
						<?php if ( 'yes' === $settings['show_add_to_cart'] && $product->is_purchasable() ) : ?>
							<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="button add_to_cart_button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>" rel="nofollow"><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Sanitize the display context received from an AJAX request.
	 *
	 * @param mixed $raw JSON string or array from the client.
	 * @return array
	 */
	private function sanitize_ajax_context( $raw ) {
		if ( is_string( $raw ) ) {
			$data = json_decode( wp_unslash( $raw ), true );
		} else {
			$data = $raw;
		}
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$context = array();

		if ( isset( $data['section_title'] ) ) {
			$context['section_title'] = sanitize_text_field( $data['section_title'] );
		}
		if ( isset( $data['display_limit'] ) ) {
			$context['display_limit'] = max( 1, min( 24, absint( $data['display_limit'] ) ) );
		}
		if ( isset( $data['desktop_columns'] ) ) {
			$context['desktop_columns'] = max( 1, min( 6, absint( $data['desktop_columns'] ) ) );
		}
		if ( isset( $data['layout'] ) ) {
			$context['layout'] = in_array( $data['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $data['layout'] : 'grid';
		}
		foreach ( array( 'show_image', 'show_title', 'show_price', 'show_rating', 'show_add_to_cart', 'hide_current_product' ) as $flag ) {
			if ( isset( $data[ $flag ] ) ) {
				$context[ $flag ] = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $data[ $flag ] );
			}
		}
		if ( isset( $data['custom_css_class'] ) ) {
			$context['custom_css_class'] = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_css_classes( $data['custom_css_class'] );
		}
		if ( isset( $data['exclude_id'] ) ) {
			$context['exclude_id'] = absint( $data['exclude_id'] );
		}
		$context['is_placement'] = ! empty( $data['is_placement'] );

		return $context;
	}

	/**
	 * Enqueue the public CSS + JS and localize the front-end config (once).
	 *
	 * @return void
	 */
	private function enqueue_front_assets() {
		wp_enqueue_style( $this->plugin_name . '-public' );
		wp_enqueue_script( $this->plugin_name . '-public' );

		if ( ! $this->localized ) {
			wp_localize_script( $this->plugin_name . '-public', 'rvpwData', $this->get_localized_data() );
			$this->localized = true;
		}
	}

	/**
	 * Front-end config passed to the public script.
	 *
	 * Every value here is identical for all visitors of a given URL, so it is
	 * safe to embed in a cached page.
	 *
	 * @return array
	 */
	private function get_localized_data() {
		$settings   = $this->get_settings();
		$product_id = ( function_exists( 'is_product' ) && is_product() ) ? absint( get_queried_object_id() ) : 0;

		return array(
			'restUrl'      => esc_url_raw( rest_url( 'rvpw/v1/recently-viewed' ) ),
			'productId'    => $product_id,
			'cookieName'   => $this->get_cookie_name(),
			'maxStored'    => absint( $settings['max_stored_products'] ),
			'expiryDays'   => absint( $settings['cookie_expiry_days'] ),
			'enabled'      => ( 'yes' === $settings['enabled'] ),
			'trackGuests'  => ( 'yes' === $settings['track_guests'] ),
		);
	}

	/**
	 * Read cookie IDs.
	 *
	 * @return array
	 */
	private function get_cookie_product_ids() {
		$cookie_name = $this->get_cookie_name();
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return array();
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		if ( '' === $cookie_value ) {
			return array();
		}

		$ids = array_filter( array_map( 'absint', explode( '|', $cookie_value ) ) );
		$ids = array_values( array_unique( $ids ) );

		$settings = $this->get_settings();

		return array_slice( $ids, 0, absint( $settings['max_stored_products'] ) );
	}

	/**
	 * Get cookie name.
	 *
	 * @return string
	 */
	private function get_cookie_name() {
		return 'rvpw_recently_viewed_' . get_current_blog_id();
	}

	/**
	 * Get settings with defaults (cached per request).
	 *
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		}

		return $this->settings;
	}
}

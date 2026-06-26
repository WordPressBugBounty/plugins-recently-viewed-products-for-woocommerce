<?php
/**
 * Display facade.
 *
 * The one place that turns a set of display overrides into final HTML:
 * merge with settings → resolve products via the Provider → render via the
 * Renderer. Every surface (shortcode, automatic placement, widget, block,
 * Elementor) calls this so behavior can never diverge.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Display facade.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Display {

	/**
	 * Build the section HTML.
	 *
	 * @param array $overrides    Display arg overrides (already sanitized by the caller).
	 * @param bool  $is_placement Whether this is automatic product-page output (suppresses empty message).
	 * @return string Escaped HTML.
	 */
	public static function section( array $overrides = array(), $is_placement = false ) {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$args     = wp_parse_args( $overrides, $settings );

		$exclude_product_id = 0;
		if ( 'yes' === $args['hide_current_product'] && is_singular( 'product' ) ) {
			$exclude_product_id = get_queried_object_id();
		}

		$products = RVPW_Recently_Viewed_Products_For_Woocommerce_Provider::get_products(
			array(
				'source'  => $args['source'],
				'sort'    => $args['sort_order'],
				'limit'   => absint( $args['display_limit'] ),
				'user_id' => get_current_user_id(),
				'filters' => array(
					'in_stock_only'      => 'yes' === ( $args['filter_in_stock_only'] ?? 'no' ),
					'exclude_purchased'  => 'yes' === ( $args['filter_exclude_purchased'] ?? 'no' ),
					'hide_free'          => 'yes' === ( $args['filter_hide_free'] ?? 'no' ),
					'exclude_categories' => isset( $args['filter_exclude_categories'] ) ? (array) $args['filter_exclude_categories'] : array(),
					'exclude_product_id' => $exclude_product_id,
				),
			)
		);

		$args['is_placement'] = $is_placement;

		return RVPW_Recently_Viewed_Products_For_Woocommerce_Renderer::render( $args, $products );
	}
}

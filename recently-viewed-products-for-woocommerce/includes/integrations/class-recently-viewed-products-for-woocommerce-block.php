<?php
/**
 * Gutenberg block (dynamic, no build step).
 *
 * Registers a block whose front-end markup is produced by the same Display
 * facade as the shortcode, guaranteeing identical output. The editor uses
 * ServerSideRender for a live preview.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Block registrar.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Block {

	/**
	 * Register the block and its editor assets.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'rvpw-block-editor',
			RVPW_PLUGIN_URL . 'blocks/recently-viewed/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			RVPW_VERSION,
			true
		);

		wp_register_style(
			'rvpw-block-editor-style',
			RVPW_PLUGIN_URL . 'blocks/recently-viewed/editor.css',
			array(),
			RVPW_VERSION
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'rvpw-block-editor', 'recently-viewed-products-for-woocommerce' );
		}

		register_block_type(
			RVPW_PLUGIN_DIR . 'blocks/recently-viewed',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Server render callback (front-end and editor preview).
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();

		$overrides = array(
			'display_limit'    => isset( $attributes['limit'] ) ? max( 1, min( 24, absint( $attributes['limit'] ) ) ) : 4,
			'desktop_columns'  => isset( $attributes['columns'] ) ? max( 1, min( 6, absint( $attributes['columns'] ) ) ) : 4,
			'layout'           => isset( $attributes['layout'] ) && in_array( $attributes['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $attributes['layout'] : 'grid',
			'sort_order'       => isset( $attributes['sort'] ) && in_array( $attributes['sort'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sorts(), true ) ? $attributes['sort'] : 'recent',
			'show_image'       => empty( $attributes['showImage'] ) ? 'no' : 'yes',
			'show_title'       => empty( $attributes['showTitle'] ) ? 'no' : 'yes',
			'show_price'       => empty( $attributes['showPrice'] ) ? 'no' : 'yes',
			'show_rating'      => empty( $attributes['showRating'] ) ? 'no' : 'yes',
			'show_add_to_cart' => empty( $attributes['showCart'] ) ? 'no' : 'yes',
		);

		if ( ! empty( $attributes['title'] ) ) {
			$overrides['section_title'] = sanitize_text_field( $attributes['title'] );
		}

		return RVPW_Recently_Viewed_Products_For_Woocommerce_Display::section( $overrides, false );
	}
}

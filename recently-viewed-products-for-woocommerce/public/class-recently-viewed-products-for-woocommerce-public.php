<?php
/**
 * Public functionality.
 *
 * Thin adapter: tracks views via the History_Store, resolves products via the
 * Provider, and renders via the Renderer. It no longer queries products or
 * builds markup directly so that every display surface stays in sync.
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
	 * Register assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( $this->plugin_name . '-public', plugin_dir_url( __FILE__ ) . 'css/recently-viewed-products-for-woocommerce-public.css', array(), $this->version );
		wp_register_style( $this->plugin_name . '-carousel', plugin_dir_url( __FILE__ ) . 'css/recently-viewed-products-for-woocommerce-carousel.css', array( $this->plugin_name . '-public' ), $this->version );
		wp_register_script( $this->plugin_name . '-carousel', plugin_dir_url( __FILE__ ) . 'js/recently-viewed-products-for-woocommerce-carousel.js', array(), $this->version, true );
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
	 * Enqueue the public stylesheet (and carousel assets when needed).
	 *
	 * @return void
	 */
	public function enqueue_public_style() {
		wp_enqueue_style( $this->plugin_name . '-public' );

		$settings = $this->get_settings();
		if ( 'carousel' === $settings['layout'] ) {
			wp_enqueue_style( $this->plugin_name . '-carousel' );
			wp_enqueue_script( $this->plugin_name . '-carousel' );
		}
	}

	/**
	 * Output the admin-defined custom CSS in the document head.
	 *
	 * @return void
	 */
	public function output_custom_css() {
		$settings = $this->get_settings();
		$css      = isset( $settings['custom_css'] ) ? RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_custom_css( $settings['custom_css'] ) : '';

		if ( '' === $css ) {
			return;
		}

		// $css is plain CSS: all tags and dangerous constructs are stripped by the sanitizer.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized CSS, output inside a style element.
		echo "\n<style id=\"rvpw-custom-css\">\n" . $css . "\n</style>\n";
	}

	/**
	 * Track view.
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

		RVPW_Recently_Viewed_Products_For_Woocommerce_History_Store::record_view( $product_id, $settings );
	}

	/**
	 * Render content on the single product page (automatic placement).
	 *
	 * @return void
	 */
	public function render_on_product_page() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped within the Renderer.
		echo RVPW_Recently_Viewed_Products_For_Woocommerce_Display::section( array(), true );
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
			'sort'         => $settings['sort_order'],
			'source'       => $settings['source'],
			'show_image'   => $settings['show_image'],
			'show_title'   => $settings['show_title'],
			'show_price'   => $settings['show_price'],
			'show_rating'  => $settings['show_rating'],
			'show_cart'    => $settings['show_add_to_cart'],
			'hide_current' => $settings['hide_current_product'],
			'class'        => $settings['custom_css_class'],
		);

		$atts = shortcode_atts( $defaults, $atts, 'rvpw_products' );

		$overrides = array(
			'section_title'        => sanitize_text_field( $atts['title'] ),
			'display_limit'        => max( 1, min( 24, absint( $atts['limit'] ) ) ),
			'desktop_columns'      => max( 1, min( 6, absint( $atts['columns'] ) ) ),
			'layout'               => in_array( $atts['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $atts['layout'] : 'grid',
			'sort_order'           => in_array( $atts['sort'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sorts(), true ) ? $atts['sort'] : $settings['sort_order'],
			'source'               => in_array( $atts['source'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sources(), true ) ? $atts['source'] : $settings['source'],
			'show_image'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_image'] ),
			'show_title'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_title'] ),
			'show_price'           => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_price'] ),
			'show_rating'          => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_rating'] ),
			'show_add_to_cart'     => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['show_cart'] ),
			'hide_current_product' => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::normalize_yes_no( $atts['hide_current'] ),
			'custom_css_class'     => RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_css_classes( $atts['class'] ),
		);

		return RVPW_Recently_Viewed_Products_For_Woocommerce_Display::section( $overrides, false );
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

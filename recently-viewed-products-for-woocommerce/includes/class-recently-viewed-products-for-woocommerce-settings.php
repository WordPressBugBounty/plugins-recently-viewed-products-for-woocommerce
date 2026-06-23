<?php
/**
 * Centralized settings handling.
 *
 * Single source of truth for plugin defaults, reading and class sanitization.
 * Previously these defaults were duplicated across the activator, admin and
 * public classes which made them easy to drift out of sync.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.2.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Settings repository.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Settings {

	/**
	 * Option key used to persist settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'rvpw_settings';

	/**
	 * Allowed layout values.
	 *
	 * @return array
	 */
	public static function get_allowed_layouts() {
		return array( 'grid', 'list' );
	}

	/**
	 * Allowed automatic placement hooks.
	 *
	 * @return array
	 */
	public static function get_allowed_placements() {
		return array(
			'disable',
			'woocommerce_before_single_product_summary',
			'woocommerce_after_single_product_summary',
			'woocommerce_after_single_product',
		);
	}

	/**
	 * Default settings.
	 *
	 * Translatable strings are resolved lazily on each call which is safe
	 * because every caller runs on or after the `init` hook.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'enabled'                  => 'yes',
			'track_guests'             => 'yes',
			'track_logged_in_users'    => 'no',
			'load_via_ajax'            => 'no',
			'cookie_expiry_days'       => 30,
			'max_stored_products'      => 20,
			'show_on_product_page'     => 'yes',
			'section_title'            => __( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			'display_limit'            => 4,
			'layout'                   => 'grid',
			'placement'                => 'woocommerce_after_single_product_summary',
			'hide_current_product'     => 'yes',
			'show_image'               => 'yes',
			'show_title'               => 'yes',
			'show_price'               => 'yes',
			'show_rating'              => 'no',
			'show_add_to_cart'         => 'yes',
			'mobile_columns'           => 2,
			'tablet_columns'           => 3,
			'desktop_columns'          => 4,
			'custom_css_class'         => '',
			'delete_data_on_uninstall' => 'no',
			'empty_message'            => __( 'You have not viewed any products yet.', 'recently-viewed-products-for-woocommerce' ),
		);
	}

	/**
	 * Retrieve settings merged over defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = self::get_defaults();
		$saved    = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Sanitize a space separated list of CSS classes.
	 *
	 * Unlike a single call to sanitize_html_class(), this preserves multiple
	 * classes (e.g. "foo bar") by sanitizing each token individually.
	 *
	 * @param string $value Raw class string.
	 * @return string Sanitized, space separated class list.
	 */
	public static function sanitize_css_classes( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$parts = preg_split( '/\s+/', trim( $value ) );

		if ( empty( $parts ) ) {
			return '';
		}

		$parts = array_filter( array_map( 'sanitize_html_class', $parts ) );

		return implode( ' ', array_unique( $parts ) );
	}

	/**
	 * Normalize a checkbox/yes-no style value into 'yes' or 'no'.
	 *
	 * @param mixed $value Raw value.
	 * @return string 'yes' or 'no'.
	 */
	public static function normalize_yes_no( $value ) {
		$truthy = array( 'yes', 'true', '1', 1, true, 'on' );

		return in_array( $value, $truthy, true ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize a full settings payload, falling back to defaults per field.
	 *
	 * @param array $input Raw, unslashed input.
	 * @return array Sanitized settings.
	 */
	public static function sanitize( $input ) {
		$defaults = self::get_defaults();
		$input    = is_array( $input ) ? $input : array();
		$settings = $defaults;

		$settings['enabled']               = isset( $input['enabled'] ) ? 'yes' : 'no';
		$settings['track_guests']          = isset( $input['track_guests'] ) ? 'yes' : 'no';
		$settings['track_logged_in_users'] = isset( $input['track_logged_in_users'] ) ? 'yes' : 'no';
		$settings['load_via_ajax']         = isset( $input['load_via_ajax'] ) ? 'yes' : 'no';
		$settings['show_on_product_page']  = isset( $input['show_on_product_page'] ) ? 'yes' : 'no';
		$settings['hide_current_product']  = isset( $input['hide_current_product'] ) ? 'yes' : 'no';
		$settings['show_image']            = isset( $input['show_image'] ) ? 'yes' : 'no';
		$settings['show_title']            = isset( $input['show_title'] ) ? 'yes' : 'no';
		$settings['show_price']            = isset( $input['show_price'] ) ? 'yes' : 'no';
		$settings['show_rating']           = isset( $input['show_rating'] ) ? 'yes' : 'no';
		$settings['show_add_to_cart']      = isset( $input['show_add_to_cart'] ) ? 'yes' : 'no';
		$settings['delete_data_on_uninstall'] = isset( $input['delete_data_on_uninstall'] ) ? 'yes' : 'no';

		$settings['cookie_expiry_days']  = max( 1, min( 365, absint( $input['cookie_expiry_days'] ?? $defaults['cookie_expiry_days'] ) ) );
		$settings['max_stored_products'] = max( 1, min( 100, absint( $input['max_stored_products'] ?? $defaults['max_stored_products'] ) ) );
		$settings['display_limit']       = max( 1, min( 24, absint( $input['display_limit'] ?? $defaults['display_limit'] ) ) );
		$settings['mobile_columns']      = max( 1, min( 2, absint( $input['mobile_columns'] ?? $defaults['mobile_columns'] ) ) );
		$settings['tablet_columns']      = max( 1, min( 4, absint( $input['tablet_columns'] ?? $defaults['tablet_columns'] ) ) );
		$settings['desktop_columns']     = max( 1, min( 6, absint( $input['desktop_columns'] ?? $defaults['desktop_columns'] ) ) );

		$section_title             = sanitize_text_field( $input['section_title'] ?? $defaults['section_title'] );
		$settings['section_title'] = '' === $section_title ? $defaults['section_title'] : $section_title;
		$settings['empty_message'] = sanitize_text_field( $input['empty_message'] ?? $defaults['empty_message'] );

		$layout              = $input['layout'] ?? $defaults['layout'];
		$settings['layout']  = in_array( $layout, self::get_allowed_layouts(), true ) ? $layout : $defaults['layout'];

		$placement              = $input['placement'] ?? $defaults['placement'];
		$settings['placement']  = in_array( $placement, self::get_allowed_placements(), true ) ? $placement : $defaults['placement'];

		$settings['custom_css_class'] = self::sanitize_css_classes( $input['custom_css_class'] ?? '' );

		return $settings;
	}
}

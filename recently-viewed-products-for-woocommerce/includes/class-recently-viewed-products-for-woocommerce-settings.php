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
		return array( 'grid', 'list', 'carousel' );
	}

	/**
	 * Allowed sort values.
	 *
	 * @return array
	 */
	public static function get_allowed_sorts() {
		return array( 'recent', 'most_viewed', 'price_asc', 'price_desc', 'random', 'date' );
	}

	/**
	 * Allowed history source values.
	 *
	 * @return array
	 */
	public static function get_allowed_sources() {
		return array( 'auto', 'cookie', 'db', 'most_viewed' );
	}

	/**
	 * Allowed coupon types.
	 *
	 * @return array
	 */
	public static function get_allowed_coupon_types() {
		return array( 'percent', 'fixed_cart' );
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
			'track_db_for_logged_in'   => 'no',
			'db_retention_days'        => 90,
			'cookie_expiry_days'       => 30,
			'max_stored_products'      => 20,
			'show_on_product_page'     => 'yes',
			'section_title'            => __( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			'display_limit'            => 4,
			'layout'                   => 'grid',
			'sort_order'               => 'recent',
			'source'                   => 'auto',
			'carousel_autoplay'        => 'yes',
			'carousel_autoplay_speed'  => 4000,
			'carousel_loop'            => 'yes',
			'carousel_arrows'          => 'yes',
			'carousel_dots'            => 'yes',
			'placement'                => 'woocommerce_after_single_product_summary',
			'hide_current_product'     => 'yes',
			'filter_in_stock_only'     => 'no',
			'filter_exclude_purchased' => 'no',
			'filter_hide_free'         => 'no',
			'filter_exclude_categories' => array(),
			'show_image'               => 'yes',
			'show_title'               => 'yes',
			'show_price'               => 'yes',
			'show_rating'              => 'no',
			'show_add_to_cart'         => 'yes',
			'mobile_columns'           => 2,
			'tablet_columns'           => 3,
			'desktop_columns'          => 4,
			'custom_css_class'         => '',
			'custom_css'               => '',
			'email_enabled'            => 'no',
			'email_inactivity_days'    => 7,
			'email_subject'            => __( 'Still thinking it over? Here are products you viewed', 'recently-viewed-products-for-woocommerce' ),
			'email_body'               => __( "Hi {first_name},\n\nYou recently looked at these products:\n\n{products}\n\n{coupon}\n\nSee you soon!", 'recently-viewed-products-for-woocommerce' ),
			'email_coupon_enabled'     => 'no',
			'email_coupon_amount'      => 10,
			'email_coupon_type'        => 'percent',
			'email_coupon_expiry_days' => 7,
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
		$settings['track_guests']           = isset( $input['track_guests'] ) ? 'yes' : 'no';
		$settings['track_logged_in_users']  = isset( $input['track_logged_in_users'] ) ? 'yes' : 'no';
		$settings['track_db_for_logged_in'] = isset( $input['track_db_for_logged_in'] ) ? 'yes' : 'no';
		$settings['show_on_product_page']  = isset( $input['show_on_product_page'] ) ? 'yes' : 'no';
		$settings['hide_current_product']  = isset( $input['hide_current_product'] ) ? 'yes' : 'no';
		$settings['filter_in_stock_only']     = isset( $input['filter_in_stock_only'] ) ? 'yes' : 'no';
		$settings['filter_exclude_purchased'] = isset( $input['filter_exclude_purchased'] ) ? 'yes' : 'no';
		$settings['filter_hide_free']         = isset( $input['filter_hide_free'] ) ? 'yes' : 'no';
		$settings['filter_exclude_categories'] = isset( $input['filter_exclude_categories'] ) ? array_values( array_filter( array_map( 'absint', (array) $input['filter_exclude_categories'] ) ) ) : array();
		$settings['show_image']            = isset( $input['show_image'] ) ? 'yes' : 'no';
		$settings['show_title']            = isset( $input['show_title'] ) ? 'yes' : 'no';
		$settings['show_price']            = isset( $input['show_price'] ) ? 'yes' : 'no';
		$settings['show_rating']           = isset( $input['show_rating'] ) ? 'yes' : 'no';
		$settings['show_add_to_cart']      = isset( $input['show_add_to_cart'] ) ? 'yes' : 'no';
		$settings['delete_data_on_uninstall'] = isset( $input['delete_data_on_uninstall'] ) ? 'yes' : 'no';

		$settings['cookie_expiry_days']  = max( 1, min( 365, absint( $input['cookie_expiry_days'] ?? $defaults['cookie_expiry_days'] ) ) );
		$settings['max_stored_products'] = max( 1, min( 100, absint( $input['max_stored_products'] ?? $defaults['max_stored_products'] ) ) );
		$settings['db_retention_days']   = max( 1, min( 730, absint( $input['db_retention_days'] ?? $defaults['db_retention_days'] ) ) );
		$settings['display_limit']       = max( 1, min( 24, absint( $input['display_limit'] ?? $defaults['display_limit'] ) ) );
		$settings['mobile_columns']      = max( 1, min( 2, absint( $input['mobile_columns'] ?? $defaults['mobile_columns'] ) ) );
		$settings['tablet_columns']      = max( 1, min( 4, absint( $input['tablet_columns'] ?? $defaults['tablet_columns'] ) ) );
		$settings['desktop_columns']     = max( 1, min( 6, absint( $input['desktop_columns'] ?? $defaults['desktop_columns'] ) ) );

		$section_title             = sanitize_text_field( $input['section_title'] ?? $defaults['section_title'] );
		$settings['section_title'] = '' === $section_title ? $defaults['section_title'] : $section_title;
		$settings['empty_message'] = sanitize_text_field( $input['empty_message'] ?? $defaults['empty_message'] );

		$layout              = $input['layout'] ?? $defaults['layout'];
		$settings['layout']  = in_array( $layout, self::get_allowed_layouts(), true ) ? $layout : $defaults['layout'];

		$sort                   = $input['sort_order'] ?? $defaults['sort_order'];
		$settings['sort_order'] = in_array( $sort, self::get_allowed_sorts(), true ) ? $sort : $defaults['sort_order'];

		$source             = $input['source'] ?? $defaults['source'];
		$settings['source'] = in_array( $source, self::get_allowed_sources(), true ) ? $source : $defaults['source'];

		$settings['carousel_autoplay']       = isset( $input['carousel_autoplay'] ) ? 'yes' : 'no';
		$settings['carousel_loop']           = isset( $input['carousel_loop'] ) ? 'yes' : 'no';
		$settings['carousel_arrows']         = isset( $input['carousel_arrows'] ) ? 'yes' : 'no';
		$settings['carousel_dots']           = isset( $input['carousel_dots'] ) ? 'yes' : 'no';
		$settings['carousel_autoplay_speed'] = max( 1000, min( 15000, absint( $input['carousel_autoplay_speed'] ?? $defaults['carousel_autoplay_speed'] ) ) );

		$placement              = $input['placement'] ?? $defaults['placement'];
		$settings['placement']  = in_array( $placement, self::get_allowed_placements(), true ) ? $placement : $defaults['placement'];

		$settings['custom_css_class'] = self::sanitize_css_classes( $input['custom_css_class'] ?? '' );
		$settings['custom_css']       = self::sanitize_custom_css( $input['custom_css'] ?? '' );

		$settings['email_enabled']            = isset( $input['email_enabled'] ) ? 'yes' : 'no';
		$settings['email_coupon_enabled']     = isset( $input['email_coupon_enabled'] ) ? 'yes' : 'no';
		$settings['email_inactivity_days']    = max( 1, min( 90, absint( $input['email_inactivity_days'] ?? $defaults['email_inactivity_days'] ) ) );
		$settings['email_coupon_expiry_days'] = max( 1, min( 365, absint( $input['email_coupon_expiry_days'] ?? $defaults['email_coupon_expiry_days'] ) ) );
		$settings['email_coupon_amount']      = max( 0, (float) ( $input['email_coupon_amount'] ?? $defaults['email_coupon_amount'] ) );
		$settings['email_subject']            = sanitize_text_field( $input['email_subject'] ?? $defaults['email_subject'] );
		$settings['email_body']               = wp_kses_post( $input['email_body'] ?? $defaults['email_body'] );

		$coupon_type                   = $input['email_coupon_type'] ?? $defaults['email_coupon_type'];
		$settings['email_coupon_type'] = in_array( $coupon_type, self::get_allowed_coupon_types(), true ) ? $coupon_type : $defaults['email_coupon_type'];

		return $settings;
	}

	/**
	 * Sanitize a raw custom-CSS string for safe output inside a <style> block.
	 *
	 * Strips any HTML and CSS constructs that could be used to break out of the
	 * style element or execute script. The result is plain CSS only.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	public static function sanitize_custom_css( $css ) {
		$css = is_scalar( $css ) ? (string) $css : '';

		// Remove any tags (including an attempt to close the style element).
		$css = wp_strip_all_tags( $css );

		// Remove dangerous CSS constructs.
		$css = preg_replace( '#@import\b#i', '', $css );
		$css = preg_replace( '#expression\s*\(#i', '', $css );
		$css = preg_replace( '#javascript\s*:#i', '', $css );
		$css = preg_replace( '#behavior\s*:#i', '', $css );
		$css = str_replace( array( '<', '>' ), '', $css );

		return trim( (string) $css );
	}
}

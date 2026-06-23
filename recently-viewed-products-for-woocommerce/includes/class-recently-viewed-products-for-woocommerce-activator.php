<?php
/**
 * Fired during activation.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class RVPW_Recently_Viewed_Products_For_Woocommerce_Activator {

	/**
	 * Activation logic.
	 *
	 * Seeds default settings without overwriting any value an existing
	 * install has already saved, keeping upgrades non-destructive.
	 *
	 * @return void
	 */
	public static function rvpw_activate() {
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-settings.php';

		$current = get_option( RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::OPTION_KEY, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		update_option(
			RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::OPTION_KEY,
			wp_parse_args( $current, RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_defaults() )
		);
	}
}

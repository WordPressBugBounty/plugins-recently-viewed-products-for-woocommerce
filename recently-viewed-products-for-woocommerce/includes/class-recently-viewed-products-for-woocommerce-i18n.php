<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://in.linkedin.com/in/maheshvajapara
 * @since      2.0.0
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.0.0
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/includes
 * @author     Mahesh Patel <p.mahesh8850@gmail.com>
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'recently-viewed-products-for-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}

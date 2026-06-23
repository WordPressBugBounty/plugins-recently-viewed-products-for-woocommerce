<?php
/**
 * Plugin Name:       Recently Viewed Product for WooCommerce
 * Plugin URI:        https://in.linkedin.com/in/maheshvajapara
 * Description:       Display and manage recently viewed WooCommerce products with shortcode and product-page placement controls.
 * Version:           2.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mahesh Patel
 * Author URI:        https://in.linkedin.com/in/maheshvajapara/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       recently-viewed-products-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:   10.8.1
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'RVPW_VERSION', '2.2.0' );
define( 'RVPW_PLUGIN_FILE', __FILE__ );
define( 'RVPW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RVPW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'RVPW_RECENTLY_VIEWED_PRODUCTS_FOR_WOOCOMMERCE_VERSION', RVPW_VERSION );

/**
 * Activation callback.
 *
 * @return void
 */
function rvpw_activate_recently_viewed_products_for_woocommerce() {
	require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-activator.php';
	RVPW_Recently_Viewed_Products_For_Woocommerce_Activator::rvpw_activate();
}

/**
 * Deactivation callback.
 *
 * @return void
 */
function rvpw_deactivate_recently_viewed_products_for_woocommerce() {
	require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-deactivator.php';
	RVPW_Recently_Viewed_Products_For_Woocommerce_Deactivator::rvpw_deactivate();
}

register_activation_hook( __FILE__, 'rvpw_activate_recently_viewed_products_for_woocommerce' );
register_deactivation_hook( __FILE__, 'rvpw_deactivate_recently_viewed_products_for_woocommerce' );

/**
 * Declare WooCommerce HPOS (custom order tables) compatibility.
 *
 * Registered at include time because WooCommerce fires `before_woocommerce_init`
 * on `plugins_loaded` at priority -1, which is earlier than this plugin's own
 * boot. The callback only runs when WooCommerce fires the action.
 *
 * @return void
 */
function rvpw_declare_hpos_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RVPW_PLUGIN_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'rvpw_declare_hpos_compatibility' );

require RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce.php';

/**
 * Start plugin.
 *
 * Runs on `plugins_loaded` so that every active plugin (including WooCommerce)
 * is fully loaded before we detect WooCommerce and register front-end hooks.
 * Initializing at file-include time would check for the WooCommerce class
 * before it is defined and silently skip the shortcode and placement
 * registrations.
 *
 * @return void
 */
function rvpw_run_recently_viewed_products_for_woocommerce() {
	$plugin = new RVPW_Recently_Viewed_Products_For_Woocommerce();
	$plugin->run();
}

add_action( 'plugins_loaded', 'rvpw_run_recently_viewed_products_for_woocommerce' );

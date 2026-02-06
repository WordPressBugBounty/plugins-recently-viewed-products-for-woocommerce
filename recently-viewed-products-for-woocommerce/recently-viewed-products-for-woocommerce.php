<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://in.linkedin.com/in/maheshvajapara
 * @since             2.0.0
 * @package           RVPW_Recently_Viewed_Products_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Recently Viewed Product for WooCommerce
 * Plugin URI:        https://in.linkedin.com/in/maheshvajapara
 * Description:       This plugin displayed Recently Viewed Products in detail page or you can use a Shortcode to display  anywhere in the site.
 * Version:           2.0.0
 * Author:            Mahesh Patel
 * Author URI:        https://in.linkedin.com/in/maheshvajapara/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       recently-viewed-products-for-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 2.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RVPW_RECENTLY_VIEWED_PRODUCTS_FOR_WOOCOMMERCE_VERSION', '2.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-recently-viewed-products-for-woocommerce-activator.php
 */
function rvpw_activate_recently_viewed_products_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-recently-viewed-products-for-woocommerce-activator.php';
	RVPW_Recently_Viewed_Products_For_Woocommerce_Activator::rvpw_activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-recently-viewed-products-for-woocommerce-deactivator.php
 */
function rvpw_deactivate_recently_viewed_products_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-recently-viewed-products-for-woocommerce-deactivator.php';
	RVPW_Recently_Viewed_Products_For_Woocommerce_Deactivator::rvpw_deactivate();
}

register_activation_hook( __FILE__, 'rvpw_activate_recently_viewed_products_for_woocommerce' );
register_deactivation_hook( __FILE__, 'rvpw_deactivate_recently_viewed_products_for_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-recently-viewed-products-for-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function rvpw_run_recently_viewed_products_for_woocommerce() {

	$plugin = new RVPW_Recently_Viewed_Products_For_Woocommerce();
	$plugin->run();
}
rvpw_run_recently_viewed_products_for_woocommerce();

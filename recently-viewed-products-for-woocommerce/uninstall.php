<?php
/**
 * Uninstall logic.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Always clear our scheduled maintenance event.
wp_clear_scheduled_hook( 'rvpw_daily_prune' );

$settings = get_option( 'rvpw_settings', array() );
if ( is_array( $settings ) && isset( $settings['delete_data_on_uninstall'] ) && 'yes' === $settings['delete_data_on_uninstall'] ) {
	delete_option( 'rvpw_settings' );
	delete_option( 'rvpw_woocommerce_recently_viewed_products_option_name' );

	// Drop the custom tables.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-recently-viewed-products-for-woocommerce-schema.php';
	if ( class_exists( 'RVPW_Recently_Viewed_Products_For_Woocommerce_Schema' ) ) {
		RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::drop_tables();
	}
}

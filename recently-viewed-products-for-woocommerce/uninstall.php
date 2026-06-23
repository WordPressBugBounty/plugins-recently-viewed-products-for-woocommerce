<?php
/**
 * Uninstall logic.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$rvpw_settings = get_option( 'rvpw_settings', array() );
if ( is_array( $rvpw_settings ) && isset( $rvpw_settings['delete_data_on_uninstall'] ) && 'yes' === $rvpw_settings['delete_data_on_uninstall'] ) {
	delete_option( 'rvpw_settings' );
	delete_option( 'rvpw_woocommerce_recently_viewed_products_option_name' );
}

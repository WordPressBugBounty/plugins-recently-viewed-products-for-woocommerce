<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://in.linkedin.com/in/maheshvajapara
 * @since      2.0.0
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/includes
 * @author     Mahesh Patel <p.mahesh8850@gmail.com>
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function rvpw_deactivate() {
		// Clear scheduled maintenance. Data and tables are left intact.
		$timestamp = wp_next_scheduled( 'rvpw_daily_prune' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'rvpw_daily_prune' );
		}
		wp_clear_scheduled_hook( 'rvpw_daily_prune' );

		// Clear the follow-up email scan (Action Scheduler and WP-Cron).
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'rvpw_email_scan' );
		}
		wp_clear_scheduled_hook( 'rvpw_email_scan' );
	}
}

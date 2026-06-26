<?php
/**
 * Database schema.
 *
 * Owns the custom tables (logged-in history, global view counts, email log).
 * This is plugin-owned data — it is unrelated to WooCommerce orders, so HPOS
 * has no bearing here. Created with dbDelta and migrated via a versioned option.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Schema.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Schema {

	/**
	 * Schema version. Bump when the table structure changes.
	 *
	 * @var string
	 */
	const DB_VERSION = '1';

	/**
	 * Option storing the installed schema version.
	 *
	 * @var string
	 */
	const VERSION_OPTION = 'rvpw_db_version';

	/**
	 * Logged-in user history table name.
	 *
	 * @return string
	 */
	public static function table_user_history() {
		global $wpdb;
		return $wpdb->prefix . 'rvpw_user_history';
	}

	/**
	 * Global view counts table name.
	 *
	 * @return string
	 */
	public static function table_view_counts() {
		global $wpdb;
		return $wpdb->prefix . 'rvpw_view_counts';
	}

	/**
	 * Email log table name.
	 *
	 * @return string
	 */
	public static function table_email_log() {
		global $wpdb;
		return $wpdb->prefix . 'rvpw_email_log';
	}

	/**
	 * Create or update the tables.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$history         = self::table_user_history();
		$counts          = self::table_view_counts();
		$log             = self::table_email_log();

		$sql = array();

		$sql[] = "CREATE TABLE {$history} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			viewed_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_product (user_id,product_id),
			KEY user_viewed (user_id,viewed_at)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$counts} (
			product_id bigint(20) unsigned NOT NULL,
			view_count bigint(20) unsigned NOT NULL DEFAULT 0,
			last_viewed datetime NOT NULL,
			PRIMARY KEY  (product_id),
			KEY count_idx (view_count)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			sent_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'sent',
			coupon_code varchar(64) DEFAULT NULL,
			unsubscribed tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY user_sent (user_id,sent_at)
		) {$charset_collate};";

		dbDelta( $sql );

		update_option( self::VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Run the installer when the stored version is out of date.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Drop all plugin tables (used on uninstall when cleanup is enabled).
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = array( self::table_user_history(), self::table_view_counts(), self::table_email_log() );
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name is a class constant, not user input; DROP cannot be prepared.
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( self::VERSION_OPTION );
	}
}

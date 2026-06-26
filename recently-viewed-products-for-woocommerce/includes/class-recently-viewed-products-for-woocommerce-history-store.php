<?php
/**
 * Recently viewed history storage.
 *
 * Owns reading/writing a visitor's recently viewed product IDs. In Phase 1 this
 * is cookie-only (preserving the original behavior); the database-backed path
 * for logged-in users is added in a later phase behind the same API.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * History store.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_History_Store {

	/**
	 * Cookie name for the current site.
	 *
	 * @return string
	 */
	public static function get_cookie_name() {
		return 'rvpw_recently_viewed_' . get_current_blog_id();
	}

	/**
	 * Read the visitor's recently viewed product IDs from the cookie.
	 *
	 * @return int[] Ordered newest-first, de-duplicated, capped to max_stored_products.
	 */
	public static function get_cookie_ids() {
		$cookie_name = self::get_cookie_name();
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return array();
		}

		$cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		if ( '' === $cookie_value ) {
			return array();
		}

		$ids = array_filter( array_map( 'absint', explode( '|', $cookie_value ) ) );
		$ids = array_values( array_unique( $ids ) );

		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();

		return array_slice( $ids, 0, absint( $settings['max_stored_products'] ) );
	}

	/**
	 * Persist the given IDs to the cookie.
	 *
	 * @param int[] $ids          Ordered product IDs.
	 * @param int   $expiry_days  Days until the cookie expires.
	 * @return void
	 */
	public static function set_cookie_ids( $ids, $expiry_days ) {
		// Defensive: avoids a WP_DEBUG notice from wc_setcookie() if output already started.
		if ( headers_sent() ) {
			return;
		}

		$ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );

		wc_setcookie(
			self::get_cookie_name(),
			implode( '|', $ids ),
			time() + ( DAY_IN_SECONDS * absint( $expiry_days ) )
		);
	}

	/**
	 * Record a product view for the current visitor.
	 *
	 * @param int   $product_id Product ID being viewed.
	 * @param array $settings   Plugin settings.
	 * @return void
	 */
	public static function record_view( $product_id, $settings ) {
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return;
		}

		$ids = self::get_cookie_ids();
		$ids = array_diff( $ids, array( $product_id ) );
		array_unshift( $ids, $product_id );
		$ids = array_slice( array_values( array_unique( array_map( 'absint', $ids ) ) ), 0, absint( $settings['max_stored_products'] ) );

		self::set_cookie_ids( $ids, $settings['cookie_expiry_days'] );

		/**
		 * Fires after a product view has been recorded to the cookie.
		 *
		 * Later phases hook this to mirror the view into the database for
		 * logged-in users and to increment the global view counter.
		 *
		 * @param int   $product_id Product viewed.
		 * @param int[] $ids        Current ordered cookie IDs.
		 * @param array $settings   Plugin settings.
		 */
		do_action( 'rvpw_product_view_recorded', $product_id, $ids, $settings );
	}

	/**
	 * Resolve ordered history IDs for a given source.
	 *
	 * @param string $source   'cookie' | 'db' | 'auto'.
	 * @param int    $user_id  Current user ID (0 for guests).
	 * @return int[] Ordered product IDs (newest first).
	 */
	public static function get_ids( $source = 'auto', $user_id = 0 ) {
		/**
		 * Allow later phases (database history) to provide IDs for the
		 * 'db'/'auto' sources without this class needing the schema yet.
		 *
		 * @param int[]|null $ids     Provided IDs, or null to use the cookie.
		 * @param string     $source  Requested source.
		 * @param int        $user_id Current user ID.
		 */
		$provided = apply_filters( 'rvpw_history_ids', null, $source, $user_id );
		if ( is_array( $provided ) ) {
			return array_values( array_unique( array_map( 'absint', $provided ) ) );
		}

		return self::get_cookie_ids();
	}

	/* ---------------------------------------------------------------------
	 * Database-backed history (logged-in users).
	 * ------------------------------------------------------------------ */

	/**
	 * UTC timestamp helper for stored datetimes.
	 *
	 * @param int|null $offset_seconds Optional seconds to subtract.
	 * @return string
	 */
	private static function utc_now( $offset_seconds = 0 ) {
		return gmdate( 'Y-m-d H:i:s', time() - absint( $offset_seconds ) );
	}

	/**
	 * Mirror a view into the database for logged-in users when enabled.
	 *
	 * Hooked to 'rvpw_product_view_recorded'.
	 *
	 * @param int   $product_id Product ID.
	 * @param array $ids        Current cookie IDs (unused).
	 * @param array $settings   Settings.
	 * @return void
	 */
	public static function maybe_record_db_view( $product_id, $ids, $settings ) {
		if ( 'yes' !== ( $settings['track_db_for_logged_in'] ?? 'no' ) || ! is_user_logged_in() ) {
			return;
		}

		self::record_db_view( get_current_user_id(), $product_id );
	}

	/**
	 * Upsert a single view row for a user.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function record_db_view( $user_id, $product_id ) {
		global $wpdb;

		$user_id    = absint( $user_id );
		$product_id = absint( $product_id );
		if ( ! $user_id || ! $product_id ) {
			return;
		}

		$table = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_user_history();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table; intentional write.
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
				"INSERT INTO {$table} (user_id, product_id, viewed_at) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE viewed_at = VALUES(viewed_at)",
				$user_id,
				$product_id,
				self::utc_now()
			)
		);
	}

	/**
	 * Read a user's stored history IDs, newest first.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_db_ids( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		}

		$table    = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_user_history();
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$max      = absint( $settings['max_stored_products'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
				"SELECT product_id FROM {$table} WHERE user_id = %d ORDER BY viewed_at DESC LIMIT %d",
				$user_id,
				$max
			)
		);

		return array_map( 'absint', (array) $ids );
	}

	/**
	 * Provide DB-backed IDs to the Provider for the 'db'/'auto' sources.
	 *
	 * Hooked to the 'rvpw_history_ids' filter. Returning null keeps the cookie.
	 *
	 * @param array|null $ids     Current value.
	 * @param string     $source  Requested source.
	 * @param int        $user_id User ID.
	 * @return array|null
	 */
	public static function filter_history_ids( $ids, $source, $user_id ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( 'cookie' === $source || ! $user_id ) {
			return $ids;
		}

		$settings   = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$db_enabled = ( 'yes' === $settings['track_db_for_logged_in'] );

		$use_db = ( 'db' === $source ) || ( 'auto' === $source && $db_enabled );
		if ( ! $use_db ) {
			return $ids;
		}

		// DB history first, then any cookie IDs not yet mirrored (current session).
		$merged = self::get_db_ids( $user_id );
		foreach ( self::get_cookie_ids() as $cookie_id ) {
			if ( ! in_array( $cookie_id, $merged, true ) ) {
				$merged[] = $cookie_id;
			}
		}

		return $merged;
	}

	/**
	 * Merge the guest cookie history into the DB when a user logs in.
	 *
	 * Hooked to 'wp_login'.
	 *
	 * @param string  $user_login User login (unused).
	 * @param WP_User $user       Logged-in user.
	 * @return void
	 */
	public static function on_login( $user_login, $user ) {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		if ( 'yes' !== $settings['track_db_for_logged_in'] || ! ( $user instanceof WP_User ) ) {
			return;
		}

		$cookie_ids = self::get_cookie_ids();
		if ( empty( $cookie_ids ) ) {
			return;
		}

		global $wpdb;
		$table  = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_user_history();
		$offset = 0;

		foreach ( $cookie_ids as $product_id ) {
			// Insert preserving cookie order; never overwrite a newer existing DB view.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write.
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
					"INSERT INTO {$table} (user_id, product_id, viewed_at) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE viewed_at = viewed_at",
					$user->ID,
					absint( $product_id ),
					self::utc_now( $offset )
				)
			);
			$offset++;
		}
	}

	/**
	 * Prune old/excess rows. Hooked to the daily maintenance event.
	 *
	 * @return void
	 */
	public static function prune() {
		global $wpdb;

		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$table    = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_user_history();
		$cutoff   = self::utc_now( DAY_IN_SECONDS * absint( $settings['db_retention_days'] ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Scheduled maintenance on a custom table.
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
				"DELETE FROM {$table} WHERE viewed_at < %s",
				$cutoff
			)
		);
	}
}

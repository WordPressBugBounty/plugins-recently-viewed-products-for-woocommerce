<?php
/**
 * Global view-count store (for "Most Viewed Products").
 *
 * Increments a per-product counter on every tracked view (guests and logged-in
 * users) and serves a transient-cached top-N list.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Counter store.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store {

	/**
	 * Transient TTL for the top-N list.
	 */
	const CACHE_TTL = 900; // 15 minutes.

	/**
	 * Increment the counter for a product. Hooked to 'rvpw_product_view_recorded'.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function increment( $product_id ) {
		global $wpdb;

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return;
		}

		$table = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_view_counts();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom counter table.
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
				"INSERT INTO {$table} (product_id, view_count, last_viewed) VALUES (%d, 1, %s) ON DUPLICATE KEY UPDATE view_count = view_count + 1, last_viewed = VALUES(last_viewed)",
				$product_id,
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}

	/**
	 * Top-N most-viewed product IDs (transient cached).
	 *
	 * @param int $limit Number of IDs.
	 * @return int[]
	 */
	public static function get_top_ids( $limit ) {
		global $wpdb;

		$limit = max( 1, absint( $limit ) );
		$key   = 'rvpw_most_viewed_' . $limit;

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_view_counts();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Result cached in a transient below.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant.
				"SELECT product_id FROM {$table} ORDER BY view_count DESC, last_viewed DESC LIMIT %d",
				$limit
			)
		);

		$ids = array_map( 'absint', (array) $ids );
		set_transient( $key, $ids, self::CACHE_TTL );

		return $ids;
	}

	/**
	 * View counts keyed by product ID for a set of products.
	 *
	 * @param int[] $product_ids Product IDs.
	 * @return array<int,int>
	 */
	public static function get_counts_for( $product_ids ) {
		global $wpdb;

		$product_ids = array_values( array_filter( array_map( 'absint', (array) $product_ids ) ) );
		if ( empty( $product_ids ) ) {
			return array();
		}

		$table        = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_view_counts();
		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

		// Table name is a class constant; every product ID is bound as a %d placeholder.
		// phpcs:disable WordPress.DB
		$query = "SELECT product_id, view_count FROM {$table} WHERE product_id IN ({$placeholders})";
		$rows  = $wpdb->get_results( $wpdb->prepare( $query, $product_ids ), ARRAY_A );
		// phpcs:enable WordPress.DB

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ absint( $row['product_id'] ) ] = (int) $row['view_count'];
		}

		return $map;
	}

	/**
	 * Clear the cached top-N lists. Hooked to settings updates.
	 *
	 * @return void
	 */
	public static function bust_cache() {
		for ( $i = 1; $i <= 24; $i++ ) {
			delete_transient( 'rvpw_most_viewed_' . $i );
		}
	}

	/**
	 * Total view count for a single product (used by analytics).
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public static function get_count( $product_id ) {
		$counts = self::get_counts_for( array( $product_id ) );
		return isset( $counts[ absint( $product_id ) ] ) ? (int) $counts[ absint( $product_id ) ] : 0;
	}
}

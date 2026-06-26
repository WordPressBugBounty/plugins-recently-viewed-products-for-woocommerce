<?php
/**
 * Dedicated "Recently Viewed Products" page.
 *
 * Creates a page containing the shortcode once, on activation. It deliberately
 * never resurrects a page the user has trashed or deleted (a one-time flag
 * guards against that).
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Page manager.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Page_Manager {

	/**
	 * Option storing the created page ID.
	 */
	const PAGE_OPTION = 'rvpw_dedicated_page_id';

	/**
	 * Flag marking that the page was created once.
	 */
	const CREATED_FLAG = 'rvpw_page_created';

	/**
	 * Create the page once, if it does not already exist.
	 *
	 * @return void
	 */
	public static function maybe_create() {
		// Already created at some point — never recreate (respects user deletion).
		if ( get_option( self::CREATED_FLAG ) ) {
			return;
		}

		$existing = absint( get_option( self::PAGE_OPTION ) );
		if ( $existing && get_post( $existing ) ) {
			update_option( self::CREATED_FLAG, 1 );
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
				'post_content' => '<!-- wp:shortcode -->[rvpw_products]<!-- /wp:shortcode -->',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( self::PAGE_OPTION, (int) $page_id );
		}

		update_option( self::CREATED_FLAG, 1 );
	}

	/**
	 * Get the live page ID (0 if missing or trashed).
	 *
	 * @return int
	 */
	public static function get_page_id() {
		$id = absint( get_option( self::PAGE_OPTION ) );
		if ( $id && get_post( $id ) && 'trash' !== get_post_status( $id ) ) {
			return $id;
		}

		return 0;
	}
}

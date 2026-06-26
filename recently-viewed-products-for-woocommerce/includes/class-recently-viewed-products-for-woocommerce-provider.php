<?php
/**
 * Product provider.
 *
 * Single entry point that resolves an ordered, filtered, visibility-checked,
 * capped list of WC_Product objects for every display surface (shortcode,
 * automatic placement, widget, block, Elementor). No display surface should
 * query products, cookies or the database directly — they all call this.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Provider.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Provider {

	/**
	 * Default query arguments.
	 *
	 * @return array
	 */
	public static function default_query() {
		return array(
			'source'    => 'auto',
			'sort'      => 'recent',
			'limit'     => 4,
			'user_id'   => 0,
			'overfetch' => 0,
			'filters'   => array(
				'in_stock_only'      => false,
				'exclude_purchased'  => false,
				'hide_free'          => false,
				'exclude_categories' => array(),
				'exclude_product_id' => 0,
			),
		);
	}

	/**
	 * Resolve products for the given query.
	 *
	 * @param array $query Query arguments. See default_query().
	 * @return WC_Product[]
	 */
	public static function get_products( array $query ) {
		$query            = wp_parse_args( $query, self::default_query() );
		$query['filters'] = wp_parse_args( (array) $query['filters'], self::default_query()['filters'] );

		$limit = max( 1, absint( $query['limit'] ) );

		// Over-fetch so object-level filters do not shrink the result below the limit.
		$overfetch = absint( $query['overfetch'] );
		if ( $overfetch < $limit ) {
			$overfetch = max( $limit * 4, $limit );
		}

		$ids = self::resolve_ids( $query );
		$ids = self::filter_ids( $ids, $query );
		if ( empty( $ids ) ) {
			return array();
		}

		$ids      = array_slice( $ids, 0, $overfetch );
		$products = self::resolve_products( $ids );
		$products = self::filter_products( $products, $query );
		$products = self::sort_products( $products, $query );

		return array_slice( $products, 0, $limit );
	}

	/**
	 * Resolve the raw ordered ID list for the requested source.
	 *
	 * @param array $query Query.
	 * @return int[]
	 */
	private static function resolve_ids( $query ) {
		$source  = $query['source'];
		$user_id = absint( $query['user_id'] );

		if ( 'most_viewed' === $source ) {
			if ( class_exists( 'RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store' ) ) {
				$overfetch = max( absint( $query['limit'] ) * 4, absint( $query['limit'] ) );
				return RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store::get_top_ids( $overfetch );
			}
			// Counter not available yet — fall back to the visitor's own history.
			$source = 'auto';
		}

		return RVPW_Recently_Viewed_Products_For_Woocommerce_History_Store::get_ids( $source, $user_id );
	}

	/**
	 * Cheap, ID-level filtering (current product exclusion).
	 *
	 * @param int[] $ids   IDs.
	 * @param array $query Query.
	 * @return int[]
	 */
	private static function filter_ids( $ids, $query ) {
		$exclude = absint( $query['filters']['exclude_product_id'] );
		if ( $exclude ) {
			$ids = array_diff( $ids, array( $exclude ) );
		}

		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Resolve IDs into WC_Product objects in a single query, preserving order.
	 *
	 * @param int[] $ids IDs.
	 * @return WC_Product[]
	 */
	private static function resolve_products( $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		// One bulk query primes the product cache instead of N single lookups.
		$found = wc_get_products(
			array(
				'include' => $ids,
				'limit'   => -1,
				'status'  => 'publish',
				'orderby' => 'none',
				'return'  => 'objects',
			)
		);

		$by_id = array();
		foreach ( $found as $product ) {
			$by_id[ $product->get_id() ] = $product;
		}

		// Re-order to match the source order (wc_get_products does not guarantee it).
		$ordered = array();
		foreach ( $ids as $id ) {
			if ( isset( $by_id[ $id ] ) ) {
				$ordered[] = $by_id[ $id ];
			}
		}

		return $ordered;
	}

	/**
	 * Object-level filtering. All filters default off, so the result is
	 * identical to the legacy behavior unless a filter is explicitly enabled.
	 *
	 * @param WC_Product[] $products Products.
	 * @param array        $query    Query.
	 * @return WC_Product[]
	 */
	private static function filter_products( $products, $query ) {
		$filters             = $query['filters'];
		$user_id             = absint( $query['user_id'] );
		$exclude_categories  = array_map( 'absint', (array) $filters['exclude_categories'] );
		$customer_email      = '';

		if ( $filters['exclude_purchased'] && $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$customer_email = $user->user_email;
			}
		}

		$kept = array();
		foreach ( $products as $product ) {
			if ( ! $product->is_visible() ) {
				continue;
			}

			if ( $filters['in_stock_only'] && ! $product->is_in_stock() ) {
				continue;
			}

			if ( $filters['hide_free'] && (float) $product->get_price() <= 0 ) {
				continue;
			}

			if ( ! empty( $exclude_categories ) && has_term( $exclude_categories, 'product_cat', $product->get_id() ) ) {
				continue;
			}

			if ( $filters['exclude_purchased'] && $user_id && wc_customer_bought_product( $customer_email, $user_id, $product->get_id() ) ) {
				continue;
			}

			$kept[] = $product;
		}

		return $kept;
	}

	/**
	 * Apply the requested sort. 'recent' keeps the source order.
	 *
	 * @param WC_Product[] $products Products.
	 * @param array        $query    Query.
	 * @return WC_Product[]
	 */
	private static function sort_products( $products, $query ) {
		switch ( $query['sort'] ) {
			case 'price_asc':
				usort(
					$products,
					static function ( $a, $b ) {
						return (float) $a->get_price() <=> (float) $b->get_price();
					}
				);
				break;

			case 'price_desc':
				usort(
					$products,
					static function ( $a, $b ) {
						return (float) $b->get_price() <=> (float) $a->get_price();
					}
				);
				break;

			case 'date':
				usort(
					$products,
					static function ( $a, $b ) {
						$a_time = $a->get_date_created() ? $a->get_date_created()->getTimestamp() : 0;
						$b_time = $b->get_date_created() ? $b->get_date_created()->getTimestamp() : 0;
						return $b_time <=> $a_time;
					}
				);
				break;

			case 'random':
				shuffle( $products );
				break;

			case 'most_viewed':
				if ( class_exists( 'RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store' ) ) {
					$counts = RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store::get_counts_for(
						array_map(
							static function ( $p ) {
								return $p->get_id();
							},
							$products
						)
					);
					usort(
						$products,
						static function ( $a, $b ) use ( $counts ) {
							$a_count = isset( $counts[ $a->get_id() ] ) ? (int) $counts[ $a->get_id() ] : 0;
							$b_count = isset( $counts[ $b->get_id() ] ) ? (int) $counts[ $b->get_id() ] : 0;
							return $b_count <=> $a_count;
						}
					);
				}
				break;

			case 'recent':
			default:
				// Keep source order.
				break;
		}

		return $products;
	}
}

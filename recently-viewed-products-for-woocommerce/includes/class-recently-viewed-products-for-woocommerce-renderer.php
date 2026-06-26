<?php
/**
 * Markup renderer.
 *
 * The single source of truth for the front-end markup, shared by every display
 * surface. It is pure: given normalized display args and an already-resolved,
 * already-ordered list of products, it returns fully escaped HTML.
 *
 * Escaping happens as the markup is assembled (esc_url/esc_attr/esc_html and
 * wp_kses_post for WooCommerce-generated fragments). Callers MUST echo the
 * return value directly and MUST NOT pass it through wp_kses_post() again — a
 * second pass re-parses the whole blob and can strip the CSS custom properties
 * that drive the responsive columns.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Renderer.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Renderer {

	/**
	 * Render the section.
	 *
	 * @param array        $args     Normalized display args.
	 * @param WC_Product[] $products Ordered products (already capped to the limit).
	 * @return string Escaped HTML.
	 */
	public static function render( array $args, array $products ) {
		$args = self::normalize_args( $args );

		if ( empty( $products ) ) {
			if ( empty( $args['is_placement'] ) && '' !== trim( (string) $args['empty_message'] ) ) {
				return '<p class="rvpw-empty">' . esc_html( $args['empty_message'] ) . '</p>';
			}
			return '';
		}

		// Enqueue styling for whichever surface is rendering (shortcode, placement, widget, block, Elementor).
		wp_enqueue_style( RVPW_ASSET_PREFIX . '-public' );
		if ( 'carousel' === $args['layout'] ) {
			wp_enqueue_style( RVPW_ASSET_PREFIX . '-carousel' );
			wp_enqueue_script( RVPW_ASSET_PREFIX . '-carousel' );
		}

		$custom_class = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::sanitize_css_classes( $args['custom_css_class'] );
		$classes      = trim( 'rvpw-products rvpw-layout-' . $args['layout'] . ' ' . $custom_class );
		$style        = sprintf(
			'--rvpw-mobile-columns:%1$d;--rvpw-tablet-columns:%2$d;--rvpw-desktop-columns:%3$d;',
			absint( $args['mobile_columns'] ),
			absint( $args['tablet_columns'] ),
			absint( $args['desktop_columns'] )
		);

		$label = '' !== $args['section_title'] ? $args['section_title'] : __( 'Recently viewed products', 'recently-viewed-products-for-woocommerce' );

		ob_start();
		?>
		<section class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<?php if ( '' !== $args['section_title'] ) : ?>
				<h2 class="rvpw-heading"><?php echo esc_html( $args['section_title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( 'carousel' === $args['layout'] ) : ?>
				<?php echo self::render_carousel( $args, $products ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within helper. ?>
			<?php else : ?>
				<ul class="rvpw-grid products columns-<?php echo esc_attr( absint( $args['desktop_columns'] ) ); ?>">
					<?php foreach ( $products as $product ) : ?>
						<?php echo self::render_product( $product, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within helper. ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render the carousel wrapper (viewport, arrows, dots).
	 *
	 * @param array        $args     Args.
	 * @param WC_Product[] $products Products.
	 * @return string
	 */
	private static function render_carousel( $args, $products ) {
		ob_start();
		?>
		<div
			class="rvpw-carousel"
			data-rvpw-carousel
			data-autoplay="<?php echo 'yes' === $args['carousel_autoplay'] ? '1' : '0'; ?>"
			data-speed="<?php echo esc_attr( absint( $args['carousel_autoplay_speed'] ) ); ?>"
			data-loop="<?php echo 'yes' === $args['carousel_loop'] ? '1' : '0'; ?>"
		>
			<?php if ( 'yes' === $args['carousel_arrows'] ) : ?>
				<button type="button" class="rvpw-carousel__arrow rvpw-carousel__arrow--prev" aria-label="<?php esc_attr_e( 'Previous products', 'recently-viewed-products-for-woocommerce' ); ?>">
					<span aria-hidden="true">&#8249;</span>
				</button>
			<?php endif; ?>

			<div class="rvpw-carousel__viewport">
				<ul class="rvpw-grid rvpw-carousel__track products columns-<?php echo esc_attr( absint( $args['desktop_columns'] ) ); ?>">
					<?php foreach ( $products as $product ) : ?>
						<?php echo self::render_product( $product, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within helper. ?>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php if ( 'yes' === $args['carousel_arrows'] ) : ?>
				<button type="button" class="rvpw-carousel__arrow rvpw-carousel__arrow--next" aria-label="<?php esc_attr_e( 'Next products', 'recently-viewed-products-for-woocommerce' ); ?>">
					<span aria-hidden="true">&#8250;</span>
				</button>
			<?php endif; ?>

			<?php if ( 'yes' === $args['carousel_dots'] ) : ?>
				<div class="rvpw-carousel__dots" role="tablist" aria-label="<?php esc_attr_e( 'Carousel pagination', 'recently-viewed-products-for-woocommerce' ); ?>"></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a single product list item.
	 *
	 * The card is split into a media block, a body block, and an actions block so
	 * that the CSS can stretch every card to an equal height and pin the call to
	 * action to the bottom regardless of how long the title is.
	 *
	 * Backward compatibility: every WooCommerce hook class is preserved
	 * (`product`, `woocommerce-loop-product__title`, `price`, `button`,
	 * `add_to_cart_button` plus its data attributes) so themes, AJAX add-to-cart
	 * and the carousel script keep working untouched.
	 *
	 * @param WC_Product $product Product.
	 * @param array      $args    Args.
	 * @return string
	 */
	private static function render_product( $product, $args ) {
		$permalink  = get_permalink( $product->get_id() );
		$name       = $product->get_name();
		$show_image = 'yes' === $args['show_image'];
		$show_title = 'yes' === $args['show_title'];

		// When both the image and the title link to the same product, expose only
		// the title link to assistive tech and keyboards to avoid a redundant tab
		// stop; the image link becomes decorative. When the title is hidden the
		// image link must stay focusable, so it carries the accessible name.
		$image_is_decorative = $show_image && $show_title;

		ob_start();
		?>
		<li class="product rvpw-product">
			<?php if ( $show_image ) : ?>
				<div class="rvpw-product__media">
					<a
						class="rvpw-product__link"
						href="<?php echo esc_url( $permalink ); ?>"
						<?php if ( $image_is_decorative ) : ?>
							tabindex="-1" aria-hidden="true"
						<?php else : ?>
							aria-label="<?php echo esc_attr( $name ); ?>"
						<?php endif; ?>
					>
						<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail' ) ); ?>
					</a>
					<?php if ( ! $product->is_in_stock() ) : ?>
						<span class="rvpw-product__badge rvpw-product__badge--out"><?php esc_html_e( 'Out of stock', 'recently-viewed-products-for-woocommerce' ); ?></span>
					<?php elseif ( $product->is_on_sale() ) : ?>
						<span class="rvpw-product__badge rvpw-product__badge--sale"><?php esc_html_e( 'Sale', 'recently-viewed-products-for-woocommerce' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="rvpw-product__body">
				<?php if ( $show_title ) : ?>
					<a class="rvpw-product__title-link" href="<?php echo esc_url( $permalink ); ?>">
						<h3 class="woocommerce-loop-product__title rvpw-product__title"><?php echo esc_html( $name ); ?></h3>
					</a>
				<?php endif; ?>

				<?php if ( 'yes' === $args['show_rating'] && $product->get_rating_count() > 0 ) : ?>
					<div class="rvpw-product__rating">
						<?php echo wp_kses_post( wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( 'yes' === $args['show_price'] && '' !== $product->get_price_html() ) : ?>
					<span class="price rvpw-product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<?php endif; ?>

				<?php if ( 'yes' === $args['show_add_to_cart'] && $product->is_purchasable() ) : ?>
					<div class="rvpw-product__actions">
						<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="button add_to_cart_button rvpw-product__button" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>" rel="nofollow"><?php echo esc_html( $product->add_to_cart_text() ); ?></a>
					</div>
				<?php endif; ?>
			</div>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Fill in any missing args from settings so partial arg arrays are safe.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	private static function normalize_args( $args ) {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$args     = wp_parse_args( $args, $settings );

		$args['layout'] = in_array( $args['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $args['layout'] : 'grid';

		return $args;
	}
}

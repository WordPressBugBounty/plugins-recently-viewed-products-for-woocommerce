<?php
/**
 * Sidebar widget.
 *
 * A thin adapter over the Display facade. Works in classic sidebars and, via
 * the legacy-widget block, in the block editor too.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Recently viewed products widget.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'rvpw_products',
			esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			array(
				'description' => esc_html__( 'Shows the products a visitor recently viewed.', 'recently-viewed-products-for-woocommerce' ),
				'classname'   => 'rvpw-widget',
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Saved values.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();

		$limit   = isset( $instance['limit'] ) ? max( 1, min( 24, absint( $instance['limit'] ) ) ) : absint( $settings['display_limit'] );
		$columns = isset( $instance['columns'] ) ? max( 1, min( 6, absint( $instance['columns'] ) ) ) : absint( $settings['desktop_columns'] );
		$layout  = isset( $instance['layout'] ) && in_array( $instance['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $instance['layout'] : $settings['layout'];
		$sort    = isset( $instance['sort'] ) && in_array( $instance['sort'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sorts(), true ) ? $instance['sort'] : $settings['sort_order'];

		$overrides = array(
			'section_title'   => '', // The widget title is rendered by the theme below.
			'display_limit'   => $limit,
			'desktop_columns' => $columns,
			'layout'          => $layout,
			'sort_order'      => $sort,
			'empty_message'   => '', // Widgets stay silent when there is no history.
		);

		$html = RVPW_Recently_Viewed_Products_For_Woocommerce_Display::section( $overrides, false );

		if ( '' === trim( $html ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided wrappers.
		echo $args['before_widget'];

		$title = isset( $instance['title'] ) ? $instance['title'] : $settings['section_title'];
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		if ( '' !== $title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme wrappers; title escaped.
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within the Renderer.
		echo $html;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided wrappers.
		echo $args['after_widget'];
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Saved values.
	 * @return string
	 */
	public function form( $instance ) {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$title    = isset( $instance['title'] ) ? $instance['title'] : $settings['section_title'];
		$limit    = isset( $instance['limit'] ) ? absint( $instance['limit'] ) : absint( $settings['display_limit'] );
		$columns  = isset( $instance['columns'] ) ? absint( $instance['columns'] ) : absint( $settings['desktop_columns'] );
		$layout   = isset( $instance['layout'] ) ? $instance['layout'] : $settings['layout'];
		$sort     = isset( $instance['sort'] ) ? $instance['sort'] : $settings['sort_order'];

		$layouts = array(
			'grid'     => esc_html__( 'Grid', 'recently-viewed-products-for-woocommerce' ),
			'list'     => esc_html__( 'List', 'recently-viewed-products-for-woocommerce' ),
			'carousel' => esc_html__( 'Carousel', 'recently-viewed-products-for-woocommerce' ),
		);
		$sorts   = array(
			'recent'     => esc_html__( 'Recently viewed', 'recently-viewed-products-for-woocommerce' ),
			'price_asc'  => esc_html__( 'Price: low to high', 'recently-viewed-products-for-woocommerce' ),
			'price_desc' => esc_html__( 'Price: high to low', 'recently-viewed-products-for-woocommerce' ),
			'date'       => esc_html__( 'Newest products', 'recently-viewed-products-for-woocommerce' ),
			'random'     => esc_html__( 'Random', 'recently-viewed-products-for-woocommerce' ),
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'recently-viewed-products-for-woocommerce' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Products to display:', 'recently-viewed-products-for-woocommerce' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="24" value="<?php echo esc_attr( $limit ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>"><?php esc_html_e( 'Columns:', 'recently-viewed-products-for-woocommerce' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>" type="number" min="1" max="6" value="<?php echo esc_attr( $columns ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"><?php esc_html_e( 'Layout:', 'recently-viewed-products-for-woocommerce' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
				<?php foreach ( $layouts as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $layout, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>"><?php esc_html_e( 'Order by:', 'recently-viewed-products-for-woocommerce' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sort' ) ); ?>">
				<?php foreach ( $sorts as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
		return '';
	}

	/**
	 * Sanitize on save.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance            = array();
		$instance['title']   = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['limit']   = isset( $new_instance['limit'] ) ? max( 1, min( 24, absint( $new_instance['limit'] ) ) ) : 4;
		$instance['columns'] = isset( $new_instance['columns'] ) ? max( 1, min( 6, absint( $new_instance['columns'] ) ) ) : 4;
		$instance['layout']  = isset( $new_instance['layout'] ) && in_array( $new_instance['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $new_instance['layout'] : 'grid';
		$instance['sort']    = isset( $new_instance['sort'] ) && in_array( $new_instance['sort'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sorts(), true ) ? $new_instance['sort'] : 'recent';

		return $instance;
	}
}

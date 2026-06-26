<?php
/**
 * Elementor widget.
 *
 * Loaded only when Elementor is active (required from the registrar inside the
 * 'elementor/widgets/register' callback), so extending \Elementor\Widget_Base
 * is always safe here.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Recently viewed products Elementor widget.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'rvpw_recently_viewed';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'recently', 'viewed', 'products', 'woocommerce' );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'rvpw_section',
			array(
				'label' => esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'recently-viewed-products-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Recently Viewed Products', 'recently-viewed-products-for-woocommerce' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Products to display', 'recently-viewed-products-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 4,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'recently-viewed-products-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 4,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'recently-viewed-products-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => array(
					'grid'     => esc_html__( 'Grid', 'recently-viewed-products-for-woocommerce' ),
					'list'     => esc_html__( 'List', 'recently-viewed-products-for-woocommerce' ),
					'carousel' => esc_html__( 'Carousel', 'recently-viewed-products-for-woocommerce' ),
				),
			)
		);

		$this->add_control(
			'sort',
			array(
				'label'   => esc_html__( 'Order by', 'recently-viewed-products-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'recent',
				'options' => array(
					'recent'     => esc_html__( 'Recently viewed', 'recently-viewed-products-for-woocommerce' ),
					'price_asc'  => esc_html__( 'Price: low to high', 'recently-viewed-products-for-woocommerce' ),
					'price_desc' => esc_html__( 'Price: high to low', 'recently-viewed-products-for-woocommerce' ),
					'date'       => esc_html__( 'Newest products', 'recently-viewed-products-for-woocommerce' ),
					'random'     => esc_html__( 'Random', 'recently-viewed-products-for-woocommerce' ),
				),
			)
		);

		foreach ( array(
			'show_image'  => esc_html__( 'Show image', 'recently-viewed-products-for-woocommerce' ),
			'show_title'  => esc_html__( 'Show title', 'recently-viewed-products-for-woocommerce' ),
			'show_price'  => esc_html__( 'Show price', 'recently-viewed-products-for-woocommerce' ),
			'show_rating' => esc_html__( 'Show rating', 'recently-viewed-products-for-woocommerce' ),
			'show_cart'   => esc_html__( 'Show add-to-cart', 'recently-viewed-products-for-woocommerce' ),
		) as $control => $label ) {
			$this->add_control(
				$control,
				array(
					'label'        => $label,
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'show_rating' === $control ? '' : 'yes',
					'return_value' => 'yes',
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Render on the front end (and Elementor editor preview).
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		$overrides = array(
			'display_limit'    => isset( $s['limit'] ) ? max( 1, min( 24, absint( $s['limit'] ) ) ) : 4,
			'desktop_columns'  => isset( $s['columns'] ) ? max( 1, min( 6, absint( $s['columns'] ) ) ) : 4,
			'layout'           => isset( $s['layout'] ) && in_array( $s['layout'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_layouts(), true ) ? $s['layout'] : 'grid',
			'sort_order'       => isset( $s['sort'] ) && in_array( $s['sort'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_sorts(), true ) ? $s['sort'] : 'recent',
			'show_image'       => empty( $s['show_image'] ) ? 'no' : 'yes',
			'show_title'       => empty( $s['show_title'] ) ? 'no' : 'yes',
			'show_price'       => empty( $s['show_price'] ) ? 'no' : 'yes',
			'show_rating'      => empty( $s['show_rating'] ) ? 'no' : 'yes',
			'show_add_to_cart' => empty( $s['show_cart'] ) ? 'no' : 'yes',
		);

		if ( ! empty( $s['title'] ) ) {
			$overrides['section_title'] = sanitize_text_field( $s['title'] );
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within the Renderer.
		echo RVPW_Recently_Viewed_Products_For_Woocommerce_Display::section( $overrides, false );
	}
}

<?php
/**
 * Elementor integration registrar.
 *
 * The widget class is only loaded inside the registration callback, which only
 * fires when Elementor is active — so this file never references Elementor
 * classes at load time.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Elementor registrar.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Elementor {

	/**
	 * Register the Elementor widget.
	 *
	 * Hooked to 'elementor/widgets/register', which only fires when Elementor
	 * is loaded.
	 *
	 * @param mixed $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widget( $widgets_manager ) {
		require_once RVPW_PLUGIN_DIR . 'includes/integrations/class-recently-viewed-products-for-woocommerce-elementor-widget.php';

		$widget = new RVPW_Recently_Viewed_Products_For_Woocommerce_Elementor_Widget();

		if ( is_object( $widgets_manager ) && method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
		} elseif ( is_object( $widgets_manager ) && method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
		}
	}
}

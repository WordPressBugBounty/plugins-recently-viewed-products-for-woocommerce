<?php
/**
 * Core plugin class.
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 */

class RVPW_Recently_Viewed_Products_For_Woocommerce {

	/**
	 * Loader.
	 *
	 * @var RVPW_Recently_Viewed_Products_For_Woocommerce_Loader
	 */
	protected $loader;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * WooCommerce active.
	 *
	 * @var bool
	 */
	protected $is_woocommerce_active = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version     = defined( 'RVPW_VERSION' ) ? RVPW_VERSION : '2.2.0';
		$this->plugin_name = 'recently-viewed-products-for-woocommerce';

		$this->load_dependencies();
		$this->define_common_hooks();
		$this->define_admin_hooks();
		$this->is_woocommerce_active = $this->is_woocommerce_active();
		if ( $this->is_woocommerce_active ) {
			$this->define_public_hooks();
		}
	}

	/**
	 * Load required files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-loader.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-settings.php';
		require_once RVPW_PLUGIN_DIR . 'admin/class-recently-viewed-products-for-woocommerce-admin.php';
		require_once RVPW_PLUGIN_DIR . 'public/class-recently-viewed-products-for-woocommerce-public.php';
		$this->loader = new RVPW_Recently_Viewed_Products_For_Woocommerce_Loader();
	}

	/**
	 * Common hooks.
	 *
	 * @return void
	 */
	private function define_common_hooks() {
		// HPOS compatibility is declared at include time in the main plugin file
		// (WooCommerce fires before_woocommerce_init earlier than this boot).
		$this->loader->add_action( 'admin_notices', $this, 'maybe_render_woocommerce_notice' );
	}

	/**
	 * Admin hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new RVPW_Recently_Viewed_Products_For_Woocommerce_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'register_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_post_rvpw_save_settings', $plugin_admin, 'save_settings' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( RVPW_PLUGIN_FILE ), $plugin_admin, 'rvpw_settings_link' );
	}

	/**
	 * Public hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public = new RVPW_Recently_Viewed_Products_For_Woocommerce_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'register_assets' );
		$this->loader->add_action( 'template_redirect', $plugin_public, 'track_product_view', 20 );
		$this->loader->add_action( 'wp', $plugin_public, 'register_product_page_placement' );
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_rest_routes' );
	}

	/**
	 * Show admin notice when WooCommerce is inactive.
	 *
	 * @return void
	 */
	public function maybe_render_woocommerce_notice() {
		if ( $this->is_woocommerce_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Recently Viewed Product for WooCommerce requires WooCommerce to be installed and active.', 'recently-viewed-products-for-woocommerce' ); ?></p></div>
		<?php
	}

	/**
	 * Check WooCommerce active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Run hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}

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
		$this->version     = defined( 'RVPW_VERSION' ) ? RVPW_VERSION : '2.3.0';
		$this->plugin_name = 'recently-viewed-products-for-woocommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_common_hooks();
		$this->define_admin_hooks();
		$this->define_engine_hooks();
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
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-i18n.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-settings.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-schema.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-history-store.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-counter-store.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-page-manager.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-emails.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-provider.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-renderer.php';
		require_once RVPW_PLUGIN_DIR . 'includes/class-recently-viewed-products-for-woocommerce-display.php';
		require_once RVPW_PLUGIN_DIR . 'includes/integrations/class-recently-viewed-products-for-woocommerce-widget.php';
		require_once RVPW_PLUGIN_DIR . 'includes/integrations/class-recently-viewed-products-for-woocommerce-block.php';
		require_once RVPW_PLUGIN_DIR . 'includes/integrations/class-recently-viewed-products-for-woocommerce-elementor.php';
		require_once RVPW_PLUGIN_DIR . 'admin/class-recently-viewed-products-for-woocommerce-admin.php';
		require_once RVPW_PLUGIN_DIR . 'admin/class-recently-viewed-products-for-woocommerce-analytics.php';
		require_once RVPW_PLUGIN_DIR . 'public/class-recently-viewed-products-for-woocommerce-public.php';
		$this->loader = new RVPW_Recently_Viewed_Products_For_Woocommerce_Loader();
	}

	/**
	 * Set i18n.
	 *
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new RVPW_Recently_Viewed_Products_For_Woocommerce_I18n();
		// Hooked to 'init' (not 'plugins_loaded') because the plugin now boots on
		// 'plugins_loaded'; WordPress 6.7+ also expects translations to load on init.
		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
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

		$plugin_analytics = new RVPW_Recently_Viewed_Products_For_Woocommerce_Analytics( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_analytics, 'register_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_analytics, 'enqueue_assets' );
	}

	/**
	 * Engine hooks (database history, schema migration, maintenance).
	 *
	 * Registered with class-name components because the callbacks are static.
	 *
	 * @return void
	 */
	private function define_engine_hooks() {
		$schema  = 'RVPW_Recently_Viewed_Products_For_Woocommerce_Schema';
		$history = 'RVPW_Recently_Viewed_Products_For_Woocommerce_History_Store';
		$counter = 'RVPW_Recently_Viewed_Products_For_Woocommerce_Counter_Store';

		$this->loader->add_action( 'admin_init', $schema, 'maybe_upgrade' );
		$this->loader->add_action( 'rvpw_product_view_recorded', $history, 'maybe_record_db_view', 10, 3 );
		$this->loader->add_action( 'wp_login', $history, 'on_login', 10, 2 );
		$this->loader->add_filter( 'rvpw_history_ids', $history, 'filter_history_ids', 10, 3 );
		$this->loader->add_action( 'rvpw_daily_prune', $history, 'prune' );

		// Most-viewed counter.
		$this->loader->add_action( 'rvpw_product_view_recorded', $counter, 'increment', 10, 1 );
		$this->loader->add_action( 'update_option_rvpw_settings', $counter, 'bust_cache' );

		// Follow-up emails.
		$emails = 'RVPW_Recently_Viewed_Products_For_Woocommerce_Emails';
		$this->loader->add_action( 'init', $emails, 'maybe_schedule' );
		$this->loader->add_action( 'init', $emails, 'handle_unsubscribe' );
		$this->loader->add_action( 'rvpw_email_scan', $emails, 'scan' );
		$this->loader->add_action( 'admin_post_rvpw_send_test_email', $emails, 'send_test' );
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
		$this->loader->add_action( 'wp_head', $plugin_public, 'output_custom_css', 99 );

		// Block (registered on init).
		$plugin_block = new RVPW_Recently_Viewed_Products_For_Woocommerce_Block();
		$this->loader->add_action( 'init', $plugin_block, 'register' );

		// Classic sidebar widget.
		$this->loader->add_action( 'widgets_init', $this, 'register_widget' );

		// Elementor widget (the hook only fires when Elementor is active).
		$plugin_elementor = new RVPW_Recently_Viewed_Products_For_Woocommerce_Elementor();
		$this->loader->add_action( 'elementor/widgets/register', $plugin_elementor, 'register_widget' );
	}

	/**
	 * Register the sidebar widget.
	 *
	 * @return void
	 */
	public function register_widget() {
		register_widget( 'RVPW_Recently_Viewed_Products_For_Woocommerce_Widget' );
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

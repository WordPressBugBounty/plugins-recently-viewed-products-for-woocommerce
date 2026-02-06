<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://in.linkedin.com/in/maheshvajapara
 * @since      2.0.0
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/admin
 * @author     Mahesh Patel <p.mahesh8850@gmail.com>
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The version of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string    $woocommerce_recently_viewed_products_options    The current version of this plugin.
	 */
	private $woocommerce_recently_viewed_products_options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in RVPW_Recently_Viewed_Products_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The RVPW_Recently_Viewed_Products_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/recently-viewed-products-for-woocommerce-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in RVPW_Recently_Viewed_Products_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The RVPW_Recently_Viewed_Products_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/recently-viewed-products-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
	}


	/**
	 * Adding admin menu using this function
	 *
	 * @since 2.0.0
	 */
	public function rvpw_woocommerce_recently_viewed_products_add_plugin_page() {
		add_options_page(
			'WooCommerce Recently Viewed Products', // page_title.
			'Recently Viewed Products', // menu_title.
			'manage_options', // capability.
			'recently-viewed-products-for-woocommerce', // menu_slug.
			array( $this, 'rvpw_woocommerce_recently_viewed_products_create_admin_page' ) // function.
		);
	}

	/**
	 * Adding admin menu using this function
	 *
	 * @since 2.0.0
	 */
	public function rvpw_woocommerce_recently_viewed_products_create_admin_page() {
		$this->woocommerce_recently_viewed_products_options = get_option( 'rvpw_woocommerce_recently_viewed_products_option_name' ); ?>

		<div class="wrap">
			<?php echo '<h2>' . esc_html( 'WooCommerce Recently Viewed Products' ) . '</h2>'; ?>			
			<?php
			echo '<p>' . wp_kses_data(
				'Recently Viewed Products Admin Settings Page. You can use Shortcode <b>[rvpw_products title="Recently Viewed Product" limit=4 column=4]</b> to show product on anywhere you want to show.
				Where title is for section title, limit is for display number of product in section and Column is for layout display of products.'
			) . '</p>';
			?>
							

			<form method="post" action="options.php">
				<?php
					settings_fields( 'woocommerce_recently_viewed_products_option_group' );
					do_settings_sections( 'recently-viewed-products-for-woocommerce-admin' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Adding admin menu using this function
	 *
	 * @since 2.0.0
	 */
	public function rvpw_woocommerce_recently_viewed_products_page_init() {
		register_setting(
			'woocommerce_recently_viewed_products_option_group', // option_group.
			'rvpw_woocommerce_recently_viewed_products_option_name', // option_name.
			array( $this, 'woocommerce_recently_viewed_products_sanitize' ) // sanitize_callback.
		);

		add_settings_section(
			'woocommerce_recently_viewed_products_setting_section', // id.
			'Settings', // title.
			array( $this, 'woocommerce_recently_viewed_products_section_info' ), // callback.
			'recently-viewed-products-for-woocommerce-admin' // page.
		);

		add_settings_field(
			'rvpw_title_0', // id.
			'Title', // title.
			array( $this, 'rvpw_title_0_callback' ), // callback.
			'recently-viewed-products-for-woocommerce-admin', // page.
			'woocommerce_recently_viewed_products_setting_section' // section.
		);

		add_settings_field(
			'rvpw_number_of_products_to_display_1', // id.
			'Number of products to display', // title.
			array( $this, 'rvpw_number_of_products_to_display_1_callback' ), // callback.
			'recently-viewed-products-for-woocommerce-admin', // page.
			'woocommerce_recently_viewed_products_setting_section' // section.
		);

		add_settings_field(
			'rvpw_product_layout_option_2', // id.
			'Product Layout Option', // title.
			array( $this, 'rvpw_product_layout_option_2_callback' ), // callback.
			'recently-viewed-products-for-woocommerce-admin', // page.
			'woocommerce_recently_viewed_products_setting_section' // section.
		);

		add_settings_field(
			'rvpw_hide_section_3', // id.
			'Hide Recently Product Section', // title.
			array( $this, 'rvpw_hide_section_3_callback' ), // callback.
			'recently-viewed-products-for-woocommerce-admin', // page.
			'woocommerce_recently_viewed_products_setting_section' // section.
		);
	}

	/**
	 * Adding admin menu using this function
	 *
	 * @since 2.0.0
	 * @param mixed $input parameter.
	 */
	public function woocommerce_recently_viewed_products_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['rvpw_title_0'] ) ) {
			$sanitary_values['rvpw_title_0'] = sanitize_text_field( $input['rvpw_title_0'] );
		}

		if ( isset( $input['rvpw_number_of_products_to_display_1'] ) ) {
			$sanitary_values['rvpw_number_of_products_to_display_1'] = sanitize_text_field( $input['rvpw_number_of_products_to_display_1'] );
		}

		if ( isset( $input['rvpw_product_layout_option_2'] ) ) {
			$sanitary_values['rvpw_product_layout_option_2'] = $input['rvpw_product_layout_option_2'];
		}

		if ( isset( $input['rvpw_hide_section_3'] ) ) {
			$sanitary_values['rvpw_hide_section_3'] = sanitize_text_field( $input['rvpw_hide_section_3'] );
		}

		return $sanitary_values;
	}

	/**
	 * Settings main menu call back function.
	 *
	 * @since 2.0.0
	 */
	public function woocommerce_recently_viewed_products_section_info() {
	}

	/**
	 * Callback function Product title field
	 *
	 * @since 2.0.0
	 */
	public function rvpw_title_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="rvpw_woocommerce_recently_viewed_products_option_name[rvpw_title_0]" id="rvpw_title_0" value="%s">',
			isset( $this->woocommerce_recently_viewed_products_options['rvpw_title_0'] ) ? esc_attr( $this->woocommerce_recently_viewed_products_options['rvpw_title_0'] ) : ''
		);
	}

	/**
	 * Callback function Product display Option
	 *
	 * @since 2.0.0
	 */
	public function rvpw_number_of_products_to_display_1_callback() {
		printf(
			'<input class="regular-text" type="number" name="rvpw_woocommerce_recently_viewed_products_option_name[rvpw_number_of_products_to_display_1]" id="rvpw_number_of_products_to_display_1" value="%s">',
			isset( $this->woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1'] ) ? esc_attr( $this->woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1'] ) : ''
		);
	}

	/**
	 * Callback function Product Layout Option
	 *
	 * @since 2.0.0
	 */
	public function rvpw_product_layout_option_2_callback() {
		?>
		 
		<select name="rvpw_woocommerce_recently_viewed_products_option_name[rvpw_product_layout_option_2]" id="rvpw_product_layout_option_2">
			<?php $selected = ( isset( $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) && '2' === $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) ? 'selected' : ''; ?>
			<option value="2" <?php echo esc_attr( $selected ); ?>>2 Column</option>
			<?php $selected = ( isset( $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) && '3' === $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) ? 'selected' : ''; ?>
			<option value="3" <?php echo esc_attr( $selected ); ?>>3 Column</option>
			<?php $selected = ( isset( $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) && '4' === $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) ? 'selected' : ''; ?>
			<option value="4" <?php echo esc_attr( $selected ); ?>>4 Column</option>
			<?php $selected = ( isset( $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) && '5' === $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) ? 'selected' : ''; ?>
			<option value="5" <?php echo esc_attr( $selected ); ?>>5 Column</option>
			<?php $selected = ( isset( $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) && '6' === $this->woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2'] ) ? 'selected' : ''; ?>
			<option value="6" <?php echo esc_attr( $selected ); ?>>6 Column</option>
		</select>
		<?php
	}

	/**
	 * Callback function Product title field
	 *
	 * @since 2.0.0
	 */
	public function rvpw_hide_section_3_callback() {
		printf(
			'<input class="regular-text" type="checkbox" name="rvpw_woocommerce_recently_viewed_products_option_name[rvpw_hide_section_3]" id="rvpw_hide_section_3" value="true" %s>',
			( isset( $this->woocommerce_recently_viewed_products_options['rvpw_hide_section_3'] ) && 'true' === $this->woocommerce_recently_viewed_products_options['rvpw_hide_section_3'] ) ? 'checked' : ''
		);
	}

	/**
	 * Add settings link to plugin actions
	 *
	 * @param  array  $plugin_actions The list of links action.
	 * @param  string $plugin_file  Provide 'Settings' link file.
	 * @return array
	 */
	public function rvpw_settings_link( $plugin_actions, $plugin_file ) {

		$new_actions = array();
		if ( 'recently-viewed-products-for-woocommerce/recently-viewed-products-for-woocommerce.php' === $plugin_file ) {
			$url = get_admin_url() . 'options-general.php?page=recently-viewed-products-for-woocommerce';
			/* translators: %s: Settings title */
			$new_actions['cl_settings'] = sprintf( __( '<a href="%s">Settings</a>', 'recently-viewed-products-for-woocommerce' ), esc_url( $url ) );
		}

		return array_merge( $new_actions, $plugin_actions );
	}
}

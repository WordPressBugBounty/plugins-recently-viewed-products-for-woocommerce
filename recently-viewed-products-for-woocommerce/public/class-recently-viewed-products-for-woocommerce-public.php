<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://in.linkedin.com/in/maheshvajapara
 * @since      2.0.0
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    RVPW_Recently_Viewed_Products_For_Woocommerce
 * @subpackage RVPW_Recently_Viewed_Products_For_Woocommerce/public
 * @author     Mahesh Patel <p.mahesh8850@gmail.com>
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Public
{
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
     * Initialize the class and set its properties.
     *
     * @since    2.0.0
     * @param      string $plugin_name       The name of the plugin.
     * @param      string $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        add_shortcode('rvpw_products', array( $this, 'rvpw_recently_viewed_products_for_woocommerce_shortcode' ));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    2.0.0
     */
    public function enqueue_styles()
    {

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
        if (is_product()) {
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/recently-viewed-products-for-woocommerce-public.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    2.0.0
     */
    public function enqueue_scripts()
    {

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
        if (is_product()) {
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/recently-viewed-products-for-woocommerce-public.js', array( 'jquery' ), $this->version, false);
        }
    }

    /*****
     * Set cookies uisng this function of product which are seen recently
     *
     * @since 2.0.0
     */
    public static function rvpw_setcookies()
    {

        if (! is_singular('product')) {

            return;
        }
        global $post;
        $currentblogid = get_current_blog_id();

        if (empty($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ])) {

            $viewed_products = array();
        } else {

            $viewed_products = (array) explode('|', sanitize_text_field(wp_unslash($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ])));
        }

        if (in_array($post->ID, $viewed_products, true)) {
            $viewed_products   = array_diff($viewed_products, array( $post->ID ));
            $viewed_products[] = $post->ID;
        } else {
            $viewed_products[] = $post->ID;
        }

        $woocommerce_recently_viewed_products_options = get_option('rvpw_woocommerce_recently_viewed_products_option_name'); // Array of All Options.
        $rvpw_number_of_products_to_display_1              = isset($woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1']) ? esc_attr($woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1']) : 4;
       
        // Store for session only.

        wc_setcookie('rvpw_woocommerce_recently_viewed_' . $currentblogid, implode('|', $viewed_products));
    }



    /**
     * Recently view product function which can populate the woocommerce template file
     *
     * @since 2.0.0
     */
    public static function rvpw_recently_viewed_products_woo()
    {


        $currentblogid = get_current_blog_id();

        // Get recently viewed product cookies data.
        $viewed_products_front = ! empty($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ]) ? (array) explode('|', sanitize_text_field(wp_unslash($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ]))) : array();

        $viewed_products_front = array_filter(array_map('absint', $viewed_products_front));

        $woocommerce_recently_viewed_products_options =  get_option('rvpw_woocommerce_recently_viewed_products_option_name') ; // Array of All Options.
      
		$rvpw_hide_section_3                               = isset($woocommerce_recently_viewed_products_options['rvpw_hide_section_3']) ? esc_attr($woocommerce_recently_viewed_products_options['rvpw_hide_section_3']) : '';
        $rvpw_title_0                                      = isset($woocommerce_recently_viewed_products_options['rvpw_title_0']) ? esc_attr($woocommerce_recently_viewed_products_options['rvpw_title_0']) : 'Recently Viewed Products'; // Title.
        $rvpw_number_of_products_to_display_1              = isset($woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1']) ? esc_attr($woocommerce_recently_viewed_products_options['rvpw_number_of_products_to_display_1']) : 4; // Number of products to display.
        $rvpw_product_layout_option_2                      = isset($woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2']) ? esc_attr($woocommerce_recently_viewed_products_options['rvpw_product_layout_option_2']): 4; // Product Layout Option.

        // If no data, quit.
        if (empty($viewed_products_front) || 'true' === $rvpw_hide_section_3) {
            return '';
        }

        $cuid = get_the_ID();

        $viewed_products_front = array_diff($viewed_products_front, array( $cuid ));

        $pid = esc_attr(implode(',', array_reverse($viewed_products_front)));

        echo '<section class="rvpw-recently-view products">';

        echo '<h2 class="h4">' . esc_html($rvpw_title_0) . '</h2>';

        $limit  = isset($rvpw_number_of_products_to_display_1) ? esc_attr($rvpw_number_of_products_to_display_1) : 4;
        $column = isset($rvpw_product_layout_option_2) ? esc_attr($rvpw_product_layout_option_2) : 4;

        echo do_shortcode("[products limit='$limit' ids='$pid' columns='$column' orderby='post__in']");

        echo '</section>';
    }



    /**
     * Recently view product function shortcode
     *
     * @param mixed $atts This parameter is for shortcode attributes.
     * @param mixed $content This parameter is for content.
     * @since 2.0.0
     */
    public function rvpw_recently_viewed_products_for_woocommerce_shortcode($atts = array(), $content = null)
    {
        ob_start();
        // Get shortcode parameters.

        $attri = shortcode_atts(
            array(
                'title'  => 'Recently Viewed Products',
                'limit'  => '4',
                'column' => '4',
            ),
            $atts
        );

        global $woocommerce;

        $currentblogid = get_current_blog_id();

        // Get recently viewed product cookies data.
        $viewed_products_front = ! empty($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ]) ? (array) explode('|', sanitize_text_field(wp_unslash($_COOKIE[ 'rvpw_woocommerce_recently_viewed_' . $currentblogid ]))) : array();

        $viewed_products_front = array_filter(array_map('absint', $viewed_products_front));

        // If no data, quit.
        if (empty($viewed_products_front)) {
            return '';
        }

        $cuid = get_the_ID();

        $viewed_products_front = array_diff($viewed_products_front, array( $cuid ));

        $pid = implode(',', array_reverse($viewed_products_front));

        $content .= '<section class="rvpw-recently-view products">';
        $title    = isset($attri['title']) ? esc_html($attri['title']) : 'Recently Viewed Products';
        $content .= '<h2 class="h4">' . esc_html($title) . '</h2>';

        $limit  = isset($attri['limit']) ? esc_attr($attri['limit']) : 4;
        $column = isset($attri['column']) ? esc_attr($attri['column']) : 4;

        $content .= do_shortcode("[products limit='$limit' ids='$pid' columns='$column' orderby='post__in']");

        $content .= '</section>';

        $content .= ob_get_clean();
        return $content;
    }
}

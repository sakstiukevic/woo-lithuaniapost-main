<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct ( $plugin_name, $version )
    {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles ()
    {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Lithuaniapost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Lithuaniapost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-lithuaniapost-multiselect-dropdown.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . 'shipping', plugin_dir_url( __FILE__ ) . 'css/woo-lithuaniapost-shipping.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . 'admin', plugin_dir_url( __FILE__ ) . 'css/woo-lithuaniapost-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . 'orders', plugin_dir_url( __FILE__ ) . 'css/woo-lithuaniapost-orders.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts ()
    {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Lithuaniapost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Lithuaniapost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-lithuaniapost-multiselect-dropdown.js', array( 'jquery' ), $this->version, false );
        wp_register_script( $this->plugin_name .'-shipping', plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost-shipping.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name. '-admin', plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost-admin.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name. '-orders', plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost-orders.js', array( 'jquery'), $this->version, false );
		wp_localize_script( $this->plugin_name, 'woo_lithuaniapost_admin', array( 'ajax_url' => admin_url ( 'admin-ajax.php' ) ) );
	}

    /**
     * Add statuses to WooCommerce
     *
     * @param $order_statuses
     * @return array
     */
    public function order_statuses ( $order_statuses )
    {
        $order_statuses [ LpOrderStatus::$COURIER_PENDING->to_order_status() ]  = _x( 'Courier Pending', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$PARCEL_CREATED->to_order_status() ] = _x( 'Parcel Created', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$PARCEL_FAILED->to_order_status() ] = _x( 'Parcel Create Failed', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$SHIPPING_INITIATED->to_order_status() ] = _x( 'Shipment Created', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$COURIER_CALLED->to_order_status() ]  = _x( 'Courier Called', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$ON_THE_WAY->to_order_status() ]  = _x( 'On the way', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$PARCEL_DELIVERED->to_order_status() ]  = _x( 'Delivered', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$PARCEL_CANCELED->to_order_status() ]  = _x( 'Cancelled', 'Order status', 'woo-lithuaniapost' );
        $order_statuses [ LpOrderStatus::$PARCEL_PENDING->to_order_status() ]  = _x( 'On-hold', 'Order status', 'woo-lithuaniapost' );

        return $order_statuses;
    }

    /**
     * Register custom order statuses
     *
     * @since 1.0.0
     */
    public function register_order_statuses($statuses)
    {
        $statuses[LpOrderStatus::$COURIER_PENDING->to_order_status()] = [
            'label' => _x('Courier Pending', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            'label_count' => _n_noop('Courier Pending <span class="count">(%s)</span>', 'Courier Pending <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];
        $statuses[LpOrderStatus::$PARCEL_CREATED->to_order_status()] = [
            'label' => _x('Parcel Created', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Parcel Created <span class="count">(%s)</span>', 'Parcel Created <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];
        $statuses[LpOrderStatus::$PARCEL_FAILED->to_order_status()] = [
            'label' => _x('Parcel Create Failed', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Parcel Create Failed <span class="count">(%s)</span>', 'Parcel Create Failed <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];

        $statuses[LpOrderStatus::$SHIPPING_INITIATED->to_order_status()] = [
            'label' => _x('Shipment Created', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Shipment Created <span class="count">(%s)</span>', 'Shipment Created <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];

        $statuses[LpOrderStatus::$COURIER_CALLED->to_order_status()] = [
            'label' => _x('Courier Called', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Courier Called <span class="count">(%s)</span>', 'Courier Called <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];

        $statuses[LpOrderStatus::$ON_THE_WAY->to_order_status()] = [
            'label' => _x('On the way', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('On the way <span class="count">(%s)</span>', 'On the way <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];

        $statuses[LpOrderStatus::$PARCEL_DELIVERED->to_order_status()] = [
            'label' => _x('Delivered', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];

        $statuses[LpOrderStatus::$PARCEL_CANCELED->to_order_status()] = [
            'label' => _x('Cancelled', 'Order status', 'woocommerce'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => false,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woo-lithuaniapost')
        ];
        return $statuses;
    }

    /**
     * Display terminal inside order view
     *
     * @param int $order_id
     * @since 1.0.0
     */
	public function display_terminal_order ( $order_id )
    {
        $order = wc_get_order($order_id);
        if ($terminal = $order->get_meta('_woo_lithuaniapost_lpexpress_terminal')):
       ?>
            <tr class="shipping">
                <td class="thumb"><div></div></td>
                <td colspan="5">
                    <?php echo sprintf ( '<b>%s</b>: %s',
                        __( 'Parcel Locker', 'woo-lithuaniapost' ), $terminal
                    ); ?>
                </td>
            </tr>
       <?php endif;
    }

    /**
     * Export table rates
     *
     * @return false|resource
     * @since 1.0.0
     */
    public function export_table_rates ()
    {
        global $wpdb;

        // Shipping method ID
        $method_id = $_GET [ 'method' ];

        // File name
        $filename = str_replace ( 'woo_lithuaniapost_', 'tablerates_', $method_id . '.csv' );

        woo_lithuaniapost_file_headers ( $filename, 'text/csv' );

        /**
         * Create a file pointer connected to the output stream
         */
        $output = fopen ( 'php://output', 'w' );

        /**
         * Output the column headings
         */
        fputcsv ( $output, [ __( 'Weight To', 'woo-lithuaniapost' ), __( 'Price', 'woo-lithuaniapost' ) ] );

        /**
         * Fill data
         */
        $results = $wpdb->get_results (
            $wpdb->prepare ( "SELECT weight_to, price FROM {$wpdb->woo_lithuaniapost_table_rates} 
                        WHERE method_id = %s", $method_id ), ARRAY_A
        );

        foreach ( $results as $key => $rate ) {
            $modified_values = [ $rate [ 'weight_to' ], $rate [ 'price' ] ];
            fputcsv ( $output, $modified_values );
        }

        return $output;
    }

    /**
     * Export country rates
     *
     * @return false|resource
     * @since 1.0.0
     */
    public function export_country_rates ()
    {
        global $wpdb;

        // Shipping method ID
        $method_id = $_GET [ 'method' ];

        // File name
        $filename = str_replace ( 'woo_lithuaniapost_', 'countryrates_', $method_id . '.csv' );

        woo_lithuaniapost_file_headers ( $filename, 'text/csv' );

        /**
         * Create a file pointer connected to the output stream
         */
        $output = fopen ( 'php://output', 'w' );

        /**
         * Output the column headings
         */
        fputcsv ( $output, [ __( 'Country', 'woo-lithuaniapost' ), __( 'Price', 'woo-lithuaniapost' ) ] );

        /**
         * Fill data
         */
        $results = $wpdb->get_results (
            $wpdb->prepare ( "SELECT country, price FROM {$wpdb->woo_lithuaniapost_country_rates} 
                        WHERE method_id = %s", $method_id ), ARRAY_A
        );

        foreach ( $results as $key => $rate ) {
            $modified_values = [ $rate [ 'country' ], $rate [ 'price' ] ];
            fputcsv ( $output, $modified_values );
        }

        return $output;
    }

    /**
     * Export country weight rates
     *
     * @return false|resource
     * @since 1.0.0
     */
    public function export_country_weight_rates ()
    {
        global $wpdb;

        // Shipping method ID
        $method_id = $_GET [ 'method' ];

        // File name
        $filename = str_replace ( 'woo_lithuaniapost_', 'countryrates_weight_', $method_id . '.csv' );

        woo_lithuaniapost_file_headers ( $filename, 'text/csv' );

        /**
         * Create a file pointer connected to the output stream
         */
        $output = fopen ( 'php://output', 'w' );

        /**
         * Output the column headings
         */
        fputcsv ( $output, [ __( 'Country', 'woo-lithuaniapost' ), __( 'Weight To (kg)', 'woo-lithuaniapost' ), __( 'Price', 'woo-lithuaniapost' ) ] );

        /**
         * Fill data
         */
        $results = $wpdb->get_results (
            $wpdb->prepare ( "SELECT country, weight, price FROM {$wpdb->woo_lithuaniapost_country_weight_rates} 
                        WHERE method_id = %s", $method_id ), ARRAY_A
        );

        foreach ( $results as $key => $rate ) {
            $modified_values = [ $rate [ 'country' ], $rate [ 'weight' ], $rate [ 'price' ] ];
            fputcsv ( $output, $modified_values );
        }

        return $output;
    }

    /**
     * Add allowed terminal checkbox to product admin
     *
     * @since 2.1.1
     */
    public function add_allowed_terminal_checkbox ()
    {
        global $post;

        $product = wc_get_product($post->ID);
        if ($product && $product->is_virtual('yes')) {
            return false;
        }

        $input_checkbox = get_post_meta ( $post->ID, 'woo_lithuaniapost_allowed_terminal', true );
        if ( empty ( $input_checkbox ) ) $input_checkbox = '';

        $parcel_locker_input_checkbox = get_post_meta ( $post->ID, 'woo_lithuaniapost_allowed_terminal_parcel_locker', true );
        if ( empty ( $parcel_locker_input_checkbox ) ) $parcel_locker_input_checkbox = '';

        woocommerce_wp_checkbox ( [
            'id'            => 'woo_lithuaniapost_allowed_terminal',
            'label'         => __( 'UNISEND', 'woo-lithuaniapost' ),
            'description'   => __( 'Disable UNISEND shipping for this product', 'woo-lithuaniapost' ),
            'value'         => $input_checkbox,
        ] );

        woocommerce_wp_checkbox ( [
            'id'            => 'woo_lithuaniapost_allowed_terminal_parcel_locker',
            'label'         => __( 'UNISEND parcel locker', 'woo-lithuaniapost' ),
            'description'   => __( 'Disable UNISEND shipping to parcel locker for this product', 'woo-lithuaniapost' ),
            'value'         => $parcel_locker_input_checkbox,
        ] );
    }

    /**
     * Save allowed UNISEND terminal checkbox
     *
     * @param int $post_id
     */
    public function save_allowed_terminal_checkbox ( $post_id )
    {
        $_terminal_option = isset ( $_POST [ 'woo_lithuaniapost_allowed_terminal' ] ) ? 'yes' : '';
        update_post_meta ( $post_id, 'woo_lithuaniapost_allowed_terminal', $_terminal_option );
        $_parcel_locker_terminal_option = isset ( $_POST [ 'woo_lithuaniapost_allowed_terminal_parcel_locker' ] ) ? 'yes' : '';
        update_post_meta ( $post_id, 'woo_lithuaniapost_allowed_terminal_parcel_locker', $_parcel_locker_terminal_option );
    }
}

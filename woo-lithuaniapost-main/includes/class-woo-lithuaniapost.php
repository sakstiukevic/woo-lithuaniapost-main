<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woo_Lithuaniapost_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct ()
    {
		if ( defined ( 'WOO_LITHUANIAPOST_VERSION' ) ) {
			$this->version = WOO_LITHUANIAPOST_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		if ( !defined ( 'WOO_LITHUANIAPOST_PLUGIN_BASENAME' ) ) {
            define( 'WOO_LITHUANIAPOST_PLUGIN_BASENAME', plugin_basename( WOO_LITHUANIAPOST_PLUGIN_FILE ) );
		}

		$this->plugin_name = 'woo-lithuaniapost';

		$this->define_tables ();
		$this->load_dependencies ();
		$this->set_locale ();
		$this->define_admin_hooks ();
		$this->define_public_hooks ();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woo_Lithuaniapost_Loader. Orchestrates the hooks of the plugin.
	 * - Woo_Lithuaniapost_i18n. Defines internationalization functionality.
	 * - Woo_Lithuaniapost_Admin. Defines all hooks for the admin area.
     * - Woo_Lithuaniapost_Admin_Settings. Defines all hooks for the settings.
	 * - Woo_Lithuaniapost_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies ()
    {
        /**
         * Plugin functions
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'includes/woo-lithuaniapost-functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'includes/class-woo-lithuaniapost-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'includes/class-woo-lithuaniapost-i18n.php';

         /**
         * The class responsible for shipping cost
         * of the plugin.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'includes/shipping/rates/includes/html-settings-cost.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin.php';

        /**
         * The class responsible for defining all actions that occur in the admin area settings.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-actions.php';

        /**
         * The class responsible for defining all bulk actions that occur in the admin area settings.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-bulk-actions.php';

        /**
         * The class responsible for defining all actions that occur in the admin area settings.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-settings.php';

        /**
         * The class responsible for defining all actions that occur in the admin area settings.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-settings.php';

        /**
         * The class responsible for shipment tracking info
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-tracking.php';

        /**
         * The class responsible for defining all actions that occur in the admin area settings.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-meta-box.php';

        /**
         * The class responsible for defining all api actions.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-api-hooks.php';
        /**
         * The class responsible for defining all LP order actions.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-order-service.php';
        /**
         * The class responsible for defining all LP order actions.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-error-handler.php';


        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-terminal-service.php';


        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/class-woo-lithuaniapost-admin-size-service.php';


        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/util/class-woo-lithuaniapost-admin-util.php';

        /**
         * The class responsible for defining all courier scheduled actions.
         */
        require_once plugin_dir_path( dirname ( __FILE__ ) ) . 'admin/schedule/class-woo-lithuaniapost-admin-courier-schedule.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path ( dirname ( __FILE__ ) ) . 'public/class-woo-lithuaniapost-public.php';

		$this->loader = new Woo_Lithuaniapost_Loader ();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woo_Lithuaniapost_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale ()
    {
		$plugin_i18n = new Woo_Lithuaniapost_i18n ();
		$this->loader->add_action ( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

    /**
     * Load shipping classes
     *
     * @since    1.0.0
     * @access   public
     */
	public function shipping_init ()
    {
        // Class responsible for table rates
        if ( ! class_exists ( 'Woo_Lithuaniapost_Table_Rates' ) ) {
            include_once plugin_dir_path ( dirname ( __FILE__ ) ) .
                'includes/shipping/rates/class-woo-lithuaniapost-tablerates.php';
        }

        // Class responsible for country rates
        if ( ! class_exists ( 'Woo_Lithuaniapost_Country_Rates' ) ) {
            include_once plugin_dir_path ( dirname ( __FILE__ ) ) .
                'includes/shipping/rates/class-woo-lithuaniapost-country-rates.php';
        }

        // UNISEND - Terminal shipping method
        if ( ! class_exists ( 'Woo_Lithuaniapost_Shipping_Lpexpress_Terminal' ) ) {
            include_once plugin_dir_path ( dirname ( __FILE__ ) ) .
                'includes/shipping/lpexpress-terminal/class-woo-lithuaniapost-shipping-lpexpress-terminal.php';
        }
    }

    /**
     * Load shipping methods.
     * @param array $methods
     * @return array
     *
     * @since    1.0.0
     * @access   public
     */
    public function load_shipping_methods ( $methods )
    {
        // Register main classes
        $methods [ 'woo_lithuaniapost_lpexpress_terminal' ]     = 'Woo_Lithuaniapost_Shipping_Lpexpress_Terminal';

        return $methods;
    }

    /**
     * Register custom tables within $wpdb object.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_tables ()
    {
        global $wpdb;

        // List of tables without prefixes.
        $tables = [
            'woo_lithuaniapost_api_token'               => 'woo_lithuaniapost_api_token',
            'woo_lithuaniapost_lpexpress_terminals'     => 'woo_lithuaniapost_lpexpress_terminals',
            'woo_lithuaniapost_unisend_terminals'       => 'woo_lithuaniapost_unisend_terminals',
            'woo_lithuaniapost_table_rates'             => 'woo_lithuaniapost_table_rates',
            'woo_lithuaniapost_country_rates'           => 'woo_lithuaniapost_country_rates',
            'woo_lithuaniapost_country_weight_rates'    => 'woo_lithuaniapost_country_weight_rates',
            'woo_lithuaniapost_shipping_request'        => 'woo_lithuaniapost_shipping_request',
            'woo_lithuaniapost_tracking_events'         => 'woo_lithuaniapost_tracking_events',
            'woo_lithuaniapost_tracking_status'         => 'woo_lithuaniapost_tracking_status'
        ];

        foreach ( $tables as $name => $table ) {
            $wpdb->$name    = $wpdb->prefix . $table;
            $wpdb->tables[] = $table;
        }
    }

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks ()
    {
        $plugin_admin               = new Woo_Lithuaniapost_Admin ( $this->get_plugin_name (), $this->get_version () );
        $plugin_admin_settings      = new Woo_Lithuaniapost_Admin_Settings ( $this->get_plugin_name (), $this->get_version () );
        $plugin_admin_actions       = new Woo_Lithuaniapost_Admin_Order_Actions ( $this->get_plugin_name (), $this->get_version () );
        $plugin_admin_bulk_actions  = new Woo_Lithuaniapost_Admin_Order_Bulk_Actions ( $this->get_plugin_name (), $this->get_version () );
        $plugin_api_hooks           = new Woo_Lithuaniapost_Admin_Api_Hooks ( $this->get_plugin_name (), $this->get_version () );
        $plugin_order_tracking      = new Woo_Lithuaniapost_Admin_Order_Tracking ( $this->get_plugin_name (), $this->get_version () );
        $plugin_admin_meta_box      = new Woo_Lithuaniapost_Admin_Order_Meta_Box ( $this->get_plugin_name (), $this->get_version () );
        $plugin_shipping_settings   = new Woo_Lithuaniapost_Settings_Cost();
        $plugin_admin_order_settings= new Woo_Lithuaniapost_Admin_Order_Settings ();
        $plugin_admin_courier_schedule = new Woo_Lithuaniapost_Admin_Courier_Schedule($this->get_plugin_name(), $this->get_version());
        $plugin_admin_order_service = new Woo_Lithuaniapost_Admin_Order_Service($this->get_plugin_name(), $this->get_version());
        $plugin_admin_size_service = new Woo_Lithuaniapost_Admin_Size_Service($this->get_plugin_name(), $this->get_version());
        $plugin_admin_error_handler = new Woo_Lithuaniapost_Admin_Error_Handler($this->get_plugin_name(), $this->get_version());
        $plugin_admin_util = new Woo_Lithuaniapost_Admin_Util($this->get_plugin_name(), $this->get_version());
        $plugin_admin_terminal_service = new Woo_Lithuaniapost_Admin_Terminal_Service($this->get_plugin_name(), $this->get_version());


		/**
         * Admin hooks
         */
		$this->loader->add_action ('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action ('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action ('wc_order_statuses', $plugin_admin, 'order_statuses' );
        $this->loader->add_action ('admin_post_woo-ltpost-export-table-rates', $plugin_admin, 'export_table_rates' );
        $this->loader->add_action ('admin_post_woo-ltpost-export-country-rates', $plugin_admin, 'export_country_rates' );
        $this->loader->add_action ('admin_post_woo-ltpost-export-country-weight-rates', $plugin_admin, 'export_country_weight_rates' );
        $this->loader->add_action ('woocommerce_admin_order_items_after_shipping', $plugin_admin, 'display_terminal_order', 10, 1);
        $this->loader->add_action ('woocommerce_product_options_shipping_product_data', $plugin_admin, 'add_allowed_terminal_checkbox' );
        $this->loader->add_action ('woocommerce_process_product_meta', $plugin_admin, 'save_allowed_terminal_checkbox' );
        $this->loader->add_action ('woocommerce_register_shop_order_post_statuses', $plugin_admin, 'register_order_statuses' );

        $this->loader->add_action ('admin_menu', $plugin_admin_order_settings, 'register_menu' );
        $this->loader->add_action ('woocommerce_screen_ids', $plugin_admin_order_settings, 'handle_woocommerce_screen_ids' );
        $this->loader->add_action ('admin_post_woo-ltpost-create-label', $plugin_admin_order_settings, 'generate_label' );
        $this->loader->add_action ('admin_post_woo-ltpost-create-parcel', $plugin_admin_order_settings, 'create_parcel' );
        $this->loader->add_action( 'posts_join', $plugin_admin_order_settings,'join_shipping_method', 10, 2 );
        $this->loader->add_action( 'posts_where', $plugin_admin_order_settings,'where_shipping_method', 10, 2 );
        $this->loader->add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $plugin_admin_order_settings,'handle_custom_query_var', 10, 2 );

        /**
         * Create settings section
         */
		$this->loader->add_action ('woocommerce_get_sections_shipping', $plugin_admin_settings, 'create_settings_section' );

        /**
         * Add settings section
         */
        $this->loader->add_action ('woocommerce_get_settings_shipping', $plugin_admin_settings, 'add_settings_section', 10, 2);

        $this->loader->add_action ('woocommerce_admin_field_call_courier_time_picker', $plugin_admin_settings, 'generate_call_courier_time_picker_html');
        $this->loader->add_action('plugin_action_links_' . WOO_LITHUANIAPOST_PLUGIN_BASENAME, $plugin_admin_settings, 'plugin_action_links');
        /**
         * Add custom actions to order actions dropdown
         */
        $this->loader->add_action ('woocommerce_order_actions', $plugin_admin_actions, 'add_order_actions' );

        /**
         * Process action
         */
        $this->loader->add_action ('woocommerce_order_action_woo_lp_create_parcel', $plugin_admin_actions, 'process_create_parcel' );
        $this->loader->add_action ('woocommerce_order_action_woo_lp_cancel_label', $plugin_admin_actions, 'process_cancel_label' );
        $this->loader->add_action ('woocommerce_order_action_woo_lp_print_label', $plugin_admin_actions, 'process_print_label' );
        $this->loader->add_action ('woocommerce_order_action_woo_lp_print_manifest', $plugin_admin_actions, 'process_print_manifest' );
        $this->loader->add_filter ( 'woo_lithuaniapost_get_shipment_data', $plugin_admin_actions, 'get_shipment_data', 10, 1 );

        /**
         * Bulk actions
         */

        $this->loader->add_filter ( 'bulk_actions-edit-shop_order', $plugin_admin_bulk_actions, 'register_bulk_actions', 20, 1 );
        $this->loader->add_filter ( 'bulk_actions-woocommerce_page_wc-orders', $plugin_admin_bulk_actions, 'register_bulk_actions', 20, 1 );

        /**
         * Bulk action handlers
         */
        $this->loader->add_filter ( 'handle_bulk_actions-edit-shop_order', $plugin_admin_bulk_actions, 'handle_action', 10, 3 );
        $this->loader->add_filter ( 'handle_bulk_actions-woocommerce_page_wc-orders', $plugin_admin_bulk_actions, 'handle_hpos_action', 10, 2 );
        $this->loader->add_action( 'admin_action_print_label', $plugin_admin_order_settings, 'handle_bulk_action');
        $this->loader->add_action( 'admin_action_print_manifest', $plugin_admin_order_settings, 'handle_bulk_action');
        $this->loader->add_action( 'admin_action_filter_orders', $plugin_admin_order_settings, 'handle_bulk_action');
        $this->loader->add_action( 'admin_action_lp-orders-reset-filter', $plugin_admin_order_settings, 'handle_bulk_action');
        /**
         * Error handler
         */
        $this->loader->add_action ( 'admin_notices', $plugin_admin_error_handler, 'display_error' );

        /**
         * Admin util
         */
        $this->loader->add_action('woo_lithuaniapost_admin_util_convert_to_grams', $plugin_admin_util, 'convert_to_grams');
        $this->loader->add_action('woo_lithuaniapost_admin_util_get_min_weight', $plugin_admin_util, 'get_min_weight');

        /**
         * Terminal service handlers
         */
        $this->loader->add_filter ( 'woo_lithuaniapost_terminal_service_get_terminals_by_country_code', $plugin_admin_terminal_service, 'get_terminals_by_country_code', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_terminal_service_get_terminal_by_id', $plugin_admin_terminal_service, 'get_terminal_by_id', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost_terminal_service_update_terminals', $plugin_admin_terminal_service, 'update_terminal_list', 10 );
        $this->loader->add_action ( 'init', $plugin_admin_terminal_service, 'schedule_update_terminal_list' );

        /**
         * Size service handlers
         */
        $this->loader->add_filter ( 'woo_lithuaniapost_size_service_resolve_order_size', $plugin_admin_size_service, 'resolve_order_size', 10, 2 );
        $this->loader->add_filter ( 'woo_lithuaniapost_size_service_resolve_shipping_size', $plugin_admin_size_service, 'resolve_shipping_size', 10, 2 );

        /**
         * Order service handlers
         */
        $this->loader->add_filter ( 'woocommerce_order_status_changed', $plugin_admin_order_service, 'on_status_changed', 10, 4 );
        $this->loader->add_filter ( 'woocommerce_order_get_lp_order_statuses', $plugin_admin_order_service, 'get_lp_order_statuses' );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_generate_stickers', $plugin_admin_order_service, 'handle_generate_stickers', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_generate_manifests', $plugin_admin_order_service, 'handle_generate_manifests', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_call_courier', $plugin_admin_order_service, 'handle_call_courier', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_create_parcel', $plugin_admin_order_service, 'handle_create_parcel', 10, 2 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_create_cn_request', $plugin_admin_order_service, 'create_cn_request', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_download_labels', $plugin_admin_order_service, 'download_labels', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_get_available_order_actions', $plugin_admin_order_service, 'get_available_order_actions', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_get_available_order_actions_by_id', $plugin_admin_order_service, 'get_available_order_actions_by_id', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_get_parcel_type_translation', $plugin_admin_order_service, 'get_parcel_type_translation', 10, 1 );
        $this->loader->add_filter ( 'woo_lithuaniapost_order_action_get_plan_translation', $plugin_admin_order_service, 'get_plan_translation', 10, 1 );
        $this->loader->add_action ('wp_ajax_woo_lithuaniapost_handle_parcel_save', $plugin_admin_order_service, 'handle_parcel_save' );
        $this->loader->add_filter ('woo_lithuaniapost__order_action_cancel_labels', $plugin_admin_order_service, 'handle_cancel_labels', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost__order_action_get_lp_shipping_method', $plugin_admin_order_service, 'get_lp_shipping_method', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost__order_action_create_parcel_create_request', $plugin_admin_order_service, 'create_parcel_create_request', 10, 2 );
        $this->loader->add_filter ('woo_lithuaniapost__order_action_get_order_weight_in_g', $plugin_admin_order_service, 'get_order_weight', 10, 1 );
        $this->loader->add_filter ('woocommerce_order_query_args', $plugin_admin_order_service, 'handle_order_query_args', 10, 1 );

        /**
         * API Hooks
         */
        $this->loader->add_filter ('woo_lithuaniapost_api_generate_stickers', $plugin_api_hooks, 'handle_generate_stickers', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost_api_get_manifest', $plugin_api_hooks, 'get_manifest', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost_api_generate_manifest', $plugin_api_hooks, 'handle_generate_manifest', 10, 1 );
		$this->loader->add_filter ('woo_lithuaniapost_api_oauth_refresh_token', $plugin_api_hooks, 'refresh_token', 10 );
		$this->loader->add_filter ('woo_lithuaniapost_api_get_tracking', $plugin_api_hooks, 'get_tracking', 10, 1 );
        $this->loader->add_filter ('woo_lithuaniapost_api_get_tracking_list', $plugin_api_hooks, 'get_tracking_list', 10, 2 );
        $this->loader->add_filter ( 'woo_lithuaniapost_api_is_shipping_available', $plugin_api_hooks, 'handle_is_shipping_available' );
        $this->loader->add_action ( 'woo_lithuaniapost_api_is_address_valid', $plugin_api_hooks, 'handle_address_validation', 10, 2 );
        $this->loader->add_action ( 'woo_lithuaniapost_api_is_parcel_valid', $plugin_api_hooks, 'handle_parcel_validation', 10, 2 );
        $this->loader->add_action ( 'woo_lithuaniapost_api_eshop_activation', $plugin_api_hooks, 'handle_eshop_activation');
        $this->loader->add_action ( 'woo_lithuaniapost_api_eshop_deactivation', $plugin_api_hooks, 'handle_eshop_deactivation');
        $this->loader->add_action ( 'woo_lithuaniapost_api_eshop_uninstall', $plugin_api_hooks, 'handle_eshop_uninstall');
        $this->loader->add_action ( 'woo_lithuaniapost_api_eshop_update', $plugin_api_hooks, 'handle_eshop_update');
        $this->loader->add_action ( 'woo_lithuaniapost_api_eshop_login', $plugin_api_hooks, 'handle_eshop_login');
        $this->loader->add_action ( 'init', $plugin_api_hooks, 'schedule_refresh_token' );

        /**
         * Schedule hooks
         */

		$this->loader->add_filter ( 'init', $plugin_admin_courier_schedule, 'schedule_call_courier' );

        /**
         *  Execute scheduled actions
         */
        $this->loader->add_action ('woo_lithuaniapost_call_courier_schedule_hook', $plugin_admin_courier_schedule, 'lp_call_courier', 10 );

        /**
         * On options update
         */
        $this->loader->add_action ('woo_lithuaniapost_access_token_saved', $plugin_api_hooks, 'run_sequence' );
        $this->loader->add_action ( 'woocommerce_update_options_shipping', $plugin_admin_courier_schedule, 'reschedule_call_courier_on_settings_save');
        $this->loader->add_action ( 'woocommerce_update_options_shipping', $plugin_api_hooks, 'save_access_token');
        $this->loader->add_action ( 'woocommerce_update_options_shipping', $plugin_api_hooks, 'update_sender_address');

        /**
         * Add order meta box
         */
        $this->loader->add_action ('add_meta_boxes', $plugin_admin_meta_box, 'add_order_meta_box' );

        /**
         * Add tracking hooks
         */
        $this->loader->add_filter ( 'woo_lithuaniapost_update_tracking_status', $plugin_order_tracking, 'update_tracking_status' );
        $this->loader->add_filter ( 'woo_lithuaniapost_sync_tracking_data', $plugin_order_tracking, 'sync_tracking_data' );
        $this->loader->add_filter ( 'woo_lithuaniapost_get_tracking_events', $plugin_order_tracking, 'get_tracking_events' );
        $this->loader->add_action ( 'init', $plugin_order_tracking, 'schedule_tracking_info' );
        $this->loader->add_action ( 'init', $plugin_order_tracking, 'schedule_tracking_data_sync' );
        $this->loader->add_action ( 'woo_lithuaniapost_send_tracking_email', $plugin_order_tracking, 'send_tracking_email' );
        $this->loader->add_action ( 'rest_api_init', $plugin_order_tracking, 'register_url_api_endpoint');


        /**
         * Shipping methods initiate and load
         */
        $this->loader->add_action ('woocommerce_shipping_init', $this, 'shipping_init' );
        $this->loader->add_action ('woocommerce_shipping_methods', $this, 'load_shipping_methods' );

        /**
         * Shipping method settings
         */
        $this->loader->add_filter ( 'woocommerce_generate_fixed_cost_html', $plugin_shipping_settings, 'generate_fixed_cost_html', 10, 4);
        $this->loader->add_filter ( 'woocommerce_generate_weight_cost_html', $plugin_shipping_settings, 'generate_weight_cost_html', 10, 4);
        $this->loader->add_filter ( 'woocommerce_generate_size_cost_html', $plugin_shipping_settings, 'generate_size_cost_html', 10, 4);
    }


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks ()
    {
		$plugin_public = new Woo_Lithuaniapost_Public ( $this->get_plugin_name (), $this->get_version () );

		$this->loader->add_action ( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action ( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $this->loader->add_filter ( 'woocommerce_after_shipping_rate', $plugin_public, 'render_terminal_field', 20, 2 );
        $this->loader->add_filter ( 'woocommerce_after_shipping_rate', $plugin_public, 'render_delivery_time', 20, 2 );
        $this->loader->add_filter ( 'woocommerce_after_get_rates_for_package', $plugin_public, 'handle_after_get_rates_for_package', 20, 2 );
        $this->loader->add_filter ( 'woocommerce_checkout_process', $plugin_public, 'validate_shipping' );
        $this->loader->add_action ( 'woocommerce_email_format_string', $plugin_public, 'add_placeholder_order_email', 10, 3 );
        $this->loader->add_action ( 'woocommerce_store_api_checkout_order_processed', $plugin_public, 'validate_api_shipping' );
        $this->loader->add_action ( 'woocommerce_store_api_checkout_order_processed', $plugin_public, 'handle_checkout_order_processed', 100 );
        $this->loader->add_action ( 'woocommerce_checkout_update_order_meta', $plugin_public, 'handle_update_order_meta_by_id', 30, 1 );
        $this->loader->add_action ( 'woocommerce_store_api_checkout_update_order_meta', $plugin_public, 'handle_update_order_meta', 30, 1 );
        $this->loader->add_action ( 'wp_ajax_save_selected_lpexpress_terminal', $plugin_public, 'save_selected_terminal_session' );
        $this->loader->add_action ( 'wp_ajax_nopriv_save_selected_lpexpress_terminal', $plugin_public, 'save_selected_terminal_session' );
        $this->loader->add_action ( 'wp_ajax_clear_selected_lpexpress_terminal', $plugin_public, 'clear_selected_terminal_session' );
        $this->loader->add_action ( 'wp_ajax_nopriv_clear_selected_lpexpress_terminal', $plugin_public, 'clear_selected_terminal_session' );
        $this->loader->add_action ( 'wp_ajax_get_terminal_dropdown_html', $plugin_public, 'get_terminal_dropdown_html' );
        $this->loader->add_action ( 'wp_ajax_nopriv_get_terminal_dropdown_html', $plugin_public, 'get_terminal_dropdown_html' );
        $this->loader->add_action ( 'woocommerce_email_order_meta_fields', $plugin_public, 'add_terminal_field_order_email', 10, 3 );
        $this->loader->add_action ( 'woocommerce_thankyou', $plugin_public, 'add_terminal_field_order_thankyou', 10, 1 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run ()
    {
		$this->loader->run ();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name ()
    {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woo_Lithuaniapost_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader ()
    {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version ()
    {
		return $this->version;
	}

}

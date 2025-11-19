<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-courier-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-sticker-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-address-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-terminal-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-shipping-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-oauth-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-tracking-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-parcel-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-eshop-api.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Api_Hooks
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
    private $courier_api;
    private $sticker_api;
    private $address_api;
    private $terminal_api;
    private $shipping_api;
    private $oauth_api;
    private $tracking_api;
    private $parcel_api;
    private $eshop_api;

    public function __construct ( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->courier_api = new Woo_Lithuaniapost_Admin_Courier_Api($plugin_name, $version);
        $this->sticker_api = new Woo_Lithuaniapost_Admin_Sticker_Api($plugin_name, $version);
        $this->terminal_api = new Woo_Lithuaniapost_Admin_Terminal_Api($plugin_name, $version);
        $this->address_api = new Woo_Lithuaniapost_Admin_Address_Api($plugin_name, $version);
        $this->shipping_api = new Woo_Lithuaniapost_Admin_Shipping_Api($plugin_name, $version);
        $this->oauth_api = new Woo_Lithuaniapost_Admin_OAuth_Api($plugin_name, $version);
        $this->tracking_api = new Woo_Lithuaniapost_Admin_Tracking_Api($plugin_name, $version);
        $this->parcel_api = new Woo_Lithuaniapost_Admin_Parcel_Api($plugin_name, $version);
        $this->eshop_api = new Woo_Lithuaniapost_Admin_Eshop_Api($plugin_name, $version);
    }


    /**
     * Refresh token for cron
     *
     * @since 1.0.0
     */
    public function refresh_token ()
    {
        $this->oauth_api->refresh_token();
    }

    /**
     * Schedule refresh token
     *
     * @since 1.0.0
     */
    public function schedule_refresh_token ()
    {
        if (! wp_next_scheduled ( 'woo_lithuaniapost_api_oauth_refresh_token' ) ) {
            wp_schedule_event ( time (), 'hourly', 'woo_lithuaniapost_api_oauth_refresh_token' );
        }
    }

    /**
     * Run API sequence on save settings
     * - Save Terminal List
     * - Save Available Country List
     * - Save Shipping Templates
     *
     * @since 1.0.0
     */
    public function run_sequence ()
    {
        // Run sequence only if in lpsettings section
        if (!get_option('woo_lithuaniapost_module_active')) {
            update_option('woo_lithuaniapost_module_active', true);
            $is_terminals_saved = apply_filters('woo_lithuaniapost_terminal_service_update_terminals', '');

            if ($is_terminals_saved === false) {
                update_option('woo_lithuaniapost_module_active', false);
            }
        }
    }

    public function update_sender_address()
    {
        global $current_section;
        if ($current_section != "lpsettings") {
            return;
        }
        $this->address_api->update_sender_address();
        if (Woo_Lithuaniapost_Admin_Settings::get_option('use_pickup_address') == 'yes') {
            $pickup_address_response = $this->address_api->save_pickup_address();
            if ($pickup_address_response) {
                Woo_Lithuaniapost_Admin_Settings::add_option('woo_lithuaniapost_address_pickup_id', $pickup_address_response->id);
            }
        }
    }

    /**
     * Get manifest base64 encoded pdf
     *
     * @param $order_id
     * @return mixed
     * @since 1.0.0
     */
    public function get_manifest ( $order_id )
    {
        $response = $this->courier_api->get_manifest($order_id);

        if ( $response ) {
            return $response->document;
        }

        return null;
    }

    /**
     * Used for tracking cronjob
     *
     * @param $barcode
     * @return mixed
     */
    public function get_tracking ( $barcode )
    {
        return $this->tracking_api->get_tracking_events($barcode);
    }

    public function get_tracking_list ( array $barcodes, $datetime )
    {
        return $this->tracking_api->get_tracking_events_by_barcodes($barcodes, $datetime);
    }

    public function save_access_token()
    {
        global $current_section;
        if ($current_section != "lpsettings") {
            return;
        }
        if ($this->oauth_api->save_access_token()) {
            $this->handle_eshop_login();
        }
    }

    public function handle_is_shipping_available(array $params):bool {
        return $this->shipping_api->is_shipping_available($params);
    }

    public function handle_address_validation(array $address, callable $error_callback):bool {
        return $this->address_api->validate_address($address, $error_callback);
    }

    public function handle_parcel_validation(array $parcel, callable $error_callback):bool {
        $result = $this->parcel_api->validate_parcel($parcel, $error_callback);
        if ($result) return $result->valid;
        return true;
    }

    public function handle_eshop_activation()
    {
        try {
            $this->eshop_api->activate();
        } catch (Exception|Throwable $e) {
            //ignore
        }
    }

    public function handle_eshop_deactivation()
    {
        try {
            $this->eshop_api->deactivate();
        } catch (Exception|Throwable $e) {
            //ignore
        }
    }

    public function handle_eshop_update()
    {
        try {
            $this->eshop_api->update();
        } catch (Exception|Throwable $e) {
            //ignore
        }
    }

    public function handle_eshop_uninstall()
    {
        try {
            $this->eshop_api->uninstall();
        } catch (Exception|Throwable $e) {
            //ignore
        }
    }

    public function handle_eshop_login()
    {
        try {
            $this->eshop_api->login();
        } catch (Exception|Throwable $e) {
            //ignore
        }
    }
}

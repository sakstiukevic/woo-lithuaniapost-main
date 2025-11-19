<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-lithuaniapost-admin-error-handler.php';


/**
 * WooCommerce custom order actions
 * Create label
 * Call courier
 * Print label
 * Print manifest
 * Print CN23 form
 * Print all documents
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

class Woo_Lithuaniapost_Admin_Order_Actions
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

    private $error_handler;

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
        $this->error_handler = new Woo_Lithuaniapost_Admin_Error_Handler($plugin_name, $version);
    }

    /**
     * Check if shipping method belongs to LP
     *
     * @return bool
     * @since 1.0.o
     */
    protected function is_shipping_method ()
    {
        $order = wc_get_order ( get_the_ID () );

        return $this->is_shipping_method_supported($order);
    }

    protected function is_shipping_method_supported($order)
    {
        return $order->has_shipping_method(Woo_Lithuaniapost_Shipping_Lpexpress_Terminal::ID);
    }

    /**
     * Check if lpexpress method
     *
     * @return bool
     * @since 1.0.0
     */
    protected function is_lpexpress_method ()
    {
        $order = wc_get_order ( get_the_ID () );

        foreach ( $order->get_shipping_methods () as $method ) {
            return strpos ( $method->get_method_id (), 'lpexpress' ) !== false;
        }

        return false;
    }

    /**
     * Add custom order actions
     *
     * @param $actions
     * @return array
     * @since 1.0.0
     */
    public function add_order_actions($actions)
    {
        foreach (apply_filters('woo_lithuaniapost_order_action_get_available_order_actions_by_id', get_the_ID()) as $lp_order_action) {
            switch ($lp_order_action) {
                case LpOrderAction::PRINT_LABEL:
                    $actions ['woo_lp_print_label'] = __('UNISEND Print Shipping Label', 'woo-lithuaniapost');
                    break;
                case LpOrderAction::CREATE_PARCEL:
                    $actions ['woo_lp_create_parcel'] = __('UNISEND Create Shipping Parcel', 'woo-lithuaniapost');
                    break;
                case LpOrderAction::CALL_COURIER:
                    $actions ['woo_lp_print_manifest'] = __('UNISEND Print Manifest', 'woo-lithuaniapost');
                    break;
                case LpOrderAction::PRINT_MANIFEST:
                    $actions ['woo_lp_print_manifest'] = __('UNISEND Print Manifest', 'woo-lithuaniapost');
                    break;
                case LpOrderAction::INIT_SHIPPING:
                    $actions ['woo_lp_print_label'] = __('UNISEND Print Shipping Label', 'woo-lithuaniapost');
                    break;
                case LpOrderAction::CANCEL_SHIPPING:
                    $actions ['woo_lp_cancel_label'] = __('UNISEND Cancel Shipping Label', 'woo-lithuaniapost');
                    break;
            }
        }
        return $actions;
    }

    public function process_create_parcel($order)
    {
        do_action("woo_lithuaniapost_order_action_create_parcel", $order, false);
    }

    /**
     * Print shipping label
     *
     * @param WC_Order $order
     * @since 1.0.0
     */
    public function process_print_label ( $order )
    {
        $result = apply_filters("woo_lithuaniapost_order_action_generate_stickers", [$order->get_id()]);
        if ($result === true) {
            die();
        }
        return $this->handle_result($result, $order);
    }

    public function process_cancel_label($order)
    {
        $result = apply_filters("woo_lithuaniapost__order_action_cancel_labels", [$order->get_id()]);
        return $this->handle_result($result, $order);
    }

    /**
     * Print manifest
     *
     * @param WC_Order $order
     * @since 1.0.0
     */
    public function process_print_manifest ( $order )
    {
        $result = apply_filters("woo_lithuaniapost_order_action_generate_manifests", [$order->get_id()]);
        if ($result === true) {
            die();
        }
        return $this->handle_result($result, $order);
    }

    private function handle_result($result, $order)
    {
        $errors = $result['errors'];
        if ($errors) {
            $this->error_handler->handle_error($errors);
            return $order->get_id();
        }
    }
}

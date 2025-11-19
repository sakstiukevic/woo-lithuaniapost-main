<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-shipping-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-courier-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-lithuaniapost-admin-error-handler.php';

/**
 * WooCommerce custom order bulk actions
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
class Woo_Lithuaniapost_Admin_Order_Bulk_Actions
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

    private $actions_handlers;

    private $shipping_api;
    private $courier_api;
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
        $this->shipping_api = new Woo_Lithuaniapost_Admin_Shipping_Api($plugin_name, $version);
        $this->courier_api = new Woo_Lithuaniapost_Admin_Courier_Api($plugin_name, $version);
        $this->error_handler = new Woo_Lithuaniapost_Admin_Error_Handler($plugin_name, $version);
        $this->actions_handlers['woo_lp_cancel_label'] ='handle_cancel_labels';
        $this->actions_handlers['woo_lp_print_label'] ='handle_print_labels';
        $this->actions_handlers['woo_lp_print_manifest'] ='handle_print_manifests';
    }

    /**
     * Register bulk actions
     *
     * @param $actions
     * @return mixed
     * @since 1.0.0
     */
    public function register_bulk_actions ( $actions )
    {
        $actions [ 'woo_lp_cancel_label' ]    = __( 'UNISEND Cancel Shipping Labels', 'woo-lithuaniapost' );
        $actions [ 'woo_lp_print_label' ]     = __( 'UNISEND Print Shipping Labels', 'woo-lithuaniapost' );
        $actions [ 'woo_lp_print_manifest' ]  = __( 'UNISEND Call courier and generate manifest', 'woo-lithuaniapost' );

        return $actions;
    }

    public function handle_action($redirect_to, $action, $post_ids)
    {
        return $this->process_handle_action($redirect_to, $action, $post_ids);
    }

    public function handle_hpos_action($redirect_to, $action)
    {
        $post_ids = $_GET['id'];
        if (!$post_ids) return $redirect_to;
        return $this->process_handle_action($redirect_to, $action, $post_ids);
    }

    /**
     * Handle cancel labels
     *
     * @param $post_ids
     * @since 1.0.0
     */
    private function handle_cancel_labels(array $post_ids, $redirect_to)
    {
        $result = apply_filters('woo_lithuaniapost__order_action_cancel_labels', $post_ids);
        return $this->handle_result($result, $redirect_to);
    }

    /**
     * Handle print labels
     *
     * @param $post_ids
     */
    private function handle_print_labels(array $post_ids, $redirect_to)
    {
        $result = apply_filters('woo_lithuaniapost_order_action_generate_stickers', $post_ids);
        if ($result === true) {
            exit();
        }
        return $this->handle_result($result, $redirect_to);
    }

    /**
     * Handle print manifests
     *
     * @param $post_ids
     */
    private function handle_print_manifests(array $post_ids, $redirect_to)
    {
        $result = apply_filters("woo_lithuaniapost_order_action_generate_manifests", $post_ids);
        if ($result === true) {
            exit();
        }
        return $this->handle_result($result, $redirect_to);
    }

    private function handle_result($result, $redirect_to)
    {
        $errors = $result['errors'];
        if ($errors) {
            $this->error_handler->handle_error($errors);
        }
        return $redirect_to;
    }

    private function process_handle_action($redirect_to, $action, $post_ids)
    {
        $handler = $this->actions_handlers[$action] ?? null;
        if ($handler) {
            $handler_result = $this->$handler($post_ids, $redirect_to);
            if ($handler_result) {
                if ($handler_result === true) {
                    return;
                }
                return $handler_result;
            }
            return $redirect_to;
        }
    }
}

<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-api.php';

class ShippingStatus
{
    const IN_PROGRESS = "IN_PROGRESS";
    const SUCCESSFUL = "SUCCESSFUL";
    const PARTIALLY_SUCCESSFUL = "PARTIALLY_SUCCESSFUL";
    const ERROR = "ERROR";

    public static function is_status_ok(string $status): bool
    {
        return $status == ShippingStatus::SUCCESSFUL || $status == ShippingStatus::PARTIALLY_SUCCESSFUL;
    }
}

class ShippingItemStatus
{
    const OK = "OK";
    const FAILED = "FAILED";
    const COURIER_PENDING = "COURIER_PENDING";
    const COURIER_CALLED = "COURIER_CALLED";

    public static function is_status_ok(string $status):bool{
        return $status == ShippingItemStatus::OK || $status == ShippingItemStatus::COURIER_PENDING || $status == ShippingItemStatus::COURIER_CALLED;
    }
}

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Shipping_Api extends Woo_Lithuaniapost_Admin_Api
{
    public function __construct($plugin_name, $version)
    {
        parent::__construct($plugin_name, $version);
    }

    /**
     * Authentication gateways
     */
    const INITIATE_URI = 'shipping/initiate?processAsync=false';
    const STATUS_URI = 'shipping/status/';
    const CANCEL_URI = 'shipping/cancel';
    const AVAILABLE_URI = 'shipping/available';

    function initiate(array $order_ids)
    {
        $body["idRefs"] = $order_ids;
        $initiate_result = $this->post(self::INITIATE_URI, $body);
        return $initiate_result;
    }

    function get_status(string $request_id)
    {
        $shipping_status_response = $this->get(self::STATUS_URI . $request_id);
        return $shipping_status_response;
    }

    function cancel(array $order_ids)
    {
        $request = [];
        $request['idRefs'] = $order_ids;
        $shipping_status_response = $this->post(self::CANCEL_URI, $request);
        return $shipping_status_response;
    }

    function is_shipping_available(array $params): bool
    {
        $shipping_available_response = $this->get(self::AVAILABLE_URI, $params, self::DEFAULT_ACCEPT, 5);
        if (!$shipping_available_response) return false;
        return @$shipping_available_response->available;
    }
}

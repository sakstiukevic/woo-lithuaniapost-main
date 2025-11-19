<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-api.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Parcel_Api extends Woo_Lithuaniapost_Admin_Api
{
    /**
     * Authentication gateways
     */
    const PARCEL_URI = 'parcel';
    const PARCEL_IDREF_URI = 'parcel/idref/';
    const PARCEL_VALIDATE_URI = 'parcel/validate';

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {
        parent::__construct($plugin_name, $version);
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function create_parcel(array $create_parcel_request, callable $error_callback)
    {
        return $this->post(self::PARCEL_URI, $create_parcel_request, $error_callback);
    }

    public function validate_parcel($validate_parcel_request, callable $error_callback)
    {
        return $this->post(self::PARCEL_VALIDATE_URI, $validate_parcel_request, $error_callback);
    }

    public function get_parcel($order_id)
    {
        return $this->get(self::PARCEL_IDREF_URI . $order_id);
    }

    public function update_parcel($order_id, array $update_parcel_request)
    {
        return $this->put(self::PARCEL_IDREF_URI . $order_id, $update_parcel_request);
    }

}

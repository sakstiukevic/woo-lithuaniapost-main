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
class Woo_Lithuaniapost_Admin_Address_Api extends Woo_Lithuaniapost_Admin_Api
{
    /**
     * Authentication gateways
     */
    const ADDRESS_SENDER = 'address/sender';
    const ADDRESS_BY_ID_SENDER = 'address/sender/';
    const ADDRESS_VALIDATE = 'address/validate';

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

    public function get_sender_address()
    {
        return $this->get(self::ADDRESS_SENDER);
    }

    public function get_pickup_address()
    {
        $address_id = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_address_pickup_id');
        if (!$address_id) {
            return false;
        }
        $response = $this->get(self::ADDRESS_BY_ID_SENDER . $address_id);
        if ($response === false) {
            Woo_Lithuaniapost_Admin_Settings::delete_option('woo_lithuaniapost_address_pickup_id');
        }
        return $response;
    }

    public function update_sender_address()
    {
        return $this->put(self::ADDRESS_SENDER, $this->create_update_sender_address_request());
    }

    public function save_pickup_address()
    {
        $address_id = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_address_pickup_id');
        if (!$address_id) {
            return $this->create_pickup_address();
        }
        return $this->put(self::ADDRESS_BY_ID_SENDER . $address_id, $this->create_pickup_address_request());
    }

    public function create_pickup_address()
    {
        return $this->post(self::ADDRESS_SENDER, $this->create_pickup_address_request());
    }

    public function validate_address($address, callable $error_callback)
    {
        return $this->post(self::ADDRESS_VALIDATE, $address, $error_callback);
    }

    public function create_update_address_request(string $prefix): array
    {
        $name = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'name'));
        $company_name = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'company_name'));
        $phone = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'phone'));
        $email = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'email'));
        $country = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'country'));
        $locality = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'city'));
        $street = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'street'));
        $building = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'building'));
        $flat = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'apartment'));
        $postal_code = $this->null_if_empty(Woo_Lithuaniapost_Admin_Settings::get_option($prefix . 'postcode'));
        $request = [];
        $this->apply_if_value_not_null($request, 'name', $name);
        $this->apply_if_value_not_null($request, 'companyName', $company_name);
        $this->apply_if_value_not_null($request['contacts'], 'phone', $phone);
        $this->apply_if_value_not_null($request['contacts'], 'email', $email);
        $this->apply_if_value_not_null($request['address'], 'countryCode', $country);
        $this->apply_if_value_not_null($request['address'], 'locality', $locality);
        $this->apply_if_value_not_null($request['address'], 'street', $street);
        $this->apply_if_value_not_null($request['address'], 'building', $building);
        $this->apply_if_value_not_null($request['address'], 'flat', $flat);
        $this->apply_if_value_not_null($request['address'], 'postalCode', $postal_code);
        return $request;
    }

    public function create_update_sender_address_request(): array
    {
        return $this->create_update_address_request('sender_');
    }

    public function create_pickup_address_request(): array
    {
        return $this->create_update_address_request('pickup_');
    }

    private function apply_if_value_not_null(&$arr, string $key, $value)
    {
        if ($value != null) {
            $arr[$key] = $value;
        }
    }

    private function null_if_empty($value)
    {
        if ($value == "") return null;
        return $value;
    }
}

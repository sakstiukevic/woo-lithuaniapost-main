<?php

use Automattic\Jetpack\Constants;

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-api.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Eshop_Api extends Woo_Lithuaniapost_Admin_Api
{
    const BASE_URI = 'eshop/plugin';

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
    }

    private function install()
    {
        $request['shop'] = get_home_url() ?: get_site_url();
        $request['name'] = 'Woocommerce';

        $request['version'] = $this->get_version();
        $response = $this->post(self::BASE_URI, $request);
        if ($response && isset($response->id)) {
            Woo_Lithuaniapost_Admin_Settings::update_option('woo_lithuaniapost_eshop_installation_id', $response->id);
        }
    }

    public function uninstall()
    {
        $request['pluginId'] = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_eshop_installation_id');
        $request['name'] = 'UNINSTALL';
        $this->post(self::BASE_URI . '/event', $request);
        Woo_Lithuaniapost_Admin_Settings::delete_option('woo_lithuaniapost_eshop_installation_id');
    }

    public function update()
    {
        $request['pluginId'] = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_eshop_installation_id');
        $request['name'] = 'UPDATE';
        $request['arg'] = $this->get_version();
        return $this->post(self::BASE_URI . '/event', $request);
    }

    public function login()
    {
        $request['pluginId'] = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_eshop_installation_id');
        $request['name'] = 'LOGIN';
        $request['arg'] = Woo_Lithuaniapost_Admin_Settings::get_option('api_username');
        return $this->post(self::BASE_URI . '/event', $request);
    }

    public function activate()
    {
        $eshop_id = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_eshop_installation_id');
        if ($eshop_id) {
            $request['pluginId'] = $eshop_id;
            $request['name'] = 'ACTIVATE';
            $this->post(self::BASE_URI . '/event', $request);
        } else {
            $this->install();
        }
    }

    public function deactivate()
    {
        $request['pluginId'] = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_eshop_installation_id');
        $request['name'] = 'DEACTIVATE';
        return $this->post(self::BASE_URI . '/event', $request);
    }

    public function authorization_required(): bool
    {
        return false;
    }

    public function disable_error_handling(): bool
    {
        return true;
    }

    private function get_version()
    {
        $version = Constants::get_constant('WC_VERSION');

        return 'Woo: [' . $version . '], unisend: [' . WOO_LITHUANIAPOST_VERSION . ']';
    }
}

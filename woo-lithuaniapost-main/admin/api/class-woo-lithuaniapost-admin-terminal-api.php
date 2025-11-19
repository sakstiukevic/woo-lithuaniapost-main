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
class Woo_Lithuaniapost_Admin_Terminal_Api extends Woo_Lithuaniapost_Admin_Api
{
    const TERMINAL_BY_RECEIVER_COUNTRY_CODE = 'terminal?receiverCountryCode=';

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

    public function get_terminals(string $receiver_country_code)
    {
        return $this->get(self::TERMINAL_BY_RECEIVER_COUNTRY_CODE . $receiver_country_code);
    }

}

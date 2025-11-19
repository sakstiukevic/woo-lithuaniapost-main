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
class Woo_Lithuaniapost_Admin_Tracking_Api extends Woo_Lithuaniapost_Admin_Api
{
    /**
     * Authentication gateways
     */
    const GET_EVENTS_BY_BARCODE_URI = 'tracking/%s/events';
    const GET_EVENTS_BY_BARCODES_URI = 'tracking/events';

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

    public function get_tracking_events(string $barcode)
    {
        return $this->get(sprintf(self::GET_EVENTS_BY_BARCODE_URI, $barcode));
    }

    public function get_tracking_events_by_barcodes(array $barcodes, $datetime)
    {
        return $this->post(self::GET_EVENTS_BY_BARCODES_URI . ($datetime ? '?dateFrom=' . $datetime : null), $barcodes);
    }

    public function disable_error_handling(): bool
    {
        return true;
    }
}

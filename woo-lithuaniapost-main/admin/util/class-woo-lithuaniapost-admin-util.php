<?php

/**
 * The admin-specific functionality of the plugin.
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
class Woo_Lithuaniapost_Admin_Util
{

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
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function convert_to_grams(float $weight): int
    {
        $weight_unit = get_option('woocommerce_weight_unit');
        $weight_in_g = $weight;
        switch ($weight_unit) {
            case 'kg':
                $weight_in_g = $weight * 1000;
                break;
            case 'oz':
                $weight_in_g = $weight * 28.35;
                break;
            case 'lbs':
                $weight_in_g = $weight * 453.6;
                break;
        }
        return intval(ceil($weight_in_g));
    }

    public function get_min_weight(): float
    {
        $weight_unit = get_option('woocommerce_weight_unit');
        switch ($weight_unit) {
            case 'kg':
                return 1.0 / 1000.0;
            case 'oz':
                return 1.0 / 28.35;
            case 'lbs':
                return 1.0 / 453.6;
        }
        return 1.0;
    }

}

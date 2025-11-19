<?php

use NAWebCo\BoxPacker\GenericPackable;
use NAWebCo\BoxPacker\Packer;

defined('ABSPATH') || exit;

require_once WOO_LITHUANIAPOST_PLUGIN_DIR . '/vendor/autoload.php';


/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Size_Service
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

    public function resolve_shipping_size($planCode, $package)
    {
        $total_weight = 0;
        $packages = [];
        $default_width_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_width');
        $default_height_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_height');
        $default_length_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_length');
        $default_width = $default_width_cfg && is_numeric($default_width_cfg) ? (float)$default_width_cfg : 10;
        $default_height = $default_height_cfg && is_numeric($default_height_cfg) ? (float)$default_height_cfg : 10;
        $default_length = $default_length_cfg && is_numeric($default_length_cfg) ? (float)$default_length_cfg : 10;
        $dimension_unit = get_option('woocommerce_dimension_unit');

        foreach ($package['contents'] as $content) {
            $quantity = $content['quantity']; // get quantity
            $product = $content['data']; // get the WC_Product_Simple object
            $virtual = $product->is_virtual();
            $product_weight = $product->get_weight(); // get the product weight
            if ($product_weight) {
                // Add the line item weight to the total weight calculation
                $total_weight += floatval($product_weight * $quantity);
            }
            $length = $product->get_length() ? (float)wc_get_dimension($product->get_length(), 'cm', $dimension_unit) : 0;
            $width = $product->get_width() ? (float)wc_get_dimension($product->get_width(), 'cm', $dimension_unit) : 0;
            $height = $product->get_height() ? (float)wc_get_dimension($product->get_height(), 'cm', $dimension_unit) : 0;
            if (!$virtual) {
                $packages[] = [$width > 0 ? $width : $default_width, $height > 0 ? $height : $default_height, $length > 0 ? $length : $default_length, $quantity];
            }
        }
        if (empty($packages)) return null;

        $total_weight_in_g = apply_filters('woo_lithuaniapost_admin_util_convert_to_grams', $total_weight);

        $packages = self::package_size($packages, $planCode, $total_weight_in_g);
        return !empty($packages) ? strtoupper($packages[0]) : null;
    }

    public function resolve_order_size($order, $lp_shipping_method)
    {
        $packages = [];
        $default_width_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_width');
        $default_height_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_height');
        $default_length_cfg = Woo_Lithuaniapost_Admin_Settings::get_option('dimension_length');
        $default_width = $default_width_cfg && is_numeric($default_width_cfg) ? (float)$default_width_cfg : 10;
        $default_height = $default_height_cfg && is_numeric($default_height_cfg) ? (float)$default_height_cfg : 10;
        $default_length = $default_length_cfg && is_numeric($default_length_cfg) ? (float)$default_length_cfg : 10;
        $dimension_unit = get_option('woocommerce_dimension_unit');
        $plan_code = $lp_shipping_method->plan;

        $total_weight_in_g = apply_filters('woo_lithuaniapost__order_action_get_order_weight_in_g', $order);

        foreach ($order->get_items() as $item_id => $product_item) {
            $product = $product_item->get_product(); // get the WC_Product object
            $virtual = $product->is_virtual() === true;
            $length = $product->get_length() ? (float)wc_get_dimension($product->get_length(), 'cm', $dimension_unit) : 0;
            $width = $product->get_width() ? (float)wc_get_dimension($product->get_width(), 'cm', $dimension_unit) : 0;
            $height = $product->get_height() ? (float)wc_get_dimension($product->get_height(), 'cm', $dimension_unit) : 0;
            if (!$virtual) {
                $packages[] = [$width > 0 ? $width : $default_width, $height > 0 ? $height : $default_height, $length > 0 ? $length : $default_length, $product_item->get_quantity() ?? 1];
            }
        }
        if (empty($packages)) return null;
        $packages = $this->package_size($packages, $plan_code, $total_weight_in_g);
        return !empty($packages) ? strtoupper($packages[0]) : null;
    }

    private function get_available_sizes($plan_code, $total_weight)
    {
        if ($plan_code === 'HANDS' || $plan_code === 'TERMINAL') {
            return $this->get_bp_sizes($total_weight);
        }
        return $this->get_lp_sizes($total_weight);
    }

    private function get_lp_sizes($weight)
    {
        if (!is_nan($weight)) {
            if ($weight <= 500) {
                $sizes['s'] = [2, 38.1, 30.5];
            }
            if ($weight <= 2000) {
                $sizes['m'] = [60, 60, 60];
            }
        }
        $sizes['l'] = [105, 105, 105];
        return $sizes;
    }

    private function get_bp_sizes($weight)
    {
        return [
            'xs' => [18.5, 61, 8],
            's' => [35, 61, 8],
            'm' => [35, 61, 17.5],
            'l' => [35, 61, 36.5],
            'xl' => [35, 61, 74.5],
        ];
    }

    private function package_size($packages, $planCode, $total_weight_in_g)
    {
        $sizes = $this->get_available_sizes($planCode, $total_weight_in_g);
        $possible_sizes = [];
        foreach ($sizes as $name => $size) {
            $packer = new Packer();
            $packer->addBox(new GenericPackable($size[0], $size[1], $size[2], $name));
            foreach ($packages as $k => $package) {
                for ($i = 0; $i < $package[3]; $i++) {
                    $packer->addItem(new GenericPackable($package[0], $package[1], $package[2], $k . $i));
                }
            }

            $result = $packer->pack();

            if ($result->success()) {
                $possible_sizes[] = $name;
            }
        }
        return $possible_sizes;
    }

}

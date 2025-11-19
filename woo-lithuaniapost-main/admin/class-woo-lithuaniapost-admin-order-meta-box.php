<?php
use Automattic\WooCommerce\Utilities\OrderUtil;

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-parcel-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-estimate-shipping-api.php';

class Woo_Lithuaniapost_Admin_Order_Meta_Box
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

    private $parcel_api;
    private $estimate_shipping_api;

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
        $this->parcel_api = new Woo_Lithuaniapost_Admin_Parcel_Api($plugin_name, $version);
        $this->estimate_shipping_api = new Woo_Lithuaniapost_Admin_Estimate_Shipping_Api($plugin_name, $version);
    }

    /**
     * Get order
     *
     * @return bool|WC_Order|WC_Order_Refund
     * @since 1.0.0
     */
    public function get_order ()
    {
        return wc_get_order ( $this->get_order_id () );
    }
    public function get_order_id ()
    {
        return $_GET['id'] ?? get_the_ID();
    }

    public function get_parcel ()
    {
        if (!$this->get_order()->get_meta('_woo_lithuaniapost_shipping_item_id')) {
            return false;
        }
        return $this->parcel_api->get_parcel($this->get_order_id());
    }

    public function get_parcel_request()
    {
        $order = $this->get_order();
        $lp_shipping_method = apply_filters("woo_lithuaniapost__order_action_get_lp_shipping_method", $order);
        if (!$lp_shipping_method) return false;
        $create_parcel_request = apply_filters("woo_lithuaniapost__order_action_create_parcel_create_request", $order, $lp_shipping_method);
        return $this->to_object($create_parcel_request);
    }

    public function estimate_parcel($parcel)
    {
        if (!$parcel) return false;
        $request_params = [];
        $request_params['planCodes'] = $parcel->plan->code;
        if ($parcel->plan->code == 'TERMINAL') {
            $request_params['planCodes'] .= ',HANDS';
        }
        $request_params['size'] = $parcel->parcel->size ?? null;
        $request_params['weight'] = $parcel->parcel->weight ?? null;
        return $this->estimate_shipping_api->get_plans($parcel->receiver->address->countryCode, $request_params);
    }

    public function get_available_terminals($parcelResponse)
    {
        if (!$parcelResponse) return [];
        $parcel = $parcelResponse->parcel;
        if ($parcel->type !== 'H2T' && $parcel->type !== 'T2T' && $parcel->type !== 'T2S') return [];
        $shipping_country_code = $parcelResponse->receiver->address->countryCode;
        return apply_filters("woo_lithuaniapost_terminal_service_get_terminals_by_country_code", $shipping_country_code);
    }

    public function get_available_actions(): array
    {
        return apply_filters('woo_lithuaniapost_order_action_get_available_order_actions', $this->get_order());
    }

    public function is_action_available(string $action, array $available_actions): bool
    {
        return array_search($action, $available_actions) !== false;
    }

    /**
     * Check if shipping method belongs to LP
     *
     * @return bool
     * @since 1.0.o
     */
    protected function is_shipping_method ()
    {
        return strpos ( $this->get_shipping_method (), 'woo_lithuaniapost' ) !== false;
    }

    /**
     * Get shipping method
     *
     * @return string
     * @since 1.0.0
     */
    public function get_shipping_method ()
    {
        $order = $this->get_order ();

        if ( $order ) {
            foreach ( $order->get_shipping_methods () as $method ) {
                return $method->get_method_id ();
            }
        }

        return null;
    }

    /**
     * Add meta box for shipment controls
     *
     * @since 1.0.0
     */
    public function add_order_meta_box ()
    {
        $screen = OrderUtil::custom_orders_table_usage_is_enabled() ? 'woocommerce_page_wc-orders' : 'shop_order';
        // Add metabox only if LP method is selected
        if ( $this->is_shipping_method () ) {
            add_meta_box (
                'woo-lithuaniapost-order-meta-box',
                __( 'UNISEND', 'woo-lithuaniapost' ),
                [ $this, 'order_meta_box_content' ],
                $screen,
                'side',
                'high'
            );
        }
    }

    /**
     * Get tracking code
     *
     * @return string
     */
    public function get_tracking_code ()
    {
        return $this->get_order ()->get_meta ( '_woo_lithuaniapost_barcode' );
    }

    /**
     * Meta box content
     *
     * @since 1.0.0
     */
    public function order_meta_box_content ()
    {
        require plugin_dir_path ( dirname ( __FILE__ ) ) .
            'admin/partials/woo-lithuaniapost-admin-display.php';
    }

    private function is_type_change_available($parcel_response) : bool {
        return $parcel_response->plan->code == 'TERMINAL';
    }

    private function is_size_available($parcel_response) : bool {
        $plan_code = $parcel_response->plan->code;
        $parcel_type = $parcel_response->parcel->type;
        return $plan_code != 'HANDS' || $parcel_type == 'T2H';
    }

    private function is_weight_available($parcel_response) : bool {
        $plan_code = $parcel_response->plan->code;
        $parcel_type = $parcel_response->parcel->type;
        return $plan_code!='TERMINAL' && $parcel_type != 'T2H';
    }

    private function is_multi_part_available($parcel_response) : bool {
        $plan_code = $parcel_response->plan->code;
        return $plan_code=='TERMINAL' || $plan_code=='HANDS';
    }

    private function is_cn_required($parcel_response, $estimated_plans): bool
    {
        $parcel_contains_cn = $parcel_response->documents->cn ?? false;
        $cn_required = null;
        if (!empty($estimated_plans)) {
            $cn_required = $estimated_plans[0]->shipping[0]->requirements->cnDocument === true;
        }
        return $parcel_contains_cn && ($cn_required === null || $cn_required === true);
    }

    private function generate_select_parcel_type_html($parcel_response, $estimated_plans)
    {
        if (!$estimated_plans) {
            return null;
        }
        $selected_value = $parcel_response->parcel->type;
        ob_start();
        foreach ($estimated_plans as $estimated_plan) {
            foreach ($estimated_plan->shipping as $shipping) {
                if ($shipping->parcelType == $selected_value) {
                    echo sprintf("<option value='%s' selected>" . $this->get_parcel_type_translation($shipping->parcelType) . "</option>", $shipping->parcelType);
                } else {
                    echo sprintf("<option value='%s'>" . $this->get_parcel_type_translation($shipping->parcelType) . "</option>", $shipping->parcelType);
                }
            }
        }
        return ob_get_clean();
    }

    private function generate_select_parcel_plan_html($parcel_response, $estimated_plans)
    {
        if (!$estimated_plans) {
            return null;
        }
        $selected_value = $parcel_response->plan->code;
        ob_start();
        foreach ($estimated_plans as $estimated_plan) {
            if ($estimated_plan->code == $selected_value) {
                echo sprintf("<option value='%s' selected>" . $this->get_plan_translation($estimated_plan->code) . "</option>", $estimated_plan->code);
            } else {
                echo sprintf("<option value='%s'>" . $this->get_plan_translation($estimated_plan->code) . "</option>", $estimated_plan->code);
            }
        }
        return ob_get_clean();
    }



    private function get_parcel_type_translation($parcel_type)
    {
        return apply_filters('woo_lithuaniapost_order_action_get_parcel_type_translation', $parcel_type);
    }

    public function get_plan_translation($plan_code)
    {
        return apply_filters('woo_lithuaniapost_order_action_get_plan_translation', $plan_code);
    }

    private function generate_select_parcel_size_html($parcel_response)
    {
        $selected_size = $parcel_response->parcel->size ?? null;
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];
        ob_start();
        foreach ($sizes as $size) {
            if ($size == $selected_size) {
                echo "<option selected>" . $size . "</option>";
            } else {
                echo "<option>" . $size . "</option>";
            }
        }
        return ob_get_clean();
    }

    private function to_object($arr)
    {
        if (is_array($arr)) {
            return (object)array_map([$this, 'to_object'], $arr);
        }
        return $arr;
    }

    private function get_error_message($error): string
    {
        $description = $error->error_description ?? $error->error;
        if (isset($error->field)) {
            return sprintf("%s: %s", $error->field, $description);
        }
        return $description;
    }
}

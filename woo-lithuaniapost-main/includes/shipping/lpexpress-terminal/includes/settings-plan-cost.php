<?php
require_once __DIR__ . '/settings-plan-cost-weight.php';
require_once __DIR__ . '/settings-plan-cost-size.php';
require_once __DIR__ . '/../../../../admin/api/class-woo-lithuaniapost-admin-estimate-shipping-api.php';
require_once __DIR__ . '/../../../../admin/api/class-woo-lithuaniapost-admin-shipping-plan-api.php';


class Settings_Plan_Cost {
    private $weight_costs;
    private $size_costs;
    private $plan_api;
    private $estimate_shipping_api;

    function __construct() {
        $this->weight_costs = new Settings_Plan_Cost_Weight();
        $this->size_costs = new Settings_Plan_Cost_Size();
        $this->plan_api = new Woo_Lithuaniapost_Admin_Shipping_Plan_Api();
        $this->estimate_shipping_api = new Woo_Lithuaniapost_Admin_Estimate_Shipping_Api();
    }

    public function process_settings($shipping_method) {
        $form_fields = $shipping_method->get_instance_form_fields();

        $weight_form_fields = $this -> weight_costs->add_settings($shipping_method, $form_fields);
        $size_form_fields = $this->size_costs->add_settings($shipping_method, $form_fields);
        $fields = array_merge($weight_form_fields, $size_form_fields);
        return $fields;
    }

    public function init_settings($shipping_Method) {
        wp_enqueue_script( 'woo-lithuaniapost-shipping' );
        wp_enqueue_style( 'woo-lithuaniapost-shipping' );

        $form_fields = $shipping_Method->get_instance_form_fields();

        $weight_form_fields = $this -> weight_costs ->remove_settings($form_fields);
        $size_form_fields = $this->size_costs->remove_settings($form_fields);
        $fields = array_merge($weight_form_fields, $size_form_fields);
        $plan_form_fields = $this->add_plan_settings($shipping_Method, $fields);
        return $plan_form_fields;
    }

    public function calculate_shipping($shipping_method, $packages) {
        $cost = $shipping_method -> get_option('cost');
        switch ($cost) {
            case 'flat':
                return $shipping_method -> get_option('fixed_cost') ?? false;
            case 'weight':
                return $this->weight_costs->calculate_shipping($shipping_method, $packages);
            case 'size':
                return $this->size_costs->calculate_shipping($shipping_method, $packages);
            default:
               return false;
        }
    }

    private function add_plan_settings($shipping_Method, $form_fields) {

        $shipping_zone = WC_Shipping_Zones::get_zone_by('instance_id', $_REQUEST['instance_id']);
        $zone_countries = array_filter($shipping_zone->get_zone_locations(), function ($zone_country) {
            return $zone_country->type == 'country';
        });
        $shipping_zone_codes = array_column($shipping_zone->get_zone_locations(), 'code');

        $plans = count($zone_countries) > 1 || empty($zone_countries) || count($zone_countries) != count($shipping_zone_codes) ? $this->plan_api->get_plans() : $this->estimate_shipping_api->get_plans($shipping_zone_codes[0]);
        foreach ( $plans as $plan ) {
            $form_fields['plan']['options'][$plan->code] = $this->get_plan_translation($plan->code);
            foreach ($plan->shipping as $shipping) {
                $shipping->translated_parcel_type = $this->get_parcel_type_translation($shipping->parcelType);
            }
        }

        $selected_plan = $plans[0];
        $stored_plan = $shipping_Method -> get_option('plan');
        if($stored_plan){
            foreach ( $plans as $plan) {
                if($plan->code === $stored_plan){
                    $selected_plan = $plan;
                    break;
                }
            }
        }
        foreach ( $selected_plan->shipping as $shipping) {
            $form_fields['parcel_type']['options'][$shipping->parcelType] = $shipping->translated_parcel_type;
        }

        wp_add_inline_script( 'woo-lithuaniapost-shipping', 'const plans = ' . json_encode($plans), 'before' );

        return $form_fields;
    }

    private function compare_by_size_code($a, $b)
    {
        return $this->get_size_value($a->code) - $this->get_size_value($b->code);
    }

    private function get_size_value($size): int
    {
        switch ($size) {
            case 'XS':
                return 1;
            case 'S':
                return 2;
            case 'M':
                return 3;
            case 'L':
                return 4;
            case 'XL':
                return 5;
        }
        return -1;
    }

    private function get_parcel_type_translation($parcel_type)
    {
        return apply_filters('woo_lithuaniapost_order_action_get_parcel_type_translation', $parcel_type);
    }

    private function get_plan_translation($plan_code)
    {
        return apply_filters('woo_lithuaniapost_order_action_get_plan_translation', $plan_code);
    }
}
?>
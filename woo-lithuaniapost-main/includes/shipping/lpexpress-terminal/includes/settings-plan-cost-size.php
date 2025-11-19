<?php

require_once WOO_LITHUANIAPOST_PLUGIN_DIR . '/vendor/autoload.php';

class Settings_Plan_Cost_Size
{

    private $size_service;

    /**
     * @param Woo_Lithuaniapost_Admin_Size_Service $size_service
     */
    public function __construct()
    {
        $this->size_service = new Woo_Lithuaniapost_Admin_Size_Service(null, null);
    }

    public function add_settings($shipping_method, $form_fields)
    {
        $cost_type = $_POST['woocommerce_woo_lithuaniapost_lpexpress_terminal_cost'] ?? $shipping_method->cost ?? null;
        if ($cost_type !== 'size') return $form_fields;
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];
        $options = get_option($shipping_method->get_instance_option_key());
        if ($options) {
            foreach ($sizes as $size) {
                $request_option_key = 'woocommerce_woo_lithuaniapost_lpexpress_terminal_sizes_cost_' . $size;
                $request_option_exists = isset($_POST[$request_option_key]);
                if ($request_option_exists) {
                    $option_key = 'sizes_cost_' . $size;
                    $options[$option_key] = $_POST[$request_option_key];
                }
            }
            update_option($shipping_method->get_instance_option_key(), $options);
        }

        return $form_fields;
    }

    public function remove_settings($form_fields)
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];
        foreach ($sizes as $size) {
            $option_key = 'sizes_cost_' . $size;
            $form_fields = $this->remove($option_key, $form_fields);
        }
        return $form_fields;
    }

    public function calculate_shipping($shipping_method, $packages)
    {
        $plan_code = $shipping_method->plan;

        $size = $this->size_service->resolve_shipping_size($plan_code, $packages);
        $settings_key = 'sizes_cost_' . $size;
        if (!array_key_exists($settings_key, $shipping_method->instance_settings)) {
            return null;
        }
        $cost = $shipping_method->get_instance_option($settings_key);

        return $cost;
    }

    private function remove($option_name, $form_fields)
    {
        $option_exists = isset($_POST['woocommerce_woo_lithuaniapost_lpexpress_terminal_' . $option_name]);
        if ($option_exists) {
            unset($form_fields[$option_name]);
        }
        return $form_fields;
    }
}

?>
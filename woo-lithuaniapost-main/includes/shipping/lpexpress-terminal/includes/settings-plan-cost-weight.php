<?php
class Settings_Plan_Cost_Weight {

    public function add_settings($shipping_method, $form_fields) {
        $cost_type = $_POST['woocommerce_woo_lithuaniapost_lpexpress_terminal_cost'] ?? $shipping_method->cost ?? null;
        if ($cost_type !== 'weight') return $form_fields;
        $weights_key = $shipping_method->get_field_key('weights');
        if(!empty($_POST[$weights_key])){
            $weight_count = sizeof($_POST[$weights_key]);

            for ($i = 0; $i < $weight_count; $i++) {
                $form_fields['weights_' . $i ] = array(
                    'type'              => 'text',
                );
                $_POST[$weights_key . '_' . $i] = $_POST[$weights_key][$i];
            }
        
            $weights_costs_key = $shipping_method->get_field_key('weights_costs');
            for ($i = 0; $i < $weight_count; $i++) {
                $form_fields['weights_costs_' . $i ] = array(
                    'type'              => 'text',
                );
                $_POST[$weights_costs_key .'_'.$i] = $_POST[$weights_costs_key ][$i];
            }
    
            $options = get_option($shipping_method->get_instance_option_key());
            if($options)
            {
                $option_exists = false;
                $i = $weight_count;
                do {
                    $weight_key = 'weights_' .$i;
                    $weight_costs_key = 'weights_costs_' .$i;
                    $option_exists = array_key_exists($weight_key, $options );
                    if($option_exists){
                        unset($options[$weight_key]);
                        unset($options[$weight_costs_key]);
                    }
                    $i++;
                } while ($option_exists);
                update_option($shipping_method->get_instance_option_key(), $options);   
            }

        }
    
        return $form_fields;
    }

    public function remove_settings($form_fields){
        $form_fields = $this->remove('weights', $form_fields);
        $form_fields = $this->remove('weights_costs',$form_fields);
       return $form_fields;
    }

    public function calculate_shipping($shipping_method, $packages)
    {
        $weights = array_map(function ($content) {
            $weight = $content['data']->get_weight();
            if ($weight) {
                return $content['quantity'] * $weight;
            }
            return 0;
        }, $packages['contents']);

        $total_weight = array_sum($weights);
        $total_weight = apply_filters('woo_lithuaniapost_admin_util_convert_to_grams', $total_weight);

        $is_weight_configured = true;
        $i = 0;
        $cost = null;
        $last_valid_index = 0;
        do {
            $is_weight_configured = array_key_exists('weights_costs_' . $i, $shipping_method->instance_settings);
            if ($is_weight_configured) {
                $last_valid_index = $i;
                $configured_weight = $shipping_method->get_instance_option('weights_' . $i);
                if ($configured_weight >= $total_weight) {
                    $cost = $shipping_method->get_instance_option('weights_costs_' . $i);
                    break;
                }
            }
            $i++;
        } while ($is_weight_configured);
        if ($cost === null) {
            $cost = $shipping_method->get_instance_option('weights_costs_' . $last_valid_index);
        }
        return $cost;
    }

    private function remove($option_name, $form_fields){
        $i = 0;
        $key_exists = false;
		do {
            $key = $option_name. '_' .$i;
            $key_exists = array_key_exists($key, $form_fields);
            if($key_exists){
                unset($form_fields[$key]);
            }
            $i++;
		} while ($key_exists);
        return $form_fields;
    }
}
?>
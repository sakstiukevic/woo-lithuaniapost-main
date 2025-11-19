<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/public
 */

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/public
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Public
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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct ( $plugin_name, $version )
    {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles ()
    {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Lithuaniapost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Lithuaniapost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style ( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-lithuaniapost-public.css', array(), $this->version, 'all' );

        //Add the Select2 CSS file
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
    {
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Lithuaniapost_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Lithuaniapost_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        wp_register_script( 'jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', array(), '1.0', 'all' );
        wp_enqueue_script('jquery');

        wp_register_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_script( 'select2' );

        wp_enqueue_script ( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost.js', array( 'jquery', 'select2' ), $this->version, false );
        wp_enqueue_script ( $this->plugin_name . '-lpexpress-terminal-block', plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost-lpexpress-terminal-block.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script ( $this->plugin_name . '-shipping-logo', plugin_dir_url( __FILE__ ) . 'js/woo-lithuaniapost-shipping-logo.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name, 'woo_lithuaniapost', array( 'ajax_url' => admin_url ( 'admin-ajax.php' ), 'shipping_logo_url' =>  plugins_url( 'images/unisend_shipping_lpexpress_logo_45x25.png', __FILE__ )) );
    }

	/**
	 * Get formatted LPEXPRESS terminal list
	 *
	 * @return array
	 * @since 1.0.0
	 */
	protected function get_terminal_list ()
    {
        $shipping_country_code = $_POST['s_country'] ?? WC()->customer->get_shipping_country();
		return apply_filters("woo_lithuaniapost_terminal_service_get_terminals_by_country_code", $shipping_country_code);
    }

    protected function get_selected_terminal_id()
    {
        $request_terminal_id = $_POST['woo_lithuaniapost_lpexpress_terminal_id'] ?? null;
        if ($request_terminal_id && is_numeric($request_terminal_id)) {
            return $request_terminal_id;
        }
        $selected_terminal_session = WC()->session->get('selected_lpexpress_terminal');
        return !empty ($selected_terminal_session) ? $selected_terminal_session ['terminal_id'] : null;
    }

    /**
     * Render field for UNISEND terminal
     *
     * @param $method
     * @param int $index
     * @since 1.0.0
     */
    public function render_terminal_field ( $method, $index )
    {
        if (!$this->is_selected_method_lp($method, $index)) {
            return;
        }

        // Check if selected method is UNISEND terminal
        if ($this->get_method_settings_value($method->get_instance_id(), 'plan') == 'TERMINAL') {
            $referrer = $_SERVER['HTTP_REFERER'] ?? null;
            $requestUri = $_SERVER['REQUEST_URI'] ?? null;
            if ($this->str_contains($requestUri, 'cart') && (!$referrer || !$this->str_contains($referrer, 'checkout'))) {
                WC ()->session->set ( 'selected_lpexpress_terminal', null );
            }
            include __DIR__ . '/partials/html-lpexpress-terminal.php';
        }
    }

    /**
     * Render delivery time
     *
     * @param $method
     * @param $index
     * @since 1.0.0
     */
    public function render_delivery_time($method, $index)
    {
        if (!is_checkout() || $method->get_method_id() != 'woo_lithuaniapost_lpexpress_terminal') {
            return;
        }
        $delivery_time = $this->get_method_settings_value($method->get_instance_id(), 'delivery_time');
        if ($delivery_time) {
            include __DIR__ . '/partials/html-delivery-time.php';
        }
    }

    public function handle_after_get_rates_for_package($package, $shipping_method)
    {
        if ($shipping_method->id != 'woo_lithuaniapost_lpexpress_terminal' || isset($_GET['wc-ajax']) || $this->is_json_response() || (!is_cart() && !is_checkout())) {
            return;
        }
        $this->handle_generate_terminal_dropdown_html($shipping_method);
        $this->handle_generate_terminal_input_html($shipping_method);
        if (is_checkout()) {
            $this->handle_render_delivery_time_code_block($shipping_method);
        }
    }

    private function handle_generate_terminal_input_html($shipping_method)
    {
        if ($this->get_method_settings_value($shipping_method->get_instance_id(), 'plan') == 'TERMINAL') {
            $element_value = $shipping_method->id . '_' . $shipping_method->get_instance_id();
            $element_key = 'woo_lithuaniapost_lpexpress_terminal_' . $element_value;
            $element_saved = ($_REQUEST[$element_key] ?? null) == true;
            if (!$element_saved) {
                echo $this->generate_terminal_input_html($element_value);
                $_REQUEST[$element_key] = true;
            }
        }
    }

    private function handle_generate_terminal_dropdown_html($shipping_method)
    {
        if ($this->get_method_settings_value($shipping_method->get_instance_id(), 'plan') == 'TERMINAL') {
            $element_value = $shipping_method->id;
            $element_key = 'woo_lithuaniapost_lpexpress_terminal_dropdown_' . $element_value;
            $element_saved = ($_REQUEST[$element_key] ?? null) == true;
            if (!$element_saved) {
                include __DIR__ . '/partials/html-block-lpexpress-terminal.php';   // execute the file
                $_REQUEST[$element_key] = true;
            }
        }
    }

    private function handle_render_delivery_time_code_block($shipping_method)
    {
        $delivery_time = $this->get_method_settings_value($shipping_method->get_instance_id(), 'delivery_time');
        if ($delivery_time) {
            $element_value = $shipping_method->id . ':' . $shipping_method->get_instance_id();
            $element_key = 'woo_lithuaniapost_lpexpress_delivery_time_' . $element_value;
            $element_saved = ($_REQUEST[$element_key] ?? null) == true;
            if (!$element_saved) {
                echo $this->generate_delivery_time_html($delivery_time, $element_value);
                $_REQUEST[$element_key] = true;
            } else {
                wp_enqueue_script($this->plugin_name . '-delivery-time-block', plugin_dir_url(__FILE__) . 'js/woo-lithuaniapost-delivery-time-block.js', array('jQuery'), $this->version, false);
            }
        }
    }

    private function generate_delivery_time_html($delivery_time, $element_value)
    {
        ob_start();
        ?>
        <p data-value="<?php echo $element_value ?>" class="woo_lithuaniapost_delivery_time_label" style="display: none;">
            <strong><?php _e('Delivery time', 'woo-lithuaniapost'); ?>
                :&nbsp;</strong><span><?php echo $delivery_time; ?></span></p>
        <?php
        return ob_get_clean();
    }

    private function generate_terminal_input_html($element_value)
    {
        ob_start();
        ?>
        <input type="hidden" id="<?php echo $element_value ?>" class="woo_lithuaniapost_shipping_method_terminal"/>
        <?php
        return ob_get_clean();
    }

    public function validate_shipping($arg)
    {
        if (!$this->is_chosen_method_lp() || !is_checkout()) return;
        $shipping_plan = $this->get_chosen_method_settings_value('plan');
        if ($shipping_plan == 'TERMINAL') {
            if (!$this->validate_selected_terminal($arg)) return;
        }
        $this->validate_parcel($arg);
    }

    private function str_contains($haystack, $needle)
    {
        if ('' === $needle) {
            return true;
        }
        return false !== strpos($haystack, $needle);
    }

    public function validate_api_shipping($order)
    {
        $lp_shipping_method = apply_filters('woo_lithuaniapost__order_action_get_lp_shipping_method', $order);
        if (!$lp_shipping_method) return;
        $validate_parcel_request = $this->create_parcel_validation_request();
        $error_callback = function ($response_body) {
            $error_messages = $this->collect_validation_error_response_messages($response_body);
            if (!empty($error_messages)) {
                $error_message = implode('<br>', $error_messages);
                throw new RouteException(
                    'woo_lithuaniapost_rest_invalid_shipping',
                    sprintf(
                        __('Please fill correct shipping data: (%s)', 'woo-lithuaniapost'),
                        $error_message
                    ),
                    400
                );
            }
        };
        apply_filters('woo_lithuaniapost_api_is_parcel_valid', $validate_parcel_request, $error_callback);
    }

    private function is_json_request()
    {
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            return $this->str_contains($_SERVER['HTTP_CONTENT_TYPE'], 'application/json');
        }
        return false;
    }

    private function is_json_response()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            return $this->str_contains($_SERVER['CONTENT_TYPE'], 'application/json');
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            return $this->str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
        }
        return false;
    }

    private function create_delivery_address_validation_request($plan_code): array
    {
        $use_shipping_address = $_POST['ship_to_different_address'] ?? null == true;
        $is_json = $this->is_json_request() || (!isset($_POST['shipping_country']) && !isset($_POST['billing_country']));
        $delivery_address_type = $is_json ? '' : ($use_shipping_address ? 'shipping_' : 'billing_');

        if ($is_json) {
            $request_body = file_get_contents('php://input');
            $data = json_decode($request_body, true);
            $billing_address_container = isset($data['billing_address']) && is_array($data['billing_address']) ? $data['billing_address'] : $_POST;
            $shipping_address_container = isset($data['shipping_address']) && is_array($data['shipping_address']) ? $data['shipping_address'] : $_POST;
        } else {
            $billing_address_container = $_POST;
            $shipping_address_container = $_POST;
        }

        $terminal_id = $this->is_terminal_required($plan_code) ? ($this->get_selected_terminal_id() ?? null) : null;
        $country_code = $shipping_address_container[$delivery_address_type . 'country'];
        $address = $country_code == 'LT' ? $shipping_address_container[$delivery_address_type . 'address_1'] . ' ' . $shipping_address_container[$delivery_address_type . 'address_2'] : null;
        return [
            'name' => sprintf('%s %s',
                $shipping_address_container[$delivery_address_type . 'first_name'],
                $shipping_address_container[$delivery_address_type . 'last_name']
            ),
            'companyName' => $shipping_address_container[$delivery_address_type . 'company'],
            'contacts' => [
                'phone' => $billing_address_container['billing_phone'] ?? $billing_address_container['phone'] ?? null,
                'email' => $billing_address_container['billing_email'] ?? $billing_address_container['email'] ?? null
            ],
            'address' => [
                'terminalId' => $terminal_id,
                'locality' => $shipping_address_container[$delivery_address_type . 'city'],
                'address' => $address,
                'address1' => $shipping_address_container[$delivery_address_type . 'address_1'],
                'address2' => $shipping_address_container[$delivery_address_type . 'address_2'],
                'postalCode' => $shipping_address_container[$delivery_address_type . 'postcode'],
                'countryCode' => $country_code
            ],
        ];
    }

    private function is_terminal_required($plan_code) {
        return $plan_code == 'TERMINAL';
    }

    private function handle_validation_error_response($response_body)
    {
        $error_messages = $this->collect_validation_error_response_messages($response_body);
        foreach ($error_messages as $error_message) {
            wc_add_notice(__('Please fill correct shipping data: ' . $error_message, 'woo-lithuaniapost'), 'error');
        }
    }

    private function collect_validation_error_response_messages($response_body)
    {
        $errors = [];
        if ($response_body && $response_body->errors && is_array($response_body->errors)) {
            foreach ($response_body->errors as $error_response) {
                $error_message = $this->to_error_message($error_response);
                if ($error_message) {
                    $errors[] = $error_message;
                }
            }
        } else if (isset($response_body->error)) {
            $error_message = $this->to_error_message($response_body->error);
            if ($error_message) {
                $errors[] = $error_message;
            }
        } else if (is_array($response_body)) {
            foreach ($response_body as $response_item) {
                if (isset($response_item->error)) {
                    $error_message = $this->to_error_message($response_item);
                    if ($error_message) {
                        $errors[] = $error_message;
                    }
                }
            }
        }
        return $errors;
    }

    private function to_error_message($error_response)
    {
        $error = @$error_response->error;
        $error_description = @$error_response->error_description;
        if ($error) {
            $error_field = $error_response->field ?? null;
            $error_message = $error_description ?? $error;
            if (strlen($error_field) > 0) {
                $error_field_parts = explode(".", $error_field);
                $last_error_field_part = end($error_field_parts);
                if ($last_error_field_part) {
                    $error_message = $last_error_field_part . ' -> ' . $error_message;
                }
            }
            return $error_message;
        }
        return null;
    }

    public function handle_update_order_meta_by_id($order_id)
    {
        if ($this->is_chosen_method_lp()) {
            $order = wc_get_order($order_id);
            if (!$order) return;
            $this->save_order_meta_info($order);
            WC()->session->set('selected_lpexpress_terminal', null);
        }
    }

    public function handle_update_order_meta($order)
    {
        if ($this->is_chosen_method_lp()) {
            $this->save_order_meta_info($order);
        }
    }

	/**
	 * Save shipping method settings and/or selected terminal
	 *
	 * @param $order_id
	 * @since 1.0.0
	 */
	private function save_order_meta_info ( $order )
	{
            $shipping_method_instance_id = $this->get_chosen_method_instance_id();
            if ($shipping_method_instance_id) {
                $order->update_meta_data('_woo_lithuaniapost_lpexpress_shipping_method_instance_id', $shipping_method_instance_id);
            }

            $saved_terminal_id = $this->get_selected_terminal_id();
            // Save terminal id
            if (isset ($saved_terminal_id)) {

                $terminal_id = sanitize_text_field($saved_terminal_id);

                $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $terminal_id);

                if ($terminal) {
                    $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal_id', $terminal_id);
                    $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal', sprintf('%s - %s, %s', $terminal [0]->name, $terminal [0]->address, $terminal [0]->city));
                }
            }
            $order->save();
    }

    /**
     * Save selected terminal to session
     * @since 1.0.0
     */
    public function save_selected_terminal_session ()
    {
        WC ()->session->set ( 'selected_lpexpress_terminal', [
                'city' => $_REQUEST ['city'] ?? null,
                'terminal' => $_REQUEST ['terminal'] ?? null,
                'terminal_id' => $_REQUEST ['terminal_id'] ?? null
            ]
        );

        wp_die ();
    }

    /**
     * Display selected terminal to order email
     *
     * @param array $fields
     * @param bool $sent_to_admin
     * @param mixed order
     * @return mixed
     * @since 1.0.0
     */
    public function add_terminal_field_order_email ( $fields, $sent_to_admin, $order )
    {
        if ( $terminal_id = $order->get_meta('_woo_lithuaniapost_lpexpress_terminal_id') ) {

            $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $terminal_id);

            $fields [ 'selected_lpexpress_terminal' ] = [
                'label' => __( 'UNISEND Parcel Locker', 'woo-lithuaniapost' ),
                'value' => sprintf ( '%s - %s, %s', $terminal [ 0 ]->name, $terminal [ 0 ]->address, $terminal [ 0 ]->city )
            ];
        }
        return $fields;
    }

    public function add_placeholder_order_email($string, $email)
    {

        $order = $email->object;

        if (!$order || !is_a($email->object, 'WC_Order')) {
            return $string; 
        }
    
        $new_placeholders = array(
            '{unisend_tracking_number}'   => '<a href="' . get_rest_url(null,'/unisend/tracking/'.$order->get_id()) . '">' . __('Track your shipment', 'woo-lithuaniapost') . '<a>'
        );
        return str_replace( array_keys( $new_placeholders ), array_values( $new_placeholders ), $string );
    }

    /**
     * Display selected terminal to order email
     *
     * @param array $fields
     * @param bool $sent_to_admin
     * @param mixed order
     * @return mixed
     * @since 1.0.0
     */
    public function add_terminal_field_order_thankyou ( $order )
    {
        $order = wc_get_order ( $order );
        if ( $terminal_id = $order->get_meta('_woo_lithuaniapost_lpexpress_terminal_id') ) {

            $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $terminal_id);

            echo sprintf ( '<b>%s:</b> %s - %s, %s<br><br><br>', __( 'UNISEND Parcel Locker', 'woo-lithuaniapost' ), $terminal [ 0 ]->name, $terminal [ 0 ]->address, $terminal [ 0 ]->city );
        }
    }

    public function handle_checkout_order_processed($order)
    {
        WC()->session->set('selected_lpexpress_terminal', null);
    }

    private function get_method_settings_value($instance_id, $key)
    {
        return get_option('woocommerce_woo_lithuaniapost_lpexpress_terminal_' . $instance_id . '_settings')[$key];
    }

    private function is_selected_method_lp($method, $index): bool
    {
        // Get selected method
        $selected_method = WC()->session->get('chosen_shipping_methods') [$index];
        if (!$selected_method) return false;
        return $method->get_id() == $selected_method && $method->get_method_id() == 'woo_lithuaniapost_lpexpress_terminal';
    }

    private function is_chosen_method_lp(): bool
    {
        $selected_method = WC()->session->get('chosen_shipping_methods');
        if (!$selected_method) return false;
        return strpos($selected_method[0], "woo_lithuaniapost_lpexpress_terminal") !== false;
    }

    private function get_chosen_method_settings_value($key)
    {
        $selected_method = WC()->session->get('chosen_shipping_methods');
        if (!$selected_method) return null;
        return $this->get_method_settings_value(explode(":", $selected_method[0])[1], $key);
    }

    private function get_chosen_method_instance_id()
    {
        $selected_method = WC()->session->get('chosen_shipping_methods');
        if (!$selected_method) return null;
        return explode(":", $selected_method[0])[1];
    }

    /**
     * Custom terminal field validation
     *
     * @since 1.0.0
     */
    private function validate_selected_terminal($arg): bool
    {
        $selected_terminal_id = $this->get_selected_terminal_id();
        if (empty ($selected_terminal_id)) {
            wc_add_notice(__('Please select parcel locker', 'woo-lithuaniapost'), 'error');
            return false;
        } else {
            // Validate against random values
            global $wpdb;

            $request_terminal_id = sanitize_text_field($selected_terminal_id);
            // Check if selected terminal exists in DB
            $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $request_terminal_id);
            if (!$terminal) {
                wc_add_notice(__('Please select parcel locker', 'woo-lithuaniapost'), 'error');
                return false;
            }
        }
        return true;
    }

    private function get_request_shipping_country(): string
    {
        $use_shipping_address = $_POST['ship_to_different_address'] ?? null == true;
        $prefix = $use_shipping_address ? 'shipping' : 'billing';
        return $_POST[$prefix . '_country'];
    }

    private function validate_parcel($arg): bool
    {
        $validation_option = Woo_Lithuaniapost_Admin_Settings::get_option('validate_shipping_before_checkout');
        if ($validation_option == 'no') {
            return true;
        }
        $validate_parcel_request = $this->create_parcel_validation_request();
        $error_callback = function ($response_body) {
            return $this->handle_validation_error_response($response_body);
        };
        return apply_filters('woo_lithuaniapost_api_is_parcel_valid', $validate_parcel_request, $error_callback);
    }

    private function create_parcel_validation_request()
    {

        $plan_code = $this->get_chosen_method_settings_value('plan');
        $parcel_type = $this->get_chosen_method_settings_value('parcel_type');
        $size = 'M';//size validation not needed but value must be send to API
        $weight = 1;//weight should be already validated via available shipping check

        $services = null;
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : false;
        if ($payment_method == 'cod') {
            $services [] = ['code' => 'cod', 'value' => 1];
        }

        $receiver_address = $this->create_delivery_address_validation_request($plan_code);

        return [
            'parcel' => [
                'size' => $size,
                'type' => $parcel_type,
                'weight' => $weight
            ],
            'plan' => [
                'code' => $plan_code
            ],
            'receiver' => $receiver_address,
            'services' => $services,
            'options' => [
                'resolveAddress' => true,
                'lookupAddress' => false,
                'validateSenderAddress' => false,
                'validateCn' => false
            ]
        ];
    }
}

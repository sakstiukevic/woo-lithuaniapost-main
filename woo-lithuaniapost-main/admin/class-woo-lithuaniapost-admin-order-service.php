<?php

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-courier-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-parcel-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-shipping-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-sticker-api.php';

/**
 * WooCommerce custom order actions
 * Create label
 * Call courier
 * Print label
 * Print manifest
 * Print CN23 form
 * Print all documents
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
class LpOrderStatus
{
    public static $PARCEL_CREATED;
    public static $PARCEL_FAILED;
    public static $COURIER_PENDING;
    public static $COURIER_CALLED;
    public static $SHIPPING_INITIATED;
    public static $ON_THE_WAY;
    public static $PARCEL_DELIVERED;
    public static $PARCEL_CANCELED;
    public static $PARCEL_PENDING;

    public $value;
    public $name;

    /**
     * @param $value
     * @param $name
     */
    public function __construct($value, $name)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function to_order_status(): string
    {
        return 'wc-' . $this->value;
    }

    public function is_shipping_initiated(): bool
    {
        return $this == LpOrderStatus::$SHIPPING_INITIATED || $this == LpOrderStatus::$COURIER_PENDING || $this == LpOrderStatus::$COURIER_CALLED || $this == LpOrderStatus::$PARCEL_DELIVERED || $this == LpOrderStatus::$ON_THE_WAY;
    }

    public function is_ready_to_initiated(): bool
    {
        return $this == LpOrderStatus::$PARCEL_CREATED;
    }

    public static function find_by_value(string $value)
    {
        return LpOrderStatus::find_by($value, 'value');
    }

    public static function find_by_name(string $name)
    {
        return LpOrderStatus::find_by($name, 'name');
    }

    private static function find_by(string $arg_to_find, string $find_by_key)
    {
        $all_statuses = [self::$PARCEL_CREATED, self::$PARCEL_FAILED, self::$COURIER_PENDING, self::$COURIER_CALLED, self::$SHIPPING_INITIATED, self::$ON_THE_WAY, self::$PARCEL_DELIVERED, self::$PARCEL_CANCELED];
        $found_value = array_search($arg_to_find, array_column($all_statuses, $find_by_key));

        if ($found_value !== false) {
            return $all_statuses[$found_value];
        }
        return null;
    }
}

LpOrderStatus::$PARCEL_CREATED = new LpOrderStatus("lp-parcel-created", "PARCEL_CREATED");
LpOrderStatus::$PARCEL_FAILED = new LpOrderStatus("lp-parcel-failed", "PARCEL_FAILED");
LpOrderStatus::$COURIER_PENDING = new LpOrderStatus("lp-courier-await", "COURIER_PENDING");
LpOrderStatus::$COURIER_CALLED = new LpOrderStatus("lp-courier-called", "COURIER_CALLED");
LpOrderStatus::$SHIPPING_INITIATED = new LpOrderStatus("lp-label-created", "SHIPPING_INITIATED");
LpOrderStatus::$ON_THE_WAY = new LpOrderStatus("lp-on-the-way", "ON_THE_WAY");
LpOrderStatus::$PARCEL_DELIVERED = new LpOrderStatus("lp-delivered", "PARCEL_DELIVERED");
LpOrderStatus::$PARCEL_CANCELED = new LpOrderStatus("lp-cancelled", "PARCEL_CANCELED");
LpOrderStatus::$PARCEL_PENDING = new LpOrderStatus("lp-parcel-await", "PARCEL_PENDING");

class LpOrderActionErrorKey
{
    const FAILED_INITIATE = "Failed to initiate shipping";
    const ACTION_NOT_AVAILABLE = "Action is not available";
}

class LpOrderAction
{
    const CALL_COURIER = "CALL_COURIER";
    const PRINT_MANIFEST = "PRINT_MANIFEST";
    const INIT_SHIPPING = "INIT_SHIPPING";
    const CANCEL_SHIPPING = "CANCEL_SHIPPING";
    const CREATE_PARCEL = "CREATE_PARCEL";
    const PRINT_LABEL = "PRINT_LABEL";
    const NONE = "NONE";
}

class Woo_Lithuaniapost_Admin_Order_Service
{

    const CN_PARCEL_TYPE = 'sell';
    const CN_PARCEL_TYPE_NOTES = 'Sell Items';

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

    private $courier_api;
    private $parcel_api;
    private $shipping_api;
    private $sticker_api;

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
        $this->courier_api = new Woo_Lithuaniapost_Admin_Courier_Api($plugin_name, $version);
        $this->parcel_api = new Woo_Lithuaniapost_Admin_Parcel_Api($plugin_name, $version);
        $this->shipping_api = new Woo_Lithuaniapost_Admin_Shipping_Api($plugin_name, $version);
        $this->sticker_api = new Woo_Lithuaniapost_Admin_Sticker_Api($plugin_name, $version);
    }

    public function get_lp_order_statuses(): array
    {
        $statuses = [];

        $statuses[LpOrderStatus::$COURIER_PENDING->value] = __( 'Courier Pending', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$PARCEL_CREATED->value] = __( 'Parcel Created', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$PARCEL_FAILED->value] = __( 'Parcel Create Failed', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$SHIPPING_INITIATED->value] = __( 'Shipment Created', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$COURIER_CALLED->value] = __( 'Courier Called', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$ON_THE_WAY->value] = __( 'On the way', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$PARCEL_DELIVERED->value] = __( 'Delivered', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$PARCEL_CANCELED->value] = __( 'Cancelled', 'woo-lithuaniapost' );
        $statuses[LpOrderStatus::$PARCEL_PENDING->value] = __( 'On-hold', 'woo-lithuaniapost' );

        return $statuses;
    }

    public function handle_order_query_args(array $args)
    {
        if ($args && isset($args['status']) && is_array($args['status'])) {
            foreach ($this->get_lp_order_statuses() as $status => $lp_order_status_name) {
                $args['status'][] = $status;
            }
        }
        return $args;
    }

    public function handle_generate_stickers(array $order_ids)
    {
        if (empty($order_ids)) {
            return false;
        }
        $result['errors'] = [];
        $order_ids_to_process = [];
        $order_ids_to_initiate = [];
        foreach ($order_ids as $order_id) {
            $available_actions = $this->get_available_order_actions_by_id($order_id);
            $action_available = false;
            foreach ($available_actions as $action) {
                switch ($action) {
                    case LpOrderAction::INIT_SHIPPING:
                        $order_ids_to_initiate[] = $order_id;
                        $order_ids_to_process[] = $order_id;
                        $action_available = true;
                        break;
                    case LpOrderAction::PRINT_LABEL:
                        $order_ids_to_process[] = $order_id;
                        $action_available = true;
                        break;
                }
            }
            if ($action_available !== true) {
                $result['errors'][LpOrderActionErrorKey::ACTION_NOT_AVAILABLE][] = $order_id;
            }
        }
        if (!empty($order_ids_to_initiate)) {
            $result['errors'] += $this->handle_initiate_shipping($order_ids_to_initiate);
        }
        if ($result['errors']) {
            $errors = $result['errors'];
            $order_ids_to_generate_labels = array_filter($order_ids_to_process, function ($order_id) use ($errors) {
                foreach ($errors as $error => $error_order_ids) {
                    if (in_array($order_id, $error_order_ids)) return false;
                }
                return true;
            });
        } else {
            $order_ids_to_generate_labels = $order_ids_to_process;
        }
        if (empty($order_ids_to_generate_labels)) {
            return $result;
        }
        return $this->sticker_api->download_stickers_pdf($order_ids_to_generate_labels);
    }

    public function download_labels(array $order_ids)
    {
        $this->sticker_api->download_stickers_pdf($order_ids);
    }

    public function handle_generate_manifests(array $order_ids)
    {
        if (empty($order_ids)) {
            return false;
        }
        $result['errors'] = [];
        $order_ids_to_generate_manifest = [];
        $order_ids_to_call_courier = [];
        foreach ($order_ids as $order_id) {
            $available_actions = $this->get_available_order_actions_by_id($order_id);
            foreach ($available_actions as $action) {
                switch ($action) {
                    case LpOrderAction::CALL_COURIER:
                        $order_ids_to_call_courier[] = $order_id;
                        $order_ids_to_generate_manifest[] = $order_id;
                        break;
                    case LpOrderAction::PRINT_MANIFEST:
                        $order_ids_to_generate_manifest[] = $order_id;
                        break;
                    case LpOrderAction::CREATE_PARCEL:
                    case LpOrderAction::INIT_SHIPPING:
                        $result['errors'][LpOrderActionErrorKey::FAILED_INITIATE][] = $order_id;
                        break;

                }
            }
        }
        if (!empty($order_ids_to_call_courier)) {
            $this->handle_call_courier($order_ids_to_call_courier);
        }
        if (empty($order_ids_to_generate_manifest)) {
            return $result;
        }
        return $this->courier_api->download_manifests($order_ids_to_generate_manifest);
    }

    public function handle_call_courier($order_ids): bool
    {
        $call_courier_response = $this->courier_api->call($order_ids);
        if (!$call_courier_response) {
            return false;
        }
        foreach ($call_courier_response as $response_item) {
            if ($response_item->idRef) {
                $order = wc_get_order(intval($response_item->idRef));
                $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$COURIER_CALLED->value);
                $order->save();
            }
        }
        return true;
    }

    public function handle_cancel_labels($order_ids)
    {
        if (empty($order_ids)) {
            return false;
        }
        $result['errors'] = [];
        $order_ids_to_process = [];
        foreach ($order_ids as $order_id) {
            $available_actions = $this->get_available_order_actions_by_id($order_id);
            $action_available = false;
            foreach ($available_actions as $action) {
                switch ($action) {
                    case LpOrderAction::CANCEL_SHIPPING:
                        $order_ids_to_process[] = $order_id;
                        $action_available = true;
                        break;
                }
            }
            if ($action_available !== true) {
                $result['errors'][LpOrderActionErrorKey::ACTION_NOT_AVAILABLE][] = $order_id;
            }
        }
        if (empty($order_ids_to_process)) {
            return $result;
        }
        $cancel_result = $this->shipping_api->cancel($order_ids_to_process);
        if ($cancel_result === false) {
            foreach ($order_ids_to_process as $order_id) {
                $result['errors'][LpOrderActionErrorKey::ACTION_NOT_AVAILABLE][] = $order_id;
            }
        } else {
            foreach ($order_ids_to_process as $order_id) {
                $found_item_index = array_search($order_id, array_column($cancel_result, 'idRef'));
                $response_item = $found_item_index === false ? null : $cancel_result[$found_item_index];
                if ($response_item && $response_item->cancelled === true) {
                    $this->remove_order_meta_data($order_id);
                } else {
                    $result['errors'][LpOrderActionErrorKey::ACTION_NOT_AVAILABLE][] = $order_id;
                }
            }
        }
        return $result;
    }

    private function remove_order_meta_data($order_id)
    {
        global $wpdb;

        $order = wc_get_order($order_id);
        $order->update_meta_data('_woo_lithuaniapost_barcode', null);
        $order->update_meta_data('_woo_lithuaniapost_shipping_item_id', null);
        $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$PARCEL_CANCELED->value);
        $order->save();

        $wpdb->delete($wpdb->woo_lithuaniapost_tracking_status, [
            'order_id' => $order->get_id()
        ]);
    }

    /**
     * Check if shipping method belongs to LP
     *
     * @return bool
     * @since 1.0.o
     */
    protected function is_shipping_method()
    {
        $order = wc_get_order(get_the_ID());

        return $this->is_shipping_method_supported($order);
    }

    protected function is_shipping_method_supported($order)
    {
        return $order->has_shipping_method('woo_lithuaniapost_lpexpress_terminal');
    }

    /**
     * Check if lpexpress method
     *
     * @return bool
     * @since 1.0.0
     */
    protected function is_lpexpress_method()
    {
        $order = wc_get_order(get_the_ID());

        foreach ($order->get_shipping_methods() as $method) {
            return strpos($method->get_method_id(), 'lpexpress') !== false;
        }

        return false;
    }

    public function handle_initiate_shipping($order_ids)
    {
        $errors = [];
        $shipping_response = $this->shipping_api->initiate($order_ids);
        if ($shipping_response) {
            $order_statuses = $this->on_initiate_success($shipping_response, $this->get_orders($order_ids));
            foreach ($order_statuses as $order_id => $status) {
                if (!ShippingItemStatus::is_status_ok($status)) {
                    $errors[LpOrderActionErrorKey::FAILED_INITIATE][] = $order_id;
                }
            }
        } else {
            foreach ($order_ids as $order_id) {
                $errors[LpOrderActionErrorKey::FAILED_INITIATE][] = $order_id;
            }
        }
        return $errors;
    }

    public function handle_create_parcel($order, $overwrite_id_ref)
    {
        $lp_shipping_method = $this->get_lp_shipping_method($order);
        if (!$lp_shipping_method) return;
        if (isset($lp_shipping_method->instance_id)) {
            $order->update_meta_data('_woo_lithuaniapost_lpexpress_shipping_method_instance_id', $lp_shipping_method->instance_id);
            $order->save();
        }
        $this->create_parcel($order, $overwrite_id_ref, $this->create_parcel_create_request($order, $lp_shipping_method));
    }

    public function create_cn_request($order): array
    {
        $cn_parts = [];
        $min_weight = apply_filters('woo_lithuaniapost_admin_util_get_min_weight', '');

        foreach ($order->get_items() as $item) {
            $virtual = $item->get_product()->is_virtual('yes');
            if ($virtual) continue;
            $weight = $item->get_product()->get_weight();
            $weight_in_grams = intval(ceil((floatval($weight) ?: 0) * ($min_weight)));
            $quantity = intval($item->get_quantity());
            $summary = $item->get_name() != null ? substr($item->get_name(), 0, 64) : null;
            $cn_parts [] = [
                'summary' => $summary ? mb_convert_encoding($summary, "UTF-8", "UTF-8") : null,
                'amount' => $item->get_total(),
                'currencyCode' => $order->get_currency(),
                'weight' => max(($weight_in_grams * $quantity), 1),
                'quantity' => $quantity
            ];
        }

        return [
            'contentType' => self::CN_PARCEL_TYPE,
            'contentDescription' => __(self::CN_PARCEL_TYPE_NOTES, 'woo-lithuaniapost'),
            'parts' => $cn_parts
        ];
    }

    public function on_status_changed($order_id, $status, $next_status, $order)
    {
        if ($order != null) {
            $lp_shipping_method = $this->get_lp_shipping_method($order);
            if (!$lp_shipping_method) {
                return;
            }
            $this->save_order_meta_info($order, $lp_shipping_method);
            $lp_shipping_item = $order->get_meta('_woo_lithuaniapost_shipping_item_id');
            if ($order->is_paid()) {
                if ($lp_shipping_item) {
                    return;
                }
                $this->handle_create_parcel($order, false);
            } else {
                if (!$lp_shipping_item) {
                    $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$PARCEL_PENDING->value);
                    $order->save();
                }
            }
        }
    }

    public function get_available_order_actions_by_id($order_id): array
    {
        return $this->get_available_order_actions(wc_get_order($order_id));
    }

    public function get_available_order_actions($order): array
    {
        $actions = [];
        if (!$this->is_shipping_method_supported($order)) {
            return $actions;
        }
        if ($order->get_meta('_woo_lithuaniapost_barcode')) {
            $shipping_status_value = $order->get_meta('_woo_lithuaniapost_shipping_status_value');
            $actions[] = LpOrderAction::PRINT_LABEL;
            $lp_order_status = $shipping_status_value ? LpOrderStatus::find_by_value($shipping_status_value) : LpOrderStatus::find_by_value($order->get_status());
            if ($lp_order_status) {
                if ($lp_order_status == LpOrderStatus::$COURIER_PENDING) {
                    $actions[] = LpOrderAction::CALL_COURIER;
                } else if ($lp_order_status == LpOrderStatus::$COURIER_CALLED || $order->get_meta('_woo_lithuaniapost_lpexpress_courier_called_date')) {
                    $actions[] = LpOrderAction::PRINT_MANIFEST;
                }
                if ($lp_order_status != LpOrderStatus::$ON_THE_WAY && $lp_order_status != LpOrderStatus::$PARCEL_DELIVERED && $lp_order_status != LpOrderStatus::$PARCEL_CANCELED) {
                    $actions[] = LpOrderAction::CANCEL_SHIPPING;
                }
            }
        } else {
            if ($order->get_meta('_woo_lithuaniapost_shipping_item_id')) {
                $actions[] = LpOrderAction::INIT_SHIPPING;
            } else {
                $actions[] = LpOrderAction::CREATE_PARCEL;
            }
        }
        return $actions;
    }

    public function get_parcel_type_translation($parcel_type)
    {
        switch ($parcel_type) {
            case 'T2S':
                return __('Storage in a parcel locker','woo-lithuaniapost');
            case 'H2T':
                return __('Courier -> Parcel locker','woo-lithuaniapost');
            case 'T2T':
                return __('Parcel locker -> Parcel locker','woo-lithuaniapost');
            case 'P2H':
                return __('Post office -> Recipient address','woo-lithuaniapost');
            case 'T2H':
                return __('Parcel locker -> Recipient address','woo-lithuaniapost');
            case 'H2H':
                return __('Courier -> Recipient address','woo-lithuaniapost');
            case 'H2P':
                return __('Courier -> Post office','woo-lithuaniapost');
        };
        return $parcel_type;
    }

    public function get_plan_translation($plan_code)
    {
        switch ($plan_code) {
            case 'PROCESSES_DOCUMENTS':
                return __('Processes documents','woo-lithuaniapost');
            case 'UNTRACKED':
                return __('Untracked','woo-lithuaniapost');
            case 'TERMINAL':
                return __('Locker','woo-lithuaniapost');
            case 'HANDS':
                return __('Courier','woo-lithuaniapost');
            case 'TRACKED_SIGNED':
                return __('Tracked signed','woo-lithuaniapost');
            case 'SIGNED':
                return __('Signed','woo-lithuaniapost');
            case 'TRACKED':
                return __('Tracked','woo-lithuaniapost');
        };
        return $plan_code;
    }

    public function get_lp_shipping_method($order)
    {
        if ($order->has_shipping_method("woo_lithuaniapost_lpexpress_terminal")) {
            $shipping_method_instance_id = array_values($order->get_shipping_methods())[0]->get_instance_id();
            // Get names of all the shipping classes
            $shipping_class_names = WC()->shipping()->get_shipping_method_class_names();

            // Create an instance of the shipping method passing the instance ID
            $lp_shipping_method = new $shipping_class_names["woo_lithuaniapost_lpexpress_terminal"]($shipping_method_instance_id);
            if ($lp_shipping_method && $lp_shipping_method->enabled == "yes") {
                return $lp_shipping_method;
            }
        }
        return null;
    }

    /**
     * Format shipment data hook
     *
     * @param WC_Order $order
     * @return array
     * @since 1.0.0
     */
    public function create_parcel_create_request($order, $lp_shipping_method)
    {
        $request = $this->to_parcel_request($order, $lp_shipping_method);
        if (!$request || !is_array($request) || !json_encode($request)) {
            $request = $this->to_parcel_request($order, $lp_shipping_method, true);
        }
        return $request;
    }

    private function to_parcel_request($order, $lp_shipping_method, $exclude_cn = false)
    {
        $receiver_phone = $order->get_billing_phone() ? $order->get_billing_phone() : null;

        $total_weight_in_g = $this->get_order_weight($order);
        $plan = $lp_shipping_method->plan;
        $parcel_type = $lp_shipping_method->parcel_type;
        $size = apply_filters('woo_lithuaniapost_size_service_resolve_order_size', $order, $lp_shipping_method);
        $cn_json = $exclude_cn ? null : ($this->create_cn_request($order) ?? null);
        $services = null;
        $payment_method = $order->get_payment_method();
        if ($payment_method == 'cod') {
            // COD Value
            $services = [];
            $services[] = ['code' => 'cod', 'value' => $order->get_total()];
        }
        $terminal_id = $this->is_terminal_required($plan) ? ($_POST['woo_lithuaniapost_lpexpress_terminal_id'] ?? $order->get_meta('_woo_lithuaniapost_lpexpress_terminal_id')) : null;
        $pickup_address_id = Woo_Lithuaniapost_Admin_Settings::get_option('use_pickup_address') == 'yes' ? Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_address_pickup_id') : null;
        $comment = $_REQUEST['order_comments'] ?? null;
        $address1 = null;
        $address2 = null;
        if ($order->get_shipping_country() != 'LT') {
            $corrected_foreign_address = $this->get_corrected_foreign_address($order);
            $address1 = $corrected_foreign_address['address1'];
            $address2 = $corrected_foreign_address['address2'];
        }

        /**
         * Form shipment data
         */
        return [
            'comment' => $comment ?? null,
            'pickupAddressId' => $pickup_address_id ?? null,
            'source' => 'woocommerce',
            'idRef' => $order->get_id(),
            'parcel' => [
                'size' => $size,
                'type' => $parcel_type,
                'weight' => $total_weight_in_g
            ],
            'plan' => [
                'code' => $plan
            ],
            'receiver' => [
                'name' => sprintf('%s %s [#%s]',
                    $order->get_shipping_first_name(),
                    $order->get_shipping_last_name(),
                    $order->get_id()
                ),
                'companyName' => $order->get_shipping_company(),
                'contacts' => [
                    'phone' => $receiver_phone,
                    'email' => $order->get_billing_email()
                ],
                'address' => [
                    'terminalId' => $plan == 'TERMINAL' ? $terminal_id : null,
                    'locality' => $order->get_shipping_city(),
                    'address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'address1' => $address1,
                    'address2' => $address2,
                    'postalCode' => $order->get_shipping_postcode(),
                    'countryCode' => $order->get_shipping_country()
                ],
            ],
            'documents' => [
                'cn' => $cn_json
            ],
            'services' => $services
        ];
    }

    private function is_terminal_required($plan_code) {
        return $plan_code == 'TERMINAL';
    }

    public function handle_parcel_save(): bool
    {
        $order_id = $_POST ['order_id'];
        if (!$order_id) return false;
        $order = wc_get_order($order_id);
        if (!$order) return false;
        if ($order->get_meta('_woo_lithuaniapost_barcode')) {
            return false;
        }
        $parcel_save_request = $this->create_parcel_update_request($order);
        if ($order->get_meta('_woo_lithuaniapost_shipping_item_id')) {
            $updated = $this->parcel_api->update_parcel($order_id, $parcel_save_request) !== false;
            if ($updated) {
                $this->on_parcel_save($order, $parcel_save_request);
            }
            return $updated;
        }
        $parcel_save_request['idRef'] = $order->get_id();
        return $this->create_parcel($order, $order->get_meta('_woo_lithuaniapost_parcel_create_error') != false, $parcel_save_request);
    }

    private function create_parcel($order, $overwrite_id_ref, $parcel_create_request): bool
    {
        $error_callback = function ($response_body) use ($order) {
            return $this->handle_failed_parcel_create_response($response_body, $order);
        };

        if (!is_array($parcel_create_request)) {
            $this->handle_failed_parcel_create_response($parcel_create_request, $order);
            return false;
        }

        $request_json = json_encode($parcel_create_request);
        if (!$request_json) {
            $this->handle_failed_parcel_create_response($parcel_create_request, $order);
            return false;
        }

        $parcel_create_request['overwriteIdRef'] = $overwrite_id_ref == true;
        $created_parcel_response = $this->parcel_api->create_parcel($parcel_create_request, $error_callback);
        if ($created_parcel_response) {
            $parcel_id = $created_parcel_response->parcelId;
            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$PARCEL_CREATED->value);
            $order->update_meta_data('_woo_lithuaniapost_shipping_item_id', $parcel_id);
            $this->on_parcel_save($order, $parcel_create_request, false);
            $order->save();
            return true;
        }
        return false;
    }

    private function on_parcel_save($order, $parcel_request, $save_order = true)
    {
        $order->update_meta_data('_woo_lithuaniapost_shipping_item_weight', $parcel_request['parcel']['weight'] ?? null);
        $order->update_meta_data('_woo_lithuaniapost_shipping_item_size', $parcel_request['parcel']['size'] ?? null);
        $order->update_meta_data('_woo_lithuaniapost_shipping_item_size', $parcel_request['parcel']['size'] ?? null);

        $saved_terminal_id = $parcel_request['receiver']['address']['terminalId'] ?? null;
        if ($saved_terminal_id) {

            $terminal_id = sanitize_text_field($saved_terminal_id);
            $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $terminal_id);

            if ($terminal) {
                $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal_id', $terminal_id);
                $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal', sprintf('%s - %s, %s', $terminal [0]->name, $terminal [0]->address, $terminal [0]->city));
            }
        }

        $order->update_meta_data('_woo_lithuaniapost_parcel_create_error', null);
        if ($save_order) {
            $order->save();
        }
    }

    public function get_order_weight($order): int
    {
        /**
         * Get WooCommerce weight unit
         */
        $min_weight = apply_filters('woo_lithuaniapost_admin_util_get_min_weight', '');

        $total_weight = 0;

        foreach ($order->get_items() as $item_id => $product_item) {
            $quantity = $product_item->get_quantity(); // get quantity
            $product = $product_item->get_product(); // get the WC_Product object
            $product_weight = $product->get_weight(); // get the product weight
            if ($product_weight) {
                // Add the line item weight to the total weight calculation
                $total_weight += max(floatval($product_weight * $quantity), $min_weight);
            } else {
                $total_weight += $min_weight;
            }
        }
        return intval(ceil(apply_filters('woo_lithuaniapost_admin_util_convert_to_grams', $total_weight)));
    }

    private function get_corrected_foreign_address($order): array
    {
        $address1 = $order->get_shipping_address_1();
        $address2 = $order->get_shipping_address_2();
        $address1_len = strlen($address1);
        if ($address1_len > 50) {
            $address1_tail = substr($address1, 50, $address1_len);
            $address1 = substr($address1, 0, 50);
            $address2 = $address1_tail . ' ' . $address2;
        }
        if (strlen($address2) > 50) {
            return array('address1' => $order->get_shipping_address_1(), 'address2' => $order->get_shipping_address_2());
        }
        return array('address1' => $address1, 'address2' => $address2);
    }

    private function get_orders($order_ids): array
    {
        $orders = [];
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $orders[] = $order;
            }
        }
        return $orders;
    }

    private function on_initiate_success($shipping_response, $orders_to_process): array
    {
        $shipping_request_id = $shipping_response->requestId;
        $shipping_status_response = $this->shipping_api->get_status($shipping_request_id);
        if ($shipping_status_response) {
            $this->save_shipping_request($shipping_request_id, "OK");
            return $this->process_shipping_status($shipping_status_response, $orders_to_process, $shipping_request_id);
        } else {
            $this->save_shipping_request($shipping_request_id, "FAILED");
        }
        return [];
    }

    private function save_shipping_request(string $request_id, string $status)
    {
        global $wpdb;

        $wpdb->insert($wpdb->woo_lithuaniapost_shipping_request, [
            'request_id' => $request_id,
            'status' => $status,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    private function process_shipping_status($shipping_status_response, $orders_to_process, $shipping_request_id): array
    {
        global $wpdb;

        $order_statuses = [];
        $event_to_complete_order = Woo_Lithuaniapost_Admin_Settings::get_option('event_to_change_status_to_completed');
        $event_to_send_tracking_email = Woo_Lithuaniapost_Admin_Settings::get_option('event_to_send_tracking_email');

        if (ShippingStatus::is_status_ok($shipping_status_response->status)) {
            foreach ($shipping_status_response->items as $shipping_item_status) {
                $order_statuses[$shipping_item_status->idRef] = $shipping_item_status->status;
                if (ShippingItemStatus::is_status_ok($shipping_item_status->status)) {
                    $shipping_item_order = array_search($shipping_item_status->idRef, array_column($orders_to_process, 'id'));
                    if ($shipping_item_order !== false) {
                        $order = $orders_to_process[$shipping_item_order];

                        $order->update_meta_data('_woo_lithuaniapost_barcode', $shipping_item_status->barcode);
                        $order->update_meta_data('_woo_lithuaniapost_shipping_request_id', $shipping_request_id);

                        if ($shipping_item_status->status == ShippingItemStatus::COURIER_PENDING) {
                            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$COURIER_PENDING->value);
                        } else if ($shipping_item_status->status == ShippingItemStatus::COURIER_CALLED) {
                            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$COURIER_CALLED->value);
                        } else {
                            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$SHIPPING_INITIATED->value);
                        }
                        if ($event_to_complete_order && $event_to_complete_order === LpOrderStatus::$SHIPPING_INITIATED->to_order_status()) {
                            $order->update_status('completed');
                        }

                        if ($event_to_send_tracking_email && $event_to_send_tracking_email === LpOrderStatus::$SHIPPING_INITIATED->to_order_status()) {
                            if (!$order->get_meta('_woo_lithuaniapost_tracking_mail_send') && $order->get_meta('_woo_lithuaniapost_barcode')) {
                                $order->update_meta_data('_woo_lithuaniapost_tracking_mail_send', true);
                                do_action('woo_lithuaniapost_send_tracking_email', $order);
                            }
                        }
                        $order->save();
                        $wpdb->insert($wpdb->woo_lithuaniapost_tracking_status, [
                            'order_id' => $order->get_id(),
                            'barcode' => $shipping_item_status->barcode,
                            'created' =>  date('Y-m-d H:i:s'),
                            'status' => 'LABEL_CREATED'
                        ]);
                    }
                }
            }
        }
        return $order_statuses;
    }

    private function handle_failed_parcel_create_response($error_result, $order)
    {
        if ($order->get_meta('_woo_lithuaniapost_shipping_item_id')) {
            return;
        }
        if ((is_array($error_result) && !empty($error_result) && $error_result[0]->error) || $error_result->error) {
            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$PARCEL_FAILED->value);
            $order->update_meta_data('_woo_lithuaniapost_parcel_create_error', $error_result);
            $order->save();
        } else {
            $json_error_code = json_last_error();
            $json_error_message = json_last_error_msg();
            $printed_result = print_r($error_result, true);
            if ($printed_result) {
                $error_message = $printed_result . 'JSON error, code: ' . $json_error_code . ' ' . 'message: ' . $json_error_message;
            } else {
                $error_message = 'JSON error, code: ' . $json_error_code . ' ' . 'message: ' . $json_error_message;
            }
            $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$PARCEL_FAILED->value);
            $order->update_meta_data('_woo_lithuaniapost_parcel_create_error', (object)['error' => 'Invalid request: ' . $error_message]);
            $order->save();
        }
    }

    private function create_parcel_update_request($order)
    {

        $receiver_phone = $order->get_billing_phone();

        $plan = $_POST['lp_plan_code'];
        $parcel_type = $_POST['lp_parcel_type'];
        $total_weight_in_g = $_POST['lp_parcel_weight'] ?? null;
        $size = $_POST['lp_parcel_size'] ?? null;
        $part_count = $_POST['lp_part_count'] ?? null;
        $cod_value = $_POST['cod'] ?? null;
        $cn_data = $this->remove_empty_values($_POST['cnForm'] ?? null) ?? null;

        $services = [];
        if ($cod_value && intval($cod_value) > 0) {
            $services[] = [
                'code' => 'cod',
                'value' => $cod_value
            ];
        }
        $services_json = $services;
        $terminal_id = $plan === 'TERMINAL' ? $_POST['woo_lithuaniapost_lpexpress_terminal_id'] ?? $order->get_meta('_woo_lithuaniapost_lpexpress_terminal_id') : null;
        $pickup_address_id = Woo_Lithuaniapost_Admin_Settings::get_option('use_pickup_address') == 'yes' ? Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_address_pickup_id') : null;
        $address1 = null;
        $address2 = null;
        if ($order->get_shipping_country() != 'LT') {
            $corrected_foreign_address = $this->get_corrected_foreign_address($order);
            $address1 = $corrected_foreign_address['address1'];
            $address2 = $corrected_foreign_address['address2'];
        }

        /**
         * Form shipment data
         */
        $create_parcel_request = [
            'pickupAddressId' => $pickup_address_id ?: null,
            'parcel' => [
                'size' => $size,
                'type' => $parcel_type,
                'weight' => $total_weight_in_g,
                'partCount' => $part_count
            ],
            'plan' => [
                'code' => $plan
            ],
            'receiver' => [
                'name' => sprintf('%s %s [#%s]',
                    $order->get_shipping_first_name(),
                    $order->get_shipping_last_name(),
                    $order->get_id()
                ),
                'companyName' => $order->get_shipping_company(),
                'contacts' => [
                    'phone' => $receiver_phone,
                    'email' => $order->get_billing_email()],
                'address' => [
                    'terminalId' => $terminal_id,
                    'locality' => $order->get_shipping_city(),
                    'address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
                    'address1' => $address1,
                    'address2' => $address2,
                    'postalCode' => $order->get_shipping_postcode(),
                    'countryCode' => $order->get_shipping_country()
                ],
            ],
            'documents' => [
                'cn' => @$cn_data
            ],
            'services' => !empty($services_json) ? $services_json : null
        ];
        return $create_parcel_request;
    }

    private function remove_empty_values($arr)
    {
        if (is_array($arr)) {
            foreach ($arr as $index => $value) {
                $arr[$index] = $this->remove_empty_values($value);
            }
        }
        if ($arr === "") {
            return null;
        }
        return $arr;
    }

    private function save_order_meta_info($order, $lp_shipping_method)
    {
        if (!$order) return;
        $save_to_db = false;
        if (isset($lp_shipping_method->instance_id) && !$order->get_meta('_woo_lithuaniapost_lpexpress_shipping_method_instance_id')) {
            $order->update_meta_data('_woo_lithuaniapost_lpexpress_shipping_method_instance_id', $lp_shipping_method->instance_id);
            $save_to_db = true;
        }

        // Save terminal id
        if (isset ($_POST ['woo_lithuaniapost_lpexpress_terminal_id'])) {

            $terminal_id = sanitize_text_field($_POST ['woo_lithuaniapost_lpexpress_terminal_id']);

            $terminal = apply_filters('woo_lithuaniapost_terminal_service_get_terminal_by_id', $terminal_id);

            if ($terminal) {
                $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal_id', $terminal_id);
                $order->update_meta_data('_woo_lithuaniapost_lpexpress_terminal', sprintf('%s - %s, %s', $terminal [0]->name, $terminal [0]->address, $terminal [0]->city));
                $save_to_db = true;
            }
        }
        if ($save_to_db) {
            $order->save();
        }
    }
}

<?php
class Woo_Lithuaniapost_Admin_Order_Settings
{
    public function register_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('UNISEND', 'woo-lithuaniapost'),
            __('UNISEND', 'woo-lithuaniapost'),
            'manage_woocommerce',
            'lp-orders',
            array($this, 'display_submenu_page')
        );
    }
    function display_submenu_page()
    {
        $query =
            [
                'paginate' => true,
                'limit' => 20,
                'shipping_method' => 'woo_lithuaniapost_lpexpress_terminal',
            ];

        if (!empty($_GET['shipping_method'])) {
            $query['shipping_method_instance'] = $_GET['shipping_method'];
            $query = $this->apply_meta_query($query, '_woo_lithuaniapost_lpexpress_shipping_method_instance_id', $_GET['shipping_method']);
        } else {
            $query = $this->apply_meta_query($query, '_woo_lithuaniapost_lpexpress_shipping_method_instance_id', null);
        }
        if (!empty($_GET['customer'])) {
            $customer = explode(" ", $_GET['customer']);
            $customer_input = $customer[0];
            if ($this->str_contains($customer_input, '@')) {
                $query['billing_email'] = $customer_input;
            } else {
                $query['shipping_first_name'] = $customer_input;
                if (!empty($customer[1])) {
                    $query['shipping_last_name'] = $customer[1];
                }
            }
        }
        if (!empty($_GET['barcode'])) {
            $query['_woo_lithuaniapost_barcode'] = $_GET['barcode'];
            $query = $this->apply_meta_query($query, '_woo_lithuaniapost_barcode', $_GET['barcode']);
        }
        if (!empty($_GET['order_id'])) {
            $query['p'] = $_GET['order_id'];
        }

        if (!empty($_GET['paged'])) {
            $query['paged'] = $_GET['paged'];
        }
        if (!empty($_GET['delivery_status']) && $_GET['delivery_status'] != 'all') {
            $completed_order_statuses = [
                LpOrderStatus::$PARCEL_DELIVERED->value,
                LpOrderStatus::$SHIPPING_INITIATED->value,
                LpOrderStatus::$COURIER_CALLED->value,
                LpOrderStatus::$ON_THE_WAY->value,
                LpOrderStatus::$PARCEL_CANCELED->value,
            ];
            switch ($_GET['delivery_status']) {
                case 'new';
                    $incomplete_status = array_diff(array_keys(apply_filters('woocommerce_order_get_lp_order_statuses', '')), $completed_order_statuses);
                    $query = $this->query_by_shipping_status($query, $incomplete_status);
                    break;
                case 'completed';
                    $query = $this->query_by_shipping_status($query, $completed_order_statuses);
                    break;
            }
        } else {
            $query = $this->query_by_shipping_status($query, array_keys(apply_filters('woocommerce_order_get_lp_order_statuses', '')));
        }
        if (!empty($_GET['order_status'])) {
            $status = str_replace('wc-', '', $_GET['order_status']);
            $query = $this->query_by_shipping_status($query, $status);
        }

        $ordersResult = wc_get_orders($query);
        $data = array_map(
            function ($item) {
                if ($item instanceof \Automattic\WooCommerce\Admin\Overrides\OrderRefund || !method_exists($item, 'get_shipping_address_1') || !method_exists($item, 'get_shipping_first_name')) {
                    $address = "-";
                    $customer = "-";
                } else {
                    $address = $item->get_shipping_address_1();
                    if ($item->get_shipping_address_2()) {
                        $address = sprintf('%s %s', $address, $item->get_shipping_address_2());
                    }
                    $address = sprintf('%s, %s %s, %s', $address, $item->get_shipping_postcode(), $item->get_shipping_city(), $item->get_shipping_country());
                    $customer = sprintf('%s %s', $item->get_shipping_first_name(), $item->get_shipping_last_name());
                }
                return array(
                    "id" => $item->get_id(),
                    "order_id" => $item->get_id(),
                    "barcode" => $item->get_meta('_woo_lithuaniapost_barcode'),
                    "orderDetails" => sprintf("<b>%s:</b> %s <br> <b>%s:</b> %s <br> <b>%s:</b> %s <br> <b>%s:</b> %s g", __('Date', 'woo-lithuaniapost'), $item->get_date_created(), __('Address', 'woo-lithuaniapost'), $address, __('Size', 'woo-lithuaniapost'), $item->get_meta('_woo_lithuaniapost_shipping_item_size') ?: '-', __('Weight', 'woo-lithuaniapost'), $item->get_meta('_woo_lithuaniapost_shipping_item_weight') ?: '0'),
                    "service" => $this->get_service_title($item),
                    "customer" => $customer,
                    "order_status" => $item->get_meta('_woo_lithuaniapost_shipping_status_value') ?: $item->get_status(),
                    "shippingMethod" => $item->get_shipping_method(),
                    "actions" => $item->get_id()
                );
            },
            $ordersResult->orders
        );
        require_once __DIR__ . '/partials/html-lithuaniapost-admin-order-settings.php';
        $table = new Lithuaniapost_Admin_Order_Settings_Table_List($data, $ordersResult->total);
        $table->prepare_items();
?>
        <div class="wrap unisend-orders">
            <?php $table->views(); ?>
            <form action="<?php echo esc_url(admin_url('admin.php')) ?>" method="post">
                <?php $table->display(); ?>
            </form>
        </div>
<?php
    }

    private function str_contains($haystack, $needle)
    {
        if ('' === $needle) {
            return true;
        }
        return false !== strpos($haystack, $needle);
    }

    private function query_by_shipping_status($query, $status) {
        $query = $this->apply_meta_query($query, '_woo_lithuaniapost_shipping_status_value', $status);
        $query['_woo_lithuaniapost_shipping_status_value'] = $status;
        return $query;
    }

    private function get_service_title($item)
    {
        $services = [];
        if (method_exists($item, 'get_payment_method') && $item->get_payment_method() === 'cod') {
            $services[] = sprintf('<b>%s:</b> COD', __('Services', 'woo-lithuaniapost'));
        }
        if ($item->get_meta('_woo_lithuaniapost_lpexpress_terminal')) {
            $services[] = sprintf('<b>%s:<br></b>', __('Parcel Locker', 'woo-lithuaniapost')) . $item->get_meta('_woo_lithuaniapost_lpexpress_terminal');
        }
        return implode('<br>', $services);
    }

    public function where_shipping_method($where, $query)
    {
        if (!$query->get('shipping_method')) {
            return $where;
        }
        global $wpdb;
        if ($query->get('shipping_method_instance')) {
            $where .= ' AND woim.meta_key = "instance_id"';
            $where .= $wpdb->prepare(' AND woim.meta_value = %s', $query->get('shipping_method_instance'));
        } else {
            $where .= ' AND woim.meta_key = "method_id"';
            $where .= $wpdb->prepare(' AND woim.meta_value = %s', $query->get('shipping_method'));
        }
        return $where;
    }

    function join_shipping_method($join, $query)
    {
        if (!$query->get('shipping_method')) {
            return $join;
        }
        global $wpdb;
        $join .= ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items as woi ON '. $wpdb->prefix .'posts.ID = woi.order_id';
        $join .= ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as woim ON woi.order_item_id = woim.order_item_id';

        return $join;
    }

    public function generate_label()
    {
        $result = apply_filters("woo_lithuaniapost_order_action_generate_stickers", [$_GET['data']]);
        if ($result === true) {
            die();
        }
        $this->on_action_completed();
    }

    public function create_parcel()
    {
        $order = wc_get_order($_GET['data']);
        if (!$order) {
            return;
        }
        apply_filters("woo_lithuaniapost_order_action_create_parcel", $order, false);
        $this->on_action_completed();
    }

    public function handle_bulk_action()
    {
        $action = "woo_lp_" . $_POST['action'];

        if ($action != 'woo_lp_filter_orders' && $action != 'woo_lp_lp-orders-reset-filter' && !empty($_POST['id'])) {
            $ids  = $_POST['id'];
            $bulk_actions = new Woo_Lithuaniapost_Admin_Order_Bulk_Actions("", "");
            $bulk_actions->handle_action("", $action, $ids);
        }

        $this->on_action_completed();
    }

    function handle_custom_query_var($query, $query_vars)
    {
        if (!empty($query_vars['_woo_lithuaniapost_barcode'])) {
            $query['meta_query'][] = array(
                'key' => '_woo_lithuaniapost_barcode',
                'value' => esc_attr($query_vars['_woo_lithuaniapost_barcode']),
            );
        }
        $status_value = $query_vars['_woo_lithuaniapost_shipping_status_value'] ?? null;
        if (!empty($status_value)) {
            $query['meta_query'][] = array(
                'key' => '_woo_lithuaniapost_shipping_status_value',
                'value' => is_array($status_value) ? $status_value : esc_attr($status_value),
                'compare' => (is_array($status_value) ? 'IN' : '=')
            );
        }
        return $query;
    }

    function handle_woocommerce_screen_ids($screen_ids)
    {
        $screen_ids[] = 'woocommerce_page_lp-orders';
        return $screen_ids;
    }

    private function on_action_completed()
    {
        if ($_POST['action'] === 'lp-orders-reset-filter') {
            $url = remove_query_arg(array(
                'shipping_method',
                'customer',
                'barcode',
                'order_id',
                'order_status'
            ), $_POST['_wp_http_referer']) ?: $_SERVER['HTTP_REFERER'];
        } else {
            $url = add_query_arg(array(
                'shipping_method' => $_POST['shipping_method'],
                'customer' => $_POST['customer'],
                'barcode' => $_POST['barcode'],
                'order_id' => $_POST['order_id'],
                'order_status' => $_POST['order_status'],
            ), $_POST['_wp_http_referer']) ?: $_SERVER['HTTP_REFERER'];
        }
        wp_redirect($url);
        exit();
    }

    private function apply_meta_query($query, $key, $value): array
    {
        if ($value) {
            $addition_query = [
                'key' => $key,
                'value' => $value
            ];
        } else {
            $addition_query = [
                'key' => $key,
                'meta_compare' => 'EXISTS'
            ];
        }
        $query['meta_query'][] = $addition_query;
        return $query;
    }
}

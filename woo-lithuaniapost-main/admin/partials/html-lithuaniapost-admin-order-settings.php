<?php
defined('ABSPATH') || exit;
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
class Lithuaniapost_Admin_Order_Settings_Table_List extends WP_List_Table
{
    private $total;
    public function __construct($data, $total)
    {
        parent::__construct(
            array(
                'singular' => 'key',
                'plural'   => 'keys',
                'ajax'     => false,
            )
        );
        $this->items = $data;
        $this->total = $total;
    }
    function get_columns()
    {
        return array(
            'cb'            => '<input type="checkbox" />',
            'order_id'          => __('Order', 'woocommerce'),
            'customer'      => __('Customer', 'woocommerce'),
            'service'         => __('Service', 'woo-lithuaniapost'),
            'orderDetails'         => __('Order details', 'woocommerce'),
            'shippingMethod'        => __('Shipping Method', 'woocommerce'),
            'order_status'   => __('Status', 'woocommerce'),
            'barcode'          => __('Barcode', 'woo-lithuaniapost'),
            'actions'        => __('Actions', 'woocommerce')
        );
    }

    protected function get_views()
    {
        $order_status = isset($_GET['delivery_status']) ? wp_unslash(trim($_REQUEST['delivery_status'])) : '';
        $url              = add_query_arg('page', 'lp-orders', 'admin.php');;
        $statuses = [
            'all' => __('All orders', 'woo-lithuaniapost'),
            'new' => __('New orders', 'woo-lithuaniapost'),
            'completed' => __('Completed orders', 'woo-lithuaniapost'),
        ];

        $links = [];
        foreach ($statuses as $status => $label) {
            $links[$status] = [
                'url' => add_query_arg('delivery_status', $status, $url),
                'label' => $label,
                'current' => $order_status === $status || ( '' === $order_status && 'all' === $status ),
            ];
        }
        return $this->get_views_links($links);
    }

    function column_order_id($item)
    {
        echo '<a href="' . admin_url('post.php?post=' . $item['id'] . '&action=edit') . '" > ' . $item['id'] . ' </a>';
    }

    function column_order_status($item)
    {
        printf('<mark class="order-status %s"><span>%s</span></mark>', esc_attr(sanitize_html_class('status-' . $item['order_status'])), esc_html(wc_get_order_status_name($item['order_status'])));
    }

    function column_actions($item)
    {
        $order_id = $item['id'];
        if (isset($item['barcode']) && $item['barcode']) {
            return sprintf(
                '<a class="button" href="admin-post.php?action=woo-ltpost-create-label&data=%s">' . __('Create Shipping Label', 'woo-lithuaniapost') . '</a>',
                $item['id']
            );
        }
        $order = wc_get_order($order_id);
        if (!$order) return null;
        if ($order->get_meta('_woo_lithuaniapost_shipping_item_id')) {
            return sprintf(
                '<a class="button" href="admin-post.php?action=woo-ltpost-create-label&data=%s">' . __('Create Shipping Label', 'woo-lithuaniapost') . '</a>',
                $item['id']
            );
        }
        if ($order->get_meta('_woo_lithuaniapost_parcel_create_error')) {
            return sprintf(
                '<a class="button" href="admin-post.php?action=woo-ltpost-create-parcel&data=%s">' . __('UNISEND Create Shipping Parcel', 'woo-lithuaniapost') . '</a>',
                $item['id']
            );
        }
        return null;
    }

    public function column_cb($item)
    {
        ob_start();
?>
        <input id="cb-select-<?php echo esc_attr($item['id']); ?>" type="checkbox" name="id[]" value="<?php echo esc_attr($item['id']); ?>" />
        <?php
        return ob_get_clean();
    }

    protected function extra_tablenav($which)
    {
        $zone_shipping_methods = array_map(function ($shipping_zone) {
            $shipping_methods = array_filter(WC_Shipping_Zones::get_zone($shipping_zone['id'])->get_shipping_methods(), function ($zone) {
                return $zone->id == Woo_Lithuaniapost_Shipping_Lpexpress_Terminal::ID;
            });
            return array_map(function ($shipping_method) {
                return [
                    'title' => $shipping_method->get_title(),
                    'instance_id' => $shipping_method->get_instance_id(),
                ];
            }, $shipping_methods);
        }, WC_Shipping_Zones::get_zones());

        $shipping_method_result = [];
        foreach ($zone_shipping_methods as $zone => $shipping_methods) {
            foreach ($shipping_methods as $key => $shipping_method) {
                $shipping_method_result[] = $shipping_method;
            }
        }

        if ('top' === $which) {
            ob_start();
        ?>
            <input type="hidden" name="page" value="lp-orders">
            <div class="alignleft actions">
                <input type="text" name="order_id" id="order_id" placeholder="<?php echo __('ID', 'woo-lithuaniapost'); ?>" value="<?php echo isset($_GET['order_id']) ? $_GET['order_id'] : '' ?>">
                <input type="text" name="customer" id="customer" placeholder="<?php echo __('Customer', 'woocommerce'); ?>" value="<?php echo isset($_GET['customer']) ? $_GET['customer'] : '' ?>">
                <input type="text" name="barcode" id="barcode" placeholder="<?php echo __('Barcode', 'woo-lithuaniapost'); ?>" value="<?php echo isset($_GET['barcode']) ? $_GET['barcode'] : '' ?>">
                <input type="hidden" id="action" name="action" value="print_labels">
            </div>
            <div class="alignleft actions">
                <select name="shipping_method" data-allow_clear="true">
                    <option value=""><?php echo __('All shipping methods', 'woo-lithuaniapost'); ?></option>
                    <?php array_map(function ($shipping_method) {
                        $selected = '';
                        if ($shipping_method['instance_id'] == ($_GET['shipping_method'] ?? null)) {
                            $selected = 'selected';
                        }
                        echo '<option value="' . $shipping_method['instance_id'] . '" ' . $selected . '>' . $shipping_method['title'] . '</option>';
                    }, $shipping_method_result) ?>
                </select>
                <select name="order_status" data-allow_clear="true">
                    <option value=""><?php echo __('Filter by status', 'woo-lithuaniapost'); ?></option>
                    <?php array_map(function ($label, $status) {
                        $selected = '';
                        if ($status == ($_GET['order_status'] ?? null)) {
                            $selected = 'selected';
                        }
                        echo '<option value="' . $status . '" ' . $selected . '>' . $label. '</option>';
                    }, apply_filters('woocommerce_order_get_lp_order_statuses', ''), array_keys(apply_filters('woocommerce_order_get_lp_order_statuses', ''))) ?>
                </select>
                <?php submit_button(__('Filter', 'woocommerce'), '', 'filter-action', false); ?>
                <?php submit_button(__('Clear', 'woocommerce'), '', 'lp-orders-reset-filter', false); ?>
            </div>
            <div class="alignright actions">
                <?php submit_button(__('Generate and print Labels', 'woo-lithuaniapost'), '', 'label-action', false); ?>
            </div>
            <div class="alignright actions">
                <?php submit_button(__('Call courier and generate manifest', 'woo-lithuaniapost'), '', 'manifest-action', false); ?>
            </div>
<?php
        }
        echo ob_get_clean();
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'order_id':
            case 'barcode':
            case 'orderDetails':
            case 'service':
            case 'customer':
            case 'date':
            case 'order_status':
            case 'order':
            default:
                return $item[$column_name];
        }
    }

    function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->set_pagination_args(array(
            'total_items' => $this->total,
            'per_page'    => 20
        ));
        $this->_column_headers = array($columns, $hidden, $sortable);
    }
}

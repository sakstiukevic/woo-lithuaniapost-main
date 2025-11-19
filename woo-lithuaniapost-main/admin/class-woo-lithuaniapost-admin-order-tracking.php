<?php
/**
 * Order tracking
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
class Woo_Lithuaniapost_Admin_Order_Tracking
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
    public function __construct ( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Schedule tracking info
     *
     * @since 1.0.0
     */
    public function schedule_tracking_info ()
    {
        if (! wp_next_scheduled ( 'woo_lithuaniapost_update_tracking_status' ) ) {
            wp_schedule_event(time(), 'hourly', 'woo_lithuaniapost_update_tracking_status');
        }
    }

    public function schedule_tracking_data_sync()
    {
        if (!wp_next_scheduled('woo_lithuaniapost_sync_tracking_data')) {
            if (Woo_Lithuaniapost_Admin_Settings::get_option('tracking_data_sync_completed')) {
                return;
            }
            wp_schedule_single_event(time() + $this->get_next_sync_tracking_data_time(), 'woo_lithuaniapost_sync_tracking_data');
        }
    }

    /**
     * Save tracking events from API
     *
     * @since 1.0.0
     */
    public function update_tracking_status ()
    {
        global $wpdb;

        date_default_timezone_set('Europe/Vilnius');

        $last_order_id = 0;
        $limit = Woo_Lithuaniapost_Admin_Settings::get_option('tracking_page_size') ?: 500;
        $tracking_datetime_option = Woo_Lithuaniapost_Admin_Settings::get_option('tracking_datetime');
        $event_to_complete_order = Woo_Lithuaniapost_Admin_Settings::get_option('event_to_change_status_to_completed');
        $event_to_send_tracking_email = Woo_Lithuaniapost_Admin_Settings::get_option('event_to_send_tracking_email');
        $tracking_datetime = $tracking_datetime_option ?: null;
        $tracking_datetime_to_save = date('Y-m-d H:i:s');
        $save_datetime = false;
        while ($statuses = $wpdb->get_results($wpdb->prepare("SELECT order_id, barcode, status FROM {$wpdb->woo_lithuaniapost_tracking_status} WHERE order_id > %d AND status NOT IN ('PARCEL_DELIVERED','PARCEL_CANCELED') ORDER BY order_id LIMIT %d", $last_order_id, $limit))) {
            if (!$statuses || empty($statuses)) {
                break;
            }
            $last_order_id = end($statuses)->order_id;

            $barcodes = array_column($statuses, 'barcode');
            $tracking_events = apply_filters('woo_lithuaniapost_api_get_tracking_list', $barcodes, $tracking_datetime);
            if ($tracking_events === false) {
                return;
            }
            $save_datetime = true;
            if (!empty($tracking_events)) {
                foreach ($statuses as $status) {
                    $status_tracking_events = array_filter($tracking_events, function ($event) use ($status) {
                        return $event->mailBarcode == $status->barcode;
                    });
                    if ($status_tracking_events && count($status_tracking_events) > 0) {
                        $last_event = $this->find_with_greatest_date($status_tracking_events);
                        if ($last_event) {
                            if ($last_event->publicStateType != $status->status) {
                                $wpdb->update($wpdb->woo_lithuaniapost_tracking_status, [
                                    'status' => $last_event->publicStateType,
                                    'updated' => date('Y-m-d H:i:s')
                                ], ['barcode' => $status->barcode]);
                                $lp_order_status = LpOrderStatus::find_by_name($last_event->publicStateType);
                                if ($lp_order_status) {
                                    $order = wc_get_order($status->order_id);
                                    if ($order) {
                                        if ($event_to_complete_order && $lp_order_status->to_order_status() === $event_to_complete_order) {
                                            $order->update_status('completed');
                                        }
                                        $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', $lp_order_status->value);
                                        $order->save();
                                        if ((!$event_to_send_tracking_email === false && $lp_order_status == LpOrderStatus::$ON_THE_WAY) || ($event_to_send_tracking_email && $lp_order_status->to_order_status() == $event_to_send_tracking_email)) {
                                            if (!$order->get_meta('_woo_lithuaniapost_tracking_mail_send') && $order->get_meta('_woo_lithuaniapost_barcode')) {
                                                $order->update_meta_data('_woo_lithuaniapost_tracking_mail_send', true);
                                                $order->save();
                                                do_action('woo_lithuaniapost_send_tracking_email', $order);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (count($statuses) < $limit) {
                break;
            }
        }
        if ($save_datetime === true) {
            Woo_Lithuaniapost_Admin_Settings::delete_option('tracking_datetime');
            Woo_Lithuaniapost_Admin_Settings::add_option('tracking_datetime', $tracking_datetime_to_save);
        }
    }

    function register_url_api_endpoint()
    {
        register_rest_route('unisend', '/tracking/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'url_api_endpoint_callback'],
            'permission_callback' => '__return_true',
        ]);
    }

    function url_api_endpoint_callback($params)
    {
        $order = wc_get_order($params['id']);
        $barcode = $order->get_meta('_woo_lithuaniapost_barcode');
        if ($barcode) {
            $tracking_url = substr(get_bloginfo('language'), 0, 2) === 'lt' 
              ? 'https://www.post.lt/siuntu-sekimas?parcels=' . $barcode
              : 'https://www.post.lt/index.php/en/shipments-search?parcels=' . $barcode;

            return new WP_REST_Response(null, 302, ['Location' => $tracking_url]);
        }

        header('Content-Type: text/html');
        _e('We haven’t received your parcel yet.', 'woo-lithuaniapost');
        exit();
    }

    public function find_with_greatest_date($tracking_events)
    {
        $max_time = 0;
        $selected_event = null;
        foreach ($tracking_events as $event) {
            $event_time = strtotime($event->eventDate);
            if ($event_time > $max_time || $event_time == $max_time) {
                $selected_event = $event;
                $max_time = $event_time;
            }
        }
        return $selected_event;
    }

    /**
     * Get tracking events by order id
     *
     * @param $order_id
     * @return array|object|void|null
     */
    public function get_tracking_events($order_id)
    {
        global $wpdb;

        $barcode = $wpdb->get_var($wpdb->prepare("SELECT barcode FROM {$wpdb->woo_lithuaniapost_tracking_status} WHERE order_id = %d", $order_id));
        if ($barcode) {
            return apply_filters('woo_lithuaniapost_api_get_tracking', $barcode);
        }
        return [];
    }

    /**
     * Load tracking email template file
     *
     * @param $order
     * @return false|string
     */
    public function load_tracking_email_template ( $order )
    {
        if ($user = wp_get_current_user()) {
            if ($user_locale = get_user_meta($user->ID, 'locale', true)) {
                switch_to_locale($user_locale);
            }
        }
        ob_start ();
        include plugin_dir_path ( __FILE__ ) . '../templates/emails/tracking-email.php';
        $template = ob_get_clean ();

        return $template;
    }

    /**
     * Send tracking email
     *
     * @param $order
     * @return void
     */
    public function send_tracking_email ( $order )
    {
        $template = $this->load_tracking_email_template ( $order );

        $mailer = WC ()->mailer();
        $subject = __( 'Your order has been sent', 'woo-lithuaniapost' );
        $mailer->send ( $order->get_billing_email (), $subject,
            $mailer->wrap_message ( $subject, $template ), '', ''
        );
    }

    public function sync_tracking_data()
    {
        global $wpdb;

        if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_tracking_events'") == $wpdb->woo_lithuaniapost_tracking_events) {

            $last_id = Woo_Lithuaniapost_Admin_Settings::get_option('tracking_data_sync_last_id') ?: 0;
            $limit = 100;
            $sync_completed = true;

            while ($tracking_events = $wpdb->get_results($wpdb->prepare("SELECT id, order_id, barcode, state FROM {$wpdb->woo_lithuaniapost_tracking_events} WHERE id > %d AND state NOT IN ('PARCEL_DELIVERED','PARCEL_CANCELED') ORDER BY id LIMIT %d", $last_id, $limit))) {
                if (empty($tracking_events)) {
                    break;
                }
                foreach ($tracking_events as $tracking_event) {
                    $last_id = $tracking_event->id;
                    if ($tracking_event->barcode && $tracking_event->order_id) {
                        if (!$wpdb->get_var($wpdb->prepare("SELECT order_id FROM {$wpdb->woo_lithuaniapost_tracking_status} WHERE order_id = %d", $tracking_event->order_id))) {
                            $wpdb->insert($wpdb->woo_lithuaniapost_tracking_status, [
                                'order_id' => $tracking_event->order_id,
                                'barcode' => $tracking_event->barcode,
                                'created' => date('Y-m-d H:i:s'),
                                'status' => $tracking_event->sate ?: 'LABEL_CREATED'
                            ]);
                        }
                    }
                }
                Woo_Lithuaniapost_Admin_Settings::delete_option('tracking_data_sync_last_id');
                Woo_Lithuaniapost_Admin_Settings::add_option('tracking_data_sync_last_id', $last_id);
                $current_hour = date('G');
                if ($current_hour > 6) {
                    $sync_completed = false;
                    break;
                }
            }
            if ($sync_completed === true) {
                Woo_Lithuaniapost_Admin_Settings::add_option('tracking_data_sync_completed', true);
            }
        }
    }

    private function get_next_sync_tracking_data_time()
    {
        $current_hour = date('G');
        if ($current_hour > 7) {
            return ((24 - $current_hour) * 60 * 60);
        }
        return 0;
    }
}

<?php

defined('ABSPATH') || exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 */

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-courier-api.php';

/**
 * The admin-specific settings functionality of the plugin.
 *
 * Defines the WooCommerce settings
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Courier_Schedule
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

    private $schedule_call_courier_hook_name = 'woo_lithuaniapost_call_courier_schedule_hook';


    private $courier_api;

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
    }

    public function reschedule_call_courier_on_settings_save()
    {
        global $current_section;
        if ($current_section != "lpsettings") {
            return;
        }

        $this->reschedule_call_courier();
    }

    public function schedule_call_courier()
    {
        $hook_name = $this->schedule_call_courier_hook_name;
        if (wp_next_scheduled($hook_name)) {
            return;
        }
        $this->reschedule_call_courier();
    }

    public function lp_call_courier()
    {
        $call_courier_response = $this->courier_api->pending_call();
        if ($call_courier_response && !empty($call_courier_response)) {
            foreach ($call_courier_response as $courier_call) {
                if ($courier_call->idRef) {
                    $order = wc_get_order($courier_call->idRef);
                    if ($order) {
                        $order->update_meta_data('_woo_lithuaniapost_shipping_status_value', LpOrderStatus::$COURIER_CALLED->value);
                        $order->update_meta_data('_woo_lithuaniapost_lpexpress_courier_called_date', date('Y-m-d H:i:s'));
                        $order->save();
                    }
                }
            }
        }
        $this->reschedule_call_courier();
    }

    private function reschedule_call_courier()
    {
        $hook_name = $this->schedule_call_courier_hook_name;

        wp_clear_scheduled_hook($hook_name);

        $call_courier_days = Woo_Lithuaniapost_Admin_Settings::get_option('courier_call_days');
        $call_courier_hours = Woo_Lithuaniapost_Admin_Settings::get_option('courier_call_hours');
        if ($call_courier_hours == null || $call_courier_days == null) {
            return;
        }

        $hour_parts = explode(":", $call_courier_hours);
        if (!$hour_parts || count($hour_parts) != 2) {
            return;
        }

        $time_zone = get_option('timezone_string');
        if ($time_zone) {
            date_default_timezone_set($time_zone);
        } else {
            date_default_timezone_set(timezone_name_from_abbr("", get_option('gmt_offset') * HOUR_IN_SECONDS, false));
        }

        $next_scheduled_time = self::get_next_scheduled_time($call_courier_days, intval($hour_parts[0]), intval($hour_parts[1]));
        if ($next_scheduled_time < 1) {
            return;
        }
        if (!wp_next_scheduled($hook_name)) {
            wp_schedule_single_event($next_scheduled_time, $hook_name);
        }
    }

    private function get_day_value($day): int
    {
        if (!$day) {
            return -1;
        }
        switch (trim($day)) {
            case "Sunday":
            case "Sekmadienis":
                return 0;
            case "Monday":
            case "Pirmadienis":
                return 1;
            case "Tuesday":
            case "Antradienis":
                return 2;
            case "Wednesday":
            case "Trečiadienis":
                return 3;
            case "Thursday":
            case "Ketvirtadienis":
                return 4;
            case "Friday":
            case "Penktadienis":
                return 5;
            case "Saturday":
            case "Šeštadienis":
                return 6;
        }
        return -1;
    }

    private function find_day($courier_days, $allow_same_day)
    {
        $current_day = date('w');
        $earliest_day_value = 7;
        $smallest_days_diff = 7;
        $next_day = null;
        foreach ($courier_days as $call_courier_day) {
            $day_value = $this->get_day_value($call_courier_day);
            if ($current_day == $day_value) {
                if ($allow_same_day)
                    return $call_courier_day;
            } else if ($current_day < $day_value) {
                $daysDiff = $day_value - $current_day;
                if ($daysDiff < $smallest_days_diff) {
                    $smallest_days_diff = $daysDiff;
                    $next_day = $call_courier_day;
                }
            }
            if ($earliest_day_value > $day_value) {
                $earliest_day_value = $day_value;
            }
        }
        if ($next_day == null) {
            return $this->get_day_from_value($earliest_day_value);
        }
        return $next_day;
    }

    public function get_next_scheduled_time(string $courier_days, int $call_courier_hour, int $call_courier_minute)
    {
        $days_arr = explode(",", $courier_days);
        if (empty($days_arr)) return null;
        $current_hour = date('G');
        $current_minute = date('i');
        $current_day = date('w');
        $allow_same_day = $current_hour < $call_courier_hour || ($current_hour == $call_courier_hour && $current_minute < $call_courier_minute);
        $next_day = $this->find_day($days_arr, $allow_same_day);
        $next_day_value = $this->get_day_value($next_day);
        if ($next_day_value < $current_day || ($next_day_value == $current_day && !$allow_same_day)) {
            $next_day_value = $next_day_value + 7;
        }

        $hours_diff = $call_courier_hour - $current_hour;
        $days_diff = $next_day_value - $current_day;
        $minutes_diff = $call_courier_minute - $current_minute;
        $seconds_diff = (($days_diff * 24 + $hours_diff) * 60 + $minutes_diff) * 60;
        return time() + $seconds_diff;
    }

    private function get_day_from_value(int $value)
    {
        switch ($value) {
            case 0:
                return 'Sunday';
            case 1:
                return 'Monday';
            case 2:
                return 'Tuesday';
            case 3:
                return 'Wednesday';
            case 4:
                return 'Thursday';
            case 5:
                return 'Friday';
            case 6:
                return 'Saturday';
        }
    }

}

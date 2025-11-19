<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'admin/api/class-woo-lithuaniapost-admin-terminal-api.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Terminal_Service
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
    private $terminal_api;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->terminal_api = new Woo_Lithuaniapost_Admin_Terminal_Api($plugin_name, $version);
    }


    public function update_terminal_list()
    {
        $unisend_terminals_saved = $this->save_unisend_terminal_list();
        return $unisend_terminals_saved;
    }

    public function save_unisend_terminal_list()
    {
        global $wpdb;

        $ee_terminals = $this->terminal_api->get_terminals("EE");
        $lv_terminals = $this->terminal_api->get_terminals("LV");
        $lt_terminals = $this->terminal_api->get_terminals("LT");

        // Get LPEXPRESS terminal list
        if ($ee_terminals && $lv_terminals && $lt_terminals) {
            $terminals = array_merge($lv_terminals, $lt_terminals, $ee_terminals);

            // Truncate terminals table
            $wpdb->query("TRUNCATE TABLE {$wpdb->woo_lithuaniapost_unisend_terminals}");
            /** @var \stdClass $terminal */
            foreach ($terminals as $terminal) {
                $wpdb->insert(
                    $wpdb->woo_lithuaniapost_unisend_terminals, [
                        'terminal_id' => $terminal->id,
                        'name' => $terminal->name,
                        'address' => $terminal->address,
                        'city' => $terminal->city,
                        'country_code' => $terminal->countryCode
                    ]
                );
            }
            return $terminals;
        }
        return false;
    }

    /**
     * Schedule update terminal list
     *
     * @since 1.0.0
     */
    public function schedule_update_terminal_list()
    {
        wp_clear_scheduled_hook('woo_lithuaniapost_api_update_terminals');
        wp_clear_scheduled_hook('woo_lithuaniapost_api_update_terminals_daily');
        if (!wp_next_scheduled('woo_lithuaniapost_terminal_service_update_terminals')) {
            wp_schedule_event(time(), 'daily', 'woo_lithuaniapost_terminal_service_update_terminals');
        }
    }

    public function get_terminals_by_country_code(string $shipping_country_code): array
    {
        global $wpdb;

        $formatted_list = [];

        // Terminal cities at top
        $top_list = [
            'Vilnius',
            'Kaunas',
            'Klaipėda',
            'Šiauliai',
            'Panevežys',
            'Alytus',
            'Marijampolė',
            'Utena',
            'Telšiai',
            'Tauragė'
        ];

        $terminals = $wpdb->get_results($wpdb->prepare("SELECT terminal_id,name,address,city FROM {$wpdb->woo_lithuaniapost_unisend_terminals} WHERE country_code = %s", $shipping_country_code)) ?? [];

        // Get terminals from DB
        if (!$terminals) {
            $terminals = $wpdb->get_results(sprintf('SELECT * FROM %s',
                    $wpdb->woo_lithuaniapost_lpexpress_terminals)
            ) ?? [];
        }

        foreach ($terminals as $terminal) {
            // Add city groups
            if (!array_key_exists($terminal->city, $formatted_list)) {
                $formatted_list [$terminal->city] = [];
            }

            // Formatted grouped list by city
            $formatted_list [$terminal->city][$terminal->terminal_id]
                = trim(sprintf('%s - %s', $terminal->name, $terminal->address));
        }

        // Sort terminals alphabetically
        foreach ($formatted_list as $key => $list) {
            asort($formatted_list [$key], SORT_ASC);
        }

        // Top sort cities
        $ordered = [];

        foreach ($top_list as $key) {
            if (array_key_exists($key, $formatted_list)) {
                $ordered [$key] = $formatted_list [$key];
                // Unset top listed cities
                unset ($formatted_list [$key]);
            }
        }

        // Sort cities alphabetically
        ksort($formatted_list);

        // Concat
        $formatted_list = $ordered + $formatted_list;

        return $formatted_list;
    }

    public function get_terminal_by_id(string $terminal_id)
    {
        global $wpdb;

        $terminal = $wpdb->get_results($wpdb->prepare("SELECT terminal_id,name,address,city FROM {$wpdb->woo_lithuaniapost_lpexpress_terminals} WHERE terminal_id = %s", $terminal_id)) ?? null;
        if (!$terminal) {
            $terminal = $wpdb->get_results($wpdb->prepare("SELECT terminal_id,name,address,city FROM {$wpdb->woo_lithuaniapost_unisend_terminals} WHERE terminal_id = %s", $terminal_id)) ?? null;
        }
        return $terminal;
    }
}

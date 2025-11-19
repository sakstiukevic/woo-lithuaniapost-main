<?php

/**
 * Fired during plugin activation
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Activator
{
	/**
     * Create database tables
	 * @since    1.0.0
	 */
	public static function activate ()
    {
        global $wpdb;
        require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );

        /**
         * Create table wp_woo_lithuaniapost_api_token
         */
        $charset_collate = $wpdb->get_charset_collate ();

        //Check to see if the table exists already, if not, then create it
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_api_token'" )
            != $wpdb->woo_lithuaniapost_api_token )
        {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_api_token (
                id int(11) NOT NULL auto_increment,
                access_token varchar(255) NOT NULL,
                refresh_token varchar(255) NOT NULL,
                expires varchar(255) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table wo_lithuaniapost_lpexpress_terminals
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_lpexpress_terminals'" )
            != $wpdb->woo_lithuaniapost_lpexpress_terminals )
        {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_lpexpress_terminals (
                id int(11) NOT NULL auto_increment,
                terminal_id varchar(255) NOT NULL,
                name varchar(255) NOT NULL,
                address varchar(255) NOT NULL,
                city varchar(255) NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table wo_lithuaniapost_lpexpress_terminals
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_unisend_terminals'" )
            != $wpdb->woo_lithuaniapost_unisend_terminals )
        {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_unisend_terminals (
                id int(11) NOT NULL auto_increment,
                terminal_id varchar(255) NOT NULL,
                name varchar(255) NOT NULL,
                address varchar(255) NOT NULL,
                city varchar(255) NOT NULL,
                country_code varchar(10) NOT NULL,
                KEY country_code (country_code),
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table woo_lithuaniapost_table_rates
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_table_rates'" ) !=
            $wpdb->woo_lithuaniapost_table_rates ) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_table_rates (
                id int(11) NOT NULL auto_increment,
                method_id TEXT NOT NULL,
                weight_to int(12) NOT NULL,
                price double NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table woo_lithuaniapost_country_rates
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_country_rates'" ) !=
            $wpdb->woo_lithuaniapost_country_rates ) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_country_rates (
                id int(11) NOT NULL auto_increment,
                method_id TEXT NOT NULL,
                country varchar(255) NOT NULL,
                price double NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table woo_lithuaniapost_shipping_request
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_shipping_request'" ) !=
            $wpdb->woo_lithuaniapost_shipping_request ) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_shipping_request (
                request_id varchar(100) NOT NULL,
                status varchar(100) NOT NULL,
                created datetime NOT NULL,
                updated datetime NULL,
                PRIMARY KEY (request_id),
                KEY status (status)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table woo_lithuaniapost_tracking_status
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_tracking_status'" ) !=
            $wpdb->woo_lithuaniapost_tracking_status ) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_tracking_status (
                order_id int NOT NULL,
                barcode varchar(100) NOT NULL,
                status varchar(100) NULL,
                created datetime NOT NULL,
                updated datetime NULL,
                PRIMARY KEY (order_id),
                KEY status (status),
                UNIQUE KEY barcode (barcode)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        wp_clear_scheduled_hook ( 'woo_lithuaniapost_admin_remove_notice' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_refresh_token' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_refresh_token_new' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_refresh_token_new_new' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_save_tracking_events' );
    }

    /**
     * Update plugin database
     * @since 3.1.3
     */
    public static function update()
    {
        global $wpdb;

        $updated_version = Woo_Lithuaniapost_Admin_Settings::get_option('woo_lithuaniapost_updated_version');
        if ($updated_version == WOO_LITHUANIAPOST_VERSION) {
            return false;
        }

        require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );

        /**
         * Create table woo_lithuaniapost_country_weight_rates
         */
        $charset_collate = $wpdb->get_charset_collate ();

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_country_weight_rates'" ) !=
            $wpdb->woo_lithuaniapost_country_weight_rates ) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_country_weight_rates (
                id int(11) NOT NULL auto_increment,
                method_id TEXT NOT NULL,
                country varchar(255) NOT NULL,
                weight double NOT NULL,
                price double NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta( $sql );
        }

        /**
         * Create table woo_lithuaniapost_unisend_terminals
         */
        if ($wpdb->get_var("SHOW TABLES LIKE '$wpdb->woo_lithuaniapost_unisend_terminals'")
            != $wpdb->woo_lithuaniapost_unisend_terminals) {
            $sql = "CREATE TABLE $wpdb->woo_lithuaniapost_unisend_terminals (
                id int(11) NOT NULL auto_increment,
                terminal_id varchar(255) NOT NULL,
                name varchar(255) NOT NULL,
                address varchar(255) NOT NULL,
                city varchar(255) NOT NULL,
                country_code varchar(10) NOT NULL,
                KEY country_code (country_code),
                UNIQUE KEY id (id)
            ) $charset_collate;";

            dbDelta($sql);
        }
        Woo_Lithuaniapost_Admin_Settings::update_option('woo_lithuaniapost_updated_version', WOO_LITHUANIAPOST_VERSION);
        return true;
    }
}

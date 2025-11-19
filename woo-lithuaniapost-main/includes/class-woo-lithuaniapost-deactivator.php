<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/includes
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_oauth_refresh_token' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_update_terminals' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_api_update_terminals_daily' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_terminal_service_update_terminals' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_update_tracking_status' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_call_courier_schedule_hook' );
        wp_clear_scheduled_hook ( 'woo_lithuaniapost_sync_tracking_data' );
	}

}

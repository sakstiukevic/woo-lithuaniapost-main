<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://post.lt
 * @since             1.0.0
 * @package           Woo_Lithuaniapost
 *
 * @wordpress-plugin
 * Plugin Name:       UNISEND for WooCommerce
 * Plugin URI:        https://post.lt
 * Description:       UNISEND shipping for WooCommerce.
 * Version:           4.0.29.1
 * Author:            UNISEND
 * Author URI:        https://post.lt
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-lithuaniapost
 * Domain Path:       /languages
 */

/**
 * Plugin requires at least PHP 8
 */
if ( phpversion () < 7 ) {
    add_action('admin_notices', function () {
        echo '<div class="error is-dismissible">
             <p>' . __( 'UNISEND plugin requires PHP version 7+', 'woo-lithuaniapost' ) . '</p>
         </div>';
    });

    return;
}

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'WOO_LITHUANIAPOST_VERSION' ) ) {
    define( 'WOO_LITHUANIAPOST_VERSION', '4.0.29' );
}

if ( ! defined( 'WOO_LITHUANIAPOST_PLUGIN_FILE' ) ) {
    define( 'WOO_LITHUANIAPOST_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'WOO_LITHUANIAPOST_PLUGIN_DIR' ) ) {
    define( 'WOO_LITHUANIAPOST_PLUGIN_DIR', plugin_dir_path(__FILE__) );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-lithuaniapost-activator.php
 */
function activate_woo_lithuaniapost ()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-lithuaniapost-activator.php';
	Woo_Lithuaniapost_Activator::activate ();
    do_action('woo_lithuaniapost_api_eshop_activation');
}

/**
 * The code that runs during admin_init updates database.
 * This action is documented in includes/class-woo-lithuaniapost-activator.php
 */
function update_woo_lithuaniapost ()
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-lithuaniapost-activator.php';
    if (Woo_Lithuaniapost_Activator::update()) {
        do_action('woo_lithuaniapost_api_eshop_update');
    }
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-lithuaniapost-deactivator.php
 */
function deactivate_woo_lithuaniapost ()
{
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woo-lithuaniapost-deactivator.php';
	Woo_Lithuaniapost_Deactivator::deactivate ();
    do_action('woo_lithuaniapost_api_eshop_deactivation');
}
function uninstall_woo_lithuaniapost ()
{
    do_action('woo_lithuaniapost_api_eshop_uninstall');
}

register_activation_hook ( __FILE__, 'activate_woo_lithuaniapost' );
add_action( 'admin_init', 'update_woo_lithuaniapost' );
register_deactivation_hook ( __FILE__, 'deactivate_woo_lithuaniapost' );
register_uninstall_hook ( __FILE__, 'uninstall_woo_lithuaniapost' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path ( __FILE__ ) . 'includes/class-woo-lithuaniapost.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_lithuaniapost ()
{
	$plugin = new Woo_Lithuaniapost ();
	$plugin->run ();
}
run_woo_lithuaniapost ();

<?php

defined('ABSPATH') || exit;

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_OAuth_Api
{
    /**
     * Authentication gateways
     */
    const AUTH_TEST_GATEWAY = 'https://api-manosiuntostst.post.lt/oauth/token';
    const AUTH_DEFAULT_GATEWAY = 'https://api-manosiuntos.post.lt/oauth/token';


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
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Get AUTH gateway depending on test mode
     *
     * @return string
     * @since 1.0.0
     */
    protected function get_auth_gateway()
    {
        return self::AUTH_DEFAULT_GATEWAY;
    }

    /**
     * Get access token
     *
     * @return string|null
     * @since 1.0.0
     */
    public function get_access_token_value()
    {
        global $wpdb;

        $token = $wpdb->get_var("SELECT access_token FROM {$wpdb->woo_lithuaniapost_api_token} WHERE id = 1");
        if ($token != null) {
            return $token;//TODO check for expiration
        }
        return $this->save_access_token();
    }


    /**
     * Get API token
     *
     * @param array $params
     * @return string|bool
     * @since 1.0.0
     */
    protected function token_request($params)
    {
        // Build query for token request
        $args = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $params
        ];

        $response = wp_remote_post($this->get_auth_gateway(), $args);

        // Access token granted
        if (wp_remote_retrieve_response_code($response) === 200) {
            return json_decode(wp_remote_retrieve_body($response));
        }

        // Error occurred
        $error = json_decode(wp_remote_retrieve_body($response));
        if ($error->error == 'invalid_scope') {
            WC_Admin_Settings::add_error(__('In order to use this UNISEND API you need to sign a contract. For more details visit https://www.post.lt/lt/api-verslui', 'woo-lithuaniapost'));
        } else {
            WC_Admin_Settings::add_error($error->error_description ??
                __('Something wen\'t wrong please try again later.', 'woo-lithuaniapost'));
        }

        return false;
    }

    /**
     * Refresh token for cron
     *
     * @since 1.0.0
     */
    public function refresh_token()
    {
        global $wpdb;

        $access_token = $wpdb->get_row("SELECT * FROM {$wpdb->woo_lithuaniapost_api_token} WHERE id = 1");

        // Set timezone
        date_default_timezone_set('Europe/Vilnius');

        // Send refresh token request
        $args = [
            'headers' => [
                'Accept' => 'application/json'
            ],
            'body' => http_build_query([
                'grant_type' => 'refresh_token',
                'clientSystem' => 'PUBLIC',
                'refresh_token' => $access_token->refresh_token
            ])
        ];

        $response = wp_remote_post($this->get_auth_gateway(), $args);

        // Access token granted
        if (wp_remote_retrieve_response_code($response) === 200) {
            $token_response = json_decode(wp_remote_retrieve_body($response));
            // Truncate API token table
            $wpdb->query("TRUNCATE TABLE {$wpdb->woo_lithuaniapost_api_token}");

            // Save API token
            $wpdb->insert($wpdb->prefix . 'woo_lithuaniapost_api_token', [
                    'access_token' => $token_response->access_token,
                    'refresh_token' => $token_response->refresh_token,
                    'expires' => date('Y-m-d H:i:s', time() + $token_response->expires_in)
                ]
            );
            return $token_response->access_token;
        }
        $isRefreshTokenExpired = wp_remote_retrieve_response_code($response) === 400;
        if ($isRefreshTokenExpired) {
            return $this->save_access_token();
        }
        return null;
    }

    /**
     * Call API and save API token
     *
     * @return bool
     * @since 1.0.0
     */
    public function save_access_token()
    {
        global $wpdb;

        // Set timezone
        date_default_timezone_set('Europe/Vilnius');

        // Request for API token
        $response = $this->token_request([
            'grant_type' => 'password',
            'clientSystem' => 'public',
            'username' => Woo_Lithuaniapost_Admin_Settings::get_option('api_username'),
            'password' => Woo_Lithuaniapost_Admin_Settings::get_option('api_password'),
            'scope' => 'read+write+API_CLIENT'
        ]);

        /** @var \stdClass $response */
        if ($response) {
            // Truncate API token table
            $wpdb->query(sprintf('TRUNCATE TABLE %s',
                $wpdb->woo_lithuaniapost_api_token));

            // Save API token
            $wpdb->insert($wpdb->prefix . 'woo_lithuaniapost_api_token', [
                    'access_token' => $response->access_token,
                    'refresh_token' => $response->refresh_token,
                    'expires' => date('Y-m-d H:i:s', time() + $response->expires_in)
                ]
            );
            do_action('woo_lithuaniapost_access_token_saved');
            return $response->access_token;
        } else {
            // Truncate API token table credentials invalid
            $wpdb->query("TRUNCATE TABLE {$wpdb->woo_lithuaniapost_api_token}");
            update_option('woo_lithuaniapost_module_active', false);
        }

        return null;
    }
}

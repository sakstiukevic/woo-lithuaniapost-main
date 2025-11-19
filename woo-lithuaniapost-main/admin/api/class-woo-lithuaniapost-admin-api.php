<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-oauth-api.php';
require_once plugin_dir_path(dirname(__FILE__)) . '../admin/class-woo-lithuaniapost-admin-error-handler.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Api
{
    const DEFAULT_ACCEPT = 'application/json';
    const DEFAULT_TIMEOUT = 30;

    /**
     * API gateways
     */
    const TEST_GATEWAY = 'https://api-manosiuntostst.post.lt/api/v2';
    const DEFAULT_GATEWAY = 'https://api-manosiuntos.post.lt/api/v2';

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

    private $oauth_api;
    private $error_handler;

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
        $this->oauth_api = new Woo_Lithuaniapost_Admin_OAuth_Api($plugin_name, $version);
        $this->error_handler = new Woo_Lithuaniapost_Admin_Error_Handler($plugin_name, $version);
    }

    /**
     * Get API gateway depending on test mode
     *
     * @return string
     * @since 1.0.0
     */
    private function get_api_gateway()
    {
        return self::DEFAULT_GATEWAY;
    }

    public function post($endpoint, $body = [], callable $error_callback = null)
    {
        return self::do_request($endpoint, null, $body, 'POST', true, self::DEFAULT_ACCEPT, $error_callback);
    }

    public function get($endpoint, $params = [], string $accept = self::DEFAULT_ACCEPT, int $timeout = self::DEFAULT_TIMEOUT)
    {
        return self::do_request($endpoint, $params, null, 'GET', true, $accept, null, $timeout);
    }

    public function put($endpoint, $body = [], callable $error_callback = null)
    {
        return self::do_request($endpoint, null, $body, 'PUT', true, self::DEFAULT_ACCEPT, $error_callback);
    }

    public function delete($endpoint, $params = [])
    {
        return self::do_request($endpoint, $params, null, 'DELETE', true);
    }

    public function disable_error_handling(): bool
    {
        return false;
    }

    public function authorization_required(): bool
    {
        return true;
    }

    /**
     * Do request to api gateway
     *
     * @param string $endpoint
     * @param array $params
     * @param string $method
     * @return mixed
     * @since 1.0.0
     */
    private function do_request($endpoint, $params = [], $body = [], $method = 'GET', $retry_on_unauthorized_request = false, $accept = self::DEFAULT_ACCEPT, callable $error_callback = null, $timeout = self::DEFAULT_TIMEOUT)
    {
        if (!get_option('woo_lithuaniapost_module_active')) {
            return false;
        }
        $headers = [
            'Content-type' => 'application/json',
            'Accept-Language' => 'en-US',
            'Accept' => $accept
        ];
        // Authorization arguments
        if ($this->authorization_required()) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->oauth_api->get_access_token_value());
        }
        $args = [
            'headers' => $headers,
            'method' => $method,
            'body' => $body ? json_encode($body) : $body,
            'timeout' => $timeout,
        ];
        $uri_param = null;
        if ($params && count($params) > 0) {
            $uri_param = http_build_query($params, '', '&');
        }
        // Send request to API
        $response = wp_remote_request(sprintf('%s/%s', $this->get_api_gateway(), $endpoint . ($uri_param ? ("?" . $uri_param) : null)),
            $args);

        // Error Occurred       
        $is_access_token_expired = wp_remote_retrieve_response_code($response) === 401;
        if ($is_access_token_expired && $this->authorization_required()) {
            if ($retry_on_unauthorized_request) {
                $this->oauth_api->refresh_token();
                return $this->do_request($endpoint, $params, $body, $method, false, $accept, $error_callback, $timeout);
            }
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            self::on_failed_request($response, $error_callback);
            return false;
        }
        if ($accept === self::DEFAULT_ACCEPT) {
            $response_body = json_decode(wp_remote_retrieve_body($response));
            $this->on_response($response_body);
        } else {
            $response_body = wp_remote_retrieve_body($response);
        }
        return $response_body !== null ? $response_body : true;
    }

    private function on_failed_request($response, $error_callback)
    {
        $response = wp_remote_retrieve_body($response);
        $responseObject = json_decode($response);
        if ($error_callback) {
            $error_callback($responseObject);
        }
        if (!$this->disable_error_handling()) {
            $this->error_handler->handle_error($responseObject);
        }
    }

    private function on_response($response_body)
    {
        if (is_array($response_body)) {
            foreach ($response_body as $value) {
                if (isset($value->error)) {
                    $warning = @$value->error_description ? $value->error_description : $value->error;
                    WC_Admin_Settings::add_message($warning);
                }
            }
        }
    }

}
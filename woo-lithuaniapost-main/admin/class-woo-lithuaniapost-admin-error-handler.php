<?php

class Woo_Lithuaniapost_Admin_Error_Handler
{

    const ERROR_KEY_ORDER_EDIT = "ERROR_KEY_ORDER_EDIT";
    const ERROR_KEY_UNISEND_ORDER_EDIT = "ERROR_KEY_UNISEND_ORDER_EDIT";
    const ERROR_KEY_ORDER_BULK_EDIT = "ERROR_KEY_ORDER_BULK_EDIT";
    const ERROR_KEY_LP_SETTINGS = "ERROR_KEY_LP_SETTINGS";

    private $handlers;

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
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_ORDER_EDIT, [new Error_Condition_Value('page', null), new Error_Condition_Value('post', true), new Error_Condition_Value('action', 'edit')], true);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_ORDER_EDIT, [new Error_Condition_Value('action', 'editpost'), new Error_Condition_Value('post_type', 'shop_order')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_ORDER_EDIT, [new Error_Condition_Value('page', 'wc-orders')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_ORDER_EDIT, [new Error_Condition_Value('action', 'woo_lithuaniapost_handle_parcel_save')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_UNISEND_ORDER_EDIT, [new Error_Condition_Value('action', 'woo-ltpost-create-label')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_UNISEND_ORDER_EDIT, [new Error_Condition_Value('action', 'woo-ltpost-create-parcel')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_UNISEND_ORDER_EDIT, [new Error_Condition_Value('page', "lp-orders")], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_ORDER_BULK_EDIT, [new Error_Condition_Value('page', null), new Error_Condition_Value('post_type', 'shop_order')], false);
        $this->handlers[] = new Error_Handler(self::ERROR_KEY_LP_SETTINGS, [new Error_Condition_Value('page', 'wc-settings'), new Error_Condition_Value('tab', 'shipping'), new Error_Condition_Value('section', 'lpsettings')], false);
    }

    public function handle_error($error_response): bool
    {
        foreach ($this->handlers as $handler) {
            $handle = $handler->can_handle();
            if ($handle === true) {
                $error_message = $this->to_error_message($error_response);
                if ($error_message) {
                    Woo_Lithuaniapost_Admin_Settings::add_option($handler->error_key, $error_message);
                    if ($handler->do_call_admin_notice()) {
                        do_action('admin_notices');
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function display_error()
    {
        foreach ($this->handlers as $handler) {
            if ($handler->can_handle()) {
                $page_error = Woo_Lithuaniapost_Admin_Settings::get_option($handler->error_key);
                if ($page_error) {
                    printf('<div class="notice notice-error is-dismissible"><p>%s: %s</p></div>', __("UNISEND Error", "woo-lithuaniapost"), esc_html($page_error));
                    Woo_Lithuaniapost_Admin_Settings::delete_option($handler->error_key);
                }
                break;
            }
        }
    }

    private function to_error_message($error_response)
    {
        if (is_array($error_response)) {
            $error_messages = [];
            foreach ($error_response as $error_key => $error_value) {
                $err_msg = $this->get_error_message($error_value) ?: $this->get_error_key_message($error_key, $error_value);
                if ($err_msg) {
                    $error_messages[] = $err_msg;
                }
            }
            if (empty($error_messages)) return null;
            return implode(',', $error_messages);
        } else {
            return $this->get_error_message($error_response);
        }
    }

    private function get_error_key_message($error_key, $error)
    {
        if ($error_key && is_array($error)) {
            return sprintf("%s: [%s]", __($error_key, "woo-lithuaniapost"), implode(",", $error));
        }
        return null;
    }

    private function get_error_message($error)
    {
        if (isset($error->errors)) return $this->to_error_message($error->errors);
        if (isset($error->error)) {
            if (isset($error->error_description)) {
                if (isset($error->field)) {
                    return $error->field . " " . $error->error_description;
                }
                return $error->error_description;
            }
            return $error->error;
        }
        return null;
    }
}

class Error_Handler
{
    public $error_key;
    private $conditions = [];
    private $call_notice_admin;

    /**
     * @param callable $condition
     * @param bool $call_notice_admin
     */
    public function __construct(string $error_key, array $conditions, bool $call_notice_admin)
    {
        $this->error_key = $error_key;
        $this->conditions = $conditions;
        $this->call_notice_admin = $call_notice_admin;
    }

    public function can_handle(): bool
    {
        foreach ($this->conditions as $condition) {
            $value = $_REQUEST[$condition->key] ?? null;
            if ($value != $condition->value) {
                return false;
            }
        }
        return true;
    }

    public function do_call_admin_notice(): bool
    {
        return $this->call_notice_admin;
    }

}

class Error_Condition_Value
{
    public $key;
    public $value;

    /**
     * @param string $key
     * @param $value
     */
    public function __construct(string $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

}
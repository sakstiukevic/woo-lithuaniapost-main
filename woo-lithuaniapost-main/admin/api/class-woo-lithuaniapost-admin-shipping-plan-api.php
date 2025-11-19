<?php

defined('ABSPATH') || exit;

require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-woo-lithuaniapost-admin-api.php';

/**
 * The admin-specific api hooks functionality of the plugin.
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin
 * @author     AB "Lietuvos Paštas" <info@post.lt>
 */
class Woo_Lithuaniapost_Admin_Shipping_Plan_Api extends Woo_Lithuaniapost_Admin_Api
{
    public function __construct()
    {
        parent::__construct('', '');
    }

    /**
     * Authentication gateways
     */
    const PLAN_URI = 'shipping/plan';


    public function get_plans()
    {
        return $this->get(self::PLAN_URI);
    }

}

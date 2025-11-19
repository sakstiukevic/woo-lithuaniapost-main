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
class Woo_Lithuaniapost_Admin_Courier_Api extends Woo_Lithuaniapost_Admin_Api
{
    /**
     * Authentication gateways
     */
    const CALL_REQUIRED_URI = 'courier/call/required';
    const PENDING_CALL_URI = 'courier/pending/call';
    const CALL_URI = 'courier/call';
    const GET_MANIFEST_URI = 'courier/manifest/list';
    const GET_MANIFEST_PDF_URI = 'courier/manifest/pdf';

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
        parent::__construct($plugin_name, $version);
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function is_call_required(): bool
    {
        $response = $this->get(self::CALL_REQUIRED_URI);
        if ($response != null) return $response === true;
        return false;
    }

    public function pending_call(): array
    {
        $offset = Woo_Lithuaniapost_Admin_Settings::get_option('courier_call_pending_offset') ?: 1;

        $response = $this->post(self::PENDING_CALL_URI . '?createdBefore=' . $offset);
        if ($response != null) {
            return $response;
        }
        return [];
    }

    public function get_manifests(array $order_ids)
    {
        $param['idRefs'] = implode(',', $order_ids);
        return $this->get(self::GET_MANIFEST_URI, $param);
    }

    public function download_manifests(array $order_ids): bool
    {
        $param['idRefs'] = implode(',', $order_ids);
        $response_body = $this->get(self::GET_MANIFEST_PDF_URI, $param, 'application/pdf');
        if (!$response_body) {
            return false;
        }
        $filename = sprintf('lp_manifests_%s.pdf',
            date('Y-m-d H:i:s')
        );
        header('Content-type: application/pdf');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
        echo $response_body;
        return true;
    }

    public function call(array $order_ids)
    {
        $body['idRefs'] = $order_ids;
        return $this->post(self::CALL_URI, $body);
    }

    public function get_manifest(int $order_id)
    {
        return $this->get_manifests([$order_id]);
    }
}

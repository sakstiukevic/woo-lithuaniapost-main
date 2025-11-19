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
class Woo_Lithuaniapost_Admin_Sticker_Api extends Woo_Lithuaniapost_Admin_Api
{
    const ORIENTATION_PORTRAIT = "PORTRAIT";
    const ORIENTATION_LANDSCAPE = "LANDSCAPE";
    const LAYOUT_MAX = "LAYOUT_MAX";
    const LAYOUT_A4 = "LAYOUT_A4";
    const LAYOUT_10X15 = "LAYOUT_10x15";

    /**
     * Authentication gateways
     */
    const STICKER_LIST_URI = 'sticker/list';
    const STICKER_PDF_URI = 'sticker/pdf';

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

    public function get_stickers(array $order_ids)
    {
        return $this->get(self::STICKER_LIST_URI, $this->get_params($order_ids));
    }

    public function download_stickers_pdf(array $order_ids): bool
    {
        $stickers_response = $this->get(self::STICKER_PDF_URI, $this->get_params($order_ids), 'application/pdf' . ',' . self::DEFAULT_ACCEPT . ';q=0.9');
        if ($stickers_response) {
            $filename = sprintf('lp_labels_%s.pdf',
                date('Y-m-d H:i:s')
            );
            header('Content-type: application/pdf');
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
            echo $stickers_response;
            return true;
        }
        return false;
    }

    private function get_params(array $order_ids): array
    {
        $params["idRefs"] = implode(',', $order_ids);

        $layout = Woo_Lithuaniapost_Admin_Settings::get_option('other_label_format');
        if (!$layout) {
            $layout = self::LAYOUT_10X15;
        }
        $params["layout"] = $layout;
        $orientation = Woo_Lithuaniapost_Admin_Settings::get_option('other_label_orientation');
        if ($orientation) {
            $params["labelOrientation"] = $orientation;
        }
        return $params;
    }
}

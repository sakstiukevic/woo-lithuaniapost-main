<?php
/**
 * Provide a admin area meta box view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/admin/partials
 */

/**
 * @var Woo_Lithuaniapost_Admin_Order_Meta_Box $this
 */
?>
<div class="panel">
    <?php if ( $tracking_code = $this->get_tracking_code () ): ?>
        <?php add_thickbox(); ?>
        <div id="woo-lp-tracking" style="display:none;">
            <?php include_once plugin_dir_path ( __FILE__ ) . 'includes/html-tracking-info.php'; ?>
        </div>
        <strong><?php _e( 'Tracking Number:', 'woo-lithuaniapost' ); ?></strong>
        <span>
            <a href="#TB_inline?&width=600&height=550&inlineId=woo-lp-tracking"
               title="<?php _e( 'UNISEND Tracking', 'woo-lithuaniapost' ); ?>" class="thickbox">
                <?php echo $tracking_code; ?>
            </a>
        </span>
    <?php else: ?>
        <?php include_once plugin_dir_path ( __FILE__ ) . 'includes/html-shipment-editor-side.php'; ?>
    <?php endif; ?>
</div>

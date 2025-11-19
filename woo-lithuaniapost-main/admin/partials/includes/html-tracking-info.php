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
 * @subpackage Woo_Lithuaniapost/admin/partials/includes
 */

/**
 * @var Woo_Lithuaniapost_Admin_Order_Meta_Box $this
 */

$tracking_events = apply_filters ( 'woo_lithuaniapost_get_tracking_events', $this->get_order_id() );
if (!$tracking_events) {
    return;
}
$last_event = end($tracking_events);
?>
<div class="page tracking">
    <div class="table-wrapper">
        <h2><?php _e( 'Tracking Information', 'woo-lithuaniapost' ); ?></h2>
        <table class="data table order tracking" id="tracking-table-popup-CH800058683LT">
            <tbody>
            <tr>
                <th class="col label" scope="row"><?php _e( 'Tracking Number:', 'woo-lithuaniapost' ); ?></th>
                <td class="col value"><?php echo $last_event->mailBarcode; ?></td>
            </tr>
            <tr>
                <th class="col label" scope="row"><?php _e( 'Status:', 'woo-lithuaniapost' ); ?></th>
                <td class="col value">
                    <?php $state = $last_event->publicStateText; ?>
                    <?php echo $state == null ? __( 'Information not yet updated', 'woo-lithuaniapost' ) : $state; ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php if ( $tracking_events ): ?>
        <div class="table-wrapper">
            <table class="data table order tracking" id="track-history-table">
                <thead>
                <tr>
                    <th class="col date" scope="col"><?php _e( 'Date', 'woo-lithuaniapost' ); ?></th>
                    <th class="col description" scope="col"><?php _e( 'Description', 'woo-lithuaniapost' ); ?></th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($tracking_events as $event ): ?>
                        <tr>
                            <td data-th="Date" class="col date"><?php echo $event->eventDate; ?></td>
                            <td data-th="Description" class="col description"><?php echo $event->publicEventText; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

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
wp_nonce_field($this->plugin_name, 'woo-lithuaniapost-order-meta-box-nonce');
$available_actions = $this->get_available_actions();
?>
<div id="lpshipping-shipment-modal">
    <input type="hidden" name="order_id" value="<?php echo $this->get_order_id(); ?>" />
    <?php if ($this->is_action_available(LpOrderAction::CREATE_PARCEL, $available_actions) || $this->is_action_available(LpOrderAction::INIT_SHIPPING, $available_actions)): ?>
        <?php
        $parcel_create_error = $this->get_order()->get_meta('_woo_lithuaniapost_parcel_create_error');
        ?>
        <div class="admin__field">
            <?php if ($parcel_create_error): ?>
                <p class="notice notice-error"><?php _e('Parcel create failed', 'woo-lithuaniapost'); ?>:</p>
                <?php if (is_array($parcel_create_error)): ?>
                    <?php foreach ($parcel_create_error as $error): ?>
                        <input type="hidden" id="parcel_create_error" name="parcel_create_error"
                               value="<?php echo @$error->error; ?>"/>
                        <input type="hidden" id="parcel_create_error_description" name="parcel_create_error_description"
                               value="<?php echo @$error->error_description; ?>"/>
                        <input type="hidden" id="parcel_create_error_field" name="parcel_create_error_field"
                               value="<?php echo @$error->field; ?>"/>
                        <p><?php echo $this->get_error_message(@$error) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <input type="hidden" id="parcel_create_error" name="parcel_create_error"
                           value="<?php echo @$parcel_create_error->error; ?>"/>
                    <input type="hidden" id="parcel_create_error_description" name="parcel_create_error_description"
                           value="<?php echo @$parcel_create_error->error_description; ?>"/>
                    <input type="hidden" id="parcel_create_error_field" name="parcel_create_error_field"
                           value="<?php echo @$parcel_create_error->field ?? null; ?>"/>
                    <p><?php echo $this->get_error_message(@$parcel_create_error) ?></p>
                <?php endif; ?>
            <?php elseif ($this->is_action_available(LpOrderAction::CREATE_PARCEL, $available_actions)): ?>
                <p><?php _e('Parcel create pending', 'woo-lithuaniapost'); ?></p>
            <?php endif; ?>
            <?php if ($this->is_action_available(LpOrderAction::INIT_SHIPPING, $available_actions) || @$parcel_create_error): ?>
                <?php $parcel = $parcel_create_error ? $this->get_parcel_request() : $this->get_parcel() ?>
                <?php $estimated_plans = $this->estimate_parcel($parcel) ?>
                <?php $available_terminals = $this->get_available_terminals($parcel) ?>
                <?php if ($parcel): ?>
                    <div style="border-bottom: 1px solid #e4e4e4; margin-top: 10px; margin-bottom: 10px; padding-bottom: 10px">
                        <div class="admin__field">
                            <label for="lp_plan_code" class="admin__field-label">
                                <?php _e('Plan', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                            </label>
                            <div class="admin__field-control">
                                <select id="lp_plan_code"
                                        class="admin__control-select widefat disabled no-pointer"
                                        name="lp_plan_code">
                                    <?php echo $this->generate_select_parcel_plan_html($parcel, $estimated_plans) ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin__field">
                            <label for="parcel_type" class="admin__field-label">
                                <?php _e('Parcel Type', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                            </label>
                            <div class="admin__field-control">
                                <select id="parcel_type"
                                        class="admin__control-select widefat <?php echo $this->is_type_change_available($parcel) ? '' : 'disabled no-pointer' ?>"
                                        name="lp_parcel_type"
                                        id="lp_parcel_type">
                                    <?php echo $this->generate_select_parcel_type_html($parcel, $estimated_plans) ?>
                                </select>
                            </div>
                        </div>

                        <?php if (!empty($available_terminals)): ?>

                            <div class="admin__field">
                                <label for="woo_lithuaniapost_lpexpress_terminal_id" class="admin__field-label">
                                    <?php _e('Parcel Locker', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                                </label>
                                <div class="admin__field-control">
                                    <select class="woo_lithuaniapost_lpexpress_terminal_id"
                                            id="woo_lithuaniapost_lpexpress_terminal_id"
                                            name="woo_lithuaniapost_lpexpress_terminal_id">
                                        <option><?php echo __('Select parcel locker', 'woo-lithuaniapost') ?></option>
                                        <?php foreach ($available_terminals as $city => $terminals): ?>
                                            <optgroup label="<?php echo $city ?>">
                                                <?php foreach ($terminals as $terminal_id => $terminal): ?>
                                                    <option
                                                        <?php if (!empty ($parcel->receiver->address->terminalId) && $parcel->receiver->address->terminalId == $terminal_id): ?>selected<?php endif; ?>
                                                        data-value="<?php echo $terminal_id ?>"
                                                        value="<?php echo $terminal_id ?>"><?php echo $terminal ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="parcel_size_field"
                             class="admin__field <?php echo $this->is_size_available($parcel) ? '' : 'hidden' ?>">
                            <label for="parcel_size" class="admin__field-label">
                                <?php _e('Parcel Size', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                            </label>
                            <div class="admin__field-control">
                                <select id="parcel_size"
                                        class="admin__control-select widefat"
                                        name="lp_parcel_size"
                                        id="lp_parcel_size">
                                    <?php echo $this->generate_select_parcel_size_html($parcel) ?>
                                </select>
                            </div>
                        </div>
                        <div id="parcel_weight_field"
                             class="admin__field <?php echo $this->is_weight_available($parcel) ? '' : 'hidden' ?>">
                            <label for="parcel_weight" class="admin__field-label">
                                <?php _e('Parcel Weight (g)', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                            </label>
                            <div class="admin__field-control">
                                <input id="parcel_weight" type="number" step="1"
                                       value="<?php echo $parcel->parcel->weight ?? null; ?>"
                                       min="1"
                                       class="admin__control-text widefat"
                                       name="lp_parcel_weight"
                                       id="lp_parcel_weight"/>
                            </div>
                        </div>
                        <div id="parcel_part_count_field"
                             class="admin__field <?php echo $this->is_multi_part_available($parcel) ? '' : 'hidden' ?>">
                            <label for="parcel_part_count" class="admin__field-label">
                                <?php _e('Parcel part count', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                            </label>
                            <div class="admin__field-control">
                                <input type="number" step="1"
                                       value="<?php echo $parcel->parcel->partCount ?? 1; ?>"
                                       min="1"
                                       class="admin__control-text widefat"
                                       name="lp_part_count" id="lp_part_count"/>
                            </div>
                        </div>
                    </div>
                    <div class="admin__field" style="padding-bottom: 10px;">
                        <?php if ($this->get_order()->get_payment_method() === 'cod'): ?>
                            <?php foreach ($parcel->services as $service): ?>
                                <?php if (strcasecmp($service->code, 'cod') === 0): ?>
                                    <?php $cod = number_format($service->value, 2); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <label style="margin-left: 10px" class="admin__field-label">COD (&euro;):
                                <input type="number" step="0.01"
                                       value="<?php echo $cod ?? $this->get_order()->get_total(); ?>"
                                       min="0.00" class="admin__control-text" name="cod" id="cod"/>
                            </label>
                        <?php endif; ?>
                    </div>
                    <button id="woo_lp_save_parcel_button"
                            class="button button-primary save"><?php $parcel_create_error ? _e('Regenerate', 'woo-lithuaniapost') : _e('Save Changes', 'woo-lithuaniapost'); ?></button>
                    <?php if ($this->is_cn_required($parcel, $estimated_plans)): ?>
                        <?php add_thickbox(); ?>
                        <div id="woo-lp-edit-cn" style="display:none;">
                            <?php $_SESSION['parcel_to_edit'] = $parcel; ?>
                            <?php include_once plugin_dir_path(__FILE__) . 'html-cn-editor.php'; ?>
                        </div>
                        <span>
                    <a href="#TB_inline?&width=800&height=550&inlineId=woo-lp-edit-cn"
                       title="<?php _e('Edit CN', 'woo-lithuaniapost'); ?>"
                       class="thickbox button button-primary save">
                        <?php _e('Edit CN', 'woo-lithuaniapost'); ?>
                    </a>
                </span>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>


<script>

    const terminalMatcher = (params, data) => {
        const originalMatcher = $.fn.select2.defaults.defaults.matcher;
        const result = originalMatcher(params, data);
        if (
            result &&
            data.children &&
            result.children &&
            data.children.length
        ) {
            if (
                data.children.length !== result.children.length &&
                data.text.toLowerCase().includes(params.term.toLowerCase())
            ) {
                result.children = data.children;
            }
            return result;
        }
        return null;
    }

    var parcel_plan_select = jQuery('#lpshipping-shipment-modal #lp_plan_code');
    var parcel_type_select = jQuery('#lpshipping-shipment-modal #parcel_type');
    var parcel_size_field = jQuery('#lpshipping-shipment-modal #parcel_size_field');
    var parcel_weight_field = jQuery('#lpshipping-shipment-modal #parcel_weight_field');
    var parcel_part_count_field = jQuery('#lpshipping-shipment-modal #parcel_part_count_field');
    parcel_type_select.on('change', function () {
        const parcel_type = jQuery(this).val();
        if (parcel_type === 'H2T' || parcel_type === 'T2T' || parcel_type === 'T2H' || parcel_type === 'T2S') {
            parcel_plan_select.val("TERMINAL");
            parcel_weight_field.addClass('hidden');
            parcel_part_count_field.removeClass('hidden');
        } else {
            parcel_weight_field.removeClass('hidden');
            parcel_part_count_field.addClass('hidden');
        }
        if (parcel_type === 'H2H') {
            parcel_plan_select.val("HANDS");
            parcel_size_field.addClass('hidden');
            parcel_part_count_field.removeClass('hidden');
        } else {
            parcel_size_field.removeClass('hidden');
        }
    });
    $('#woo_lithuaniapost_lpexpress_terminal_id').select2({
        width: 'resolve',
        matcher(params, data) {
            return terminalMatcher(params, data);
        },
    });
</script>

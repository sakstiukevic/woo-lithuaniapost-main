<?php

defined('ABSPATH') || exit;

/**
 * HTML for UNISEND terminals
 *
 * @link       https://post.lt
 * @since      1.0.0
 *
 * @package    Woo_Lithuaniapost
 * @subpackage Woo_Lithuaniapost/public/partials
 */

/**
 * @var Woo_Lithuaniapost_Public $this
 */
?>
<br>
<label for="woo_lithuaniapost_lpexpress_terminal_id" class="">
<p class="woocommerce-input-wrapper woo_lithuaniapost_lpexpress_terminal_select_container">
    <select class="woo_lithuaniapost_lpexpress_terminal_id" name="woo_lithuaniapost_lpexpress_terminal_id" style="width: 100%;">
        <option><?php echo __('Select parcel locker', 'woo-lithuaniapost') ?></option>
        <?php foreach ($this->get_terminal_list() as $city => $terminals): ?>
            <optgroup label="<?php echo $city ?>">
                <?php foreach ($terminals as $terminal_id => $terminal): ?>
                    <option
                        <?php if (!empty ($this->get_selected_terminal_id()) && $this->get_selected_terminal_id() == $terminal_id): ?>selected<?php endif; ?>
                        data-value="<?php echo $terminal_id ?>" value="<?php echo $terminal_id ?>"><?php echo $terminal ?></option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
    </select>
</p>
</label>
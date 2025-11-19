<?php

?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="woocommerce_woo_lithuaniapost_lpexpress_terminal_table_rates">
            <?php echo __( 'Export Price vs Country and Weight', 'woo-lithuaniapost' ); ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                            <span>
                                <?php echo __( 'Export Price vs Country and Weight', 'woo-lithuaniapost' ); ?>
                            </span>
            </legend>
            <a href="<?php echo admin_url( 'admin-post.php' ); ?>?action=woo-ltpost-export-country-weight-rates&method=<?php echo $this->id; ?>"
               class="button button-primary"><?php echo __( 'Export', 'woo-lithuaniapost' ); ?></a>
        </fieldset>
    </td>
</tr>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo $this->id; ?>_tablerates">
            <?php echo __( 'Import Price vs Country and Weight', 'woo-lithuaniapost' ); ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                <span>
                    <?php echo __( 'Import Price vs Country and Weight', 'woo-lithuaniapost' ); ?>
                </span>
            </legend>
            <input class="input-text regular-input " type="file"
                   name="<?php echo $this->id; ?>_countryrates_weight"
                   id="<?php echo $this->id; ?>_countryrates_weight"
                   style="" value="" placeholder="">
        </fieldset>
    </td>
</tr>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="woocommerce_woo_lithuaniapost_lpexpress_terminal_table_rates">
            <?php echo __( 'Export Price vs Country', 'woo-lithuaniapost' ); ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                            <span>
                                <?php echo __( 'Export Price vs Country', 'woo-lithuaniapost' ); ?>
                            </span>
            </legend>
            <a href="<?php echo admin_url( 'admin-post.php' ); ?>?action=woo-ltpost-export-country-rates&method=<?php echo $this->id; ?>"
               class="button button-primary"><?php echo __( 'Export', 'woo-lithuaniapost' ); ?></a>
        </fieldset>
    </td>
</tr>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo $this->id; ?>_tablerates">
            <?php echo __( 'Import Price vs Country', 'woo-lithuaniapost' ); ?>
        </label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                <span>
                    <?php echo __( 'Import Price vs Country', 'woo-lithuaniapost' ); ?>
                </span>
            </legend>
            <input class="input-text regular-input " type="file"
                   name="<?php echo $this->id; ?>_countryrates"
                   id="<?php echo $this->id; ?>_countryrates"
                   style="" value="" placeholder="">
        </fieldset>
    </td>
</tr>
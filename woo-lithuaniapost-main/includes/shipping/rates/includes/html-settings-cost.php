<?php

class Woo_Lithuaniapost_Settings_Cost
{
	public function generate_fixed_cost_html($defult, $key, $data, $settings_api)
	{
		$shipping_rate = $settings_api->get_option('cost');
		$style = !empty($shipping_rate) && $shipping_rate !== 'flat' ? 'display: none;' : '';
		$field_key = $settings_api->get_field_key($key);

		ob_start();
?>
		<tr valign="top" class="flat-rate" style="<?php echo $style ?>">
			<th scope="row" class="titledesc">
                <?php esc_html_e('Shipping rates', 'woo-lithuaniapost') ?>
			</th>
			<td class="forminp">
				<fieldset>
					<input class="wc_input_decimal input-text regular-input" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" value="<?php echo esc_attr(wc_format_localized_decimal($settings_api->get_option($key))); ?>" />
				</fieldset>
			</td>
		</tr>
	<?php

		return ob_get_clean();
	}

	public function generate_weight_cost_html($defult, $key, $values, $settings_api)
	{
		$style = $settings_api->get_option('cost') !== 'weight' ? 'display: none;' : '';
		$weights = $this->get_weights($settings_api);

		ob_start();
	?>
		<tr valign="top" class="weight-rate" style="<?php echo $style ?>">
			<th scope="row" class="titledesc">
                <?php esc_html_e('Shipping rates', 'woo-lithuaniapost') ?>
			</th>
			<td class="forminp">
				<fieldset>
					<table class="wp-list-table widefat fixed striped table-view-list customers">
						<thead>
							<tr>
								<th id="cb" class="manage-column column-cb check-column"></th>
								<th scope="col" id="size" class="manage-column column-size column-primary"><?php esc_html_e('Shipping weight', 'woo-lithuaniapost') ?></th>
								<th scope="col" id="cost" class="manage-column column-cost"><?php esc_html_e('Cost', 'woocommerce') ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody id="the-list" data-wp-lists="list:customer">
							<?php
							for ($i = 0; $i < sizeof($weights); $i++) {
								$weights_row = $weights[$i];
								$weights_row['weight_from'] = $i == 0 ? 0 : $weights[$i - 1]['weight'] + 1;
								include 'html-settings-cost-weight-row.php';
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<a href="#" class="button insert" data-row="
							<?php
							$weights_row = [
								'weight' => '',
								'cost' => '',
							];
							ob_start();
							require 'html-settings-cost-weight-row.php';
							echo esc_attr(ob_get_clean());
							?>
					  		"><?php esc_html_e('Add Weight', 'woo-lithuaniapost'); ?>
									</a>
								</th>
							</tr>
						</tfoot>
					</table>
				</fieldset>
			</td>
		</tr>
<?php

		return ob_get_clean();
	}

    public function generate_size_cost_html($defult, $key, $values, $settings_api)
    {
        $style = $settings_api->get_option('cost') !== 'size' ? 'display: none;' : '';
        $sizes = $this->get_sizes($settings_api);

        ob_start();
        ?>
        <tr valign="top" class="size-rate" style="<?php echo $style ?>">
            <th scope="row" class="titledesc">
                <?php esc_html_e('Shipping rates', 'woo-lithuaniapost') ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <table class="wp-list-table widefat fixed striped table-view-list form-table">
                        <thead>
                        <tr>
                            <th scope="col" id="size"
                                class="manage-column column-size column-primary"><?php esc_html_e('Shipping size', 'woo-lithuaniapost') ?></th>
                            <th></th>
                            <th scope="col" id="cost"
                                class="manage-column column-cost"><?php esc_html_e('Cost', 'woocommerce') ?></th>
                        </tr>
                        </thead>
                        <tbody id="the-list" data-wp-lists="list:customer">
                        <?php
                        foreach ($sizes as $size) {
                            include 'html-settings-cost-size-row.php';
                        }
                        ?>
                        </tbody>
                    </table>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    private function get_sizes($settings_api)
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL'];

        foreach ($sizes as $size) {
            $sizeRates[] = [
                'size' => $size,
                'cost' => array_key_exists('sizes_cost_' . $size, $settings_api->instance_settings) ? $settings_api->get_instance_option('sizes_cost_' . $size) : null
            ];
        }

        return $sizeRates;
    }

	private function get_weights($settings_api)
	{
		$weights = [];
		$is_weight_configured = true;
		$i = 0;
		do {
			$is_weight_configured = array_key_exists('weights_' . $i, $settings_api->instance_settings);
			if ($is_weight_configured) {
				$weights[] = [
					'weight' => $settings_api->get_instance_option('weights_' . $i),
					'cost' => $settings_api->get_instance_option('weights_costs_' . $i)
				];
			}
			$i++;
		} while ($is_weight_configured);

		return $weights;
	}
}

<tr>
	<td class="sort"><div style="text-align: end;" class ="weight"><?php echo esc_attr($weights_row['weight_from'] ?? null) ?> g -</div></td>
	<td>
		<input type="number" class="input_text weight-cost"  name="woocommerce_woo_lithuaniapost_lpexpress_terminal_weights[]" value="<?php echo esc_attr( $weights_row['weight'] ?? null ); ?>" />
	</td>
	<td>
		<input class="input_text" name="woocommerce_woo_lithuaniapost_lpexpress_terminal_weights_costs[]" value="<?php echo esc_attr( $weights_row['cost'] ?? null ); ?>" />
	</td>
	<td width="1%"><a href="#" class="delete"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a></td>
</tr>
<?php
$parcel = $_SESSION['parcel_to_edit'];
?>
<?php if (@$parcel->documents && @$parcel->documents->cn): ?>
    <?php $cn_data = $parcel->documents->cn; ?>
    <div class="tab" data-tab="4" style="margin-top: 20px">
        <h3><?php _e('CN Declaration', 'woo-lithuaniapost'); ?></h3>
        <div class="admin__field">
            <label for="parcel_type" class="admin__field-label">
                <?php _e('Parcel Type', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
            </label>
            <div class="admin__field-control">
                <select name="cnForm[contentType]" id="content_type" class="admin__control-select widefat">
                    <option value="Sell"><?php _e('Sell', 'woo-lithuaniapost'); ?></option>
                    <option value="Gift" <?php echo $cn_data->contentType == 'Gift' ? 'selected' : ''; ?>><?php _e('Gift', 'woo-lithuaniapost'); ?></option>
                    <option value="Document" <?php echo $cn_data->contentType == 'Document' ? 'selected' : ''; ?>><?php _e('Document', 'woo-lithuaniapost'); ?></option>
                    <option value="Sample" <?php echo $cn_data->contentType == 'Sample' ? 'selected' : ''; ?>><?php _e('Sample', 'woo-lithuaniapost'); ?></option>
                    <option value="Return" <?php echo $cn_data->contentType == 'Return' ? 'selected' : ''; ?>><?php _e('Return', 'woo-lithuaniapost'); ?></option>
                    <option value="Other" <?php echo $cn_data->contentType == 'Other' ? 'selected' : ''; ?>><?php _e('Other', 'woo-lithuaniapost'); ?></option>
                </select>
            </div>
        </div>
        <div class="admin__field">
            <label for="parcel_type_notes" class="admin__field-label">
                <?php _e('Parcel Type Notes', 'woo-lithuaniapost'); ?>
            </label>
            <div class="admin__field-control">
                <input name="cnForm[contentDescription]"
                       value="<?php echo @$cn_data->contentDescription ?: __('Sell Items', 'woo-lithuaniapost'); ?>"
                       id="parcel_type_notes" type="text" class="admin__control-text widefat"/>
            </div>
        </div>
        <section class="admin__page-section">
            <div class="admin__page-section-title">
                <span class="title"><?php _e('Parcel Items', 'woo-lithuaniapost') ?></span>
            </div>
            <div class="admin__table-wrapper">
                <table class="data-table admin__table-primary edit-order-table">
                    <thead class="lp-cn-parts">
                    <tr class="headings">
                        <th class="col-summary">
                        <span>
                            <?php _e('Summary', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                        </span>
                        </th>
                        <th class="col-amount">
                        <span>
                            <?php _e('Amount', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                        </span>
                        </th>
                        <th class="col-currency">
                        <span>
                            <?php _e('Currency', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                        </span>
                        </th>
                        <th class="col-weight">
                        <span>
                            <?php _e('Weight', 'woo-lithuaniapost'); ?> (g) <span style="color:red">*</span>
                        </span>
                        </th>
                        <th class="col-quantity">
                        <span>
                            <?php _e('Quantity', 'woo-lithuaniapost'); ?> <span style="color:red">*</span>
                        </span>
                        </th>
                        <th class="col-country">
                        <span>
                            <?php _e('Country of Origin', 'woo-lithuaniapost'); ?>
                        </span>
                        </th>
                        <th class="col-hs-code">
                            <span><?php _e('HS Item Number', 'woo-lithuaniapost'); ?></span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="even">
                    <?php $counter = -1;
                    foreach ($cn_data->parts as $item): $counter++; ?>
                        <tr>
                            <td class="col-summary">
                                <div class="admin__field-control">
                                    <input name="cnForm[parts][<?php echo $counter; ?>][summary]" type="text"
                                           id="summary"
                                           class="admin__control-text" type="text" value="<?php echo $item->summary; ?>"
                                           required/>
                                </div>
                            </td>
                            <td class="col-amount">
                                <div class="admin__field-control">
                                    <input name="cnForm[parts][<?php echo $counter; ?>][amount]" type="number"
                                           value="<?php echo number_format($item->amount, 2); ?>"
                                           step="0.01" id="amount" type="text" class="admin__control-text"
                                           required/>
                                </div>
                            </td>
                            <td class="col-currency">
                                <select name="cnForm[parts][<?php echo $counter; ?>][currencyCode]" id="currencyCode"
                                        class="admin__control-select">
                                    <option value="EUR">EUR</option>
                                    <option value="USD" <?php echo $item->currencyCode
                                    == 'USD' ? 'selected' : ''; ?>>USD
                                    </option>
                                </select>
                            </td>
                            <td class="col-weight">
                                <div class="admin__field-control">
                                    <input name="cnForm[parts][<?php echo $counter; ?>][weight]" type="number"
                                           value="<?php echo $item->weight; ?>"
                                           step="1" id="weight" type="text" class="admin__control-text"
                                           required/>
                                </div>
                            </td>
                            <td class="col-quantity">
                                <div class="admin__field-control">
                                    <input name="cnForm[parts][<?php echo $counter; ?>][quantity]" type="number"
                                           value="<?php echo intval($item->quantity); ?>"
                                           step="1" id="quantity" type="text" class="admin__control-text"
                                           required/>
                                </div>
                            </td>
                            <td class="col-country" style="max-width: 155px">
                                <select name="cnForm[parts][<?php echo $counter; ?>][countryCode]" id="countryCode"
                                        class="admin__control-select">
                                    <option value=""><?php _e('Select', 'woo-lithuaniapost')?></option>
                                    <?php foreach (Woo_Lithuaniapost_Admin_Settings::get_country_list() as $code => $country): ?>
                                        <option value="<?php echo $code; ?>" <?php echo $code == ($item->countryCode ?? null) ? 'selected' : ''; ?>>
                                            <?php echo $country; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="col-hs-code">
                                <div class="admin__field-control">
                                    <input name="cnForm[parts][<?php echo $counter; ?>][hsCode]" id="hscode"
                                           value="<?php echo $item->hsCode ?? null; ?>" type="text"
                                           class="admin__control-text"/>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php if (($parcel->documents->cn->type ?? null) == 'CN23'): ?>
            <div class="admin__field">
                <label for="exporter_customs_code" class="admin__field-label">
                    <?php _e('Exporter Customs Code', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[exporter][customsRegistrationNo]"
                           value="<?php echo $cn_data->exporter->customsRegistrationNo ?? null; ?>"
                           id="exporter_customs_code" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="license" class="admin__field-label">
                    <?php _e('License', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[documents][license]"
                           value="<?php echo $cn_data->documents->license ?? null; ?>"
                           id="license" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="certificate" class="admin__field-label">
                    <?php _e('Certificate', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[documents][certificate]"
                           value="<?php echo $cn_data->documents->certificate ?? null; ?>"
                           id="certificate" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="invoice" class="admin__field-label">
                    <?php _e('Invoice', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[documents][invoice]"
                           value="<?php echo $cn_data->documents->invoice ?? null; ?>"
                           id="invoice" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="notes" class="admin__field-label">
                    <?php _e('Notes', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
            <textarea name="cnForm[documents][notes]" id="parcel_description"
                      class="admin__control-textarea widefat"><?php echo $cn_data->documents->notes ?? null; ?></textarea>
                </div>
            </div>
            <div class="admin__field">
                <label for="failure_instructions" class="admin__field-label">
                    <?php _e('Failure Instruction', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <select name="cnForm[failureInstruction]" id="parcel_type" class="admin__control-select widefat">
                        <option value="RETURN_TO_SENDER_NON_PRIORITY"
                            <?php echo $cn_data->failureInstruction == 'RETURN_TO_SENDER_NON_PRIORITY' ? 'selected' : ''; ?>
                        >RETURN_TO_SENDER_NON_PRIORITY
                        </option>
                        <option value="RETURN_TO_SENDER_PRIORITY"
                            <?php echo $cn_data->failureInstruction == 'RETURN_TO_SENDER_PRIORITY' ? 'selected' : ''; ?>
                        >RETURN_TO_SENDER_PRIORITY
                        </option>
                        <option value="TREAT_AS_ABANDONED"
                            <?php echo $cn_data->failureInstruction == 'TREAT_AS_ABANDONED' ? 'selected' : ''; ?>
                        >TREAT_AS_ABANDONED
                        </option>
                    </select>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_code" class="admin__field-label">
                    <?php _e('Importer Code', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][code]"
                           value="<?php echo $cn_data->importer->code ?? null; ?>"
                           id="importer_code" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_customs_code" class="admin__field-label">
                    <?php _e('Importer Customs Code', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][customsRegistrationNo]"
                           value="<?php echo $cn_data->importer->customsRegistrationNo ?? null; ?>"
                           id="importer_customs_code" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_email" class="admin__field-label">
                    <?php _e('Importer Email', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][contact][email]"
                           value="<?php echo $cn_data->importer->contact->email ?? null; ?>"
                           id="importer_email" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_fax" class="admin__field-label">
                    <?php _e('Importer Fax', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][contact][fax]"
                           value="<?php echo $cn_data->importer->contract->fax ?? null; ?>"
                           id="importer_fax" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_phone" class="admin__field-label">
                    <?php _e('Importer Phone', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][contact][phone]"
                           value="<?php echo $cn_data->importer->contract->phone ?? null; ?>"
                           id="importer_phone" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_tax_code" class="admin__field-label">
                    <?php _e('Importer Tax Code', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][taxCode]"
                           value="<?php echo $cn_data->importer->taxCode ?? null; ?>"
                           id="importer_tax_code" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
            <div class="admin__field">
                <label for="importer_vat_code" class="admin__field-label">
                    <?php _e('Importer Vat Code', 'woo-lithuaniapost'); ?>
                </label>
                <div class="admin__field-control">
                    <input name="cnForm[importer][vatCode]"
                           value="<?php echo $cn_data->importer->vatCode ?? null; ?>"
                           id="importer_vat_code" type="text" class="admin__control-text widefat"/>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
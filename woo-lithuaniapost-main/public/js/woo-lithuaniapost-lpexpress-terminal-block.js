(function ($) {
    'use strict';

    function attachTerminalDropdown() {
        let checkedShippingMethodLabel = $('.wc-block-components-shipping-rates-control .wc-block-components-radio-control__option-checked');
        if (checkedShippingMethodLabel.length > 0) {
            let input = $(checkedShippingMethodLabel).find('input');
            if (input) {
                if (input.val() && input.val().indexOf('woo_lithuaniapost_lpexpress_terminal') !== -1) {
                    let terminalLabel = $('.woo_lithuaniapost_lpexpress_terminal_label');
                    if (terminalLabel.length > 0) {
                        let terminalInput = $('#' + input.val().replace(":", "_") + '.woo_lithuaniapost_shipping_method_terminal');
                        if (terminalInput.length > 0) {
                            terminalLabel.detach();
                            terminalLabel.appendTo(checkedShippingMethodLabel);
                            $(terminalLabel).show();
                        } else {
                            $(terminalLabel).hide();
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    function loopAttachTerminalDropdown() {
        if (!attachTerminalDropdown()) {
            setTimeout(() => {
                loopAttachTerminalDropdown()
            }, 1000);
        }
    }

    $(document).ready(function () {
        $(document).delegate('.wp-site-blocks, .wp-block-woocommerce-cart-order-summary-block, .wp-block-woocommerce-checkout-shipping-methods-block', 'change', function (e) {
            attachTerminalDropdown();
        });
        loopAttachTerminalDropdown();
    });
})(jQuery);
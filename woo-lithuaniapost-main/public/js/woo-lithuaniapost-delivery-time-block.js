(function ($) {
    'use strict';

    function loopAttachDeliveryTime() {
        if (!attachDeliveryTime()) {
            setTimeout(() => {
                loopAttachDeliveryTime()
            }, 1000);
        }
    }

    function attachDeliveryTime() {
        const deliveryTimeLabels = $('.woo_lithuaniapost_delivery_time_label');
        if (deliveryTimeLabels.length > 0) {
            let countLeft = deliveryTimeLabels.length;
            $(deliveryTimeLabels).each(function( index ) {
                const deliveryTimeLabel = $(this);
                const value = deliveryTimeLabel.attr('data-value');
                if (value) {
                    const shippingMethodInput = $('input[value="' + value + '"]');
                    if (shippingMethodInput.length === 1) {
                        const shippingMethodLabel = $(shippingMethodInput).closest('.wc-block-components-shipping-rates-control label');
                        if (shippingMethodLabel.length > 0) {
                            deliveryTimeLabel.detach();
                            deliveryTimeLabel.appendTo(shippingMethodLabel);
                            deliveryTimeLabel.show();
                            countLeft--;
                        }
                    }
                }
            });
            return countLeft < 1;
        }
        return false;
    }

    $(document).ready(function () {
        loopAttachDeliveryTime();
    });

})(jQuery);
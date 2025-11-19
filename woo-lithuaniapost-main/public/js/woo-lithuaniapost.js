(function ($) {
    'use strict';

    let savedTerminalId = undefined;

    function onTerminalSelect(data) {
        const terminalId = data.id;
        const terminalName = data.text;
        if (terminalId && terminalId !== savedTerminalId && !isNaN(terminalId)) {
            $.ajax({
                type: "POST",
                dataType: "json",
                url: woo_lithuaniapost.ajax_url,
                data: {
                    action: "save_selected_lpexpress_terminal",
                    terminal: terminalName,
                    terminal_id: terminalId
                }, success: function () {
                    savedTerminalId = terminalId;
                }
            });
        }
    }

    $(document).ready(function () {

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

        const recurse = (parent) => {

            if (parent.nodeName === 'SELECT' && parent.className === 'woo_lithuaniapost_lpexpress_terminal_id') {
                const terminalsElem = $(parent);
                if(terminalsElem.width() < 250) {
                    const tdElem = $('<td colspan=\"2\">').append(terminalsElem.parent());
                    const trElem = $('<tr>').append(tdElem)
                    $('.woocommerce-shipping-totals.shipping').after(trElem);
                }

                let eventSelect = terminalsElem.select2({
                    width: 'resolve',
                    matcher(params, data) {
                        return terminalMatcher(params, data);
                    },
                });
                eventSelect.on("select2:select", function (e) { onTerminalSelect(e.params.data) });
                return;
            }
            if (parent.childNodes) {
                [...parent.childNodes].forEach(recurse);
            }
        };

        // select the target node
        var target = $('.cart-collaterals, #order_review, .wc-block-components-totals-shipping');
        let terminalIdElem = $('.woo_lithuaniapost_lpexpress_terminal_id');
        if (terminalIdElem.length > 0) {
            let eventSelect = $(terminalIdElem).select2({
                width: 'resolve',
                matcher(params, data) {
                    return terminalMatcher(params, data);
                },
            });
            eventSelect.on("select2:select", function (e) { onTerminalSelect(e.params.data) });
        }

        if (target) {
            // create an observer instance
            var observer = new MutationObserver(function (mutations) {
                //loop through the detected mutations(added controls)
                mutations.forEach(function (mutation) {
                    for (const node of mutation.addedNodes) {
                        recurse(node);
                    }
                });
            });
            let obsConfig = {
                childList: true,
                characterData: true,
                attributes: true,
                subtree: true
            };
            target.each(function () {
                observer.observe(this, obsConfig);
            });

            // later, you can stop observing
            //observer.disconnect();
        }
    });
})(jQuery);
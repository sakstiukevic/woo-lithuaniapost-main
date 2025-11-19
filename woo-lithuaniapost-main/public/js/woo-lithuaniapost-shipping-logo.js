(function ($) {
    'use strict';

    function startListeningForMutations(elements, matcher, callback) {

        const recurse = (parent) => {

            for (let match in matcher) {
                const parentValue = parent[match];
                const matcherValue = matcher[match];
                if (!parentValue || (parentValue !== matcherValue && !String(parentValue).includes(matcherValue))) {
                    if (parent.childNodes) {
                        [...parent.childNodes].forEach(recurse);
                    }
                    return;
                }
            }
            callback(parent);
        };

        // select the target node
        var target = $(elements);

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
    }

    function attachShippingLogos() {
        const shippingMethods = $("input[type='radio'][value^=woo_lithuaniapost_lpexpress_terminal]");
        if (shippingMethods.length > 0) {
            $(shippingMethods).each(function (index, input) {
                attachLogo(input);
            });
            return true;
        }
        return false;
    }

    function attachLogo(shippingMethodElement) {
        const imgUrl = woo_lithuaniapost.shipping_logo_url;
        if ($(shippingMethodElement).next().filter((index, element) => $(element).prop('nodeName') === 'IMG').length === 0) {
            $(shippingMethodElement).after("<img style='max-width:45px; max-height:25px; padding-right: 0.1em;' src=" + imgUrl + ">");
        }
    }

    $(document).ready(function () {
        const elementMatcher = {
            nodeName: 'INPUT',
            type: 'radio',
            value: 'woo_lithuaniapost_lpexpress_terminal'
        };
        startListeningForMutations(document, elementMatcher, function (matchedElement) {
            attachLogo(matchedElement);
        });
        attachShippingLogos();
    });

})(jQuery);
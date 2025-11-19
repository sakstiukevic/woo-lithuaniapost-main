(function ($) {
    'use strict';

    let select2Initialized = {};

    function initializeSelect2(terminalSelect) {
        if (!terminalSelect || terminalSelect.length === 0) {
            return false;
        }

        const selectId = terminalSelect.attr('id') || terminalSelect.attr('name') || 'default';
        if (select2Initialized[selectId] && terminalSelect.hasClass('select2-hidden-accessible')) {
            return true;
        }

        if (typeof $.fn.select2 === 'undefined') {
            return false;
        }

        try {
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
            };

            terminalSelect.select2({
                width: '100%',
                matcher(params, data) {
                    return terminalMatcher(params, data);
                },
            });

            terminalSelect.off("select2:select").on("select2:select", function (e) {
                const terminalId = e.params.data.id;
                const terminalName = e.params.data.text;
                if (terminalId && !isNaN(terminalId) && typeof woo_lithuaniapost !== 'undefined' && woo_lithuaniapost.ajax_url) {
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: woo_lithuaniapost.ajax_url,
                        data: {
                            action: "save_selected_lpexpress_terminal",
                            terminal: terminalName,
                            terminal_id: terminalId
                        }
                    });
                }
            });

            select2Initialized[selectId] = true;
            return true;
        } catch (e) {
            console.error('Error initializing Select2:', e);
            return false;
        }
    }

    let isAttaching = false;
    let lastCheckedMethod = null;
    let attachDebounceTimeout = null;
    let isProcessingTerminalSelection = false;

    function attachTerminalDropdown() {
        // Clear any pending debounce timeout
        if (attachDebounceTimeout) {
            clearTimeout(attachDebounceTimeout);
            attachDebounceTimeout = null;
        }
        
        // Prevent multiple simultaneous calls
        if (isAttaching) {
            return;
        }
        
        // Set flag immediately to prevent other calls
        isAttaching = true;

        // Only work with block checkout
        // Find checked shipping method
        let checkedShippingMethodLabel = $('.wc-block-components-shipping-rates-control .wc-block-components-radio-control__option-checked');
        
        if (checkedShippingMethodLabel.length === 0) {
            let checkedInput = $('.wc-block-components-shipping-rates-control input[type="radio"]:checked');
            if (checkedInput.length > 0) {
                checkedShippingMethodLabel = checkedInput.closest('.wc-block-components-radio-control__option');
            }
        }
        
        // Early exit if no shipping method found
        if (checkedShippingMethodLabel.length === 0) {
            isAttaching = false;
            return;
        }
        
        // Quick check: if dropdown is already correctly attached and visible, exit early
        let quickCheckDropdown = checkedShippingMethodLabel.find('.woo_lithuaniapost_lpexpress_terminal_label');
        if (quickCheckDropdown.length > 0 && quickCheckDropdown.is(':visible')) {
            let quickCheckSelect = quickCheckDropdown.find('.woo_lithuaniapost_lpexpress_terminal_id');
            if (quickCheckSelect.length > 0 && quickCheckSelect.hasClass('select2-hidden-accessible')) {
                // Everything is already set up - exit immediately
                isAttaching = false;
                return;
            }
        }

        if (checkedShippingMethodLabel.length > 0) {
            let input = $(checkedShippingMethodLabel).find('input[type="radio"]');
            if (input.length > 0 && input.is(':checked')) {
                let inputValue = input.val();
                
                // Check if terminal dropdown already exists and is attached to this method
                let existingDropdown = checkedShippingMethodLabel.find('.woo_lithuaniapost_lpexpress_terminal_label');
                
                // Skip if same method is already checked AND dropdown is already attached and visible (prevent flashing)
                // But if dropdown is missing, we need to attach it even if method is the same
                if (lastCheckedMethod === inputValue && existingDropdown.length > 0 && existingDropdown.is(':visible')) {
                    // Also check if Select2 is initialized
                    let terminalSelect = existingDropdown.find('.woo_lithuaniapost_lpexpress_terminal_id');
                    if (terminalSelect.length > 0 && terminalSelect.hasClass('select2-hidden-accessible')) {
                        // Everything is already set up correctly - don't do anything
                        isAttaching = false;
                        return;
                    }
                }
                lastCheckedMethod = inputValue;
                
                // Only show terminal dropdown for terminal shipping method
                if (inputValue && inputValue.indexOf('woo_lithuaniapost_lpexpress_terminal') !== -1) {
                    // Check if dropdown is already correctly attached to this method and visible
                    existingDropdown = checkedShippingMethodLabel.find('.woo_lithuaniapost_lpexpress_terminal_label');
                    if (existingDropdown.length > 0 && existingDropdown.is(':visible')) {
                        // Dropdown is already attached and visible - just ensure Select2 is initialized
                        let terminalSelect = existingDropdown.find('.woo_lithuaniapost_lpexpress_terminal_id');
                        if (terminalSelect.length > 0 && !terminalSelect.hasClass('select2-hidden-accessible')) {
                            setTimeout(function() {
                                initializeSelect2(terminalSelect);
                                isAttaching = false;
                            }, 200);
                        } else {
                            isAttaching = false;
                        }
                        return;
                    }
                    
                    // First, remove terminal labels from OTHER shipping methods (not the current one)
                    // This ensures no duplicates exist before we attach to the correct one
                    // But DON'T detach if it's already correctly attached to the current method
                    $('.woo_lithuaniapost_lpexpress_terminal_label').each(function() {
                        let $label = $(this);
                        // Only detach if it's NOT in the correct place (current checked method)
                        if (!$label.closest(checkedShippingMethodLabel).length) {
                            $label.detach();
                        }
                    });
                    
                    // Re-check if dropdown is now correctly attached (after removing duplicates)
                    existingDropdown = checkedShippingMethodLabel.find('.woo_lithuaniapost_lpexpress_terminal_label');
                    if (existingDropdown.length > 0 && existingDropdown.is(':visible')) {
                        // Dropdown is already correctly attached - just ensure Select2 is initialized
                        let terminalSelect = existingDropdown.find('.woo_lithuaniapost_lpexpress_terminal_id');
                        if (terminalSelect.length > 0 && !terminalSelect.hasClass('select2-hidden-accessible')) {
                            setTimeout(function() {
                                initializeSelect2(terminalSelect);
                                isAttaching = false;
                            }, 200);
                        } else {
                            isAttaching = false;
                        }
                        return;
                    }
                    
                    let terminalLabel = $('.woo_lithuaniapost_lpexpress_terminal_label');
                    
                    // Always check if terminal label exists in DOM, if not fetch via AJAX
                    // This handles cases when shipping methods reload after address change
                    if (terminalLabel.length === 0) {
                        let instanceId = inputValue.split(':')[1];
                        if (instanceId && typeof woo_lithuaniapost !== 'undefined' && woo_lithuaniapost.ajax_url) {
                            $.ajax({
                                type: "POST",
                                dataType: "json",
                                url: woo_lithuaniapost.ajax_url,
                                data: {
                                    action: "get_terminal_dropdown_html",
                                    instance_id: instanceId
                                },
                                success: function(response) {
                                    if (response.success && response.data.html) {
                                        // Double-check if label was added by another process
                                        let existingLabel = $('.woo_lithuaniapost_lpexpress_terminal_label');
                                        if (existingLabel.length > 0) {
                                            // Label exists, just attach it
                                            existingLabel.detach();
                                            existingLabel.appendTo(checkedShippingMethodLabel);
                                            existingLabel.show();
                                            
                                            setTimeout(function() {
                                                let terminalSelect = existingLabel.find('.woo_lithuaniapost_lpexpress_terminal_id');
                                                if (terminalSelect.length > 0 && !terminalSelect.hasClass('select2-hidden-accessible')) {
                                                    initializeSelect2(terminalSelect);
                                                }
                                                isAttaching = false;
                                            }, 200);
                                            return;
                                        }
                                        
                                        // Parse and attach new label
                                        let tempDiv = $('<div>').html(response.data.html);
                                        terminalLabel = tempDiv.find('.woo_lithuaniapost_lpexpress_terminal_label');
                                        if (terminalLabel.length > 0) {
                                            terminalLabel.detach();
                                            terminalLabel.appendTo(checkedShippingMethodLabel);
                                            terminalLabel.show();
                                            
                                            setTimeout(function() {
                                                let terminalSelect = terminalLabel.find('.woo_lithuaniapost_lpexpress_terminal_id');
                                                if (terminalSelect.length > 0) {
                                                    initializeSelect2(terminalSelect);
                                                }
                                                isAttaching = false;
                                            }, 200);
                                        } else {
                                            isAttaching = false;
                                        }
                                    } else {
                                        isAttaching = false;
                                    }
                                },
                                error: function() {
                                    isAttaching = false;
                                }
                            });
                            return;
                        } else {
                            isAttaching = false;
                        }
                    }

                    // Terminal label should already be detached (except if it's already in the right place)
                    // Re-check terminal label
                    terminalLabel = $('.woo_lithuaniapost_lpexpress_terminal_label');
                    
                    // Check if label is already in the correct place
                    let labelInCorrectPlace = false;
                    if (terminalLabel.length > 0) {
                        terminalLabel.each(function() {
                            if ($(this).closest(checkedShippingMethodLabel).length > 0) {
                                labelInCorrectPlace = true;
                                return false; // break
                            }
                        });
                    }
                    
                    // If label is already in the correct place and visible, don't do anything
                    if (labelInCorrectPlace && terminalLabel.is(':visible')) {
                        // Just ensure Select2 is initialized
                        let terminalSelect = terminalLabel.find('.woo_lithuaniapost_lpexpress_terminal_id');
                        if (terminalSelect.length > 0 && !terminalSelect.hasClass('select2-hidden-accessible')) {
                            setTimeout(function() {
                                initializeSelect2(terminalSelect);
                                isAttaching = false;
                            }, 200);
                        } else {
                            isAttaching = false;
                        }
                        return;
                    }
                    
                    // Remove duplicates - keep only one
                    if (terminalLabel.length > 1) {
                        terminalLabel.slice(1).remove();
                        terminalLabel = terminalLabel.first();
                    }
                    
                    if (terminalLabel.length > 0) {
                        // Only detach if not already in the correct place
                        if (!terminalLabel.closest(checkedShippingMethodLabel).length) {
                            terminalLabel.detach();
                            terminalLabel.appendTo(checkedShippingMethodLabel);
                        }
                        terminalLabel.show();
                        
                        // Initialize Select2
                        let terminalSelect = terminalLabel.find('.woo_lithuaniapost_lpexpress_terminal_id');
                        if (terminalSelect.length > 0 && !terminalSelect.hasClass('select2-hidden-accessible')) {
                            setTimeout(function() {
                                initializeSelect2(terminalSelect);
                                isAttaching = false;
                            }, 200);
                        } else {
                            isAttaching = false;
                        }
                    } else {
                        isAttaching = false;
                    }
                } else {
                    // Non-terminal shipping method selected - remove terminal labels from all shipping methods
                    $('.woo_lithuaniapost_lpexpress_terminal_label').each(function() {
                        $(this).detach();
                    });
                    
                    // Clear terminal session when non-terminal method is selected
                    if (typeof woo_lithuaniapost !== 'undefined' && woo_lithuaniapost.ajax_url) {
                        $.ajax({
                            type: "POST",
                            dataType: "json",
                            url: woo_lithuaniapost.ajax_url,
                            data: {
                                action: "clear_selected_lpexpress_terminal"
                            }
                        });
                    }
                    isAttaching = false;
                }
            } else {
                // No checked input found - remove terminal labels from all shipping methods
                $('.woo_lithuaniapost_lpexpress_terminal_label').each(function() {
                    $(this).detach();
                });
                isAttaching = false;
            }
        } else {
            // No shipping method options found - remove terminal labels
            $('.woo_lithuaniapost_lpexpress_terminal_label').each(function() {
                $(this).detach();
            });
            isAttaching = false;
        }
    }

    $(document).ready(function () {
        // Only run for block checkout
        if ($('.wc-block-components-shipping-rates-control').length === 0) {
            return;
        }
        
        // Reset tracking when shipping methods reload
        function resetTracking() {
            lastCheckedMethod = null;
            isAttaching = false;
        }
        
        // Initial attempt after page load
        setTimeout(function() {
            attachTerminalDropdown();
        }, 2000);
        
        // Listen for WooCommerce block checkout updates (when address changes, shipping methods reload)
        // Block checkout uses 'updated_wc_block' event
        let blockUpdateTimeout = null;
        $(document.body).on('updated_wc_block', function() {
            // Clear any pending timeout
            if (blockUpdateTimeout) {
                clearTimeout(blockUpdateTimeout);
            }
            
            resetTracking();
            // Clear Select2 initialization cache when shipping methods reload
            select2Initialized = {};
            // Wait for shipping methods to reload
            blockUpdateTimeout = setTimeout(function() {
                attachTerminalDropdown();
                blockUpdateTimeout = null;
            }, 2500);
        });
        
        // Also listen for classic checkout event (in case block checkout uses it too)
        let checkoutUpdateTimeout = null;
        $(document.body).on('updated_checkout', function() {
            // Clear any pending timeout
            if (checkoutUpdateTimeout) {
                clearTimeout(checkoutUpdateTimeout);
            }
            
            resetTracking();
            // Clear Select2 initialization cache when shipping methods reload
            select2Initialized = {};
            // Wait for shipping methods to reload
            checkoutUpdateTimeout = setTimeout(function() {
                attachTerminalDropdown();
                checkoutUpdateTimeout = null;
            }, 2500);
        });
        
        // Listen for shipping method selection changes
        // Use debounce to prevent multiple rapid calls
        let changeDebounceTimeout = null;
        $(document).on('change', '.wc-block-components-shipping-rates-control input[type="radio"]', function() {
            // Clear any pending timeout
            if (changeDebounceTimeout) {
                clearTimeout(changeDebounceTimeout);
            }
            
            let selectedValue = $(this).val();
            // Set flag to prevent MutationObserver from interfering
            if (selectedValue && selectedValue.indexOf('woo_lithuaniapost_lpexpress_terminal') !== -1) {
                isProcessingTerminalSelection = true;
            }
            
            resetTracking();
            // Wait a bit for shipping methods to be ready
            changeDebounceTimeout = setTimeout(function() {
                attachTerminalDropdown();
                // Reset flag after processing
                setTimeout(function() {
                    isProcessingTerminalSelection = false;
                }, 1000);
                changeDebounceTimeout = null;
            }, 800);
        });
        
        // Use MutationObserver to watch for shipping methods being added/updated
        // This handles cases when address changes and shipping methods reload
        let shippingRatesControl = $('.wc-block-components-shipping-rates-control');
        if (shippingRatesControl.length > 0) {
            let mutationTimeout = null;
            let observer = new MutationObserver(function(mutations) {
                // Skip if already attaching or processing terminal selection
                if (isAttaching || isProcessingTerminalSelection) {
                    return;
                }
                
                let shouldAttach = false;
                mutations.forEach(function(mutation) {
                    // Check if shipping method radio buttons were added or changed
                    if (mutation.addedNodes.length > 0) {
                        // Check if any added node contains radio buttons
                        for (let i = 0; i < mutation.addedNodes.length; i++) {
                            let node = mutation.addedNodes[i];
                            if (node.nodeType === 1) { // Element node
                                let $node = $(node);
                                if ($node.find('input[type="radio"]').length > 0 || 
                                    $node.is('input[type="radio"]') ||
                                    $node.closest('.wc-block-components-shipping-rates-control').length > 0) {
                                    shouldAttach = true;
                                    break;
                                }
                            }
                        }
                    }
                });
                
                if (shouldAttach) {
                    // Clear any pending timeout
                    if (mutationTimeout) {
                        clearTimeout(mutationTimeout);
                    }
                    
                    // Reset tracking when DOM changes
                    resetTracking();
                    
                    // Debounce: wait a bit for shipping methods to be fully loaded
                    mutationTimeout = setTimeout(function() {
                        attachTerminalDropdown();
                        mutationTimeout = null;
                    }, 2000);
                }
            });
            
            // Observe the shipping rates control container
            observer.observe(shippingRatesControl[0], {
                childList: true,
                subtree: true,
                attributes: false
            });
        }
        
        // Use a passive observer that only acts AFTER shipping methods are loaded
        // Don't interfere with shipping methods loading
        let checkInterval = setInterval(function() {
            // Only check if shipping methods are loaded
            let shippingMethodsLoaded = $('.wc-block-components-shipping-rates-control input[type="radio"]').length > 0;
            if (shippingMethodsLoaded) {
                attachTerminalDropdown();
            }
        }, 5000); // Check every 5 seconds - less frequent to avoid interference
    });
})(jQuery);

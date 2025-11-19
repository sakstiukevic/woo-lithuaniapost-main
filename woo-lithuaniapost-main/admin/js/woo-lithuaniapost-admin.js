(function( $ ) {
	'use strict';

	/**
	 * Convert serialized array to JSON object
	 */
	$.fn.getForm2obj = function () {
		var _ = {};
		$.map(this.serializeArray(), function(n) {
			const keys = n.name.match(/[a-zA-Z0-9_]+|(?=\[\])/g);
			if (keys.length > 1) {
				let tmp = _;
				let j;
				let pop = keys.pop();
				for (let i = 0; i < keys.length, j = keys[i]; i++) {
					tmp[j] = (!tmp[j] ? (pop === '') ? [] : {} : tmp[j]), tmp = tmp[j];
				}
				if (pop === '') tmp = (!Array.isArray(tmp) ? [] : tmp), tmp.push(n.value);
				else tmp[pop] = n.value;
			} else _[keys.pop()] = n.value;
		});
		return _;
	};

	function togglePickupAddress(animated) {
		var pickupAddress = $('#lpsettings_use_pickup_address');
		if (pickupAddress) {
			var parentTable = $("#lpsettings_pickup_name").parents("table");
			if (parentTable) {
				if (pickupAddress.is(":checked")) {
					parentTable.find('input').attr('disabled', false);
					parentTable.prev("h2").show();
					if (animated === true) {
						parentTable.fadeIn();
					} else {
						parentTable.show();
					}
				} else {
					parentTable.find('input').attr('disabled', true);
					parentTable.prev("h2").hide();
					if (animated === true) {
						parentTable.fadeOut();
					} else {
						parentTable.hide();
					}
				}
			}
		}
	}

	$('document').ready(function () {
		togglePickupAddress(false);
		$('#lpsettings_use_pickup_address').on('click', function (e) {
			togglePickupAddress(true);
		});

		$('#woo_lp_save_parcel_button').on('click', function (e) {
			e.preventDefault();
			var postForm = $('#post');
			var formToProcess = (postForm != null && postForm.is('form')) ? postForm[0] : $('#order')[0];
			if (formToProcess.checkValidity()) {
				var data = $.extend($('#lpshipping-shipment-modal :input').getForm2obj(), {
					action: 'woo_lithuaniapost_handle_parcel_save',
					dataType: 'json',
				});

				$('#lpshipping-shipment-modal').block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				$.ajax({
					url: woo_lithuaniapost_admin.ajax_url,
					data: data,
					type: 'POST',
					success: function (response) {
						$('#lpshipping-shipment-modal').unblock();
						location.reload();
					},
					error: function (xhr, resp, text) {
						console.log(xhr, resp, text);
						$('#lpshipping-shipment-modal').unblock();
					}
				});
			} else {
				formToProcess.reportValidity()
			}
		});
	});
})( jQuery );

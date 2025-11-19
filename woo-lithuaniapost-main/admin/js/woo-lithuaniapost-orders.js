(function ($) {
	'use strict';
	$(function () {
		var action = $('#action');

		$('#label-action').on('click', function () {
			action.val('print_label');
		});

		$('#manifest-action').on('click', function () {
			action.val('print_manifest');
		});

		$('#filter-action').on('click', function () {
			action.val('filter_orders');
		});

		$('#lp-orders-reset-filter').on('click', function () {
			action.val('lp-orders-reset-filter');
		});
	});
})(jQuery);

(function ($) {
	'use strict';
	$(document).ready(function () {
		$('.woocommerce-save-button').removeAttr('disabled');
	});

	document
	class Estimate {
		constructor(plans) {
			this.plans = plans.map(x => new Plan(x));
		}

		getPlan(planCode) {
			return this.plans.find(x => x.hasCode(planCode));
		}
	}

	class Plan {
		constructor(plan) {
			this.code = plan.code;
			this.shipping = plan.shipping;
		}

		getCode() {
			return this.code;
		}

		hasCode(code) {
			return this.code == code;
		}

		isWeightRequired(parcelType) {
			return this.shipping.find(x => x.parcelType == parcelType).requirements.weight;
		}

		isSizeRequired(parcelType) {
			return this.shipping.find(x => x.parcelType === parcelType).requirements.size;
		}

		getParcelTypes() {
			return this.shipping.map(x => x.parcelType);
		}

		getParcelTypesOptions(){
			return this.shipping.map(x => new Option(x.translated_parcel_type, x.parcelType));
		}

		getSizes(parcelType) {
			const shippingWithSize = this.shipping.find(x => x.parcelType == parcelType && x.requirements.size);

			if (shippingWithSize) {
				return shippingWithSize.options.map(x => ({
					key: x.size.code,
					size: `${x.size.code} (${x.size.length}${x.size.unit} x ${x.size.width}${x.size.unit} x ${x.size.height}${x.size.unit})`,
					selected: x.size.selected
				}));
			} else {
				return [];
			}
		}
	}

	class WeightCollection {
		constructor(weights) {
			this.weights = weights;
		}

		getNextWeight() {

			return this.weights.length ? this.weights[this.weights.length - 1] + 1 : 0;
		}

		add() {
			weights.push(0);
		}

		isValid(index, val) {
			this.weights[index] = val;
			return (index == 0 || this.weights[index - 1] < val)
				&&
				(index == this.weights.length - 1 || val < this.weights[index + 1]);
		}

		remove(index) {
			this.weights.splice(index);
		}
	}

	var weights = $('.weight-cost').map((index, x) => parseInt($(x).val())).get();
	var weightCollection = new WeightCollection(weights);


	$('.weight-cost').each((index, x) => $(x).on("blur", y => {
		var isValid = weightCollection.isValid(index, parseInt($(x).val()));
		if (isValid) {
			$(x).removeClass('error');
		} else {
			$(x).addClass('error');
		}
	}));



	$('#woocommerce_woo_lithuaniapost_lpexpress_terminal_cost').on('change', function () {
		switch (this.value) {
			case 'flat':
				$('.flat-rate').show();
				$('.size-rate').hide();
				$('.weight-rate').hide();
				$('.carrier-rate').hide();
				break;
			case 'weight':
				$('.flat-rate').hide();
				$('.size-rate').hide();
				$('.weight-rate').show();
				$('.carrier-rate').hide();
				break;
			case 'size':
				$('.flat-rate').hide();
				$('.size-rate').show();
				$('.weight-rate').hide();
				$('.carrier-rate').hide();
				break;
			case 'carrier':
				$('.flat-rate').hide();
				$('.size-rate').hide();
				$('.weight-rate').hide();
				$('.carrier-rate').show();
				break;
		}
	});

	$('.weight-rate').on(
		'click',
		'a.insert',
		function () {
			var table = $(this)
				.closest('.weight-rate')
				.find('tbody');
			table.append($(this).data('row'));

			var row = table.children().last();
			var val = weightCollection.getNextWeight();
			weightCollection.add();
			row.find('.weight').text(val + " g -");
			var weightElem = row.find('.weight-cost');
			weightElem.on("blur", y => {
				var weightIndex = $('.weight-cost').length - 1;
				var isValid = weightCollection.isValid(weightIndex, parseInt(weightElem.val()));
				if (isValid) {
					weightElem.removeClass('error');
				} else {
					weightElem.addClass('error');
				}
			})


			return false;
		}
	);
	$('.weight-rate').on(
		'click',
		'a.delete',
		function () {
			var weightIndex = $('.weight-rate a.delete').index(this);
			weightCollection.remove(weightIndex);
			$(this).closest('tr').remove();
			return false;
		}
	);



	const planElem = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_plan');
	const parcelTypeElem = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_parcel_type');
	const sizeElem = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_size');

	const estimate = new Estimate(plans);

	planElem.on('change', function () {
		parcelTypeElem.empty();
		const options = estimate.getPlan(this.value).getParcelTypesOptions();
		parcelTypeElem.append(options);
		parcelTypeElem.trigger('change');
	});

	update_dropdowns(parcelTypeElem.find(":selected").val());

	function update_dropdowns(selectedParcelType) {
		if (!selectedParcelType) {
			return;
		}
		const plan = estimate.getPlan(planElem.val());
		const weightOption = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_cost option[value="weight"]');
		const flatOption = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_cost option[value="flat"]');
		if (plan.isWeightRequired(selectedParcelType)) {
			weightOption.show();
		} else {
			weightOption.hide();
			weightOption.removeAttr('selected');
			flatOption.attr('selected');
		}
		if (plan.isSizeRequired(selectedParcelType)) {
			sizeElem.removeClass('disabled no-pointer');
			sizeElem.empty();
			const sizes = plan.getSizes(selectedParcelType);
			sizes.sort(compare_by_size_code);
			const options = sizes.map(x => new Option(x.size, x.key, x.selected, x.selected));
			sizeElem.append(options);
		} else {
			sizeElem.addClass('disabled no-pointer');
		}
		const costModels = $('#woocommerce_woo_lithuaniapost_lpexpress_terminal_cost');
		costModels.trigger('change')
	}

	parcelTypeElem.on('change', function () {
		update_dropdowns(this.value);
	});

	function compare_by_size_code(a, b) {
		return get_size_value(a.key) - get_size_value(b.key);
	}

	function get_size_value(size) {
		switch (size) {
			case 'XS':
				return 1;
			case 'S':
				return 2;
			case 'M':
				return 3;
			case 'L':
				return 4;
			case 'XL':
				return 5;
		}
		return -1;
	}

})(jQuery);

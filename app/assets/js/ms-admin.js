/*! MemberDash - v1.0.0
 * Copyright (c) 2019; * Licensed GPLv2+ */
/*global ms_data:false */
/*global ms_functions:false */
// cSpell:ignore WPSATCHELI

window.ms_init = window.ms_init || {};

jQuery(function () {
	let i;

	window.ms_init._done = window.ms_init._done || {};

	function initialize(callback) {
		if (undefined !== callback && undefined !== window.ms_init[callback]) {
			// Prevent multiple calls to init functions...
			if (true === window.ms_init._done[callback]) {
				return false;
			}

			window.ms_init._done[callback] = true;
			window.ms_init[callback]();
		}
	}

	if (undefined === window.ms_data) {
		return;
	}

	if (undefined !== ms_data.ms_init) {
		if (ms_data.ms_init instanceof Array) {
			for (i = 0; i < ms_data.ms_init.length; i += 1) {
				initialize(ms_data.ms_init[i]);
			}
		} else {
			initialize(ms_data.ms_init);
		}

		// Prevent multiple calls to init functions...
		ms_data.ms_init = [];
	}
});

/*global memberdashLi:false */

/* Global functions */

window.ms_functions = {
	dp_config: {
		dateFormat: 'yy-mm-dd', //TODO get wp configured date format
		dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thy', 'Fri', 'Sat'],
		custom_class: 'memberdash-datepicker', // Not a jQuery argument!
	},

	chosen_options: {
		minimumResultsForSearch: 6,
		width: 'auto',
	},

	// Initialize some UI components.
	init(scope) {
		const fn = window.ms_functions;

		// Initialize all select boxes.
		jQuery('.ms-wrap select, .ms-select', scope).each(function () {
			const el = jQuery(this);
			if (el.closest('.no-auto-init').length) {
				return;
			}
			if (el.closest('.manual-init').length) {
				return;
			}

			el.memberdashSelect(fn.chosen_options);
		});

		// Initialize the datepickers.
		jQuery('.memberdash-datepicker', scope).each(function () {
			const sel = jQuery(this);

			if (sel.closest('.no-auto-init').length) {
				return;
			}
			sel.ms_datepicker();
		});

		window.setTimeout(function () {
			jQuery('body').trigger('resize');
		}, 50);
	},

	ajax_update(obj) {
		let data,
			val,
			info_field,
			field = jQuery(obj),
			fn = window.ms_functions,
			anim = field;

		if (!field.hasClass('ms-processing')) {
			if (anim.parents('.memberdash-radio-wrapper').length) {
				anim = anim.parents('.memberdash-radio-wrapper').first();
			} else if (
				anim.parents('.memberdash-radio-slider-wrapper').length
			) {
				anim = anim.parents('.memberdash-radio-slider-wrapper').first();
			} else if (anim.parents('.memberdash-input-wrapper').length) {
				anim = anim.parents('.memberdash-input-wrapper').first();
			} else if (anim.parents('.memberdash-select-wrapper').length) {
				anim = anim.parents('.memberdash-select-wrapper').first();
			} else if (anim.parents('label').length) {
				anim = anim.parents('label').first();
			}

			anim.addClass('memberdash-loading');
			info_field = fn.ajax_show_indicator(field);

			data = field.data('memberdash-ajax');

			if (field.is(':checkbox')) {
				data.value = field.prop('checked');
			} else {
				val = field.val();
				if (
					val instanceof Array ||
					val instanceof Object ||
					null === val
				) {
					data.values = val;
				} else {
					data.value = val;
				}
			}
			if (undefined === data.field) {
				data.field = field.attr('name');
			}

			// Allow fields to pre-process the data before sending it.
			if ('function' === typeof field.data('before_ajax')) {
				data = field.data('before_ajax')(data, field);
			}

			field.trigger('ms-ajax-start', [data, info_field, anim]);
			jQuery
				.post(window.ajaxurl, data, function (response) {
					const is_err = fn.ajax_error(response, info_field);
					if (is_err) {
						// Reset the input control to previous value...
					}

					anim.removeClass('memberdash-loading');
					info_field.removeClass('ms-processing');
					field.trigger('ms-ajax-updated', [data, response, is_err]);
				})
				.always(function () {
					field.trigger('ms-ajax-done', [data, info_field, anim]);
				});
		}
	},

	radio_slider_ajax_update(obj) {
		let data,
			info_field,
			toggle,
			states,
			state,
			slider = jQuery(obj),
			fn = window.ms_functions;

		if (!slider.hasClass('ms-processing') && !slider.attr('readonly')) {
			slider.toggleClass('on');
			slider.toggleClass('off');
			slider.parent().toggleClass('on');
			slider.parent().toggleClass('off');
			slider.trigger('change');

			toggle = slider.children('.memberdash-toggle');
			data = toggle.data('memberdash-ajax');
			states = toggle.data('states');

			if (null !== data && undefined !== data) {
				info_field = fn.ajax_show_indicator(slider);
				slider.addClass('ms-processing memberdash-loading');
				state = slider.hasClass('on');

				if (undefined !== states.active && state) {
					data.value = states.active;
				} else if (undefined !== states.inactive && !state) {
					data.value = states.inactive;
				} else {
					data.value = state;
				}

				// Allow fields to pre-process the data before sending it.
				if ('function' === typeof slider.data('before_ajax')) {
					data = slider.data('before_ajax')(data, slider);
				}

				slider.trigger('ms-ajax-start', [data, info_field, slider]);
				jQuery
					.post(window.ajaxurl, data, function (response) {
						const is_err = fn.ajax_error(response, info_field);
						if (is_err) {
							slider.toggleClass('on');
							slider.toggleClass('off');
						}

						info_field.removeClass('ms-processing');

						slider.removeClass('ms-processing memberdash-loading');
						slider.children('input').val(slider.hasClass('on'));
						data.response = response;
						slider.trigger('ms-ajax-updated', [
							data,
							response,
							is_err,
						]);
						slider.trigger('ms-radio-slider-updated', [
							data,
							is_err,
						]);
						// Used for the add-on list (which is a WPSATCHELI module)
						slider.trigger('memberdash-radio-slider-updated', [
							data,
							is_err,
						]);
					})
					.always(function () {
						slider.trigger('ms-ajax-done', [
							data,
							info_field,
							slider,
						]);
					});
			} else {
				slider.children('input').val(slider.hasClass('on'));
			}
		}
	},

	dynamic_form_submit(ev, el) {
		let i,
			field_value,
			field_key,
			is_popup,
			info_field,
			popup,
			fn = window.ms_functions,
			me = jQuery(el),
			fields = me.serializeArray(),
			data = {};

		ev.preventDefault();

		// Convert the form-data into an object.
		for (i = 0; i < fields.length; i += 1) {
			field_key = fields[i].name;
			field_value = fields[i].value;

			if (undefined === data[field_key]) {
				data[field_key] = field_value;
			} else {
				if (!(data[field_key] instanceof Array)) {
					data[field_key] = [data[field_key]];
				}
				data[field_key].push(field_value);
			}
		}
		data.action = 'ms_submit';

		popup = me.parents('.memberdash-popup');
		is_popup = popup.length;
		if (!is_popup) {
			info_field = fn.ajax_show_indicator(me);
		} else {
			popup.addClass('memberdash-loading');
		}

		jQuery(document).trigger('ms-ajax-form-send', [
			me,
			data,
			is_popup,
			info_field,
		]);

		jQuery.post(window.ajaxurl, data, function (response) {
			const is_err = fn.ajax_error(response, info_field);

			if (popup.length) {
				popup.removeClass('memberdash-loading');
			}

			if (is_err) {
				// Reset the input control to previous value...
			} else if (is_popup) {
				fn.close_dialogs();
			}
			jQuery(document).trigger('ms-ajax-form-done', [
				me,
				response,
				is_err,
				data,
				is_popup,
				info_field,
			]);
		});
		return false;
	},

	/**
	 * Receives the ajax response string and checks if the response starts with
	 * an error code.
	 * An error code is a negative number at the start of the response.
	 *
	 * Returns true when an error code is found.
	 * When no numeric code is found the function returns false (no error)
	 *
	 * @param  response
	 * @param  info_field
	 */
	ajax_error(response, info_field) {
		let code = 0,
			parts = [],
			msg = '',
			fn = window.ms_functions;

		if (isNaN(response)) {
			parts = response.split(':', 2);
			if (!isNaN(parts[0])) {
				code = parts[0];
			}
			if (undefined !== parts[1]) {
				msg = parts[1];
			}
		} else {
			code = response;
		}

		if (code < 0) {
			// Negative number as response code is an error-indicator.
			if (info_field) {
				info_field.removeClass('okay').addClass('error');
				info_field.find('.err-code').text(msg);

				// Automatically hide success message after a longer timeout.
				fn.ajax_hide_message(8000, info_field);
			}
			return true;
		}
		if (info_field) {
			// No response code or positive number is interpreted as success.
			info_field.removeClass('error').addClass('okay');
			info_field.find('.err-code').text('');

			// Automatically hide success message after short timeout.
			fn.ajax_hide_message(4000, info_field);
		}
		return false;
	},

	/**
	 * Displays the ajax progress message and cancels the hide-timeout if required.
	 *
	 * @param  field
	 */
	ajax_show_indicator(field) {
		let info_field;

		info_field = field.closest('.ms-save-text-wrapper');

		if (null !== info_field.data('msg_timeout')) {
			window.clearTimeout(info_field.data('msg_timeout'));
			info_field.data('msg_timeout', null);
		}

		info_field.addClass('ms-processing');
		info_field.removeClass('error okay');
		return info_field;
	},

	/**
	 * Hides the ajax response message after a short timeout
	 *
	 * @param  timeout
	 * @param  info_field
	 */
	ajax_hide_message(timeout, info_field) {
		let tmr_id;

		if (isNaN(timeout)) {
			timeout = 4000;
		}
		if (timeout < 0) {
			timeout = 0;
		}

		tmr_id = window.setTimeout(function () {
			const field = info_field;
			field.removeClass('error okay');
		}, timeout);

		info_field.data('msg_timeout', tmr_id);
	},

	/**
	 * Select the whole content inside the specified element.
	 *
	 * @param  el
	 */
	select_all(el) {
		let range;
		el = jQuery(el)[0];

		if (document.selection) {
			range = document.body.createTextRange();
			range.moveToElementText(el);
			range.select();
		} else if (window.getSelection) {
			range = document.createRange();
			range.selectNode(el);
			// eslint-disable-next-line @wordpress/no-global-get-selection
			window.getSelection().addRange(range);
		}
	},

	/**
	 * Toggle the accordion box state
	 *
	 * @param  ev
	 * @param  el
	 */
	toggle_box(ev, el) {
		const me = jQuery(el),
			box = me.closest('.ms-settings-box');

		if (box.hasClass('static')) {
			return false;
		}
		if (box.hasClass('closed')) {
			box.removeClass('closed').addClass('open');
		} else {
			box.removeClass('open').addClass('closed');
		}
	},

	/**
	 * Toggle datepicker when user clicks on icon.
	 *
	 * @param  el
	 */
	toggle_datepicker(el) {
		const me = jQuery(el),
			dp = me
				.closest('.memberdash-datepicker-wrapper')
				.find('.memberdash-datepicker');

		dp.datepicker('show');
	},

	/**
	 * Tag-Selector component:
	 * Add new tag to the selected-tags list.
	 *
	 * @param  ev
	 */
	tag_selector_add(ev) {
		const fn = window.ms_functions,
			me = jQuery(this).closest('.memberdash-tag-selector-wrapper'),
			el_src = me.find('select.memberdash-tag-source'),
			el_dst = me.find('select.memberdash-tag-data'),
			list = el_dst.val() || [];

		if (!el_src.val().length) {
			return;
		}

		list.push(el_src.val());
		el_dst.val(list).trigger('change');
		el_src.val('').trigger('change');

		fn.tag_selector_refresh_source(ev, this);
	},

	/**
	 * Tag-Selector component:
	 * Disable or Enable options in the source list.
	 *
	 * @param  ev
	 * @param  el
	 */
	tag_selector_refresh_source(ev, el) {
		let i = 0,
			item = null,
			me = jQuery(el).closest('.memberdash-tag-selector-wrapper'),
			el_src = me.find('select.memberdash-tag-source'),
			el_src_items = el_src.find('option'),
			el_dst = me.find('select.memberdash-tag-data'),
			list = el_dst.val() || [];

		for (i = 0; i < el_src_items.length; i += 1) {
			item = jQuery(el_src_items[i]);
			if (-1 !== jQuery.inArray(item.val(), list)) {
				item.prop('disabled', true);
			} else {
				item.prop('disabled', false);
			}
		}
		el_src.trigger('change');
	},

	/**
	 * Reload the current page.
	 */
	reload() {
		window.location.reload();
	},

	/**
	 * Load a popup dialog via ajax.
	 *
	 * @param  ev
	 */
	show_dialog(ev) {
		let me = jQuery(this),
			data = {},
			manual_data;

		ev.preventDefault();

		manual_data = me.attr('data-ms-data');
		if (undefined !== manual_data) {
			try {
				data = jQuery.parseJSON(manual_data);
			} catch (err) {
				data = {};
			}
		}

		data.action = 'ms_dialog';
		data.dialog = me.attr('data-ms-dialog');
		jQuery(document).trigger('ms-load-dialog', [data]);
		me.addClass('memberdash-loading');

		jQuery.post(window.ajaxurl, data, function (response) {
			let resp = false;

			me.removeClass('memberdash-loading');

			try {
				resp = jQuery.parseJSON(response);
			} catch (err) {
				resp = false;
			}

			resp.title = resp.title || 'Dialog';
			resp.height = resp.height || 100;
			resp.width = resp.width > 0 ? resp.width : undefined;
			resp.content = resp.content || '';
			resp.modal = resp.modal || true;

			memberdashLi
				.popup()
				.modal(true, !resp.modal)
				.title(resp.title)
				.size(resp.width, resp.height)
				.content(resp.content)
				.show();
		});

		return false;
	},

	/**
	 * Closes all open dialogs.
	 */
	close_dialogs() {
		let id,
			popups = memberdashLi.popups();

		for (id in popups) {
			popups[id].close();
		}
	},

	/**
	 * Update the view-counter when protection inside a view list-table is changed
	 *
	 * @param  event
	 * @param  data
	 * @param  is_err
	 */
	// eslint-disable-next-line no-unused-vars
	update_view_count(event, data, is_err) {
		let me = jQuery(this),
			table = me.closest('.wp-list-table'),
			form = table.closest('form'),
			box = form.parent(),
			views = box.find('.subsubsub').first(),
			el_open = views.find('.has_access .count'),
			el_locked = views.find('.no_access .count'),
			num_open = parseInt(el_open.text().replace(/\D/, '')),
			num_locked = parseInt(el_locked.text().replace(/\D/, ''));

		if (isNaN(num_open)) {
			num_open = 0;
		}
		if (isNaN(num_locked)) {
			num_locked = 0;
		}

		if (data.value) {
			num_locked -= 1;
			num_open += 1;
		} else {
			num_locked += 1;
			num_open -= 1;
		}

		if (num_open < 0) {
			num_open = 0;
		}
		if (num_locked < 0) {
			num_locked = 0;
		}

		if (num_open === 0) {
			el_open.text('');
		} else {
			el_open.text('(' + num_open + ')');
		}

		if (num_locked === 0) {
			el_locked.text('');
		} else {
			el_locked.text('(' + num_locked + ')');
		}
	},

	// Submit a form from outside the form tag:
	// <span class="ms-submit-form" data-form="class-of-the-form">Submit</span>
	submit_form() {
		const me = jQuery(this),
			selector = me.data('form'),
			form = jQuery('form.' + selector);

		if (form.length) {
			form.submit();
		}
	},
	/**
	 * Check if there is any gateway enabled to prevent the creation of the paid Membership without a gateway. Validation
	 * takes place by checking the server for active gateway.
	 *
	 * @since 1.0.2
	 *
	 * @param  ev The event that triggered this function.
	 * @param  el The current element where the event was triggered.
	 *
	 * @return {boolean} False to prevent the event bubbling up. Otherwise, the form submit will be triggered.
	 */
	check_async_gateway(ev, el) {
		const me = jQuery(el).closest('form'),
			gateway_check_async = me.find('#gateway_check_async'),
			nonce = me.find('#_wpnonce');

		// Bail if element was not found by jQuery.
		if (!gateway_check_async.length) {
			return false;
		}

		// Bail if element was not found by jQuery.
		if (gateway_check_async.val() === 'no') {
			return false;
		}

		ev.preventDefault();

		if (gateway_check_async.val() === 'yes') {
			const params = {
				action: 'verify_enabled_gateways',
				action_verify: 'save_payment_settings',
				nonce: nonce.val(),
			};
			jQuery
				.when(
					jQuery.ajax({
						url: window.ajaxurl,
						data: params,
						type: 'GET',
						dataType: 'json',
					})
				)
				.then(function (response) {
					// If response.data.invalid_request is set, then the nonce is invalid
					if (
						!response.success &&
						response.data.invalid_request !== 'undefined' &&
						response.data.invalid_request === 'yes'
					) {
						return false;
					}

					if (!response.success) {
						args = {
							message: response.data.message,
						};
						// TODO: This will change for using a different modal and let the user dismiss it but will allow to continue.
						memberdashLi.message(args);
						return false;
					} else {
						me.submit();
					}
				});
		}
	},
	/**
	 * Validate that both dates are set when the payment type is "One payment for date range access" and also when the
	 * date range is valid. A date range is valid if the start date is older than the end date.
	 *
	 * @since 1.0.2
	 *
	 * @param {Event} ev The event that triggered this function.
	 *
	 * @return {boolean} False to prevent the event bubbling up. True if we don't need to validate the date.
	 */
	check_date_range(ev) {
		const select_payment_type = jQuery(
			'.ms-payment-form select#payment_type'
		);
		const date_range_container_obj = jQuery('.ms-payment-type-date-range');
		const settings_footer_error_obj = jQuery(
			'.memberdash-save-settings-footer.memberdash-validation-error'
		);
		// We are not in the membership configuration.
		if (select_payment_type.length === 0) {
			return true;
		}
		// The selected payment type is not "One payment for date range access".
		if (select_payment_type.val() !== 'date-range') {
			return true;
		}

		// Hide settings footer error message.
		settings_footer_error_obj.hide();

		// Create the error element to use it below
		let date_range_error_obj = mdAppendErrorBox(date_range_container_obj);

		// If the error element is false then we prevent the validations to continue.
		if (!date_range_error_obj) {
			return false;
		}

		const start_date = jQuery(
			'.ms-payment-form input#period_date_start'
		).val();
		const end_date = jQuery('.ms-payment-form input#period_date_end').val();

		// Start date or end date is empty.
		if (
			jQuery.trim(start_date).length === 0 ||
			jQuery.trim(end_date).length === 0
		) {
			ev.preventDefault();
			date_range_error_obj.text(ms_data.lang.msg_incomplete_date_range);
			date_range_error_obj.show();

			settings_footer_error_obj.text(
				ms_data.lang.msg_missing_required_field
			);
			settings_footer_error_obj.show();
			return false;
		}

		const date_start = new Date(start_date);
		const date_end = new Date(end_date);

		if (
			date_start.getTime() > date_end.getTime() ||
			date_start.getTime() === date_end.getTime()
		) {
			ev.preventDefault();
			date_range_error_obj.text(ms_data.lang.msg_incorrect_date_range);
			date_range_error_obj.show();

			settings_footer_error_obj.text(
				ms_data.lang.msg_missing_required_field
			);
			settings_footer_error_obj.show();
			return false;
		}

		return true;
	},
	/**
	 * Validate that the payment type if selected and is different from 'Please select a payment type'.
	 *
	 * @since 1.0.2
	 *
	 * @param {Event} ev The event that triggered this function.
	 *
	 * @return {boolean} False to prevent the event bubbling up. True if the payment type is not set or if different from none.
	 */
	check_payment_type(ev) {
		const select_payment_type_obj = jQuery(
			'.ms-payment-form select#payment_type'
		);
		// The -wrapper is dynamically appended. The class 'payment_type_container' is defined in the view configuration.
		const payment_select_wrapper_obj = jQuery(
			'.payment_type_container-wrapper'
		);
		const settings_footer_error_obj = jQuery(
			'.memberdash-save-settings-footer.memberdash-validation-error'
		);

		// We are not in the membership configuration.
		if (select_payment_type_obj.length === 0) {
			return true;
		}

		// Bail since the selection is different from "Please select a payment type".
		if (select_payment_type_obj.val() !== 'none') {
			return true;
		}

		// Create the error element to use it below
		let payment_type_error_obj = mdAppendErrorBox(
			payment_select_wrapper_obj
		);

		// If the error element is false then we prevent the validations to continue.
		if (!payment_type_error_obj) {
			return false;
		}

		// The selected payment type is different from "Please select a payment type".
		ev.preventDefault();
		payment_type_error_obj.text(ms_data.lang.msg_payment_type_missing);
		payment_type_error_obj.show();

		settings_footer_error_obj.text(ms_data.lang.msg_missing_required_field);
		settings_footer_error_obj.show();

		return false;
	},
	/**
	 * Validate that the payment amount is greater than 0 and prompts the user to set a correct price.
	 *
	 * @since 1.0.2
	 *
	 * @param {Event} ev The event that triggered this function.
	 *
	 * @return {boolean} False to prevent the event bubbling up. True if price object is not set or price is greater than 0.
	 */
	check_payment_amount(ev) {
		const price_obj = jQuery('.ms-payment-form input#price');
		// The -wrapper is dynamically appended. The class 'price_container' is defined in the view configuration.
		const price_wrapper_obj = jQuery(
			'.ms-payment-form .price_container-wrapper'
		);
		const settings_footer_error_obj = jQuery(
			'.memberdash-save-settings-footer.memberdash-validation-error'
		);

		// We are not in the membership configuration.
		if (price_obj.length === 0) {
			return true;
		}

		let price = parseFloat(price_obj.val()).toFixed(2);

		// Bail since the price is greater than 0.
		if (price > 0.0) {
			return true;
		}

		// Create the error element to use it below
		let payment_type_error_obj = mdAppendErrorBox(price_wrapper_obj);

		// If the error element is false then we prevent the validations to continue.
		if (!payment_type_error_obj) {
			return false;
		}

		// The selected payment type is different from "Please select a payment type".
		ev.preventDefault();
		payment_type_error_obj.text(ms_data.lang.msg_price_zero);
		payment_type_error_obj.show();

		settings_footer_error_obj.text(ms_data.lang.msg_missing_required_field);
		settings_footer_error_obj.show();

		return false;
	},
	/**
	 * Hides the error messages under the footer section and where the error element was created.
	 *
	 * @since 1.0.2
	 */
	hide_error_messages() {
		jQuery(
			'.memberdash-validation-error.memberdash-validation--error-box'
		).hide();
		jQuery(
			'.memberdash-save-settings-footer.memberdash-validation-error'
		).hide();
	},
	/**
	 * Disable the Finish button if an error was triggered by the user.
	 *
	 * @since 1.0.2
	 *
	 * @returns {boolean} False if the jQuery elements is not found. Void otherwise.
	 */
	block_finish_button() {
		const finish_btn_obj = jQuery('form button.memberdash-submit');
		// We are not in the membership configuration.
		if (finish_btn_obj.length === 0) {
			return false;
		}

		finish_btn_obj.prop('disabled', true);
	},
	/**
	 * Enable the Finish button after the all validations are completed or
	 * if the user has updated the field(s) that disabled the button.
	 *
	 * @since 1.0.2
	 *
	 * @returns {boolean} False if the jQuery elements is not found. Void otherwise.
	 */
	enable_finish_button() {
		const finish_btn_obj = jQuery('form button.memberdash-submit');
		// We are not in the membership configuration.
		if (finish_btn_obj.length === 0) {
			return false;
		}

		finish_btn_obj.prop('disabled', false);
	},
};

// Add our own Datepicker-init function which extends the jQuery Datepicker.
jQuery.fn.ms_datepicker = function (args) {
	let bs_callback = null,
		fn = window.ms_functions,
		config = jQuery.extend(fn.dp_config, args);

	if ('function' === typeof config.beforeShow) {
		bs_callback = config.beforeShow;
	}

	config.beforeShow = function (input, inst) {
		if (undefined !== inst && undefined !== inst.dpDiv) {
			jQuery(inst.dpDiv).addClass(config.custom_class);
		}

		window.setTimeout(function () {
			jQuery(inst.dpDiv).css({ zIndex: '10' });
		}, 20);

		if (null !== bs_callback) {
			bs_callback(input, inst);
		}
	};

	return this.each(function () {
		jQuery(this).datepicker(config);
	});
};

/**
 * Do general initialization:
 * Hook up various events with the plugin callback functions.
 */
jQuery(document).ready(function () {
	const fn = window.ms_functions;

	jQuery('body')
		// Checks if there is any active payment gateway requesting the server.
		.on('click', 'form button.memberdash-submit', function (ev) {
			/**
			 * Hide any error messages. If there are any they will be made visible by the function
			 * that triggered them.
			 */
			fn.hide_error_messages();
			fn.block_finish_button();

			if (
				fn.check_date_range(ev) === true &&
				fn.check_payment_type(ev) &&
				fn.check_payment_amount(ev)
			) {
				fn.check_async_gateway(ev, this);

				fn.enable_finish_button();
			}
		})
		// Toggle radio-sliders on click.
		.on('click', '.memberdash-radio-slider', function () {
			fn.radio_slider_ajax_update(this);
		})
		// Toggle accordion boxes on click.
		.on('click', '.ms-settings-box .handlediv', function (ev) {
			fn.toggle_box(ev, this);
		})
		// Toggle datepickers when user clicks on icon.
		.on(
			'click',
			'.memberdash-datepicker-wrapper .memberdash-icon',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				fn.toggle_datepicker(this);
			}
		)
		// Initialize the tag-select components.
		.on(
			'select2-opening',
			'.memberdash-tag-selector-wrapper .memberdash-tag-data',
			function (ev) {
				ev.preventDefault();
			}
		)
		.on(
			'change',
			'.memberdash-tag-selector-wrapper .memberdash-tag-data',
			function (ev) {
				fn.tag_selector_refresh_source(ev, this);
			}
		)
		.on(
			'click',
			'.memberdash-tag-selector-wrapper .memberdash-tag-button',
			fn.tag_selector_add
		)
		// Ajax-Submit data when ms-ajax-update fields are changed.
		.on(
			'change',
			'input.memberdash-ajax-update:not([type=number]), select.memberdash-ajax-update, textarea.memberdash-ajax-update',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				// Hides validation errors
				fn.hide_error_messages();
				fn.enable_finish_button();
				fn.ajax_update(this);
			}
		)
		.on(
			'blur',
			'input.memberdash-ajax-update[type="number"]',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				// Hides validation errors
				fn.hide_error_messages();
				fn.enable_finish_button();
				const el = jQuery(this);
				if (el.val() !== el.data('val')) {
					el.data('val', el.val());
					fn.ajax_update(this);
				}
			}
		)
		.on(
			'change',
			'input.memberdash-ajax-update[type="number"]',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				const el = jQuery(this);
				el.focus();
			}
		)
		.on(
			'focus',
			'input.memberdash-ajax-update[type="number"]',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				const el = jQuery(this);
				if (undefined === el.data('val')) {
					el.data('val', el.val());
				}
			}
		)
		.on(
			'click',
			'button.memberdash-ajax-update',
			// eslint-disable-next-line no-unused-vars
			function (ev) {
				fn.ajax_update(this);
			}
		)
		.on('submit', 'form.memberdash-ajax-update', function (ev) {
			fn.dynamic_form_submit(ev, this);
		})
		// Initialize popup dialogs.
		.on('click', '[data-ms-dialog]', fn.show_dialog)
		// Update counter of the views in rule list-tables
		.on(
			'ms-radio-slider-updated',
			'.wp-list-table.rules .memberdash-radio-slider',
			fn.update_view_count
		)
		.on('click', '.ms-submit-form', fn.submit_form);

	// Select all text inside <code> tags on click.
	jQuery('.ms-wrap').on('click', 'code', function () {
		fn.select_all(this);
	});

	// close dismissible notices
	jQuery('.notice-dismiss').on('click', function () {
		let that = this;
		let noticeId = jQuery(this).data('noticeId');
		jQuery
			.post(
				window.ajaxurl,
				'action=ms_admin_notice_dismiss&notice_id=' + noticeId,
				null,
				'json'
			)
			.always(function () {
				jQuery(that).parent().fadeOut();
			});
	});

	fn.init('body');

	// Add a global CSS class to the html tag
	jQuery('html').addClass('ms-html');
});

/*global ms_inline_editor:false */

/* Membership Inline Editor */
(function () {
	let quickedit = null,
		the_item = null,
		template = null;

	window.ms_inline_editor = {
		init() {
			template = jQuery('.ms-wrap #inline-edit');

			/*
			 * Remove the form-template from the DOM so it does not mess with
			 * the containing form...
			 */
			template.detach();

			// prepare the edit rows
			template.keyup(function (e) {
				if (e.which === 27) {
					return ms_inline_editor.revert();
				}
			});

			jQuery('a.cancel', template).click(function () {
				return ms_inline_editor.revert();
			});
			jQuery('a.save', template).click(function () {
				return ms_inline_editor.save(this);
			});
			jQuery('td', template).keydown(function (e) {
				if (e.which === 13) {
					return ms_inline_editor.save(this);
				}
			});

			// add events
			jQuery('.ms-wrap .wp-list-table').on(
				'click',
				'a.editinline',
				function () {
					ms_inline_editor.edit(this);
					return false;
				}
			);
		},

		edit(id) {
			let item_data, row_data;

			ms_inline_editor.revert();

			if (typeof id === 'object') {
				id = ms_inline_editor.get_id(id);
			}

			// add the new blank row
			quickedit = template.clone(true);
			the_item = jQuery('#item-' + id);

			jQuery('td', quickedit).attr(
				'colspan',
				jQuery('.widefat:first thead th:visible').length
			);

			if (the_item.hasClass('alternate')) {
				quickedit.addClass('alternate');
			}
			the_item.hide().after(quickedit);

			// populate the data
			row_data = {};
			item_data = the_item.find('.inline_data');
			item_data.children().each(function () {
				const field = jQuery(this),
					inp_name = field.attr('class'),
					input = quickedit.find(':input[name="' + inp_name + '"]'),
					label = quickedit.find('.lbl-' + inp_name);

				row_data[inp_name] = field.text();
				if (input.length) {
					input.val(row_data[inp_name]);
				}
				if (label.length) {
					label.text(row_data[inp_name]);
				}
			});
			jQuery(document).trigger('ms-inline-editor', [
				quickedit,
				the_item,
				row_data,
			]);

			quickedit
				.attr('id', 'edit-' + id)
				.addClass('inline-editor')
				.show();
			quickedit.find(':input:visible').first().focus();

			return false;
		},

		save(id) {
			let params;

			if (typeof id === 'object') {
				id = ms_inline_editor.get_id(id);
			}

			quickedit.find('td').addClass('memberdash-loading');
			params = quickedit.find(':input').serialize();

			// make ajax request
			jQuery.post(
				window.ajaxurl,
				params,
				function (response) {
					quickedit.find('td').removeClass('memberdash-loading');

					if (response) {
						if (-1 !== response.indexOf('<tr')) {
							the_item.remove();
							the_item = jQuery(response);
							quickedit.before(the_item).remove();
							the_item.hide().fadeIn();

							// Update the "alternate" class
							ms_inline_editor.update_alternate(the_item);

							jQuery(document).trigger(
								'ms-inline-editor-updated',
								[the_item]
							);
						} else {
							response = response.replace(/<.[^<>]*?>/g, '');
							quickedit.find('.error').html(response).show();
						}
					} else {
						quickedit
							.find('.error')
							.html(ms_data.lang.quickedit_error)
							.show();
					}

					if (the_item.prev().hasClass('alternate')) {
						the_item.removeClass('alternate');
					}
				},
				'html' // Tell jQuery that we expect HTML code as response
			);

			return false;
		},

		revert() {
			if (quickedit) {
				quickedit.remove();
				the_item.show();

				quickedit = null;
				the_item = null;
			}

			return false;
		},

		update_alternate(element) {
			let ind,
				len,
				row,
				tbody = jQuery(element).closest('tbody'),
				rows = tbody.find('tr:visible');

			for (ind = 0, len = rows.length; ind < len; ind++) {
				row = jQuery(rows[ind]);
				if (ind % 2 === 0) {
					row.addClass('alternate');
				} else {
					row.removeClass('alternate');
				}
			}
		},

		get_id(obj) {
			const id = jQuery(obj).closest('tr').attr('id'),
				parts = id.split('-');

			return parts[parts.length - 1];
		},
	};

	// Initialize the inline editor

	jQuery(function () {
		ms_inline_editor.init();
	});
})();

/* Tooltip component */
jQuery(function init_tooltip() {
	// Hide all tooltips when user clicks anywhere outside a tooltip element.
	jQuery(document).click(function () {
		function hide_tooltip() {
			const el = jQuery(this),
				stamp = el.attr('timestamp'),
				parent = jQuery(
					'.memberdash-tooltip-wrapper[timestamp="' + stamp + '"]'
				).first();

			el.hide();

			// Move tooltip back into the DOM hierarchy
			el.appendTo(jQuery(parent));
		}

		// Hide multiple tooltips
		jQuery('.memberdash-tooltip[timestamp]').each(hide_tooltip);
	});

	// Hide single tooltip when Close-Button is clicked.
	jQuery('.memberdash-tooltip-button').click(function () {
		const el = jQuery(this),
			parent = el.parents('.memberdash-tooltip'),
			stamp = jQuery(parent).attr('timestamp'),
			super_parent = jQuery(
				'.memberdash-tooltip-wrapper[timestamp="' + stamp + '"]'
			).first();

		jQuery(parent).hide();

		// Move tooltip back into the DOM hierarchy
		jQuery(parent).appendTo(jQuery(super_parent));
	});

	// Don't propagate click events inside the tooltip to the document.
	jQuery('.memberdash-tooltip').click(function (e) {
		e.stopPropagation();
	});

	// Toggle a tooltip
	jQuery('.memberdash-tooltip-info').click(function (event) {
		let parent,
			stamp,
			sibling,
			newpos,
			tooltip,
			el = jQuery(this);

		el.toggleClass('open');

		if (!el.hasClass('open')) {
			// HIDE
			parent = el.parents('.memberdash-tooltip-wrapper');
			stamp = jQuery(parent).attr('timestamp');
			sibling = jQuery(
				'.memberdash-tooltip[timestamp="' + stamp + '"]'
			).first();

			jQuery(sibling).hide();

			// Move tooltip back into the DOM hierarchy
			jQuery(sibling).appendTo(jQuery(parent));
		} else {
			// SHOW
			el.parents('.memberdash-tooltip-wrapper').attr(
				'timestamp',
				event.timeStamp
			);
			event.stopPropagation();
			tooltip = el.siblings('.memberdash-tooltip');

			tooltip.attr('timestamp', event.timeStamp);

			// Move tooltip out of the hierarchy...
			// This is to avoid situations where large tooltips are cut off by parent elements.
			newpos = el.offset();
			tooltip.appendTo('#wpcontent');
			tooltip.css({
				left: newpos.left + 25,
				top: newpos.top - 40,
			});

			tooltip.fadeIn(300);
		}
	});
});

window.ms_init.controller_adminbar = function init() {
	function change_membership(ev) {
		// Get selected Membership ID
		const membership_id = ev.currentTarget.value;
		// Get selected Membership nonce
		const nonce = jQuery('#wpadminbar #view-as-selector')
			.find('option[value="' + membership_id + '"]')
			.attr('nonce');

		// Update hidden fields
		jQuery('#wpadminbar #ab-membership-id').val(membership_id);
		jQuery('#wpadminbar #view-site-as #_wpnonce').val(nonce);

		// Submit form
		jQuery('#wpadminbar #view-site-as').submit();
	}

	// eslint-disable-next-line no-unused-vars
	jQuery('#wp-admin-bar-membership-simulate')
		.find('a')
		.click(function (e) {
			jQuery('#wp-admin-bar-membership-simulate')
				.removeClass('hover')
				.find('> div')
				.filter(':first-child')
				.html(ms_data.switching_text);
		});

	jQuery('.ms-date').ms_datepicker();

	jQuery('#wpadminbar #view-site-as')
		.parents('#wpadminbar')
		.addClass('simulation-mode');

	jQuery('#wpadminbar #view-as-selector').change(change_membership);
};

window.ms_init.view_help = function init() {
	function toggle_section() {
		const me = jQuery(this),
			block = me.parents('.ms-help-box').first(),
			details = block.find('.ms-help-details');

		details.toggle();
	}

	jQuery('.ms-help-toggle').click(toggle_section);
};

window.ms_init.view_billing_edit = function init() {
	const args = {
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			name: 'required',
			user_id: {
				required: true,
				min: 1,
			},
			membership_id: {
				required: true,
				min: 1,
			},
			amount: {
				required: true,
				min: 0,
			},
			due_date: {
				required: true,
				dateISO: true,
			},
		},
	};

	jQuery('.ms-form').validate(args);
};

window.ms_init.view_billing_transactions = function init() {
	let table = jQuery(
			'.wp-list-table.transactions, .wp-list-table.transaction_matches'
		),
		frm_match = jQuery('.transaction-matching'),
		btn_clear = table.find('.action-clear'),
		btn_ignore = table.find('.action-ignore'),
		btn_link = table.find('.action-link'),
		btn_retry = table.find('.action-retry'),
		btn_match = frm_match.find('.action-match'),
		retry_transactions,
		show_link_dialog,
		append_option;

	// Handle the "Save Matching" action.
	// eslint-disable-next-line no-unused-vars
	function save_matching(ev) {
		const ajax = memberdashLi.ajax(),
			data = ajax.extract_data(frm_match);

		frm_match.addClass('memberdash-loading');
		jQuery
			.post(
				window.ajaxurl,
				data,
				function (response) {
					if (response.success) {
						memberdashLi.message(response.data.message);

						// Start to process the transactions.
						retry_transactions();
					}
				},
				'json'
			)
			.always(function () {
				frm_match.removeClass('memberdash-loading');
			});

		return false;
	}

	// Retry to process all displayed transactions.
	retry_transactions = function () {
		let rows = table.find('.item'),
			nonce = frm_match.find('.retry_nonce').val(),
			progress = memberdashLi.progressbar(),
			counter = 0,
			ajax_data = {},
			queue = [];

		ajax_data.action = 'transaction_retry';
		ajax_data._wpnonce = nonce;

		// Collect all log-IDs in the queue.
		rows.each(function () {
			const row = jQuery(this),
				row_id = row.attr('id').replace(/^item-/, '');

			row.find('.column-note').addClass('memberdash-loading');
			queue.push(row_id);
		});

		progress.value(0);
		progress.max(queue.length);
		progress.$().insertBefore(frm_match);
		frm_match.hide();

		// Process the queue.
		function process_queue() {
			if (!queue.length) {
				progress.$().remove();
				return;
			}

			const id = queue.shift(),
				data = jQuery.extend({}, ajax_data),
				row = table.find('#item-' + id);

			data.id = id;
			counter += 1;
			progress.value(counter);

			jQuery
				.post(
					window.ajaxurl,
					data,
					function (response) {
						if (response.success && response.data.desc) {
							row.removeClass('log-err log-ignore log-ok');
							row.addClass('log-' + response.data.state);
							row.find('.column-note .txt').text(
								response.data.desc
							);
						}

						window.setTimeout(function () {
							process_queue();
						}, 1);
					},
					'json'
				)
				.always(function () {
					row.find('.column-note').removeClass('memberdash-loading');
				});
		}

		process_queue();
	};

	// Handle the "Reset" action.
	// eslint-disable-next-line no-unused-vars
	function clear_line(ev) {
		const cell = jQuery(this).closest('td'),
			nonce = cell.find('input[name=nonce_update]').val(),
			row = cell.closest('.item'),
			row_id = row.attr('id').replace(/^item-/, ''),
			data = {};

		if (!row.hasClass('log-ignore')) {
			return false;
		}

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'clear';

		cell.addClass('memberdash-loading');
		jQuery
			.post(
				window.ajaxurl,
				data,
				// eslint-disable-next-line no-unused-vars
				function (response) {
					row.removeClass('log-ignore is-manual').addClass('log-err');
				}
			)
			.always(function () {
				cell.removeClass('memberdash-loading');
			});

		return false;
	}

	// Handle the "Ignore" action.
	// eslint-disable-next-line no-unused-vars
	function ignore_line(ev) {
		const cell = jQuery(this).closest('td'),
			nonce = cell.find('input[name=nonce_update]').val(),
			row = cell.closest('.item'),
			row_id = row.attr('id').replace(/^item-/, ''),
			data = {};

		if (!row.hasClass('log-err')) {
			return false;
		}

		data.action = 'transaction_update';
		data._wpnonce = nonce;
		data.id = row_id;
		data.state = 'ignore';

		cell.addClass('memberdash-loading');
		jQuery
			.post(
				window.ajaxurl,
				data,
				// eslint-disable-next-line no-unused-vars
				function (response) {
					row.removeClass('log-err').addClass('log-ignore is-manual');
				}
			)
			.always(function () {
				cell.removeClass('memberdash-loading');
			});

		return false;
	}

	// Handle the "Retry" action.
	// eslint-disable-next-line no-unused-vars
	function retry_line(ev) {
		const cell = jQuery(this).closest('td'),
			nonce = cell.find('input[name=nonce_retry]').val(),
			row = cell.closest('.item'),
			row_id = row.attr('id').replace(/^item-/, ''),
			data = {};

		if (!row.hasClass('log-err') && !row.hasClass('log-ignore')) {
			return false;
		}

		data.action = 'transaction_retry';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass('memberdash-loading');
		jQuery
			.post(
				window.ajaxurl,
				data,
				function (response) {
					if (response.success && response.data.desc) {
						row.removeClass('log-err log-ignore log-ok');
						row.addClass('log-' + response.data.state);
						row.find('.column-note .txt').text(response.data.desc);
					}
				},
				'json'
			)
			.always(function () {
				cell.removeClass('memberdash-loading');
			});

		return false;
	}

	// Handle the "Link" action.
	// eslint-disable-next-line no-unused-vars
	function link_line(ev) {
		const cell = jQuery(this).closest('td'),
			nonce = cell.find('input[name=nonce_link]').val(),
			row = cell.closest('.item'),
			row_id = row.attr('id').replace(/^item-/, ''),
			data = {};

		if (!row.hasClass('log-err')) {
			return false;
		}

		data.action = 'transaction_link';
		data._wpnonce = nonce;
		data.id = row_id;

		cell.addClass('memberdash-loading');
		jQuery
			.post(window.ajaxurl, data, function (response) {
				if (response.length) {
					show_link_dialog(row, response);
				}
			})
			.always(function () {
				cell.removeClass('memberdash-loading');
			});

		return false;
	}

	// Display the Transaction-Link popup.
	show_link_dialog = function (row, data) {
		let sel_user,
			sel_subscription,
			sel_invoice,
			nonce_data,
			nonce_update,
			row_subscription,
			row_invoice,
			btn_submit,
			log_id,
			popup = memberdashLi.popup(),
			wnd = popup.$();

		popup.modal(true);
		popup.title(ms_data.lang.link_title);
		popup.content(data);
		popup.show();

		// Add event handlers inside the popup.
		sel_user = wnd.find('select[name=user_id]');
		sel_subscription = wnd.find('select[name=subscription_id]');
		sel_invoice = wnd.find('select[name=invoice_id]');
		row_subscription = wnd.find('.link-subscription');
		row_invoice = wnd.find('.link-invoice');
		nonce_data = wnd.find('input[name=nonce_link_data]');
		nonce_update = wnd.find('input[name=nonce_update]');
		log_id = wnd.find('input[name=log_id]');
		btn_submit = wnd.find('button[name=submit]');

		row_subscription.hide();
		row_invoice.hide();
		btn_submit.prop('disabled', true).addClass('disabled');

		function load_subscriptions() {
			let dataS,
				user_id = sel_user.val();

			if (isNaN(user_id) || user_id < 1) {
				row_invoice.find('.memberdash-label-after').hide();
				row_subscription.hide();
				row_invoice.hide();
				return false;
			}

			dataS = {
				action: 'transaction_link_data',
				_wpnonce: nonce_data.val(),
				get: 'subscriptions',
				for: user_id,
			};

			sel_subscription.empty();
			sel_invoice.empty();
			row_subscription.show().addClass('memberdash-loading');
			row_invoice.find('.memberdash-label-after').hide();
			row_invoice.hide();
			btn_submit.prop('disabled', true).addClass('disabled');

			jQuery
				.post(
					window.ajaxurl,
					dataS,
					function (response) {
						jQuery.each(response, function (val, label) {
							append_option(sel_subscription, val, label);
						});
					},
					'json'
				)
				.always(function () {
					row_subscription.removeClass('memberdash-loading');
				});
		}

		function load_invoices() {
			let dataI,
				subscription_id = sel_subscription.val();

			if (isNaN(subscription_id) || subscription_id < 1) {
				row_invoice.find('.memberdash-label-after').hide();
				row_invoice.hide();
				return false;
			}

			dataI = {
				action: 'transaction_link_data',
				_wpnonce: nonce_data.val(),
				get: 'invoices',
				for: subscription_id,
			};

			sel_invoice.empty();
			row_invoice.show().addClass('memberdash-loading');
			row_invoice.find('.memberdash-label-after').hide();
			btn_submit.prop('disabled', true).addClass('disabled');

			jQuery
				.post(
					window.ajaxurl,
					dataI,
					function (response) {
						window.console.log(response);
						jQuery.each(response, function (val, label) {
							window.console.log(val, label);
							append_option(sel_invoice, val, label);
						});
					},
					'json'
				)
				.always(function () {
					row_invoice.removeClass('memberdash-loading');
				});
		}

		function confirm_data() {
			const inv_id = sel_invoice.val();

			if (!isNaN(inv_id) && inv_id > 0) {
				row_invoice.find('.memberdash-label-after').show();
				btn_submit.prop('disabled', false).removeClass('disabled');
			} else {
				row_invoice.find('.memberdash-label-after').hide();
				btn_submit.prop('disabled', true).addClass('disabled');
			}
		}

		function save_link() {
			const dataL = {
				action: 'transaction_update',
				_wpnonce: nonce_update.val(),
				id: log_id.val(),
				link: sel_invoice.val(),
			};

			if (!dataL.link) {
				return false;
			}
			wnd.addClass('memberdash-loading');

			jQuery
				.post(window.ajaxurl, dataL, function (response) {
					if ('3' === response) {
						row.removeClass('log-err').addClass('log-ok is-manual');
						popup.close();
					}
				})
				.always(function () {
					wnd.removeClass('memberdash-loading');
				});
		}

		sel_user.change(load_subscriptions);
		sel_subscription.change(load_invoices);
		sel_invoice.change(confirm_data);
		btn_submit.click(save_link);

		if (!isNaN(sel_user.val()) && sel_user.val() > 0) {
			load_subscriptions();
		}
	};

	append_option = function (container, val, label) {
		if (typeof label === 'object') {
			const group = jQuery('<optgroup></optgroup>');
			group.attr('label', val);
			jQuery.each(label, function (subval, sublabel) {
				append_option(group, subval, sublabel);
			});
			container.append(group);
		} else {
			container.append(jQuery('<option></option>').val(val).html(label));
		}
	};

	btn_clear.click(clear_line);
	btn_ignore.click(ignore_line);
	btn_link.click(link_line);
	btn_retry.click(retry_line);
	btn_match.click(save_matching);
};

window.ms_init.view_member_date = function init() {
	jQuery('.ms-date').ms_datepicker();
};

window.ms_init.view_member_editor = function init() {
	let txt_username = jQuery('#username'),
		txt_email = jQuery('#email'),
		sel_user = jQuery('.ms-group-select #user_id'),
		btn_add = jQuery('#btn_create'),
		btn_select = jQuery('#btn_select'),
		payment_type = jQuery('#payment_type'),
		subscription_status = jQuery('#subscription-status'),
		chosen_options = {},
		validate_buttons;

	function validate_field(fieldname, field) {
		const value = field.val(),
			data = {},
			row = field.closest('.memberdash-wrapper');

		data.action = 'member_validate_field';
		data.field = fieldname;
		data.value = value;

		row.addClass('memberdash-loading');

		jQuery.post(window.ajaxurl, data, function (response) {
			const info = row.find('.memberdash-label-after');
			row.removeClass('memberdash-loading');

			if ('1' === response) {
				field.removeClass('invalid');
				field.addClass('valid');
				info.html('');
			} else {
				field.removeClass('valid');
				field.addClass('invalid');
				info.html(response);
			}

			validate_buttons();
		});
	}

	validate_buttons = function () {
		if (txt_username.hasClass('valid') && txt_email.hasClass('valid')) {
			btn_add.prop('disabled', false);
			btn_add.removeClass('disabled');
		} else {
			btn_add.prop('disabled', true);
			btn_add.addClass('disabled');
		}

		if (sel_user.val()) {
			btn_select.prop('disabled', false);
			btn_select.removeClass('disabled');
		} else {
			btn_select.prop('disabled', true);
			btn_select.addClass('disabled');
		}
	};

	txt_username.change(function () {
		validate_field('username', txt_username);
	});

	txt_email.change(function () {
		validate_field('email', txt_email);
	});

	sel_user.change(validate_buttons);

	chosen_options.minimumInputLength = 3;
	chosen_options.multiple = false;
	chosen_options.ajax = {
		url: window.ajaxurl,
		dataType: 'json',
		type: 'GET',
		delay: 100,
		data(params) {
			return {
				action: 'member_search',
				q: params.term,
				p: params.page,
			};
		},
		// eslint-disable-next-line no-unused-vars
		processResults(data, page) {
			return { results: data.items, more: data.more };
		},
	};

	sel_user.removeClass('memberdash-hidden');
	sel_user.memberdashSelect(chosen_options);

	validate_buttons();

	// If admin in going to cancel recurring subscription, show warning.
	subscription_status.on('change', function () {
		// If changed status is canceled and payment type is recurring.
		if (
			'canceled' === subscription_status.val() &&
			'recurring' === payment_type.val()
		) {
			// eslint-disable-next-line no-alert
			window.alert(window.ms_admin_text.recurring_cancel_warning);
		}
	});
};

window.ms_init.view_member_list = function init() {
	window.ms_init.memberships_column('.column-membership');
};

window.ms_init.view_membership_add = function init() {
	const chk_public = jQuery('input#public'),
		el_public = chk_public.closest('.opt'),
		chk_paid = jQuery('input#paid'),
		inp_name = jQuery('input#name'),
		el_name = inp_name.closest('.opt'),
		el_paid = chk_paid.closest('.opt');

	jQuery('#ms-choose-type-form').validate({
		onkeyup: false,
		errorClass: 'ms-validation-error',
		rules: {
			name: {
				required: true,
			},
		},
	});

	// Lock the options then guest membership is selected.
	jQuery('input[name="type"]')
		.click(function () {
			const types = jQuery('input[name="type"]'),
				current = types.filter(':checked'),
				cur_type = current.val();

			types
				.closest('.memberdash-radio-input-wrapper')
				.removeClass('active');
			current
				.closest('.memberdash-radio-input-wrapper')
				.addClass('active');

			if ('guest' === cur_type || 'user' === cur_type) {
				chk_public.prop('disabled', true);
				chk_paid.prop('disabled', true);
				inp_name.prop('readonly', true);
				el_public.addClass('disabled ms-locked');
				el_paid.addClass('disabled ms-locked');
				el_name.addClass('disabled ms-locked');
				inp_name.val(current.next('.memberdash-radio-caption').text());
			} else {
				chk_public.prop('disabled', false);
				chk_paid.prop('disabled', false);
				inp_name.prop('readonly', false);
				el_public.removeClass('disabled ms-locked');
				el_paid.removeClass('disabled ms-locked');
				el_name.removeClass('disabled ms-locked');
				inp_name.val('').focus();
			}
		})
		.filter(':checked')
		.trigger('click');

	// Cancel the wizard.
	jQuery('#cancel').click(function () {
		const me = jQuery(this);

		// Simply reload the page after the setting has been changed.
		me.on('ms-ajax-updated', function () {
			window.location = ms_data.initial_url;
		});
		ms_functions.ajax_update(me);
	});
};

window.ms_init.view_membership_list = function init() {
	const table = jQuery('#the-list-membership');

	function confirm_delete(ev) {
		let args,
			me = jQuery(this),
			row = me.parents('tr'),
			name = row.find('.column-name .the-name').text(),
			delete_url = me.attr('href');

		ev.preventDefault();
		args = {
			message: ms_data.lang.msg_delete.replace('%s', name),
			buttons: [ms_data.lang.btn_delete, ms_data.lang.btn_cancel],
			callback(key) {
				if (key === 0) {
					window.location = delete_url;
				}
			},
		};
		memberdashLi.confirm(args);

		return false;
	}

	table.on('click', '.delete a', confirm_delete);

	// Triggered after any Membership details were modified via the edit popup.
	// eslint-disable-next-line no-unused-vars
	jQuery(document).on(
		'ms-ajax-form-done',
		function (ev, form, response, is_err, data) {
			if (!is_err) {
				// reload the page to reflect the update
				window.location.reload();
			}
		}
	);
};

window.ms_init.bulk_delete_membership = function () {
	const delete_url = jQuery('.bulk_delete_memberships_button').attr('href');

	const serialize_membership_ids = function () {
		const membership_ids = [];
		jQuery('input.del_membership_ids:checked').each(function () {
			membership_ids.push(jQuery(this).val());
		});

		if (membership_ids.length > 0) {
			return delete_url + '&membership_ids=' + membership_ids.join('-');
		}
		return delete_url;
	};

	function confirm_bulk_delete(ev) {
		let args;

		ev.preventDefault();
		args = {
			message: ms_data.lang.msg_bulk_delete,
			buttons: [ms_data.lang.btn_delete, ms_data.lang.btn_cancel],
			callback(key) {
				if (key === 0) {
					window.location = serialize_membership_ids();
				}
			},
		};
		memberdashLi.confirm(args);

		return false;
	}

	jQuery('.bulk_delete_memberships_button').click(confirm_bulk_delete);
};

window.ms_init.metabox = function init() {
	const radio_protection = jQuery('.ms-protect-content'),
		radio_rule = jQuery('.ms-protection-rule'),
		box_access = jQuery('#ms-metabox-access-wrapper');

	if (radio_protection.hasClass('on')) {
		box_access.show();
	} else {
		box_access.hide();
	}

	jQuery('.dripped').click(function () {
		const tooltip = jQuery(this).children('.tooltip');
		tooltip.toggle(300);
	});

	// Callback after the base protection setting was changed.
	window.ms_init.ms_metabox_event = function (event, data) {
		jQuery('#ms-metabox-wrapper').replaceWith(data.response);
		window.ms_init.metabox();

		jQuery('.memberdash-radio-slider').click(function () {
			window.ms_functions.radio_slider_ajax_update(this);
		});

		// eslint-disable-next-line no-shadow
		radio_protection.on('ms-radio-slider-updated', function (event, data) {
			window.ms_init.ms_metabox_event(event, data);
		});
	};

	// Callback after a MemberDash detection setting was changed.
	// eslint-disable-next-line no-unused-vars
	function rule_updated(event, data) {
		const active = radio_rule.filter('.on,.memberdash-loading').length;

		if (active) {
			box_access.show();
			radio_protection.addClass('on');
		} else {
			box_access.hide();
			radio_protection.removeClass('on');
		}
	}

	radio_protection.on('ms-radio-slider-updated', function (event, data) {
		window.ms_init.ms_metabox_event(event, data);
	});
	radio_rule.on('ms-radio-slider-updated', function (event, data) {
		rule_updated(event, data);
	});
};

window.ms_init.view_membership_overview = function init() {
	jQuery('.memberdash-radio-slider').on(
		'ms-radio-slider-updated',
		function () {
			const object = this,
				obj = jQuery('#ms-membership-status');

			if (jQuery(object).hasClass('on')) {
				obj.addClass('ms-active');
			} else {
				obj.removeClass('ms-active');
			}
		}
	);

	// Triggered after the Membership details were modified via the edit popup.
	// eslint-disable-next-line no-unused-vars
	jQuery(document).on(
		'ms-ajax-form-done',
		function (ev, form, response, is_err, data) {
			if (!is_err) {
				// reload the page to reflect the update
				window.location.reload();
			}
		}
	);
};

window.ms_init.view_membership_urlgroup = function init() {
	let timeout = false;

	//global functions defined in ms-functions.js
	ms_functions.test_url = function () {
		if (timeout) {
			window.clearTimeout(timeout);
		}

		timeout = window.setTimeout(function () {
			let container = jQuery('#url-test-results-wrapper'),
				url = jQuery.trim(jQuery('#url_test').val()),
				rules = jQuery('#rule_value').val().split('\n'),
				is_regex = jQuery('#is_regex').val();

			if (is_regex === 'true' || is_regex === '1') {
				is_regex = true;
			} else {
				is_regex = false;
			}

			container.empty().hide();

			if (url === '') {
				return;
			}

			jQuery.each(rules, function (i, rule) {
				let line, result, reg, match;

				rule = jQuery.trim(rule);
				if (rule === '') {
					return;
				}

				line = jQuery('<div />').addClass('ms-rule-test');
				result = jQuery('<span />')
					.appendTo(line)
					.addClass('ms-test-result');

				match = false;
				if (is_regex) {
					reg = new RegExp(rule, 'i');
					match = reg.test(url);
				} else {
					match = url.indexOf(rule) >= 0;
				}

				if (match) {
					line.addClass('ms-rule-valid');
					result.text(ms_data.valid_rule_msg);
				} else {
					line.addClass('ms-rule-invalid');
					result.text(ms_data.invalid_rule_msg);
				}

				container.append(line);
			});

			if (!container.find('> div').length) {
				container.html('<div><i>' + ms_data.empty_msg + '</i></div>');
			}

			container.show();
		}, 500);
	};

	jQuery('#url_test, #rule_value').keyup(ms_functions.test_url);
};

window.ms_init.view_membership_payment = function init() {
	function payment_type() {
		const me = jQuery(this),
			block = me.closest('.ms-payment-form'),
			pay_type = me.val(),
			all_settings = block.find('.ms-payment-type-wrapper'),
			active_settings = block.find('.ms-payment-type-' + pay_type),
			pay_types_block = block.find('.ms-payment-types-wrapper');

		// Hides validation errors
		window.ms_functions.hide_error_messages();
		window.ms_functions.enable_finish_button();

		all_settings.hide();
		active_settings.show();

		if ('permanent' === pay_type) {
			pay_types_block.hide();
		} else if ('date-range' === pay_type) {
			let start_date_obj = jQuery(
				'.ms-payment-form input#period_date_start'
			);
			// Get the current date
			const currentDate = new Date();

			// Extract date components
			const year = currentDate.getFullYear();
			const month = (currentDate.getMonth() + 1)
				.toString()
				.padStart(2, '0');
			const day = currentDate.getDate().toString().padStart(2, '0');

			// Format the date as "YYYY-MM-DD"
			const formattedDate = `${year}-${month}-${day}`;

			if (jQuery.trim(start_date_obj.val()) === '') {
				// Set start date.
				start_date_obj.val(formattedDate);
			}
			pay_types_block.show();
		} else {
			pay_types_block.show();
		}

		// Toggle the Trial options

		if ('recurring' === pay_type) {
			jQuery('.ms-trial-wrapper').show();
		} else {
			jQuery('.ms-trial-wrapper').hide();
		}
	}

	function show_currency() {
		let currency = jQuery(this).val(),
			items = jQuery('.ms-payment-structure-wrapper');

		// Same translation table in:
		// -> class-ms-model-settings.php
		switch (currency) {
			case 'USD':
				currency = '$';
				break;
			case 'EUR':
				currency = '&euro;';
				break;
			case 'JPY':
				currency = '&yen;';
				break;
		}

		items.find('.memberdash-label-before').html(currency);
	}

	// eslint-disable-next-line no-unused-vars
	function toggle_trial(ev, data, is_err) {
		if (data.value) {
			jQuery('.ms-trial-period-details').show();
		} else {
			jQuery('.ms-trial-period-details').hide();
		}
	}

	function reload_page(ev, data, response, is_err) {
		if (!is_err) {
			jQuery('.ms-specific-payment-wrapper').addClass(
				'memberdash-loading'
			);
			window.location.reload();
		}
	}

	// Show the correct payment options
	jQuery('#payment_type').change(payment_type);
	jQuery('#payment_type').each(payment_type);

	// Update currency symbols in payment descriptions.
	jQuery('#currency').change(show_currency);

	jQuery('.memberdash-slider-trial_period_enabled').on(
		'ms-radio-slider-updated',
		toggle_trial
	);
	jQuery(document).on('ms-ajax-updated', '#enable_trial_addon', reload_page);
};

window.ms_init.view_protected_content = function init() {
	let table = jQuery('.wp-list-table'),
		sel_network_site = jQuery('#select-site'),
		setup_editor;

	window.ms_init.memberships_column('.column-access');

	// After a membership was added or removed. Check if there are dripped memberships.
	// eslint-disable-next-line no-unused-vars
	function check_if_dripped(ev) {
		let ind,
			membership_id,
			cell = jQuery(this).closest('.column-access'),
			row = cell.closest('tr.item'),
			list = cell.find('select.ms-memberships'),
			memberships = list.val(),
			num_dripped = 0;

		if (memberships && memberships.length) {
			for (ind in memberships) {
				membership_id = memberships[ind];
				if (undefined !== ms_data.dripped[membership_id]) {
					num_dripped += 1;
				}
			}
		}

		if (num_dripped > 1) {
			// Multiple dripped memberships. Inline editor required.
			row.addClass('ms-dripped');
		} else if (num_dripped === 1) {
			// Single dripped membership. No inline editor required.
			row.addClass('ms-dripped');
		} else {
			row.removeClass('ms-dripped');
		}
	}

	// Right before the inline editor is displayed. We can prepare the form.
	function populate_inline_editor(ev, editor, row, item_data) {
		let ind,
			len,
			memberships = row.find('select.ms-memberships option:selected'),
			form = editor.find('.dripped-form'),
			target = editor.find('.dynamic-form');

		for (ind = 0, len = memberships.length; ind < len; ind++) {
			const item = jQuery(memberships[ind]),
				membership_id = item.val(),
				color = item.data('color'),
				form_row = form.clone(false),
				base = 'ms_' + membership_id;

			if (undefined !== ms_data.dripped[membership_id]) {
				// Create input fields for the dripped membership
				form_row
					.find('.the-name')
					.text(ms_data.dripped[membership_id])
					.css({ background: color });

				form_row
					.find('[name=membership_ids]')
					.attr('name', 'membership_ids[]')
					.val(membership_id);

				form_row.find('[name=item_id]').val(item_data.item_id);

				form_row.find('[name=offset]').val(item_data.offset);

				form_row.find('[name=number]').val(item_data.number);

				form_row
					.find('[name=dripped_type]')
					.attr('name', base + '[dripped_type]')
					.val(item_data[base + '[dripped_type]']);

				form_row
					.find('[name=date]')
					.attr('name', base + '[date]')
					.val(item_data[base + '[date]']);

				form_row
					.find('[name=delay_unit]')
					.attr('name', base + '[delay_unit]')
					.val(item_data[base + '[delay_unit]']);

				form_row
					.find('[name=delay_type]')
					.attr('name', base + '[delay_type]')
					.val(item_data[base + '[delay_type]']);

				// Add the membership form to the inline editor
				form_row.appendTo(target).removeClass('hidden');

				setup_editor(form_row);
			}
		}

		// Remove the form-template from the inline editor.
		form.remove();
	}

	// Set up the event-handlers of the inline editor.
	setup_editor = function (form) {
		const sel_type = form.find('select.dripped_type'),
			inp_date = form.find('.memberdash-datepicker');

		// Type-selection
		sel_type
			.change(function () {
				const me = jQuery(this),
					val = me.val(),
					types = me.closest('.dripped-form').find('.drip-option');

				types.removeClass('active');
				types.filter('.' + val).addClass('active');
			})
			.trigger('change');

		// Datepicker
		inp_date.ms_datepicker();
	};

	// The table was updated, at least one row needs to be re-initialized.
	function update_table(ev, row) {
		window.ms_init.memberships_column('.column-access');

		row.find('.ms-memberships').each(function () {
			check_if_dripped.apply(this);
		});
	}

	// Network-wide protection
	// eslint-disable-next-line no-unused-vars
	function refresh_site_data(ev) {
		const url = sel_network_site.val();

		window.location.href = url;
		sel_network_site.addClass('memberdash-loading');
	}

	// Add event hooks.

	table.on('ms-ajax-updated', '.ms-memberships', check_if_dripped);
	table.find('.ms-memberships').each(function () {
		check_if_dripped.apply(this);
	});

	jQuery(document).on('ms-inline-editor', populate_inline_editor);
	jQuery(document).on('ms-inline-editor-updated', update_table);

	sel_network_site.on('change', refresh_site_data);
};

// -----------------------------------------------------------------------------

// This is also used on the Members page
window.ms_init.memberships_column = function init_column(column_class) {
	const table = jQuery('.wp-list-table');

	// Change the table row to "protected"
	// eslint-disable-next-line no-unused-vars
	function protect_item() {
		const cell = jQuery(this).closest(column_class),
			row = cell.closest('tr.item'),
			inp = cell.find('select.ms-memberships');

		row.removeClass('ms-empty').addClass('ms-assigned');

		cell.addClass('ms-focused');

		inp.memberdashSelect('focus');
		inp.memberdashSelect('open');
	}

	// If the item is not protected by any membership it will change to public
	// eslint-disable-next-line no-unused-vars
	function maybe_make_public(ev) {
		const cell = jQuery(this).closest(column_class),
			row = cell.closest('tr.item'),
			list = cell.find('select.ms-memberships'),
			memberships = list.val();

		cell.removeClass('ms-focused');

		if (memberships && memberships.length) {
			return;
		}
		row.removeClass('ms-assigned').addClass('ms-empty');
	}

	// Format the memberships in the dropdown list (= unselected items)
	function format_result(state) {
		let attr,
			original_option = state.element;

		attr =
			'class="val" style="background: ' +
			jQuery(original_option).data('color') +
			'"';
		return '<span ' + attr + '>&emsp;</span> ' + state.text;
	}

	// Format the memberships in the tag list (= selected items)
	function format_tag(state, container) {
		let original_option = state.element;
		const color = jQuery(original_option).data('color');

		container.css({ background: color });
		container.addClass('val');

		return '<span class="txt">' + state.text + '</span>';
	}

	// add hooks

	table.on(
		'click',
		'.ms-empty-note-wrapper .memberdash-label-after',
		protect_item
	);

	table.on('ms-ajax-updated', '.ms-memberships', maybe_make_public);
	table.on('blur', '.ms-memberships', function (ev) {
		const me = jQuery(this);
		// We need a delay here to allow select2 to forward the selection to us.
		window.setTimeout(function () {
			maybe_make_public.apply(me, ev);
		}, 250);
	});

	jQuery('select.ms-memberships')
		.memberdashSelect({
			templateResult: format_result,
			templateSelection: format_tag,
			escapeMarkup(m) {
				return m;
			},
			maximumSelectionLength:
				window.ms_admin_text.membership_multiple_limit,
			dropdownCssClass: 'ms-memberships memberdash-select2',
			width: '100%',
			language: {
				// Custom message for multi select limit.
				maximumSelected() {
					return window.ms_admin_text.membership_multiple_message;
				},
			},
		})
		.on('select2:select', function () {
			jQuery('select.ms-memberships').memberdashSelect('open');
		})
		.on('select2:unselect', function () {
			if (window.ms_admin_text.membership_multiple_limit == 1) {
				setTimeout(function () {
					jQuery('select.ms-memberships').memberdashSelect('close');
				}, 200);
			}
		});
};

//
// JS for the Membership > Edit > Upgrade Paths page.
//
window.ms_init.view_membership_upgrade = function init() {
	const slider_allow = jQuery('.ms-allow .memberdash-radio-slider');

	function slider_updated() {
		const me = jQuery(this),
			denied = me.hasClass('on'),
			row = me.closest('.ms-allow'),
			upd_replace = row.next('.ms-update-replace');

		if (!upd_replace.length) {
			return;
		}

		if (denied) {
			upd_replace.hide();
		} else {
			upd_replace.show();
		}
	}

	slider_allow.on('ms-radio-slider-updated', slider_updated);

	slider_allow.each(function () {
		slider_updated.apply(this);
	});
};

window.ms_init.view_addons = function init() {
	const list = jQuery('.ms-addon-list');

	// Apply the custom list-filters
	function filter_addons(event, filter, items) {
		switch (filter) {
			case 'options':
				items.hide().filter('.ms-options').show();
				break;
		}
	}

	// Show an overlay when ajax update starts (prevent multiple ajax calls at once!)
	function ajax_start(event, data, status, animation) {
		animation.removeClass('memberdash-loading');
		list.addClass('memberdash-loading');
	}

	// Remove the overlay after ajax update is done
	// eslint-disable-next-line no-unused-vars
	function ajax_done(event, data, status, animation) {
		list.removeClass('memberdash-loading');
	}

	// After an add-on was activated or deactivated
	function addon_toggle(event) {
		let el = jQuery(event.target),
			card = el.closest('.list-card-top'),
			details = card.find('.details'),
			fields = details.find('.memberdash-ajax-update-wrapper');

		if (!el.hasClass('memberdash-ajax-update')) {
			el = el.find('.memberdash-ajax-update');
		}

		if (el.closest('.details').length) {
			return;
		} // A detail setting was updated; add-on status was not changed...

		if (el.hasClass('on')) {
			fields.removeClass('disabled');
		} else {
			fields.addClass('disabled');
		}
	}

	jQuery(document).on('list-filter', filter_addons);
	jQuery(document).on('ms-ajax-start', ajax_start);
	jQuery(document).on('ms-ajax-updated', addon_toggle);
	jQuery(document).on('ms-ajax-done', ajax_done);

	jQuery('.list-card-top .memberdash-ajax-update-wrapper').each(function () {
		jQuery(this).trigger('ms-ajax-updated');
	});

	jQuery('#invoice_id_update').on('ms-ajax-updated', function () {
		jQuery('#invoice_id_update').hide();
	});

	jQuery('#invoice_sequence_type').on('ms-ajax-updated', function () {
		const $selected = jQuery('#invoice_sequence_type').val();
		jQuery('.invoice-types').each(function () {
			jQuery(this).hide();
		});
		if (jQuery('#' + $selected).length) {
			jQuery('#' + $selected).show();
		}
	});
};

window.ms_init.view_settings = function init() {
	// eslint-disable-next-line no-unused-vars
	function page_changed(event, data, response, is_err) {
		const lists = jQuery('select.memberdash-wp-pages'),
			cur_pages = lists.map(function () {
				return jQuery(this).val();
			});

		lists.each(function () {
			let ind,
				me = jQuery(this),
				options = me.find('option'),
				row = me.parents('.ms-settings-page-wrapper').first(),
				actions = row.find('.ms-action a'),
				val = me.val();

			// Disable the pages that are used already.
			options.prop('disabled', false);
			for (ind = 0; ind < cur_pages.length; ind += 1) {
				if (val === cur_pages[ind]) {
					continue;
				}
				options
					.filter('[value="' + cur_pages[ind] + '"]')
					.prop('disabled', true);
			}

			// Update the view/edit links
			actions.each(function () {
				const link = jQuery(this),
					dataE = link.data('memberdash-ajax'),
					url = dataE.base + val;

				if (undefined === val || isNaN(val) || val < 1) {
					link.addClass('disabled');
					link.attr('href', '');
				} else {
					link.removeClass('disabled');
					link.attr('href', url);
				}
			});
		});
	}

	function ignore_disabled(ev) {
		const me = jQuery(this);

		if (me.hasClass('disabled') || !me.attr('href').length) {
			ev.preventDefault();
			return false;
		}
	}

	function reload_window() {
		window.location = ms_data.initial_url;
	}

	function update_toolbar(ev, data) {
		// Show/Hide the Toolbar menu for Membership.
		if (data.value) {
			jQuery('#wp-admin-bar-ms-unprotected').hide();
			jQuery('#wp-admin-bar-ms-test-memberships').show();
		} else {
			jQuery('#wp-admin-bar-ms-unprotected').show();
			jQuery('#wp-admin-bar-ms-test-memberships').hide();
		}
	}

	function hide_footer(ev, data) {
		// Show/Hide the footer for Membership.
		if (!data.value) {
			jQuery('.ms-settings-email-cron').hide();
		} else {
			jQuery('.ms-settings-email-cron').show();
		}
		let ajax_data = jQuery(
			'.memberdash-slider-enable_cron_use .memberdash-toggle'
		).attr('data-memberdash-ajax');
		ajax_data = JSON.parse(ajax_data);
		jQuery.post(
			window.ajaxurl,
			{ action: 'toggle_cron', _wpnonce: ajax_data._wpnonce },
			function () {}
		);
	}

	// Reload the page when Wizard mode is activated.
	jQuery('#initial_setup').on('ms-ajax-updated', reload_window);

	// Hide/Show the "Test Membership" button in the toolbar.
	jQuery('.memberdash-slider-plugin_enabled').on(
		'ms-radio-slider-updated',
		update_toolbar
	);
	//Hide/Show footer when the cron is enabled or disabled
	jQuery('.memberdash-slider-enable_cron_use').on(
		'ms-radio-slider-updated',
		hide_footer
	);

	// Membership Pages: Update contents after a page was saved
	jQuery('.memberdash-wp-pages').on('ms-ajax-updated', page_changed);
	jQuery('.ms-action a').on('click', ignore_disabled);
	jQuery(function () {
		page_changed();
	});
};

window.ms_init.view_settings_automated_msg = function init() {
	let is_dirty = false;

	function change_comm_type() {
		let me = jQuery(this),
			form = me.closest('form'),
			ind = 0;

		for (ind = 0; ind < window.tinymce.editors.length; ind += 1) {
			if (window.tinymce.editors[ind].isDirty()) {
				is_dirty = true;
				break;
			}
		}

		if (is_dirty) {
			// eslint-disable-next-line no-alert
			if (!window.confirm(ms_data.lang_confirm)) {
				return false;
			}
		}

		form.submit();
	}

	function make_dirty() {
		is_dirty = true;
	}

	function toggle_override() {
		const toggle = jQuery(this),
			block = toggle.closest('.ms-settings-wrapper'),
			form = block.find('.ms-editor-form');

		if (toggle.hasClass('on')) {
			form.show();
		} else {
			form.hide();
		}
	}

	jQuery('#switch_comm_type').click(change_comm_type);
	jQuery('input, select, textarea', '.ms-editor-form').change(make_dirty);
	jQuery('.override-slider')
		.each(function () {
			toggle_override.apply(this);
		})
		.on('ms-ajax-done', toggle_override);

	/**
	 * Add the javascript for our custom TinyMCE button
	 *
	 * @see class-ms-controller-settings.php (function add_mce_buttons)
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 */
	window.tinymce.PluginManager.add(
		'ms_variable',
		// eslint-disable-next-line no-unused-vars
		function membership_variables(editor, url) {
			let key,
				item,
				items = [];

			// This function inserts the variable to the current cursor position.
			function insert_variable() {
				editor.insertContent(this.value());
			}
			// Build the list of available variables (defined in the view!)
			for (key in ms_data.var_button.items) {
				// eslint-disable-next-line no-prototype-builtins
				if (!ms_data.var_button.items.hasOwnProperty(key)) {
					continue;
				}

				item = ms_data.var_button.items[key];
				items.push({
					text: item,
					value: key,
					onclick: insert_variable,
				});
			}

			// Add the custom button to the editor.
			editor.addButton('ms_variable', {
				text: ms_data.var_button.title,
				icon: false,
				type: 'menubutton',
				menu: items,
			});
		}
	);
};

window.ms_init.view_settings_import = function init() {
	let form_import = jQuery('.ms-settings-import'),
		btn_download = form_import.find('#btn-download'),
		btn_import = form_import.find('#btn-import'),
		btn_user_import = form_import.find('#btn-user-import'),
		chk_clear = form_import.find('#clear_all'),
		sel_batchsize = form_import.find('#batchsize'),
		the_popup = null,
		the_progress = null,
		action_name = null,
		queue = [],
		queue_count = 0;

	/**
	 * Checks if the browser supports downloading js-created files.
	 */
	function support_download() {
		const a = document.createElement('a');
		if (undefined === a.download) {
			return false;
		}
		if (undefined === window.Blob) {
			return false;
		}
		if (undefined === window.JSON) {
			return false;
		}
		if (undefined === window.JSON.stringify) {
			return false;
		}

		return true;
	}

	/**
	 * Tries to provide the specified data as a file-download.
	 *
	 * @param  content
	 * @param  filename
	 * @param  contentType
	 */
	function download(content, filename, contentType) {
		let a, blob;
		if (!support_download()) {
			return false;
		}

		if (!contentType) {
			contentType = 'application/octet-stream';
		}
		a = document.createElement('a');
		blob = new window.Blob([content], { type: contentType });

		a.href = window.URL.createObjectURL(blob);
		a.download = filename;
		a.click();
	}

	/**
	 * Provides the import data object as file-download.
	 */
	function download_import_data() {
		let content;

		if (undefined === window._ms_import_obj) {
			return;
		}

		content = window.JSON.stringify(window._ms_import_obj);
		download(content, 'protected-content.json');
	}

	/**
	 * Displays the Import-Progress popup
	 */
	function show_popup() {
		const content = jQuery('<div></div>');

		the_progress = memberdashLi.progressbar();

		content.append(the_progress.$());
		the_popup = memberdashLi
			.popup()
			.title(ms_data.lang.progress_title, false)
			.modal(true, false)
			.content(content, true)
			.size(600, 140)
			.show();
	}

	/**
	 * Hides the Import-Progress popup
	 */
	function allow_hide_popup() {
		const el = jQuery('<div style="text-align:center"></div>'),
			btn = jQuery('<a href="#" class="close"></a>');

		btn.text(ms_data.lang.close_progress);
		if (ms_data.close_link) {
			btn.attr('href', ms_data.close_link);
		}
		btn.addClass('button-primary');
		btn.appendTo(el);

		the_popup
			.content(el, true)
			.modal(true, true)
			.title(ms_data.lang.import_done);
	}

	/**
	 * Returns the next batch for import.
	 *
	 * @param  max_items
	 */
	function get_next_batch(max_items) {
		let batch = {},
			count = 0,
			item;

		batch.items = [];
		batch.item_count = 0;
		batch.label = '';
		batch.source = window._ms_import_obj.source_key;
		if (typeof window._ms_import_obj.membership !== 'undefined') {
			batch.membership = window._ms_import_obj.membership;
		}
		if (typeof window._ms_import_obj.status !== 'undefined') {
			batch.status = window._ms_import_obj.status;
		}
		if (typeof window._ms_import_obj.start !== 'undefined') {
			batch.start = window._ms_import_obj.start;
		}
		if (typeof window._ms_import_obj.expire !== 'undefined') {
			batch.expire = window._ms_import_obj.expire;
		}

		for (count = 0; count < max_items; count += 1) {
			item = queue.shift();

			if (undefined === item) {
				// Whole queue is processed.
				break;
			}

			batch.label = item.label;
			delete item.label;

			batch.items.push(item);
			batch.item_count += 1;
		}

		return batch;
	}

	/**
	 * Send the next item from the import queue to the ajax handler.
	 */
	function process_queue() {
		const icon = '<i class="memberdash-loading-icon"></i> ',
			batchsize = sel_batchsize.val(),
			batch = get_next_batch(batchsize);

		if (!batch.item_count) {
			// All items were sent - hide the progress bar and show close button.
			allow_hide_popup();
			return;
		}

		// Update the progress bar.
		the_progress
			.value(queue_count - queue.length)
			.label(icon + '<span>' + batch.label + '</span>');

		// Prepare the ajax payload.
		if (!action_name) {
			action_name = btn_import.length
				? btn_import.val()
				: btn_user_import.val();
		}
		batch.action = action_name;

		delete batch.label;

		// Send the ajax request and call this function again when done.
		jQuery.post(window.ajaxurl, batch, process_queue);
	}

	function process_user_queue() {
		const icon = '<i class="memberdash-loading-icon"></i> ',
			batchsize = sel_batchsize.val(),
			batch = get_next_batch(batchsize);

		if (!batch.item_count) {
			// All items were sent - hide the progress bar and show close button.
			allow_hide_popup();
			return;
		}

		// Update the progress bar.
		the_progress
			.value(queue_count - queue.length)
			.label(icon + '<span>' + batch.label + '</span>');

		// Prepare the ajax payload.
		batch.action = btn_user_import.val();
		delete batch.label;

		// Send the ajax request and call this function again when done.
		jQuery.post(window.ajaxurl, batch, process_queue);
	}

	/**
	 * Starts the import process: A popup is opened to display the progress and
	 * then all import items are individually sent to the plugin via Ajax.
	 */
	function start_import() {
		let k,
			data,
			count,
			lang = ms_data.lang;

		queue = [];

		// This will prepare the import process
		queue.push({
			task: 'start',
			clear: chk_clear.is(':checked'),
			label: lang.task_start,
		});

		// _ms_import_obj is a JSON object, so we skip the .hasOwnProperty() check.
		count = 0;
		for (k in window._ms_import_obj.memberships) {
			data = window._ms_import_obj.memberships[k];
			count += 1;
			queue.push({
				task: 'import-membership',
				data,
				label: lang.task_import_membership + ': ' + count + '...',
			});
		}

		count = 0;
		for (k in window._ms_import_obj.members) {
			data = window._ms_import_obj.members[k];
			count += 1;
			queue.push({
				task: 'import-member',
				data,
				label: lang.task_import_member + ': ' + count + '...',
			});
		}

		for (k in window._ms_import_obj.settings) {
			data = window._ms_import_obj.settings[k];
			queue.push({
				task: 'import-settings',
				setting: k,
				value: data,
				label: lang.task_import_settings + '...',
			});
		}

		// Finally clean up after the import
		queue.push({
			task: 'done',
			label: lang.task_done,
		});

		// Display the import progress bar
		show_popup();
		queue_count = queue.length;
		the_progress.max(queue_count);

		// Start to process the import queue
		process_queue();
	}

	function start_user_import() {
		let k,
			data,
			count,
			membership,
			status,
			start,
			expire,
			lang = ms_data.lang;

		queue = [];

		// This will prepare the import process
		queue.push({
			task: 'start',
			clear: chk_clear.is(':checked'),
			label: lang.task_start,
		});

		count = 0;
		for (k in window._ms_import_obj.users) {
			data = window._ms_import_obj.users[k];
			membership = window._ms_import_obj.membership;
			status = window._ms_import_obj.status;
			start = window._ms_import_obj.start;
			expire = window._ms_import_obj.expire;
			count += 1;
			queue.push({
				task: 'import-member',
				data,
				membership,
				status,
				start,
				expire,
				label: lang.task_import_member + ': ' + count + '...',
			});
		}

		// Finally clean up after the import
		queue.push({
			task: 'done',
			label: lang.task_done,
		});

		// Display the import progress bar
		show_popup();
		queue_count = queue.length;
		the_progress.max(queue_count);

		// Start to process the import queue
		process_user_queue();
	}

	if (support_download()) {
		btn_download.click(download_import_data);
	} else {
		btn_download.hide();
	}

	btn_import.click(start_import);
	btn_user_import.click(start_user_import);

	/**
	 * Hide format on settings select
	 */
	jQuery(document).on('change', 'select.ms-select-type', function () {
		if (!jQuery('.ms-select-format-wrapper').is(':visible')) {
			jQuery('.ms-select-format-wrapper').show();
		}
		if (jQuery(this).val() === 'plugin') {
			jQuery('.ms-select-format-wrapper').hide();
		}
	});

	//hide by default
	jQuery('.ms-select-format-wrapper').hide();
};

window.ms_init.view_settings_mailchimp = function init() {
	jQuery('#mailchimp_api_key').on('ms-ajax-updated', ms_functions.reload);
};

window.ms_init.view_settings_addon_media = function init() {
	jQuery(document).ajaxComplete(function (event, xhr, settings) {
		if (settings.data.indexOf('field=advanced_media_protection') !== -1) {
			window.location.reload();
		}
	});
};

window.ms_init.view_settings_media = function init() {
	jQuery('#direct_access').on('ms-ajax-updated', function () {
		//update nginx rules
		const excludedFiles = jQuery('#direct_access').val();
		if (excludedFiles) {
			const array = excludedFiles.split(',');
			const $wp_content = jQuery('#wp_content_dir').val();
			const $extensions = array.join('|');
			const newRule =
				'location ~* ^' +
				$wp_content +
				'/.*&#92;.(' +
				$extensions +
				')$ {' +
				' \n  allow all;' +
				'\n}';
			jQuery('.application-servers-nginx-extra-instructions').html(
				newRule
			);
		}
	});

	jQuery('#application_server').on('ms-ajax-updated', function () {
		//show server div
		const $selected = jQuery('#application_server').val();
		jQuery('.application-servers').each(function () {
			jQuery(this).hide();
		});
		jQuery('.application-server-' + $selected).show();
	});
};

window.ms_init.view_settings_payment = function init() {
	function toggle_status(ev, data, response, is_err) {
		if (undefined === data.gateway_id) {
			return;
		}
		if ('update_gateway' !== data.action) {
			return;
		}

		const row = jQuery('.gateway-' + data.gateway_id);

		if (!is_err) {
			row.removeClass('not-configured').addClass('is-configured');

			if ('sandbox' === data.value) {
				row.removeClass('is-live').addClass('is-sandbox');
			} else if ('live' === data.value) {
				row.removeClass('is-sandbox').addClass('is-live');
			}
		} else {
			row.removeClass('is-configured is-live is-sandbox').addClass(
				'not-configured'
			);
		}
	}

	// eslint-disable-next-line no-unused-vars
	function change_icon(ev) {
		const el = jQuery(this),
			row = el.closest('.ms-gateway-item');

		if (el.prop('checked')) {
			row.addClass('open');
		} else {
			row.removeClass('open');
		}
	}

	function toggle_description() {
		const secure_cc = jQuery('#secure_cc').val();

		if ('false' === secure_cc || !secure_cc) {
			jQuery('.secure_cc_on').hide();
			jQuery('.secure_cc_off').removeClass('hidden').show();
		} else {
			jQuery('.secure_cc_off').hide();
			jQuery('.secure_cc_on').removeClass('hidden').show();
		}
	}

	jQuery(document).on('ms-ajax-updated', toggle_status);
	jQuery(document).on('click', '.show-settings', change_icon);

	jQuery('.memberdash-slider-secure_cc').on(
		'ms-ajax-done',
		toggle_description
	);
	toggle_description();
};

window.ms_init.view_settings_protection = function init() {
	// eslint-disable-next-line no-unused-vars
	function before_ajax(data, el) {
		const textarea = jQuery('#' + data.type),
			container = textarea.closest('.wp-editor-wrap'),
			editor = window.tinyMCE.get(data.type);

		if (editor && container.hasClass('tmce-active')) {
			editor.save(); // Update the textarea content.
		}

		data.value = textarea.val();

		return data;
	}

	function toggle_override() {
		const toggle = jQuery(this),
			block = toggle.closest('.inside'),
			content = block.find('.wp-editor-wrap'),
			button = block.find('.button-primary');

		if (toggle.hasClass('on')) {
			button.show();
			content.show();
		} else {
			button.hide();
			content.hide();
		}
	}

	jQuery('.button-primary.memberdash-ajax-update').data(
		'before_ajax',
		before_ajax
	);

	jQuery('.override-slider')
		.each(function () {
			toggle_override.apply(this);
		})
		.on('ms-ajax-done', toggle_override);
};

window.ms_init.view_settings_setup = function init() {
	const site_block = jQuery('.ms-setup-pages-site'),
		site_form = site_block.find('.ms-setup-pages-site-form'),
		btn_site_edit = site_block.find('.ms-setup-pages-change-site'),
		btn_site_cancel = site_block.find('.ms-setup-pages-cancel');

	function menu_created(event, data, response, is_err) {
		let parts;

		if (!is_err) {
			parts = response.split(':');
			if (undefined !== parts[1]) {
				parts.shift();
				jQuery('.ms-nav-controls').replaceWith(parts.join(':'));
			}
		}
	}

	// eslint-disable-next-line no-unused-vars
	function show_site_form(ev) {
		site_form.show();
		btn_site_edit.hide();
		return false;
	}

	// eslint-disable-next-line no-unused-vars
	function hide_site_form(ev) {
		site_form.hide();
		btn_site_edit.show();
		return false;
	}

	// Reload the page when Wizard mode is activated.
	jQuery(document).on('ms-ajax-updated', '#create_menu', menu_created);

	btn_site_edit.click(show_site_form);
	btn_site_cancel.click(hide_site_form);
};

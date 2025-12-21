/**
 * Medici Forms - Frontend JavaScript
 *
 * @package MediciForms
 * @version 1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Medici Forms Handler
	 */
	const MediciForms = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
			this.initPhoneMask();
			this.initAutoGrowTextarea();
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			$(document).on('submit', '.medici-form', this.handleSubmit.bind(this));
			$(document).on(
				'input',
				'.medici-form-field--error input, .medici-form-field--error textarea, .medici-form-field--error select',
				this.clearFieldError
			);
		},

		/**
		 * Handle form submission
		 */
		handleSubmit: function (e) {
			const $form = $(e.target);
			const isAjax = $form.data('ajax') === true;

			// Client-side validation
			if (!this.validateForm($form)) {
				e.preventDefault();
				return false;
			}

			// Non-AJAX submission
			if (!isAjax) {
				return true;
			}

			e.preventDefault();

			// Disable submit button
			const $button = $form.find('.medici-form-button');
			$button.prop('disabled', true).addClass('is-loading');

			// Hide previous messages
			$form.find('.medici-form-message').hide();

			// Prepare form data
			const formData = new FormData($form[0]);

			// AJAX request
			$.ajax({
				url: mediciFormsConfig.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function (response) {
					if (response.success) {
						MediciForms.handleSuccess($form, response.data);
					} else {
						MediciForms.handleError($form, response.data);
					}
				},
				error: function () {
					MediciForms.handleError($form, {
						message: mediciFormsConfig.i18n.error,
					});
				},
				complete: function () {
					$button.prop('disabled', false).removeClass('is-loading');
				},
			});

			return false;
		},

		/**
		 * Validate form (client-side)
		 */
		validateForm: function ($form) {
			let isValid = true;

			// Clear previous errors
			$form.find('.medici-form-field--error').removeClass('medici-form-field--error');
			$form.find('.medici-form-field__error').remove();

			// Validate required fields
			$form.find('[required]').each(function () {
				const $field = $(this);
				const $wrapper = $field.closest('.medici-form-field');
				const value = $field.val();

				if (!value || (Array.isArray(value) && value.length === 0)) {
					isValid = false;
					MediciForms.showFieldError($wrapper, mediciFormsConfig.i18n.required);
				}
			});

			// Validate email
			$form.find('input[type="email"]').each(function () {
				const $field = $(this);
				const value = $field.val();

				if (value && !MediciForms.isValidEmail(value)) {
					isValid = false;
					const $wrapper = $field.closest('.medici-form-field');
					MediciForms.showFieldError($wrapper, mediciFormsConfig.i18n.invalidEmail);
				}
			});

			// Validate phone
			$form.find('input[type="tel"]').each(function () {
				const $field = $(this);
				const value = $field.val();

				if (value && !MediciForms.isValidPhone(value)) {
					isValid = false;
					const $wrapper = $field.closest('.medici-form-field');
					MediciForms.showFieldError($wrapper, mediciFormsConfig.i18n.invalidPhone);
				}
			});

			// Scroll to first error
			if (!isValid) {
				const $firstError = $form.find('.medici-form-field--error').first();
				if ($firstError.length) {
					$('html, body').animate(
						{
							scrollTop: $firstError.offset().top - 100,
						},
						300
					);
				}
			}

			return isValid;
		},

		/**
		 * Show field error
		 */
		showFieldError: function ($wrapper, message) {
			$wrapper.addClass('medici-form-field--error');
			$wrapper.append('<span class="medici-form-field__error">' + message + '</span>');
		},

		/**
		 * Clear field error
		 */
		clearFieldError: function () {
			const $wrapper = $(this).closest('.medici-form-field');
			$wrapper.removeClass('medici-form-field--error');
			$wrapper.find('.medici-form-field__error').remove();
		},

		/**
		 * Handle successful submission
		 */
		handleSuccess: function ($form, data) {
			// Show success message
			if (data.message) {
				$form.find('.medici-form-message--success').html(data.message).slideDown();
			}

			// Reset form
			$form[0].reset();

			// Redirect if specified
			if (data.redirect_url) {
				setTimeout(function () {
					window.location.href = data.redirect_url;
				}, 1500);
			}

			// Scroll to message
			$('html, body').animate(
				{
					scrollTop: $form.find('.medici-form-message--success').offset().top - 100,
				},
				300
			);

			// Trigger custom event
			$(document).trigger('mediciFormsSuccess', [data, $form]);
		},

		/**
		 * Handle submission error
		 */
		handleError: function ($form, data) {
			// Show error message
			const message = data.message || mediciFormsConfig.i18n.error;
			$form.find('.medici-form-message--error').html(message).slideDown();

			// Mark fields with errors
			if (data.errors) {
				$.each(data.errors, function (fieldId, errorMessage) {
					const $field = $form.find('[name*="[' + fieldId + ']"]');
					const $wrapper = $field.closest('.medici-form-field');
					MediciForms.showFieldError($wrapper, errorMessage);
				});
			}

			// Scroll to message
			$('html, body').animate(
				{
					scrollTop: $form.find('.medici-form-message--error').offset().top - 100,
				},
				300
			);

			// Trigger custom event
			$(document).trigger('mediciFormsError', [data, $form]);
		},

		/**
		 * Validate email
		 */
		isValidEmail: function (email) {
			const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return regex.test(email);
		},

		/**
		 * Validate phone
		 */
		isValidPhone: function (phone) {
			// Remove all non-digits except +
			const digits = phone.replace(/[^0-9+]/g, '');
			// Check minimum length
			return digits.replace(/\+/g, '').length >= 10;
		},

		/**
		 * Initialize phone mask (basic formatting)
		 */
		initPhoneMask: function () {
			$(document).on('input', 'input[type="tel"]', function () {
				let value = $(this).val().replace(/\D/g, '');

				// Ukrainian format
				if (value.startsWith('380')) {
					value = value.substring(0, 12);
					if (value.length > 2) {
						value =
							'+380 ' +
							value.substring(3, 5) +
							' ' +
							value.substring(5, 8) +
							' ' +
							value.substring(8, 10) +
							' ' +
							value.substring(10);
					}
				} else if (value.startsWith('0')) {
					value = value.substring(0, 10);
					if (value.length > 0) {
						value =
							'+38 ' +
							value.substring(0, 3) +
							' ' +
							value.substring(3, 6) +
							' ' +
							value.substring(6, 8) +
							' ' +
							value.substring(8);
					}
				}

				$(this).val(value.trim());
			});
		},

		/**
		 * Initialize auto-growing textarea
		 * Uses CSS Grid technique for seamless height adjustment
		 */
		initAutoGrowTextarea: function () {
			// Handle textareas with auto-grow wrapper
			$(document).on('input', '.medici-form-textarea-wrapper > .medici-form-textarea', function () {
				const $textarea = $(this);
				const $wrapper = $textarea.parent('.medici-form-textarea-wrapper');

				// Update the data attribute with the current value
				// The CSS ::after pseudo-element will use this to calculate height
				$wrapper.attr('data-cloned-val', $textarea.val());
			});

			// Initialize existing textareas on page load
			$('.medici-form-textarea-wrapper > .medici-form-textarea').each(function () {
				const $textarea = $(this);
				const $wrapper = $textarea.parent('.medici-form-textarea-wrapper');
				$wrapper.attr('data-cloned-val', $textarea.val());
			});
		},
	};

	// Initialize on DOM ready
	$(document).ready(function () {
		MediciForms.init();
	});
})(jQuery);

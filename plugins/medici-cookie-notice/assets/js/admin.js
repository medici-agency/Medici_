/**
 * Medici Cookie Notice - Admin JavaScript
 *
 * @version 1.0.0
 * @package Medici_Cookie_Notice
 */

(function ($) {
	'use strict';

	/**
	 * Admin Module
	 */
	const MCNAdmin = {
		/**
		 * Initialize
		 */
		init: function () {
			this.initColorPickers();
			this.initRangeSliders();
			this.initPreview();
			this.initTabs();
		},

		/**
		 * Initialize color pickers
		 */
		initColorPickers: function () {
			$('.mcn-color-picker').wpColorPicker({
				change: function (event, ui) {
					MCNAdmin.updatePreview();
				},
			});
		},

		/**
		 * Initialize range sliders
		 */
		initRangeSliders: function () {
			$('.mcn-range').on('input', function () {
				const $this = $(this);
				const value = $this.val();
				$this.siblings('.mcn-range-value').text(value + '%');
				MCNAdmin.updatePreview();
			});
		},

		/**
		 * Initialize live preview
		 */
		initPreview: function () {
			// Watch for changes in text fields
			$(
				'input[name*="[message]"], input[name*="[accept_text]"], input[name*="[reject_text]"], input[name*="[settings_text]"]'
			).on('input', function () {
				MCNAdmin.updatePreview();
			});

			// Watch for changes in checkboxes
			$('input[name*="[show_reject_button]"], input[name*="[show_settings_button]"]').on(
				'change',
				function () {
					MCNAdmin.updatePreview();
				}
			);

			// Initial preview update
			this.updatePreview();
		},

		/**
		 * Update preview
		 */
		updatePreview: function () {
			const $preview = $('#mcn-banner-preview');
			if (!$preview.length) return;

			// Get values
			const message = $('textarea[name*="[message]"]').val() || '';
			const acceptText = $('input[name*="[accept_text]"]').val() || 'Прийняти всі';
			const rejectText = $('input[name*="[reject_text]"]').val() || 'Відхилити всі';
			const settingsText = $('input[name*="[settings_text]"]').val() || 'Налаштування';

			const showReject = $('input[name*="[show_reject_button]"]').is(':checked');
			const showSettings = $('input[name*="[show_settings_button]"]').is(':checked');

			const bgColor = $('input[name*="[bar_bg_color]"]').val() || '#1e293b';
			const textColor = $('input[name*="[bar_text_color]"]').val() || '#f8fafc';
			const opacity = parseInt($('input[name*="[bar_opacity]"]').val()) || 100;

			const acceptBg = $('input[name*="[btn_accept_bg]"]').val() || '#10b981';
			const acceptTextColor = $('input[name*="[btn_accept_text]"]').val() || '#ffffff';
			const rejectBg = $('input[name*="[btn_reject_bg]"]').val() || '#6b7280';
			const rejectTextColor = $('input[name*="[btn_reject_text]"]').val() || '#ffffff';

			const btnRadius = parseInt($('input[name*="[btn_border_radius]"]').val()) || 8;

			// Convert hex to rgba
			const rgba = this.hexToRgba(bgColor, opacity / 100);

			// Update preview styles
			$preview.css({
				background: rgba,
				color: textColor,
			});

			// Update message
			$preview.find('.mcn-preview-message').text(message);

			// Update buttons
			$preview
				.find('.mcn-preview-btn-accept')
				.text(acceptText)
				.css({
					background: acceptBg,
					color: acceptTextColor,
					'border-radius': btnRadius + 'px',
				});

			$preview
				.find('.mcn-preview-btn-reject')
				.text(rejectText)
				.css({
					background: rejectBg,
					color: rejectTextColor,
					'border-radius': btnRadius + 'px',
				})
				.toggle(showReject);

			$preview
				.find('.mcn-preview-btn-settings')
				.text(settingsText)
				.css({
					'border-radius': btnRadius + 'px',
					color: textColor,
				})
				.toggle(showSettings);
		},

		/**
		 * Convert hex to rgba
		 * @param {string} hex
		 * @param {number} alpha
		 * @returns {string}
		 */
		hexToRgba: function (hex, alpha) {
			hex = hex.replace('#', '');
			if (hex.length === 3) {
				hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
			}
			const r = parseInt(hex.substring(0, 2), 16);
			const g = parseInt(hex.substring(2, 4), 16);
			const b = parseInt(hex.substring(4, 6), 16);
			return `rgba(${r}, ${g}, ${b}, ${alpha})`;
		},

		/**
		 * Initialize tabs
		 */
		initTabs: function () {
			// Smooth scroll to tab content on tab click
			$('.mcn-tabs .nav-tab').on('click', function () {
				$('html, body').animate(
					{
						scrollTop: $('.mcn-settings-form').offset().top - 50,
					},
					200
				);
			});
		},
	};

	// Initialize when ready
	$(document).ready(function () {
		MCNAdmin.init();
	});
})(jQuery);

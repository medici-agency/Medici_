/**
 * Theme Settings Admin JavaScript
 *
 * @param $
 * @package
 * @since   1.5.0
 */

(function ($) {
	'use strict';

	/**
	 * Initialize theme settings
	 */
	function init() {
		initColorPickers();
		initImageUploaders();
	}

	/**
	 * Initialize color pickers
	 */
	function initColorPickers() {
		$('.medici-color-picker').wpColorPicker({
			change(event, ui) {
				updateColorPreview();
			},
			clear() {
				updateColorPreview();
			},
		});
	}

	/**
	 * Update color preview blocks
	 */
	function updateColorPreview() {
		const primary = $('#medici_color_primary').val() || '#10B981';
		const secondary = $('#medici_color_secondary').val() || '#0F172A';
		const accent = $('#medici_color_accent').val() || '#3B82F6';

		$('.preview-block.primary').css('background-color', primary);
		$('.preview-block.secondary').css('background-color', secondary);
		$('.preview-block.accent').css('background-color', accent);
	}

	/**
	 * Initialize image uploaders
	 */
	function initImageUploaders() {
		// Upload button click
		$(document).on('click', '.upload-image-button', function (e) {
			e.preventDefault();

			const $button = $(this);
			const $container = $button.closest('.image-upload-field');
			const $input = $container.find('.image-url-input');
			const $removeBtn = $container.find('.remove-image-button');
			const $preview = $container.siblings('.image-preview');

			// Create media frame
			const frame = wp.media({
				title: mediciThemeSettings.i18n.selectImage,
				button: {
					text: mediciThemeSettings.i18n.useImage,
				},
				multiple: false,
			});

			// On select
			frame.on('select', function () {
				const attachment = frame.state().get('selection').first().toJSON();
				$input.val(attachment.url);
				$removeBtn.show();

				// Update or create preview
				if ($preview.length) {
					$preview.find('img').attr('src', attachment.url);
				} else {
					$container.after(
						'<div class="image-preview" style="margin-top: 10px;"><img src="' +
							attachment.url +
							'" alt="" style="max-width: 200px; max-height: 100px;"></div>'
					);
				}
			});

			frame.open();
		});

		// Remove button click
		$(document).on('click', '.remove-image-button', function (e) {
			e.preventDefault();

			const $button = $(this);
			const $container = $button.closest('.image-upload-field');
			const $input = $container.find('.image-url-input');
			const $preview = $container.siblings('.image-preview');

			$input.val('');
			$button.hide();
			$preview.remove();
		});
	}

	// Initialize on document ready
	$(document).ready(init);
})(jQuery);

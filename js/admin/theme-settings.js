/**
 * Theme Settings Admin JavaScript
 *
 * ⚠️ ВАЖЛИВО: jQuery вимагається для WordPress компонентів
 * ----------------------------------------------------------------------------
 * Цей файл використовує jQuery тому що:
 * - $.wpColorPicker() - WordPress color picker plugin (ВИМАГАЄ jQuery)
 * - wp.media() - WordPress media library
 *
 * jQuery завжди завантажений в wp-admin, тому це не додає overhead.
 *
 * API:
 * - window.MediciThemeSettings.init()    - Initialize settings
 * - window.MediciThemeSettings.destroy() - Cleanup event listeners
 *
 * @package
 * @since   1.5.0
 * @version 1.6.0 Added cleanup method, modernized syntax
 */

(function ($) {
	'use strict';

	// =====================================================
	// STATE - Store references for cleanup
	// =====================================================
	const state = {
		initialized: false,
		mediaFrames: [],
	};

	// =====================================================
	// SELECTORS
	// =====================================================
	const SELECTORS = {
		colorPicker: '.medici-color-picker',
		uploadButton: '.upload-image-button',
		removeButton: '.remove-image-button',
		imageContainer: '.image-upload-field',
		imageInput: '.image-url-input',
		imagePreview: '.image-preview',
		previewPrimary: '.preview-block.primary',
		previewSecondary: '.preview-block.secondary',
		previewAccent: '.preview-block.accent',
	};

	// =====================================================
	// DEFAULT COLORS
	// =====================================================
	const DEFAULTS = {
		primary: '#10B981',
		secondary: '#0F172A',
		accent: '#3B82F6',
	};

	/**
	 * Initialize theme settings
	 */
	function init() {
		if (state.initialized) {
			return;
		}

		initColorPickers();
		initImageUploaders();

		state.initialized = true;
	}

	/**
	 * Destroy/cleanup all event listeners and references
	 */
	function destroy() {
		if (!state.initialized) {
			return;
		}

		// Destroy color pickers
		$(SELECTORS.colorPicker).each(function () {
			const $picker = $(this);
			if ($picker.data('wpWpColorPicker')) {
				$picker.wpColorPicker('destroy');
			}
		});

		// Close and clean media frames
		state.mediaFrames.forEach((frame) => {
			if (frame && typeof frame.close === 'function') {
				frame.close();
			}
		});
		state.mediaFrames = [];

		// Remove delegated event handlers
		$(document).off('click', SELECTORS.uploadButton);
		$(document).off('click', SELECTORS.removeButton);

		state.initialized = false;
	}

	/**
	 * Initialize WordPress color pickers
	 * Note: wpColorPicker is a jQuery plugin, cannot be converted to vanilla JS
	 */
	function initColorPickers() {
		const $pickers = $(SELECTORS.colorPicker);

		if (!$pickers.length) {
			return;
		}

		$pickers.wpColorPicker({
			change: () => updateColorPreview(),
			clear: () => updateColorPreview(),
		});
	}

	/**
	 * Update color preview blocks
	 * Uses vanilla JS where jQuery not required
	 */
	function updateColorPreview() {
		const primary = document.getElementById('medici_color_primary')?.value || DEFAULTS.primary;
		const secondary =
			document.getElementById('medici_color_secondary')?.value || DEFAULTS.secondary;
		const accent = document.getElementById('medici_color_accent')?.value || DEFAULTS.accent;

		// Use vanilla JS for simple style updates
		const primaryEl = document.querySelector(SELECTORS.previewPrimary);
		const secondaryEl = document.querySelector(SELECTORS.previewSecondary);
		const accentEl = document.querySelector(SELECTORS.previewAccent);

		if (primaryEl) primaryEl.style.backgroundColor = primary;
		if (secondaryEl) secondaryEl.style.backgroundColor = secondary;
		if (accentEl) accentEl.style.backgroundColor = accent;
	}

	/**
	 * Initialize image uploaders
	 * Uses event delegation for dynamically added elements
	 */
	function initImageUploaders() {
		// Check if WordPress media is available
		if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
			console.warn('Theme Settings: wp.media not available');
			return;
		}

		// Upload button click (delegated)
		$(document).on('click', SELECTORS.uploadButton, handleUploadClick);

		// Remove button click (delegated)
		$(document).on('click', SELECTORS.removeButton, handleRemoveClick);
	}

	/**
	 * Handle upload button click
	 *
	 * @param {Event} e - Click event
	 */
	function handleUploadClick(e) {
		e.preventDefault();

		const $button = $(this);
		const $container = $button.closest(SELECTORS.imageContainer);
		const $input = $container.find(SELECTORS.imageInput);
		const $removeBtn = $container.find(SELECTORS.removeButton);
		const $preview = $container.siblings(SELECTORS.imagePreview);

		// Get i18n strings with fallbacks
		const i18n = window.mediciThemeSettings?.i18n || {
			selectImage: 'Select Image',
			useImage: 'Use This Image',
		};

		// Create media frame
		const frame = wp.media({
			title: i18n.selectImage,
			button: { text: i18n.useImage },
			multiple: false,
		});

		// Store frame for cleanup
		state.mediaFrames.push(frame);

		// On select
		frame.on('select', () => {
			const attachment = frame.state().get('selection').first().toJSON();
			const imageUrl = attachment.url;

			// Update input value
			$input.val(imageUrl);
			$removeBtn.show();

			// Update or create preview
			if ($preview.length) {
				$preview.find('img').attr('src', imageUrl);
			} else {
				const previewHtml = createPreviewHtml(imageUrl);
				$container.after(previewHtml);
			}
		});

		frame.open();
	}

	/**
	 * Handle remove button click
	 *
	 * @param {Event} e - Click event
	 */
	function handleRemoveClick(e) {
		e.preventDefault();

		const $button = $(this);
		const $container = $button.closest(SELECTORS.imageContainer);
		const $input = $container.find(SELECTORS.imageInput);
		const $preview = $container.siblings(SELECTORS.imagePreview);

		$input.val('');
		$button.hide();
		$preview.remove();
	}

	/**
	 * Create preview HTML
	 *
	 * @param {string} imageUrl - Image URL
	 * @return {string} HTML string
	 */
	function createPreviewHtml(imageUrl) {
		const escapedUrl = escapeHtml(imageUrl);
		return `<div class="image-preview" style="margin-top: 10px;">
			<img src="${escapedUrl}" alt="" style="max-width: 200px; max-height: 100px;">
		</div>`;
	}

	/**
	 * Escape HTML to prevent XSS
	 *
	 * @param {string} str - String to escape
	 * @return {string} Escaped string
	 */
	function escapeHtml(str) {
		const div = document.createElement('div');
		div.textContent = str;
		return div.innerHTML;
	}

	// =====================================================
	// PUBLIC API
	// =====================================================
	window.MediciThemeSettings = {
		init,
		destroy,
	};

	// Initialize on document ready
	$(document).ready(init);
})(jQuery);

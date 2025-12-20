/**
 * Medici Cookie Notice - Admin JavaScript
 *
 * @version 1.3.0
 * @package Medici_Cookie_Notice
 */

(function ($) {
	'use strict';

	/**
	 * Admin Module
	 */
	const MCNAdmin = {
		/**
		 * Config from PHP
		 */
		config: window.mcnAdmin || {},

		/**
		 * Initialize
		 */
		init: function () {
			this.initColorPickers();
			this.initRangeSliders();
			this.initPreview();
			this.initTabs();
			this.initCategoryToggles();
			this.initTwemoji();
		},

		/**
		 * Initialize Twemoji
		 */
		initTwemoji: function () {
			if (typeof twemoji === 'undefined') return;

			// Parse emojis in the page
			const options = {
				folder: 'svg',
				ext: '.svg',
			};

			if (this.config.twemojiBase) {
				options.base = this.config.twemojiBase;
			}

			// Parse tab labels and other UI elements
			twemoji.parse(document.body, options);
		},

		/**
		 * Initialize color pickers
		 */
		initColorPickers: function () {
			$('.mcn-color-picker').wpColorPicker({
				change: function () {
					// Debounce preview update
					clearTimeout(MCNAdmin.colorPickerTimeout);
					MCNAdmin.colorPickerTimeout = setTimeout(function () {
						MCNAdmin.updatePreview();
					}, 100);
				},
				clear: function () {
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
				const unit = $this.data('unit') || '%';
				$this.siblings('.mcn-range-value').text(value + unit);
				MCNAdmin.updatePreview();
			});
		},

		/**
		 * Initialize category toggles
		 */
		initCategoryToggles: function () {
			// Enable/disable category editing based on checkbox
			$(document).on('change', 'input[name*="[categories]"][name*="[enabled]"]', function () {
				const $row = $(this).closest('.mcn-category-row');
				const isEnabled = $(this).is(':checked');
				$row
					.find("input:not([name*='[enabled]']):not([name*='[required]'])")
					.prop('disabled', !isEnabled);
				$row.toggleClass('mcn-category-disabled', !isEnabled);
			});

			// Initialize state
			$('input[name*="[categories]"][name*="[enabled]"]').trigger('change');
		},

		/**
		 * Initialize live preview
		 */
		initPreview: function () {
			const self = this;

			// Watch for changes in text fields
			$(document).on(
				'input',
				'textarea[name*="[message]"], input[name*="[accept_text]"], input[name*="[reject_text]"], input[name*="[settings_text]"], input[name*="[save_text]"]',
				function () {
					self.updatePreview();
				}
			);

			// Watch for changes in select fields
			$(document).on(
				'change',
				'select[name*="[position]"], select[name*="[layout]"], select[name*="[animation]"]',
				function () {
					self.updatePreview();
				}
			);

			// Watch for changes in checkboxes
			$(document).on(
				'change',
				'input[name*="[show_reject_button]"], input[name*="[show_settings_button]"], input[name*="[enable_categories]"], input[name*="[use_twemoji]"]',
				function () {
					self.updatePreview();
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

			// Get values from form or fallback to config
			const message =
				$('textarea[name*="[message]"]').val() ||
				this.config.options?.message ||
				'Ми використовуємо файли cookie...';
			const acceptText =
				$('input[name*="[accept_text]"]').val() ||
				this.config.options?.accept_text ||
				'Прийняти всі';
			const rejectText =
				$('input[name*="[reject_text]"]').val() ||
				this.config.options?.reject_text ||
				'Відхилити всі';
			const settingsText =
				$('input[name*="[settings_text]"]').val() ||
				this.config.options?.settings_text ||
				'Налаштування';

			const showReject = $('input[name*="[show_reject_button]"]').length
				? $('input[name*="[show_reject_button]"]').is(':checked')
				: this.config.options?.show_reject_button !== false;
			const showSettings = $('input[name*="[show_settings_button]"]').length
				? $('input[name*="[show_settings_button]"]').is(':checked')
				: this.config.options?.show_settings_button !== false;

			const bgColor =
				$('input[name*="[bar_bg_color]"]').val() || this.config.options?.bar_bg_color || '#1e293b';
			const textColor =
				$('input[name*="[bar_text_color]"]').val() ||
				this.config.options?.bar_text_color ||
				'#f8fafc';
			const opacity =
				parseInt($('input[name*="[bar_opacity]"]').val()) ||
				this.config.options?.bar_opacity ||
				100;

			const acceptBg =
				$('input[name*="[btn_accept_bg]"]').val() ||
				this.config.options?.btn_accept_bg ||
				'#10b981';
			const acceptTextColor =
				$('input[name*="[btn_accept_text]"]').val() ||
				this.config.options?.btn_accept_text ||
				'#ffffff';
			const rejectBg =
				$('input[name*="[btn_reject_bg]"]').val() ||
				this.config.options?.btn_reject_bg ||
				'#6b7280';
			const rejectTextColor =
				$('input[name*="[btn_reject_text]"]').val() ||
				this.config.options?.btn_reject_text ||
				'#ffffff';
			const settingsBg =
				$('input[name*="[btn_settings_bg]"]').val() ||
				this.config.options?.btn_settings_bg ||
				'transparent';
			const settingsTextColor =
				$('input[name*="[btn_settings_text]"]').val() ||
				this.config.options?.btn_settings_text ||
				'#f8fafc';

			const btnRadius =
				parseInt($('input[name*="[btn_border_radius]"]').val()) ||
				this.config.options?.btn_border_radius ||
				8;

			const position =
				$('select[name*="[position]"]').val() || this.config.options?.position || 'bottom';
			const layout = $('select[name*="[layout]"]').val() || this.config.options?.layout || 'bar';

			const useTwemoji = $('input[name*="[use_twemoji]"]').length
				? $('input[name*="[use_twemoji]"]').is(':checked')
				: this.config.useTwemoji !== false;

			// Convert hex to rgba
			const rgba = this.hexToRgba(bgColor, opacity / 100);

			// Update preview container classes for position/layout
			$preview
				.removeClass(
					'mcn-preview-bottom mcn-preview-top mcn-preview-floating-left mcn-preview-floating-right'
				)
				.addClass('mcn-preview-' + position);

			$preview
				.removeClass('mcn-preview-bar mcn-preview-box mcn-preview-modal')
				.addClass('mcn-preview-' + layout);

			// Update preview styles
			$preview.css({
				background: rgba,
				color: textColor,
			});

			// Build message with cookie icon
			let displayMessage = message;
			if (useTwemoji) {
				displayMessage = '🍪 ' + message;
			}

			// Update message
			const $message = $preview.find('.mcn-preview-message');
			$message.html(displayMessage);

			// Apply Twemoji to message
			if (useTwemoji && typeof twemoji !== 'undefined') {
				twemoji.parse($message[0], {
					folder: 'svg',
					ext: '.svg',
					base:
						this.config.twemojiBase || 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/',
				});
			}

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
					background: settingsBg,
					color: settingsTextColor,
					'border-radius': btnRadius + 'px',
					border: settingsBg === 'transparent' ? '1px solid ' + textColor : 'none',
				})
				.toggle(showSettings);

			// Update preview header with current settings
			this.updatePreviewInfo(position, layout);
		},

		/**
		 * Update preview info display
		 */
		updatePreviewInfo: function (position, layout) {
			const $info = $('.mcn-preview-info');
			if (!$info.length) return;

			const positionLabels = {
				bottom: 'Знизу',
				top: 'Зверху',
				'floating-left': 'Плаваючий зліва',
				'floating-right': 'Плаваючий справа',
			};

			const layoutLabels = {
				bar: 'Панель',
				box: 'Блок',
				modal: 'Модальне вікно',
			};

			$info.html(
				'<span class="mcn-info-item"><strong>Позиція:</strong> ' +
					(positionLabels[position] || position) +
					'</span>' +
					'<span class="mcn-info-item"><strong>Макет:</strong> ' +
					(layoutLabels[layout] || layout) +
					'</span>'
			);
		},

		/**
		 * Convert hex to rgba
		 * @param {string} hex
		 * @param {number} alpha
		 * @returns {string}
		 */
		hexToRgba: function (hex, alpha) {
			if (!hex) return 'rgba(30, 41, 59, ' + alpha + ')';
			hex = hex.replace('#', '');
			if (hex.length === 3) {
				hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
			}
			const r = parseInt(hex.substring(0, 2), 16) || 0;
			const g = parseInt(hex.substring(2, 4), 16) || 0;
			const b = parseInt(hex.substring(4, 6), 16) || 0;
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

			// Highlight current tab
			const currentTab = new URLSearchParams(window.location.search).get('tab') || 'general';
			$('.mcn-tabs .nav-tab').each(function () {
				const href = $(this).attr('href');
				if (href && href.includes('tab=' + currentTab)) {
					$(this).addClass('nav-tab-active');
				}
			});
		},
	};

	// Initialize when ready
	$(document).ready(function () {
		MCNAdmin.init();
	});
})(jQuery);

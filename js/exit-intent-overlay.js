/**
 * Exit-Intent Overlay Popup - Complete Handler
 *
 * Version: 2.0.0
 * Features:
 * - bioEp (beeker1121) exit-intent detection
 * - GenerateBlocks Pro 2.3+ Overlay Panel trigger
 * - Events API: consultation_request
 *
 * @package
 */

(function () {
	'use strict';

	// Configuration from PHP (window.mediciExitIntentConfig)
	const config = window.mediciExitIntentConfig || {
		overlayPanelId: 'medici-exit-intent-panel',
		cookieExp: 30,
		delay: 2,
		debug: false,
	};

	/**
	 * Initialize Exit-Intent Handler
	 */
	function init() {
		// 1. Initialize bioEp exit-intent detection
		initBioEp();

		// 2. Initialize form handler
		initFormHandler();

		if (config.debug) {
			console.log('[Medici Exit-Intent] Initialized', config);
		}
	}

	/**
	 * Initialize bioEp Exit-Intent Detection
	 */
	function initBioEp() {
		// Check if bioEp is loaded
		if (typeof bioEp !== 'function') {
			if (config.debug) {
				console.warn('[Medici Exit-Intent] bioEp library not loaded');
			}
			return;
		}

		// Configure bioEp
		bioEp({
			// Cookie expiration (days)
			cookieExp: config.cookieExp,

			// Delay before showing (seconds)
			delay: config.delay,

			// Show only on desktop (>1024px)
			showOnDelay: false,
			showOnScroll: false,

			// Callback when exit-intent detected
			onExit: function () {
				openOverlayPanel();
			},
		});

		if (config.debug) {
			console.log('[Medici Exit-Intent] bioEp configured');
		}
	}

	/**
	 * Open GenerateBlocks Overlay Panel
	 */
	function openOverlayPanel() {
		// Find overlay trigger element
		const trigger = document.querySelector(`[data-gb-overlay="${config.overlayPanelId}"]`);

		if (!trigger) {
			if (config.debug) {
				console.error('[Medici Exit-Intent] Overlay trigger not found:', config.overlayPanelId);
			}
			return;
		}

		// Trigger click to open overlay
		trigger.click();

		if (config.debug) {
			console.log('[Medici Exit-Intent] Overlay opened:', config.overlayPanelId);
		}
	}

	/**
	 * Initialize Form Handler
	 */
	function initFormHandler() {
		const form = document.querySelector('.js-exit-intent-form');
		if (!form) {
			if (config.debug) {
				console.warn('[Medici Exit-Intent] Form not found');
			}
			return;
		}

		form.addEventListener('submit', handleFormSubmit);

		if (config.debug) {
			console.log('[Medici Exit-Intent] Form handler attached');
		}
	}

	/**
	 * Handle Form Submission
	 *
	 * @param {Event} e - Submit event
	 */
	async function handleFormSubmit(e) {
		e.preventDefault();

		const form = e.target;
		const messageContainer = form.querySelector('.js-exit-intent-message');
		const submitButton = form.querySelector('.exit-intent-submit');

		// Get form data
		const formData = new FormData(form);
		const data = {
			event_type: 'consultation_request',
			name: formData.get('name'),
			email: formData.get('email'),
			phone: formData.get('phone'),
			service: 'exit_intent_popup', // Special marker
			message: 'Lead captured via Exit-Intent Popup',
			consent: formData.get('consent') ? '1' : '0',
			page_url: window.location.href,
		};

		// Validate consent
		if (data.consent !== '1') {
			showMessage(messageContainer, '❌ Необхідна згода на обробку персональних даних', 'error');
			return;
		}

		// Disable button
		submitButton.disabled = true;
		submitButton.textContent = 'Відправка...';

		try {
			// Use Events API
			if (typeof window.mediciEvents === 'undefined') {
				throw new Error('Events API не доступний');
			}

			const result = await window.mediciEvents.send('consultation_request', data);

			if (result.success) {
				showMessage(
					messageContainer,
					"✅ Дякуємо! Ми зв'яжемось з вами найближчим часом.",
					'success'
				);
				form.reset();

				// Close popup after 3 seconds
				setTimeout(() => {
					closeOverlayPanel();
				}, 3000);
			} else {
				throw new Error(result.message || 'Помилка відправки форми');
			}
		} catch (error) {
			console.error('Exit-Intent Form Error:', error);
			showMessage(messageContainer, `❌ Помилка: ${error.message}`, 'error');
		} finally {
			// Re-enable button
			submitButton.disabled = false;
			submitButton.textContent = 'Отримати консультацію';
		}
	}

	/**
	 * Show Message
	 *
	 * @param {HTMLElement} container - Message container
	 * @param {string}      message   - Message text
	 * @param {string}      type      - Message type (success/error)
	 */
	function showMessage(container, message, type) {
		if (!container) {
			return;
		}

		container.textContent = message;
		container.className = `exit-intent-message ${type}`;
		container.setAttribute('role', 'alert');

		// Clear after 5 seconds for errors
		if (type === 'error') {
			setTimeout(() => {
				container.textContent = '';
				container.className = 'exit-intent-message';
			}, 5000);
		}
	}

	/**
	 * Close Overlay Panel
	 * GenerateBlocks Pro method
	 */
	function closeOverlayPanel() {
		const closeButton = document.querySelector('[data-gb-close-panel]');
		if (closeButton) {
			closeButton.click();
		}
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

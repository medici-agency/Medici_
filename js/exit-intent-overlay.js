/**
 * Exit-Intent Overlay Popup - Complete Handler
 *
 * Version: 2.1.3
 * Features:
 * - bioEp (beeker1121) exit-intent detection via window.bioEp.init()
 * - Custom loadEvents() - exit-intent detector only (no DOM dependencies)
 * - Double-patching protection via window.mediciBioEpPatched flag
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

	// Debug helpers
	const log = (...args) => {
		if (config.debug) console.log('[Medici Exit-Intent]', ...args);
	};
	const warn = (...args) => {
		if (config.debug) console.warn('[Medici Exit-Intent]', ...args);
	};
	const error = (...args) => {
		if (config.debug) console.error('[Medici Exit-Intent]', ...args);
	};

	/**
	 * Check if desktop (>1024px)
	 */
	function isDesktop() {
		return window.matchMedia && window.matchMedia('(min-width: 1025px)').matches;
	}

	/**
	 * Open GenerateBlocks Overlay Panel
	 */
	function openOverlayPanel() {
		const trigger = document.querySelector(`[data-gb-overlay="${config.overlayPanelId}"]`);

		if (!trigger) {
			error('Overlay trigger not found:', config.overlayPanelId);
			return;
		}

		trigger.click();
		log('Overlay opened:', config.overlayPanelId);
	}

	/**
	 * Close Overlay Panel
	 * GenerateBlocks Pro method
	 */
	function closeOverlayPanel() {
		// Safer to scope to exit-intent popup to avoid closing other overlays
		const scope = document.querySelector('.exit-intent-content') || document;
		const closeButton = scope.querySelector('[data-gb-close-panel]');
		if (closeButton) {
			closeButton.click();
			log('Overlay closed');
		}
	}

	/**
	 * Initialize bioEp Exit-Intent Detection
	 *
	 * IMPORTANT: bioEp uses window.bioEp.init() and triggers via showPopup()
	 * We intercept showPopup() to open GenerateBlocks Overlay instead
	 */
	function initBioEp() {
		// Desktop only
		if (!isDesktop()) {
			log('Skipped (not desktop)');
			return;
		}

		// Check if bioEp is loaded (it's an object, not a function!)
		// Correct check: typeof window.bioEp === 'object'
		if (!window.bioEp || typeof window.bioEp.init !== 'function') {
			warn('bioEp library not loaded (window.bioEp missing)');
			return;
		}

		// Prevent double patching (SPA navigation, script re-execution)
		if (window.mediciBioEpPatched) {
			log('bioEp already patched, skipping');
			return;
		}
		window.mediciBioEpPatched = true;

		// Prevent bioEp from adding unnecessary DOM/CSS/Events
		// We only need exit-intent detection + cookie management

		// 1. No CSS injection
		if (typeof window.bioEp.addCSS === 'function') {
			window.bioEp.addCSS = function () {
				// No-op: prevent bioEp's CSS injection
			};
		}

		// 2. No DOM popup creation
		if (typeof window.bioEp.addPopup === 'function') {
			window.bioEp.addPopup = function () {
				// No-op: prevent bioEp's DOM popup creation
			};
		}

		// 3. Custom loadEvents: keep ONLY exit-intent detector
		// Remove: closeBtnEl click, window resize, other DOM dependencies
		if (typeof window.bioEp.loadEvents === 'function') {
			window.bioEp.loadEvents = function () {
				// Exit-intent detector (mouseout to top of viewport)
				this.addEvent(document, 'mouseout', function (e) {
					e = e || window.event;

					// Ignore if interacting with form elements
					const targetTag = e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
					if (targetTag === 'input' || targetTag === 'select' || targetTag === 'textarea') {
						return;
					}

					// Ignore if mouse is near right edge (scrollbar area)
					const viewportWidth = Math.max(
						document.documentElement.clientWidth,
						window.innerWidth || 0
					);
					const threshold = viewportWidth - 50;
					if (e.clientX > threshold) {
						return;
					}

					// Check if mouse is moving to top (exit-intent)
					if (e.clientY >= 50) {
						return;
					}

					// Ensure it's actually leaving viewport (relatedTarget = null)
					if (e.relatedTarget || e.toElement) {
						return;
					}

					// Trigger popup (intercepted by our showPopup override)
					window.bioEp.showPopup();
				});
			};

			log('bioEp.loadEvents() overridden (exit-intent only)');
		}

		// Intercept showPopup() - this is what bioEp calls on exit-intent
		const originalShowPopup = window.bioEp.showPopup && window.bioEp.showPopup.bind(window.bioEp);

		if (typeof originalShowPopup === 'function') {
			window.bioEp.showPopup = function () {
				// Prevent showing twice
				if (this.shown) {
					return;
				}

				this.shown = true;

				// Set cookie using bioEp's cookie manager
				if (this.cookieManager && typeof this.cookieManager.create === 'function') {
					this.cookieManager.create('bioep_shown', 'true', config.cookieExp, false);
				}

				// Open GenerateBlocks Overlay instead of bioEp's DOM popup
				openOverlayPanel();

				// IMPORTANT: Don't call originalShowPopup() to avoid showing bioEp's popup
			};

			log('bioEp.showPopup() intercepted');
		}

		// Initialize bioEp with configuration
		window.bioEp.init({
			cookieExp: config.cookieExp,
			delay: config.delay,
			showOnDelay: false,
			showOncePerSession: false,
		});

		log('bioEp configured (window.bioEp.init)');
	}

	/**
	 * Initialize Form Handler
	 */
	function initFormHandler() {
		const form = document.querySelector('.js-exit-intent-form');
		if (!form) {
			warn('Form not found');
			return;
		}

		form.addEventListener('submit', handleFormSubmit);
		log('Form handler attached');
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
		if (submitButton) {
			submitButton.disabled = true;
			submitButton.textContent = 'Відправка...';
		}

		try {
			// Use Events API
			if (!window.mediciEvents || typeof window.mediciEvents.send !== 'function') {
				throw new Error('Events API не доступний');
			}

			const result = await window.mediciEvents.send('consultation_request', data);

			if (!result || !result.success) {
				throw new Error((result && result.message) || 'Помилка відправки форми');
			}

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

			log('Form submitted successfully');
		} catch (err) {
			error('Form error:', err);
			showMessage(messageContainer, `❌ Помилка: ${err.message || err}`, 'error');
		} finally {
			// Re-enable button
			if (submitButton) {
				submitButton.disabled = false;
				submitButton.textContent = 'Отримати консультацію';
			}
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
		container.className = `exit-intent-message ${type || ''}`.trim();
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
	 * Initialize Exit-Intent Handler
	 */
	function init() {
		// 1. Initialize bioEp exit-intent detection
		initBioEp();

		// 2. Initialize form handler
		initFormHandler();

		log('Initialized', config);
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

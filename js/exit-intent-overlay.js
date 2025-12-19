/**
 * Exit-Intent Overlay Popup - Complete Handler
 * Version: 2.1.3 (fixed)
 *
 * Features:
 * - bioEp (beeker1121) exit-intent detection via window.bioEp.init()
 * - Custom loadEvents() - exit-intent detector only (no DOM dependencies)
 * - Double-patching protection via window.mediciBioEpPatched flag
 * - GenerateBlocks Pro Overlay Panel trigger (data-gb-overlay)
 * - Events API: consultation_request
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
	const err = (...args) => {
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
	 * @returns {boolean} success
	 */
	function openOverlayPanel() {
		const trigger = document.querySelector(`[data-gb-overlay="${config.overlayPanelId}"]`);
		if (!trigger) {
			err('Overlay trigger not found:', config.overlayPanelId);
			return false;
		}

		trigger.click();
		log('Overlay opened:', config.overlayPanelId);
		return true;
	}

	/**
	 * Close Overlay Panel (scoped)
	 */
	function closeOverlayPanel() {
		const scope = document.querySelector('.exit-intent-content') || document;
		const closeButton = scope.querySelector('[data-gb-close-panel]');

		if (closeButton) {
			closeButton.click();
			log('Overlay closed');
		}
	}

	/**
	 * Initialize bioEp Exit-Intent Detection
	 */
	function initBioEp() {
		if (!isDesktop()) {
			log('Skipped (not desktop)');
			return;
		}

		// bioEp is an object: window.bioEp
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

		// 1) No CSS injection
		if (typeof window.bioEp.addCSS === 'function') {
			window.bioEp.addCSS = function () {};
		}

		// 2) No DOM popup creation
		if (typeof window.bioEp.addPopup === 'function') {
			window.bioEp.addPopup = function () {};
		}

		// 3) Custom loadEvents: exit-intent detector ONLY
		if (typeof window.bioEp.loadEvents === 'function') {
			window.bioEp.loadEvents = function () {
				this.addEvent(document, 'mouseout', function (e) {
					e = e || window.event;

					// Ignore if interacting with form elements
					const targetTag =
						e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
					if (
						targetTag === 'input' ||
						targetTag === 'select' ||
						targetTag === 'textarea'
					) {
						return;
					}

					// Ignore scrollbar area (right 50px)
					const viewportWidth = Math.max(
						document.documentElement.clientWidth,
						window.innerWidth || 0
					);
					if (e.clientX > viewportWidth - 50) {
						return;
					}

					// Exit-intent near top
					if (e.clientY >= 50) {
						return;
					}

					// Ensure leaving viewport
					if (e.relatedTarget || e.toElement) {
						return;
					}

					window.bioEp.showPopup();
				});
			};

			log('bioEp.loadEvents() overridden (exit-intent only)');
		}

		// Intercept showPopup(): open GB overlay instead of bioEp DOM popup
		const originalShowPopup =
			typeof window.bioEp.showPopup === 'function'
				? window.bioEp.showPopup.bind(window.bioEp)
				: null;

		if (typeof originalShowPopup === 'function') {
			window.bioEp.showPopup = function () {
				// Prevent showing twice
				if (this.shown) return;

				// Open overlay first; only then mark as shown + set cookie
				const opened = openOverlayPanel();
				if (!opened) return;

				this.shown = true;

				if (this.cookieManager && typeof this.cookieManager.create === 'function') {
					this.cookieManager.create('bioep_shown', 'true', config.cookieExp, false);
				}

				// IMPORTANT: Don't call originalShowPopup()
			};

			log('bioEp.showPopup() intercepted');
		}

		// Initialize bioEp (no DOM/CSS due to overrides)
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

		async function handleFormSubmit(e) {
			e.preventDefault();

			const messageContainer = form.querySelector('.js-exit-intent-message');
			const submitButton = form.querySelector('.exit-intent-submit');

			const formData = new FormData(form);
			const data = {
				event_type: 'consultation_request',
				name: formData.get('name'),
				email: formData.get('email'),
				phone: formData.get('phone'),
				service: 'exit_intent_popup',
				message: 'Lead captured via Exit-Intent Popup',
				consent: formData.get('consent') ? '1' : '0',
				page_url: window.location.href,
			};

			if (data.consent !== '1') {
				showMessage(
					messageContainer,
					'❌ Необхідна згода на обробку персональних даних',
					'error'
				);
				return;
			}

			if (submitButton) {
				submitButton.disabled = true;
				submitButton.textContent = 'Відправка...';
			}

			try {
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
				setTimeout(closeOverlayPanel, 3000);

				log('Form submitted successfully');
			} catch (e2) {
				err('Form error:', e2);
				showMessage(messageContainer, `❌ Помилка: ${e2.message || e2}`, 'error');
			} finally {
				if (submitButton) {
					submitButton.disabled = false;
					submitButton.textContent = 'Отримати консультацію';
				}
			}
		}
	}

	function showMessage(container, message, type) {
		if (!container) return;

		container.textContent = message;
		container.className = `exit-intent-message ${type || ''}`.trim();
		container.setAttribute('role', 'alert');

		if (type === 'error') {
			setTimeout(() => {
				container.textContent = '';
				container.className = 'exit-intent-message';
			}, 5000);
		}
	}

	/**
	 * Initialize
	 */
	function init() {
		initBioEp();
		initFormHandler();
		log('Initialized', config);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
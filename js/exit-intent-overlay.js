/**
 * Exit-Intent Overlay Popup - Form Handler
 *
 * Version: 1.0.0
 * GenerateBlocks Pro 2.3+ Overlay Panel Integration
 * Events API: consultation_request
 *
 * @package
 */

(function () {
	'use strict';

	/**
	 * Initialize Exit-Intent Form Handler
	 */
	function init() {
		const form = document.querySelector('.js-exit-intent-form');
		if (!form) {
			return;
		}

		form.addEventListener('submit', handleFormSubmit);
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

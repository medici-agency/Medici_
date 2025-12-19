/**
 * Consultation Request Form Handler
 *
 * Usage: Add class 'js-consultation-form' to your form element.
 *        (Legacy: 'consultation-form' still supported for backwards compatibility)
 *
 * Required fields: name, email, phone, consent
 * Optional fields: message, service
 *
 * JS Hooks (recommended):
 * - .js-consultation-form    - Form element
 * - .js-consultation-message - Message display element
 *
 * API:
 * - window.MediciConsultationForm.init()    - Initialize forms
 * - window.MediciConsultationForm.destroy() - Cleanup event listeners
 *
 * @package
 * @since   1.4.0
 * @version 1.5.0 Added cleanup/destroy method to prevent memory leaks
 */

(function () {
	'use strict';

	// =====================================================
	// JS HOOKS SELECTORS (js-* classes convention)
	// Fallback to CSS classes for backward compatibility
	// =====================================================
	const SELECTORS = {
		form: '.js-consultation-form, .consultation-form',
		message: '.js-consultation-message, .consultation-message',
	};

	// i18n texts (from wp_localize_script)
	const i18n = window.mediciData?.i18n?.consultation ?? {
		// Fallback texts (if wp_localize_script failed)
		sending: 'Відправка...',
		submit: 'Відправити',
		errorName: "Будь ласка, вкажіть ваше ім'я",
		errorEmail: 'Будь ласка, вкажіть email',
		errorEmailInvalid: 'Будь ласка, вкажіть коректний email',
		errorPhone: 'Будь ласка, вкажіть номер телефону',
		errorPhoneInvalid: 'Будь ласка, вкажіть коректний номер телефону',
		errorConsent: 'Для відправки потрібна ваша згода на обробку персональних даних',
		errorGeneral: 'Сталася помилка. Спробуйте ще раз.',
	};

	// =====================================================
	// STATE - Store references for cleanup
	// =====================================================
	const state = {
		initialized: false,
		forms: [],
		handlers: new WeakMap(),
		textareaHandlers: new WeakMap(),
	};

	/**
	 * Initialize consultation forms
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function init() {
		// Prevent double initialization
		if (state.initialized) {
			return;
		}

		// Check if Events API is loaded
		if (!window.mediciEvents) {
			console.warn('Consultation forms: mediciEvents API not loaded');
			return;
		}

		// Find all consultation forms (js-* hook preferred, legacy class supported)
		const forms = document.querySelectorAll(SELECTORS.form);

		if (!forms.length) {
			return;
		}

		// Attach handlers to each form
		forms.forEach((form) => {
			// Create bound handler for this form
			const handler = handleSubmit.bind(null, form);
			state.handlers.set(form, handler);
			form.addEventListener('submit', handler);

			// Initialize autogrowing textarea
			initAutogrowingTextarea(form);

			// Store form reference
			state.forms.push(form);
		});

		state.initialized = true;
	}

	/**
	 * Destroy/cleanup all event listeners
	 * Call this before removing forms from DOM or on page unload
	 */
	function destroy() {
		if (!state.initialized) {
			return;
		}

		// Remove submit handlers
		state.forms.forEach((form) => {
			const handler = state.handlers.get(form);
			if (handler) {
				form.removeEventListener('submit', handler);
				state.handlers.delete(form);
			}

			// Remove textarea handlers
			const textareas = form.querySelectorAll('textarea');
			textareas.forEach((textarea) => {
				const inputHandler = state.textareaHandlers.get(textarea);
				if (inputHandler) {
					textarea.removeEventListener('input', inputHandler);
					state.textareaHandlers.delete(textarea);
				}
			});
		});

		// Clear forms array
		state.forms = [];
		state.initialized = false;
	}

	/**
	 * Initialize autogrowing textarea (CSS Grid technique)
	 *
	 * @param {HTMLElement} form - Form element
	 */
	function initAutogrowingTextarea(form) {
		const textareas = form.querySelectorAll('textarea');

		textareas.forEach((textarea) => {
			// Check if already wrapped
			if (textarea.parentElement.classList.contains('textarea-wrapper')) {
				return;
			}

			// Create wrapper
			const wrapper = document.createElement('div');
			wrapper.className = 'textarea-wrapper';
			wrapper.setAttribute('data-cloned-val', textarea.value);

			// Wrap textarea
			textarea.parentNode.insertBefore(wrapper, textarea);
			wrapper.appendChild(textarea);

			// Create bound handler for cleanup
			const inputHandler = () => {
				wrapper.setAttribute('data-cloned-val', textarea.value);
			};

			// Store handler reference
			state.textareaHandlers.set(textarea, inputHandler);

			// Update wrapper data on input
			textarea.addEventListener('input', inputHandler);

			// Trigger initial update
			wrapper.setAttribute('data-cloned-val', textarea.value);
		});
	}

	/**
	 * Validate email address
	 *
	 * @param {string} email - Email to validate
	 * @return {boolean} True if valid
	 */
	function isValidEmail(email) {
		// Simple email regex (RFC 5322 compliant)
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	}

	/**
	 * Validate phone number (simple check)
	 *
	 * @param {string} phone - Phone to validate
	 * @return {boolean} True if valid
	 */
	function isValidPhone(phone) {
		// Allow digits, spaces, dashes, parentheses, plus sign
		// Min length 10 chars (basic check)
		const phoneRegex = /^[\d\s\-\(\)\+]{10,}$/;
		return phoneRegex.test(phone);
	}

	/**
	 * Handle form submission
	 *
	 * @param {HTMLFormElement} form - Form element (bound)
	 * @param {Event} event - Submit event
	 */
	function handleSubmit(form, event) {
		event.preventDefault();

		// Get form elements
		const nameField = form.querySelector('input[name="name"]');
		const emailField = form.querySelector('input[name="email"]');
		const phoneField = form.querySelector('input[name="phone"]');
		const messageField = form.querySelector('textarea[name="message"]');
		const serviceField = form.querySelector('input[name="service"], select[name="service"]');
		const consentField = form.querySelector('input[name="consent"]');
		const submitBtn = form.querySelector('button[type="submit"]');
		const messageEl = form.querySelector(SELECTORS.message);

		// Validate required fields exist
		if (!nameField || !emailField || !phoneField || !consentField) {
			console.error('Consultation form: required fields not found');
			return;
		}

		// Get field values
		const name = nameField.value.trim();
		const email = emailField.value.trim();
		const phone = phoneField.value.trim();

		// Client-side validation (fast, no server roundtrip)
		if (!name) {
			showMessage(messageEl, 'error', i18n.errorName);
			nameField.focus();
			return;
		}

		if (!email) {
			showMessage(messageEl, 'error', i18n.errorEmail);
			emailField.focus();
			return;
		}

		if (!isValidEmail(email)) {
			showMessage(messageEl, 'error', i18n.errorEmailInvalid);
			emailField.focus();
			return;
		}

		if (!phone) {
			showMessage(messageEl, 'error', i18n.errorPhone);
			phoneField.focus();
			return;
		}

		if (!isValidPhone(phone)) {
			showMessage(messageEl, 'error', i18n.errorPhoneInvalid);
			phoneField.focus();
			return;
		}

		if (!consentField.checked) {
			showMessage(messageEl, 'error', i18n.errorConsent);
			consentField.focus();
			return;
		}

		// Disable submit button
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = i18n.sending;
		}

		// Prepare data
		const data = {
			name,
			email,
			phone,
			message: messageField ? messageField.value.trim() : '',
			service: serviceField ? serviceField.value : '',
			consent: consentField.checked,
		};

		// Send event
		mediciEvents
			.requestConsultation(data)
			.then((result) => {
				// Success
				showMessage(messageEl, 'success', result.message);

				// Reset form
				form.reset();

				// Track event (if analytics available)
				if (typeof gtag === 'function') {
					gtag('event', 'consultation_request', {
						event_category: 'lead',
						event_label: data.service || 'general',
					});
				}
			})
			.catch((error) => {
				// Error
				showMessage(messageEl, 'error', error.message || i18n.errorGeneral);

				console.error('Consultation request error:', error);
			})
			.finally(() => {
				// Re-enable submit button
				if (submitBtn) {
					submitBtn.disabled = false;
					submitBtn.textContent = i18n.submit;
				}
			});
	}

	/**
	 * Show message to user
	 *
	 * @param {HTMLElement|null} messageEl - Message element
	 * @param {string}           type      - Message type ('success' or 'error')
	 * @param {string}           text      - Message text
	 */
	function showMessage(messageEl, type, text) {
		if (!messageEl) {
			return;
		}

		// Use BEM modifier classes (--success, --error)
		// Keep both old and new class naming for backwards compatibility
		messageEl.className = `consultation-form__message consultation-form__message--${type} consultation-message ${type}`;
		messageEl.textContent = text;
	}

	// =====================================================
	// PUBLIC API
	// =====================================================
	window.MediciConsultationForm = {
		init,
		destroy,
	};

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

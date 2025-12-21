/**
 * Newsletter Subscription Form Handler
 *
 * Usage: Add class 'js-newsletter-form' to your form element.
 *        (Legacy: 'newsletter-form' still supported for backwards compatibility)
 *
 * Required field: input[name="email"]
 * Optional: data-source attribute on form element
 *
 * JS Hooks (recommended):
 * - .js-newsletter-form    - Form element
 * - .js-newsletter-message - Message display element
 *
 * @package
 * @since   1.4.0
 * @version 1.4.0 Added js-* hooks for BEM separation
 */

(function () {
	'use strict';

	// =====================================================
	// JS HOOKS SELECTORS (js-* classes convention)
	// Fallback to CSS classes for backward compatibility
	// =====================================================
	const SELECTORS = {
		form: '.js-newsletter-form, .newsletter-form',
		message: '.js-newsletter-message, .newsletter-message',
	};

	// i18n texts (from wp_localize_script)
	const i18n = window.mediciData?.i18n?.newsletter ?? {
		// Fallback texts (if wp_localize_script failed)
		sending: 'Відправка...',
		submit: 'Підписатись',
		errorEmpty: 'Будь ласка, введіть email',
		errorInvalid: 'Будь ласка, введіть коректний email',
		errorGeneral: 'Сталася помилка. Спробуйте ще раз.',
		successSuffix: 'Перевірте вашу пошту.',
	};

	/**
	 * Initialize newsletter forms
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function init() {
		// Check if Events API is loaded
		if (!window.mediciEvents) {
			console.warn('Newsletter forms: mediciEvents API not loaded');
			return;
		}

		// Find all newsletter forms (js-* hook preferred, legacy class supported)
		const forms = document.querySelectorAll('.js-newsletter-form, .newsletter-form');

		if (!forms.length) {
			return;
		}

		// Attach handlers to each form
		forms.forEach((form) => {
			form.addEventListener('submit', handleSubmit);
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
	 * Handle form submission
	 *
	 * @param {Event} event - Submit event
	 */
	function handleSubmit(event) {
		event.preventDefault();

		const form = event.target;

		// Get form elements (uses standard field selectors)
		const emailField = form.querySelector('input[name="email"]');
		const submitBtn = form.querySelector('button[type="submit"]');
		const messageEl = form.querySelector('.js-newsletter-message, .newsletter-message');

		if (!emailField) {
			console.error('Newsletter form: email field not found');
			return;
		}

		// Get email value
		const email = emailField.value.trim();

		// Client-side validation (fast, no server roundtrip)
		if (!email) {
			showMessage(messageEl, 'error', i18n.errorEmpty);
			emailField.focus();
			return;
		}

		if (!isValidEmail(email)) {
			showMessage(messageEl, 'error', i18n.errorInvalid);
			emailField.focus();
			return;
		}

		// Disable submit button
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = i18n.sending;
		}

		// Get form data
		const source = form.getAttribute('data-source') || 'footer';
		const tags = form.getAttribute('data-tags')
			? form.getAttribute('data-tags').split(',')
			: ['blog-newsletter'];

		// Send event
		mediciEvents
			.subscribeNewsletter(email, { source, tags })
			.then((result) => {
				// Success
				showMessage(messageEl, 'success', result.message || i18n.successSuffix);

				// Reset form
				form.reset();

				// Track event (if analytics available)
				if (typeof gtag === 'function') {
					gtag('event', 'newsletter_subscribe', {
						event_category: 'engagement',
						event_label: source,
					});
				}
			})
			.catch((error) => {
				// Error
				showMessage(messageEl, 'error', error.message || i18n.errorGeneral);

				console.error('Newsletter subscription error:', error);
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
		messageEl.className = `newsletter-form__message newsletter-form__message--${type} newsletter-message ${type}`;
		messageEl.textContent = text;
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

/**
 * Newsletter Subscription Form Handler
 *
 * Usage: Add class 'js-newsletter-form' to your form element.
 * (Legacy: 'newsletter-form' still supported for backwards compatibility)
 *
 * Required field: input[name="email"]
 * Optional: data-source attribute on form element
 * Optional: data-tags attribute on form element (comma-separated)
 *
 * JS Hooks (recommended):
 * - .js-newsletter-form - Form element
 * - .js-newsletter-message - Message display element
 *
 * API:
 * - window.MediciNewsletterForm.init() - Initialize forms
 * - window.MediciNewsletterForm.destroy() - Cleanup event listeners
 *
 * @since 1.4.0
 * @version 1.5.1 Fixed syntax errors, improved robustness, ensured safe DOM ops
 */

(function () {
	'use strict';

	const SELECTORS = {
		form: '.js-newsletter-form, .newsletter-form',
		message: '.js-newsletter-message, .newsletter-message, .newsletter-form__message',
		emailField: 'input[name="email"], input[type="email"]',
		submitButton: 'button[type="submit"], input[type="submit"]',
	};

	const DEFAULT_I18N = {
		sending: 'Відправка...',
		submit: 'Підписатись',
		errorEmpty: 'Будь ласка, введіть email',
		errorInvalid: 'Будь ласка, введіть коректний email',
		errorGeneral: 'Сталася помилка. Спробуйте ще раз.',
		success: 'Дякуємо за підписку!',
		successSuffix: 'Перевірте вашу пошту.',
	};

	const i18n = window.mediciData?.i18n?.newsletter ?? DEFAULT_I18N;

	const state = {
		initialized: false,
		forms: [],
		handlers: new WeakMap(),
	};

	function isValidEmail(email) {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	}

	function getOrCreateMessageElement(form) {
		const existing = form.querySelector(SELECTORS.message);
		if (existing) {
			return existing;
		}

		const messageEl = document.createElement('div');
		messageEl.className = 'newsletter-form__message newsletter-message';
		messageEl.setAttribute('role', 'status');
		messageEl.setAttribute('aria-live', 'polite');

		const submitBtn = form.querySelector(SELECTORS.submitButton);
		if (submitBtn && submitBtn.parentNode) {
			submitBtn.parentNode.insertBefore(messageEl, submitBtn.nextSibling);
		} else {
			form.appendChild(messageEl);
		}

		return messageEl;
	}

	function showMessage(form, type, text) {
		const messageEl = getOrCreateMessageElement(form);
		if (!messageEl) {
			return;
		}

		const safeType = type === 'success' ? 'success' : 'error';

		messageEl.className = [
			'newsletter-form__message',
			`newsletter-form__message--${safeType}`,
			'newsletter-message',
			safeType,
		].join(' ');

		messageEl.textContent = String(text ?? '');
	}

	function setBusy(form, submitBtn, isBusy) {
		form.setAttribute('aria-busy', isBusy ? 'true' : 'false');

		if (!submitBtn) {
			return;
		}

		if (submitBtn.tagName === 'BUTTON') {
			submitBtn.disabled = Boolean(isBusy);
			submitBtn.textContent = isBusy ? i18n.sending : i18n.submit;
			return;
		}

		// input[type="submit"]
		submitBtn.disabled = Boolean(isBusy);
		if (submitBtn.value != null) {
			submitBtn.value = isBusy ? i18n.sending : i18n.submit;
		}
	}

	function parseTags(form) {
		const raw = form.getAttribute('data-tags');
		if (!raw) {
			return ['blog-newsletter'];
		}

		const tags = raw
			.split(',')
			.map((t) => t.trim())
			.filter(Boolean);

		return tags.length ? tags : ['blog-newsletter'];
	}

	function getSource(form) {
		return form.getAttribute('data-source') || 'footer';
	}

	function trackNewsletter(source) {
		if (typeof window.gtag !== 'function') {
			return;
		}

		window.gtag('event', 'newsletter_subscribe', {
			event_category: 'engagement',
			event_label: source,
		});
	}

	function handleSubmit(form, event) {
		event.preventDefault();

		if (!window.mediciEvents || typeof window.mediciEvents.subscribeNewsletter !== 'function') {
			console.warn('Newsletter forms: mediciEvents.subscribeNewsletter API not available');
			showMessage(form, 'error', i18n.errorGeneral);
			return;
		}

		const emailField = form.querySelector(SELECTORS.emailField);
		const submitBtn = form.querySelector(SELECTORS.submitButton);

		if (!emailField) {
			console.error('Newsletter form: email field not found');
			showMessage(form, 'error', i18n.errorGeneral);
			return;
		}

		const email = String(emailField.value ?? '').trim();

		if (!email) {
			showMessage(form, 'error', i18n.errorEmpty);
			emailField.focus();
			return;
		}

		if (!isValidEmail(email)) {
			showMessage(form, 'error', i18n.errorInvalid);
			emailField.focus();
			return;
		}

		setBusy(form, submitBtn, true);

		const source = getSource(form);
		const tags = parseTags(form);

		window.mediciEvents
			.subscribeNewsletter(email, { source, tags })
			.then((result) => {
				const message =
					(result && typeof result.message === 'string' && result.message.trim()) ||
					`${i18n.success} ${i18n.successSuffix}`.trim();

				showMessage(form, 'success', message);
				form.reset();
				trackNewsletter(source);
			})
			.catch((error) => {
				const message =
					(error && typeof error.message === 'string' && error.message.trim()) || i18n.errorGeneral;

				showMessage(form, 'error', message);
				console.error('Newsletter subscription error:', error);
			})
			.finally(() => {
				setBusy(form, submitBtn, false);
			});
	}

	function init() {
		if (state.initialized) {
			return;
		}

		const forms = document.querySelectorAll(SELECTORS.form);
		if (!forms.length) {
			return;
		}

		forms.forEach((form) => {
			if (!(form instanceof HTMLFormElement)) {
				return;
			}

			const handler = handleSubmit.bind(null, form);
			state.handlers.set(form, handler);
			form.addEventListener('submit', handler);
			state.forms.push(form);
		});

		state.initialized = true;
	}

	function destroy() {
		if (!state.initialized) {
			return;
		}

		state.forms.forEach((form) => {
			const handler = state.handlers.get(form);
			if (handler) {
				form.removeEventListener('submit', handler);
				state.handlers.delete(form);
			}
		});

		state.forms = [];
		state.initialized = false;
	}

	window.MediciNewsletterForm = {
		init,
		destroy,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

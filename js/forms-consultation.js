/**
 * Consultation Request Form Handler
 *
 * Usage: Add class 'js-consultation-form' to your form element.
 * (Legacy: 'consultation-form' still supported for backwards compatibility)
 *
 * Required fields: name, email, phone, consent
 * Optional fields: message, service
 *
 * JS Hooks (recommended):
 * - .js-consultation-form - Form element
 * - .js-consultation-message - Message display element
 *
 * API:
 * - window.MediciConsultationForm.init() - Initialize forms
 * - window.MediciConsultationForm.destroy() - Cleanup event listeners
 *
 * @since 1.4.0
 * @version 1.5.1 Fixed syntax errors, aligned textarea wrapper with CSS, improved validation UX
 */

(function () {
	'use strict';

	const SELECTORS = {
		form: '.js-consultation-form, .consultation-form',
		message: '.js-consultation-message, .consultation-message, .consultation-form__message',
		nameField: 'input[name="name"]',
		emailField: 'input[name="email"], input[type="email"]',
		phoneField: 'input[name="phone"], input[type="tel"]',
		messageField: 'textarea[name="message"]',
		serviceField: 'input[name="service"], select[name="service"]',
		consentField: 'input[name="consent"]',
		submitButton: 'button[type="submit"], input[type="submit"]',
	};

	const DEFAULT_I18N = {
		sending: 'Відправка...',
		submit: 'Відправити',
		errorName: "Будь ласка, вкажіть ваше ім'я",
		errorEmail: 'Будь ласка, вкажіть email',
		errorEmailInvalid: 'Будь ласка, вкажіть коректний email',
		errorPhone: 'Будь ласка, вкажіть номер телефону',
		errorPhoneInvalid: 'Будь ласка, вкажіть коректний номер телефону',
		errorConsent: 'Для відправки потрібна ваша згода на обробку персональних даних',
		errorGeneral: 'Сталася помилка. Спробуйте ще раз.',
		success: 'Дякуємо! Заявку отримано.',
	};

	const i18n = window.mediciData?.i18n?.consultation ?? DEFAULT_I18N;

	const state = {
		initialized: false,
		forms: [],
		submitHandlers: new WeakMap(),
		textareaHandlers: new WeakMap(),
	};

	function isValidEmail(email) {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(email);
	}

	function isValidPhone(phone) {
		// Allow digits, spaces, dashes, parentheses, plus sign; basic length check.
		const phoneRegex = /^[\d\s\-()+]{10,}$/;
		return phoneRegex.test(phone);
	}

	function getOrCreateMessageElement(form) {
		const existing = form.querySelector(SELECTORS.message);
		if (existing) {
			return existing;
		}

		const messageEl = document.createElement('div');
		messageEl.className = 'consultation-form__message consultation-message';
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
			'consultation-form__message',
			`consultation-form__message--${safeType}`,
			'consultation-message',
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

		submitBtn.disabled = Boolean(isBusy);
		if (submitBtn.value != null) {
			submitBtn.value = isBusy ? i18n.sending : i18n.submit;
		}
	}

	function clearFieldErrors(form) {
		const errorFields = form.querySelectorAll('.consultation-form__field--error');
		errorFields.forEach((el) => el.classList.remove('consultation-form__field--error'));

		const errorControls = form.querySelectorAll(
			'.consultation-form__input--error, .consultation-form__select--error, .consultation-form__textarea--error',
		);

		errorControls.forEach((el) => {
			el.classList.remove(
				'consultation-form__input--error',
				'consultation-form__select--error',
				'consultation-form__textarea--error',
			);
		});
	}

	function markControlError(control) {
		if (!control) {
			return;
		}

		const field = control.closest('.consultation-form__field');
		if (field) {
			field.classList.add('consultation-form__field--error');
		}

		if (control.matches('select')) {
			control.classList.add('consultation-form__select--error');
			return;
		}

		if (control.matches('textarea')) {
			control.classList.add('consultation-form__textarea--error');
			return;
		}

		control.classList.add('consultation-form__input--error');
	}

	function initAutogrowingTextarea(form) {
		const textareas = form.querySelectorAll('textarea');
		textareas.forEach((textarea) => {
			if (!(textarea instanceof HTMLTextAreaElement)) {
				return;
			}

			// If already wrapped with our wrapper, do nothing.
			if (textarea.parentElement && textarea.parentElement.classList.contains('consultation-form__textarea-wrapper')) {
				return;
			}

			// If legacy wrapper exists, keep it and just bind handler.
			if (textarea.parentElement && textarea.parentElement.classList.contains('textarea-wrapper')) {
				const wrapper = textarea.parentElement;
				const inputHandler = () => {
					wrapper.setAttribute('data-cloned-val', textarea.value);
				};

				state.textareaHandlers.set(textarea, inputHandler);
				textarea.addEventListener('input', inputHandler);
				wrapper.setAttribute('data-cloned-val', textarea.value);

				return;
			}

			// Create wrapper aligned with CSS (.consultation-form__textarea-wrapper),
			// and also keep legacy class for backwards compatibility.
			const wrapper = document.createElement('div');
			wrapper.className = 'consultation-form__textarea-wrapper textarea-wrapper';
			wrapper.setAttribute('data-cloned-val', textarea.value);

			const parent = textarea.parentNode;
			if (!parent) {
				return;
			}

			parent.insertBefore(wrapper, textarea);
			wrapper.appendChild(textarea);

			const inputHandler = () => {
				wrapper.setAttribute('data-cloned-val', textarea.value);
			};

			state.textareaHandlers.set(textarea, inputHandler);
			textarea.addEventListener('input', inputHandler);
			wrapper.setAttribute('data-cloned-val', textarea.value);
		});
	}

	function trackConsultation(service) {
		if (typeof window.gtag !== 'function') {
			return;
		}

		window.gtag('event', 'consultation_request', {
			event_category: 'lead',
			event_label: service || 'general',
		});
	}

	function handleSubmit(form, event) {
		event.preventDefault();

		if (!window.mediciEvents || typeof window.mediciEvents.requestConsultation !== 'function') {
			console.warn('Consultation forms: mediciEvents.requestConsultation API not available');
			showMessage(form, 'error', i18n.errorGeneral);
			return;
		}

		clearFieldErrors(form);

		const nameField = form.querySelector(SELECTORS.nameField);
		const emailField = form.querySelector(SELECTORS.emailField);
		const phoneField = form.querySelector(SELECTORS.phoneField);
		const messageField = form.querySelector(SELECTORS.messageField);
		const serviceField = form.querySelector(SELECTORS.serviceField);
		const consentField = form.querySelector(SELECTORS.consentField);
		const submitBtn = form.querySelector(SELECTORS.submitButton);

		if (!nameField || !emailField || !phoneField || !consentField) {
			console.error('Consultation form: required fields not found');
			showMessage(form, 'error', i18n.errorGeneral);
			return;
		}

		const name = String(nameField.value ?? '').trim();
		const email = String(emailField.value ?? '').trim();
		const phone = String(phoneField.value ?? '').trim();
		const consent = Boolean(consentField.checked);

		if (!name) {
			markControlError(nameField);
			showMessage(form, 'error', i18n.errorName);
			nameField.focus();
			return;
		}

		if (!email) {
			markControlError(emailField);
			showMessage(form, 'error', i18n.errorEmail);
			emailField.focus();
			return;
		}

		if (!isValidEmail(email)) {
			markControlError(emailField);
			showMessage(form, 'error', i18n.errorEmailInvalid);
			emailField.focus();
			return;
		}

		if (!phone) {
			markControlError(phoneField);
			showMessage(form, 'error', i18n.errorPhone);
			phoneField.focus();
			return;
		}

		if (!isValidPhone(phone)) {
			markControlError(phoneField);
			showMessage(form, 'error', i18n.errorPhoneInvalid);
			phoneField.focus();
			return;
		}

		if (!consent) {
			markControlError(consentField);
			showMessage(form, 'error', i18n.errorConsent);
			consentField.focus();
			return;
		}

		setBusy(form, submitBtn, true);

		const payload = {
			name,
			email,
			phone,
			message: messageField ? String(messageField.value ?? '').trim() : '',
			service: serviceField ? String(serviceField.value ?? '') : '',
			consent,
		};

		window.mediciEvents
			.requestConsultation(payload)
			.then((result) => {
				const message =
					(result && typeof result.message === 'string' && result.message.trim()) || i18n.success;

				showMessage(form, 'success', message);
				form.reset();
				clearFieldErrors(form);
				trackConsultation(payload.service);
			})
			.catch((error) => {
				const message =
					(error && typeof error.message === 'string' && error.message.trim()) || i18n.errorGeneral;

				showMessage(form, 'error', message);
				console.error('Consultation request error:', error);
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
			state.submitHandlers.set(form, handler);
			form.addEventListener('submit', handler);

			initAutogrowingTextarea(form);

			state.forms.push(form);
		});

		state.initialized = true;
	}

	function destroy() {
		if (!state.initialized) {
			return;
		}

		state.forms.forEach((form) => {
			const handler = state.submitHandlers.get(form);
			if (handler) {
				form.removeEventListener('submit', handler);
				state.submitHandlers.delete(form);
			}

			const textareas = form.querySelectorAll('textarea');
			textareas.forEach((textarea) => {
				const inputHandler = state.textareaHandlers.get(textarea);
				if (inputHandler) {
					textarea.removeEventListener('input', inputHandler);
					state.textareaHandlers.delete(textarea);
				}
			});
		});

		state.forms = [];
		state.initialized = false;
	}

	window.MediciConsultationForm = {
		init,
		destroy,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
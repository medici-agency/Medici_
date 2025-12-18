/**
 * Webhook Admin JavaScript
 *
 * Vanilla JS version (migrated from jQuery).
 *
 * @package
 * @since   1.5.0
 * @version 2.0.0
 */

(function () {
	'use strict';

	/**
	 * Initialize webhook admin
	 */
	function init() {
		bindTestWebhook();
		bindAuthTypeToggle();
	}

	/**
	 * Bind test webhook button click (event delegation)
	 */
	function bindTestWebhook() {
		document.addEventListener('click', function (e) {
			const button = e.target.closest('.test-webhook');
			if (!button) {
				return;
			}

			e.preventDefault();

			const webhookId = button.dataset.webhookId;
			const originalText = button.textContent;

			if (!webhookId) {
				return;
			}

			// Set loading state
			button.classList.add('loading');
			button.textContent = mediciWebhook.i18n.testing;

			// Prepare form data
			const formData = new FormData();
			formData.append('action', 'medici_test_webhook');
			formData.append('nonce', mediciWebhook.nonce);
			formData.append('webhook_id', webhookId);

			// Send fetch request
			fetch(mediciWebhook.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					button.classList.remove('loading');

					if (data.success) {
						button.classList.add('success');
						button.textContent = mediciWebhook.i18n.testSuccess;
					} else {
						button.classList.add('error');
						button.textContent = mediciWebhook.i18n.testError + ': ' + (data.data?.message || '');
					}

					// Reset after delay
					setTimeout(function () {
						button.classList.remove('success', 'error');
						button.textContent = originalText;
					}, 3000);
				})
				.catch(function () {
					button.classList.remove('loading');
					button.classList.add('error');
					button.textContent = mediciWebhook.i18n.testError;

					setTimeout(function () {
						button.classList.remove('error');
						button.textContent = originalText;
					}, 3000);
				});
		});
	}

	/**
	 * Bind auth type dropdown toggle
	 */
	function bindAuthTypeToggle() {
		const authType = document.getElementById('webhook_auth_type');
		const authValueRow = document.getElementById('auth_value_row');

		if (!authType) {
			return;
		}

		authType.addEventListener('change', function () {
			const value = this.value;

			if (value) {
				authValueRow.style.display = '';
			} else {
				authValueRow.style.display = 'none';
				const authValue = document.getElementById('webhook_auth_value');
				if (authValue) {
					authValue.value = '';
				}
			}
		});
	}

	// Initialize on DOMContentLoaded
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

/**
 * Medici Events API - Frontend Module
 *
 * Unified JavaScript module for sending events to backend.
 * Supports: Newsletter subscription, Consultation requests, and custom events.
 *
 * @param window
 * @package
 * @since   1.4.0
 * @version 1.2.0
 */

(function (window) {
	'use strict';

	/**
	 * Events API Module
	 *
	 * Usage:
	 * mediciEvents.send('newsletter_subscribe', { email: 'user@example.com' })
	 *   .then((result) => console.log(result))
	 *   .catch((error) => console.error(error));
	 */
	const mediciEvents = {
		/**
		 * Send event to backend
		 *
		 * @param {string} eventType - Event type identifier
		 * @param {Object} payload   - Event data
		 * @return {Promise} Response from backend
		 */
		send(eventType, payload) {
			// Validate input
			if (!eventType || typeof eventType !== 'string') {
				return Promise.reject(new Error('Event type is required'));
			}

			if (!payload || typeof payload !== 'object') {
				return Promise.reject(new Error('Payload must be an object'));
			}

			// Check if mediciData is available
			if (typeof mediciData === 'undefined') {
				return Promise.reject(new Error('Medici Events API not initialized'));
			}

			// Prepare FormData
			const data = new FormData();
			data.append('action', 'medici_event');
			data.append('nonce', mediciData.eventNonce);
			data.append('event_type', eventType);

			// Add payload fields
			this._appendPayload(data, payload);

			// Send request
			return fetch(mediciData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: data,
			})
				.then((response) => {
					// Check HTTP status first
					if (!response.ok) {
						throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
					}

					// Try to parse JSON, handle non-JSON responses
					return response.json().catch(() => {
						throw new Error('Invalid JSON response from server');
					});
				})
				.then((result) => {
					if (result?.success) {
						// Return full data object (includes event_id, message, etc.)
						return result.data;
					}
					// Extract error message from response
					const errorMessage = result?.data?.message || 'Unknown error occurred';
					throw new Error(errorMessage);
				});
		},

		/**
		 * Append payload to FormData
		 *
		 * @private
		 * @param {FormData} formData - FormData object
		 * @param {Object}   payload  - Payload data
		 */
		_appendPayload(formData, payload) {
			Object.keys(payload).forEach((key) => {
				const value = payload[key];

				if (Array.isArray(value)) {
					// Handle arrays
					value.forEach((item, index) => {
						formData.append(`payload[${key}][${index}]`, item);
					});
				} else if (typeof value === 'boolean') {
					// Handle booleans
					formData.append(`payload[${key}]`, value ? '1' : '0');
				} else if (value !== null && value !== undefined) {
					// Handle other types
					formData.append(`payload[${key}]`, String(value));
				}
			});
		},

		/**
		 * Subscribe to newsletter
		 *
		 * Helper method for newsletter subscription.
		 *
		 * @param {string} email   - Email address
		 * @param {Object} options - Additional options (source, tags, UTM params)
		 * @return {Promise} Response from backend
		 */
		subscribeNewsletter(email, options = {}) {
			const payload = {
				email,
				source: options.source || 'unknown',
				tags: options.tags || [],
				page_url: window.location.href,
			};

			// Add UTM params if available
			const utmParams = this._getUTMParams();
			if (utmParams) {
				Object.assign(payload, utmParams);
			}

			return this.send('newsletter_subscribe', payload);
		},

		/**
		 * Request consultation
		 *
		 * Helper method for consultation request.
		 *
		 * @param {Object} data - Form data (name, email, phone, message, service)
		 * @return {Promise} Response from backend
		 */
		requestConsultation(data) {
			if (!data?.name || !data?.email || !data?.phone) {
				return Promise.reject(new Error('Name, email and phone are required'));
			}

			const payload = {
				name: data.name,
				email: data.email,
				phone: data.phone,
				message: data.message || '',
				service: data.service || '',
				consent: data.consent || false,
				page_url: window.location.href,
			};

			// Add UTM params if available
			const utmParams = this._getUTMParams();
			if (utmParams) {
				Object.assign(payload, utmParams);
			}

			return this.send('consultation_request', payload);
		},

		/**
		 * Get UTM parameters from URL
		 *
		 * @private
		 * @return {Object|null} UTM parameters or null
		 */
		_getUTMParams() {
			const urlParams = new URLSearchParams(window.location.search);
			const utmParams = {};
			let hasParams = false;

			['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((param) => {
				const value = urlParams.get(param);
				if (value) {
					utmParams[param] = value;
					hasParams = true;
				}
			});

			return hasParams ? utmParams : null;
		},
	};

	// Expose to global scope
	window.mediciEvents = mediciEvents;
})(window);

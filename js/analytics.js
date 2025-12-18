/**
 * Medici Analytics Module
 *
 * Handles GA4 Events tracking:
 * - Scroll depth (25%, 50%, 75%, 100%)
 * - Time on page (30s, 60s, 2min, 5min)
 * - CTA clicks
 * - Form interactions
 * - UTM parameter storage
 *
 * @package
 * @since 1.6.0
 */

(function () {
	'use strict';

	// Check if gtag exists (GA4).
	const hasGtag = typeof gtag === 'function';

	// Configuration (can be overridden via window.mediciAnalyticsConfig).
	const config = Object.assign(
		{
			scrollDepth: [25, 50, 75, 100],
			timeOnPage: [30, 60, 120, 300],
			ctaSelector: '[data-track-cta]',
			formSelector: 'form[data-track-form]',
		},
		window.mediciAnalyticsConfig || {}
	);

	/**
	 * Send event to GA4.
	 *
	 * @param {string} eventName Event name.
	 * @param {Object} params    Event parameters.
	 */
	const sendEvent = (eventName, params = {}) => {
		if (hasGtag) {
			gtag('event', eventName, params);
		}

		// Debug mode.
		if (window.location.search.includes('debug_analytics=1')) {
			console.log('[Medici Analytics]', eventName, params);
		}
	};

	/**
	 * Scroll Depth Tracking
	 */
	const ScrollDepthTracker = {
		tracked: {},

		init() {
			config.scrollDepth.forEach((percent) => {
				this.tracked[percent] = false;
			});

			window.addEventListener('scroll', () => this.check(), { passive: true });
		},

		check() {
			const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
			if (scrollHeight <= 0) {
				return;
			}

			const scrollPercent = Math.round((window.scrollY / scrollHeight) * 100);

			config.scrollDepth.forEach((milestone) => {
				if (scrollPercent >= milestone && !this.tracked[milestone]) {
					this.tracked[milestone] = true;
					sendEvent('scroll_depth', {
						percent: milestone,
						page_path: window.location.pathname,
					});
				}
			});
		},
	};

	/**
	 * Time on Page Tracking
	 */
	const TimeOnPageTracker = {
		init() {
			config.timeOnPage.forEach((seconds) => {
				setTimeout(() => {
					sendEvent('time_on_page', {
						seconds,
						page_path: window.location.pathname,
					});
				}, seconds * 1000);
			});
		},
	};

	/**
	 * CTA Click Tracking
	 */
	const CTATracker = {
		init() {
			document.addEventListener('click', (e) => {
				const cta = e.target.closest(config.ctaSelector);
				if (!cta) {
					return;
				}

				const ctaName = cta.dataset.trackCta || cta.textContent.trim().substring(0, 50);
				const ctaType = cta.dataset.trackCtaType || 'button';

				sendEvent('cta_click', {
					cta_name: ctaName,
					cta_type: ctaType,
					page_path: window.location.pathname,
				});
			});
		},
	};

	/**
	 * Form Interaction Tracking
	 */
	const FormTracker = {
		interacted: new Set(),

		init() {
			// Track form focus (start).
			document.addEventListener('focusin', (e) => {
				const form = e.target.closest(config.formSelector);
				if (!form) {
					return;
				}

				const formId = form.id || form.dataset.trackForm || 'unknown';

				if (!this.interacted.has(formId)) {
					this.interacted.add(formId);
					sendEvent('form_start', {
						form_id: formId,
						page_path: window.location.pathname,
					});
				}
			});

			// Track form submit.
			document.addEventListener('submit', (e) => {
				const form = e.target.closest(config.formSelector);
				if (!form) {
					return;
				}

				const formId = form.id || form.dataset.trackForm || 'unknown';

				sendEvent('form_submit', {
					form_id: formId,
					page_path: window.location.pathname,
				});
			});
		},
	};

	/**
	 * UTM Parameter Storage
	 */
	const UTMTracker = {
		storageKey: 'medici_utm',
		sessionKey: 'medici_session_id',

		init() {
			if (!window.mediciAnalytics?.utmStorage) {
				return;
			}

			this.captureUTM();
			this.captureReferrer();
		},

		captureUTM() {
			const params = new URLSearchParams(window.location.search);
			const utmParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

			const utm = {};
			let hasUTM = false;

			utmParams.forEach((param) => {
				if (params.has(param)) {
					utm[param] = params.get(param);
					hasUTM = true;
				}
			});

			if (hasUTM) {
				// Store in localStorage (persists).
				const existing = this.getStoredUTM();

				// First touch attribution - keep original UTM if exists.
				if (!existing.utm_source) {
					utm.first_touch = true;
					utm.landing_page = window.location.pathname;
					utm.timestamp = new Date().toISOString();
					localStorage.setItem(this.storageKey, JSON.stringify(utm));
				}

				// Last touch - always update session storage.
				utm.last_touch = true;
				sessionStorage.setItem(this.storageKey + '_last', JSON.stringify(utm));

				// Send to server.
				this.sendToServer(utm);
			}
		},

		captureReferrer() {
			const referrer = document.referrer;
			if (!referrer) {
				return;
			}

			// Don't track internal referrers.
			if (referrer.includes(window.location.hostname)) {
				return;
			}

			const existing = this.getStoredUTM();
			if (existing.referrer) {
				return;
			}

			const data = {
				referrer,
				landing_page: window.location.pathname,
				timestamp: new Date().toISOString(),
			};

			// Parse referrer for source.
			if (referrer.includes('google.')) {
				data.utm_source = 'google';
				data.utm_medium = 'organic';
			} else if (referrer.includes('facebook.') || referrer.includes('fb.')) {
				data.utm_source = 'facebook';
				data.utm_medium = 'referral';
			} else if (referrer.includes('instagram.')) {
				data.utm_source = 'instagram';
				data.utm_medium = 'referral';
			} else if (referrer.includes('linkedin.')) {
				data.utm_source = 'linkedin';
				data.utm_medium = 'referral';
			} else if (referrer.includes('t.me') || referrer.includes('telegram.')) {
				data.utm_source = 'telegram';
				data.utm_medium = 'referral';
			}

			if (data.utm_source) {
				localStorage.setItem(this.storageKey, JSON.stringify(data));
				this.sendToServer(data);
			}
		},

		getStoredUTM() {
			try {
				return JSON.parse(localStorage.getItem(this.storageKey)) || {};
			} catch (e) {
				return {};
			}
		},

		getSessionId() {
			let sessionId = sessionStorage.getItem(this.sessionKey);
			if (!sessionId) {
				sessionId = 'session_' + Math.random().toString(36).substring(2, 15);
				sessionStorage.setItem(this.sessionKey, sessionId);
			}
			return sessionId;
		},

		sendToServer(data) {
			if (!window.mediciAnalytics?.ajaxUrl) {
				return;
			}

			const formData = new FormData();
			formData.append('action', 'medici_save_utm');
			formData.append('nonce', window.mediciAnalytics.nonce);
			formData.append('session_id', this.getSessionId());

			Object.keys(data).forEach((key) => {
				formData.append(key, data[key]);
			});

			fetch(window.mediciAnalytics.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			}).catch(() => {
				// Silently fail - analytics should not break the site.
			});
		},

		/**
		 * Get UTM data for form submission.
		 *
		 * @return {Object} UTM data.
		 */
		getForForm() {
			const firstTouch = this.getStoredUTM();
			const lastTouch = (() => {
				try {
					return JSON.parse(sessionStorage.getItem(this.storageKey + '_last')) || {};
				} catch (e) {
					return {};
				}
			})();

			return {
				// Use last touch for attribution, fallback to first touch.
				utm_source: lastTouch.utm_source || firstTouch.utm_source || '',
				utm_medium: lastTouch.utm_medium || firstTouch.utm_medium || '',
				utm_campaign: lastTouch.utm_campaign || firstTouch.utm_campaign || '',
				utm_term: lastTouch.utm_term || firstTouch.utm_term || '',
				utm_content: lastTouch.utm_content || firstTouch.utm_content || '',
				landing_page: firstTouch.landing_page || '',
				session_id: this.getSessionId(),
			};
		},
	};

	/**
	 * Initialize all trackers.
	 */
	const init = () => {
		ScrollDepthTracker.init();
		TimeOnPageTracker.init();
		CTATracker.init();
		FormTracker.init();
		UTMTracker.init();

		// Send page view event.
		sendEvent('page_view_custom', {
			page_path: window.location.pathname,
			page_title: document.title,
			referrer: document.referrer,
		});
	};

	// Expose UTM getter for forms.
	window.mediciGetUTM = UTMTracker.getForForm.bind(UTMTracker);

	// Initialize on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

/**
 * Medici Cookie Notice - Frontend JavaScript
 *
 * @version 1.0.0
 * @package Medici_Cookie_Notice
 */

(function () {
	'use strict';

	// Prevent running in iframes
	if (window !== window.top) {
		return;
	}

	/**
	 * Cookie Notice Module
	 */
	const MediciCookieNotice = {
		config: null,
		consent: null,
		elements: {
			banner: null,
			modal: null,
			revokeBtn: null,
		},

		/**
		 * Initialize
		 */
		init: function () {
			// Get config from localized script
			if (typeof mcnConfig === 'undefined') {
				console.warn('Medici Cookie Notice: Config not found');
				return;
			}

			this.config = mcnConfig;
			this.consent = this.getStoredConsent();

			// Cache DOM elements
			this.elements.banner = document.getElementById('mcn-cookie-banner');
			this.elements.modal = document.getElementById('mcn-settings-modal');
			this.elements.revokeBtn = document.getElementById('mcn-revoke-button');

			if (!this.elements.banner) {
				return;
			}

			// Bind events
			this.bindEvents();

			// Initialize Twemoji
			if (this.config.useTwemoji) {
				this.initTwemoji();
			}

			// Show banner or revoke button
			if (this.shouldShowBanner()) {
				this.showBanner();
			} else {
				this.showRevokeButton();
				this.enableScripts();
			}
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			// Delegate click events
			document.addEventListener('click', this.handleClick.bind(this));

			// Category expand toggles
			document.querySelectorAll('.mcn-category__expand').forEach((btn) => {
				btn.addEventListener('click', this.handleCategoryExpand.bind(this));
			});

			// Accept on scroll
			if (this.config.acceptOnScroll && this.shouldShowBanner()) {
				window.addEventListener('scroll', this.handleScroll.bind(this), {
					once: true,
				});
			}

			// Accept on click outside
			if (this.config.acceptOnClick && this.shouldShowBanner()) {
				document.addEventListener('click', this.handleOutsideClick.bind(this));
			}

			// Keyboard navigation
			document.addEventListener('keydown', this.handleKeydown.bind(this));
		},

		/**
		 * Handle click events
		 * @param {Event} e
		 */
		handleClick: function (e) {
			const target = e.target.closest('[data-action]');
			if (!target) return;

			const action = target.dataset.action;

			switch (action) {
				case 'accept-all':
					this.acceptAll();
					break;
				case 'reject-all':
					this.rejectAll();
					break;
				case 'open-settings':
					this.openSettingsModal();
					break;
				case 'close-modal':
					this.closeSettingsModal();
					break;
				case 'save-preferences':
					this.savePreferences();
					break;
				case 'revoke':
					this.revokeConsent();
					break;
				case 'accept-category':
					this.acceptCategory(target.dataset.category);
					break;
			}
		},

		/**
		 * Handle category expand
		 * @param {Event} e
		 */
		handleCategoryExpand: function (e) {
			const btn = e.currentTarget;
			const details = btn.closest('.mcn-category').querySelector('.mcn-category__details');

			if (details) {
				const isExpanded = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', !isExpanded);
				details.hidden = isExpanded;
			}
		},

		/**
		 * Handle scroll (accept on scroll)
		 */
		handleScroll: function () {
			if (window.scrollY > this.config.scrollOffset) {
				this.acceptAll();
			}
		},

		/**
		 * Handle click outside banner
		 * @param {Event} e
		 */
		handleOutsideClick: function (e) {
			if (!this.elements.banner.contains(e.target) && !this.elements.modal?.contains(e.target)) {
				this.acceptAll();
			}
		},

		/**
		 * Handle keyboard navigation
		 * @param {Event} e
		 */
		handleKeydown: function (e) {
			if (e.key === 'Escape') {
				if (this.elements.modal && !this.elements.modal.classList.contains('mcn-modal--hidden')) {
					this.closeSettingsModal();
				}
			}
		},

		/**
		 * Check if banner should be shown
		 * @returns {boolean}
		 */
		shouldShowBanner: function () {
			if (this.config.debugMode) {
				return true;
			}
			return !this.consent;
		},

		/**
		 * Show banner
		 */
		showBanner: function () {
			if (!this.elements.banner) return;

			// Remove hidden class after small delay for animation
			requestAnimationFrame(() => {
				this.elements.banner.classList.remove('mcn-banner--hidden');
			});

			// Dispatch event
			this.dispatchEvent('showCookieNotice');
		},

		/**
		 * Hide banner
		 */
		hideBanner: function () {
			if (!this.elements.banner) return;

			this.elements.banner.classList.add('mcn-banner--hidden');

			// Dispatch event
			this.dispatchEvent('hideCookieNotice');
		},

		/**
		 * Show revoke button
		 */
		showRevokeButton: function () {
			if (!this.elements.revokeBtn) return;

			requestAnimationFrame(() => {
				this.elements.revokeBtn.classList.remove('mcn-revoke-btn--hidden');
			});
		},

		/**
		 * Hide revoke button
		 */
		hideRevokeButton: function () {
			if (!this.elements.revokeBtn) return;

			this.elements.revokeBtn.classList.add('mcn-revoke-btn--hidden');
		},

		/**
		 * Open settings modal
		 */
		openSettingsModal: function () {
			if (!this.elements.modal) return;

			// Restore saved preferences
			if (this.consent && this.consent.categories) {
				Object.keys(this.consent.categories).forEach((cat) => {
					const checkbox = this.elements.modal.querySelector(`[data-category="${cat}"]`);
					if (checkbox && !checkbox.disabled) {
						checkbox.checked = this.consent.categories[cat];
					}
				});
			} else {
				// Default: necessary only
				this.elements.modal.querySelectorAll('.mcn-category__checkbox').forEach((cb) => {
					if (!cb.disabled) {
						cb.checked = false;
					}
				});
			}

			this.elements.modal.classList.remove('mcn-modal--hidden');

			// Focus first interactive element
			const firstBtn = this.elements.modal.querySelector('.mcn-btn');
			if (firstBtn) {
				firstBtn.focus();
			}

			// Trap focus in modal
			this.trapFocus(this.elements.modal);
		},

		/**
		 * Close settings modal
		 */
		closeSettingsModal: function () {
			if (!this.elements.modal) return;

			this.elements.modal.classList.add('mcn-modal--hidden');

			// Return focus to settings button
			const settingsBtn = this.elements.banner.querySelector('[data-action="open-settings"]');
			if (settingsBtn) {
				settingsBtn.focus();
			}
		},

		/**
		 * Accept all cookies
		 */
		acceptAll: function () {
			const categories = this.getAllCategories(true);
			this.saveConsent(categories, 'accepted');
			this.hideBanner();
			this.closeSettingsModal();
			this.showRevokeButton();
			this.enableScripts();
			this.updateGoogleConsentMode(categories);

			if (this.config.reloadOnChange) {
				window.location.reload();
			}
		},

		/**
		 * Reject all cookies
		 */
		rejectAll: function () {
			const categories = this.getAllCategories(false);
			// Keep necessary enabled
			categories.necessary = true;

			this.saveConsent(categories, 'rejected');
			this.hideBanner();
			this.closeSettingsModal();
			this.showRevokeButton();
		},

		/**
		 * Save preferences from modal
		 */
		savePreferences: function () {
			const categories = {};

			if (this.elements.modal) {
				this.elements.modal.querySelectorAll('.mcn-category__checkbox').forEach((cb) => {
					const cat = cb.dataset.category;
					categories[cat] = cb.checked;
				});
			}

			// Ensure necessary is always enabled
			categories.necessary = true;

			this.saveConsent(categories, 'custom');
			this.hideBanner();
			this.closeSettingsModal();
			this.showRevokeButton();
			this.enableScripts();
			this.updateGoogleConsentMode(categories);

			if (this.config.reloadOnChange) {
				window.location.reload();
			}
		},

		/**
		 * Accept specific category
		 * @param {string} category
		 */
		acceptCategory: function (category) {
			let categories = this.consent?.categories || this.getAllCategories(false);
			categories[category] = true;
			categories.necessary = true;

			this.saveConsent(categories, 'custom');
			this.enableScripts();
			this.updateGoogleConsentMode(categories);

			// Reload iframe placeholder
			document
				.querySelectorAll(`[data-mcn-category="${category}"][data-mcn-blocked="true"]`)
				.forEach((el) => {
					this.unblockElement(el);
				});
		},

		/**
		 * Revoke consent
		 */
		revokeConsent: function () {
			this.hideRevokeButton();
			this.showBanner();
		},

		/**
		 * Get all categories
		 * @param {boolean} value
		 * @returns {Object}
		 */
		getAllCategories: function (value) {
			const categories = {};

			if (this.config.categories) {
				Object.keys(this.config.categories).forEach((cat) => {
					const catConfig = this.config.categories[cat];
					categories[cat] = catConfig.required ? true : value;
				});
			}

			return categories;
		},

		/**
		 * Save consent
		 * @param {Object} categories
		 * @param {string} status
		 */
		saveConsent: function (categories, status) {
			const consentId = this.consent?.id || this.generateConsentId();
			const expiry =
				status === 'rejected' ? this.config.cookieExpiryRejected : this.config.cookieExpiry;

			this.consent = {
				id: consentId,
				categories: categories,
				status: status,
				timestamp: Date.now(),
			};

			// Save to cookie
			this.setCookie(this.config.cookieName, JSON.stringify(this.consent), expiry);

			// Send to server
			this.sendConsentToServer(consentId, categories, status);

			// Update body classes (як в оригінальному cookie-notice)
			this.updateBodyClasses();

			// Show/hide conditional content
			this.showConditionalContent();

			// Dispatch event
			this.dispatchEvent('setCookieNotice', {
				consentId: consentId,
				categories: categories,
				status: status,
			});
		},

		/**
		 * Send consent to server via REST API (with AJAX fallback)
		 * @param {string} consentId
		 * @param {Object} categories
		 * @param {string} status
		 */
		sendConsentToServer: function (consentId, categories, status) {
			// Try REST API first (Cloudflare-friendly)
			if (this.config.restUrl) {
				this.sendConsentViaRest(consentId, categories, status);
			} else {
				// Fallback to AJAX
				this.sendConsentViaAjax(consentId, categories, status);
			}
		},

		/**
		 * Send consent via REST API
		 * @param {string} consentId
		 * @param {Object} categories
		 * @param {string} status
		 */
		sendConsentViaRest: function (consentId, categories, status) {
			const self = this;

			fetch(this.config.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.config.restNonce,
				},
				body: JSON.stringify({
					consent_id: consentId,
					categories: categories,
					status: status,
				}),
				credentials: 'same-origin',
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error('REST API failed');
					}
					return response.json();
				})
				.then((data) => {
					if (this.config.debugMode) {
						console.log('MCN: Consent saved via REST API', data);
					}
				})
				.catch((err) => {
					console.warn('MCN: REST API failed, trying AJAX fallback', err);
					// Fallback to AJAX if REST fails
					self.sendConsentViaAjax(consentId, categories, status);
				});
		},

		/**
		 * Send consent via AJAX (legacy/fallback)
		 * @param {string} consentId
		 * @param {Object} categories
		 * @param {string} status
		 */
		sendConsentViaAjax: function (consentId, categories, status) {
			const formData = new FormData();
			formData.append('action', 'mcn_save_consent');
			formData.append('nonce', this.config.nonce);
			formData.append('consent_id', consentId);
			formData.append('status', status);

			Object.keys(categories).forEach((cat) => {
				formData.append(`categories[${cat}]`, categories[cat] ? '1' : '0');
			});

			fetch(this.config.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then((response) => {
					if (this.config.debugMode) {
						console.log('MCN: Consent saved via AJAX');
					}
				})
				.catch((err) => {
					console.warn('MCN: Failed to save consent to server', err);
				});
		},

		/**
		 * Get stored consent
		 * @returns {Object|null}
		 */
		getStoredConsent: function () {
			const cookie = this.getCookie(this.config.cookieName);
			if (!cookie) return null;

			try {
				return JSON.parse(cookie);
			} catch (e) {
				return null;
			}
		},

		/**
		 * Check if category is allowed
		 * @param {string} category
		 * @returns {boolean}
		 */
		isCategoryAllowed: function (category) {
			if (!this.consent || !this.consent.categories) {
				return false;
			}
			return this.consent.categories[category] === true;
		},

		/**
		 * Enable blocked scripts
		 */
		enableScripts: function () {
			if (!this.consent || !this.consent.categories) return;

			// Enable scripts
			document.querySelectorAll('script[data-mcn-blocked="true"]').forEach((script) => {
				const category = script.dataset.mcnCategory;
				if (this.isCategoryAllowed(category)) {
					this.unblockScript(script);
				}
			});

			// Enable iframes
			document.querySelectorAll('iframe[data-mcn-blocked="true"]').forEach((iframe) => {
				const category = iframe.dataset.mcnCategory;
				if (this.isCategoryAllowed(category)) {
					this.unblockElement(iframe);
				}
			});

			// Show conditional content (shortcode [mcn_cookies_accepted])
			// Адаптовано з оригінального cookie-notice плагіну
			this.showConditionalContent();
		},

		/**
		 * Show conditional content based on consent
		 * Підтримка shortcode [mcn_cookies_accepted]
		 */
		showConditionalContent: function () {
			document
				.querySelectorAll('.mcn-conditional-content[data-mcn-show-if-accepted]')
				.forEach((el) => {
					const category = el.dataset.mcnCategory;

					// Якщо вказана конкретна категорія - перевіряємо її
					if (category) {
						if (this.isCategoryAllowed(category)) {
							el.style.display = '';
						}
					} else {
						// Якщо категорія не вказана - показуємо при будь-якому accepted
						if (this.consent && this.consent.status === 'accepted') {
							el.style.display = '';
						}
					}
				});
		},

		/**
		 * Update body classes based on consent status
		 * Адаптовано з оригінального cookie-notice плагіну
		 */
		updateBodyClasses: function () {
			const body = document.body;

			// Remove old classes
			body.classList.remove(
				'cookies-not-set',
				'cookies-set',
				'cookies-accepted',
				'cookies-refused',
				'cookies-custom'
			);

			// Remove category classes
			body.className = body.className.replace(/cookies-category-\S+/g, '').trim();

			if (this.consent && this.consent.status) {
				body.classList.add('cookies-set');

				switch (this.consent.status) {
					case 'accepted':
						body.classList.add('cookies-accepted');
						break;
					case 'rejected':
						body.classList.add('cookies-refused');
						break;
					case 'custom':
						body.classList.add('cookies-custom');
						break;
				}

				// Add category classes
				if (this.consent.categories) {
					Object.keys(this.consent.categories).forEach((category) => {
						if (this.consent.categories[category]) {
							body.classList.add('cookies-category-' + category);
						}
					});
				}
			} else {
				body.classList.add('cookies-not-set');
			}
		},

		/**
		 * Unblock script
		 * @param {HTMLScriptElement} script
		 */
		unblockScript: function (script) {
			const newScript = document.createElement('script');

			// Copy attributes
			Array.from(script.attributes).forEach((attr) => {
				if (attr.name === 'type') {
					newScript.type = 'text/javascript';
				} else if (attr.name === 'data-mcn-src') {
					newScript.src = attr.value;
				} else if (!attr.name.startsWith('data-mcn-')) {
					newScript.setAttribute(attr.name, attr.value);
				}
			});

			// Copy inline content
			if (script.innerHTML) {
				newScript.innerHTML = script.innerHTML;
			}

			// Replace old script
			script.parentNode.replaceChild(newScript, script);
		},

		/**
		 * Unblock element (iframe)
		 * @param {HTMLElement} el
		 */
		unblockElement: function (el) {
			const src = el.dataset.mcnSrc;
			if (src) {
				el.src = src;
				el.removeAttribute('data-mcn-src');
				el.removeAttribute('data-mcn-blocked');
				el.removeAttribute('data-mcn-category');

				// Remove placeholder
				const placeholder = el.closest('.mcn-iframe-placeholder');
				if (placeholder) {
					const placeholderUI = placeholder.querySelector('.mcn-placeholder');
					if (placeholderUI) {
						placeholderUI.remove();
					}
					// Unwrap iframe
					placeholder.replaceWith(el);
				}
			}
		},

		/**
		 * Update Google Consent Mode
		 * @param {Object} categories
		 */
		updateGoogleConsentMode: function (categories) {
			if (!this.config.enableGcm || typeof gtag !== 'function') return;

			const consent = {
				ad_storage: categories.marketing ? 'granted' : 'denied',
				ad_user_data: categories.marketing ? 'granted' : 'denied',
				ad_personalization: categories.marketing ? 'granted' : 'denied',
				analytics_storage: categories.analytics ? 'granted' : 'denied',
				functionality_storage: categories.preferences ? 'granted' : 'denied',
				personalization_storage: categories.preferences ? 'granted' : 'denied',
			};

			gtag('consent', 'update', consent);

			// Dispatch event for other integrations
			this.dispatchEvent('googleConsentUpdate', consent);
		},

		/**
		 * Initialize Twemoji
		 */
		initTwemoji: function () {
			if (typeof twemoji === 'undefined') return;

			const options = {
				folder: 'svg',
				ext: '.svg',
			};

			// Use theme's Twemoji if available
			if (this.config.twemojiBase) {
				options.base = this.config.twemojiBase;
			}

			// Parse banner
			if (this.elements.banner) {
				twemoji.parse(this.elements.banner, options);
			}

			// Parse modal
			if (this.elements.modal) {
				twemoji.parse(this.elements.modal, options);
			}

			// Parse revoke button
			if (this.elements.revokeBtn) {
				twemoji.parse(this.elements.revokeBtn, options);
			}
		},

		/**
		 * Cookie helpers
		 */
		setCookie: function (name, value, days) {
			const expires = new Date();
			expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);

			let cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`;
			cookie += `; expires=${expires.toUTCString()}`;
			cookie += `; path=${this.config.cookiePath || '/'}`;

			if (this.config.cookieDomain) {
				cookie += `; domain=${this.config.cookieDomain}`;
			}

			if (this.config.cookieSecure && location.protocol === 'https:') {
				cookie += '; secure';
			}

			cookie += '; samesite=lax';

			document.cookie = cookie;
		},

		getCookie: function (name) {
			const cookies = document.cookie.split(';');
			const prefix = encodeURIComponent(name) + '=';

			for (let i = 0; i < cookies.length; i++) {
				let cookie = cookies[i].trim();
				if (cookie.indexOf(prefix) === 0) {
					return decodeURIComponent(cookie.substring(prefix.length));
				}
			}

			return null;
		},

		deleteCookie: function (name) {
			this.setCookie(name, '', -1);
		},

		/**
		 * Generate consent ID
		 * @returns {string}
		 */
		generateConsentId: function () {
			return (
				'mcn_' +
				Math.random().toString(36).substring(2, 15) +
				Math.random().toString(36).substring(2, 15)
			);
		},

		/**
		 * Dispatch custom event
		 * @param {string} name
		 * @param {Object} detail
		 */
		dispatchEvent: function (name, detail = {}) {
			const event = new CustomEvent(name, {
				bubbles: true,
				detail: detail,
			});
			document.dispatchEvent(event);
		},

		/**
		 * Trap focus within element
		 * @param {HTMLElement} el
		 */
		trapFocus: function (el) {
			const focusable = el.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			const firstFocusable = focusable[0];
			const lastFocusable = focusable[focusable.length - 1];

			el.addEventListener('keydown', function (e) {
				if (e.key !== 'Tab') return;

				if (e.shiftKey) {
					if (document.activeElement === firstFocusable) {
						lastFocusable.focus();
						e.preventDefault();
					}
				} else {
					if (document.activeElement === lastFocusable) {
						firstFocusable.focus();
						e.preventDefault();
					}
				}
			});
		},
	};

	/**
	 * Public API
	 */
	window.MediciCookieNotice = {
		/**
		 * Check if category is allowed
		 * @param {string} category
		 * @returns {boolean}
		 */
		isCategoryAllowed: function (category) {
			return MediciCookieNotice.isCategoryAllowed(category);
		},

		/**
		 * Get consent
		 * @returns {Object|null}
		 */
		getConsent: function () {
			return MediciCookieNotice.consent;
		},

		/**
		 * Accept all
		 */
		acceptAll: function () {
			MediciCookieNotice.acceptAll();
		},

		/**
		 * Reject all
		 */
		rejectAll: function () {
			MediciCookieNotice.rejectAll();
		},

		/**
		 * Open settings
		 */
		openSettings: function () {
			MediciCookieNotice.openSettingsModal();
		},

		/**
		 * Revoke consent
		 */
		revoke: function () {
			MediciCookieNotice.revokeConsent();
		},
	};

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			MediciCookieNotice.init();
		});
	} else {
		MediciCookieNotice.init();
	}
})();

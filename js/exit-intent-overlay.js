/**
 * Exit-Intent Overlay Popup - Complete Handler
 *
 * Version: 2.0.0
 * Features:
 * - bioEp (beeker1121) exit-intent detection
 * - GenerateBlocks Pro 2.3+ Overlay Panel trigger
 * - Events API: consultation_request
 *
 * @package
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

	/**
	 * Initialize Exit-Intent Handler
	 */
	function init() {
		// 1. Initialize bioEp exit-intent detection
		initBioEp();

		// 2. Initialize form handler
		initFormHandler();

		if (config.debug) {
			console.log('[Medici Exit-Intent] Initialized', config);
		}
	}

	/**
	 * Initialize bioEp Exit-Intent Detection
	 */
	function initBioEp() {
		// Check if bioEp is loaded
		if (typeof bioEp !== 'function') {
			if (config.debug) {
				console.warn('[Medici Exit-Intent] bioEp library not loaded');
			}
			return;
		}

		// Configure bioEp
		bioEp({
			// Cookie expiration (days)
			cookieExp: config.cookieExp,

			// Delay before showing (seconds)
			delay: config.delay,

			// Show only on desktop (>1024px)
			showOnDelay: false,
			showOnScroll: false,

			// Callback when exit-intent detected
			onExit: function () {
				openOverlayPanel();
			},
		});

		if (config.debug) {
			console.log('[Medici Exit-Intent] bioEp configured');
		}
	}

	/**
	 * Open GenerateBlocks Overlay Panel
	 */
	function openOverlayPanel() {
		// Find overlay trigger element
		const trigger = document.querySelector(`[data-gb-overlay="${config.overlayPanelId}"]`);

		if (!trigger) {
			if (config.debug) {
				console.error('[Medici Exit-Intent] Overlay trigger not found:', config.overlayPanelId);
			}
			return;
		}

		// Trigger click to open overlay
		trigger.click();

		if (config.debug) {
			console.log('[Medici Exit-Intent] Overlay opened:', config.overlayPanelId);
		}
	}

	/**
	 * Initialize Form Handler
	 */
	function initFormHandler() {
		const form = document.querySelector('.js-exit-intent-form');
		if (!form) {
			if (config.debug) {
				console.warn('[Medici Exit-Intent] Form not found');
			}
			return;
		}

		form.addEventListener('submit', handleFormSubmit);

		if (config.debug) {
			console.log('[Medici Exit-Intent] Form handler attached');
		}
	}

	function getCookie(name) {
		const value = `; ${document.cookie}`;
		const parts = value.split(`; ${name}=`);
		if (parts.length !== 2) return null;
		return parts.pop().split(';').shift() || null;
	}

	function setCookie(name, val, days) {
		const date = new Date();
		date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);

		const isHttps = window.location.protocol === 'https:';
		const secure = isHttps ? '; Secure' : '';

		document.cookie =
			`${name}=${encodeURIComponent(val)}` +
			`; expires=${date.toUTCString()}` +
			'; path=/' +
			'; SameSite=Lax' +
			secure;
	}

	function deleteCookie(name) {
		const isHttps = window.location.protocol === 'https:';
		const secure = isHttps ? '; Secure' : '';

		document.cookie =
			`${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT` +
			'; path=/' +
			'; SameSite=Lax' +
			secure;
	}

	function wasShown() {
		return getCookie(CONFIG.COOKIE_NAME) === 'true';
	}

	function markAsShown() {
		setCookie(CONFIG.COOKIE_NAME, 'true', CONFIG.COOKIE_DAYS);
	}

	function isMobile() {
		return window.innerWidth < CONFIG.MIN_WIDTH;
	}

	function openOverlayPanel() {
		const trigger = document.querySelector(CONFIG.TRIGGER_SELECTOR);
		if (!trigger) {
			log('Trigger not found:', CONFIG.TRIGGER_SELECTOR);
			return false;
		}

		trigger.click();
		return true;
	}

	function onExitIntent(e) {
		if (!isActivated) return;
		if (isShown) return;

		// Має бути "вихід" курсора за межі вікна (relatedTarget = null)
		const to = e.relatedTarget || e.toElement;
		if (to) return;

		// Exit-intent тільки зверху
		if (typeof e.clientY === 'number' && e.clientY > CONFIG.SENSITIVITY) return;

		isShown = true;
		markAsShown();
		openOverlayPanel();

		document.documentElement.removeEventListener('mouseout', onExitIntent);
		log('Exit-intent fired');
	}

	function activate() {
		if (isActivated) return;
		if (wasShown()) {
			log('Already shown (cookie)');
			return;
		}
		if (isMobile()) {
			log('Mobile/tablet detected, skipping');
			return;
		}

		document.documentElement.addEventListener('mouseout', onExitIntent);
		isActivated = true;
		log('Activated');
	}

	function init() {
		if (wasShown() || isMobile()) return;

		window.setTimeout(activate, CONFIG.ACTIVATION_DELAY);
		log('Initialized, activation in ms:', CONFIG.ACTIVATION_DELAY);
	}

	// Public API (для дебагу/тесту)
	window.MediciExitIntent = {
		init,
		trigger() {
			isShown = true;
			markAsShown();
			openOverlayPanel();
		},
		reset() {
			isShown = false;
			deleteCookie(CONFIG.COOKIE_NAME);
		},
		config: CONFIG,
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
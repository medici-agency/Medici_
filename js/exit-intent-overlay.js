/**
 * ============================================================================
 * MEDICI.AGENCY - EXIT-INTENT (GenerateBlocks Overlay Panel + 30d cookie)
 * File: js/exit-intent.js
 * ============================================================================
 *
 * Логіка:
 * - Desktop-only (>= 1024px)
 * - Активація через 5 секунд
 * - Exit-intent: курсор виходить за межі вікна зверху (clientY <= 20)
 * - Показ 1 раз на 30 днів (cookie)
 * - Відкриває Overlay Panel через клік по елементу з data-gb-overlay="gb-overlay-424"
 */

(function () {
	'use strict';

	const CONFIG = {
		TRIGGER_SELECTOR: '[data-gb-overlay="gb-overlay-424"]',
		COOKIE_NAME: 'medici_exit_popup_shown',
		COOKIE_DAYS: 30,

		ACTIVATION_DELAY: 5000,
		SENSITIVITY: 20,
		MIN_WIDTH: 1024,

		DEBUG: false,
	};

	let isActivated = false;
	let isShown = false;

	function log(...args) {
		if (!CONFIG.DEBUG) return;
		// eslint-disable-next-line no-console
		console.log('[ExitIntent]', ...args);
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
/**
 * Exit-Intent Hybrid Solution
 *
 * Combines beeker1121/exit-intent-popup detection with GenerateBlocks Overlay Panel
 *
 * Architecture:
 * - bioEp detects exit-intent + cookie tracking (30 days)
 * - Triggers GenerateBlocks Overlay Panel programmatically
 * - Overlay Panel contains form with Events API integration
 *
 * @package
 * @version 1.0.0
 */

(function () {
	'use strict';

	/**
	 * Configuration
	 */
	const CONFIG = {
		overlayPanelId: 'medici-exit-intent-panel', // Overlay Panel container ID
		cookieExp: 30, // Days (30 днів cookie)
		delay: 2, // Seconds before activation (2 секунди на ознайомлення)
		debug: false, // Enable console logging
	};

	/**
	 * Initialize Hybrid Exit-Intent
	 */
	function init() {
		// Check if bioEp loaded
		if (typeof window.bioEp === 'undefined') {
			console.error('bioEp library not loaded');
			return;
		}

		// Check screen width (desktop only > 1024px)
		if (window.innerWidth <= 1024) {
			if (CONFIG.debug) {
				console.log('Exit-Intent: Screen too small (mobile/tablet)');
			}
			return;
		}

		// Initialize bioEp with custom settings
		window.bioEp.init({
			// Width/Height not used (GenerateBlocks handles this)
			width: 1,
			height: 1,

			// Empty HTML (we don't use bioEp popup, only detection)
			html: '',
			css: '#bio_ep { display: none !important; } #bio_ep_bg { display: none !important; }',

			// Cookie settings
			cookieExp: CONFIG.cookieExp, // 30 days
			showOncePerSession: false, // Use cookie, not session

			// Delay before activation (2 seconds)
			delay: CONFIG.delay,
			showOnDelay: false, // Don't show on delay, only on exit-intent

			// Callback when exit-intent detected
			onPopup() {
				if (CONFIG.debug) {
					console.log('Exit-Intent detected! Triggering GenerateBlocks Overlay Panel...');
				}

				// Trigger GenerateBlocks Overlay Panel
				triggerOverlayPanel();

				// Hide bioEp popup (we don't need it)
				window.bioEp.hidePopup();
			},
		});

		if (CONFIG.debug) {
			console.log('Exit-Intent Hybrid initialized');
			console.log('Cookie expiration:', CONFIG.cookieExp, 'days');
			console.log('Delay:', CONFIG.delay, 'seconds');
		}
	}

	/**
	 * Trigger GenerateBlocks Overlay Panel Programmatically
	 */
	function triggerOverlayPanel() {
		// Method 1: Find overlay panel trigger button/link
		const triggerSelector = `[data-gb-trigger-panel="${CONFIG.overlayPanelId}"]`;
		const triggerElement = document.querySelector(triggerSelector);

		if (triggerElement) {
			// Simulate click on trigger
			triggerElement.click();
			return;
		}

		// Method 2: Direct panel opening (if GenerateBlocks API available)
		if (typeof window.GenerateBlocksOpenPanel === 'function') {
			window.GenerateBlocksOpenPanel(CONFIG.overlayPanelId);
			return;
		}

		// Method 3: Fallback - dispatch custom event
		const event = new CustomEvent('gbOpenPanel', {
			detail: { panelId: CONFIG.overlayPanelId },
		});
		document.dispatchEvent(event);

		if (CONFIG.debug) {
			console.log('Overlay Panel triggered via custom event');
		}
	}

	/**
	 * Public API
	 */
	window.MediciExitIntent = {
		init,
		triggerPanel: triggerOverlayPanel,
		config: CONFIG,
	};

	// Auto-initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

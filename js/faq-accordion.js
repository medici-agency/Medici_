/**
 * FAQ Accordion Functionality
 *
 * Animated accordion based on CSS Grid technique from Cruip
 * Features:
 * - Smooth height transitions using CSS Grid (grid-rows)
 * - Plus/minus icon rotation animation
 * - ARIA accessibility (aria-expanded, aria-hidden)
 * - Keyboard navigation support
 * - Supports multiple accordions on same page
 *
 * API:
 * - window.MediciFaqAccordion.init()    - Initialize accordions
 * - window.MediciFaqAccordion.destroy() - Cleanup event listeners
 *
 * @package
 * @since   1.3.4
 * @version 1.5.0 Added cleanup/destroy method to prevent memory leaks
 */

(function () {
	'use strict';

	// =====================================================
	// STATE - Store references for cleanup
	// =====================================================
	const state = {
		initialized: false,
		containers: [],
		handlers: new WeakMap(),
	};

	/**
	 * Initialize accordion when DOM is ready
	 */
	function init() {
		// Prevent double initialization
		if (state.initialized) {
			return;
		}

		const accordionContainers = document.querySelectorAll('[data-accordion="true"]');

		if (!accordionContainers.length) {
			return;
		}

		accordionContainers.forEach((container) => {
			const items = container.querySelectorAll('[data-accordion-item="true"]');

			items.forEach((item) => {
				const button = item.querySelector('[data-accordion-button="true"]');
				const panel = item.querySelector('[data-accordion-panel="true"]');

				if (!button || !panel) {
					return;
				}

				// Create bound handlers
				const clickHandler = () => {
					toggleAccordionItem(item, button, panel);
				};

				const keydownHandler = (e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						toggleAccordionItem(item, button, panel);
					}
				};

				// Store handlers for cleanup
				state.handlers.set(button, { click: clickHandler, keydown: keydownHandler });

				// Add event listeners
				button.addEventListener('click', clickHandler);
				button.addEventListener('keydown', keydownHandler);
			});

			// Store container reference
			state.containers.push(container);
		});

		state.initialized = true;
	}

	/**
	 * Destroy/cleanup all event listeners
	 * Call this before removing accordions from DOM or on page unload
	 */
	function destroy() {
		if (!state.initialized) {
			return;
		}

		state.containers.forEach((container) => {
			const buttons = container.querySelectorAll('[data-accordion-button="true"]');

			buttons.forEach((button) => {
				const handlers = state.handlers.get(button);
				if (handlers) {
					button.removeEventListener('click', handlers.click);
					button.removeEventListener('keydown', handlers.keydown);
					state.handlers.delete(button);
				}
			});
		});

		// Clear containers array
		state.containers = [];
		state.initialized = false;
	}

	/**
	 * Toggle accordion item open/closed
	 *
	 * @param {HTMLElement} item   - Accordion item container
	 * @param {HTMLElement} button - Accordion button
	 * @param {HTMLElement} panel  - Accordion panel
	 */
	function toggleAccordionItem(item, button, panel) {
		const isExpanded = button.getAttribute('aria-expanded') === 'true';

		if (isExpanded) {
			// Close accordion
			closeAccordionItem(button, panel);
		} else {
			// Close all other items in same accordion (optional: single-open behavior)
			// Comment out lines below if you want multiple items open at once
			const accordion = item.closest('[data-accordion="true"]');
			if (accordion) {
				const allButtons = accordion.querySelectorAll('[data-accordion-button="true"]');
				const allPanels = accordion.querySelectorAll('[data-accordion-panel="true"]');

				allButtons.forEach((btn, index) => {
					if (btn !== button) {
						closeAccordionItem(btn, allPanels[index]);
					}
				});
			}

			// Open accordion
			openAccordionItem(button, panel);
		}
	}

	/**
	 * Open accordion item
	 *
	 * @param {HTMLElement} button - Accordion button
	 * @param {HTMLElement} panel  - Accordion panel
	 */
	function openAccordionItem(button, panel) {
		// Update ARIA attributes
		button.setAttribute('aria-expanded', 'true');
		panel.setAttribute('aria-hidden', 'false');

		// Add active class to button
		button.classList.add('accordion-active');

		// Expand panel using CSS Grid
		panel.style.gridTemplateRows = '1fr';
		panel.style.opacity = '1';
	}

	/**
	 * Close accordion item
	 *
	 * @param {HTMLElement} button - Accordion button
	 * @param {HTMLElement} panel  - Accordion panel
	 */
	function closeAccordionItem(button, panel) {
		// Update ARIA attributes
		button.setAttribute('aria-expanded', 'false');
		panel.setAttribute('aria-hidden', 'true');

		// Remove active class from button
		button.classList.remove('accordion-active');

		// Collapse panel using CSS Grid
		panel.style.gridTemplateRows = '0fr';
		panel.style.opacity = '0';
	}

	// =====================================================
	// PUBLIC API
	// =====================================================
	window.MediciFaqAccordion = {
		init,
		destroy,
	};

	/**
	 * Initialize on DOMContentLoaded
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

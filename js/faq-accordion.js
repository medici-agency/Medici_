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
 * @package
 * @since 1.3.4
 */

(function () {
	'use strict';

	/**
	 * Initialize accordion when DOM is ready
	 */
	function initAccordion() {
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

				// Add click handler
				button.addEventListener('click', () => {
					toggleAccordionItem(item, button, panel);
				});

				// Add keyboard support (Enter and Space)
				button.addEventListener('keydown', (e) => {
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						toggleAccordionItem(item, button, panel);
					}
				});
			});
		});
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

	/**
	 * Initialize on DOMContentLoaded
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAccordion);
	} else {
		initAccordion();
	}
})();

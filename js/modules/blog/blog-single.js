/**
 * Blog Single Post JavaScript
 *
 * Інтерактивність для окремої статті блогу:
 * - Table of Contents generation
 * - Smooth scroll navigation
 * - Active link highlight
 * - Scroll spy functionality
 *
 * JavaScript Hooks Convention:
 * - js-* classes are used for JavaScript functionality
 * - CSS classes are used for styling only
 *
 * Performance Optimizations (v1.3.0):
 * - Cached heading positions (updated on resize)
 * - requestAnimationFrame for scroll handlers
 * - Reduced forced reflows (batch DOM reads)
 *
 * @module Blog/Scripts
 * @since   1.0.16
 * @version 1.3.0
 */

(function () {
	'use strict';

	// =====================================================
	// JS HOOKS SELECTORS (js-* classes convention)
	// Fallback to CSS classes for backward compatibility
	// =====================================================
	const SELECTORS = {
		tocContainer: '#js-toc-container, #medici-toc-container',
		articleContent: '.js-article-content, .medici-article-content',
		tocLink: '.js-toc-link, .toc-link, .toc-list a',
		newsletterForm: '.js-newsletter-form, .newsletter-form',
	};

	/**
	 * =====================================================
	 * DOM CACHE (Performance optimization)
	 * =====================================================
	 */
	const domCache = {
		tocContainer: null,
		articleContent: null,
		headings: null,
		tocLinks: null,
	};

	/**
	 * =====================================================
	 * POSITION CACHE (Reduces forced reflows)
	 * =====================================================
	 */
	const positionCache = {
		headingPositions: [], // { id, top } - cached heading positions
		articleTop: 0,
		articleHeight: 0,
		viewportHeight: 0,
		isDirty: true, // Flag to recalculate positions
	};

	/**
	 * Initialize DOM cache
	 * Uses js-* hooks with fallback to legacy selectors
	 * @return {boolean} - true if critical elements found
	 */
	function initDomCache() {
		domCache.tocContainer = document.querySelector(SELECTORS.tocContainer);
		domCache.articleContent = document.querySelector(SELECTORS.articleContent);

		if (domCache.articleContent) {
			domCache.headings = domCache.articleContent.querySelectorAll('h2, h3');
		}

		return !!domCache.articleContent;
	}

	/**
	 * Update TOC links cache (after TOC is generated)
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function updateTocLinksCache() {
		domCache.tocLinks = document.querySelectorAll(SELECTORS.tocLink);
	}

	/**
	 * =====================================================
	 * UTILITIES
	 * =====================================================
	 */

	/**
	 * Debounce function for resize events
	 * @param {Function} func - Function to debounce
	 * @param {number}   wait - Wait time in ms
	 * @return {Function} - Debounced function
	 */
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			clearTimeout(timeout);
			timeout = setTimeout(() => func.apply(this, args), wait);
		};
	}

	/**
	 * =====================================================
	 * POSITION CACHE MANAGEMENT
	 * =====================================================
	 */

	/**
	 * Update cached positions for all headings and article
	 * Called on init and resize (not on every scroll!)
	 */
	function updatePositionCache() {
		if (!domCache.articleContent || !domCache.headings) {
			return;
		}

		// Batch all DOM reads together (prevents forced reflows)
		const articleRect = domCache.articleContent.getBoundingClientRect();
		const scrollTop = window.pageYOffset;

		positionCache.articleTop = articleRect.top + scrollTop;
		positionCache.articleHeight = domCache.articleContent.offsetHeight;
		positionCache.viewportHeight = window.innerHeight;

		// Cache all heading positions at once
		positionCache.headingPositions = [];
		domCache.headings.forEach((heading) => {
			positionCache.headingPositions.push({
				id: heading.id,
				top: heading.offsetTop,
			});
		});

		positionCache.isDirty = false;
	}

	/**
	 * Mark position cache as dirty (needs recalculation)
	 */
	function invalidatePositionCache() {
		positionCache.isDirty = true;
	}

	/**
	 * =====================================================
	 * TABLE OF CONTENTS
	 * =====================================================
	 */

	/**
	 * Generate Table of Contents from H2 and H3 headings
	 */
	function generateTableOfContents() {
		if (!domCache.tocContainer || !domCache.articleContent) {
			return;
		}

		const headings = domCache.headings;

		if (!headings || headings.length === 0) {
			domCache.tocContainer.innerHTML =
				'<p style="font-size: 14px; color: var(--text-muted);">Зміст недоступний</p>';
			return;
		}

		// Створити список
		const tocList = document.createElement('ul');
		tocList.className = 'toc-list';

		headings.forEach((heading, index) => {
			// Додати ID до заголовка якщо його немає
			if (!heading.id) {
				heading.id = `heading-${index}`;
			}

			// Створити елемент списку
			const listItem = document.createElement('li');
			const link = document.createElement('a');

			link.href = `#${heading.id}`;
			// Використовуємо innerText замість textContent для коректного відображення після Twemoji parse
			link.textContent = heading.innerText || heading.textContent;
			link.dataset.target = heading.id;

			// Додати відступ для H3
			if (heading.tagName === 'H3') {
				listItem.style.paddingLeft = '16px';
				link.style.fontSize = '13px';
			}

			listItem.appendChild(link);
			tocList.appendChild(listItem);
		});

		domCache.tocContainer.appendChild(tocList);

		// Update cache after TOC generation
		updateTocLinksCache();
	}

	/**
	 * =====================================================
	 * SMOOTH SCROLL
	 * =====================================================
	 */

	/**
	 * Initialize smooth scroll for TOC links
	 * Підтримує обидві структури: .toc-link (серверний) та .toc-list a (legacy)
	 */
	function initSmoothScroll() {
		const tocLinks = domCache.tocLinks;

		if (!tocLinks || tocLinks.length === 0) {
			return;
		}

		tocLinks.forEach((link) => {
			link.addEventListener('click', function (e) {
				e.preventDefault();

				const targetId = this.getAttribute('href').substring(1);
				const targetElement = document.getElementById(targetId);

				if (targetElement) {
					// Offset для sticky header (якщо є)
					const headerHeight = 100;
					const targetPosition =
						targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;

					window.scrollTo({
						top: targetPosition,
						behavior: 'smooth',
					});

					// Оновити URL без scroll
					if (history.pushState) {
						history.pushState(null, null, `#${targetId}`);
					}
				}
			});
		});
	}

	/**
	 * =====================================================
	 * SCROLL SPY (Optimized for performance)
	 * =====================================================
	 */

	/**
	 * Scroll spy - highlight active TOC link based on scroll position
	 * Підтримує обидві структури: .toc-link (серверний) та .toc-list a (legacy)
	 *
	 * Performance: Uses cached positions + requestAnimationFrame
	 */
	function initScrollSpy() {
		const tocLinks = domCache.tocLinks;
		const headings = domCache.headings;

		if (!tocLinks || tocLinks.length === 0 || !headings) {
			return;
		}

		const headerHeight = 150; // Offset для визначення активного заголовка
		let ticking = false; // RAF flag
		let lastActiveId = null; // Track last active to avoid unnecessary DOM updates
		let tocScrollTicking = false; // Separate RAF for TOC scroll

		/**
		 * Update active link (optimized)
		 * Uses cached positions instead of reading offsetTop on every scroll
		 */
		function updateActiveLink() {
			// Update cache if dirty (after resize)
			if (positionCache.isDirty) {
				updatePositionCache();
			}

			const scrollPosition = window.pageYOffset;

			// Use cached positions instead of reading from DOM
			let currentHeadingId = null;

			for (const cached of positionCache.headingPositions) {
				if (scrollPosition >= cached.top - headerHeight) {
					currentHeadingId = cached.id;
				}
			}

			// Якщо scroll position близько до верху (< 100px), завжди активувати першу секцію
			if (scrollPosition < 100 && positionCache.headingPositions.length > 0) {
				currentHeadingId = positionCache.headingPositions[0].id;
			}

			// Skip DOM updates if nothing changed
			if (currentHeadingId === lastActiveId) {
				return;
			}
			lastActiveId = currentHeadingId;

			// Batch all DOM writes together
			let activeLink = null;
			tocLinks.forEach((link) => {
				const isActive = currentHeadingId && link.getAttribute('href') === `#${currentHeadingId}`;
				link.classList.toggle('active', isActive);
				if (isActive) {
					activeLink = link;
				}
			});

			// Schedule TOC scroll in separate RAF to avoid layout thrashing
			if (activeLink && !tocScrollTicking) {
				tocScrollTicking = true;
				requestAnimationFrame(() => {
					scrollTocToActiveLink(activeLink);
					tocScrollTicking = false;
				});
			}
		}

		/**
		 * Scroll TOC container to show active link
		 * Optimized: minimal DOM reads, batched
		 * @param {HTMLElement} activeLink - Active TOC link element
		 */
		function scrollTocToActiveLink(activeLink) {
			const tocContainer = domCache.tocContainer;
			if (!tocContainer) {
				return;
			}

			// Batch DOM reads
			const linkRect = activeLink.getBoundingClientRect();
			const containerRect = tocContainer.getBoundingClientRect();

			// Check visibility
			const isLinkVisible =
				linkRect.top >= containerRect.top && linkRect.bottom <= containerRect.bottom;

			// Only scroll if needed (DOM write)
			if (!isLinkVisible) {
				const linkOffsetTop = activeLink.offsetTop;
				const containerHeight = tocContainer.clientHeight;
				const scrollTo = linkOffsetTop - containerHeight / 2 + activeLink.offsetHeight / 2;

				tocContainer.scrollTo({
					top: scrollTo,
					behavior: 'smooth',
				});
			}
		}

		/**
		 * Scroll handler with requestAnimationFrame
		 * Prevents forced synchronous layouts
		 */
		function onScroll() {
			if (!ticking) {
				requestAnimationFrame(() => {
					updateActiveLink();
					ticking = false;
				});
				ticking = true;
			}
		}

		// Listen to scroll events with RAF (better than throttle for smooth animations)
		window.addEventListener('scroll', onScroll, { passive: true });

		// Listen to resize events to invalidate cache
		window.addEventListener('resize', debounce(invalidatePositionCache, 150));

		// Initial position cache and check
		updatePositionCache();
		updateActiveLink();
	}

	/**
	 * =====================================================
	 * NEWSLETTER FORM
	 * =====================================================
	 */

	/**
	 * Initialize newsletter form (if needed)
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function initNewsletterForm() {
		const form = document.querySelector(SELECTORS.newsletterForm);

		if (!form) {
			return;
		}

		form.addEventListener('submit', function (e) {
			const emailInput = this.querySelector('input[type="email"]');

			// Базова валідація
			if (!emailInput || !emailInput.value) {
				e.preventDefault();
				alert('Будь ласка, введіть email адресу');
				return;
			}

			// Email regex
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(emailInput.value)) {
				e.preventDefault();
				alert('Будь ласка, введіть коректну email адресу');
			}

			// Дозволити форму відправитись
			// WordPress обробить це через admin-post.php
		});
	}

	/**
	 * =====================================================
	 * READING PROGRESS BAR (Optimized)
	 * =====================================================
	 */

	/**
	 * Initialize reading progress bar (Mobile only)
	 * Показує animated gradient progress bar на мобільних пристроях
	 *
	 * Performance: Uses cached positions + requestAnimationFrame
	 */
	function initReadingProgress() {
		const articleContent = domCache.articleContent;

		if (!articleContent) {
			return;
		}

		// Створити progress bar (CSS контролює відображення на mobile)
		const progressBar = document.createElement('div');
		progressBar.className = 'medici-reading-progress-bar';
		document.body.appendChild(progressBar);

		let ticking = false;
		let lastProgress = -1; // Track last value to avoid unnecessary DOM updates

		/**
		 * Update progress on scroll
		 * Uses cached positions from positionCache
		 */
		function updateProgress() {
			// Update cache if dirty
			if (positionCache.isDirty) {
				updatePositionCache();
			}

			const scrollTop = window.pageYOffset;

			// Use cached values instead of reading from DOM
			const scrollableDistance = positionCache.articleHeight - positionCache.viewportHeight;
			const scrolled = scrollTop - positionCache.articleTop;

			// Progress від 0 до 100
			const progress = Math.min(Math.max((scrolled / scrollableDistance) * 100, 0), 100);

			// Only update DOM if value changed significantly (> 0.5%)
			if (Math.abs(progress - lastProgress) > 0.5) {
				lastProgress = progress;
				progressBar.style.width = progress + '%';
			}
		}

		/**
		 * Scroll handler with requestAnimationFrame
		 */
		function onScroll() {
			if (!ticking) {
				requestAnimationFrame(() => {
					updateProgress();
					ticking = false;
				});
				ticking = true;
			}
		}

		// Слухати scroll events з RAF (оптимізація)
		window.addEventListener('scroll', onScroll, { passive: true });

		// Початкове оновлення (ensure cache is ready)
		if (positionCache.isDirty) {
			updatePositionCache();
		}
		updateProgress();
	}

	/**
	 * =====================================================
	 * INITIALIZATION
	 * =====================================================
	 */

	/**
	 * Initialize all functions
	 */
	function init() {
		// Initialize DOM cache first
		if (!initDomCache()) {
			return; // No article content - skip initialization
		}

		// Затримка для TOC generation щоб Twemoji встиг parse emoji
		// Twemoji запускається на DOMContentLoaded, тому даємо йому час виконатись
		setTimeout(() => {
			generateTableOfContents();
			initSmoothScroll();
			initScrollSpy();
		}, 100);

		initNewsletterForm();
		initReadingProgress();
	}

	/**
	 * Wait for DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

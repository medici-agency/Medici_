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
 * @package
 * @subpackage Blog/Scripts
 * @since      1.0.16
 * @version    1.2.0
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
	 * Throttle function for scroll events optimization
	 * @param {Function} func - Function to throttle
	 * @param {number}   wait - Wait time in ms
	 * @return {Function} - Throttled function
	 */
	function throttle(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
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
	 * SCROLL SPY
	 * =====================================================
	 */

	/**
	 * Scroll spy - highlight active TOC link based on scroll position
	 * Підтримує обидві структури: .toc-link (серверний) та .toc-list a (legacy)
	 */
	function initScrollSpy() {
		const tocLinks = domCache.tocLinks;
		const headings = domCache.headings;

		if (!tocLinks || tocLinks.length === 0 || !headings) {
			return;
		}

		const headerHeight = 150; // Offset для визначення активного заголовка

		// Update active link
		function updateActiveLink() {
			// Знайти поточний активний заголовок
			let currentHeading = null;
			let closestDistance = Infinity;

			headings.forEach((heading) => {
				// Використовуємо getBoundingClientRect() для точного визначення позиції
				const rect = heading.getBoundingClientRect();
				const headingTop = rect.top;

				// Відстань від верху viewport (з урахуванням header offset)
				const distanceFromTop = Math.abs(headingTop - headerHeight);

				// Заголовок вважається активним якщо він пройшов header offset
				// і є найближчим до header offset
				if (headingTop <= headerHeight && distanceFromTop < closestDistance) {
					currentHeading = heading;
					closestDistance = distanceFromTop;
				}
			});

			// Якщо жоден заголовок не активний (scroll зверху), активувати перший
			if (!currentHeading && headings.length > 0) {
				currentHeading = headings[0];
			}

			// Оновити active class
			let activeLink = null;
			tocLinks.forEach((link) => {
				link.classList.remove('active');

				if (currentHeading && link.getAttribute('href') === `#${currentHeading.id}`) {
					link.classList.add('active');
					activeLink = link;
				}
			});

			// Auto-scroll TOC до активного елемента
			if (activeLink) {
				scrollTocToActiveLink(activeLink);
			}
		}

		/**
		 * Scroll TOC container to show active link
		 * Автоматично прокручує TOC sidebar щоб активний елемент був видимий
		 * @param activeLink
		 */
		function scrollTocToActiveLink(activeLink) {
			const tocContainer = domCache.tocContainer;
			if (!tocContainer) {
				return;
			}

			// Отримати позицію активного елемента відносно TOC контейнера
			const linkRect = activeLink.getBoundingClientRect();
			const containerRect = tocContainer.getBoundingClientRect();

			// Перевірити чи елемент видимий у контейнері
			const isLinkVisible =
				linkRect.top >= containerRect.top && linkRect.bottom <= containerRect.bottom;

			// Якщо елемент не видимий - прокрутити контейнер
			if (!isLinkVisible) {
				// Рахуємо offset відносно батьківського UL елемента (.toc-list)
				const tocList = activeLink.closest('.toc-list');
				if (!tocList) {
					return;
				}

				// Позиція активного елемента відносно .toc-list
				let linkOffsetTop = 0;
				let element = activeLink.parentElement; // li елемент
				while (element && element !== tocList) {
					linkOffsetTop += element.offsetTop;
					element = element.offsetParent;
				}

				const containerHeight = tocContainer.clientHeight;
				const linkHeight = activeLink.offsetHeight;

				// Прокрутити так щоб активний елемент був посередині (з невеликим offset)
				const scrollTo = linkOffsetTop - containerHeight / 2 + linkHeight / 2;

				tocContainer.scrollTo({
					top: Math.max(0, scrollTo),
					behavior: 'smooth',
				});
			}
		}

		// Listen to scroll events (throttled)
		window.addEventListener('scroll', throttle(updateActiveLink, 100));

		// Initial check
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
	 * READING PROGRESS BAR
	 * =====================================================
	 */

	/**
	 * Initialize reading progress bar (Mobile only)
	 * Показує animated gradient progress bar на мобільних пристроях
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

		// Update progress on scroll
		function updateProgress() {
			const articleTop = articleContent.offsetTop;
			const articleHeight = articleContent.offsetHeight;
			const viewportHeight = window.innerHeight;
			const scrollTop = window.pageYOffset;

			// Розрахунок відсотка прочитаного
			const scrollableDistance = articleHeight - viewportHeight;
			const scrolled = scrollTop - articleTop;

			// Progress від 0 до 100
			const progress = Math.min(Math.max((scrolled / scrollableDistance) * 100, 0), 100);

			progressBar.style.width = progress + '%';
		}

		// Слухати scroll events з throttling (оптимізація)
		window.addEventListener('scroll', throttle(updateProgress, 50));

		// Початкове оновлення
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

/**
 * Blog Single Post JavaScript
 *
 * Інтерактивність для окремої статті блогу:
 * - Table of Contents generation
 * - Smooth scroll navigation
 * - Active link highlight
 * - Scroll spy functionality
 *
 * @package    Medici
 * @subpackage Blog/Scripts
 * @since      1.0.16
 * @version    1.0.0
 */

(function () {
	'use strict';

	/**
	 * Wait for DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	/**
	 * Initialize all functions
	 */
	function init() {
		// Затримка для TOC generation щоб Twemoji встиг parse emoji
		// Twemoji запускається на DOMContentLoaded, тому даємо йому час виконатись
		setTimeout(() => {
			generateTableOfContents();
			initSmoothScroll();
			initScrollSpy();
		}, 100);

		initNewsletterForm();
	}

	/**
	 * Generate Table of Contents from H2 and H3 headings
	 */
	function generateTableOfContents() {
		const tocContainer = document.getElementById('medici-toc-container');
		const articleContent = document.querySelector('.medici-article-content');

		if (!tocContainer || !articleContent) {
			return;
		}

		// Знайти всі H2 та H3 заголовки
		const headings = articleContent.querySelectorAll('h2, h3');

		if (headings.length === 0) {
			tocContainer.innerHTML =
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
			// Копіюємо HTML вміст включаючи Twemoji іконки (img теги)
			// innerHTML безпечний тут, оскільки заголовки вже пройшли WordPress санітизацію
			link.innerHTML = heading.innerHTML;
			link.dataset.target = heading.id;

			// Додати відступ для H3
			if (heading.tagName === 'H3') {
				listItem.style.paddingLeft = '16px';
				link.style.fontSize = '13px';
			}

			listItem.appendChild(link);
			tocList.appendChild(listItem);
		});

		tocContainer.appendChild(tocList);
	}

	/**
	 * Initialize smooth scroll for TOC links
	 * Підтримує обидві структури: .toc-link (серверний) та .toc-list a (legacy)
	 */
	function initSmoothScroll() {
		// Підтримка серверного та JS-генерованого TOC
		const tocLinks = document.querySelectorAll('.toc-link, .toc-list a');

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
	 * Scroll spy - highlight active TOC link based on scroll position
	 * Підтримує обидві структури: .toc-link (серверний) та .toc-list a (legacy)
	 */
	function initScrollSpy() {
		// Підтримка серверного та JS-генерованого TOC
		const tocLinks = document.querySelectorAll('.toc-link, .toc-list a');
		const articleContent = document.querySelector('.medici-article-content');

		if (tocLinks.length === 0 || !articleContent) {
			return;
		}

		const headings = articleContent.querySelectorAll('h2, h3');
		const headerHeight = 150; // Offset для визначення активного заголовка

		// Throttle function для оптимізації
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

		// Update active link
		function updateActiveLink() {
			const scrollPosition = window.pageYOffset;

			// Знайти поточний активний заголовок
			let currentHeading = null;

			headings.forEach((heading) => {
				const headingTop = heading.offsetTop - headerHeight;

				if (scrollPosition >= headingTop) {
					currentHeading = heading;
				}
			});

			// Якщо scroll position близько до верху (< 100px), завжди активувати першу секцію
			// Це виправляє проблему коли користувач натискає scroll-to-top
			if (scrollPosition < 100 && headings.length > 0) {
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
		 */
		function scrollTocToActiveLink(activeLink) {
			const tocContainer = document.getElementById('medici-toc-container');
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
				const linkOffsetTop = activeLink.offsetTop;
				const containerScrollTop = tocContainer.scrollTop;
				const containerHeight = tocContainer.clientHeight;

				// Прокрутити так щоб активний елемент був посередині (з невеликим offset)
				const scrollTo = linkOffsetTop - containerHeight / 2 + activeLink.offsetHeight / 2;

				tocContainer.scrollTo({
					top: scrollTo,
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
	 * Initialize newsletter form (if needed)
	 */
	function initNewsletterForm() {
		const form = document.querySelector('.newsletter-form');

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
				return;
			}

			// Дозволити форму відправитись
			// WordPress обробить це через admin-post.php
		});
	}

	// Initialize newsletter form
	initNewsletterForm();

	/**
	 * Initialize reading progress bar (Mobile only)
	 * Показує animated gradient progress bar на мобільних пристроях
	 */
	function initReadingProgress() {
		const articleContent = document.querySelector('.medici-article-content');

		if (!articleContent) {
			return;
		}

		// Створити progress bar (CSS контролює відображення на mobile)
		const progressBar = document.createElement('div');
		progressBar.className = 'medici-reading-progress-bar';
		document.body.appendChild(progressBar);

		// Throttle helper для оптимізації scroll events
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

	// Initialize reading progress bar (АКТИВОВАНО)
	initReadingProgress();
})();

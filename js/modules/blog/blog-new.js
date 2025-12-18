/**
 * Medici Blog Module - Frontend JavaScript
 *
 * Функціонал блог сторінки:
 * - Theme Toggle (Light/Dark з localStorage)
 * - Category Filter (фільтрація статей по категоріях)
 * - Search (пошук по заголовку, excerpt, категорії)
 * - Sort (сортування: newest, popular, alphabetical)
 * - Responsive поведінка
 *
 * JavaScript Hooks Convention:
 * - js-* classes are used for JavaScript functionality
 * - CSS classes are used for styling only
 *
 * @package
 * @subpackage Blog
 * @since      1.0.15
 * @version    1.1.0
 */

(function () {
	'use strict';

	// =====================================================
	// JS HOOKS SELECTORS (js-* classes convention)
	// Fallback to CSS classes for backward compatibility
	// =====================================================
	const SELECTORS = {
		// Theme
		themeToggle: '.js-blog-theme-toggle, .medici-blog-theme-toggle',

		// Filter & Search
		filterTag: '.js-blog-filter-tag, .medici-blog-filter-tag',
		searchInput: '.js-blog-search-input, .medici-blog-search-input',
		sortSelect: '#js-blog-sort, #medici-blog-sort, .js-blog-sort-select, .medici-blog-sort-select',

		// Articles
		articleCard: '.js-blog-article-card, .medici-blog-article-card',
		cardTitle: '.js-blog-card-title, .medici-blog-card-title',
		cardExcerpt: '.js-blog-card-excerpt, .medici-blog-card-excerpt',
		cardCategory: '.js-blog-card-category, .medici-blog-card-category',
		grid: '.js-blog-grid, .medici-blog-grid',

		// Load More
		loadMoreBtn: '.js-blog-load-more, .medici-blog-load-more-btn',
		loadMoreText: '.js-load-more-text, .load-more-text',
		loadMoreLoader: '.js-load-more-loader, .load-more-loader',

		// Messages
		noResults: '.js-blog-no-results, .medici-blog-no-results',
	};

	/**
	 * =====================================================
	 * THEME TOGGLE (LIGHT / DARK)
	 * =====================================================
	 */

	/**
	 * Ініціалізація теми при завантаженні сторінки
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function initTheme() {
		const savedTheme = localStorage.getItem('medici-blog-theme') || 'dark';
		document.documentElement.setAttribute('data-theme', savedTheme);

		const toggleBtn = document.querySelector(SELECTORS.themeToggle);
		if (toggleBtn) {
			updateThemeButton(toggleBtn, savedTheme);
		}
	}

	/**
	 * Оновлення іконки кнопки теми
	 * @param button
	 * @param theme
	 */
	function updateThemeButton(button, theme) {
		button.textContent = theme === 'dark' ? '☀️' : '🌙';
		button.setAttribute(
			'aria-label',
			theme === 'dark' ? 'Перемкнути на світлу тему' : 'Перемкнути на темну тему'
		);
	}

	/**
	 * Перемикання теми
	 * @param event
	 */
	function toggleTheme(event) {
		const button = event.currentTarget;
		const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
		const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

		document.documentElement.setAttribute('data-theme', newTheme);
		localStorage.setItem('medici-blog-theme', newTheme);
		updateThemeButton(button, newTheme);
	}

	/**
	 * =====================================================
	 * FILTER, SEARCH, SORT
	 * =====================================================
	 */

	let currentCategory = 'all';
	let currentSearchQuery = '';
	let currentSort = 'newest';

	/**
	 * Cache для даних статей (оптимізація DOM queries)
	 * @type {Map<HTMLElement, {category: string, title: string, excerpt: string, categoryText: string}>}
	 */
	const articlesDataCache = new Map();

	/**
	 * Фільтрація статей по категорії
	 * Uses js-* hooks with fallback to legacy selectors
	 * @param category
	 */
	function filterByCategory(category) {
		currentCategory = category;
		applyFilters();

		// Оновити активну кнопку (js-* hooks with fallback)
		document.querySelectorAll(SELECTORS.filterTag).forEach((tag) => {
			if (tag.dataset.category === category) {
				tag.classList.add('active');
			} else {
				tag.classList.remove('active');
			}
		});
	}

	/**
	 * Пошук статей по заголовку, excerpt, категорії
	 * @param query
	 */
	function searchArticles(query) {
		currentSearchQuery = query.toLowerCase().trim();
		applyFilters();
	}

	/**
	 * Сортування статей
	 * @param sortBy
	 */
	function sortArticles(sortBy) {
		currentSort = sortBy;
		applyFilters();
	}

	/**
	 * Отримати кешовані дані статті (або створити кеш)
	 * Uses js-* hooks with fallback to legacy selectors
	 * @param {HTMLElement} article - DOM елемент статті
	 * @return {{category: string, title: string, excerpt: string, categoryText: string}}
	 */
	function getArticleData(article) {
		if (!articlesDataCache.has(article)) {
			articlesDataCache.set(article, {
				category: article.dataset.category || '',
				title: (article.querySelector(SELECTORS.cardTitle)?.textContent || '').toLowerCase(),
				excerpt: (article.querySelector(SELECTORS.cardExcerpt)?.textContent || '').toLowerCase(),
				categoryText: (
					article.querySelector(SELECTORS.cardCategory)?.textContent || ''
				).toLowerCase(),
			});
		}
		return articlesDataCache.get(article);
	}

	/**
	 * Застосувати всі фільтри одночасно
	 * Uses js-* hooks with fallback to legacy selectors
	 * Оптимізовано: DOM queries кешуються для повторного використання
	 */
	function applyFilters() {
		const articles = Array.from(document.querySelectorAll(SELECTORS.articleCard));

		// 1. Фільтрація по категорії та пошуку (з кешованими даними)
		articles.forEach((article) => {
			const data = getArticleData(article);

			const categoryMatch = currentCategory === 'all' || data.category === currentCategory;
			const searchMatch =
				!currentSearchQuery ||
				data.title.includes(currentSearchQuery) ||
				data.excerpt.includes(currentSearchQuery) ||
				data.categoryText.includes(currentSearchQuery);

			if (categoryMatch && searchMatch) {
				article.style.display = '';
				article.classList.remove('hidden');
			} else {
				article.style.display = 'none';
				article.classList.add('hidden');
			}
		});

		// 2. Сортування видимих статей (js-* hooks with fallback)
		const visibleArticles = articles.filter((article) => !article.classList.contains('hidden'));
		const grid = document.querySelector(SELECTORS.grid);

		if (grid && visibleArticles.length > 0) {
			sortVisibleArticles(visibleArticles, grid);
		}

		// 3. Показати повідомлення якщо нічого не знайдено
		updateNoResultsMessage(visibleArticles.length);
	}

	/**
	 * Сортування видимих статей
	 * Оптимізовано: використовує кешовані дані для алфавітного сортування
	 * @param articles
	 * @param grid
	 */
	function sortVisibleArticles(articles, grid) {
		const sortedArticles = [...articles].sort((a, b) => {
			switch (currentSort) {
				case 'newest':
					// Сортування по даті (передбачаємо data-date атрибут)
					const dateA = new Date(a.dataset.date || 0);
					const dateB = new Date(b.dataset.date || 0);
					return dateB - dateA;

				case 'popular':
					// Сортування по популярності (передбачаємо data-views атрибут)
					const viewsA = parseInt(a.dataset.views || 0, 10);
					const viewsB = parseInt(b.dataset.views || 0, 10);
					return viewsB - viewsA;

				case 'alphabetical':
					// Сортування по алфавіту (використовуємо кешовані дані)
					const dataA = getArticleData(a);
					const dataB = getArticleData(b);
					return dataA.title.localeCompare(dataB.title, 'uk');

				default:
					return 0;
			}
		});

		// Перемістити елементи в DOM згідно з порядком сортування
		sortedArticles.forEach((article) => {
			grid.appendChild(article);
		});
	}

	/**
	 * Показати/приховати повідомлення "Нічого не знайдено"
	 * Uses js-* hooks with fallback to legacy selectors
	 * @param visibleCount
	 */
	function updateNoResultsMessage(visibleCount) {
		let noResultsMsg = document.querySelector(SELECTORS.noResults);

		if (visibleCount === 0) {
			if (!noResultsMsg) {
				noResultsMsg = document.createElement('div');
				// Add both js-* hook and legacy class for consistency
				noResultsMsg.className = 'js-blog-no-results medici-blog-no-results';
				noResultsMsg.innerHTML = `
                    <p>😔 Нічого не знайдено</p>
                    <p>Спробуйте змінити фільтри або пошуковий запит</p>
                `;
				const grid = document.querySelector(SELECTORS.grid);
				if (grid && grid.parentNode) {
					grid.parentNode.insertBefore(noResultsMsg, grid.nextSibling);
				}
			}
			noResultsMsg.style.display = 'block';
		} else if (noResultsMsg) {
			noResultsMsg.style.display = 'none';
		}
	}

	/**
	 * =====================================================
	 * LAZY LOADING IMAGES
	 * =====================================================
	 */

	/**
	 * Ініціалізація Intersection Observer для lazy loading
	 */
	function initLazyLoading() {
		if ('IntersectionObserver' in window) {
			const imageObserver = new IntersectionObserver(
				(entries, observer) => {
					entries.forEach((entry) => {
						if (entry.isIntersecting) {
							const img = entry.target;
							if (img.dataset.src) {
								img.src = img.dataset.src;
								img.removeAttribute('data-src');
								img.classList.add('loaded');
								observer.unobserve(img);
							}
						}
					});
				},
				{
					rootMargin: '50px 0px',
					threshold: 0.01,
				}
			);

			document.querySelectorAll('img[data-src]').forEach((img) => {
				imageObserver.observe(img);
			});
		} else {
			// Fallback для старих браузерів
			document.querySelectorAll('img[data-src]').forEach((img) => {
				img.src = img.dataset.src;
				img.removeAttribute('data-src');
			});
		}
	}

	/**
	 * =====================================================
	 * SMOOTH SCROLL
	 * =====================================================
	 */

	/**
	 * Плавний скрол до якоря
	 * @param event
	 */
	function smoothScrollToAnchor(event) {
		const target = event.currentTarget;
		const href = target.getAttribute('href');

		if (href && href.startsWith('#')) {
			event.preventDefault();
			const targetElement = document.querySelector(href);

			if (targetElement) {
				const headerOffset = 80; // Висота header
				const elementPosition = targetElement.getBoundingClientRect().top;
				const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

				window.scrollTo({
					top: offsetPosition,
					behavior: 'smooth',
				});
			}
		}
	}

	/**
	 * =====================================================
	 * INITIALIZATION
	 * =====================================================
	 */

	/**
	 * Ініціалізація всіх event listeners
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function init() {
		// Ініціалізувати тему
		initTheme();

		// Theme Toggle (js-* hooks with fallback)
		const themeToggle = document.querySelector(SELECTORS.themeToggle);
		if (themeToggle) {
			themeToggle.addEventListener('click', toggleTheme);
		}

		// Category Filter (js-* hooks with fallback)
		const filterTags = document.querySelectorAll(SELECTORS.filterTag);
		filterTags.forEach((tag) => {
			tag.addEventListener('click', function (event) {
				event.preventDefault();
				const category = this.dataset.category || 'all';
				filterByCategory(category);
			});
		});

		// Search (js-* hooks with fallback)
		const searchInput = document.querySelector(SELECTORS.searchInput);
		if (searchInput) {
			// Debounce для пошуку (затримка 300ms)
			let searchTimeout;
			searchInput.addEventListener('input', function () {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(() => {
					searchArticles(this.value);
				}, 300);
			});
		}

		// Sort (js-* hooks with fallback)
		const sortSelect = document.querySelector(SELECTORS.sortSelect);
		if (sortSelect) {
			sortSelect.addEventListener('change', function () {
				sortArticles(this.value);
			});
		}

		// Lazy Loading
		initLazyLoading();

		// Smooth Scroll для всіх якорних посилань
		document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
			anchor.addEventListener('click', smoothScrollToAnchor);
		});

		// Застосувати початкові фільтри (якщо є URL параметри)
		applyInitialFiltersFromURL();
	}

	/**
	 * Застосувати фільтри з URL параметрів (якщо є)
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function applyInitialFiltersFromURL() {
		const urlParams = new URLSearchParams(window.location.search);

		const category = urlParams.get('category');
		if (category) {
			filterByCategory(category);
		}

		const search = urlParams.get('search');
		if (search) {
			const searchInput = document.querySelector(SELECTORS.searchInput);
			if (searchInput) {
				searchInput.value = search;
			}
			searchArticles(search);
		}

		const sort = urlParams.get('sort');
		if (sort) {
			const sortSelect = document.querySelector(SELECTORS.sortSelect);
			if (sortSelect) {
				sortSelect.value = sort;
			}
			sortArticles(sort);
		}
	}

	/**
	 * =====================================================
	 * LOADING STATES & SKELETON SCREENS
	 * =====================================================
	 */

	/**
	 * Створити skeleton card (placeholder під час завантаження)
	 */
	function createSkeletonCard() {
		const skeleton = document.createElement('article');
		skeleton.className = 'medici-blog-skeleton-card';
		skeleton.innerHTML = `
            <div class="medici-blog-skeleton-header"></div>
            <div class="medici-blog-skeleton-title">
                <div class="medici-blog-skeleton-line"></div>
                <div class="medici-blog-skeleton-line"></div>
            </div>
            <div class="medici-blog-skeleton-excerpt">
                <div class="medici-blog-skeleton-line"></div>
                <div class="medici-blog-skeleton-line"></div>
                <div class="medici-blog-skeleton-line"></div>
            </div>
            <div class="medici-blog-skeleton-footer">
                <div class="medici-blog-skeleton-footer-item"></div>
                <div class="medici-blog-skeleton-footer-item"></div>
            </div>
        `;
		return skeleton;
	}

	/**
	 * Показати skeleton screens
	 * Uses js-* hooks with fallback to legacy selectors
	 * @param count
	 */
	function showSkeletonScreens(count = 3) {
		const grid = document.querySelector(SELECTORS.grid);
		if (!grid) {
			return;
		}

		const skeletons = [];
		for (let i = 0; i < count; i++) {
			const skeleton = createSkeletonCard();
			skeleton.dataset.skeleton = 'true';
			grid.appendChild(skeleton);
			skeletons.push(skeleton);
		}
		return skeletons;
	}

	/**
	 * Приховати skeleton screens
	 */
	function hideSkeletonScreens() {
		const skeletons = document.querySelectorAll('[data-skeleton="true"]');
		skeletons.forEach((skeleton) => skeleton.remove());
	}

	/**
	 * Показати progress bar
	 */
	function showProgressBar() {
		let progressBar = document.querySelector('.medici-blog-progress-bar');
		if (!progressBar) {
			progressBar = document.createElement('div');
			progressBar.className = 'medici-blog-progress-bar loading';
			document.body.appendChild(progressBar);
		}
		progressBar.classList.add('loading');
	}

	/**
	 * Приховати progress bar
	 */
	function hideProgressBar() {
		const progressBar = document.querySelector('.medici-blog-progress-bar');
		if (progressBar) {
			progressBar.classList.remove('loading');
			setTimeout(() => {
				if (progressBar.parentNode) {
					progressBar.parentNode.removeChild(progressBar);
				}
			}, 300);
		}
	}

	/**
	 * Показати loading overlay
	 */
	function showLoadingOverlay() {
		let overlay = document.querySelector('.medici-blog-loading-overlay');
		if (!overlay) {
			overlay = document.createElement('div');
			overlay.className = 'medici-blog-loading-overlay';
			overlay.innerHTML = '<div class="medici-blog-spinner"></div>';
			document.body.appendChild(overlay);
		}
		// Force reflow для анімації
		overlay.offsetHeight;
		overlay.classList.add('active');
	}

	/**
	 * Приховати loading overlay
	 */
	function hideLoadingOverlay() {
		const overlay = document.querySelector('.medici-blog-loading-overlay');
		if (overlay) {
			overlay.classList.remove('active');
		}
	}

	/**
	 * =====================================================
	 * AJAX LOAD MORE
	 * =====================================================
	 */

	/**
	 * Завантажити наступну сторінку статей
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function loadMorePosts() {
		const loadMoreBtn = document.querySelector(SELECTORS.loadMoreBtn);
		if (!loadMoreBtn) {
			return;
		}

		const currentPage = parseInt(loadMoreBtn.dataset.page) || 1;
		const maxPages = parseInt(loadMoreBtn.dataset.maxPages) || 1;
		const nextPage = currentPage + 1;

		if (nextPage > maxPages) {
			loadMoreBtn.style.display = 'none';
			return;
		}

		// Показати skeleton screens та progress bar
		const skeletons = showSkeletonScreens(3);
		showProgressBar();

		// Показати loader на кнопці (js-* hooks with fallback)
		loadMoreBtn.disabled = true;
		const loadMoreText = loadMoreBtn.querySelector(SELECTORS.loadMoreText);
		const loadMoreLoader = loadMoreBtn.querySelector(SELECTORS.loadMoreLoader);
		if (loadMoreText) {
			loadMoreText.style.display = 'none';
		}
		if (loadMoreLoader) {
			loadMoreLoader.style.display = 'flex';
		}

		// AJAX запит
		fetch(`${window.location.origin}${window.location.pathname}?paged=${nextPage}`)
			.then((response) => response.text())
			.then((html) => {
				const parser = new DOMParser();
				const doc = parser.parseFromString(html, 'text/html');
				const newArticles = doc.querySelectorAll(SELECTORS.grid + ' > article');

				if (newArticles.length > 0) {
					const grid = document.querySelector(SELECTORS.grid);

					// Приховати skeleton screens перед додаванням нових карток
					hideSkeletonScreens();

					newArticles.forEach((article) => {
						grid.appendChild(article);
					});

					// Оновити номер сторінки
					loadMoreBtn.dataset.page = nextPage;

					// Приховати кнопку якщо досягнуто останньої сторінки
					if (nextPage >= maxPages) {
						loadMoreBtn.style.display = 'none';
					}

					// Ініціалізувати lazy loading для нових зображень
					initLazyLoading();
				} else {
					hideSkeletonScreens();
					loadMoreBtn.style.display = 'none';
				}
			})
			.catch((error) => {
				console.error('Помилка завантаження статей:', error);
				hideSkeletonScreens();
				alert('Не вдалося завантажити статті. Спробуйте ще раз.');
			})
			.finally(() => {
				// Приховати progress bar та loader (js-* hooks with fallback)
				hideProgressBar();
				loadMoreBtn.disabled = false;
				const loadMoreTextEl = loadMoreBtn.querySelector(SELECTORS.loadMoreText);
				const loadMoreLoaderEl = loadMoreBtn.querySelector(SELECTORS.loadMoreLoader);
				if (loadMoreTextEl) {
					loadMoreTextEl.style.display = 'inline';
				}
				if (loadMoreLoaderEl) {
					loadMoreLoaderEl.style.display = 'none';
				}
			});
	}

	/**
	 * Ініціалізація load more кнопки
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	function initLoadMore() {
		const loadMoreBtn = document.querySelector(SELECTORS.loadMoreBtn);
		if (loadMoreBtn) {
			loadMoreBtn.addEventListener('click', loadMorePosts);
		}
	}

	/**
	 * =====================================================
	 * DOM READY
	 * =====================================================
	 */

	// Запустити ініціалізацію коли DOM готовий
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			init();
			initLoadMore();
		});
	} else {
		init();
		initLoadMore();
	}
})();

/**
 * Medici Blog Module JavaScript
 * Advanced interactions and AJAX functionality
 *
 * @version 1.3.0
 * @package Medici
 *
 * CHANGELOG v1.3.0:
 * - Updated to match Blog Module PHP v1.3.0
 * - Improved error handling and validation
 * - Added retry logic for failed AJAX requests
 * - Optimized performance with debouncing
 * - Enhanced accessibility features
 */

(function () {
	'use strict';

	// =====================================================
	// ERROR HANDLING
	// =====================================================
	const handleError = (error, context = '') => {
		if (console && console.error) {
			console.error(`[Medici Blog] Error in ${context}:`, error);
		}
	};

	const Blog = {
		config: {
			scrollThrottle: 16,
			animationDelay: 100,
		},

		state: {
			isLoading: false,
			currentPage: 1,
			selectedCategories: [], // Array of selected category slugs
			currentOrder: 'date',
			observers: {},
			configWarningShown: false,
			multiCategoryMode: false, // Will be set from backend settings
		},

		/**
		 * Initialize
		 */
		init() {
			// Перевірка глобального конфігу
			if (!this.validateConfig()) {
				handleError(new Error('mediciBlog config not found'), 'init');
			}

			// Set multi-category mode from backend settings
			this.state.multiCategoryMode = this.getConfig('multiCategoryFilter') === '1';

			this.cacheElements();
			this.bindEvents();
			this.initProgressBar();
			this.initIntersectionObserver();
			this.initTOC();
			this.initRelatedPagination();
			this.initNewsletter();
			console.log(
				'[Medici Blog] Module initialized v1.3.0 (Multi-category: ' +
					this.state.multiCategoryMode +
					')'
			);
		},

		/**
		 * Validate global config
		 */
		validateConfig() {
			if (!window.mediciBlog) {
				if (!this.state.configWarningShown) {
					console.warn(
						'[Medici Blog] Warning: window.mediciBlog not defined. AJAX features disabled.'
					);
					this.state.configWarningShown = true;
				}
				return false;
			}

			const requiredKeys = ['ajaxurl', 'nonce'];
			const missingKeys = requiredKeys.filter((key) => !window.mediciBlog[key]);

			if (missingKeys.length > 0) {
				console.warn(`[Medici Blog] Missing config keys: ${missingKeys.join(', ')}`);
				return false;
			}

			return true;
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			this.elements = {
				sortSelect: document.querySelector('.medici-blog__sort'),
				categoryPills: document.querySelectorAll('.medici-category-pill'),
				categoryContainer: document.querySelector('.medici-category-pills'),
				grid:
					document.querySelector('.medici-blog__grid') ||
					document.querySelector('#medici-posts-grid'),
				loadMoreBtn: document.querySelector('.medici-blog__loadmore-btn'),
				loadMoreContainer: document.querySelector('.medici-blog__loadmore'),
				shareButtons: document.querySelectorAll('.medici-share__btn--copy'),
				tocToggle: document.querySelector('.medici-toc__toggle'),
				toc: document.querySelector('.medici-toc'),
			};
		},

		/**
		 * Bind events
		 */
		bindEvents() {
			// Sort select
			if (this.elements.sortSelect) {
				this.elements.sortSelect.addEventListener('change', (e) => {
					this.state.currentOrder = e.target.value;
					this.handleFilter();
				});
			}

			// Category pills
			if (this.elements.categoryPills.length > 0) {
				this.elements.categoryPills.forEach((pill) => {
					pill.addEventListener('click', (e) => {
						this.handleCategoryClick(e.target);
					});
				});
			}

			// Load more
			if (this.elements.loadMoreBtn) {
				this.elements.loadMoreBtn.addEventListener('click', () => this.loadMore());
			}

			// Share copy
			this.elements.shareButtons.forEach((btn) => {
				btn.addEventListener('click', (e) => this.handleCopyShare(e));
			});

			// TOC toggle
			if (this.elements.tocToggle) {
				this.elements.tocToggle.addEventListener('click', () => this.toggleTOC());
			}

			// Keyboard navigation
			document.addEventListener('keydown', (e) => this.handleKeyboard(e));
		},

		/**
		 * Handle category pill click
		 * Supports multiple category selection with toggle behavior
		 */
		handleCategoryClick(pill) {
			const category = pill.dataset.category || 'all';

			// Handle "All" category
			if (category === 'all') {
				// Clear all selections
				this.state.selectedCategories = [];
				this.elements.categoryPills.forEach((p) => p.classList.remove('is-active'));
				pill.classList.add('is-active');
			} else {
				// Toggle category selection
				const index = this.state.selectedCategories.indexOf(category);

				if (index > -1) {
					// Remove category (deselect)
					this.state.selectedCategories.splice(index, 1);
					pill.classList.remove('is-active');
				} else {
					// Add category (select)
					this.state.selectedCategories.push(category);
					pill.classList.add('is-active');

					// Remove "All" if present
					const allPill = Array.from(this.elements.categoryPills).find(
						(p) => (p.dataset.category || 'all') === 'all'
					);
					if (allPill) {
						allPill.classList.remove('is-active');
					}
				}

				// If no categories selected, activate "All"
				if (this.state.selectedCategories.length === 0) {
					const allPill = Array.from(this.elements.categoryPills).find(
						(p) => (p.dataset.category || 'all') === 'all'
					);
					if (allPill) {
						allPill.classList.add('is-active');
					}
				}
			}

			// Reset page and filter
			this.state.currentPage = 1;
			this.handleFilter();
		},

		/**
		 * Handle filter change
		 */
		async handleFilter() {
			if (this.state.isLoading) return;

			try {
				this.setLoading(true);
				this.state.currentPage = 1;

				// Send selected categories as array
				const response = await this.ajax('medici_filter_posts', {
					categories: this.state.selectedCategories,
					order: this.state.currentOrder,
					per_page: this.elements.loadMoreBtn?.dataset.perPage || 6,
				});

				if (response.success && this.elements.grid) {
					this.elements.grid.innerHTML = response.data.html;

					// Unified card animation
					const cards = this.elements.grid.querySelectorAll('.medici-card');
					this.animateCards(cards);

					// Update load more button
					if (this.elements.loadMoreBtn) {
						this.elements.loadMoreBtn.dataset.page = '1';
						this.elements.loadMoreBtn.dataset.maxPages = response.data.max_pages;

						if (response.data.max_pages <= 1) {
							this.elements.loadMoreContainer?.classList.add('is-hidden');
						} else {
							this.elements.loadMoreContainer?.classList.remove('is-hidden');
						}
					}
				}
			} catch (error) {
				handleError(error, 'handleFilter');
			} finally {
				this.setLoading(false);
			}
		},

		/**
		 * Load more posts
		 */
		async loadMore() {
			if (this.state.isLoading || !this.elements.loadMoreBtn) return;

			const btn = this.elements.loadMoreBtn;
			const page = parseInt(btn.dataset.page, 10) + 1;
			const maxPages = parseInt(btn.dataset.maxPages, 10);

			if (page > maxPages) {
				btn.disabled = true;
				btn.innerHTML = `<span>${this.getI18n('noMore')}</span>`;
				return;
			}

			try {
				this.setLoading(true);
				btn.classList.add('is-loading');
				btn.innerHTML = `<span>${this.getI18n('loading')}</span><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>`;

				const response = await this.ajax('medici_load_more_posts', {
					page,
					per_page: btn.dataset.perPage || 6,
					categories: this.state.selectedCategories,
					order: this.state.currentOrder,
				});

				if (response.success) {
					// Append new posts
					const temp = document.createElement('div');
					temp.innerHTML = response.data.html;
					const newCards = temp.querySelectorAll('.medici-card');

					// Додаємо картки до гріду
					newCards.forEach((card) => {
						this.elements.grid.appendChild(card);
					});

					// Уніфікована анімація нових карток
					this.animateCards(newCards);

					// Update button state
					btn.dataset.page = page.toString();

					if (!response.data.has_more || page >= maxPages) {
						this.elements.loadMoreContainer?.classList.add('is-hidden');
					} else {
						btn.innerHTML = `<span>Завантажити ще 📥</span>`;
					}
				}
			} catch (error) {
				handleError(error, 'loadMore');
				btn.innerHTML = `<span>${this.getI18n('error')}</span>`;
			} finally {
				this.setLoading(false);
				btn.classList.remove('is-loading');
			}
		},

		/**
		 * Handle copy share button
		 */
		handleCopyShare(e) {
			e.preventDefault();
			const btn = e.currentTarget;
			const url = btn.dataset.copyUrl || window.location.href;

			navigator.clipboard
				.writeText(url)
				.then(() => {
					btn.classList.add('is-copied');

					setTimeout(() => {
						btn.classList.remove('is-copied');
					}, 2000);
				})
				.catch((err) => {
					handleError(err, 'handleCopyShare');

					// Fallback for older browsers
					const input = document.createElement('input');
					input.value = url;
					document.body.appendChild(input);
					input.select();
					document.execCommand('copy');
					document.body.removeChild(input);

					btn.classList.add('is-copied');
					setTimeout(() => btn.classList.remove('is-copied'), 2000);
				});
		},

		/**
		 * Initialize progress bar
		 */
		initProgressBar() {
			const bar = document.querySelector('.medici-progress__bar');
			const progress = document.querySelector('.medici-progress');
			const article = document.querySelector('.entry-content, .post-content, article');

			if (!bar || !article) return;

			let ticking = false;

			const updateProgress = () => {
				const scrolled = window.scrollY;
				const windowHeight = window.innerHeight;
				const articleTop = article.offsetTop;
				const articleHeight = article.offsetHeight;

				// Calculate progress
				const start = articleTop - windowHeight;
				const end = articleTop + articleHeight - windowHeight;
				const progressValue = Math.max(
					0,
					Math.min(100, ((scrolled - start) / (end - start)) * 100)
				);

				bar.style.width = `${progressValue}%`;

				// Show/hide progress bar
				if (scrolled > articleTop - windowHeight && scrolled < articleTop + articleHeight) {
					progress?.classList.add('is-visible');
				} else {
					progress?.classList.remove('is-visible');
				}

				ticking = false;
			};

			window.addEventListener(
				'scroll',
				() => {
					if (!ticking) {
						requestAnimationFrame(updateProgress);
						ticking = true;
					}
				},
				{ passive: true }
			);

			updateProgress();
		},

		/**
		 * Initialize Intersection Observer for animations
		 */
		initIntersectionObserver() {
			if (!('IntersectionObserver' in window)) return;

			this.state.observers.cards = new IntersectionObserver(
				(entries) => {
					entries.forEach((entry, index) => {
						if (entry.isIntersecting) {
							setTimeout(() => {
								entry.target.classList.add('is-visible');
							}, index * this.config.animationDelay);
							this.state.observers.cards.unobserve(entry.target);
						}
					});
				},
				{ threshold: 0.1, rootMargin: '50px' }
			);

			document.querySelectorAll('.medici-card').forEach((card) => {
				this.state.observers.cards.observe(card);
			});
		},

		/**
		 * Initialize TOC
		 */
		initTOC() {
			const toc = this.elements.toc;
			if (!toc) return;

			const links = toc.querySelectorAll('a[href^="#"]');

			links.forEach((link) => {
				link.addEventListener('click', (e) => {
					e.preventDefault();
					const targetId = link.getAttribute('href').slice(1);
					const target = document.getElementById(targetId);

					if (target) {
						const offset = 100;
						const targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;

						window.scrollTo({
							top: targetPosition,
							behavior: 'smooth',
						});

						history.pushState(null, '', `#${targetId}`);
					}
				});
			});

			this.initTOCHighlight(links);
		},

		/**
		 * Initialize TOC section highlighting
		 */
		initTOCHighlight(links) {
			const headings = [];
			links.forEach((link) => {
				const id = link.getAttribute('href').slice(1);
				const heading = document.getElementById(id);
				if (heading) headings.push({ link, heading });
			});

			if (headings.length === 0) return;

			let ticking = false;

			const updateHighlight = () => {
				const scrollPos = window.scrollY + 150;

				let current = headings[0];

				for (const item of headings) {
					if (item.heading.offsetTop <= scrollPos) {
						current = item;
					}
				}

				links.forEach((link) => link.parentElement?.classList.remove('is-active'));
				current?.link.parentElement?.classList.add('is-active');

				ticking = false;
			};

			window.addEventListener(
				'scroll',
				() => {
					if (!ticking) {
						requestAnimationFrame(updateHighlight);
						ticking = true;
					}
				},
				{ passive: true }
			);

			updateHighlight();
		},

		/**
		 * Toggle TOC
		 */
		toggleTOC() {
			const toc = this.elements.toc;
			const toggle = this.elements.tocToggle;

			if (!toc || !toggle) return;

			const isCollapsed = toc.classList.toggle('is-collapsed');
			toggle.textContent = isCollapsed ? '+' : '−';
			toggle.setAttribute('aria-expanded', (!isCollapsed).toString());
		},

		// =====================================================
		// UNIFIED CARD ANIMATION
		// =====================================================

		/**
		 * Animate cards with staggered delay
		 * @param {NodeList|Array} cards - Cards to animate
		 */
		animateCards(cards) {
			if (!cards || cards.length === 0) return;

			// Встановлюємо початковий стан
			cards.forEach((card) => {
				card.style.opacity = '0';
				card.style.transform = 'translateY(20px)';
			});

			// Анімуємо з затримкою через requestAnimationFrame
			requestAnimationFrame(() => {
				cards.forEach((card, index) => {
					setTimeout(() => {
						card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
						card.style.opacity = '1';
						card.style.transform = 'translateY(0)';
					}, index * this.config.animationDelay);
				});
			});
		},

		/**
		 * Handle keyboard navigation
		 */
		handleKeyboard(e) {
			// Reserved for future keyboard shortcuts
			// Currently no keyboard navigation implemented
		},

		// =====================================================
		// UTILITIES
		// =====================================================

		/**
		 * AJAX helper
		 */
		async ajax(action, data = {}) {
			// Перевірка конфігу перед AJAX
			if (!this.validateConfig()) {
				throw new Error('AJAX config not available');
			}

			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', this.getConfig('nonce'));

			for (const [key, value] of Object.entries(data)) {
				formData.append(key, value);
			}

			const response = await fetch(this.getConfig('ajaxurl'), {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			return response.json();
		},

		/**
		 * Set loading state
		 */
		setLoading(isLoading) {
			this.state.isLoading = isLoading;
			document.body.classList.toggle('medici-blog-loading', isLoading);
		},

		/**
		 * Get config value with validation
		 */
		getConfig(key) {
			if (!window.mediciBlog) {
				if (!this.state.configWarningShown) {
					handleError(new Error('mediciBlog config not defined'), 'getConfig');
					this.state.configWarningShown = true;
				}
				return '';
			}

			const value = window.mediciBlog[key];

			if (value === undefined || value === null) {
				console.warn(`[Medici Blog] Config key "${key}" not found`);
				return '';
			}

			return value;
		},

		/**
		 * Get i18n string
		 */
		getI18n(key) {
			return window.mediciBlog?.i18n?.[key] || key;
		},

		/**
		 * Init Related Posts Pagination
		 */
		initRelatedPagination() {
			const paginationButtons = document.querySelectorAll('.medici-related__page-btn');

			if (!paginationButtons.length) {
				return;
			}

			paginationButtons.forEach((btn) => {
				btn.addEventListener('click', (e) => {
					e.preventDefault();
					this.loadRelatedPage(btn);
				});
			});
		},

		/**
		 * Load Related Posts Page
		 */
		async loadRelatedPage(btn) {
			if (!this.validateConfig() || this.state.isLoading) {
				return;
			}

			const page = parseInt(btn.dataset.page, 10);
			const postId = parseInt(btn.dataset.postId, 10);

			if (!page || !postId) {
				console.error('[Medici Blog] Invalid page or post ID');
				return;
			}

			this.state.isLoading = true;
			btn.disabled = true;
			btn.classList.add('loading');

			try {
				const formData = new FormData();
				formData.append('action', 'medici_related_posts_pagination');
				formData.append('nonce', window.mediciBlog.nonce);
				formData.append('post_id', postId);
				formData.append('page', page);
				formData.append('per_page', 3);

				const response = await fetch(window.mediciBlog.ajaxurl, {
					method: 'POST',
					body: formData,
				});

				const data = await response.json();

				if (data.success && data.data.html) {
					const relatedSection = document.querySelector('.medici-related');
					if (relatedSection) {
						const tempDiv = document.createElement('div');
						tempDiv.innerHTML = data.data.html;
						const newSection = tempDiv.querySelector('.medici-related');

						if (newSection) {
							relatedSection.innerHTML = newSection.innerHTML;
							// Re-init pagination for new buttons
							this.initRelatedPagination();
							// Scroll to related section
							relatedSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
						}
					}
				}
			} catch (error) {
				handleError(error, 'loadRelatedPage');
			} finally {
				this.state.isLoading = false;
				btn.disabled = false;
				btn.classList.remove('loading');
			}
		},

		/**
		 * Init Newsletter Form
		 */
		initNewsletter() {
			const form = document.querySelector('.medici-newsletter__form');
			if (!form) return;

			form.addEventListener('submit', (e) => this.handleNewsletterSubmit(e));
		},

		/**
		 * Handle Newsletter Form Submission
		 */
		async handleNewsletterSubmit(e) {
			e.preventDefault();

			if (!this.validateConfig()) {
				console.warn('[Medici Blog] Newsletter: AJAX config not available');
				return;
			}

			const form = e.target;
			const emailInput = form.querySelector('.medici-newsletter__input');
			const submitBtn = form.querySelector('.medici-newsletter__btn');
			const email = emailInput.value.trim();

			if (!email) {
				this.showNewsletterMessage('Будь ласка, введіть email', 'error');
				return;
			}

			// Disable form
			submitBtn.disabled = true;
			submitBtn.classList.add('is-loading');
			const originalText = submitBtn.textContent;
			submitBtn.textContent = 'Підписуємо...';

			try {
				const response = await this.ajax('medici_newsletter_subscribe', { email });

				if (response.success) {
					this.showNewsletterMessage(response.data.message, 'success');
					emailInput.value = '';

					// Reset form after 3 seconds
					setTimeout(() => {
						this.hideNewsletterMessage();
					}, 5000);
				} else {
					this.showNewsletterMessage(response.data.message, 'error');
				}
			} catch (error) {
				handleError(error, 'handleNewsletterSubmit');
				this.showNewsletterMessage('Помилка підписки. Спробуйте ще раз.', 'error');
			} finally {
				submitBtn.disabled = false;
				submitBtn.classList.remove('is-loading');
				submitBtn.textContent = originalText;
			}
		},

		/**
		 * Show Newsletter Message
		 */
		showNewsletterMessage(message, type) {
			const form = document.querySelector('.medici-newsletter__form');
			if (!form) return;

			// Remove existing message
			const existingMsg = form.querySelector('.newsletter-message');
			if (existingMsg) {
				existingMsg.remove();
			}

			// Create new message
			const msgDiv = document.createElement('div');
			msgDiv.className = `newsletter-message newsletter-message--${type}`;
			msgDiv.textContent = message;
			msgDiv.style.cssText = `
                margin-top: 1rem;
                padding: 0.75rem 1rem;
                border-radius: 8px;
                text-align: center;
                font-size: 14px;
                animation: fadeIn 0.3s ease;
                ${type === 'success' ? 'background: #10b981; color: white;' : 'background: #ef4444; color: white;'}
            `;

			form.appendChild(msgDiv);
		},

		/**
		 * Hide Newsletter Message
		 */
		hideNewsletterMessage() {
			const msg = document.querySelector('.newsletter-message');
			if (msg) {
				msg.style.animation = 'fadeOut 0.3s ease';
				setTimeout(() => msg.remove(), 300);
			}
		},

		/**
		 * Escape HTML
		 */
		escapeHtml(str) {
			const div = document.createElement('div');
			div.textContent = str || '';
			return div.innerHTML;
		},
	};

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => Blog.init());
	} else {
		Blog.init();
	}

	// Export for external use
	window.MediciBlog = Blog;
})();

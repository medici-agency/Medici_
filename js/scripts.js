/**
 * Medici Medical Marketing
 * Version: 1.3.0
 * Author: Medici - Medical Marketing Agency
 * Domain: medici.agency
 *
 * JS Hooks (recommended for new code):
 * - .js-mobile-toggle    - Mobile menu toggle button
 * - .js-nav-menu         - Navigation menu container
 * - .js-nav-link         - Navigation link
 * - .js-theme-toggle     - Theme toggle button
 * - .js-scroll-to-top    - Scroll to top button
 *
 * @since 1.3.0 Added js-* hooks for BEM separation
 */

'use strict';

// =====================================================
// CONFIGURATION
// =====================================================
const CONFIG = {
	THEME: {
		STORAGE_KEY: 'theme',
		DEFAULT: 'light',
		ICONS: { light: '🌙', dark: '☀️' },
		LABELS: { light: 'Темна тема', dark: 'Світла тема' },
	},
	SCROLL: {
		NAV_OFFSET: 100,
		SMOOTH_OFFSET: 20,
		BUTTON_THRESHOLD: 300,
		SCROLLED_THRESHOLD: 50,
	},
	BREAKPOINTS: {
		MOBILE: 1024,
	},
	TIMING: {
		RESIZE_DEBOUNCE: 250,
	},
};

// =====================================================
// JS HOOKS SELECTORS (js-* classes convention)
// Fallback to CSS classes for backward compatibility
// =====================================================
const SELECTORS = {
	// Theme Toggle
	themeToggle: '.js-theme-toggle, #theme-toggle, #themeToggle',
	themeIcon: '.js-theme-icon, .theme-icon',

	// Navigation
	navigation: '.js-navigation, .gbp-navigation',
	navCenter: '.js-nav-center, .gbp-nav-center',
	navLink: '.js-nav-link, .gbp-nav-link',
	mobileToggle: '.js-mobile-toggle, .gbp-mobile-toggle',
	ctaButton: '.js-cta-button, .gbp-cta-button',

	// Scroll to Top
	scrollToTop: '.js-scroll-to-top, .scroll-to-top',

	// Animations
	fadeIn: '.fade-in',
	fadeInUp: '.fade-in-up',
	fadeInLeft: '.fade-in-left',
	fadeInRight: '.fade-in-right',
	fadeInScale: '.fade-in-scale',
};

// =====================================================
// STORAGE UTILITIES
// =====================================================
const storageAvailable = (() => {
	try {
		const test = '__storage_test__';
		localStorage.setItem(test, test);
		localStorage.removeItem(test);
		return true;
	} catch {
		return false;
	}
})();

const storage = {
	/**
	 * Отримати значення з localStorage
	 * @param {string} key          - Ключ
	 * @param {*}      defaultValue - Значення за замовчуванням
	 * @return {*} Збережене значення або defaultValue
	 */
	get: (key, defaultValue = null) => {
		if (!storageAvailable) {
			return defaultValue;
		}
		try {
			return localStorage.getItem(key) || defaultValue;
		} catch {
			return defaultValue;
		}
	},

	/**
	 * Зберегти значення в localStorage
	 * @param {string} key   - Ключ
	 * @param {string} value - Значення
	 * @return {boolean} Успішність операції
	 */
	set: (key, value) => {
		if (!storageAvailable) {
			return false;
		}
		try {
			localStorage.setItem(key, value);
			return true;
		} catch {
			return false;
		}
	},
};

// =====================================================
// DOM UTILITIES
// =====================================================
const domCache = new Map();

/**
 * Отримати елемент з кешуванням
 * @param {string} selector - CSS селектор
 * @return {Element|null}
 */
const getElement = (selector) => {
	if (!domCache.has(selector)) {
		domCache.set(selector, document.querySelector(selector));
	}
	return domCache.get(selector);
};

/**
 * Отримати множину елементів з кешуванням
 * @param {string} selector - CSS селектор
 * @return {NodeList}
 */
const getElements = (selector) => {
	if (!domCache.has(selector)) {
		domCache.set(selector, document.querySelectorAll(selector));
	}
	return domCache.get(selector);
};

/**
 * Очистити кеш DOM-елементів
 */
const clearDOMCache = () => domCache.clear();

// =====================================================
// UTILITY FUNCTIONS
// =====================================================
/**
 * Debounce функція
 * @param {Function} func - Функція для debounce
 * @param {number}   wait - Затримка в мс
 * @return {Function}
 */
const debounce = (func, wait) => {
	let timeout;
	return function executedFunction(...args) {
		const later = () => {
			clearTimeout(timeout);
			func(...args);
		};
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
	};
};

/**
 * Throttle функція через requestAnimationFrame
 * @param {Function} func - Функція для throttle
 * @return {Function}
 */
const throttle = (func) => {
	let ticking = false;
	return function throttledFunction(...args) {
		if (!ticking) {
			window.requestAnimationFrame(() => {
				func.apply(this, args);
				ticking = false;
			});
			ticking = true;
		}
	};
};

/**
 * Обробка помилок
 * @param {Error}  error   - Об'єкт помилки
 * @param {string} context - Контекст помилки
 */
const handleError = (error, context = '') => {
	if (console?.error) {
		console.error(`[Medici] Error in ${context}:`, error);
	}
};

// =====================================================
// THEME TOGGLE MODULE
// =====================================================
const ThemeModule = {
	toggle: null,
	_handlers: {},

	/**
	 * Ініціалізація перемикача теми
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	init() {
		// Use js-* hook with fallback to legacy ID selectors
		this.toggle =
			getElement('.js-theme-toggle') || getElement('#theme-toggle') || getElement('#themeToggle');
		if (!this.toggle) {
			return;
		}

		const savedTheme = storage.get(CONFIG.THEME.STORAGE_KEY, CONFIG.THEME.DEFAULT);
		this.setTheme(savedTheme);

		// Зберігаємо посилання на обробник для можливості видалення
		this._handlers.click = () => {
			try {
				const currentTheme = document.documentElement.getAttribute('data-theme');
				const newTheme = currentTheme === 'light' ? 'dark' : 'light';
				this.setTheme(newTheme);
				storage.set(CONFIG.THEME.STORAGE_KEY, newTheme);
			} catch (error) {
				handleError(error, 'themeToggle');
			}
		};

		this.toggle.addEventListener('click', this._handlers.click);
	},

	/**
	 * Встановити тему
	 * @param {string} theme - Назва теми (light/dark)
	 */
	setTheme(theme) {
		document.documentElement.setAttribute('data-theme', theme);
		this.updateIcon(theme);
	},

	/**
	 * Оновити іконку перемикача
	 * @param {string} theme - Назва теми
	 */
	updateIcon(theme) {
		if (!this.toggle) {
			return;
		}

		const icon = this.toggle.querySelector(SELECTORS.themeIcon);
		const iconText = CONFIG.THEME.ICONS[theme];
		const ariaLabel = CONFIG.THEME.LABELS[theme];

		if (icon) {
			icon.textContent = iconText;
		} else {
			this.toggle.textContent = iconText;
		}

		this.toggle.setAttribute('aria-label', ariaLabel);
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		if (this.toggle && this._handlers.click) {
			this.toggle.removeEventListener('click', this._handlers.click);
		}
		this._handlers = {};
		this.toggle = null;
	},
};

// =====================================================
// MOBILE MENU MODULE
// =====================================================
const MobileMenuModule = {
	toggle: null,
	container: null,
	nav: null,
	isOpen: false,
	touchStartX: 0,
	touchEndX: 0,
	touchStartY: 0,
	touchEndY: 0,
	_handlers: {},
	_menuLinkHandlers: [],

	/**
	 * Ініціалізація мобільного меню
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	init() {
		// Use js-* hooks with fallback to legacy class names
		this.toggle = getElement('.js-mobile-toggle') || getElement('.gbp-mobile-toggle');
		this.container = getElement('.js-nav-menu') || getElement('.gbp-nav-center');
		this.nav = getElement('.gbp-navigation');

		if (!this.toggle || !this.container) {
			return;
		}

		this.createHamburgerLines();
		this.attachEventListeners();
		this.initSwipeGestures();
	},

	/**
	 * Створити лінії hamburger меню
	 */
	createHamburgerLines() {
		const existingLines = this.toggle.querySelectorAll(
			'.hamburger-line, .gbp-navigation__hamburger-line'
		);
		if (existingLines.length === 0) {
			for (let i = 0; i < 3; i++) {
				const line = document.createElement('span');
				// Use both BEM and legacy class names for compatibility
				line.className = 'gbp-navigation__hamburger-line hamburger-line';
				this.toggle.appendChild(line);
			}
		}
	},

	/**
	 * Прикріпити event listeners
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	attachEventListeners() {
		// Toggle кнопка
		this._handlers.toggleClick = (e) => {
			e.stopPropagation();
			try {
				this.toggleMenu();
			} catch (error) {
				handleError(error, 'mobileMenuToggle');
			}
		};
		this.toggle.addEventListener('click', this._handlers.toggleClick);

		// Закриття при кліку на посилання (js-* hooks + legacy classes)
		const menuLinks = this.container.querySelectorAll(
			'.js-nav-link, .gbp-nav-link, .gbp-navigation__link, .gbp-cta-button'
		);
		menuLinks.forEach((link) => {
			const handler = () => {
				if (this.isOpen) {
					this.close();
				}
			};
			this._menuLinkHandlers.push({ element: link, handler });
			link.addEventListener('click', handler);
		});

		// Закриття при кліку зовні (uses SELECTORS.navigation)
		this._handlers.documentClick = (e) => {
			if (!e.target.closest(SELECTORS.navigation) && this.isOpen) {
				this.close();
			}
		};
		document.addEventListener('click', this._handlers.documentClick);

		// Закриття по Escape
		this._handlers.documentKeydown = (e) => {
			if (e.key === 'Escape' && this.isOpen) {
				this.close();
			}
		};
		document.addEventListener('keydown', this._handlers.documentKeydown);
	},

	/**
	 * Перемкнути стан меню
	 */
	toggleMenu() {
		this.isOpen = !this.isOpen;

		// Use both BEM and legacy class names for compatibility
		this.container.classList.toggle('gbp-navigation__menu--open', this.isOpen);
		this.container.classList.toggle('menu--open', this.isOpen);
		this.toggle.classList.toggle('gbp-navigation__mobile-toggle--active', this.isOpen);
		this.toggle.classList.toggle('mobile-menu-toggle--active', this.isOpen);
		document.body.style.overflow = this.isOpen ? 'hidden' : '';

		this.toggle.setAttribute('aria-expanded', this.isOpen.toString());
		this.toggle.setAttribute('aria-label', this.isOpen ? 'Закрити меню' : 'Відкрити меню');

		// Haptic feedback при відкритті/закритті
		this.triggerHapticFeedback(this.isOpen ? 'medium' : 'light');

		if (this.isOpen && this.nav) {
			this.container.style.top = `${this.nav.offsetHeight}px`;
		}
	},

	/**
	 * Закрити меню
	 */
	close() {
		this.isOpen = false;
		// Remove both BEM and legacy class names
		this.toggle.classList.remove(
			'gbp-navigation__mobile-toggle--active',
			'mobile-menu-toggle--active'
		);
		this.container.classList.remove('gbp-navigation__menu--open', 'menu--open');
		document.body.style.overflow = '';
		this.toggle.setAttribute('aria-expanded', 'false');
		this.toggle.setAttribute('aria-label', 'Відкрити меню');
	},

	/**
	 * Ініціалізація swipe gestures для mobile menu
	 */
	initSwipeGestures() {
		if (!this.container) {
			return;
		}

		// Touch Start
		this._handlers.touchStart = (e) => {
			this.touchStartX = e.changedTouches[0].screenX;
			this.touchStartY = e.changedTouches[0].screenY;
		};
		this.container.addEventListener('touchstart', this._handlers.touchStart, { passive: true });

		// Touch End
		this._handlers.touchEnd = (e) => {
			this.touchEndX = e.changedTouches[0].screenX;
			this.touchEndY = e.changedTouches[0].screenY;
			this.handleSwipeGesture();
		};
		this.container.addEventListener('touchend', this._handlers.touchEnd, { passive: true });
	},

	/**
	 * Обробка swipe gesture
	 */
	handleSwipeGesture() {
		const swipeThreshold = 50; // Мінімальна відстань для розпізнавання свайпу
		const swipeDistanceX = this.touchEndX - this.touchStartX;
		const swipeDistanceY = Math.abs(this.touchEndY - this.touchStartY);

		// Закриття меню при свайпі вліво або вправо (тільки якщо меню відкрите)
		if (this.isOpen && Math.abs(swipeDistanceX) > swipeThreshold && swipeDistanceY < 100) {
			// Swipe left або right - закрити меню
			this.close();
			this.triggerHapticFeedback('light');
		}
	},

	/**
	 * Тригер haptic feedback (вібрація тільки для Android)
	 * @param {string} type - Тип вібрації: 'light', 'medium', 'heavy'
	 */
	triggerHapticFeedback(type = 'light') {
		// Vibration API працює тільки на Android
		if (!('vibrate' in navigator)) {
			return;
		}

		try {
			const vibrationPatterns = {
				light: 10, // Легка вібрація (10ms)
				medium: 20, // Середня вібрація (20ms)
				heavy: 30, // Сильна вібрація (30ms)
			};
			const pattern = vibrationPatterns[type] || vibrationPatterns.light;
			navigator.vibrate(pattern);
		} catch (error) {
			// Ігноруємо помилки якщо vibrate не підтримується
		}
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		// Видалити toggle click
		if (this.toggle && this._handlers.toggleClick) {
			this.toggle.removeEventListener('click', this._handlers.toggleClick);
		}

		// Видалити menu link handlers
		this._menuLinkHandlers.forEach(({ element, handler }) => {
			element.removeEventListener('click', handler);
		});
		this._menuLinkHandlers = [];

		// Видалити document listeners
		if (this._handlers.documentClick) {
			document.removeEventListener('click', this._handlers.documentClick);
		}
		if (this._handlers.documentKeydown) {
			document.removeEventListener('keydown', this._handlers.documentKeydown);
		}

		// Видалити touch listeners
		if (this.container) {
			if (this._handlers.touchStart) {
				this.container.removeEventListener('touchstart', this._handlers.touchStart);
			}
			if (this._handlers.touchEnd) {
				this.container.removeEventListener('touchend', this._handlers.touchEnd);
			}
		}

		this._handlers = {};
		this.toggle = null;
		this.container = null;
		this.nav = null;
		this.isOpen = false;
	},
};

// =====================================================
// NAVIGATION MODULE
// =====================================================
const NavigationModule = {
	nav: null,
	_handlers: {},

	/**
	 * Ініціалізація ефекту прокрутки навігації
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	init() {
		this.nav = getElement(SELECTORS.navigation);
		if (!this.nav) {
			return;
		}

		this._handlers.scroll = throttle(() => {
			const scrollY = window.scrollY;
			const isScrolled = scrollY > CONFIG.SCROLL.SCROLLED_THRESHOLD;
			// Use both BEM and legacy class names for compatibility
			this.nav.classList.toggle('gbp-navigation--scrolled', isScrolled);
			this.nav.classList.toggle('scrolled', isScrolled);
		});

		window.addEventListener('scroll', this._handlers.scroll, { passive: true });
	},

	/**
	 * Отримати висоту навігації
	 * @return {number}
	 */
	getHeight() {
		return this.nav ? this.nav.offsetHeight : 0;
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		if (this._handlers.scroll) {
			window.removeEventListener('scroll', this._handlers.scroll);
		}
		this._handlers = {};
		this.nav = null;
	},
};

// =====================================================
// SMOOTH SCROLL MODULE
// =====================================================
const SmoothScrollModule = {
	_anchorHandlers: [],

	/**
	 * Ініціалізація плавної прокрутки
	 */
	init() {
		const navHeight = NavigationModule.getHeight();

		getElements('a[href^="#"]').forEach((anchor) => {
			const handler = function (e) {
				try {
					const targetId = this.getAttribute('href');

					if (targetId === '#') {
						e.preventDefault();
						window.scrollTo({ top: 0, behavior: 'smooth' });
						return;
					}

					const targetElement = document.querySelector(targetId);
					if (targetElement) {
						e.preventDefault();
						const targetPosition =
							targetElement.offsetTop - navHeight - CONFIG.SCROLL.SMOOTH_OFFSET;
						window.scrollTo({ top: targetPosition, behavior: 'smooth' });
					}
				} catch (error) {
					handleError(error, 'smoothScroll');
				}
			};

			SmoothScrollModule._anchorHandlers.push({ element: anchor, handler });
			anchor.addEventListener('click', handler);
		});
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		this._anchorHandlers.forEach(({ element, handler }) => {
			element.removeEventListener('click', handler);
		});
		this._anchorHandlers = [];
	},
};

// =====================================================
// ACTIVE LINKS MODULE
// =====================================================
const ActiveLinksModule = {
	_handlers: {},

	/**
	 * Ініціалізація підсвічування активних посилань
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	init() {
		const navHeight = NavigationModule.getHeight();
		const sections = getElements('section[id]');
		// Support both js-* hooks and legacy class names
		const navLinks = document.querySelectorAll(
			'.js-nav-link, .gbp-nav-link, .gbp-navigation__link'
		);

		if (!sections.length || !navLinks.length) {
			return;
		}

		this._handlers.scroll = throttle(() => {
			const scrollY = window.scrollY;

			sections.forEach((section) => {
				const sectionTop = section.offsetTop - navHeight - CONFIG.SCROLL.NAV_OFFSET;
				const sectionHeight = section.offsetHeight;
				const sectionId = section.getAttribute('id');

				if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
					navLinks.forEach((link) =>
						link.classList.remove('active', 'gbp-navigation__link--active')
					);
					// Find active link with any of the supported class names
					const activeLink = document.querySelector(
						`.js-nav-link[href="#${sectionId}"], .gbp-nav-link[href="#${sectionId}"], .gbp-navigation__link[href="#${sectionId}"]`
					);
					if (activeLink) {
						activeLink.classList.add('active');
						activeLink.classList.add('gbp-navigation__link--active');
					}
				}
			});
		});

		window.addEventListener('scroll', this._handlers.scroll, { passive: true });
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		if (this._handlers.scroll) {
			window.removeEventListener('scroll', this._handlers.scroll);
		}
		this._handlers = {};
	},
};

// =====================================================
// SCROLL TO TOP MODULE
// =====================================================
const ScrollToTopModule = {
	button: null,
	_handlers: {},

	/**
	 * Ініціалізація кнопки прокрутки вгору
	 */
	init() {
		this.createButton();
		this.attachEventListeners();
	},

	/**
	 * Створити кнопку
	 */
	createButton() {
		this.button = document.createElement('button');
		// Use both js-* hook and base class
		this.button.className = 'scroll-to-top js-scroll-to-top';
		this.button.innerHTML = '↑';
		this.button.setAttribute('aria-label', 'Прокрутити вгору');
		document.body.appendChild(this.button);
	},

	/**
	 * Прикріпити event listeners
	 */
	attachEventListeners() {
		this._handlers.scroll = throttle(() => {
			const isVisible = window.scrollY > CONFIG.SCROLL.BUTTON_THRESHOLD;
			// Use both BEM modifier and legacy class for compatibility
			this.button.classList.toggle('scroll-to-top--visible', isVisible);
			this.button.classList.toggle('visible', isVisible);
		});

		window.addEventListener('scroll', this._handlers.scroll, { passive: true });

		this._handlers.click = () => {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		};
		this.button.addEventListener('click', this._handlers.click);
	},

	/**
	 * Очистити event listeners та видалити кнопку
	 */
	destroy() {
		if (this._handlers.scroll) {
			window.removeEventListener('scroll', this._handlers.scroll);
		}
		if (this.button) {
			if (this._handlers.click) {
				this.button.removeEventListener('click', this._handlers.click);
			}
			this.button.remove();
		}
		this._handlers = {};
		this.button = null;
	},
};

// =====================================================
// ACCESSIBILITY MODULE
// =====================================================
const AccessibilityModule = {
	_keyboardHandlers: [],
	_focusTrapHandler: null,
	_menuContainer: null,

	/**
	 * Ініціалізація accessibility features
	 */
	init() {
		this.initKeyboardNavigation();
		this.initFocusTrap();
	},

	/**
	 * Ініціалізація клавіатурної навігації
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	initKeyboardNavigation() {
		// Use js-* hooks with fallback to legacy class names
		const interactiveElements = [
			getElement('.js-mobile-toggle') || getElement('.gbp-mobile-toggle'),
			getElement('.js-theme-toggle') || getElement('#theme-toggle') || getElement('#themeToggle'),
			getElement('.js-scroll-to-top') || getElement('.scroll-to-top'),
		].filter(Boolean);

		interactiveElements.forEach((element) => {
			const handler = (e) => {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					element.click();
				}
			};
			this._keyboardHandlers.push({ element, handler });
			element.addEventListener('keydown', handler);
		});
	},

	/**
	 * Ініціалізація focus trap для мобільного меню
	 * Uses js-* hooks with fallback to legacy selectors
	 */
	initFocusTrap() {
		// Use js-* hook with fallback to legacy class name
		this._menuContainer = getElement('.js-nav-menu') || getElement('.gbp-nav-center');
		if (!this._menuContainer) {
			return;
		}

		this._focusTrapHandler = (e) => {
			// Check both BEM modifier and legacy class
			const isMenuOpen =
				this._menuContainer.classList.contains('gbp-navigation__menu--open') ||
				this._menuContainer.classList.contains('menu--open');
			if (e.key === 'Tab' && isMenuOpen) {
				const focusableElements = this._menuContainer.querySelectorAll(
					'a:not([disabled]), button:not([disabled])'
				);

				const firstFocusable = focusableElements[0];
				const lastFocusable = focusableElements[focusableElements.length - 1];

				if (e.shiftKey) {
					if (document.activeElement === firstFocusable) {
						e.preventDefault();
						lastFocusable.focus();
					}
				} else if (document.activeElement === lastFocusable) {
					e.preventDefault();
					firstFocusable.focus();
				}
			}
		};

		this._menuContainer.addEventListener('keydown', this._focusTrapHandler);
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		// Видалити keyboard handlers
		this._keyboardHandlers.forEach(({ element, handler }) => {
			element.removeEventListener('keydown', handler);
		});
		this._keyboardHandlers = [];

		// Видалити focus trap
		if (this._menuContainer && this._focusTrapHandler) {
			this._menuContainer.removeEventListener('keydown', this._focusTrapHandler);
		}
		this._focusTrapHandler = null;
		this._menuContainer = null;
	},
};

// =====================================================
// SCROLL ANIMATIONS MODULE (Intersection Observer)
// =====================================================
const ScrollAnimationsModule = {
	observer: null,
	_observedElements: [],

	/**
	 * Ініціалізація scroll-triggered анімацій
	 */
	init() {
		// Перевірка підтримки Intersection Observer
		if (!('IntersectionObserver' in window)) {
			this.showAllElements();
			return;
		}

		// Перевірка prefers-reduced-motion
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			this.showAllElements();
			return;
		}

		this.createObserver();
		this.observeElements();
	},

	/**
	 * Створити Intersection Observer
	 */
	createObserver() {
		const options = {
			root: null,
			rootMargin: '0px 0px -50px 0px', // Тригер трохи раніше
			threshold: 0.1, // 10% елемента видно
		};

		this.observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					const element = entry.target;

					// Встановити затримку з data-delay атрибута
					const delay = element.dataset.delay;
					if (delay) {
						element.style.setProperty('--animation-delay', `${delay}ms`);
					}

					// Додати клас для анімації
					element.classList.add('is-visible');

					// Припинити спостереження (анімація одноразова)
					this.observer.unobserve(element);
				}
			});
		}, options);
	},

	/**
	 * Спостерігати за елементами з класами анімації
	 */
	observeElements() {
		const selectors = [
			'.fade-in',
			'.fade-in-up',
			'.fade-in-left',
			'.fade-in-right',
			'.fade-in-scale',
		];

		const elements = document.querySelectorAll(selectors.join(', '));

		elements.forEach((element) => {
			this.observer.observe(element);
			this._observedElements.push(element);
		});
	},

	/**
	 * Показати всі елементи (fallback для браузерів без підтримки)
	 */
	showAllElements() {
		const selectors = [
			'.fade-in',
			'.fade-in-up',
			'.fade-in-left',
			'.fade-in-right',
			'.fade-in-scale',
		];

		document.querySelectorAll(selectors.join(', ')).forEach((element) => {
			element.classList.add('is-visible');
		});
	},

	/**
	 * Очистити observer
	 */
	destroy() {
		if (this.observer) {
			this._observedElements.forEach((element) => {
				this.observer.unobserve(element);
			});
			this.observer.disconnect();
			this.observer = null;
		}
		this._observedElements = [];
	},
};

// =====================================================
// RESIZE HANDLER MODULE
// =====================================================
const ResizeHandlerModule = {
	_handlers: {},

	/**
	 * Ініціалізація обробника зміни розміру
	 */
	init() {
		this._handlers.resize = debounce(() => {
			if (window.innerWidth > CONFIG.BREAKPOINTS.MOBILE && MobileMenuModule.isOpen) {
				MobileMenuModule.close();
			}
		}, CONFIG.TIMING.RESIZE_DEBOUNCE);

		window.addEventListener('resize', this._handlers.resize);
	},

	/**
	 * Очистити event listeners
	 */
	destroy() {
		if (this._handlers.resize) {
			window.removeEventListener('resize', this._handlers.resize);
		}
		this._handlers = {};
	},
};

// =====================================================
// MAIN INITIALIZATION
// =====================================================

const MediciApp = {
	_initialized: false,

	/**
	 * Вивести брендинг в консоль з ASCII art
	 */
	logBranding() {
		console.log(
			`%c
	███╗   ███╗███████╗██████╗  ██╗ ██████╗██╗
	████╗ ████║██╔════╝██╔══██╗ ██║██╔════╝██║
	██╔████╔██║█████╗  ██║  ██║ ██║██║     ██║
	██║╚██╔╝██║██╔══╝  ██║  ██║ ██║██║     ██║
	██║ ╚═╝ ██║███████╗██████╔╝ ██║╚██████╗██║
	╚═╝     ╚═╝╚══════╝╚═════╝  ╚═╝ ╚═════╝╚═╝
`,
			'color: #FFD700; font-family: monospace; font-size: 12px; font-weight: bold; text-shadow: 2px 2px 4px #0099FF, 3px 3px 6px rgba(0, 153, 255, 0.5);'
		);

		console.log(
			'%cРозроблено з ❤️ до медицини',
			'color: #FFD700; font-size: 14px; font-weight: bold; margin-top: 10px;'
		);
		console.log(
			'%cMEDICI AGENCY | МЕДИЧНИЙ МАРКЕТИНГ - ЗАКОННО ТА ЕТИЧНО',
			'color: #666; font-size: 12px;'
		);
		console.log('%chttps://medici.agency', 'color: #0099FF; font-size: 12px;');
	},

	/**
	 * Ініціалізація всіх модулів
	 */
	init() {
		if (this._initialized) {
			return;
		}

		try {
			ThemeModule.init();
			MobileMenuModule.init();
			NavigationModule.init();
			SmoothScrollModule.init();
			ActiveLinksModule.init();
			AccessibilityModule.init();
			ResizeHandlerModule.init();
			ScrollToTopModule.init();
			ScrollAnimationsModule.init();

			this._initialized = true;

			// Вивести брендинг з ASCII art в консоль
			this.logBranding();
		} catch (error) {
			handleError(error, 'MediciApp.init');
		}
	},

	/**
	 * Очистити всі event listeners та ресурси
	 * Викликати при видаленні/оновленні компонентів
	 */
	destroy() {
		if (!this._initialized) {
			return;
		}

		try {
			ThemeModule.destroy();
			MobileMenuModule.destroy();
			NavigationModule.destroy();
			SmoothScrollModule.destroy();
			ActiveLinksModule.destroy();
			AccessibilityModule.destroy();
			ResizeHandlerModule.destroy();
			ScrollToTopModule.destroy();
			ScrollAnimationsModule.destroy();

			clearDOMCache();
			this._initialized = false;
		} catch (error) {
			handleError(error, 'MediciApp.destroy');
		}
	},
};

// Запуск додатку
document.addEventListener('DOMContentLoaded', () => MediciApp.init());

// Експорт для можливого використання ззовні
if (typeof window !== 'undefined') {
	window.MediciApp = MediciApp;
}

# STYLE-RULES-GENERATEBLOCKS.md - GenerateBlocks Класи

**Версія:** 5.0.0
**Дата:** 2025-12-02
**Частина:** GenerateBlocks (gbp-_, gb-_)
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

---

## ⚠️ ПОПЕРЕДЖЕННЯ ДЛЯ LLM

**Цей файл містить тільки GenerateBlocks класи (gbp-_, gb-_).**

Якщо потрібні:

- **medici-\*** класи → Читай `STYLE-RULES-THEME.md`
- **Utility classes** → Читай `STYLE-RULES-THEME.md`
- **Загальна інформація** → Читай `STYLE-RULES.md` (Master Index)

---

## 📋 ЗМІСТ

### Категорія A: gbp-\* (GenerateBlocks Pro) - 60+ класів

- [A.1 Sections](#a1-sections)
- [A.2 Inner Containers](#a2-inner-containers)
- [A.3 Typography](#a3-typography-text-classes)
- [A.4 Buttons](#a4-buttons)
- [A.5 Cards](#a5-cards)
- [A.6 Footer](#a6-footer)
- [A.7 Navigation](#a7-navigation)
- [A.8 Hero](#a8-hero)
- [A.9 Borders](#a9-borders)

### Категорія B: gb-\* (GenerateBlocks Core) - 15+ класів

- [B.1 Block Elements](#b1-block-elements)
- [B.2 Query Loop](#b2-query-loop)
- [B.3 Menu Toggle](#b3-menu-toggle)

---

## КАТЕГОРІЯ A: gbp-\* (GenerateBlocks Pro)

### A.1 Sections

```css
/* === БАЗОВІ СЕКЦІЇ === */

.gbp-section {
	padding: 8rem 2rem;
}

@media (max-width: 767px) {
	.gbp-section {
		padding: 6rem 1.5rem;
	}
}

.gbp-section--alt {
	/* Альтернативна секція (інший фон) */
	background-color: var(--base-2);
	position: relative;
}

.gbp-section--accent {
	/* Акцентна секція */
	background-color: var(--accent);
	color: var(--base-3);
}

.gbp-section--dark {
	/* Темна секція */
	background-color: var(--base);
	color: white;
}

.gbp-section--background {
	/* Секція з фоном */
	background-color: var(--contrast);
	color: var(--base-2);
}

/* 🆕 НОВИЙ КЛАС (6 використань у проєкті) */
.gbp-section-header {
	/* Заголовок секції (header контейнер) */
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	margin-bottom: 3rem;
}

@media (max-width: 767px) {
	.gbp-section-header {
		margin-bottom: 2rem;
	}
}
```

**Використання:**

```json
{
	"globalClasses": ["gbp-section"]
}
```

### A.2 Inner Containers

```css
/* === INNER CONTAINERS === */

/* Варіант 1: Старий формат (без BEM) */
.gbp-sectioninner {
	margin-left: auto;
	margin-right: auto;
	max-width: var(--gb-container-width);
	padding-left: 2rem;
	padding-right: 2rem;
}

/* Варіант 2: BEM формат (з подвійним підкресленням) */
.gbp-section__inner {
	margin-left: auto;
	margin-right: auto;
	max-width: var(--gb-container-width);
	padding-left: 2rem;
	padding-right: 2rem;
}

@media (max-width: 767px) {
	.gbp-sectioninner,
	.gbp-section__inner {
		padding-left: 1rem;
		padding-right: 1rem;
	}
}
```

**⚠️ ПРИМІТКА:** Існують дві варіації, обидві валідні та використовуються в проєкті.
**Рекомендація:** Використовуй `.gbp-section__inner` (з `__`) для нового коду!

### A.3 Typography (Text Classes)

```css
/* === TYPOGRAPHY === */

/* Заголовки секцій */

/* Варіант 1: Старий формат */
.gbp-sectionheadline {
	font-weight: 800;
	margin-bottom: 1.4rem;
	font-size: clamp(2rem, 5vw, 4rem);
	line-height: 1.2;
}

/* Варіант 2: BEM формат */
.gbp-section__headline {
	font-weight: 800;
	margin-bottom: 1.4rem;
	font-size: clamp(2rem, 5vw, 4rem);
	line-height: 1.2;
}

@media (max-width: 767px) {
	.gbp-sectionheadline,
	.gbp-section__headline {
		margin-bottom: 1.25rem;
	}
}

/* Теглайни (підзаголовки) */

/* Варіант 1: Старий формат */
.gbp-sectiontagline {
	align-items: flex-start;
	border-left-color: var(--accent-3);
	color: var(--accent);
	display: flex;
	font-family: var(--gp-font--amatic-sc);
	font-size: 1.9rem;
	font-weight: 700;
	letter-spacing: 2px;
	line-height: 1.2em;
	margin-bottom: 1rem;
	text-transform: uppercase;
}

/* Варіант 2: BEM формат */
.gbp-section__tagline {
	align-items: flex-start;
	border-left-color: var(--accent-3);
	color: var(--accent);
	display: flex;
	font-family: var(--gp-font--amatic-sc);
	font-size: 1.9rem;
	font-weight: 700;
	letter-spacing: 2px;
	line-height: 1.2em;
	margin-bottom: 1rem;
	text-transform: uppercase;
}

/* Текст секції */
.gbp-section__text {
	font-size: 1.125rem;
	line-height: 1.6;
	color: var(--text-primary);
	margin-bottom: 1rem;
}
```

**⚠️ ПРИМІТКА:** Існують дві варіації typography класів.
**Рекомендація:** Використовуй варіанти з `__` для нового коду!

### A.4 Buttons

```css
/* === BUTTON CLASSES === */

/* Primary Button */
.gbp-button--primary {
	align-items: center;
	background-color: transparent;
	color: var(--contrast);
	column-gap: 0.4rem;
	display: inline-flex;
	font-size: 0.94rem;
	font-weight: 600;
	justify-content: center;
	text-align: center;
	text-transform: uppercase;
	transition: all 0.3s ease 0s;
	border: 1px dashed var(--contrast);
	border-radius: 9999px;
	padding: 14px 24px;
}

.gbp-button--primary:hover span.gb-shape {
	transform: rotate(0deg);
	transition: transform 0.3s ease 0s;
}

.gbp-button--primary:is(:hover, :focus) {
	background-color: var(--accent);
	color: var(--base-2);
}

.gbp-button--primary span.gb-shape {
	transform: rotate(-45deg);
	transition: all 0.3s ease 0s;
}

/* Secondary Button */
.gbp-button--secondary {
	align-items: center;
	background-color: transparent;
	color: var(--base-2);
	column-gap: 0.4rem;
	display: inline-flex;
	font-size: 0.94rem;
	font-weight: 600;
	justify-content: center;
	text-align: center;
	text-transform: uppercase;
	transition: all 0.3s ease 0s;
	border: 1px dashed var(--base-2);
	border-radius: 9999px;
	padding: 14px 24px;
}

.gbp-button--secondary:hover span.gb-shape {
	transform: rotate(0deg);
	transition: transform 0.3s ease 0s;
}

.gbp-button--secondary:is(:hover, :focus) {
	background-color: var(--accent);
	color: var(--base-2);
}

.gbp-button--secondary span.gb-shape {
	transform: rotate(-45deg);
	transition: all 0.3s ease 0s;
}

/* Tertiary Button */
.gbp-button--tertiary {
	align-items: center;
	background-color: var(--accent);
	color: var(--base-2);
	column-gap: 0.5em;
	display: inline-flex;
	font-size: 0.86rem;
	font-weight: 600;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	border-radius: 3px;
	padding: 16px 26px;
}

.gbp-button--tertiary:is(:hover, :focus) {
	background-color: var(--accent-2);
	color: var(--base-2);
}

.gbp-button--tertiary .gb-shape svg {
	width: 1em;
	height: 1em;
	fill: currentColor;
	font-size: 1.4rem;
	color: var(--base-2);
}

/* 🆕 Tertiary Button - Варіант 2 (НОВИЙ) */
.gbp-button--tertiary-2 {
	align-items: center;
	background-color: var(--contrast);
	color: var(--base-2);
	column-gap: 0.5em;
	display: inline-flex;
	font-size: 0.86rem;
	font-weight: 600;
	justify-content: center;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	border-radius: 3px;
	padding: 16px 26px;
}

.gbp-button--tertiary-2:is(:hover, :focus) {
	background-color: var(--base);
	color: var(--contrast-2);
}

.gbp-button--tertiary-2 .gb-shape svg {
	width: 1em;
	height: 1em;
	fill: currentColor;
	font-size: 1.4rem;
	color: var(--base-2);
}
```

**Доступні варіанти:**

- `gbp-button--primary` - Primary (dashed border, transparent)
- `gbp-button--secondary` - Secondary (dashed border, light)
- `gbp-button--tertiary` - Tertiary (filled, accent)
- `gbp-button--tertiary-2` - Tertiary variant 2 (filled, contrast)

### A.5 Cards

```css
/* === CARD CLASSES === */

/* Базова карточка */
.gbp-card {
	padding: 1.5rem;
	background-color: white;
	border-radius: var(--border-radius-md);
	box-shadow: var(--shadow-sm);
	transition: all 0.3s ease;
}

@media (max-width: 767px) {
	.gbp-card {
		padding: 1.25rem;
	}
}

.gbp-card:hover {
	transform: translateY(-4px);
	box-shadow: var(--shadow-lg);
}

/* 🆕 Карточка з бордером (НОВИЙ) */
.gbp-card--border {
	border-radius: 15px;
	border: 2px solid var(--base-2);
}

/* Card Title */
.gbp-card__title {
	color: var(--base-2);
	font-size: 1.35rem;
	margin-bottom: 0.6rem;
	font-weight: 700;
}

/* Card Meta Text */
.gbp-card__meta-text {
	color: var(--contrast-3);
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	display: inline-flex;
	align-items: center;
	column-gap: 0.5em;
}

/* Card Text */
.gbp-card__text {
	font-size: 0.96rem;
	margin-bottom: 0px;
	line-height: 1.6;
}

/* 🆕 Service Card (НОВИЙ - 9 використань) */
.gbp-service-card {
	padding: 2rem;
	background-color: var(--accent-3);
	border-radius: var(--border-radius-lg);
	transition: all 0.5s ease;
	display: flex;
	flex-direction: column;
	min-height: 300px;
}

.gbp-service-card:hover {
	transform: translate3d(0px, -8px, 0px);
	box-shadow: var(--shadow-xl);
	background-color: var(--accent);
}

@media (max-width: 767px) {
	.gbp-service-card {
		min-height: auto;
	}
}

/* 🆕 Testimonial Card (НОВИЙ - 6 використань) */
.gbp-testimonial-card {
	padding: 2.5rem;
	background-color: var(--base-3);
	color: var(--contrast);
	border-radius: var(--border-radius-md);
	position: relative;
	transition: all 0.4s ease;
}

.gbp-testimonial-card::before {
	content: '"';
	position: absolute;
	top: 1rem;
	left: 1.5rem;
	font-size: 4rem;
	color: var(--accent);
	opacity: 0.3;
	font-family: Georgia, serif;
}

.gbp-testimonial-card:hover {
	background-color: var(--base-2);
	transform: scale(1.02);
}

/* 🆕 Value Card (НОВИЙ - 3 використання) */
.gbp-value-card {
	padding: 2rem;
	background-color: white;
	border: 1px solid var(--contrast-2);
	border-radius: var(--border-radius-sm);
	text-align: center;
	transition: all 0.3s ease;
}

.gbp-value-card:hover {
	border-color: var(--accent);
	box-shadow: var(--shadow-md);
}
```

**Доступні типи карточок:**

- `gbp-card` - Базова карточка (універсальна)
- `gbp-service-card` - Service card (послуги)
- `gbp-testimonial-card` - Testimonial card (відгуки)
- `gbp-value-card` - Value card (цінності)

### A.6 Footer

```css
/* === FOOTER CLASSES === */

/* Базовий footer */
.gbp-footer {
	max-width: var(--gb-container-width);
	margin: 0 auto;
	padding: 0 2rem;
}

/* Footer legal section */
.gbp-footer-legal {
	background-color: var(--base);
	color: var(--contrast);
	padding: 4rem 0 2rem;
}

/* Footer content grid */
.gbp-footer-content {
	display: grid;
	grid-template-columns: 2fr 1fr 1fr;
	gap: 3rem;
	margin-bottom: 3rem;
}

@media (max-width: 1024px) {
	.gbp-footer-content {
		grid-template-columns: 1fr 1fr;
	}
}

@media (max-width: 767px) {
	.gbp-footer-content {
		grid-template-columns: 1fr;
		gap: 2rem;
	}
}

/* Footer company */
.gbp-footer-company {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

/* Footer logo */
.gbp-footer-logo {
	font-size: 1.5rem;
	font-weight: 700;
	color: var(--accent);
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	column-gap: 0.5em;
	margin-bottom: 1rem;
}

/* Footer description */
.gbp-footer-description {
	color: var(--contrast-2);
	line-height: 1.6;
	margin-bottom: 1.5rem;
}

/* Footer social */
.gbp-footer-social {
	display: flex;
	gap: 1rem;
}

/* Social icon */
.gbp-social-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 40px;
	height: 40px;
	background-color: var(--accent);
	color: white;
	border-radius: 50%;
	text-decoration: none;
	transition: var(--transition-base);
}

.gbp-social-icon:hover {
	background-color: var(--accent-2);
	transform: translateY(-3px);
}

/* Footer links section */
.gbp-footer-links {
	display: flex;
	flex-direction: column;
}

/* Footer navigation */
.gbp-footer-nav {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
}

/* Footer link */
.gbp-footer-link {
	color: var(--contrast-2);
	text-decoration: none;
	transition: var(--transition-base);
}

.gbp-footer-link:hover {
	color: var(--accent);
	padding-left: 0.5rem;
}

/* 🆕 НОВІ FOOTER КЛАСИ */

/* Footer badges container */
.gbp-footer-badges {
	display: flex;
	gap: 1rem;
	align-items: center;
	flex-wrap: wrap;
	margin-top: 1rem;
}

/* Footer badge */
.gbp-footer-badge {
	display: inline-flex;
	align-items: center;
	padding: 0.5rem 1rem;
	background-color: var(--base-2);
	border-radius: var(--border-radius-sm);
	font-size: 0.875rem;
	color: var(--contrast-2);
}

/* Footer bottom (copyright area) */
.gbp-footer-bottom {
	border-top: 1px solid var(--base-2);
	padding-top: 2rem;
	margin-top: 2rem;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

@media (max-width: 767px) {
	.gbp-footer-bottom {
		flex-direction: column;
		gap: 1rem;
		text-align: center;
	}
}

/* Footer copyright */
.gbp-footer-copyright {
	color: var(--contrast-3);
	font-size: 0.875rem;
}

/* Footer contacts container */
.gbp-footer-contacts {
	display: flex;
	flex-direction: column;
	gap: 1rem;
}

/* Footer contact item */
.gbp-footer-contact-item {
	display: flex;
	align-items: center;
	gap: 1rem;
}

/* Footer contact icon */
.gbp-footer-contact-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	background-color: var(--accent);
	border-radius: 50%;
	flex-shrink: 0;
}

.gbp-footer-contact-icon .gb-shape svg {
	width: 18px;
	height: 18px;
	fill: white;
}

/* Footer contact link */
.gbp-footer-contact-link {
	color: var(--contrast-2);
	text-decoration: none;
	transition: var(--transition-base);
}

.gbp-footer-contact-link:hover {
	color: var(--accent);
}

/* Footer legal link */
.gbp-footer-legal-link {
	color: var(--contrast-3);
	text-decoration: none;
	font-size: 0.875rem;
	transition: var(--transition-base);
}

.gbp-footer-legal-link:hover {
	color: var(--accent);
}
```

**Footer структура:**

- `gbp-footer` - контейнер footer
- `gbp-footer-legal` - legal секція
- `gbp-footer-content` - grid контент (3 колонки → 2 → 1)
- `gbp-footer-company` - компанія блок
- `gbp-footer-links` - посилання блок
- `gbp-footer-contacts` - контакти блок
- `gbp-footer-bottom` - copyright area

### A.7 Navigation

```css
/* === NAVIGATION CLASSES === */

.gbp-navigation {
	width: 100%;
	background-color: white;
	box-shadow: var(--shadow-sm);
	z-index: var(--z-fixed);
}

.gbp-logo {
	font-size: 1.5rem;
	font-weight: 700;
	color: var(--accent);
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	column-gap: 0.5em;
}

.gbp-nav-center {
	display: flex;
	align-items: center;
	column-gap: 2rem;
}

.gbp-nav-link {
	color: var(--text-primary);
	text-decoration: none;
	font-weight: 500;
	transition: var(--transition-base);
	padding: 0.5rem 1rem;
}

.gbp-nav-link:hover,
.gbp-nav-link:focus {
	color: var(--accent);
}

.gbp-nav-right {
	display: flex;
	align-items: center;
	column-gap: 1rem;
}

.gbp-nav-phone {
	color: var(--text-primary);
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	column-gap: 0.5em;
	font-weight: 500;
}

.gbp-theme-toggle {
	background: transparent;
	border: none;
	font-size: 1.5rem;
	cursor: pointer;
	padding: 0.5rem;
	transition: var(--transition-base);
}

.gbp-theme-toggle:hover {
	transform: scale(1.1);
}

.gbp-cta-button {
	background-color: var(--accent);
	color: white;
	padding: 0.75rem 1.5rem;
	border-radius: var(--border-radius-sm);
	text-decoration: none;
	font-weight: 600;
	transition: var(--transition-base);
}

.gbp-cta-button:hover {
	background-color: var(--accent-2);
	transform: translateY(-2px);
}

.gbp-mobile-toggle {
	display: none;
	cursor: pointer;
	width: 30px;
	height: 30px;
}

@media (max-width: 1024px) {
	.gbp-nav-center,
	.gbp-nav-phone {
		display: none;
	}

	.gbp-mobile-toggle {
		display: block;
	}
}
```

**Navigation структура:**

- `gbp-navigation` - головний контейнер
- `gbp-logo` - логотип
- `gbp-nav-center` - центральні посилання (desktop)
- `gbp-nav-link` - посилання меню
- `gbp-nav-right` - права частина (кнопки)
- `gbp-cta-button` - CTA кнопка
- `gbp-mobile-toggle` - мобільний toggle

### A.8 Hero

```css
/* === HERO CLASSES === */

.gbp-hero {
	min-height: 100vh;
	display: flex;
	align-items: center;
	justify-content: center;
	text-align: center;
	background: linear-gradient(135deg, var(--base) 0%, var(--accent-2) 100%);
	color: white;
	position: relative;
	overflow: hidden;
}

@media (max-width: 767px) {
	.gbp-hero {
		min-height: 80vh;
		padding: 4rem 1rem;
	}
}
```

**Hero використання:**

- Full viewport height (100vh → 80vh на mobile)
- Gradient background
- Centered content

### A.9 Borders

```css
/* === BORDER CLASSES === */

/* 🆕 Загальний клас для бордеру (НОВИЙ) */
.gbp--border {
	border: 3px solid var(--base-2);
}
```

---

## КАТЕГОРІЯ B: gb-\* (GenerateBlocks Core)

### B.1 Block Elements

```css
/* === ELEMENT BLOCK === */

.gb-element {
  /* Базовий element блок */
}

.gb-element-{uniqueId} {
  /* Конкретний element з uniqueId (8 hex chars) */
}

/* === TEXT BLOCK === */

.gb-text {
  /* Базовий text блок */
}

.gb-text-{uniqueId} {
  /* Конкретний text з uniqueId */
}

/* === MEDIA BLOCK === */

.gb-media {
  /* Базовий media блок */
}

.gb-media-{uniqueId} {
  /* Конкретне media з uniqueId */
}

/* === SHAPE BLOCK === */

.gb-shape {
  /* Базовий shape блок (SVG іконки) */
}

.gb-shape-{uniqueId} {
  /* Конкретний shape з uniqueId */
}

.gb-shape svg {
  fill: currentColor;
  width: 1em;
  height: 1em;
}

/* 🆕 Shape Divider (НОВИЙ) */
.gb-shape--divider {
  /* SVG divider (роздільник секцій) */
  width: 100%;
  height: auto;
  display: block;
}
```

**Базові блоки GenerateBlocks:**

- `gb-element` - Container block
- `gb-text` - Text block
- `gb-media` - Image/video block
- `gb-shape` - SVG icon block

**UniqueId format:** 8 lowercase hex characters (e.g., `a1b2c3d4`)

### B.2 Query Loop

```css
/* === QUERY LOOP CLASSES === */

/* 🆕 Query Loop Container (НОВИЙ) */
.gb-query-loop {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 2rem;
}

@media (max-width: 767px) {
	.gb-query-loop {
		grid-template-columns: 1fr;
		gap: 1.5rem;
	}
}

/* 🆕 Query Loop Pagination (НОВИЙ) */
.gb-query-loop-pagination {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 1rem;
	margin-top: 3rem;
}

.gb-query-loop-pagination a,
.gb-query-loop-pagination span {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-width: 40px;
	height: 40px;
	padding: 0.5rem 1rem;
	background-color: var(--base-3);
	color: var(--contrast);
	text-decoration: none;
	border-radius: var(--border-radius-sm);
	transition: var(--transition-base);
}

.gb-query-loop-pagination a:hover {
	background-color: var(--accent);
}

.gb-query-loop-pagination .current {
	background-color: var(--accent);
	font-weight: 700;
}
```

**Query Loop використання:**

- Dynamic post queries
- Auto-fill grid layout
- Responsive breakpoints
- Pagination support

### B.3 Menu Toggle

```css
/* === MENU TOGGLE CLASSES === */

.gb-menu-hide-on-toggled {
	/* Приховувати коли мобільне меню відкрите */
	display: flex;
}

.gb-menu-show-on-toggled {
	/* Показувати коли мобільне меню відкрите */
	display: none;
}

@media (max-width: 1024px) {
	body.menu-toggled .gb-menu-hide-on-toggled {
		display: none;
	}

	body.menu-toggled .gb-menu-show-on-toggled {
		display: flex;
	}
}
```

**Menu Toggle використання:**

- Мобільне меню
- Toggle visibility на основі `body.menu-toggled` class
- Працює тільки на ≤1024px

---

## 📊 СТАТИСТИКА GENERATEBLOCKS КЛАСІВ

**Категорія A (gbp-\*):** 60+ класів

- Sections: 5 варіацій
- Containers: 2 BEM варіації
- Typography: 6 варіацій
- Buttons: 4 типи
- Cards: 7 типів (включно з service, testimonial, value)
- Footer: 20+ класів
- Navigation: 9 класів
- Hero: 1 клас
- Borders: 1 клас

**Категорія B (gb-\*):** 15+ класів

- Block Elements: 8 (element, text, media, shape + uniqueId варіанти)
- Query Loop: 2 (container + pagination)
- Menu Toggle: 2 (hide/show)
- Shape Divider: 1

**ЗАГАЛЬНА КІЛЬКІСТЬ:** 75+ GenerateBlocks класів

---

## 🔗 ЗВ'ЯЗОК З ІНШИМИ ФАЙЛАМИ

Цей файл є частиною STYLE-RULES документації:

- `STYLE-RULES.md` - Master Index (завжди читай першим!)
- `STYLE-RULES-THEME.md` - medici-\* та utilities класи
- `CODING-RULES.md` - правила кодування GenerateBlocks
- `Skill.md` - документація GenerateBlocks 2.x API

**ВАЖЛИВО:** Завжди використовуйте STYLE-RULES разом з CODING-RULES при створенні блоків!

---

**END OF GENERATEBLOCKS CLASSES**

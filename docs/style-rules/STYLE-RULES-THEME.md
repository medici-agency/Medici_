# STYLE-RULES-THEME.md - Власні Класи Теми

**Версія:** 5.0.0
**Дата:** 2025-12-02
**Частина:** Theme Custom Classes (medici-\*, utilities)
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

---

## ⚠️ ПОПЕРЕДЖЕННЯ ДЛЯ LLM

**Цей файл містить тільки власні класи теми (medici-\*, utilities).**

Якщо потрібні:

- **gbp-\*** класи → Читай `STYLE-RULES-GENERATEBLOCKS.md`
- **gb-\*** класи → Читай `STYLE-RULES-GENERATEBLOCKS.md`
- **Загальна інформація** → Читай `STYLE-RULES.md` (Master Index)

---

## 📋 ЗМІСТ

### Категорія C: medici-\* (Кастомні класи теми) - 30+ класів

- [C.1 Blog](#c1-blog)
- [C.2 Card Components](#c2-card-components)
- [C.3 Featured Post](#c3-featured-post)
- [C.4 Post Meta](#c4-post-meta)
- [C.5 Sections](#c5-sections)

### Категорія D: Utility Classes - 50+ класів

- [D.1 Display](#d1-display)
- [D.2 Flexbox](#d2-flexbox)
- [D.3 Spacing](#d3-spacing)
- [D.4 Text](#d4-text)
- [D.5 Behavior](#d5-behavior)

### Додатково

- [CSS Стилі з XML Експортів](#css-стилі-з-xml-експортів)

---

## КАТЕГОРІЯ C: medici-\* (Кастомні класи теми)

### C.1 Blog

```css
/* === BLOG CLASSES === */

/* 🆕 Blog Grid (НОВИЙ) */
.medici-blog-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 2rem;
	margin-bottom: 3rem;
}

@media (max-width: 1024px) {
	.medici-blog-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 767px) {
	.medici-blog-grid {
		grid-template-columns: 1fr;
		gap: 1.5rem;
	}
}

/* 🆕 Blog Card (НОВИЙ) */
.medici-blog-card {
	background-color: white;
	border-radius: var(--border-radius-md);
	overflow: hidden;
	box-shadow: var(--shadow-sm);
	transition: all 0.3s ease;
	display: flex;
	flex-direction: column;
}

.medici-blog-card:hover {
	transform: translateY(-4px);
	box-shadow: var(--shadow-lg);
}
```

**Використання Blog Grid:**

- Desktop: 3 колонки
- Tablet (≤1024px): 2 колонки
- Mobile (≤767px): 1 колонка

### C.2 Card Components

```css
/* === CARD COMPONENTS === */

/* 🆕 Card Image Wrapper (НОВИЙ) */
.medici-card-image-wrapper {
	position: relative;
	width: 100%;
	padding-top: 56.25%; /* 16:9 Aspect Ratio */
	overflow: hidden;
	background-color: var(--base-3);
}

.medici-card-image-wrapper img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

/* 🆕 Card Content (НОВИЙ) */
.medici-card-content {
	padding: 1.5rem;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
}

/* 🆕 Card Category (НОВИЙ) */
.medici-card-category {
	display: inline-block;
	padding: 0.25rem 0.75rem;
	background-color: var(--accent);
	color: white;
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	border-radius: var(--border-radius-sm);
	margin-bottom: 0.75rem;
}

/* 🆕 Card Title (НОВИЙ) */
.medici-card-title {
	font-size: 1.25rem;
	font-weight: 700;
	color: var(--base-3);
	margin-bottom: 0.75rem;
	line-height: 1.3;
}

.medici-card-title a {
	color: inherit;
	text-decoration: none;
	transition: var(--transition-base);
}

.medici-card-title a:hover {
	color: var(--accent);
}

/* 🆕 Card Excerpt (НОВИЙ) */
.medici-card-excerpt {
	font-size: 0.95rem;
	color: var(--text-secondary);
	line-height: 1.6;
	margin-bottom: 1rem;
	flex-grow: 1;
}

/* 🆕 Card Meta (НОВИЙ) */
.medici-card-meta {
	display: flex;
	align-items: center;
	gap: 1rem;
	font-size: 0.875rem;
	color: var(--text-secondary);
}

/* 🆕 Card Footer (НОВИЙ) */
.medici-card-footer {
	border-top: 1px solid var(--contrast-2);
	padding-top: 1rem;
	margin-top: auto;
}
```

**Card Components структура:**

- `medici-card-image-wrapper` - 16:9 aspect ratio image
- `medici-card-content` - padded content area
- `medici-card-category` - badge стиль
- `medici-card-title` - heading з hover
- `medici-card-excerpt` - flex-grow для вирівнювання
- `medici-card-meta` - metadata (дата, автор, тощо)
- `medici-card-footer` - footer з border-top

### C.3 Featured Post

```css
/* === FEATURED POST CLASSES === */

/* 🆕 Featured Card (НОВИЙ) */
.medici-featured-card {
	display: grid;
	grid-template-columns: 1.5fr 1fr;
	gap: 2rem;
	background-color: var(--base-3);
	border-radius: var(--border-radius-lg);
	overflow: hidden;
	margin-bottom: 3rem;
	box-shadow: var(--shadow-lg);
}

@media (max-width: 1024px) {
	.medici-featured-card {
		grid-template-columns: 1fr;
	}
}

/* 🆕 Featured Image (НОВИЙ) */
.medici-featured-image {
	position: relative;
	width: 100%;
	height: 100%;
	min-height: 400px;
	overflow: hidden;
}

.medici-featured-image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
}

@media (max-width: 1024px) {
	.medici-featured-image {
		min-height: 300px;
	}
}

/* 🆕 Featured Content (НОВИЙ) */
.medici-featured-content {
	padding: 3rem;
	display: flex;
	flex-direction: column;
	justify-content: center;
	color: white;
}

@media (max-width: 767px) {
	.medici-featured-content {
		padding: 2rem;
	}
}

/* 🆕 Featured Badge (НОВИЙ) */
.medici-featured-badge {
	display: inline-block;
	padding: 0.5rem 1rem;
	background-color: var(--accent);
	color: white;
	font-size: 0.75rem;
	font-weight: 700;
	text-transform: uppercase;
	border-radius: var(--border-radius-sm);
	margin-bottom: 1rem;
	letter-spacing: 0.05em;
}

/* 🆕 Featured Category (НОВИЙ) */
.medici-featured-category {
	display: inline-block;
	color: var(--accent);
	font-size: 0.875rem;
	font-weight: 600;
	text-transform: uppercase;
	margin-bottom: 0.75rem;
}

/* 🆕 Featured Title (НОВИЙ) */
.medici-featured-title {
	font-size: 2.5rem;
	font-weight: 800;
	color: white;
	margin-bottom: 1rem;
	line-height: 1.2;
}

@media (max-width: 767px) {
	.medici-featured-title {
		font-size: 1.75rem;
	}
}

/* 🆕 Featured Excerpt (НОВИЙ) */
.medici-featured-excerpt {
	font-size: 1.125rem;
	color: var(--contrast-2);
	line-height: 1.6;
	margin-bottom: 1.5rem;
}

/* 🆕 Featured Meta (НОВИЙ) */
.medici-featured-meta {
	display: flex;
	align-items: center;
	gap: 1.5rem;
	margin-bottom: 1.5rem;
	font-size: 0.875rem;
	color: var(--contrast-3);
}

/* 🆕 Featured Reading (НОВИЙ) */
.medici-featured-reading {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
}

/* 🆕 Featured Views (НОВИЙ) */
.medici-featured-views {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
}

/* 🆕 Featured Footer (НОВИЙ) */
.medici-featured-footer {
	margin-top: auto;
	padding-top: 1.5rem;
	border-top: 1px solid rgba(255, 255, 255, 0.2);
}

/* 🆕 Featured Button (НОВИЙ) */
.medici-featured-button {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.875rem 1.5rem;
	background-color: var(--accent);
	color: white;
	font-weight: 600;
	text-decoration: none;
	border-radius: var(--border-radius-sm);
	transition: var(--transition-base);
}

.medici-featured-button:hover {
	background-color: var(--accent-2);
	transform: translateX(4px);
}
```

**Featured Post структура:**

- Grid layout: 1.5fr (image) + 1fr (content) → 1fr на tablet
- Large title (2.5rem → 1.75rem на mobile)
- Dark background з white text
- Metadata: reading time, views
- CTA button з slide-right hover

### C.4 Post Meta

```css
/* === POST META CLASSES === */

/* 🆕 Reading Time (НОВИЙ) */
.medici-reading-time {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.875rem;
	color: var(--text-secondary);
}

.medici-reading-time::before {
	content: '📖';
	font-size: 1rem;
}

/* 🆕 Post Views (НОВИЙ) */
.medici-post-views {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.875rem;
	color: var(--text-secondary);
}

.medici-post-views::before {
	content: '👁️';
	font-size: 1rem;
}
```

**Post Meta Features:**

- Emoji prefixes (📖 для reading time, 👁️ для views)
- Inline-flex з gap для spacing
- Responsive font sizing

### C.5 Sections

```css
/* === MEDICI SECTION CLASSES === */

/* 🆕 Container (НОВИЙ) */
.medici-container {
	max-width: var(--gb-container-width);
	margin-left: auto;
	margin-right: auto;
	padding-left: 2rem;
	padding-right: 2rem;
}

@media (max-width: 767px) {
	.medici-container {
		padding-left: 1rem;
		padding-right: 1rem;
	}
}

/* 🆕 Section Header (НОВИЙ) */
.medici-section-header {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	margin-bottom: 3rem;
	max-width: 800px;
	margin-left: auto;
	margin-right: auto;
}

/* 🆕 Section Badge (НОВИЙ) */
.medici-section-badge {
	display: inline-block;
	padding: 0.5rem 1rem;
	background-color: var(--accent-3);
	color: var(--base-3);
	font-size: 0.875rem;
	font-weight: 600;
	text-transform: uppercase;
	border-radius: 999px;
	margin-bottom: 1rem;
	letter-spacing: 0.05em;
}

/* 🆕 Featured Section (НОВИЙ) */
.medici-featured-section {
	background: linear-gradient(135deg, var(--base-3) 0%, var(--base-2) 100%);
	padding: 4rem 0;
	position: relative;
}

@media (max-width: 767px) {
	.medici-featured-section {
		padding: 3rem 0;
	}
}
```

**Section Classes:**

- `medici-container` - альтернатива до gbp-section\_\_inner
- `medici-section-header` - центрований header з max-width 800px
- `medici-section-badge` - pill-shape badge
- `medici-featured-section` - gradient background section

---

## КАТЕГОРІЯ D: Utility Classes

### D.1 Display

```css
/* === DISPLAY UTILITIES === */

.d-flex {
	display: flex;
}
.d-inline-flex {
	display: inline-flex;
}
.d-grid {
	display: grid;
}
.d-block {
	display: block;
}
.d-inline-block {
	display: inline-block;
}
.d-none {
	display: none;
}
```

**Display utilities:**

- 6 основних display значень
- Bootstrap-like naming
- Immediate display control

### D.2 Flexbox

```css
/* === FLEXBOX UTILITIES === */

.flex-row {
	flex-direction: row;
}
.flex-column {
	flex-direction: column;
}
.flex-wrap {
	flex-wrap: wrap;
}

.align-items-start {
	align-items: flex-start;
}
.align-items-center {
	align-items: center;
}
.align-items-end {
	align-items: flex-end;
}
.align-items-stretch {
	align-items: stretch;
}

.justify-content-start {
	justify-content: flex-start;
}
.justify-content-center {
	justify-content: center;
}
.justify-content-end {
	justify-content: flex-end;
}
.justify-content-between {
	justify-content: space-between;
}
.justify-content-around {
	justify-content: space-around;
}
```

**Flexbox utilities:**

- Direction: row, column, wrap
- Align items: start, center, end, stretch
- Justify content: start, center, end, between, around
- Total: 11 flexbox utilities

### D.3 Spacing

```css
/* === SPACING UTILITIES === */

/* Margin */
.m-0 {
	margin: 0;
}
.m-1 {
	margin: 0.5rem;
}
.m-2 {
	margin: 1rem;
}
.m-3 {
	margin: 1.5rem;
}
.m-4 {
	margin: 2rem;
}

.mt-0 {
	margin-top: 0;
}
.mt-1 {
	margin-top: 0.5rem;
}
.mt-2 {
	margin-top: 1rem;
}
.mt-3 {
	margin-top: 1.5rem;
}
.mt-4 {
	margin-top: 2rem;
}

.mb-0 {
	margin-bottom: 0;
}
.mb-1 {
	margin-bottom: 0.5rem;
}
.mb-2 {
	margin-bottom: 1rem;
}
.mb-3 {
	margin-bottom: 1.5rem;
}
.mb-4 {
	margin-bottom: 2rem;
}

/* Padding */
.p-0 {
	padding: 0;
}
.p-1 {
	padding: 0.5rem;
}
.p-2 {
	padding: 1rem;
}
.p-3 {
	padding: 1.5rem;
}
.p-4 {
	padding: 2rem;
}
```

**Spacing scale:**

- 0: 0rem (reset)
- 1: 0.5rem (8px @ 16px base)
- 2: 1rem (16px @ 16px base)
- 3: 1.5rem (24px @ 16px base)
- 4: 2rem (32px @ 16px base)

**Available classes:**

- All margins: `m-{0-4}`
- Margin top: `mt-{0-4}`
- Margin bottom: `mb-{0-4}`
- All padding: `p-{0-4}`
- Total: 15+ spacing utilities

### D.4 Text

```css
/* === TEXT UTILITIES === */

.text-left {
	text-align: left;
}
.text-center {
	text-align: center;
}
.text-right {
	text-align: right;
}

.font-weight-normal {
	font-weight: 400;
}
.font-weight-medium {
	font-weight: 500;
}
.font-weight-semibold {
	font-weight: 600;
}
.font-weight-bold {
	font-weight: 700;
}

.text-uppercase {
	text-transform: uppercase;
}
.text-lowercase {
	text-transform: lowercase;
}
.text-capitalize {
	text-transform: capitalize;
}
```

**Text utilities:**

- Alignment: left, center, right
- Font weight: 400, 500, 600, 700
- Text transform: uppercase, lowercase, capitalize
- Total: 10+ text utilities

### D.5 Behavior

```css
/* === BEHAVIOR UTILITIES === */

/* 🆕 Smooth Scroll (НОВИЙ) */
.smooth-scroll {
	scroll-behavior: smooth;
}

html.smooth-scroll {
	scroll-behavior: smooth;
}
```

**Smooth Scroll:**

- Застосовується до елемента або `<html>`
- Smooth scrolling для anchor links
- Browser native behavior

---

## CSS СТИЛІ З XML ЕКСПОРТІВ

### Повний список витягнутих CSS стилів:

```css
/* З meta_value gb_style_css */

.gbp--border {
	border: 3px solid var(--base-2);
}

.gbp-section {
	padding: 8rem 2rem;
}

@media (max-width: 767px) {
	.gbp-section {
		padding: 6rem 1.5rem;
	}
}

.gbp-section--background {
	background-color: var(--contrast);
	color: var(--base-2);
}

.gbp-section__inner {
	margin-left: auto;
	margin-right: auto;
	max-width: var(--gb-container-width);
}

.gbp-section__headline {
	font-weight: 800;
	margin-bottom: 1.4rem;
}

@media (max-width: 767px) {
	.gbp-section__headline {
		margin-bottom: 1.25rem;
	}
}

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

.gbp-section__text {
	font-size: 1.125rem;
}

.gbp-card {
	padding: 1.5rem;
}

@media (max-width: 767px) {
	.gbp-card {
		padding: 1.25rem;
	}
}

.gbp-card--border {
	border-radius: 15px;
}

.gbp-card__title {
	color: var(--base-2);
	font-size: 1.35rem;
	margin-bottom: 0.6rem;
}

.gbp-card__meta-text {
	color: var(--contrast-3);
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
}

.gbp-card__text {
	font-size: 0.96rem;
	margin-bottom: 0px;
}

/* Кнопки з анімацією */
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

**Примітка:** CSS стилі з XML експортів містять дублікати класів з GENERATEBLOCKS файлу. Вони включені для повноти інформації та перехресної перевірки.

---

## 📊 СТАТИСТИКА THEME КЛАСІВ

**Категорія C (medici-\*):** 30+ класів (ВСІ НОВІ!)

- Blog: 2 класи (grid, card)
- Card Components: 7 класів (image, content, category, title, excerpt, meta, footer)
- Featured Post: 12 класів (card, image, content, badge, category, title, excerpt, meta, reading, views, footer, button)
- Post Meta: 2 класи (reading-time, post-views)
- Sections: 4 класи (container, section-header, section-badge, featured-section)

**Категорія D (Utilities):** 50+ класів

- Display: 6 класів (flex, inline-flex, grid, block, inline-block, none)
- Flexbox: 11 класів (direction, align, justify)
- Spacing: 15+ класів (margin, padding, з префіксами)
- Text: 10+ класів (align, weight, transform)
- Behavior: 1 клас (smooth-scroll)

**ЗАГАЛЬНА КІЛЬКІСТЬ:** 80+ Theme класів

---

## 🔗 ЗВ'ЯЗОК З ІНШИМИ ФАЙЛАМИ

Цей файл є частиною STYLE-RULES документації:

- `STYLE-RULES.md` - Master Index (завжди читай першим!)
- `STYLE-RULES-GENERATEBLOCKS.md` - gbp-_ та gb-_ класи
- `CODING-RULES.md` - правила кодування GenerateBlocks
- `CLAUDE.md` - загальна архітектура теми

**ВАЖЛИВО:** Utility classes можна комбінувати з GenerateBlocks класами для швидкого прототипування!

---

**END OF THEME CLASSES**

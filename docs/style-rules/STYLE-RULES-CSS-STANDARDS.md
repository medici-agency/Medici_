# STYLE-RULES-CSS-STANDARDS.md - CSS Coding Standards

**Версія:** 5.1.0
**Дата:** 2025-12-02
**Частина:** CSS Coding Standards (WordPress)
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

---

## ⚠️ ПОПЕРЕДЖЕННЯ ДЛЯ LLM

**Цей файл містить тільки правила написання CSS коду (форматування, архітектура, best practices).**

Якщо потрібні:

- **CSS класи (gbp-\*)** → Читай `STYLE-RULES-GENERATEBLOCKS.md`
- **CSS класи (medici-\*)** → Читай `STYLE-RULES-THEME.md`
- **Загальна інформація** → Читай `STYLE-RULES.md` (Master Index)

---

## 📋 ЗМІСТ

1. [Офіційні WordPress CSS Coding Standards](#офіційні-wordpress-css-coding-standards)
2. [Структура та форматування](#структура-та-форматування)
3. [Іменування селекторів](#іменування-селекторів)
4. [Властивості та значення](#властивості-та-значення)
5. [Порядок властивостей](#порядок-властивостей)
6. [CSS-архітектурні методології](#css-архітектурні-методології)
7. [CSS Custom Properties (Variables)](#css-custom-properties-variables)
8. [CSS Cascade Layers (@layer)](#css-cascade-layers-layer)
9. [Specificity та !important](#specificity-та-important)
10. [Performance Optimization](#performance-optimization)
11. [Коментування CSS](#коментування-css)
12. [Linting та автоматизація](#linting-та-автоматизація)
13. [Рекомендований стек для GeneratePress](#рекомендований-стек-для-generatepress)

---

## Офіційні WordPress CSS Coding Standards

WordPress має чіткі стандарти форматування CSS, які слід дотримуватися для всіх проектів від ядра до тем і плагінів.

**Джерело:** [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

---

## Структура та форматування

### Індентація

**Використовуйте tabs, не spaces для відступів властивостей.**

```css
/* ✅ Правильно - tabs для indent */
.selector {
	background: #fff;
	color: #000;
	padding: 1rem;
}

/* ❌ Неправильно - spaces для indent */
.selector {
	background: #fff;
	color: #000;
}
```

### Селектори

**Кожен селектор на окремому рядку, закінчуючи комою або відкриваючою фігурною дужкою:**

```css
/* ✅ Правильно */
#selector-1,
#selector-2,
#selector-3 {
	background: #fff;
	color: #000;
}

/* ❌ Неправильно - на одному рядку */
#selector-1,
#selector-2,
#selector-3 {
	background: #fff;
	color: #000;
}

/* ❌ Неправильно - inline стилі */
#selector-1 {
	background: #fff;
	color: #000;
}
```

### Пробіли між секціями

**Два порожні рядки між секціями, один — між блоками в секції.**

```css
/* Section 1 */
.selector-1 {
	margin: 0;
}

.selector-2 {
	padding: 0;
}

/* Section 2 */
.another-selector {
	display: block;
}
```

---

## Іменування селекторів

WordPress рекомендує **lowercase з дефісами (hyphen-case)**:

```css
/* ✅ Правильно - lowercase + hyphens */
#comment-form {
	margin: 1em 0;
}

input[type='text'] {
	line-height: 1.1;
}

.site-header {
	background: #fff;
}

/* ❌ Неправильно - camelCase */
#commentForm {
}
.siteHeader {
}

/* ❌ Неправильно - underscores */
#comment_form {
}
.site_header {
}

/* ❌ Неправильно - over-qualification */
div#comment_form {
}
div.site-header {
}

/* ❌ Неправильно - незрозумілі назви */
#c1-xr {
}
.cls-1 {
}
```

**Рекомендації для іменування:**

- Використовуйте описові назви (`button-primary` замість `btn-p`)
- Уникайте абревіатур крім загальноприйнятих (`nav`, `btn`, `img`)
- Використовуйте BEM для компонентів (`.block__element--modifier`)

---

## Властивості та значення

### Правила форматування

| Правило                 | Приклад                          | Опис                    |
| ----------------------- | -------------------------------- | ----------------------- |
| Пробіл після двокрапки  | `color: #000;`                   | Завжди один пробіл      |
| Lowercase значення      | `display: block;`                | Не `Display: BLOCK;`    |
| Hex кольори скорочені   | `#fff` замість `#FFFFFF`         | Якщо можливо            |
| Font weights числові    | `700` замість `bold`             | 400, 500, 600, 700, 800 |
| 0 без одиниць           | `margin: 0;`                     | Не `margin: 0px;`       |
| Line-height без одиниць | `line-height: 1.4;`              | Відносні значення       |
| Лідируючий нуль         | `opacity: 0.5;`                  | Не `opacity: .5;`       |
| Подвійні лапки          | `font-family: "Helvetica Neue";` | Не одинарні             |

### Приклади правильного форматування

```css
/* ✅ Правильно */
.sample-output {
	background-image: url(images/bg.png);
	font-family: 'Helvetica Neue', sans-serif;
	font-weight: 700;
	line-height: 1.4;
	opacity: 0.5;
	margin: 0;
	text-shadow:
		0 -1px 0 rgba(0, 0, 0, 0.5),
		0 1px 0 #fff;
}

/* ❌ Неправильно */
.sample-output {
	background: #ffffff; /* Не uppercase, не скорочено */
	font-weight: bold; /* Не числове */
	line-height: 1.4em; /* З одиницями */
	margin: 0px 0px 20px 0px; /* З px для нуля */
	opacity: 0.5; /* Без лідируючого нуля */
}
```

### Багаторядкові значення

```css
/* ✅ Правильно - кожне значення на новому рядку з indent */
.sample-output {
	box-shadow:
		0 -1px 0 rgba(0, 0, 0, 0.5),
		0 1px 0 #fff,
		0 2px 4px rgba(0, 0, 0, 0.1);
}

/* ✅ Правильно - градієнти */
.gradient {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

---

## Порядок властивостей

WordPress Core використовує **логічне групування** властивостей:

### 1. Display & Visibility

```css
.element {
	display: block;
	visibility: visible;
}
```

### 2. Positioning

```css
.element {
	position: absolute;
	z-index: 1;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
}
```

### 3. Box Model

```css
.element {
	margin: 0;
	padding: 1rem;
	width: 100%;
	height: auto;
	border: 1px solid #ddd;
	border-radius: 0.5rem;
}
```

### 4. Colors & Typography

```css
.element {
	background: #fff;
	color: #333;
	font-family: sans-serif;
	font-size: 1rem;
	font-weight: 400;
	line-height: 1.6;
}
```

### 5. Other (Animations, Transforms, etc.)

```css
.element {
	transition: all 0.3s ease;
	transform: translateY(-4px);
	animation: fadeIn 0.5s ease;
	cursor: pointer;
}
```

### Повний приклад

```css
#overlay {
	/* Display */
	display: flex;
	visibility: visible;

	/* Positioning */
	position: absolute;
	z-index: 999;
	top: 0;
	left: 0;

	/* Box Model */
	margin: 0;
	padding: 10px;
	width: 100%;
	height: 100%;
	border: 1px solid #eee;

	/* Colors & Typography */
	background: #fff;
	color: #777;
	font-size: 1rem;

	/* Other */
	transition: opacity 0.3s ease;
	cursor: pointer;
}
```

### Альтернатива: Alphabetical ordering

Команда **Automattic/WordPress.com** використовує алфавітний порядок:

```css
.element {
	background: #fff;
	border: 1px solid #ddd;
	color: #333;
	display: block;
	font-size: 1rem;
	margin: 0;
	padding: 1rem;
	position: relative;
	width: 100%;
	z-index: 1;
}
```

**Рекомендація:** Оберіть один підхід для всього проекту та дотримуйтесь його консистентно.

---

## CSS-архітектурні методології

Для масштабованих проектів використовуйте одну з перевірених методологій.

### BEM (Block Element Modifier)

**Найпопулярніша методологія для великих проектів.**

#### Структура іменування

```
.block__element--modifier
```

- **Block** — самостійний компонент (`.card`)
- **Element** — частина блоку (`.card__title`)
- **Modifier** — варіація блоку (`.card--highlighted`)

#### Приклад BEM

```css
/* Block */
.card {
	border: 1px solid #ddd;
	background-color: #fff;
	padding: 1rem;
	border-radius: 0.25rem;
}

/* Modifier */
.card--highlighted {
	border-color: var(--primary-color);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Element */
.card__header {
	border-bottom: 1px solid #eee;
	margin-bottom: 0.75rem;
	padding-bottom: 0.75rem;
}

.card__title {
	font-size: 1.5rem;
	color: #333;
	margin: 0;
}

.card__body {
	font-size: 1rem;
	color: #555;
	line-height: 1.6;
}

/* Element Modifier */
.card__title--large {
	font-size: 2rem;
}
```

#### HTML приклад

```html
<div class="card card--highlighted">
	<div class="card__header">
		<h2 class="card__title card__title--large">BEM Card</h2>
	</div>
	<div class="card__body">
		<p>Block, Element, Modifier naming convention.</p>
	</div>
</div>
```

#### Переваги BEM

✅ **Модульність та перевикористання**
✅ **Чітка структура для team collaboration**
✅ **Уникнення specificity конфліктів**
✅ **Самодокументований код**
✅ **Легко знайти пов'язані стилі**

---

### ITCSS (Inverted Triangle CSS)

**Архітектура шарів від generic до specific.**

#### 7 шарів ITCSS

```
1. Settings   — CSS-змінні
2. Tools      — Mixins, functions (Sass)
3. Generic    — Reset, Normalize
4. Elements   — Base HTML styles
5. Objects    — Layout patterns (o- prefix)
6. Components — UI components (c- prefix)
7. Utilities  — Helper classes (u- prefix)
```

#### Приклад ITCSS структури

```css
/* 1. Settings — CSS-змінні */
:root {
	--primary-color: #4f46e5;
	--font-body: 'Inter', sans-serif;
	--spacing-unit: 0.5rem;
}

/* 2. Tools — не застосовується в чистому CSS (Sass only) */

/* 3. Generic — Reset/Normalize */
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}

/* 4. Elements — Base HTML styles */
body {
	font-family: var(--font-body);
	line-height: 1.6;
	color: #333;
}

h1,
h2,
h3 {
	line-height: 1.2;
}

a {
	color: var(--primary-color);
	text-decoration: none;
}

/* 5. Objects — Layout patterns */
.o-container {
	max-width: 1200px;
	margin-inline: auto;
	padding-inline: 1rem;
}

.o-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 2rem;
}

/* 6. Components — UI components */
.c-card {
	background-color: #fff;
	border-radius: 0.5rem;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
	padding: 1.5rem;
}

.c-button {
	padding: 0.75rem 1.5rem;
	background-color: var(--primary-color);
	color: white;
	border: none;
	border-radius: 0.25rem;
	cursor: pointer;
}

/* 7. Utilities — Helper classes */
.u-text-center {
	text-align: center;
}

.u-margin-top-lg {
	margin-top: 2rem;
}

.u-visually-hidden {
	position: absolute;
	width: 1px;
	height: 1px;
	overflow: hidden;
	clip: rect(0, 0, 0, 0);
}
```

#### ITCSS Prefixes

- `o-` — Objects (layout patterns)
- `c-` — Components (UI blocks)
- `u-` — Utilities (helper classes)
- `t-` — Themes (optional)
- `s-` — Scopes (optional, for user-generated content)

---

### SMACSS (Scalable and Modular Architecture)

**Категоризація за типом правил.**

#### 5 категорій SMACSS

1. **Base** — Default HTML element styles
2. **Layout** — Page structure (l- prefix)
3. **Module** — Reusable components
4. **State** — Dynamic states (is- prefix)
5. **Theme** — Visual variations

#### Приклад SMACSS

```css
/* 1. Base — Default HTML element styles */
body,
h1,
p {
	margin: 0;
	padding: 0;
}

body {
	font-family: sans-serif;
	line-height: 1.6;
}

/* 2. Layout — Page structure */
.l-header {
	background: #f5f5f5;
	padding: 1rem;
	position: sticky;
	top: 0;
	z-index: 100;
}

.l-sidebar {
	width: 250px;
	float: left;
}

.l-main {
	margin-left: 270px;
}

/* 3. Module — Reusable components */
.btn {
	padding: 0.5rem 1rem;
	background-color: #007bff;
	color: white;
	border: none;
	border-radius: 0.25rem;
	cursor: pointer;
}

.card {
	background: white;
	border: 1px solid #ddd;
	padding: 1rem;
	border-radius: 0.5rem;
}

/* 4. State — Dynamic states */
.is-hidden {
	display: none;
}

.is-active {
	background-color: #0056b3;
}

.is-loading {
	opacity: 0.5;
	pointer-events: none;
}

/* 5. Theme — Visual variations */
.theme-dark .btn {
	background-color: #333;
	color: #fff;
}

.theme-dark .card {
	background-color: #222;
	border-color: #444;
	color: #fff;
}
```

---

## CSS Custom Properties (Variables)

**CSS-змінні — обов'язковий інструмент для сучасних WordPress тем.**

### Базове використання

```css
:root {
	/* Colors */
	--color-primary: #667eea;
	--color-secondary: #764ba2;
	--color-text: #333;
	--color-text-muted: #666;
	--color-background: #fff;
	--color-border: #e5e5e5;

	/* Typography */
	--font-family-base: 'Inter', sans-serif;
	--font-family-heading: 'Montserrat', sans-serif;
	--font-size-base: 1rem;
	--font-size-lg: 1.25rem;
	--font-size-xl: 1.5rem;
	--line-height-base: 1.6;
	--line-height-heading: 1.2;

	/* Spacing */
	--spacing-xs: 0.25rem;
	--spacing-sm: 0.5rem;
	--spacing-md: 1rem;
	--spacing-lg: 2rem;
	--spacing-xl: 4rem;

	/* Borders */
	--border-radius-sm: 0.25rem;
	--border-radius-md: 0.5rem;
	--border-radius-lg: 1rem;
	--border-width: 1px;
	--border-color: var(--color-border);

	/* Shadows */
	--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
	--shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
	--shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
	--shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.15);

	/* Transitions */
	--transition-base: all 0.3s ease;
	--transition-fast: all 0.15s ease;
	--transition-slow: all 0.5s ease;
}

/* Використання */
.card {
	padding: var(--spacing-md);
	border-radius: var(--border-radius-md);
	box-shadow: var(--shadow-md);
	color: var(--color-text);
	background: var(--color-background);
	transition: var(--transition-base);
}

/* Fallback значення */
.element {
	color: var(--color-accent, #007bff);
	font-size: var(--font-size-custom, 1rem);
}
```

### Scoped Variables

```css
/* Component-level variables */
.hero {
	--hero-padding: var(--spacing-xl);
	--hero-bg: var(--color-primary);
	--hero-text-color: white;

	padding: var(--hero-padding);
	background: var(--hero-bg);
	color: var(--hero-text-color);
}

/* Override в контексті */
.hero--compact {
	--hero-padding: var(--spacing-lg);
}

.hero--dark {
	--hero-bg: var(--color-secondary);
}
```

### CSS Variables vs Sass Variables

| Аспект              | CSS Variables   | Sass Variables     |
| ------------------- | --------------- | ------------------ |
| Час компіляції      | Runtime         | Compile-time       |
| Зміна через JS      | ✅ Так          | ❌ Ні              |
| Cascade/Inheritance | ✅ Так          | ❌ Ні              |
| Theming             | Dynamic         | Static             |
| Browser Support     | Modern browsers | Всі (компілюється) |
| Обчислення          | Обмежене        | Повне (math)       |

**Рекомендація:** Використовуйте Sass для математичних операцій та mixins, CSS Variables для runtime theming та динамічних змін.

### Dynamic Theming з CSS Variables

```css
/* Light theme (default) */
:root {
	--bg-primary: #ffffff;
	--text-primary: #333333;
	--border-color: #e5e5e5;
}

/* Dark theme */
[data-theme='dark'] {
	--bg-primary: #1a1a1a;
	--text-primary: #ffffff;
	--border-color: #444444;
}

/* Автоматичне застосування */
body {
	background: var(--bg-primary);
	color: var(--text-primary);
}

.card {
	border: 1px solid var(--border-color);
}
```

---

## CSS Cascade Layers (@layer)

**Cascade Layers — новий стандарт для контролю специфічності.**

### Базове використання

```css
/* Оголошення порядку шарів */
@layer reset, base, components, utilities;

/* Reset layer — найнижчий пріоритет */
@layer reset {
	* {
		margin: 0;
		padding: 0;
		box-sizing: border-box;
	}
}

/* Base layer */
@layer base {
	body {
		font-family: var(--font-family-base);
		line-height: var(--line-height-base);
		color: var(--color-text);
	}

	a {
		color: var(--color-primary);
		text-decoration: none;
	}

	a:hover {
		text-decoration: underline;
	}
}

/* Components layer */
@layer components {
	.button {
		padding: 0.75rem 1.5rem;
		border-radius: var(--border-radius-md);
		background: var(--color-primary);
		color: white;
		border: none;
		cursor: pointer;
		transition: var(--transition-base);
	}

	.button:hover {
		background: var(--color-primary-dark);
	}

	.card {
		padding: var(--spacing-md);
		border-radius: var(--border-radius-md);
		box-shadow: var(--shadow-md);
		background: white;
	}
}

/* Utilities layer — найвищий пріоритет */
@layer utilities {
	.text-center {
		text-align: center;
	}
	.hidden {
		display: none;
	}
	.mt-lg {
		margin-top: var(--spacing-lg);
	}
	.p-md {
		padding: var(--spacing-md);
	}
}
```

### Вкладені layers для компонентів

```css
@layer components {
	.button {
		/* Base button styles */
		padding: 0.75rem 1.5rem;
		border: none;
		cursor: pointer;

		@layer modifiers {
			/* Модифікатори */
			&.button--large {
				padding: 1rem 2rem;
				font-size: 1.25rem;
			}

			&.button--small {
				padding: 0.5rem 1rem;
				font-size: 0.875rem;
			}

			&.button--outline {
				background: transparent;
				border: 2px solid var(--color-primary);
				color: var(--color-primary);
			}
		}

		@layer states {
			/* Стани */
			&:hover {
				transform: translateY(-2px);
			}

			&:active {
				transform: translateY(0);
			}

			&:disabled {
				opacity: 0.5;
				cursor: not-allowed;
			}
		}
	}
}
```

### Переваги Cascade Layers

✅ **Контроль порядку каскаду** без !important
✅ **Ізоляція стилів** між шарами
✅ **Легке override** через порядок layers
✅ **Модульність** для великих проектів

---

## Specificity та !important

### Уникайте !important

**!important порушує cascade і створює "specificity wars".**

```css
/* ❌ Погано */
.button {
	background: blue !important;
}

/* ✅ Краще — збільшити специфічність природно */
.site-header .button {
	background: blue;
}

/* ✅ Ще краще — використовувати :where() для zero specificity */
:where(.button) {
	background: blue;
}
```

### Порядок специфічності (від низької до високої)

1. **Type selectors** (`div`, `p`, `a`) — 0,0,1
2. **Class selectors** (`.class-name`) — 0,1,0
3. **ID selectors** (`#id-name`) — 1,0,0
4. **Inline styles** (`style=""`) — 1,0,0,0
5. **!important** — перекриває все

### Використання :is() та :where()

```css
/* :is() — приймає найвищу специфічність з аргументів */
:is(.card, .panel, .box) .title {
	font-size: 1.25rem;
	/* Specificity: 0,1,0 + 0,1,0 = 0,2,0 */
}

/* :where() — zero specificity, легко override */
:where(.card, .panel, .box) .title {
	font-size: 1.25rem;
	/* Specificity: 0,0,0 + 0,1,0 = 0,1,0 */
}

/* Легко перекрити :where() */
.custom-title {
	font-size: 1.5rem; /* Wins! */
}
```

### Best practices для specificity

✅ **Використовуйте classes** замість IDs для стилізації
✅ **Уникайте deep nesting** (max 3 рівні)
✅ **Використовуйте :where()** для базових стилів
✅ **Використовуйте @layer** для контролю каскаду
❌ **Не використовуйте !important** без крайньої необхідності
❌ **Не комбінуйте type + class** селектори (`div.class`)

---

## Performance Optimization

### Critical CSS

**Critical CSS — мінімальний CSS для above-the-fold контенту.**

#### Стратегія

1. **Inline critical CSS** у `<head>` для швидкого першого render
2. **Defer non-critical CSS** через `media="print"` hack або async loading
3. **Remove unused CSS** через PurgeCSS або WP Rocket

```html
<!-- Critical CSS inline -->
<style>
	.hero {
		/* above-the-fold styles */
	}
	.header {
		/* visible immediately */
	}
</style>

<!-- Non-critical CSS deferred -->
<link rel="stylesheet" href="style.css" media="print" onload="this.media='all'" />
<noscript><link rel="stylesheet" href="style.css" /></noscript>
```

### CSS Optimization Best Practices

| Практика                      | Опис                                | Impact            |
| ----------------------------- | ----------------------------------- | ----------------- |
| **Minify CSS**                | Видалення пробілів, коментарів      | 20-30% reduction  |
| **Combine files**             | Зменшення HTTP запитів              | Faster load       |
| **Remove unused CSS**         | PurgeCSS, UnCSS tools               | 50-90% reduction  |
| **Use shorthand**             | `margin: 0;` замість окремих сторін | Smaller file size |
| **Avoid expensive selectors** | Уникайте `*`, deep nesting          | Faster render     |
| **Preload critical CSS**      | `<link rel="preload">`              | Faster FCP        |

### Expensive селектори (уникайте)

```css
/* ❌ Дуже повільно - universal selector */
* {
	margin: 0;
}

/* ❌ Повільно - deep nesting */
.wrapper .container .card .header .title span {
	color: red;
}

/* ❌ Повільно - attribute selector з regex */
[class*='icon-'] {
	display: inline-block;
}

/* ✅ Швидко - class selector */
.card-title {
	color: red;
}

/* ✅ Швидко - shallow nesting */
.card .title {
	color: red;
}
```

---

## Коментування CSS

WordPress рекомендує **ліберальне коментування** з 80-символьним line break.

### Формат коментарів

```css
/**
 * 1.0 Section Title
 *
 * Description of section, whether or not it has media queries, etc.
 * Long comments should manually break at 80 characters for readability.
 */

.selector {
	float: left;
}

/* This is a comment about this selector */
.another-selector {
	position: absolute;
	top: 0 !important; /* Explain why this is !important */
}
```

### Table of Contents

Для великих stylesheets додайте ToC:

```css
/**
 * Table of Contents
 *
 * 1.0 - Reset
 * 2.0 - Typography
 *   2.1 - Headings
 *   2.2 - Body Copy
 * 3.0 - Layout
 *   3.1 - Header
 *   3.2 - Footer
 * 4.0 - Components
 *   4.1 - Buttons
 *   4.2 - Forms
 *   4.3 - Cards
 * 5.0 - Utilities
 */

/**
 * 1.0 - Reset
 */
* {
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}

/**
 * 2.0 - Typography
 */

/* 2.1 - Headings */
h1,
h2,
h3 {
	line-height: 1.2;
}

/* 2.2 - Body Copy */
body {
	font-family: sans-serif;
	line-height: 1.6;
}
```

---

## Linting та автоматизація

### Stylelint з WordPress конфігурацією

**Встановлення:**

```bash
npm install --save-dev @wordpress/stylelint-config stylelint stylelint-scss
```

**`.stylelintrc.json`:**

```json
{
	"extends": "@wordpress/stylelint-config/scss",
	"rules": {
		"selector-class-pattern": null,
		"no-descending-specificity": null,
		"indentation": "tab"
	}
}
```

**`package.json` scripts:**

```json
{
	"scripts": {
		"lint:css": "stylelint '**/*.css'",
		"lint:css:fix": "stylelint '**/*.css' --fix"
	}
}
```

### Autoprefixer

WordPress Core використовує **Autoprefixer** для vendor prefixes.

**Input:**

```css
.sample-output {
	box-shadow: inset 0 0 1px 1px #eee;
	display: flex;
	transform: translateY(-4px);
}
```

**Output (Autoprefixer):**

```css
.sample-output {
	-webkit-box-shadow: inset 0 0 1px 1px #eee;
	-moz-box-shadow: inset 0 0 1px 1px #eee;
	box-shadow: inset 0 0 1px 1px #eee;
	display: -webkit-box;
	display: -ms-flexbox;
	display: flex;
	-webkit-transform: translateY(-4px);
	-ms-transform: translateY(-4px);
	transform: translateY(-4px);
}
```

---

## Рекомендований стек для GeneratePress

### Компоненти стеку

| Компонент           | Рішення                                         | Причина                       |
| ------------------- | ----------------------------------------------- | ----------------------------- |
| **Методологія**     | BEM для компонентів, ITCSS для структури        | Модульність + масштабованість |
| **Variables**       | CSS Custom Properties у `:root`                 | Runtime theming, JS control   |
| **Cascade control** | `@layer` для WordPress child themes             | Контроль specificity          |
| **Preprocessing**   | Sass для mixins/functions, CSS vars для theming | Best of both worlds           |
| **Linting**         | Stylelint з `@wordpress/stylelint-config`       | WordPress standards           |
| **Optimization**    | Autoprefixer + PurgeCSS + minification          | Performance                   |
| **Browser Support** | Autoprefixer для старих браузерів               | Compatibility                 |

### Файлова структура

```
medici/
├── css/
│   ├── core/
│   │   ├── variables.css       # CSS Variables
│   │   ├── reset.css          # Reset/Normalize
│   │   └── base.css           # Base HTML styles
│   ├── components/
│   │   ├── buttons.css        # BEM buttons
│   │   ├── cards.css          # BEM cards
│   │   └── navigation.css     # BEM nav
│   ├── layout/
│   │   ├── header.css
│   │   ├── footer.css
│   │   └── grid.css
│   ├── modules/
│   │   └── blog/              # Blog-specific
│   └── utilities/
│       └── utilities.css      # Helper classes
└── style.css                  # Main stylesheet (imports)
```

### Приклад main stylesheet

```css
/**
 * Theme Name: Medici Medical Marketing
 * Main Stylesheet
 */

/* Core */
@import 'css/core/variables.css';
@import 'css/core/reset.css';
@import 'css/core/base.css';

/* Layout */
@import 'css/layout/header.css';
@import 'css/layout/footer.css';
@import 'css/layout/grid.css';

/* Components */
@import 'css/components/buttons.css';
@import 'css/components/cards.css';
@import 'css/components/navigation.css';

/* Modules */
@import 'css/modules/blog/blog-hero.css';
@import 'css/modules/blog/blog-cards.css';

/* Utilities */
@import 'css/utilities/utilities.css';
```

---

## 14. CSS Refactoring Pitfalls (Medici Project)

**⚠️ КРИТИЧНІ УРОКИ З ПРАКТИКИ**

Ця секція документує реальні проблеми та їх рішення з CSS refactoring проєкту Medici (грудень 2025).

### Проблема 1: @layer Cascade Conflicts

**Проблема:** Темна тема не працює після refactoring - навігація показує білий фон замість темного.

**Корінна причина:**

```css
/* variables.css - LOWER specificity через @layer */
@layer settings {
	[data-theme='dark'] .gbp-navigation {
		background: rgba(15, 23, 42, 0.95); /* НЕ працює! */
	}
}

/* navigation.css - HIGHER specificity без @layer */
.gbp-navigation {
	background: rgba(255, 255, 255, 0.95); /* Виграє! */
}
```

**Чому це проблема:**

- CSS з `@layer` має **НИЖЧУ специфічність** ніж non-layered CSS
- Коли variables.css має @layer, а components ні - component стилі виграють
- Dark theme стилі з variables.css не можуть override navigation.css

**Рішення:**

```css
/* OPTION 1: Видалити @layer з variables.css */
/* variables.css - NO @layer */
[data-theme='dark'] .gbp-navigation {
	background: rgba(15, 23, 42, 0.95); /* Працює! */
}

/* OPTION 2: Додати @layer всюди */
/* variables.css */
@layer settings {
	[data-theme='dark'] .gbp-navigation {
		background: rgba(15, 23, 42, 0.95);
	}
}

/* navigation.css */
@layer components {
	.gbp-navigation {
		background: rgba(255, 255, 255, 0.95);
	}
}
```

**Правило:**

> **НІКОЛИ не мішайте @layer та non-@layer CSS** в одному проєкті. Або використовуйте @layer всюди, або ніде.

**Impact:** Це була корінна причина більшості проблем з темною темою в Medici проєкті.

---

### Проблема 2: Incomplete CSS Variables в Dark Theme

**Проблема:** Кнопки мають невидимий фон та текст у темній темі.

**Корінна причина:** Неповний набір CSS variables у `[data-theme="dark"]` блоці.

```css
/* buttons.css використовує */
.gbp-button--primary {
	background: var(--accent); /* undefined в dark theme! */
	box-shadow: var(--shadow-md); /* undefined в dark theme! */
	color: white;
}

/* variables.css - НЕПОВНИЙ dark theme */
[data-theme='dark'] {
	--bg-primary: #0f172a;
	--text-primary: #f1f5f9;
	/* --accent ВІДСУТНЯ! */
	/* --shadow-md ВІДСУТНЯ! */
}
```

**Результат:**

- `var(--accent)` падає до light theme значення або undefined
- Кнопка має синій фон з синім текстом (невидимий текст)
- Shadows не працюють

**Рішення:** Визначити ВСІ використовувані variables у dark theme блоці:

```css
[data-theme='dark'] {
	/* Базові кольори */
	--base: #0f172a;
	--base-2: #64748b;
	--base-3: #0f172a;

	/* Accent кольори - КРИТИЧНО! */
	--accent: #3b82f6;
	--accent-2: #60a5fa;
	--accent-3: #93c5fd;

	/* Background кольори */
	--bg-primary: #0f172a;
	--bg-secondary: #1e293b;
	--bg-card: #1e293b;

	/* Text кольори */
	--text-primary: #f1f5f9;
	--text-secondary: #94a3b8;

	/* Shadows - adapted для темного фону */
	--shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.5);
	--shadow-md: 0 4px 6px rgba(0, 0, 0, 0.5);
	--shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.6);
	--shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.7);

	/* Brand кольори */
	--color-accent: #00a693;

	/* Borders */
	--border-color: #334155;

	/* Footer */
	--footer-bg: #1e293b;
	--footer-text: #e2e8f0;
}
```

**Checklist для Dark Theme Variables:**

- [ ] Color palette (--accent-_, --base-_, --text-\*)
- [ ] Backgrounds (--bg-\*)
- [ ] Shadows (--shadow-\* - **адаптовані** для темного фону!)
- [ ] Brand colors (--color-\*)
- [ ] Borders (--border-color)
- [ ] Component-specific colors (footer, navigation, etc.)

**Правило:**

> При створенні dark theme ВСІ CSS variables, які використовуються в компонентах, мають бути **явно перевизначені**.

---

### Проблема 3: GenerateBlocks Class Override

**Проблема:** Текст на кнопках невидимий навіть після додавання CSS variables.

**Корінна причина:** GenerateBlocks додає `.gb-text` клас з власними стилями кольору.

```html
<!-- GenerateBlocks генерує -->
<a class="gb-text gbp-button--primary gb-text-a1b2c3d4" href="#"> Кнопка </a>
```

```css
/* buttons.css - недостатньо специфічності */
.gbp-button--primary {
	color: white; /* НЕ працює! */
}

/* GenerateBlocks .gb-text має вищу специфічність */
.gb-text {
	color: inherit; /* Виграє! */
}
```

**Рішення:** Використовувати селектори з вищою специфічністю + `!important`:

```css
/* ❌ НЕ достатньо */
.gbp-button--primary {
	color: white;
}

/* ✅ ПОТРІБНО - higher specificity + !important */
.gb-text.gbp-button--primary,
a.gb-text.gbp-button--primary,
a.gbp-button--primary {
	color: white !important;
}

.gb-text.gbp-cta-button,
a.gb-text.gbp-cta-button,
a.gbp-cta-button {
	color: white !important;
}
```

**Пояснення:**

- `.gb-text.gbp-button--primary` - дві classes (0,2,0 specificity)
- `a.gb-text.gbp-button--primary` - element + 2 classes (0,2,1 specificity)
- `!important` гарантує override навіть inline styles

**Коли використовувати !important з framework класами:**
✅ Override framework стилів (GenerateBlocks, Bootstrap)
✅ Utility classes (з найвищим пріоритетом)
✅ Critical styles що мають працювати завжди
❌ Не використовувати для звичайних компонентних стилів

**Правило:**

> При інтеграції з CSS frameworks (GenerateBlocks, Bootstrap) використовуйте **higher specificity + !important** для override framework класів.

---

### Проблема 4: Missing Body Background

**Проблема:** Світла тема показує сірий фон замість білого.

**Корінна причина:** Відсутній явний `background` для `body` в light theme.

```css
/* variables.css - тільки dark theme body */
[data-theme='dark'] body {
	background: var(--bg-primary);
	color: var(--text-primary);
}

/* Light theme body - ВІДСУТНІЙ! */
/* Browser default може бути сірим */
```

**Рішення:** Явно визначити body background для **обох** тем:

```css
/* Light Theme Body Styles (Default) */
body {
	background: #ffffff; /* Явно білий */
	color: var(--text-primary);
}

/* Dark Theme Body Styles */
[data-theme='dark'] body {
	background: var(--bg-primary); /* Явно темний */
	color: var(--text-primary);
}
```

**Правило:**

> Завжди **явно визначайте** body background для обох тем. Не покладайтесь на browser defaults.

---

### Проблема 5: Semantic HTML для Interactive Elements

**Проблема:** Мобільне меню не працює після рефакторингу.

**Корінна причина:** HTML використовував `<div>` замість `<button>` для interactive element.

```html
<!-- ❌ НЕПРАВИЛЬНО - div замість button -->
<div class="gbp-mobile-toggle" type="button" aria-label="Toggle menu"></div>

<!-- ✅ ПРАВИЛЬНО - button element -->
<button class="gbp-mobile-toggle" type="button" aria-label="Відкрити меню" aria-expanded="false">
	<span class="hamburger-line"></span>
	<span class="hamburger-line"></span>
	<span class="hamburger-line"></span>
</button>
```

**Чому це важливо:**
✅ Accessibility - keyboard navigation, screen readers
✅ Native behavior - focus states, :hover/:active
✅ Semantic clarity - зрозуміло що це кнопка
✅ ARIA attributes - працюють правильно з button

**Правило:**

> Інтерактивні елементи мають використовувати **семантично правильні** HTML теги (`<button>`, `<a>`, `<input>`).

---

### Best Practices Summary

**✅ РОБИТИ:**

1. **@layer consistency** - або всюди, або ніде
2. **Complete CSS variables** - всі variables для dark theme
3. **Higher specificity** - для override framework класів
4. **Explicit defaults** - body background для обох тем
5. **Semantic HTML** - правильні теги для interactive elements
6. **Test both themes** - light + dark після кожної зміни
7. **Use browser DevTools** - inspect cascade та specificity issues

**❌ НЕ РОБИТИ:**

1. ❌ Мішати @layer та non-@layer CSS
2. ❌ Неповний набір variables у dark theme
3. ❌ Покладатись на browser defaults (body background)
4. ❌ Використовувати `<div>` для buttons
5. ❌ Пропускати тестування темної теми
6. ❌ Використовувати !important без необхідності (але з frameworks - OK)

---

### Debugging Checklist

Коли темна тема не працює:

1. **Перевірити @layer usage:**

   ```bash
   grep -r "@layer" css/
   ```

   Якщо є @layer - переконатись що скрізь або ніде.

2. **Перевірити CSS variables completeness:**

   ```bash
   # Знайти всі використання var()
   grep -roh "var(--[a-z-]*)" css/components/ | sort -u

   # Перевірити чи всі визначені в [data-theme="dark"]
   grep "data-theme=\"dark\"" css/core/variables.css -A 50
   ```

3. **Перевірити specificity з DevTools:**
   - Inspect element в браузері
   - Дивитись Computed styles
   - Шукати crossed-out styles (overridden)
   - Перевіряти де стилі прийшли (з якого селектора)

4. **Перевірити framework classes:**
   - Inspect HTML в браузері
   - Дивитись які classes додає framework
   - Тестувати override з higher specificity

5. **Перевірити body background:**
   ```css
   /* В variables.css має бути */
   body {
   	background: #ffffff;
   }
   [data-theme='dark'] body {
   	background: var(--bg-primary);
   }
   ```

---

### Real-World Example (Medici Project)

**Timeline проблем:**

1. ✅ Initial refactor (03aefc5) - видалено @layer з components
2. ❌ Dark theme breaks - navigation білий (причина: @layer в variables.css)
3. ✅ Fix 1 (8f378f9) - видалено @layer з variables.css
4. ❌ Buttons invisible - no background (причина: missing variables)
5. ✅ Fix 2 (491ecb8) - додано 11 missing variables
6. ❌ Light theme gray - not white (причина: no body background)
7. ✅ Fix 3 (5173880) - додано body background для light theme
8. ❌ Button text invisible - blue on blue (причина: GenerateBlocks override)
9. ✅ Fix 4 (0129803) - додано !important з higher specificity

**Уроки:**

- @layer conflict був **root cause** більшості проблем
- Incomplete variables призвели до **cascading failures**
- GenerateBlocks integration потребує **!important strategy**
- Testing обох тем після **кожної зміни** критично важливо

**Файли документації:**

- `CHANGELOG.md` - повний аналіз всіх 6 commits
- `STYLE-RULES-CSS-STANDARDS.md` (ця секція) - technical pitfalls
- `CLAUDE.md` - буде оновлено з Common Pitfalls для AI асистентів

---

## 📊 Checklist

Перед коммітом CSS коду перевірте:

✅ **Форматування**

- [ ] Tabs для індентації
- [ ] Кожен селектор на новому рядку
- [ ] Lowercase для селекторів та значень
- [ ] Hex кольори скорочені (#fff)
- [ ] 0 без одиниць
- [ ] Line-height без одиниць

✅ **Іменування**

- [ ] Hyphen-case для класів
- [ ] BEM для компонентів
- [ ] Описові назви

✅ **Структура**

- [ ] Логічний порядок властивостей
- [ ] Два порожні рядки між секціями
- [ ] Коментарі для складних секцій

✅ **Якість**

- [ ] Немає !important без причини
- [ ] Уникання deep nesting (max 3)
- [ ] CSS Variables для theming
- [ ] @layer для cascade control

✅ **Performance**

- [ ] Видалено unused CSS
- [ ] Autoprefixer для compatibility
- [ ] Critical CSS inline
- [ ] Minification для production

✅ **Автоматизація**

- [ ] Stylelint перевірка пройшла
- [ ] Prettier форматування
- [ ] Build process працює

---

## 🔗 Корисні ресурси

- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [BEM Methodology](https://en.bem.info/methodology/)
- [ITCSS Architecture](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/)
- [CSS Tricks - BEM 101](https://css-tricks.com/bem-101/)
- [MDN - CSS Cascade Layers](https://developer.mozilla.org/en-US/docs/Web/CSS/@layer)
- [Can I Use - Browser Support](https://caniuse.com/)

---

**END OF CSS CODING STANDARDS**

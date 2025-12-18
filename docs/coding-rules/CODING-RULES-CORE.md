# 🚨 ОБОВ'ЯЗКОВІ ПРАВИЛА КОДУВАННЯ MEDICI

## 📑 ЗМІСТ

- [⚠️ КРИТИЧНО ВАЖЛИВО](#️-критично-важливо---читати-перед-будь-яким-кодом)
- [📚 ОБОВ'ЯЗКОВА ДОКУМЕНТАЦІЯ](#-обовязкова-документація)
- [🔴 КРИТИЧНІ ПРАВИЛА GENERATEBLOCKS 2.X](#-критичні-правила-generateblocks-2x)
  - [1. UniqueId Format](#1-uniqueid-format-порушення--зламана-тема)
  - [2. CSS Variables Escaping](#2-css-variables-escaping-обовязково)
  - [3. Responsive Breakpoints](#3-responsive-breakpoints-завжди)
  - [4. Ampersand Escaping](#4-ampersand-escaping-обовязково)
  - [5. GenerateBlocks Patterns](#5-generateblocks-patterns)
  - [6. Visual Effects](#6-visual-effects-критично)
  - [7. Global Styles](#7-global-styles-gblocks_styles---16-класів)
  - [8. Two-Level Section Pattern](#8-two-level-section-pattern-стандарт)
  - [9. Dynamic Content Hooks](#9-dynamic-content-hooks-query-loops)
  - [10. Attribute Order](#10-attribute-order-стандарт)
  - [11. Local Fonts](#11-local-fonts-gp_font)
  - [12. Custom Elements](#12-custom-elements-gp_elements)
  - [13. WordPress Hooks та Events](#13-wordpress-hooks-та-events-критично)
  - [14. Меню та Навігація](#14-меню-та-навігація)
  - [15. Блог, Статті, Коментарі](#15-блог-статті-коментарі)
  - [16. Форми та Newsletter](#16-форми-та-newsletter)
  - [17. Медіафайли та Оптимізація](#17-медіафайли-та-оптимізація)
  - [18. Overlay Panels](#18-overlay-panels)
  - [19. Dynamic Content - Query & Looper](#19-dynamic-content---query--looper)
  - [20. Structured Styles](#20-structured-styles-gb-2x)

---

## ⚠️ КРИТИЧНО ВАЖЛИВО - ЧИТАТИ ПЕРЕД БУДЬ-ЯКИМ КОДОМ!

Ця тема використовує **комерційні (платні) версії**:

- ✅ **GeneratePress Premium** (не безкоштовна)
- ✅ **GenerateBlocks Pro 2.0+** (не безкоштовна)

**ВСІ ЗМІНИ КОДУ МАЮТЬ ВІДПОВІДАТИ ВИМОГАМ ЦЬОГО ФАЙЛУ!**

---

## 📚 ОБОВ'ЯЗКОВА ДОКУМЕНТАЦІЯ

### Перед написанням коду завжди читайте:

1. **`Skill.md`** - Повна документація GenerateBlocks 2.x (1109 рядків)
   - 4 Core блоки + 7 Pro блоки
   - UniqueId правила (КРИТИЧНО!)
   - CSS Variables escaping
   - Responsive breakpoints
   - Production patterns

2. **`CLAUDE.md`** - Архітектура теми Medici
   - Структура файлів
   - WordPress hooks
   - Blog Module API
   - Security guidelines
   - Performance best practices

3. **`.claude/skills/generateblocks.md`** - Skill метадані
   - Auto-activation правила
   - Quick reference
   - Integration points

---

## 🔴 КРИТИЧНІ ПРАВИЛА GENERATEBLOCKS 2.X

### 1. UniqueId Format (ПОРУШЕННЯ = ЗЛАМАНА ТЕМА!)

**✅ ПРАВИЛЬНО:**

```json
"uniqueId": "96646288"  // 8 hex символів (0-9, a-f)
"uniqueId": "b00feabd"  // тільки малі букви
"uniqueId": "9e8d7c6b"  // рівно 8 символів
```

**❌ ЗАБОРОНЕНО:**

```json
"uniqueId": "8821b73e"     // містить o, n, t (НЕ hex!)
"uniqueId": "72a20e46"     // містить h, e, r, o (НЕ hex!)
"uniqueId": "A1B2C3D4"     // великі букви ЗАБОРОНЕНІ
"uniqueId": "a1b2c3"       // менше 8 символів
"uniqueId": "96646288e5"   // більше 8 символів
```

**Генератор (використовуйте це!):**

## ГЕНЕРАТОР uniqueId

Щоб генерувати правильний uniqueId, використовуйте:

**JavaScript:**

````javascript
function generateUniqueId() {
  return Array.from({ length: 8 }, () =>
    Math.floor(Math.random() * 16).toString(16)
  ).join('');
}

**Python:**
```python
import secrets
def generate_unique_id():
    return ''.join(secrets.choice('0123456789abcdef') for _ in range(8))


### 2. CSS Variables Escaping (ОБОВ'ЯЗКОВО!)

**КРИТИЧНО: Формат залежить від контексту!**

**А. В Markdown файлах (.md) - Gutenberg patterns:**
```json
{
  "color": "var(\\u002d\\u002daccent)",
  "backgroundColor": "var(\\u002d\\u002dbase-2)"
}
````

**Б. В XML експортах WordPress:**

```json
{
	"color": "var(\u002d\u002daccent)",
	"backgroundColor": "var(\u002d\u002dbase-2)"
}
```

**В. В CSS файлах (нормально):**

```css
.my-class {
	color: var(--accent);
	background-color: var(--base-2);
}
```

**ПРАВИЛО:** У `.md` файлах використовуйте `\\u002d\\u002d` (подвійний backslash), в XML - `\u002d\u002d` (одинарний).

**Доступні CSS змінні (GeneratePress + Medici):**

- `--base` - Білий фон
- `--base-2` - Світло-сірий (#f9fafb)
- `--base-3` - Чистий білий
- `--contrast` - Темний текст
- `--contrast-2` - Приглушений текст (#6b7280)
- `--contrast-3` - Ще світліший текст
- `--accent` - Синій (#2563eb)
- `--accent-2` - Темно-синій
- `--accent-3` - Найтемніший синій
- `--gb-container-width` - Ширина контейнера (GenerateBlocks)
- `--global-color-8` - Глобальний колір 8

### 3. Responsive Breakpoints (ЗАВЖДИ!)

```json
{
	"styles": {
		"padding": "4rem",
		"@media (max-width: 1024px)": {
			"padding": "3rem"
		},
		"@media (max-width: 768px)": {
			"padding": "2rem"
		}
	}
}
```

**Стандарт Medici:**

- Desktop: 1441px+
- Laptop: 1025-1440px
- Tablet: 768-1024px
- Mobile: <767px

### 4. Ampersand Escaping (ОБОВ'ЯЗКОВО!)

**Для pseudo-селекторів в JSON:**

```json
{
	"styles": {
		"transition": "all 0.5s ease 0s",
		"\\u0026:is(:hover, :focus)": {
			"transform": "translateY(-5px)"
		}
	}
}
```

**Правила:**

- `&` → `\u0026` → `\\u0026` (подвійний backslash для JSON)
- **ЗАВЖДИ** використовуй `:is(:hover, :focus)` не просто `:hover`
- Accessibility: підтримка клавіатурної навігації

### 5. GenerateBlocks Patterns

Використовуй **Reusable Blocks** (багаторазові блоки) через WordPress Editor:

1. Створи блок в редакторі
2. `Options → Create Reusable Block`
3. Використовуй через `Add Block → Reusable`

### 6. Visual Effects (КРИТИЧНО!)

**✅ ДОЗВОЛЕНІ трансформації:**

```json
{
	"transition": "all 0.3s ease 0s",
	"\\u0026:is(:hover, :focus)": {
		"transform": "translateY(-5px)" // ✅ Підняття
	}
}
```

```json
{
	"transform": "translate3d(0px, -3px, 0px)" // ✅ 3D переміщення
}
```

```json
{
	"transform": "scale(1.1)" // ✅ Збільшення
}
```

**❌ ЗАБОРОНЕНІ трансформації на ОСНОВНИХ БЛОКАХ:**

```json
{
	"transform": "rotate(20deg)" // ❌ ЗАБОРОНЕНО на section, div, article!
}
```

```json
{
	"transform": "rotateX(45deg)" // ❌ ЗАБОРОНЕНО на блоках!
}
```

```json
{
	"transform": "rotateY(90deg)" // ❌ ЗАБОРОНЕНО на блоках!
}
```

**✅ ВИНЯТОК: Rotate ДОЗВОЛЕНО на вкладених іконках:**

```json
{
	"uniqueId": "1503c9ba",
	"globalClasses": ["gbp-button--primary"],
	"styles": {
		"span.gb-shape": {
			"transform": "rotate(-45deg)", // ✅ ДОЗВОЛЕНО на іконці!
			"transition": "all 0.3s ease 0s"
		},
		"\\u0026:is(:hover, :focus) span.gb-shape": {
			"transform": "rotate(0deg)" // ✅ ДОЗВОЛЕНО на іконці!
		}
	}
}
```

**Правило:** Rotate ЗАБОРОНЕНО на основних блоках (section, div, article), але ДОЗВОЛЕНО на вкладених іконках (span.gb-shape, svg).

**Стандартний transition timing:** `"all 0.3s ease 0s"` (НЕ 0.5s!)

### 7. Global Styles (gblocks_styles) - 16 класів

**GeneratePress Premium надає 16 готових глобальних класів:**

**SECTIONS (6 класів):**

```json
{
	"globalClasses": ["gbp-section"] // Секція (padding 8rem desktop, 6rem mobile)
}
```

```json
{
	"globalClasses": ["gbp-section__inner"] // Контейнер (max-width: var(--gb-container-width))
}
```

```json
{
	"globalClasses": ["gbp-section__headline"] // Заголовок секції
}
```

```json
{
	"globalClasses": ["gbp-section__tagline"] // Tagline (Amatic SC, uppercase, letter-spacing: 2px)
}
```

```json
{
	"globalClasses": ["gbp-section__text"] // Текст секції
}
```

```json
{
	"globalClasses": ["gbp-section--background"] // Секція з фоном
}
```

**CARDS (5 класів):**

```json
{
	"globalClasses": ["gbp-card"] // Основна картка
}
```

```json
{
	"globalClasses": ["gbp-card--border"] // Картка з бордером
}
```

```json
{
	"globalClasses": ["gbp-card__title"] // Заголовок картки
}
```

```json
{
	"globalClasses": ["gbp-card__text"] // Текст картки
}
```

```json
{
	"globalClasses": ["gbp-card__meta-text"] // Мета інформація
}
```

**BUTTONS (4 класи):**

```json
{
	"globalClasses": ["gbp-button--primary"] // Первинна кнопка (з rotate на іконці!)
}
```

```json
{
	"globalClasses": ["gbp-button--secondary"] // Вторинна кнопка
}
```

```json
{
	"globalClasses": ["gbp-button--tertiary"] // Третинна кнопка
}
```

```json
{
	"globalClasses": ["gbp-button--tertiary-2"] // Альтернативна третинна
}
```

**UTILITIES (1 клас):**

```json
{
	"globalClasses": ["gbp--border"] // Border стиль (3px solid)
}
```

**ВАЖЛИВО:**

- Використовуй `globalClasses` array, НЕ `className`
- Ніколи не додавай CSS variables в className
- Global classes автоматично застосовують theme стилі
- `.gbp-button--primary` має rotate(-45deg) на іконці (span.gb-shape)
- `.gbp-section__tagline` використовує `var(--gp-font--amatic-sc)`

**Структура PostType `gblocks_styles`:**

```php
// Meta Fields:
gb_style_selector   // CSS селектор (.gbp-section, .gbp-button--primary)
gb_style_css        // Мініфікований CSS код
gb_style_data       // PHP serialized: структурована CSS дата
```

**Приклад `gb_style_data` (PHP Serialized):**

```php
// .gbp-button--secondary
a:34:{
  s:7:"display";s:11:"inline-flex";
  s:7:"padding";s:9:"14px 24px";
  s:10:"fontWeight";s:3:"600";
  s:20:"&:is(:hover, :focus)";a:2:{
    s:17:"backgroundColor";s:31:"var(\\u002d\\u002dbase)";
    s:9:"transform";s:18:"translateY(-2px)";
  }
  s:21:"&:hover span.gb-shape";a:2:{
    s:9:"transform";s:13:"rotate(0deg)";
  }
}

// Розшифровка:
array(
  'display' => 'inline-flex',
  'padding' => '14px 24px',
  'fontWeight' => '600',
  '&:is(:hover, :focus)' => array(
    'backgroundColor' => 'var(\\u002d\\u002dbase)',
    'transform' => 'translateY(-2px)'
  ),
  '&:hover span.gb-shape' => array(
    'transform' => 'rotate(0deg)'
  )
)
```

**Generated CSS (`gb_style_css`):**

```css
.gbp-button--secondary {
	display: inline-flex;
	padding: 14px 24px;
	font-weight: 600;
	transition: all 0.3s ease 0s;
}
.gbp-button--secondary:is(:hover, :focus) {
	background-color: var(--base);
	transform: translateY(-2px);
}
.gbp-button--secondary:hover span.gb-shape {
	transform: rotate(0deg);
}
```

### 8. Two-Level Section Pattern (СТАНДАРТ!)

**Правильна структура секції:**

```html
<!-- Outer element: gbp-section -->
<!-- wp:generateblocks/element {
  "uniqueId": "1a1bcca9",
  "tagName": "section",
  "styles": {
    "paddingTop": "6rem",
    "paddingBottom": "6rem",
    "backgroundColor": "var(\\u002d\\u002dbase-2)"
  },
  "globalClasses": ["gbp-section"]
} -->
<section class="gbp-section gb-element-1a1bcca9">
	<!-- Inner element: gbp-section__inner + max-width -->
	<!-- wp:generateblocks/element {
    "uniqueId": "9364e92e",
    "tagName": "div",
    "styles": {
      "maxWidth": "var(\\u002d\\u002dgb-container-width)",
      "marginLeft": "auto",
      "marginRight": "auto",
      "paddingLeft": "1rem",
      "paddingRight": "1rem"
    },
    "globalClasses": ["gbp-section__inner"]
  } -->
	<div class="gbp-section__inner gb-element-9364e92e">
		<!-- Контент тут -->
	</div>
</section>
```

**Правило:** Завжди використовуй двох-рівневу структуру для секцій!

### 9. Dynamic Content Hooks (Query Loops)

**Доступні хуки:**

```json
"content": "{{post_title}}"                              // Заголовок посту
"content": "{{post_permalink}}"                          // URL посту
"content": "{{post_excerpt length:120}}"                 // Витяг (120 символів)
"content": "{{post_date format:j F Y}}"                  // Дата
"content": "{{post_terms taxonomy:category}}"            // Категорії
"content": "{{post_meta key:_medici_views}}"             // Custom meta
"content": "{{featured_image key:url size:large}}"       // Featured image URL
```

**Query Loop Hierarchy:**

```
query (встановлює WP_Query параметри)
  └── looper (контейнер з grid/flex)
        └── loop-item (шаблон для одного елементу)
              └── content blocks (text, media з dynamic hooks)
```

**Приклад Query:**

```json
{
	"query": {
		"post_type": "post",
		"posts_per_page": 6,
		"orderby": "date",
		"order": "DESC",
		"meta_key": "_medici_featured",
		"meta_value": "1"
	}
}
```

### 10. Attribute Order (СТАНДАРТ!)

**Правильний порядок атрибутів:**

```json
{
  "uniqueId": "64074c98",        // 1. ЗАВЖДИ ПЕРШИЙ
  "tagName": "div",              // 2. Тег
  "styles": { ... },             // 3. Стилі
  "css": "...",                  // 4. Auto-generated CSS
  "globalClasses": [...],        // 5. Глобальні класи
  "metadata": { ... },           // 6. Метадані
  "htmlAttributes": { ... }      // 7. HTML атрибути
}
```

**НЕ ЗМІНЮЙТЕ ПОРЯДОК!** Це стандарт GenerateBlocks 2.x.

### 11. Local Fonts (gp_font)

**GeneratePress Premium дозволяє завантажувати локальні шрифти:**

**Структура PostType `gp_font`:**

```php
// Meta Fields:
gp_font_variants    // PHP serialized array з font-weight, font-style, src
gp_font_display     // auto (default) | block | swap | fallback | optional
gp_font_variable    // CSS variable: --gp-font--{name}
```

**Доступні локальні шрифти в темі:**

- **Amatic SC** - 2 варіанти (400, 700)
  - CSS var: `var(--gp-font--amatic-sc)`
  - font-display: `auto`
  - Font variants:
    - Regular 400 normal: TUZyzwprpvBS1izr_vOECuSf.woff2
    - Bold 700 normal: TUZ3zwprpvBS1izr_vOMscGKfrUC.woff2
  - Використання: `.gbp-section__tagline` (uppercase, letter-spacing: 2px)

- **Rubik** - 14 варіантів (300-900, normal + italic)
  - CSS var: `var(--gp-font--rubik)`
  - font-display: `auto`
  - Font weights: Light 300, Regular 400, Medium 500, SemiBold 600, Bold 700, ExtraBold 800, Black 900
  - Кожен вага має нормальний (`normal`) і `italic` стиль
  - Формат: WOFF2 (оптимізований)
  - Використання: основний текст, заголовки, кнопки

**Приклад використання в JSON:**

```json
{
	"styles": {
		"fontFamily": "var(\\u002d\\u002dgp-font--amatic-sc)",
		"fontSize": "clamp(1.5rem, 3vw, 2rem)",
		"fontWeight": "700",
		"textTransform": "uppercase",
		"letterSpacing": "2px"
	}
}
```

**Альтернатива в CSS:**

```css
.my-tagline {
	font-family: var(--gp-font--amatic-sc);
	font-weight: 700;
}

.my-heading {
	font-family: var(--gp-font--rubik);
	font-weight: 600;
	font-style: normal; /* або italic */
}
```

**ВАЖЛИВО:**

- Всі шрифти self-hosted (без зовнішніх запитів)
- Font files у форматі WOFF2 (краща компресія)
- font-display: auto - браузер вирішує стратегію завантаження
- PHP serialized structure зберігається в `gp_font_variants`

### 12. Custom Elements (gp_elements)

**GeneratePress Premium підтримує 24 типи кастомних елементів:**

**Types:**

- `page-hero` (8) - Hero секції для сторінок
- `content-template` (7) - Шаблони контенту
- `site-header` (2) - Заголовок сайту
- `site-footer` (2) - Підвал сайту
- `loop-template` (2) - Шаблони блог-циклу
- `right-sidebar` (1) - Права колонка
- `hook` (1) - Кастомний хук

**Hooks для Custom Elements:**

- `generate_after_header` - Після header (використовується для hero)
- `generate_after_main_content` - Після основного контенту
- `generate_after_content` - Після контенту
- `generate_before_footer` - Перед footer

**Display Conditions (PHP Serialized):**

```php
// Meta field: _generate_element_display_conditions
// Format: PHP serialized array

// Приклад 1: Show on front page only
a:1:{i:0;a:2:{s:4:"rule";s:18:"general:front_page";s:6:"object";s:1:"0";}}

// Розшифровка:
array(
    0 => array(
        'rule' => 'general:front_page',
        'object' => '0'  // 0 або ID залежно від rule
    )
)

// Приклад 2: Show on all site
a:1:{i:0;a:2:{s:4:"rule";s:12:"general:site";s:6:"object";s:1:"0";}}
```

**Доступні Display Rules:**

- `general:site` - Весь сайт
- `general:front_page` - Лише на головній
- `general:post_type` - Певний тип посту (object = post type name)
- `general:archive` - На архівних сторінках
- `general:singular` - На окремих постах
- `general:category` - На сторінках категорій (object = category ID)
- `general:tag` - На сторінках тегів (object = tag ID)
- `general:taxonomy` - На таксономіях
- `general:author` - На сторінках авторів

**Meta Fields:**

```php
'_generate_block_type'              => 'page-hero'               // Тип елементу
'_generate_hook'                    => 'generate_after_header'  // Hook location
'_generate_custom_hook'             => ''                       // Кастомний hook
'_generate_hook_priority'           => ''                       // Пріоритет (пусто = 10)
'_generate_element_type'            => 'block'                  // Тип: block/legacy
'_generate_element_display_conditions' => 'a:1:{...}'           // PHP serialized
```

**ВАЖЛИВО:**

- Custom Elements створюються в WordPress Admin → GeneratePress → Elements
- Кожен елемент може мати свої Display Conditions
- Hooks дозволяють вставляти контент в різні місця теми
- Loop Templates використовуються для custom post types

### 13. WordPress Hooks та Events (КРИТИЧНО!)

**ПРАВИЛО ПРІОРИТЕТІВ:**

```php
// Low priority (1-5) = виконується РАНІШЕ
add_action('init', 'early_function', 1);

// Normal priority (10) = стандартний
add_action('wp_enqueue_scripts', 'enqueue_assets', 10);

// High priority (100-999) = виконується ПІЗНІШЕ
add_action('wp_footer', 'late_function', 999);
```

**Основні WordPress Hooks:**

**Init та Setup:**

```php
// Ранній init для реєстрації post types, taxonomies
add_action('init', 'register_custom_post_types', 1);

// Пізній init для shortcodes
add_action('init', 'register_shortcodes', 20);

// After theme setup
add_action('after_setup_theme', 'theme_setup', 10);

// Widgets registration
add_action('widgets_init', 'register_widgets', 10);
```

**Asset Loading:**

```php
// Frontend assets
add_action('wp_enqueue_scripts', 'enqueue_frontend_assets', 10);

// Admin assets
add_action('admin_enqueue_scripts', 'enqueue_admin_assets', 10);

// Conditional loading (ВАЖЛИВО для performance!)
if (is_single() && get_post_type() === 'post') {
	wp_enqueue_style('blog-single');
}
```

**Content Modification:**

```php
// Модифікація контенту (priority ВАЖЛИВО!)
add_filter('the_content', 'add_table_of_contents', 5); // Рано
add_filter('the_content', 'add_reading_time', 10); // Стандарт
add_filter('the_content', 'add_social_share', 20); // Пізно
```

**Headers та Footer:**

```php
// Security headers (КРИТИЧНО - priority 1!)
add_action('send_headers', 'security_headers', 1);

// Meta tags in <head>
add_action('wp_head', 'custom_meta_tags', 10);

// Scripts in footer
add_action('wp_footer', 'custom_scripts', 10);
```

**Admin та Backend:**

```php
// Admin menu
add_action('admin_menu', 'register_admin_pages', 10);

// Meta boxes
add_action('add_meta_boxes', 'register_meta_boxes', 10);

// Save post
add_action('save_post', 'save_custom_meta', 10, 3);

// Admin columns
add_filter('manage_post_posts_columns', 'add_custom_columns', 10);
add_action('manage_post_posts_custom_column', 'render_custom_column', 10, 2);
```

**AJAX Handlers:**

```php
// Logged in users
add_action('wp_ajax_custom_action', 'ajax_handler');

// Non-logged in users (public)
add_action('wp_ajax_nopriv_custom_action', 'ajax_handler');

// Обидва варіанти
add_action('wp_ajax_load_more_posts', 'ajax_load_more');
add_action('wp_ajax_nopriv_load_more_posts', 'ajax_load_more');
```

**ПРАВИЛА ВИКОРИСТАННЯ:**

1. **Завжди перевіряйте існування функції:**

```php
if (!function_exists('my_function')) {
	function my_function()
	{
		// код
	}
}
```

2. **Використовуйте правильний priority:**

```php
// РАНО (1-5) для критичних операцій
add_action('init', 'critical_init', 1);

// СТАНДАРТ (10) для звичайних операцій
add_action('wp_enqueue_scripts', 'enqueue_assets', 10);

// ПІЗНО (50-999) для операцій що залежать від інших
add_filter('the_content', 'final_content_mod', 50);
```

3. **Conditional hooks для performance:**

```php
// ПРАВИЛЬНО
function enqueue_blog_assets()
{
	if (!is_single() || get_post_type() !== 'post') {
		return;
	}
	wp_enqueue_style('blog-single');
}
add_action('wp_enqueue_scripts', 'enqueue_blog_assets');

// НЕПРАВИЛЬНО (завжди завантажує)
function enqueue_blog_assets()
{
	wp_enqueue_style('blog-single');
}
add_action('wp_enqueue_scripts', 'enqueue_blog_assets');
```

4. **Remove hooks безпечно:**

```php
// ПРАВИЛЬНО
remove_action('wp_head', 'wp_generator');

// З priority
remove_filter('the_content', 'wpautop', 10);

// З class method
remove_action('init', [$instance, 'method'], 10);
```

### 14. Меню та Навігація

**Реєстрація меню:**

```php
function register_menus()
{
	register_nav_menus([
		'primary' => __('Головне меню', 'medici.agency'),
		'mobile' => __('Мобільне меню', 'medici.agency'),
		'footer' => __('Меню футера', 'medici.agency'),
	]);
}
add_action('after_setup_theme', 'register_menus');
```

**Виведення меню:**

```php
// Стандартне меню WordPress
wp_nav_menu([
	'theme_location' => 'primary',
	'container' => 'nav',
	'container_class' => 'main-navigation',
	'menu_class' => 'menu',
	'fallback_cb' => false,
]);
```

**GenerateBlocks Navigation Pattern:**

```html
<!-- wp:generateblocks/element {
  "uniqueId": "0c764291",
  "tagName": "nav",
  "htmlAttributes": {
    "aria-label": "Головна навігація"
  }
} -->
<nav class="gb-element-0c764291" aria-label="Головна навігація">
	<!-- Посилання меню -->
</nav>
```

**Navigation Link Pattern:**

```json
{
	"uniqueId": "14ce5f3e",
	"tagName": "a",
	"styles": {
		"color": "var(\\u002d\\u002dbase)",
		"textDecoration": "none",
		"fontWeight": "500",
		"transition": "all 0.3s ease 0s",
		"\\u0026:is(:hover, :focus)": {
			"color": "var(\\u002d\\u002daccent)"
		}
	},
	"htmlAttributes": {
		"href": "/page"
	}
}
```

**Mobile Menu Toggle:**

```json
{
	"uniqueId": "0ef3c1ce",
	"tagName": "button",
	"styles": {
		"display": "none",
		"@media (max-width:1024px)": {
			"display": "flex"
		}
	},
	"htmlAttributes": {
		"id": "mobileMenuToggle",
		"type": "button",
		"aria-label": "Відкрити меню"
	}
}
```

**ВАЖЛИВО:**

- Завжди додавайте `aria-label` для accessibility
- Використовуйте `:is(:hover, :focus)` для keyboard navigation
- Мобільне меню має з'являтися на `@media (max-width:1024px)`
- Toggle button має type="button" та aria-label

### 15. Блог, Статті, Коментарі

**Реєстрація Blog Meta:**

```php
function register_blog_meta()
{
	// Featured post
	register_post_meta('post', '_medici_featured', [
		'type' => 'boolean',
		'single' => true,
		'default' => false,
		'show_in_rest' => true,
	]);

	// Reading time override
	register_post_meta('post', '_medici_reading_time', [
		'type' => 'integer',
		'single' => true,
		'show_in_rest' => true,
	]);

	// Post views
	register_post_meta('post', '_medici_views', [
		'type' => 'integer',
		'single' => true,
		'default' => 0,
		'show_in_rest' => true,
	]);
}
add_action('init', 'register_blog_meta', 1);
```

**Query для Blog Posts:**

```php
// Featured posts
$featured_query = new WP_Query([
	'post_type' => 'post',
	'posts_per_page' => 6,
	'meta_key' => '_medici_featured',
	'meta_value' => '1',
	'orderby' => 'date',
	'order' => 'DESC',
]);

// Category posts
$category_query = new WP_Query([
	'post_type' => 'post',
	'posts_per_page' => 10,
	'category_name' => 'кейси', // slug
	'orderby' => 'date',
]);

// Popular posts (за переглядами)
$popular_query = new WP_Query([
	'post_type' => 'post',
	'posts_per_page' => 5,
	'meta_key' => '_medici_views',
	'orderby' => 'meta_value_num',
	'order' => 'DESC',
]);
```

**Коментарі (Theme Support):**

```php
// Увімкнення коментарів
add_theme_support('post-thumbnails');
add_theme_support('automatic-feed-links');
add_theme_support('title-tag');
add_theme_support('html5', ['comment-list', 'comment-form', 'search-form', 'gallery', 'caption']);

// Кастомізація форми коментарів
function custom_comment_form($args)
{
	$args['comment_field'] =
		'<p class="comment-form-comment">
        <label for="comment">' .
		__('Коментар', 'medici.agency') .
		'</label>
        <textarea id="comment" name="comment" required></textarea>
    </p>';
	return $args;
}
add_filter('comment_form_defaults', 'custom_comment_form');
```

**Blog Archive Title:**

```php
function custom_archive_title($title)
{
	if (is_category()) {
		$title = single_cat_title('', false);
	} elseif (is_tag()) {
		$title = single_tag_title('', false);
	} elseif (is_author()) {
		$title = get_the_author();
	}
	return $title;
}
add_filter('get_the_archive_title', 'custom_archive_title');
```

### 16. Форми та Newsletter

**Newsletter Subscription:**

```php
function ajax_newsletter_subscribe()
{
	// Verify nonce
	check_ajax_referer('medici_newsletter_nonce', 'nonce');

	// Sanitize input
	$email = sanitize_email($_POST['email']);

	if (!is_email($email)) {
		wp_send_json_error([
			'message' => __('Невірний email', 'medici.agency'),
		]);
	}

	// Зберегти в базі або відправити на email-сервіс
	$subscribers = get_option('medici_subscribers', []);

	if (in_array($email, $subscribers)) {
		wp_send_json_error([
			'message' => __('Ви вже підписані', 'medici.agency'),
		]);
	}

	$subscribers[] = $email;
	update_option('medici_subscribers', $subscribers);

	wp_send_json_success([
		'message' => __('Дякуємо за підписку!', 'medici.agency'),
	]);
}
add_action('wp_ajax_medici_newsletter_subscribe', 'ajax_newsletter_subscribe');
add_action('wp_ajax_nopriv_medici_newsletter_subscribe', 'ajax_newsletter_subscribe');
```

**Contact Form:**

```php
function ajax_contact_form()
{
	check_ajax_referer('medici_contact_nonce', 'nonce');

	// Sanitize
	$name = sanitize_text_field($_POST['name']);
	$email = sanitize_email($_POST['email']);
	$message = sanitize_textarea_field($_POST['message']);

	// Validate
	if (empty($name) || !is_email($email) || empty($message)) {
		wp_send_json_error([
			'message' => __('Всі поля обов\'язкові', 'medici.agency'),
		]);
	}

	// Send email
	$to = get_option('admin_email');
	$subject = sprintf(__('Новий запит від %s', 'medici.agency'), $name);
	$body = sprintf("Ім'я: %s\nEmail: %s\n\nПовідомлення:\n%s", $name, $email, $message);

	$sent = wp_mail($to, $subject, $body, ['Reply-To: ' . $email]);

	if ($sent) {
		wp_send_json_success([
			'message' => __('Повідомлення надіслано!', 'medici.agency'),
		]);
	} else {
		wp_send_json_error([
			'message' => __('Помилка відправки', 'medici.agency'),
		]);
	}
}
add_action('wp_ajax_medici_contact_form', 'ajax_contact_form');
add_action('wp_ajax_nopriv_medici_contact_form', 'ajax_contact_form');
```

**ВАЖЛИВО:**

- Завжди використовуйте `check_ajax_referer()` для AJAX
- Sanitize всі input: `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()`
- Validate email через `is_email()`
- Використовуйте `wp_send_json_success()` та `wp_send_json_error()`

### 17. Медіафайли та Оптимізація

**Image Sizes Registration:**

```php
function register_image_sizes()
{
	// Blog thumbnail
	add_image_size('blog-thumb', 400, 300, true); // crop

	// Blog featured
	add_image_size('blog-featured', 800, 600, true);

	// Blog hero
	add_image_size('blog-hero', 1200, 800, true);

	// Blog full (no crop)
	add_image_size('blog-full', 1920, 9999, false);
}
add_action('after_setup_theme', 'register_image_sizes');
```

**Media з Lazy Loading:**

```json
{
	"uniqueId": "bcd99dc6",
	"tagName": "img",
	"htmlAttributes": {
		"src": "image.jpg",
		"alt": "Опис зображення",
		"loading": "lazy",
		"decoding": "async",
		"width": "800",
		"height": "600"
	}
}
```

**Hero Image (High Priority):**

```json
{
	"uniqueId": "hero-img",
	"htmlAttributes": {
		"loading": "eager",
		"fetchpriority": "high",
		"decoding": "async"
	}
}
```

**Responsive Images:**

```php
// Генерація srcset
$image_id = get_post_thumbnail_id();
echo wp_get_attachment_image($image_id, 'large', false, [
	'loading' => 'lazy',
	'class' => 'responsive-image',
]);
```

**SVG Support:**

```php
function enable_svg_upload($mimes)
{
	$mimes['svg'] = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'enable_svg_upload');

// Sanitize SVG (ВАЖЛИВО для безпеки!)
function sanitize_svg($file)
{
	if ($file['type'] === 'image/svg+xml') {
		// Додаткова перевірка та sanitization
		$svg_content = file_get_contents($file['tmp_name']);

		// Видалити <script> та event handlers
		$svg_content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svg_content);
		$svg_content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $svg_content);

		file_put_contents($file['tmp_name'], $svg_content);
	}
	return $file;
}
add_filter('wp_handle_upload_prefilter', 'sanitize_svg');
```

**Image Optimization:**

```php
// Disable big image threshold (WordPress 5.3+)
add_filter('big_image_size_threshold', '__return_false');

// JPEG quality
add_filter('jpeg_quality', function () {
	return 85; // 85% якість (оптимально)
});

// WebP support
add_filter('wp_get_attachment_image_src', 'convert_to_webp', 10, 4);
function convert_to_webp($image, $attachment_id, $size, $icon)
{
	if (empty($image[0])) {
		return $image;
	}

	$webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image[0]);

	if (file_exists(str_replace(site_url(), ABSPATH, $webp_path))) {
		$image[0] = $webp_path;
	}

	return $image;
}
```

**ВАЖЛИВО:**

- Завжди додавайте `alt` для accessibility та SEO
- Використовуйте `loading="lazy"` для зображень нижче fold
- Hero image має `loading="eager"` та `fetchpriority="high"`
- Додавайте `width` та `height` для CLS optimization
- SVG файли мають бути sanitized для безпеки

### 18. Overlay Panels

**Gradient Overlay Pattern:**

```json
{
	"uniqueId": "0e8f5083",
	"tagName": "div",
	"styles": {
		"position": "absolute",
		"inset": "0", // Shorthand для top/right/bottom/left: 0
		"background": "linear-gradient(180deg, transparent 30%, rgba(15, 23, 42, 0.95) 100%)",
		"zIndex": "1",
		"pointerEvents": "none" // Важливо для overlay
	},
	"metadata": {
		"name": "Gradient Overlay"
	}
}
```

**Multi-Layer Background:**

```json
{
	"styles": {
		"backgroundImage": "radial-gradient(rgba(102, 70, 232, 0.2) 0%, rgba(255, 255, 255, 0) 50%), radial-gradient(rgba(235, 236, 242, 0.94) 0%, var(\\u002d\\u002dbase-3) 80%), url(https://example.com/pattern.png)",
		"backgroundSize": "cover, cover, 600px",
		"backgroundRepeat": "no-repeat, no-repeat, repeat",
		"backgroundPosition": "center, center, center",
		"backgroundBlendMode": "normal, normal, ",
		"backgroundAttachment": "fixed, fixed, fixed"
	}
}
```

**Z-Index Layers (стандарт):**

- Background image/gradient: `zIndex: "0"` (або без z-index)
- First overlay layer: `zIndex: "1"`
- Content layer: `zIndex: "2"` (або більше)
- header: `zIndex: "1000"` (якщо використовується)

**Position Shorthand:**

```json
// ✅ ПРАВИЛЬНО - використовуй inset
{
  "inset": "0"              // Замінює top/right/bottom/left: 0
}

// ❌ СТАРИЙ СПОСІБ (працює, але більше коду)
{
  "top": "0px",
  "right": "0px",
  "bottom": "0px",
  "left": "0px"
}
```

**Складні Радіальні Градієнти:**

```json
{
	"uniqueId": "b2c3d4e5",
	"tagName": "div",
	"styles": {
		"position": "absolute",
		"inset": "0",
		"width": "60%",
		"height": "200%",
		"background": "radial-gradient(ellipse, rgba(37, 99, 235, 0.15) 0%, transparent 70%)",
		"pointerEvents": "none",
		"zIndex": "0"
	}
}
```

**ВАЖЛИВО про pointerEvents:**

```json
// ✅ ЗАВЖДИ додавай pointerEvents: "none" для overlay
{
  "pointerEvents": "none"  // Дозволяє кліки через overlay до контенту
}

// ❌ БЕЗ pointerEvents - кліки не працюватимуть
{
  "position": "absolute",
  "inset": "0"
  // pointerEvents відсутній - ПОМИЛКА!
}
```

**Приклад повної структури з overlay:**

```html
<!-- Container -->
<div class="gb-element-container" style="position:relative;">
	<!-- Background gradient (z-index: 0) -->
	<div
		class="gb-element-bg"
		style="position:absolute;inset:0;background:radial-gradient(...);z-index:0;pointer-events:none;"
	></div>

	<!-- Gradient overlay (z-index: 1) -->
	<div
		class="gb-element-overlay"
		style="position:absolute;inset:0;background:linear-gradient(...);z-index:1;pointer-events:none;"
	></div>

	<!-- Content (z-index: 2) -->
	<div class="gb-element-content" style="position:relative;z-index:2;">
		<!-- Interactive content тут -->
	</div>
</div>
```

### 19. Dynamic Content - Query & Looper

**Query Block (WP_Query):**

```json
{
	"uniqueId": "26a52469",
	"tagName": "div",
	"query": {
		"post_type": "post",
		"posts_per_page": 6,
		"order": "DESC",
		"orderby": "date",
		"meta_key": "_medici_featured",
		"meta_value": "1"
	},
	"metadata": {
		"categories": ["medici-blog"],
		"patternName": "medici/blog-featured",
		"name": "Featured Blog Posts"
	}
}
```

**Looper Block:**

```json
{
	"uniqueId": "a1cf895c",
	"tagName": "div",
	"styles": {
		"display": "grid",
		"gridTemplateColumns": "repeat(auto-fit, minmax(340px, 1fr))",
		"gap": "2rem"
	}
}
```

**Dynamic Tags (ВАЖЛИВО!):**

```
{{post_title}}                                    // Заголовок
{{post_permalink}}                                // URL посту
{{post_excerpt length:120}}                       // Excerpt (120 символів)
{{post_date format:j F Y}}                        // Дата: 24 листопада 2025
{{post_terms taxonomy:category}}                  // Категорії
{{post_meta key:_medici_reading_time}}            // Custom meta field
{{post_meta key:_medici_views}}                   // Перегляди
{{featured_image key:url size:large}}             // Featured image URL
{{featured_image key:url size:medium_large}}      // Medium large size
```

**Media з Dynamic Content:**

```json
{
	"uniqueId": "bcd99dc6",
	"tagName": "img",
	"mediaType": "image",
	"dynamicImage": "{{featured_image key:url size:large}}",
	"alt": "{{post_title}}",
	"styles": {
		"width": "100%",
		"height": "100%",
		"objectFit": "cover"
	}
}
```

### 20. Structured Styles (GB 2.x)

**GenerateBlocks 2.x підтримує нові structured objects в styles:**

**Sizing Object:**

```json
{
	"styles": {
		"sizing": {
			"width": "100%",
			"height": "400px",
			"maxWidth": "1200px",
			"objectFit": "cover"
		}
	}
}
```

**Spacing Object:**

```json
{
	"styles": {
		"spacing": {
			"padding": {
				"top": "2rem",
				"right": "2rem",
				"bottom": "2rem",
				"left": "2rem"
			},
			"margin": {
				"left": "auto",
				"right": "auto"
			},
			"gap": "1.5rem"
		}
	}
}
```

**Typography Object:**

```json
{
	"styles": {
		"typography": {
			"fontSize": "0.875rem",
			"fontWeight": "600",
			"lineHeight": "1.6"
		}
	}
}
```

**Borders Object:**

```json
{
	"styles": {
		"borders": {
			"radius": {
				"topLeft": "20px",
				"topRight": "20px",
				"bottomLeft": "20px",
				"bottomRight": "20px"
			}
		}
	}
}
```

**Display Object:**

```json
{
	"styles": {
		"display": {
			"type": "flex",
			"direction": "column",
			"justifyContent": "center",
			"alignItems": "center"
		}
	}
}
```

**Color Object:**

```json
{
	"styles": {
		"color": {
			"text": "var(\\u002d\\u002daccent, #2563eb)",
			"background": "rgba(37, 99, 235, 0.1)"
		}
	}
}
```

**ВАЖЛИВО:**

- Це НОВА структура GB 2.x - використовуйте structured objects коли можливо
- Стара flat структура (`"fontSize": "1rem"`) також працює
- Structured objects кращі для організації та читабельності
- Fallback values в CSS variables: `var(\\u002d\\u002daccent, #2563eb)`

---

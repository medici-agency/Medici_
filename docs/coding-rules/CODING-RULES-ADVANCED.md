## 🚀 ПРОДВИНУТІ ФУНКЦІЇ GENERATEPRESS & GENERATEBLOCKS PRO

## 📑 ЗМІСТ

- [21. Реєстрація власних Dynamic Tags у GenerateBlocks 2.x](#21-реєстрація-власних-dynamic-tags-у-generateblocks-2x)
- [22. Query Block Pro: Продвинуті запити до Post Meta та Options](#22-query-block-pro-продвинуті-запити-до-post-meta-та-options)
- [23. Conditions API у GenerateBlocks 2.4+](#23-conditions-api-у-generateblocks-24)
- [24. Custom Selectors у GenerateBlocks Pro](#24-custom-selectors-у-generateblocks-pro)
- [25. Container Queries для компонентного дизайну](#25-container-queries-для-компонентного-дизайну)
- [26. Ефекти та анімації у GenerateBlocks 2.x](#26-ефекти-та-анімації-у-generateblocks-2x)
- [27. Інтеграція з WooCommerce через Dynamic Tags](#27-інтеграція-з-woocommerce-через-dynamic-tags)
- [28. Perfmatters + GeneratePress: Продвинута оптимізація](#28-perfmatters--generatepress-продвинута-оптимізація)
- [29. Full Site Editing (FSE) vs Elements: Вибір архітектури](#29-full-site-editing-fse-vs-elements-вибір-архітектури)
- [30. Performance Metrics для медичних сайтів](#30-performance-metrics-для-медичних-сайтів)
- [31. Архітектура Child Theme для агенції](#31-архітектура-child-theme-для-агенції)
- [32. Git Versioning + GeneratePress Configuration](#32-git-versioning--generatepress-configuration)
- [33. REST API & Headless Development](#33-rest-api--headless-development)
- [34. Модуль "Блог" у GeneratePress: Експертний гайд](#34-модуль-блог-у-generatepress-експертний-гайд)
  - [Активація та базова конфігурація](#активація-та-базова-конфігурація)
  - [Конфігурація Layout і Content](#конфігурація-layout-і-content)
  - [Налаштування Excerpt Length через PHP](#налаштування-excerpt-length-через-php)
  - [Query Block для динамічного контролю blog layout](#query-block-для-динамічного-контролю-blog-layout)
  - [Sticky Posts у блозі](#sticky-posts-у-блозі)
  - [Пагінація: SEO та UX](#пагінація-seo-та-ux)
  - [Featured Image Optimization](#featured-image-optimization)
  - [Категорії та Теги у Блозі](#категорії-та-теги-у-блозі)
  - [Related Posts Section](#related-posts-section)
  - [Performance: Blog Archive Optimization](#performance-blog-archive-optimization)
  - [Архівні сторінки: Category, Tag, Author, Search](#архівні-сторінки-category-tag-author-search)
  - [Медична специфіка: Privacy & Compliance](#медична-специфіка-privacy--compliance)
  - [Внутрішнє посилання для SEO](#внутрішнє-посилання-для-seo)
  - [Checksum: Рекомендована Setup](#checksum-рекомендована-setup)
- [35. Рекомендований стек для експертів](#35-рекомендований-стек-для-експертів)

---

### 21. Реєстрація власних Dynamic Tags у GenerateBlocks 2.x

**Однією з найпотужніших функцій GenerateBlocks 2.x є можливість реєстрації кастомних динамічних тегів через API.**

**⚠️ ПРИМІТКА:** Офіційної документації поки бракує, але ви можете використовувати клас `GenerateBlocks_Register_Dynamic_Tag`.

**Приклад реєстрації кастомного тегу:**

```php
<?php
/**
 * Реєстрація кастомних динамічних тегів
 * Додайте до functions.php або окремого модуля
 */
function medici_register_dynamic_tags()
{
	if (!class_exists('GenerateBlocks_Register_Dynamic_Tag')) {
		return;
	}

	// Приклад: Ціна товару з податком
	new GenerateBlocks_Register_Dynamic_Tag([
		'title' => __('Custom Product Price', 'medici.agency'),
		'tag' => 'product_price_custom',
		'type' => 'post', // Групування: 'post', 'author', 'elements', 'woocommerce'
		'supports' => ['source'], // Дозволяє preview в редакторі
		'options' => [
			'include_tax' => [
				'type' => 'checkbox',
				'label' => __('Include Tax', 'medici.agency'),
				'default' => true,
			],
			'format' => [
				'type' => 'select',
				'label' => __('Price Format', 'medici.agency'),
				'default' => 'formatted',
				'options' => [
					'formatted' => __('Formatted (€25.00)', 'medici.agency'),
					'raw' => __('Raw (25)', 'medici.agency'),
				],
			],
		],
		'return' => 'medici_product_price_callback', // Callback функція
	]);
}
add_action('init', 'medici_register_dynamic_tags');

/**
 * Callback для динамічного тегу
 *
 * @param array $options - значення з block settings (format, include_tax)
 * @param array $block - дані поточного блока
 * @param array $instance - глобальна інформація про сторінку
 * @return string
 */
function medici_product_price_callback($options, $block, $instance)
{
	if (!is_singular('product')) {
		return '';
	}

	$product = wc_get_product();
	if (!$product) {
		return '';
	}

	$price = $options['include_tax']
		? wc_get_price_including_tax($product)
		: wc_get_price_excluding_tax($product);

	if ($options['format'] === 'raw') {
		return number_format($price, 2);
	}

	return '<span class="price">' . wc_price($price) . '</span>';
}
```

**Основні параметри API:**

| Параметр   | Опис                                         | Значення                                    |
| ---------- | -------------------------------------------- | ------------------------------------------- |
| `title`    | Назва у селекторі редактора                  | String                                      |
| `tag`      | Унікальний ідентифікатор тегу (snake_case)   | String                                      |
| `type`     | Групування тегів                             | 'post', 'author', 'elements', 'woocommerce' |
| `supports` | Додаткові фічі                               | ['source'], ['image-size']                  |
| `options`  | Конфігураційні опції UI                      | Array                                       |
| `return`   | Callback-функція або ['ClassName', 'method'] | Callable                                    |

**Типи опцій:**

- `text` - текстове поле
- `select` - випадаючий список
- `checkbox` - чекбокс
- `number` - числове поле

**Автоматична обробка output:**

Використовуйте `GenerateBlocks_Dynamic_Tag_Callbacks::output()` для автоматичної обробки:

- `truncate` - обрізання тексту
- `replace` - заміна підстрок
- `trim` - видалення пробілів
- `case` - перетворення регістру
- `wpautop` - автоматичні параграфи
- `link` - обгортання в посилання

---

### 22. Query Block Pro: Продвинуті запити до Post Meta та Options

**GenerateBlocks Pro дозволяє створювати Query типу Post Meta для виведення даних з ACF Repeater полів та Option для даних з таблиці wp_options.**

**Три типи Query:**

1. **Posts** - стандартні post type, з модифікацією через `pre_get_posts`
2. **Post Meta** - вилучення вкладених ACF Repeater полів
3. **Options** - дані з таблиці wp_options (ACF Options Pages)

**Приклад 1: Модифікація Query через фільтр**

```php
<?php
/**
 * Фільтрація Query Loop за ACF Options
 * Коли динамічний тег {{option key:filtro_por_etiqueta}}
 * не спрацьовує в полі Terms Query Block
 */
add_filter(
	'generateblocks_query_loop_args',
	function ($args, $atts) {
		// Перевірка класу контейнера для конкретної Query Loop
		if (strpos($atts['className'] ?? '', 'gb-loop-featured-posts') === false) {
			return $args;
		}

		// Отримання значення з ACF Options Page
		$tag_id = get_field('featured_post_category', 'option');

		if ($tag_id) {
			// Заміна або додання до існуючих параметрів
			$args['tax_query'] = [
				[
					'taxonomy' => 'category',
					'field' => 'term_id',
					'terms' => [intval($tag_id)],
					'operator' => 'IN',
				],
			];
		}

		return $args;
	},
	10,
	2,
);
```

**Приклад 2: Модифікація успадкованого Query для архівів**

```php
<?php
/**
 * Сортування архіву подій за датою події (ACF поле)
 */
add_action('pre_get_posts', function ($query) {
	if (!is_admin() && $query->is_main_query() && is_post_type_archive('event')) {
		$query->set('meta_key', 'event_date');
		$query->set('orderby', 'meta_value');
		$query->set('order', 'ASC');
	}
});
```

**Приклад 3: Доступ до вкладених ACF Repeater даних**

Для доступу до вкладених даних ACF Repeater використовуйте синтаксис динамічних тегів з dot notation:

```text
{{loop_item key:speakers_name}}
{{loop_item key:social_media_platform.label}}
{{loop_item key:gallery.0.id}}
{{loop_item key:speakers.0.social_media.platform}}
{{loop_item key:speakers.0.social_media.url}}
```

**Структура ACF Repeater:**

```text
repeater (speakers)
  ├─ name
  ├─ social_media (object)
  │  ├─ platform
  │  └─ url
  └─ gallery (array)
     └─ id
```

---

### 23. Conditions API у GenerateBlocks 2.4+

**GenerateBlocks Pro 2.4 інтегрував систему Conditions для всіх блоків без необхідності в додаткових плагінах.**

**Типи умов:**

| Тип              | Опис                                      |
| ---------------- | ----------------------------------------- |
| **User**         | Залогований користувач, роль, метадані    |
| **Post**         | Тип допису, ID, терміни (категорія, теги) |
| **Date/Time**    | Залежно від часу або дати                 |
| **Device**       | Екран, браузер, мобільний пристрій        |
| **Query String** | Параметри URL (utm параметри, фільтри)    |
| **Custom PHP**   | Власна логіка через фільтр                |

**Приклад: Додавання кастомної умови для VIP користувачів**

```php
<?php
/**
 * Реєстрація кастомної умови
 */
add_filter('generateblocks_conditions_types', function ($types) {
	$types['custom_vip_user'] = [
		'label' => __('Is VIP Customer', 'medici.agency'),
		'group' => 'user', // Групування у редакторі
		'render' => 'medici_is_vip_customer', // Callback функція
	];

	return $types;
});

/**
 * Callback для перевірки VIP статусу
 *
 * @param mixed $condition_value - значення з Conditions panel
 * @param array $rule - дані правила
 * @return bool
 */
function medici_is_vip_customer($condition_value, $rule)
{
	if (!is_user_logged_in()) {
		return false;
	}

	$user = wp_get_current_user();

	// Перевірка кастомного user meta
	$vip_level = get_user_meta($user->ID, 'customer_vip_level', true);

	return intval($vip_level) >= intval($condition_value);
}
```

**Переваги Conditions API vs Conditional Blocks плагіна:**

✅ **Native серверна обробка** (без JavaScript на клієнтові)
✅ **Меньше overhead** - немає додаткових плагінів
✅ **Краща SEO** - контент все одно рендериться на сервері
✅ **Менша CLS** - немає стрибків контенту

---

### 24. Custom Selectors у GenerateBlocks Pro

**GenerateBlocks автоматично призначає унікальний CSS-клас кожному блоку (наприклад, `.gb-element-0d3db716`).**

**У Pro версії ви можете створювати custom селектори з compound та descendant комбінаторами.**

**Типи селекторів:**

**1. Compound selector (hover state):**

```css
.gb-element-9901dac8:hover {
	background: var(--accent);
	transform: translateY(-4px);
}
```

**2. Descendant selector (inner paragraphs):**

```css
.gb-element-9901dac8 p {
	color: var(--text-secondary);
	line-height: 1.6;
}
```

**3. Complex selector з combinator:**

```css
.gb-accordion-item.is-open > a.post-link {
	font-weight: 700;
	color: var(--accent);
}
```

**4. Pseudo-елементи:**

```css
.gb-element-9901dac8::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	width: 4px;
	height: 100%;
	background: var(--accent);
}

.gb-element-9901dac8::after {
	content: '→';
	margin-left: 0.5rem;
}
```

**Створення ::before контенту:**

Додайте `content` property через Styles panel → Effects → це дозволяє створювати декоративні елементи без додаткової розмітки.

**У редакторі:**

```text
Styles → Custom Selectors → Add Selector
Selector: &::before
Content: '' (обов'язково!)
Position: absolute
```

---

### 25. Container Queries для компонентного дизайну

**GenerateBlocks Pro підтримує @container queries додатково до стандартних media queries.**

**Container Queries дозволяють стилізувати елементи залежно від розміру їх контейнера, а не viewport.**

**Приклад використання:**

```css
/* Встановіть container у батьківському блоці */
.card-wrapper {
	container-type: inline-size;
	container-name: card;
}

/* Стилізуйте дочірні елементи залежно від розміру контейнера */
@container card (min-width: 400px) {
	.card-content {
		flex-direction: row;
		gap: 2rem;
	}

	.card-image {
		width: 40%;
	}
}

@container card (max-width: 399px) {
	.card-content {
		flex-direction: column;
		gap: 1rem;
	}

	.card-image {
		width: 100%;
	}
}
```

**У GenerateBlocks редакторі:**

```text
Container Block → Styles → Custom CSS
container-type: inline-size;
container-name: my-container;

Card Block → Styles → Responsive Controls
Breakpoint: Container (@container)
Container Name: my-container
Min-width: 400px
```

**Для приховування блоків на кастомних breakpoints:**

Створюйте Global Styles classes з відповідними container query правилами:

```css
/* Global Style: hide-on-small-container */
@container (max-width: 300px) {
	.hide-on-small-container {
		display: none;
	}
}
```

---

### 26. Ефекти та анімації у GenerateBlocks 2.x

**GenerateBlocks 2.x надає візуальний інтерфейс для hover/focus ефектів без ручного CSS.**

**Transition Workflow:**

1. Створіть hover selector (`&:hover`)
2. Встановіть стилі для hover стану (background, box-shadow, transform)
3. Поверніться до main selector
4. Додайте transition у Effects panel (property, duration, timing-function)

**Приклад: Button з lift ефектом**

```json
{
	"styles": {
		"color": {
			"background": "var(\\u002d\\u002daccent)"
		},
		"spacing": {
			"padding": {
				"top": "1rem",
				"right": "2rem",
				"bottom": "1rem",
				"left": "2rem"
			}
		},
		"borders": {
			"radius": { "topLeft": "8px", "topRight": "8px", "bottomLeft": "8px", "bottomRight": "8px" }
		},
		"effects": {
			"transition": {
				"property": "all",
				"duration": "0.3s",
				"timingFunction": "ease-out"
			}
		}
	},
	"customSelectors": {
		"&:hover": {
			"color": {
				"background": "var(\\u002d\\u002daccentDark)"
			},
			"effects": {
				"transform": {
					"translateY": "-4px"
				},
				"boxShadow": "0 8px 16px rgba(0, 0, 0, 0.15)"
			}
		}
	}
}
```

**Transform ефекти:**

| Ефект        | Використання                        |
| ------------ | ----------------------------------- |
| `translateY` | "Lift" ефект карток (-4px на hover) |
| `scale`      | Zoom ефект (1.05 на hover)          |
| `rotate`     | Обертання іконок (45deg, 90deg)     |

**Stacking transitions (окремі transitions для різних properties):**

```json
{
	"effects": {
		"transition": {
			"boxShadow": {
				"duration": "0.25s",
				"timingFunction": "ease-out"
			},
			"transform": {
				"duration": "1s",
				"timingFunction": "cubic-bezier(0.4, 0, 0.2, 1)"
			}
		}
	}
}
```

---

### 27. Інтеграція з WooCommerce через Dynamic Tags

**Для виведення WooCommerce даних використовуйте фільтр `generateblocks_dynamic_tag_output`.**

**Приклад: Ціна товару з оригінальною ціною (закресленою)**

```php
<?php
/**
 * Модифікація виведення динамічного тегу для WooCommerce ціни
 */
add_filter(
	'generateblocks_dynamic_tag_output',
	function ($output, $options) {
		// Перевірка, що це запит для WooCommerce продукту
		if (!isset($options['products-query-attributes']) || !is_numeric($output)) {
			return $output;
		}

		$product = wc_get_product(esc_html($output));
		if (!$product) {
			return $output;
		}

		$price_html =
			'<span class="price">' . wc_price(wc_get_price_including_tax($product)) . '</span>';

		// Якщо товар на розпродажі, додати оригінальну ціну
		if ($product->is_on_sale()) {
			$price_html = '<del>' . wc_price($product->get_regular_price()) . '</del> ' . $price_html;
		}

		return $price_html;
	},
	10,
	2,
);
```

**Переваги:**

- ❌ Без WooCommerce shortcode overhead
- ✅ Чистіший HTML
- ✅ Легший для SEO
- ✅ Повний контроль над виведенням

---

### 28. Perfmatters + GeneratePress: Продвинута оптимізація

**Для експертного рівня оптимізації комбінуйте GeneratePress з Perfmatters.**

**Рекомендовані налаштування:**

**1. CSS Print Method:**

```text
GeneratePress → Customizer → General → CSS Print Method
Значення: External File (для сумісності з Remove Unused CSS)
```

**2. Exclude GeneratePress scripts з Delay JS:**

```text
Perfmatters → JavaScript → Delay JavaScript
Exclude від затримки:
- menu.min.js
- gb-off-canvas.js
- navigation.min.js (якщо використовується)
```

**Результат:** LCP покращується на 0.3-0.8s, INP покращується на 15-25ms.

**3. Preload Critical Images:**

```text
Perfmatters → Preload → Add Preload
Type: Image
URL: /wp-content/uploads/hero-image.webp
As: image
Fetchpriority: high
```

**4. Script Manager:**

Відключайте WooCommerce скрипти на не-product сторінках:

```text
Perfmatters → Script Manager
Disable on Posts/Pages:
- woocommerce-general.js
- cart-fragments.js
- wc-add-to-cart.js
```

**5. Filters для кастомізації:**

GeneratePress має **150+ filters**, Perfmatters — **50+ filters** для кастомізації будь-якого аспекту.

**Результат:** PageSpeed 95-100 для production сайтів.

---

### 29. Full Site Editing (FSE) vs Elements: Вибір архітектури

**GeneratePress підтримує два підходи для кастомізації: Elements (hook-based) та FSE (template-based).**

**Коли використовувати Elements:**

✅ Глобальні header/footer
✅ Hook-based sections (перед контентом, після контенту)
✅ Template-specific варіації (наприклад, інший header для WooCommerce)

**Приклад Elements:**

```text
Appearance → Elements → Create New

Element Type: Header
Location: Hook
Hook: generate_after_header_end
Condition: Post Type = product (тільки на товарах)
Priority: 10
```

**Коли використовувати FSE:**

✅ Шаблони архівів (archive.html, category.html)
✅ Глобальні шаблони (404, search)
✅ Single post шаблони з динамічним контентом

**FSE переважніший за Elements при наявності багатьох варіацій шаблонів**, оскільки дозволяє template-specific Conditions.

**Рекомендація:**

- **Elements** для глобальних секцій та hook-based модифікацій
- **FSE** для повноцінних шаблонів з багатьма варіаціями

---

### 30. Performance Metrics для медичних сайтів

**Для медичних/healthcare проектів перформанс критичніший через регуляції та довіру користувачів.**

**Core Web Vitals цілі для медичних сайтів:**

| Метрика | Стандарт Google | Медичні сайти | Примітка                                                    |
| ------- | --------------- | ------------- | ----------------------------------------------------------- |
| **LCP** | < 2.5s          | **< 2.0s**    | Медичні користувачі мають низьку толерантність до зависання |
| **INP** | < 200ms         | **< 100ms**   | Замінює FID з 2024 року                                     |
| **CLS** | < 0.1           | **< 0.05**    | Критично для форм запису на прийом                          |

**Оптимізація для медичних сайтів:**

**1. Preload критичних зображень:**

```xml
<link rel="preload" as="image" href="/logo.svg" fetchpriority="high">
<link rel="preload" as="image" href="/hero-doctor.webp" fetchpriority="high">
```

**2. Запобігання CLS на таблицях прайсів:**

```css
.price-table {
	width: 100%;
	min-height: 400px; /* Зарезервуйте місце */
	contain: layout;
}

.price-table tr {
	height: 60px; /* Фіксована висота рядків */
}
```

**3. Уникайте динамічних модалів при завантаженні:**

❌ **Неправильно:**

```javascript
// Modal з'являється через 2 секунди - вносить CLS
setTimeout(() => {
	modal.style.display = 'block';
}, 2000);
```

✅ **Правильно:**

```css
/* Резервуйте місце заздалегідь */
.modal {
	display: none; /* Приховано, але займає місце в DOM */
	opacity: 0;
	visibility: hidden;
	position: fixed;
}

.modal.active {
	opacity: 1;
	visibility: visible;
}
```

**4. Оптимізація форм:**

```css
/* Фіксовані розміри полів форми */
.appointment-form input,
.appointment-form select {
	height: 48px; /* Фіксована висота */
	width: 100%;
}

.appointment-form button {
	height: 56px; /* Фіксована висота кнопки */
}
```

---

### 31. Архітектура Child Theme для агенції

**Рекомендована структура для multi-client проектів.**

**Файлова структура:**

```text
generatepress-child/
├── style.css              (header із theme information)
├── functions.php          (hooks, filters, custom functions)
├── inc/                   (модулі)
│   ├── custom-blocks.php
│   ├── dynamic-tags.php
│   ├── hooks.php
│   ├── conditions.php
│   └── helpers.php
├── patterns/              (Reusable block patterns)
│   ├── hero.php
│   ├── services.php
│   └── testimonials.php
├── templates/             (FSE шаблони)
│   ├── archive.html
│   └── single.html
└── assets/
    ├── css/
    │   └── custom.css     (Global styles, CSS vars)
    └── js/
        └── main.js        (Enqueued separately)
```

**functions.php структура:**

```php
<?php
/**
 * GeneratePress Child Theme Functions
 *
 * @package GeneratePress Child
 * @parent GeneratePress
 * @version 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
	exit();
}

// Theme version constant
define('CHILD_THEME_VERSION', '1.0.0');

/**
 * Enqueue parent theme stylesheet
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		// Parent theme styles
		wp_enqueue_style(
			'generatepress',
			get_template_directory_uri() . '/style.css',
			[],
			wp_get_theme()->parent()->get('Version'),
		);

		// Child theme styles
		wp_enqueue_style(
			'generatepress-child',
			get_stylesheet_uri(),
			['generatepress'],
			CHILD_THEME_VERSION,
		);
	},
	15,
);

/**
 * Include custom modules
 */
require_once get_stylesheet_directory() . '/inc/dynamic-tags.php';
require_once get_stylesheet_directory() . '/inc/hooks.php';
require_once get_stylesheet_directory() . '/inc/custom-blocks.php';
require_once get_stylesheet_directory() . '/inc/conditions.php';
```

---

### 32. Git Versioning + GeneratePress Configuration

**Best practice: Версіонуйте GeneratePress Elements і GenerateBlocks контент як код через export/import.**

**WP-CLI команди для експорту:**

```bash
# Експорт Elements
wp generatepress export-elements --format=json > elements-backup.json

# Експорт GenerateBlocks patterns
wp generateblocks export-blocks --format=json > blocks-backup.json

# Версіонування через Git
git add elements-backup.json blocks-backup.json
git commit -m "feat: Updated Elements and Block patterns for v2.0"
```

**Переваги:**
✅ Команди можуть безпечно працювати на різних гілках
✅ Немає конфліктів в БД
✅ History змін через Git
✅ Легке відкочування (rollback)

**Git workflow для команди:**

```bash
# Developer 1: Feature branch
git checkout -b feature/new-header
# Змінює Elements через WordPress admin
wp generatepress export-elements > elements.json
git add elements.json
git commit -m "feat: New header design"
git push origin feature/new-header

# Developer 2: Import на локальному середовищі
git pull origin feature/new-header
wp generatepress import-elements elements.json
# Перевіряє в WordPress admin
```

---

### 33. REST API & Headless Development

**GenerateBlocks 2.0+ підтримує REST API для headless архітектури.**

**Отримання блоків через REST API:**

```bash
# Отримання всіх блоків сторінки
GET /wp-json/wp/v2/pages/123?_embed=true

# Фільтрація Query Loop даних
GET /wp-json/wp/v2/posts?categories=5&per_page=12&orderby=date
```

**Frontend інтеграція (React/Vue):**

```javascript
/**
 * Отримання блогових постів для headless frontend
 */
const queryBlocks = async (query) => {
	try {
		const response = await fetch(`/wp-json/wp/v2/posts?search=${query}&_embed=true`);
		const posts = await response.json();

		return posts.map((post) => ({
			id: post.id,
			title: post.title.rendered,
			excerpt: post.excerpt.rendered,
			image: post._embedded['wp:featuredmedia']?.[0]?.source_url,
			author: post._embedded['author']?.[0]?.name,
			date: new Date(post.date).toLocaleDateString('uk-UA'),
			link: post.link,
		}));
	} catch (error) {
		console.error('Failed to fetch posts:', error);
		return [];
	}
};

// Використання
const posts = await queryBlocks('медична допомога');
```

**Кастомні REST endpoints для GenerateBlocks:**

```php
<?php
/**
 * Реєстрація кастомного REST endpoint для блогу
 */
add_action('rest_api_init', function () {
	register_rest_route('medici/v1', '/blog-stats', [
		'methods' => 'GET',
		'callback' => 'medici_get_blog_stats',
		'permission_callback' => '__return_true',
	]);
});

function medici_get_blog_stats()
{
	return [
		'total_posts' => wp_count_posts()->publish,
		'total_views' => medici_get_total_views(),
		'categories' => get_terms(['taxonomy' => 'category', 'hide_empty' => true]),
	];
}
```

---

### 34. Модуль "Блог" у GeneratePress: Експертний гайд

**Модуль "Блог" в GeneratePress дозволяє глибоко налаштувати архівні сторінки без написання коду.**

#### Активація та базова конфігурація

**Крок 1: Активація Модуля**

```text
Appearance → GeneratePress → Modules → Blog
Натисніть Activate
```

**Крок 2: Присвоєння сторінки блога**

```text
Settings → Reading → Posts Page
Встановіть сторінку для архіву дописів
```

#### Конфігурація Layout і Content

**Blog Layout Options:**

| Опція              | Призначення                             | SEO Примітка                                     |
| ------------------ | --------------------------------------- | ------------------------------------------------ |
| **Columns**        | 1, 2, або 3 колонки для гріду           | 2-3 колонки на desktop — найліпше для engagement |
| **Featured Image** | Показувати/приховати зображення         | Обов'язково включіть для соціальних мереж        |
| **Post Excerpt**   | Показувати excerpt замість full content | Скорочує розмір сторінки, покращує LCP           |
| **Post Meta**      | Author, date, categories, tags          | Виключіть непотрібні елементи                    |
| **Archive Type**   | Blog vs Custom Post Types               | Налаштовується окремо для кожного типу           |

**Рекомендована конфігурація для медичних сайтів:**

```text
Columns: 2 (desktop), 1 (mobile)
Featured Image: Так, з aspect ratio 16:9
Post Excerpt: Так, максимум 20-30 слів
Show Meta: Дата, категорія, час читання
Sidebar: Yes (на desktop) — для довіри, call-to-action
```

#### Налаштування Excerpt Length через PHP

**GeneratePress не має вбудованої опції для встановлення довжини excerpt через Customizer.**

**Додайте до Hook Element або functions.php:**

```php
<?php
/**
 * Кастомна довжина excerpt для блогу
 */
add_filter('excerpt_length', function ($length) {
	if (is_home() || is_archive()) {
		return 20; // 20 слів для блога
	}
	return 55; // default
});

/**
 * Кастомний текст "читати більше"
 */
add_filter('excerpt_more', function () {
	return ' <a href="' . get_the_permalink() . '">Читати більше →</a>';
});
```

#### Query Block для динамічного контролю blog layout

**Рекомендований підхід для професіоналів: повністю замінити стандартне відображення дописів власним Query Block.**

**Архітектура Loop Template:**

```text
Appearance → Elements → Add New

Element Type: Block Element
Configure:
  - Name: "Blog Loop"
  - Element Type: Loop Template
  - Display Rules: Specific page → Your Blog Page

Структура блоків:
Container (section)
  └─ Looper (Grid: 2 columns on desktop, 1 on mobile)
      └─ Loop Item (article tag)
          ├─ Image (Featured Image - 16:9 aspect ratio)
          ├─ Container (text wrapper)
          │   ├─ Headline H2 ({{post_title}})
          │   ├─ Container (meta)
          │   │   ├─ Text ({{post_date}})
          │   │   ├─ Text (" • ")
          │   │   └─ Text ({{term_list taxonomy:category}})
          │   └─ Text ({{post_excerpt}})
          └─ Container (footer actions)
              └─ Button (Link to Post)
```

**Query Block Settings:**

| Параметр            | Значення                             |
| ------------------- | ------------------------------------ |
| **Post Type**       | Post                                 |
| **Posts per Page**  | Успадкуйте з Settings → Reading (12) |
| **Pagination Type** | Standard (не Instant) для SEO        |
| **Order By**        | date                                 |
| **Order**           | DESC                                 |
| **Tag Name**        | section                              |

**Переваги цього підходу:**
✅ Контролюєте кожен аспект дизайну
✅ Ігноруєте обмеження GeneratePress Blog Module
✅ Легко модифікувати для категорій, тегів, пошуку

#### Sticky Posts у блозі

**Проблема:** WordPress за замовчуванням показує sticky posts на початку, що може порушити пагінацію.

**Рішення через Query Block:**

```text
Query Block → Add Parameter → Sticky Posts
Опції:
  - include (default) — показувати sticky посередині звичайних дописів
  - exclude — ігнорувати sticky
  - only — показувати тільки sticky
  - ignore — показувати sticky на початку (WordPress default)
```

**Рекомендація для медичних блогів:** Встановіть `exclude` для архівів, щоб не домінувати "оголошеннями".

#### Пагінація: SEO та UX

**Типи пагінації:**

| Тип                    | Переваги                  | Недоліки             | SEO                  |
| ---------------------- | ------------------------- | -------------------- | -------------------- |
| **Numbered (1, 2, 3)** | Хорошо для навігації      | Багато HTTP запитів  | ✅ Краще — indexed   |
| **Next/Prev**          | Мінімальна, чистий вигляд | Важче навігувати     | ✅ Хорошо            |
| **Load More**          | Infinite scroll feel      | Потребує JavaScript  | ⚠️ Ризик не-indexing |
| **Infinite Scroll**    | Максимум engagement       | CLS issues, SEO риск | ❌ Гірше             |

**Рекомендація для медичних сайтів:** Numbered pagination з self-referencing canonicals.

**Self-referencing canonicals:**

```xml
<!-- На сторінці 2 блога -->
<link rel="canonical" href="https://clinica.ua/blog/page/2/">

<!-- На сторінці 1 -->
<link rel="canonical" href="https://clinica.ua/blog/">
```

**GenerateBlocks 2.x Pagination:**

```text
Query Block → + Button → Pagination

Це додасть:
  - Previous button
  - Page numbers (1, 2, 3...)
  - Next button
```

#### Featured Image Optimization

**Best Practice для медичних сайтів:**

```text
Size: 16:9 aspect ratio (1200px × 675px minimum)
Format: WebP з fallback на JPEG (Perfmatters)
Lazy Loading: По замовчуванню включено в GeneratePress
```

**Налаштування в GenerateBlocks:**

```text
Image Block → Styling
  ├─ Aspect Ratio: 16:9
  ├─ Overflow: clip (для rounded corners)
  └─ Width: 100%
```

**Якщо використовуєте ACF для custom featured images:**

```text
Dynamic Tag: {{post_meta key:custom_featured_image.0.ID}}
```

#### Категорії та Теги у Блозі

**Фільтрація по категорії (для підсторінок типу /blog/healthcare/):**

```text
Appearance → Elements → Add New → Block Element

Element Type: Loop Template
Display Rules:
  - Location: Post Type Archive
  - Specific Post Type Archive: Category

Query Block Settings:
  - Add Parameter → Taxonomy: Categories
  - Select: Healthcare
```

**Dynamic Term Listing:**

```text
{{term_list taxonomy:category}}
```

Виводить усі категорії допису як посилання.

#### Related Posts Section

**На single post шаблоні (не на блозі):**

```text
Query Block Settings:
  - Post Type: Post
  - Add Parameter → Exclude Posts: Current Post
  - Add Parameter → Taxonomy: Categories → Current Post Categories
  - Posts per Page: 3
```

Цей запит виведе дописи в тих самих категоріях, крім поточного.

#### Performance: Blog Archive Optimization

**Для досягнення PageSpeed 95+ на архівах:**

**1. CSS Print Method:**

```text
Customizer → General → CSS Print Method: External File
```

**2. Disable Elements на архівах:**

```text
Appearance → GeneratePress → Modules → Disable Elements

Виберіть Blog Archive, відключіть:
  - Featured Image (якщо не потрібна)
  - Content Title
  - Meta (якщо не потрібна)
```

**3. Preload LCP Image (Perfmatters):**

```php
<?php
// Додайте перший featured image в preload
$first_post = get_posts(['posts_per_page' => 1]);
if (!empty($first_post)) {
	$image_url = get_the_post_thumbnail_url($first_post[0]->ID);
	echo '<link rel="preload" as="image" href="' . esc_url($image_url) . '" fetchpriority="high">';
}
?>
```

**4. Excerpt length:** Встановіть 20-30 слів (скорочує HTML).

**Результат:** LCP 1.2-1.8s, CLS < 0.05 на чистому блозі.

#### Архівні сторінки: Category, Tag, Author, Search

**GeneratePress Blog Module контролює усі архіви централізовано:**

```text
Appearance → Customize → Layout → Blog
```

Усі налаштування (columns, excerpt, meta) застосовуються до:

- Category archives (/category/healthcare/)
- Tag archives (/tag/regulations/)
- Author archives (/author/dr-ivanov/)
- Search results (/?s=pacemaker)

**Override для конкретного архіву (Elements):**

```text
Appearance → Elements → Add New → Block Element

Element Type: Loop Template
Display Rules:
  - Location: Archive
  - Specific Archive: Category → Healthcare
```

Це переписуватиме layout тільки для категорії "Healthcare", решта використовуватимуть default.

#### Медична специфіка: Privacy & Compliance

**Для GDPR + медичних сайтів:**

**1. Приховайте Author bio в List View:**

```text
Query Block → Loop Item → Container (author) → Add Condition

Condition Type: User
Condition: Is logged in: True
```

**2. Скройте коментарі на деяких дописах:**

```php
<?php
// У single.php template
if (!get_post_meta(get_the_ID(), 'hide_comments', true)) {
	comments_template();
}
?>
```

**3. Exclude sensitive posts з публічного архіву:**

```text
Query Block → Add Parameter → Exclude Posts: [ID1, ID2, ID3]
```

#### Внутрішнє посилання для SEO

**Related Posts SEO Strategy:**

Встановіть "More Posts" секцію на end single post з:

- 3-4 пов'язаних дописів
- Тих же категорій
- З anchor text на заголовок

**Це поліпшує:**
✅ Crawlability (Google проходить глибше)
✅ User engagement (users залишаються довше)
✅ Link equity distribution

#### Checksum: Рекомендована Setup

**Для професійної медичної клініки:**

```text
1. Blog Archive:
   - 12 дописів на сторінку
   - 2 колонки на desktop, 1 на mobile
   - Featured image 16:9
   - 25-слівний excerpt
   - Meta: date, category
   - Pagination: numbered

2. Single Post Template:
   - Full content
   - Author box (з ACF)
   - Related posts: 3 дописи тієї ж категорії
   - Comments: enable

3. Performance:
   - External CSS
   - Lazy loading: enable
   - Preload LCP images
   - Target: LCP < 2.0s, CLS < 0.05

4. SEO:
   - Self-referencing canonicals на кожній сторінці
   - Breadcrumbs через Elements
   - Schema markup: BlogPosting
```

Цей підхід забезпечує оптимальний баланс між UX, SEO і перформансом для медичних блогів.

---

### 35. Рекомендований стек для експертів

**Для професійних медичних сайтів та агентських проектів:**

| Компонент                  | Рішення                                  |
| -------------------------- | ---------------------------------------- |
| **Тема**                   | GeneratePress Premium 2.5.x              |
| **Блоки**                  | GenerateBlocks Pro 2.4+                  |
| **Оптимізація**            | Perfmatters                              |
| **Custom Fields**          | ACF Pro                                  |
| **Query модифікації**      | `pre_get_posts` hook                     |
| **Версіонування**          | Child Theme + Git                        |
| **Performance Monitoring** | Core Web Vitals (LCP < 2.0s, CLS < 0.05) |

**Цей стек забезпечує:**
✅ PageSpeed 95-100 навіть у складних медичних або e-commerce сценаріях
✅ Максимальну гнучкість при мінімальному overhead
✅ Повну відповідність медичним регуляціям та GDPR

---

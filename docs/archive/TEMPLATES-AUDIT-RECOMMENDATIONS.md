# 🔧 ДЕТАЛЬНІ РЕКОМЕНДАЦІЇ ДЛЯ ВИПРАВЛЕННЯ

## 1️⃣ CRITICAL FIX: archive-medici_blog.php (Lines 117-121)

### ❌ ПРОБЛЕМА

```html
<div
	class="medici-blog-container"
	style="
    /* margin-right: 40px; */*  ← СИНТАКСИС ПОМИЛКА
    margin-left: 40px; */
    /* width: 1580px; */
"
></div>
```

**Чому це проблема?**

- CSS парсер не розуміє закриття коментаря `*/*`
- Це може впливати на парсинг наступних стилів
- Браузер може неправильно інтерпретувати CSS

### ✅ РІШЕННЯ

Видалити весь коментований блок:

```html
<div class="medici-blog-container"></div>
```

**Оцінка складності:** ⭐ Дуже легко (30 секунд)

---

## 2️⃣ CRITICAL FIX: single-medici_blog.php (Line 55)

### ❌ ПРОБЛЕМА

```php
<span class="current"><?php the_title(); ?></span>
```

**Чому це проблема?**

- `the_title()` за замовчуванням НЕ екранує вивід
- WordPress документація рекомендує використовувати `esc_html( get_the_title() )`
- Потенційна XSS уразливість якщо title містить HTML

### ✅ РІШЕННЯ

```php
<span class="current"><?php echo esc_html(get_the_title()); ?></span>
```

**Оцінка складності:** ⭐ Дуже легко (30 секунд)

---

## 3️⃣ IMPORTANT: Оновити CLAUDE.md документацію

### 🎯 ЧИМ ОНОВИТИ

#### Секція 1: Файлова структура

```markdown
## 📁 РЕАЛЬНА ФАЙЛОВА СТРУКТУРА

### ⚠️ ВАЖЛИВО: Не всі файли з документації існують!

Дійсні шаблони:
✅ single-medici_blog.php (239 рядків) - Blog single post
✅ archive-medici_blog.php (360 рядків) - Blog archive
✅ gutenberg/ (HTML markup) - GeneratePress Elements

Не існують (задокументовано неправильно):
❌ front-page.php
❌ home.php
❌ patterns/ директорія
❌ partials/ директорія

Замість patterns/, проект використовує:

- GeneratePress Elements з HTML markup у gutenberg/
- GenerateBlocks Pro 2.0+ для блоків
```

#### Секція 2: Архітектура

```markdown
## 🏗️ ГІБРИДНА АРХІТЕКТУРА ШАБЛОНІВ

### Рівень 1: Blog Content (PHP шаблони)

- **single-medici_blog.php** - Динамічна сторінка для окремої статті
  - Sidebar з Table of Contents
  - Related articles
  - Newsletter форма
  - Relevant services widget
- **archive-medici_blog.php** - Динамічна сторінка архіву/домашньої сторінки блогу
  - Featured post logic (manual + auto + fallback)
  - Фільтрація за категоріями
  - Сортування (newest, popular, alphabetical)
  - AJAX Load More
  - Pagination

### Рівень 2: Agency Content (GeneratePress Elements)

- **gutenberg/\*.html** - HTML markup для Elements
  - HEADER.html - верхня навігація та логотип
  - HERO.html - hero section
  - SERVICES-1.html, SERVICES-2.html - sections послуг
  - TEAM.html - team section
  - FOOTER.html - footer
  - FAQ.html, FEEDBACK.html - інші sections

- Процес публікації:
  1. Редагування HTML у gutenberg/
  2. Копіювання в GeneratePress > Elements
  3. Налаштування Display Rules
  4. Публікація Element
```

#### Секція 3: Helper функції

```markdown
## 🔧 Helper функції для шаблонів

Всі ці функції винесені в inc/ модулі:

### Blog-related

- `medici_get_category_style( $term_id )` -
  Повертає inline-стилі для категорії (колір + rgba background)
- `medici_get_related_blog_posts( $post_id, $limit )` -
  WP_Query з пошуком схожих статей за категоріями
- `medici_render_relevant_services_widget( $post_id )` -
  Виводить рекомендовані сервіси на sidebar
- `medici_should_show_newsletter_widget()` -
  Логіка для показу newsletter форми
- `medici_should_show_back_to_blog_widget()` -
  Логіка для показу back to blog кнопки
- `medici_render_blog_pagination( $query )` -
  Custom пагінація з номерованими посиланнями
```

---

## 4️⃣ MEDIUM: Винести Featured Post logic у функцію

### 📌 ЧИМ ПРОБЛЕМА?

У archive-medici_blog.php 40+ рядків коду присвячено логіці вибору featured post:

```php
// Lines 40-91
$featured_post    = null;
$featured_post_id = (int) get_option( 'medici_blog_featured_post_id', 0 );

if ( $featured_post_id > 0 ) {
    // Вручну обрана стаття
    $featured_post = get_post( $featured_post_id );
    if ( ! $featured_post || ... ) {
        $featured_post = null;
    }
}

if ( ! $featured_post ) {
    // Автоматично - найновіша рекомендована стаття
    $featured_query = new WP_Query( [...] );
    if ( $featured_query->have_posts() ) {
        ...
    }
}

if ( ! $featured_post ) {
    // Fallback: найновіша опубліковна
    $latest_query = new WP_Query( [...] );
    if ( $latest_query->have_posts() ) {
        ...
    }
}
```

### ✅ РІШЕННЯ

Створити функцію `inc/blog-featured-post.php`:

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Get featured blog post with fallback logic
 *
 * Порядок пошуку:
 * 1. Вручну обрана статья (medici_blog_featured_post_id option)
 * 2. Автоматично - найновіша рекомендована (_medici_featured_article meta)
 * 3. Fallback - найновіша опублікована
 *
 * @return WP_Post|null
 */
function medici_get_featured_blog_post(): ?WP_Post
{
	// 1. Manual selection
	$featured_post_id = (int) get_option('medici_blog_featured_post_id', 0);

	if ($featured_post_id > 0) {
		$featured_post = get_post($featured_post_id);

		if (
			$featured_post &&
			$featured_post->post_type === 'medici_blog' &&
			$featured_post->post_status === 'publish'
		) {
			return $featured_post;
		}
	}

	// 2. Auto-select (featured meta)
	$featured_query = new WP_Query([
		'post_type' => 'medici_blog',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_key' => '_medici_featured_article',
		'meta_value' => '1',
		'orderby' => 'date',
		'order' => 'DESC',
	]);

	if ($featured_query->have_posts()) {
		$featured_query->the_post();
		$featured_post = get_post();
		wp_reset_postdata();
		return $featured_post;
	}

	// 3. Fallback (latest post)
	$latest_query = new WP_Query([
		'post_type' => 'medici_blog',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
	]);

	if ($latest_query->have_posts()) {
		$latest_query->the_post();
		$featured_post = get_post();
		wp_reset_postdata();
		return $featured_post;
	}

	return null;
}
```

Тоді у archive-medici_blog.php замість 50 рядків коду:

```php
$featured_post = medici_get_featured_blog_post();
```

**Оцінка складності:** ⭐⭐ Легко (30 хвилин)

**Користь:**

- Reusability (можна використати в інших місцях)
- Testability (можна написати unit-тести)
- Чистота шаблону

---

## 5️⃣ NICE TO HAVE: Partial templates

### 🎯 ЧИМ КОРИСНО?

Чого повторювати HTML структури, коли можна винести в partials?

### ✅ ПРОПОНОВАНИЙ ПІДХІД

```
partials/
├── featured-card.php
├── article-card.php
├── sidebar-newsletter.php
├── sidebar-services.php
└── related-articles.php
```

#### Приклад: featured-card.php

```php
<?php
/**
 * Partial: Featured Post Card
 *
 * @param WP_Post $featured_post
 * @return void
 */
if (!defined('ABSPATH')) {
	exit();
}

$featured_image = get_the_post_thumbnail_url($featured_post->ID, 'large');
$featured_category = get_the_terms($featured_post->ID, 'blog_category');
$featured_cat_name =
	$featured_category && !is_wp_error($featured_category) ? $featured_category[0]->name : '';
$featured_excerpt = get_the_excerpt($featured_post->ID);
$reading_time = (int) get_post_meta($featured_post->ID, '_medici_reading_time', true);
$post_date_text = get_the_date('j F Y', $featured_post->ID);
?>

<div class="medici-blog-featured-card">
    <span class="medici-blog-featured-badge">
        ⭐ <?php esc_html_e('Рекомендовано', 'medici.agency'); ?>
    </span>
    <h3 class="medici-blog-featured-title">
        <?php echo esc_html(get_the_title($featured_post->ID)); ?>
    </h3>
    <?php if ($featured_excerpt): ?>
        <p class="medici-blog-featured-excerpt">
            <?php echo esc_html(wp_trim_words($featured_excerpt, 25)); ?>
        </p>
    <?php endif; ?>
    <div class="medici-blog-featured-meta">
        <span>📅 <?php echo esc_html($post_date_text); ?></span>
        <?php if ($reading_time > 0): ?>
            <span>⏱ <?php echo esc_html($reading_time); ?> хв читання</span>
        <?php endif; ?>
    </div>
    <a href="<?php echo esc_url(
    	get_permalink($featured_post->ID)
    ); ?>" class="medici-blog-featured-link">
        <?php esc_html_e('Читати статтю', 'medici.agency'); ?>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14m-7-7l7 7-7 7"/>
        </svg>
    </a>
</div>
```

Тоді у archive-medici_blog.php:

```php
<?php if ($featured_post): ?>
    <?php get_template_part('partials/featured-card', null, ['featured_post' => $featured_post]); ?>
<?php endif; ?>
```

**Оцінка складності:** ⭐⭐⭐ Середня (2-3 години)

**Користь:**

- DRY principle (Don't Repeat Yourself)
- Легше міняти у одному місці
- Потенційне повторне використання

---

## 📊 PRIORITY MATRIX

| #   | Завдання                    | Складність | Час  | Важливість | Статус |
| --- | --------------------------- | ---------- | ---- | ---------- | ------ |
| 1   | Fix CSS syntax error        | ⭐         | 30s  | 🔴 HIGH    | TODO   |
| 2   | Fix the_title() escaping    | ⭐         | 30s  | 🔴 HIGH    | TODO   |
| 3   | Update CLAUDE.md docs       | ⭐⭐       | 1-2h | 🟡 MEDIUM  | TODO   |
| 4   | Extract featured post logic | ⭐⭐       | 30m  | 🟡 MEDIUM  | TODO   |
| 5   | Add partial templates       | ⭐⭐⭐     | 2-3h | 🟢 LOW     | TODO   |
| 6   | Add comment_form()          | ⭐⭐       | 1h   | 🟢 LOW     | SKIP   |
| 7   | Optimize WP_Query           | ⭐⭐       | 30m  | 🟢 LOW     | SKIP   |

---

## ✅ QA CHECKLIST ПІСЛЯ ВИПРАВЛЕНЬ

### Before Commit

- [ ] Виправлена CSS помилка у archive-medici_blog.php (lines 117-121)
- [ ] Виправлена the_title() escaping у single-medici_blog.php (line 55)
- [ ] Оновлена CLAUDE.md документація
- [ ] Тестування шаблонів у браузері
  - [ ] Single post сторінка (layout, styles, JS)
  - [ ] Archive сторінка (filters, sorting, pagination)
  - [ ] Mobile responsive (767px viewport)
  - [ ] Dark theme (якщо активна)
- [ ] Перевіра всіх посилань
- [ ] Перевіра форми newsletter (nonce fields)

### After Commit

- [ ] Push до git
- [ ] Оновлення CHANGELOG.md
- [ ] Testing на production (якщо потрібно)
- [ ] Monitoring browser console (no JS errors)

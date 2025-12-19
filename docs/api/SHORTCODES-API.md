# Shortcodes Reference

Тема надає **3 shortcodes** для функціоналу блогу, зареєстровані в `inc/blog-shortcodes.php` (v1.0.16).

**⚠️ ВАЖЛИВО:** Тільки ці 3 shortcodes реально існують в темі. Решта функціоналу (author box, TOC, newsletter) реалізовані через інші методи.

---

## 1. Warning Box Shortcode

**Usage:**

```php
[medici_warning title="УВАГА: Найпоширеніша помилка" icon="⚠️"]
Текст попередження тут.
[/medici_warning]
```

**Parameters:**

- `title` (string, default: "УВАГА: Найпоширеніша помилка") - Warning heading
- `icon` (string, default: "⚠️") - Emoji icon

**File:** `inc/blog-shortcodes.php:33-64`

---

## 2. Inline CTA Shortcode

**Usage:**

```php
[medici_cta
    title="Потрібна консультація?"
    text="Наші експерти допоможуть вам вирішити будь-яке питання"
    button_text="Зв'язатися з нами"
    button_url="#contact"
    button_icon="→"]
```

**Parameters:**

- `title` (string) - CTA heading
- `text` (string) - CTA description
- `button_text` (string) - Button label
- `button_url` (string) - Button link URL
- `button_icon` (string) - Button icon

**File:** `inc/blog-shortcodes.php:79-119`

---

## 3. Key Takeaways Shortcode

**Usage:**

```php
[medici_takeaways title="Що ви дізнаєтесь з цієї статті:"]
Перший важливий пункт
Другий важливий пункт
[/medici_takeaways]
```

**Parameters:**

- `title` (string, default: "Що ви дізнаєтесь з цієї статті:") - Takeaways heading

**File:** `inc/blog-shortcodes.php:135-169`

---

## Technical Details

**All shortcodes:**

- ✅ Full PHP type hints (strict_types=1)
- ✅ PHPDoc documentation
- ✅ Input sanitization (security)
- ✅ Output escaping (XSS protection)
- ✅ WordPress Coding Standards compliant

**Security features:**

- `sanitize_text_field()` for text inputs
- `esc_url()` for URLs
- `wp_kses_post()` for HTML content
- `esc_html()` for output

**CSS Styling:**

- `.warning-box` - Warning box styles
- `.inline-cta` - CTA box styles
- `.takeaways` - Takeaways box styles

---

## Not Available as Shortcodes

The following functionality is **NOT** available as shortcodes but implemented differently:

- **Author Box** - Available via `Medici_Blog_Module::render_author_box()` method
- **Share Buttons** - Available via `Medici_Blog_Module::render_share_buttons()` method
- **Breadcrumbs** - Built into `single-medici_blog.php` template
- **Related Posts** - Built into `single-medici_blog.php` template
- **Newsletter Form** - Available in sidebar
- **Table of Contents** - Auto-generated from H2/H3 via JavaScript (`js/modules/blog/blog-single.js`)
- **Blog Posts Grid** - Use GenerateBlocks patterns

**Why not shortcodes?**
These features require complex logic, database queries, and state management better handled through PHP methods and widgets.

---

**Last Updated:** 2025-12-18

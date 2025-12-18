# 🎯 НАЙКРАЩІ ПРАКТИКИ MEDICI THEME

## PHP & WordPress

### Модульна Архітектура з Traits

```php
class Medici_Blog_Module
{
	use Medici_Blog_Rendering;
	use Medici_Blog_AJAX;
	use Medici_Blog_Shortcodes;
}
```

### Singleton Pattern

```php
private static $instance = null;
public static function get_instance() {
    if (null === self::$instance) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### Strict Types + Type Hints

```php
declare(strict_types=1);

function calculate_reading_time(int $post_id, int $wpm = 200): array
{
	// ...
}
```

### Static Caching

```php
function get_icons(): array
{
	static $icons = null;
	if (null !== $icons) {
		return $icons;
	}
	$icons = [
		/* ... */
	];
	return $icons;
}
```

### WordPress Coding Standards

- `esc_html()`, `esc_url()`, `esc_attr()`
- `sanitize_text_field()`, `wp_kses_post()`
- `__()`, `_e()`, `esc_html__()`
- `absint()`, `intval()`

---

## CSS & Стилізація

### CSS Variables

```css
:root {
	--accent: #3b82f6;
	--text-primary: #0f172a;
	--spacing-md: 1.5rem;
}

[data-theme='dark'] {
	--text-primary: #f1f5f9;
	--bg-primary: #0f172a;
}
```

### ITCSS Architecture

```
css/
├── core/ (variables, fonts, reset, base)
├── components/ (buttons, cards, sections)
├── layout/ (hero, footer, grid, utilities)
└── modules/blog/ (blog-specific)
```

### Responsive Design

```css
/* Mobile-first */
.element {
	width: 100%;
}

@media (min-width: 768px) {
	.element {
		width: 50%;
	}
}

@media (min-width: 1024px) {
	.element {
		width: 33.333%;
	}
}
```

### GenerateBlocks CSS Variables

```css
.gb-container {
	--container-padding: clamp(1rem, 2vw, 2rem);
	padding: var(--container-padding);
}
```

---

## JavaScript

### Vanilla JS (No jQuery)

```javascript
document.addEventListener('DOMContentLoaded', function () {
	const element = document.querySelector('.selector');
	element.addEventListener('click', handleClick);
});
```

### Event Delegation

```javascript
document.addEventListener('click', function (e) {
	if (e.target.matches('.dynamic-button')) {
		// Handle click
	}
});
```

### Async/Defer

```php
wp_enqueue_script('theme-js', get_stylesheet_directory_uri() . '/js/scripts.js', [], '1.0.0', [
	'strategy' => 'defer',
]);
```

---

## GenerateBlocks 2.x

### UniqueId Format

```javascript
// 8 hex lowercase characters
const uniqueId = Math.random().toString(16).substring(2, 10);
// Example: "a3f7b9c2"
```

### CSS Variables Escaping

```json
{
	"useGlobalMaxWidth": false,
	"sizing": {
		"minHeight": "var(\\u002d\\u002dmin-height)"
	}
}
```

### Responsive Breakpoints

```json
{
	"tablet": {
		"sizing": { "width": "50%" }
	},
	"mobile": {
		"sizing": { "width": "100%" }
	}
}
```

---

## GeneratePress Child Theme

### Hooks Usage

```php
// Remove sidebar
add_filter(
	'generate_sidebar_layout',
	function () {
		return 'no-sidebar';
	},
	99
);

// Modify content width
add_filter(
	'generate_content_width',
	function ($width) {
		return is_home() ? 1200 : $width;
	},
	99
);
```

### Body Classes

```php
add_filter('body_class', function ($classes) {
	if (is_home()) {
		$classes[] = 'blog-homepage';
		$classes[] = 'full-width-page';
	}
	return $classes;
});
```

---

## Продуктивність

### Critical CSS Inline

```php
function inline_critical_css()
{
	$critical = file_get_contents(get_stylesheet_directory() . '/css/critical.css');
	echo '<style id="critical-css">' . $critical . '</style>';
}
add_action('wp_head', 'inline_critical_css', 1);
```

### Local Fonts

```php
function local_fonts()
{
	$fonts_dir = get_stylesheet_directory_uri() . '/fonts/';
	echo '<link rel="preload" href="' .
		$fonts_dir .
		'montserrat-regular.woff2" as="font" type="font/woff2" crossorigin>';
}
```

### Conditional Loading

```php
if (is_singular('medici_blog')) {
	wp_enqueue_style('blog-single');
	wp_enqueue_script('blog-single');
}
```

### Image Optimization

```php
add_filter('wp_get_attachment_image_attributes', function ($attr) {
	$attr['loading'] = 'lazy';
	$attr['decoding'] = 'async';
	return $attr;
});
```

---

## Безпека

### Nonce Verification

```php
if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'action_name')) {
	wp_die('Security check failed');
}
```

### Capability Checks

```php
if (!current_user_can('manage_options')) {
	wp_die('Unauthorized');
}
```

### Input Sanitization

```php
$text = sanitize_text_field($_POST['text']);
$html = wp_kses_post($_POST['content']);
$email = sanitize_email($_POST['email']);
$url = esc_url_raw($_POST['url']);
```

### CSP Headers

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . $nonce . "'");
```

---

## Архітектурні Рішення

### Priority-Based Module Loader

```php
$priority_modules = [
	'theme-setup.php', // Core
	'assets.php', // Assets
	'blog-cpt.php', // Blog
	'svg-icons.php', // Enhancements
];

foreach ($priority_modules as $module) {
	require_once get_stylesheet_directory() . '/inc/' . $module;
}
```

### Dependency Chain (CSS)

```php
wp_enqueue_style('core-variables');
wp_enqueue_style('components', [], ['core-variables']);
wp_enqueue_style('layout', [], ['components']);
```

### Widget-Based Modular Features

```php
class Custom_Widget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct('widget_id', 'Widget Name');
	}
}
register_widget('Custom_Widget');
```

---

**END OF BEST PRACTICES**

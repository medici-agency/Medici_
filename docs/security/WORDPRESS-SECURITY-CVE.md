# 🔐 WORDPRESS SECURITY (CVE 2024-2025)

**⚠️ Критична інформація:** Аналіз ландшафту загроз WordPress за 2024-2025 для захисту теми Medici.

## 1. Ландшафт загроз 2024-2025

**Статистика вразливостей:**

- **Плагіни:** 96% всіх CVE (зростання на 34% порівняно з 2023)
- **Теми:** 3.5% вразливостей
- **WordPress Core:** 0.5% (переважно Authenticated XSS, низький імпакт)

**Критичні тренди:**

- **Supply Chain Attacks** — компрометація розробників, бекдори в легітимних оновленнях
- **Mass Exploitation** — автоматизовані атаки протягом 24-48 годин після публікації PoC
- **Unauthenticated RCE** — найнебезпечніший вектор (CVSS 9.0-10.0)

## 2. Критичні патерни вразливостей

**A. Unprotected File Upload (CVSS 10.0):**

```php
// ❌ ВРАЗЛИВИЙ КОД (CVE-2025-6327 pattern)
function ajax_upload()
{
	move_uploaded_file($_FILES['file']['tmp_name'], $target);
}
add_action('wp_ajax_nopriv_upload', 'ajax_upload'); // Неавтентифікований!

// ✅ БЕЗПЕЧНИЙ КОД
function ajax_upload()
{
	check_ajax_referer('upload_nonce');
	if (!current_user_can('upload_files')) {
		wp_die('Недостатньо прав');
	}

	$allowed = ['jpg', 'jpeg', 'png', 'gif'];
	$type = wp_check_filetype($_FILES['file']['name']);
	if (!in_array($type['ext'], $allowed)) {
		wp_die('Недозволений тип');
	}

	wp_handle_upload($_FILES['file'], ['test_form' => false]);
}
add_action('wp_ajax_upload', 'ajax_upload'); // Тільки залогінені
```

**B. SQL Injection через Partial Sanitization:**

```php
// ❌ ВРАЗЛИВИЙ КОД (CVE-2025-12197 pattern)
$orderby = esc_sql($_GET['orderby']);
$where = 'AND ' . $_GET['where']; // Не санітизовано!
$query = "SELECT * FROM posts WHERE status = 1 $where ORDER BY $orderby";

// ✅ БЕЗПЕЧНИЙ КОД
$allowed_orderby = ['date', 'title', 'id'];
$orderby = in_array($_GET['orderby'], $allowed_orderby) ? $_GET['orderby'] : 'date';
$meta_key = sanitize_text_field($_GET['meta_key']);
$query = $wpdb->prepare("SELECT * FROM posts WHERE meta_key = %s ORDER BY $orderby", $meta_key);
```

**C. Insecure REST API (CVE-2024-25600 pattern):**

```php
// ❌ ВРАЗЛИВИЙ КОД
register_rest_route('medici/v1', '/admin', [
	'callback' => 'do_admin_action',
	'permission_callback' => '__return_true', // КРИТИЧНА ПОМИЛКА!
]);

// ✅ БЕЗПЕЧНИЙ КОД
register_rest_route('medici/v1', '/admin', [
	'callback' => 'do_admin_action',
	'permission_callback' => fn() => current_user_can('manage_options'),
	'args' => [
		'id' => [
			'validate_callback' => fn($p) => is_numeric($p),
			'sanitize_callback' => 'absint',
		],
	],
]);
```

## 3. Security Audit Checklist

**Перед кожним коммітом перевір:**

✅ **Input Validation:**

- `$_GET`, `$_POST`, `$_REQUEST` санітизовані (`sanitize_text_field`, `absint`, `esc_url`)
- REST API має `validate_callback` та `sanitize_callback`

✅ **Authentication:**

- AJAX без `nopriv` має `check_ajax_referer()`
- REST API має `permission_callback` з `current_user_can()`

✅ **Database:**

- Завжди `$wpdb->prepare()` для динамічних запитів
- Whitelist для `ORDER BY`, `WHERE`

✅ **Output Escaping:**

- `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`

## 4. Hardening для теми Medici

**Поточний стан (v1.5.2):**

- ✅ Security module: `inc/security.php` (XML-RPC disable, CSP, headers)
- ✅ Nonce verification: `inc/class-events.php`
- ✅ Input sanitization: `inc/blog-*.php`
- ✅ **CSP Policy** з підтримкою Google Analytics, GTM, Fonts, Cloudflare Zaraz
- ✅ **Cross-Origin headers** (CORP, COOP, COEP)
- ✅ **CSP Report Endpoint** (Cloudflare Worker)

**CSP директиви (inc/security.php v1.5.2):**

```
script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googletagmanager.com *.google-analytics.com *.cloudflare.com
style-src 'self' 'unsafe-inline' fonts.googleapis.com
font-src 'self' data: fonts.gstatic.com
connect-src 'self' *.google-analytics.com stats.g.doubleclick.net *.cloudflare.com
frame-src 'self' youtube.com youtube-nocookie.com player.vimeo.com
object-src 'none'
report-uri https://csp-report-endpoint.moto08405.workers.dev
```

**Cross-Origin заголовки:**

```
Cross-Origin-Resource-Policy: cross-origin
Cross-Origin-Opener-Policy: same-origin-allow-popups
Cross-Origin-Embedder-Policy: unsafe-none
```

**Посилання:**

- Security: `inc/security.php:153-255`
- Events API: `inc/class-events.php` (nonce examples)
- Blog modules: `inc/blog-*.php` (sanitization)

---

**Last Updated:** 2025-12-18

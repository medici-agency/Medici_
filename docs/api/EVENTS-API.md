# Events API - Unified Event Handling System

**Version:** 1.0.0
**Since:** 1.4.0
**Date:** 2025-12-08

---

## 📖 Overview

Events API - єдиний AJAX endpoint для обробки подій у темі Medici (newsletter, consultation, future events). Локальне логування у БД + webhook інтеграція (Zapier/Make/n8n).

---

## 🏗️ Architecture

### PHP Backend

**Files:**

- `inc/class-events.php` - Events handler клас
- `functions.php:60` - Ініціалізація

**Class:** `Medici\Events`

**AJAX Endpoint:** `wp_ajax_medici_event` + `wp_ajax_nopriv_medici_event`

**Init:**

```php
// functions.php:121-126
function medici_init_events_api(): void
{
	if (class_exists('Medici\Events')) {
		\Medici\Events::init();
	}
}
add_action('init', 'medici_init_events_api', 5);
```

### JavaScript Frontend

**Files:**

- `js/events.js` - Core Events API module
- `js/forms-newsletter.js` - Newsletter form handler
- `js/forms-consultation.js` - Consultation form handler

**Global Object:** `window.mediciEvents`

### Templates

- `templates/newsletter-form.html` - Newsletter форма
- `templates/consultation-form.html` - Consultation форма

---

## 🗄️ Database Structure

### Table: `wp_medici_events`

| Column     | Type            | Description                |
| ---------- | --------------- | -------------------------- |
| id         | BIGINT UNSIGNED | Auto-increment primary key |
| event_type | VARCHAR(100)    | Event type identifier      |
| email      | VARCHAR(190)    | Email (for quick query)    |
| created_at | DATETIME        | Event timestamp (UTC)      |
| payload    | LONGTEXT        | JSON-encoded event data    |

**Indexes:** PRIMARY (id), KEY (event_type), KEY (email), KEY (created_at)

**SQL:**

```sql
CREATE TABLE wp_medici_events (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	event_type VARCHAR(100) NOT NULL,
	email VARCHAR(190) NULL,
	created_at DATETIME NOT NULL,
	payload LONGTEXT NULL,
	PRIMARY KEY  (id),
	KEY event_type (event_type),
	KEY email (email),
	KEY created_at (created_at)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Query Examples:**

```php
// Get all newsletter subscribers
global $wpdb;
$table = $wpdb->prefix . 'medici_events';

$subscribers = $wpdb->get_results(
	"SELECT email, created_at FROM {$table}
	 WHERE event_type = 'newsletter_subscribe'
	 ORDER BY created_at DESC",
);

// Get consultation requests (last 7 days)
$consultations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT payload FROM {$table}
		 WHERE event_type = 'consultation_request'
		 AND created_at >= %s",
		gmdate('Y-m-d H:i:s', strtotime('-7 days')),
	),
);
```

---

## 🔗 Webhook Integration

### Payload Structure

```json
{
	"event_type": "newsletter_subscribe",
	"event_id": 42,
	"payload": {
		"email": "user@example.com",
		"source": "footer",
		"tags": ["blog", "newsletter"],
		"page_url": "https://medici.agency/blog/article-name/",
		"utm_source": "google",
		"utm_medium": "cpc",
		"utm_campaign": "summer2024"
	},
	"meta": {
		"site_url": "https://medici.agency",
		"site_name": "Medici - Medical Marketing",
		"created_at": "2025-12-08T10:30:00+00:00"
	}
}
```

**Setup:**

```php
// Save webhook URL
update_option('medici_events_webhook_url', 'https://hooks.zapier.com/hooks/catch/...');
```

---

## 🔒 Security

**Built-in:**

1. **Nonce Verification:** `check_ajax_referer('medici_event', 'nonce');`
2. **Input Sanitization:** `sanitize_email()`, `sanitize_text_field()`, `sanitize_textarea_field()`, `esc_url_raw()`
3. **Email Validation:** `is_email()` check, duplicate detection (newsletter)
4. **Consent Validation:** Required checkbox для consultation form

**Critical:**

- ⚠️ Webhook URL НЕ зберігається в git (тільки в БД)
- ⚠️ Використовуйте HTTPS webhook URLs
- ⚠️ TODO: Add transient-based rate limiting (max 5 events per IP per 10 minutes)

---

## 📚 API Reference

### PHP Methods

#### `Medici\Events::init(): void`

Ініціалізація Events API (AJAX handlers).

#### `Medici\Events::create_table(): void`

Створення таблиці `wp_medici_events`.

**Usage:**

```php
// Auto-create on theme activation (uncomment in class-events.php:37)
add_action('after_switch_theme', [self::class, 'create_table']);
```

---

### JavaScript Methods

#### `mediciEvents.send(eventType, payload): Promise`

Універсальний метод відправки події.

**Parameters:**

- `eventType` (string) - Тип події
- `payload` (object) - Дані події

**Returns:** `Promise<{success: boolean, message: string, data?: object}>`

**Example:**

```javascript
mediciEvents
	.send('custom_event', { foo: 'bar' })
	.then((result) => console.log(result.message))
	.catch((error) => console.error(error.message));
```

---

#### `mediciEvents.subscribeNewsletter(email, options): Promise`

Helper для підписки на newsletter.

**Parameters:**

- `email` (string) - Email адреса
- `options` (object) - Опції: `{source?: string, tags?: string[]}`

**Example:**

```javascript
mediciEvents
	.subscribeNewsletter('user@example.com', {
		source: 'footer',
		tags: ['blog', 'promo'],
	})
	.then((result) => alert(result.message))
	.catch((error) => alert(error.message));
```

**Form HTML:**

```html
<form class="newsletter-form" data-source="footer">
	<input type="email" name="email" placeholder="Ваш email" required />
	<button type="submit">Підписатись</button>
	<div class="newsletter-message"></div>
</form>
```

**Auto-handler:** `js/forms-newsletter.js` знаходить всі `.newsletter-form` та підключає обробку.

---

#### `mediciEvents.requestConsultation(data): Promise`

Helper для запиту консультації.

**Parameters:**

- `data` (object) - Form data: `{name: string, email: string, phone: string, message?: string, service?: string, consent: boolean}`

**Example:**

```javascript
mediciEvents
	.requestConsultation({
		name: 'Іван',
		email: 'ivan@example.com',
		phone: '+380501234567',
		service: 'smm',
		consent: true,
	})
	.then((result) => console.log(result.message))
	.catch((error) => console.error(error.message));
```

**Form HTML:**

```html
<form class="consultation-form">
	<input type="text" name="name" required />
	<input type="email" name="email" required />
	<input type="tel" name="phone" required />
	<textarea name="message"></textarea>
	<select name="service">
		<option value="smm">SMM</option>
		<option value="seo">SEO</option>
	</select>
	<label>
		<input type="checkbox" name="consent" required />
		Згода на обробку даних
	</label>
	<button type="submit">Відправити</button>
	<div class="consultation-message"></div>
</form>
```

**Auto-handler:** `js/forms-consultation.js` знаходить всі `.consultation-form` та підключає обробку.

---

## 🔧 Extending - Add New Event Type

**1. Sanitization** (`inc/class-events.php` → `sanitize_payload`):

```php
if ('webinar_registration' === $event_type) {
	$result['name'] = isset($payload['name']) ? sanitize_text_field($payload['name']) : '';
	$result['email'] = isset($payload['email']) ? sanitize_email($payload['email']) : '';
	$result['webinar_id'] = isset($payload['webinar_id']) ? (int) $payload['webinar_id'] : 0;
}
```

**2. Validation** (`inc/class-events.php` → `validate_payload`):

```php
if ('webinar_registration' === $event_type) {
	if (empty($payload['email']) || !is_email($payload['email'])) {
		return __('Невірний email', 'medici.agency');
	}
}
```

**3. Success message** (`inc/class-events.php` → `get_success_message`):

```php
$messages = [
	'webinar_registration' => __('Реєстрація завершена!', 'medici.agency'),
];
```

**4. JS helper** (`js/events.js`):

```javascript
mediciEvents.registerWebinar = function (webinarId, name, email) {
	var payload = {
		webinar_id: webinarId,
		name: name,
		email: email,
		page_url: window.location.href,
	};

	var utmParams = this._getUTMParams();
	if (utmParams) Object.assign(payload, utmParams);

	return this.send('webinar_registration', payload);
};
```

---

**Last Updated:** 2025-12-08
**Maintainer:** AI Assistant (Claude)
**Project:** Medici Medical Marketing Theme

# Events API - Unified Event Handling System

**Version:** 1.0.0
**Since:** 1.4.0
**Date:** 2025-12-08

---

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Installation](#installation)
- [Usage](#usage)
  - [Newsletter Subscription](#newsletter-subscription)
  - [Consultation Request](#consultation-request)
- [Webhook Integration](#webhook-integration)
- [Database Structure](#database-structure)
- [Security](#security)
- [Extending](#extending)

---

## 📖 Overview

Events API є єдиною точкою входу для обробки всіх подій у темі Medici:

- Newsletter subscription (підписка на розсилку)
- Consultation requests (запити на консультацію)
- Майбутні події (webinar registration, downloads, тощо)

**Переваги:**

- ✅ Єдиний AJAX endpoint для всіх форм
- ✅ Локальне логування подій у БД
- ✅ Webhook інтеграція (Zapier/Make/n8n)
- ✅ Автоматичне збір UTM параметрів
- ✅ Повна типізація PHP (strict_types)
- ✅ Security-first підхід

---

## 🏗️ Architecture

### PHP Backend

**Файли:**

- `inc/class-events.php` - Events handler клас
- `functions.php` - Ініціалізація Events API

**Клас:** `Medici\Events`

**AJAX Endpoint:** `wp_ajax_medici_event` + `wp_ajax_nopriv_medici_event`

### JavaScript Frontend

**Файли:**

- `js/events.js` - Core Events API module
- `js/forms-newsletter.js` - Newsletter form handler
- `js/forms-consultation.js` - Consultation form handler

**Global Object:** `window.mediciEvents`

### Templates

**Директорія:** `templates/`

- `newsletter-form.html` - Newsletter форма (HTML + CSS)
- `consultation-form.html` - Consultation форма (HTML + CSS)

---

## ⚙️ Installation

### 1. Активація модуля

Events API **вже активований** в темі Medici. Перевірте що:

```php
// functions.php - рядок 60
'class-events.php',  // ✅ Events API у priority_modules

// functions.php - рядки 121-126
function medici_init_events_api(): void {
	if ( class_exists( 'Medici\Events' ) ) {
		\Medici\Events::init();
	}
}
add_action( 'init', 'medici_init_events_api', 5 );
```

### 2. Створення таблиці БД

**Опція A: Автоматичне створення** (рекомендовано)

Розкоментуйте в `inc/class-events.php` (рядок 37):

```php
public static function init(): void {
	$self = new self();

	add_action( 'wp_ajax_medici_event', [ $self, 'handle_ajax' ] );
	add_action( 'wp_ajax_nopriv_medici_event', [ $self, 'handle_ajax' ] );

	// Uncomment for auto-creation on theme activation
	add_action( 'after_switch_theme', [ $self, 'create_table' ] );
}
```

Після збереження перемкніть тему і поверніться назад (це викличе `after_switch_theme` hook).

**Опція B: Ручне створення**

Відкрийте **WordPress Admin → Tools → Site Health → Info → Database**, натисніть "Copy site info to clipboard" та знайдіть таблицю `wp_medici_events`.

Якщо таблиці немає, виконайте SQL:

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

### 3. Налаштування Webhook URL

**Крок 1:** Створіть webhook в Zapier/Make/n8n

- **Zapier:** Create Zap → Webhooks by Zapier → Catch Hook → Copy URL
- **Make:** Create Scenario → Webhooks → Custom webhook → Copy URL
- **n8n:** Webhook node → Production URL → Copy URL

**Крок 2:** Збережіть URL в WordPress

```php
// Додайте в functions.php або виконайте через WP-CLI
update_option('medici_events_webhook_url', 'https://hooks.zapier.com/hooks/catch/...');
```

АБО через WordPress Admin Console (Chrome DevTools):

```javascript
// Відкрийте будь-яку сторінку в admin, натисніть F12, Console
fetch(ajaxurl, {
	method: 'POST',
	headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
	body: new URLSearchParams({
		action: 'update_option',
		option: 'medici_events_webhook_url',
		value: 'https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID',
	}),
});
```

⚠️ **Безпека:** Webhook URL НЕ зберігається в git (він в БД). Додайте у `.gitignore`:

```gitignore
# Events API webhook config
*-webhook-*.txt
```

---

## 🚀 Usage

### Newsletter Subscription

#### Варіант 1: Використати готовий шаблон

1. Відкрийте `templates/newsletter-form.html`
2. Скопіюйте весь код
3. У GeneratePress → Elements → Add New → Layout Block
4. Додайте Container block → Advanced → Additional HTML
5. Вставте код з `newsletter-form.html`
6. Налаштуйте `data-source` (footer, sidebar, popup)
7. Опубліку йте

#### Варіант 2: Власна форма

```html
<form class="newsletter-form" data-source="footer">
	<input type="email" name="email" placeholder="Ваш email" required />
	<button type="submit">Підписатись</button>
	<div class="newsletter-message"></div>
</form>
```

#### JS Handler (автоматичний)

Events API автоматично підключає `js/forms-newsletter.js`, який знаходить всі форми з класом `.newsletter-form`.

**Що відбувається:**

1. Користувач вводить email
2. JS відправляє AJAX запит через `mediciEvents.subscribeNewsletter()`
3. PHP валідує, логує в БД, відправляє webhook
4. JS показує повідомлення (success/error)

#### Пряме використання API

```javascript
mediciEvents
	.subscribeNewsletter('user@example.com', {
		source: 'popup',
		tags: ['promo', 'webinar'],
	})
	.then(function (result) {
		console.log('Success:', result.message);
	})
	.catch(function (error) {
		console.error('Error:', error.message);
	});
```

---

### Consultation Request

#### Варіант 1: Використати готовий шаблон

1. Відкрийте `templates/consultation-form.html`
2. Скопіюйте весь код
3. У GeneratePress → Elements → Add New → Layout Block
4. Додайте Container block → Advanced → Additional HTML
5. Вставте код з `consultation-form.html`
6. Налаштуйте опції в `<select name="service">`
7. Опубліку йте

#### Варіант 2: Власна форма

```html
<form class="consultation-form">
	<input type="text" name="name" placeholder="Ім'я" required />
	<input type="email" name="email" placeholder="Email" required />
	<input type="tel" name="phone" placeholder="Телефон" required />
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

#### Пряме використання API

```javascript
mediciEvents
	.requestConsultation({
		name: 'Іван Петренко',
		email: 'ivan@example.com',
		phone: '+380501234567',
		message: 'Потрібна консультація з SMM',
		service: 'smm',
		consent: true,
	})
	.then(function (result) {
		console.log('Success:', result.message);
	})
	.catch(function (error) {
		console.error('Error:', error.message);
	});
```

---

## 🔗 Webhook Integration

### Payload Structure

Events API відправляє JSON payload на webhook URL:

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

### Zapier Integration

**Крок 1: Тригер**

- Webhooks by Zapier → Catch Hook
- Copy webhook URL → Збережіть в WordPress (див. Installation #3)

**Крок 2: Фільтр** (опціонально)

- Filter by Zapier
- Only continue if... `event_type` exactly matches `newsletter_subscribe`

**Крок 3: Дія - Newsletter**

- Mailchimp: Add/Update Subscriber
  - Email: `payload.email`
  - Tags: `payload.tags` (join with comma)
  - Source: `payload.source`
  - Merge Fields: `UTM_SOURCE` = `payload.utm_source`, тощо

**Крок 4: Дія - Consultation**

- Gmail: Send Email
  - To: sales@medici.agency
  - Subject: `New Consultation Request - [payload.service]`
  - Body:

    ```
    Name: [payload.name]
    Email: [payload.email]
    Phone: [payload.phone]
    Service: [payload.service]
    Message: [payload.message]

    Page: [payload.page_url]
    UTM Source: [payload.utm_source]
    Created: [meta.created_at]
    ```

АБО

- HubSpot: Create Contact
  - Email: `payload.email`
  - Name: `payload.name`
  - Phone: `payload.phone`
  - Custom Property `consultation_service`: `payload.service`

---

## 🗄️ Database Structure

### Table: `wp_medici_events`

| Column     | Type            | Description                     |
| ---------- | --------------- | ------------------------------- |
| id         | BIGINT UNSIGNED | Auto-increment primary key      |
| event_type | VARCHAR(100)    | Event type identifier           |
| email      | VARCHAR(190)    | Email address (for quick query) |
| created_at | DATETIME        | Event timestamp (UTC)           |
| payload    | LONGTEXT        | JSON-encoded event data         |

**Indexes:**

- PRIMARY KEY (`id`)
- KEY `event_type` (`event_type`)
- KEY `email` (`email`)
- KEY `created_at` (`created_at`)

### Query Examples

**Get all newsletter subscribers:**

```php
global $wpdb;
$table = $wpdb->prefix . 'medici_events';

$subscribers = $wpdb->get_results(
	"SELECT email, created_at FROM {$table}
	 WHERE event_type = 'newsletter_subscribe'
	 ORDER BY created_at DESC"
);
```

**Get consultation requests by service:**

```php
$consultations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT payload FROM {$table}
		 WHERE event_type = 'consultation_request'
		 AND created_at >= %s",
		gmdate('Y-m-d H:i:s', strtotime('-7 days'))
	)
);

foreach ($consultations as $row) {
	$data = json_decode($row->payload, true);
	echo $data['service']; // smm, seo, etc.
}
```

---

## 🔒 Security

### Built-in Security Measures

1. **Nonce Verification:**

   ```php
   check_ajax_referer('medici_event', 'nonce');
   ```

2. **Input Sanitization:**
   - Email: `sanitize_email()`
   - Text: `sanitize_text_field()`
   - Textarea: `sanitize_textarea_field()`
   - URL: `esc_url_raw()`

3. **Email Validation:**
   - `is_email()` check
   - Duplicate detection (newsletter)

4. **Consent Validation:**
   - Required checkbox для consultation form
   - Logged in payload

5. **Rate Limiting** (рекомендовано додати):
   ```php
   // TODO: Add transient-based rate limiting
   // Max 5 events per IP per 10 minutes
   ```

### Рекомендації

**1. CSP Headers:**
Переконайтесь що CSP дозволяє AJAX запити:

```
script-src 'self' 'unsafe-inline';
connect-src 'self';
```

**2. Webhook Security:**

- Використовуйте HTTPS webhook URLs
- Розгляньте додавання HMAC signature:
  ```php
  $signature = hash_hmac('sha256', wp_json_encode($payload), MEDICI_WEBHOOK_SECRET);
  $headers['X-Medici-Signature'] = $signature;
  ```

**3. Data Privacy:**

- Додайте GDPR disclaimer у форми
- Реалізуйте data export/deletion (якщо потрібно)
- Розгляньте auto-deletion старих events (>1 рік)

---

## 🔧 Extending

### Додавання нового типу події

**Крок 1: Додати sanitization**

`inc/class-events.php` (метод `sanitize_payload`):

```php
if ('webinar_registration' === $event_type) {
	$result['name'] = isset($payload['name']) ? sanitize_text_field($payload['name']) : '';
	$result['email'] = isset($payload['email']) ? sanitize_email($payload['email']) : '';
	$result['webinar_id'] = isset($payload['webinar_id']) ? (int) $payload['webinar_id'] : 0;
}
```

**Крок 2: Додати validation**

`inc/class-events.php` (метод `validate_payload`):

```php
if ('webinar_registration' === $event_type) {
	if (empty($payload['email']) || !is_email($payload['email'])) {
		return __('Невірний email', 'medici.agency');
	}
	if (empty($payload['webinar_id'])) {
		return __('Оберіть вебінар', 'medici.agency');
	}
}
```

**Крок 3: Додати success message**

`inc/class-events.php` (метод `get_success_message`):

```php
$messages = [
	'newsletter_subscribe' => __('Дякуємо за підписку!', 'medici.agency'),
	'consultation_request' => __('Ми зв\'яжемось з вами!', 'medici.agency'),
	'webinar_registration' => __('Реєстрація на вебінар завершена!', 'medici.agency'),
];
```

**Крок 4: Створити JS helper**

`js/events.js`:

```javascript
mediciEvents.registerWebinar = function (webinarId, name, email) {
	var payload = {
		webinar_id: webinarId,
		name: name,
		email: email,
		page_url: window.location.href,
	};

	var utmParams = this._getUTMParams();
	if (utmParams) {
		Object.assign(payload, utmParams);
	}

	return this.send('webinar_registration', payload);
};
```

**Крок 5: Використати**

```javascript
mediciEvents.registerWebinar(123, 'Іван Петренко', 'ivan@example.com').then(function (result) {
	alert(result.message);
});
```

---

## 📚 API Reference

### PHP Methods

#### `Medici\Events::init(): void`

Ініціалізація Events API (AJAX handlers).

#### `Medici\Events::create_table(): void`

Створення таблиці `wp_medici_events`.

---

### JavaScript Methods

#### `mediciEvents.send(eventType, payload): Promise`

Універсальний метод відправки події.

**Parameters:**

- `eventType` (string) - Тип події
- `payload` (object) - Дані події

**Returns:** Promise with result data

**Example:**

```javascript
mediciEvents
	.send('custom_event', { foo: 'bar' })
	.then((result) => console.log(result))
	.catch((error) => console.error(error));
```

#### `mediciEvents.subscribeNewsletter(email, options): Promise`

Helper для підписки на newsletter.

**Parameters:**

- `email` (string) - Email адреса
- `options` (object) - Опції (source, tags)

**Example:**

```javascript
mediciEvents.subscribeNewsletter('user@example.com', {
	source: 'footer',
	tags: ['blog', 'promo'],
});
```

#### `mediciEvents.requestConsultation(data): Promise`

Helper для запиту консультації.

**Parameters:**

- `data` (object) - Form data (name, email, phone, message, service, consent)

**Example:**

```javascript
mediciEvents.requestConsultation({
	name: 'Іван',
	email: 'ivan@example.com',
	phone: '+380501234567',
	consent: true,
});
```

---

## 📝 Changelog

### 1.0.0 (2025-12-08)

**Initial Release:**

- ✅ PHP class `Medici\Events` з AJAX handler
- ✅ JS module `mediciEvents` (global API)
- ✅ Newsletter subscription support
- ✅ Consultation request support
- ✅ Database logging (`wp_medici_events` table)
- ✅ Webhook integration (Zapier/Make/n8n)
- ✅ UTM параметри auto-capture
- ✅ Form handlers (newsletter + consultation)
- ✅ HTML templates для GenerateBlocks
- ✅ Full security implementation

---

## 🤝 Contributing

Events API є частиною Medici theme. Для змін:

1. Створіть feature branch: `git checkout -b feature/events-api-improvement`
2. Зробіть зміни з дотриманням WordPress Coding Standards
3. Додайте типізацію PHP (strict_types)
4. Тестуйте на локальному середовищі
5. Оновіть цю документацію
6. Створіть Pull Request

---

## 📞 Support

**Maintainer:** AI Assistant (Claude)
**Project:** Medici Medical Marketing Theme
**Repository:** ua5220/medici

**Troubleshooting:**

- Перевірте browser console для JS errors
- Перевірте WordPress Debug Log для PHP errors
- Перевірте `wp_medici_events` table існує
- Перевірте webhook URL збережено: `get_option('medici_events_webhook_url')`

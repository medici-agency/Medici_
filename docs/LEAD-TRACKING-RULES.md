# Lead Tracking Rules — Technical Reference

**Версія:** 1.0.0
**Дата:** 2025-12-15

---

## 🏷️ UTM Governance

### Обов'язкові правила

```
✅ ФОРМАТ: lowercase, snake_case, без пробілів
✅ МОВА: English only
✅ ДОВЖИНА: max 50 символів per parameter
```

### utm_source (Платформа/Канал)

**VALID VALUES:**

| Значення    | Опис                          |
| ----------- | ----------------------------- |
| `google`    | Google Ads, Search, Display   |
| `facebook`  | Facebook Ads, Posts           |
| `instagram` | Instagram Ads, Posts, Stories |
| `linkedin`  | LinkedIn Ads, Posts           |
| `telegram`  | Telegram канал/бот            |
| `email`     | Email розсилки                |
| `direct`    | Прямий трафік                 |
| `referral`  | Реферальний трафік            |

**❌ ЗАБОРОНЕНО:** `insta`, `fb`, `ig`, `Google`, `FACEBOOK`, `e-mail`

### utm_medium (Тип трафіку)

**VALID VALUES:**

| Значення   | Опис              |
| ---------- | ----------------- |
| `cpc`      | Cost per click    |
| `cpm`      | Cost per mille    |
| `organic`  | Органічний трафік |
| `social`   | Social organic    |
| `post`     | Публікація        |
| `story`    | Stories           |
| `reel`     | Reels/Shorts      |
| `bio`      | Profile link      |
| `dm`       | Direct message    |
| `email`    | Email             |
| `referral` | Referral          |

**❌ ЗАБОРОНЕНО:** `paid`, `free`, `ads`, `social-media`

### utm_campaign (Кампанія)

**Формат:** `{product}_{audience}_{goal}_{date}`

**Examples:**

```
smm_clinics_leads_2025q1
branding_doctors_awareness_jan25
seo_pharma_leads_2025-01
```

### utm_content (Варіант креативу)

**Формат:** `{format}_{variant}_{cta}`

**Examples:**

```
carousel_v1_book-call
video_testimonial_learn-more
static_case-study_contact
```

### utm_term (Keyword/Targeting)

**Examples:**

```
медичний+маркетинг
smm+для+клінік
реклама+лікарських+засобів
```

---

## 🎯 Lead Stages

**Flow:** `NEW → CONTACTED → MQL → SQL → OPPORTUNITY → CLOSED`

| Стадія          | Визначення              |
| --------------- | ----------------------- |
| **NEW**         | Форма заповнена         |
| **CONTACTED**   | Перший контакт зроблено |
| **MQL**         | Marketing Qualified     |
| **SQL**         | Sales Qualified         |
| **OPPORTUNITY** | Активна угода           |
| **CLOSED-WON**  | Угода закрита           |
| **CLOSED-LOST** | Відмова                 |

**CLOSED-LOST причини (обов'язково фіксувати!):**

- `budget` - Недостатній бюджет
- `timing` - Не зараз
- `competitor` - Вибрав конкурента
- `no_need` - Відпала потреба
- `no_response` - Не відповідає
- `spam` - Spam/fake
- `duplicate` - Дублікат

---

## 📊 Атрибуція

**Обрана модель:** First Touch + Last Touch

**Attribution Window:**

| Тип конверсії | Window  |
| ------------- | ------- |
| Lead form     | 30 днів |
| Consultation  | 60 днів |
| Sale          | 90 днів |

**Storage:**

```javascript
// First Touch (зберігається назавжди)
localStorage.setItem(
	'medici_first_touch',
	JSON.stringify({
		source: 'instagram',
		medium: 'cpc',
		campaign: 'smm_clinics_leads_2025q1',
		timestamp: '2025-01-15T10:30:00Z',
	}),
);

// Last Touch (перезаписується)
sessionStorage.setItem('medici_last_touch', JSON.stringify({...}));
```

**Cross-device stitching:**

1. **Anonymous:** Cookie ID + Device fingerprint
2. **Known:** Email як primary key
3. **Merge:** При заповненні форми з'єднуємо anonymous + known

---

## ✅ Валідація даних

### Frontend Validation

```javascript
const VALIDATION_RULES = {
	email: {
		required: true,
		pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
		blocklist: ['tempmail.com', 'guerrillamail.com', '10minutemail.com'],
		testPatterns: ['test@', 'demo@', 'example@'],
	},
	phone: {
		required: true,
		minDigits: 10,
		pattern: /^\+?380\d{9}$/, // Україна
	},
	name: {
		required: true,
		minLength: 2,
		blocklist: ['test', 'asd', 'qwe', '123'],
	},
};
```

### Backend Validation

```php
// inc/lead-validation.php

const SPAM_INDICATORS = [
	'too_fast' => 'Форма заповнена < 3 секунд',
	'honeypot' => 'Honeypot field заповнено',
	'suspicious_ip' => 'IP в blacklist або datacenter',
	'repeat_submit' => 'Та сама email за останню годину',
	'invalid_phone' => 'Телефон не існує (Twilio lookup)',
];

const QUALITY_SCORE = [
	'has_phone' => +20,
	'has_message' => +15,
	'long_message' => +10,
	'business_email' => +15,
	'returning' => +10,
	'read_blog' => +5,
	'temp_email' => -50,
	'suspicious' => -30,
];
```

### UTM Валідація

```php
const VALID_SOURCES = [
	'google',
	'facebook',
	'instagram',
	'linkedin',
	'telegram',
	'email',
	'direct',
	'referral',
];

const VALID_MEDIUMS = [
	'cpc',
	'cpm',
	'organic',
	'social',
	'post',
	'story',
	'reel',
	'bio',
	'dm',
	'email',
	'referral',
];

function validate_utm($utm_source, $utm_medium): array
{
	$source = strtolower(trim($utm_source));
	$medium = strtolower(trim($utm_medium));

	// Auto-correct common mistakes
	$source_fixes = [
		'insta' => 'instagram',
		'ig' => 'instagram',
		'fb' => 'facebook',
		'ln' => 'linkedin',
		'tg' => 'telegram',
	];

	if (isset($source_fixes[$source])) {
		$source = $source_fixes[$source];
		log_utm_correction($utm_source, $source);
	}

	return [
		'source' => in_array($source, VALID_SOURCES) ? $source : 'direct',
		'medium' => in_array($medium, VALID_MEDIUMS) ? $medium : 'unknown',
	];
}
```

---

## 📈 KPIs (Моніторинг)

| Метрика             | Ціль    | Alert   |
| ------------------- | ------- | ------- |
| % лідів без UTM     | < 10%   | > 20%   |
| % невалідних UTM    | < 5%    | > 10%   |
| % spam/fake лідів   | < 5%    | > 10%   |
| % дублікатів        | < 3%    | > 5%    |
| Response time P1    | < 1 год | > 2 год |
| MQL conversion rate | > 30%   | < 20%   |
| SQL conversion rate | > 50%   | < 30%   |

---

**Документ підтримується:** Marketing Team
**Останнє оновлення:** 2025-12-15

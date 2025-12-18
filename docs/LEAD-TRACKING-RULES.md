# Lead Tracking Rules — Medici Agency

> **Мета:** Забезпечити якість даних та консистентність метрик по всій воронці продажів.
> **Версія:** 1.0.0
> **Дата:** 2025-12-15

---

## 📋 Зміст

1. [Сліпі зони та ризики](#-сліпі-зони-та-ризики)
2. [UTM Governance](#-utm-governance)
3. [Naming Conventions](#-naming-conventions)
4. [Lead Stages (MQL/SQL)](#-lead-stages-mqlsql)
5. [Атрибуція](#-атрибуція)
6. [Валідація даних](#-валідація-даних)
7. [SLA та процеси](#-sla-та-процеси)
8. [Чек-лист впровадження](#-чек-лист-впровадження)

---

## 🚨 Сліпі зони та ризики

### Категорія 1: Технічні обмеження

| #   | Сліпа зона                | Ризик                                                            | Мітигація                             |
| --- | ------------------------- | ---------------------------------------------------------------- | ------------------------------------- |
| 1   | **Cross-device tracking** | Користувач бачить рекламу на телефоні, конвертується на десктопі | First-party cookies + email як ключ   |
| 2   | **iOS 14.5+ ATT**         | ~70% opt-out з Facebook/Instagram tracking                       | Server-side tracking (CAPI)           |
| 3   | **Ad blockers**           | ~30% користувачів блокують analytics                             | Server-side events, fallback tracking |
| 4   | **Cookie expiration**     | Chrome 24h for 3rd party, Safari 7 days 1st party                | localStorage + server session         |
| 5   | **Redirect chains**       | UTM губиться при редиректах                                      | Canonical UTM storage на landing      |
| 6   | **AMP pages**             | Окремий tracking context                                         | AMP Client ID handoff                 |

### Категорія 2: Людський фактор

| #   | Сліпа зона                   | Ризик                             | Мітигація                       |
| --- | ---------------------------- | --------------------------------- | ------------------------------- |
| 7   | **UTM хаос**                 | `instagram` vs `insta` vs `IG`    | Строгий словник + валідація     |
| 8   | **Забули змінити статус**    | Лід "застряг" в NEW назавжди      | Автоматичні нагадування + звіти |
| 9   | **Суб'єктивна кваліфікація** | Різні критерії MQL у різних людей | Чіткий чек-лист критеріїв       |
| 10  | **Copy-paste помилки**       | Неправильний UTM в посиланні      | URL Builder + QR preview        |
| 11  | **Забули consent**           | GDPR штраф                        | Обов'язкове поле + audit log    |

### Категорія 3: Бізнес-логіка

| #   | Сліпа зона               | Ризик                              | Мітигація                     |
| --- | ------------------------ | ---------------------------------- | ----------------------------- |
| 12  | **Дублікати лідів**      | Один клієнт = 3 ліди (різні форми) | Dedupe по email/phone         |
| 13  | **Offline конверсії**    | Телефонний дзвінок не трекається   | Call tracking + manual import |
| 14  | **Довгий sales cycle**   | Атрибуція "протухає" (90+ днів)    | Extended attribution window   |
| 15  | **Повторні клієнти**     | Upsell vs New lead confusion       | Customer ID tracking          |
| 16  | **Referral attribution** | "Друг порадив" не має UTM          | Referral program tracking     |
| 17  | **Dark social**          | Копіювання URL без UTM             | Short links + default UTM     |

### Категорія 4: Spam та якість

| #   | Сліпа зона              | Ризик                            | Мітигація                     |
| --- | ----------------------- | -------------------------------- | ----------------------------- |
| 18  | **Bot submissions**     | Fake leads забруднюють дані      | Honeypot + reCAPTCHA v3       |
| 19  | **Competitor research** | Конкуренти заповнюють форми      | IP filtering + behavior score |
| 20  | **Test submissions**    | QA ліди в production             | Test email domain filter      |
| 21  | **Incomplete data**     | Email без телефону = low quality | Progressive profiling         |

---

## 🏷️ UTM Governance

### Обов'язкові правила

```
✅ ФОРМАТ: lowercase, snake_case, без пробілів
✅ МОВА: English only (для analytics tools)
✅ ДОВЖИНА: max 50 символів per parameter
```

### utm_source (Платформа/Канал)

| Значення    | Опис                          | Приклади використання   |
| ----------- | ----------------------------- | ----------------------- |
| `google`    | Google Ads, Search, Display   | Paid search, GDN        |
| `facebook`  | Facebook Ads, Posts           | Lead ads, boosted posts |
| `instagram` | Instagram Ads, Posts, Stories | Reels, stories, feed    |
| `linkedin`  | LinkedIn Ads, Posts           | Sponsored content       |
| `telegram`  | Telegram канал/бот            | Posts, bot messages     |
| `email`     | Email розсилки                | Newsletter, sequences   |
| `direct`    | Прямий трафік                 | Без referrer            |
| `referral`  | Реферальний трафік            | Partner sites           |

**❌ ЗАБОРОНЕНО:** `insta`, `fb`, `ig`, `Google`, `FACEBOOK`, `e-mail`

### utm_medium (Тип трафіку)

| Значення   | Опис              | Коли використовувати          |
| ---------- | ----------------- | ----------------------------- |
| `cpc`      | Cost per click    | Платна реклама (обов'язково!) |
| `cpm`      | Cost per mille    | Display, awareness campaigns  |
| `organic`  | Органічний трафік | SEO, unpaid search            |
| `social`   | Social organic    | Unpaid posts                  |
| `post`     | Публікація        | Feed posts                    |
| `story`    | Stories           | Instagram/Facebook stories    |
| `reel`     | Reels/Shorts      | Short video                   |
| `bio`      | Profile link      | Link in bio                   |
| `dm`       | Direct message    | Personal outreach             |
| `email`    | Email             | Newsletter clicks             |
| `referral` | Referral          | Partner links                 |

**❌ ЗАБОРОНЕНО:** `paid`, `free`, `ads`, `social-media`

### utm_campaign (Кампанія)

**Формат:** `{product}_{audience}_{goal}_{date}`

| Компонент    | Значення  | Приклади                         |
| ------------ | --------- | -------------------------------- |
| `{product}`  | Послуга   | `smm`, `seo`, `branding`, `ads`  |
| `{audience}` | Аудиторія | `doctors`, `clinics`, `pharma`   |
| `{goal}`     | Мета      | `awareness`, `leads`, `retarget` |
| `{date}`     | Період    | `2025q1`, `2025-01`, `jan25`     |

**Приклади:**

```
smm_clinics_leads_2025q1
branding_doctors_awareness_jan25
seo_pharma_leads_2025-01
```

### utm_content (Варіант креативу)

**Формат:** `{format}_{variant}_{cta}`

```
carousel_v1_book-call
video_testimonial_learn-more
static_case-study_contact
```

### utm_term (Keyword/Targeting)

Для paid search:

```
медичний+маркетинг
smm+для+клінік
реклама+лікарських+засобів
```

---

## 📝 Naming Conventions

### Campaigns (Ads Manager)

**Формат:** `[Client]_[Product]_[Audience]_[Objective]_[Date]`

```
Medici_SMM_Doctors-Kyiv_Leads_2025-01
Medici_Branding_Clinics-UA_Awareness_2025-Q1
```

### Ad Sets / Audiences

**Формат:** `[Targeting]_[Placement]_[Optimization]`

```
Doctors-35-55-Kyiv_Feed-Stories_Conversions
Clinics-Lookalike-1%_AllPlacements_LeadGen
```

### Ads / Creatives

**Формат:** `[Format]_[Theme]_[CTA]_[Version]`

```
Carousel_CaseStudy_BookCall_v1
Video_Testimonial_LearnMore_v2
Static_Benefits_Contact_v3
```

---

## 🎯 Lead Stages (MQL/SQL)

### Визначення стадій

```
┌─────────────────────────────────────────────────────────────┐
│  NEW → CONTACTED → MQL → SQL → OPPORTUNITY → CLOSED        │
└─────────────────────────────────────────────────────────────┘
```

| Стадія          | Визначення              | Критерії переходу       | SLA        |
| --------------- | ----------------------- | ----------------------- | ---------- |
| **NEW**         | Форма заповнена         | Автоматично             | —          |
| **CONTACTED**   | Перший контакт зроблено | Відповідь email/дзвінок | ≤4 години  |
| **MQL**         | Marketing Qualified     | Див. MQL Checklist      | ≤24 години |
| **SQL**         | Sales Qualified         | Див. SQL Checklist      | ≤48 годин  |
| **OPPORTUNITY** | Активна угода           | КП відправлено          | —          |
| **CLOSED-WON**  | Угода закрита           | Оплата отримана         | —          |
| **CLOSED-LOST** | Відмова                 | Причина зафіксована     | —          |

### MQL Checklist (Marketing Qualified Lead)

Лід стає MQL якщо **≥3 з 5** критеріїв виконано:

- [ ] **Бюджет:** Має/планує бюджет на маркетинг
- [ ] **Потреба:** Потребує нашу послугу (SMM, SEO, Branding, Ads)
- [ ] **Повноваження:** ЛПР або впливає на рішення
- [ ] **Термін:** Планує почати протягом 1-3 місяців
- [ ] **Engagement:** Відповів на follow-up / відвідав сайт повторно

### SQL Checklist (Sales Qualified Lead)

Лід стає SQL якщо **ВСІ** критерії виконано:

- [ ] **MQL критерії** виконані
- [ ] **Discovery call** проведено
- [ ] **Потреба підтверджена** конкретними задачами
- [ ] **Бюджет узгоджено** (хоча б діапазон)
- [ ] **Timeline визначено** (коли планує стартувати)
- [ ] **Наступний крок** узгоджено (КП, зустріч)

### Причини CLOSED-LOST (обов'язково фіксувати!)

| Код           | Причина            | Дія                         |
| ------------- | ------------------ | --------------------------- |
| `budget`      | Недостатній бюджет | Nurturing sequence          |
| `timing`      | Не зараз (timing)  | Follow-up через 3 міс       |
| `competitor`  | Вибрав конкурента  | Win/loss analysis           |
| `no_need`     | Відпала потреба    | Archive                     |
| `no_response` | Не відповідає      | 3 follow-ups, потім archive |
| `spam`        | Spam/fake          | Blacklist                   |
| `duplicate`   | Дублікат           | Merge з основним            |

---

## 📊 Атрибуція

### Обрана модель: First Touch + Last Touch

| Модель          | Що вимірює      | Де використовувати                |
| --------------- | --------------- | --------------------------------- |
| **First Touch** | Хто привів ліда | Awareness метрики, CAC по каналах |
| **Last Touch**  | Що конвертувало | Conversion метрики, CPA           |

### Attribution Window

| Тип конверсії | Window  | Обґрунтування        |
| ------------- | ------- | -------------------- |
| Lead form     | 30 днів | Стандартний B2B цикл |
| Consultation  | 60 днів | Довгий цикл рішення  |
| Sale          | 90 днів | Enterprise deals     |

### Як зберігати атрибуцію

```javascript
// First Touch (зберігається назавжди)
localStorage.setItem('medici_first_touch', JSON.stringify({
  source: 'instagram',
  medium: 'cpc',
  campaign: 'smm_clinics_leads_2025q1',
  timestamp: '2025-01-15T10:30:00Z'
}));

// Last Touch (перезаписується)
sessionStorage.setItem('medici_last_touch', JSON.stringify({...}));
```

### Cross-device stitching

1. **Anonymous:** Cookie ID + Device fingerprint
2. **Known:** Email як primary key
3. **Merge:** При заповненні форми з'єднуємо anonymous + known

---

## ✅ Валідація даних

### На рівні форми (Frontend)

```javascript
const VALIDATION_RULES = {
	email: {
		required: true,
		pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
		// Блокуємо temp emails
		blocklist: ['tempmail.com', 'guerrillamail.com', '10minutemail.com'],
		// Блокуємо тестові
		testPatterns: ['test@', 'demo@', 'example@'],
	},
	phone: {
		required: true,
		minDigits: 10,
		// Україна: +380XXXXXXXXX
		pattern: /^\+?380\d{9}$/,
	},
	name: {
		required: true,
		minLength: 2,
		// Блокуємо підозрілі
		blocklist: ['test', 'asd', 'qwe', '123'],
	},
};
```

### На рівні сервера (Backend)

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
	'has_phone' => +20, // Вказав телефон
	'has_message' => +15, // Написав повідомлення
	'long_message' => +10, // Повідомлення > 100 символів
	'business_email' => +15, // Корпоративна пошта (не gmail/yahoo)
	'returning' => +10, // Повторний візит
	'read_blog' => +5, // Читав блог
	'temp_email' => -50, // Тимчасова пошта
	'suspicious' => -30, // Spam indicators
];
```

### UTM Валідація (обов'язково!)

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

function validate_utm($utm_source, $utm_medium)
{
	// Normalize to lowercase
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
		// Log correction for monitoring
		log_utm_correction($utm_source, $source);
	}

	return [
		'source' => in_array($source, VALID_SOURCES) ? $source : 'direct',
		'medium' => in_array($medium, VALID_MEDIUMS) ? $medium : 'unknown',
	];
}
```

---

## ⏱️ SLA та процеси

### Response Time SLA

| Пріоритет   | Критерій    | Response Time | Escalation |
| ----------- | ----------- | ------------- | ---------- |
| **P1 Hot**  | Score ≥ 70  | ≤1 година     | CEO        |
| **P2 Warm** | Score 40-69 | ≤4 години     | Sales Lead |
| **P3 Cold** | Score < 40  | ≤24 години    | —          |

### Процес обробки ліда

```
1. NEW LEAD
   ↓ (автоматично)
2. NOTIFICATION
   - Email менеджеру
   - Telegram alert (Hot leads)
   - CRM task created
   ↓ (≤1-4 години)
3. FIRST CONTACT
   - Дзвінок/email
   - Статус → CONTACTED
   - Notes в CRM
   ↓ (≤24 години)
4. QUALIFICATION
   - MQL checklist
   - Статус → MQL або CLOSED-LOST
   ↓ (≤48 годин)
5. SALES HANDOFF
   - SQL checklist
   - Discovery call
   - Статус → SQL
```

### Автоматизація нагадувань

```
IF lead.status = 'NEW' AND lead.created_at < NOW() - 4 hours:
  → Send reminder to owner
  → Escalate to manager if > 8 hours

IF lead.status = 'CONTACTED' AND last_activity < NOW() - 48 hours:
  → Send follow-up reminder
  → Auto-create follow-up task

IF lead.status = 'MQL' AND no_activity > 7 days:
  → Send "Are you still interested?" email
  → Alert sales manager
```

---

## 📋 Чек-лист впровадження

### Фаза 1: Документація (День 1)

- [ ] UTM словник затверджено командою
- [ ] Lead stages визначено та задокументовано
- [ ] MQL/SQL критерії узгоджено з Sales
- [ ] SLA визначено та погоджено
- [ ] Closed-Lost причини затверджено

### Фаза 2: Технічна підготовка (День 2-3)

- [ ] UTM validation на формах
- [ ] First/Last touch tracking впроваджено
- [ ] Honeypot + spam detection налаштовано
- [ ] Email validation (temp email blocking)
- [ ] Phone validation
- [ ] Duplicate detection по email/phone
- [ ] Test submissions filtering

### Фаза 3: Інструменти (День 4-5)

- [ ] URL Builder (Google Sheet або tool)
- [ ] QR code generator з UTM
- [ ] Short link service налаштовано (bit.ly/rebrandly)
- [ ] UTM audit dashboard

### Фаза 4: Навчання (День 6)

- [ ] Training для маркетинг команди (UTM rules)
- [ ] Training для Sales (Lead stages, SLA)
- [ ] Documentation shared та accessible

### Фаза 5: Моніторинг (Ongoing)

- [ ] Weekly UTM audit (% без UTM, некоректні)
- [ ] Response time tracking (SLA compliance)
- [ ] Lead quality score distribution
- [ ] Conversion rate by stage
- [ ] Attribution report review

---

## 🔧 Корисні інструменти

### URL Builders

1. **Google Campaign URL Builder**
   https://ga-dev-tools.google/campaign-url-builder/

2. **UTM.io** (з templates)
   https://utm.io/

3. **Custom Google Sheet** (рекомендовано)
   - Dropdown для source/medium
   - Auto-validation
   - History log

### Short Links

1. **Bitly** - Базовий функціонал
2. **Rebrandly** - Custom domain
3. **Short.io** - Self-hosted option

### Validation Services

1. **ZeroBounce** - Email validation
2. **NumVerify** - Phone validation
3. **IPQualityScore** - Spam/bot detection

---

## 📈 KPIs для моніторингу якості даних

| Метрика             | Ціль    | Alert   |
| ------------------- | ------- | ------- |
| % лідів без UTM     | < 10%   | > 20%   |
| % невалідних UTM    | < 5%    | > 10%   |
| % spam/fake лідів   | < 5%    | > 10%   |
| % дублікатів        | < 3%    | > 5%    |
| Response time P1    | < 1 год | > 2 год |
| Response time P2    | < 4 год | > 8 год |
| MQL conversion rate | > 30%   | < 20%   |
| SQL conversion rate | > 50%   | < 30%   |

---

## 📚 Додаткові ресурси

- [Google Analytics UTM Best Practices](https://support.google.com/analytics/answer/1033863)
- [HubSpot Lead Scoring Guide](https://blog.hubspot.com/marketing/lead-scoring-instructions)
- [Salesforce Lead Management](https://www.salesforce.com/products/sales-cloud/lead-management/)

---

**Документ підтримується:** Marketing Team
**Останнє оновлення:** 2025-12-15
**Наступний review:** 2025-03-15

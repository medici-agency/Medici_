# TODO - Medici Theme & Marketing Roadmap

## 🗺️ ДОРОЖНЯ КАРТА ПРОЕКТУ

### Екосистема Medici Agency

```
                    ┌─────────────────────────────────────┐
                    │          🌐 САЙТ                    │
                    │      medici.agency                  │
                    │   (Віртуальний офіс / Hub)          │
                    │                                     │
                    │  • Портфоліо та кейси              │
                    │  • Форма консультації              │
                    │  • Блог з експертизою              │
                    │  • Lead CPT + Інтеграції           │
                    └───────────────┬─────────────────────┘
                                    │
          ┌─────────────────────────┼─────────────────────────┐
          │                         │                         │
          ▼                         ▼                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   INSTAGRAM     │     │    FACEBOOK     │     │    LINKEDIN     │
│                 │     │                 │     │                 │
│ • Візуальний    │     │ • Реклама       │     │ • B2B контент   │
│   контент       │     │ • Таргетинг     │     │ • Нетворкінг    │
│ • Stories/Reels │     │ • Messenger     │     │ • Експертиза    │
│ • DM → Сайт     │     │ • DM → Сайт     │     │ • DM → Сайт     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
          │                         │                         │
          └─────────────────────────┼─────────────────────────┘
                                    │
                                    ▼
                    ┌─────────────────────────────────────┐
                    │            ⚡ ZAPIER                 │
                    │        (Автоматизація)              │
                    │                                     │
                    │  • Новий лід → Email + Telegram     │
                    │  • Форма → Google Sheets            │
                    │  • Публікація → Соцмережі           │
                    └───────────────┬─────────────────────┘
                                    │
          ┌─────────────────────────┼─────────────────────────┐
          │                         │                         │
          ▼                         ▼                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    TELEGRAM     │     │     VIBER       │     │  GOOGLE SHEETS  │
│                 │     │                 │     │                 │
│ • Канал/Дзеркало│     │ • Чат підтримки │     │ • CRM (ліди)    │
│ • Бот підтримки │     │ • Сповіщення    │     │ • Аналітика     │
│ • Сповіщення    │     │ • Quick replies │     │ • Звіти         │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 🎯 ФАЗИ РОЗВИТКУ

### Фаза 1: Фундамент (✅ Завершено)

- [x] PHPStan + Composer DevOps
- [x] GitHub Actions CI/CD
- [x] Pre-commit hooks
- [x] Database Optimization (indexes)
- [x] Cache Manager (Transients)
- [x] Lead CPT + Email/Telegram інтеграції
- [x] Blog система (CPT, meta fields, TOC)
- [x] **Code Quality Tools** — Prettier + ESLint + StyleLint ✅ 2025-12-17
  - Prettier 3.4.2 (автоформатування CSS/JS/PHP/JSON/MD)
  - ESLint 8.57.1 (@wordpress/eslint-plugin)
  - StyleLint 16.10.0 (BEM validation, property order)
  - VS Code integration (format on save)
  - GitHub Actions CI/CD (3 нові jobs)
  - Pre-commit hook integration (6 перевірок)
  - Документація: CODE-QUALITY.md
- [x] **CLAUDE.md Critical Update** — @prettier/plugin-php помилка ✅ 2025-12-19
  - Додано критичну помилку #7 (Missing @prettier/plugin-php)
  - Посилено "MANDATORY PRE-COMMIT WORKFLOW" (крок #0)
  - Оновлено "ПЕРЕД НАПИСАННЯМ КОДУ" (+2 пункти)
  - Розширено секцію "ЗАБОРОНЕНО" (+3 заборони)
  - Bash скрипт автоперевірки node_modules/@prettier/
  - Золоте правило: npm run format:check перед комітом
  - Мета: уникнення повторних помилок з npm залежностями

### Фаза 2: Аналітика та трекінг (✅ Завершено)

- [ ] Microsoft Clarity інтеграція (heatmaps)
- [x] GA4 Events (scroll, time, CTA clicks) ✅ 2025-12-17
- [x] UTM стратегія для соцмереж ✅ 2025-12-17
- [x] Lead Scoring система ✅ 2025-12-17
- [x] WordPress Global Styles (theme.json) ✅ 2025-12-17
- [x] Dashboard Lead Scoring widget ✅ 2025-12-17

### Фаза 3: Автоматизація (📋 Планується)

- [ ] Zapier інтеграція (webhooks)
- [ ] Telegram бот
- [ ] Viber канал
- [ ] Автопостинг у соцмережі

### Фаза 4: Оптимізація UX (✅ Завершено)

- [x] Fade-in анімації (scroll-triggered) — реалізовано в scripts.js + layout.css
- [x] Exit-intent popup — HYBRID (bioEp + GenerateBlocks Overlay Panel + OOP Architecture)
  - GenerateBlocks Overlay Panel (Scale In анімація, backdrop blur)
  - bioEp library (exit-intent detection + 30-day cookies)
  - Events API form handler (Lead CPT integration)
  - OOP Architecture (WordPress Plugin Boilerplate pattern)
  - Commits: 58ff25b (GenerateBlocks), c647c1e (Hybrid), 8355861 (OOP)
- [ ] A/B тестування форм

### Фаза 5: OOP Refactoring v2.0.0 (✅ Завершено 2025-12-18)

- [x] **Blog Module** (`inc/blog/`) — Repository + Service Pattern
  - [x] `BlogPostRepository.php` — Data access abstraction
  - [x] `ReadingTimeService.php` — Reading time calculation
  - [x] `PostViewsService.php` — Atomic view counting with sessions
  - [x] `bootstrap.php` — Module initialization
- [x] **Lead Module** (`inc/lead/`) — Adapter Pattern
  - [x] `IntegrationInterface.php` — Contract for all integrations
  - [x] `AbstractIntegration.php` — Base class with error handling
  - [x] `EmailAdapter.php` — HTML email notifications
  - [x] `TelegramAdapter.php` — Telegram Bot API
  - [x] `GoogleSheetsAdapter.php` — Apps Script integration
  - [x] `IntegrationManager.php` — Orchestrates adapters
- [x] **Events Module** (`inc/events/`) — Event Dispatcher + Observer
  - [x] `EventInterface.php` + `AbstractEvent.php` — Event contracts
  - [x] `EventDispatcher.php` — Central event bus (Singleton)
  - [x] `ConsultationRequestEvent.php`, `NewsletterSubscribeEvent.php` — Concrete events
  - [x] 4 Observers: Logging, LeadCreation, Integration, Webhook
- [x] **PHPStan Compliance**
  - [x] EventInterface: getEventId/setEventId methods
  - [x] All modules pass PHPStan Level 5
- [x] **Commits:** 8e5180d, a83361d, 4e0a5ae
- [x] **Statistics:** +4211 рядків, 23 файли, 3 модульні директорії

### Фаза 5.1: Code Audit Fixes v2.0.1 (✅ Завершено 2025-12-18)

- [x] **Duplicate Handler Fixes**
  - [x] Вимкнено дублювання AJAX handlers в `events/bootstrap.php`
  - [x] Вимкнено дублювання views tracking в `blog-meta-fields.php`
- [x] **Version Synchronization**
  - [x] `style.css` version: 1.4.0 → 2.0.0
- [x] **Performance Optimization**
  - [x] Додано `no_found_rows => true` до 6 WP_Query
- [x] **Security Fixes**
  - [x] Telegram Markdown escaping (UTM values)
  - [x] Input length validation (name, email, phone, message)
- [x] **Verification (no action needed)**
  - [x] SQL injection in rest-api.php — вже sanitized via absint()

### Фаза 6: Legacy → OOP Migration (✅ Завершено 2025-12-19)

- [x] **OOP Observers тепер викликаються**
  - Legacy `class-events.php` dispatch'ить події через `EventDispatcher`
  - Метод `dispatch_oop_event()` створює OOP події
  - Lead ID передається для уникнення дублювання
- [x] **Дублювання коду усунено**
  - `lead-integrations.php` → deprecated wrapper для `IntegrationManager`
  - `LeadCreationObserver` перевіряє чи лід вже створений
  - Інтеграції викликаються тільки через OOP `IntegrationObserver`
- [x] **Legacy файли збережено для backwards compatibility**
  - `inc/lead-integrations.php` — deprecated, делегує на OOP
  - `inc/blog-cache.php` — залишено (унікальний функціонал кешування)

---

## 🔴 ВИСОКИЙ ПРІОРИТЕТ

### Lead Tracking & Analytics

- [ ] **Microsoft Clarity** — безкоштовні heatmaps та session recording
  - Реєстрація: https://clarity.microsoft.com
  - Додати tracking code в `<head>`
  - Час: 15 хв
- [x] **GA4 Events** — відстеження поведінки користувачів ✅ 2025-12-17
  - ✅ Scroll depth (25%, 50%, 75%, 100%) — `js/analytics.js`
  - ✅ Time on page (30s, 60s, 2min, 5min) — `js/analytics.js`
  - ✅ CTA clicks (data-track-cta атрибут) — `js/analytics.js`
  - ✅ Form interactions — `js/analytics.js`
  - ✅ Admin settings — `inc/analytics.php`
- [x] **UTM Builder** — створити шаблони для всіх каналів ✅ 2025-12-17
  - ✅ Instagram, Facebook, LinkedIn presets — `inc/analytics.php`
  - ✅ UTM Builder в admin — Settings → Medici → UTM Builder

### Lead Management

- [x] **Lead Scoring** — пріоритезація лідів ✅ 2025-12-17
  - ✅ За джерелом (LinkedIn > Google > Instagram > Direct) — `inc/lead-scoring.php`
  - ✅ За послугою (Branding > Advertising > SMM) — `inc/lead-scoring.php`
  - ✅ За engagement (visited services, read blog, time on site)
  - ✅ Dashboard widget — `inc/dashboard-analytics.php`
  - ✅ Hot/Warm/Cold визуалізація (70+/40-69/0-39)
- [ ] **Lead Status Automation** — автоматичні статуси
  - Новий → 24 год без відповіді → Нагадування
  - Contacted → 7 днів → Follow-up
  - Час: 1 год

---

## 🟡 СЕРЕДНІЙ ПРІОРИТЕТ

### Zapier Integration

- [ ] **Webhook endpoint** для Zapier
  - POST `/wp-json/medici/v1/zapier/lead`
  - Верифікація через secret key
  - Файл: `inc/zapier-integration.php`
  - Час: 2 год
- [ ] **Zaps для автоматизації:**
  - Новий лід → Slack/Discord notification
  - Новий лід → Trello card
  - Публікація блогу → Twitter/LinkedIn post
  - Час: 1 год кожен

### Telegram Integration

- [ ] **Telegram канал** — дзеркало контенту сайту
  - Автопостинг нових статей блогу
  - Анонси послуг
  - Час: 1 год setup
- [ ] **Telegram бот** — підтримка та ліди
  - /start — привітання та меню
  - /services — список послуг
  - /consultation — форма заявки
  - /contact — контакти
  - Файл: `inc/telegram-bot.php` (webhook)
  - Час: 4 год

### Viber Integration

- [ ] **Viber Business** — додатковий канал
  - Підключення через Viber API
  - Сповіщення про нові ліди
  - Quick replies для FAQ
  - Час: 2 год

### UI/UX Improvements

- [ ] **Fade-in анімації** — scroll-triggered

  ```css
  .fade-in {
  	opacity: 0;
  	transform: translateY(20px);
  }
  .fade-in.visible {
  	opacity: 1;
  	transform: translateY(0);
  }
  ```

  - Файл: `css/components/animations.css`
  - Час: 1 год

- [x] **BEM naming** — консистентність у CSS ✅ Виконано 2025-12-15
  - Рефакторинг forms.css, navigation.css, cards.css, layout.css
  - Backwards compatibility секції для legacy class names
- [x] **JS hooks** — `js-*` класи для JavaScript ✅ Виконано 2025-12-15
  - scripts.js, forms-consultation.js, forms-newsletter.js
  - Приклад: `<button class="btn js-open-modal">`

### Design System Integration

- [x] **WordPress Global Styles (theme.json)** — повна інтеграція з GeneratePress + GenerateBlocks ✅ 2025-12-17

  **Реалізовано:**
  - ✅ `theme.json` (версія 3, WordPress 6.5+) — **СТВОРЕНО**
  - ✅ 14 кольорів palette (primary, hot-lead, warm-lead, cold-lead, etc.)
  - ✅ 8 fluid font sizes (Utopia scale)
  - ✅ 9 spacing sizes (3xs → 3xl)
  - ✅ 6 shadow presets (sm, md, lg, xl, card, card-hover)
  - ✅ Lead Scoring thresholds у custom settings (hot: 70, warm: 40, cold: 0)
  - ✅ Typography settings (Montserrat primary, System fallback)
  - ✅ Border radius settings (sm, md, lg, xl, full)
  - ✅ Transition settings (base, fast, slow)

  **Файли:**
  - `theme.json` — WordPress Global Styles (460 рядків)

  **Майбутні покращення (опціонально):**
  - [ ] **GenerateBlocks Pro інтеграція**
    - Global Styles для Container, Headline, Button, Grid blocks
    - Використання theme.json color presets у GB editor
  - [ ] **GeneratePress Premium синхронізація**
    - GP Customizer colors → theme.json (двонаправлена синхронізація)
  - [ ] **Gutenberg Editor Styles**
    - Editor styles = Frontend styles (100% відповідність)

---

## 🟢 НИЗЬКИЙ ПРІОРИТЕТ

### Content & Social Media

- [ ] **Instagram** — візуальна присутність
  - Link in bio з UTM
  - Stories з CTA → Сайт
  - Reels з експертизою
- [ ] **Facebook** — реклама та community
  - Business Page setup
  - Facebook Pixel інтеграція
  - Messenger інтеграція
- [ ] **LinkedIn** — B2B позиціонування
  - Company Page
  - LinkedIn Insight Tag
  - Контент-план для експертизи

### Advanced Features

- [x] **Exit-intent popup** — захоплення лідів що йдуть ✅ ЗАВЕРШЕНО
  - JavaScript: `js/exit-intent.js`
  - CSS: `css/components/forms.css` (exit-popup секція)
  - Показується при русі миші до верхньої частини екрану
  - Один раз за сесію (localStorage)
  - Тільки desktop (> 1024px)
  - Інтеграція з Events API
- [ ] **A/B тестування форм** — оптимізація конверсії
  - Різні заголовки
  - Різна кількість полів
  - Час: 3 год
- [ ] **Newsletter subscription** — email маркетинг
  - MailChimp/Brevo інтеграція
  - Double opt-in
  - Час: 2 год

### Blog Enhancements

- [ ] Пагінація для архівів (AJAX load more)
- [ ] Social sharing buttons
- [ ] Estimated reading time progress bar

### Accessibility

- [ ] ARIA labels audit
- [ ] Keyboard navigation
- [ ] Color contrast (WCAG AA)
- [ ] Screen reader testing

---

## ✅ ЗАВЕРШЕНО

### v2.0.0 - PHP OOP Refactoring (2025-12-18)

- [x] **Lead Scoring Module** (Strategy Pattern)
  - ScoringStrategyInterface, ScoringConfig, ScoringService, ScoringAdmin
  - 4 стратегії: SourceStrategy, MediumStrategy, ServiceStrategy, BonusStrategy
  - Helper: `medici_scoring()->calculate($data)`
- [x] **Lead Validation Module** (Chain of Responsibility)
  - ValidatorInterface, ValidationResult (Value Object), ValidationService
  - 7 валідаторів: Email, Phone, Name, Message, Utm, Spam, Service
  - Helper: `medici_validation()->validate($data)`
- [x] **Schema Module** (Builder Pattern)
  - SchemaBuilderInterface, AbstractSchemaBuilder, SchemaConfig, SchemaRenderer
  - 4 builders: Organization, Faq, HowTo, Video
  - Helper: `medici_schema()->render()`, `medici_schema_build_*($content)`
- [x] PHPStan помилки виправлено (getInstance, setContent, function renaming)

### v1.8.0 - GA4 Analytics + Lead Scoring Dashboard + theme.json (2025-12-17)

- [x] GA4 Events tracking (scroll depth, time on page, CTA clicks, form interactions)
- [x] UTM Builder з presets для соцмереж (Instagram, Facebook, LinkedIn)
- [x] Lead Scoring система (hot 70+, warm 40-69, cold 0-39)
- [x] Dashboard Lead Scoring widget з візуалізацією
- [x] WordPress Global Styles (theme.json v3)
- [x] 14 кольорів palette + Utopia typography + spacing scale
- [x] Lead Scoring thresholds у theme.json custom settings

### v1.7.0 - Exit-Intent Popup Fix (2025-12-16)

- [x] Exit-intent popup - 7 комітів troubleshooting
- [x] Public Form Handler (БЕЗ WordPress nonce)
- [x] SMTP Integration (mail.adm.tools)
- [x] Lead CPT integration з Events API
- [x] Security: IP rate limiting + honeypot + User-Agent + Referer
- [x] Виправлено 7 критичних багів (wp_is_mobile, defer, trackEvent, 403, тощо)

### v1.6.1 - BEM + JS Hooks (2025-12-15)

- [x] BEM CSS рефакторинг (forms, navigation, cards, layout)
- [x] JavaScript js-\* hooks для BEM separation
- [x] Backwards compatibility для legacy class names
- [x] Документація: `docs/FRONTEND-CONVENTIONS.md`

### v1.6.0 - Modern Solutions (2025-12-15)

- [x] PHPStan + Composer DevOps
- [x] GitHub Actions CI/CD
- [x] Pre-commit hooks
- [x] Database Optimization (`inc/database-optimization.php`)
- [x] Cache Manager (`inc/class-cache-manager.php`)
- [x] Видалено overkill: Webpack, PostCSS, PHPUnit, Web Workers

### v1.5.0 - Dashboard Analytics (2025-12-14)

- [x] Dashboard widgets (ліди, блог, SEO)
- [x] REST API endpoints
- [x] Lead bulk actions + CSV export
- [x] SEO Audit tool

### v1.4.0 - Lead Management (2025-12-13)

- [x] Lead CPT (`medici_lead`)
- [x] Email notifications
- [x] Telegram notifications
- [x] Google Sheets integration
- [x] Admin settings page

### v1.3.5 - Blog & Schema (2025-12-09)

- [x] Blog CPT + TOC
- [x] Schema.org (Organization, FAQ, HowTo, Video)
- [x] XML Sitemap optimization

---

## 📊 UTM СТРАТЕГІЯ

```
INSTAGRAM:
  Bio link:     ?utm_source=instagram&utm_medium=bio
  Stories:      ?utm_source=instagram&utm_medium=story&utm_campaign={name}
  Reels:        ?utm_source=instagram&utm_medium=reels
  DM:           ?utm_source=instagram&utm_medium=dm

FACEBOOK:
  Posts:        ?utm_source=facebook&utm_medium=post
  Ads:          ?utm_source=facebook&utm_medium=cpc&utm_campaign={name}
  Messenger:    ?utm_source=facebook&utm_medium=messenger

LINKEDIN:
  Profile:      ?utm_source=linkedin&utm_medium=profile
  Posts:        ?utm_source=linkedin&utm_medium=post
  DM:           ?utm_source=linkedin&utm_medium=dm
  Ads:          ?utm_source=linkedin&utm_medium=cpc&utm_campaign={name}

TELEGRAM:
  Channel:      ?utm_source=telegram&utm_medium=channel
  Bot:          ?utm_source=telegram&utm_medium=bot
  DM:           ?utm_source=telegram&utm_medium=dm

EMAIL:
  Newsletter:   ?utm_source=email&utm_medium=newsletter&utm_campaign={name}
  Transactional:?utm_source=email&utm_medium=transactional
```

---

## 🔧 КОМАНДИ РОЗРОБКИ

```bash
# PHP аналіз
composer phpstan          # Статичний аналіз
composer phpcs            # WordPress Coding Standards
composer lint             # Все разом

# Git hooks
./scripts/install-hooks.sh  # Встановити pre-commit
```

---

## 📝 ПРИМІТКИ

- Всі зміни проходять через PHPStan перед комітом
- CSS файли потребують перевірки балансу дужок
- UTM параметри обов'язкові для всіх зовнішніх посилань
- Lead tracking: кожен лід має мати джерело (utm_source)

---

**Last Updated:** 2025-12-19
**Theme Version:** 2.1.0
**Roadmap Version:** 1.4

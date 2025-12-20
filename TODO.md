# TODO - Medici Theme & Marketing Roadmap

## 🗺️ ДОРОЖНЯ КАРТА ПРОЕКТУ

### Екосистема Medici Agency

```
                    ┌─────────────────────────────────────┐
                    │          🌐 САЙТ                    │
                    │      medici.agency                  │
                    │   (Віртуальний офіс / Hub)          │
                    └───────────────┬─────────────────────┘
                                    │
          ┌─────────────────────────┼─────────────────────────┐
          │                         │                         │
          ▼                         ▼                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   INSTAGRAM     │     │    FACEBOOK     │     │    LINKEDIN     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
          │                         │                         │
          └─────────────────────────┼─────────────────────────┘
                                    │
                                    ▼
                    ┌─────────────────────────────────────┐
                    │            ⚡ ZAPIER                 │
                    │        (Автоматизація)              │
                    └───────────────┬─────────────────────┘
                                    │
          ┌─────────────────────────┼─────────────────────────┐
          │                         │                         │
          ▼                         ▼                         ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    TELEGRAM     │     │     VIBER       │     │  GOOGLE SHEETS  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 🎯 ФАЗИ РОЗВИТКУ

### Фаза 1: Фундамент (✅ Завершено)

- [x] PHPStan + Composer DevOps
- [x] GitHub Actions CI/CD
- [x] Lead CPT + Email/Telegram інтеграції
- [x] Blog система (CPT, meta fields, TOC)
- [x] Code Quality Tools (Prettier + ESLint + StyleLint)

### Фаза 2: Аналітика та трекінг (✅ Завершено)

- [x] GA4 Events
- [x] UTM стратегія для соцмереж
- [x] Lead Scoring система
- [x] WordPress Global Styles (theme.json)

### Фаза 3: Автоматизація (📋 Планується)

- [ ] Zapier інтеграція (webhooks)
- [ ] Telegram бот
- [ ] Viber канал
- [ ] Автопостинг у соцмережі

### Фаза 4: Оптимізація UX (✅ Завершено)

- [x] Fade-in анімації (scroll-triggered)
- [x] Exit-intent popup (HYBRID: bioEp + GenerateBlocks Overlay Panel + OOP)

### Фаза 5: OOP Refactoring v2.0.0 (✅ Завершено 2025-12-18)

- [x] Blog Module (Repository + Service Pattern)
- [x] Lead Module (Adapter Pattern)
- [x] Events Module (Event Dispatcher + Observer)
- [x] PHPStan Compliance (Level 5)

### Фаза 6: Legacy → OOP Migration (✅ Завершено 2025-12-19)

- [x] OOP Observers тепер викликаються
- [x] Дублювання коду усунено
- [x] Legacy файли збережено для backwards compatibility

---

## 🔴 ВИСОКИЙ ПРІОРИТЕТ

### Lead Tracking & Analytics

- [ ] **Microsoft Clarity** — безкоштовні heatmaps та session recording
  - Реєстрація: https://clarity.microsoft.com
  - Додати tracking code в `<head>`
  - Час: 15 хв

### Lead Management

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

### Design System Integration

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

Детальна історія змін доступна в `/CHANGELOG.md`

**Останні версії:**

- **v2.0.1** - Code Audit Fixes (2025-12-18)
- **v2.0.0** - PHP OOP Refactoring (2025-12-18)
- **v1.8.0** - GA4 Analytics + Lead Scoring Dashboard + theme.json (2025-12-17)
- **v1.7.0** - Exit-Intent Popup Fix (2025-12-16)
- **v1.6.1** - BEM + JS Hooks (2025-12-15)
- **v1.6.0** - Modern Solutions (2025-12-15)
- **v1.5.0** - Dashboard Analytics (2025-12-14)
- **v1.4.0** - Lead Management (2025-12-13)
- **v1.3.5** - Blog & Schema (2025-12-09)

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
**Roadmap Version:** 1.5

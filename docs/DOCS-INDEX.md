# Documentation Index - Medici Theme v1.3.5

> **Останнє оновлення:** 2025-12-14
> **Theme Version:** 1.3.5
> **Documentation Status:** ✅ Synchronized
> **Documentation Version:** 5.4

---

## 📋 Огляд документації

Цей файл містить індекс всієї документації проєкту Medici Theme з описом нової структури директорій `docs/`.

---

## 🗂️ Структура документації (НОВА!)

З версії 5.4 документація організована в `docs/` директорії для кращої навігації:

```
medici/
├── CLAUDE.md                    # Головний гід для AI
├── CODING-RULES.md              # Master Index для правил кодування
├── STYLE-RULES.md               # Master Index для CSS правил
├── TODO.md                      # Трекінг завдань
├── CHANGELOG.md                 # Історія змін
│
└── docs/
    ├── coding-rules/            # Правила кодування (3 файли)
    ├── style-rules/             # CSS правила (4 файли)
    ├── guides/                  # Довідники (3 файли)
    ├── api/                     # API документація (1 файл)
    ├── bot/                     # Bot документація (2 файли)
    ├── archive/                 # Застарілі звіти (9 файлів)
    └── DOCS-INDEX.md            # Цей файл
```

---

## ✅ Головні файли (Root Directory)

### 1. CLAUDE.md

**Локація:** `/CLAUDE.md`
**Статус:** ✅ Оновлено до версії 1.3.5
**Останнє оновлення:** 2025-12-14
**Documentation Version:** 5.4
**Розмір:** ~2560 рядків
**Призначення:** Головна документація для AI асистентів

**Key Information:**

- Theme Version: 1.3.5
- GeneratePress Premium + GenerateBlocks Pro 2.0+
- Повна архітектура теми (20 модулів)
- Lead Management System
- Events API
- Twemoji Local Integration
- Schema Markup & Sitemap

---

### 2. CODING-RULES.md (Master Index)

**Локація:** `/CODING-RULES.md`
**Статус:** ✅ Оновлено до версії 3.0.0+
**Останнє оновлення:** 2025-12-14
**Version:** 3.0.0
**Розмір:** ~430 рядків
**Призначення:** Індекс правил кодування та маршрутизація

**Посилання на детальні файли:**

- `docs/coding-rules/CODING-RULES-CORE.md`
- `docs/coding-rules/CODING-RULES-ADVANCED.md`
- `docs/coding-rules/CODING-RULES-WORDPRESS.md`

---

### 3. STYLE-RULES.md (Master Index)

**Локація:** `/STYLE-RULES.md`
**Статус:** ✅ Оновлено до версії 5.2.0
**Останнє оновлення:** 2025-12-14
**Version:** 5.2.0
**Розмір:** ~650 рядків
**Призначення:** Індекс CSS правил та маршрутизація

**Посилання на детальні файли:**

- `docs/style-rules/STYLE-RULES-GENERATEBLOCKS.md`
- `docs/style-rules/STYLE-RULES-THEME.md`
- `docs/style-rules/STYLE-RULES-CSS-STANDARDS.md`
- `docs/style-rules/STYLE-RULES-EFFECTS.md`

---

### 4. TODO.md

**Локація:** `/TODO.md`
**Статус:** ✅ Актуальний
**Розмір:** ~210 рядків
**Призначення:** Трекінг завдань проєкту

**Пріоритети:**

- 🔴 High Priority: Security, Критичний функціонал
- 🟡 Medium Priority: Оптимізація, Документація
- 🟢 Low Priority: UX/UI, SEO

---

### 5. CHANGELOG.md

**Локація:** `/CHANGELOG.md`
**Статус:** ✅ Оновлено до версії 1.3.5
**Останнє оновлення:** 2025-12-14
**Розмір:** ~2000+ рядків
**Призначення:** Повна історія змін проєкту

**Останні версії:**

- [1.3.5] - Lead Management System, Schema Markup
- [1.3.4] - Performance optimizations
- [1.3.3] - PHP Language Features

---

## 📚 Coding Rules (docs/coding-rules/)

### 6. CODING-RULES-CORE.md

**Локація:** `docs/coding-rules/CODING-RULES-CORE.md`
**Статус:** ✅ Актуальний
**Розмір:** ~1400 рядків
**Призначення:** Базові правила GenerateBlocks 2.x

**Секції (20 total):**

- UniqueId Format (hex, 8 chars)
- CSS Variables Escaping
- Responsive Breakpoints (768px, 1024px)
- Global Styles (16 класів)
- Visual Effects (Hover, Focus, Active)
- Two-Level Section Pattern
- Dynamic Content та Query Loops
- Security та Performance

---

### 7. CODING-RULES-ADVANCED.md

**Локація:** `docs/coding-rules/CODING-RULES-ADVANCED.md`
**Статус:** ✅ Актуальний
**Розмір:** ~1300 рядків
**Призначення:** Продвинуті техніки GenerateBlocks

**Секції (15 total):**

- Dynamic Tags API (кастомні теги)
- Query Block Pro (ACF Repeater, Options)
- Conditions API (умовний рендеринг)
- Custom Selectors (hover, pseudo-елементи)
- Container Queries (@container)
- WooCommerce Integration
- Performance Metrics
- REST API & Headless
- Blog Module (секція 34)

---

### 8. CODING-RULES-WORDPRESS.md

**Локація:** `docs/coding-rules/CODING-RULES-WORDPRESS.md`
**Статус:** ✅ Актуальний
**Розмір:** ~1600 рядків
**Призначення:** WordPress Coding Standards

**Секції (26 total):**

- PHP 7.4+ / 8.0+ Strict Types
- WordPress Coding Standards (WPCS)
- Типізація (type hints, return types)
- PSR-4 Autoloading
- Security (Sanitization, Escaping, Nonces)
- Database (WP_Query, $wpdb->prepare())
- Hooks та Events
- Заборонені практики

---

## 📖 Style Rules (docs/style-rules/)

### 9. STYLE-RULES-GENERATEBLOCKS.md

**Локація:** `docs/style-rules/STYLE-RULES-GENERATEBLOCKS.md`
**Статус:** ✅ Актуальний
**Розмір:** ~950 рядків
**Призначення:** GenerateBlocks класи (gbp-_, gb-_)

**Містить:**

- gbp-section (секції)
- gbp-button (кнопки)
- gbp-card (картки)
- gbp-footer (футер)
- gbp-navigation (навігація)
- gbp-hero (hero секції)
- gb-element (елементи)
- gb-query-loop (Query Block)

---

### 10. STYLE-RULES-THEME.md

**Локація:** `docs/style-rules/STYLE-RULES-THEME.md`
**Статус:** ✅ Актуальний
**Розмір:** ~750 рядків
**Призначення:** Власні класи теми

**Містить:**

- medici-blog (блог компоненти)
- medici-featured (featured post)
- medici-card (кастомні картки)
- medici-reading-time (мета час читання)
- medici-post-views (мета перегляди)
- Utility Classes (d-flex, m-0, text-center)

---

### 11. STYLE-RULES-CSS-STANDARDS.md

**Локація:** `docs/style-rules/STYLE-RULES-CSS-STANDARDS.md`
**Статус:** ✅ Актуальний
**Розмір:** ~650 рядків
**Призначення:** WordPress CSS Coding Standards

**Містить:**

- CSS Formatting (indent, spacing, colors)
- BEM, ITCSS, SMACSS методології
- CSS Variables та Custom Properties
- @layer Cascade Control
- Specificity та !important
- Performance Optimization
- Linting (stylelint)

---

### 12. STYLE-RULES-EFFECTS.md

**Локація:** `docs/style-rules/STYLE-RULES-EFFECTS.md`
**Статус:** ✅ Актуальний
**Розмір:** ~800 рядків
**Призначення:** Visual Effects & Animations

**Містить:**

- Glassmorphism (backdrop-filter)
- Card Lift (transform: translateY)
- Hover Effects (scale, rotate, opacity)
- Transitions та Animations
- Focus States (accessibility)
- Active States (button feedback)

---

## 🔧 Guides (docs/guides/)

### 13. QUICK-REFERENCE.md

**Локація:** `docs/guides/QUICK-REFERENCE.md`
**Статус:** ✅ Актуальний
**Розмір:** ~195 рядків
**Призначення:** Ультра-швидкий довідник (30 секунд)

**Містить:**

- ТОП-10 критичних правил (таблиця)
- Генератори коду (UniqueId: JS/Python/Bash)
- Формати для Copy-Paste
- ТОП-10 найчастіших помилок
- 5-секундний checklist

---

### 14. CHECKLIST.md

**Локація:** `docs/guides/CHECKLIST.md`
**Статус:** ✅ Актуальний
**Розмір:** ~550 рядків
**Призначення:** Детальна перевірка перед коммітом (100+ пунктів)

**Секції:**

- Pre-Code Checklist (документація, структура, безпека)
- Coding Checklist (GenerateBlocks JSON, PHP, CSS, JS)
- Pre-Commit Checklist (40+ пунктів критичних перевірок)
- Debug Checklist (GenerateBlocks, PHP, Performance issues)
- Workflow Checklist (10-step process)

---

### 15. BEST-PRACTICES.md

**Локація:** `docs/guides/BEST-PRACTICES.md`
**Статус:** ✅ Актуальний
**Розмір:** ~1220 рядків
**Призначення:** Найкращі практики розробки

**Секції:**

- PHP Best Practices (Strict Types, PSR-4, DI)
- WordPress Best Practices (Hooks, Security, WP_Query)
- GenerateBlocks Best Practices (Patterns, Global Styles)
- CSS Best Practices (BEM, ITCSS, Performance)
- JavaScript Best Practices (ES6+, Vanilla JS)
- Version Control (Semantic Versioning, Git Flow)

---

## 📡 API Documentation (docs/api/)

### 16. EVENTS-API.md

**Локація:** `docs/api/EVENTS-API.md`
**Статус:** ✅ Актуальний
**Розмір:** ~650 рядків
**Призначення:** Events API документація

**Містить:**

- PHP Events API (Medici\Events class)
- JavaScript Events API (mediciEvents object)
- Event Types (newsletter_subscription, consultation_request)
- Database Schema (wp_medici_events table)
- Usage Examples (PHP та JavaScript)
- Security (nonce verification, sanitization)

---

## 🤖 Bot Documentation (docs/bot/)

### 17. README_BOT.md

**Локація:** `docs/bot/README_BOT.md`
**Статус:** ✅ Актуальний
**Розмір:** ~650 рядків
**Призначення:** Bot integration documentation

---

### 18. QUICKSTART_BOT.md

**Локація:** `docs/bot/QUICKSTART_BOT.md`
**Статус:** ✅ Актуальний
**Розмір:** ~210 рядків
**Призначення:** Quick start guide для bot setup

---

## 📦 Archive (docs/archive/)

### 19. Audit Reports (9 файлів)

**Локація:** `docs/archive/`
**Дата:** 2025-12-12 (застарілі)
**Статус:** 🗄️ Archived

**Файли:**

- AUDIT-REPORT.md (17KB)
- COMPONENT-MAP.md
- INCONSISTENCIES-REPORT.md (14KB)
- CSS-AUDIT-REPORT.md (12KB)
- CSP-SECURITY-REPORT.md (14KB)
- VALIDATION-REPORT.md (15KB)
- TEMPLATES-AUDIT-\*.md (3 файли, 39KB)

**Примітка:** Всі критичні проблеми з цих звітів вже виправлені.

---

## 🔄 Історія оновлень

### Version 5.4 (2025-12-14)

- ✅ Реструктуризація: 19 → 5 файлів в корні (-74%)
- ✅ Створено docs/ структуру (6 директорій)
- ✅ Оновлено всі посилання в документації
- ✅ Архівовано застарілі звіти аудиту

### Version 5.3 (2025-12-14)

- ✅ Видалено застарілі звіти аудиту
- ✅ Виправлено посилання на patterns/ → gutenberg/
- ✅ Актуалізовано CLAUDE.md

### Version 5.2 (2025-12-09)

- ✅ Додано Schema Markup (FAQ, HowTo, Video)
- ✅ Додано Sitemap Optimization

### Version 5.1 (2025-12-08)

- ✅ Додано Lead Management System
- ✅ Оновлено Events API

---

## 📊 Статистика документації

| Метрика           | Значення     |
| ----------------- | ------------ |
| Всього файлів .md | 24           |
| Файлів в корні    | 5            |
| Файлів в docs/    | 14           |
| Файлів в архіві   | 9            |
| Загальний розмір  | ~400KB       |
| Coding Rules      | ~4300 рядків |
| Style Rules       | ~3150 рядків |
| Guides            | ~1965 рядків |

---

## 🎯 Як використовувати цю документацію

### Для AI асистентів (LLM):

1. **Завжди починай з Master Index:**
   - CODING-RULES.md для PHP/GenerateBlocks
   - STYLE-RULES.md для CSS
   - CLAUDE.md для архітектури теми

2. **Використовуй таблиці маршрутизації:**
   - Визнач тип завдання
   - Прочитай ТІЛЬКИ релевантні файли з docs/
   - Не читай всі файли одночасно

3. **Для швидких завдань:**
   - docs/guides/QUICK-REFERENCE.md (30 секунд)
   - ТОП-10 правил та генератори коду

4. **Перед коммітом:**
   - docs/guides/CHECKLIST.md
   - Pre-Commit Checklist (40+ пунктів)

### Для розробників:

1. **Налаштування проекту:**
   - Прочитай CLAUDE.md (архітектура)
   - Прочитай docs/guides/BEST-PRACTICES.md

2. **Під час розробки:**
   - Використовуй Master Index (CODING-RULES.md, STYLE-RULES.md)
   - Звіряйся з docs/guides/QUICK-REFERENCE.md

3. **Перед коммітом:**
   - docs/guides/CHECKLIST.md
   - CHANGELOG.md (додай запис про зміни)

---

**Документація актуальна станом на:** 2025-12-14
**Наступне оновлення:** При зміні версії теми або структури проекту

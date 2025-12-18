# 🚨 ОБОВ'ЯЗКОВІ ПРАВИЛА КОДУВАННЯ MEDICI

## ⚠️ КРИТИЧНО ВАЖЛИВО - ЧИТАТИ ПЕРЕД БУДЬ-ЯКИМ КОДОМ!

Ця тема використовує **комерційні (платні) версії**:

- ✅ **GeneratePress Premium** (не безкоштовна)
- ✅ **GenerateBlocks Pro 2.0+** (не безкоштовна)

**ВСІ ЗМІНИ КОДУ МАЮТЬ ВІДПОВІДАТИ ВИМОГАМ ЦЬОГО ФАЙЛУ!**

---

## 📚 СТРУКТУРА ДОКУМЕНТАЦІЇ (3 ФАЙЛИ)

**Правила кодування розділені на 3 спеціалізовані файли для оптимізації роботи LLM:**

### 1️⃣ **docs/coding-rules/CODING-RULES-CORE.md** (~1400 рядків)

**Критичні базові правила GenerateBlocks 2.x**

**📖 ЧИТАТИ:**

- ✅ При роботі з GenerateBlocks patterns
- ✅ При створенні/редагуванні блоків
- ✅ При роботі з UniqueId, CSS Variables, Responsive
- ✅ При використанні Global Styles, Buttons, Cards
- ✅ При роботі з Dynamic Content та Query Loops

**📋 Містить секції 1-20:**

- UniqueId Format (КРИТИЧНО!)
- CSS Variables Escaping
- Responsive Breakpoints
- Ampersand Escaping
- Visual Effects
- Global Styles (16 класів)
- Two-Level Section Pattern
- Dynamic Content Hooks
- Local Fonts (gp_font)
- Custom Elements (gp_elements)
- WordPress Hooks та Events
- Меню та Навігація
- Блог, Статті, Коментарі
- Форми та Newsletter
- Медіафайли та Оптимізація
- Overlay Panels
- Dynamic Content - Query & Looper
- Structured Styles (GB 2.x)

---

### 2️⃣ **docs/coding-rules/CODING-RULES-ADVANCED.md** (~1300 рядків)

**Продвинуті техніки GeneratePress & GenerateBlocks Pro**

**📖 ЧИТАТИ:**

- ✅ При реєстрації кастомних Dynamic Tags
- ✅ При роботі з Query Block Pro (ACF Repeater, Options)
- ✅ При створенні Conditions API
- ✅ При роботі з Custom Selectors, Container Queries
- ✅ При інтеграції з WooCommerce
- ✅ При оптимізації з Perfmatters
- ✅ При роботі з FSE vs Elements
- ✅ При налаштуванні Блог модуля GeneratePress
- ✅ При REST API та Headless розробці

**📋 Містить секції 21-35:**

- Реєстрація власних Dynamic Tags
- Query Block Pro (ACF Repeater, Post Meta, Options)
- Conditions API (кастомні умови)
- Custom Selectors (hover, pseudo-елементи)
- Container Queries
- Ефекти та анімації
- WooCommerce інтеграція
- Perfmatters + GeneratePress оптимізація
- FSE vs Elements (вибір архітектури)
- Performance Metrics для медичних сайтів
- Child Theme архітектура для агенцій
- Git Versioning + WP-CLI
- REST API & Headless Development
- Модуль "Блог" експертний гайд (~700 рядків)
- Рекомендований стек для експертів

---

### 3️⃣ **docs/coding-rules/CODING-RULES-WORDPRESS.md** (~1600 рядків)

**WordPress стандарти, типізація, організація коду**

**📖 ЧИТАТИ:**

- ✅ При написанні PHP коду
- ✅ При роботі з WordPress Coding Standards
- ✅ При використанні declare(strict_types=1)
- ✅ При типізації функцій та класів
- ✅ При організації коду (PSR-4, Composer, DI)
- ✅ При роботі з GeneratePress хуками
- ✅ При створенні модульної структури

**📋 Містить секції:**

- WordPress Coding Standards (PHP Tags, Naming, Formatting, Security)
- **Типові помилки WPCS та їх виправлення** (секція 10) ⭐ NEW
- Типізація та strict_types (PHP 7.4+, 8.0+)
- Nullable типи та Union Types
- Value Objects та Constructor Property Promotion
- Сучасна організація коду (PSR-4, Composer, Service Container)
- Сучасна організація для GeneratePress (Elements First, Global Styles)
- Medici Blog Module Integration
- Performance Rules
- File Naming Conventions
- Заборонені практики
- Checklist перед commit

---

## 🔍 МАРШРУТИЗАЦІЯ: ЯКИЙ ФАЙЛ ЧИТАТИ?

**LLM має ОБОВ'ЯЗКОВО читати файли згідно з таблицею:**

| Завдання користувача                   | Файли для читання                                   |
| -------------------------------------- | --------------------------------------------------- |
| Створення GenerateBlocks patterns      | **CORE**                                            |
| Редагування блоків, JSON               | **CORE**                                            |
| UniqueId, CSS Variables, Responsive    | **CORE**                                            |
| Global Styles, Buttons, Cards          | **CORE**                                            |
| Dynamic Content, Query Loops           | **CORE** + **ADVANCED** (секція 34)                 |
| Реєстрація Dynamic Tags API            | **ADVANCED** (секція 21)                            |
| Query Block Pro (ACF)                  | **ADVANCED** (секція 22)                            |
| Conditions API                         | **ADVANCED** (секція 23)                            |
| Custom Selectors, Container Queries    | **ADVANCED** (секції 24-25)                         |
| WooCommerce інтеграція                 | **ADVANCED** (секція 27)                            |
| Performance оптимізація                | **ADVANCED** (секція 28, 30) + **CORE** (секція 17) |
| Блог модуль GeneratePress              | **ADVANCED** (секція 34)                            |
| REST API, Headless                     | **ADVANCED** (секція 33)                            |
| PHP код, WordPress функції             | **WORDPRESS**                                       |
| Типізація, strict_types                | **WORDPRESS**                                       |
| PSR-4, Composer, DI                    | **WORDPRESS**                                       |
| GeneratePress хуки                     | **WORDPRESS** + **CORE** (секція 13)                |
| Безпека, sanitization                  | **WORDPRESS** + **CORE** (секція 16)                |
| **WPCS помилки, phpcs:ignore, PHPCBF** | **WORDPRESS** (секція 10)                           |
| Checklist, заборонені практики         | **WORDPRESS** + Цей файл                            |

---

## 🚀 ДОДАТКОВІ ПРАВИЛА

### 📦 MEDICI BLOG MODULE INTEGRATION

**Доступні методи (ВИКОРИСТОВУЙТЕ ЦІ!):**

```php
// Singleton instance
$blog = Medici_Blog_Module::get_instance();

// Методи
$blog->calculate_reading_time($post_id); // Час читання
$blog->get_post_views($post_id); // Перегляди
$blog->get_related_posts($post_id, 3); // Схожі пости
$blog->render_author_box($author_id); // Author box
$blog->render_share_buttons($post_id); // Кнопки share
$blog->render_breadcrumbs(); // Breadcrumbs

// Utilities
Medici_Blog_Utilities::get_category_icon($slug); // Іконка категорії
Medici_Blog_Utilities::get_client_ip(); // IP клієнта
Medici_Blog_Utilities::format_reading_time($min); // Форматований час
```

**Shortcodes (7 total):**

- `[medici_blog_page]` - Повна сторінка блогу
- `[medici_posts_grid]` - Сітка постів
- `[medici_featured_post]` - Featured post
- `[medici_author_box]` - Автор
- `[medici_toc]` - Table of Contents
- `[medici_newsletter]` - Newsletter форма

---

### 🔧 PERFORMANCE RULES

**1. Asset Loading:**

- Conditional loading (тільки де потрібно)
- Defer non-critical scripts
- Preload critical resources
- Версіонування через filemtime()

**2. Database Queries:**

- Cache результати з transients
- Використовуй WP_Query efficiently
- Index custom meta keys
- Уникай N+1 queries

**3. Image Optimization:**

- WebP з fallback
- Lazy loading (loading="lazy")
- Hero images: loading="eager", fetchpriority="high"
- Завжди додавай width та height (CLS)

---

### 📝 FILE NAMING CONVENTIONS

**PHP:**

- Classes: `class-blog-module.php`
- Traits: `trait-blog-ajax.php`
- Functions: `functions-helpers.php`

**CSS:**

- Modules: `blog-cards.css`
- Components: `buttons.css`
- Utilities: `utilities.css`

**JavaScript:**

- Modules: `blog-module.js`
- Components: `newsletter-form.js`

---

### 🚫 ЗАБОРОНЕНІ ПРАКТИКИ

**НІКОЛИ НЕ РОБІТЬ:**

1. ❌ UniqueId не у hex форматі або не 8 символів
2. ❌ CSS Variables без escaping (`--var` замість `\\u002d\\u002dvar`)
3. ❌ Rotate на основних блоках (section, div, article)
4. ❌ Пропускати responsive breakpoints
5. ❌ Inline styles замість Global Classes
6. ❌ jQuery dependencies (використовуйте vanilla JS)
7. ❌ Unescaped output (`echo $var` замість `echo esc_html($var)`)
8. ❌ Direct DB queries (використовуйте WP_Query, $wpdb->prepare())
9. ❌ Hardcoded URLs (використовуйте get_template_directory_uri())
10. ❌ Mixed tabs та spaces (тільки tabs для indentation)
11. ❌ Глобальні змінні (використовуйте namespace, classes)
12. ❌ Короткі PHP теги (`<?` замість `<?php`)
13. ❌ eval(), exec(), system() functions
14. ❌ Suppress errors з @ operator
15. ❌ require/include без file_exists() check

---

### ✅ CHECKLIST ПЕРЕД COMMIT

**Перед кожним коммітом перевіряйте:**

**🔴 CI/CD (ОБОВ'ЯЗКОВО!):**

```bash
# ЗАВЖДИ виконувати ПЕРЕД комітом:
npm run format              # Prettier форматування
npm run format:check        # Перевірка (має пройти!)

# Якщо редагував CSS:
for f in css/**/*.css; do echo "$f: {=$(grep -c '{' $f) }=$(grep -c '}' $f)"; done

# Опціонально (CI перевірить автоматично):
npm run lint:js             # ESLint
npm run lint:css            # StyleLint
composer phpstan            # PHPStan
```

- [ ] `npm run format` виконано (Prettier)
- [ ] `npm run format:check` проходить без помилок
- [ ] CSS баланс `{` та `}` однаковий (якщо редагував CSS)
- [ ] Немає ESLint помилок (якщо редагував JS)
- [ ] Немає PHPStan помилок (якщо редагував PHP)

**GenerateBlocks:**

- [ ] UniqueId у hex форматі (8 символів, lowercase, 0-9 a-f)
- [ ] CSS Variables екрановані (`\\u002d\\u002d`)
- [ ] Responsive breakpoints додані (768px, 1024px)
- [ ] Global Classes використані правильно
- [ ] Ampersand escape для pseudo-селекторів (`\\u0026`)
- [ ] pointerEvents: "none" на overlay елементах
- [ ] Attribute order правильний (uniqueId, tagName, styles, ...)
- [ ] Two-level section pattern (outer + inner)

**PHP Code:**

- [ ] declare(strict_types=1) на початку файлу
- [ ] Типізація функцій та методів (int, string, array, ...)
- [ ] Return types вказані
- [ ] Security: всі output escaped (esc_html, esc_url, esc_attr)
- [ ] Security: всі input sanitized (sanitize_text_field, wp_kses_post)
- [ ] WordPress Coding Standards дотримані
- [ ] Text domain 'medici.agency' використовується
- [ ] Hooks priority правильний
- [ ] Conditional loading для assets
- [ ] No jQuery dependencies

**Performance:**

- [ ] Images мають width та height (CLS)
- [ ] Lazy loading для below-fold images
- [ ] Hero images: loading="eager", fetchpriority="high"
- [ ] Transients використані для expensive queries
- [ ] Conditional asset loading
- [ ] Defer non-critical scripts

**Security:**

- [ ] Nonce verification для forms та AJAX
- [ ] Capability checks (current_user_can())
- [ ] Prepared statements для DB queries
- [ ] No eval(), exec(), system() calls
- [ ] File upload validation та sanitization
- [ ] SQL injection захист
- [ ] XSS захист (escaped output)
- [ ] CSRF захист (nonces)

**Documentation:**

- [ ] PHPDoc коментарі для функцій та класів
- [ ] @param та @return типи вказані
- [ ] Складна логіка прокоментована
- [ ] CHANGELOG.md оновлено (якщо потрібно)
- [ ] Version number оновлено (якщо потрібно)

---

### 📞 ЯКЩО СУМНІВ

**Порядок дій при невпевненості:**

1. **Прочитай відповідний файл:**
   - GenerateBlocks → **docs/coding-rules/CODING-RULES-CORE.md**
   - Продвинуті техніки → **docs/coding-rules/CODING-RULES-ADVANCED.md**
   - PHP/WordPress → **docs/coding-rules/CODING-RULES-WORDPRESS.md**

2. **Перевір документацію:**
   - `CLAUDE.md` - Архітектура теми
   - `Skill.md` - GenerateBlocks 2.x
   - `.claude/skills/generateblocks.md` - Skill метадані

3. **Перевір існуючий код:**
   - Подивись на схожі реалізації в проєкті
   - Використай Grep для пошуку прикладів

4. **Запитай користувача:**
   - Якщо невпевнений у підході
   - Якщо потрібна додаткова інформація
   - Якщо є декілька варіантів рішення

**НЕ ВГАДУЙ! Краще запитати, ніж зробити неправильно.**

---

### 🎓 РЕСУРСИ

**Обов'язкові файли:**

- `CODING-RULES-CORE.md` - Базові правила GenerateBlocks
- `CODING-RULES-ADVANCED.md` - Продвинуті техніки
- `CODING-RULES-WORDPRESS.md` - WordPress стандарти
- `CLAUDE.md` - Архітектура теми Medici
- `Skill.md` - GenerateBlocks 2.x повна документація

**Корисні файли:**

- `TODO.md` - Поточні завдання
- `CHANGELOG.md` - Історія змін

**Зовнішні ресурси:**

- [GeneratePress Docs](https://docs.generatepress.com/)
- [GenerateBlocks Docs](https://docs.generateblocks.com/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

---

## 📚 ДОДАТКОВІ РЕСУРСИ

### ⚡ ШВИДКІ ДОВІДНИКИ

**Для ultra-fast доступу до критичних правил:**

#### 1. **QUICK-REFERENCE.md** (193 рядки, 30 секунд читання)

**Призначення:** Ультра-короткий довідник для простих завдань

**Містить:**

- 🔴 ТОП-10 критичних правил (таблиця)
- 🔧 Генератори коду (UniqueId: JavaScript / Python / Bash)
- 📋 Формати для Copy-Paste (CSS Vars, Hover Effect, Section, PHP Function)
- 🚫 ТОП-10 найчастіших помилок (з прикладами)
- ✅ 5-секундний checklist
- 📖 Маршрутизація файлів (скорочена таблиця)
- ⚡ Ultra-Quick Tips

**Коли використовувати:**

- ✅ Прості завдання (button, card, section)
- ✅ Швидка перевірка формату (UniqueId, CSS Variables)
- ✅ Миттєве нагадування перед кодом

**Економія токенів:** 80-90% для простих завдань

---

#### 2. **CHECKLIST.md** (351 рядок, повна перевірка)

**Призначення:** Детальний checklist для всіх етапів розробки

**Містить:**

- ⚡ Швидкий Checklist (5 секунд)
- 📝 Pre-Code Checklist (15 пунктів)
- 🔨 Coding Checklist (50+ пунктів для GenerateBlocks / PHP / CSS / JS)
- 🚀 Pre-Commit Checklist (40+ пунктів) - **КРИТИЧНО!**
- 🐛 Debug Checklist (GenerateBlocks / PHP / Performance Issues)
- 📊 Workflow Checklist (10 кроків)

**Коли використовувати:**

- ✅ Перед написанням коду (Pre-Code)
- ✅ Під час розробки (Coding)
- ✅ Перед коммітом (Pre-Commit) - **ОБОВ'ЯЗКОВО!**
- ✅ При troubleshooting (Debug)

**КРИТИЧНО:** Завжди використовуй Pre-Commit Checklist перед коммітом!

---

#### 3. **Метадані файлів (.meta.json)**

Для LLM доступні структуровані метадані:

- **CODING-RULES-CORE.meta.json** - Структура CORE файлу
  - 1452 рядки, 20 секцій
  - Карта секцій з номерами рядків
  - Рівень критичності кожної секції
  - Ключові слова та use cases

- **CODING-RULES-ADVANCED.meta.json** - Структура ADVANCED файлу
  - 1304 рядки, 15 секцій
  - Залежності (plugins, PHP version)
  - Підсекції Blog Module (14 підрозділів)

- **CODING-RULES-WORDPRESS.meta.json** - Структура WORDPRESS файлу
  - 1635 рядків, 30 секцій
  - 4 основні частини (Coding Standards, Типізація, PSR-4, GeneratePress)

**Використання метаданих:**

- Швидке визначення локації потрібної секції
- Оцінка складності завдання
- Пошук по ключовим словам
- Визначення залежностей між файлами

---

### 📖 ЗОВНІШНІ РЕСУРСИ

**Офіційна документація:**

- [GeneratePress Docs](https://docs.generatepress.com/)
- [GenerateBlocks Docs](https://docs.generateblocks.com/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [PHP The Right Way](https://phptherightway.com/)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)

**Інструменти:**

- [UniqueId Generator Online](https://www.random.org/strings/) - 8 hex symbols
- [WordPress Code Reference](https://developer.wordpress.org/reference/)
- [PHP Manual](https://www.php.net/manual/en/)

---

## 🔄 ВЕРСІЯ

**CODING-RULES Version:** 3.0.0 (Master Index)
**Останнє оновлення:** 2025-12-07
**Застосовується до:** Medici Theme 1.3.3+

**Структура:**

- **CODING-RULES.md** - Master Index (цей файл)
- **CODING-RULES-CORE.md** - Базові правила (~1400 рядків)
- **CODING-RULES-ADVANCED.md** - Продвинуті техніки (~1300 рядків)
- **CODING-RULES-WORDPRESS.md** - WordPress стандарти (~1600 рядків)

**Базовано на:**

- WordPress Coding Standards 3.3.0 (офіційні)
- PHP 7.4+ / 8.0+ (типізація, strict_types)
- PSR-4 Autoloading Standard
- GeneratePress Premium + GenerateBlocks Pro 2.x

---

## 📋 CHANGELOG

### 3.0.0 (2025-12-02) 🔄 РЕФАКТОРИНГ: РОЗДІЛЕННЯ НА 3 ФАЙЛИ

**МЕГА-РЕФАКТОРИНГ: Оптимізація для LLM та ефективності**

**🎯 Головні зміни:**

✅ **Розділення на 3 спеціалізовані файли:**

- **CODING-RULES-CORE.md** (~1400 рядків) - Критичні базові правила GenerateBlocks
- **CODING-RULES-ADVANCED.md** (~1300 рядків) - Продвинуті техніки для експертів
- **CODING-RULES-WORDPRESS.md** (~1600 рядків) - WordPress стандарти та типізація

✅ **Master Index (цей файл):**

- Маршрутизація (таблиця "Який файл читати?")
- Структура документації з детальними описами
- Додаткові правила (Blog Module, Performance, File Naming)
- Заборонені практики та Checklist
- Ресурси та довідка

✅ **Оптимізація для LLM:**

- Чітка маршрутизація за типом завдання
- Зменшення token usage (читання тільки потрібних секцій)
- Швидший пошук інформації
- Модульна структура

✅ **Механізм автоматичного оновлення:**

- Зміни в CORE → автоматично застосовуються до базових правил
- Зміни в ADVANCED → автоматично застосовуються до продвинутих технік
- Зміни в WORDPRESS → автоматично застосовуються до WordPress стандартів
- Master Index оновлюється незалежно

**📊 Статистика:**

- 🎯 3 спеціалізовані файли замість 1 великого
- 📚 ~4300 рядків розділено на логічні модулі
- ⚡ Зменшення token usage на 60-70% (читання тільки релевантних секцій)
- 🔍 Швидший пошук через маршрутизацію
- 🚀 Оптимізація для LLM workflow

**🎓 Для користувачів:**

- Master Index для швидкої навігації
- Детальні описи кожного файлу
- Таблиця маршрутизації для вибору файлу
- Всі базові правила залишились доступними

**🤖 Для LLM:**

- Чітка маршрутизація за завданням
- Читання тільки релевантних секцій
- Модульна структура для easy navigation
- Token-efficient approach

---

### 2.8.0 (2025-12-02) 🚀 ЕКСПЕРТНІ ФУНКЦІЇ GENERATEPRESS & GENERATEBLOCKS PRO

[Детальний CHANGELOG див. у CODING-RULES-ADVANCED.md]

### 2.7.0 (2025-12-02) 🚀 СУЧАСНА РОЗРОБКА: ТИПІЗАЦІЯ + ОРГАНІЗАЦІЯ КОДУ

[Детальний CHANGELOG див. у CODING-RULES-WORDPRESS.md]

### 2.6.0 - 1.0.0

[Історія попередніх версій див. у відповідних спеціалізованих файлах]

---

## ⚡ ШВИДКЕ НАГАДУВАННЯ

**Перед написанням коду:**

1. ✅ Визнач тип завдання (GenerateBlocks / WordPress / Продвинуті техніки)
2. ✅ Прочитай відповідний файл згідно з таблицею маршрутизації
3. ✅ Перевір UniqueId format (8 hex, lowercase)
4. ✅ Екрануй CSS Variables (`\\u002d\\u002d`)
5. ✅ Додай responsive breakpoints
6. ✅ Використай security functions (esc*\*, sanitize*\*)
7. ✅ Додай типізацію (declare(strict_types=1))
8. ✅ Перевір checklist перед коммітом

**НЕДОТРИМАННЯ = ЗЛАМАНА ТЕМА!**

**Успішного кодування! 🚀**

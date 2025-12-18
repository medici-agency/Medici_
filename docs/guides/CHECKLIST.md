# ✅ CHECKLIST - CODING-RULES MEDICI

## 🎯 ПРО ЦЕЙ ФАЙЛ

Цей файл містить **детальні чек-листи** для різних етапів розробки.

**Використовуй:**

- ✅ Перед написанням коду
- ✅ Під час розробки
- ✅ Перед коммітом
- ✅ Для debug та troubleshooting

---

## ⚡ ШВИДКИЙ CHECKLIST (5 СЕКУНД)

**Перед кожним комітом:**

```
[ ] npm run format                    # ОБОВ'ЯЗКОВО!
[ ] npm run format:check              # Має пройти!
```

**Перед кожним кодом:**

```
[ ] Прочитав QUICK-REFERENCE.md?
[ ] UniqueId: 8 hex lowercase?
[ ] CSS Vars: \\u002d\\u002d escape?
[ ] Responsive: @media додані?
[ ] Security: esc_html() використано?
```

---

## 📝 PRE-CODE CHECKLIST

**Перед написанням БУДЬ-ЯКОГО коду виконай:**

### 1. Документація

- [ ] Прочитав **CODING-RULES.md** (Master Index)?
- [ ] Визначив тип завдання (GenerateBlocks / WordPress / Advanced)?
- [ ] Прочитав відповідний файл (CORE / ADVANCED / WORDPRESS)?
- [ ] Перевірив **QUICK-REFERENCE.md** для критичних правил?

### 2. Структура завдання

- [ ] Зрозумів вимоги користувача?
- [ ] Визначив, які файли потрібно змінити?
- [ ] Перевірив існуючий код для прикладів?
- [ ] Запланував структуру коду?

### 3. Безпека

- [ ] Перевірив, чи немає чутливих даних в коді?
- [ ] Заплановав використання escape functions (esc_html, esc_url)?
- [ ] Заплановав використання sanitize functions?
- [ ] Перевірив, чи не використовую небезпечні функції (eval, exec)?

---

## 🔨 CODING CHECKLIST

**Під час написання коду:**

### GenerateBlocks JSON

#### UniqueId

- [ ] 8 символів?
- [ ] Тільки hex (0-9, a-f)?
- [ ] Тільки малі букви?
- [ ] Унікальний в межах pattern?
- [ ] Згенерований через `openssl rand -hex 4` або генератор?

#### CSS Variables

- [ ] Escape з `\\u002d\\u002d` (подвійний backslash)?
- [ ] Використав доступні змінні (--accent, --base, --contrast)?
- [ ] Додав fallback значення: `var(\\u002d\\u002daccent, #2563eb)`?

#### Responsive

- [ ] Додав @media для 768px (mobile)?
- [ ] Додав @media для 1024px (tablet)?
- [ ] Перевірив на всіх breakpoints?
- [ ] Використав mobile-first підхід?

#### Visual Effects

- [ ] Transition додано: `"all 0.3s ease 0s"`?
- [ ] Hover з :focus: `"\\u0026:is(:hover, :focus)"`?
- [ ] НЕ використав rotate на section/div/article?
- [ ] Rotate тільки на іконках (span.gb-shape)?

#### Global Styles

- [ ] Використав `globalClasses` array, НЕ `className`?
- [ ] Вибрав правильний клас (gbp-section, gbp-button--primary)?
- [ ] Перевірив, що клас існує в темі?

#### Overlay

- [ ] Додав `"pointerEvents": "none"` для overlay?
- [ ] Встановив правильний z-index?
- [ ] Використав `"inset": "0"` для full coverage?

#### Two-Level Section

- [ ] Outer element з `gbp-section`?
- [ ] Inner element з `gbp-section__inner`?
- [ ] Inner element з max-width обмеженням?

#### Attribute Order

- [ ] uniqueId (перший)?
- [ ] tagName (другий)?
- [ ] styles (третій)?
- [ ] globalClasses (п'ятий)?
- [ ] metadata (шостий)?

---

### PHP Code

#### Типізація

- [ ] `declare(strict_types=1);` на початку файлу?
- [ ] Типи аргументів функцій вказані (int, string, array)?
- [ ] Return type вказаний?
- [ ] Nullable типи з `?` де потрібно?
- [ ] Union types правильно (PHP 8.0+)?

#### Security

- [ ] Всі output escaped: `esc_html()`, `esc_url()`, `esc_attr()`?
- [ ] Всі input sanitized: `sanitize_text_field()`, `sanitize_email()`?
- [ ] Nonce verification для forms: `check_ajax_referer()`?
- [ ] Capability checks: `current_user_can()`?
- [ ] Prepared statements для DB: `$wpdb->prepare()`?

#### WordPress Standards

- [ ] Text domain `'medici.agency'` використаний?
- [ ] Translation functions: `__()`, `_e()`, `esc_html__()`?
- [ ] Hooks priority правильний?
- [ ] Conditional loading для assets?
- [ ] No jQuery dependencies?

#### PHPDoc

- [ ] Всі функції мають PHPDoc коментарі?
- [ ] @param типи вказані?
- [ ] @return тип вказаний?
- [ ] Складна логіка прокоментована?

---

### CSS/Styles

#### Performance

- [ ] Використав CSS variables замість hardcoded values?
- [ ] Мінімізував кількість селекторів?
- [ ] Уникнув !important?
- [ ] Використав efficient selectors?

#### Responsive

- [ ] Mobile-first approach?
- [ ] Breakpoints: 768px, 1024px?
- [ ] Tested на всіх розмірах екрану?

---

### JavaScript

#### Performance

- [ ] Vanilla JS (без jQuery)?
- [ ] Event delegation для dynamic content?
- [ ] DOM queries cached?
- [ ] Debounce/throttle для scroll/resize?

#### Security

- [ ] Input validation?
- [ ] XSS protection?
- [ ] CSRF tokens для AJAX?

---

## 🚀 PRE-COMMIT CHECKLIST

**Перед кожним коммітом перевір:**

### 🔴 CI/CD (ОБОВ'ЯЗКОВО ПЕРШИМ!)

**⚠️ Без цих перевірок PR буде заблоковано!**

```bash
# 1. ФОРМАТУВАННЯ (ЗАВЖДИ!)
npm run format              # Автоматично виправляє
npm run format:check        # Має вивести "All matched files use Prettier code style!"

# 2. CSS БАЛАНС (якщо редагував CSS)
for f in css/**/*.css; do echo "$f: {=$(grep -c '{' $f) }=$(grep -c '}' $f)"; done

# 3. LINTING (опціонально, CI перевірить)
npm run lint:js             # ESLint
npm run lint:css            # StyleLint
composer phpstan            # PHPStan Level 5
```

- [ ] `npm run format` виконано?
- [ ] `npm run format:check` проходить без помилок?
- [ ] CSS баланс `{` та `}` однаковий?
- [ ] Немає ESLint помилок (якщо редагував JS)?
- [ ] Немає StyleLint помилок (якщо редагував CSS)?
- [ ] Немає PHPStan помилок (якщо редагував PHP)?

**Типові помилки:**

| Помилка     | Команда для виправлення     |
| ----------- | --------------------------- |
| Prettier    | `npm run format`            |
| ESLint      | `npm run lint:js -- --fix`  |
| StyleLint   | `npm run lint:css -- --fix` |
| CSS Balance | Додати пропущену `}`        |
| PHPStan     | Cast типів: `(string) $id`  |

### GenerateBlocks

- [ ] UniqueId у hex форматі (8 символів, lowercase, 0-9 a-f)?
- [ ] CSS Variables екрановані (`\\u002d\\u002d`)?
- [ ] Responsive breakpoints додані (768px, 1024px)?
- [ ] Global Classes використані правильно?
- [ ] Ampersand escape для pseudo-селекторів (`\\u0026`)?
- [ ] pointerEvents: "none" на overlay елементах?
- [ ] Attribute order правильний (uniqueId, tagName, styles)?
- [ ] Two-level section pattern (outer + inner)?
- [ ] No rotate на section/div/article?
- [ ] Transition додано для hover effects?

### PHP Code

- [ ] `declare(strict_types=1)` на початку файлу?
- [ ] Типізація функцій та методів (int, string, array)?
- [ ] Return types вказані?
- [ ] Security: всі output escaped (esc_html, esc_url, esc_attr)?
- [ ] Security: всі input sanitized (sanitize_text_field, wp_kses_post)?
- [ ] WordPress Coding Standards дотримані?
- [ ] Text domain 'medici.agency' використовується?
- [ ] Hooks priority правильний?
- [ ] Conditional loading для assets?
- [ ] No jQuery dependencies?
- [ ] PHPDoc коментарі додані?

### Performance

- [ ] Images мають width та height (CLS prevention)?
- [ ] Lazy loading для below-fold images (`loading="lazy"`)?
- [ ] Hero images: `loading="eager"` + `fetchpriority="high"`?
- [ ] Transients використані для expensive queries?
- [ ] Conditional asset loading (тільки де потрібно)?
- [ ] Defer non-critical scripts?
- [ ] CSS variables використані (не hardcoded)?
- [ ] Мінімізовано кількість HTTP requests?

### Security

- [ ] Nonce verification для forms та AJAX?
- [ ] Capability checks (`current_user_can()`)?
- [ ] Prepared statements для DB queries (`$wpdb->prepare()`)?
- [ ] No eval(), exec(), system() calls?
- [ ] File upload validation та sanitization?
- [ ] SQL injection захист?
- [ ] XSS захист (escaped output)?
- [ ] CSRF захист (nonces)?
- [ ] No sensitive data in code (API keys, passwords)?

### Documentation

- [ ] PHPDoc коментарі для функцій та класів?
- [ ] @param та @return типи вказані?
- [ ] Складна логіка прокоментована?
- [ ] CHANGELOG.md оновлено (якщо потрібно)?
- [ ] Version number оновлено (якщо потрібно)?
- [ ] TODO.md оновлено (якщо є завдання)?

### Testing

- [ ] Код працює на frontend?
- [ ] Код працює в admin panel (якщо потрібно)?
- [ ] Responsive design перевірено (mobile/tablet/desktop)?
- [ ] Browser compatibility (Chrome, Firefox, Safari, Edge)?
- [ ] No JavaScript console errors?
- [ ] No PHP errors/warnings?
- [ ] Performance не погіршився?

### Git

- [ ] Commit message зрозумілий та описовий?
- [ ] Українською мовою?
- [ ] Emoji використані (🚀, ✅, 🔧, тощо)?
- [ ] No sensitive files committed (wp-config.php, .env)?
- [ ] .gitignore актуальний?

---

## 🐛 DEBUG CHECKLIST

**Коли щось не працює:**

### GenerateBlocks Issues

#### UniqueId не працює

- [ ] Перевірив, що тільки hex (0-9, a-f)?
- [ ] Перевірив, що lowercase?
- [ ] Перевірив, що рівно 8 символів?
- [ ] Перевірив, що унікальний?
- [ ] Згенерував новий через `openssl rand -hex 4`?

#### CSS Variables не працюють

- [ ] Перевірив escape: `\\u002d\\u002d` (подвійний backslash)?
- [ ] Перевірив, що змінна існує в темі?
- [ ] Перевірив fallback значення?
- [ ] Перевірив browser console для помилок?

#### Hover effects не працюють

- [ ] Додав transition на main selector?
- [ ] Використав `\\u0026:is(:hover, :focus)`?
- [ ] Перевірив z-index?
- [ ] Перевірив `pointerEvents`?

#### Responsive не працює

- [ ] Додав @media queries?
- [ ] Використав правильні breakpoints (768px, 1024px)?
- [ ] Перевірив mobile-first approach?
- [ ] Tested на реальних пристроях?

---

### PHP Issues

#### Функція не працює

- [ ] Перевірив, що функція існує (`function_exists()`)?
- [ ] Перевірив типи аргументів?
- [ ] Перевірив return type?
- [ ] Перевірив, що strict_types = 1?
- [ ] Додав error_log() для debug?

#### Hook не спрацьовує

- [ ] Перевірив правильність hook name?
- [ ] Перевірив priority?
- [ ] Перевірив кількість аргументів?
- [ ] Перевірив, що hook існує?
- [ ] Додав remove_action перед add_action?

#### Security функції блокують

- [ ] Перевірив nonce?
- [ ] Перевірив capabilities?
- [ ] Перевірив sanitization?
- [ ] Перевірив, що user logged in?

---

### Performance Issues

#### Slow page load

- [ ] Перевірив кількість DB queries?
- [ ] Додав caching (transients)?
- [ ] Перевірив image optimization?
- [ ] Перевірив lazy loading?
- [ ] Перевірив defer scripts?
- [ ] Використав Performance Profiler?

#### High CLS

- [ ] Додав width/height для images?
- [ ] Видалив dynamic content shifts?
- [ ] Зафіксував розміри елементів?
- [ ] Використав aspect-ratio?

---

## 📊 WORKFLOW CHECKLIST

**Повний workflow для завдання:**

```
[ ] 1. Прочитав CODING-RULES.md (Master Index)
[ ] 2. Визначив тип завдання
[ ] 3. Прочитав відповідний файл (CORE/ADVANCED/WORDPRESS)
[ ] 4. Перевірив QUICK-REFERENCE.md
[ ] 5. Перевірив існуючий код для прикладів
[ ] 6. Написав код згідно з правилами
[ ] 7. Використав Pre-Commit Checklist
[ ] 8. Tested код
[ ] 9. Створив commit з описовим message
[ ] 10. Push до репозиторію
```

---

## 🔗 ДОДАТКОВІ РЕСУРСИ

- **QUICK-REFERENCE.md** - Ультра-швидкий довідник (30 секунд)
- **CODING-RULES.md** - Master Index з маршрутизацією
- **CODING-RULES-CORE.md** - Базові правила GenerateBlocks
- **CODING-RULES-ADVANCED.md** - Продвинуті техніки
- **CODING-RULES-WORDPRESS.md** - WordPress стандарти

---

**🚀 Версія:** 1.0.0
**📅 Останнє оновлення:** 2025-12-02

**💡 Порада:** Роздрукуй цей checklist та тримай поряд з монітором!

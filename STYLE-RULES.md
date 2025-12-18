# STYLE-RULES.md - Master Index (Індекс CSS Класів)

**Версія:** 5.2.0 (Додано Visual Effects Guide)
**Дата:** 2025-12-02
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

---

## 🚨 КРИТИЧНО ВАЖЛИВО - ЧИТАТИ ПЕРШИМ!

### ⚠️ НОВА СТРУКТУРА (5 ФАЙЛІВ)

**STYLE-RULES тепер розділений на 5 спеціалізованих файлів для оптимізації:**

1. **STYLE-RULES.md** - Master Index (цей файл) - ЗАВЖДИ читати ПЕРШИМ
2. **docs/style-rules/STYLE-RULES-GENERATEBLOCKS.md** (~950 рядків) - GenerateBlocks класи (gbp-_, gb-_)
3. **docs/style-rules/STYLE-RULES-THEME.md** (~750 рядків) - Власні класи теми (medici-\*, utilities)
4. **docs/style-rules/STYLE-RULES-CSS-STANDARDS.md** (~650 рядків) - WordPress CSS Coding Standards
5. **docs/style-rules/STYLE-RULES-EFFECTS.md** (~800 рядків) - Visual Effects & Animations 🆕

---

## 🚨 ЖОРСТКІ ВИМОГИ ДЛЯ LLM (ОБОВ'ЯЗКОВО!)

### 1️⃣ ЗАВЖДИ СПОЧАТКУ ПРОЧИТАЙ MASTER INDEX:

```
📖 Read: STYLE-RULES.md (Master Index)
```

Master Index містить:

- ✅ Структуру документації
- ✅ Таблицю маршрутизації (Який файл читати?)
- ✅ Quick Reference (швидкий довідник)
- ✅ Правила використання класів

### 2️⃣ ВИЗНАЧ ТИП ЗАВДАННЯ ТА ПРОЧИТАЙ ТІЛЬКИ РЕЛЕВАНТНІ ФАЙЛИ:

**КАТЕГОРИЧНО ЗАБОРОНЕНО:**

- ❌ Читати всі 5 файлів одночасно (марнування токенів!)
- ❌ Читати файли "на всяк випадок"
- ❌ Читати файли без перевірки таблиці маршрутизації
- ❌ Пропускати Master Index

**ДОЗВОЛЕНО:**

- ✅ Прочитай Master Index (ЗАВЖДИ!)
- ✅ Визнач тип завдання згідно з таблицею маршрутизації
- ✅ Прочитай ТІЛЬКИ потрібний файл (або 2 файли, якщо таблиця вказує)
- ✅ Якщо сумнів - запитай користувача

---

## 📊 ТАБЛИЦЯ МАРШРУТИЗАЦІЇ (ОБОВ'ЯЗКОВО!)

### 🎯 Використовуй цю таблицю для вибору файлу:

| Завдання користувача                               | Файли для читання              |
| -------------------------------------------------- | ------------------------------ |
| Створення секцій (gbp-section)                     | **GENERATEBLOCKS**             |
| Створення кнопок (gbp-button)                      | **GENERATEBLOCKS**             |
| Створення карточок (gbp-card)                      | **GENERATEBLOCKS**             |
| Footer класи (gbp-footer)                          | **GENERATEBLOCKS**             |
| Navigation класи (gbp-navigation)                  | **GENERATEBLOCKS**             |
| Hero секція (gbp-hero)                             | **GENERATEBLOCKS**             |
| GenerateBlocks елементи (gb-element, gb-text)      | **GENERATEBLOCKS**             |
| Query Loop (gb-query-loop)                         | **GENERATEBLOCKS**             |
| Menu Toggle (gb-menu)                              | **GENERATEBLOCKS**             |
| Blog класи (medici-blog)                           | **THEME**                      |
| Featured Post (medici-featured)                    | **THEME**                      |
| Card Components (medici-card)                      | **THEME**                      |
| Post Meta (medici-reading-time, medici-post-views) | **THEME**                      |
| Utility Classes (d-flex, m-0, text-center)         | **THEME**                      |
| Змішані завдання (gbp + medici)                    | **GENERATEBLOCKS** + **THEME** |
| CSS форматування, індентація                       | **CSS-STANDARDS**              |
| BEM, ITCSS, SMACSS методології                     | **CSS-STANDARDS**              |
| CSS Variables, @layer, specificity                 | **CSS-STANDARDS**              |
| WordPress CSS Coding Standards                     | **CSS-STANDARDS**              |
| Performance optimization, linting                  | **CSS-STANDARDS**              |
| Glassmorphism, Card Lift, Hover effects            | **EFFECTS** 🆕                 |
| Box Shadow, Transform, Filter effects              | **EFFECTS** 🆕                 |
| Scroll-Driven Animations, GSAP                     | **EFFECTS** 🆕                 |
| Overlay Panels, Neumorphism                        | **EFFECTS** 🆕                 |
| Mix Blend Modes, Gradient animations               | **EFFECTS** 🆕                 |

**Приклад читання:**

```
Користувач: "Створи секцію з кнопкою та карточкою"
→ Читай GENERATEBLOCKS (містить gbp-section, gbp-button, gbp-card)

Користувач: "Створи blog grid з карточками"
→ Читай THEME (містить medici-blog-grid, medici-blog-card)

Користувач: "Як правильно форматувати CSS згідно WordPress стандартів?"
→ Читай CSS-STANDARDS (містить форматування, BEM, індентація)

Користувач: "Створи glassmorphism card з hover lift effect"
→ Читай EFFECTS (містить glassmorphism, card lift, transitions)

Користувач: "Створи footer з кнопками та utility класами"
→ Читай GENERATEBLOCKS + THEME (footer у GB, utilities у THEME)
```

---

## 📚 СТРУКТУРА ДОКУМЕНТАЦІЇ

### STYLE-RULES-GENERATEBLOCKS.md (~950 рядків)

**Категорія A: gbp-\* (GenerateBlocks Pro) - 60+ класів**

- A.1 Sections (gbp-section, gbp-section--alt, gbp-section-header)
- A.2 Inner Containers (gbp-sectioninner, gbp-section\_\_inner)
- A.3 Typography (gbp-sectionheadline, gbp-section**tagline, gbp-section**text)
- A.4 Buttons (gbp-button--primary, gbp-button--secondary, gbp-button--tertiary)
- A.5 Cards (gbp-card, gbp-service-card, gbp-testimonial-card, gbp-value-card)
- A.6 Footer (gbp-footer, gbp-footer-legal, gbp-footer-content, +20 класів)
- A.7 Navigation (gbp-navigation, gbp-logo, gbp-nav-link, gbp-cta-button)
- A.8 Hero (gbp-hero)
- A.9 Borders (gbp--border)

**Категорія B: gb-\* (GenerateBlocks Core) - 15+ класів**

- B.1 Block Elements (gb-element, gb-text, gb-media, gb-shape)
- B.2 Query Loop (gb-query-loop, gb-query-loop-pagination)
- B.3 Menu Toggle (gb-menu-hide-on-toggled, gb-menu-show-on-toggled)

### STYLE-RULES-THEME.md (~750 рядків)

**Категорія C: medici-\* (Кастомні класи теми) - 30+ класів**

- C.1 Blog (medici-blog-grid, medici-blog-card)
- C.2 Card Components (medici-card-image-wrapper, medici-card-content, +5 класів)
- C.3 Featured Post (medici-featured-card, medici-featured-image, +10 класів)
- C.4 Post Meta (medici-reading-time, medici-post-views)
- C.5 Sections (medici-container, medici-section-header, medici-section-badge)

**Категорія D: Utility Classes - 50+ класів**

- D.1 Display (d-flex, d-grid, d-none)
- D.2 Flexbox (flex-row, align-items-center, justify-content-between)
- D.3 Spacing (m-0, m-1, mt-2, p-0, p-1)
- D.4 Text (text-left, font-weight-bold, text-uppercase)
- D.5 Behavior (smooth-scroll)

**Додатково:**

- CSS стилі з XML експортів (gb_style_css meta_value)

### STYLE-RULES-CSS-STANDARDS.md (~650 рядків)

**WordPress CSS Coding Standards - Правила написання CSS:**

- 1. Офіційні WordPress CSS Coding Standards
- 2. Структура та форматування (tabs, селектори, пробіли)
- 3. Іменування селекторів (hyphen-case, BEM)
- 4. Властивості та значення (hex, font-weight, одиниці)
- 5. Порядок властивостей (логічне групування)
- 6. CSS-архітектурні методології (BEM, ITCSS, SMACSS)
- 7. CSS Custom Properties (Variables)
- 8. CSS Cascade Layers (@layer)
- 9. Specificity та !important
- 10. Performance Optimization (Critical CSS, minification)
- 11. Коментування CSS (Table of Contents)
- 12. Linting та автоматизація (Stylelint, Autoprefixer)
- 13. Рекомендований стек для GeneratePress

### STYLE-RULES-EFFECTS.md (~800 рядків) 🆕

**Visual Effects & Animations - Візуальні ефекти GenerateBlocks:**

- 1. Effects Panel: Повний арсенал візуальних інструментів
- 2. Glassmorphism: Frosted Glass Effect
- 3. Card Lift Effect: Інтерактивні картки послуг
- 4. Stacked Transitions: Різний timing для різних ефектів
- 5. Targeting Inner Elements on Parent Hover
- 6. Pseudo-Elements для декоративних форм
- 7. Mix Blend Modes для креативних overlay
- 8. Filter Effects для зображень
- 9. Scroll-Driven Animations: Без JavaScript
- 10. GSAP Integration для складних анімацій
- 11. Animated Gradient Backgrounds
- 12. Overlay Panels: Popups, Mega Menu, Off-Canvas
- 13. Neumorphism: Soft UI для premium brands
- 14. Transform Effects для динамічності
- 15. Performance: Оптимізація анімацій
- 16. Checklist візуальних ефектів для маркетингової агенції

---

## 🔍 QUICK REFERENCE (ШВИДКИЙ ДОВІДНИК)

### Найчастіше використовувані класи:

#### Секції та Layout:

- `gbp-section` - базова секція (8rem padding)
- `gbp-section__inner` - inner container (max-width)
- `gbp-section-header` - header секції (центрований)
- `medici-container` - власний контейнер теми

#### Кнопки:

- `gbp-button--primary` - primary button (dashed border)
- `gbp-button--secondary` - secondary button (light)
- `gbp-button--tertiary` - tertiary button (filled)
- `gbp-button--tertiary-2` - tertiary variant 2

#### Карточки:

- `gbp-card` - базова карточка GenerateBlocks
- `gbp-service-card` - service card з hover ефектом
- `medici-blog-card` - blog карточка теми
- `medici-featured-card` - featured post card

#### Typography:

- `gbp-section__headline` - заголовок секції (H2)
- `gbp-section__tagline` - підзаголовок (Amatic SC font)
- `gbp-section__text` - текст секції
- `gbp-card__title` - заголовок карточки

#### Utilities:

- `d-flex` - display: flex
- `align-items-center` - вирівнювання по центру
- `justify-content-between` - space between
- `m-0`, `p-0` - margin/padding reset
- `text-center` - центрований текст

---

## 📊 ЗАГАЛЬНА СТАТИСТИКА

**Загальна кількість класів:** 150+ унікальних класів

**Розподіл по категоріях:**

- **Категорія A (gbp-\*):** 60+ класів (GenerateBlocks Pro)
- **Категорія B (gb-\*):** 15+ класів (GenerateBlocks Core)
- **Категорія C (medici-\*):** 30+ класів (Власні класи теми)
- **Категорія D (Utilities):** 50+ класів (Utility classes)

**Джерела даних:**

- content.md, content-2.md, content-3.md
- style-css-extended.md, style-css-guide.md
- gb_style_css (WordPress meta_value)
- Аналіз 1,126 входжень globalClasses

---

## ⚡ WORKFLOW ДЛЯ LLM (ОБОВ'ЯЗКОВИЙ АЛГОРИТМ)

**Перед використанням БУДЬ-ЯКОГО CSS класу:**

```
КРОК 1: Прочитай STYLE-RULES.md (Master Index)
        ↓
КРОК 2: Визнач тип завдання користувача
        ↓
КРОК 3: Знайди завдання в таблиці маршрутизації
        ↓
КРОК 4: Прочитай ТІЛЬКИ вказані файли (1 або 2)
        ↓
КРОК 5: Використай правильні класи згідно з гайдом
        ↓
КРОК 6: Перевір responsive breakpoints та hover стани
```

### ✅ ПРИКЛАД ПРАВИЛЬНОГО WORKFLOW:

```
Користувач: "Створи секцію з 3 карточками послуг та кнопкою"

LLM думає:
1. ✅ Read: STYLE-RULES.md (Master Index)
2. ✅ Тип завдання: Створення секції з gbp класами
3. ✅ Таблиця маршрутизації: "Створення секцій, кнопок, карточок" → GENERATEBLOCKS
4. ✅ Read: STYLE-RULES-GENERATEBLOCKS.md (тільки цей файл!)
5. ✅ Використай: gbp-section, gbp-section__inner, gbp-service-card, gbp-button--primary
6. ✅ Перевір responsive та hover стани з файлу
```

### ❌ ПРИКЛАД НЕПРАВИЛЬНОГО WORKFLOW (ЗАБОРОНЕНО!):

```
Користувач: "Створи секцію з 3 карточками послуг"

LLM думає:
1. ❌ Read: STYLE-RULES-GENERATEBLOCKS.md (пропущено Master Index!)
2. ❌ Read: STYLE-RULES-THEME.md (непотрібно!)
3. ❌ Марнування 1700+ токенів замість 950!
```

---

## 🔧 ПРАВИЛА ВИКОРИСТАННЯ КЛАСІВ

### 1️⃣ Naming Conventions:

**GenerateBlocks Pro (gbp-\*):**

- Префікс: `gbp-`
- Модифікатори: `--` (подвійне тире, BEM-like)
- Елементи: `__` (подвійне підкреслення, BEM)
- Приклади: `gbp-section`, `gbp-button--primary`, `gbp-section__inner`

**GenerateBlocks Core (gb-\*):**

- Префікс: `gb-`
- UniqueId: `gb-{type}-{uniqueId}` (8 hex chars)
- Приклади: `gb-element`, `gb-text-a1b2c3d4`, `gb-shape`

**Theme Custom (medici-\*):**

- Префікс: `medici-`
- Hyphen-separated (kebab-case)
- Приклади: `medici-blog-card`, `medici-featured-title`

**Utilities:**

- Bootstrap-like naming
- Приклади: `d-flex`, `m-0`, `text-center`

### 2️⃣ BEM Варіації (ВАЖЛИВО!):

У проєкті існують **ДВІ варіації** деяких класів:

**Старий формат (без підкреслення):**

- `gbp-sectioninner` ❌
- `gbp-sectionheadline` ❌
- `gbp-sectiontagline` ❌

**Новий формат (BEM з підкресленням):**

- `gbp-section__inner` ✅ (PREFERRED)
- `gbp-section__headline` ✅ (PREFERRED)
- `gbp-section__tagline` ✅ (PREFERRED)

**Рекомендація:** Використовуй **НОВИЙ формат** (з `__`) для нового коду!

### 3️⃣ Responsive Design:

**Breakpoints:**

- Mobile: `max-width: 767px`
- Tablet: `max-width: 1024px`
- Desktop: `min-width: 1025px`

Усі класи мають responsive варіанти у відповідних файлах.

### 4️⃣ Hover States:

Більшість інтерактивних класів мають `:hover` та `:focus` стани:

- Buttons: transform, background change
- Cards: translateY, box-shadow change
- Links: color change, padding-left shift

---

## 💡 МЕХАНІЗМ АВТОМАТИЧНОГО ОНОВЛЕННЯ

### Коли оновлювати файли:

**GENERATEBLOCKS файл оновлюється:**

- ✅ При додаванні нових gbp-\* класів
- ✅ При оновленні gb-\* core classes
- ✅ При змінах в GenerateBlocks patterns
- ✅ При оновленні секцій A.1-A.9, B.1-B.3

**THEME файл оновлюється:**

- ✅ При додаванні нових medici-\* класів
- ✅ При оновленні utility classes
- ✅ При змінах в blog/card компонентах
- ✅ При оновленні секцій C.1-C.5, D.1-D.5

**CSS-STANDARDS файл оновлюється:**

- ✅ При змінах WordPress CSS Coding Standards
- ✅ При оновленні BEM/ITCSS/SMACSS методологій
- ✅ При додаванні нових CSS best practices
- ✅ При оновленні performance optimization правил
- ✅ При змінах в linting конфігурації
- ✅ При оновленні секцій 1-13

**EFFECTS файл оновлюється:**

- ✅ При додаванні нових візуальних ефектів
- ✅ При оновленні Effects Panel можливостей
- ✅ При змінах в анімаціях (hover, scroll, transitions)
- ✅ При додаванні GSAP/JS анімацій
- ✅ При оновленні performance правил
- ✅ При оновленні секцій 1-16

**Master Index оновлюється:**

- ✅ При додаванні нових файлів до структури
- ✅ При змінах в таблиці маршрутизації
- ✅ При оновленні Quick Reference
- ✅ При зміні версії (5.0.0+)
- ✅ При оновленні статистики

**LLM має:**

1. ✅ Визначити, який файл потрібно оновити
2. ✅ Оновити ТІЛЬКИ відповідний файл
3. ✅ Оновити Master Index, якщо змінилась структура
4. ✅ Оновити версію та CHANGELOG в Master Index

---

## ⚠️ ПОРУШЕННЯ ЦІЄЇ ВИМОГИ = КРИТИЧНА ПОМИЛКА!

**Якщо LLM:**

- ❌ Пропустив Master Index
- ❌ Прочитав всі файли без необхідності
- ❌ Не використав таблицю маршрутизації
- ❌ Не перевірив Quick Reference

**Результат:**

- 🔴 Марнування токенів (1700+ токенів замість 500-950)
- 🔴 Повільний response
- 🔴 Можлива помилка через перевантаження контексту
- 🔴 Неефективна робота LLM

**ПРАВИЛЬНА РОБОТА LLM:**

- ✅ Економія токенів (60-70% менше)
- ✅ Швидший response
- ✅ Точна інформація з потрібного файлу
- ✅ Ефективний workflow

---

## 🔗 ЗВ'ЯЗОК З ІНШИМИ ФАЙЛАМИ

Цей гайд доповнює:

- `CODING-RULES.md` - правила кодування GenerateBlocks
- `Skill.md` - документація GenerateBlocks 2.x
- `CLAUDE.md` - загальна архітектура теми
- `.claude/uniqueId-database.json` - база даних uniqueId

**ВАЖЛИВО:** Завжди використовуйте STYLE-RULES разом з CODING-RULES при створенні нових блоків!

---

## 📝 CHANGELOG

### Версія 5.2.0 (2025-12-02) 🎨 ДОДАНО VISUAL EFFECTS

- ✅ Додано **STYLE-RULES-EFFECTS.md** (~800 рядків)
- ✅ Effects Panel: Box Shadow, Transform, Filter, Backdrop Filter
- ✅ Glassmorphism та Neumorphism реалізації
- ✅ Card Lift Effect та Stacked Transitions
- ✅ Targeting Inner Elements on Parent Hover
- ✅ Pseudo-Elements для декоративних форм
- ✅ Mix Blend Modes та Filter Effects
- ✅ Scroll-Driven Animations (CSS-only, без JS)
- ✅ GSAP Integration для enterprise-level анімацій
- ✅ Animated Gradient Backgrounds
- ✅ Overlay Panels (Popups, Mega Menu, Off-Canvas)
- ✅ Transform Effects для динамічності
- ✅ Performance optimization для анімацій
- ✅ Checklist візуальних ефектів для маркетингової агенції
- ✅ Оновлено таблицю маршрутизації (+5 візуальних ефектів завдань)
- ✅ Структура змінена з 4 на 5 файлів
- ✅ Оновлено механізм автоматичного оновлення

### Версія 5.1.0 (2025-12-02) 📝 ДОДАНО CSS STANDARDS

- ✅ Додано **STYLE-RULES-CSS-STANDARDS.md** (~650 рядків)
- ✅ Офіційні WordPress CSS Coding Standards
- ✅ CSS-архітектурні методології (BEM, ITCSS, SMACSS)
- ✅ CSS Custom Properties (Variables) та @layer
- ✅ Specificity та !important best practices
- ✅ Performance optimization (Critical CSS, minification, PurgeCSS)
- ✅ Linting та автоматизація (Stylelint, Autoprefixer)
- ✅ Рекомендований стек для GeneratePress
- ✅ Оновлено таблицю маршрутизації (+5 CSS завдань)
- ✅ Структура змінена з 3 на 4 файли
- ✅ Оновлено механізм автоматичного оновлення

### Версія 5.0.0 (2025-12-02) 🔄 РЕФАКТОРИНГ

- ✅ Розділено на 3 файли (Master Index + 2 спеціалізовані)
- ✅ Додано таблицю маршрутизації для LLM
- ✅ Додано Quick Reference (швидкий довідник)
- ✅ Додано workflow алгоритм для LLM
- ✅ Додано правила naming conventions
- ✅ Додано механізм автоматичного оновлення
- ✅ Оптимізовано для LLM (економія токенів 60-70%)

### Версія 4.0 (2025-11-29)

- Проаналізовано 858 входжень у content-2.md
- Проаналізовано 268 входжень у content-3.md
- Додано 45+ нових класів
- Додано категорію medici-\* (30+ класів)

### Версія 3.0 (2025-11-28)

- Перша версія створена на основі style-css-extended.md та style-css-guide.md
- Базовий перелік gbp-_ та gb-_ класів

---

**ВАЖЛИВО ДЛЯ LLM:**

1. ЗАВЖДИ читай Master Index ПЕРШИМ
2. Використовуй таблицю маршрутизації
3. Читай ТІЛЬКИ потрібні файли
4. НЕ марнуй токени!

---

**END OF MASTER INDEX**

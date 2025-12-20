# STYLE-RULES.md - CSS Classes Quick Reference

**Версія:** 6.0.0 (Optimized for LLM)
**Дата:** 2025-12-19
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

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

**Джерела:**

- CSS files: `css/components/*.css`, `css/layout/*.css`
- GenerateBlocks patterns: `gutenberg/*.html`
- WordPress Core CSS Coding Standards

---

## 🔧 ПРАВИЛА ВИКОРИСТАННЯ КЛАСІВ

### Naming Conventions:

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

### BEM Варіації:

**Старий формат (deprecated):**

- `gbp-sectioninner` ❌
- `gbp-sectionheadline` ❌

**Новий формат (preferred):**

- `gbp-section__inner` ✅
- `gbp-section__headline` ✅

### Responsive Design:

**Breakpoints:**

- Mobile: `max-width: 767px`
- Tablet: `max-width: 1024px`
- Desktop: `min-width: 1025px`

### Hover States:

- Buttons: transform, background change
- Cards: translateY, box-shadow change
- Links: color change, padding-left shift

---

## 🔗 ЗВ'ЯЗОК З ІНШИМИ ФАЙЛАМИ

**Детальні правила:**

- `CODING-RULES.md` - правила кодування GenerateBlocks
- `Skill.md` - документація GenerateBlocks 2.x
- `CLAUDE.md` - загальна архітектура теми

**CSS Standards:**

- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)

**Важливо:** Використовуйте цей Quick Reference разом з CODING-RULES при створенні нових блоків!

---

**END OF QUICK REFERENCE**

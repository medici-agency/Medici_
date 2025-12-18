# Code Quality Tools - Medici Theme

Цей документ описує інтеграцію Prettier, ESLint та StyleLint в проект Medici.

## 🛠️ Встановлені інструменти

| Інструмент    | Версія  | Призначення                                            |
| ------------- | ------- | ------------------------------------------------------ |
| **Prettier**  | 3.4.2   | Автоматичне форматування коду (CSS, JS, PHP, JSON, MD) |
| **ESLint**    | 8.57.1  | JavaScript linting (@wordpress/eslint-plugin)          |
| **StyleLint** | 16.10.0 | CSS linting (BEM validation, property order)           |

## 📦 Встановлення

```bash
# Встановити залежності
npm install

# Перевірити версії
npx prettier --version
npx eslint --version
npx stylelint --version
```

## 🚀 Використання

### Prettier (автоформатування)

```bash
# Перевірити які файли потребують форматування
npm run format:check

# Автоматично відформатувати всі файли
npm run format

# Форматувати конкретний файл
npx prettier --write css/components/cards.css
```

### ESLint (JavaScript linting)

```bash
# Перевірити JavaScript файли
npm run lint:js

# Автоматично виправити issues
npm run lint:js:fix

# Перевірити конкретний файл
npx eslint js/scripts.js
```

### StyleLint (CSS linting)

```bash
# Перевірити CSS файли
npm run lint:css

# Автоматично виправити issues
npm run lint:css:fix

# Перевірити конкретний файл
npx stylelint css/components/cards.css
```

### Комбіновані команди

```bash
# Перевірити все (Prettier + ESLint + StyleLint)
npm run check

# Виправити все (format + lint:fix)
npm run fix
```

## 🎯 Конфігурація

### .prettierrc.json

- **tabWidth**: 2
- **useTabs**: true
- **singleQuote**: true (JS), false (CSS)
- **trailingComma**: es5
- **printWidth**: 100
- **endOfLine**: lf

### .eslintrc.json

- **extends**: @wordpress/eslint-plugin/recommended
- **rules**:
  - `no-console`: warn
  - `no-debugger`: error
  - `no-var`: error
  - `prefer-const`: error
  - `camelcase`: error (з винятками medici*, wp*)

### .stylelintrc.json

- **extends**: stylelint-config-standard
- **plugins**: stylelint-order
- **rules**:
  - `selector-class-pattern`: BEM naming
  - `max-nesting-depth`: 3
  - `selector-max-specificity`: 0,4,0
  - `color-named`: never
  - `order/properties-order`: логічний порядок властивостей

## 🔧 VS Code Integration

Встановіть розширення:

1. **Prettier - Code formatter** (esbenp.prettier-vscode)
2. **ESLint** (dbaeumer.vscode-eslint)
3. **Stylelint** (stylelint.vscode-stylelint)

Налаштування автоматично застосуються з `.vscode/settings.json`:

- Format on Save: ✅
- Auto-fix ESLint/StyleLint on Save: ✅

## 🪝 Pre-commit Hook

Pre-commit hook автоматично перевіряє код перед кожним комітом:

```bash
# Встановити hook
./scripts/install-hooks.sh

# Або вручну
ln -sf ../../scripts/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

**Що перевіряється:**

1. PHPStan (Level 5)
2. CSS bracket balance
3. Prettier formatting
4. ESLint (JavaScript)
5. StyleLint (CSS)
6. Debug statements (var_dump, console.log)

**Пропустити перевірку:**

```bash
git commit --no-verify
```

## 🔄 GitHub Actions CI/CD

Автоматичні перевірки при push/PR:

- ✅ PHPStan (обов'язково)
- ✅ Prettier (обов'язково)
- ⚠️ PHPCS (warning)
- ⚠️ ESLint (warning)
- ⚠️ StyleLint (warning)
- ✅ CSS Balance Check (обов'язково)

Workflow: `.github/workflows/ci.yml`

## 📊 Перші результати

### Виявлені issues:

| Інструмент    | Кількість файлів | Типові проблеми                           |
| ------------- | ---------------- | ----------------------------------------- |
| **Prettier**  | 323 файли        | Непослідовне форматування                 |
| **ESLint**    | ~15 файлів       | no-var, no-unused-vars, prettier/prettier |
| **StyleLint** | ~20 файлів       | property order, color-named, BEM naming   |

### ESLint топ issues:

1. `no-var` → use `let`/`const` (50+ occurrences)
2. `no-unused-vars` → unused variables (10+ occurrences)
3. `prettier/prettier` → formatting (200+ occurrences)
4. `no-undef` → undefined globals (5+ occurrences)

### StyleLint топ issues:

1. `order/properties-order` → неправильний порядок властивостей (100+ occurrences)
2. `color-named` → `white` → `#fff` (30+ occurrences)
3. `font-family-name-quotes` → непотрібні лапки (20+ occurrences)
4. `color-function-notation` → `rgba()` → `rgb()` (50+ occurrences)

## 🎯 Наступні кроки

1. ✅ Встановлено Prettier, ESLint, StyleLint
2. ✅ GitHub Actions CI/CD integration
3. ✅ Pre-commit hook integration
4. ✅ VS Code settings
5. ⏳ Поступово виправляти issues (не блокуючи розробку)
6. ⏳ Автоформатувати всі файли після review

## 💡 Best Practices

### Для нових файлів:

- Завжди форматувати через Prettier
- Перевіряти ESLint/StyleLint перед комітом
- Використовувати VS Code auto-fix on save

### Для існуючих файлів:

- Виправляти issues поступово (не в одному PR)
- Пріоритет: критичні bugs (no-var, no-undef) → formatting
- Використовувати `--fix` для автоматичних виправлень

### Для code review:

- Не коментувати форматування (Prettier це робить)
- Фокусуватись на логіці та архітектурі
- Використовувати ESLint/StyleLint reports

## 🔗 Посилання

- [Prettier Documentation](https://prettier.io/docs/en/)
- [ESLint Rules](https://eslint.org/docs/rules/)
- [StyleLint Rules](https://stylelint.io/user-guide/rules/)
- [@wordpress/eslint-plugin](https://www.npmjs.com/package/@wordpress/eslint-plugin)
- [BEM Naming Convention](https://getbem.com/naming/)

---

**Last Updated:** 2025-12-17
**Medici Theme Version:** 1.7.0
**Інтеграція:** Prettier 3.4.2 + ESLint 8.57.1 + StyleLint 16.10.0

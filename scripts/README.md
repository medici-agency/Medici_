# Medici Theme Scripts

Автоматизовані скрипти для підтримки теми.

## 📋 CHANGELOG Auto-Update

Автоматично оновлює `CHANGELOG.md` на основі виконаних TODO items.

### Використання

#### Варіант 1: Node.js скрипт (Рекомендовано)

```bash
# Попередній перегляд змін
node scripts/update-changelog.js

# Автоматичне оновлення (без підтвердження)
node scripts/update-changelog.js --auto
```

**Що робить:**

- ✅ Парсить `TODO.md` і знаходить виконані завдання для поточної версії
- ✅ Автоматично категоризує зміни (Added, Changed, Removed, Fixed)
- ✅ Генерує красиво відформатований changelog entry
- ✅ Додає entry в `CHANGELOG.md` з сьогоднішньою датою
- ✅ Показує попередній перегляд перед оновленням

#### Варіант 2: Bash скрипт

```bash
./scripts/sync-changelog.sh
```

Простіший варіант для швидкої синхронізації.

### Автоматизація через Git Hook

Git hook автоматично нагадує про оновлення CHANGELOG при commit з TODO.md:

```bash
# Hook вже встановлено: .git/hooks/prepare-commit-msg
# Працює автоматично при кожному commit
```

## 🔄 Workflow

### Рекомендований процес:

1. **Виконай TODO items** та позначай їх як `[x]` в `TODO.md`

2. **Додай деталі в секцію Completed Items:**

   ```markdown
   ### v1.0.12

   - [x] Додана нова фіча X
     - Детальний опис функціоналу
     - Технічні деталі
   ```

3. **Запусти auto-update:**

   ```bash
   node scripts/update-changelog.js --auto
   ```

4. **Переглянь та відредагуй** `CHANGELOG.md` якщо потрібно

5. **Зроби commit:**
   ```bash
   git add TODO.md CHANGELOG.md
   git commit -m "docs: Update documentation for vX.X.X"
   ```

## 🎯 Приклади

### Структура TODO.md для автоматизації

```markdown
## Completed Items

### v1.0.12

- [x] Added admin settings page for blog module
  - General settings section
  - Author box configuration
  - Performance options
- [x] Fixed duplicate function error
- [x] Removed deprecated search functionality
```

Скрипт автоматично розпізнає:

- **Added**: "added", "created", "implemented"
- **Changed**: "updated", "changed", "enhanced"
- **Removed**: "removed", "deleted"
- **Fixed**: "fixed", "fix:"

### Результат в CHANGELOG.md

```markdown
## [1.0.12] - 2025-11-26

### Added

- Added admin settings page for blog module
  - General settings section
  - Author box configuration
  - Performance options

### Fixed

- Fixed duplicate function error

### Removed

- Removed deprecated search functionality
```

## 🛠 Налаштування

### Конфігурація версії

Змінюй в `scripts/update-changelog.js`:

```javascript
const VERSION = '1.0.12'; // Поточна версія
```

### Категорії змін

Додай власні ключові слова для категоризації:

```javascript
if (item.toLowerCase().includes('security')) {
	completed.security.push(item);
}
```

## 💡 Поради

1. **Пиши детальні TODO items** - вони стануть частиною changelog
2. **Використовуй ключові слова** - "Added", "Fixed", "Removed" для автокатегоризації
3. **Перевіряй генерований changelog** - додай деталі якщо потрібно
4. **Запускай скрипт перед commit** - щоб не забути оновити CHANGELOG

## 🐛 Troubleshooting

### Скрипт не знаходить completed items

Переконайся що в `TODO.md`:

- ✅ Є секція `## Completed Items`
- ✅ Є підсекція `### v1.0.12` (або інша поточна версія)
- ✅ Items позначені як `- [x]`

### Entry вже існує

Якщо entry для сьогоднішньої дати вже є в CHANGELOG:

- Видали існуючий entry вручну, або
- Змінюй дату в існуючому entry

## 📝 Ліцензія

Частина Medici Theme - GPL-2.0+

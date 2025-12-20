# 🛠️ DevOps & Code Quality

## Composer & PHPStan

**Встановлення:**

```bash
composer install
```

**Доступні команди:**

```bash
composer phpstan          # PHPStan аналіз
composer phpstan:baseline # Baseline для існуючих помилок
composer phpcs            # PHP CodeSniffer
composer phpcbf           # Автоматичне виправлення
composer lint             # Всі linting інструменти
composer test             # Всі тести та linting
```

**PHPStan конфігурація:**

- **Рівень:** 5 (рекомендовано для WordPress)
- **WordPress stubs:** szepeviktor/phpstan-wordpress
- **Baseline:** 23 помилки в існуючому коді

**Файли:**

- `composer.json` - Залежності та scripts
- `phpstan.neon` - PHPStan конфігурація
- `phpstan-baseline.neon` - Ігноровані помилки
- `.gitignore` - Git виключення

**PHPStan Baseline помилки (23):**

- `esc_html()` / `esc_attr()` отримують int замість string (12)
- Unreachable code / always true conditions (4)
- Type mismatches у WordPress functions (7)

**Виправлення:**

```php
// ❌ До
echo esc_html($post_id);

// ✅ Після
echo esc_html((string) $post_id);
```

## PHP CodeSniffer

**Стандарт:** WordPress Coding Standards (WPCS 3.3.0)

```bash
composer phpcs   # Перевірка
composer phpcbf  # Виправлення
```

## GitHub Actions CI/CD

**Автоматичні перевірки при push/PR:**

- **PHPStan** - Статичний аналіз (level 5)
- **PHPCS** - WordPress Coding Standards
- **PHP Compatibility** - Сумісність з PHP 8.1+
- **CSS Check** - Баланс фігурних дужок

**Файл:** `.github/workflows/ci.yml`

**Статус:**

```
✅ PHPStan (Level 5)      - Обов'язково
✅ PHPCS (WordPress)      - Warning
✅ PHP Compatibility      - Warning
✅ CSS Balance Check      - Обов'язково
```

## Pre-commit Hooks

**Встановлення:**

```bash
./scripts/install-hooks.sh
```

**Або вручну:**

```bash
chmod +x scripts/pre-commit
ln -sf ../../scripts/pre-commit .git/hooks/pre-commit
```

**Що перевіряється:**

1. PHPStan аналіз
2. CSS баланс дужок
3. Debug statements (var_dump, print_r, die, dd)

**Пропустити:**

```bash
git commit --no-verify
```

## Git Sparse Checkout (Dev Optimization)

**🚀 Прискорення розробки через виключення непотрібних директорій**

**Проблема:**

- 4,616 файлів у репозиторії
- 4,349 файлів (94%) у `assets/`, `bot/`, `docs/`, `fonts/`, `scripts/`, `skills/`
- Повільний `git clone`, `git pull`, індексування IDE

**Рішення: Git Sparse Checkout**

```bash
# Активувати Dev режим (тільки потрібні файли)
./git-dev-mode enable

# Перевірити статус
./git-dev-mode status

# Деактивувати (повний checkout)
./git-dev-mode disable
```

**Результат:**

- ✅ **17x менше файлів** (267 замість 4,616)
- ✅ **10x швидший clone** (~3 сек замість ~30 сек)
- ✅ **5x швидший pull** (<1 сек замість ~5 сек)
- ✅ **10x швидше індексування IDE**

**Детальна документація:**

- 📖 [GIT-SPARSE-CHECKOUT.md](./GIT-SPARSE-CHECKOUT.md) - Повний гайд з troubleshooting

**Що виключається:**

```
assets/   (11MB)  - fonts, images, twemoji
skills/   (8.7MB) - AI skills
docs/     (446KB) - крім docs/coding-rules/
fonts/    (191KB) - web fonts
bot/      (110KB) - bot scripts
scripts/  (58KB)  - build scripts
```

**Що залишається:**

```
inc/, css/, js/, templates/, gutenberg/, plugins/
package.json, composer.json, theme.json
docs/coding-rules/  # КРИТИЧНО для LLM!
.github/            # CI/CD
```

**ВАЖЛИВО:**

- ⚠️ Локальна конфігурація (не впливає на інших)
- ⚠️ CI/CD завжди робить повний checkout
- ⚠️ Деактивуйте при роботі з виключеними директоріями

---

## Рекомендований Workflow

**Перед комітом:**

```bash
# Hook автоматично запустить:
# 1. PHPStan
# 2. CSS balance check
# 3. Debug statement check
```

**При додаванні коду:**

1. Переконайся що PHPStan проходить без нових помилок
2. Запусти `composer phpcs` для перевірки стилю
3. Не додавай debug statements

**Ручна перевірка:**

```bash
composer lint  # PHPStan + PHPCS
```

**Dev optimization (опціонально):**

```bash
./git-dev-mode enable   # Прискорити розробку
./git-dev-mode status   # Перевірити статус
```

---

**Last Updated:** 2025-12-19

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

---

**Last Updated:** 2025-12-18

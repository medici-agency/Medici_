# Рішення та обговорення — 2025-12-15

## Огляд сесії

Дослідження та впровадження сучасних рішень для проекту Medici.

---

## ✅ ВПРОВАДЖЕНО (залишається)

### 1. PHPStan + Composer

**Файли:**

- `composer.json` — залежності та scripts
- `phpstan.neon` — конфігурація (level 5)
- `phpstan-baseline.neon` — baseline (0 помилок)

**Команди:**

```bash
composer phpstan          # Статичний аналіз
composer phpcs            # WordPress Coding Standards
composer phpcbf           # Автовиправлення
composer lint             # PHPStan + PHPCS
composer test             # Те саме що lint
```

**Причина:** Ловить PHP помилки до production.

---

### 2. GitHub Actions CI/CD

**Файл:** `.github/workflows/ci.yml`

**Автоматичні перевірки при push/PR:**

- PHPStan (обов'язково)
- PHPCS (warning)
- PHP Compatibility (warning)
- CSS Balance Check (обов'язково)

**Причина:** Автоматична перевірка якості коду.

---

### 3. Pre-commit Hooks

**Файли:**

- `scripts/pre-commit` — hook script
- `scripts/install-hooks.sh` — інсталятор

**Встановлення:**

```bash
./scripts/install-hooks.sh
```

**Перевіряє:**

- PHPStan
- CSS баланс дужок
- Debug statements (var_dump, print_r, die)

---

### 4. Database Optimization

**Файл:** `inc/database-optimization.php`

**Індекси:**

- `idx_medici_views` — перегляди постів
- `idx_medici_reading_time` — час читання
- `idx_medici_featured` — featured пости
- `idx_event_type` — тип події
- `idx_event_date` — дата події

**Причина:** Оптимізація meta queries для блогу та Events API.

---

### 5. Cache Manager

**Файл:** `inc/class-cache-manager.php`

**API:**

```php
Cache_Manager::remember($key, $callback, $ttl, $group);
Cache_Manager::forget($key, $group);
Cache_Manager::flush_group($group);
Cache_Manager::get_stats();
```

**Групи кешу:** `blog`, `leads`, `seo`, `general`

**Причина:** Wrapper для Transients API з автоматичною інвалідацією.

---

## ❌ ВИДАЛЕНО (як overkill)

### 1. CSS/JS Bundling (Webpack + PostCSS)

**Видалені файли:**

- `package.json`
- `webpack.config.js`
- `postcss.config.js`
- `js/src/` (6 файлів)
- `css/src/` (1 файл)

**Причина:** GeneratePress Premium вже оптимізує CSS/JS автоматично.

---

### 2. Autoprefixer

**Причина:** 99% браузерів підтримують flexbox, grid, transform без префіксів у 2025.

---

### 3. Unit Testing (PHPUnit)

**Видалені файли:**

- `phpunit.xml`
- `tests/` (3 файли)

**Видалені залежності:**

- `phpunit/phpunit`
- `yoast/phpunit-polyfills`
- `brain/monkey`

**Причина:** WordPress тема без складної бізнес-логіки не потребує unit тестів.

---

### 4. Web Workers

**Видалені файли:**

- `js/src/workers/seo-analyzer.worker.js`

**Причина:** Маркетинговий сайт не потребує важких обчислень на клієнті.

---

## ❌ ОЦІНЕНО ТА ВІДХИЛЕНО

### 1. Airbnb CSS Guide (`border: 0` vs `border: none`)

**Посилання:** https://github.com/airbnb/css

**Рішення:** НЕ впроваджувати

**Причина:** Мікро-оптимізація (3 байти на правило), не має практичного значення.

---

### 2. GenerateBlocks Icon Sets

**Посилання:** https://github.com/EncodeDotHost/GenerateBlocks-Icon-Sets

**Рішення:** НЕ впроваджувати

**Причина:**

- GenerateBlocks Pro 2.0+ має вбудовані іконки
- Twemoji вже покриває 4009 emoji
- Зайва залежність

---

### 3. GeneratePress Child (Addison Hall)

**Посилання:** https://github.com/addisonhall/generatepress-child

**Рішення:** Частково запозичити пізніше

**Корисне:**

- Fade-in анімації (scroll-triggered) — **додати пізніше**

**Не потрібно:**

- CSS структура (Medici має кращу)
- PHP модулі (Medici має свої)
- ACF helpers (не використовуємо ACF)

---

## 📋 НА МАЙБУТНЄ

### 1. Fade-in анімації (scroll-triggered)

```css
.fade-in {
	opacity: 0;
	transform: translateY(20px);
	transition:
		opacity 0.6s ease,
		transform 0.6s ease;
}

.fade-in.visible {
	opacity: 1;
	transform: translateY(0);
}
```

**Статус:** Запланована на пізніше

---

### 2. BEM naming консистентність

**Статус:** Рекомендовано для рефакторингу CSS

---

### 3. JavaScript hooks (`js-*` класи)

**Приклад:**

```html
<button class="btn btn-primary js-open-modal">Відкрити</button>
```

**Статус:** Рекомендовано для нового JS коду

---

## 🎯 РЕКОМЕНДАЦІЇ ДЛЯ LEAD TRACKING

### Високий пріоритет:

| Інструмент        | Час   | Користь                                          |
| ----------------- | ----- | ------------------------------------------------ |
| Microsoft Clarity | 5 хв  | Heatmaps, session recording (безкоштовно)        |
| GA4 Events        | 1 год | Scroll depth, time on page, CTA clicks           |
| UTM стратегія     | 30 хв | Атрибуція джерел (Instagram, Facebook, LinkedIn) |

### Середній пріоритет:

| Інструмент     | Час   | Користь                                       |
| -------------- | ----- | --------------------------------------------- |
| Lead Scoring   | 2 год | Пріоритезація лідів за джерелом та поведінкою |
| Facebook Pixel | 30 хв | Ретаргетинг                                   |

### Низький пріоритет:

| Інструмент           | Час   | Користь          |
| -------------------- | ----- | ---------------- |
| Exit-intent popup    | 2 год | +5-10% конверсії |
| LinkedIn Insight Tag | 30 хв | B2B аналітика    |

---

## UTM стратегія для соцмереж

```
INSTAGRAM:
  bio:     ?utm_source=instagram&utm_medium=bio
  stories: ?utm_source=instagram&utm_medium=story&utm_campaign={name}
  reels:   ?utm_source=instagram&utm_medium=reels
  dm:      ?utm_source=instagram&utm_medium=dm

FACEBOOK:
  posts:   ?utm_source=facebook&utm_medium=post
  ads:     ?utm_source=facebook&utm_medium=cpc&utm_campaign={name}

LINKEDIN:
  profile: ?utm_source=linkedin&utm_medium=profile
  posts:   ?utm_source=linkedin&utm_medium=post
  dm:      ?utm_source=linkedin&utm_medium=dm
```

---

## Підсумок змін у файлах

### Додано:

- `inc/database-optimization.php` — Database indexes
- `inc/class-cache-manager.php` — Cache Manager
- `.github/workflows/ci.yml` — CI/CD pipeline
- `scripts/pre-commit` — Pre-commit hook
- `scripts/install-hooks.sh` — Hook installer
- `composer.json` — Composer configuration
- `phpstan.neon` — PHPStan configuration

### Оновлено:

- `functions.php` — Додано нові модулі
- `.gitignore` — Додано PHP tools cache

### Видалено:

- `package.json`, `webpack.config.js`, `postcss.config.js`
- `js/src/`, `css/src/`, `tests/`
- `phpunit.xml`

---

**Дата:** 2025-12-15
**Гілка:** `claude/medici-modern-solutions-89p74`

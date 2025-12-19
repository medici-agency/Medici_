# 🚀 ADVANCED ARCHITECTURE PATTERNS

**⚠️ Примітка:** Ці патерни — майбутні покращення для масштабування теми. Поточна реалізація використовує модульний підхід (17 PHP модулів в `inc/`).

## 1. Dependency Injection Container (Future Enhancement)

**Концепція:** PSR-11 контейнер для управління залежностями та lazy loading сервісів.

**Приклад використання:**

```php
// inc/core/Container.php
namespace Medici\Core;
class Container implements \ArrayAccess
{
	public function service(callable $callable): callable
	{
		return function ($c) use ($callable) {
			static $object;
			return $object ?? ($object = $callable($c));
		};
	}
}

// functions.php
$container['blog.reading_time'] = $container->service(function ($c) {
	return new ReadingTimeCalculator(200); // WPM
});
```

**Переваги:** Централізоване управління залежностями, легше тестування, lazy loading.

## 2. PSR-4 Autoloading

**Структура для WordPress теми з Composer:**

```
medici/
├── composer.json      # PSR-4 autoload config
├── src/               # Namespace: Medici\
│   ├── Blog/
│   ├── Performance/
│   └── Schema/
└── functions.php      # Bootstrap
```

**composer.json:**

```json
{
	"autoload": {
		"psr-4": { "Medici\\": "src/" }
	}
}
```

**Переваги:** Автоматичне завантаження класів без `require_once`, краща IDE підтримка.

## 3. Repository Pattern для WordPress

**Приклад абстракції доступу до даних:**

```php
namespace Medici\Blog;
class PostRepository
{
	public function __construct(private readonly \wpdb $wpdb) {}

	public function findFeaturedPosts(int $limit = 6): array
	{
		return (new \WP_Query([
			'post_type' => 'medici_blog',
			'posts_per_page' => $limit,
			'meta_key' => 'medici_featured',
			'meta_value' => '1',
		]))->posts;
	}

	public function incrementViews(int $post_id): bool
	{
		// Атомарний інкремент через SQL
		return false !==
			$this->wpdb->query(
				$this->wpdb->prepare(
					"INSERT INTO {$this->wpdb->postmeta} (post_id, meta_key, meta_value)
                VALUES (%d, 'medici_views', 1)
                ON DUPLICATE KEY UPDATE meta_value = meta_value + 1",
					$post_id
				)
			);
	}
}
```

**Використання в темі:** Замінити прямі виклики WP_Query в модулях (`inc/blog-meta-fields.php`) на Repository methods.

**Переваги:** Легше тестувати (mock repository), бізнес-логіка відокремлена від WordPress API.

## 4. Advanced Performance Patterns

**A. Object Caching з Transients API**

```php
namespace Medici\Cache;
class CacheManager
{
	private const PREFIX = 'medici_';

	public function remember(string $key, callable $callback, int $ttl = HOUR_IN_SECONDS): mixed
	{
		$value = get_transient(self::PREFIX . $key);
		if (false !== $value) {
			return $value;
		}

		$value = $callback();
		set_transient(self::PREFIX . $key, $value, $ttl);
		return $value;
	}
}
```

**Використання:**

```php
$cache = new CacheManager();
$top_posts = $cache->remember(
	'top_posts_10',
	fn() => $repository->getTopViewedPosts(10),
	HOUR_IN_SECONDS
);
```

**B. Custom Database Indexes**

```php
// Для швидкого пошуку за meta_key + meta_value
global $wpdb;
$wpdb->query("CREATE INDEX idx_medici_views ON {$wpdb->postmeta} (meta_key, meta_value(10))");
```

**Інтеграція в тему:** Додати хук `after_switch_theme` в `functions.php`.

## 5. REST API Endpoints для Headless

**Приклад endpoint для featured posts:**

```php
namespace Medici\Api;
class BlogRestController
{
	public function register(): void
	{
		register_rest_route('medici/v1', '/posts/featured', [
			'methods' => 'GET',
			'callback' => [$this, 'getFeaturedPosts'],
			'permission_callback' => '__return_true',
			'args' => [
				'per_page' => [
					'default' => 6,
					'sanitize_callback' => 'absint',
					'validate_callback' => fn($v) => $v > 0 && $v <= 50,
				],
			],
		]);
	}
}
```

**Використання в темі:** Створити `inc/rest-api.php` модуль, реєструвати на `rest_api_init` hook.

## 6. Static Analysis & Code Quality

**PHPStan конфігурація (phpstan.neon):**

```yaml
parameters:
  level: 8
  paths: [inc, src]
  bootstrapFiles: [wordpress-stubs.php]
```

**Composer scripts:**

```json
"scripts": {
    "phpstan": "phpstan analyse",
    "phpcs": "phpcs --standard=WordPress inc/",
    "test": "phpunit"
}
```

**GitHub Actions (.github/workflows/code-quality.yml):**

```yaml
name: Code Quality
on: [push, pull_request]
jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: composer phpstan
```

**Інтеграція в тему:** Додати `composer.json`, `phpstan.neon`, GitHub Actions workflow.

## 7. Production Performance & Core Web Vitals

**Метрики:**

- **LCP < 2.0s** — Preload критичних шрифтів (`css/core/core.css`), defer non-critical CSS
- **INP < 100ms** — Delay JS execution (`inc/assets.php`), minimal event listeners
- **CLS < 0.05** — Fixed dimensions для images (вже реалізовано в `blog-single.css`)

**Рекомендовані інструменти:**

- **Perfmatters** — Script management, delay JavaScript
- **Query Monitor** — Debug WordPress queries (95 queries на homepage → optimize!)
- **PHPStan Level 8** — Static analysis для виявлення помилок до production

**Моніторинг:**

```php
// inc/performance.php
add_action('shutdown', function () {
	if (defined('SAVEQUERIES') && SAVEQUERIES) {
		global $wpdb;
		error_log(
			sprintf(
				'Queries: %d, Time: %.4fs',
				count($wpdb->queries),
				array_sum(wp_list_pluck($wpdb->queries, 1))
			)
		);
	}
});
```

**Поточний стан теми:**

- ✅ Lazy loading images (`inc/performance.php`)
- ✅ Critical CSS inline (`inc/assets.php`)
- ✅ Local fonts (WOFF2, `fonts/`)
- ⚠️ Потрібно: Query optimization, object caching, REST API

---

**Last Updated:** 2025-12-18

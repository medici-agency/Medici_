# Як уникнути помилок у WordPress Hooks

**Дата:** 2025-12-18
**Контекст:** Виправлення критичної помилки sitemap (TypeError у `medici_disable_user_sitemap`)

---

## 🐛 Типова помилка (Case Study)

### Що сталося:

```php
// ❌ НЕПРАВИЛЬНО - Type Mismatch
function medici_disable_user_sitemap(array $providers): array
{
	unset($providers['users']);
	return $providers;
}
add_filter('wp_sitemaps_add_provider', 'medici_disable_user_sitemap', 10);
// Fatal Error: очікував array, отримав WP_Sitemaps_Provider object
```

### Чому виникла:

1. ❌ Не перевірили WordPress documentation для filter `wp_sitemaps_add_provider`
2. ❌ PHPStan не виявив неправильну signature (WordPress filters динамічні)
3. ❌ Не було automated tests для sitemap functionality
4. ❌ Не тестували код локально після написання

---

## ✅ Як уникнути таких помилок у майбутньому

### 1. **ЗАВЖДИ перевіряй WordPress Developer Docs** ⭐⭐⭐

**Правило:** Перед використанням будь-якого WordPress filter/action — читай офіційну документацію.

**Де шукати:**

- https://developer.wordpress.org/reference/hooks/
- https://github.com/WordPress/WordPress (source code)
- PHPStorm Quick Documentation (Ctrl+Q / Cmd+J)

**Приклад workflow:**

```bash
# 1. Пошук у WordPress Docs
https://developer.wordpress.org/reference/hooks/wp_sitemaps_add_provider/

# 2. Перевірка signature:
apply_filters( 'wp_sitemaps_add_provider', WP_Sitemaps_Provider $provider, string $name )
#              ↑ Filter name             ↑ Param 1              ↑ Param 2

# 3. Пиши функцію з правильною signature:
function my_filter( WP_Sitemaps_Provider $provider, string $name ) {
    // ...
}
add_filter( 'wp_sitemaps_add_provider', 'my_filter', 10, 2 );
#                                                          ↑ 2 параметри!
```

---

### 2. **PHPDoc коментарі для всіх hooks**

**Правило:** Кожен `add_filter()` / `add_action()` має PHPDoc з типами параметрів.

**Template:**

```php
/**
 * Short description of what this hook does
 *
 * @since 1.0.0
 * @param Type1 $param1 Description
 * @param Type2 $param2 Description
 * @return ReturnType Description
 *
 * @see https://developer.wordpress.org/reference/hooks/hook_name/
 */
function my_hook_function($param1, $param2)
{
	// Implementation
}
add_filter('hook_name', 'my_hook_function', 10, 2);
//                                               ↑  ↑
//                                            priority | params count
```

**Чому важливо:**

- ✅ PHPStan може виявити type mismatches
- ✅ IDE автодоповнення працює правильно
- ✅ Інші розробники розуміють що очікується

---

### 3. **Pre-Commit Checklist для WordPress Hooks**

**Правило:** Перед комітом коду з `add_filter()` / `add_action()` перевір:

#### WordPress Hooks Checklist:

- [ ] **1. Documentation перевірена?**
  - Відкрив https://developer.wordpress.org/reference/hooks/hook_name/
  - Переглянув signature: `apply_filters( 'name', $param1, $param2, ... )`
  - Зрозумів які параметри передаються та їх типи

- [ ] **2. Function signature правильна?**
  - Параметри функції відповідають параметрам filter/action
  - Type hints додані (якщо можливо без breaking BC)
  - Return type задокументовано у PHPDoc

- [ ] **3. Parameters count вказано?**
  - `add_filter( 'name', 'func', 10, 2 )` ← 4-й параметр = кількість params
  - Якщо filter має 3 параметри → вказуй `3`
  - Default = 1, тому для 2+ параметрів ОБОВ'ЯЗКОВО вказувати

- [ ] **4. PHPDoc коментар додано?**
  - `@param` для кожного параметра з типом
  - `@return` з типом (для filters)
  - `@see` з посиланням на WordPress docs

- [ ] **5. Локально протестовано?**
  - Код виконується без Fatal Errors
  - Hook викликається у правильному контексті
  - Результат відповідає очікуванням

#### Приклад заповненого checklist:

```php
/**
 * Exclude 'users' provider from WordPress Core Sitemap
 *
 * @since 1.3.5
 * @param WP_Sitemaps_Provider $provider Sitemap provider object   ✅
 * @param string               $name     Provider name             ✅
 * @return WP_Sitemaps_Provider|false Provider or false to exclude ✅
 *
 * @see https://developer.wordpress.org/reference/hooks/wp_sitemaps_add_provider/ ✅
 */
function medici_disable_user_sitemap($provider, string $name)
{
	// ✅ signature
	if ('users' === $name) {
		return false;
	}
	return $provider;
}
add_filter('wp_sitemaps_add_provider', 'medici_disable_user_sitemap', 10, 2); // ✅ 2 params
```

---

### 4. **IDE Setup з WordPress Stubs**

**Правило:** Використовуй PHPStorm/VS Code з правильними stubs для WordPress.

#### PHPStorm:

```bash
# 1. Install WordPress Integration plugin
Settings → Plugins → WordPress

# 2. Enable WordPress support
Settings → Languages & Frameworks → PHP → Frameworks → WordPress
✅ Enable WordPress integration
✅ WordPress installation path: /path/to/wordpress

# 3. Install WordPress stubs via Composer
composer require --dev php-stubs/wordpress-stubs
```

#### VS Code:

```bash
# 1. Install Intelephense extension
ext install bmewburn.vscode-intelephense-client

# 2. Add WordPress stubs
composer require --dev php-stubs/wordpress-stubs

# 3. Configure settings.json
{
    "intelephense.stubs": [
        "wordpress",
        "wordpress-globals"
    ]
}
```

**Результат:**

- ✅ Autocomplete для WordPress functions
- ✅ Type hints у tooltips
- ✅ Go to definition працює для WP classes
- ✅ PHPDoc з правильними типами

---

### 5. **Automated Testing для WordPress Hooks**

**Правило:** Критичні hooks мають unit tests.

#### Приклад: PHPUnit Test для sitemap filter

```php
<?php
/**
 * Tests for Sitemap Optimization
 *
 * @package Medici_Agency
 * @subpackage Tests
 */

class Test_Sitemap_Optimization extends WP_UnitTestCase
{
	/**
	 * Test: medici_disable_user_sitemap() excludes users provider
	 */
	public function test_disable_user_sitemap_excludes_users_provider()
	{
		// Arrange
		$provider = new WP_Sitemaps_Users();
		$name = 'users';

		// Act
		$result = medici_disable_user_sitemap($provider, $name);

		// Assert
		$this->assertFalse($result, 'Users provider should be excluded');
	}

	/**
	 * Test: medici_disable_user_sitemap() keeps other providers
	 */
	public function test_disable_user_sitemap_keeps_other_providers()
	{
		// Arrange
		$provider = new WP_Sitemaps_Posts();
		$name = 'posts';

		// Act
		$result = medici_disable_user_sitemap($provider, $name);

		// Assert
		$this->assertInstanceOf(
			WP_Sitemaps_Provider::class,
			$result,
			'Non-users provider should be kept',
		);
	}

	/**
	 * Test: Filter is hooked correctly
	 */
	public function test_filter_is_hooked()
	{
		$this->assertIsInt(
			has_filter('wp_sitemaps_add_provider', 'medici_disable_user_sitemap'),
			'Filter should be hooked',
		);
	}
}
```

#### Setup PHPUnit для WordPress теми:

```bash
# 1. Install WP Test Suite
./bin/install-wp-tests.sh wordpress_test root '' localhost latest

# 2. Install PHPUnit
composer require --dev phpunit/phpunit

# 3. Create phpunit.xml
cp phpunit.xml.dist phpunit.xml

# 4. Run tests
./vendor/bin/phpunit
```

---

### 6. **CI/CD Improvements**

**Правило:** GitHub Actions перевіряє WordPress hooks автоматично.

#### Додати до `.github/workflows/ci.yml`:

```yaml
phpunit-tests:
  name: PHPUnit Tests
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mysqli

    - name: Install WordPress Test Suite
      run: |
        bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

    - name: Run PHPUnit
      run: ./vendor/bin/phpunit

wordpress-hooks-check:
  name: WordPress Hooks Validation
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4

    - name: Check hooks have parameter count
      run: |
        # Grep для add_filter/add_action без параметра count
        ! grep -r "add_filter.*10\s*)" inc/ || {
          echo "❌ Found hooks without parameter count!"
          exit 1
        }
```

---

### 7. **Code Review Checklist**

**Правило:** Pull Request з WordPress hooks проходить спеціальний review.

#### PR Checklist для reviewer:

```markdown
## WordPress Hooks Review

- [ ] **Documentation перевірена?**
  - Reviewer перевірив WordPress docs для кожного нового hook
  - Signature відповідає документації

- [ ] **PHPDoc коментарі присутні?**
  - `@param` з типами
  - `@return` з типом
  - `@see` з посиланням

- [ ] **Parameters count вказано?**
  - Якщо hook має 2+ параметри → 4-й аргумент `add_filter()` присутній

- [ ] **Tests додано?**
  - Unit test для нового hook functionality
  - Або обґрунтування чому тести не потрібні

- [ ] **Локально протестовано?**
  - PR author підтвердив що код працює
  - Screenshots/logs додані як proof
```

---

### 8. **Development Workflow**

**Правило:** Стандартний workflow для роботи з WordPress hooks.

#### Workflow для додавання нового hook:

```bash
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# КРОК 1: Research (5-10 хвилин)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# 1.1. Знайди filter/action у WordPress Docs
open "https://developer.wordpress.org/reference/hooks/$hook_name/"

# 1.2. Переглянь source code
open "https://github.com/WordPress/WordPress/search?q=$hook_name"

# 1.3. Перевір чи є приклади у темах/плагінах
open "https://github.com/search?q=$hook_name+language%3APHP"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# КРОК 2: Implementation (10-20 хвилин)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# 2.1. Створи функцію з правильною signature
# 2.2. Додай PHPDoc коментар
# 2.3. Додай hook з правильним priority та params count
# 2.4. Додай inline коментарі для складної логіки

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# КРОК 3: Testing (5-10 хвилин)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

# 3.1. Локальне тестування
wp server  # Запусти WordPress dev server
# Перевір що hook працює у browser/console

# 3.2. PHPStan перевірка
composer phpstan -- inc/your-file.php

# 3.3. Prettier форматування
npm run format

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# КРОК 4: Commit & Push
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

git add inc/your-file.php
git commit -m "✨ Add: WordPress hook for [feature]

- Filter: $hook_name
- Purpose: [describe purpose]
- Signature verified against WordPress docs
- PHPDoc added with types
- Tested locally: [describe test]
"
git push
```

---

## 📚 Корисні ресурси

### WordPress Developer Resources:

- **Hooks Reference:** https://developer.wordpress.org/reference/hooks/
- **Plugin Handbook:** https://developer.wordpress.org/plugins/hooks/
- **Theme Handbook:** https://developer.wordpress.org/themes/basics/theme-functions/
- **WordPress Core Search:** https://github.com/WordPress/WordPress

### Tools:

- **PHPStorm WordPress Plugin:** https://plugins.jetbrains.com/plugin/7973-wordpress
- **VS Code Intelephense:** https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client
- **WordPress Stubs:** https://github.com/php-stubs/wordpress-stubs
- **WP-CLI:** https://wp-cli.org/ (для testing WordPress functionality)

### Testing:

- **WP_UnitTestCase:** https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/
- **WordPress Test Suite:** https://github.com/WordPress/wordpress-develop
- **wp scaffold tests:** https://developer.wordpress.org/cli/commands/scaffold/plugin-tests/

---

## 🎯 Підсумок: 8 правил для уникнення помилок

1. ✅ **ЗАВЖДИ читай WordPress docs перед використанням hook**
2. ✅ **Додавай PHPDoc з типами для всіх hook functions**
3. ✅ **Вказуй parameters count у `add_filter()` (4-й аргумент)**
4. ✅ **Використовуй IDE з WordPress stubs**
5. ✅ **Пиши unit tests для критичних hooks**
6. ✅ **CI/CD перевіряє hooks автоматично**
7. ✅ **Code review з WordPress Hooks Checklist**
8. ✅ **Дотримуйся стандартного workflow**

---

**Створено:** 2025-12-18
**Версія:** 1.0.0
**Автор:** Claude (Medici Theme Maintainer)
**Контекст:** Після виправлення Critical Sitemap Error (commit `8b116e1`)

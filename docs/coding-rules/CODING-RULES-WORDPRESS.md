## 📑 ЗМІСТ

- [🎯 ПРАВИЛА WORDPRESS КОДУВАННЯ](#-правила-wordpress-кодування)
  - [1. PHP Tags та Базовий Синтаксис](#1-php-tags-та-базовий-синтаксис)
  - [2. Naming Conventions (Іменування)](#2-naming-conventions-іменування)
  - [3. Whitespace та Indentation](#3-whitespace-та-indentation)
  - [4. Formatting Standards](#4-formatting-standards)
  - [5. Control Structures](#5-control-structures)
  - [6. Security (ОБОВ'ЯЗКОВО!)](#6-security-обовязково)
  - [7. Text Domain (ЗАВЖДИ!)](#7-text-domain-завжди)
  - [8. Hooks Priority](#8-hooks-priority)
  - [9. Додаткові Правила](#9-додаткові-правила)
- [🎯 ТИПІЗАЦІЯ ТА STRICT_TYPES У WORDPRESS](#-типізація-та-strict_types-у-wordpress)
  - [Загальна інформація](#загальна-інформація)
  - [1. Правильна декларація strict_types](#1-правильна-декларація-strict_types)
  - [2. Типізація функцій та методів (PHP 7.4+)](#2-типізація-функцій-та-методів-php-74)
  - [3. Типізація властивостей класу (PHP 7.4+)](#3-типізація-властивостей-класу-php-74)
  - [4. Nullable типи та Union Types (PHP 8.0+)](#4-nullable-типи-та-union-types-php-80)
  - [5. Value Objects та Constructor Property Promotion (PHP 8.0+)](#5-value-objects-та-constructor-property-promotion-php-80)
  - [6. Return Type для WordPress функцій](#6-return-type-для-wordpress-функцій)
  - [7. Практичні приклади для Medici Theme](#7-практичні-приклади-для-medici-theme)
  - [8. Interface Design та PHPStan Compliance](#8-interface-design-та-phpstan-compliance)
- [🏗️ СУЧАСНА ОРГАНІЗАЦІЯ КОДУ ДЛЯ WORDPRESS](#️-сучасна-організація-коду-для-wordpress)
  - [Загальна концепція](#загальна-концепція)
  - [1. Структура проєкту за стандартом PSR-4](#1-структура-проєкту-за-стандартом-psr-4)
  - [2. Налаштування Autoloading (Composer)](#2-налаштування-autoloading-composer)
  - [3. Використання Service Container (Dependency Injection)](#3-використання-service-container-dependency-injection)
  - [4. Сучасні стартові шаблони (Boilerplates)](#4-сучасні-стартові-шаблони-boilerplates)
  - [5. Відокремлення логіки від представлення (MVC)](#5-відокремлення-логіки-від-представлення-mvc)
  - [6. Практичний приклад: Medici Theme з PSR-4](#6-практичний-приклад-medici-theme-з-psr-4)
- [🎨 СУЧАСНА ОРГАНІЗАЦІЯ КОДУ ДЛЯ GENERATEPRESS](#-сучасна-організація-коду-для-generatepress)
  - [Загальна концепція](#загальна-концепція-1)
  - [1. Гібридна структура Child Theme](#1-гібридна-структура-child-theme)
  - [2. Стратегія "Elements First" (замість редагування шаблонів)](#2-стратегія-elements-first-замість-редагування-шаблонів)
  - [3. Організація CSS: Global Styles у GenerateBlocks](#3-організація-css-global-styles-у-generateblocks)
  - [4. PHP-хуки для модифікації GeneratePress](#4-php-хуки-для-модифікації-generatepress)
  - [5. Оптимізація та скрипти](#5-оптимізація-та-скрипти)
  - [6. Інтеграція з GenerateBlocks Pro 2.x](#6-інтеграція-з-generateblocks-pro-2x)
  - [7. Практичний приклад: повна інтеграція](#7-практичний-приклад-повна-інтеграція)

---

## 🎯 ПРАВИЛА WORDPRESS КОДУВАННЯ

### 1. PHP Tags та Базовий Синтаксис

**PHP Tags:**

```php
// ✅ ПРАВИЛЬНО - повні теги
<?php
// код
?>

// ❌ ЗАБОРОНЕНО - короткі теги
<?
// код
?>

// ❌ ЗАБОРОНЕНО - short echo
<?= $var ?>
```

**Багаторядковий PHP у HTML:**

```php
<!-- ✅ ПРАВИЛЬНО - теги на окремих рядках -->
<div>
    <?php if ($condition) {
    	echo 'text';
    } ?>
</div>

<!-- ❌ НЕПРАВИЛЬНО - теги не на окремих рядках -->
<div><?php if ($condition) {
	echo 'text';
} ?></div>
```

**Quotes (Лапки):**

```php
// ✅ Одинарні лапки для звичайних стрінгів
$text = 'Hello World';

// ✅ Подвійні лапки для інтерполяції змінних
$text = "Hello $name";

// ✅ Альтернативний стиль для уникнення екранування
$html = '<a href="/link">text</a>';
$html = "<a href='$link'>text</a>";

// ❌ НЕПРАВИЛЬНО - зайве екранування
$html = '<a href=\"/link\">text</a>';
```

**Include/Require:**

```php
// ✅ ПРАВИЛЬНО - без дужок, з одним пробілом
require_once ABSPATH . 'file.php';
include_once WPINC . '/file.php';

// ❌ НЕПРАВИЛЬНО - з дужками
require_once ABSPATH . 'file.php';
include_once WPINC . '/file.php';
```

### 2. Naming Conventions (Іменування)

**Functions та Variables:**

```php
// ✅ ПРАВИЛЬНО - lowercase з underscores
function some_name($some_variable)
{
	return $some_variable;
}

$user_count = 10;
$post_data = get_post();

// ❌ ЗАБОРОНЕНО - camelCase
function someName($someVariable)
{
	return $someVariable;
}

$userCount = 10;
$postData = get_post();
```

**Classes, Interfaces, Traits, Enums:**

```php
// ✅ ПРАВИЛЬНО - Capitalized_Words з underscores
class Walker_Category extends Walker {}
interface Mailer_Interface {}
trait Post_Handler {}
enum HTTP_Status {}

// Акроніми - повністю великі
class WP_HTTP_Response {}
class XML_Parser {}

// ❌ НЕПРАВИЛЬНО - camelCase або без underscores
class WalkerCategory extends Walker {}
interface MailerInterface {}
```

**Constants (Константи):**

```php
// ✅ ПРАВИЛЬНО - UPPERCASE з underscores
define('DOING_AJAX', true);
const MEDICI_VERSION = '1.0.12';

// ❌ НЕПРАВИЛЬНО - lowercase або mixed case
define('doing_ajax', true);
const mediciVersion = '1.0.12';
```

**Files (Файли):**

```php
// ✅ ПРАВИЛЬНО - lowercase з hyphens
// class-wp-error.php
// trait-blog-ajax.php
// inc/class-blog-module.php

// Class файли - префікс class-, underscores замінюються hyphens
class WP_Error {} // → class-wp-error.php
class Blog_Module {} // → class-blog-module.php

// ❌ НЕПРАВИЛЬНО
// class_wp_error.php (underscores)
// BlogModule.php (camelCase)
```

**Dynamic Hooks (Динамічні хуки):**

```php
// ✅ ПРАВИЛЬНО - інтерполяція з фігурними дужками
do_action("{$status}_{$type}", $id);
apply_filters("{$prefix}_custom_filter", $value);

// ❌ НЕПРАВИЛЬНО - конкатенація
do_action($status . '_' . $type, $id);
apply_filters($prefix . '_custom_filter', $value);
```

### 3. Whitespace та Indentation

**Spacing навколо операторів:**

```php
// ✅ ПРАВИЛЬНО - пробіли навколо операторів
$x = 1 + 2;
$result = $a * $b;
$is_valid = $x === $y;
$assigned = $value ? 'yes' : 'no';

// ❌ НЕПРАВИЛЬНО - без пробілів
$x = 1 + 2;
$result = $a * $b;
$is_valid = $x === $y;
```

**Spacing у control structures:**

```php
// ✅ ПРАВИЛЬНО - пробіли всередині дужок
if ($condition) {
	action();
}

while ($x < 10) {
	$x++;
}

foreach ($items as $item) {
	process($item);
}

// ❌ НЕПРАВИЛЬНО - без пробілів
if ($condition) {
	action();
}

while ($x < 10) {
	$x++;
}
```

**Spacing після ком:**

```php
// ✅ ПРАВИЛЬНО - пробіл після коми
function_name($arg1, $arg2, $arg3);
$array = [1, 2, 3];

// ❌ НЕПРАВИЛЬНО - без пробілу
function_name($arg1, $arg2, $arg3);
$array = [1, 2, 3];
```

**Indentation (Відступи):**

```php
// ✅ ПРАВИЛЬНО - використовуйте справжні TABS для структури
function example()
{
	if ($condition) {
		echo 'Hello';
	}
}

// Пробіли для вирівнювання середини рядка
$array = [
	'first' => 'value', // Spaces для вирівнювання
	'second' => 'another', // не tabs!
];

// ❌ НЕПРАВИЛЬНО - пробіли для структурного відступу
function example()
{
	if ($condition) {
		echo 'Hello';
	}
}
```

**Type Casts:**

```php
// ✅ ПРАВИЛЬНО - lowercase canonical forms
$int = (int) $value;
$bool = (bool) $string;
$float = (float) $number;
$string = (string) $int;
$array = (array) $object;
$object = (object) $array;

// ❌ НЕПРАВИЛЬНО - довгі форми або uppercase
$int = (int) $value;
$bool = (bool) $string;
$float = (float) $number;
```

**Array Access (Доступ до масивів):**

```php
// ✅ ПРАВИЛЬНО
$foo[$bar]; // Пробіли навколо змінного індексу
$foo['bar']; // Без пробілів для літералів
$foo[0]; // Без пробілів для чисел

// ❌ НЕПРАВИЛЬНО
$foo[$bar]; // Без пробілів навколо змінної
$foo['bar']; // Пробіли для літералів
$foo[0]; // Пробіли для чисел
```

**Increment/Decrement:**

```php
// ✅ ПРАВИЛЬНО - без пробілів
$i++;
$i--;
++$i;
--$i;

// ❌ НЕПРАВИЛЬНО - з пробілами
$i++;
$i--;
++$i;
--$i;
```

### 4. Formatting Standards

**Brace Style (Стиль фігурних дужок):**

```php
// ✅ ПРАВИЛЬНО - opening brace на тому ж рядку
if ($condition) {
	action();
}

function example()
{
	return true;
}

// ❌ НЕПРАВИЛЬНО - opening brace на новому рядку
if ($condition) {
	action();
}

// ✅ ОБОВ'ЯЗКОВІ braces навіть для одного statement
if ($condition) {
	return true;
}

// ❌ ЗАБОРОНЕНО - без braces
if ($condition) {
	return true;
}
```

**Array Declaration (Оголошення масивів):**

```php
// ✅ ПРАВИЛЬНО - long syntax
$array = [1, 2, 3];
$assoc = [
	'key1' => 'value1',
	'key2' => 'value2',
];

// ❌ НЕПРАВИЛЬНО - short syntax
$array = [1, 2, 3];
$assoc = [
	'key1' => 'value1',
	'key2' => 'value2',
];

// Багаторядкові масиви - кожен елемент на окремому рядку
$args = [
	'post_type' => 'post',
	'posts_per_page' => 10,
	'orderby' => 'date', // Trailing comma!
];
```

**Multiline Function Calls:**

```php
// ✅ ПРАВИЛЬНО - кожен параметр на окремому рядку
$result = some_function($parameter1, $parameter2, $parameter3, $parameter4);

// Складні значення - спочатку присвоїти змінній
$complex_value = [
	'key' => 'value',
];
$result = another_function($simple, $complex_value);

// ❌ НЕПРАВИЛЬНО - все в одному рядку коли занадто довго
$result = some_function($parameter1, $parameter2, $parameter3, $parameter4, $parameter5);
```

**Type Declarations:**

```php
// ✅ ПРАВИЛЬНО
function example(int $param): bool
{
	return true;
}

function nullable(?string $param): ?array
{
	return null;
}

// Один пробіл перед та після типу
function typed(string $a, int $b): void
{
	// код
}

// Return type - без пробілу після ):
function name(): Type {}

// ❌ НЕПРАВИЛЬНО
function example(int $param): bool
{
	// Пробіл перед :
	return true;
}

function nullable(?string $param): ?array
{
	// Пробіл після ?
	return null;
}
```

### 5. Control Structures

**Elseif vs else if:**

```php
// ✅ ПРАВИЛЬНО - використовуйте elseif
if ($condition1) {
	action1();
} elseif ($condition2) {
	action2();
} else {
	action3();
}

// ❌ НЕПРАВИЛЬНО - else if
if ($condition1) {
	action1();
} elseif ($condition2) {
	action2();
}
```

**Yoda Conditions (Умови Йоди):**

```php
// ✅ ПРАВИЛЬНО - константа/літерал зліва
if ('publish' === $status) {
	// захищає від випадкового присвоєння
}

if (true === $is_active) {
	// true не може бути перезаписано
}

if (10 < $count) {
	// читається як "10 менше ніж count"
}

// ❌ НЕПРАВИЛЬНО - змінна зліва
if ($status === 'publish') {
	// можливо випадково написати $status = 'publish'
}

// Виняток: null checks можуть бути звичайними
if (null === $value || $value === null) {
	// обидва варіанти OK
}
```

### 6. Security (ОБОВ'ЯЗКОВО!)

**ЗАВЖДИ:**

```php
// Escape output - ОБОВ'ЯЗКОВО!
echo esc_html($text);
echo esc_url($url);
echo esc_attr($attribute);
echo esc_js($javascript);
echo wp_kses_post($html); // Для HTML контенту

// Sanitize input - ОБОВ'ЯЗКОВО!
$clean = sanitize_text_field($_POST['field']);
$email = sanitize_email($_POST['email']);
$html = wp_kses_post($_POST['content']);
$int = absint($_GET['id']);

// Check capabilities - ОБОВ'ЯЗКОВО!
if (!current_user_can('manage_options')) {
	wp_die(__('Unauthorized', 'medici.agency'));
}

// Verify nonces - ОБОВ'ЯЗКОВО!
if (!wp_verify_nonce($_POST['nonce'], 'action_name')) {
	wp_die(__('Security check failed', 'medici.agency'));
}

check_ajax_referer('medici_nonce', 'nonce');
```

**НІКОЛИ:**

```php
// ❌ ЗАБОРОНЕНО - не escaped output!
echo $_GET['param'];
echo $user_input;

// ❌ ЗАБОРОНЕНО - не sanitized input!
$value = $_POST['field'];
update_option('key', $_REQUEST['value']);

// ❌ ЗАБОРОНЕНО - без capabilities check!
if (is_admin()) {
	delete_all_posts(); // НЕБЕЗПЕЧНО!
}
```

### 7. Text Domain (ЗАВЖДИ!)

```php
// ✅ ПРАВИЛЬНО - завжди використовуйте text domain
__('Text', 'medici.agency');
_e('Text', 'medici.agency');
esc_html__('Text', 'medici.agency');
esc_html_e('Text', 'medici.agency');
esc_attr__('Text', 'medici.agency');

// Sprintf з перекладом
sprintf(__('Hello %s', 'medici.agency'), $name);

// Множина
_n('%s comment', '%s comments', $count, 'medici.agency');

// ❌ НІКОЛИ НЕ ВИКОРИСТОВУЙТЕ
echo 'Hardcoded text'; // Без перекладу
__('Text'); // Без text domain
__('Text', 'other-domain'); // Інший text domain
```

### 8. Hooks Priority

```php
// Low priority (1-5) для early execution
add_action('init', 'function_name', 1);

// Normal priority (10) - default
add_action('wp_enqueue_scripts', 'function_name', 10);

// High priority (100-999) для late execution
add_action('wp_footer', 'function_name', 999);
```

### 9. Додаткові Правила

**Error Control Operator (@):**

```php
// ❌ УНИКАЙТЕ використання @
@unlink($file); // Приховує помилки!

// ✅ ПРАВИЛЬНО - обробляйте помилки явно
if (file_exists($file)) {
	unlink($file);
}
```

**Closures (Анонімні функції):**

```php
// ✅ ПРАВИЛЬНО - стандартний spacing
$closure = function ($param) {
	return $param * 2;
};

array_map(function ($item) {
	return $item->name;
}, $items);

// З use
$multiplier = 10;
$closure = function ($value) use ($multiplier) {
	return $value * $multiplier;
};
```

**Don't Use extract():**

```php
// ❌ ЗАБОРОНЕНО - extract() створює непередбачувані змінні
extract($_POST);
extract($array);

// ✅ ПРАВИЛЬНО - явний доступ до даних
$name = $_POST['name'];
$email = $_POST['email'];

// Або через масив
$data = [
	'name' => $_POST['name'],
	'email' => $_POST['email'],
];
```

**Regular Expressions:**

```php
// ✅ ПРАВИЛЬНО - документуйте складні patterns
// Pattern для email validation
$pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

if (preg_match($pattern, $email)) {
	// valid email
}

// Складний regex - розбивайте на частини з коментарями
$pattern =
	'/' .
	'(?P<year>\d{4})' . // Рік (4 цифри)
	'-' .
	'(?P<month>\d{2})' . // Місяць (2 цифри)
	'-' .
	'(?P<day>\d{2})' . // День (2 цифри)
	'/';
```

### 10. Типові помилки WPCS та їх виправлення

**Ця секція базується на реальних помилках, виявлених під час аудиту проєкту Medici (грудень 2024).**

#### 10.1 Short Ternaries (ЗАБОРОНЕНО!)

```php
// ❌ ЗАБОРОНЕНО - short ternary
$color = $value ?: '#3B82F6';
$name = $input ?: 'Default';

// ✅ ПРАВИЛЬНО - повний тернарний оператор
$color = !empty($value) ? $value : '#3B82F6';
$name = !empty($input) ? $input : 'Default';
```

**Причина:** WPCS вважає short ternaries неоднозначними та забороняє їх використання.

#### 10.2 wp_unslash() для POST/GET/REQUEST даних

```php
// ❌ НЕПРАВИЛЬНО - без wp_unslash
$color = sanitize_hex_color($_POST['color']);

// ✅ ПРАВИЛЬНО - з wp_unslash
$color = sanitize_hex_color(wp_unslash($_POST['color']));

// ✅ ПРАВИЛЬНО - для текстових полів
$name = sanitize_text_field(wp_unslash($_POST['name']));

// ✅ ПРАВИЛЬНО - для textarea
$message = sanitize_textarea_field(wp_unslash($_POST['message']));
```

**Причина:** WordPress додає slashes до $\_POST, $\_GET, $\_REQUEST. `wp_unslash()` видаляє їх перед санітизацією.

#### 10.3 Nonce Verification та phpcs:ignore

```php
// ❌ НЕПРАВИЛЬНО - WPCS скаржиться на відсутність nonce
function my_save_term(int $term_id): void
{
	if (!isset($_POST['my_field'])) {
		return;
	}
	// ...
}

// ✅ ПРАВИЛЬНО для власних форм - явна перевірка nonce
function my_save_form(): void
{
	if (!wp_verify_nonce($_POST['my_nonce'], 'my_action')) {
		return;
	}
	$value = sanitize_text_field(wp_unslash($_POST['my_field']));
}

// ✅ ПРАВИЛЬНО для WordPress API хуків (taxonomy, meta boxes)
// Nonce перевіряється WordPress автоматично
function my_save_term(int $term_id): void
{
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress Taxonomy API.
	if (!isset($_POST['my_field'])) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress Taxonomy API.
	$value = sanitize_text_field(wp_unslash($_POST['my_field']));
}
add_action('created_my_taxonomy', 'my_save_term');
add_action('edited_my_taxonomy', 'my_save_term');
```

**WordPress API хуки, де nonce перевіряється автоматично:**

- `created_{taxonomy}`, `edited_{taxonomy}` - taxonomy terms
- `save_post`, `save_post_{post_type}` - post meta boxes
- `personal_options_update`, `edit_user_profile_update` - user profile

#### 10.4 Inline коментарі з крапкою

```php
// ❌ НЕПРАВИЛЬНО - без крапки
// Sanitize input
$value = sanitize_text_field($input);

// ✅ ПРАВИЛЬНО - з крапкою в кінці
// Sanitize input.
$value = sanitize_text_field($input);

// ✅ ПРАВИЛЬНО - коментар-пояснення
$rgb = [59, 130, 246]; // Fallback to blue.
```

**Причина:** WPCS вимагає, щоб всі inline коментарі закінчувались крапкою, знаком оклику або питання.

#### 10.5 Output Escaping з phpcs:ignore

```php
// ❌ НЕПРАВИЛЬНО - WPCS скаржиться на wpautop
echo wpautop($content);

// ✅ ПРАВИЛЬНО - якщо контент вже санітизований
$content = wp_kses_post($raw_content);
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already sanitized with wp_kses_post above.
echo wpautop($content);

// ✅ АБО - обернути в wp_kses_post
echo wp_kses_post(wpautop($raw_content));
```

#### 10.6 Line Endings (CRLF → LF)

```bash
# Перевірка line endings
file inc/my-file.php
# Має показати: "ASCII text" без "CRLF"

# Автоматичне виправлення через PHPCBF
~/.config/composer/vendor/bin/phpcbf --standard=WordPress inc/my-file.php

# Або через dos2unix
dos2unix inc/my-file.php

# Git налаштування для автоматичної конвертації
git config --global core.autocrlf input
```

#### 10.7 Імена файлів (filename conventions)

```php
// ❌ НЕПРАВИЛЬНО - підкреслення в імені файлу
single-medici_blog.php

// ✅ ПРАВИЛЬНО - тільки дефіси
single-medici-blog.php

// ⚠️ ВИНЯТОК - WordPress templates
// single-{post_type}.php - вимога WordPress, phpcs:ignore дозволений
```

**Коли ігнорувати:**

```php
<?php
// phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress template naming convention.
```

#### 10.8 PHPCBF - автоматичне виправлення

```bash
# Встановлення PHPCS + WPCS
composer global require "squizlabs/php_codesniffer=^3.13" "wp-coding-standards/wpcs=^3.3" -W

# Дозволити plugin
composer global config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true

# Перевірка файлу
~/.config/composer/vendor/bin/phpcs --standard=WordPress -s inc/my-file.php

# Автоматичне виправлення
~/.config/composer/vendor/bin/phpcbf --standard=WordPress inc/my-file.php

# Перевірка всіх PHP файлів
~/.config/composer/vendor/bin/phpcs --standard=WordPress --extensions=php -s inc/
```

**Що PHPCBF виправляє автоматично:**

- ✅ Line endings (CRLF → LF)
- ✅ Trailing whitespace
- ✅ Indentation (spaces vs tabs)
- ✅ Spacing навколо operators

**Що потребує ручного виправлення:**

- ❌ Short ternaries
- ❌ Nonce verification
- ❌ Output escaping
- ❌ wp_unslash()

---

## 🎯 ТИПІЗАЦІЯ ТА STRICT_TYPES У WORDPRESS

### Загальна інформація

Використання строгої типізації (`declare(strict_types=1)`) та PHP 7.4+ типізації значно підвищує:

- ✅ Стабільність коду
- ✅ Зменшення silent failures (тихих помилок)
- ✅ Покращення підтримки IDE (автодоповнення, перевірки)
- ✅ Самодокументованість коду

### 1. Правильна декларація strict_types

**КРИТИЧНО:** Директива `declare(strict_types=1);` має бути **найпершою** інструкцією у файлі, одразу після `<?php`.

```php
<?php
declare(strict_types=1);

/**
 * Файл з строгою типізацією.
 *
 * @package Medici\Theme
 */

namespace Medici\Theme\Core;

// БЕЗ strict_types: PHP конвертує "10" (string) → 10 (int) автоматично
// З declare(strict_types=1): викине TypeError, допоможе знайти помилку
```

**ВАЖЛИВО:**

- Діє **ЛИШЕ на той файл**, у якому оголошена
- Має бути у **КОЖНОМУ** файлі окремо
- Не поширюється на підключені файли

### 2. Типізація функцій та методів (PHP 7.4+)

**Правило:** Завжди вказуйте типи аргументів та return type.

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\Utils;

/**
 * Отримує кількість переглядів посту з перевіркою типів.
 *
 * @param int $post_id ID посту (обов'язково integer).
 * @return int Кількість переглядів або 0.
 */
function get_post_views(int $post_id): int
{
	// get_post_meta може повернути mixed, тому приводимо тип
	$views = get_post_meta($post_id, 'post_views_count', true);

	if (!is_numeric($views)) {
		return 0;
	}

	return (int) $views;
}

// ✅ ПРАВИЛЬНО
$views = get_post_views(125);

// ❌ ПОМИЛКА: Fatal Error: Argument #1 must be of type int
$views = get_post_views('125');
```

### 3. Типізація властивостей класу (PHP 7.4+)

**Typed Properties** - властивості класу з явним типом.

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\SEO;

class Metadata_Handler
{
	// Типізовані властивості
	private int $post_id;
	private string $default_title;
	private bool $is_indexable;

	/**
	 * @param int    $post_id
	 * @param string $default_title
	 * @param bool   $is_indexable
	 */
	public function __construct(int $post_id, string $default_title = '', bool $is_indexable = true)
	{
		$this->post_id = $post_id;
		$this->default_title = $default_title;
		$this->is_indexable = $is_indexable;
	}

	public function get_seo_title(): string
	{
		$custom_title = get_post_meta($this->post_id, '_medici_seo_title', true);

		// У strict mode повертаємо string гарантовано
		return is_string($custom_title) && !empty($custom_title) ? $custom_title : $this->default_title;
	}
}
```

### 4. Nullable типи та Union Types (PHP 8.0+)

WordPress часто повертає `false` або `null` у випадку помилки. Використовуйте `?` (nullable) або union types.

**Nullable type (`?string`):**

```php
<?php
declare(strict_types=1);

use WP_Post;

/**
 * Отримує заголовок посту або null.
 *
 * @param int $post_id
 * @return string|null
 */
function get_safe_post_title(int $post_id): ?string
{
	$post = get_post($post_id);

	// get_post може повернути WP_Post, array або null
	if (!$post instanceof WP_Post) {
		return null;
	}

	return $post->post_title;
}

// Використання
$title = get_safe_post_title(123);
if (null !== $title) {
	echo esc_html($title);
}
```

**Union Types (PHP 8.0+):**

```php
<?php
declare(strict_types=1);

/**
 * Отримує значення опції.
 *
 * @param string $key
 * @return string|int|bool|null Різні типи в залежності від опції
 */
function get_theme_option(string $key): string|int|bool|null
{
	$value = get_option("medici_{$key}");

	if (false === $value) {
		return null;
	}

	return $value; // може бути string, int, bool
}
```

### 5. Value Objects та Constructor Property Promotion (PHP 8.0+)

Замість асоціативних масивів (де структура не гарантована) використовуйте **DTO (Data Transfer Objects)** або **Value Objects**.

**Constructor Property Promotion** - властивості оголошуються і присвоюються прямо в конструкторі.

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\Blocks;

/**
 * Value Object для налаштувань кнопки.
 */
class Button_Config
{
	/**
	 * Constructor promotion: властивості оголошуються в конструкторі.
	 *
	 * @param string $text Текст кнопки
	 * @param string $url URL посилання
	 * @param string $style Стиль (primary, secondary, tertiary)
	 * @param bool   $open_new_tab Відкривати в новій вкладці
	 */
	public function __construct(
		public readonly string $text,
		public readonly string $url,
		public readonly string $style = 'primary',
		public readonly bool $open_new_tab = false
	) {}
}

/**
 * Рендер кнопки з типізованою конфігурацією.
 *
 * @param Button_Config $config
 * @return void
 */
function render_button(Button_Config $config): void
{
	$target = $config->open_new_tab ? 'target="_blank"' : '';

	printf(
		'<a href="%s" class="btn btn-%s" %s>%s</a>',
		esc_url($config->url),
		esc_attr($config->style),
		$target,
		esc_html($config->text)
	);
}

// Використання з named arguments (PHP 8.0+)
$btn = new Button_Config(text: 'Детальніше', url: 'https://example.com', style: 'outline');

render_button($btn);
```

**Переваги Value Objects:**

- ✅ IDE підказує доступні властивості
- ✅ Неможливо передати невірну структуру
- ✅ Самодокументованість коду
- ✅ Перевірка типів на етапі розробки

### 6. Return Type для WordPress функцій

WordPress функції часто повертають `mixed`. Додайте обгортки з типізацією:

```php
<?php
declare(strict_types=1);

/**
 * Обгортка get_option з гарантованим string.
 *
 * @param string $option
 * @param string $default
 * @return string
 */
function get_string_option(string $option, string $default = ''): string
{
	$value = get_option($option, $default);
	return is_string($value) ? $value : $default;
}

/**
 * Обгортка get_post_meta з гарантованим int.
 *
 * @param int    $post_id
 * @param string $key
 * @param int    $default
 * @return int
 */
function get_int_meta(int $post_id, string $key, int $default = 0): int
{
	$value = get_post_meta($post_id, $key, true);
	return is_numeric($value) ? (int) $value : $default;
}
```

### 7. Практичні приклади для Medici Theme

**Приклад 1: Blog Module з типізацією**

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\Blog;

class Reading_Time_Calculator
{
	private const WORDS_PER_MINUTE_SLOW = 150;
	private const WORDS_PER_MINUTE_AVERAGE = 200;
	private const WORDS_PER_MINUTE_FAST = 250;

	public function __construct(
		private readonly int $words_per_minute = self::WORDS_PER_MINUTE_AVERAGE
	) {}

	/**
	 * Обчислює час читання.
	 *
	 * @param string $content Контент статті
	 * @return int Хвилини читання
	 */
	public function calculate(string $content): int
	{
		$word_count = str_word_count(strip_tags($content));
		$minutes = (int) ceil($word_count / $this->words_per_minute);

		return max(1, $minutes); // Мінімум 1 хвилина
	}

	/**
	 * Форматує час читання для виводу.
	 *
	 * @param int $minutes
	 * @return string
	 */
	public function format(int $minutes): string
	{
		return sprintf(
			_n('%d хвилина читання', '%d хвилини читання', $minutes, 'medici.agency'),
			$minutes
		);
	}
}

// Використання
$calculator = new Reading_Time_Calculator(words_per_minute: 180);
$minutes = $calculator->calculate(get_the_content());
echo esc_html($calculator->format($minutes));
```

**Приклад 2: Category Icon Mapper**

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\Blog;

class Category_Icon_Mapper
{
	/**
	 * Мапа іконок категорій.
	 *
	 * @var array<string, string>
	 */
	private const ICONS = [
		'кейси' => '📊',
		'поради' => '💡',
		'smm' => '📱',
		'реклама' => '📈',
		'технології' => '💻',
	];

	private const DEFAULT_ICON = '📁';

	/**
	 * Отримує іконку для категорії.
	 *
	 * @param string $slug Slug категорії
	 * @return string Emoji іконка
	 */
	public function get_icon(string $slug): string
	{
		return self::ICONS[$slug] ?? self::DEFAULT_ICON;
	}

	/**
	 * Отримує всі доступні іконки.
	 *
	 * @return array<string, string>
	 */
	public function get_all_icons(): array
	{
		return self::ICONS;
	}
}
```

**ВАЖЛИВО:**

- Ці методики роблять код самодокументованим
- Значно покращують надійність у великих проєктах
- IDE краще підказує та перевіряє код
- Помилки виявляються на етапі розробки, а не production

### 8. Interface Design та PHPStan Compliance

**КРИТИЧНО:** При створенні інтерфейсів та abstract класів дотримуйтесь цих правил:

#### 8.1 Інтерфейс має декларувати ВСІ публічні методи

```php
<?php
declare(strict_types=1);

namespace Medici\Events;

// ❌ НЕПРАВИЛЬНО - метод є в AbstractEvent, але не в інтерфейсі
interface EventInterface
{
	public function getName(): string;
	public function getPayload(): array;
	// getEventId() ВІДСУТНІЙ - PHPStan помилка!
}

abstract class AbstractEvent implements EventInterface
{
	protected ?int $eventId = null;

	// Метод є, але в інтерфейсі не оголошений
	public function getEventId(): ?int
	{
		return $this->eventId;
	}
}

// ✅ ПРАВИЛЬНО - всі методи оголошені в інтерфейсі
interface EventInterface
{
	public function getName(): string;
	public function getPayload(): array;
	public function getEventId(): ?int; // ← Додано!
	public function setEventId(int $id): void; // ← Додано!
}
```

#### 8.2 Правило "Interface First"

**При додаванні нового методу:**

1. **СПОЧАТКУ** додай метод до інтерфейсу
2. **ПОТІМ** реалізуй в abstract/concrete класі
3. **НІКОЛИ** не додавай методи лише до реалізації

```php
// Порядок дій при додаванні нового методу:
// 1. EventInterface.php → додати сигнатуру
// 2. AbstractEvent.php → додати реалізацію
// 3. composer phpstan → перевірити
```

#### 8.3 PHPStan перевірка ПЕРЕД комітом

```bash
# ОБОВ'ЯЗКОВО перед кожним комітом:
composer phpstan

# Якщо є помилки типу "Call to an undefined method":
# → Перевір чи метод оголошений в інтерфейсі
# → Додай сигнатуру методу до інтерфейсу
```

**Типові PHPStan помилки та їх вирішення:**

| Помилка                                              | Причина                 | Вирішення                      |
| ---------------------------------------------------- | ----------------------- | ------------------------------ |
| `Call to undefined method Interface::method()`       | Метод не в інтерфейсі   | Додати сигнатуру до інтерфейсу |
| `Method must be compatible with Interface::method()` | Невірні типи параметрів | Виправити типи в реалізації    |
| `Return type must be compatible`                     | Невірний return type    | Синхронізувати типи            |

---

## 🏗️ СУЧАСНА ОРГАНІЗАЦІЯ КОДУ ДЛЯ WORDPRESS

### Загальна концепція

Сучасна розробка під WordPress відходить від **"скриптового" підходу** (все в одному `functions.php`) до **інженерного (application-based)** підходу, схожого на Laravel або Symfony.

### 1. Структура проєкту за стандартом PSR-4

Використовуйте **Composer** для автозавантаження класів. Забудьте про десятки `require_once`.

**Рекомендована структура плагіна/теми:**

```
my-plugin/
├── composer.json       # Налаштування автозавантаження
├── my-plugin.php       # Точка входу (тільки ініціалізація)
├── assets/             # JS, CSS, Images (публічні файли)
│   ├── css/
│   ├── js/
│   └── img/
├── languages/          # Файли перекладу .pot, .po
├── templates/          # HTML/PHP шаблони (View)
├── src/                # Основний PHP код (PSR-4)
│   ├── Setup/          # Класи ініціалізації (Enqueues, Theme Support)
│   ├── Admin/          # Логіка адмінки (Settings, MetaBoxes)
│   ├── Blocks/         # Логіка Gutenberg блоків
│   ├── Api/            # REST API контролери
│   └── Utils/          # Допоміжні класи (Helpers)
├── vendor/             # Бібліотеки Composer (не чіпати руками)
└── views/              # Шаблони (якщо Timber/Blade)
```

### 2. Налаштування Autoloading (Composer)

**Крок 1:** Виконайте `composer init` у папці плагіна.

**Крок 2:** Додайте секцію `autoload` до `composer.json`:

```json
{
	"name": "medici/my-awesome-plugin",
	"description": "Modern WordPress plugin with PSR-4 autoloading",
	"type": "wordpress-plugin",
	"autoload": {
		"psr-4": {
			"Medici\\Plugin\\": "src/"
		}
	},
	"require": {
		"php": ">=8.0"
	},
	"require-dev": {
		"phpstan/phpstan": "^1.10",
		"squizlabs/php_codesniffer": "^3.7"
	}
}
```

**Крок 3:** Виконайте `composer dump-autoload`.

**Крок 4:** У головному файлі плагіна підключіть **лише один файл**:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 * Version: 1.0.0
 */

// Перевірка прямого доступу
if (!defined('ABSPATH')) {
	exit();
}

// Autoload через Composer
require_once __DIR__ . '/vendor/autoload.php';

// Ініціалізація плагіна
Medici\Plugin\Core\Plugin::get_instance()->run();
```

Тепер клас `src/Admin/Settings.php` буде доступний як `new Medici\Plugin\Admin\Settings()`.

### 3. Використання Service Container (Dependency Injection)

Замість створення екземплярів класів через `new ClassName()` у різних місцях (що створює жорсткі залежності), використовуйте **Dependency Injection**.

**Простий приклад ініціалізації:**

```php
<?php
declare(strict_types=1);

namespace Medici\Plugin\Core;

/**
 * Головний клас плагіна.
 */
class Plugin
{
	private static ?Plugin $instance = null;

	/**
	 * Singleton pattern.
	 */
	public static function get_instance(): Plugin
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Приватний конструктор (Singleton).
	 */
	private function __construct() {}

	/**
	 * Запуск плагіна.
	 */
	public function run(): void
	{
		// Ініціалізація модулів
		(new \Medici\Plugin\Admin\Menu())->init();
		(new \Medici\Plugin\Api\Controller())->register_routes();
		(new \Medici\Plugin\Blocks\Loader())->register_blocks();
	}
}
```

**Dependency Injection Container (для складних проєктів):**

Для великих проєктів використовуйте бібліотеки:

- [PHP-DI](https://php-di.org/) - найпопулярніший DI контейнер
- [Pimple](https://pimple.symfony.com/) - легковаговий контейнер від Symfony

### 4. Сучасні стартові шаблони (Boilerplates)

Для старту нових проєктів використовуйте перевірені рішення:

**Bedrock** - сучасна структура WordPress:

- ✅ Зберігає конфіги в `.env` файлі (безпека)
- ✅ Керує WordPress як залежністю Composer
- ✅ Має папку `app` замість `wp-content`
- 🔗 https://roots.io/bedrock/

**Sage (Roots)** - стартова тема для професіоналів:

- ✅ Використовує Blade (шаблонізатор Laravel)
- ✅ Має вбудований процес збірки (Bud.js/Webpack)
- ✅ Підтримка Tailwind CSS/SCSS out of the box
- 🔗 https://roots.io/sage/

**WordPlate** - легковагова альтернатива Bedrock:

- ✅ Простіша структура
- ✅ Laravel-like конфігурація
- 🔗 https://wordplate.github.io/

### 5. Відокремлення логіки від представлення (MVC)

**Не пишіть логіку** (запити до БД, перевірки) всередині HTML-структури.

**Погано (старий стиль):**

```php
<!-- ❌ НЕПРАВИЛЬНО -->
<div class="header">
    <?php
    $user = wp_get_current_user();
    if (in_array('administrator', (array) $user->roles)) {
    	echo 'Hello Admin';
    }
    ?>
</div>
```

**Добре (сучасний стиль):**

**Логіка у класі:**

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\View;

class Header_View
{
	/**
	 * Отримує привітання для поточного користувача.
	 *
	 * @return string
	 */
	public function get_greeting(): string
	{
		return current_user_can('manage_options')
			? __('Hello Admin', 'medici.agency')
			: __('Hello User', 'medici.agency');
	}
}
```

**Шаблон (чистий PHP):**

```php
<?php
$header_view = new Medici\Theme\View\Header_View(); ?>
<div class="header">
    <?php echo esc_html($header_view->get_greeting()); ?>
</div>
```

**Або з Blade (Sage):**

```blade
<div class="header">
    {{ $header_view->get_greeting() }}
</div>
```

### 6. Практичний приклад: Medici Theme з PSR-4

**Структура:**

```
medici/
├── composer.json
├── functions.php       # Тільки autoload та ініціалізація
├── style.css
├── src/
│   ├── Core/
│   │   └── Theme.php   # Головний клас теми
│   ├── Blog/
│   │   ├── Reading_Time_Calculator.php
│   │   └── Category_Icon_Mapper.php
│   ├── Admin/
│   │   ├── Settings_Page.php
│   │   └── Meta_Boxes.php
│   └── Assets/
│       └── Asset_Loader.php
└── vendor/
```

**functions.php:**

```php
<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit();
}

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

// Константи
define('MEDICI_VERSION', '1.0.12');
define('MEDICI_DIR', __DIR__);
define('MEDICI_URL', get_stylesheet_directory_uri());

// Ініціалізація теми
add_action(
	'after_setup_theme',
	function () {
		Medici\Theme\Core\Theme::get_instance()->init();
	},
	1
);
```

**src/Core/Theme.php:**

```php
<?php
declare(strict_types=1);

namespace Medici\Theme\Core;

use Medici\Theme\Assets\Asset_Loader;
use Medici\Theme\Blog\Reading_Time_Calculator;

class Theme
{
	private static ?Theme $instance = null;

	public static function get_instance(): Theme
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void
	{
		// Підтримка теми
		$this->add_theme_support();

		// Завантаження assets
		(new Asset_Loader())->init();

		// Модулі
		add_filter('the_content', [$this, 'add_reading_time'], 10);
	}

	private function add_theme_support(): void
	{
		add_theme_support('post-thumbnails');
		add_theme_support('title-tag');
		add_theme_support('html5', ['search-form', 'comment-form']);
	}

	public function add_reading_time(string $content): string
	{
		if (!is_single()) {
			return $content;
		}

		$calculator = new Reading_Time_Calculator();
		$minutes = $calculator->calculate($content);
		$time_text = $calculator->format($minutes);

		return sprintf('<div class="reading-time">%s</div>%s', esc_html($time_text), $content);
	}
}
```

---

## 🎨 СУЧАСНА ОРГАНІЗАЦІЯ КОДУ ДЛЯ GENERATEPRESS

### Загальна концепція

Для професійного розробника, що працює з **GeneratePress (GP)** і **GenerateBlocks (GB)**, стандартний підхід "все в `functions.php`" застарілий.

### 1. Гібридна структура Child Theme

Сучасна дочірня тема GP має бути **структурованим додатком**, а не смітником для сніпетів.

**Підхід:** Modular Snippets замість одного гігантського `functions.php`.

**Рекомендована структура:**

```
gp-child-theme/
├── assets/              # Статичні файли
│   ├── css/             # Кастомні стилі (якщо не вистачає GB Global Styles)
│   ├── js/              # Ваші скрипти
│   └── img/
├── inc/                 # PHP логіка (розбита на модулі)
│   ├── elements.php     # Хуки для GP Elements
│   ├── woocommerce.php  # Твіки для магазину
│   ├── performance.php  # Вимкнення зайвих скриптів/стилів
│   └── shortcodes.php   # Власні шорткоди
├── parts/               # Шаблони (рідко потрібні, бо є Block Elements)
├── functions.php        # Тільки підключення файлів з inc/
└── style.css            # Тільки базові метадані теми
```

**functions.php (автозавантаження модулів):**

```php
<?php
/**
 * Medici Child Theme - Functions
 */

if (!defined('ABSPATH')) {
	exit();
}

// Константи
define('MEDICI_VERSION', '1.0.12');
define('MEDICI_DIR', get_stylesheet_directory());
define('MEDICI_URL', get_stylesheet_directory_uri());

// Автоматичне підключення всіх файлів з папки inc/
foreach (glob(MEDICI_DIR . '/inc/*.php') as $filename) {
	require_once $filename;
}
```

### 2. Стратегія "Elements First" (замість редагування шаблонів)

**КРИТИЧНО:** У GeneratePress **НЕ РЕКОМЕНДУЄТЬСЯ** копіювати файли шаблонів (`header.php`, `single.php`) у дочірню тему, оскільки це блокує оновлення батьківської теми.

**Замість цього використовуйте модуль Elements:**

**Block Elements - Loop Template:**

- Для повного переписування дизайну архівів та записів
- Дозволяє верстати динамічні шаблони візуально в GB
- Не торкаючись PHP-файлів

**Block Elements - Page Hero:**

- Для заміни заголовків сторінок
- Візуальне редагування hero секцій

**Block Elements - Hook:**

- Для вставки коду (аналітика, мета-теги)
- Для вставки блоків у конкретні місця
- Приклад: `generate_after_entry_content` для "Схожі статті"

**Layout Elements:**

- Для програмного відключення елементів
- Приклад: сайдбар на Checkout без `display: none`

**Доступні хуки GeneratePress:**

```php
// Before/After Content
generate_before_main_content
generate_after_main_content
generate_before_content
generate_after_content
generate_before_entry_content
generate_after_entry_content

// Header/Footer
generate_before_header
generate_after_header
generate_before_footer
generate_after_footer

// Sidebar
generate_before_sidebar
generate_after_sidebar
```

### 3. Організація CSS: Global Styles у GenerateBlocks

З приходом **GenerateBlocks 2.x**, більшість CSS має жити всередині **Global Styles**, а не в `style.css`.

**Global Styles (BEM-like):**

- ✅ Створюйте глобальні стилі для кнопок, карток, контейнерів
- ✅ Називайте зрозуміло: `.card--featured`, `.btn--primary`
- ✅ Використовуйте 16 доступних класів (див. CODING-RULES розділ 7)

**Локальні стилі:**

- ❌ Уникайте їх
- ✅ Якщо стилізуєте блок на сторінці, подумайте чи не стане він глобальним

**style.css дочірньої теми** - використовуйте **ТІЛЬКИ** для:

- Стилізації сторонніх плагінів (WooCommerce, Contact Form 7)
- CSS змінних (`:root`), якщо не через GP Customizer
- Складних CSS-анімацій (`@keyframes`)

**Приклад style.css (мінімалістичний):**

```css
/**
 * Theme Name: Medici Child
 * Theme URI: https://medici.agency
 * Template: generatepress
 * Version: 1.0.12
 */

/* CSS змінні (якщо не через Customizer) */
:root {
	--medici-primary: #2563eb;
	--medici-secondary: #1e40af;
}

/* Стилізація сторонніх плагінів */
.woocommerce-message {
	border-left-color: var(--medici-primary);
}

/* Анімації */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
```

### 4. PHP-хуки для модифікації GeneratePress

Якщо потрібно змінити **дані** (а не візуал), використовуйте фільтри GP у файлах папки `/inc/`.

**Приклад `inc/gp-tweaks.php`:**

```php
<?php
declare(strict_types=1);

/**
 * GeneratePress Tweaks
 */

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Зміна тексту "Read more".
 */
add_filter('generate_excerpt_more_output', function (): string {
	return sprintf(
		' ... <a title="%1$s" class="read-more" href="%2$s">%3$s</a>',
		the_title_attribute(['echo' => false]),
		esc_url(get_permalink()),
		__('Читати далі', 'medici.agency')
	);
});

/**
 * Вимкнення Google Fonts для GDPR.
 */
add_filter('generate_google_fonts_array', '__return_empty_array');

/**
 * Додавання schema.org до Article.
 */
add_filter('generate_article_schema', function (array $schema): array {
	if (is_single()) {
		$schema['@type'] = 'BlogPosting';
		$schema['author'] = [
			'@type' => 'Person',
			'name' => get_the_author(),
		];
	}
	return $schema;
});

/**
 * Кастомізація breadcrumbs.
 */
add_filter('generate_breadcrumbs', function (string $breadcrumbs): string {
	// Заміна "Home" на "Головна"
	return str_replace('Home', __('Головна', 'medici.agency'), $breadcrumbs);
});
```

### 5. Оптимізація та скрипти

Для додавання JS/CSS використовуйте хуки `wp_enqueue_scripts` у `inc/assets.php`, а **НЕ** вставляйте теги `<script>` через Elements (це може ламати кешування та порядок завантаження).

**Правильний Enqueue для GP Child:**

**Файл `inc/assets.php`:**

```php
<?php
declare(strict_types=1);

/**
 * Asset Loading
 */

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Завантаження скриптів та стилів.
 */
add_action(
	'wp_enqueue_scripts',
	function (): void {
		// Підключаємо скрипт, залежний від GP
		wp_enqueue_script(
			'medici-main',
			MEDICI_URL . '/assets/js/main.js',
			['generate-main'], // Залежність від скрипта GP
			filemtime(MEDICI_DIR . '/assets/js/main.js'), // Версія = час модифікації файлу
			true // В footer
		);

		// Локалізація для AJAX
		wp_localize_script('medici-main', 'mediciData', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('medici_nonce'),
		]);

		// Conditional loading для blog single
		if (is_single() && 'post' === get_post_type()) {
			wp_enqueue_style(
				'medici-blog-single',
				MEDICI_URL . '/assets/css/blog-single.css',
				[],
				filemtime(MEDICI_DIR . '/assets/css/blog-single.css')
			);
		}
	},
	20
); // Priority 20 - після GP (щоб override працював)
```

### 6. Інтеграція з GenerateBlocks Pro 2.x

**Використання Global Classes у коді:**

```php
<?php
/**
 * Рендер кнопки з GP/GB класами.
 */
function medici_render_cta_button(string $text, string $url): void
{
	printf(
		'<a href="%s" class="gb-button gbp-button--primary">%s</a>',
		esc_url($url),
		esc_html($text)
	);
}
```

**Динамічна генерація GB блоків через PHP:**

```php
<?php
/**
 * Генерація GB Container через PHP.
 */
function medici_render_hero_section(string $title, string $subtitle): string
{
	return sprintf(
		'<!-- wp:generateblocks/container {"uniqueId":"hero123","className":"gbp-section"} -->
        <div class="gb-container gbp-section">
            <h1>%s</h1>
            <p>%s</p>
        </div>
        <!-- /wp:generateblocks/container -->',
		esc_html($title),
		esc_html($subtitle)
	);
}
```

### 7. Практичний приклад: повна інтеграція

**Структура Medici Theme з GP/GB:**

```
medici/
├── functions.php           # Autoload
├── style.css               # Мінімалістичний
├── inc/
│   ├── gp-tweaks.php       # GP фільтри
│   ├── assets.php          # Enqueue scripts/styles
│   ├── shortcodes.php      # Власні шорткоди
│   ├── performance.php     # Оптимізації
│   └── woocommerce.php     # WooCommerce твіки
├── assets/
│   ├── css/
│   │   └── blog-single.css
│   └── js/
│       └── main.js
└── parts/                  # Рідко використовується
```

**ВАЖЛИВО:**

- ✅ Використовуйте **Elements замість шаблонів**
- ✅ Більшість CSS має бути у **Global Styles**
- ✅ PHP хуки у **модульних файлах** (`inc/`)
- ✅ Assets через **`wp_enqueue_scripts`**, НЕ через `<script>` теги
- ✅ Conditional loading для performance
- ❌ НЕ копіюйте файли шаблонів GP у child theme без необхідності

---

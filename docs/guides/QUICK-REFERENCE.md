# ⚡ ШВИДКИЙ ДОВІДНИК CODING-RULES

## 🎯 ЧИТАЙ ЦЕ ПЕРЕД БУДЬ-ЯКИМ КОДОМ (30 СЕКУНД!)

**Цей файл містить ТІЛЬКИ критичні правила для швидкої перевірки.**

---

## 🔴 ТОП-10 КРИТИЧНИХ ПРАВИЛ

| #   | Правило                                | Приклад                               |
| --- | -------------------------------------- | ------------------------------------- |
| 1   | **UniqueId:** 8 hex, lowercase         | `"uniqueId": "96646288"` ✅           |
| 2   | **CSS Vars:** `\\u002d\\u002d` в .md   | `var(\\u002d\\u002daccent)` ✅        |
| 3   | **Responsive:** Завжди додавай         | `@media (max-width: 768px)` ✅        |
| 4   | **Ampersand:** `\\u0026` для pseudo    | `"\\u0026:hover"` ✅                  |
| 5   | **No Rotate:** На section/div/article  | ❌ `"transform": "rotate(20deg)"`     |
| 6   | **Global Classes:** Не className       | `"globalClasses": ["gbp-section"]` ✅ |
| 7   | **pointerEvents:** "none" на overlay   | `"pointerEvents": "none"` ✅          |
| 8   | **Security:** Завжди escape output     | `esc_html($var)` ✅                   |
| 9   | **Типізація:** declare(strict_types=1) | Перший рядок після `<?php` ✅         |
| 10  | **Text Domain:** medici.agency         | `__('Text', 'medici.agency')` ✅      |

---

## 🔧 ГЕНЕРАТОРИ КОДУ

### UniqueId Generator

**JavaScript:**

```javascript
Array.from({ length: 8 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
```

**Python:**

```python
''.join(__import__('secrets').choice('0123456789abcdef') for _ in range(8))
```

**Bash:**

```bash
openssl rand -hex 4
```

---

## 📋 ФОРМАТИ (COPY-PASTE)

### CSS Variables (в .md файлах)

```json
{
	"color": "var(\\u002d\\u002daccent)",
	"backgroundColor": "var(\\u002d\\u002dbase-2)"
}
```

### Hover Effect (правильний)

```json
{
	"transition": "all 0.3s ease 0s",
	"\\u0026:is(:hover, :focus)": {
		"transform": "translateY(-4px)"
	}
}
```

### Two-Level Section

```json
{
  "uniqueId": "outer123",
  "tagName": "section",
  "globalClasses": ["gbp-section"]
}
  → Всередині:
{
  "uniqueId": "inner456",
  "tagName": "div",
  "globalClasses": ["gbp-section__inner"],
  "styles": {
    "maxWidth": "var(\\u002d\\u002dgb-container-width)"
  }
}
```

### PHP Function з типізацією

```php
<?php
declare(strict_types=1);

function get_post_views(int $post_id): int
{
	$views = get_post_meta($post_id, '_medici_views', true);
	return is_numeric($views) ? (int) $views : 0;
}
```

---

## 🚫 ТОП-10 НАЙЧАСТІШИХ ПОМИЛОК

| Помилка                   | Неправильно ❌                        | Правильно ✅                       |
| ------------------------- | ------------------------------------- | ---------------------------------- |
| UniqueId не hex           | `"uniqueId": "hello123"`              | `"uniqueId": "96646288"`           |
| CSS Vars без escape       | `var(--accent)`                       | `var(\\u002d\\u002daccent)`        |
| Великі букви UniqueId     | `"uniqueId": "A1B2C3D4"`              | `"uniqueId": "a1b2c3d4"`           |
| Rotate на блоках          | `"transform": "rotate(20deg)"` на div | Тільки на іконках!                 |
| className замість global  | `"className": "gbp-section"`          | `"globalClasses": ["gbp-section"]` |
| Hover без :focus          | `"&:hover"`                           | `"\\u0026:is(:hover, :focus)"`     |
| Overlay без pointerEvents | `position: absolute`                  | + `"pointerEvents": "none"`        |
| Без responsive            | Тільки desktop стилі                  | + `@media` breakpoints             |
| Unescaped output          | `echo $var;`                          | `echo esc_html($var);`             |
| Без типізації             | `function foo($x)`                    | `function foo(int $x): int`        |

---

## ✅ ШВИДКИЙ CHECKLIST (5 СЕКУНД)

**Перед кожним кодом перевір:**

```
[ ] UniqueId: 8 hex lowercase?
[ ] CSS Vars: \\u002d\\u002d escape?
[ ] Responsive: @media додані?
[ ] Security: esc_html() використано?
[ ] Типізація: declare(strict_types=1)?
```

---

## 📖 МАРШРУТИЗАЦІЯ ФАЙЛІВ

**Яку задачу виконуєш?**

| Задача                        | Читай файл               |
| ----------------------------- | ------------------------ |
| GenerateBlocks patterns/JSON  | → **CORE**               |
| UniqueId, CSS Variables       | → **CORE**               |
| Dynamic Tags API, Query Block | → **ADVANCED**           |
| WooCommerce, Perfmatters      | → **ADVANCED**           |
| Blog Module GeneratePress     | → **ADVANCED** секція 34 |
| PHP код, strict_types         | → **WORDPRESS**          |
| Security, sanitization        | → **WORDPRESS**          |

---

## 🎯 WORKFLOW (ЗАВЖДИ!)

```
1. Read: CODING-RULES.md (Master Index)
2. Визнач тип завдання
3. Read: Відповідний файл (CORE/ADVANCED/WORDPRESS)
4. Перевір цей QUICK-REFERENCE
5. Пиши код
6. Checklist перед commit
```

---

## 🔗 КОРИСНІ ПОСИЛАННЯ

- **Master Index:** CODING-RULES.md (таблиця маршрутизації)
- **Core правила:** CODING-RULES-CORE.md (~1400 рядків)
- **Advanced техніки:** CODING-RULES-ADVANCED.md (~1300 рядків)
- **WordPress стандарти:** CODING-RULES-WORDPRESS.md (~1600 рядків)
- **Повний Checklist:** CHECKLIST.md

---

## 🔍 WPCS ТИПОВІ ПОМИЛКИ (PHP_CodeSniffer)

| Помилка WPCS        | Неправильно ❌                       | Правильно ✅                                       |
| ------------------- | ------------------------------------ | -------------------------------------------------- |
| Short ternary       | `$x ?: 'default'`                    | `! empty( $x ) ? $x : 'default'`                   |
| Без wp_unslash      | `sanitize_text_field( $_POST['x'] )` | `sanitize_text_field( wp_unslash( $_POST['x'] ) )` |
| Коментар без крапки | `// Check value`                     | `// Check value.`                                  |
| wpautop без escape  | `echo wpautop( $content );`          | `// phpcs:ignore ... echo wpautop( $content );`    |

**Nonce verification phpcs:ignore:**

```php
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress Taxonomy API.
$value = sanitize_text_field(wp_unslash($_POST['field']));
```

**Коли можна ігнорувати nonce:**

- ✅ WordPress Taxonomy API hooks (`{taxonomy}_add_form_fields`, `{taxonomy}_edit_form`)
- ✅ WordPress Settings API (`register_setting` з sanitize callback)
- ❌ Власні AJAX handlers - завжди перевіряй nonce!

---

## ⚡ ULTRA-QUICK TIPS

**GenerateBlocks:**

- UniqueId = `openssl rand -hex 4`
- CSS Vars = `\\u002d\\u002d` (подвійний backslash)
- Hover = `\\u0026:is(:hover, :focus)`
- No rotate на section/div

**WordPress:**

- `declare(strict_types=1);` - перший рядок
- `esc_html()` - завжди escape
- `sanitize_text_field()` - завжди sanitize
- Text domain: `'medici.agency'`
- `wp_unslash()` - ПЕРЕД sanitize для POST/GET!

**Performance:**

- Lazy loading: `loading="lazy"`
- Hero images: `loading="eager"` + `fetchpriority="high"`
- Width/Height для CLS

**WPCS Quick Fix:**

```bash
# Автоматичне виправлення (line endings, spacing)
phpcbf --standard=WordPress inc/file.php

# Перевірка помилок
phpcs --standard=WordPress inc/file.php
```

---

**🚀 Версія:** 1.1.0
**📅 Останнє оновлення:** 2025-12-13

**⏱️ Час читання:** 30 секунд
**💾 Економія токенів:** 80-90% для простих завдань

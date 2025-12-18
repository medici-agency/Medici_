# WordPress Hooks Checklist

**Використовуй цей checklist кожного разу коли додаєш `add_filter()` або `add_action()`**

---

## ✅ Pre-Code Checklist

- [ ] **1. Відкрив WordPress Developer Docs для hook:**
  - URL: https://developer.wordpress.org/reference/hooks/`hook_name`/
  - Переглянув signature: `apply_filters( 'name', $param1, $param2, ... )`
  - Зрозумів які параметри передаються та їх типи

- [ ] **2. Перевірив source code у WordPress Core (якщо потрібно):**
  - GitHub: https://github.com/WordPress/WordPress/search?q=`hook_name`
  - Знайшов де hook викликається
  - Переглянув контекст виклику

---

## ✅ During Code Checklist

- [ ] **3. Function signature відповідає hook:**

  ```php
  // ✅ ПРАВИЛЬНО: параметри відповідають apply_filters()
  function my_function(Type1 $param1, Type2 $param2)
  {
  	// ...
  }
  ```

- [ ] **4. PHPDoc коментар додано:**

  ```php
  /**
   * Short description
   *
   * @since 1.0.0
   * @param Type1 $param1 Description
   * @param Type2 $param2 Description
   * @return ReturnType Description
   *
   * @see https://developer.wordpress.org/reference/hooks/hook_name/
   */
  ```

- [ ] **5. Parameters count вказано (якщо 2+ параметри):**

  ```php
  add_filter('hook_name', 'my_function', 10, 2);
  //                                          ↑  ↑
  //                                       priority | params count
  ```

- [ ] **6. Type hints додано (де можливо):**
  ```php
  // ✅ З type hints
  function my_function(WP_Post $post, string $name): string
  {
  	return $name;
  }
  ```

---

## ✅ Testing Checklist

- [ ] **7. PHPStan перевірка:**

  ```bash
  composer phpstan -- inc/your-file.php
  ```

- [ ] **8. Prettier форматування:**

  ```bash
  npx prettier --write inc/your-file.php
  ```

- [ ] **9. Локальне тестування:**
  - Запустив WordPress dev server
  - Перевірив що hook працює без Fatal Errors
  - Перевірив результат у browser/console

---

## ✅ Commit Checklist

- [ ] **10. Коміт message описує що робить hook:**

  ```bash
  git commit -m "✨ Add: [hook_name] filter for [purpose]

  - Signature verified against WordPress docs
  - PHPDoc added with @param types
  - Tested locally: [describe test result]
  "
  ```

---

## 🚨 Типові помилки (уникай!)

### ❌ 1. Забув вказати parameters count:

```php
// ❌ НЕПРАВИЛЬНО (default = 1 parameter)
add_filter('wp_sitemaps_add_provider', 'my_func', 10);

// ✅ ПРАВИЛЬНО (2 parameters)
add_filter('wp_sitemaps_add_provider', 'my_func', 10, 2);
```

### ❌ 2. Type mismatch у function signature:

```php
// ❌ НЕПРАВИЛЬНО - очікує array, отримує object
function my_func(array $providers): array {}
add_filter('wp_sitemaps_add_provider', 'my_func', 10, 2);

// ✅ ПРАВИЛЬНО - правильні типи
function my_func(WP_Sitemaps_Provider $provider, string $name) {}
add_filter('wp_sitemaps_add_provider', 'my_func', 10, 2);
```

### ❌ 3. Немає PHPDoc коментаря:

```php
// ❌ НЕПРАВИЛЬНО - немає документації
function my_func($provider, $name) {}

// ✅ ПРАВИЛЬНО - є PHPDoc
/**
 * Description
 * @param WP_Sitemaps_Provider $provider
 * @param string $name
 * @return WP_Sitemaps_Provider|false
 */
function my_func($provider, string $name) {}
```

---

## 📚 Швидкі посилання

- **WordPress Hooks:** https://developer.wordpress.org/reference/hooks/
- **PHPStorm Docs:** `Ctrl+Q` / `Cmd+J` на функції
- **Source Code:** https://github.com/WordPress/WordPress
- **Детальний гайд:** `docs/PREVENT-WORDPRESS-HOOK-ERRORS.md`

---

**Дата:** 2025-12-18
**Версія:** 1.0.0

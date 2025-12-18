# 🎨 MODERN JAVASCRIPT & CSS ARCHITECTURE

**⚠️ Примітка:** Поточна тема використовує vanilla JavaScript. Ці патерни — для майбутнього розвитку з Gutenberg блоками та сучасним frontend.

## 1. Modern ES6+ JavaScript для WordPress

**ES6 Modules замість глобальних змінних:**

```javascript
// src/utils/api.js
export const fetchPosts = async (perPage = 6) => {
	const response = await fetch(`/wp-json/wp/v2/posts?per_page=${perPage}`);
	if (!response.ok) throw new Error('Failed to fetch posts');
	return response.json();
};

// src/main.js
import { fetchPosts } from './utils/api.js';

document.addEventListener('DOMContentLoaded', async () => {
	const posts = await fetchPosts(10);
	console.log(posts);
});
```

**Destructuring та Spread Operator:**

```javascript
// Immutable state updates
const [posts, setPosts] = useState([]);
setPosts([...posts, newPost]); // ✅ Не мутує масив

// Object destructuring
const { title, content, author } = post;
```

**Інтеграція в тему:** Використати `@wordpress/scripts` для Webpack збірки, модульна структура `js/src/`.

## 2. Gutenberg Block Development

**block.json — стандарт WordPress 5.8+:**

```json
{
	"apiVersion": 3,
	"name": "medici/featured-post",
	"title": "Featured Post Card",
	"category": "medici-blocks",
	"attributes": {
		"postId": { "type": "number", "default": 0 },
		"showExcerpt": { "type": "boolean", "default": true }
	},
	"supports": {
		"align": ["wide", "full"],
		"color": { "background": true, "text": true }
	},
	"editorScript": "file:./index.js",
	"render": "file:./render.php"
}
```

**Dynamic Block з Server-Side Rendering:**

```php
// render.php
$post = get_post($attributes['postId']);
?>
<article <?php echo get_block_wrapper_attributes(); ?>>
  <h3><?php echo esc_html($post->post_title); ?></h3>
</article>
```

**React Hooks у Gutenberg:**

```javascript
import { useSelect } from '@wordpress/data';

const PostSelector = () => {
	const posts = useSelect((select) => select('core').getEntityRecords('postType', 'medici_blog'));

	return (
		<select>
			{posts?.map((p) => (
				<option>{p.title.rendered}</option>
			))}
		</select>
	);
};
```

**Інтеграція в тему:** Створити `blocks/` директорію, використати `register_block_type(__DIR__ . '/build/featured-post')`.

## 3. CSS Architecture — BEM + ITCSS

**ITCSS (Inverted Triangle CSS) — 7 шарів:**

```
01-settings/    # Змінні ($color-primary, $spacing)
02-tools/       # Mixins, functions (без CSS output)
03-generic/     # Reset, normalize
04-elements/    # HTML tags (body, h1, a)
05-objects/     # Layout patterns (.o-container, .o-grid)
06-components/  # UI компоненти (.c-card, .c-button) - BEM
07-utilities/   # Helpers (.u-mt-2, .u-hidden) з !important
```

**BEM Naming Convention:**

```css
.blog-card {
} /* Block */
.blog-card__title {
} /* Element */
.blog-card--featured {
} /* Modifier */

/* Приклад */
.blog-card {
	background: white;
	border-radius: 12px;

	&__image {
		width: 100%;
		aspect-ratio: 16/9;
	}

	&__title {
		font-size: 1.5rem;
	}

	&--featured {
		border: 3px solid var(--accent);
	}
}
```

**Інтеграція в тему:** Рефакторинг `css/` директорії з ITCSS структурою, BEM класи для компонентів.

## 4. Performance Optimization

**A. REST API Caching (Redis):**

```php
class REST_API_Cache
{
	private $redis;
	private $ttl = 300; // 5 хвилин

	public function get_cached_response($result, $server, $request)
	{
		if ($request->get_method() !== 'GET') {
			return $result;
		}

		$cache_key = 'rest_api:' . md5($request->get_route());
		$cached = $this->redis->get($cache_key);

		return $cached ? json_decode($cached) : $result;
	}
}
add_filter('rest_pre_dispatch', [new REST_API_Cache(), 'get_cached_response'], 10, 3);
```

**B. Core Web Vitals Optimization:**

```php
// LCP — Preload критичних ресурсів
add_action(
	'wp_head',
	function () {
		echo '<link rel="preload" as="image" href="hero.jpg" fetchpriority="high">';
	},
	1
);
```

```css
/* CLS — Fixed dimensions */
.hero-image {
	width: 100%;
	aspect-ratio: 16/9; /* Запобігає layout shift */
	object-fit: cover;
}
```

```javascript
// INP — Event delegation
document.querySelector('.cards-container').addEventListener('click', (e) => {
	const card = e.target.closest('.card');
	if (card) handleClick(card);
});
```

**C. Code Splitting та Lazy Loading:**

```javascript
// Динамічний імпорт
const loadComments = async () => {
	const { initComments } = await import('./comments.js');
	initComments();
};

// Intersection Observer
const observer = new IntersectionObserver((entries) => {
	if (entries[0].isIntersecting) {
		loadComments();
		observer.disconnect();
	}
});
observer.observe(document.querySelector('#comments-trigger'));
```

**Поточний стан теми:**

- ✅ Vanilla JS модулі (`js/scripts.js`, `js/events.js`)
- ✅ ITCSS-подібна структура (`css/core/`, `css/components/`, `css/layout/`)
- ⚠️ Потрібно: Gutenberg блоки, BEM naming, REST API caching, Web Workers

**Цільові метрики Core Web Vitals:**

- **LCP < 2.0s** — Largest Contentful Paint
- **INP < 100ms** — Interaction to Next Paint
- **CLS < 0.05** — Cumulative Layout Shift

---

**Last Updated:** 2025-12-18

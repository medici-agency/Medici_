# STYLE-RULES-EFFECTS.md - Visual Effects Guide

**Версія:** 5.2.0
**Дата:** 2025-12-02
**Частина:** Visual Effects & Animations (GenerateBlocks)
**Проєкт:** Medici Medical Marketing Theme
**Мова:** Українська

---

## ⚠️ ПОПЕРЕДЖЕННЯ ДЛЯ LLM

**Цей файл містить тільки візуальні ефекти та анімації GenerateBlocks.**

Якщо потрібні:

- **CSS класи (gbp-\*)** → Читай `STYLE-RULES-GENERATEBLOCKS.md`
- **CSS класи (medici-\*)** → Читай `STYLE-RULES-THEME.md`
- **CSS стандарти** → Читай `STYLE-RULES-CSS-STANDARDS.md`
- **Загальна інформація** → Читай `STYLE-RULES.md` (Master Index)

---

## 📋 ЗМІСТ

1. [Effects Panel: Повний арсенал візуальних інструментів](#effects-panel-повний-арсенал-візуальних-інструментів)
2. [Glassmorphism: Frosted Glass Effect](#glassmorphism-frosted-glass-effect)
3. [Card Lift Effect: Інтерактивні картки послуг](#card-lift-effect-інтерактивні-картки-послуг)
4. [Stacked Transitions: Різний timing для різних ефектів](#stacked-transitions-різний-timing-для-різних-ефектів)
5. [Targeting Inner Elements on Parent Hover](#targeting-inner-elements-on-parent-hover)
6. [Pseudo-Elements для декоративних форм](#pseudo-elements-для-декоративних-форм)
7. [Mix Blend Modes для креативних overlay](#mix-blend-modes-для-креативних-overlay)
8. [Filter Effects для зображень](#filter-effects-для-зображень)
9. [Scroll-Driven Animations: Без JavaScript](#scroll-driven-animations-без-javascript)
10. [GSAP Integration для складних анімацій](#gsap-integration-для-складних-анімацій)
11. [Animated Gradient Backgrounds](#animated-gradient-backgrounds)
12. [Overlay Panels: Popups, Mega Menu, Off-Canvas](#overlay-panels-popups-mega-menu-off-canvas)
13. [Neumorphism: Soft UI для premium brands](#neumorphism-soft-ui-для-premium-brands)
14. [Transform Effects для динамічності](#transform-effects-для-динамічності)
15. [Performance: Оптимізація анімацій](#performance-оптимізація-анімацій)
16. [Checklist візуальних ефектів для маркетингової агенції](#checklist-візуальних-ефектів-для-маркетингової-агенції)

---

## Effects Panel: Повний арсенал візуальних інструментів

GenerateBlocks 2.x має потужну **Effects Panel**, яка об'єднує всі візуальні ефекти в одному місці.

### Таблиця ефектів:

| Ефект               | CSS Property      | Використання                   |
| ------------------- | ----------------- | ------------------------------ |
| **Box Shadow**      | `box-shadow`      | Глибина, "lift" ефект карток   |
| **Text Shadow**     | `text-shadow`     | Акцентні заголовки             |
| **Transform**       | `transform`       | Scale, rotate, translate, skew |
| **Filter**          | `filter`          | Blur, brightness, grayscale    |
| **Backdrop Filter** | `backdrop-filter` | Glassmorphism                  |
| **Opacity**         | `opacity`         | Fade ефекти                    |
| **Mix Blend Mode**  | `mix-blend-mode`  | Креативне змішування           |
| **Transition**      | `transition`      | Плавні переходи                |

### Ключова особливість:

**Effects Panel підтримує repeater control** — ви можете додавати декілька шарів одного ефекту (наприклад, 5 різних `box-shadow` для реалістичної тіні).

**Доступ:** `Styles → Effects → Add Effect`

---

## Glassmorphism: Frosted Glass Effect

**Glassmorphism** — один з топ-трендів 2025 року для маркетингових сайтів, особливо для tech та digital агенцій.

### Реалізація в GenerateBlocks:

#### Крок 1: Напівпрозорий фон

```
Styles → Background → Color
rgba(255, 255, 255, 0.15)
```

#### Крок 2: Backdrop Filter

```
Styles → Effects → Add Effect → Backdrop Filter
blur(10px)
```

#### Крок 3: Subtle Border

```
Styles → Border
1px solid rgba(255, 255, 255, 0.2)
border-radius: 16px
```

#### Крок 4: Box Shadow для глибини

```
Styles → Effects → Box Shadow
0 8px 32px rgba(0, 0, 0, 0.1)
```

### Повний CSS еквівалент:

```css
.glass-card {
	background: rgba(255, 255, 255, 0.15);
	backdrop-filter: blur(10px);
	-webkit-backdrop-filter: blur(10px);
	border: 1px solid rgba(255, 255, 255, 0.2);
	border-radius: 16px;
	box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}
```

### Важливо для маркетингових сайтів:

Glassmorphism працює найкраще на **яскравих gradient backgrounds**. Встановіть gradient на батьківський container:

```
Background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
```

### Browser Support:

- ✅ Chrome, Edge, Safari (повна підтримка)
- ⚠️ Firefox (часткова підтримка `backdrop-filter`)
- 🔧 Fallback: додайте `background: rgba(255, 255, 255, 0.25)` для Firefox

---

## Card Lift Effect: Інтерактивні картки послуг

Для сторінки послуг агенції створіть **interactive cards** з **hover lift effect**.

### Структура картки:

```
Container Block
├─ Image Block (service icon/image)
├─ Headline Block (service title)
└─ Text Block (description)
```

### Крок 1: Hover Selector

1. Виберіть Container
2. `Styles → Selectors → More → Hover`
3. Активуйте **Compound Selector** (додає `&:hover`)

### Крок 2: Hover Effects

```
Effects → Box Shadow:
  offset-x: 0
  offset-y: 20px
  blur: 40px
  spread: 0
  color: rgba(0, 0, 0, 0.15)

Effects → Transform:
  translate: 0, -10px
```

### Крок 3: Transition на Main Selector

Поверніться до main selector і додайте:

```
Effects → Transition:
  property: all
  duration: 0.3s
  timing: ease-out
```

### Результат:

При наведенні картка **піднімається на 10px** і отримує **глибоку тінь**.

### CSS еквівалент:

```css
.service-card {
	transition: all 0.3s ease-out;
}

.service-card:hover {
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
	transform: translateY(-10px);
}
```

---

## Stacked Transitions: Різний timing для різних ефектів

Для більш **sophistacted анімацій** використовуйте різні transition timings.

### Налаштування:

```
Transition 1:
  property: box-shadow
  duration: 0.25s
  timing: ease-in-out

Transition 2:
  property: transform
  duration: 0.5s
  timing: ease-out
```

### Результат:

- Тінь з'являється **швидко (0.25s)**
- Картка піднімається **повільніше (0.5s)**

Це створює **layered, organic feel**.

### CSS еквівалент:

```css
.card {
	transition:
		box-shadow 0.25s ease-in-out,
		transform 0.5s ease-out;
}
```

### Рекомендовані timing combinations:

| Ефект      | Duration | Timing Function | Відчуття         |
| ---------- | -------- | --------------- | ---------------- |
| Box Shadow | 0.2-0.3s | ease-in-out     | Швидкий response |
| Transform  | 0.4-0.6s | ease-out        | Плавний рух      |
| Opacity    | 0.3-0.5s | ease-in-out     | Природний fade   |
| Color      | 0.2s     | linear          | Миттєва зміна    |

---

## Targeting Inner Elements on Parent Hover

Для **advanced card interactions** — зміна кольору тексту при hover на батьківському контейнері.

### Workflow:

#### Крок 1: Скопіюйте клас inner element

Наприклад: `.gb-element-abc123` (headline block)

#### Крок 2: Створіть custom selector на parent

1. Виберіть parent container
2. `Selectors → More → New`
3. Встановіть selector:

```
&:hover .gb-element-abc123
```

#### Крок 3: Змініть стилі

```
Typography → Color: #667eea (accent color)
```

### Результат:

При наведенні на картку **заголовок змінює колір**.

### CSS еквівалент:

```css
.service-card:hover .card-title {
	color: #667eea;
}
```

### Advanced: Targeting multiple elements

```css
/* У Custom Selector */
&:hover .card-title,
&:hover .card-icon {
	color: var(--primary-color);
	transform: scale(1.05);
}
```

---

## Pseudo-Elements для декоративних форм

Використовуйте `::before` та `::after` для створення декоративних елементів **без додаткової розмітки**.

### Приклад: Кольоровий блок за зображенням

#### Крок 1: Створіть структуру

```
Container (з Image всередині)
```

#### Крок 2: Додайте ::before selector

```
Selectors → More → New → &::before
```

#### Крок 3: Стилізуйте pseudo-element

```
content: ""
position: absolute
inset: 0
background: linear-gradient(135deg, #667eea, #764ba2)
transform: rotate(-5deg)
z-index: -1
```

#### Крок 4: Z-index на Image

```
На Image встановіть z-index: 5
```

### Результат:

Кольоровий фон виглядає як **"тінь" за зображенням під кутом**.

### Clip-path ефекти з CSS-змінними:

```css
/* У Customizer → Additional CSS */
.image-with-shape {
	--clip: polygon(0 0, 100% 0, 100% 85%, 0 100%);
}

.image-with-shape img,
.image-with-shape::before {
	clip-path: var(--clip);
}

/* Alternate shape */
.image-with-shape.alt {
	--clip: polygon(0 15%, 100% 0, 100% 100%, 0 100%);
}
```

### Popular clip-path shapes:

```css
/* Triangle */
clip-path: polygon(50% 0%, 0% 100%, 100% 100%);

/* Hexagon */
clip-path: polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%);

/* Arrow pointing right */
clip-path: polygon(0 0, 75% 0, 100% 50%, 75% 100%, 0 100%);

/* Notched corner */
clip-path: polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 0 100%);
```

---

## Mix Blend Modes для креативних overlay

**Mix Blend Mode** дозволяє змішувати елементи з фоном.

### Таблиця blend modes:

| Mode            | Ефект              | Використання           |
| --------------- | ------------------ | ---------------------- |
| **multiply**    | Темніший           | Затемнення зображень   |
| **screen**      | Світліший          | Освітлення, glow       |
| **overlay**     | Контрастний        | Драматичний ефект      |
| **color-dodge** | Яскравий highlight | Neon glow              |
| **difference**  | Інвертований       | Artistic, experimental |
| **lighten**     | Світліші пікселі   | Soft glow              |
| **darken**      | Темніші пікселі    | Vignette effect        |

### Приклад: Text over image з blend mode

```
Container (background-image: hero.jpg)
└─ Headline Block
     mix-blend-mode: difference
     color: white
```

**Результат:** Текст буде інвертуватися відносно зображення — темні області стануть білими, світлі — темними.

### Налаштування в GenerateBlocks:

```
Styles → Effects → Mix Blend Mode → difference
```

### CSS еквівалент:

```css
.hero-title {
	mix-blend-mode: difference;
	color: white;
	font-size: 4rem;
	font-weight: 700;
}
```

### Advanced: Colored overlay з blend mode

```css
.image-overlay::after {
	content: '';
	position: absolute;
	inset: 0;
	background: linear-gradient(135deg, #667eea, #764ba2);
	mix-blend-mode: multiply;
	opacity: 0.6;
}
```

---

## Filter Effects для зображень

**Filter property** застосовує ефекти безпосередньо до елемента.

### Доступ:

```
Image Block → Effects → Filter
```

### Таблиця filters:

| Filter         | Опис          | Значення          | Ефект                      |
| -------------- | ------------- | ----------------- | -------------------------- |
| **blur**       | М'який фокус  | `2px` - `10px`    | Розмиття                   |
| **brightness** | Яскравість    | `0` - `2`         | Темніше/світліше           |
| **contrast**   | Контраст      | `0` - `2`         | Більше/менше контрасту     |
| **grayscale**  | Чорно-біле    | `0` - `1`         | Відсутність кольору        |
| **saturate**   | Насиченість   | `0` - `2`         | Тьмяніші/яскравіші кольори |
| **sepia**      | Vintage look  | `0` - `1`         | Теплий тон                 |
| **hue-rotate** | Зміна кольору | `0deg` - `360deg` | Інші кольори               |
| **invert**     | Інверсія      | `0` - `1`         | Негатив                    |

### Приклад комбінації:

```
filter: brightness(1.1) contrast(1.05) saturate(1.2)
```

**Результат:** Зображення стає яскравішим, контрастнішим та насиченішим.

### Hover grayscale-to-color effect:

#### Main selector:

```
filter: grayscale(1)
```

#### Hover selector:

```
filter: grayscale(0)
```

#### Transition:

```
transition: filter 0.5s ease
```

### CSS еквівалент:

```css
.portfolio-image {
	filter: grayscale(1);
	transition: filter 0.5s ease;
}

.portfolio-image:hover {
	filter: grayscale(0);
}
```

### Advanced: Image enhancement filter

```css
.enhanced-image {
	filter: brightness(1.05) contrast(1.1) saturate(1.15) blur(0.3px);
}
```

Легке розмиття (0.3px) **покращує якість зображення** на екранах з низькою щільністю пікселів.

---

## Scroll-Driven Animations: Без JavaScript

**CSS Scroll-Driven Animations** — новий стандарт для анімацій при прокрутці.

### Базова реалізація:

```css
/* У Customizer → Additional CSS */

@keyframes fadeSlideIn {
	from {
		opacity: 0;
		transform: translateY(50px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.animate-on-scroll {
	animation: fadeSlideIn forwards;
	animation-timeline: view();
	animation-range: cover 20% cover 50%;
}
```

### Застосування в GenerateBlocks:

Додайте клас `animate-on-scroll` через **HTML Attributes**:

```
Advanced → HTML Attributes → className: animate-on-scroll
```

Елемент **анімуватиметься**, коли входить у viewport.

### Browser Support:

- ✅ Chrome 115+, Edge 115+ (повна підтримка)
- ⚠️ Firefox, Safari (потребують polyfill)

### Polyfill:

```html
<!-- У Hook Element (Header Scripts) -->
<script src="https://flackr.github.io/scroll-timeline/dist/scroll-timeline.js"></script>
```

### Advanced: Scroll-linked progress bar

```css
@keyframes grow {
	from {
		transform: scaleX(0);
	}
	to {
		transform: scaleX(1);
	}
}

.progress-bar {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 4px;
	background: var(--primary-color);
	transform-origin: left;
	animation: grow linear;
	animation-timeline: scroll(root);
}
```

---

## GSAP Integration для складних анімацій

Для **enterprise-level анімацій** інтегруйте **GSAP ScrollTrigger**.

### Крок 1: Enqueue GSAP

```php
// В Hook Element або functions.php
add_action('wp_enqueue_scripts', function () {
	wp_enqueue_script(
		'gsap',
		'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
		[],
		'3.12.2',
		true
	);
	wp_enqueue_script(
		'gsap-scrolltrigger',
		'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
		['gsap'],
		'3.12.2',
		true
	);
});
```

### Крок 2: Animation Script

```javascript
// У Hook Element (Footer Scripts)
gsap.registerPlugin(ScrollTrigger);

gsap.utils.toArray('.service-card').forEach((card, i) => {
	gsap.from(card, {
		scrollTrigger: {
			trigger: card,
			start: 'top 80%',
			end: 'bottom 20%',
			toggleActions: 'play none none reverse',
		},
		y: 100,
		opacity: 0,
		duration: 0.8,
		delay: i * 0.1, // Staggered effect
	});
});
```

### Результат:

Картки послуг **з'являються послідовно** при прокрутці.

### Advanced GSAP: Parallax effect

```javascript
gsap.to('.parallax-bg', {
	scrollTrigger: {
		trigger: '.parallax-section',
		start: 'top bottom',
		end: 'bottom top',
		scrub: true,
	},
	y: -200,
	ease: 'none',
});
```

### GSAP Timeline для sequential animations

```javascript
const tl = gsap.timeline({
	scrollTrigger: {
		trigger: '.hero-section',
		start: 'top center',
		end: 'bottom center',
		scrub: 1,
	},
});

tl.from('.hero-title', { opacity: 0, y: 50 })
	.from('.hero-subtitle', { opacity: 0, y: 30 }, '-=0.3')
	.from('.hero-button', { opacity: 0, scale: 0.8 }, '-=0.2');
```

---

## Animated Gradient Backgrounds

**Gradient morphing** — популярний ефект для hero секцій.

### Реалізація:

```css
/* У Customizer → Additional CSS */

@keyframes gradientShift {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}

.animated-gradient {
	background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
	background-size: 400% 400%;
	animation: gradientShift 15s ease infinite;
}
```

### Застосування:

Додайте клас `animated-gradient` до hero container через **HTML Attributes**.

### Advanced: Multi-layer gradient animation

```css
.hero-gradient {
	position: relative;
	overflow: hidden;
}

.hero-gradient::before,
.hero-gradient::after {
	content: '';
	position: absolute;
	inset: 0;
	background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
	background-size: 400% 400%;
	animation: gradientShift 20s ease infinite;
	opacity: 0.7;
}

.hero-gradient::after {
	animation-duration: 30s;
	animation-direction: reverse;
	opacity: 0.5;
}
```

### Performance tip:

Для кращої performance використовуйте `will-change`:

```css
.animated-gradient {
	will-change: background-position;
}
```

---

## Overlay Panels: Popups, Mega Menu, Off-Canvas

GenerateBlocks Pro 2.3+ має вбудовану систему **Overlay Panels**.

### Типи overlay:

| Тип            | Опис                    | Використання         |
| -------------- | ----------------------- | -------------------- |
| **Modal**      | Центрований popup       | Lead capture, відео  |
| **Mega Menu**  | Dropdown під navigation | Складне меню         |
| **Off-Canvas** | Бічна панель            | Mobile menu, фільтри |
| **Tooltip**    | Floating content        | Підказки             |

### Triggers:

- **Click** / **Hover** / **Click or Hover**
- **Exit Intent** (при спробі закрити сторінку)
- **Percentage Scrolled** (наприклад, 50%)
- **Time Delay** (наприклад, 5 секунд)
- **Custom Events** (наприклад, `wc-blocks_added_to_cart`)

### Animations:

- **Fade In** (з будь-якого напрямку)
- **Slide In** (top, right, bottom, left)
- **Scale In** (grow effect)

### Приклад: Exit Intent Popup для lead capture

#### Крок 1: Створіть Block Element

```
Appearance → Elements → Add New → Block Element
Element Type: Content
```

#### Крок 2: Додайте Overlay Panel block

Всередину Element додайте **Overlay Panel block**.

#### Крок 3: Settings

```
Trigger: Exit Intent
Animation: Scale In
Duration: 300ms
```

#### Крок 4: Контент панелі

Всередину panel додайте:

- Headline ("Не пропустіть!")
- Text (пропозиція)
- Form (email subscription)

### CSS для custom backdrop:

```css
.gb-overlay-backdrop {
	background: rgba(0, 0, 0, 0.8);
	backdrop-filter: blur(5px);
}
```

### Advanced: Mega Menu з hover trigger

```
Container → Settings → Overlay Panel
Trigger: Hover
Target: #mega-menu-1
Animation: Slide In (from top)
```

---

## Neumorphism: Soft UI для premium brands

**Neumorphism** використовує **dual shadows** для "м'якого 3D" ефекту.

### CSS Implementation:

```css
.neumorphic-card {
	background: #e0e5ec;
	border-radius: 20px;
	box-shadow:
		9px 9px 16px rgba(163, 177, 198, 0.6),
		-9px -9px 16px rgba(255, 255, 255, 0.5);
}

/* Pressed state */
.neumorphic-button:active {
	box-shadow:
		inset 9px 9px 16px rgba(163, 177, 198, 0.6),
		inset -9px -9px 16px rgba(255, 255, 255, 0.5);
}
```

### У GenerateBlocks:

#### Крок 1: Background

```
Background: #e0e5ec
Border Radius: 20px
```

#### Крок 2: First Shadow (темна)

```
Effects → Box Shadow → Add:
  offset-x: 9px
  offset-y: 9px
  blur: 16px
  color: rgba(163, 177, 198, 0.6)
```

#### Крок 3: Second Shadow (світла)

```
Add another shadow:
  offset-x: -9px
  offset-y: -9px
  blur: 16px
  color: rgba(255, 255, 255, 0.5)
```

### Застереження:

⚠️ **Neumorphism має низький контраст** — уникайте для accessibility-critical елементів.

### Color палітра для neumorphism:

```css
:root {
	/* Light theme */
	--neuro-bg: #e0e5ec;
	--neuro-shadow-dark: rgba(163, 177, 198, 0.6);
	--neuro-shadow-light: rgba(255, 255, 255, 0.5);

	/* Dark theme */
	--neuro-bg-dark: #2d3142;
	--neuro-shadow-dark-dark: rgba(0, 0, 0, 0.4);
	--neuro-shadow-light-dark: rgba(255, 255, 255, 0.05);
}
```

---

## Transform Effects для динамічності

**Transform property** у GenerateBlocks підтримує всі CSS transform functions.

### Таблиця transform functions:

| Function        | Опис                 | Приклад               | Ефект         |
| --------------- | -------------------- | --------------------- | ------------- |
| **scale**       | Збільшення/зменшення | `scale(1.1)`          | 110% розміру  |
| **rotate**      | Обертання            | `rotate(5deg)`        | Нахил на 5°   |
| **skew**        | Нахил                | `skew(5deg, 0)`       | Perspective   |
| **translate**   | Переміщення          | `translate(0, -10px)` | Вгору на 10px |
| **perspective** | 3D глибина           | `perspective(1000px)` | 3D контекст   |

### Transform Origin:

```
transform-origin: center       // default
transform-origin: left top     // верхній лівий кут
transform-origin: 100% 0       // верхній правий кут
transform-origin: 50% 100%     // нижній центр
```

### Приклад: Image zoom on hover

#### Container:

```
overflow: clip
```

#### Image (main selector):

```
transform: scale(1)
transition: transform 0.5s ease
```

#### Image (hover selector):

```
&:hover img {
	transform: scale(1.1)
}
```

### CSS еквівалент:

```css
.image-container {
	overflow: clip;
}

.image-container img {
	transform: scale(1);
	transition: transform 0.5s ease;
}

.image-container:hover img {
	transform: scale(1.1);
}
```

### Advanced: 3D card flip

```css
.flip-card {
	perspective: 1000px;
}

.flip-card-inner {
	transition: transform 0.6s;
	transform-style: preserve-3d;
}

.flip-card:hover .flip-card-inner {
	transform: rotateY(180deg);
}

.flip-card-front,
.flip-card-back {
	backface-visibility: hidden;
}

.flip-card-back {
	transform: rotateY(180deg);
}
```

---

## Performance: Оптимізація анімацій

### Правила для маркетингових сайтів:

#### 1. Використовуйте CSS-based анімації

✅ **Hardware-accelerated** — використовує GPU.

#### 2. Анімуйте тільки `transform` і `opacity`

✅ **Найшвидші properties** — не викликають reflow/repaint.

❌ Уникайте: `width`, `height`, `top`, `left`, `margin`, `padding`.

#### 3. Prefer `will-change` для складних анімацій

```css
.animated-element {
	will-change: transform, opacity;
}
```

⚠️ **Не зловживайте** — `will-change` споживає пам'ять.

#### 4. Reduce Motion для accessibility

```css
@media (prefers-reduced-motion: reduce) {
	* {
		animation: none !important;
		transition: none !important;
	}
}
```

### Performance checklist:

- [ ] Використовується `transform` замість `top`/`left`
- [ ] Використовується `opacity` замість `visibility`
- [ ] Додано `will-change` для складних анімацій
- [ ] Додано `@media (prefers-reduced-motion)`
- [ ] Анімації тривають < 0.6s
- [ ] Box-shadow не анімується (або use `filter: drop-shadow()`)
- [ ] Gradient animations використовують `background-position`

### DevTools Performance profiling:

1. **Chrome DevTools** → Performance tab
2. **Start Recording** → Виконайте анімацію → **Stop**
3. Шукайте червоні смуги (**Long Tasks**)
4. Аналізуйте **FPS** (має бути 60fps)

### Оптимізація box-shadow animations:

❌ **Повільно:**

```css
.card {
	transition: box-shadow 0.3s;
}
.card:hover {
	box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}
```

✅ **Швидко:**

```css
.card {
	filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.1));
	transition: filter 0.3s;
}
.card:hover {
	filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.2));
}
```

`filter: drop-shadow()` — hardware-accelerated.

---

## Checklist візуальних ефектів для маркетингової агенції

### Секція Hero:

- [ ] **Animated gradient background** (15-20s duration)
- [ ] **Glassmorphism cards** для featured services
- [ ] **Smooth scroll anchor** для CTA button
- [ ] **Text shadow** для accent headline
- [ ] **Parallax background** (якщо використовується GSAP)

### Секція Services:

- [ ] **Card lift effect** на hover (translateY + box-shadow)
- [ ] **Staggered scroll animations** (GSAP або CSS scroll-driven)
- [ ] **Icon scale** на hover (transform: scale(1.1))
- [ ] **Color transition** для заголовків (0.3s ease)

### Секція Portfolio:

- [ ] **Image zoom** на hover (overflow: clip + scale(1.1))
- [ ] **Filter hover** (grayscale → color)
- [ ] **Overlay з blend mode** (multiply або overlay)
- [ ] **Caption slide-in** animation

### Секція Testimonials:

- [ ] **Soft shadows** (neumorphism або subtle box-shadow)
- [ ] **Subtle scale** на hover (1.02-1.05)
- [ ] **Avatar border** animation (border-color transition)
- [ ] **Quote icon** з opacity animation

### Секція CTA:

- [ ] **Gradient button** з hover effect
- [ ] **Pulse animation** для primary button
- [ ] **Arrow icon** з translate animation
- [ ] **Background overlay** з backdrop-filter

### Footer:

- [ ] **Backdrop blur** для glassmorphism effect
- [ ] **Subtle parallax** (якщо є background)
- [ ] **Link hover** effects (color + padding-left shift)
- [ ] **Social icons** з scale/rotate hover

### Global:

- [ ] **Smooth scroll** behavior (`scroll-behavior: smooth`)
- [ ] **Reduce motion** media query
- [ ] **Loading animations** для async content
- [ ] **Exit intent popup** для lead capture

---

## 📝 Зв'язок з іншими файлами

Цей гайд доповнює:

- **STYLE-RULES-GENERATEBLOCKS.md** - класи для структури
- **STYLE-RULES-CSS-STANDARDS.md** - правила написання CSS
- **CODING-RULES.md** - загальні правила кодування
- **Skill.md** - документація GenerateBlocks 2.x

**ВАЖЛИВО:** Завжди використовуйте візуальні ефекти разом з правилами performance optimization!

---

**Версія:** 5.2.0 (Візуальні ефекти GenerateBlocks)
**Останнє оновлення:** 2025-12-02
**Автор:** Medici Medical Marketing Agency

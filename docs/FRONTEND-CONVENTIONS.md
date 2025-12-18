# Frontend Conventions — Medici Agency

> **Мета:** Консистентність CSS/JS коду та чітке розділення styling vs behavior.
> **Версія:** 1.0.0

---

## 🎯 BEM Naming Convention

### Формат

```
.block {}
.block__element {}
.block--modifier {}
.block__element--modifier {}
```

### Правила

| Правило                 | Приклад ✅        | Антипатерн ❌                      |
| ----------------------- | ----------------- | ---------------------------------- |
| Block = компонент       | `.card`           | `.cardComponent`                   |
| Element = частина block | `.card__title`    | `.card-title`, `.cardTitle`        |
| Modifier = варіант      | `.card--featured` | `.card.featured`, `.card-featured` |
| Lowercase + hyphens     | `.blog-card`      | `.blogCard`, `.BlogCard`           |
| Max 2 рівні вкладеності | `.card__header`   | `.card__header__title__text`       |

### Приклади для Medici

```css
/* ✅ ПРАВИЛЬНО: BEM */
.lead-form {
}
.lead-form__input {
}
.lead-form__input--error {
}
.lead-form__submit {
}
.lead-form__submit--loading {
}
.lead-form--compact {
}

/* ❌ НЕПРАВИЛЬНО: Хаотичний naming */
.leadForm {
}
.lead-form-input {
}
.lead-form .input.error {
}
.submitBtn {
}
```

### Компоненти Medici (стандартизація)

```css
/* Navigation */
.nav-header {
}
.nav-header__logo {
}
.nav-header__menu {
}
.nav-header__item {
}
.nav-header__item--active {
}
.nav-header--sticky {
}

/* Cards */
.service-card {
}
.service-card__icon {
}
.service-card__title {
}
.service-card__description {
}
.service-card--featured {
}

/* Blog */
.blog-card {
}
.blog-card__image {
}
.blog-card__meta {
}
.blog-card__title {
}
.blog-card__excerpt {
}
.blog-card--horizontal {
}

/* Lead Form */
.lead-form {
}
.lead-form__field {
}
.lead-form__label {
}
.lead-form__input {
}
.lead-form__input--error {
}
.lead-form__error-message {
}
.lead-form__submit {
}
.lead-form__submit--loading {
}
.lead-form--inline {
}

/* Hero */
.hero-section {
}
.hero-section__content {
}
.hero-section__title {
}
.hero-section__subtitle {
}
.hero-section__cta {
}
.hero-section--fullscreen {
}

/* Footer */
.site-footer {
}
.site-footer__column {
}
.site-footer__nav {
}
.site-footer__social {
}
.site-footer__copyright {
}
```

---

## 🔧 JavaScript Hooks (`js-*` класи)

### Проблема

```html
<!-- ❌ ПОГАНО: Один клас для styling І behavior -->
<button class="submit-btn">Submit</button>

<style>
	.submit-btn {
		background: blue;
	}
</style>

<script>
	document.querySelector('.submit-btn').addEventListener('click', ...);
	// Якщо дизайнер змінить клас → JS зламається!
</script>
```

### Рішення: Розділення concerns

```html
<!-- ✅ ДОБРЕ: Окремі класи для styling та JS -->
<button class="lead-form__submit js-form-submit">Submit</button>

<style>
	.lead-form__submit {
		background: blue;
	} /* Тільки styling */
</style>

<script>
	document.querySelector('.js-form-submit').addEventListener('click', ...);
	// Зміна BEM класу не зламає JS!
</script>
```

### Правила `js-*` класів

| Правило             | Опис                                    |
| ------------------- | --------------------------------------- |
| Prefix `js-`        | Всі JS hooks починаються з `js-`        |
| Без styling         | `js-*` класи НІКОЛИ не мають CSS правил |
| Descriptive         | Описують behavior, не appearance        |
| Lowercase + hyphens | `js-toggle-menu`, не `jsToggleMenu`     |

### Стандартні hooks для Medici

```html
<!-- Forms -->
<form class="lead-form js-lead-form">
  <input class="lead-form__input js-form-input" data-validate="email">
  <button class="lead-form__submit js-form-submit">
  <div class="lead-form__error js-form-error"></div>
</form>

<!-- Navigation -->
<nav class="nav-header js-nav">
  <button class="nav-header__toggle js-nav-toggle">Menu</button>
  <ul class="nav-header__menu js-nav-menu">
</nav>

<!-- Modals -->
<button class="cta-button js-modal-trigger" data-modal="consultation">
<div class="modal js-modal" data-modal-id="consultation">
  <button class="modal__close js-modal-close">
</div>

<!-- Accordions/FAQ -->
<div class="faq-item js-accordion-item">
  <button class="faq-item__question js-accordion-trigger">
  <div class="faq-item__answer js-accordion-content">
</div>

<!-- Tabs -->
<div class="tabs js-tabs">
  <button class="tabs__tab js-tab-trigger" data-tab="services">
  <div class="tabs__panel js-tab-panel" data-tab-id="services">
</div>

<!-- Sliders/Carousels -->
<div class="testimonials js-slider">
  <button class="testimonials__prev js-slider-prev">
  <button class="testimonials__next js-slider-next">
</div>

<!-- Analytics tracking -->
<a class="service-card js-track-click" data-track-category="services" data-track-action="click">
<button class="cta-button js-track-cta" data-track-cta="hero-consultation">

<!-- Lazy loading -->
<img class="blog-card__image js-lazy-load" data-src="image.jpg">

<!-- Scroll effects -->
<section class="hero-section js-parallax" data-parallax-speed="0.5">
<div class="stats-counter js-count-up" data-count-to="500">
```

### Data attributes для конфігурації

```html
<!-- ✅ Використовуй data-* для параметрів -->
<div class="js-slider" data-slides-per-view="3" data-autoplay="true" data-autoplay-delay="5000">
	<input
		class="js-form-input"
		data-validate="phone"
		data-validate-message="Невірний формат телефону"
	/>

	<button class="js-modal-trigger" data-modal="consultation" data-modal-size="large"></button>
</div>
```

---

## 📋 Checklist для Code Review

### CSS

- [ ] Всі класи відповідають BEM конвенції
- [ ] Немає camelCase або PascalCase
- [ ] Немає глибокої вкладеності (max 2 рівні)
- [ ] Модифікатори використовують `--`
- [ ] Елементи використовують `__`

### JavaScript

- [ ] Всі DOM selectors використовують `js-*` класи
- [ ] `js-*` класи НЕ мають CSS правил
- [ ] Конфігурація через `data-*` атрибути
- [ ] Event listeners прив'язані до `js-*`, не BEM класів

### HTML

- [ ] Кожен інтерактивний елемент має `js-*` клас
- [ ] BEM клас для styling
- [ ] `js-*` клас для behavior
- [ ] `data-*` для конфігурації

---

## 🔄 Міграція існуючого коду

### Пріоритет 1: Форми (High Impact)

```diff
- <form class="consultation-form">
-   <input class="form-input email-input">
-   <button class="submit-button">
+ <form class="lead-form js-lead-form">
+   <input class="lead-form__input js-form-input" data-validate="email">
+   <button class="lead-form__submit js-form-submit">
```

### Пріоритет 2: Navigation

```diff
- <nav class="main-nav">
-   <button class="menu-toggle">
+ <nav class="nav-header js-nav">
+   <button class="nav-header__toggle js-nav-toggle">
```

### Пріоритет 3: Cards

```diff
- <div class="service-card featured">
-   <h3 class="card-title">
+ <div class="service-card service-card--featured">
+   <h3 class="service-card__title">
```

---

## 🚫 Заборонені практики

```css
/* ❌ ЗАБОРОНЕНО */

/* 1. Styling на js-* класах */
.js-form-submit { background: blue; }

/* 2. ID selectors для styling */
#submit-button { ... }

/* 3. !important (окрім utilities) */
.card { margin: 0 !important; }

/* 4. Inline styles в HTML */
<div style="margin-top: 20px;">

/* 5. Глибока вкладеність */
.nav .menu .item .link .icon { }

/* 6. Неконсистентний naming */
.cardTitle { }      /* camelCase */
.Card-Title { }     /* PascalCase + hyphen */
.card_title { }     /* snake_case */
```

---

## 📊 Метрики якості

| Метрика                 | Ціль  | Alert   |
| ----------------------- | ----- | ------- |
| % BEM-compliant класів  | > 90% | < 80%   |
| % JS selectors з `js-*` | 100%  | < 100%  |
| CSS specificity max     | 0,2,0 | > 0,3,0 |
| Глибина вкладеності     | ≤ 3   | > 4     |

---

**Документ підтримується:** Frontend Team
**Останнє оновлення:** 2025-12-15

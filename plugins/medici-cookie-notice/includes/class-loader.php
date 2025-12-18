<?php
/**
 * Клас Loader для централізованого управління WordPress hooks
 *
 * Адаптовано з WordPress Plugin Boilerplate (DevinVinson)
 * @link https://github.com/DevinVinson/WordPress-Plugin-Boilerplate
 *
 * @package Medici_Cookie_Notice
 * @since 1.1.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас Loader
 *
 * Реєструє всі actions та filters для плагіну.
 * Централізований підхід забезпечує:
 * - Кращу організацію коду
 * - Легше відстеження залежностей
 * - Простіше тестування
 */
class Loader {

	/**
	 * Масив actions для реєстрації в WordPress
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $actions = [];

	/**
	 * Масив filters для реєстрації в WordPress
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $filters = [];

	/**
	 * Масив shortcodes для реєстрації
	 *
	 * @var array<int, array{tag: string, component: object, callback: string}>
	 */
	protected array $shortcodes = [];

	/**
	 * Додати новий action до колекції
	 *
	 * @param string $hook         WordPress hook name
	 * @param object $component    Об'єкт з callback методом
	 * @param string $callback     Назва методу для виклику
	 * @param int    $priority     Пріоритет виконання (default: 10)
	 * @param int    $accepted_args Кількість аргументів (default: 1)
	 * @return self
	 */
	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
		return $this;
	}

	/**
	 * Додати новий filter до колекції
	 *
	 * @param string $hook         WordPress hook name
	 * @param object $component    Об'єкт з callback методом
	 * @param string $callback     Назва методу для виклику
	 * @param int    $priority     Пріоритет виконання (default: 10)
	 * @param int    $accepted_args Кількість аргументів (default: 1)
	 * @return self
	 */
	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): self {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
		return $this;
	}

	/**
	 * Додати новий shortcode до колекції
	 *
	 * @param string $tag       Shortcode tag
	 * @param object $component Об'єкт з callback методом
	 * @param string $callback  Назва методу для виклику
	 * @return self
	 */
	public function add_shortcode( string $tag, object $component, string $callback ): self {
		$this->shortcodes[] = [
			'tag'       => $tag,
			'component' => $component,
			'callback'  => $callback,
		];
		return $this;
	}

	/**
	 * Додати hook до відповідної колекції
	 *
	 * @param array<int, array<string, mixed>> $hooks     Колекція hooks
	 * @param string                           $hook      WordPress hook name
	 * @param object                           $component Об'єкт з callback
	 * @param string                           $callback  Назва методу
	 * @param int                              $priority  Пріоритет
	 * @param int                              $accepted_args Кількість аргументів
	 * @return array<int, array<string, mixed>>
	 */
	private function add( array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args ): array {
		$hooks[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
		return $hooks;
	}

	/**
	 * Виконати реєстрацію всіх зібраних hooks в WordPress
	 *
	 * Цей метод викликається після того, як всі компоненти
	 * додали свої hooks через add_action/add_filter.
	 *
	 * @return void
	 */
	public function run(): void {
		// Реєстрація filters
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				[ $hook['component'], $hook['callback'] ],
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Реєстрація actions
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				[ $hook['component'], $hook['callback'] ],
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Реєстрація shortcodes
		foreach ( $this->shortcodes as $shortcode ) {
			add_shortcode(
				$shortcode['tag'],
				[ $shortcode['component'], $shortcode['callback'] ]
			);
		}
	}

	/**
	 * Отримати всі зареєстровані actions
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_actions(): array {
		return $this->actions;
	}

	/**
	 * Отримати всі зареєстровані filters
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_filters(): array {
		return $this->filters;
	}

	/**
	 * Отримати всі зареєстровані shortcodes
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_shortcodes(): array {
		return $this->shortcodes;
	}

	/**
	 * Очистити всі зареєстровані hooks (для тестування)
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->actions    = [];
		$this->filters    = [];
		$this->shortcodes = [];
	}
}

<?php
/**
 * Hook Loader Class
 *
 * Manages registration of actions and filters.
 * Based on WP Mail SMTP architecture.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Loader Class
 *
 * Maintains arrays of hooks to be registered with WordPress.
 */
final class Loader {

	/**
	 * Actions to register.
	 *
	 * @var array<int, array{hook: string, callback: callable, priority: int, args: int}>
	 */
	private array $actions = array();

	/**
	 * Filters to register.
	 *
	 * @var array<int, array{hook: string, callback: callable, priority: int, args: int}>
	 */
	private array $filters = array();

	/**
	 * Add action to queue.
	 *
	 * @since 1.0.0
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted arguments.
	 * @return self
	 */
	public function add_action( string $hook, callable $callback, int $priority = 10, int $args = 1 ): self {
		$this->actions[] = array(
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
			'args'     => $args,
		);

		return $this;
	}

	/**
	 * Add filter to queue.
	 *
	 * @since 1.0.0
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @param int      $priority Priority.
	 * @param int      $args     Accepted arguments.
	 * @return self
	 */
	public function add_filter( string $hook, callable $callback, int $priority = 10, int $args = 1 ): self {
		$this->filters[] = array(
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
			'args'     => $args,
		);

		return $this;
	}

	/**
	 * Register all hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run(): void {
		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				$action['callback'],
				$action['priority'],
				$action['args']
			);
		}

		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				$filter['callback'],
				$filter['priority'],
				$filter['args']
			);
		}
	}

	/**
	 * Remove action from queue.
	 *
	 * @since 1.0.0
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback function.
	 * @return bool
	 */
	public function remove_action( string $hook, callable $callback ): bool {
		foreach ( $this->actions as $key => $action ) {
			if ( $action['hook'] === $hook && $action['callback'] === $callback ) {
				unset( $this->actions[ $key ] );
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all registered actions.
	 *
	 * @since 1.0.0
	 * @return array<int, array{hook: string, callback: callable, priority: int, args: int}>
	 */
	public function get_actions(): array {
		return $this->actions;
	}

	/**
	 * Get all registered filters.
	 *
	 * @since 1.0.0
	 * @return array<int, array{hook: string, callback: callable, priority: int, args: int}>
	 */
	public function get_filters(): array {
		return $this->filters;
	}
}

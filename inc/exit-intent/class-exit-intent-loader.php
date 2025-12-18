<?php
declare(strict_types=1);

/**
 * Exit-Intent Loader Class
 *
 * Register all actions and filters for the exit-intent popup
 * Based on WordPress Plugin Boilerplate Loader pattern
 *
 * @package    Medici
 * @subpackage Exit_Intent
 * @version    1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit_Intent_Loader Class
 *
 * Maintains two arrays of hooks:
 * - $actions: Stores all actions to be registered
 * - $filters: Stores all filters to be registered
 */
class Exit_Intent_Loader {

	/**
	 * Array of actions to be registered
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $actions = array();

	/**
	 * Array of filters to be registered
	 *
	 * @var array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $filters = array();

	/**
	 * Add a new action to the collection
	 *
	 * @param string $hook          The name of the WordPress action being registered
	 * @param object $component     A reference to the instance of the object on which the action is defined
	 * @param string $callback      The name of the function definition on the $component
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default 10
	 * @param int    $accepted_args Optional. The number of arguments that should be passed. Default 1
	 */
	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection
	 *
	 * @param string $hook          The name of the WordPress filter being registered
	 * @param object $component     A reference to the instance of the object on which the filter is defined
	 * @param string $callback      The name of the function definition on the $component
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default 10
	 * @param int    $accepted_args Optional. The number of arguments that should be passed. Default 1
	 */
	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add hook to collection
	 *
	 * @param array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}> $hooks
	 * @param string                                                                                                  $hook
	 * @param object                                                                                                  $component
	 * @param string                                                                                                  $callback
	 * @param int                                                                                                     $priority
	 * @param int                                                                                                     $accepted_args
	 * @return array<int, array{hook: string, component: object, callback: string, priority: int, accepted_args: int}>
	 */
	private function add( array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args ): array {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all hooks with WordPress
	 */
	public function run(): void {
		// Register filters
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Register actions
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}

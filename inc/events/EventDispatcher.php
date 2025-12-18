<?php
/**
 * Event Dispatcher
 *
 * Central event bus for dispatching events to registered observers.
 *
 * @package    Medici_Agency
 * @subpackage Events
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Events;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Dispatcher Class
 *
 * Manages event registration and dispatching.
 *
 * @since 2.0.0
 */
final class EventDispatcher {

	/**
	 * Registered observers by event name
	 *
	 * @var array<string, array<ObserverInterface>>
	 */
	private array $observers = array();

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register an observer
	 *
	 * @since 2.0.0
	 * @param ObserverInterface $observer Observer instance.
	 * @return self For method chaining.
	 */
	public function subscribe( ObserverInterface $observer ): self {
		foreach ( $observer->getSubscribedEvents() as $event_name ) {
			if ( ! isset( $this->observers[ $event_name ] ) ) {
				$this->observers[ $event_name ] = array();
			}

			$this->observers[ $event_name ][] = $observer;

			// Sort by priority (lower = first).
			usort(
				$this->observers[ $event_name ],
				fn( ObserverInterface $a, ObserverInterface $b ) => $a->getPriority() <=> $b->getPriority()
			);
		}

		return $this;
	}

	/**
	 * Unregister an observer
	 *
	 * @since 2.0.0
	 * @param ObserverInterface $observer Observer instance.
	 * @return self For method chaining.
	 */
	public function unsubscribe( ObserverInterface $observer ): self {
		foreach ( $observer->getSubscribedEvents() as $event_name ) {
			if ( isset( $this->observers[ $event_name ] ) ) {
				$this->observers[ $event_name ] = array_filter(
					$this->observers[ $event_name ],
					fn( ObserverInterface $o ) => $o !== $observer
				);
			}
		}

		return $this;
	}

	/**
	 * Dispatch an event
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event to dispatch.
	 * @return EventInterface The event (may be modified by observers).
	 */
	public function dispatch( EventInterface $event ): EventInterface {
		$event_name = $event->getName();

		if ( ! isset( $this->observers[ $event_name ] ) ) {
			return $event;
		}

		foreach ( $this->observers[ $event_name ] as $observer ) {
			if ( $event->isPropagationStopped() ) {
				break;
			}

			$observer->handle( $event );
		}

		// Fire WordPress action for extensibility.
		do_action( 'medici_event_dispatched', $event );
		do_action( "medici_event_{$event_name}", $event );

		return $event;
	}

	/**
	 * Get all observers for an event
	 *
	 * @since 2.0.0
	 * @param string $event_name Event name.
	 * @return array<ObserverInterface> Observers.
	 */
	public function getObservers( string $event_name ): array {
		return $this->observers[ $event_name ] ?? array();
	}

	/**
	 * Check if event has observers
	 *
	 * @since 2.0.0
	 * @param string $event_name Event name.
	 * @return bool True if has observers.
	 */
	public function hasObservers( string $event_name ): bool {
		return ! empty( $this->observers[ $event_name ] );
	}

	/**
	 * Clear all observers
	 *
	 * @since 2.0.0
	 * @param string|null $event_name Optional event name to clear only specific observers.
	 * @return self For method chaining.
	 */
	public function clear( ?string $event_name = null ): self {
		if ( null === $event_name ) {
			$this->observers = array();
		} else {
			unset( $this->observers[ $event_name ] );
		}

		return $this;
	}

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

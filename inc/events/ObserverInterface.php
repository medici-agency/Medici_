<?php
/**
 * Observer Interface
 *
 * Contract for event observers (handlers).
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
 * Observer Interface
 *
 * Defines the contract for event observers.
 *
 * @since 2.0.0
 */
interface ObserverInterface {

	/**
	 * Handle the event
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event object.
	 * @return void
	 */
	public function handle( EventInterface $event ): void;

	/**
	 * Get observer priority
	 *
	 * Lower numbers = higher priority (executed first).
	 * Default priority is 10.
	 *
	 * @since 2.0.0
	 * @return int Priority level.
	 */
	public function getPriority(): int;

	/**
	 * Get list of event names this observer handles
	 *
	 * @since 2.0.0
	 * @return array<string> Event names.
	 */
	public function getSubscribedEvents(): array;
}

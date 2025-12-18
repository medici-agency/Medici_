<?php
/**
 * Event Interface
 *
 * Contract for all event objects in the system.
 *
 * @package    Medici_Agency
 * @subpackage Events
 * @since      2.0.0
 * @version    1.0.1
 */

declare(strict_types=1);

namespace Medici\Events;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event Interface
 *
 * Defines the contract for event objects.
 *
 * @since 2.0.0
 */
interface EventInterface {

	/**
	 * Get event name/type
	 *
	 * @since 2.0.0
	 * @return string Event name.
	 */
	public function getName(): string;

	/**
	 * Get event payload data
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> Event data.
	 */
	public function getPayload(): array;

	/**
	 * Get event creation timestamp
	 *
	 * @since 2.0.0
	 * @return int Unix timestamp.
	 */
	public function getTimestamp(): int;

	/**
	 * Check if event propagation is stopped
	 *
	 * @since 2.0.0
	 * @return bool True if stopped.
	 */
	public function isPropagationStopped(): bool;

	/**
	 * Stop event propagation
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function stopPropagation(): void;

	/**
	 * Get event ID (from database)
	 *
	 * @since 2.0.0
	 * @return int|null Event ID or null if not logged.
	 */
	public function getEventId(): ?int;

	/**
	 * Set event ID (after database insert)
	 *
	 * @since 2.0.0
	 * @param int $id Event ID from database.
	 * @return void
	 */
	public function setEventId( int $id ): void;
}

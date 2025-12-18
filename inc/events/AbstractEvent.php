<?php
/**
 * Abstract Event
 *
 * Base class for all event objects.
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
 * Abstract Event Class
 *
 * Base implementation for events.
 *
 * @since 2.0.0
 */
abstract class AbstractEvent implements EventInterface {

	/**
	 * Event payload
	 *
	 * @var array<string, mixed>
	 */
	protected array $payload;

	/**
	 * Creation timestamp
	 *
	 * @var int
	 */
	protected int $timestamp;

	/**
	 * Propagation stopped flag
	 *
	 * @var bool
	 */
	protected bool $propagationStopped = false;

	/**
	 * Event ID (if logged)
	 *
	 * @var int|null
	 */
	protected ?int $eventId = null;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $payload Event payload.
	 */
	public function __construct( array $payload = array() ) {
		$this->payload   = $payload;
		$this->timestamp = time();
	}

	/**
	 * Get event payload
	 *
	 * @since 2.0.0
	 * @return array<string, mixed> Event data.
	 */
	public function getPayload(): array {
		return $this->payload;
	}

	/**
	 * Get event timestamp
	 *
	 * @since 2.0.0
	 * @return int Unix timestamp.
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * Check if propagation is stopped
	 *
	 * @since 2.0.0
	 * @return bool True if stopped.
	 */
	public function isPropagationStopped(): bool {
		return $this->propagationStopped;
	}

	/**
	 * Stop event propagation
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function stopPropagation(): void {
		$this->propagationStopped = true;
	}

	/**
	 * Get a specific payload value
	 *
	 * @since 2.0.0
	 * @param string $key     Payload key.
	 * @param mixed  $default Default value.
	 * @return mixed Value or default.
	 */
	public function get( string $key, $default = null ) {
		return $this->payload[ $key ] ?? $default;
	}

	/**
	 * Set event ID
	 *
	 * @since 2.0.0
	 * @param int $id Event ID.
	 * @return void
	 */
	public function setEventId( int $id ): void {
		$this->eventId = $id;
	}

	/**
	 * Get event ID
	 *
	 * @since 2.0.0
	 * @return int|null Event ID or null.
	 */
	public function getEventId(): ?int {
		return $this->eventId;
	}

	/**
	 * Get email from payload
	 *
	 * @since 2.0.0
	 * @return string Email or empty string.
	 */
	public function getEmail(): string {
		return $this->get( 'email', '' );
	}
}

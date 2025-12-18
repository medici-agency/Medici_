<?php
/**
 * Consultation Request Event
 *
 * Event triggered when user requests a consultation.
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
 * Consultation Request Event Class
 *
 * @since 2.0.0
 */
final class ConsultationRequestEvent extends AbstractEvent {

	/**
	 * Event name constant
	 */
	public const NAME = 'consultation_request';

	/**
	 * Lead ID if created
	 *
	 * @var int|null
	 */
	private ?int $leadId = null;

	/**
	 * Get event name
	 *
	 * @since 2.0.0
	 * @return string Event name.
	 */
	public function getName(): string {
		return self::NAME;
	}

	/**
	 * Get customer name
	 *
	 * @since 2.0.0
	 * @return string Customer name.
	 */
	public function getCustomerName(): string {
		return $this->get( 'name', '' );
	}

	/**
	 * Get customer phone
	 *
	 * @since 2.0.0
	 * @return string Phone number.
	 */
	public function getPhone(): string {
		return $this->get( 'phone', '' );
	}

	/**
	 * Get requested service
	 *
	 * @since 2.0.0
	 * @return string Service name.
	 */
	public function getService(): string {
		return $this->get( 'service', '' );
	}

	/**
	 * Get message
	 *
	 * @since 2.0.0
	 * @return string Message text.
	 */
	public function getMessage(): string {
		return $this->get( 'message', '' );
	}

	/**
	 * Check if consent was given
	 *
	 * @since 2.0.0
	 * @return bool True if consent given.
	 */
	public function hasConsent(): bool {
		return (bool) $this->get( 'consent', false );
	}

	/**
	 * Get page URL
	 *
	 * @since 2.0.0
	 * @return string Page URL.
	 */
	public function getPageUrl(): string {
		return $this->get( 'page_url', '' );
	}

	/**
	 * Set lead ID
	 *
	 * @since 2.0.0
	 * @param int $lead_id Lead post ID.
	 * @return void
	 */
	public function setLeadId( int $lead_id ): void {
		$this->leadId = $lead_id;
	}

	/**
	 * Get lead ID
	 *
	 * @since 2.0.0
	 * @return int|null Lead ID or null.
	 */
	public function getLeadId(): ?int {
		return $this->leadId;
	}

	/**
	 * Get UTM parameters
	 *
	 * @since 2.0.0
	 * @return array<string, string> UTM parameters.
	 */
	public function getUtmParams(): array {
		return array(
			'utm_source'   => $this->get( 'utm_source', '' ),
			'utm_medium'   => $this->get( 'utm_medium', '' ),
			'utm_campaign' => $this->get( 'utm_campaign', '' ),
			'utm_term'     => $this->get( 'utm_term', '' ),
			'utm_content'  => $this->get( 'utm_content', '' ),
		);
	}

	/**
	 * Create event from sanitized payload
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $payload Raw payload.
	 * @return self
	 */
	public static function fromPayload( array $payload ): self {
		$sanitized = array(
			'name'         => isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '',
			'email'        => isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '',
			'phone'        => isset( $payload['phone'] ) ? sanitize_text_field( $payload['phone'] ) : '',
			'service'      => isset( $payload['service'] ) ? sanitize_text_field( $payload['service'] ) : '',
			'message'      => isset( $payload['message'] ) ? sanitize_textarea_field( $payload['message'] ) : '',
			'consent'      => ! empty( $payload['consent'] ),
			'page_url'     => isset( $payload['page_url'] ) ? esc_url_raw( $payload['page_url'] ) : '',
			'utm_source'   => isset( $payload['utm_source'] ) ? sanitize_text_field( $payload['utm_source'] ) : '',
			'utm_medium'   => isset( $payload['utm_medium'] ) ? sanitize_text_field( $payload['utm_medium'] ) : '',
			'utm_campaign' => isset( $payload['utm_campaign'] ) ? sanitize_text_field( $payload['utm_campaign'] ) : '',
			'utm_term'     => isset( $payload['utm_term'] ) ? sanitize_text_field( $payload['utm_term'] ) : '',
			'utm_content'  => isset( $payload['utm_content'] ) ? sanitize_text_field( $payload['utm_content'] ) : '',
		);

		return new self( $sanitized );
	}
}

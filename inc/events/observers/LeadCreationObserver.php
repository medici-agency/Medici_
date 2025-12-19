<?php
/**
 * Lead Creation Observer
 *
 * Creates Lead CPT entry for consultation requests.
 * Skips creation if lead_id is already set on event (by legacy handler).
 *
 * @package    Medici_Agency
 * @subpackage Events\Observers
 * @since      2.0.0
 * @version    1.1.0
 */

declare(strict_types=1);

namespace Medici\Events\Observers;

use Medici\Events\EventInterface;
use Medici\Events\ObserverInterface;
use Medici\Events\ConsultationRequestEvent;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Creation Observer Class
 *
 * Creates leads in medici_lead CPT.
 *
 * @since 2.0.0
 */
final class LeadCreationObserver implements ObserverInterface {

	/**
	 * Get priority
	 *
	 * Lead creation should happen early.
	 *
	 * @since 2.0.0
	 * @return int Priority level.
	 */
	public function getPriority(): int {
		return 5;
	}

	/**
	 * Get subscribed events
	 *
	 * @since 2.0.0
	 * @return array<string> Event names.
	 */
	public function getSubscribedEvents(): array {
		return array(
			ConsultationRequestEvent::NAME,
		);
	}

	/**
	 * Handle event
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event object.
	 * @return void
	 */
	public function handle( EventInterface $event ): void {
		if ( ! $event instanceof ConsultationRequestEvent ) {
			return;
		}

		// Skip if lead already created (by legacy handler in class-events.php)
		$existing_lead_id = $event->getLeadId();
		if ( null !== $existing_lead_id && $existing_lead_id > 0 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( '[Medici Events] LeadCreationObserver: Skipping - lead already exists (ID=%d)', $existing_lead_id ) );
			}
			return;
		}

		// Check if Lead_CPT exists.
		if ( ! class_exists( 'Medici\Lead_CPT' ) ) {
			$this->logError( 'Lead_CPT class not found' );
			return;
		}

		$lead_id = \Medici\Lead_CPT::create_lead( $event->getPayload() );

		if ( $lead_id > 0 ) {
			$event->setLeadId( $lead_id );
			$this->logSuccess( $lead_id, $event->getEmail() );
		} else {
			$this->logError( 'Failed to create lead' );
		}
	}

	/**
	 * Log success
	 *
	 * @param int    $lead_id Lead ID.
	 * @param string $email   Lead email.
	 * @return void
	 */
	private function logSuccess( int $lead_id, string $email ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Medici Events] Lead created: ID=%d, Email=%s',
					$lead_id,
					$email
				)
			);
		}
	}

	/**
	 * Log error
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function logError( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Medici Events] ' . $message );
		}
	}
}

<?php
/**
 * Integration Observer
 *
 * Sends lead notifications to all configured integrations.
 *
 * @package    Medici_Agency
 * @subpackage Events\Observers
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Events\Observers;

use Medici\Events\EventInterface;
use Medici\Events\ObserverInterface;
use Medici\Events\ConsultationRequestEvent;
use Medici\Lead\IntegrationManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration Observer Class
 *
 * Triggers Email, Telegram, Google Sheets integrations.
 *
 * @since 2.0.0
 */
final class IntegrationObserver implements ObserverInterface {

	/**
	 * Get priority
	 *
	 * Integrations should happen after lead creation.
	 *
	 * @since 2.0.0
	 * @return int Priority level.
	 */
	public function getPriority(): int {
		return 10;
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

		$lead_id = $event->getLeadId();

		if ( null === $lead_id || $lead_id <= 0 ) {
			$this->logError( 'Cannot send integrations - no lead ID' );
			return;
		}

		// Use new Integration Manager.
		$manager = IntegrationManager::getInstance();
		$results = $manager->sendAll( $event->getPayload(), $lead_id );

		// Log results.
		$this->logResults( $lead_id, $results );
	}

	/**
	 * Log integration results
	 *
	 * @param int                $lead_id Lead ID.
	 * @param array<string,bool> $results Results by integration.
	 * @return void
	 */
	private function logResults( int $lead_id, array $results ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$success = array_filter( $results );
		$failed  = array_diff_key( $results, $success );

		if ( ! empty( $success ) ) {
			error_log(
				sprintf(
					'[Medici Events] Integrations sent for lead #%d: %s',
					$lead_id,
					implode( ', ', array_keys( $success ) )
				)
			);
		}

		if ( ! empty( $failed ) ) {
			error_log(
				sprintf(
					'[Medici Events] Integrations failed for lead #%d: %s',
					$lead_id,
					implode( ', ', array_keys( $failed ) )
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

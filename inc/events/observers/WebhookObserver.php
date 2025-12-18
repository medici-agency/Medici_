<?php
/**
 * Webhook Observer
 *
 * Sends events to external webhook endpoints (Zapier, Make).
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
use Medici\Events\NewsletterSubscribeEvent;
use Medici\Events\ConsultationRequestEvent;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook Observer Class
 *
 * Sends events to Zapier/Make webhooks.
 *
 * @since 2.0.0
 */
final class WebhookObserver implements ObserverInterface {

	/**
	 * Option key for webhook URL
	 */
	private const OPTION_WEBHOOK_URL = 'medici_events_webhook_url';

	/**
	 * Allowed webhook domains
	 */
	private const ALLOWED_DOMAINS = array(
		'hooks.zapier.com',
		'hook.eu1.make.com',
		'hook.us1.make.com',
		'hook.eu2.make.com',
		'hooks.slack.com',
		'api.telegram.org',
		'script.google.com',
	);

	/**
	 * Get priority
	 *
	 * Webhooks should happen last.
	 *
	 * @since 2.0.0
	 * @return int Priority level.
	 */
	public function getPriority(): int {
		return 100;
	}

	/**
	 * Get subscribed events
	 *
	 * @since 2.0.0
	 * @return array<string> Event names.
	 */
	public function getSubscribedEvents(): array {
		return array(
			NewsletterSubscribeEvent::NAME,
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
		$webhook_url = get_option( self::OPTION_WEBHOOK_URL );

		if ( empty( $webhook_url ) ) {
			return;
		}

		if ( ! $this->isUrlAllowed( $webhook_url ) ) {
			$this->logError( 'Webhook URL not in allowed list: ' . $webhook_url );
			return;
		}

		$this->sendWebhook( $webhook_url, $event );
	}

	/**
	 * Check if URL is allowed
	 *
	 * @param string $url Webhook URL.
	 * @return bool True if allowed.
	 */
	private function isUrlAllowed( string $url ): bool {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$parsed = wp_parse_url( $url );
		if ( ! $parsed || empty( $parsed['host'] ) ) {
			return false;
		}

		// Must use HTTPS.
		if ( empty( $parsed['scheme'] ) || 'https' !== strtolower( $parsed['scheme'] ) ) {
			return false;
		}

		$host = strtolower( $parsed['host'] );
		foreach ( self::ALLOWED_DOMAINS as $allowed ) {
			if ( $host === $allowed || str_ends_with( $host, '.' . $allowed ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Send webhook request
	 *
	 * @param string         $url   Webhook URL.
	 * @param EventInterface $event Event object.
	 * @return void
	 */
	private function sendWebhook( string $url, EventInterface $event ): void {
		$data = array(
			'event_type' => $event->getName(),
			'event_id'   => $event->getEventId(),
			'payload'    => $event->getPayload(),
			'meta'       => array(
				'site_url'   => home_url( '/' ),
				'site_name'  => get_bloginfo( 'name' ),
				'created_at' => gmdate( 'c', $event->getTimestamp() ),
			),
		);

		// Fire and forget (non-blocking).
		wp_remote_post(
			$url,
			array(
				'timeout'  => 5,
				'blocking' => false,
				'headers'  => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'     => wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			)
		);
	}

	/**
	 * Log error
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function logError( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Medici Events Webhook] ' . $message );
		}
	}
}

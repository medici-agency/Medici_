<?php
/**
 * Events Module Bootstrap
 *
 * Loads all event classes and registers default observers.
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

// Load core classes.
require_once __DIR__ . '/EventInterface.php';
require_once __DIR__ . '/ObserverInterface.php';
require_once __DIR__ . '/AbstractEvent.php';
require_once __DIR__ . '/EventDispatcher.php';

// Load concrete events.
require_once __DIR__ . '/NewsletterSubscribeEvent.php';
require_once __DIR__ . '/ConsultationRequestEvent.php';

// Load observers.
require_once __DIR__ . '/observers/LoggingObserver.php';
require_once __DIR__ . '/observers/LeadCreationObserver.php';
require_once __DIR__ . '/observers/IntegrationObserver.php';
require_once __DIR__ . '/observers/WebhookObserver.php';

use Medici\Events\Observers\LoggingObserver;
use Medici\Events\Observers\LeadCreationObserver;
use Medici\Events\Observers\IntegrationObserver;
use Medici\Events\Observers\WebhookObserver;

/**
 * Events Module
 *
 * Main entry point for the events system.
 *
 * @since 2.0.0
 */
final class EventsModule {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Event dispatcher
	 *
	 * @var EventDispatcher
	 */
	private EventDispatcher $dispatcher;

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
	 * Constructor
	 */
	private function __construct() {
		$this->dispatcher = EventDispatcher::getInstance();
	}

	/**
	 * Initialize the events module
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Register default observers.
		$this->registerDefaultObservers();

		// AJAX handlers are registered in class-events.php (legacy)
		// to avoid duplicate handling. OOP observers are triggered
		// via do_action('medici_event_dispatched') from legacy handler.
		// @see inc/class-events.php:92
	}

	/**
	 * Register default observers
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerDefaultObservers(): void {
		$this->dispatcher
			->subscribe( new LoggingObserver() )
			->subscribe( new LeadCreationObserver() )
			->subscribe( new IntegrationObserver() )
			->subscribe( new WebhookObserver() );
	}

	/**
	 * Get dispatcher instance
	 *
	 * @since 2.0.0
	 * @return EventDispatcher
	 */
	public function getDispatcher(): EventDispatcher {
		return $this->dispatcher;
	}

	/**
	 * Dispatch an event
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event to dispatch.
	 * @return EventInterface
	 */
	public function dispatch( EventInterface $event ): EventInterface {
		return $this->dispatcher->dispatch( $event );
	}

	/**
	 * Handle AJAX request
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handleAjax(): void {
		// Security check.
		if ( ! $this->verifyRequest() ) {
			wp_send_json_error(
				array( 'message' => __( 'Помилка безпеки. Оновіть сторінку та спробуйте знову.', 'medici.agency' ) ),
				403
			);
		}

		// Rate limiting.
		$rate_error = $this->checkRateLimit();
		if ( $rate_error ) {
			wp_send_json_error( array( 'message' => $rate_error ), 429 );
		}

		// Get event type.
		$event_type = isset( $_POST['event_type'] )
			? sanitize_key( wp_unslash( $_POST['event_type'] ) )
			: '';

		// Get payload.
		$payload = isset( $_POST['payload'] )
			? wp_unslash( $_POST['payload'] )
			: array();

		if ( empty( $event_type ) || ! is_array( $payload ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Некоректні дані', 'medici.agency' ) ),
				400
			);
		}

		// Create and dispatch event.
		$event = $this->createEvent( $event_type, $payload );

		if ( null === $event ) {
			wp_send_json_error(
				array( 'message' => __( 'Невідомий тип події', 'medici.agency' ) ),
				400
			);
		}

		// Validate event.
		$validation_error = $this->validateEvent( $event );
		if ( $validation_error ) {
			wp_send_json_error( array( 'message' => $validation_error ), 400 );
		}

		// Dispatch.
		$this->dispatch( $event );

		// Success.
		wp_send_json_success(
			array(
				'message'  => $this->getSuccessMessage( $event_type ),
				'event_id' => $event->getEventId(),
			)
		);
	}

	/**
	 * Create event from type and payload
	 *
	 * @param string               $event_type Event type.
	 * @param array<string, mixed> $payload    Payload data.
	 * @return EventInterface|null
	 */
	private function createEvent( string $event_type, array $payload ): ?EventInterface {
		return match ( $event_type ) {
			NewsletterSubscribeEvent::NAME   => NewsletterSubscribeEvent::fromPayload( $payload ),
			ConsultationRequestEvent::NAME   => ConsultationRequestEvent::fromPayload( $payload ),
			default                          => null,
		};
	}

	/**
	 * Validate event
	 *
	 * @param EventInterface $event Event to validate.
	 * @return string|null Error message or null.
	 */
	private function validateEvent( EventInterface $event ): ?string {
		if ( $event instanceof NewsletterSubscribeEvent ) {
			if ( empty( $event->getEmail() ) || ! is_email( $event->getEmail() ) ) {
				return __( 'Вкажіть коректний email', 'medici.agency' );
			}

			if ( $this->isEmailSubscribed( $event->getEmail() ) ) {
				return __( 'Цей email вже підписаний', 'medici.agency' );
			}
		}

		if ( $event instanceof ConsultationRequestEvent ) {
			if ( empty( $event->getCustomerName() ) ) {
				return __( "Вкажіть ваше ім'я", 'medici.agency' );
			}

			if ( empty( $event->getEmail() ) || ! is_email( $event->getEmail() ) ) {
				return __( 'Вкажіть коректний email', 'medici.agency' );
			}

			if ( empty( $event->getPhone() ) ) {
				return __( 'Вкажіть номер телефону', 'medici.agency' );
			}

			if ( ! $event->hasConsent() ) {
				return __( 'Потрібна згода на обробку даних', 'medici.agency' );
			}
		}

		return null;
	}

	/**
	 * Verify AJAX request security
	 *
	 * @return bool True if valid.
	 */
	private function verifyRequest(): bool {
		$nonce       = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		$is_public   = ! is_user_logged_in();
		$nonce_valid = wp_verify_nonce( $nonce, 'medici_event' );

		// Strict for logged-in users.
		if ( ! $is_public && ! $nonce_valid ) {
			return false;
		}

		// Lenient for public forms - use alternative checks.
		if ( $is_public && ! $nonce_valid ) {
			// Honeypot.
			if ( ! empty( $_POST['website'] ) || ! empty( $_POST['url'] ) || ! empty( $_POST['company'] ) ) {
				return false;
			}

			// User-Agent.
			$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			if ( empty( $ua ) || strlen( $ua ) < 10 ) {
				return false;
			}

			// Referer.
			$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$site_url = get_site_url();
			if ( ! empty( $referer ) && 0 !== strpos( $referer, $site_url ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check rate limit
	 *
	 * @return string|null Error message or null.
	 */
	private function checkRateLimit(): ?string {
		$ip = $this->getClientIp();
		if ( empty( $ip ) ) {
			return null;
		}

		$key   = 'medici_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 5 ) {
			return __( 'Забагато запитів. Спробуйте пізніше.', 'medici.agency' );
		}

		set_transient( $key, $count + 1, 300 );

		return null;
	}

	/**
	 * Get client IP
	 *
	 * @return string IP address.
	 */
	private function getClientIp(): string {
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}

	/**
	 * Check if email is already subscribed
	 *
	 * @param string $email Email address.
	 * @return bool True if subscribed.
	 */
	private function isEmailSubscribed( string $email ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'medici_events';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE event_type = %s AND email = %s",
				'newsletter_subscribe',
				$email
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Get success message
	 *
	 * @param string $event_type Event type.
	 * @return string Success message.
	 */
	private function getSuccessMessage( string $event_type ): string {
		return match ( $event_type ) {
			'newsletter_subscribe'   => __( 'Дякуємо за підписку! Перевірте пошту.', 'medici.agency' ),
			'consultation_request'   => __( "Дякуємо! Ми зв'яжемось з вами найближчим часом.", 'medici.agency' ),
			default                  => __( 'Дякуємо! Дані відправлені.', 'medici.agency' ),
		};
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

/**
 * Get events module instance
 *
 * @since 2.0.0
 * @return EventsModule
 */
function medici_events(): EventsModule {
	return EventsModule::getInstance();
}

// Initialize on init hook.
add_action(
	'init',
	function () {
		medici_events()->init();
	},
	5
);

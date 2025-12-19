<?php
/**
 * Events API - Unified Event Handling System
 *
 * Handles all events (Newsletter, Consultation, etc.) through single AJAX endpoint
 * with local logging and webhook integration (Zapier/Make).
 *
 * @package    Medici_Agency
 * @subpackage Events
 * @since      1.4.0
 * @version    2.0.0
 * @changelog  2.0.0 - OOP Integration: EventDispatcher + IntegrationManager bridge
 * @changelog  1.2.3 - Lenient nonce verification for public forms (honeypot + User-Agent + Referer)
 * @changelog  1.2.2 - Better nonce verification + Lead CPT integration
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events API Handler Class
 *
 * @since 1.4.0
 */
final class Events {

	/**
	 * Webhook URL option key
	 */
	private const OPTION_WEBHOOK_URL = 'medici_events_webhook_url';

	/**
	 * Table created flag option key
	 *
	 * @since 1.1.0
	 */
	private const OPTION_TABLE_CREATED = 'medici_events_table_created';

	/**
	 * Events table name (without prefix)
	 */
	private const TABLE_NAME = 'medici_events';

	/**
	 * Rate limit: max requests per window
	 *
	 * @since 1.2.0
	 */
	private const RATE_LIMIT_MAX_REQUESTS = 5;

	/**
	 * Rate limit: time window in seconds (5 minutes)
	 *
	 * @since 1.2.0
	 */
	private const RATE_LIMIT_WINDOW = 300;

	/**
	 * Allowed webhook domains for security
	 *
	 * Only webhooks to these domains are allowed.
	 * Prevents SSRF (Server-Side Request Forgery) attacks.
	 *
	 * @since 1.2.0
	 */
	private const ALLOWED_WEBHOOK_DOMAINS = array(
		'hooks.zapier.com',
		'hook.eu1.make.com',
		'hook.us1.make.com',
		'hook.eu2.make.com',
		'hooks.slack.com',
		'api.telegram.org',
		'script.google.com',
	);

	/**
	 * Initialize Events API
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Register AJAX handlers
		add_action( 'wp_ajax_medici_event', array( $self, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_medici_event', array( $self, 'handle_ajax' ) );

		// Create table on theme activation (auto-installer)
		add_action( 'after_switch_theme', array( $self, 'install' ) );
	}

	/**
	 * Install database table (run on theme activation)
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function install(): void {
		// Check if already installed
		if ( get_option( self::OPTION_TABLE_CREATED ) ) {
			return;
		}

		// Create table
		$this->create_table();

		// Set flag to avoid repeated checks
		update_option( self::OPTION_TABLE_CREATED, '1', false );
	}

	/**
	 * Create events table
	 *
	 * Table structure:
	 * - id: Auto-increment primary key
	 * - event_type: Event type (newsletter_subscribe, consultation_request, etc.)
	 * - email: Email address (for quick queries)
	 * - created_at: Event timestamp
	 * - payload: JSON-encoded event data
	 *
	 * @since 1.4.0
	 * @return void
	 */
	private function create_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE {$table_name} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_type VARCHAR(100) NOT NULL,
				email VARCHAR(190) NULL,
				created_at DATETIME NOT NULL,
				payload LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY event_type (event_type),
				KEY email (email),
				KEY created_at (created_at)
			) {$charset_collate};
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Ensure table exists (cached check via option)
	 *
	 * Instead of running SHOW TABLES LIKE on every request,
	 * we check a cached option first for better performance.
	 *
	 * @since 1.1.0
	 * @return bool True if table exists
	 */
	private function ensure_table_exists(): bool {
		// Fast path: check cached option first (no DB query)
		if ( get_option( self::OPTION_TABLE_CREATED ) ) {
			return true;
		}

		// Slow path: verify table actually exists and create if needed
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			$this->create_table();
		}

		// Cache the fact that table exists
		update_option( self::OPTION_TABLE_CREATED, '1', false );

		return true;
	}

	/**
	 * AJAX handler - single entry point for all events
	 *
	 * Expected POST data:
	 * - nonce: Security nonce
	 * - event_type: Event type identifier
	 * - payload: Array with event-specific data
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function handle_ajax(): void {
		// Security check with lenient verification for public forms
		$nonce          = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		$is_public_form = ! is_user_logged_in();
		$nonce_valid    = wp_verify_nonce( $nonce, 'medici_event' );

		// For logged-in users, enforce strict nonce verification
		if ( ! $is_public_form && ! $nonce_valid ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Medici Events] Nonce verification failed for logged-in user' );
			}

			wp_send_json_error(
				array(
					'message' => __( 'Помилка безпеки. Будь ласка, оновіть сторінку та спробуйте знову.', 'medici.agency' ),
					'code'    => 'invalid_nonce',
				),
				403
			);
		}

		// For public forms (exit-intent, newsletter), use alternative security measures
		if ( $is_public_form && ! $nonce_valid ) {
			// Log but continue (rely on rate limiting and honeypot)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Medici Events] Public form nonce invalid - using alternative security' );
			}

			// Honeypot check (bots often fill hidden fields)
			if ( ! empty( $_POST['website'] ) || ! empty( $_POST['url'] ) || ! empty( $_POST['company'] ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Medici Events] Honeypot triggered - blocking spam' );
				}

				wp_send_json_error( array( 'message' => __( 'Помилка валідації', 'medici.agency' ) ), 400 );
			}

			// User-Agent check (basic bot detection)
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			if ( empty( $user_agent ) || strlen( $user_agent ) < 10 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Medici Events] Suspicious User-Agent - blocking' );
				}

				wp_send_json_error( array( 'message' => __( 'Помилка валідації', 'medici.agency' ) ), 400 );
			}

			// HTTP_REFERER check (must be from same domain)
			$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
			$site_url = get_site_url();
			if ( ! empty( $referer ) && strpos( $referer, $site_url ) !== 0 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Medici Events] Invalid referer - blocking cross-origin request' );
				}

				wp_send_json_error( array( 'message' => __( 'Помилка валідації', 'medici.agency' ) ), 400 );
			}
		}

		// Rate limiting check
		$rate_limit_error = $this->check_rate_limit();
		if ( $rate_limit_error ) {
			wp_send_json_error( array( 'message' => $rate_limit_error ), 429 );
		}

		// Get event type
		$event_type = isset( $_POST['event_type'] )
			? sanitize_key( wp_unslash( $_POST['event_type'] ) )
			: '';

		// Get payload
		$raw_payload = isset( $_POST['payload'] )
			? wp_unslash( $_POST['payload'] )
			: array();

		// Validate input
		if ( empty( $event_type ) || ! is_array( $raw_payload ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Некоректні дані події', 'medici.agency' ) ),
				400
			);
		}

		// Sanitize payload based on event type
		$payload = $this->sanitize_payload( $event_type, $raw_payload );

		// Validate event data
		$validation_error = $this->validate_payload( $event_type, $payload );
		if ( $validation_error ) {
			wp_send_json_error( array( 'message' => $validation_error ), 400 );
		}

		// Log event locally
		$event_id = $this->log_event( $event_type, $payload );

		// Send to webhook (Zapier/Make)
		$this->send_to_webhook( $event_type, $payload, $event_id );

		// Create lead in CPT for consultation requests (legacy handler)
		$lead_id = 0;
		if ( 'consultation_request' === $event_type ) {
			if ( class_exists( 'Medici\Lead_CPT' ) ) {
				$lead_id = \Medici\Lead_CPT::create_lead( $payload );

				if ( $lead_id > 0 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( sprintf( '[Medici Events] Lead created: ID=%d, Email=%s', $lead_id, $payload['email'] ?? 'N/A' ) );
				}
			}
		}

		// Dispatch OOP event through EventDispatcher (v2.0.0)
		// Integrations are handled by OOP IntegrationObserver
		$this->dispatch_oop_event( $event_type, $payload, $event_id, $lead_id );

		// Success response
		wp_send_json_success(
			array(
				'message'  => $this->get_success_message( $event_type ),
				'event_id' => $event_id,
			)
		);
	}

	/**
	 * Sanitize payload based on event type
	 *
	 * @since 1.4.0
	 * @param string $event_type Event type identifier
	 * @param array  $payload    Raw payload data
	 * @return array Sanitized payload
	 */
	private function sanitize_payload( string $event_type, array $payload ): array {
		$result = array();

		// Common fields (available for all events)
		if ( isset( $payload['page_url'] ) ) {
			$result['page_url'] = esc_url_raw( $payload['page_url'] );
		}
		if ( isset( $payload['utm_source'] ) ) {
			$result['utm_source'] = sanitize_text_field( $payload['utm_source'] );
		}
		if ( isset( $payload['utm_medium'] ) ) {
			$result['utm_medium'] = sanitize_text_field( $payload['utm_medium'] );
		}
		if ( isset( $payload['utm_campaign'] ) ) {
			$result['utm_campaign'] = sanitize_text_field( $payload['utm_campaign'] );
		}
		if ( isset( $payload['utm_term'] ) ) {
			$result['utm_term'] = sanitize_text_field( $payload['utm_term'] );
		}
		if ( isset( $payload['utm_content'] ) ) {
			$result['utm_content'] = sanitize_text_field( $payload['utm_content'] );
		}

		// Newsletter subscription fields
		if ( 'newsletter_subscribe' === $event_type ) {
			$result['email']  = isset( $payload['email'] )
				? sanitize_email( $payload['email'] )
				: '';
			$result['source'] = isset( $payload['source'] )
				? sanitize_text_field( $payload['source'] )
				: '';
			$result['tags']   = isset( $payload['tags'] ) && is_array( $payload['tags'] )
				? array_map( 'sanitize_text_field', $payload['tags'] )
				: array();
		}

		// Consultation request fields
		if ( 'consultation_request' === $event_type ) {
			$result['name']    = isset( $payload['name'] )
				? sanitize_text_field( $payload['name'] )
				: '';
			$result['email']   = isset( $payload['email'] )
				? sanitize_email( $payload['email'] )
				: '';
			$result['phone']   = isset( $payload['phone'] )
				? sanitize_text_field( $payload['phone'] )
				: '';
			$result['message'] = isset( $payload['message'] )
				? sanitize_textarea_field( $payload['message'] )
				: '';
			$result['service'] = isset( $payload['service'] )
				? sanitize_text_field( $payload['service'] )
				: '';
			$result['consent'] = ! empty( $payload['consent'] );
		}

		return $result;
	}

	/**
	 * Check rate limit for current IP
	 *
	 * Prevents spam by limiting requests per IP address.
	 * Uses WordPress transients for storage.
	 *
	 * @since 1.2.0
	 * @return string|null Error message if rate limited, null if OK
	 */
	private function check_rate_limit(): ?string {
		// Get client IP
		$ip = $this->get_client_ip();
		if ( empty( $ip ) ) {
			return null; // Can't rate limit without IP
		}

		// Create transient key (sanitized IP)
		$transient_key = 'medici_rate_' . md5( $ip );

		// Get current count
		$count = (int) get_transient( $transient_key );

		// Check if over limit
		if ( $count >= self::RATE_LIMIT_MAX_REQUESTS ) {
			return __( 'Забагато запитів. Будь ласка, спробуйте пізніше.', 'medici.agency' );
		}

		// Increment counter
		if ( 0 === $count ) {
			// First request - set transient with expiration
			set_transient( $transient_key, 1, self::RATE_LIMIT_WINDOW );
		} else {
			// Increment existing counter (preserve TTL by getting remaining time)
			++$count;
			// Note: WordPress doesn't have a native way to preserve TTL,
			// so we set the full window again. This is acceptable for rate limiting.
			set_transient( $transient_key, $count, self::RATE_LIMIT_WINDOW );
		}

		return null;
	}

	/**
	 * Get client IP address
	 *
	 * Handles proxies (Cloudflare, load balancers).
	 *
	 * @since 1.2.0
	 * @return string Client IP or empty string
	 */
	private function get_client_ip(): string {
		// Cloudflare
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		// Standard proxy headers
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}

		// Direct connection
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}

	/**
	 * Validate webhook URL
	 *
	 * Ensures the webhook URL is:
	 * 1. Valid URL format
	 * 2. Uses HTTPS protocol
	 * 3. Points to an allowed domain
	 *
	 * Prevents SSRF attacks by restricting outbound requests.
	 *
	 * @since 1.2.0
	 * @param string $url Webhook URL to validate
	 * @return bool True if URL is valid and allowed
	 */
	private function is_webhook_url_allowed( string $url ): bool {
		// Must be a valid URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Parse URL components
		$parsed = wp_parse_url( $url );
		if ( ! $parsed || empty( $parsed['host'] ) ) {
			return false;
		}

		// Must use HTTPS
		if ( empty( $parsed['scheme'] ) || 'https' !== strtolower( $parsed['scheme'] ) ) {
			return false;
		}

		// Check against allowed domains
		$host = strtolower( $parsed['host'] );
		foreach ( self::ALLOWED_WEBHOOK_DOMAINS as $allowed_domain ) {
			// Exact match or subdomain match
			if ( $host === $allowed_domain || str_ends_with( $host, '.' . $allowed_domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate payload based on event type
	 *
	 * @since 1.4.0
	 * @param string $event_type Event type identifier
	 * @param array  $payload    Sanitized payload data
	 * @return string|null Error message or null if valid
	 */
	private function validate_payload( string $event_type, array $payload ): ?string {
		// Input length limits (prevent oversized inputs).
		$max_lengths = array(
			'name'    => 100,
			'email'   => 254,  // RFC 5321 max email length.
			'phone'   => 20,
			'service' => 100,
			'message' => 2000,
		);

		// Validate field lengths.
		foreach ( $max_lengths as $field => $max ) {
			if ( ! empty( $payload[ $field ] ) && mb_strlen( $payload[ $field ] ) > $max ) {
				return sprintf(
					/* translators: %1$s: field name, %2$d: max length */
					__( 'Поле "%1$s" занадто довге (максимум %2$d символів)', 'medici.agency' ),
					$field,
					$max
				);
			}
		}

		// Newsletter validation.
		if ( 'newsletter_subscribe' === $event_type ) {
			if ( empty( $payload['email'] ) || ! is_email( $payload['email'] ) ) {
				return __( 'Будь ласка, вкажіть коректний email', 'medici.agency' );
			}

			// Check if already subscribed (prevent duplicates).
			if ( $this->is_email_subscribed( $payload['email'] ) ) {
				return __( 'Цей email вже підписаний на розсилку', 'medici.agency' );
			}
		}

		// Consultation validation.
		if ( 'consultation_request' === $event_type ) {
			if ( empty( $payload['name'] ) ) {
				return __( 'Будь ласка, вкажіть ваше ім\'я', 'medici.agency' );
			}
			if ( empty( $payload['email'] ) || ! is_email( $payload['email'] ) ) {
				return __( 'Будь ласка, вкажіть коректний email', 'medici.agency' );
			}
			if ( empty( $payload['phone'] ) ) {
				return __( 'Будь ласка, вкажіть номер телефону', 'medici.agency' );
			}
			if ( empty( $payload['consent'] ) ) {
				return __( 'Для відправки потрібна ваша згода на обробку персональних даних', 'medici.agency' );
			}
		}

		return null;
	}

	/**
	 * Check if email is already subscribed
	 *
	 * @since 1.4.0
	 * @param string $email Email address
	 * @return bool True if subscribed
	 */
	private function is_email_subscribed( string $email ): bool {
		global $wpdb;

		// Ensure table exists (cached check, fast)
		if ( ! $this->ensure_table_exists() ) {
			return false;
		}

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE event_type = %s AND email = %s",
				'newsletter_subscribe',
				$email
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Log event to database
	 *
	 * @since 1.4.0
	 * @param string $event_type Event type identifier
	 * @param array  $payload    Sanitized payload data
	 * @return int Event ID or 0 on failure
	 */
	private function log_event( string $event_type, array $payload ): int {
		global $wpdb;

		// Ensure table exists (cached check, fast)
		$this->ensure_table_exists();

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'event_type' => $event_type,
				'email'      => $payload['email'] ?? null,
				'created_at' => current_time( 'mysql', true ),
				'payload'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Send event to webhook (Zapier/Make)
	 *
	 * @since 1.4.0
	 * @param string $event_type Event type identifier
	 * @param array  $payload    Sanitized payload data
	 * @param int    $event_id   Local event ID
	 * @return void
	 */
	private function send_to_webhook( string $event_type, array $payload, int $event_id ): void {
		$webhook_url = get_option( self::OPTION_WEBHOOK_URL );

		if ( empty( $webhook_url ) ) {
			return;
		}

		// Security: Validate webhook URL before sending
		if ( ! $this->is_webhook_url_allowed( $webhook_url ) ) {
			// Log invalid webhook attempt (for debugging)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log(
					sprintf(
						'[Medici Events] Webhook URL rejected (not in allowed list): %s',
						$webhook_url
					)
				);
			}
			return;
		}

		// Prepare webhook payload
		$webhook_data = array(
			'event_type' => $event_type,
			'event_id'   => $event_id,
			'payload'    => $payload,
			'meta'       => array(
				'site_url'   => home_url( '/' ),
				'site_name'  => get_bloginfo( 'name' ),
				'created_at' => current_time( 'c' ), // ISO 8601 format
			),
		);

		// Send async request (fire and forget)
		wp_remote_post(
			$webhook_url,
			array(
				'timeout'     => 5,
				'blocking'    => false, // Non-blocking request
				'headers'     => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'        => wp_json_encode( $webhook_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
				'data_format' => 'body',
			)
		);
	}

	/**
	 * Get success message based on event type
	 *
	 * @since 1.4.0
	 * @param string $event_type Event type identifier
	 * @return string Success message
	 */
	private function get_success_message( string $event_type ): string {
		$messages = array(
			'newsletter_subscribe' => __( 'Дякуємо за підписку! Перевірте вашу пошту.', 'medici.agency' ),
			'consultation_request' => __( 'Дякуємо! Ми зв\'яжемось з вами найближчим часом.', 'medici.agency' ),
		);

		return $messages[ $event_type ] ?? __( 'Дякуємо! Дані успішно відправлені.', 'medici.agency' );
	}

	/**
	 * Dispatch OOP event through EventDispatcher
	 *
	 * Bridges legacy Events API with new OOP Event system (v2.0.0).
	 * Creates appropriate event object and dispatches through EventDispatcher.
	 *
	 * @since 2.0.0
	 * @param string $event_type Event type identifier
	 * @param array  $payload    Sanitized payload data
	 * @param int    $event_id   Local event ID
	 * @param int    $lead_id    Lead ID (for consultation requests, created by legacy handler)
	 * @return void
	 */
	private function dispatch_oop_event( string $event_type, array $payload, int $event_id, int $lead_id = 0 ): void {
		// Check if OOP Events module is loaded
		if ( ! class_exists( 'Medici\Events\EventDispatcher' ) ) {
			return;
		}

		$dispatcher = \Medici\Events\EventDispatcher::getInstance();

		// Create and dispatch appropriate event
		switch ( $event_type ) {
			case 'consultation_request':
				if ( class_exists( 'Medici\Events\ConsultationRequestEvent' ) ) {
					$event = \Medici\Events\ConsultationRequestEvent::fromPayload( $payload );
					$event->setEventId( $event_id );

					// Pass lead_id from legacy handler to avoid duplicate creation
					if ( $lead_id > 0 ) {
						$event->setLeadId( $lead_id );
					}

					$dispatcher->dispatch( $event );

					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf( '[Medici Events] OOP ConsultationRequestEvent dispatched: ID=%d, LeadID=%d', $event_id, $lead_id ) );
					}
				}
				break;

			case 'newsletter_subscribe':
				if ( class_exists( 'Medici\Events\NewsletterSubscribeEvent' ) ) {
					$event = \Medici\Events\NewsletterSubscribeEvent::fromPayload( $payload );
					$event->setEventId( $event_id );
					$dispatcher->dispatch( $event );

					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf( '[Medici Events] OOP NewsletterSubscribeEvent dispatched: ID=%d', $event_id ) );
					}
				}
				break;
		}
	}
}

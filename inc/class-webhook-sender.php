<?php
/**
 * Webhook Sender Class
 *
 * Handles sending webhooks with retry logic and logging.
 *
 * @package    Medici_Agency
 * @subpackage Webhooks
 * @since      1.5.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook Sender Class
 *
 * @since 1.5.0
 */
final class Webhook_Sender {

	/**
	 * Maximum retry attempts
	 *
	 * @var int
	 */
	private const MAX_RETRIES = 3;

	/**
	 * Base delay for exponential backoff (seconds)
	 *
	 * @var int
	 */
	private const BASE_DELAY = 2;

	/**
	 * Timeout for HTTP requests (seconds)
	 *
	 * @var int
	 */
	private const TIMEOUT = 30;

	/**
	 * Option name for webhooks configuration
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOKS = 'medici_webhooks';

	/**
	 * Option name for webhook logs
	 *
	 * @var string
	 */
	public const OPTION_LOGS = 'medici_webhook_logs';

	/**
	 * Maximum log entries to keep
	 *
	 * @var int
	 */
	private const MAX_LOGS = 50;

	/**
	 * Available webhook events
	 *
	 * @var array<string, string>
	 */
	public const EVENTS = array(
		'new_lead'             => 'Новий лід',
		'lead_status_changed'  => 'Зміна статусу ліда',
		'newsletter_subscribe' => 'Підписка на розсилку',
	);

	/**
	 * Initialize webhook sender
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Hook into lead events.
		add_action( 'medici_lead_created', array( $self, 'on_lead_created' ), 10, 2 );
		add_action( 'medici_lead_status_changed', array( $self, 'on_lead_status_changed' ), 10, 3 );
		add_action( 'medici_newsletter_subscribed', array( $self, 'on_newsletter_subscribed' ), 10, 1 );

		// AJAX for test webhook.
		add_action( 'wp_ajax_medici_test_webhook', array( $self, 'ajax_test_webhook' ) );
	}

	/**
	 * Handle new lead created event
	 *
	 * @since 1.5.0
	 * @param int   $lead_id Lead post ID.
	 * @param array $data    Lead data.
	 * @return void
	 */
	public function on_lead_created( int $lead_id, array $data ): void {
		$payload = array(
			'event'     => 'new_lead',
			'lead_id'   => $lead_id,
			'timestamp' => current_time( 'c' ),
			'data'      => $data,
		);

		$this->trigger_webhooks( 'new_lead', $payload );
	}

	/**
	 * Handle lead status changed event
	 *
	 * @since 1.5.0
	 * @param int    $lead_id    Lead post ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function on_lead_status_changed( int $lead_id, string $old_status, string $new_status ): void {
		$payload = array(
			'event'      => 'lead_status_changed',
			'lead_id'    => $lead_id,
			'timestamp'  => current_time( 'c' ),
			'old_status' => $old_status,
			'new_status' => $new_status,
		);

		$this->trigger_webhooks( 'lead_status_changed', $payload );
	}

	/**
	 * Handle newsletter subscription event
	 *
	 * @since 1.5.0
	 * @param array $data Subscription data (email, name, etc.).
	 * @return void
	 */
	public function on_newsletter_subscribed( array $data ): void {
		$payload = array(
			'event'     => 'newsletter_subscribe',
			'timestamp' => current_time( 'c' ),
			'data'      => $data,
		);

		$this->trigger_webhooks( 'newsletter_subscribe', $payload );
	}

	/**
	 * Trigger all webhooks for an event
	 *
	 * @since 1.5.0
	 * @param string $event   Event name.
	 * @param array  $payload Event payload.
	 * @return void
	 */
	public function trigger_webhooks( string $event, array $payload ): void {
		$webhooks = self::get_webhooks();

		foreach ( $webhooks as $webhook ) {
			// Check if webhook is enabled and listens to this event.
			if ( empty( $webhook['enabled'] ) || ! in_array( $event, $webhook['events'] ?? array(), true ) ) {
				continue;
			}

			// Send webhook asynchronously via wp_schedule_single_event.
			$this->send_webhook( $webhook, $payload );
		}
	}

	/**
	 * Send webhook with retry logic
	 *
	 * @since 1.5.0
	 * @param array $webhook Webhook configuration.
	 * @param array $payload Event payload.
	 * @return bool Success status.
	 */
	public function send_webhook( array $webhook, array $payload ): bool {
		$url     = $webhook['url'] ?? '';
		$headers = $this->build_headers( $webhook );
		$body    = wp_json_encode( $payload );

		if ( empty( $url ) || false === $body ) {
			return false;
		}

		$attempt = 0;
		$success = false;

		while ( $attempt < self::MAX_RETRIES && ! $success ) {
			++$attempt;

			// Exponential backoff (except first attempt).
			if ( $attempt > 1 ) {
				$delay = self::BASE_DELAY ** $attempt;
				sleep( $delay );
			}

			$response = wp_remote_post(
				$url,
				array(
					'timeout'     => self::TIMEOUT,
					'headers'     => $headers,
					'body'        => $body,
					'data_format' => 'body',
				)
			);

			$status_code = wp_remote_retrieve_response_code( $response );

			if ( ! is_wp_error( $response ) && $status_code >= 200 && $status_code < 300 ) {
				$success = true;
			}

			// Log attempt.
			$this->log_attempt(
				$webhook,
				$payload,
				$response,
				$attempt,
				$success
			);
		}

		return $success;
	}

	/**
	 * Build request headers
	 *
	 * @since 1.5.0
	 * @param array $webhook Webhook configuration.
	 * @return array Headers array.
	 */
	private function build_headers( array $webhook ): array {
		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent'   => 'Medici-Webhook/1.0',
		);

		// Add authorization if configured.
		if ( ! empty( $webhook['auth_type'] ) && ! empty( $webhook['auth_value'] ) ) {
			switch ( $webhook['auth_type'] ) {
				case 'bearer':
					$headers['Authorization'] = 'Bearer ' . $webhook['auth_value'];
					break;
				case 'basic':
					$headers['Authorization'] = 'Basic ' . base64_encode( $webhook['auth_value'] );
					break;
				case 'api_key':
					$headers['X-API-Key'] = $webhook['auth_value'];
					break;
			}
		}

		// Add custom headers.
		if ( ! empty( $webhook['custom_headers'] ) && is_array( $webhook['custom_headers'] ) ) {
			foreach ( $webhook['custom_headers'] as $key => $value ) {
				$headers[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $headers;
	}

	/**
	 * Log webhook attempt
	 *
	 * @since 1.5.0
	 * @param array           $webhook  Webhook configuration.
	 * @param array           $payload  Request payload.
	 * @param array|\WP_Error $response HTTP response.
	 * @param int             $attempt  Attempt number.
	 * @param bool            $success  Success status.
	 * @return void
	 */
	private function log_attempt( array $webhook, array $payload, $response, int $attempt, bool $success ): void {
		$logs = get_option( self::OPTION_LOGS, array() );

		$log_entry = array(
			'timestamp'   => current_time( 'c' ),
			'webhook_id'  => $webhook['id'] ?? '',
			'webhook_url' => $webhook['url'] ?? '',
			'event'       => $payload['event'] ?? '',
			'attempt'     => $attempt,
			'success'     => $success,
			'status_code' => is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response ),
			'error'       => is_wp_error( $response ) ? $response->get_error_message() : '',
		);

		// Prepend new log (newest first).
		array_unshift( $logs, $log_entry );

		// Trim to max logs.
		$logs = array_slice( $logs, 0, self::MAX_LOGS );

		update_option( self::OPTION_LOGS, $logs, false );
	}

	/**
	 * AJAX handler for test webhook
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function ajax_test_webhook(): void {
		check_ajax_referer( 'medici_webhook_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Недостатньо прав' ) );
		}

		$webhook_id = isset( $_POST['webhook_id'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_id'] ) ) : '';

		if ( empty( $webhook_id ) ) {
			wp_send_json_error( array( 'message' => 'ID вебхука не вказано' ) );
		}

		$webhooks = self::get_webhooks();
		$webhook  = null;

		foreach ( $webhooks as $wh ) {
			if ( ( $wh['id'] ?? '' ) === $webhook_id ) {
				$webhook = $wh;
				break;
			}
		}

		if ( ! $webhook ) {
			wp_send_json_error( array( 'message' => 'Вебхук не знайдено' ) );
		}

		// Send test payload.
		$test_payload = array(
			'event'     => 'test',
			'timestamp' => current_time( 'c' ),
			'message'   => 'Це тестове повідомлення від Medici Agency',
			'data'      => array(
				'test_id'   => wp_generate_uuid4(),
				'site_url'  => home_url(),
				'site_name' => get_bloginfo( 'name' ),
			),
		);

		$success = $this->send_webhook( $webhook, $test_payload );

		if ( $success ) {
			wp_send_json_success( array( 'message' => 'Тестовий вебхук успішно відправлено!' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Помилка відправки вебхука. Перевірте логи.' ) );
		}
	}

	/**
	 * Get all configured webhooks
	 *
	 * @since 1.5.0
	 * @return array Array of webhook configurations.
	 */
	public static function get_webhooks(): array {
		return get_option( self::OPTION_WEBHOOKS, array() );
	}

	/**
	 * Save webhooks configuration
	 *
	 * @since 1.5.0
	 * @param array $webhooks Array of webhook configurations.
	 * @return bool Success status.
	 */
	public static function save_webhooks( array $webhooks ): bool {
		return update_option( self::OPTION_WEBHOOKS, $webhooks, false );
	}

	/**
	 * Add a new webhook
	 *
	 * @since 1.5.0
	 * @param array $webhook Webhook configuration.
	 * @return string Webhook ID.
	 */
	public static function add_webhook( array $webhook ): string {
		$webhooks = self::get_webhooks();

		$webhook['id']         = wp_generate_uuid4();
		$webhook['created_at'] = current_time( 'c' );

		$webhooks[] = $webhook;

		self::save_webhooks( $webhooks );

		return $webhook['id'];
	}

	/**
	 * Update a webhook
	 *
	 * @since 1.5.0
	 * @param string $id      Webhook ID.
	 * @param array  $webhook Updated webhook configuration.
	 * @return bool Success status.
	 */
	public static function update_webhook( string $id, array $webhook ): bool {
		$webhooks = self::get_webhooks();

		foreach ( $webhooks as $index => $wh ) {
			if ( ( $wh['id'] ?? '' ) === $id ) {
				$webhook['id']         = $id;
				$webhook['created_at'] = $wh['created_at'] ?? current_time( 'c' );
				$webhook['updated_at'] = current_time( 'c' );
				$webhooks[ $index ]    = $webhook;

				return self::save_webhooks( $webhooks );
			}
		}

		return false;
	}

	/**
	 * Delete a webhook
	 *
	 * @since 1.5.0
	 * @param string $id Webhook ID.
	 * @return bool Success status.
	 */
	public static function delete_webhook( string $id ): bool {
		$webhooks = self::get_webhooks();

		foreach ( $webhooks as $index => $wh ) {
			if ( ( $wh['id'] ?? '' ) === $id ) {
				unset( $webhooks[ $index ] );
				return self::save_webhooks( array_values( $webhooks ) );
			}
		}

		return false;
	}

	/**
	 * Get webhook logs
	 *
	 * @since 1.5.0
	 * @param int $limit Number of logs to retrieve.
	 * @return array Array of log entries.
	 */
	public static function get_logs( int $limit = 50 ): array {
		$logs = get_option( self::OPTION_LOGS, array() );
		return array_slice( $logs, 0, $limit );
	}

	/**
	 * Clear webhook logs
	 *
	 * @since 1.5.0
	 * @return bool Success status.
	 */
	public static function clear_logs(): bool {
		return delete_option( self::OPTION_LOGS );
	}
}

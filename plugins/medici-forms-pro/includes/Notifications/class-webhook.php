<?php
/**
 * Webhook Notifications Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Notifications;

use MediciForms\Plugin;
use MediciForms\Post_Types\Form;

/**
 * Webhook Class.
 *
 * @since 1.0.0
 */
class Webhook {

	/**
	 * Send webhook notification.
	 *
	 * @since 1.0.0
	 * @param int                  $form_id    Form ID.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param int                  $entry_id   Entry ID.
	 * @return bool
	 */
	public function send( int $form_id, array $entry_data, int $entry_id ): bool {
		$url = Plugin::get_option( 'webhook_url', '' );

		if ( empty( $url ) ) {
			return false;
		}

		$method  = Plugin::get_option( 'webhook_method', 'POST' );
		$headers = $this->get_headers();
		$payload = $this->build_payload( $form_id, $entry_data, $entry_id );

		$args = array(
			'method'      => $method,
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => wp_json_encode( $payload ),
			'cookies'     => array(),
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->log_error( $form_id, $entry_id, $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$this->log_error( $form_id, $entry_id, 'HTTP ' . $response_code );
			return false;
		}

		return true;
	}

	/**
	 * Get webhook headers.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	private function get_headers(): array {
		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent'   => 'MediciForms/' . MEDICI_FORMS_VERSION,
		);

		$custom_headers = Plugin::get_option( 'webhook_headers', array() );

		if ( is_array( $custom_headers ) ) {
			$headers = array_merge( $headers, $custom_headers );
		}

		return $headers;
	}

	/**
	 * Build webhook payload.
	 *
	 * @since 1.0.0
	 * @param int                  $form_id    Form ID.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param int                  $entry_id   Entry ID.
	 * @return array<string, mixed>
	 */
	private function build_payload( int $form_id, array $entry_data, int $entry_id ): array {
		$form = Form::get( $form_id );
		$meta = $entry_data['_meta'] ?? array();

		// Flatten entry data for easier processing.
		$fields = array();
		foreach ( $entry_data as $field_id => $field ) {
			if ( '_meta' === $field_id || ! is_array( $field ) ) {
				continue;
			}

			$value = $field['value'] ?? '';
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$fields[ $field_id ] = array(
				'label' => $field['label'] ?? $field_id,
				'value' => $value,
				'type'  => $field['type'] ?? 'text',
			);
		}

		return array(
			'event'     => 'form_submission',
			'timestamp' => gmdate( 'c' ),
			'form'      => array(
				'id'   => $form_id,
				'name' => $form ? $form->post_title : '',
			),
			'entry'     => array(
				'id'     => $entry_id,
				'fields' => $fields,
			),
			'meta'      => array(
				'page_url'   => $meta['page_url'] ?? '',
				'user_ip'    => $meta['user_ip'] ?? '',
				'user_id'    => $meta['user_id'] ?? 0,
				'utm'        => $meta['utm'] ?? array(),
			),
			'site'      => array(
				'url'  => home_url(),
				'name' => get_bloginfo( 'name' ),
			),
		);
	}

	/**
	 * Log webhook error.
	 *
	 * @since 1.0.0
	 * @param int    $form_id  Form ID.
	 * @param int    $entry_id Entry ID.
	 * @param string $error    Error message.
	 */
	private function log_error( int $form_id, int $entry_id, string $error ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'[Medici Forms] Webhook error for form %d, entry %d: %s',
					$form_id,
					$entry_id,
					$error
				)
			);
		}
	}
}

<?php
/**
 * SendGrid Provider
 *
 * @package Jexi\Providers
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Providers;

/**
 * SendGrid Class
 */
final class SendGrid implements ProviderInterface {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.sendgrid.com/v3/';

	/**
	 * Get provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'SendGrid';
	}

	/**
	 * Get provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'sendgrid';
	}

	/**
	 * Subscribe email.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public function subscribe( array $data ): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'sendgrid' );

		if ( empty( $config['api_key'] ) ) {
			return false;
		}

		$body = array(
			'list_ids' => array( $config['list_id'] ),
			'contacts' => array(
				array(
					'email'      => $data['email'],
					'first_name' => $data['name'] ?? '',
					'phone'      => $data['phone'] ?? '',
				),
			),
		);

		$response = $this->request( 'marketing/contacts', 'PUT', $body );

		return ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), array( 200, 202 ), true );
	}

	/**
	 * Test connection.
	 *
	 * @return bool
	 */
	public function test_connection(): bool {
		$response = $this->request( 'user/profile', 'GET' );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check if configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'sendgrid' );

		return ! empty( $config['api_key'] );
	}

	/**
	 * Get lists.
	 *
	 * @return array<string, string>
	 */
	public function get_lists(): array {
		$response = $this->request( 'marketing/lists', 'GET' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$lists = array();

		foreach ( ( $body['result'] ?? array() ) as $list ) {
			$lists[ $list['id'] ] = $list['name'];
		}

		return $lists;
	}

	/**
	 * Make API request.
	 *
	 * @param string               $endpoint Endpoint.
	 * @param string               $method   HTTP method.
	 * @param array<string, mixed> $body     Request body.
	 * @return array<string, mixed>|\WP_Error
	 */
	private function request( string $endpoint, string $method = 'GET', array $body = array() ): array|\WP_Error {
		$options = jexi()->get_options();
		$api_key = $options->get( 'integrations', 'sendgrid' )['api_key'] ?? '';

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		return wp_remote_request( self::API_URL . $endpoint, $args );
	}
}

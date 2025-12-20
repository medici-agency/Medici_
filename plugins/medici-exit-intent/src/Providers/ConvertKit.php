<?php
/**
 * ConvertKit Provider
 *
 * @package Jexi\Providers
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Providers;

/**
 * ConvertKit Class
 */
final class ConvertKit implements ProviderInterface {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.convertkit.com/v3/';

	/**
	 * Get provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'ConvertKit';
	}

	/**
	 * Get provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'convertkit';
	}

	/**
	 * Subscribe email.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public function subscribe( array $data ): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'convertkit' );

		if ( empty( $config['api_key'] ) || empty( $config['form_id'] ) ) {
			return false;
		}

		$body = array(
			'api_key'    => $config['api_key'],
			'email'      => $data['email'],
			'first_name' => $data['name'] ?? '',
		);

		$response = $this->request( "forms/{$config['form_id']}/subscribe", 'POST', $body );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Test connection.
	 *
	 * @return bool
	 */
	public function test_connection(): bool {
		$options = jexi()->get_options();
		$api_key = $options->get( 'integrations', 'convertkit' )['api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return false;
		}

		$response = $this->request( "account?api_secret={$api_key}", 'GET' );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check if configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'convertkit' );

		return ! empty( $config['api_key'] ) && ! empty( $config['form_id'] );
	}

	/**
	 * Get lists (forms in ConvertKit).
	 *
	 * @return array<string, string>
	 */
	public function get_lists(): array {
		$options = jexi()->get_options();
		$api_key = $options->get( 'integrations', 'convertkit' )['api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return array();
		}

		$response = $this->request( "forms?api_key={$api_key}", 'GET' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$forms = array();

		foreach ( ( $body['forms'] ?? array() ) as $form ) {
			$forms[ (string) $form['id'] ] = $form['name'];
		}

		return $forms;
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
		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		return wp_remote_request( self::API_URL . $endpoint, $args );
	}
}

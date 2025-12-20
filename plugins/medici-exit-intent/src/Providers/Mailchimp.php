<?php
/**
 * Mailchimp Provider
 *
 * @package Jexi\Providers
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Providers;

/**
 * Mailchimp Class
 */
final class Mailchimp implements ProviderInterface {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private string $api_url = '';

	/**
	 * Get provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Mailchimp';
	}

	/**
	 * Get provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'mailchimp';
	}

	/**
	 * Subscribe email.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public function subscribe( array $data ): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'mailchimp' );

		if ( empty( $config['api_key'] ) || empty( $config['list_id'] ) ) {
			return false;
		}

		$this->set_api_url( $config['api_key'] );

		$subscriber_hash = md5( strtolower( $data['email'] ) );
		$endpoint        = "lists/{$config['list_id']}/members/{$subscriber_hash}";

		$body = array(
			'email_address' => $data['email'],
			'status_if_new' => 'subscribed',
			'status'        => 'subscribed',
			'merge_fields'  => array(
				'FNAME' => $data['name'] ?? '',
				'PHONE' => $data['phone'] ?? '',
			),
		);

		if ( ! empty( $config['tags'] ) ) {
			$body['tags'] = $config['tags'];
		}

		$response = $this->request( $endpoint, 'PUT', $body );

		return ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), array( 200, 201 ), true );
	}

	/**
	 * Test connection.
	 *
	 * @return bool
	 */
	public function test_connection(): bool {
		$options = jexi()->get_options();
		$api_key = $options->get( 'integrations', 'mailchimp' )['api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return false;
		}

		$this->set_api_url( $api_key );
		$response = $this->request( 'ping', 'GET' );

		return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check if configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		$options = jexi()->get_options();
		$config  = $options->get( 'integrations', 'mailchimp' );

		return ! empty( $config['api_key'] ) && ! empty( $config['list_id'] );
	}

	/**
	 * Get lists.
	 *
	 * @return array<string, string>
	 */
	public function get_lists(): array {
		$options = jexi()->get_options();
		$api_key = $options->get( 'integrations', 'mailchimp' )['api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return array();
		}

		$this->set_api_url( $api_key );
		$response = $this->request( 'lists?count=100', 'GET' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$lists = array();

		foreach ( ( $body['lists'] ?? array() ) as $list ) {
			$lists[ $list['id'] ] = $list['name'];
		}

		return $lists;
	}

	/**
	 * Set API URL from API key.
	 *
	 * @param string $api_key API key.
	 */
	private function set_api_url( string $api_key ): void {
		$datacenter     = substr( $api_key, strpos( $api_key, '-' ) + 1 );
		$this->api_url = "https://{$datacenter}.api.mailchimp.com/3.0/";
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
		$api_key = $options->get( 'integrations', 'mailchimp' )['api_key'] ?? '';

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
				'Content-Type'  => 'application/json',
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		return wp_remote_request( $this->api_url . $endpoint, $args );
	}
}

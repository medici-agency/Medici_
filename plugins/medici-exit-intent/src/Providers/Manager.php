<?php
/**
 * Providers Manager Class
 *
 * Manages email and CRM integrations.
 * Based on WP Mail SMTP Providers architecture.
 *
 * @package Jexi\Providers
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Providers;

/**
 * Manager Class
 */
final class Manager {

	/**
	 * Registered providers.
	 *
	 * @var array<string, ProviderInterface>
	 */
	private array $providers = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_providers();
	}

	/**
	 * Register default providers.
	 *
	 * @since 1.0.0
	 */
	private function register_providers(): void {
		$this->providers = array(
			'mailchimp'  => new Mailchimp(),
			'sendgrid'   => new SendGrid(),
			'convertkit' => new ConvertKit(),
		);

		/**
		 * Filter registered providers.
		 *
		 * @since 1.0.0
		 * @param array<string, ProviderInterface> $providers Providers.
		 */
		$this->providers = apply_filters( 'jexi_providers', $this->providers );
	}

	/**
	 * Get provider by ID.
	 *
	 * @since 1.0.0
	 * @param string $id Provider ID.
	 * @return ProviderInterface|null
	 */
	public function get( string $id ): ?ProviderInterface {
		return $this->providers[ $id ] ?? null;
	}

	/**
	 * Get all providers.
	 *
	 * @since 1.0.0
	 * @return array<string, ProviderInterface>
	 */
	public function get_all(): array {
		return $this->providers;
	}

	/**
	 * Send data to configured provider.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public function send( array $data ): bool {
		$options  = jexi()->get_options();
		$provider = $options->get( 'integrations', 'email_provider' );

		if ( 'none' === $provider || ! isset( $this->providers[ $provider ] ) ) {
			return false;
		}

		try {
			$result = $this->providers[ $provider ]->subscribe( $data );

			if ( $result ) {
				/**
				 * Fires after successful subscription.
				 *
				 * @since 1.0.0
				 * @param string               $provider Provider ID.
				 * @param array<string, mixed> $data     Form data.
				 */
				do_action( 'jexi_provider_success', $provider, $data );

				jexi()->get_debug()->info( "Sent to {$provider} successfully", array( 'email' => $data['email'] ?? '' ) );
			}

			return $result;
		} catch ( \Exception $e ) {
			/**
			 * Fires on provider error.
			 *
			 * @since 1.0.0
			 * @param string     $provider Provider ID.
			 * @param \Exception $error    Exception.
			 */
			do_action( 'jexi_provider_error', $provider, $e );

			jexi()->get_debug()->error( "Provider {$provider} error: " . $e->getMessage() );

			return false;
		}
	}

	/**
	 * Test provider connection.
	 *
	 * @since 1.0.0
	 * @param string $provider_id Provider ID.
	 * @return bool
	 */
	public function test_connection( string $provider_id ): bool {
		if ( ! isset( $this->providers[ $provider_id ] ) ) {
			return false;
		}

		return $this->providers[ $provider_id ]->test_connection();
	}
}

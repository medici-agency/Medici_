<?php
/**
 * Integration Manager
 *
 * Orchestrates all lead integrations.
 * Uses the Adapter pattern to manage multiple notification channels.
 *
 * @package    Medici_Agency
 * @subpackage Lead
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration Manager Class
 *
 * Manages all lead notification integrations.
 *
 * @since 2.0.0
 */
final class IntegrationManager {

	/**
	 * Registered integrations
	 *
	 * @var array<string, IntegrationInterface>
	 */
	private array $integrations = array();

	/**
	 * Execution results
	 *
	 * @var array<string, bool>
	 */
	private array $results = array();

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->registerDefaultIntegrations();
		}

		return self::$instance;
	}

	/**
	 * Register default integrations
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerDefaultIntegrations(): void {
		$this->register( new EmailAdapter() );
		$this->register( new TelegramAdapter() );
		$this->register( new GoogleSheetsAdapter() );
	}

	/**
	 * Register an integration
	 *
	 * @since 2.0.0
	 * @param IntegrationInterface $integration Integration instance.
	 * @return self For method chaining.
	 */
	public function register( IntegrationInterface $integration ): self {
		$this->integrations[ $integration->getName() ] = $integration;
		return $this;
	}

	/**
	 * Unregister an integration
	 *
	 * @since 2.0.0
	 * @param string $name Integration name.
	 * @return self For method chaining.
	 */
	public function unregister( string $name ): self {
		unset( $this->integrations[ $name ] );
		return $this;
	}

	/**
	 * Get an integration by name
	 *
	 * @since 2.0.0
	 * @param string $name Integration name.
	 * @return IntegrationInterface|null Integration or null.
	 */
	public function get( string $name ): ?IntegrationInterface {
		return $this->integrations[ $name ] ?? null;
	}

	/**
	 * Get all registered integrations
	 *
	 * @since 2.0.0
	 * @return array<string, IntegrationInterface> All integrations.
	 */
	public function getAll(): array {
		return $this->integrations;
	}

	/**
	 * Get all enabled integrations
	 *
	 * @since 2.0.0
	 * @return array<string, IntegrationInterface> Enabled integrations.
	 */
	public function getEnabled(): array {
		return array_filter(
			$this->integrations,
			fn( IntegrationInterface $i ) => $i->isEnabled()
		);
	}

	/**
	 * Send lead to all enabled integrations
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return array<string, bool> Results by integration name.
	 */
	public function sendAll( array $data, int $lead_id ): array {
		$this->results = array();

		foreach ( $this->getEnabled() as $name => $integration ) {
			$this->results[ $name ] = $integration->send( $data, $lead_id );
		}

		return $this->results;
	}

	/**
	 * Send lead to specific integration
	 *
	 * @since 2.0.0
	 * @param string               $name    Integration name.
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return bool True on success.
	 */
	public function sendTo( string $name, array $data, int $lead_id ): bool {
		$integration = $this->get( $name );

		if ( null === $integration ) {
			return false;
		}

		if ( ! $integration->isEnabled() ) {
			return false;
		}

		return $integration->send( $data, $lead_id );
	}

	/**
	 * Get last execution results
	 *
	 * @since 2.0.0
	 * @return array<string, bool> Results by integration name.
	 */
	public function getResults(): array {
		return $this->results;
	}

	/**
	 * Check if all integrations succeeded
	 *
	 * @since 2.0.0
	 * @return bool True if all succeeded.
	 */
	public function allSucceeded(): bool {
		if ( empty( $this->results ) ) {
			return false;
		}

		return ! in_array( false, $this->results, true );
	}

	/**
	 * Get failed integrations from last execution
	 *
	 * @since 2.0.0
	 * @return array<string, string> Failed integration names with errors.
	 */
	public function getFailures(): array {
		$failures = array();

		foreach ( $this->results as $name => $success ) {
			if ( ! $success ) {
				$integration       = $this->get( $name );
				$failures[ $name ] = $integration ? $integration->getLastError() ?? 'Unknown error' : 'Integration not found';
			}
		}

		return $failures;
	}

	/**
	 * Get integration status summary
	 *
	 * @since 2.0.0
	 * @return array<string, array{enabled: bool, name: string}> Status by integration.
	 */
	public function getStatus(): array {
		$status = array();

		foreach ( $this->integrations as $name => $integration ) {
			$status[ $name ] = array(
				'name'    => $integration->getName(),
				'enabled' => $integration->isEnabled(),
			);
		}

		return $status;
	}

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

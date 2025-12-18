<?php
/**
 * Abstract Lead Integration
 *
 * Base class for all lead integration adapters.
 * Provides common functionality like error handling and logging.
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
 * Abstract Integration Class
 *
 * Base implementation for lead integrations.
 *
 * @since 2.0.0
 */
abstract class AbstractIntegration implements IntegrationInterface {

	/**
	 * Last error message
	 *
	 * @var string|null
	 */
	protected ?string $lastError = null;

	/**
	 * Get last error message
	 *
	 * @since 2.0.0
	 * @return string|null Last error message or null.
	 */
	public function getLastError(): ?string {
		return $this->lastError;
	}

	/**
	 * Set error message
	 *
	 * @since 2.0.0
	 * @param string $message Error message.
	 * @return void
	 */
	protected function setError( string $message ): void {
		$this->lastError = $message;
		$this->logError( $message );
	}

	/**
	 * Log error message
	 *
	 * @since 2.0.0
	 * @param string $message Error message.
	 * @return void
	 */
	protected function logError( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Medici Lead %s] %s',
					$this->getName(),
					$message
				)
			);
		}
	}

	/**
	 * Log success message
	 *
	 * @since 2.0.0
	 * @param string $message Success message.
	 * @return void
	 */
	protected function logSuccess( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(
				sprintf(
					'[Medici Lead %s] SUCCESS: %s',
					$this->getName(),
					$message
				)
			);
		}
	}

	/**
	 * Get option value with default
	 *
	 * @since 2.0.0
	 * @param string $option_name Option name.
	 * @param mixed  $default     Default value.
	 * @return mixed Option value.
	 */
	protected function getOption( string $option_name, $default = '' ) {
		return get_option( $option_name, $default );
	}

	/**
	 * Update option value
	 *
	 * @since 2.0.0
	 * @param string $option_name Option name.
	 * @param mixed  $value       Option value.
	 * @return bool True on success.
	 */
	protected function updateOption( string $option_name, $value ): bool {
		return update_option( $option_name, $value );
	}
}

<?php
/**
 * Lead Integration Interface
 *
 * Contract for all lead integration adapters.
 * Each integration (Email, Telegram, Google Sheets) must implement this interface.
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
 * Integration Interface
 *
 * Defines the contract for lead notification integrations.
 *
 * @since 2.0.0
 */
interface IntegrationInterface {

	/**
	 * Get integration name
	 *
	 * @since 2.0.0
	 * @return string Integration identifier.
	 */
	public function getName(): string;

	/**
	 * Check if integration is configured and enabled
	 *
	 * @since 2.0.0
	 * @return bool True if integration is ready to use.
	 */
	public function isEnabled(): bool;

	/**
	 * Send lead notification
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return bool True on success.
	 */
	public function send( array $data, int $lead_id ): bool;

	/**
	 * Get last error message
	 *
	 * @since 2.0.0
	 * @return string|null Last error message or null.
	 */
	public function getLastError(): ?string;
}

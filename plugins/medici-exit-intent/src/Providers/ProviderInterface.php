<?php
/**
 * Provider Interface
 *
 * Contract for email/CRM providers.
 *
 * @package Jexi\Providers
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Providers;

/**
 * ProviderInterface
 */
interface ProviderInterface {

	/**
	 * Get provider name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get provider slug.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Subscribe email to list.
	 *
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public function subscribe( array $data ): bool;

	/**
	 * Test provider connection.
	 *
	 * @return bool
	 */
	public function test_connection(): bool;

	/**
	 * Check if provider is configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Get available lists/audiences.
	 *
	 * @return array<string, string>
	 */
	public function get_lists(): array;
}

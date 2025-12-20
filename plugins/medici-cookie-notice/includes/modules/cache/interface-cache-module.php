<?php
/**
 * Interface for cache module implementations
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Modules\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Module Interface
 *
 * All cache compatibility modules must implement this interface.
 */
interface Cache_Module_Interface {

	/**
	 * Check if the cache plugin is active
	 *
	 * @return bool
	 */
	public function is_active(): bool;

	/**
	 * Get the cache plugin name
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get the cache plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Load hooks for this cache module
	 *
	 * @return void
	 */
	public function load_hooks(): void;

	/**
	 * Clear cache for this plugin
	 *
	 * @return bool
	 */
	public function clear_cache(): bool;

	/**
	 * Exclude scripts from optimization
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_scripts( array $excludes ): array;

	/**
	 * Exclude inline JavaScript code from optimization
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_inline_js( array $excludes ): array;
}

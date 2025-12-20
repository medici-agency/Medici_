<?php
/**
 * WP Super Cache Compatibility Module
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
 * WP Super Cache Module
 */
class WP_Super_Cache implements Cache_Module_Interface {

	/**
	 * Check if WP Super Cache is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return function_exists( 'wp_cache_clear_cache' );
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WP Super Cache';
	}

	/**
	 * Get plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'wp-super-cache';
	}

	/**
	 * Load hooks
	 *
	 * @return void
	 */
	public function load_hooks(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'mcn_settings_updated', [ $this, 'clear_cache' ] );

		// Add cookie to cache key variation
		add_filter( 'wpsc_cachedata', [ $this, 'add_cache_variation' ] );
	}

	/**
	 * Clear cache
	 *
	 * @return bool
	 */
	public function clear_cache(): bool {
		if ( ! $this->is_active() ) {
			return false;
		}

		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			return true;
		}

		return false;
	}

	/**
	 * Exclude scripts
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_scripts( array $excludes ): array {
		$excludes[] = 'medici-cookie-notice';
		$excludes[] = 'mcn-frontend';

		return $excludes;
	}

	/**
	 * Exclude inline JS
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_inline_js( array $excludes ): array {
		$excludes[] = 'mcnConfig';
		$excludes[] = 'mcnConsentConfig';

		return $excludes;
	}

	/**
	 * Add consent cookie to cache key variation
	 *
	 * @param array<string, mixed> $cache_data Cache data.
	 * @return array<string, mixed>
	 */
	public function add_cache_variation( array $cache_data ): array {
		// Vary cache by consent status
		$consent_cookie = $_COOKIE['mcn_consent'] ?? '';
		if ( ! empty( $consent_cookie ) ) {
			$cache_data['mcn_consent'] = $consent_cookie;
		}

		return $cache_data;
	}
}

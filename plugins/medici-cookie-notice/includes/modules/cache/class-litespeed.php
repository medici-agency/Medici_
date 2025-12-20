<?php
/**
 * LiteSpeed Cache Compatibility Module
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
 * LiteSpeed Cache Module
 */
class LiteSpeed implements Cache_Module_Interface {

	/**
	 * Check if LiteSpeed Cache is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed_Cache' );
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'LiteSpeed Cache';
	}

	/**
	 * Get plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'litespeed-cache';
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

		// Exclude from JS optimization
		add_filter( 'litespeed_optimize_js_excludes', [ $this, 'exclude_scripts' ] );
		add_filter( 'litespeed_optm_js_defer_exc', [ $this, 'exclude_scripts' ] );

		// Exclude from CSS optimization
		add_filter( 'litespeed_optimize_css_excludes', [ $this, 'exclude_css' ] );

		// Vary cache by cookie consent
		add_action( 'litespeed_vary_add', [ $this, 'add_vary_cookie' ] );
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

		// LiteSpeed Cache API
		if ( class_exists( 'LiteSpeed\Purge' ) ) {
			\LiteSpeed\Purge::purge_all();
			return true;
		}

		// Legacy API
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			\LiteSpeed_Cache_API::purge_all();
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
		$excludes[] = 'mcnConfig';

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
	 * Exclude CSS
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_css( array $excludes ): array {
		$excludes[] = 'medici-cookie-notice';
		$excludes[] = 'mcn-frontend';

		return $excludes;
	}

	/**
	 * Add vary cookie for cache segmentation
	 *
	 * @return void
	 */
	public function add_vary_cookie(): void {
		if ( class_exists( 'LiteSpeed\Vary' ) ) {
			\LiteSpeed\Vary::add( 'mcn_consent' );
		}
	}
}

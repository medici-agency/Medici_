<?php
/**
 * Autoptimize Cache Compatibility Module
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
 * Autoptimize Cache Module
 */
class Autoptimize implements Cache_Module_Interface {

	/**
	 * Check if Autoptimize is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) || class_exists( 'autoptimizeCache' );
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Autoptimize';
	}

	/**
	 * Get plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'autoptimize';
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
		add_filter( 'autoptimize_filter_js_exclude', [ $this, 'exclude_scripts_string' ] );

		// Exclude from CSS optimization
		add_filter( 'autoptimize_filter_css_exclude', [ $this, 'exclude_css_string' ] );

		// Exclude inline JS
		add_filter( 'autoptimize_filter_js_dontmove', [ $this, 'exclude_inline_scripts' ] );
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

		if ( class_exists( 'autoptimizeCache' ) ) {
			\autoptimizeCache::clearall();
			return true;
		}

		return false;
	}

	/**
	 * Exclude scripts (array format)
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
	 * Exclude scripts (string format for Autoptimize filter)
	 *
	 * @param string $excludes Comma-separated exclusions.
	 * @return string
	 */
	public function exclude_scripts_string( string $excludes ): string {
		$additions = 'medici-cookie-notice, mcn-frontend, mcnConfig';

		if ( ! empty( $excludes ) ) {
			return $excludes . ', ' . $additions;
		}

		return $additions;
	}

	/**
	 * Exclude CSS (string format)
	 *
	 * @param string $excludes Comma-separated exclusions.
	 * @return string
	 */
	public function exclude_css_string( string $excludes ): string {
		$additions = 'medici-cookie-notice, mcn-frontend';

		if ( ! empty( $excludes ) ) {
			return $excludes . ', ' . $additions;
		}

		return $additions;
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
	 * Exclude inline scripts from moving
	 *
	 * @param string $dontmove Current exclusions.
	 * @return string
	 */
	public function exclude_inline_scripts( string $dontmove ): string {
		$additions = 'mcnConfig, mcnConsentConfig';

		if ( ! empty( $dontmove ) ) {
			return $dontmove . ', ' . $additions;
		}

		return $additions;
	}
}

<?php
/**
 * W3 Total Cache Compatibility Module
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
 * W3 Total Cache Module
 */
class W3_Total_Cache implements Cache_Module_Interface {

	/**
	 * Check if W3 Total Cache is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'W3TC' ) || function_exists( 'w3tc_flush_all' );
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'W3 Total Cache';
	}

	/**
	 * Get plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'w3-total-cache';
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

		// W3TC minify exclusions
		add_filter( 'w3tc_minify_js_do_tag_minification', [ $this, 'maybe_exclude_script' ], 10, 3 );
		add_filter( 'w3tc_minify_css_do_tag_minification', [ $this, 'maybe_exclude_style' ], 10, 3 );
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

		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
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
	 * Maybe exclude script from minification
	 *
	 * @param bool   $do_minify Whether to minify.
	 * @param string $script_tag The script tag.
	 * @param string $file The file URL.
	 * @return bool
	 */
	public function maybe_exclude_script( bool $do_minify, string $script_tag, string $file ): bool {
		if ( str_contains( $file, 'medici-cookie-notice' ) || str_contains( $file, 'mcn-frontend' ) ) {
			return false;
		}

		if ( str_contains( $script_tag, 'mcnConfig' ) || str_contains( $script_tag, 'mcnConsentConfig' ) ) {
			return false;
		}

		return $do_minify;
	}

	/**
	 * Maybe exclude style from minification
	 *
	 * @param bool   $do_minify Whether to minify.
	 * @param string $style_tag The style tag.
	 * @param string $file The file URL.
	 * @return bool
	 */
	public function maybe_exclude_style( bool $do_minify, string $style_tag, string $file ): bool {
		if ( str_contains( $file, 'medici-cookie-notice' ) || str_contains( $file, 'mcn-frontend' ) ) {
			return false;
		}

		return $do_minify;
	}
}

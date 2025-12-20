<?php
/**
 * WP Rocket Cache Compatibility Module
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
 * WP Rocket Cache Module
 *
 * Provides full compatibility with WP Rocket including:
 * - Cache clearing on settings update
 * - JS defer/delay exclusions
 * - Inline JS exclusions
 * - Minification exclusions
 */
class WP_Rocket implements Cache_Module_Interface {

	/**
	 * Script handles to exclude
	 *
	 * @var array<int, string>
	 */
	private array $script_handles = [
		'mcn-frontend',
		'mcn-cookie-notice',
		'medici-cookie-notice',
	];

	/**
	 * Inline code patterns to exclude
	 *
	 * @var array<int, string>
	 */
	private array $inline_patterns = [
		'mcnConfig',
		'mcnConsentConfig',
		'medici-cookie-notice',
		'cookie_notice_options',
		'dataLayer',
	];

	/**
	 * Check if WP Rocket is active
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return defined( 'WP_ROCKET_VERSION' );
	}

	/**
	 * Get plugin name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WP Rocket';
	}

	/**
	 * Get plugin slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'wp-rocket';
	}

	/**
	 * Load all WP Rocket hooks
	 *
	 * @return void
	 */
	public function load_hooks(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		// Cache clearing on settings update
		add_action( 'mcn_settings_updated', [ $this, 'clear_cache' ] );
		add_action( 'mcn_consent_saved', [ $this, 'maybe_clear_cache' ] );

		// Exclude from defer
		add_filter( 'rocket_exclude_defer_js', [ $this, 'exclude_scripts' ] );

		// Exclude from combine
		add_filter( 'rocket_exclude_js', [ $this, 'exclude_scripts' ] );

		// Exclude from delay JS
		add_filter( 'rocket_delay_js_exclusions', [ $this, 'exclude_scripts' ] );
		add_filter( 'rocket_delay_js_exclusions', [ $this, 'exclude_inline_js' ] );

		// Exclude inline JS from defer
		add_filter( 'rocket_defer_inline_exclusions', [ $this, 'exclude_inline_js' ] );

		// Exclude inline JS content
		add_filter( 'rocket_excluded_inline_js_content', [ $this, 'exclude_inline_js' ] );

		// Exclude from minification
		add_filter( 'rocket_minify_excluded_external_js', [ $this, 'exclude_scripts' ] );

		// Preload exclusions (for critical CSS/JS)
		add_filter( 'rocket_exclude_async_css', [ $this, 'exclude_css' ] );
	}

	/**
	 * Clear WP Rocket cache
	 *
	 * @return bool
	 */
	public function clear_cache(): bool {
		if ( ! $this->is_active() ) {
			return false;
		}

		$cleared = false;

		// Clear domain cache
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			$cleared = true;
		}

		// Clear minified files
		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify( [ 'js', 'css' ] );
			$cleared = true;
		}

		// Clear cache busting
		if ( function_exists( 'rocket_clean_cache_busting' ) ) {
			rocket_clean_cache_busting();
		}

		return $cleared;
	}

	/**
	 * Maybe clear cache on consent change (rate limited)
	 *
	 * @return void
	 */
	public function maybe_clear_cache(): void {
		// Rate limit cache clearing - max once per 5 minutes
		$transient_key = 'mcn_wp_rocket_cache_cleared';
		if ( get_transient( $transient_key ) ) {
			return;
		}

		$this->clear_cache();
		set_transient( $transient_key, 1, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Exclude scripts from optimization
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_scripts( array $excludes ): array {
		foreach ( $this->script_handles as $handle ) {
			if ( ! in_array( $handle, $excludes, true ) ) {
				$excludes[] = $handle;
			}
		}

		// Also exclude the actual file paths
		$excludes[] = 'medici-cookie-notice/assets/js/frontend';
		$excludes[] = 'mcn-frontend';

		return $excludes;
	}

	/**
	 * Exclude inline JavaScript from optimization
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_inline_js( array $excludes ): array {
		foreach ( $this->inline_patterns as $pattern ) {
			if ( ! in_array( $pattern, $excludes, true ) ) {
				$excludes[] = $pattern;
			}
		}

		return $excludes;
	}

	/**
	 * Exclude CSS from async loading
	 *
	 * @param array<int, string> $excludes Current exclusions.
	 * @return array<int, string>
	 */
	public function exclude_css( array $excludes ): array {
		$excludes[] = 'medici-cookie-notice/assets/css/frontend';
		$excludes[] = 'mcn-frontend';

		return $excludes;
	}
}

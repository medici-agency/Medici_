<?php
/**
 * Cache Compatibility Module Orchestrator
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Modules;

use Medici\CookieNotice\Cookie_Notice;
use Medici\CookieNotice\Modules\Cache\Cache_Module_Interface;
use Medici\CookieNotice\Modules\Cache\WP_Rocket;
use Medici\CookieNotice\Modules\Cache\LiteSpeed;
use Medici\CookieNotice\Modules\Cache\Autoptimize;
use Medici\CookieNotice\Modules\Cache\W3_Total_Cache;
use Medici\CookieNotice\Modules\Cache\WP_Super_Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache Compatibility Manager
 *
 * Detects active caching plugins and loads appropriate compatibility modules.
 */
class Cache_Compatibility {

	/**
	 * Reference to main plugin
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Registered cache modules
	 *
	 * @var array<string, Cache_Module_Interface>
	 */
	private array $modules = [];

	/**
	 * Active modules (detected as running)
	 *
	 * @var array<string, Cache_Module_Interface>
	 */
	private array $active_modules = [];

	/**
	 * Constructor
	 *
	 * @param Cookie_Notice $plugin Main plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize cache compatibility
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! $this->plugin->get_option( 'cache_compatibility' ) ) {
			return;
		}

		$this->register_modules();
		$this->detect_active_plugins();
		$this->load_active_modules();

		// Add Vary header for caching
		add_action( 'send_headers', [ $this, 'add_vary_header' ] );

		// Admin status display
		add_action( 'mcn_admin_after_settings', [ $this, 'render_cache_status' ] );
	}

	/**
	 * Register all available cache modules
	 *
	 * @return void
	 */
	private function register_modules(): void {
		// Register built-in modules
		$this->register_module( new WP_Rocket() );
		$this->register_module( new LiteSpeed() );
		$this->register_module( new Autoptimize() );
		$this->register_module( new W3_Total_Cache() );
		$this->register_module( new WP_Super_Cache() );

		/**
		 * Allow third-party modules to be registered
		 *
		 * @param Cache_Compatibility $this The cache compatibility instance.
		 */
		do_action( 'mcn_register_cache_modules', $this );
	}

	/**
	 * Register a single cache module
	 *
	 * @param Cache_Module_Interface $module The module to register.
	 * @return void
	 */
	public function register_module( Cache_Module_Interface $module ): void {
		$this->modules[ $module->get_slug() ] = $module;
	}

	/**
	 * Detect which cache plugins are active
	 *
	 * @return void
	 */
	private function detect_active_plugins(): void {
		foreach ( $this->modules as $slug => $module ) {
			if ( $module->is_active() ) {
				$this->active_modules[ $slug ] = $module;
			}
		}
	}

	/**
	 * Load hooks for all active modules
	 *
	 * @return void
	 */
	private function load_active_modules(): void {
		foreach ( $this->active_modules as $module ) {
			$module->load_hooks();
		}
	}

	/**
	 * Get list of active cache modules
	 *
	 * @return array<string, Cache_Module_Interface>
	 */
	public function get_active_modules(): array {
		return $this->active_modules;
	}

	/**
	 * Get list of active module names
	 *
	 * @return array<int, string>
	 */
	public function get_active_module_names(): array {
		return array_map(
			fn( Cache_Module_Interface $module ): string => $module->get_name(),
			$this->active_modules
		);
	}

	/**
	 * Clear all caches
	 *
	 * @return array<string, bool> Results per module.
	 */
	public function clear_all_caches(): array {
		$results = [];

		foreach ( $this->active_modules as $slug => $module ) {
			$results[ $slug ] = $module->clear_cache();
		}

		// WordPress object cache
		wp_cache_flush();

		// WordPress transients (consent-related)
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_mcn_%'"
		);

		/**
		 * Action after all caches cleared
		 *
		 * @param array<string, bool> $results Results per module.
		 */
		do_action( 'mcn_caches_cleared', $results );

		return $results;
	}

	/**
	 * Add Vary: Cookie header for proper cache segmentation
	 *
	 * @return void
	 */
	public function add_vary_header(): void {
		if ( headers_sent() ) {
			return;
		}

		// Only on frontend
		if ( is_admin() ) {
			return;
		}

		header( 'Vary: Cookie', false );
	}

	/**
	 * Render cache status in admin
	 *
	 * @return void
	 */
	public function render_cache_status(): void {
		$active_names = $this->get_active_module_names();

		if ( empty( $active_names ) ) {
			return;
		}

		$names_list = implode( ', ', $active_names );
		?>
		<div class="mcn-cache-status notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Сумісність з кешем:', 'medici-cookie-notice' ); ?></strong>
				<?php
				printf(
					/* translators: %s: List of active cache plugins */
					esc_html__( 'Виявлено та інтегровано: %s', 'medici-cookie-notice' ),
					esc_html( $names_list )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if a specific cache plugin is active
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public function is_cache_plugin_active( string $slug ): bool {
		return isset( $this->active_modules[ $slug ] );
	}

	/**
	 * Get a specific module by slug
	 *
	 * @param string $slug Module slug.
	 * @return Cache_Module_Interface|null
	 */
	public function get_module( string $slug ): ?Cache_Module_Interface {
		return $this->modules[ $slug ] ?? null;
	}

	/**
	 * Get cache status info for debugging
	 *
	 * @return array<string, mixed>
	 */
	public function get_debug_info(): array {
		$info = [
			'enabled'        => $this->plugin->get_option( 'cache_compatibility' ),
			'registered'     => array_keys( $this->modules ),
			'active'         => array_keys( $this->active_modules ),
			'active_names'   => $this->get_active_module_names(),
			'vary_header'    => true,
		];

		foreach ( $this->active_modules as $slug => $module ) {
			$info['modules'][ $slug ] = [
				'name'   => $module->get_name(),
				'active' => $module->is_active(),
			];
		}

		return $info;
	}
}

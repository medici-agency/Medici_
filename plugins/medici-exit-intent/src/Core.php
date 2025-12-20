<?php
/**
 * Core Plugin Class
 *
 * Main orchestrator for all plugin functionality.
 * Based on WP Mail SMTP Core.php architecture.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Core Class
 *
 * Singleton pattern with lazy-loaded modules.
 */
final class Core {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Loader instance.
	 *
	 * @var Loader|null
	 */
	private ?Loader $loader = null;

	/**
	 * Options instance.
	 *
	 * @var Options|null
	 */
	private ?Options $options = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin\Area|null
	 */
	private ?Admin\Area $admin = null;

	/**
	 * Public instance.
	 *
	 * @var Frontend|null
	 */
	private ?Frontend $frontend = null;

	/**
	 * Assets instance.
	 *
	 * @var Assets|null
	 */
	private ?Assets $assets = null;

	/**
	 * Templates instance.
	 *
	 * @var Templates\Manager|null
	 */
	private ?Templates\Manager $templates = null;

	/**
	 * Analytics instance.
	 *
	 * @var Analytics\Tracker|null
	 */
	private ?Analytics\Tracker $analytics = null;

	/**
	 * Providers manager instance.
	 *
	 * @var Providers\Manager|null
	 */
	private ?Providers\Manager $providers = null;

	/**
	 * Debug instance.
	 *
	 * @var Debug|null
	 */
	private ?Debug $debug = null;

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init(): void {
		$this->register_hooks();

		/**
		 * Fires after the plugin is fully initialized.
		 *
		 * @since 1.0.0
		 * @param Core $core The core plugin instance.
		 */
		do_action( 'jexi_loaded', $this );
	}

	/**
	 * Register all hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks(): void {
		// Admin hooks.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this->get_admin(), 'register_menu' ) );
			add_action( 'admin_init', array( $this->get_admin(), 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this->get_assets(), 'enqueue_admin' ) );
			add_action( 'admin_init', array( $this, 'maybe_redirect_after_activation' ) );

			// Plugin action links.
			add_filter(
				'plugin_action_links_' . JEXI_PLUGIN_BASENAME,
				array( $this->get_admin(), 'add_action_links' )
			);
		}

		// Frontend hooks.
		add_action( 'wp_enqueue_scripts', array( $this->get_assets(), 'enqueue_frontend' ) );
		add_action( 'wp_footer', array( $this->get_frontend(), 'render_popup' ), 100 );
		add_action( 'init', array( $this->get_frontend(), 'register_shortcodes' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_jexi_submit', array( $this->get_frontend(), 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_jexi_submit', array( $this->get_frontend(), 'handle_submission' ) );
		add_action( 'wp_ajax_jexi_track', array( $this->get_analytics(), 'handle_tracking' ) );
		add_action( 'wp_ajax_nopriv_jexi_track', array( $this->get_analytics(), 'handle_tracking' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Cron hooks.
		add_action( 'jexi_daily_cleanup', array( $this->get_analytics(), 'daily_cleanup' ) );
		add_action( 'jexi_analytics_aggregate', array( $this->get_analytics(), 'aggregate_stats' ) );

		// Schedule cron events.
		if ( ! wp_next_scheduled( 'jexi_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'jexi_daily_cleanup' );
		}

		/**
		 * Fires after all hooks are registered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'jexi_hooks_registered' );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'jexi/v1',
			'/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->get_frontend(), 'rest_submit' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'jexi/v1',
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->get_analytics(), 'rest_get_stats' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'jexi/v1',
			'/templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->get_templates(), 'rest_get_templates' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Redirect to settings page after activation.
	 *
	 * @since 1.0.0
	 */
	public function maybe_redirect_after_activation(): void {
		if ( get_transient( 'jexi_activation_redirect' ) ) {
			delete_transient( 'jexi_activation_redirect' );

			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=jexi-settings' ) );
				exit;
			}
		}
	}

	/**
	 * Get Loader instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Loader
	 */
	public function get_loader(): Loader {
		if ( null === $this->loader ) {
			$this->loader = new Loader();
		}

		return $this->loader;
	}

	/**
	 * Get Options instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Options
	 */
	public function get_options(): Options {
		if ( null === $this->options ) {
			$this->options = new Options();
		}

		/**
		 * Filter the Options instance.
		 *
		 * @since 1.0.0
		 * @param Options $options The options instance.
		 */
		return apply_filters( 'jexi_get_options', $this->options );
	}

	/**
	 * Get Admin instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Admin\Area
	 */
	public function get_admin(): Admin\Area {
		if ( null === $this->admin ) {
			$this->admin = new Admin\Area();
		}

		return $this->admin;
	}

	/**
	 * Get Frontend instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Frontend
	 */
	public function get_frontend(): Frontend {
		if ( null === $this->frontend ) {
			$this->frontend = new Frontend();
		}

		/**
		 * Filter the Frontend instance.
		 *
		 * @since 1.0.0
		 * @param Frontend $frontend The frontend instance.
		 */
		return apply_filters( 'jexi_get_frontend', $this->frontend );
	}

	/**
	 * Get Assets instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Assets
	 */
	public function get_assets(): Assets {
		if ( null === $this->assets ) {
			$this->assets = new Assets();
		}

		return $this->assets;
	}

	/**
	 * Get Templates Manager instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Templates\Manager
	 */
	public function get_templates(): Templates\Manager {
		if ( null === $this->templates ) {
			$this->templates = new Templates\Manager();
		}

		/**
		 * Filter the Templates Manager instance.
		 *
		 * @since 1.0.0
		 * @param Templates\Manager $templates The templates manager instance.
		 */
		return apply_filters( 'jexi_get_templates', $this->templates );
	}

	/**
	 * Get Analytics Tracker instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Analytics\Tracker
	 */
	public function get_analytics(): Analytics\Tracker {
		if ( null === $this->analytics ) {
			$this->analytics = new Analytics\Tracker();
		}

		/**
		 * Filter the Analytics Tracker instance.
		 *
		 * @since 1.0.0
		 * @param Analytics\Tracker $analytics The analytics tracker instance.
		 */
		return apply_filters( 'jexi_get_analytics', $this->analytics );
	}

	/**
	 * Get Providers Manager instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Providers\Manager
	 */
	public function get_providers(): Providers\Manager {
		if ( null === $this->providers ) {
			$this->providers = new Providers\Manager();
		}

		/**
		 * Filter the Providers Manager instance.
		 *
		 * @since 1.0.0
		 * @param Providers\Manager $providers The providers manager instance.
		 */
		return apply_filters( 'jexi_get_providers', $this->providers );
	}

	/**
	 * Get Debug instance (lazy loading).
	 *
	 * @since 1.0.0
	 * @return Debug
	 */
	public function get_debug(): Debug {
		if ( null === $this->debug ) {
			$this->debug = new Debug();
		}

		return $this->debug;
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_debug(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Get plugin URL.
	 *
	 * @since 1.0.0
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function get_url( string $path = '' ): string {
		return JEXI_PLUGIN_URL . ltrim( $path, '/' );
	}

	/**
	 * Get plugin directory path.
	 *
	 * @since 1.0.0
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function get_path( string $path = '' ): string {
		return JEXI_PLUGIN_DIR . ltrim( $path, '/' );
	}
}

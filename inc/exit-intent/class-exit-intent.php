<?php
declare(strict_types=1);

/**
 * Exit-Intent Main Class
 *
 * Core exit-intent popup functionality
 * Based on WordPress Plugin Boilerplate architecture
 *
 * HYBRID SOLUTION:
 * - bioEp (beeker1121) - exit-intent detection + cookie tracking (30 days)
 * - GenerateBlocks Overlay Panel - design + animations
 * - Events API - form handling + Lead CPT integration
 *
 * @package    Medici
 * @subpackage Exit_Intent
 * @version    1.1.0
 * @link       https://github.com/beeker1121/exit-intent-popup
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit_Intent Class
 *
 * Main class that orchestrates all exit-intent functionality
 */
class Exit_Intent {

	/**
	 * Loader instance
	 *
	 * @var Exit_Intent_Loader
	 */
	protected Exit_Intent_Loader $loader;

	/**
	 * Assets manager instance
	 *
	 * @var Exit_Intent_Assets
	 */
	protected Exit_Intent_Assets $assets;

	/**
	 * Public functionality instance
	 *
	 * @var Exit_Intent_Public
	 */
	protected Exit_Intent_Public $public;

	/**
	 * Exit-Intent version
	 *
	 * @var string
	 */
	private string $version = '1.1.0';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Load required dependencies
	 */
	private function load_dependencies(): void {
		$base_path = get_stylesheet_directory() . '/inc/exit-intent/';

		// Load Loader class
		require_once $base_path . 'class-exit-intent-loader.php';

		// Load Assets manager
		require_once $base_path . 'class-exit-intent-assets.php';

		// Load Public functionality
		require_once $base_path . 'class-exit-intent-public.php';

		// Instantiate classes
		$this->loader = new Exit_Intent_Loader();
		$this->assets = new Exit_Intent_Assets();
		$this->public = new Exit_Intent_Public();
	}

	/**
	 * Define hooks using Loader pattern
	 */
	private function define_hooks(): void {
		// Register shortcode (init)
		$this->loader->add_action(
			'init',
			$this->public,
			'register_shortcodes',
			10
		);

		// Fix overlay panel attributes (early in wp_head)
		$this->loader->add_action(
			'wp_head',
			$this->public,
			'fix_overlay_panel_attributes',
			1 // Priority 1 - execute early
		);

		// Enqueue styles
		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->assets,
			'enqueue_styles',
			10
		);

		// Enqueue scripts
		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->assets,
			'enqueue_scripts',
			10
		);

		// Add inline configuration
		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->public,
			'add_inline_config',
			20 // After scripts enqueued
		);

		// Add body class
		$this->loader->add_filter(
			'body_class',
			$this->public,
			'add_body_class',
			10,
			1
		);

		// Display debug info (wp_footer)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->loader->add_action(
				'wp_footer',
				$this->public,
				'display_debug_info',
				999
			);
		}
	}

	/**
	 * Run the loader to register all hooks with WordPress
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get loader instance
	 *
	 * @return Exit_Intent_Loader
	 */
	public function get_loader(): Exit_Intent_Loader {
		return $this->loader;
	}

	/**
	 * Get assets manager instance
	 *
	 * @return Exit_Intent_Assets
	 */
	public function get_assets(): Exit_Intent_Assets {
		return $this->assets;
	}

	/**
	 * Get public functionality instance
	 *
	 * @return Exit_Intent_Public
	 */
	public function get_public(): Exit_Intent_Public {
		return $this->public;
	}
}

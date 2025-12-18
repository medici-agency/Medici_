<?php
declare(strict_types=1);

/**
 * Exit-Intent Public Class
 *
 * Handles all public-facing functionality for exit-intent popup
 *
 * @package    Medici
 * @subpackage Exit_Intent
 * @version    1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit_Intent_Public Class
 *
 * Public-facing functionality:
 * - Form rendering (via GenerateBlocks Overlay Panel)
 * - JavaScript configuration
 * - Integration with Events API
 */
class Exit_Intent_Public {

	/**
	 * Exit-Intent configuration
	 *
	 * @var array<string, mixed>
	 */
	private array $config;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->config = array(
			'panel_id'   => 'medici-exit-intent-panel',
			'cookie_exp' => 30, // Days
			'delay'      => 2,  // Seconds
			'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
		);
	}

	/**
	 * Add inline configuration script
	 *
	 * Passes PHP configuration to JavaScript
	 */
	public function add_inline_config(): void {
		// Check if hybrid script is enqueued
		if ( ! wp_script_is( 'medici-exit-intent-hybrid', 'enqueued' ) ) {
			return;
		}

		// Pass configuration to JavaScript
		$config_js = sprintf(
			'if (typeof window.MediciExitIntent !== "undefined") {
				window.MediciExitIntent.config.overlayPanelId = %s;
				window.MediciExitIntent.config.cookieExp = %d;
				window.MediciExitIntent.config.delay = %d;
				window.MediciExitIntent.config.debug = %s;
			}',
			wp_json_encode( $this->config['panel_id'] ),
			$this->config['cookie_exp'],
			$this->config['delay'],
			$this->config['debug'] ? 'true' : 'false'
		);

		wp_add_inline_script( 'medici-exit-intent-hybrid', $config_js, 'after' );
	}

	/**
	 * Get configuration
	 *
	 * @return array<string, mixed>
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Update configuration
	 *
	 * @param array<string, mixed> $new_config
	 */
	public function set_config( array $new_config ): void {
		$this->config = array_merge( $this->config, $new_config );
	}

	/**
	 * Add body class for exit-intent
	 *
	 * @param array<int, string> $classes
	 * @return array<int, string>
	 */
	public function add_body_class( array $classes ): array {
		// Add class only on desktop (> 1024px detected by JavaScript)
		$classes[] = 'has-exit-intent';

		return $classes;
	}

	/**
	 * Display debug information (if WP_DEBUG enabled)
	 */
	public function display_debug_info(): void {
		if ( ! $this->config['debug'] ) {
			return;
		}

		echo '<!-- Exit-Intent Debug Info -->' . "\n";
		echo '<!-- Panel ID: ' . esc_html( $this->config['panel_id'] ) . ' -->' . "\n";
		echo '<!-- Cookie Exp: ' . esc_html( (string) $this->config['cookie_exp'] ) . ' days -->' . "\n";
		echo '<!-- Delay: ' . esc_html( (string) $this->config['delay'] ) . ' seconds -->' . "\n";
		echo '<!-- bioEp loaded: ' . ( wp_script_is( 'medici-bioep', 'enqueued' ) ? 'YES' : 'NO' ) . ' -->' . "\n";
		echo '<!-- Hybrid loaded: ' . ( wp_script_is( 'medici-exit-intent-hybrid', 'enqueued' ) ? 'YES' : 'NO' ) . ' -->' . "\n";
		echo '<!-- Form loaded: ' . ( wp_script_is( 'medici-exit-intent-form', 'enqueued' ) ? 'YES' : 'NO' ) . ' -->' . "\n";
	}
}

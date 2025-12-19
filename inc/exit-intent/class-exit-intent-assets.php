<?php
declare(strict_types=1);

/**
 * Exit-Intent Assets Manager
 *
 * Handles enqueueing of styles and scripts for exit-intent popup
 *
 * @package    Medici
 * @subpackage Exit_Intent
 * @version    1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit_Intent_Assets Class
 *
 * Manages all CSS and JavaScript assets for exit-intent functionality
 */
class Exit_Intent_Assets {

	/**
	 * Theme directory path
	 *
	 * @var string
	 */
	private string $theme_dir;

	/**
	 * Theme URI
	 *
	 * @var string
	 */
	private string $theme_uri;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->theme_dir = get_stylesheet_directory();
		$this->theme_uri = get_stylesheet_directory_uri();
	}

	/**
	 * Enqueue styles
	 *
	 * Uses inline CSS to bypass optimization plugins that defer external stylesheets.
	 * This ensures exit-intent styles are always loaded, even when CSS optimizers
	 * move external <link> tags into <noscript> blocks.
	 */
	public function enqueue_styles(): void {
		$css_path = $this->theme_dir . '/css/components/exit-intent-overlay.css';

		if ( ! file_exists( $css_path ) ) {
			return;
		}

		$handle = 'medici-exit-intent-overlay-inline';

		// Register empty style handle
		wp_register_style( $handle, false, array(), (string) filemtime( $css_path ) );
		wp_enqueue_style( $handle );

		// Add inline CSS from file
		$css = file_get_contents( $css_path );
		if ( is_string( $css ) && '' !== $css ) {
			wp_add_inline_style( $handle, $css );
		}
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts(): void {
		// 1. bioEp library (beeker1121) - exit-intent detection
		$this->enqueue_bioep();

		// 2. Overlay handler - form handling + overlay trigger + Events API
		$this->enqueue_overlay_handler();
	}

	/**
	 * Enqueue bioEp library
	 */
	private function enqueue_bioep(): void {
		$bioep_path = $this->theme_dir . '/js/vendor/bioep.min.js';

		if ( ! file_exists( $bioep_path ) ) {
			return;
		}

		wp_enqueue_script(
			'medici-bioep',
			$this->theme_uri . '/js/vendor/bioep.min.js',
			array(), // No dependencies - vanilla JS
			(string) filemtime( $bioep_path ),
			true // Load in footer
		);
	}

	/**
	 * Enqueue overlay handler script
	 *
	 * Handles form submission, overlay trigger, and Events API integration
	 */
	private function enqueue_overlay_handler(): void {
		$overlay_path = $this->theme_dir . '/js/exit-intent-overlay.js';

		if ( ! file_exists( $overlay_path ) ) {
			return;
		}

		wp_enqueue_script(
			'medici-exit-intent-overlay',
			$this->theme_uri . '/js/exit-intent-overlay.js',
			array( 'medici-bioep', 'medici-events' ), // Depends on bioEp + Events API
			(string) filemtime( $overlay_path ),
			true // Load in footer
		);
	}

	/**
	 * Check if should load assets (desktop only, > 1024px)
	 *
	 * @return bool
	 */
	public function should_load_assets(): bool {
		// Always load (JavaScript handles screen width detection)
		// bioEp checks window.innerWidth > 1024 internally
		return true;
	}
}

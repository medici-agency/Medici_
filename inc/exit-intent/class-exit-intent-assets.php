<?php
declare(strict_types=1);

/**
 * Exit-Intent Assets Manager
 *
 * Handles enqueueing of styles and scripts for exit-intent popup
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
	 */
	public function enqueue_styles(): void {
		$css_path = $this->theme_dir . '/css/components/exit-intent-overlay.css';

		if ( ! file_exists( $css_path ) ) {
			return;
		}

		wp_enqueue_style(
			'medici-exit-intent-overlay',
			$this->theme_uri . '/css/components/exit-intent-overlay.css',
			array(), // No dependencies
			(string) filemtime( $css_path ),
			'all'
		);
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts(): void {
		// 1. bioEp library (beeker1121) - exit-intent detection
		$this->enqueue_bioep();

		// 2. Hybrid adapter - connects bioEp with GenerateBlocks Overlay Panel
		$this->enqueue_hybrid_adapter();

		// 3. Form handler - Events API integration
		$this->enqueue_form_handler();
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
	 * Enqueue hybrid adapter script
	 */
	private function enqueue_hybrid_adapter(): void {
		$hybrid_path = $this->theme_dir . '/js/exit-intent-hybrid.js';

		if ( ! file_exists( $hybrid_path ) ) {
			return;
		}

		wp_enqueue_script(
			'medici-exit-intent-hybrid',
			$this->theme_uri . '/js/exit-intent-hybrid.js',
			array( 'medici-bioep' ), // Depends on bioEp
			(string) filemtime( $hybrid_path ),
			true // Load in footer
		);
	}

	/**
	 * Enqueue form handler script
	 */
	private function enqueue_form_handler(): void {
		$form_path = $this->theme_dir . '/js/exit-intent-overlay.js';

		if ( ! file_exists( $form_path ) ) {
			return;
		}

		wp_enqueue_script(
			'medici-exit-intent-form',
			$this->theme_uri . '/js/exit-intent-overlay.js',
			array( 'medici-events' ), // Depends on Events API
			(string) filemtime( $form_path ),
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

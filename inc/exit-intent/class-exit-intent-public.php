<?php
declare(strict_types=1);

/**
 * Exit-Intent Public Class
 *
 * Handles all public-facing functionality for exit-intent popup
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
		// Check if overlay script is enqueued
		if ( ! wp_script_is( 'medici-exit-intent-overlay', 'enqueued' ) ) {
			return;
		}

		// Pass configuration to JavaScript
		$config_js = sprintf(
			'window.mediciExitIntentConfig = {
				overlayPanelId: %s,
				cookieExp: %d,
				delay: %d,
				debug: %s
			};',
			wp_json_encode( $this->config['panel_id'] ),
			$this->config['cookie_exp'],
			$this->config['delay'],
			$this->config['debug'] ? 'true' : 'false'
		);

		wp_add_inline_script( 'medici-exit-intent-overlay', $config_js, 'before' );
	}

	/**
	 * Fix GenerateBlocks Overlay Panel data-gb-overlay attribute
	 *
	 * Removes empty data-gb-overlay="" from overlay containers
	 * to prevent "Empty string passed to getElementById()" errors
	 *
	 * @since 1.0.1
	 */
	public function fix_overlay_panel_attributes(): void {
		?>
		<script id="medici-overlay-panel-fix">
		(function() {
			'use strict';

			// Run early to fix before GenerateBlocks overlay.js initializes
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', fixOverlayAttributes);
			} else {
				fixOverlayAttributes();
			}

			function fixOverlayAttributes() {
				// Find all elements with empty data-gb-overlay attribute
				const elements = document.querySelectorAll('[data-gb-overlay]');

				elements.forEach(function(el) {
					const overlayValue = el.getAttribute('data-gb-overlay');

					// Remove ONLY empty data-gb-overlay="" from overlay containers
					// Keep non-empty values (triggers like data-gb-overlay="gb-overlay-424")
					if (overlayValue === '') {
						// Check if it's the overlay container itself (has id starting with gb-overlay-)
						const elId = el.getAttribute('id');
						if (elId && elId.indexOf('gb-overlay-') === 0) {
							el.removeAttribute('data-gb-overlay');

							if (<?php echo $this->config['debug'] ? 'true' : 'false'; ?>) {
								console.log('[Medici] Fixed empty data-gb-overlay on:', elId);
							}
						}
					}
				});
			}
		})();
		</script>
		<?php
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
		echo '<!-- Overlay loaded: ' . ( wp_script_is( 'medici-exit-intent-overlay', 'enqueued' ) ? 'YES' : 'NO' ) . ' -->' . "\n";
	}

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'medici_exit_intent_popup', array( $this, 'render_popup_shortcode' ) );
	}

	/**
	 * Shortcode renderer
	 *
	 * Usage:
	 * [medici_exit_intent_popup title="..." subtitle="..." button_text="..."]
	 *
	 * @param array<string, mixed> $atts Shortcode attributes
	 * @return string
	 */
	public function render_popup_shortcode( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'title'       => 'Зачекайте! Не йдіть без подарунка',
				'subtitle'    => 'Отримайте <strong>безкоштовну 30-хвилинну консультацію</strong> з медичного маркетингу від наших експертів.',
				'button_text' => 'Отримати консультацію',
			),
			$atts,
			'medici_exit_intent_popup'
		);

		$title       = sanitize_text_field( (string) $atts['title'] );
		$button_text = sanitize_text_field( (string) $atts['button_text'] );

		// Дозволяємо тільки базову розмітку в підзаголовку (щоб працював <strong>).
		$subtitle = wp_kses(
			(string) $atts['subtitle'],
			array(
				'strong' => array(),
				'em'     => array(),
				'br'     => array(),
			)
		);

		ob_start();
		?>
		<div class="exit-intent-content">
			<button class="exit-intent-close" type="button" aria-label="Закрити" data-gb-close-panel>
				<span aria-hidden="true">×</span>
			</button>

			<div class="exit-intent-icon">👋</div>

			<h2 class="exit-intent-heading"><?php echo esc_html( $title ); ?></h2>

			<p class="exit-intent-subheading"><?php echo $subtitle; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

			<form class="exit-intent-form js-exit-intent-form">
				<div class="exit-intent-field">
					<label for="exit-name" class="sr-only">Ім'я</label>
					<input id="exit-name" name="name" type="text" placeholder="Ваше ім'я" required autocomplete="name">
				</div>

				<div class="exit-intent-field">
					<label for="exit-email" class="sr-only">Email</label>
					<input id="exit-email" name="email" type="email" placeholder="email@example.com" required autocomplete="email">
				</div>

				<div class="exit-intent-field">
					<label for="exit-phone" class="sr-only">Телефон</label>
					<input id="exit-phone" name="phone" type="tel" placeholder="+380 XX XXX XX XX" autocomplete="tel">
				</div>

				<div class="exit-intent-consent">
					<label>
						<input type="checkbox" name="consent" value="1" required>
						<span>Я даю згоду на обробку персональних даних <span class="required">*</span></span>
					</label>
				</div>

				<button type="submit" class="exit-intent-submit"><?php echo esc_html( $button_text ); ?></button>

				<div class="js-exit-intent-message exit-intent-message" role="status" aria-live="polite"></div>
			</form>

			<a href="#" class="exit-intent-decline" data-gb-close-panel>Ні, дякую, продовжити перегляд</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

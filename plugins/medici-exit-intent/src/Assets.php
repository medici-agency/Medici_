<?php
/**
 * Assets Manager Class
 *
 * Handles CSS, JavaScript, and Twemoji enqueuing.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Assets Class
 *
 * Manages all plugin assets with:
 * - Twemoji integration
 * - Conditional loading
 * - Inline CSS/JS injection
 */
final class Assets {

	/**
	 * Twemoji CDN base URL.
	 *
	 * @var string
	 */
	private const TWEMOJI_CDN = 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/';

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin( string $hook_suffix ): void {
		// Only on plugin pages.
		if ( strpos( $hook_suffix, 'jexi' ) === false ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'jexi-admin',
			JEXI_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			JEXI_VERSION
		);

		// Admin JS.
		wp_enqueue_script(
			'jexi-admin',
			JEXI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			JEXI_VERSION,
			true
		);

		// Color picker.
		wp_enqueue_style( 'wp-color-picker' );

		// Media uploader.
		wp_enqueue_media();

		// Localization.
		wp_localize_script(
			'jexi-admin',
			'jexiAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'jexi_admin' ),
				'restUrl'   => rest_url( 'jexi/v1/' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'      => array(
					'saved'       => __( 'Settings saved!', 'medici-exit-intent' ),
					'error'       => __( 'Error saving settings.', 'medici-exit-intent' ),
					'confirm'     => __( 'Are you sure?', 'medici-exit-intent' ),
					'selectIcon'  => __( 'Select Icon', 'medici-exit-intent' ),
					'removeIcon'  => __( 'Remove Icon', 'medici-exit-intent' ),
				),
			)
		);
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend(): void {
		$options = jexi()->get_options();

		// Check if enabled.
		if ( ! $options->get( 'general', 'enabled' ) ) {
			return;
		}

		// Check exclusions.
		if ( $this->is_excluded() ) {
			return;
		}

		// Main popup CSS (inline to bypass optimization).
		$this->enqueue_inline_styles();

		// Twemoji (if enabled).
		if ( $options->get( 'twemoji', 'enabled' ) ) {
			$this->enqueue_twemoji();
		}

		// Exit-intent detection library.
		wp_enqueue_script(
			'jexi-bioep',
			JEXI_PLUGIN_URL . 'assets/js/vendor/bioep.min.js',
			array(),
			JEXI_VERSION,
			true
		);

		// Main popup handler.
		wp_enqueue_script(
			'jexi-popup',
			JEXI_PLUGIN_URL . 'assets/js/popup.js',
			array( 'jexi-bioep' ),
			JEXI_VERSION,
			true
		);

		// Pass configuration to JavaScript.
		$this->localize_frontend_script();
	}

	/**
	 * Enqueue inline styles.
	 *
	 * Uses wp_add_inline_style to bypass CSS optimization plugins.
	 *
	 * @since 1.0.0
	 */
	private function enqueue_inline_styles(): void {
		$css_path = JEXI_PLUGIN_DIR . 'assets/css/popup.css';

		if ( ! file_exists( $css_path ) ) {
			return;
		}

		// Register empty style handle.
		wp_register_style( 'jexi-popup', false, array(), JEXI_VERSION );
		wp_enqueue_style( 'jexi-popup' );

		// Add CSS content inline.
		$css = file_get_contents( $css_path );
		if ( is_string( $css ) && '' !== $css ) {
			// Add custom CSS from options.
			$custom_css = jexi()->get_options()->get( 'advanced', 'custom_css', '' );
			if ( ! empty( $custom_css ) ) {
				$css .= "\n/* Custom CSS */\n" . $custom_css;
			}

			// Generate CSS custom properties from options.
			$css = $this->get_css_variables() . "\n" . $css;

			wp_add_inline_style( 'jexi-popup', $css );
		}
	}

	/**
	 * Generate CSS custom properties.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_css_variables(): string {
		$options = jexi()->get_options();
		$design  = $options->get( 'design' );

		$vars = array(
			'--jexi-primary'    => $design['primary_color'] ?? '#2563eb',
			'--jexi-text'       => $design['text_color'] ?? '#1f2937',
			'--jexi-bg'         => $design['background_color'] ?? '#ffffff',
			'--jexi-backdrop'   => $design['backdrop_color'] ?? 'rgba(0, 0, 0, 0.6)',
			'--jexi-max-width'  => $design['popup_max_width'] ?? '480px',
			'--jexi-radius'     => $design['border_radius'] ?? '16px',
		);

		$css = ":root {\n";
		foreach ( $vars as $name => $value ) {
			$css .= "  {$name}: {$value};\n";
		}
		$css .= "}\n";

		return $css;
	}

	/**
	 * Enqueue Twemoji library.
	 *
	 * @since 1.0.0
	 */
	private function enqueue_twemoji(): void {
		$options = jexi()->get_options();
		$size    = $options->get( 'twemoji', 'size', '72x72' );
		$style   = $options->get( 'twemoji', 'style', 'svg' );

		// Twemoji parser script.
		wp_enqueue_script(
			'twemoji',
			'https://cdn.jsdelivr.net/npm/twemoji@latest/dist/twemoji.min.js',
			array(),
			'14.0.2',
			true
		);

		// Twemoji initialization.
		$base_url = $options->get( 'twemoji', 'base_url', '' );
		if ( empty( $base_url ) ) {
			$base_url = self::TWEMOJI_CDN;
		}

		$folder = 'svg' === $style ? 'svg' : $size;
		$ext    = 'svg' === $style ? '.svg' : '.png';

		$init_script = sprintf(
			'document.addEventListener("DOMContentLoaded", function() {
				if (typeof twemoji !== "undefined") {
					twemoji.parse(document.body, {
						folder: "%s",
						ext: "%s",
						base: "%s",
						className: "jexi-twemoji"
					});
				}
			});',
			esc_js( $folder ),
			esc_js( $ext ),
			esc_js( rtrim( $base_url, '/' ) . '/' )
		);

		wp_add_inline_script( 'twemoji', $init_script );
	}

	/**
	 * Localize frontend script.
	 *
	 * @since 1.0.0
	 */
	private function localize_frontend_script(): void {
		$options = jexi()->get_options();

		$config = array(
			// General.
			'enabled'        => $options->get( 'general', 'enabled', true ),
			'debug'          => $options->get( 'general', 'debug', false ) || jexi()->is_debug(),
			'cookieDays'     => $options->get( 'general', 'cookie_days', 30 ),
			'delaySeconds'   => $options->get( 'general', 'delay_seconds', 2 ),
			'minScreenWidth' => $options->get( 'general', 'min_screen_width', 1024 ),
			'triggerType'    => $options->get( 'general', 'trigger_type', 'exit' ),
			'scrollPercent'  => $options->get( 'general', 'scroll_percent', 50 ),
			'timeSeconds'    => $options->get( 'general', 'time_seconds', 30 ),
			'inactiveSeconds' => $options->get( 'general', 'inactive_seconds', 15 ),

			// Design.
			'template'      => $options->get( 'design', 'template', 'modern' ),
			'animation'     => $options->get( 'design', 'animation', 'scale' ),
			'position'      => $options->get( 'design', 'position', 'center' ),
			'backdropBlur'  => $options->get( 'design', 'backdrop_blur', true ),

			// Form.
			'honeypot'      => $options->get( 'form', 'honeypot', true ),
			'recaptcha'     => $options->get( 'form', 'recaptcha_enabled', false ),
			'recaptchaSiteKey' => $options->get( 'form', 'recaptcha_site_key', '' ),

			// API.
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'restUrl'       => rest_url( 'jexi/v1/' ),
			'nonce'         => wp_create_nonce( 'jexi_frontend' ),

			// Messages.
			'i18n'          => array(
				'success' => $options->get( 'content', 'success_message' ),
				'error'   => $options->get( 'content', 'error_message' ),
				'loading' => __( 'Sending...', 'medici-exit-intent' ),
			),
		);

		/**
		 * Filter frontend configuration.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $config Configuration array.
		 */
		$config = apply_filters( 'jexi_frontend_config', $config );

		wp_localize_script( 'jexi-popup', 'jexiConfig', $config );
	}

	/**
	 * Check if current page is excluded.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_excluded(): bool {
		$options = jexi()->get_options();

		// Check page exclusions.
		$exclude_pages = $options->get( 'advanced', 'exclude_pages', array() );
		if ( ! empty( $exclude_pages ) && is_singular() ) {
			$current_id = get_the_ID();
			if ( in_array( $current_id, $exclude_pages, true ) ) {
				return true;
			}
		}

		// Check URL patterns.
		$exclude_urls = $options->get( 'advanced', 'exclude_urls', '' );
		if ( ! empty( $exclude_urls ) ) {
			$current_url = home_url( add_query_arg( array() ) );
			$patterns    = array_filter( explode( "\n", $exclude_urls ) );

			foreach ( $patterns as $pattern ) {
				$pattern = trim( $pattern );
				if ( ! empty( $pattern ) && fnmatch( $pattern, $current_url ) ) {
					return true;
				}
			}
		}

		// Check user role exclusions.
		$exclude_users = $options->get( 'advanced', 'exclude_users', array() );
		if ( ! empty( $exclude_users ) && is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = array_intersect( $exclude_users, $user->roles );
			if ( ! empty( $roles ) ) {
				return true;
			}
		}

		// Check mobile.
		if ( ! $options->get( 'advanced', 'show_on_mobile', false ) ) {
			if ( wp_is_mobile() ) {
				return true;
			}
		}

		/**
		 * Filter whether current page is excluded.
		 *
		 * @since 1.0.0
		 * @param bool $excluded Whether excluded.
		 */
		return apply_filters( 'jexi_is_excluded', false );
	}

	/**
	 * Get available animations.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public static function get_animations(): array {
		return array(
			'scale'  => __( 'Scale In', 'medici-exit-intent' ),
			'fade'   => __( 'Fade In', 'medici-exit-intent' ),
			'slide'  => __( 'Slide In', 'medici-exit-intent' ),
			'bounce' => __( 'Bounce', 'medici-exit-intent' ),
			'flip'   => __( 'Flip', 'medici-exit-intent' ),
		);
	}

	/**
	 * Get available positions.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public static function get_positions(): array {
		return array(
			'center'       => __( 'Center', 'medici-exit-intent' ),
			'top'          => __( 'Top', 'medici-exit-intent' ),
			'bottom'       => __( 'Bottom', 'medici-exit-intent' ),
			'bottom-right' => __( 'Bottom Right', 'medici-exit-intent' ),
			'bottom-left'  => __( 'Bottom Left', 'medici-exit-intent' ),
		);
	}

	/**
	 * Get available trigger types.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public static function get_trigger_types(): array {
		return array(
			'exit'     => __( 'Exit Intent', 'medici-exit-intent' ),
			'scroll'   => __( 'Scroll Percentage', 'medici-exit-intent' ),
			'time'     => __( 'Time on Page', 'medici-exit-intent' ),
			'inactive' => __( 'User Inactivity', 'medici-exit-intent' ),
		);
	}
}

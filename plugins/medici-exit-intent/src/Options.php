<?php
/**
 * Options Management Class
 *
 * Handles all plugin settings with constants support.
 * Based on WP Mail SMTP Options.php architecture.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Options Class
 *
 * Manages plugin settings with:
 * - WordPress options API
 * - wp-config.php constants override
 * - Default values
 * - Validation & sanitization
 */
final class Options {

	/**
	 * WordPress option key.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'jexi_options';

	/**
	 * Constants prefix for wp-config.php.
	 *
	 * @var string
	 */
	public const CONST_PREFIX = 'JEXI_';

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $options = null;

	/**
	 * Get all options.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		if ( null === $this->options ) {
			$saved_options  = get_option( self::OPTION_KEY, array() );
			$this->options  = wp_parse_args( $saved_options, self::get_defaults() );

			// Apply constants overrides.
			$this->options = $this->apply_constants( $this->options );
		}

		/**
		 * Filter all options.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $options All options.
		 */
		return apply_filters( 'jexi_options_all', $this->options );
	}

	/**
	 * Get a single option.
	 *
	 * @since 1.0.0
	 * @param string $group   Option group.
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed
	 */
	public function get( string $group, string $key = '', mixed $default = null ): mixed {
		$options = $this->get_all();

		// Return entire group.
		if ( '' === $key ) {
			return $options[ $group ] ?? $default;
		}

		// Return specific key.
		return $options[ $group ][ $key ] ?? $default;
	}

	/**
	 * Set options.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $new_options Options to set.
	 * @param bool                 $merge       Whether to merge with existing.
	 * @return bool
	 */
	public function set( array $new_options, bool $merge = true ): bool {
		if ( $merge ) {
			$options = $this->get_all();
			$options = $this->array_merge_recursive_distinct( $options, $new_options );
		} else {
			$options = $new_options;
		}

		// Validate and sanitize.
		$options = $this->sanitize_options( $options );

		// Clear cache.
		$this->options = null;

		/**
		 * Fires before options are saved.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $options Options being saved.
		 */
		do_action( 'jexi_options_before_save', $options );

		$result = update_option( self::OPTION_KEY, $options );

		/**
		 * Fires after options are saved.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $options Saved options.
		 * @param bool                 $result  Whether save was successful.
		 */
		do_action( 'jexi_options_saved', $options, $result );

		return $result;
	}

	/**
	 * Check if a constant is defined.
	 *
	 * @since 1.0.0
	 * @param string $group Option group.
	 * @param string $key   Option key.
	 * @return bool
	 */
	public function is_const_defined( string $group, string $key ): bool {
		$const_name = self::CONST_PREFIX . strtoupper( $group ) . '_' . strtoupper( $key );
		return defined( $const_name );
	}

	/**
	 * Get constant value.
	 *
	 * @since 1.0.0
	 * @param string $group Option group.
	 * @param string $key   Option key.
	 * @return mixed
	 */
	public function get_const_value( string $group, string $key ): mixed {
		$const_name = self::CONST_PREFIX . strtoupper( $group ) . '_' . strtoupper( $key );

		if ( defined( $const_name ) ) {
			return constant( $const_name );
		}

		return null;
	}

	/**
	 * Apply constants overrides.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $options Options.
	 * @return array<string, mixed>
	 */
	private function apply_constants( array $options ): array {
		// Check global enable constant.
		if ( ! defined( 'JEXI_ON' ) || ! JEXI_ON ) {
			return $options;
		}

		// Apply group-level constants.
		$const_map = array(
			'general' => array( 'enabled', 'debug', 'cookie_days', 'delay_seconds', 'min_screen_width' ),
			'design'  => array( 'template', 'icon', 'position', 'animation', 'backdrop_blur' ),
			'content' => array( 'title', 'subtitle', 'button_text', 'decline_text' ),
			'form'    => array( 'show_name', 'show_email', 'show_phone', 'require_consent' ),
			'twemoji' => array( 'enabled', 'size', 'style' ),
			'integrations' => array( 'lead_cpt', 'events_api', 'email_provider' ),
			'advanced' => array( 'exclude_pages', 'exclude_users', 'custom_css', 'custom_js' ),
		);

		foreach ( $const_map as $group => $keys ) {
			foreach ( $keys as $key ) {
				if ( $this->is_const_defined( $group, $key ) ) {
					$options[ $group ][ $key ] = $this->get_const_value( $group, $key );
				}
			}
		}

		return $options;
	}

	/**
	 * Get default options.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		/**
		 * Filter default options.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $defaults Default options.
		 */
		return apply_filters(
			'jexi_options_defaults',
			array(
				// General Settings.
				'general'      => array(
					'enabled'          => true,
					'debug'            => false,
					'cookie_days'      => 30,
					'delay_seconds'    => 2,
					'min_screen_width' => 1024,
					'trigger_type'     => 'exit',  // exit, scroll, time, inactive.
					'scroll_percent'   => 50,
					'time_seconds'     => 30,
					'inactive_seconds' => 15,
				),

				// Design Settings.
				'design'       => array(
					'template'          => 'modern',  // modern, minimal, bold, playful.
					'icon'              => '👋',
					'icon_type'         => 'twemoji', // twemoji, native, custom, none.
					'custom_icon_url'   => '',
					'position'          => 'center',  // center, top, bottom-right.
					'animation'         => 'scale',   // scale, slide, fade, bounce.
					'backdrop_blur'     => true,
					'backdrop_color'    => 'rgba(0, 0, 0, 0.6)',
					'popup_max_width'   => '480px',
					'border_radius'     => '16px',
					'primary_color'     => '#2563eb',
					'text_color'        => '#1f2937',
					'background_color'  => '#ffffff',
					'dark_mode'         => true,
				),

				// Content Settings.
				'content'      => array(
					'title'           => __( 'Wait! Don\'t leave yet', 'medici-exit-intent' ),
					'subtitle'        => __( 'Get a <strong>free 30-minute consultation</strong> from our marketing experts.', 'medici-exit-intent' ),
					'button_text'     => __( 'Get Free Consultation', 'medici-exit-intent' ),
					'decline_text'    => __( 'No thanks, continue browsing', 'medici-exit-intent' ),
					'success_message' => __( 'Thank you! We\'ll be in touch soon.', 'medici-exit-intent' ),
					'error_message'   => __( 'Something went wrong. Please try again.', 'medici-exit-intent' ),
				),

				// Form Settings.
				'form'         => array(
					'show_name'         => true,
					'show_email'        => true,
					'show_phone'        => true,
					'require_name'      => false,
					'require_email'     => true,
					'require_phone'     => false,
					'require_consent'   => true,
					'consent_text'      => __( 'I agree to the processing of personal data', 'medici-exit-intent' ),
					'honeypot'          => true,
					'recaptcha_enabled' => false,
					'recaptcha_site_key' => '',
					'recaptcha_secret'  => '',
				),

				// Twemoji Settings.
				'twemoji'      => array(
					'enabled'   => true,
					'size'      => '72x72',  // 72x72, 36x36.
					'style'     => 'svg',    // svg, png.
					'base_url'  => '',       // Empty = use default CDN.
					'lazy_load' => true,
				),

				// Integration Settings.
				'integrations' => array(
					'lead_cpt'       => true,   // Create Lead CPT entries.
					'events_api'     => true,   // Use Medici Events API.
					'email_provider' => 'none', // none, mailchimp, sendgrid, convertkit.
					'mailchimp'      => array(
						'api_key'  => '',
						'list_id'  => '',
						'tags'     => array(),
					),
					'sendgrid'       => array(
						'api_key' => '',
						'list_id' => '',
					),
					'convertkit'     => array(
						'api_key' => '',
						'form_id' => '',
					),
					'webhook_url'    => '',
					'zapier_webhook' => '',
				),

				// A/B Testing Settings.
				'ab_testing'   => array(
					'enabled'    => false,
					'variants'   => array(),  // Array of variant configs.
					'split_type' => 'random', // random, cookie-based.
				),

				// Analytics Settings.
				'analytics'    => array(
					'enabled'        => true,
					'track_views'    => true,
					'track_closes'   => true,
					'track_submits'  => true,
					'retention_days' => 90,
					'export_format'  => 'csv', // csv, json.
				),

				// Advanced Settings.
				'advanced'     => array(
					'exclude_pages'     => array(),  // Page/Post IDs to exclude.
					'exclude_urls'      => '',       // URL patterns (one per line).
					'exclude_users'     => array(),  // User roles to exclude.
					'show_on_mobile'    => false,
					'disable_on_touch'  => true,
					'custom_css'        => '',
					'custom_js'         => '',
					'script_priority'   => 100,
					'async_loading'     => true,
				),
			)
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $options Raw options.
	 * @return array<string, mixed>
	 */
	private function sanitize_options( array $options ): array {
		$sanitized = array();

		// General.
		if ( isset( $options['general'] ) ) {
			$sanitized['general'] = array(
				'enabled'          => (bool) ( $options['general']['enabled'] ?? true ),
				'debug'            => (bool) ( $options['general']['debug'] ?? false ),
				'cookie_days'      => absint( $options['general']['cookie_days'] ?? 30 ),
				'delay_seconds'    => absint( $options['general']['delay_seconds'] ?? 2 ),
				'min_screen_width' => absint( $options['general']['min_screen_width'] ?? 1024 ),
				'trigger_type'     => sanitize_text_field( $options['general']['trigger_type'] ?? 'exit' ),
				'scroll_percent'   => min( 100, max( 0, absint( $options['general']['scroll_percent'] ?? 50 ) ) ),
				'time_seconds'     => absint( $options['general']['time_seconds'] ?? 30 ),
				'inactive_seconds' => absint( $options['general']['inactive_seconds'] ?? 15 ),
			);
		}

		// Design.
		if ( isset( $options['design'] ) ) {
			$sanitized['design'] = array(
				'template'         => sanitize_text_field( $options['design']['template'] ?? 'modern' ),
				'icon'             => wp_kses_post( $options['design']['icon'] ?? '👋' ),
				'icon_type'        => sanitize_text_field( $options['design']['icon_type'] ?? 'twemoji' ),
				'custom_icon_url'  => esc_url_raw( $options['design']['custom_icon_url'] ?? '' ),
				'position'         => sanitize_text_field( $options['design']['position'] ?? 'center' ),
				'animation'        => sanitize_text_field( $options['design']['animation'] ?? 'scale' ),
				'backdrop_blur'    => (bool) ( $options['design']['backdrop_blur'] ?? true ),
				'backdrop_color'   => sanitize_text_field( $options['design']['backdrop_color'] ?? 'rgba(0, 0, 0, 0.6)' ),
				'popup_max_width'  => sanitize_text_field( $options['design']['popup_max_width'] ?? '480px' ),
				'border_radius'    => sanitize_text_field( $options['design']['border_radius'] ?? '16px' ),
				'primary_color'    => sanitize_hex_color( $options['design']['primary_color'] ?? '#2563eb' ),
				'text_color'       => sanitize_hex_color( $options['design']['text_color'] ?? '#1f2937' ),
				'background_color' => sanitize_hex_color( $options['design']['background_color'] ?? '#ffffff' ),
				'dark_mode'        => (bool) ( $options['design']['dark_mode'] ?? true ),
			);
		}

		// Content.
		if ( isset( $options['content'] ) ) {
			$sanitized['content'] = array(
				'title'           => sanitize_text_field( $options['content']['title'] ?? '' ),
				'subtitle'        => wp_kses_post( $options['content']['subtitle'] ?? '' ),
				'button_text'     => sanitize_text_field( $options['content']['button_text'] ?? '' ),
				'decline_text'    => sanitize_text_field( $options['content']['decline_text'] ?? '' ),
				'success_message' => sanitize_text_field( $options['content']['success_message'] ?? '' ),
				'error_message'   => sanitize_text_field( $options['content']['error_message'] ?? '' ),
			);
		}

		// Form.
		if ( isset( $options['form'] ) ) {
			$sanitized['form'] = array(
				'show_name'          => (bool) ( $options['form']['show_name'] ?? true ),
				'show_email'         => (bool) ( $options['form']['show_email'] ?? true ),
				'show_phone'         => (bool) ( $options['form']['show_phone'] ?? true ),
				'require_name'       => (bool) ( $options['form']['require_name'] ?? false ),
				'require_email'      => (bool) ( $options['form']['require_email'] ?? true ),
				'require_phone'      => (bool) ( $options['form']['require_phone'] ?? false ),
				'require_consent'    => (bool) ( $options['form']['require_consent'] ?? true ),
				'consent_text'       => sanitize_text_field( $options['form']['consent_text'] ?? '' ),
				'honeypot'           => (bool) ( $options['form']['honeypot'] ?? true ),
				'recaptcha_enabled'  => (bool) ( $options['form']['recaptcha_enabled'] ?? false ),
				'recaptcha_site_key' => sanitize_text_field( $options['form']['recaptcha_site_key'] ?? '' ),
				'recaptcha_secret'   => sanitize_text_field( $options['form']['recaptcha_secret'] ?? '' ),
			);
		}

		// Twemoji.
		if ( isset( $options['twemoji'] ) ) {
			$sanitized['twemoji'] = array(
				'enabled'   => (bool) ( $options['twemoji']['enabled'] ?? true ),
				'size'      => sanitize_text_field( $options['twemoji']['size'] ?? '72x72' ),
				'style'     => sanitize_text_field( $options['twemoji']['style'] ?? 'svg' ),
				'base_url'  => esc_url_raw( $options['twemoji']['base_url'] ?? '' ),
				'lazy_load' => (bool) ( $options['twemoji']['lazy_load'] ?? true ),
			);
		}

		// Integrations.
		if ( isset( $options['integrations'] ) ) {
			$sanitized['integrations'] = array(
				'lead_cpt'       => (bool) ( $options['integrations']['lead_cpt'] ?? true ),
				'events_api'     => (bool) ( $options['integrations']['events_api'] ?? true ),
				'email_provider' => sanitize_text_field( $options['integrations']['email_provider'] ?? 'none' ),
				'mailchimp'      => array(
					'api_key' => sanitize_text_field( $options['integrations']['mailchimp']['api_key'] ?? '' ),
					'list_id' => sanitize_text_field( $options['integrations']['mailchimp']['list_id'] ?? '' ),
					'tags'    => array_map( 'sanitize_text_field', (array) ( $options['integrations']['mailchimp']['tags'] ?? array() ) ),
				),
				'sendgrid'       => array(
					'api_key' => sanitize_text_field( $options['integrations']['sendgrid']['api_key'] ?? '' ),
					'list_id' => sanitize_text_field( $options['integrations']['sendgrid']['list_id'] ?? '' ),
				),
				'convertkit'     => array(
					'api_key' => sanitize_text_field( $options['integrations']['convertkit']['api_key'] ?? '' ),
					'form_id' => sanitize_text_field( $options['integrations']['convertkit']['form_id'] ?? '' ),
				),
				'webhook_url'    => esc_url_raw( $options['integrations']['webhook_url'] ?? '' ),
				'zapier_webhook' => esc_url_raw( $options['integrations']['zapier_webhook'] ?? '' ),
			);
		}

		// A/B Testing.
		if ( isset( $options['ab_testing'] ) ) {
			$sanitized['ab_testing'] = array(
				'enabled'    => (bool) ( $options['ab_testing']['enabled'] ?? false ),
				'variants'   => (array) ( $options['ab_testing']['variants'] ?? array() ),
				'split_type' => sanitize_text_field( $options['ab_testing']['split_type'] ?? 'random' ),
			);
		}

		// Analytics.
		if ( isset( $options['analytics'] ) ) {
			$sanitized['analytics'] = array(
				'enabled'        => (bool) ( $options['analytics']['enabled'] ?? true ),
				'track_views'    => (bool) ( $options['analytics']['track_views'] ?? true ),
				'track_closes'   => (bool) ( $options['analytics']['track_closes'] ?? true ),
				'track_submits'  => (bool) ( $options['analytics']['track_submits'] ?? true ),
				'retention_days' => absint( $options['analytics']['retention_days'] ?? 90 ),
				'export_format'  => sanitize_text_field( $options['analytics']['export_format'] ?? 'csv' ),
			);
		}

		// Advanced.
		if ( isset( $options['advanced'] ) ) {
			$sanitized['advanced'] = array(
				'exclude_pages'    => array_map( 'absint', (array) ( $options['advanced']['exclude_pages'] ?? array() ) ),
				'exclude_urls'     => sanitize_textarea_field( $options['advanced']['exclude_urls'] ?? '' ),
				'exclude_users'    => array_map( 'sanitize_text_field', (array) ( $options['advanced']['exclude_users'] ?? array() ) ),
				'show_on_mobile'   => (bool) ( $options['advanced']['show_on_mobile'] ?? false ),
				'disable_on_touch' => (bool) ( $options['advanced']['disable_on_touch'] ?? true ),
				'custom_css'       => wp_strip_all_tags( $options['advanced']['custom_css'] ?? '' ),
				'custom_js'        => $options['advanced']['custom_js'] ?? '', // Allow JS.
				'script_priority'  => absint( $options['advanced']['script_priority'] ?? 100 ),
				'async_loading'    => (bool) ( $options['advanced']['async_loading'] ?? true ),
			);
		}

		return $sanitized;
	}

	/**
	 * Merge arrays recursively (distinct).
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $array1 First array.
	 * @param array<string, mixed> $array2 Second array.
	 * @return array<string, mixed>
	 */
	private function array_merge_recursive_distinct( array $array1, array $array2 ): array {
		$merged = $array1;

		foreach ( $array2 as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = $this->array_merge_recursive_distinct( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Reset options to defaults.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function reset(): bool {
		$this->options = null;
		return update_option( self::OPTION_KEY, self::get_defaults() );
	}

	/**
	 * Delete all options.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function delete(): bool {
		$this->options = null;
		return delete_option( self::OPTION_KEY );
	}

	/**
	 * Export options as JSON.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function export(): string {
		$options = $this->get_all();

		// Remove sensitive data.
		unset( $options['integrations']['mailchimp']['api_key'] );
		unset( $options['integrations']['sendgrid']['api_key'] );
		unset( $options['integrations']['convertkit']['api_key'] );
		unset( $options['form']['recaptcha_secret'] );

		return (string) wp_json_encode( $options, JSON_PRETTY_PRINT );
	}

	/**
	 * Import options from JSON.
	 *
	 * @since 1.0.0
	 * @param string $json JSON string.
	 * @return bool
	 */
	public function import( string $json ): bool {
		$options = json_decode( $json, true );

		if ( ! is_array( $options ) ) {
			return false;
		}

		return $this->set( $options, true );
	}
}

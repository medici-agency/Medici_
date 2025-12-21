<?php
/**
 * Medici Forms Advanced - Settings Class
 *
 * Управління налаштуваннями плагіна з validation та санітизацією
 *
 * @package    Medici_Agency
 * @subpackage Forms_Advanced
 * @since      1.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class
 *
 * @since 1.0.0
 */
class Medici_Forms_Advanced_Settings {

	/**
	 * Settings data
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	private array $settings = [];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load settings from database
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_settings(): void {
		$saved_settings = get_option( Medici_Forms_Advanced::OPTION_NAME, [] );

		$this->settings = wp_parse_args( $saved_settings, $this->get_defaults() );
	}

	/**
	 * Get default settings
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return [
			// Layout Settings
			'layout' => [
				'form_max_width'   => '600px',
				'field_width'      => '100%',
				'button_width'     => 'auto',
				'button_max_width' => '300px',
				'field_gap'        => '1.5rem',
				'button_alignment' => 'left', // left, center, right, full
			],

			// Styling Settings (HIGH CONTRAST for WCAG AAA)
			'styling' => [
				// Fields - Light Theme
				'field_bg_color'           => '#ffffff',
				'field_border_color'       => '#d1d5db',
				'field_border_width'       => '2px',
				'field_border_radius'      => '6px',
				'field_text_color'         => '#111827',
				'field_font_size'          => '1rem',
				'field_padding'            => '0.875rem 1.125rem',

				// Fields Focus - High Contrast Blue
				'field_focus_border_color' => '#2563eb',
				'field_focus_shadow'       => '0 0 0 3px rgba(37, 99, 235, 0.15)',

				// Buttons - Modern Blue (High Contrast)
				'button_bg_color'          => '#2563eb',
				'button_text_color'        => '#ffffff',
				'button_border_radius'     => '6px',
				'button_font_size'         => '1.0625rem',
				'button_font_weight'       => '600',
				'button_padding'           => '0.875rem 2.25rem',

				// Buttons Hover - Darker Blue
				'button_hover_bg_color'    => '#1d4ed8',
				'button_hover_transform'   => 'translateY(-1px)',

				// Labels - High Contrast
				'label_color'              => '#0f172a',
				'label_font_size'          => '0.9375rem',
				'label_font_weight'        => '600',

				// Dark Theme - High Contrast for Accessibility
				'dark_field_bg_color'      => '#1e293b',
				'dark_field_border_color'  => 'rgba(148, 163, 184, 0.4)',
				'dark_field_text_color'    => '#f1f5f9',
				'dark_label_color'         => '#f1f5f9',
			],

			// Anti-Bot Settings
			'antibot' => [
				'enabled'              => true,
				'provider'             => 'turnstile', // turnstile, recaptcha, none
				'turnstile_site_key'   => '',
				'turnstile_secret_key' => '',
				'recaptcha_site_key'   => '',
				'recaptcha_secret_key' => '',
				'recaptcha_type'       => 'v2', // v2, v3
			],

			// Email Settings
			'email' => [
				'custom_templates' => false,
				'template_style'   => 'modern', // modern, classic, minimal
				'logo_url'         => '',
				'footer_text'      => '',
			],

			// File Upload Settings
			'file_upload' => [
				'enabled'          => true,
				'max_size'         => 5, // MB
				'allowed_types'    => [ 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png' ],
				'upload_path'      => 'wpforms-uploads',
			],

			// Advanced Settings
			'advanced' => [
				'custom_css'       => '',
				'load_css'         => true,
				'load_js'          => true,
			],
		];
	}

	/**
	 * Get setting value
	 *
	 * @since 1.0.0
	 * @param string $group Setting group (layout, styling, antibot, email, file_upload, advanced)
	 * @param string $key Setting key
	 * @param mixed  $default Default value if setting doesn't exist
	 * @return mixed Setting value
	 */
	public function get( string $group, string $key, $default = null ) {
		if ( isset( $this->settings[ $group ][ $key ] ) ) {
			return $this->settings[ $group ][ $key ];
		}

		$defaults = $this->get_defaults();

		return $defaults[ $group ][ $key ] ?? $default;
	}

	/**
	 * Get all settings for a group
	 *
	 * @since 1.0.0
	 * @param string $group Setting group
	 * @return array<string, mixed> Group settings
	 */
	public function get_group( string $group ): array {
		return $this->settings[ $group ] ?? [];
	}

	/**
	 * Get all settings
	 *
	 * @since 1.0.0
	 * @return array<string, mixed> All settings
	 */
	public function get_all(): array {
		return $this->settings;
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings to save
	 * @return bool True on success, false on failure
	 */
	public function save( array $settings ): bool {
		// Sanitize settings
		$sanitized = $this->sanitize( $settings );

		// Merge with existing settings
		$this->settings = wp_parse_args( $sanitized, $this->settings );

		// Save to database
		$result = update_option( Medici_Forms_Advanced::OPTION_NAME, $this->settings );

		// Clear cache
		wp_cache_delete( Medici_Forms_Advanced::OPTION_NAME, 'options' );

		return $result;
	}

	/**
	 * Sanitize settings
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Raw settings
	 * @return array<string, mixed> Sanitized settings
	 */
	private function sanitize( array $settings ): array {
		$sanitized = [];

		// Layout settings
		if ( isset( $settings['layout'] ) && is_array( $settings['layout'] ) ) {
			$sanitized['layout'] = [
				'form_max_width'   => sanitize_text_field( $settings['layout']['form_max_width'] ?? '600px' ),
				'field_width'      => sanitize_text_field( $settings['layout']['field_width'] ?? '100%' ),
				'button_width'     => sanitize_text_field( $settings['layout']['button_width'] ?? 'auto' ),
				'button_max_width' => sanitize_text_field( $settings['layout']['button_max_width'] ?? '300px' ),
				'field_gap'        => sanitize_text_field( $settings['layout']['field_gap'] ?? '1.5rem' ),
				'button_alignment' => in_array( $settings['layout']['button_alignment'] ?? '', [ 'left', 'center', 'right', 'full' ], true )
					? $settings['layout']['button_alignment']
					: 'left',
			];
		}

		// Styling settings
		if ( isset( $settings['styling'] ) && is_array( $settings['styling'] ) ) {
			$sanitized['styling'] = [];
			foreach ( $settings['styling'] as $key => $value ) {
				if ( str_contains( (string) $key, '_color' ) ) {
					$sanitized['styling'][ $key ] = sanitize_hex_color( $value );
				} else {
					$sanitized['styling'][ $key ] = sanitize_text_field( (string) $value );
				}
			}
		}

		// Anti-bot settings
		if ( isset( $settings['antibot'] ) && is_array( $settings['antibot'] ) ) {
			$sanitized['antibot'] = [
				'enabled'              => ! empty( $settings['antibot']['enabled'] ),
				'provider'             => in_array( $settings['antibot']['provider'] ?? '', [ 'turnstile', 'recaptcha', 'none' ], true )
					? $settings['antibot']['provider']
					: 'turnstile',
				'turnstile_site_key'   => sanitize_text_field( $settings['antibot']['turnstile_site_key'] ?? '' ),
				'turnstile_secret_key' => sanitize_text_field( $settings['antibot']['turnstile_secret_key'] ?? '' ),
				'recaptcha_site_key'   => sanitize_text_field( $settings['antibot']['recaptcha_site_key'] ?? '' ),
				'recaptcha_secret_key' => sanitize_text_field( $settings['antibot']['recaptcha_secret_key'] ?? '' ),
				'recaptcha_type'       => in_array( $settings['antibot']['recaptcha_type'] ?? '', [ 'v2', 'v3' ], true )
					? $settings['antibot']['recaptcha_type']
					: 'v2',
			];
		}

		// Email settings
		if ( isset( $settings['email'] ) && is_array( $settings['email'] ) ) {
			$sanitized['email'] = [
				'custom_templates' => ! empty( $settings['email']['custom_templates'] ),
				'template_style'   => in_array( $settings['email']['template_style'] ?? '', [ 'modern', 'classic', 'minimal' ], true )
					? $settings['email']['template_style']
					: 'modern',
				'logo_url'         => esc_url_raw( $settings['email']['logo_url'] ?? '' ),
				'footer_text'      => wp_kses_post( $settings['email']['footer_text'] ?? '' ),
			];
		}

		// File upload settings
		if ( isset( $settings['file_upload'] ) && is_array( $settings['file_upload'] ) ) {
			$allowed_types = $settings['file_upload']['allowed_types'] ?? [];
			if ( is_array( $allowed_types ) ) {
				$allowed_types = array_map( 'sanitize_text_field', $allowed_types );
			}

			$sanitized['file_upload'] = [
				'enabled'       => ! empty( $settings['file_upload']['enabled'] ),
				'max_size'      => absint( $settings['file_upload']['max_size'] ?? 5 ),
				'allowed_types' => $allowed_types,
				'upload_path'   => sanitize_text_field( $settings['file_upload']['upload_path'] ?? 'wpforms-uploads' ),
			];
		}

		// Advanced settings
		if ( isset( $settings['advanced'] ) && is_array( $settings['advanced'] ) ) {
			$sanitized['advanced'] = [
				'custom_css' => wp_strip_all_tags( $settings['advanced']['custom_css'] ?? '' ),
				'load_css'   => ! empty( $settings['advanced']['load_css'] ),
				'load_js'    => ! empty( $settings['advanced']['load_js'] ),
			];
		}

		return $sanitized;
	}

	/**
	 * Reset settings to defaults
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure
	 */
	public function reset(): bool {
		$this->settings = $this->get_defaults();

		return update_option( Medici_Forms_Advanced::OPTION_NAME, $this->settings );
	}
}

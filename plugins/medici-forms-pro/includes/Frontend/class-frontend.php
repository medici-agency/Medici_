<?php
/**
 * Frontend Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Frontend;

use MediciForms\Plugin;

/**
 * Frontend Class.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Shortcode instance.
	 *
	 * @var Shortcode
	 */
	private Shortcode $shortcode;

	/**
	 * AJAX Handler instance.
	 *
	 * @var Ajax_Handler
	 */
	private Ajax_Handler $ajax;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->shortcode = new Shortcode();
		$this->ajax      = new Ajax_Handler();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'output_custom_css' ) );
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes(): void {
		$this->shortcode->register();
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		// Check if we should load assets.
		if ( ! Plugin::get_option( 'load_styles', true ) && ! Plugin::get_option( 'load_scripts', true ) ) {
			return;
		}

		// Styles.
		if ( Plugin::get_option( 'load_styles', true ) ) {
			wp_enqueue_style(
				'medici-forms',
				MEDICI_FORMS_PLUGIN_URL . 'assets/css/frontend.css',
				array(),
				MEDICI_FORMS_VERSION
			);
		}

		// Scripts.
		if ( Plugin::get_option( 'load_scripts', true ) ) {
			wp_enqueue_script(
				'medici-forms',
				MEDICI_FORMS_PLUGIN_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				MEDICI_FORMS_VERSION,
				true
			);

			wp_localize_script(
				'medici-forms',
				'mediciFormsConfig',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'required'     => __( "Це поле обов'язкове", 'medici-forms-pro' ),
						'invalidEmail' => __( 'Введіть коректну email адресу', 'medici-forms-pro' ),
						'invalidPhone' => __( 'Введіть коректний номер телефону', 'medici-forms-pro' ),
						'submitting'   => __( 'Відправляється...', 'medici-forms-pro' ),
						'success'      => __( 'Форму успішно відправлено!', 'medici-forms-pro' ),
						'error'        => __( 'Виникла помилка. Спробуйте ще раз.', 'medici-forms-pro' ),
					),
				)
			);

			// reCAPTCHA.
			if ( Plugin::get_option( 'enable_recaptcha', false ) ) {
				$site_key = Plugin::get_option( 'recaptcha_site_key', '' );
				$version  = Plugin::get_option( 'recaptcha_version', 'v3' );

				if ( ! empty( $site_key ) ) {
					if ( 'v3' === $version ) {
						wp_enqueue_script(
							'google-recaptcha',
							'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key ),
							array(),
							null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
							true
						);
					} else {
						wp_enqueue_script(
							'google-recaptcha',
							'https://www.google.com/recaptcha/api.js',
							array(),
							null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
							true
						);
					}
				}
			}
		}
	}

	/**
	 * Output custom CSS from settings.
	 *
	 * @since 1.0.0
	 */
	public function output_custom_css(): void {
		if ( 'none' === Plugin::get_option( 'form_style', 'modern' ) ) {
			return;
		}

		$css = $this->generate_custom_css();

		echo '<style id="medici-forms-custom-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Generate HIGH CONTRAST custom CSS from settings.
	 *
	 * @since 1.1.0
	 * @return string Generated CSS
	 */
	private function generate_custom_css(): string {
		// Layout settings.
		$form_max_width    = Plugin::get_option( 'form_max_width', '600px' );
		$field_width       = Plugin::get_option( 'field_width', '100%' );
		$button_max_width  = Plugin::get_option( 'button_max_width', 'auto' );
		$field_gap         = Plugin::get_option( 'field_gap', 20 );
		$button_alignment  = Plugin::get_option( 'button_alignment', 'left' );

		// Color settings.
		$primary_color = Plugin::get_option( 'primary_color', '#2563eb' );
		$success_color = Plugin::get_option( 'success_color', '#16a34a' );
		$error_color   = Plugin::get_option( 'error_color', '#dc2626' );
		$border_radius = Plugin::get_option( 'border_radius', 8 );

		$css = <<<CSS
		/* Medici Forms Pro - HIGH CONTRAST Custom Styles (v1.1.0) */

		:root {
			--mf-primary-color: {$primary_color};
			--mf-success-color: {$success_color};
			--mf-error-color: {$error_color};
			--mf-border-radius: {$border_radius}px;
		}

		/* Layout Settings */
		.medici-form-container {
			max-width: {$form_max_width} !important;
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.medici-form .medici-form-field {
			gap: {$field_gap}px !important;
			margin-bottom: {$field_gap}px !important;
		}

		.medici-form input[type="text"],
		.medici-form input[type="email"],
		.medici-form input[type="tel"],
		.medici-form input[type="url"],
		.medici-form input[type="number"],
		.medici-form input[type="password"],
		.medici-form input[type="date"],
		.medici-form select,
		.medici-form textarea {
			width: {$field_width} !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
		}

		.medici-form button[type="submit"],
		.medici-form .medici-submit-container button {
			width: auto !important;
			max-width: {$button_max_width} !important;
		}

		.medici-form .medici-submit-container {
			text-align: {$button_alignment} !important;
		}

		/* Field Styling - Light Theme (HIGH CONTRAST) */
		.medici-form input[type="text"],
		.medici-form input[type="email"],
		.medici-form input[type="tel"],
		.medici-form input[type="url"],
		.medici-form input[type="number"],
		.medici-form input[type="password"],
		.medici-form input[type="date"],
		.medici-form select,
		.medici-form textarea {
			background-color: #ffffff !important;
			border: 2px solid #d1d5db !important;
			border-radius: 6px !important;
			color: #111827 !important;
			font-size: 1rem !important;
			padding: 0.875rem 1.125rem !important;
			transition: all 0.2s ease !important;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
		}

		.medici-form input:focus,
		.medici-form select:focus,
		.medici-form textarea:focus {
			border-color: {$primary_color} !important;
			box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
			outline: none !important;
			background-color: #ffffff !important;
		}

		.medici-form input::placeholder,
		.medici-form textarea::placeholder {
			color: #9ca3af !important;
			opacity: 1 !important;
		}

		/* Button Styling - Modern & Accessible */
		.medici-form button[type="submit"],
		.medici-form .medici-submit-container button {
			background-color: {$primary_color} !important;
			color: #ffffff !important;
			border: none !important;
			border-radius: 6px !important;
			font-size: 1.0625rem !important;
			font-weight: 600 !important;
			padding: 0.875rem 2.25rem !important;
			transition: all 0.2s ease !important;
			cursor: pointer !important;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
		}

		.medici-form button[type="submit"]:hover,
		.medici-form .medici-submit-container button:hover {
			background-color: #1d4ed8 !important;
			transform: translateY(-1px) !important;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06) !important;
		}

		.medici-form button[type="submit"]:focus,
		.medici-form .medici-submit-container button:focus {
			outline: 2px solid {$primary_color} !important;
			outline-offset: 2px !important;
		}

		/* Label Styling - High Contrast */
		.medici-form .medici-form-label {
			color: #0f172a !important;
			font-size: 0.9375rem !important;
			font-weight: 600 !important;
			margin-bottom: 0.5rem !important;
		}

		.medici-form .medici-form-sublabel {
			color: #6b7280 !important;
			font-size: 0.875rem !important;
		}

		/* Required Field Indicator */
		.medici-form .medici-required-label {
			color: {$error_color} !important;
			font-weight: 600 !important;
		}

		/* Dark Theme Support - HIGH CONTRAST для доступності */
		[data-theme="dark"] .medici-form input[type="text"],
		[data-theme="dark"] .medici-form input[type="email"],
		[data-theme="dark"] .medici-form input[type="tel"],
		[data-theme="dark"] .medici-form input[type="url"],
		[data-theme="dark"] .medici-form input[type="number"],
		[data-theme="dark"] .medici-form input[type="password"],
		[data-theme="dark"] .medici-form input[type="date"],
		[data-theme="dark"] .medici-form select,
		[data-theme="dark"] .medici-form textarea {
			background-color: #1e293b !important;
			border-color: rgba(148, 163, 184, 0.4) !important;
			color: #f1f5f9 !important;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
		}

		[data-theme="dark"] .medici-form input:focus,
		[data-theme="dark"] .medici-form select:focus,
		[data-theme="dark"] .medici-form textarea:focus {
			border-color: #60a5fa !important;
			background-color: #1e293b !important;
			box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2) !important;
		}

		[data-theme="dark"] .medici-form input::placeholder,
		[data-theme="dark"] .medici-form textarea::placeholder {
			color: #94a3b8 !important;
		}

		[data-theme="dark"] .medici-form .medici-form-label {
			color: #f1f5f9 !important;
		}

		[data-theme="dark"] .medici-form .medici-form-sublabel {
			color: #94a3b8 !important;
		}

		/* Error States - High Visibility */
		.medici-form .medici-error,
		.medici-form input.medici-error,
		.medici-form textarea.medici-error,
		.medici-form select.medici-error {
			border-color: {$error_color} !important;
			background-color: #fef2f2 !important;
		}

		[data-theme="dark"] .medici-form .medici-error,
		[data-theme="dark"] .medici-form input.medici-error,
		[data-theme="dark"] .medici-form textarea.medici-error,
		[data-theme="dark"] .medici-form select.medici-error {
			border-color: #ef4444 !important;
			background-color: #7f1d1d !important;
		}

		/* Success States */
		.medici-form .medici-success-message {
			background-color: #f0fdf4 !important;
			border: 2px solid {$success_color} !important;
			color: #166534 !important;
			padding: 1rem !important;
			border-radius: 6px !important;
			margin-bottom: 1rem !important;
		}

		[data-theme="dark"] .medici-form .medici-success-message {
			background-color: #14532d !important;
			color: #86efac !important;
		}

		/* Responsive - mobile font size 16px to prevent zoom on iOS */
		@media (max-width: 767px) {
			.medici-form input[type="text"],
			.medici-form input[type="email"],
			.medici-form input[type="tel"],
			.medici-form input[type="url"],
			.medici-form input[type="number"],
			.medici-form input[type="password"],
			.medici-form input[type="date"],
			.medici-form select,
			.medici-form textarea {
				font-size: 16px !important;
			}

			.medici-form button[type="submit"],
			.medici-form .medici-submit-container button {
				width: 100% !important;
				max-width: 100% !important;
			}
		}

		/* Accessibility - Focus Visible */
		.medici-form *:focus-visible {
			outline: 2px solid {$primary_color} !important;
			outline-offset: 2px !important;
		}
		CSS;

		return $css;
	}
}

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

		$primary_color = Plugin::get_option( 'primary_color', '#2563eb' );
		$success_color = Plugin::get_option( 'success_color', '#16a34a' );
		$error_color   = Plugin::get_option( 'error_color', '#dc2626' );
		$border_radius = Plugin::get_option( 'border_radius', '8' );

		$css = ':root {
			--mf-primary-color: ' . esc_attr( $primary_color ) . ';
			--mf-success-color: ' . esc_attr( $success_color ) . ';
			--mf-error-color: ' . esc_attr( $error_color ) . ';
			--mf-border-radius: ' . esc_attr( $border_radius ) . 'px;
		}';

		echo '<style id="medici-forms-custom-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

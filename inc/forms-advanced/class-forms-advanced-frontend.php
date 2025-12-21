<?php
/**
 * Medici Forms Advanced - Frontend Class
 *
 * Застосування стилів та функціоналу на фронтенді
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
 * Frontend class
 *
 * @since 1.0.0
 */
class Medici_Forms_Advanced_Frontend {

	/**
	 * Settings instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced_Settings
	 */
	private Medici_Forms_Advanced_Settings $settings;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Medici_Forms_Advanced_Settings $settings Settings instance
	 */
	public function __construct( Medici_Forms_Advanced_Settings $settings ) {
		$this->settings = $settings;

		$this->hooks();
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function hooks(): void {
		// Enqueue frontend assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );

		// Add custom CSS to forms
		add_action( 'wp_footer', [ $this, 'add_custom_css' ], 30 );

		// Modify WPForms output
		add_filter( 'wpforms_frontend_container_class', [ $this, 'add_container_classes' ], 10, 2 );

		// Anti-bot integration
		if ( $this->settings->get( 'antibot', 'enabled', false ) ) {
			add_action( 'wpforms_frontend_output_before', [ $this, 'add_antibot_scripts' ], 10 );
			add_filter( 'wpforms_process_before_form_data', [ $this, 'verify_antibot' ], 10, 2 );
		}
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_assets(): void {
		// Перевірка чи завантажувати CSS
		if ( ! $this->settings->get( 'advanced', 'load_css', true ) ) {
			return;
		}

		// Генеруємо CSS динамічно на основі налаштувань
		$custom_css = $this->generate_custom_css();

		// Додаємо inline CSS (економія файлів згідно з CLAUDE.md policy)
		wp_add_inline_style( 'wpforms-full', $custom_css );
	}

	/**
	 * Generate custom CSS based on settings
	 *
	 * @since 1.0.0
	 * @return string Generated CSS
	 */
	private function generate_custom_css(): string {
		$layout  = $this->settings->get_group( 'layout' );
		$styling = $this->settings->get_group( 'styling' );

		$css = <<<CSS
		/* Medici Forms Advanced - Custom Styles */

		/* Layout Settings */
		.wpforms-container {
			max-width: {$layout['form_max_width']} !important;
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.wpforms-form .wpforms-field {
			gap: {$layout['field_gap']} !important;
		}

		.wpforms-form input[type="text"],
		.wpforms-form input[type="email"],
		.wpforms-form input[type="tel"],
		.wpforms-form input[type="url"],
		.wpforms-form input[type="number"],
		.wpforms-form select,
		.wpforms-form textarea {
			width: {$layout['field_width']} !important;
			max-width: 100% !important;
		}

		.wpforms-form button[type="submit"],
		.wpforms-form .wpforms-submit-container button {
			width: {$layout['button_width']} !important;
			max-width: {$layout['button_max_width']} !important;
		}

		.wpforms-form .wpforms-submit-container {
			text-align: {$this->get_button_alignment_css( $layout['button_alignment'] )} !important;
		}

		/* Field Styling */
		.wpforms-form input[type="text"],
		.wpforms-form input[type="email"],
		.wpforms-form input[type="tel"],
		.wpforms-form input[type="url"],
		.wpforms-form input[type="number"],
		.wpforms-form select,
		.wpforms-form textarea {
			background-color: {$styling['field_bg_color']} !important;
			border: {$styling['field_border_width']} solid {$styling['field_border_color']} !important;
			border-radius: {$styling['field_border_radius']} !important;
			color: {$styling['field_text_color']} !important;
			font-size: {$styling['field_font_size']} !important;
			padding: {$styling['field_padding']} !important;
		}

		.wpforms-form input:focus,
		.wpforms-form select:focus,
		.wpforms-form textarea:focus {
			border-color: {$styling['field_focus_border_color']} !important;
			box-shadow: {$styling['field_focus_shadow']} !important;
			outline: none !important;
		}

		/* Button Styling */
		.wpforms-form button[type="submit"],
		.wpforms-form .wpforms-submit-container button {
			background-color: {$styling['button_bg_color']} !important;
			color: {$styling['button_text_color']} !important;
			border: none !important;
			border-radius: {$styling['button_border_radius']} !important;
			font-size: {$styling['button_font_size']} !important;
			font-weight: {$styling['button_font_weight']} !important;
			padding: {$styling['button_padding']} !important;
			transition: all 0.2s ease !important;
		}

		.wpforms-form button[type="submit"]:hover,
		.wpforms-form .wpforms-submit-container button:hover {
			background-color: {$styling['button_hover_bg_color']} !important;
			transform: {$styling['button_hover_transform']} !important;
		}

		/* Label Styling */
		.wpforms-form .wpforms-field-label {
			color: {$styling['label_color']} !important;
			font-size: {$styling['label_font_size']} !important;
			font-weight: {$styling['label_font_weight']} !important;
		}

		/* Dark Theme Support */
		[data-theme="dark"] .wpforms-form input[type="text"],
		[data-theme="dark"] .wpforms-form input[type="email"],
		[data-theme="dark"] .wpforms-form input[type="tel"],
		[data-theme="dark"] .wpforms-form input[type="url"],
		[data-theme="dark"] .wpforms-form input[type="number"],
		[data-theme="dark"] .wpforms-form select,
		[data-theme="dark"] .wpforms-form textarea {
			background-color: {$styling['dark_field_bg_color']} !important;
			border-color: {$styling['dark_field_border_color']} !important;
			color: {$styling['dark_field_text_color']} !important;
		}

		[data-theme="dark"] .wpforms-form .wpforms-field-label {
			color: {$styling['dark_label_color']} !important;
		}

		/* Responsive - mobile font size 16px to prevent zoom on iOS */
		@media (max-width: 767px) {
			.wpforms-form input[type="text"],
			.wpforms-form input[type="email"],
			.wpforms-form input[type="tel"],
			.wpforms-form input[type="url"],
			.wpforms-form input[type="number"],
			.wpforms-form select,
			.wpforms-form textarea {
				font-size: 16px !important;
			}
		}
		CSS;

		return $css;
	}

	/**
	 * Get button alignment CSS value
	 *
	 * @since 1.0.0
	 * @param string $alignment Alignment value (left, center, right, full)
	 * @return string CSS text-align value
	 */
	private function get_button_alignment_css( string $alignment ): string {
		return match ( $alignment ) {
			'center' => 'center',
			'right'  => 'right',
			'full'   => 'left',
			default  => 'left',
		};
	}

	/**
	 * Add custom CSS from advanced settings
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_custom_css(): void {
		$custom_css = $this->settings->get( 'advanced', 'custom_css', '' );

		if ( empty( $custom_css ) ) {
			return;
		}

		?>
		<style id="medici-forms-advanced-custom">
			<?php echo wp_strip_all_tags( $custom_css ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
		<?php
	}

	/**
	 * Add container classes
	 *
	 * @since 1.0.0
	 * @param array<string> $classes Container classes
	 * @param array<string, mixed> $form_data Form data
	 * @return array<string> Modified classes
	 */
	public function add_container_classes( array $classes, array $form_data ): array {
		$classes[] = 'medici-forms-advanced';

		$button_alignment = $this->settings->get( 'layout', 'button_alignment', 'left' );

		if ( 'full' === $button_alignment ) {
			$classes[] = 'medici-forms-advanced--button-full';
		}

		return $classes;
	}

	/**
	 * Add anti-bot scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_antibot_scripts(): void {
		$provider = $this->settings->get( 'antibot', 'provider', 'turnstile' );

		if ( 'turnstile' === $provider ) {
			$this->add_turnstile_scripts();
		} elseif ( 'recaptcha' === $provider ) {
			$this->add_recaptcha_scripts();
		}
	}

	/**
	 * Add Cloudflare Turnstile scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function add_turnstile_scripts(): void {
		$site_key = $this->settings->get( 'antibot', 'turnstile_site_key', '' );

		if ( empty( $site_key ) ) {
			return;
		}

		wp_enqueue_script(
			'cf-turnstile',
			'https://challenges.cloudflare.com/turnstile/v0/api.js',
			[],
			null,
			true
		);

		?>
		<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
		<?php
	}

	/**
	 * Add reCAPTCHA scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function add_recaptcha_scripts(): void {
		$site_key = $this->settings->get( 'antibot', 'recaptcha_site_key', '' );
		$type     = $this->settings->get( 'antibot', 'recaptcha_type', 'v2' );

		if ( empty( $site_key ) ) {
			return;
		}

		$script_url = 'v3' === $type
			? "https://www.google.com/recaptcha/api.js?render={$site_key}"
			: 'https://www.google.com/recaptcha/api.js';

		wp_enqueue_script(
			'google-recaptcha',
			$script_url,
			[],
			null,
			true
		);

		if ( 'v2' === $type ) {
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<?php
		}
	}

	/**
	 * Verify anti-bot token
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form_data Form data
	 * @param array<string, mixed> $entry Entry data
	 * @return array<string, mixed> Form data
	 */
	public function verify_antibot( array $form_data, array $entry ): array {
		$provider = $this->settings->get( 'antibot', 'provider', 'turnstile' );

		if ( 'turnstile' === $provider ) {
			return $this->verify_turnstile( $form_data, $entry );
		} elseif ( 'recaptcha' === $provider ) {
			return $this->verify_recaptcha( $form_data, $entry );
		}

		return $form_data;
	}

	/**
	 * Verify Cloudflare Turnstile token
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form_data Form data
	 * @param array<string, mixed> $entry Entry data
	 * @return array<string, mixed> Form data
	 */
	private function verify_turnstile( array $form_data, array $entry ): array {
		$secret_key = $this->settings->get( 'antibot', 'turnstile_secret_key', '' );

		if ( empty( $secret_key ) ) {
			return $form_data;
		}

		// Get token from POST
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';

		if ( empty( $token ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'Please complete the CAPTCHA verification.', 'medici' );
			return $form_data;
		}

		// Verify token with Cloudflare
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			[
				'body' => [
					'secret'   => $secret_key,
					'response' => $token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'CAPTCHA verification failed. Please try again.', 'medici' );
			return $form_data;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( empty( $result['success'] ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'CAPTCHA verification failed. Please try again.', 'medici' );
		}

		return $form_data;
	}

	/**
	 * Verify reCAPTCHA token
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $form_data Form data
	 * @param array<string, mixed> $entry Entry data
	 * @return array<string, mixed> Form data
	 */
	private function verify_recaptcha( array $form_data, array $entry ): array {
		$secret_key = $this->settings->get( 'antibot', 'recaptcha_secret_key', '' );
		$type       = $this->settings->get( 'antibot', 'recaptcha_type', 'v2' );

		if ( empty( $secret_key ) ) {
			return $form_data;
		}

		// Get token from POST
		$token_field = 'v3' === $type ? 'g-recaptcha-response-v3' : 'g-recaptcha-response';
		$token       = isset( $_POST[ $token_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $token_field ] ) ) : '';

		if ( empty( $token ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'Please complete the CAPTCHA verification.', 'medici' );
			return $form_data;
		}

		// Verify token with Google
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			[
				'body' => [
					'secret'   => $secret_key,
					'response' => $token,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'CAPTCHA verification failed. Please try again.', 'medici' );
			return $form_data;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( empty( $result['success'] ) ) {
			wpforms()->process->errors[ $form_data['id'] ]['header'] = __( 'CAPTCHA verification failed. Please try again.', 'medici' );
		}

		return $form_data;
	}
}

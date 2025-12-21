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

		// Реєструємо власний handle (завжди працює, навіть якщо WPForms CSS вимкнено)
		wp_register_style( 'medici-forms-advanced-styles', false, [], Medici_Forms_Advanced::VERSION );
		wp_enqueue_style( 'medici-forms-advanced-styles' );

		// Генеруємо CSS динамічно на основі налаштувань
		$custom_css = $this->generate_custom_css();

		// Додаємо inline CSS (економія файлів згідно з CLAUDE.md policy)
		wp_add_inline_style( 'medici-forms-advanced-styles', $custom_css );
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
		/* Medici Forms Advanced - Custom Styles (v1.0.0) */

		/* Layout Settings */
		.wpforms-container {
			max-width: {$layout['form_max_width']} !important;
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.wpforms-form .wpforms-field {
			gap: {$layout['field_gap']} !important;
			margin-bottom: {$layout['field_gap']} !important;
		}

		.wpforms-form input[type="text"],
		.wpforms-form input[type="email"],
		.wpforms-form input[type="tel"],
		.wpforms-form input[type="url"],
		.wpforms-form input[type="number"],
		.wpforms-form input[type="password"],
		.wpforms-form input[type="date"],
		.wpforms-form select,
		.wpforms-form textarea {
			width: {$layout['field_width']} !important;
			max-width: 100% !important;
			box-sizing: border-box !important;
		}

		.wpforms-form button[type="submit"],
		.wpforms-form .wpforms-submit-container button,
		.wpforms-form .wpforms-page-button {
			width: {$layout['button_width']} !important;
			max-width: {$layout['button_max_width']} !important;
		}

		.wpforms-form .wpforms-submit-container {
			text-align: {$this->get_button_alignment_css( $layout['button_alignment'] )} !important;
		}

		/* Field Styling - Light Theme (HIGH CONTRAST) */
		.wpforms-form input[type="text"],
		.wpforms-form input[type="email"],
		.wpforms-form input[type="tel"],
		.wpforms-form input[type="url"],
		.wpforms-form input[type="number"],
		.wpforms-form input[type="password"],
		.wpforms-form input[type="date"],
		.wpforms-form select,
		.wpforms-form textarea {
			background-color: {$styling['field_bg_color']} !important;
			border: {$styling['field_border_width']} solid {$styling['field_border_color']} !important;
			border-radius: {$styling['field_border_radius']} !important;
			color: {$styling['field_text_color']} !important;
			font-size: {$styling['field_font_size']} !important;
			padding: {$styling['field_padding']} !important;
			transition: all 0.2s ease !important;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
		}

		.wpforms-form input:focus,
		.wpforms-form select:focus,
		.wpforms-form textarea:focus {
			border-color: {$styling['field_focus_border_color']} !important;
			box-shadow: {$styling['field_focus_shadow']} !important;
			outline: none !important;
			background-color: #ffffff !important;
		}

		.wpforms-form input::placeholder,
		.wpforms-form textarea::placeholder {
			color: #9ca3af !important;
			opacity: 1 !important;
		}

		/* Button Styling - Modern & Accessible */
		.wpforms-form button[type="submit"],
		.wpforms-form .wpforms-submit-container button,
		.wpforms-form .wpforms-page-button {
			background-color: {$styling['button_bg_color']} !important;
			color: {$styling['button_text_color']} !important;
			border: none !important;
			border-radius: {$styling['button_border_radius']} !important;
			font-size: {$styling['button_font_size']} !important;
			font-weight: {$styling['button_font_weight']} !important;
			padding: {$styling['button_padding']} !important;
			transition: all 0.2s ease !important;
			cursor: pointer !important;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06) !important;
		}

		.wpforms-form button[type="submit"]:hover,
		.wpforms-form .wpforms-submit-container button:hover,
		.wpforms-form .wpforms-page-button:hover {
			background-color: {$styling['button_hover_bg_color']} !important;
			transform: {$styling['button_hover_transform']} !important;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06) !important;
		}

		.wpforms-form button[type="submit"]:focus,
		.wpforms-form .wpforms-submit-container button:focus,
		.wpforms-form .wpforms-page-button:focus {
			outline: 2px solid {$styling['button_bg_color']} !important;
			outline-offset: 2px !important;
		}

		/* Label Styling - High Contrast */
		.wpforms-form .wpforms-field-label {
			color: {$styling['label_color']} !important;
			font-size: {$styling['label_font_size']} !important;
			font-weight: {$styling['label_font_weight']} !important;
			margin-bottom: 0.5rem !important;
		}

		.wpforms-form .wpforms-field-sublabel {
			color: #6b7280 !important;
			font-size: 0.875rem !important;
		}

		/* Required Field Indicator */
		.wpforms-form .wpforms-required-label {
			color: #dc2626 !important;
			font-weight: 600 !important;
		}

		/* Dark Theme Support - HIGH CONTRAST для доступності */
		[data-theme="dark"] .wpforms-form input[type="text"],
		[data-theme="dark"] .wpforms-form input[type="email"],
		[data-theme="dark"] .wpforms-form input[type="tel"],
		[data-theme="dark"] .wpforms-form input[type="url"],
		[data-theme="dark"] .wpforms-form input[type="number"],
		[data-theme="dark"] .wpforms-form input[type="password"],
		[data-theme="dark"] .wpforms-form input[type="date"],
		[data-theme="dark"] .wpforms-form select,
		[data-theme="dark"] .wpforms-form textarea {
			background-color: {$styling['dark_field_bg_color']} !important;
			border-color: {$styling['dark_field_border_color']} !important;
			color: {$styling['dark_field_text_color']} !important;
			box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
		}

		[data-theme="dark"] .wpforms-form input:focus,
		[data-theme="dark"] .wpforms-form select:focus,
		[data-theme="dark"] .wpforms-form textarea:focus {
			border-color: #60a5fa !important;
			background-color: #1e293b !important;
			box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2) !important;
		}

		[data-theme="dark"] .wpforms-form input::placeholder,
		[data-theme="dark"] .wpforms-form textarea::placeholder {
			color: #94a3b8 !important;
		}

		[data-theme="dark"] .wpforms-form .wpforms-field-label {
			color: {$styling['dark_label_color']} !important;
		}

		[data-theme="dark"] .wpforms-form .wpforms-field-sublabel {
			color: #94a3b8 !important;
		}

		/* Error States - High Visibility */
		.wpforms-form .wpforms-error,
		.wpforms-form input.wpforms-error,
		.wpforms-form textarea.wpforms-error,
		.wpforms-form select.wpforms-error {
			border-color: #dc2626 !important;
			background-color: #fef2f2 !important;
		}

		[data-theme="dark"] .wpforms-form .wpforms-error,
		[data-theme="dark"] .wpforms-form input.wpforms-error,
		[data-theme="dark"] .wpforms-form textarea.wpforms-error,
		[data-theme="dark"] .wpforms-form select.wpforms-error {
			border-color: #ef4444 !important;
			background-color: #7f1d1d !important;
		}

		/* Responsive - mobile font size 16px to prevent zoom on iOS */
		@media (max-width: 767px) {
			.wpforms-form input[type="text"],
			.wpforms-form input[type="email"],
			.wpforms-form input[type="tel"],
			.wpforms-form input[type="url"],
			.wpforms-form input[type="number"],
			.wpforms-form input[type="password"],
			.wpforms-form input[type="date"],
			.wpforms-form select,
			.wpforms-form textarea {
				font-size: 16px !important;
			}

			.wpforms-form button[type="submit"],
			.wpforms-form .wpforms-submit-container button {
				width: 100% !important;
				max-width: 100% !important;
			}
		}

		/* Accessibility - Focus Visible */
		.wpforms-form *:focus-visible {
			outline: 2px solid #3b82f6 !important;
			outline-offset: 2px !important;
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

	/**
	 * Render email template
	 *
	 * @since 1.0.0
	 * @param string $template_style Template style (modern, classic, minimal)
	 * @param string $form_title Form title
	 * @param array<string, mixed> $fields Form fields data
	 * @return string Rendered HTML email
	 */
	public function render_email_template( string $template_style, string $form_title, array $fields ): string {
		// Check if custom templates enabled
		if ( ! $this->settings->get( 'email', 'custom_templates', false ) ) {
			return $this->render_default_email( $form_title, $fields );
		}

		// Get template file
		$template_file = match ( $template_style ) {
			'classic' => 'email-classic.php',
			'minimal' => 'email-minimal.php',
			default   => 'email-modern.php',
		};

		$template_path = __DIR__ . '/templates/' . $template_file;

		if ( ! file_exists( $template_path ) ) {
			return $this->render_default_email( $form_title, $fields );
		}

		// Prepare template variables
		$logo_url    = $this->settings->get( 'email', 'logo_url', '' );
		$footer_text = $this->settings->get( 'email', 'footer_text', '' );
		$site_name   = get_bloginfo( 'name' );
		$site_url    = home_url();

		// Render template
		ob_start();
		include $template_path;
		return (string) ob_get_clean();
	}

	/**
	 * Render default email (fallback)
	 *
	 * @since 1.0.0
	 * @param string $form_title Form title
	 * @param array<string, mixed> $fields Form fields data
	 * @return string Rendered HTML email
	 */
	private function render_default_email( string $form_title, array $fields ): string {
		$html = '<h2>' . esc_html( $form_title ) . '</h2>';
		$html .= '<p><strong>Дата:</strong> ' . esc_html( gmdate( 'd.m.Y H:i' ) ) . '</p>';
		$html .= '<hr>';

		foreach ( $fields as $label => $value ) {
			$html .= '<p><strong>' . esc_html( $label ) . ':</strong><br>';
			$html .= wp_kses_post( nl2br( $value ) ) . '</p>';
		}

		return $html;
	}
}

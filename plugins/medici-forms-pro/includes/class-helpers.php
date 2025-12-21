<?php
/**
 * Helper Functions.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Helpers Class.
 *
 * @since 1.0.0
 */
class Helpers {

	/**
	 * Get form by ID.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return \WP_Post|null
	 */
	public static function get_form( int $form_id ): ?\WP_Post {
		$form = get_post( $form_id );

		if ( ! $form || 'medici_form' !== $form->post_type ) {
			return null;
		}

		return $form;
	}

	/**
	 * Get form fields.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_form_fields( int $form_id ): array {
		$fields = get_post_meta( $form_id, '_medici_form_fields', true );
		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Get form settings.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return array<string, mixed>
	 */
	public static function get_form_settings( int $form_id ): array {
		$settings = get_post_meta( $form_id, '_medici_form_settings', true );
		return is_array( $settings ) ? $settings : self::get_default_form_settings();
	}

	/**
	 * Get default form settings.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public static function get_default_form_settings(): array {
		return array(
			// Submit button.
			'submit_text'          => __( 'Відправити заявку', 'medici-forms-pro' ),
			'submit_processing'    => __( 'Відправляється...', 'medici-forms-pro' ),

			// Messages.
			'success_message'      => __( 'Дякуємо! Ваша заявка успішно відправлена.', 'medici-forms-pro' ),
			'error_message'        => __( 'Виникла помилка. Спробуйте ще раз.', 'medici-forms-pro' ),

			// Redirect.
			'enable_redirect'      => false,
			'redirect_url'         => '',

			// Email notifications.
			'enable_admin_email'   => true,
			'admin_email_to'       => get_option( 'admin_email' ),
			'admin_email_subject'  => __( 'Нова заявка з форми', 'medici-forms-pro' ),
			'enable_user_email'    => false,
			'user_email_subject'   => __( 'Дякуємо за вашу заявку', 'medici-forms-pro' ),
			'user_email_message'   => '',

			// Anti-spam.
			'enable_honeypot'      => true,
			'enable_time_check'    => true,
			'require_consent'      => true,
			'consent_text'         => __( 'Я даю згоду на обробку моїх персональних даних відповідно до Політики конфіденційності', 'medici-forms-pro' ),

			// Styling.
			'form_class'           => '',
			'label_position'       => 'top', // top, inline, hidden.
			'field_size'           => 'medium', // small, medium, large.

			// Advanced.
			'enable_ajax'          => true,
			'store_entries'        => true,
			'entry_title_format'   => '{name} - {date}',
		);
	}

	/**
	 * Get client IP address.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs.
				if ( str_contains( $ip, ',' ) ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get available field types.
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_field_types(): array {
		return array(
			'text'       => array(
				'label' => __( 'Текст', 'medici-forms-pro' ),
				'icon'  => 'dashicons-editor-textcolor',
				'group' => 'basic',
			),
			'email'      => array(
				'label' => __( 'Email', 'medici-forms-pro' ),
				'icon'  => 'dashicons-email',
				'group' => 'basic',
			),
			'phone'      => array(
				'label' => __( 'Телефон', 'medici-forms-pro' ),
				'icon'  => 'dashicons-phone',
				'group' => 'basic',
			),
			'textarea'   => array(
				'label' => __( 'Текстове поле', 'medici-forms-pro' ),
				'icon'  => 'dashicons-editor-paragraph',
				'group' => 'basic',
			),
			'select'     => array(
				'label' => __( 'Випадаючий список', 'medici-forms-pro' ),
				'icon'  => 'dashicons-arrow-down-alt2',
				'group' => 'choice',
			),
			'radio'      => array(
				'label' => __( 'Радіо кнопки', 'medici-forms-pro' ),
				'icon'  => 'dashicons-marker',
				'group' => 'choice',
			),
			'checkbox'   => array(
				'label' => __( 'Чекбокси', 'medici-forms-pro' ),
				'icon'  => 'dashicons-yes',
				'group' => 'choice',
			),
			'name'       => array(
				'label' => __( "Ім'я", 'medici-forms-pro' ),
				'icon'  => 'dashicons-admin-users',
				'group' => 'advanced',
			),
			'url'        => array(
				'label' => __( 'URL', 'medici-forms-pro' ),
				'icon'  => 'dashicons-admin-links',
				'group' => 'advanced',
			),
			'number'     => array(
				'label' => __( 'Число', 'medici-forms-pro' ),
				'icon'  => 'dashicons-calculator',
				'group' => 'advanced',
			),
			'date'       => array(
				'label' => __( 'Дата', 'medici-forms-pro' ),
				'icon'  => 'dashicons-calendar-alt',
				'group' => 'advanced',
			),
			'time'       => array(
				'label' => __( 'Час', 'medici-forms-pro' ),
				'icon'  => 'dashicons-clock',
				'group' => 'advanced',
			),
			'file'       => array(
				'label' => __( 'Файл', 'medici-forms-pro' ),
				'icon'  => 'dashicons-upload',
				'group' => 'advanced',
			),
			'hidden'     => array(
				'label' => __( 'Приховане поле', 'medici-forms-pro' ),
				'icon'  => 'dashicons-hidden',
				'group' => 'advanced',
			),
			'html'       => array(
				'label' => __( 'HTML', 'medici-forms-pro' ),
				'icon'  => 'dashicons-editor-code',
				'group' => 'layout',
			),
			'divider'    => array(
				'label' => __( 'Роздільник', 'medici-forms-pro' ),
				'icon'  => 'dashicons-minus',
				'group' => 'layout',
			),
			'heading'    => array(
				'label' => __( 'Заголовок', 'medici-forms-pro' ),
				'icon'  => 'dashicons-heading',
				'group' => 'layout',
			),
		);
	}

	/**
	 * Generate unique field ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function generate_field_id(): string {
		return 'field_' . bin2hex( random_bytes( 4 ) );
	}

	/**
	 * Format phone number.
	 *
	 * @since 1.0.0
	 * @param string $phone Phone number.
	 * @return string
	 */
	public static function format_phone( string $phone ): string {
		// Remove all non-numeric characters.
		$digits = preg_replace( '/[^0-9]/', '', $phone );

		if ( null === $digits ) {
			return $phone;
		}

		// Ukrainian format.
		if ( strlen( $digits ) === 12 && str_starts_with( $digits, '380' ) ) {
			return '+' . substr( $digits, 0, 3 ) . ' ' .
				   substr( $digits, 3, 2 ) . ' ' .
				   substr( $digits, 5, 3 ) . ' ' .
				   substr( $digits, 8, 2 ) . ' ' .
				   substr( $digits, 10, 2 );
		}

		if ( strlen( $digits ) === 10 && str_starts_with( $digits, '0' ) ) {
			return '+38 ' . substr( $digits, 0, 3 ) . ' ' .
				   substr( $digits, 3, 3 ) . ' ' .
				   substr( $digits, 6, 2 ) . ' ' .
				   substr( $digits, 8, 2 );
		}

		return $phone;
	}

	/**
	 * Get UTM parameters from request.
	 *
	 * @since 1.0.0
	 * @return array<string, string>
	 */
	public static function get_utm_params(): array {
		$utm_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
		$params   = array();

		foreach ( $utm_keys as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST[ $key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$params[ $key ] = sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
			} elseif ( isset( $_COOKIE[ $key ] ) ) {
				$params[ $key ] = sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) );
			}
		}

		return $params;
	}
}

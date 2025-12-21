<?php
/**
 * Form Processor Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Processor;

use MediciForms\Helpers;
use MediciForms\Security;
use MediciForms\Plugin;
use MediciForms\Bot_Detect;
use MediciForms\Notifications\Email;
use MediciForms\Notifications\Webhook;
use MediciForms\Entries\Entries;

/**
 * Form Processor Class.
 *
 * @since 1.0.0
 */
class Form_Processor {

	/**
	 * Validator instance.
	 *
	 * @var Validator
	 */
	private Validator $validator;

	/**
	 * Sanitizer instance.
	 *
	 * @var Sanitizer
	 */
	private Sanitizer $sanitizer;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->validator = new Validator();
		$this->sanitizer = new Sanitizer();
	}

	/**
	 * Process form submission.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function process(): array {
		// Get form ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( 0 === $form_id ) {
			return $this->error( __( 'ID форми не вказано.', 'medici-forms-pro' ) );
		}

		// Verify nonce.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$nonce = isset( $_POST['mf_nonce'] ) ? wp_unslash( $_POST['mf_nonce'] ) : '';
		if ( ! Security::verify_nonce( (string) $nonce, $form_id ) ) {
			return $this->error( __( 'Помилка безпеки. Оновіть сторінку і спробуйте знову.', 'medici-forms-pro' ) );
		}

		// Get form.
		$form = \MediciForms\Post_Types\Form::get( $form_id );
		if ( ! $form ) {
			return $this->error( __( 'Форма не знайдена.', 'medici-forms-pro' ) );
		}

		$fields   = Helpers::get_form_fields( $form_id );
		$settings = Helpers::get_form_settings( $form_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_data = $_POST;

		// Anti-spam checks.
		$spam_check = $this->check_spam( $raw_data, $form_id, $settings );
		if ( ! $spam_check['passed'] ) {
			return $this->error( $spam_check['message'] );
		}

		// reCAPTCHA check.
		if ( Plugin::get_option( 'enable_recaptcha', false ) ) {
			$recaptcha_token = isset( $raw_data['recaptcha_token'] ) ? sanitize_text_field( $raw_data['recaptcha_token'] ) : '';
			$recaptcha_token = isset( $raw_data['g-recaptcha-response'] ) ? sanitize_text_field( $raw_data['g-recaptcha-response'] ) : $recaptcha_token;

			if ( ! Security::verify_recaptcha( $recaptcha_token ) ) {
				return $this->error( __( 'Перевірка reCAPTCHA не пройдена.', 'medici-forms-pro' ) );
			}
		}

		// Get submitted fields.
		$submitted_fields = isset( $raw_data['fields'] ) && is_array( $raw_data['fields'] ) ? $raw_data['fields'] : array();

		// Sanitize data.
		$sanitized_data = $this->sanitizer->sanitize( $submitted_fields, $fields );

		// Validate data.
		$validation = $this->validator->validate( $sanitized_data, $fields, $settings );
		if ( ! $validation['valid'] ) {
			return $this->error( $validation['message'], $validation['errors'] );
		}

		// Prepare entry data.
		$entry_data = $this->prepare_entry_data( $sanitized_data, $fields, $raw_data );

		// Store entry.
		$entry_id = 0;
		if ( ! empty( $settings['store_entries'] ) || Plugin::get_option( 'log_entries', true ) ) {
			$entry_id = $this->store_entry( $form_id, $entry_data, $raw_data );
		}

		// Send notifications.
		$this->send_notifications( $form_id, $form, $entry_data, $settings, $entry_id );

		// Fire action for integrations.
		do_action( 'medici_forms_submission_complete', $form_id, $entry_data, $entry_id );

		return array(
			'success'      => true,
			'message'      => $settings['success_message'],
			'entry_id'     => $entry_id,
			'redirect_url' => ! empty( $settings['enable_redirect'] ) ? $settings['redirect_url'] : '',
		);
	}

	/**
	 * Check for spam.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data     Form data.
	 * @param int                  $form_id  Form ID.
	 * @param array<string, mixed> $settings Form settings.
	 * @return array{passed: bool, message: string}
	 */
	private function check_spam( array $data, int $form_id, array $settings ): array {
		// Bot detection check.
		if ( Plugin::get_option( 'enable_bot_detection', true ) ) {
			$bot_detector = Bot_Detect::get_instance();
			if ( $bot_detector->is_bot() ) {
				return array(
					'passed'  => false,
					'message' => __( 'Автоматичні запити не дозволені.', 'medici-forms-pro' ),
				);
			}
		}

		// Honeypot check.
		if ( ! empty( $settings['enable_honeypot'] ) ) {
			if ( ! Security::verify_honeypot( $data ) ) {
				return array(
					'passed'  => false,
					'message' => __( 'Виявлено підозрілу активність.', 'medici-forms-pro' ),
				);
			}
		}

		// Time check.
		if ( ! empty( $settings['enable_time_check'] ) ) {
			$min_time = Plugin::get_option( 'min_submission_time', 3 );
			if ( ! Security::verify_time_check( $data, $min_time ) ) {
				return array(
					'passed'  => false,
					'message' => __( 'Форма відправлена занадто швидко. Спробуйте ще раз.', 'medici-forms-pro' ),
				);
			}
		}

		// Rate limiting.
		$ip = Helpers::get_client_ip();
		if ( ! Security::check_rate_limit( $form_id, $ip ) ) {
			return array(
				'passed'  => false,
				'message' => __( 'Занадто багато спроб. Спробуйте пізніше.', 'medici-forms-pro' ),
			);
		}

		// Spam patterns.
		$submitted_fields = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
		if ( Security::detect_spam_patterns( $submitted_fields ) ) {
			return array(
				'passed'  => false,
				'message' => __( 'Повідомлення містить заборонений контент.', 'medici-forms-pro' ),
			);
		}

		return array(
			'passed'  => true,
			'message' => '',
		);
	}

	/**
	 * Prepare entry data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>             $sanitized_data Sanitized field data.
	 * @param array<int, array<string, mixed>> $fields         Form fields.
	 * @param array<string, mixed>             $raw_data       Raw POST data.
	 * @return array<string, mixed>
	 */
	private function prepare_entry_data( array $sanitized_data, array $fields, array $raw_data ): array {
		$entry_data = array();

		foreach ( $fields as $field ) {
			$field_id = $field['id'] ?? '';
			$type     = $field['type'] ?? 'text';

			if ( ! isset( $sanitized_data[ $field_id ] ) ) {
				continue;
			}

			$value = $sanitized_data[ $field_id ];

			// Handle name field.
			if ( 'name' === $type && is_array( $value ) ) {
				$value = trim( ( $value['first'] ?? '' ) . ' ' . ( $value['last'] ?? '' ) );
			}

			// Handle checkbox array.
			if ( 'checkbox' === $type && is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$entry_data[ $field_id ] = array(
				'label' => $field['label'] ?? '',
				'type'  => $type,
				'value' => $value,
			);
		}

		// Add metadata.
		$entry_data['_meta'] = array(
			'page_url'   => isset( $raw_data['page_url'] ) ? esc_url_raw( wp_unslash( $raw_data['page_url'] ) ) : '',
			'user_ip'    => Helpers::get_client_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'user_id'    => get_current_user_id(),
			'utm'        => Helpers::get_utm_params(),
		);

		return $entry_data;
	}

	/**
	 * Store entry in database.
	 *
	 * @since 1.0.0
	 * @param int                  $form_id    Form ID.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param array<string, mixed> $raw_data   Raw POST data.
	 * @return int Entry ID.
	 */
	private function store_entry( int $form_id, array $entry_data, array $raw_data ): int {
		global $wpdb;

		$meta     = $entry_data['_meta'] ?? array();
		$utm      = $meta['utm'] ?? array();
		$table    = $wpdb->prefix . 'medici_form_entries';

		// Prepare entry data for storage (without meta).
		$storage_data = $entry_data;
		unset( $storage_data['_meta'] );

		$insert_data = array(
			'form_id'      => $form_id,
			'user_id'      => $meta['user_id'] ?? 0,
			'user_ip'      => $meta['user_ip'] ?? '',
			'user_agent'   => $meta['user_agent'] ?? '',
			'entry_data'   => wp_json_encode( $storage_data ),
			'status'       => 'unread',
			'source_url'   => $meta['page_url'] ?? '',
			'utm_source'   => $utm['utm_source'] ?? '',
			'utm_medium'   => $utm['utm_medium'] ?? '',
			'utm_campaign' => $utm['utm_campaign'] ?? '',
			'created_at'   => current_time( 'mysql' ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $insert_data );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Send notifications.
	 *
	 * @since 1.0.0
	 * @param int                  $form_id    Form ID.
	 * @param \WP_Post             $form       Form post object.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param array<string, mixed> $settings   Form settings.
	 * @param int                  $entry_id   Entry ID.
	 */
	private function send_notifications( int $form_id, \WP_Post $form, array $entry_data, array $settings, int $entry_id ): void {
		// Admin email.
		if ( ! empty( $settings['enable_admin_email'] ) ) {
			$email = new Email();
			$email->send_admin_notification( $form, $entry_data, $settings, $entry_id );
		}

		// User email (autoresponder).
		if ( ! empty( $settings['enable_user_email'] ) ) {
			$email = new Email();
			$email->send_user_notification( $form, $entry_data, $settings );
		}

		// Webhook.
		if ( Plugin::get_option( 'webhook_enabled', false ) ) {
			$webhook = new Webhook();
			$webhook->send( $form_id, $entry_data, $entry_id );
		}
	}

	/**
	 * Return error response.
	 *
	 * @since 1.0.0
	 * @param string               $message Error message.
	 * @param array<string, string> $errors  Field errors.
	 * @return array<string, mixed>
	 */
	private function error( string $message, array $errors = array() ): array {
		return array(
			'success' => false,
			'message' => $message,
			'errors'  => $errors,
		);
	}
}

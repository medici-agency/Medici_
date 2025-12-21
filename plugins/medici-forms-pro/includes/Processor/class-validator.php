<?php
/**
 * Validator Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Processor;

/**
 * Validator Class.
 *
 * @since 1.0.0
 */
class Validator {

	/**
	 * Validation errors.
	 *
	 * @var array<string, string>
	 */
	private array $errors = array();

	/**
	 * Validate form data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>             $data     Sanitized field data.
	 * @param array<int, array<string, mixed>> $fields   Form fields.
	 * @param array<string, mixed>             $settings Form settings.
	 * @return array{valid: bool, message: string, errors: array<string, string>}
	 */
	public function validate( array $data, array $fields, array $settings ): array {
		$this->errors = array();

		foreach ( $fields as $field ) {
			$field_id = $field['id'] ?? '';
			$type     = $field['type'] ?? 'text';
			$label    = $field['label'] ?? $field_id;
			$required = ! empty( $field['required'] );

			// Skip non-input fields.
			if ( in_array( $type, array( 'html', 'divider', 'heading' ), true ) ) {
				continue;
			}

			$value = $data[ $field_id ] ?? null;

			// Required check.
			if ( $required && $this->is_empty( $value, $type ) ) {
				$this->errors[ $field_id ] = sprintf(
					/* translators: %s: field label */
					__( "Поле '%s' є обов'язковим.", 'medici-forms-pro' ),
					$label
				);
				continue;
			}

			// Skip empty non-required fields.
			if ( $this->is_empty( $value, $type ) ) {
				continue;
			}

			// Type-specific validation.
			$this->validate_field_type( $field_id, $type, $value, $label );
		}

		// Consent check.
		if ( ! empty( $settings['require_consent'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$consent = isset( $_POST['consent'] ) ? absint( $_POST['consent'] ) : 0;
			if ( ! $consent ) {
				$this->errors['consent'] = __( 'Необхідно погодитись з обробкою персональних даних.', 'medici-forms-pro' );
			}
		}

		if ( ! empty( $this->errors ) ) {
			return array(
				'valid'   => false,
				'message' => __( 'Будь ласка, виправте помилки в формі.', 'medici-forms-pro' ),
				'errors'  => $this->errors,
			);
		}

		return array(
			'valid'   => true,
			'message' => '',
			'errors'  => array(),
		);
	}

	/**
	 * Check if value is empty.
	 *
	 * @since 1.0.0
	 * @param mixed  $value Field value.
	 * @param string $type  Field type.
	 * @return bool
	 */
	private function is_empty( mixed $value, string $type ): bool {
		if ( null === $value ) {
			return true;
		}

		if ( is_array( $value ) ) {
			// Name field.
			if ( 'name' === $type ) {
				return empty( trim( $value['first'] ?? '' ) );
			}
			// Checkbox.
			return empty( $value );
		}

		return '' === trim( (string) $value );
	}

	/**
	 * Validate field by type.
	 *
	 * @since 1.0.0
	 * @param string $field_id Field ID.
	 * @param string $type     Field type.
	 * @param mixed  $value    Field value.
	 * @param string $label    Field label.
	 */
	private function validate_field_type( string $field_id, string $type, mixed $value, string $label ): void {
		switch ( $type ) {
			case 'email':
				if ( ! is_email( (string) $value ) ) {
					$this->errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( "Поле '%s' має містити коректну email адресу.", 'medici-forms-pro' ),
						$label
					);
				}
				break;

			case 'phone':
				if ( ! $this->validate_phone( (string) $value ) ) {
					$this->errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( "Поле '%s' має містити коректний номер телефону.", 'medici-forms-pro' ),
						$label
					);
				}
				break;

			case 'url':
				if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$this->errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( "Поле '%s' має містити коректну URL адресу.", 'medici-forms-pro' ),
						$label
					);
				}
				break;

			case 'number':
				if ( ! is_numeric( $value ) ) {
					$this->errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( "Поле '%s' має містити число.", 'medici-forms-pro' ),
						$label
					);
				}
				break;

			case 'date':
				if ( ! $this->validate_date( (string) $value ) ) {
					$this->errors[ $field_id ] = sprintf(
						/* translators: %s: field label */
						__( "Поле '%s' має містити коректну дату.", 'medici-forms-pro' ),
						$label
					);
				}
				break;
		}
	}

	/**
	 * Validate phone number.
	 *
	 * @since 1.0.0
	 * @param string $phone Phone number.
	 * @return bool
	 */
	private function validate_phone( string $phone ): bool {
		// Remove all non-numeric except +.
		$digits = preg_replace( '/[^0-9+]/', '', $phone );

		if ( null === $digits ) {
			return false;
		}

		// Check minimum length.
		$length = strlen( str_replace( '+', '', $digits ) );
		return $length >= 10 && $length <= 15;
	}

	/**
	 * Validate date.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return bool
	 */
	private function validate_date( string $date ): bool {
		// Try common formats.
		$formats = array( 'Y-m-d', 'd.m.Y', 'd/m/Y', 'm/d/Y' );

		foreach ( $formats as $format ) {
			$parsed = \DateTime::createFromFormat( $format, $date );
			if ( $parsed && $parsed->format( $format ) === $date ) {
				return true;
			}
		}

		return false;
	}
}

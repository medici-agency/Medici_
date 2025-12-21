<?php
/**
 * Sanitizer Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Processor;

/**
 * Sanitizer Class.
 *
 * @since 1.0.0
 */
class Sanitizer {

	/**
	 * Sanitize form data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>             $data   Submitted field data.
	 * @param array<int, array<string, mixed>> $fields Form fields definition.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data, array $fields ): array {
		$sanitized = array();

		foreach ( $fields as $field ) {
			$field_id = $field['id'] ?? '';
			$type     = $field['type'] ?? 'text';

			if ( ! isset( $data[ $field_id ] ) ) {
				continue;
			}

			$value = $data[ $field_id ];

			$sanitized[ $field_id ] = $this->sanitize_value( $value, $type );
		}

		return $sanitized;
	}

	/**
	 * Sanitize single value by field type.
	 *
	 * @since 1.0.0
	 * @param mixed  $value Field value.
	 * @param string $type  Field type.
	 * @return mixed
	 */
	private function sanitize_value( mixed $value, string $type ): mixed {
		// Handle arrays (checkboxes, name fields).
		if ( is_array( $value ) ) {
			return $this->sanitize_array( $value, $type );
		}

		// Ensure string.
		$value = (string) $value;

		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'phone':
				return $this->sanitize_phone( $value );

			case 'number':
				return is_numeric( $value ) ? $value : '';

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'html':
				return wp_kses_post( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize array value.
	 *
	 * @since 1.0.0
	 * @param array<string|int, mixed> $value Array value.
	 * @param string                   $type  Field type.
	 * @return array<string|int, mixed>
	 */
	private function sanitize_array( array $value, string $type ): array {
		// Name field.
		if ( 'name' === $type ) {
			return array(
				'first' => sanitize_text_field( $value['first'] ?? '' ),
				'last'  => sanitize_text_field( $value['last'] ?? '' ),
			);
		}

		// Checkbox (multiple values).
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize phone number.
	 *
	 * @since 1.0.0
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function sanitize_phone( string $phone ): string {
		// Allow only digits, +, -, spaces, parentheses.
		$sanitized = preg_replace( '/[^0-9+\-\s\(\)]/', '', $phone );
		return $sanitized ?? '';
	}
}

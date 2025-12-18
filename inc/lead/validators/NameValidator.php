<?php
/**
 * Name Validator
 *
 * Validates customer names.
 *
 * @package    Medici_Agency
 * @subpackage Lead\Validation
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead\Validators;

use Medici\Lead\ValidatorInterface;
use Medici\Lead\ValidationResult;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Name Validator Class
 *
 * Validates name format and detects suspicious patterns.
 *
 * @since 2.0.0
 */
final class NameValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'name';

	/**
	 * Minimum name length
	 */
	private const MIN_LENGTH = 2;

	/**
	 * Suspicious names
	 *
	 * @var array<string>
	 */
	private const SUSPICIOUS_NAMES = array(
		'test',
		'testing',
		'demo',
		'asd',
		'asdf',
		'qwe',
		'qwerty',
		'xxx',
		'abc',
		'123',
		'admin',
		'null',
		'undefined',
	);

	/**
	 * Get name
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getName(): string {
		return self::NAME;
	}

	/**
	 * Validate name
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with 'name' key.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$name      = sanitize_text_field( (string) ( $data['name'] ?? '' ) );
		$warnings  = array();
		$score_mod = 0;

		// Required check.
		if ( empty( $name ) ) {
			return ValidationResult::error(
				array( __( 'Ім\'я є обов\'язковим полем', 'medici.agency' ) )
			);
		}

		// Length check.
		if ( strlen( $name ) < self::MIN_LENGTH ) {
			return ValidationResult::error(
				array( __( 'Ім\'я занадто коротке', 'medici.agency' ) )
			);
		}

		// Suspicious name check.
		$name_lower = strtolower( $name );
		if ( in_array( $name_lower, self::SUSPICIOUS_NAMES, true ) ) {
			$warnings[] = __( 'Ім\'я виглядає підозрілим', 'medici.agency' );
			$score_mod -= 30;
		}

		// Repeated characters check.
		if ( preg_match( '/(.)\1{4,}/', $name ) ) {
			$warnings[] = __( 'Ім\'я містить повторювані символи', 'medici.agency' );
			$score_mod -= 20;
		}

		return ValidationResult::success(
			array( 'name' => $name ),
			$score_mod,
			$warnings
		);
	}

	/**
	 * Sanitize name
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['name'] ) ) {
			$data['name'] = sanitize_text_field( (string) $data['name'] );
		}
		return $data;
	}
}

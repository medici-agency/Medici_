<?php
/**
 * Phone Validator
 *
 * Validates and normalizes phone numbers.
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
 * Phone Validator Class
 *
 * Validates phone format and normalizes Ukrainian phone numbers.
 *
 * @since 2.0.0
 */
final class PhoneValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'phone';

	/**
	 * Ukrainian phone pattern
	 */
	private const UA_PATTERN = '/^\+380\d{9}$/';

	/**
	 * Minimum phone length
	 */
	private const MIN_LENGTH = 10;

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
	 * Validate phone
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with 'phone' key.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$phone     = (string) ( $data['phone'] ?? '' );
		$warnings  = array();
		$score_mod = 0;

		// Phone is optional.
		if ( empty( $phone ) ) {
			return ValidationResult::success();
		}

		// Remove non-digit chars (except +).
		$phone = preg_replace( '/[^0-9+]/', '', $phone ) ?: '';

		// Normalize Ukrainian phone.
		$phone = $this->normalizeUkrainianPhone( $phone );

		// Validate format.
		if ( ! preg_match( self::UA_PATTERN, $phone ) && strlen( $phone ) < self::MIN_LENGTH ) {
			$warnings[] = __( 'Телефон може бути невірним', 'medici.agency' );
			$score_mod -= 5;
		}

		// Has phone bonus.
		$score_mod += 20;

		return ValidationResult::success(
			array( 'phone' => $phone ),
			$score_mod,
			$warnings
		);
	}

	/**
	 * Sanitize phone
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['phone'] ) ) {
			$phone         = preg_replace( '/[^0-9+]/', '', (string) $data['phone'] ) ?: '';
			$data['phone'] = $this->normalizeUkrainianPhone( $phone );
		}
		return $data;
	}

	/**
	 * Normalize Ukrainian phone number
	 *
	 * @since 2.0.0
	 * @param string $phone Phone number.
	 * @return string Normalized phone.
	 */
	private function normalizeUkrainianPhone( string $phone ): string {
		// Format: 0XXXXXXXXX → +380XXXXXXXXX.
		if ( preg_match( '/^0\d{9}$/', $phone ) ) {
			return '+38' . $phone;
		}

		// Format: 380XXXXXXXXX → +380XXXXXXXXX.
		if ( preg_match( '/^380\d{9}$/', $phone ) ) {
			return '+' . $phone;
		}

		return $phone;
	}
}

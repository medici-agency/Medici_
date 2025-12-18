<?php
/**
 * Spam Validator
 *
 * Detects spam submissions via honeypots and timing.
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
 * Spam Validator Class
 *
 * Detects spam using honeypot fields and submission timing.
 *
 * @since 2.0.0
 */
final class SpamValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'spam';

	/**
	 * Honeypot field names
	 *
	 * @var array<string>
	 */
	private const HONEYPOT_FIELDS = array(
		'website',
		'url',
		'company_website',
		'address',
		'fax',
	);

	/**
	 * Minimum form time (seconds)
	 */
	private const MIN_FORM_TIME = 3;

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
	 * Validate for spam
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Form data.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$warnings  = array();
		$score_mod = 0;

		// Honeypot check.
		foreach ( self::HONEYPOT_FIELDS as $field ) {
			if ( ! empty( $data[ $field ] ) ) {
				return ValidationResult::error(
					array( __( 'Spam detection triggered', 'medici.agency' ) ),
					-100
				);
			}
		}

		// Timing check (too fast = bot).
		$form_time = (int) ( $data['form_time'] ?? 0 );
		if ( $form_time > 0 && $form_time < self::MIN_FORM_TIME ) {
			$warnings[] = __( 'Форма заповнена занадто швидко', 'medici.agency' );
			$score_mod -= 30;
		}

		// Consent check.
		if ( empty( $data['consent'] ) ) {
			return ValidationResult::error(
				array( __( 'Необхідна згода на обробку персональних даних', 'medici.agency' ) )
			);
		}

		return ValidationResult::success( array(), $score_mod, $warnings );
	}

	/**
	 * Sanitize data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		// Remove honeypot fields from data.
		foreach ( self::HONEYPOT_FIELDS as $field ) {
			unset( $data[ $field ] );
		}
		return $data;
	}
}

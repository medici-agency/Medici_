<?php
/**
 * Email Validator
 *
 * Validates email addresses for leads.
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
 * Email Validator Class
 *
 * Validates email format, blocks disposable emails, detects test patterns.
 *
 * @since 2.0.0
 */
final class EmailValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'email';

	/**
	 * Temporary/disposable email domains to block
	 *
	 * @var array<string>
	 */
	private const BLOCKED_DOMAINS = array(
		'tempmail.com',
		'guerrillamail.com',
		'10minutemail.com',
		'mailinator.com',
		'throwaway.email',
		'temp-mail.org',
		'fakeinbox.com',
		'trashmail.com',
		'sharklasers.com',
		'guerrillamail.info',
		'grr.la',
		'dispostable.com',
		'yopmail.com',
		'getairmail.com',
		'mohmal.com',
	);

	/**
	 * Test email patterns
	 *
	 * @var array<string>
	 */
	private const TEST_PATTERNS = array(
		'/^test[@.]/',
		'/^demo[@.]/',
		'/^example[@.]/',
		'/^fake[@.]/',
		'/^asdf[@.]/',
		'/^qwerty[@.]/',
		'/[@.]test\./',
		'/[@.]example\./',
	);

	/**
	 * Free email domains
	 *
	 * @var array<string>
	 */
	private const FREE_DOMAINS = array(
		'gmail.com',
		'yahoo.com',
		'hotmail.com',
		'outlook.com',
		'ukr.net',
		'i.ua',
		'meta.ua',
		'mail.ru',
		'yandex.ru',
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
	 * Validate email
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with 'email' key.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$email     = sanitize_email( (string) ( $data['email'] ?? '' ) );
		$errors    = array();
		$warnings  = array();
		$score_mod = 0;

		// Required check.
		if ( empty( $email ) ) {
			return ValidationResult::error(
				array( __( 'Email є обов\'язковим полем', 'medici.agency' ) )
			);
		}

		// Format check.
		if ( ! is_email( $email ) ) {
			return ValidationResult::error(
				array( __( 'Невірний формат email', 'medici.agency' ) )
			);
		}

		// Extract domain.
		$domain = strtolower( substr( strrchr( $email, '@' ) ?: '', 1 ) );

		// Blocked domain check.
		if ( in_array( $domain, self::BLOCKED_DOMAINS, true ) ) {
			return ValidationResult::error(
				array( __( 'Тимчасові email адреси не приймаються', 'medici.agency' ) ),
				-50
			);
		}

		// Test pattern check.
		foreach ( self::TEST_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, strtolower( $email ) ) ) {
				$warnings[] = __( 'Email схожий на тестовий', 'medici.agency' );
				$score_mod -= 20;
				break;
			}
		}

		// Business email bonus.
		if ( ! in_array( $domain, self::FREE_DOMAINS, true ) ) {
			$score_mod += 15;
		}

		return ValidationResult::success(
			array( 'email' => $email ),
			$score_mod,
			$warnings
		);
	}

	/**
	 * Sanitize email
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['email'] ) ) {
			$data['email'] = sanitize_email( (string) $data['email'] );
		}
		return $data;
	}
}

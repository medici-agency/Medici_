<?php
/**
 * Validator Interface
 *
 * Contract for lead data validators (Chain of Responsibility pattern).
 *
 * @package    Medici_Agency
 * @subpackage Lead\Validation
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validator Interface
 *
 * Each validator checks specific field(s) and returns validation result.
 *
 * @since 2.0.0
 */
interface ValidatorInterface {

	/**
	 * Get validator name
	 *
	 * @since 2.0.0
	 * @return string Validator identifier.
	 */
	public function getName(): string;

	/**
	 * Validate data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data to validate.
	 * @return ValidationResult Validation result.
	 */
	public function validate( array $data ): ValidationResult;

	/**
	 * Get validated/sanitized data
	 *
	 * Returns sanitized version of input data.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data to sanitize.
	 * @return array<string, mixed> Sanitized data.
	 */
	public function sanitize( array $data ): array;
}

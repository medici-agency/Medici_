<?php
/**
 * Service Validator
 *
 * Validates selected service.
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
 * Service Validator Class
 *
 * Validates service selection against allowed values.
 *
 * @since 2.0.0
 */
final class ServiceValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'service';

	/**
	 * Valid services
	 *
	 * @var array<string>
	 */
	private const VALID_SERVICES = array(
		'smm',
		'seo',
		'advertising',
		'branding',
		'consultation',
		'other',
	);

	/**
	 * Default service
	 */
	private const DEFAULT_SERVICE = 'other';

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
	 * Validate service
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with 'service' key.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$service = strtolower( sanitize_text_field( (string) ( $data['service'] ?? '' ) ) );

		// Validate against whitelist.
		if ( ! in_array( $service, self::VALID_SERVICES, true ) ) {
			$service = self::DEFAULT_SERVICE;
		}

		return ValidationResult::success( array( 'service' => $service ) );
	}

	/**
	 * Sanitize service
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['service'] ) ) {
			$service         = strtolower( sanitize_text_field( (string) $data['service'] ) );
			$data['service'] = in_array( $service, self::VALID_SERVICES, true )
				? $service
				: self::DEFAULT_SERVICE;
		}
		return $data;
	}

	/**
	 * Get valid services
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public static function getValidServices(): array {
		return self::VALID_SERVICES;
	}
}

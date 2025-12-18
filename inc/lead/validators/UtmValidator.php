<?php
/**
 * UTM Validator
 *
 * Validates and normalizes UTM parameters.
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
 * UTM Validator Class
 *
 * Validates UTM parameters and auto-corrects common mistakes.
 *
 * @since 2.0.0
 */
final class UtmValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'utm';

	/**
	 * Valid UTM sources
	 *
	 * @var array<string>
	 */
	private const VALID_SOURCES = array(
		'google',
		'facebook',
		'instagram',
		'linkedin',
		'telegram',
		'email',
		'direct',
		'referral',
		'viber',
		'youtube',
		'tiktok',
	);

	/**
	 * Valid UTM mediums
	 *
	 * @var array<string>
	 */
	private const VALID_MEDIUMS = array(
		'cpc',
		'cpm',
		'organic',
		'social',
		'post',
		'story',
		'reel',
		'bio',
		'dm',
		'email',
		'referral',
		'video',
		'display',
	);

	/**
	 * Source corrections
	 *
	 * @var array<string, string>
	 */
	private const SOURCE_CORRECTIONS = array(
		'insta'     => 'instagram',
		'ig'        => 'instagram',
		'fb'        => 'facebook',
		'ln'        => 'linkedin',
		'li'        => 'linkedin',
		'tg'        => 'telegram',
		'yt'        => 'youtube',
		'tt'        => 'tiktok',
		'ggl'       => 'google',
		'adwords'   => 'google',
		'googleads' => 'google',
	);

	/**
	 * Medium corrections
	 *
	 * @var array<string, string>
	 */
	private const MEDIUM_CORRECTIONS = array(
		'paid'         => 'cpc',
		'ppc'          => 'cpc',
		'ads'          => 'cpc',
		'social-media' => 'social',
		'feed'         => 'post',
		'stories'      => 'story',
		'reels'        => 'reel',
		'newsletter'   => 'email',
		'mail'         => 'email',
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
	 * Validate UTM parameters
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with UTM keys.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$warnings = array();
		$result   = array();

		// Validate source.
		$source_result        = $this->validateSource( (string) ( $data['utm_source'] ?? '' ) );
		$result['utm_source'] = $source_result['value'];
		if ( $source_result['corrected'] ) {
			$warnings[] = sprintf(
				/* translators: 1: original value 2: corrected value */
				__( 'UTM source автоматично виправлено: %1$s → %2$s', 'medici.agency' ),
				$source_result['original'],
				$source_result['value']
			);
		}

		// Validate medium.
		$medium_result        = $this->validateMedium( (string) ( $data['utm_medium'] ?? '' ) );
		$result['utm_medium'] = $medium_result['value'];
		if ( $medium_result['corrected'] ) {
			$warnings[] = sprintf(
				/* translators: 1: original value 2: corrected value */
				__( 'UTM medium автоматично виправлено: %1$s → %2$s', 'medici.agency' ),
				$medium_result['original'],
				$medium_result['value']
			);
		}

		// Sanitize campaign.
		$result['utm_campaign'] = $this->sanitizeUtmValue( (string) ( $data['utm_campaign'] ?? '' ) );
		$result['utm_term']     = $this->sanitizeUtmValue( (string) ( $data['utm_term'] ?? '' ) );
		$result['utm_content']  = $this->sanitizeUtmValue( (string) ( $data['utm_content'] ?? '' ) );

		return ValidationResult::success( $result, 0, $warnings );
	}

	/**
	 * Sanitize UTM data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['utm_source'] ) ) {
			$data['utm_source'] = $this->validateSource( (string) $data['utm_source'] )['value'];
		}
		if ( isset( $data['utm_medium'] ) ) {
			$data['utm_medium'] = $this->validateMedium( (string) $data['utm_medium'] )['value'];
		}
		if ( isset( $data['utm_campaign'] ) ) {
			$data['utm_campaign'] = $this->sanitizeUtmValue( (string) $data['utm_campaign'] );
		}
		if ( isset( $data['utm_term'] ) ) {
			$data['utm_term'] = $this->sanitizeUtmValue( (string) $data['utm_term'] );
		}
		if ( isset( $data['utm_content'] ) ) {
			$data['utm_content'] = $this->sanitizeUtmValue( (string) $data['utm_content'] );
		}
		return $data;
	}

	/**
	 * Validate and correct UTM source
	 *
	 * @since 2.0.0
	 * @param string $source Raw source.
	 * @return array{value: string, original: string, corrected: bool}
	 */
	private function validateSource( string $source ): array {
		$original  = $source;
		$source    = strtolower( trim( $source ) );
		$corrected = false;

		// Auto-correct.
		if ( isset( self::SOURCE_CORRECTIONS[ $source ] ) ) {
			$source    = self::SOURCE_CORRECTIONS[ $source ];
			$corrected = true;
		}

		// Validate whitelist.
		if ( ! in_array( $source, self::VALID_SOURCES, true ) ) {
			if ( ! empty( $source ) ) {
				$corrected = true;
				// Try to find closest match.
				foreach ( self::VALID_SOURCES as $valid ) {
					if ( str_contains( $source, $valid ) || str_contains( $valid, $source ) ) {
						$source = $valid;
						break;
					}
				}
				// Fallback.
				if ( ! in_array( $source, self::VALID_SOURCES, true ) ) {
					$source = 'direct';
				}
			} else {
				$source = 'direct';
			}
		}

		return array(
			'value'     => $source,
			'original'  => $original,
			'corrected' => $corrected && $original !== $source,
		);
	}

	/**
	 * Validate and correct UTM medium
	 *
	 * @since 2.0.0
	 * @param string $medium Raw medium.
	 * @return array{value: string, original: string, corrected: bool}
	 */
	private function validateMedium( string $medium ): array {
		$original  = $medium;
		$medium    = strtolower( trim( $medium ) );
		$corrected = false;

		// Auto-correct.
		if ( isset( self::MEDIUM_CORRECTIONS[ $medium ] ) ) {
			$medium    = self::MEDIUM_CORRECTIONS[ $medium ];
			$corrected = true;
		}

		// Validate whitelist.
		if ( ! in_array( $medium, self::VALID_MEDIUMS, true ) ) {
			if ( ! empty( $medium ) ) {
				$corrected = true;
			}
			$medium = 'unknown';
		}

		return array(
			'value'     => $medium,
			'original'  => $original,
			'corrected' => $corrected && $original !== $medium,
		);
	}

	/**
	 * Sanitize UTM value
	 *
	 * @since 2.0.0
	 * @param string $value UTM value.
	 * @return string Sanitized value.
	 */
	private function sanitizeUtmValue( string $value ): string {
		$value = strtolower( trim( sanitize_text_field( $value ) ) );
		return preg_replace( '/[^a-z0-9_-]/', '', $value ) ?: '';
	}
}

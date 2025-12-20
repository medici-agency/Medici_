<?php
/**
 * Geo Location Evaluator
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Rules\Evaluators;

use Medici\CookieNotice\Rules\Rule_Evaluator_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geo Evaluator
 *
 * Evaluates rules based on visitor's geographic location.
 */
class Geo_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * EU country codes
	 *
	 * @var array<int, string>
	 */
	private array $eu_countries = [
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
		'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
		'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
	];

	/**
	 * EEA country codes (EU + Iceland, Liechtenstein, Norway)
	 *
	 * @var array<int, string>
	 */
	private array $eea_countries = [
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
		'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
		'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
		'IS', 'LI', 'NO',
	];

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'geo';
	}

	/**
	 * Get available operators
	 *
	 * @return array<string, string>
	 */
	public function get_operators(): array {
		return [
			'is'     => __( 'є', 'medici-cookie-notice' ),
			'is_not' => __( 'не є', 'medici-cookie-notice' ),
			'in'     => __( 'у списку', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Evaluate the rule
	 *
	 * @param string $operator Operator.
	 * @param mixed  $value Value to compare (country code, region, or special value).
	 * @return bool
	 */
	public function evaluate( string $operator, mixed $value ): bool {
		$visitor_country = $this->get_visitor_country();

		if ( empty( $visitor_country ) ) {
			// Can't determine location, default to show banner
			return false;
		}

		// Handle special values
		$value_str = (string) $value;

		$is_match = match ( $value_str ) {
			'EU'      => in_array( $visitor_country, $this->eu_countries, true ),
			'EEA'     => in_array( $visitor_country, $this->eea_countries, true ),
			'US'      => 'US' === $visitor_country,
			'GDPR'    => in_array( $visitor_country, $this->eea_countries, true ), // GDPR applies to EEA
			default   => $this->match_country( $visitor_country, $value ),
		};

		return match ( $operator ) {
			'is'     => $is_match,
			'is_not' => ! $is_match,
			'in'     => $is_match,
			default  => false,
		};
	}

	/**
	 * Match country against value (single or comma-separated)
	 *
	 * @param string $visitor_country Visitor's country code.
	 * @param mixed  $value Value to match (single code or comma-separated).
	 * @return bool
	 */
	private function match_country( string $visitor_country, mixed $value ): bool {
		if ( is_array( $value ) ) {
			return in_array( $visitor_country, $value, true );
		}

		$value_str = (string) $value;

		// Handle comma-separated list
		if ( str_contains( $value_str, ',' ) ) {
			$countries = array_map( 'trim', explode( ',', $value_str ) );
			$countries = array_map( 'strtoupper', $countries );
			return in_array( $visitor_country, $countries, true );
		}

		return strtoupper( $value_str ) === $visitor_country;
	}

	/**
	 * Get visitor's country code
	 *
	 * @return string Two-letter country code or empty string.
	 */
	private function get_visitor_country(): string {
		// Try to get from plugin's geo detection
		$geo_detection = \Medici\CookieNotice\mcn()->geo_detection ?? null;

		if ( null !== $geo_detection ) {
			$location = $geo_detection->get_visitor_location();
			if ( ! empty( $location['country'] ) ) {
				return strtoupper( $location['country'] );
			}
		}

		// Fallback: Check Cloudflare header
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			return strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
		}

		return '';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Географія', 'medici-cookie-notice' );
	}

	/**
	 * Get value field type
	 *
	 * @return string
	 */
	public function get_value_field_type(): string {
		return 'select';
	}

	/**
	 * Get value options
	 *
	 * @return array<string, string>
	 */
	public function get_value_options(): array {
		return [
			'EU'   => __( 'Європейський Союз (27 країн)', 'medici-cookie-notice' ),
			'EEA'  => __( 'Європейська економічна зона (30 країн)', 'medici-cookie-notice' ),
			'GDPR' => __( 'Країни з GDPR', 'medici-cookie-notice' ),
			'US'   => __( 'США', 'medici-cookie-notice' ),
			'UA'   => __( 'Україна', 'medici-cookie-notice' ),
			'GB'   => __( 'Велика Британія', 'medici-cookie-notice' ),
			'CA'   => __( 'Канада', 'medici-cookie-notice' ),
			'AU'   => __( 'Австралія', 'medici-cookie-notice' ),
		];
	}
}

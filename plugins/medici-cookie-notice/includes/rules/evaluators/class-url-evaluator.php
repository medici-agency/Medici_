<?php
/**
 * URL Evaluator
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
 * URL Evaluator
 *
 * Evaluates rules based on current URL patterns.
 */
class URL_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'url';
	}

	/**
	 * Get available operators
	 *
	 * @return array<string, string>
	 */
	public function get_operators(): array {
		return [
			'contains'     => __( 'містить', 'medici-cookie-notice' ),
			'not_contains' => __( 'не містить', 'medici-cookie-notice' ),
			'starts_with'  => __( 'починається з', 'medici-cookie-notice' ),
			'ends_with'    => __( 'закінчується на', 'medici-cookie-notice' ),
			'equals'       => __( 'дорівнює', 'medici-cookie-notice' ),
			'regex'        => __( 'відповідає regex', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Evaluate the rule
	 *
	 * @param string $operator Operator.
	 * @param mixed  $value Value to compare.
	 * @return bool
	 */
	public function evaluate( string $operator, mixed $value ): bool {
		$current_url = $this->get_current_url();
		$pattern     = (string) $value;

		return match ( $operator ) {
			'contains'     => str_contains( $current_url, $pattern ),
			'not_contains' => ! str_contains( $current_url, $pattern ),
			'starts_with'  => str_starts_with( $current_url, $pattern ),
			'ends_with'    => str_ends_with( $current_url, $pattern ),
			'equals'       => $current_url === $pattern,
			'regex'        => $this->match_regex( $current_url, $pattern ),
			default        => false,
		};
	}

	/**
	 * Get current URL (path only)
	 *
	 * @return string
	 */
	private function get_current_url(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '/';
		}

		$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Remove query string for cleaner matching
		$path = wp_parse_url( $uri, PHP_URL_PATH );

		return $path ?: '/';
	}

	/**
	 * Match regex pattern safely
	 *
	 * @param string $url URL to test.
	 * @param string $pattern Regex pattern.
	 * @return bool
	 */
	private function match_regex( string $url, string $pattern ): bool {
		// Add delimiters if not present
		if ( ! preg_match( '/^[\/\#\~]/', $pattern ) ) {
			$pattern = '/' . $pattern . '/';
		}

		// Suppress errors for invalid patterns
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$result = @preg_match( $pattern, $url );

		return 1 === $result;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'URL', 'medici-cookie-notice' );
	}

	/**
	 * Get value field type
	 *
	 * @return string
	 */
	public function get_value_field_type(): string {
		return 'text';
	}

	/**
	 * Get value options
	 *
	 * @return array<string, string>|null
	 */
	public function get_value_options(): ?array {
		return null; // Text input, no predefined options
	}
}

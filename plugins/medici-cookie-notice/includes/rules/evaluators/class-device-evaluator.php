<?php
/**
 * Device Type Evaluator
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
 * Device Evaluator
 *
 * Evaluates rules based on device type (mobile, tablet, desktop).
 */
class Device_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'device';
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
		$device_type = $this->detect_device_type();

		$is_match = $device_type === (string) $value;

		return 'is' === $operator ? $is_match : ! $is_match;
	}

	/**
	 * Detect device type from user agent
	 *
	 * @return string
	 */
	private function detect_device_type(): string {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return 'desktop';
		}

		$ua = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		// WordPress built-in mobile detection
		if ( function_exists( 'wp_is_mobile' ) && wp_is_mobile() ) {
			// Differentiate tablet from mobile
			if ( preg_match( '/iPad|Android(?!.*Mobile)|Tablet/i', $ua ) ) {
				return 'tablet';
			}
			return 'mobile';
		}

		// Additional tablet detection
		if ( preg_match( '/iPad|Android(?!.*Mobile)|Tablet|PlayBook|Silk/i', $ua ) ) {
			return 'tablet';
		}

		// Mobile detection
		if ( preg_match( '/Mobile|iPhone|iPod|Android.*Mobile|webOS|BlackBerry|Opera Mini|IEMobile/i', $ua ) ) {
			return 'mobile';
		}

		return 'desktop';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Пристрій', 'medici-cookie-notice' );
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
			'desktop' => __( 'Комп\'ютер', 'medici-cookie-notice' ),
			'mobile'  => __( 'Мобільний', 'medici-cookie-notice' ),
			'tablet'  => __( 'Планшет', 'medici-cookie-notice' ),
		];
	}
}

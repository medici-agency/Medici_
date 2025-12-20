<?php
/**
 * User Type/Role Evaluator
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
 * User Evaluator
 *
 * Evaluates rules based on user login status and roles.
 */
class User_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'user_type';
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
		$is_match = match ( (string) $value ) {
			'logged_in' => is_user_logged_in(),
			'guest'     => ! is_user_logged_in(),
			default     => false,
		};

		return 'is' === $operator ? $is_match : ! $is_match;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Тип користувача', 'medici-cookie-notice' );
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
			'logged_in' => __( 'Авторизований', 'medici-cookie-notice' ),
			'guest'     => __( 'Гість', 'medici-cookie-notice' ),
		];
	}
}

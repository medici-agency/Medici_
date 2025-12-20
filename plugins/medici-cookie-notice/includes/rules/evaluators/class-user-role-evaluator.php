<?php
/**
 * User Role Evaluator
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
 * User Role Evaluator
 *
 * Evaluates rules based on user role.
 */
class User_Role_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'user_role';
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
			'in'     => __( 'один з', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Evaluate the rule
	 *
	 * @param string $operator Operator.
	 * @param mixed  $value Value to compare (role slug or array of slugs).
	 * @return bool
	 */
	public function evaluate( string $operator, mixed $value ): bool {
		if ( ! is_user_logged_in() ) {
			// Guest users have no role
			return 'is_not' === $operator;
		}

		$user       = wp_get_current_user();
		$user_roles = $user->roles;

		// Handle array of roles
		if ( is_array( $value ) ) {
			$has_role = ! empty( array_intersect( $value, $user_roles ) );
		} else {
			$has_role = in_array( (string) $value, $user_roles, true );
		}

		return match ( $operator ) {
			'is'     => $has_role,
			'is_not' => ! $has_role,
			'in'     => $has_role,
			default  => false,
		};
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Роль користувача', 'medici-cookie-notice' );
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
		$roles = wp_roles()->get_names();
		return array_map( 'translate_user_role', $roles );
	}
}

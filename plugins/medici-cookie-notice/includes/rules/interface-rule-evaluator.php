<?php
/**
 * Rule Evaluator Interface
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Rules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule Evaluator Interface
 *
 * All rule evaluators must implement this interface.
 */
interface Rule_Evaluator_Interface {

	/**
	 * Get the evaluator type identifier
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Get available operators for this evaluator
	 *
	 * @return array<string, string>
	 */
	public function get_operators(): array;

	/**
	 * Evaluate a rule
	 *
	 * @param string $operator The operator to use.
	 * @param mixed  $value The value to compare.
	 * @return bool
	 */
	public function evaluate( string $operator, mixed $value ): bool;

	/**
	 * Get the display label for this evaluator
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Get the value field type (select, text, number, date, etc.)
	 *
	 * @return string
	 */
	public function get_value_field_type(): string;

	/**
	 * Get available options for select-type fields
	 *
	 * @return array<string, string>|null
	 */
	public function get_value_options(): ?array;
}

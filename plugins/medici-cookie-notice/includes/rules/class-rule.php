<?php
/**
 * Rule Class
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
 * Individual Rule
 *
 * Represents a single rule within a rule group.
 */
class Rule {

	/**
	 * Rule ID
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Parent group ID
	 *
	 * @var int
	 */
	public int $group_id;

	/**
	 * Rule type (evaluator identifier)
	 *
	 * @var string
	 */
	public string $rule_type;

	/**
	 * Operator (is, is_not, contains, etc.)
	 *
	 * @var string
	 */
	public string $operator;

	/**
	 * Value to compare
	 *
	 * @var mixed
	 */
	public mixed $value;

	/**
	 * Whether rule is active
	 *
	 * @var bool
	 */
	public bool $is_active;

	/**
	 * Sort order within group
	 *
	 * @var int
	 */
	public int $sort_order;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $data Rule data.
	 */
	public function __construct( array $data = [] ) {
		$this->id         = (int) ( $data['id'] ?? 0 );
		$this->group_id   = (int) ( $data['group_id'] ?? 0 );
		$this->rule_type  = (string) ( $data['rule_type'] ?? '' );
		$this->operator   = (string) ( $data['operator'] ?? 'is' );
		$this->value      = $data['value'] ?? '';
		$this->is_active  = (bool) ( $data['is_active'] ?? true );
		$this->sort_order = (int) ( $data['sort_order'] ?? 0 );
	}

	/**
	 * Evaluate this rule using the provided evaluator
	 *
	 * @param Rule_Evaluator_Interface $evaluator The evaluator to use.
	 * @return bool
	 */
	public function evaluate( Rule_Evaluator_Interface $evaluator ): bool {
		// Inactive rules always pass (don't block)
		if ( ! $this->is_active ) {
			return true;
		}

		// Ensure evaluator matches rule type
		if ( $evaluator->get_type() !== $this->rule_type ) {
			return true; // Wrong evaluator, pass through
		}

		return $evaluator->evaluate( $this->operator, $this->value );
	}

	/**
	 * Convert to array for database storage
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'id'         => $this->id,
			'group_id'   => $this->group_id,
			'rule_type'  => $this->rule_type,
			'operator'   => $this->operator,
			'value'      => is_array( $this->value ) ? wp_json_encode( $this->value ) : (string) $this->value,
			'is_active'  => $this->is_active ? 1 : 0,
			'sort_order' => $this->sort_order,
		];
	}

	/**
	 * Create from database row
	 *
	 * @param object $row Database row.
	 * @return self
	 */
	public static function from_db( object $row ): self {
		$value = $row->value;

		// Try to decode JSON value
		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
			$value = $decoded;
		}

		return new self( [
			'id'         => (int) $row->id,
			'group_id'   => (int) $row->group_id,
			'rule_type'  => $row->rule_type,
			'operator'   => $row->operator,
			'value'      => $value,
			'is_active'  => (bool) $row->is_active,
			'sort_order' => (int) $row->sort_order,
		] );
	}
}

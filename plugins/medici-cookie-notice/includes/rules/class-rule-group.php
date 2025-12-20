<?php
/**
 * Rule Group Class
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
 * Rule Group
 *
 * Contains multiple rules that are evaluated together with AND/OR logic.
 */
class Rule_Group {

	/**
	 * Group ID
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Group name
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Operator (AND or OR)
	 *
	 * @var string
	 */
	public string $operator;

	/**
	 * Action (show or hide)
	 *
	 * @var string
	 */
	public string $action;

	/**
	 * Priority (lower = evaluated first)
	 *
	 * @var int
	 */
	public int $priority;

	/**
	 * Whether group is active
	 *
	 * @var bool
	 */
	public bool $is_active;

	/**
	 * Rules within this group
	 *
	 * @var array<int, Rule>
	 */
	public array $rules = [];

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $data Group data.
	 */
	public function __construct( array $data = [] ) {
		$this->id        = (int) ( $data['id'] ?? 0 );
		$this->name      = (string) ( $data['name'] ?? '' );
		$this->operator  = (string) ( $data['operator'] ?? 'AND' );
		$this->action    = (string) ( $data['action'] ?? 'show' );
		$this->priority  = (int) ( $data['priority'] ?? 10 );
		$this->is_active = (bool) ( $data['is_active'] ?? true );
		$this->rules     = $data['rules'] ?? [];
	}

	/**
	 * Add a rule to this group
	 *
	 * @param Rule $rule The rule to add.
	 * @return void
	 */
	public function add_rule( Rule $rule ): void {
		$this->rules[] = $rule;
	}

	/**
	 * Evaluate all rules in this group
	 *
	 * @param array<string, Rule_Evaluator_Interface> $evaluators Available evaluators.
	 * @return bool Whether this group matches.
	 */
	public function evaluate( array $evaluators ): bool {
		// Inactive groups always pass (don't affect result)
		if ( ! $this->is_active ) {
			return false; // Don't match, let other groups decide
		}

		// No rules = no match
		if ( empty( $this->rules ) ) {
			return false;
		}

		$results = [];

		foreach ( $this->rules as $rule ) {
			// Get evaluator for this rule type
			$evaluator = $evaluators[ $rule->rule_type ] ?? null;

			if ( null === $evaluator ) {
				// No evaluator found, skip this rule (treat as pass)
				continue;
			}

			$results[] = $rule->evaluate( $evaluator );
		}

		// If no rules were evaluated, don't match
		if ( empty( $results ) ) {
			return false;
		}

		// Combine results based on operator
		if ( 'AND' === $this->operator ) {
			// All rules must pass
			return ! in_array( false, $results, true );
		}

		// OR: At least one rule must pass
		return in_array( true, $results, true );
	}

	/**
	 * Get the action result based on evaluation
	 *
	 * @param bool $matches Whether the group matches.
	 * @return bool|null True = show, False = hide, null = no decision.
	 */
	public function get_action_result( bool $matches ): ?bool {
		if ( ! $matches ) {
			return null; // No decision from this group
		}

		return 'show' === $this->action;
	}

	/**
	 * Convert to array for database storage
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return [
			'id'        => $this->id,
			'name'      => $this->name,
			'operator'  => $this->operator,
			'action'    => $this->action,
			'priority'  => $this->priority,
			'is_active' => $this->is_active ? 1 : 0,
		];
	}

	/**
	 * Create from database row
	 *
	 * @param object $row Database row.
	 * @return self
	 */
	public static function from_db( object $row ): self {
		return new self( [
			'id'        => (int) $row->id,
			'name'      => $row->name,
			'operator'  => $row->operator,
			'action'    => $row->action,
			'priority'  => (int) $row->priority,
			'is_active' => (bool) $row->is_active,
		] );
	}
}

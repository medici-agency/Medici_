<?php
/**
 * Scoring Strategy Interface
 *
 * Contract for lead scoring strategies (Strategy Pattern).
 *
 * @package    Medici_Agency
 * @subpackage Lead\Scoring
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scoring Strategy Interface
 *
 * Each strategy calculates a partial score based on specific lead attributes.
 *
 * @since 2.0.0
 */
interface ScoringStrategyInterface {

	/**
	 * Get strategy name
	 *
	 * @since 2.0.0
	 * @return string Strategy identifier.
	 */
	public function getName(): string;

	/**
	 * Calculate partial score for lead data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data array.
	 * @return int Partial score (can be negative for penalties).
	 */
	public function calculate( array $lead_data ): int;

	/**
	 * Get score breakdown
	 *
	 * Returns detailed breakdown of how score was calculated.
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data array.
	 * @return array<string, int> Score components.
	 */
	public function getBreakdown( array $lead_data ): array;

	/**
	 * Get maximum possible score from this strategy
	 *
	 * @since 2.0.0
	 * @return int Maximum score.
	 */
	public function getMaxScore(): int;
}

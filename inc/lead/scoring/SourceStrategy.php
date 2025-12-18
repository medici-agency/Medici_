<?php
/**
 * Source Scoring Strategy
 *
 * Calculates score based on UTM source (traffic origin).
 *
 * @package    Medici_Agency
 * @subpackage Lead\Scoring
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead\Scoring;

use Medici\Lead\ScoringStrategyInterface;
use Medici\Lead\ScoringConfig;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Source Scoring Strategy
 *
 * Evaluates lead quality based on traffic source.
 * LinkedIn and Google indicate higher intent than direct traffic.
 *
 * @since 2.0.0
 */
final class SourceStrategy implements ScoringStrategyInterface {

	/**
	 * Strategy name
	 */
	private const NAME = 'source';

	/**
	 * Get strategy name
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getName(): string {
		return self::NAME;
	}

	/**
	 * Calculate source score
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score.
	 */
	public function calculate( array $lead_data ): int {
		$source = strtolower( (string) ( $lead_data['utm_source'] ?? 'direct' ) );
		return ScoringConfig::getSourceScore( $source );
	}

	/**
	 * Get breakdown
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return array<string, int>
	 */
	public function getBreakdown( array $lead_data ): array {
		$source = strtolower( (string) ( $lead_data['utm_source'] ?? 'direct' ) );
		$score  = ScoringConfig::getSourceScore( $source );

		return array(
			'source'       => $source,
			'source_score' => $score,
		);
	}

	/**
	 * Get maximum score
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getMaxScore(): int {
		return max( ScoringConfig::getSourceScores() );
	}
}

<?php
/**
 * Medium Scoring Strategy
 *
 * Calculates score based on UTM medium (traffic type).
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
 * Medium Scoring Strategy
 *
 * Evaluates lead quality based on traffic medium.
 * CPC (paid) indicates higher intent than organic.
 *
 * @since 2.0.0
 */
final class MediumStrategy implements ScoringStrategyInterface {

	/**
	 * Strategy name
	 */
	private const NAME = 'medium';

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
	 * Calculate medium score
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score.
	 */
	public function calculate( array $lead_data ): int {
		$medium = strtolower( (string) ( $lead_data['utm_medium'] ?? '' ) );
		return ScoringConfig::getMediumScore( $medium );
	}

	/**
	 * Get breakdown
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return array<string, int>
	 */
	public function getBreakdown( array $lead_data ): array {
		$medium = strtolower( (string) ( $lead_data['utm_medium'] ?? '' ) );
		$score  = ScoringConfig::getMediumScore( $medium );

		return array(
			'medium'       => $medium ?: 'none',
			'medium_score' => $score,
		);
	}

	/**
	 * Get maximum score
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getMaxScore(): int {
		return max( ScoringConfig::getMediumScores() );
	}
}

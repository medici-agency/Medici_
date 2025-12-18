<?php
/**
 * Service Scoring Strategy
 *
 * Calculates score based on requested service type.
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
 * Service Scoring Strategy
 *
 * Evaluates lead value based on requested service.
 * Branding and advertising indicate higher potential value.
 *
 * @since 2.0.0
 */
final class ServiceStrategy implements ScoringStrategyInterface {

	/**
	 * Strategy name
	 */
	private const NAME = 'service';

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
	 * Calculate service score
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score.
	 */
	public function calculate( array $lead_data ): int {
		$service = strtolower( (string) ( $lead_data['service'] ?? 'other' ) );
		return ScoringConfig::getServiceScore( $service );
	}

	/**
	 * Get breakdown
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return array<string, int>
	 */
	public function getBreakdown( array $lead_data ): array {
		$service = strtolower( (string) ( $lead_data['service'] ?? 'other' ) );
		$score   = ScoringConfig::getServiceScore( $service );

		return array(
			'service'       => $service,
			'service_score' => $score,
		);
	}

	/**
	 * Get maximum score
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getMaxScore(): int {
		return max( ScoringConfig::getServiceScores() );
	}
}

<?php
/**
 * Bonus Scoring Strategy
 *
 * Calculates bonus scores based on engagement and contact details.
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
 * Bonus Scoring Strategy
 *
 * Awards bonus points for:
 * - Providing phone number (+15)
 * - Providing message (+10)
 * - Long message 100+ chars (+5)
 * - Business email domain (+10)
 * - Engagement signals (services, cases, blog, returning)
 *
 * @since 2.0.0
 */
final class BonusStrategy implements ScoringStrategyInterface {

	/**
	 * Strategy name
	 */
	private const NAME = 'bonus';

	/**
	 * Long message threshold
	 */
	private const LONG_MESSAGE_LENGTH = 100;

	/**
	 * Free email domains
	 *
	 * @var array<string>
	 */
	private const FREE_EMAIL_DOMAINS = array(
		'gmail.com',
		'yahoo.com',
		'hotmail.com',
		'outlook.com',
		'ukr.net',
		'i.ua',
		'meta.ua',
		'mail.ru',
		'yandex.ru',
		'proton.me',
		'protonmail.com',
	);

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
	 * Calculate bonus scores
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Total bonus score.
	 */
	public function calculate( array $lead_data ): int {
		$breakdown = $this->getBreakdown( $lead_data );
		return array_sum( $breakdown );
	}

	/**
	 * Get breakdown of all bonus scores
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return array<string, int> Bonus breakdown.
	 */
	public function getBreakdown( array $lead_data ): array {
		$breakdown = array();

		// Has phone bonus.
		if ( ! empty( $lead_data['phone'] ) ) {
			$breakdown['has_phone'] = ScoringConfig::getBonusScore( 'has_phone' );
		}

		// Has message bonus.
		$message = (string) ( $lead_data['message'] ?? '' );
		if ( ! empty( $message ) ) {
			$breakdown['has_message'] = ScoringConfig::getBonusScore( 'has_message' );

			// Long message bonus.
			if ( strlen( $message ) >= self::LONG_MESSAGE_LENGTH ) {
				$breakdown['long_message'] = ScoringConfig::getBonusScore( 'long_message' );
			}
		}

		// Business email bonus.
		$email = (string) ( $lead_data['email'] ?? '' );
		if ( ! empty( $email ) && $this->isBusinessEmail( $email ) ) {
			$breakdown['business_email'] = ScoringConfig::getBonusScore( 'business_email' );
		}

		// Engagement bonuses (from analytics tracking).
		$engagement = $lead_data['engagement'] ?? array();
		if ( is_array( $engagement ) ) {
			if ( ! empty( $engagement['visited_services'] ) ) {
				$breakdown['visited_services'] = ScoringConfig::getBonusScore( 'visited_services' );
			}
			if ( ! empty( $engagement['visited_cases'] ) ) {
				$breakdown['visited_cases'] = ScoringConfig::getBonusScore( 'visited_cases' );
			}
			if ( ! empty( $engagement['read_blog'] ) ) {
				$breakdown['read_blog'] = ScoringConfig::getBonusScore( 'read_blog' );
			}
			if ( ! empty( $engagement['returning_user'] ) ) {
				$breakdown['returning_user'] = ScoringConfig::getBonusScore( 'returning_user' );
			}
		}

		return $breakdown;
	}

	/**
	 * Get maximum score
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getMaxScore(): int {
		return array_sum( ScoringConfig::getBonusScores() );
	}

	/**
	 * Check if email is business (non-free) domain
	 *
	 * @since 2.0.0
	 * @param string $email Email address.
	 * @return bool True if business email.
	 */
	private function isBusinessEmail( string $email ): bool {
		$domain = strtolower( substr( strrchr( $email, '@' ) ?: '', 1 ) );
		return ! empty( $domain ) && ! in_array( $domain, self::FREE_EMAIL_DOMAINS, true );
	}
}

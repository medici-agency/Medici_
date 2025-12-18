<?php
/**
 * Scoring Configuration
 *
 * Centralized configuration for lead scoring weights and thresholds.
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
 * Scoring Configuration Class
 *
 * Provides all score weights and thresholds.
 * Can be extended to load from WordPress options.
 *
 * @since 2.0.0
 */
final class ScoringConfig {

	/**
	 * Option name for custom settings
	 */
	private const OPTION_NAME = 'medici_lead_scoring_settings';

	/**
	 * Score weights by source
	 *
	 * Higher score = higher quality lead.
	 *
	 * @var array<string, int>
	 */
	private const SOURCE_SCORES = array(
		'linkedin'  => 20, // B2B, high intent.
		'google'    => 15, // Search intent.
		'facebook'  => 12, // Ads/targeted.
		'instagram' => 10, // Visual discovery.
		'telegram'  => 10, // Engaged audience.
		'email'     => 18, // Newsletter = warm lead.
		'referral'  => 15, // Word of mouth.
		'direct'    => 5,  // Unknown source.
	);

	/**
	 * Score weights by medium
	 *
	 * @var array<string, int>
	 */
	private const MEDIUM_SCORES = array(
		'cpc'        => 15, // Paid ads = intent.
		'newsletter' => 12, // Email subscriber.
		'dm'         => 10, // Direct message.
		'post'       => 8,  // Social post.
		'bio'        => 6,  // Profile link.
		'story'      => 6,  // Story link.
		'organic'    => 10, // Search.
		'referral'   => 8,  // Link from site.
	);

	/**
	 * Score weights by service
	 *
	 * @var array<string, int>
	 */
	private const SERVICE_SCORES = array(
		'branding'     => 25, // High-value service.
		'advertising'  => 20, // Media budgets.
		'seo'          => 18, // Long-term client.
		'smm'          => 15, // Ongoing work.
		'consultation' => 10, // Entry point.
		'other'        => 5,  // Unknown need.
	);

	/**
	 * Additional score factors
	 *
	 * @var array<string, int>
	 */
	private const BONUS_SCORES = array(
		'has_phone'        => 15, // Serious inquiry.
		'has_message'      => 10, // Detailed request.
		'long_message'     => 5,  // 100+ chars message.
		'visited_services' => 5,  // Browsed services.
		'visited_cases'    => 10, // Viewed portfolio.
		'read_blog'        => 3,  // Content engagement.
		'returning_user'   => 8,  // Multiple visits.
		'business_email'   => 10, // Non-free email domain.
	);

	/**
	 * Score thresholds for labels
	 *
	 * @var array<string, int>
	 */
	private const THRESHOLDS = array(
		'hot'  => 70, // 70+ = Hot lead.
		'warm' => 40, // 40-69 = Warm lead.
		'cold' => 0,  // 0-39 = Cold lead.
	);

	/**
	 * Default settings
	 *
	 * @var array<string, mixed>
	 */
	private const DEFAULTS = array(
		'enabled'       => true,
		'crm_threshold' => 40,
		'crm_sync'      => false,
	);

	/**
	 * Cached settings
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $settings = null;

	/**
	 * Get source scores
	 *
	 * @since 2.0.0
	 * @return array<string, int>
	 */
	public static function getSourceScores(): array {
		return self::SOURCE_SCORES;
	}

	/**
	 * Get source score for specific source
	 *
	 * @since 2.0.0
	 * @param string $source Source name.
	 * @return int Score value.
	 */
	public static function getSourceScore( string $source ): int {
		$source = strtolower( $source );
		return self::SOURCE_SCORES[ $source ] ?? self::SOURCE_SCORES['direct'];
	}

	/**
	 * Get medium scores
	 *
	 * @since 2.0.0
	 * @return array<string, int>
	 */
	public static function getMediumScores(): array {
		return self::MEDIUM_SCORES;
	}

	/**
	 * Get medium score for specific medium
	 *
	 * @since 2.0.0
	 * @param string $medium Medium name.
	 * @return int Score value.
	 */
	public static function getMediumScore( string $medium ): int {
		$medium = strtolower( $medium );
		return self::MEDIUM_SCORES[ $medium ] ?? 0;
	}

	/**
	 * Get service scores
	 *
	 * @since 2.0.0
	 * @return array<string, int>
	 */
	public static function getServiceScores(): array {
		return self::SERVICE_SCORES;
	}

	/**
	 * Get service score for specific service
	 *
	 * @since 2.0.0
	 * @param string $service Service name.
	 * @return int Score value.
	 */
	public static function getServiceScore( string $service ): int {
		$service = strtolower( $service );
		return self::SERVICE_SCORES[ $service ] ?? self::SERVICE_SCORES['other'];
	}

	/**
	 * Get bonus scores
	 *
	 * @since 2.0.0
	 * @return array<string, int>
	 */
	public static function getBonusScores(): array {
		return self::BONUS_SCORES;
	}

	/**
	 * Get bonus score for specific factor
	 *
	 * @since 2.0.0
	 * @param string $factor Bonus factor name.
	 * @return int Score value.
	 */
	public static function getBonusScore( string $factor ): int {
		return self::BONUS_SCORES[ $factor ] ?? 0;
	}

	/**
	 * Get thresholds
	 *
	 * @since 2.0.0
	 * @return array<string, int>
	 */
	public static function getThresholds(): array {
		return self::THRESHOLDS;
	}

	/**
	 * Get threshold for specific label
	 *
	 * @since 2.0.0
	 * @param string $label Label name (hot, warm, cold).
	 * @return int Threshold value.
	 */
	public static function getThreshold( string $label ): int {
		return self::THRESHOLDS[ $label ] ?? 0;
	}

	/**
	 * Get settings from WordPress options
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>
	 */
	public static function getSettings(): array {
		if ( null === self::$settings ) {
			$saved          = get_option( self::OPTION_NAME, array() );
			self::$settings = wp_parse_args( $saved, self::DEFAULTS );
		}
		return self::$settings;
	}

	/**
	 * Check if scoring is enabled
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public static function isEnabled(): bool {
		$settings = self::getSettings();
		return (bool) $settings['enabled'];
	}

	/**
	 * Get CRM threshold
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public static function getCrmThreshold(): int {
		$settings = self::getSettings();
		return (int) $settings['crm_threshold'];
	}

	/**
	 * Update settings
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $settings New settings.
	 * @return bool Success.
	 */
	public static function updateSettings( array $settings ): bool {
		$current        = self::getSettings();
		$settings       = wp_parse_args( $settings, $current );
		self::$settings = $settings;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Get maximum possible total score
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public static function getMaxTotalScore(): int {
		return max( self::SOURCE_SCORES )
			+ max( self::MEDIUM_SCORES )
			+ max( self::SERVICE_SCORES )
			+ array_sum( self::BONUS_SCORES );
	}

	/**
	 * Clear cached settings
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function clearCache(): void {
		self::$settings = null;
	}
}

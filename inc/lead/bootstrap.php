<?php
/**
 * Lead Module Bootstrap
 *
 * Loads all lead module classes:
 * - Integration adapters (Email, Telegram, Google Sheets)
 * - Scoring strategies (Source, Medium, Service, Bonus)
 * - Validation services (Email, Phone, Name, UTM, Spam)
 *
 * @package    Medici_Agency
 * @subpackage Lead
 * @since      2.0.0
 * @version    1.2.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// INTEGRATION MODULE
// ============================================================================

require_once __DIR__ . '/IntegrationInterface.php';
require_once __DIR__ . '/AbstractIntegration.php';
require_once __DIR__ . '/EmailAdapter.php';
require_once __DIR__ . '/TelegramAdapter.php';
require_once __DIR__ . '/GoogleSheetsAdapter.php';
require_once __DIR__ . '/IntegrationManager.php';

// ============================================================================
// SCORING MODULE (Strategy Pattern)
// ============================================================================

require_once __DIR__ . '/ScoringStrategyInterface.php';
require_once __DIR__ . '/ScoringConfig.php';
require_once __DIR__ . '/scoring/SourceStrategy.php';
require_once __DIR__ . '/scoring/MediumStrategy.php';
require_once __DIR__ . '/scoring/ServiceStrategy.php';
require_once __DIR__ . '/scoring/BonusStrategy.php';
require_once __DIR__ . '/ScoringService.php';
require_once __DIR__ . '/ScoringAdmin.php';

// ============================================================================
// VALIDATION MODULE (Chain of Responsibility Pattern)
// ============================================================================

require_once __DIR__ . '/ValidatorInterface.php';
require_once __DIR__ . '/ValidationResult.php';
require_once __DIR__ . '/validators/EmailValidator.php';
require_once __DIR__ . '/validators/PhoneValidator.php';
require_once __DIR__ . '/validators/NameValidator.php';
require_once __DIR__ . '/validators/MessageValidator.php';
require_once __DIR__ . '/validators/UtmValidator.php';
require_once __DIR__ . '/validators/SpamValidator.php';
require_once __DIR__ . '/validators/ServiceValidator.php';
require_once __DIR__ . '/ValidationService.php';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get integration manager instance
 *
 * @since 2.0.0
 * @return IntegrationManager
 */
function medici_integrations(): IntegrationManager {
	return IntegrationManager::getInstance();
}

/**
 * Get scoring service instance
 *
 * @since 2.0.0
 * @return ScoringService
 */
function medici_scoring(): ScoringService {
	return ScoringService::getInstance();
}

/**
 * Calculate lead score
 *
 * Shortcut function for calculating score from lead data.
 *
 * @since 2.0.0
 * @param array<string, mixed> $lead_data Lead data array.
 * @return int Score (0-100).
 */
function medici_calculate_lead_score( array $lead_data ): int {
	return ScoringService::getInstance()->calculate( $lead_data );
}

/**
 * Get lead score label
 *
 * @since 2.0.0
 * @param int $score Lead score.
 * @return string Label (hot, warm, cold).
 */
function medici_get_score_label( int $score ): string {
	return ScoringService::getInstance()->getLabel( $score );
}

/**
 * Get validation service instance
 *
 * @since 2.0.0
 * @return ValidationService
 */
function medici_validation(): ValidationService {
	return ValidationService::getInstance();
}

/**
 * Validate lead data
 *
 * @since 2.0.0
 * @param array<string, mixed> $data Lead data.
 * @return array{valid: bool, errors: array<string>, warnings: array<string>, quality_score: int, data: array<string, mixed>}
 */
function medici_validate_lead( array $data ): array {
	return ValidationService::getInstance()->process( $data );
}

// ============================================================================
// INITIALIZATION
// ============================================================================

/**
 * Initialize lead module
 *
 * @since 2.0.0
 * @return void
 */
function medici_lead_init(): void {
	// Initialize scoring admin.
	$scoring_admin = new ScoringAdmin( ScoringService::getInstance() );
	$scoring_admin->init();
}

// Initialize on admin_init for admin features.
add_action( 'admin_init', __NAMESPACE__ . '\medici_lead_init', 5 );

// ============================================================================
// BACKWARDS COMPATIBILITY LAYER
// ============================================================================

/**
 * Legacy compatibility - Lead_Integrations class alias
 */
class_alias( IntegrationManager::class, 'Medici\Lead\LegacyWrapper' );

/**
 * Legacy compatibility - Lead_Scoring class alias
 *
 * Maps old Lead_Scoring static methods to new ScoringService.
 */
class Lead_Scoring_Compat {

	/**
	 * Calculate score (legacy)
	 *
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score.
	 */
	public static function calculate( array $lead_data ): int {
		return ScoringService::getInstance()->calculate( $lead_data );
	}

	/**
	 * Get label (legacy)
	 *
	 * @param int $score Score.
	 * @return string Label.
	 */
	public static function get_label( int $score ): string {
		return ScoringService::getInstance()->getLabel( $score );
	}

	/**
	 * Get label HTML (legacy)
	 *
	 * @param int $score Score.
	 * @return string HTML.
	 */
	public static function get_label_html( int $score ): string {
		return ScoringService::getInstance()->getLabelHtml( $score );
	}

	/**
	 * Is enabled (legacy)
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return ScoringConfig::isEnabled();
	}

	/**
	 * Calculate and save score (legacy)
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function calculate_and_save_score( int $post_id ): void {
		ScoringService::getInstance()->calculateAndSave( $post_id );
	}
}

/**
 * Legacy compatibility - Lead_Validation class alias
 *
 * Maps old Lead_Validation static methods to new ValidationService.
 */
class Lead_Validation_Compat {

	/**
	 * Validate lead (legacy)
	 *
	 * @param array<string, mixed> $data Lead data.
	 * @return array{valid: bool, errors: array<string>, warnings: array<string>, data: array<string, mixed>, quality_score: int}
	 */
	public static function validate_lead( array $data ): array {
		return ValidationService::getInstance()->process( $data );
	}

	/**
	 * Check duplicate (legacy)
	 *
	 * @param string $email Email.
	 * @param string $phone Phone.
	 * @param int    $hours Hours.
	 * @return int|null Existing lead ID.
	 */
	public static function check_duplicate( string $email, string $phone, int $hours = 24 ): ?int {
		return ValidationService::getInstance()->checkDuplicate( $email, $phone, $hours );
	}

	/**
	 * Get validation stats (legacy)
	 *
	 * @param int $days Days.
	 * @return array<string, mixed>
	 */
	public static function get_validation_stats( int $days = 30 ): array {
		return ValidationService::getInstance()->getStats( $days );
	}
}

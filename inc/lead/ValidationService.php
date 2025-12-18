<?php
/**
 * Lead Validation Service
 *
 * Main orchestrator for lead data validation.
 *
 * @package    Medici_Agency
 * @subpackage Lead\Validation
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

use Medici\Lead\Validators\EmailValidator;
use Medici\Lead\Validators\PhoneValidator;
use Medici\Lead\Validators\NameValidator;
use Medici\Lead\Validators\MessageValidator;
use Medici\Lead\Validators\UtmValidator;
use Medici\Lead\Validators\SpamValidator;
use Medici\Lead\Validators\ServiceValidator;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Validation Service
 *
 * Combines multiple validators to validate complete lead data.
 *
 * @since 2.0.0
 */
final class ValidationService {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered validators
	 *
	 * @var array<ValidatorInterface>
	 */
	private array $validators = array();

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - registers default validators
	 */
	private function __construct() {
		$this->registerDefaultValidators();
	}

	/**
	 * Register default validators
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerDefaultValidators(): void {
		$this->validators = array(
			new SpamValidator(),    // Check spam first.
			new NameValidator(),
			new EmailValidator(),
			new PhoneValidator(),
			new MessageValidator(),
			new ServiceValidator(),
			new UtmValidator(),
		);
	}

	/**
	 * Add custom validator
	 *
	 * @since 2.0.0
	 * @param ValidatorInterface $validator Validator to add.
	 * @return self For chaining.
	 */
	public function addValidator( ValidatorInterface $validator ): self {
		$this->validators[] = $validator;
		return $this;
	}

	/**
	 * Remove validator by name
	 *
	 * @since 2.0.0
	 * @param string $name Validator name.
	 * @return self For chaining.
	 */
	public function removeValidator( string $name ): self {
		$this->validators = array_filter(
			$this->validators,
			fn( ValidatorInterface $v ) => $v->getName() !== $name
		);
		return $this;
	}

	/**
	 * Validate lead data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Lead data.
	 * @return ValidationResult Combined validation result.
	 */
	public function validate( array $data ): ValidationResult {
		$result = ValidationResult::success( array(), 0 );

		foreach ( $this->validators as $validator ) {
			$validator_result = $validator->validate( $data );
			$result           = $result->merge( $validator_result );

			// Stop on first hard error.
			if ( ! $validator_result->isValid() ) {
				break;
			}
		}

		return $result;
	}

	/**
	 * Sanitize lead data
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Lead data.
	 * @return array<string, mixed> Sanitized data.
	 */
	public function sanitize( array $data ): array {
		foreach ( $this->validators as $validator ) {
			$data = $validator->sanitize( $data );
		}
		return $data;
	}

	/**
	 * Validate and sanitize
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Lead data.
	 * @return array{valid: bool, errors: array<string>, warnings: array<string>, quality_score: int, data: array<string, mixed>}
	 */
	public function process( array $data ): array {
		$result = $this->validate( $data );
		return $result->toArray();
	}

	/**
	 * Check for duplicate lead
	 *
	 * @since 2.0.0
	 * @param string $email Lead email.
	 * @param string $phone Lead phone.
	 * @param int    $hours Check within last X hours.
	 * @return int|null Existing lead ID if duplicate.
	 */
	public function checkDuplicate( string $email, string $phone, int $hours = 24 ): ?int {
		$args = array(
			'post_type'      => 'medici_lead',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after' => sprintf( '%d hours ago', $hours ),
				),
			),
			'meta_query'     => array(
				'relation' => 'OR',
			),
		);

		if ( ! empty( $email ) ) {
			$args['meta_query'][] = array(
				'key'   => '_lead_email',
				'value' => $email,
			);
		}

		if ( ! empty( $phone ) ) {
			$args['meta_query'][] = array(
				'key'   => '_lead_phone',
				'value' => $phone,
			);
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			return (int) $query->posts[0];
		}

		return null;
	}

	/**
	 * Get validation statistics
	 *
	 * @since 2.0.0
	 * @param int $days Number of days to analyze.
	 * @return array<string, mixed> Statistics.
	 */
	public function getStats( int $days = 30 ): array {
		global $wpdb;

		$date_from = gmdate( 'Y-m-d', strtotime( sprintf( '-%d days', $days ) ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE post_type = 'medici_lead'
				AND post_date >= %s",
				$date_from
			)
		);

		$without_utm = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lead_utm_source'
				WHERE p.post_type = 'medici_lead'
				AND p.post_date >= %s
				AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = 'direct')",
				$date_from
			)
		);

		$spam_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'medici_lead'
				AND p.post_date >= %s
				AND pm.meta_key = '_lead_status'
				AND pm.meta_value = 'spam'",
				$date_from
			)
		);
		// phpcs:enable

		return array(
			'total_leads'     => $total,
			'without_utm'     => $without_utm,
			'without_utm_pct' => $total > 0 ? round( ( $without_utm / $total ) * 100, 1 ) : 0,
			'spam_count'      => $spam_count,
			'spam_pct'        => $total > 0 ? round( ( $spam_count / $total ) * 100, 1 ) : 0,
			'period_days'     => $days,
		);
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

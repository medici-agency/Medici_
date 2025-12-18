<?php
/**
 * Lead Scoring Service
 *
 * Main orchestrator for lead scoring using Strategy pattern.
 *
 * @package    Medici_Agency
 * @subpackage Lead\Scoring
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

use Medici\Lead\Scoring\SourceStrategy;
use Medici\Lead\Scoring\MediumStrategy;
use Medici\Lead\Scoring\ServiceStrategy;
use Medici\Lead\Scoring\BonusStrategy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Scoring Service
 *
 * Combines multiple scoring strategies to calculate total lead score.
 * Provides label classification and CRM integration helpers.
 *
 * @since 2.0.0
 */
final class ScoringService {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered strategies
	 *
	 * @var array<ScoringStrategyInterface>
	 */
	private array $strategies = array();

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
	 * Constructor - registers default strategies
	 */
	private function __construct() {
		$this->registerDefaultStrategies();
	}

	/**
	 * Register default scoring strategies
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerDefaultStrategies(): void {
		$this->strategies = array(
			new SourceStrategy(),
			new MediumStrategy(),
			new ServiceStrategy(),
			new BonusStrategy(),
		);
	}

	/**
	 * Add custom strategy
	 *
	 * @since 2.0.0
	 * @param ScoringStrategyInterface $strategy Strategy to add.
	 * @return self For chaining.
	 */
	public function addStrategy( ScoringStrategyInterface $strategy ): self {
		$this->strategies[] = $strategy;
		return $this;
	}

	/**
	 * Remove strategy by name
	 *
	 * @since 2.0.0
	 * @param string $name Strategy name.
	 * @return self For chaining.
	 */
	public function removeStrategy( string $name ): self {
		$this->strategies = array_filter(
			$this->strategies,
			fn( ScoringStrategyInterface $s ) => $s->getName() !== $name
		);
		return $this;
	}

	/**
	 * Calculate total lead score
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score (0-100).
	 */
	public function calculate( array $lead_data ): int {
		$total = 0;

		foreach ( $this->strategies as $strategy ) {
			$total += $strategy->calculate( $lead_data );
		}

		// Cap between 0 and 100.
		return min( 100, max( 0, $total ) );
	}

	/**
	 * Get full score breakdown
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return array<string, mixed> Detailed breakdown.
	 */
	public function getBreakdown( array $lead_data ): array {
		$breakdown = array(
			'total'      => 0,
			'strategies' => array(),
		);

		foreach ( $this->strategies as $strategy ) {
			$name               = $strategy->getName();
			$score              = $strategy->calculate( $lead_data );
			$strategy_breakdown = $strategy->getBreakdown( $lead_data );

			$breakdown['strategies'][ $name ] = array(
				'score'     => $score,
				'max_score' => $strategy->getMaxScore(),
				'details'   => $strategy_breakdown,
			);

			$breakdown['total'] += $score;
		}

		$breakdown['total'] = min( 100, max( 0, $breakdown['total'] ) );
		$breakdown['label'] = $this->getLabel( $breakdown['total'] );

		return $breakdown;
	}

	/**
	 * Get score label
	 *
	 * @since 2.0.0
	 * @param int $score Lead score.
	 * @return string Label (hot, warm, cold).
	 */
	public function getLabel( int $score ): string {
		if ( $score >= ScoringConfig::getThreshold( 'hot' ) ) {
			return 'hot';
		}

		if ( $score >= ScoringConfig::getThreshold( 'warm' ) ) {
			return 'warm';
		}

		return 'cold';
	}

	/**
	 * Get label HTML badge
	 *
	 * @since 2.0.0
	 * @param int $score Lead score.
	 * @return string HTML badge.
	 */
	public function getLabelHtml( int $score ): string {
		$label = $this->getLabel( $score );

		$labels = array(
			'hot'  => array(
				'text'  => '🔥 Гарячий',
				'color' => '#dc2626',
			),
			'warm' => array(
				'text'  => '🌡️ Теплий',
				'color' => '#f59e0b',
			),
			'cold' => array(
				'text'  => '❄️ Холодний',
				'color' => '#3b82f6',
			),
		);

		$config = $labels[ $label ];

		return sprintf(
			'<span style="background: %s; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">%s (%d)</span>',
			esc_attr( $config['color'] ),
			esc_html( $config['text'] ),
			$score
		);
	}

	/**
	 * Get label text for CRM
	 *
	 * @since 2.0.0
	 * @param string $label Label name.
	 * @return string Human-readable text.
	 */
	public function getLabelText( string $label ): string {
		$texts = array(
			'hot'  => 'Hot Lead (70+)',
			'warm' => 'Warm Lead (40-69)',
			'cold' => 'Cold Lead (0-39)',
		);

		return $texts[ $label ] ?? 'Unknown';
	}

	/**
	 * Calculate and save score for lead post
	 *
	 * @since 2.0.0
	 * @param int $post_id Lead post ID.
	 * @return int Calculated score.
	 */
	public function calculateAndSave( int $post_id ): int {
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return 0;
		}

		// Get lead data from post meta.
		$lead_data = $this->getLeadDataFromPost( $post_id );

		// Calculate score.
		$score = $this->calculate( $lead_data );
		$label = $this->getLabel( $score );

		// Save.
		update_post_meta( $post_id, '_lead_score', $score );
		update_post_meta( $post_id, '_lead_score_label', $label );

		return $score;
	}

	/**
	 * Get lead data from post meta
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return array<string, mixed> Lead data.
	 */
	public function getLeadDataFromPost( int $post_id ): array {
		return array(
			'utm_source' => get_post_meta( $post_id, '_lead_utm_source', true ),
			'utm_medium' => get_post_meta( $post_id, '_lead_utm_medium', true ),
			'service'    => get_post_meta( $post_id, '_lead_service', true ),
			'phone'      => get_post_meta( $post_id, '_lead_phone', true ),
			'message'    => get_post_meta( $post_id, '_lead_message', true ),
			'email'      => get_post_meta( $post_id, '_lead_email', true ),
		);
	}

	/**
	 * Get breakdown for lead post
	 *
	 * @since 2.0.0
	 * @param int $post_id Lead post ID.
	 * @return array<string, mixed> Score breakdown.
	 */
	public function getBreakdownForPost( int $post_id ): array {
		$lead_data = $this->getLeadDataFromPost( $post_id );
		return $this->getBreakdown( $lead_data );
	}

	/**
	 * Check if lead should sync to CRM
	 *
	 * @since 2.0.0
	 * @param int $post_id Lead post ID.
	 * @return bool True if should sync.
	 */
	public function shouldSyncToCrm( int $post_id ): bool {
		if ( ! ScoringConfig::isEnabled() ) {
			return true; // If scoring disabled, always sync.
		}

		$threshold = ScoringConfig::getCrmThreshold();
		$score     = (int) get_post_meta( $post_id, '_lead_score', true );

		return $score >= $threshold;
	}

	/**
	 * Get CRM-ready lead data
	 *
	 * @since 2.0.0
	 * @param int $post_id Lead post ID.
	 * @return array<string, mixed> CRM data.
	 */
	public function getCrmData( int $post_id ): array {
		$score = (int) get_post_meta( $post_id, '_lead_score', true );
		$label = $this->getLabel( $score );

		return array(
			// Standard lead fields.
			'lead_id'        => $post_id,
			'name'           => get_post_meta( $post_id, '_lead_name', true ),
			'email'          => get_post_meta( $post_id, '_lead_email', true ),
			'phone'          => get_post_meta( $post_id, '_lead_phone', true ),
			'service'        => get_post_meta( $post_id, '_lead_service', true ),
			'message'        => get_post_meta( $post_id, '_lead_message', true ),
			'page_url'       => get_post_meta( $post_id, '_lead_page_url', true ),
			'status'         => get_post_meta( $post_id, '_lead_status', true ) ?: 'new',
			'created_at'     => get_the_date( 'c', $post_id ),

			// UTM fields.
			'utm_source'     => get_post_meta( $post_id, '_lead_utm_source', true ),
			'utm_medium'     => get_post_meta( $post_id, '_lead_utm_medium', true ),
			'utm_campaign'   => get_post_meta( $post_id, '_lead_utm_campaign', true ),
			'utm_term'       => get_post_meta( $post_id, '_lead_utm_term', true ),
			'utm_content'    => get_post_meta( $post_id, '_lead_utm_content', true ),

			// WordPress scoring.
			'wp_score'       => $score,
			'wp_score_label' => $label,
			'wp_score_text'  => $this->getLabelText( $label ),
		);
	}

	/**
	 * Get leads ready for CRM sync
	 *
	 * @since 2.0.0
	 * @param int $limit Max leads.
	 * @return array<int, array<string, mixed>> Leads data.
	 */
	public function getLeadsForCrmSync( int $limit = 50 ): array {
		$threshold = ScoringConfig::getCrmThreshold();

		$query = new \WP_Query(
			array(
				'post_type'      => 'medici_lead',
				'posts_per_page' => $limit,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_lead_score',
						'value'   => $threshold,
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_lead_crm_synced',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => '_lead_crm_synced',
							'value' => '0',
						),
					),
				),
				'orderby'        => 'meta_value_num',
				'meta_key'       => '_lead_score',
				'order'          => 'DESC',
			)
		);

		$leads = array();
		foreach ( $query->posts as $post ) {
			$leads[ $post->ID ] = $this->getCrmData( $post->ID );
		}

		return $leads;
	}

	/**
	 * Mark lead as synced to CRM
	 *
	 * @since 2.0.0
	 * @param int    $post_id    Lead post ID.
	 * @param string $crm_id     CRM record ID.
	 * @param string $crm_system CRM system name.
	 * @return bool Success.
	 */
	public function markAsSynced( int $post_id, string $crm_id = '', string $crm_system = '' ): bool {
		update_post_meta( $post_id, '_lead_crm_synced', '1' );
		update_post_meta( $post_id, '_lead_crm_synced_at', current_time( 'mysql' ) );

		if ( $crm_id ) {
			update_post_meta( $post_id, '_lead_crm_id', $crm_id );
		}

		if ( $crm_system ) {
			update_post_meta( $post_id, '_lead_crm_system', $crm_system );
		}

		return true;
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

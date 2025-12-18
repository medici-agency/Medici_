<?php
/**
 * Lead Scoring Module
 *
 * Calculates lead score based on source, service, and engagement.
 * Can be disabled when using external CRM with built-in scoring.
 *
 * @package Medici
 * @since 1.6.0
 */

declare(strict_types=1);

namespace Medici;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Scoring Class
 */
final class Lead_Scoring {

	/**
	 * Option name for settings.
	 */
	private const OPTION_NAME = 'medici_lead_scoring_settings';

	/**
	 * Default settings.
	 */
	private const DEFAULTS = array(
		'enabled'       => true,  // Enable/disable scoring.
		'crm_threshold' => 40,    // Min score to sync to CRM (warm+).
		'crm_sync'      => false, // Auto-sync to CRM.
	);

	/**
	 * Score weights by source.
	 * Higher score = higher quality lead.
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
	 * Score weights by medium.
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
	 * Score weights by service.
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
	 * Additional score factors.
	 */
	private const BONUS_SCORES = array(
		'has_phone'        => 15, // Serious inquiry.
		'has_message'      => 10, // Detailed request.
		'long_message'     => 5,  // 100+ chars message.
		'visited_services' => 5,  // Browsed services.
		'visited_cases'    => 10, // Viewed portfolio.
		'read_blog'        => 3,  // Content engagement.
		'returning_user'   => 8,  // Multiple visits.
	);

	/**
	 * Score thresholds for labels.
	 */
	private const THRESHOLDS = array(
		'hot'  => 70, // 70+ = Hot lead.
		'warm' => 40, // 40-69 = Warm lead.
		'cold' => 0,  // 0-39 = Cold lead.
	);

	/**
	 * Initialize lead scoring.
	 */
	public static function init(): void {
		// Register settings.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Add settings section to Ліди > Інтеграції page.
		add_action( 'medici_lead_integrations_settings', array( __CLASS__, 'render_settings_section' ) );

		// Skip if disabled.
		if ( ! self::is_enabled() ) {
			return;
		}

		// Calculate score when lead is created.
		add_action( 'save_post_medici_lead', array( __CLASS__, 'calculate_and_save_score' ), 20, 1 );

		// Add score column to leads list.
		add_filter( 'manage_medici_lead_posts_columns', array( __CLASS__, 'add_score_column' ) );
		add_action( 'manage_medici_lead_posts_custom_column', array( __CLASS__, 'render_score_column' ), 10, 2 );
		add_filter( 'manage_edit-medici_lead_sortable_columns', array( __CLASS__, 'make_score_sortable' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_score' ) );

		// Add score meta box.
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_score_meta_box' ) );
	}

	/**
	 * Check if lead scoring is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$settings = self::get_settings();
		return (bool) $settings['enabled'];
	}

	/**
	 * Get settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $settings, self::DEFAULTS );
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $settings Settings to update.
	 * @return bool
	 */
	public static function update_settings( array $settings ): bool {
		$current  = self::get_settings();
		$settings = wp_parse_args( $settings, $current );
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'medici_lead_integrations',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::DEFAULTS,
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Input settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::DEFAULTS;
		}

		return array(
			'enabled'       => ! empty( $input['enabled'] ),
			'crm_threshold' => isset( $input['crm_threshold'] ) ? absint( $input['crm_threshold'] ) : 40,
			'crm_sync'      => ! empty( $input['crm_sync'] ),
		);
	}

	/**
	 * Render settings section (added to Ліди > Інтеграції).
	 */
	public static function render_settings_section(): void {
		$settings = self::get_settings();
		?>
		<div class="postbox" style="margin-top: 20px;">
			<h2 class="hndle" style="padding: 10px 15px; margin: 0;">
				<span>📊 <?php esc_html_e( 'Lead Scoring', 'flavor' ); ?></span>
			</h2>
			<div class="inside" style="padding: 15px;">
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Автоматичний скоринг лідів за джерелом, послугою та engagement. Вимкніть, якщо використовуєте CRM з власною системою скорингу (HubSpot, Salesforce, тощо).', 'flavor' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Увімкнути Lead Scoring', 'flavor' ); ?></th>
						<td>
							<label>
								<input type="checkbox"
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]"
									value="1"
									<?php checked( $settings['enabled'] ); ?>>
								<?php esc_html_e( 'Рахувати score для нових лідів', 'flavor' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Якщо вимкнено, score не буде розраховуватись. Існуючі scores залишаться.', 'flavor' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'CRM поріг синхронізації', 'flavor' ); ?></th>
						<td>
							<input type="number"
								name="<?php echo esc_attr( self::OPTION_NAME ); ?>[crm_threshold]"
								value="<?php echo esc_attr( (string) $settings['crm_threshold'] ); ?>"
								min="0"
								max="100"
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Мінімальний score для відправки в CRM. За замовчуванням 40 (warm+).', 'flavor' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h4 style="margin-top: 20px;"><?php esc_html_e( '💡 Рекомендації для CRM інтеграції:', 'flavor' ); ?></h4>
				<ul style="list-style: disc; margin-left: 20px; color: #666;">
					<li><?php esc_html_e( 'HubSpot/Salesforce: Вимкніть scoring тут, використовуйте CRM scoring', 'flavor' ); ?></li>
					<li><?php esc_html_e( 'Pipedrive/Zoho: Можна передавати WP score як custom field', 'flavor' ); ?></li>
					<li><?php esc_html_e( 'Без CRM: Залиште увімкненим для візуалізації в WordPress', 'flavor' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Calculate lead score.
	 *
	 * @param array<string, mixed> $lead_data Lead data.
	 * @return int Score (0-100).
	 */
	public static function calculate( array $lead_data ): int {
		$score = 0;

		// Source score.
		$source = strtolower( $lead_data['utm_source'] ?? 'direct' );
		$score += self::SOURCE_SCORES[ $source ] ?? self::SOURCE_SCORES['direct'];

		// Medium score.
		$medium = strtolower( $lead_data['utm_medium'] ?? '' );
		$score += self::MEDIUM_SCORES[ $medium ] ?? 0;

		// Service score.
		$service = strtolower( $lead_data['service'] ?? 'other' );
		$score  += self::SERVICE_SCORES[ $service ] ?? self::SERVICE_SCORES['other'];

		// Bonus: Has phone.
		if ( ! empty( $lead_data['phone'] ) ) {
			$score += self::BONUS_SCORES['has_phone'];
		}

		// Bonus: Has message.
		$message = $lead_data['message'] ?? '';
		if ( ! empty( $message ) ) {
			$score += self::BONUS_SCORES['has_message'];

			// Long message bonus.
			if ( strlen( $message ) > 100 ) {
				$score += self::BONUS_SCORES['long_message'];
			}
		}

		// Bonus: Engagement (from analytics).
		$engagement = $lead_data['engagement'] ?? array();
		if ( ! empty( $engagement['visited_services'] ) ) {
			$score += self::BONUS_SCORES['visited_services'];
		}
		if ( ! empty( $engagement['visited_cases'] ) ) {
			$score += self::BONUS_SCORES['visited_cases'];
		}
		if ( ! empty( $engagement['read_blog'] ) ) {
			$score += self::BONUS_SCORES['read_blog'];
		}
		if ( ! empty( $engagement['returning_user'] ) ) {
			$score += self::BONUS_SCORES['returning_user'];
		}

		// Cap at 100.
		return min( 100, max( 0, $score ) );
	}

	/**
	 * Get score label.
	 *
	 * @param int $score Lead score.
	 * @return string Label (hot, warm, cold).
	 */
	public static function get_label( int $score ): string {
		if ( $score >= self::THRESHOLDS['hot'] ) {
			return 'hot';
		}

		if ( $score >= self::THRESHOLDS['warm'] ) {
			return 'warm';
		}

		return 'cold';
	}

	/**
	 * Get label display.
	 *
	 * @param int $score Lead score.
	 * @return string HTML badge.
	 */
	public static function get_label_html( int $score ): string {
		$label = self::get_label( $score );

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
	 * Calculate and save score for lead.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function calculate_and_save_score( int $post_id ): void {
		// Don't recalculate if score exists and lead data hasn't changed.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Get lead data.
		$lead_data = array(
			'utm_source' => get_post_meta( $post_id, '_lead_utm_source', true ),
			'utm_medium' => get_post_meta( $post_id, '_lead_utm_medium', true ),
			'service'    => get_post_meta( $post_id, '_lead_service', true ),
			'phone'      => get_post_meta( $post_id, '_lead_phone', true ),
			'message'    => get_post_meta( $post_id, '_lead_message', true ),
		);

		// Calculate score.
		$score = self::calculate( $lead_data );

		// Save score.
		update_post_meta( $post_id, '_lead_score', $score );
		update_post_meta( $post_id, '_lead_score_label', self::get_label( $score ) );
	}

	/**
	 * Add score column.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function add_score_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			// Add score after title.
			if ( 'title' === $key ) {
				$new_columns['lead_score'] = __( 'Скор', 'flavor' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render score column.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_score_column( string $column, int $post_id ): void {
		if ( 'lead_score' !== $column ) {
			return;
		}

		$score = (int) get_post_meta( $post_id, '_lead_score', true );

		if ( 0 === $score ) {
			// Calculate if not exists.
			self::calculate_and_save_score( $post_id );
			$score = (int) get_post_meta( $post_id, '_lead_score', true );
		}

		echo self::get_label_html( $score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Make score column sortable.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function make_score_sortable( array $columns ): array {
		$columns['lead_score'] = 'lead_score';
		return $columns;
	}

	/**
	 * Sort by score.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function sort_by_score( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'medici_lead' !== $query->get( 'post_type' ) ) {
			return;
		}

		if ( 'lead_score' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_lead_score' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Add score meta box.
	 */
	public static function add_score_meta_box(): void {
		add_meta_box(
			'medici_lead_score',
			__( 'Lead Score', 'flavor' ),
			array( __CLASS__, 'render_score_meta_box' ),
			'medici_lead',
			'side',
			'high'
		);
	}

	/**
	 * Render score meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public static function render_score_meta_box( \WP_Post $post ): void {
		$score = (int) get_post_meta( $post->ID, '_lead_score', true );

		if ( 0 === $score ) {
			self::calculate_and_save_score( $post->ID );
			$score = (int) get_post_meta( $post->ID, '_lead_score', true );
		}

		$label = self::get_label( $score );
		?>
		<div style="text-align: center; padding: 15px 0;">
			<div style="font-size: 48px; font-weight: bold; color: <?php echo 'hot' === $label ? '#dc2626' : ( 'warm' === $label ? '#f59e0b' : '#3b82f6' ); ?>;">
				<?php echo esc_html( (string) $score ); ?>
			</div>
			<div style="margin-top: 10px;">
				<?php echo self::get_label_html( $score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<hr>

		<h4 style="margin: 10px 0 5px;"><?php esc_html_e( 'Як рахується:', 'flavor' ); ?></h4>
		<ul style="font-size: 12px; color: #666; margin: 0; padding-left: 15px;">
			<li><?php esc_html_e( 'Джерело (LinkedIn, Google...)', 'flavor' ); ?></li>
			<li><?php esc_html_e( 'Послуга (Branding, Ads...)', 'flavor' ); ?></li>
			<li><?php esc_html_e( 'Телефон (+15 балів)', 'flavor' ); ?></li>
			<li><?php esc_html_e( 'Повідомлення (+10 балів)', 'flavor' ); ?></li>
		</ul>

		<p style="font-size: 11px; color: #999; margin-top: 10px;">
			<?php esc_html_e( '🔥 70+ Гарячий | 🌡️ 40-69 Теплий | ❄️ 0-39 Холодний', 'flavor' ); ?>
		</p>
		<?php
	}

	/**
	 * Get score breakdown for lead.
	 *
	 * @param int $post_id Lead post ID.
	 * @return array<string, int> Score breakdown.
	 */
	public static function get_breakdown( int $post_id ): array {
		$source  = strtolower( (string) get_post_meta( $post_id, '_lead_utm_source', true ) ) ?: 'direct';
		$medium  = strtolower( (string) get_post_meta( $post_id, '_lead_utm_medium', true ) );
		$service = strtolower( (string) get_post_meta( $post_id, '_lead_service', true ) ) ?: 'other';
		$phone   = get_post_meta( $post_id, '_lead_phone', true );
		$message = get_post_meta( $post_id, '_lead_message', true );

		return array(
			'source'      => self::SOURCE_SCORES[ $source ] ?? self::SOURCE_SCORES['direct'],
			'medium'      => self::MEDIUM_SCORES[ $medium ] ?? 0,
			'service'     => self::SERVICE_SCORES[ $service ] ?? self::SERVICE_SCORES['other'],
			'has_phone'   => ! empty( $phone ) ? self::BONUS_SCORES['has_phone'] : 0,
			'has_message' => ! empty( $message ) ? self::BONUS_SCORES['has_message'] : 0,
		);
	}

	// =========================================================================
	// CRM INTEGRATION HELPERS
	// =========================================================================

	/**
	 * Check if lead should be synced to CRM based on score threshold.
	 *
	 * @param int $post_id Lead post ID.
	 * @return bool True if score >= threshold.
	 */
	public static function should_sync_to_crm( int $post_id ): bool {
		if ( ! self::is_enabled() ) {
			return true; // If scoring disabled, always sync.
		}

		$settings  = self::get_settings();
		$threshold = (int) $settings['crm_threshold'];
		$score     = (int) get_post_meta( $post_id, '_lead_score', true );

		return $score >= $threshold;
	}

	/**
	 * Get CRM-ready lead data with score information.
	 * Use this when sending to HubSpot, Salesforce, Pipedrive, etc.
	 *
	 * @param int $post_id Lead post ID.
	 * @return array<string, mixed> CRM-ready data.
	 */
	public static function get_crm_data( int $post_id ): array {
		$score = (int) get_post_meta( $post_id, '_lead_score', true );
		$label = self::get_label( $score );

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

			// WordPress scoring (use as custom field in CRM).
			'wp_score'       => $score,
			'wp_score_label' => $label, // hot, warm, cold.
			'wp_score_text'  => self::get_label_text( $label ),
		);
	}

	/**
	 * Get label text for CRM.
	 *
	 * @param string $label Label (hot, warm, cold).
	 * @return string Human-readable text.
	 */
	private static function get_label_text( string $label ): string {
		$texts = array(
			'hot'  => 'Hot Lead (70+)',
			'warm' => 'Warm Lead (40-69)',
			'cold' => 'Cold Lead (0-39)',
		);

		return $texts[ $label ] ?? 'Unknown';
	}

	/**
	 * Get leads ready for CRM sync (above threshold).
	 *
	 * @param int $limit Max leads to return.
	 * @return array<int, array<string, mixed>> Array of CRM-ready lead data.
	 */
	public static function get_leads_for_crm_sync( int $limit = 50 ): array {
		$settings  = self::get_settings();
		$threshold = (int) $settings['crm_threshold'];

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
			$leads[ $post->ID ] = self::get_crm_data( $post->ID );
		}

		return $leads;
	}

	/**
	 * Mark lead as synced to CRM.
	 *
	 * @param int    $post_id    Lead post ID.
	 * @param string $crm_id     CRM record ID (optional).
	 * @param string $crm_system CRM system name (optional).
	 * @return bool
	 */
	public static function mark_as_synced( int $post_id, string $crm_id = '', string $crm_system = '' ): bool {
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
}

// Initialize.
Lead_Scoring::init();

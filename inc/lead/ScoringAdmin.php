<?php
/**
 * Lead Scoring Admin
 *
 * WordPress admin integration for lead scoring.
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
 * Lead Scoring Admin Class
 *
 * Handles admin columns, meta boxes, and settings UI.
 *
 * @since 2.0.0
 */
final class ScoringAdmin {

	/**
	 * Scoring service
	 *
	 * @var ScoringService
	 */
	private ScoringService $scoring;

	/**
	 * Constructor
	 *
	 * @param ScoringService $scoring Scoring service instance.
	 */
	public function __construct( ScoringService $scoring ) {
		$this->scoring = $scoring;
	}

	/**
	 * Initialize admin hooks
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Register settings.
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		// Add settings section.
		add_action( 'medici_lead_integrations_settings', array( $this, 'renderSettingsSection' ) );

		// Skip rest if scoring disabled.
		if ( ! ScoringConfig::isEnabled() ) {
			return;
		}

		// Calculate score on lead save.
		add_action( 'save_post_medici_lead', array( $this, 'onLeadSave' ), 20, 1 );

		// Admin columns.
		add_filter( 'manage_medici_lead_posts_columns', array( $this, 'addScoreColumn' ) );
		add_action( 'manage_medici_lead_posts_custom_column', array( $this, 'renderScoreColumn' ), 10, 2 );
		add_filter( 'manage_edit-medici_lead_sortable_columns', array( $this, 'makeScoreSortable' ) );
		add_action( 'pre_get_posts', array( $this, 'sortByScore' ) );

		// Meta box.
		add_action( 'add_meta_boxes', array( $this, 'addScoreMetaBox' ) );
	}

	/**
	 * Register settings
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function registerSettings(): void {
		register_setting(
			'medici_lead_integrations',
			'medici_lead_scoring_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitizeSettings' ),
				'default'           => array(
					'enabled'       => true,
					'crm_threshold' => 40,
					'crm_sync'      => false,
				),
			)
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @since 2.0.0
	 * @param mixed $input Input settings.
	 * @return array<string, mixed>
	 */
	public function sanitizeSettings( $input ): array {
		if ( ! is_array( $input ) ) {
			return array(
				'enabled'       => true,
				'crm_threshold' => 40,
				'crm_sync'      => false,
			);
		}

		return array(
			'enabled'       => ! empty( $input['enabled'] ),
			'crm_threshold' => isset( $input['crm_threshold'] ) ? absint( $input['crm_threshold'] ) : 40,
			'crm_sync'      => ! empty( $input['crm_sync'] ),
		);
	}

	/**
	 * Render settings section
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function renderSettingsSection(): void {
		$settings = ScoringConfig::getSettings();
		?>
		<div class="postbox" style="margin-top: 20px;">
			<h2 class="hndle" style="padding: 10px 15px; margin: 0;">
				<span>📊 <?php esc_html_e( 'Lead Scoring', 'medici.agency' ); ?></span>
			</h2>
			<div class="inside" style="padding: 15px;">
				<p class="description" style="margin-bottom: 15px;">
					<?php esc_html_e( 'Автоматичний скоринг лідів за джерелом, послугою та engagement. Вимкніть, якщо використовуєте CRM з власною системою скорингу.', 'medici.agency' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Увімкнути Lead Scoring', 'medici.agency' ); ?></th>
						<td>
							<label>
								<input type="checkbox"
									name="medici_lead_scoring_settings[enabled]"
									value="1"
									<?php checked( $settings['enabled'] ); ?>>
								<?php esc_html_e( 'Рахувати score для нових лідів', 'medici.agency' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Якщо вимкнено, score не буде розраховуватись.', 'medici.agency' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'CRM поріг синхронізації', 'medici.agency' ); ?></th>
						<td>
							<input type="number"
								name="medici_lead_scoring_settings[crm_threshold]"
								value="<?php echo esc_attr( (string) $settings['crm_threshold'] ); ?>"
								min="0"
								max="100"
								class="small-text">
							<p class="description">
								<?php esc_html_e( 'Мінімальний score для відправки в CRM (40 = warm+).', 'medici.agency' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<h4 style="margin-top: 20px;"><?php esc_html_e( '💡 Пороги оцінки:', 'medici.agency' ); ?></h4>
				<ul style="list-style: disc; margin-left: 20px; color: #666;">
					<li>🔥 70+ — Гарячий лід (high intent)</li>
					<li>🌡️ 40-69 — Теплий лід (interested)</li>
					<li>❄️ 0-39 — Холодний лід (exploring)</li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle lead save
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function onLeadSave( int $post_id ): void {
		$this->scoring->calculateAndSave( $post_id );
	}

	/**
	 * Add score column
	 *
	 * @since 2.0.0
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function addScoreColumn( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['lead_score'] = __( 'Скор', 'medici.agency' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render score column
	 *
	 * @since 2.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function renderScoreColumn( string $column, int $post_id ): void {
		if ( 'lead_score' !== $column ) {
			return;
		}

		$score = (int) get_post_meta( $post_id, '_lead_score', true );

		if ( 0 === $score ) {
			$score = $this->scoring->calculateAndSave( $post_id );
		}

		echo $this->scoring->getLabelHtml( $score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Make score column sortable
	 *
	 * @since 2.0.0
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public function makeScoreSortable( array $columns ): array {
		$columns['lead_score'] = 'lead_score';
		return $columns;
	}

	/**
	 * Sort by score
	 *
	 * @since 2.0.0
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function sortByScore( \WP_Query $query ): void {
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
	 * Add score meta box
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function addScoreMetaBox(): void {
		add_meta_box(
			'medici_lead_score',
			__( 'Lead Score', 'medici.agency' ),
			array( $this, 'renderScoreMetaBox' ),
			'medici_lead',
			'side',
			'high'
		);
	}

	/**
	 * Render score meta box
	 *
	 * @since 2.0.0
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function renderScoreMetaBox( \WP_Post $post ): void {
		$score = (int) get_post_meta( $post->ID, '_lead_score', true );

		if ( 0 === $score ) {
			$score = $this->scoring->calculateAndSave( $post->ID );
		}

		$label     = $this->scoring->getLabel( $score );
		$breakdown = $this->scoring->getBreakdownForPost( $post->ID );
		?>
		<div style="text-align: center; padding: 15px 0;">
			<div style="font-size: 48px; font-weight: bold; color: <?php echo 'hot' === $label ? '#dc2626' : ( 'warm' === $label ? '#f59e0b' : '#3b82f6' ); ?>;">
				<?php echo esc_html( (string) $score ); ?>
			</div>
			<div style="margin-top: 10px;">
				<?php echo $this->scoring->getLabelHtml( $score ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<hr>

		<h4 style="margin: 10px 0 5px;"><?php esc_html_e( 'Розбивка:', 'medici.agency' ); ?></h4>
		<ul style="font-size: 12px; color: #666; margin: 0; padding-left: 15px;">
			<?php foreach ( $breakdown['strategies'] as $name => $data ) : ?>
				<li>
					<?php echo esc_html( ucfirst( $name ) ); ?>:
					<strong><?php echo esc_html( (string) $data['score'] ); ?></strong>
					/ <?php echo esc_html( (string) $data['max_score'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<p style="font-size: 11px; color: #999; margin-top: 10px;">
			<?php esc_html_e( '🔥 70+ | 🌡️ 40-69 | ❄️ 0-39', 'medici.agency' ); ?>
		</p>
		<?php
	}
}

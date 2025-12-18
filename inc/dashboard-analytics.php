<?php
/**
 * Dashboard Analytics Module
 *
 * Provides analytics dashboard widgets, lead statistics, and SEO audit tools.
 * Includes:
 * - Dashboard Widget with lead and blog stats
 * - Lead Analytics page with charts and conversion funnels
 * - SEO Audit Tool for blog posts
 * - Bulk Actions for leads
 *
 * @package    Medici_Agency
 * @subpackage Analytics
 * @since      1.5.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Analytics Class
 *
 * @since 1.5.0
 */
final class Dashboard_Analytics {

	/**
	 * Initialize Dashboard Analytics
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Dashboard widgets
		add_action( 'wp_dashboard_setup', array( $self, 'register_dashboard_widgets' ) );

		// Admin menu for analytics page
		add_action( 'admin_menu', array( $self, 'add_analytics_page' ) );

		// Bulk actions for leads
		add_filter( 'bulk_actions-edit-medici_lead', array( $self, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-medici_lead', array( $self, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $self, 'bulk_action_notices' ) );

		// SEO Audit column for blog posts
		add_filter( 'manage_medici_blog_posts_columns', array( $self, 'add_seo_column' ) );
		add_action( 'manage_medici_blog_posts_custom_column', array( $self, 'render_seo_column' ), 10, 2 );

		// Enqueue admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register dashboard widgets
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function register_dashboard_widgets(): void {
		// Lead stats widget
		wp_add_dashboard_widget(
			'medici_leads_widget',
			__( '📊 Статистика лідів', 'medici.agency' ),
			array( $this, 'render_leads_widget' )
		);

		// Blog stats widget
		wp_add_dashboard_widget(
			'medici_blog_widget',
			__( '📝 Статистика блогу', 'medici.agency' ),
			array( $this, 'render_blog_widget' )
		);
	}

	/**
	 * Render leads dashboard widget
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_leads_widget(): void {
		$stats        = $this->get_lead_stats();
		$recent_leads = $this->get_recent_leads( 5 );

		?>
		<div class="medici-dashboard-widget">
			<div class="medici-stats-grid">
				<div class="medici-stat-card">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['total'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Всього лідів', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card medici-stat-new">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['new'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Нових', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card medici-stat-qualified">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['qualified'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Кваліфікованих', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card medici-stat-closed">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['closed'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Закритих', 'medici.agency' ); ?></span>
				</div>
			</div>

			<h4><?php esc_html_e( 'Останні ліди', 'medici.agency' ); ?></h4>
			<?php if ( ! empty( $recent_leads ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( "Ім'я", 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Послуга', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Дата', 'medici.agency' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_leads as $lead ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $lead->ID ) ); ?>">
										<?php echo esc_html( get_post_meta( $lead->ID, '_medici_lead_name', true ) ?: '—' ); ?>
									</a>
								</td>
								<td><?php echo esc_html( get_post_meta( $lead->ID, '_medici_lead_service', true ) ?: '—' ); ?></td>
								<td><?php echo esc_html( $this->get_status_label( get_post_meta( $lead->ID, '_medici_lead_status', true ) ?: 'new' ) ); ?></td>
								<td><?php echo esc_html( get_the_date( 'd.m.Y', $lead->ID ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'Поки немає лідів.', 'medici.agency' ); ?></p>
			<?php endif; ?>

			<p class="medici-widget-footer">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead' ) ); ?>" class="button">
					<?php esc_html_e( 'Всі ліди', 'medici.agency' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=medici-lead-analytics' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Аналітика', 'medici.agency' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render blog dashboard widget
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_blog_widget(): void {
		$stats         = $this->get_blog_stats();
		$popular_posts = $this->get_popular_posts( 5 );

		?>
		<div class="medici-dashboard-widget">
			<div class="medici-stats-grid">
				<div class="medici-stat-card">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['total'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Статей', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card">
					<span class="medici-stat-number"><?php echo esc_html( number_format( (float) $stats['total_views'] ) ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Переглядів', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card">
					<span class="medici-stat-number"><?php echo esc_html( (string) $stats['featured'] ); ?></span>
					<span class="medici-stat-label"><?php esc_html_e( 'Рекомендованих', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-stat-card">
					<span class="medici-stat-number"><?php echo esc_html( (string) round( $stats['avg_reading_time'], 1 ) ); ?> хв</span>
					<span class="medici-stat-label"><?php esc_html_e( 'Сер. час читання', 'medici.agency' ); ?></span>
				</div>
			</div>

			<h4><?php esc_html_e( 'Популярні статті', 'medici.agency' ); ?></h4>
			<?php if ( ! empty( $popular_posts ) ) : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Стаття', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Перегляди', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'SEO', 'medici.agency' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $popular_posts as $post ) : ?>
							<?php $seo_score = $this->calculate_seo_score( $post->ID ); ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
										<?php echo esc_html( wp_trim_words( $post->post_title, 8 ) ); ?>
									</a>
								</td>
								<td><?php echo esc_html( number_format( (float) get_post_meta( $post->ID, '_medici_post_views', true ) ?: 0 ) ); ?></td>
								<td>
									<span class="medici-seo-badge medici-seo-<?php echo esc_attr( $seo_score['level'] ); ?>">
										<?php echo esc_html( $seo_score['score'] . '%' ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'Поки немає статей.', 'medici.agency' ); ?></p>
			<?php endif; ?>

			<p class="medici-widget-footer">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_blog' ) ); ?>" class="button">
					<?php esc_html_e( 'Всі статті', 'medici.agency' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=medici-seo-audit' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'SEO Аудит', 'medici.agency' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Add analytics pages to admin menu
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function add_analytics_page(): void {
		// Lead Analytics page
		add_submenu_page(
			'edit.php?post_type=medici_lead',
			__( 'Аналітика лідів', 'medici.agency' ),
			__( '📊 Аналітика', 'medici.agency' ),
			'manage_options',
			'medici-lead-analytics',
			array( $this, 'render_analytics_page' )
		);

		// SEO Audit page
		add_submenu_page(
			'edit.php?post_type=medici_blog',
			__( 'SEO Аудит', 'medici.agency' ),
			__( '🔍 SEO Аудит', 'medici.agency' ),
			'manage_options',
			'medici-seo-audit',
			array( $this, 'render_seo_audit_page' )
		);
	}

	/**
	 * Render Lead Analytics page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_analytics_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats             = $this->get_lead_stats();
		$by_service        = $this->get_leads_by_service();
		$by_source         = $this->get_leads_by_utm_source();
		$by_date           = $this->get_leads_by_date( 30 );
		$conversion_funnel = $this->get_conversion_funnel();

		?>
		<div class="wrap medici-analytics-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Summary Cards -->
			<div class="medici-analytics-cards">
				<div class="medici-card">
					<div class="medici-card-icon">📥</div>
					<div class="medici-card-content">
						<span class="medici-card-number"><?php echo esc_html( (string) $stats['total'] ); ?></span>
						<span class="medici-card-label"><?php esc_html_e( 'Всього лідів', 'medici.agency' ); ?></span>
					</div>
				</div>
				<div class="medici-card">
					<div class="medici-card-icon">🆕</div>
					<div class="medici-card-content">
						<span class="medici-card-number"><?php echo esc_html( (string) $stats['new'] ); ?></span>
						<span class="medici-card-label"><?php esc_html_e( 'Нових (потребують обробки)', 'medici.agency' ); ?></span>
					</div>
				</div>
				<div class="medici-card">
					<div class="medici-card-icon">✅</div>
					<div class="medici-card-content">
						<span class="medici-card-number"><?php echo esc_html( (string) $stats['qualified'] ); ?></span>
						<span class="medici-card-label"><?php esc_html_e( 'Кваліфікованих', 'medici.agency' ); ?></span>
					</div>
				</div>
				<div class="medici-card">
					<div class="medici-card-icon">🎉</div>
					<div class="medici-card-content">
						<span class="medici-card-number"><?php echo esc_html( (string) $stats['closed'] ); ?></span>
						<span class="medici-card-label"><?php esc_html_e( 'Закритих угод', 'medici.agency' ); ?></span>
					</div>
				</div>
				<div class="medici-card">
					<div class="medici-card-icon">📈</div>
					<div class="medici-card-content">
						<?php
						$conversion_rate = $stats['total'] > 0 ? round( ( $stats['closed'] / $stats['total'] ) * 100, 1 ) : 0;
						?>
						<span class="medici-card-number"><?php echo esc_html( (string) $conversion_rate ); ?>%</span>
						<span class="medici-card-label"><?php esc_html_e( 'Конверсія', 'medici.agency' ); ?></span>
					</div>
				</div>
			</div>

			<div class="medici-analytics-row">
				<!-- Conversion Funnel -->
				<div class="medici-analytics-section">
					<h2><?php esc_html_e( '🎯 Воронка конверсії', 'medici.agency' ); ?></h2>
					<div class="medici-funnel">
						<?php
						$max = max( array_values( $conversion_funnel ) ) ?: 1;
						foreach ( $conversion_funnel as $stage => $count ) :
							$width = round( ( $count / $max ) * 100 );
							$label = $this->get_status_label( $stage );
							?>
							<div class="medici-funnel-stage">
								<div class="medici-funnel-bar" style="width: <?php echo esc_attr( (string) $width ); ?>%;">
									<span class="medici-funnel-label"><?php echo esc_html( $label ); ?></span>
									<span class="medici-funnel-count"><?php echo esc_html( (string) $count ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Leads by Service -->
				<div class="medici-analytics-section">
					<h2><?php esc_html_e( '📋 Ліди за послугами', 'medici.agency' ); ?></h2>
					<?php if ( ! empty( $by_service ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Послуга', 'medici.agency' ); ?></th>
									<th><?php esc_html_e( 'Кількість', 'medici.agency' ); ?></th>
									<th><?php esc_html_e( '%', 'medici.agency' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $by_service as $service => $count ) : ?>
									<tr>
										<td><?php echo esc_html( $service ?: __( 'Не вказано', 'medici.agency' ) ); ?></td>
										<td><?php echo esc_html( (string) $count ); ?></td>
										<td><?php echo esc_html( (string) round( ( $count / $stats['total'] ) * 100, 1 ) ); ?>%</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'Дані відсутні.', 'medici.agency' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="medici-analytics-row">
				<!-- Leads by UTM Source -->
				<div class="medici-analytics-section">
					<h2><?php esc_html_e( '📊 Ліди за джерелами (UTM)', 'medici.agency' ); ?></h2>
					<?php if ( ! empty( $by_source ) ) : ?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Джерело', 'medici.agency' ); ?></th>
									<th><?php esc_html_e( 'Кількість', 'medici.agency' ); ?></th>
									<th><?php esc_html_e( '%', 'medici.agency' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $by_source as $source => $count ) : ?>
									<tr>
										<td><?php echo esc_html( $source ?: __( 'Прямий трафік', 'medici.agency' ) ); ?></td>
										<td><?php echo esc_html( (string) $count ); ?></td>
										<td><?php echo esc_html( (string) round( ( $count / $stats['total'] ) * 100, 1 ) ); ?>%</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p><?php esc_html_e( 'Дані відсутні.', 'medici.agency' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Leads Timeline -->
				<div class="medici-analytics-section">
					<h2><?php esc_html_e( '📅 Ліди за останні 30 днів', 'medici.agency' ); ?></h2>
					<div class="medici-timeline-chart">
						<?php
						$max_day = max( array_values( $by_date ) ) ?: 1;
						foreach ( $by_date as $date => $count ) :
							$height = round( ( $count / $max_day ) * 100 );
							?>
							<div class="medici-timeline-bar" title="<?php echo esc_attr( $date . ': ' . $count ); ?>">
								<div class="medici-timeline-fill" style="height: <?php echo esc_attr( (string) $height ); ?>%;"></div>
								<span class="medici-timeline-count"><?php echo esc_html( (string) $count ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render SEO Audit page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_seo_audit_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'medici_blog',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$audit_results = array();
		$total_score   = 0;

		foreach ( $posts as $post ) {
			$audit           = $this->perform_seo_audit( $post->ID );
			$audit_results[] = $audit;
			$total_score    += $audit['score'];
		}

		$avg_score = count( $posts ) > 0 ? (int) round( $total_score / count( $posts ) ) : 0;

		?>
		<div class="wrap medici-seo-audit-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Summary -->
			<div class="medici-seo-summary">
				<div class="medici-seo-score-circle medici-seo-<?php echo esc_attr( $this->get_score_level( $avg_score ) ); ?>">
					<span class="medici-score-number"><?php echo esc_html( (string) $avg_score ); ?></span>
					<span class="medici-score-label"><?php esc_html_e( 'Середній SEO бал', 'medici.agency' ); ?></span>
				</div>
				<div class="medici-seo-stats">
					<p>
						<?php
						printf(
							/* translators: %d: number of posts */
							esc_html__( 'Проаналізовано %d статей', 'medici.agency' ),
							count( $posts )
						);
						?>
					</p>
					<ul>
						<li><span class="medici-seo-badge medici-seo-good"><?php esc_html_e( 'Добре', 'medici.agency' ); ?></span> — 80-100%</li>
						<li><span class="medici-seo-badge medici-seo-warning"><?php esc_html_e( 'Увага', 'medici.agency' ); ?></span> — 50-79%</li>
						<li><span class="medici-seo-badge medici-seo-bad"><?php esc_html_e( 'Погано', 'medici.agency' ); ?></span> — 0-49%</li>
					</ul>
				</div>
			</div>

			<!-- Audit Results Table -->
			<table class="widefat striped medici-seo-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Стаття', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'SEO Бал', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Title', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Meta Desc', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Зображення', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Контент', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Рекомендації', 'medici.agency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $audit_results as $audit ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $audit['post_id'] ) ); ?>">
									<?php echo esc_html( wp_trim_words( $audit['title'], 6 ) ); ?>
								</a>
							</td>
							<td>
								<span class="medici-seo-badge medici-seo-<?php echo esc_attr( $audit['level'] ); ?>">
									<?php echo esc_html( $audit['score'] . '%' ); ?>
								</span>
							</td>
							<td><?php echo $audit['checks']['title'] ? '✅' : '❌'; ?></td>
							<td><?php echo $audit['checks']['meta_description'] ? '✅' : '❌'; ?></td>
							<td><?php echo $audit['checks']['featured_image'] ? '✅' : '❌'; ?></td>
							<td><?php echo $audit['checks']['content_length'] ? '✅' : '❌'; ?></td>
							<td>
								<?php if ( ! empty( $audit['recommendations'] ) ) : ?>
									<ul class="medici-recommendations">
										<?php foreach ( array_slice( $audit['recommendations'], 0, 3 ) as $rec ) : ?>
											<li><?php echo esc_html( $rec ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<span class="medici-seo-ok"><?php esc_html_e( 'Все добре!', 'medici.agency' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Register bulk actions for leads
	 *
	 * @since 1.5.0
	 * @param array<string, string> $actions Existing actions
	 * @return array<string, string> Modified actions
	 */
	public function register_bulk_actions( array $actions ): array {
		$actions['mark_contacted'] = __( '📞 Позначити як "Зв\'язались"', 'medici.agency' );
		$actions['mark_qualified'] = __( '✅ Позначити як "Кваліфікований"', 'medici.agency' );
		$actions['mark_closed']    = __( '🎉 Позначити як "Закритий"', 'medici.agency' );
		$actions['mark_lost']      = __( '❌ Позначити як "Втрачений"', 'medici.agency' );
		$actions['export_csv']     = __( '📥 Експорт в CSV', 'medici.agency' );

		return $actions;
	}

	/**
	 * Handle bulk actions
	 *
	 * @since 1.5.0
	 * @param string $redirect_url Redirect URL
	 * @param string $action       Action name
	 * @param array  $post_ids     Selected post IDs
	 * @return string Modified redirect URL
	 */
	public function handle_bulk_actions( string $redirect_url, string $action, array $post_ids ): string {
		$status_actions = array(
			'mark_contacted' => 'contacted',
			'mark_qualified' => 'qualified',
			'mark_closed'    => 'closed',
			'mark_lost'      => 'lost',
		);

		if ( isset( $status_actions[ $action ] ) ) {
			$new_status = $status_actions[ $action ];
			$count      = 0;

			foreach ( $post_ids as $post_id ) {
				update_post_meta( (int) $post_id, '_medici_lead_status', $new_status );
				++$count;
			}

			$redirect_url = add_query_arg( 'medici_bulk_updated', $count, $redirect_url );
		}

		if ( 'export_csv' === $action ) {
			$this->export_leads_csv( $post_ids );
		}

		return $redirect_url;
	}

	/**
	 * Display bulk action notices
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function bulk_action_notices(): void {
		if ( ! empty( $_REQUEST['medici_bulk_updated'] ) ) {
			$count = (int) $_REQUEST['medici_bulk_updated'];
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d: number of leads updated */
						_n( '%d лід оновлено.', '%d лідів оновлено.', $count, 'medici.agency' ),
						$count
					)
				)
			);
		}
	}

	/**
	 * Export leads to CSV
	 *
	 * @since 1.5.0
	 * @param array $post_ids Lead post IDs
	 * @return void
	 */
	private function export_leads_csv( array $post_ids ): void {
		$filename = 'medici-leads-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Add BOM for Excel
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Headers
		fputcsv(
			$output,
			array(
				'ID',
				'Дата',
				"Ім'я",
				'Email',
				'Телефон',
				'Послуга',
				'Повідомлення',
				'Статус',
				'UTM Source',
				'UTM Medium',
				'UTM Campaign',
				'Сторінка',
			)
		);

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			fputcsv(
				$output,
				array(
					$post_id,
					get_the_date( 'Y-m-d H:i:s', $post_id ),
					get_post_meta( $post_id, '_medici_lead_name', true ),
					get_post_meta( $post_id, '_medici_lead_email', true ),
					get_post_meta( $post_id, '_medici_lead_phone', true ),
					get_post_meta( $post_id, '_medici_lead_service', true ),
					get_post_meta( $post_id, '_medici_lead_message', true ),
					get_post_meta( $post_id, '_medici_lead_status', true ),
					get_post_meta( $post_id, '_medici_lead_utm_source', true ),
					get_post_meta( $post_id, '_medici_lead_utm_medium', true ),
					get_post_meta( $post_id, '_medici_lead_utm_campaign', true ),
					get_post_meta( $post_id, '_medici_lead_page_url', true ),
				)
			);
		}

		fclose( $output );
		exit;
	}

	/**
	 * Add SEO column to blog posts
	 *
	 * @since 1.5.0
	 * @param array<string, string> $columns Existing columns
	 * @return array<string, string> Modified columns
	 */
	public function add_seo_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'title' === $key ) {
				$new_columns['seo_score'] = __( 'SEO', 'medici.agency' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render SEO column content
	 *
	 * @since 1.5.0
	 * @param string $column  Column name
	 * @param int    $post_id Post ID
	 * @return void
	 */
	public function render_seo_column( string $column, int $post_id ): void {
		if ( 'seo_score' === $column ) {
			$score = $this->calculate_seo_score( $post_id );
			echo '<span class="medici-seo-badge medici-seo-' . esc_attr( $score['level'] ) . '">' .
				esc_html( $score['score'] . '%' ) . '</span>';
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.5.0
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		$allowed_pages = array(
			'index.php',
			'medici_lead_page_medici-lead-analytics',
			'medici_blog_page_medici-seo-audit',
			'edit.php',
		);

		$is_allowed = in_array( $hook, $allowed_pages, true );

		// Also check for our custom pages
		if ( isset( $_GET['page'] ) ) {
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			if ( in_array( $page, array( 'medici-lead-analytics', 'medici-seo-audit' ), true ) ) {
				$is_allowed = true;
			}
		}

		// Check for post type pages
		if ( isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) );
			if ( in_array( $post_type, array( 'medici_lead', 'medici_blog' ), true ) ) {
				$is_allowed = true;
			}
		}

		if ( ! $is_allowed ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', $this->get_admin_css() );
	}

	/**
	 * Get admin CSS
	 *
	 * @since 1.5.0
	 * @return string CSS
	 */
	private function get_admin_css(): string {
		return '
/* Dashboard Widgets */
.medici-dashboard-widget { padding: 0; }
.medici-stats-grid {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 12px;
	margin-bottom: 20px;
}
.medici-stat-card {
	background: #f0f0f1;
	padding: 15px;
	border-radius: 8px;
	text-align: center;
}
.medici-stat-number {
	display: block;
	font-size: 28px;
	font-weight: 700;
	color: #1d2327;
}
.medici-stat-label {
	display: block;
	font-size: 12px;
	color: #50575e;
	margin-top: 4px;
}
.medici-stat-new { background: #e7f5ff; }
.medici-stat-new .medici-stat-number { color: #0073aa; }
.medici-stat-qualified { background: #e6ffed; }
.medici-stat-qualified .medici-stat-number { color: #00a32a; }
.medici-stat-closed { background: #fff8e5; }
.medici-stat-closed .medici-stat-number { color: #996800; }
.medici-widget-footer {
	margin-top: 15px;
	padding-top: 15px;
	border-top: 1px solid #ddd;
}
.medici-widget-footer .button { margin-right: 8px; }

/* SEO Badges */
.medici-seo-badge {
	display: inline-block;
	padding: 3px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
}
.medici-seo-good { background: #d4edda; color: #155724; }
.medici-seo-warning { background: #fff3cd; color: #856404; }
.medici-seo-bad { background: #f8d7da; color: #721c24; }

/* Analytics Page */
.medici-analytics-wrap { max-width: 1400px; }
.medici-analytics-cards {
	display: grid;
	grid-template-columns: repeat(5, 1fr);
	gap: 16px;
	margin: 20px 0 30px;
}
.medici-card {
	background: #fff;
	padding: 20px;
	border-radius: 8px;
	border: 1px solid #ddd;
	display: flex;
	align-items: center;
	gap: 15px;
}
.medici-card-icon { font-size: 32px; }
.medici-card-number {
	display: block;
	font-size: 32px;
	font-weight: 700;
	color: #1d2327;
}
.medici-card-label {
	display: block;
	font-size: 13px;
	color: #50575e;
}
.medici-analytics-row {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
	margin-bottom: 20px;
}
.medici-analytics-section {
	background: #fff;
	padding: 20px;
	border-radius: 8px;
	border: 1px solid #ddd;
}
.medici-analytics-section h2 {
	margin: 0 0 15px;
	font-size: 16px;
}

/* Conversion Funnel */
.medici-funnel { display: flex; flex-direction: column; gap: 8px; }
.medici-funnel-stage { width: 100%; }
.medici-funnel-bar {
	background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
	padding: 12px 15px;
	border-radius: 6px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	min-width: 120px;
	transition: width 0.3s ease;
}
.medici-funnel-label { color: #fff; font-weight: 500; }
.medici-funnel-count { color: #fff; font-weight: 700; }

/* Timeline Chart */
.medici-timeline-chart {
	display: flex;
	align-items: flex-end;
	height: 150px;
	gap: 4px;
	padding: 10px 0;
}
.medici-timeline-bar {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	height: 100%;
}
.medici-timeline-fill {
	width: 100%;
	background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
	border-radius: 4px 4px 0 0;
	min-height: 4px;
	margin-top: auto;
}
.medici-timeline-count {
	font-size: 10px;
	color: #666;
	margin-top: 4px;
}

/* SEO Audit Page */
.medici-seo-audit-wrap { max-width: 1400px; }
.medici-seo-summary {
	display: flex;
	gap: 30px;
	align-items: center;
	background: #fff;
	padding: 30px;
	border-radius: 8px;
	border: 1px solid #ddd;
	margin: 20px 0;
}
.medici-seo-score-circle {
	width: 120px;
	height: 120px;
	border-radius: 50%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	text-align: center;
}
.medici-seo-score-circle.medici-seo-good { background: #d4edda; }
.medici-seo-score-circle.medici-seo-warning { background: #fff3cd; }
.medici-seo-score-circle.medici-seo-bad { background: #f8d7da; }
.medici-score-number { font-size: 36px; font-weight: 700; }
.medici-score-label { font-size: 12px; color: #666; }
.medici-seo-stats ul {
	display: flex;
	gap: 20px;
	list-style: none;
	padding: 0;
	margin: 10px 0 0;
}
.medici-seo-table { margin-top: 20px; }
.medici-recommendations {
	margin: 0;
	padding-left: 18px;
	font-size: 12px;
	color: #666;
}
.medici-recommendations li { margin-bottom: 4px; }
.medici-seo-ok { color: #00a32a; font-weight: 500; }

@media (max-width: 1200px) {
	.medici-analytics-cards { grid-template-columns: repeat(3, 1fr); }
	.medici-analytics-row { grid-template-columns: 1fr; }
}
@media (max-width: 782px) {
	.medici-stats-grid { grid-template-columns: repeat(2, 1fr); }
	.medici-analytics-cards { grid-template-columns: 1fr 1fr; }
}
		';
	}

	// ========================================================================
	// DATA RETRIEVAL METHODS
	// ========================================================================

	/**
	 * Get lead statistics
	 *
	 * @since 1.5.0
	 * @return array<string, int> Stats array
	 */
	private function get_lead_stats(): array {
		$statuses = array( 'new', 'contacted', 'qualified', 'closed', 'lost' );
		$stats    = array(
			'total'     => 0,
			'new'       => 0,
			'contacted' => 0,
			'qualified' => 0,
			'closed'    => 0,
			'lost'      => 0,
		);

		$leads = get_posts(
			array(
				'post_type'      => 'medici_lead',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$stats['total'] = count( $leads );

		foreach ( $leads as $lead_id ) {
			$status = get_post_meta( $lead_id, '_medici_lead_status', true ) ?: 'new';
			if ( isset( $stats[ $status ] ) ) {
				++$stats[ $status ];
			}
		}

		return $stats;
	}

	/**
	 * Get recent leads
	 *
	 * @since 1.5.0
	 * @param int $limit Number of leads to retrieve
	 * @return array<\WP_Post> Recent leads
	 */
	private function get_recent_leads( int $limit = 5 ): array {
		return get_posts(
			array(
				'post_type'      => 'medici_lead',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
	}

	/**
	 * Get leads grouped by service
	 *
	 * @since 1.5.0
	 * @return array<string, int> Service => count
	 */
	private function get_leads_by_service(): array {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT meta_value as service, COUNT(*) as count
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_medici_lead_service'
			AND p.post_type = 'medici_lead'
			AND p.post_status = 'publish'
			GROUP BY meta_value
			ORDER BY count DESC",
			ARRAY_A
		);

		$by_service = array();
		foreach ( $results as $row ) {
			$by_service[ $row['service'] ?: '' ] = (int) $row['count'];
		}

		return $by_service;
	}

	/**
	 * Get leads grouped by UTM source
	 *
	 * @since 1.5.0
	 * @return array<string, int> Source => count
	 */
	private function get_leads_by_utm_source(): array {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT meta_value as source, COUNT(*) as count
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_medici_lead_utm_source'
			AND p.post_type = 'medici_lead'
			AND p.post_status = 'publish'
			GROUP BY meta_value
			ORDER BY count DESC",
			ARRAY_A
		);

		$by_source = array();
		foreach ( $results as $row ) {
			$by_source[ $row['source'] ?: '' ] = (int) $row['count'];
		}

		// Add direct traffic (no UTM source)
		$total       = $this->get_lead_stats()['total'];
		$with_source = array_sum( $by_source );
		if ( $total > $with_source ) {
			$by_source[''] = $total - $with_source;
		}

		return $by_source;
	}

	/**
	 * Get leads by date for the last N days
	 *
	 * @since 1.5.0
	 * @param int $days Number of days
	 * @return array<string, int> Date => count
	 */
	private function get_leads_by_date( int $days = 30 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(post_date) as date, COUNT(*) as count
				FROM {$wpdb->posts}
				WHERE post_type = 'medici_lead'
				AND post_status = 'publish'
				AND post_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				GROUP BY DATE(post_date)
				ORDER BY date ASC",
				$days
			),
			ARRAY_A
		);

		// Fill in missing dates with 0
		$by_date = array();
		$current = new \DateTime( "-{$days} days" );
		$end     = new \DateTime();

		while ( $current <= $end ) {
			$by_date[ $current->format( 'Y-m-d' ) ] = 0;
			$current->modify( '+1 day' );
		}

		foreach ( $results as $row ) {
			$by_date[ $row['date'] ] = (int) $row['count'];
		}

		return $by_date;
	}

	/**
	 * Get conversion funnel data
	 *
	 * @since 1.5.0
	 * @return array<string, int> Stage => count
	 */
	private function get_conversion_funnel(): array {
		$stats = $this->get_lead_stats();

		return array(
			'new'       => $stats['new'] + $stats['contacted'] + $stats['qualified'] + $stats['closed'],
			'contacted' => $stats['contacted'] + $stats['qualified'] + $stats['closed'],
			'qualified' => $stats['qualified'] + $stats['closed'],
			'closed'    => $stats['closed'],
		);
	}

	/**
	 * Get status label
	 *
	 * @since 1.5.0
	 * @param string $status Status key
	 * @return string Status label
	 */
	private function get_status_label( string $status ): string {
		$labels = array(
			'new'       => __( '🆕 Новий', 'medici.agency' ),
			'contacted' => __( '📞 Зв\'язались', 'medici.agency' ),
			'qualified' => __( '✅ Кваліфікований', 'medici.agency' ),
			'closed'    => __( '🎉 Закритий', 'medici.agency' ),
			'lost'      => __( '❌ Втрачений', 'medici.agency' ),
		);

		return $labels[ $status ] ?? $status;
	}

	/**
	 * Get blog statistics
	 *
	 * @since 1.5.0
	 * @return array<string, mixed> Stats array
	 */
	private function get_blog_stats(): array {
		global $wpdb;

		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'medici_blog' AND post_status = 'publish'"
		);

		$total_views = (int) $wpdb->get_var(
			"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_medici_post_views'
			AND p.post_type = 'medici_blog'
			AND p.post_status = 'publish'"
		);

		$featured = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_medici_featured_article'
			AND pm.meta_value = '1'
			AND p.post_type = 'medici_blog'
			AND p.post_status = 'publish'"
		);

		$avg_reading_time = (float) $wpdb->get_var(
			"SELECT AVG(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_medici_reading_time'
			AND p.post_type = 'medici_blog'
			AND p.post_status = 'publish'"
		);

		return array(
			'total'            => $total,
			'total_views'      => $total_views ?: 0,
			'featured'         => $featured,
			'avg_reading_time' => $avg_reading_time ?: 0,
		);
	}

	/**
	 * Get popular posts
	 *
	 * @since 1.5.0
	 * @param int $limit Number of posts
	 * @return array<\WP_Post> Popular posts
	 */
	private function get_popular_posts( int $limit = 5 ): array {
		return get_posts(
			array(
				'post_type'      => 'medici_blog',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_key'       => '_medici_post_views',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);
	}

	// ========================================================================
	// SEO AUDIT METHODS
	// ========================================================================

	/**
	 * Calculate SEO score for a post
	 *
	 * @since 1.5.0
	 * @param int $post_id Post ID
	 * @return array{score: int, level: string} Score and level
	 */
	private function calculate_seo_score( int $post_id ): array {
		$audit = $this->perform_seo_audit( $post_id );
		return array(
			'score' => $audit['score'],
			'level' => $audit['level'],
		);
	}

	/**
	 * Perform full SEO audit for a post
	 *
	 * @since 1.5.0
	 * @param int $post_id Post ID
	 * @return array Audit results
	 */
	private function perform_seo_audit( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'post_id'         => $post_id,
				'title'           => '',
				'score'           => 0,
				'level'           => 'bad',
				'checks'          => array(),
				'recommendations' => array(),
			);
		}

		$checks          = array();
		$recommendations = array();
		$score           = 0;
		$max_score       = 0;

		// 1. Title check (15 points)
		$max_score   += 15;
		$title_length = mb_strlen( $post->post_title );
		if ( $title_length >= 30 && $title_length <= 60 ) {
			$checks['title'] = true;
			$score          += 15;
		} else {
			$checks['title'] = false;
			if ( $title_length < 30 ) {
				$recommendations[] = __( 'Заголовок занадто короткий (мін. 30 символів)', 'medici.agency' );
			} else {
				$recommendations[] = __( 'Заголовок занадто довгий (макс. 60 символів)', 'medici.agency' );
			}
		}

		// 2. Meta description check (15 points) - Using Yoast/custom meta
		$max_score       += 15;
		$meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( ! $meta_description ) {
			$meta_description = get_post_meta( $post_id, '_medici_meta_description', true );
		}
		$meta_length = mb_strlen( $meta_description ?: '' );
		if ( $meta_length >= 120 && $meta_length <= 160 ) {
			$checks['meta_description'] = true;
			$score                     += 15;
		} else {
			$checks['meta_description'] = false;
			if ( $meta_length === 0 ) {
				$recommendations[] = __( 'Додайте мета-опис (120-160 символів)', 'medici.agency' );
			} elseif ( $meta_length < 120 ) {
				$recommendations[] = __( 'Мета-опис занадто короткий', 'medici.agency' );
			} else {
				$recommendations[] = __( 'Мета-опис занадто довгий', 'medici.agency' );
			}
		}

		// 3. Featured image check (15 points)
		$max_score += 15;
		if ( has_post_thumbnail( $post_id ) ) {
			$checks['featured_image'] = true;
			$score                   += 15;
		} else {
			$checks['featured_image'] = false;
			$recommendations[]        = __( 'Додайте зображення статті', 'medici.agency' );
		}

		// 4. Content length check (20 points)
		$max_score += 20;
		$content    = wp_strip_all_tags( $post->post_content );
		$word_count = str_word_count( $content );
		if ( $word_count >= 800 ) {
			$checks['content_length'] = true;
			$score                   += 20;
		} else {
			$checks['content_length'] = false;
			$recommendations[]        = sprintf(
				/* translators: %d: current word count */
				__( 'Контент занадто короткий (%d слів, рекомендовано 800+)', 'medici.agency' ),
				$word_count
			);
		}

		// 5. Headings check (15 points)
		$max_score += 15;
		$has_h2     = preg_match( '/<h2/i', $post->post_content );
		if ( $has_h2 ) {
			$checks['headings'] = true;
			$score             += 15;
		} else {
			$checks['headings'] = false;
			$recommendations[]  = __( 'Додайте підзаголовки H2 для структурування контенту', 'medici.agency' );
		}

		// 6. Internal links check (10 points)
		$max_score     += 10;
		$internal_links = preg_match_all( '/href=["\']' . preg_quote( home_url(), '/' ) . '/i', $post->post_content );
		if ( $internal_links >= 2 ) {
			$checks['internal_links'] = true;
			$score                   += 10;
		} else {
			$checks['internal_links'] = false;
			$recommendations[]        = __( 'Додайте більше внутрішніх посилань (мін. 2)', 'medici.agency' );
		}

		// 7. Images alt text check (10 points)
		$max_score += 10;
		$images     = preg_match_all( '/<img[^>]+>/i', $post->post_content, $img_matches );
		$images_alt = preg_match_all( '/<img[^>]+alt=["\'][^"\']+["\']/i', $post->post_content );
		if ( $images === 0 || $images_alt === $images ) {
			$checks['images_alt'] = true;
			$score               += 10;
		} else {
			$checks['images_alt'] = false;
			$recommendations[]    = __( 'Додайте alt-текст до всіх зображень', 'medici.agency' );
		}

		// Calculate percentage (max_score is always > 0 after all checks)
		$percentage = (int) round( ( $score / $max_score ) * 100 );

		return array(
			'post_id'         => $post_id,
			'title'           => $post->post_title,
			'score'           => $percentage,
			'level'           => $this->get_score_level( $percentage ),
			'checks'          => $checks,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Get score level based on percentage
	 *
	 * @since 1.5.0
	 * @param int $score Score percentage
	 * @return string Level (good/warning/bad)
	 */
	private function get_score_level( int $score ): string {
		if ( $score >= 80 ) {
			return 'good';
		}
		if ( $score >= 50 ) {
			return 'warning';
		}
		return 'bad';
	}
}

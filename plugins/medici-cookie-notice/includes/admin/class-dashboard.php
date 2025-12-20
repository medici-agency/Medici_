<?php
/**
 * Admin Dashboard with Analytics Visualization
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Admin;

use Medici\CookieNotice\Cookie_Notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Class
 *
 * Renders the admin dashboard with charts and statistics.
 */
class Dashboard {

	/**
	 * Plugin instance
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Constructor
	 *
	 * @param Cookie_Notice $plugin Plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;

		// Register AJAX handlers
		add_action( 'wp_ajax_mcn_get_dashboard_data', [ $this, 'ajax_get_dashboard_data' ] );
		add_action( 'wp_ajax_mcn_export_analytics', [ $this, 'ajax_export_analytics' ] );
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render(): void {
		$analytics = $this->plugin->analytics;

		if ( null === $analytics ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Аналітика не увімкнена.', 'medici-cookie-notice' ) . '</p></div>';
			return;
		}

		$stats       = $analytics->get_stats( 30 );
		$rates       = $analytics->get_acceptance_rates( 30 );
		$comparison  = $analytics->get_period_comparison( 30 );
		$cat_rates   = $analytics->get_category_rates( 30 );
		$geo_stats   = $analytics->get_geo_stats( 30 );
		?>
		<div class="wrap mcn-dashboard">
			<h1><?php esc_html_e( 'Панель управління Cookie Notice', 'medici-cookie-notice' ); ?></h1>

			<!-- Period Selector -->
			<div class="mcn-period-selector">
				<label for="mcn-period"><?php esc_html_e( 'Період:', 'medici-cookie-notice' ); ?></label>
				<select id="mcn-period" class="mcn-period-select">
					<option value="7"><?php esc_html_e( 'Останні 7 днів', 'medici-cookie-notice' ); ?></option>
					<option value="30" selected><?php esc_html_e( 'Останні 30 днів', 'medici-cookie-notice' ); ?></option>
					<option value="90"><?php esc_html_e( 'Останні 90 днів', 'medici-cookie-notice' ); ?></option>
					<option value="365"><?php esc_html_e( 'Останній рік', 'medici-cookie-notice' ); ?></option>
				</select>
				<button type="button" id="mcn-export-csv" class="button">
					<?php esc_html_e( 'Експорт CSV', 'medici-cookie-notice' ); ?>
				</button>
			</div>

			<!-- Stats Cards -->
			<div class="mcn-stats-cards">
				<div class="mcn-stat-card mcn-stat-total">
					<div class="mcn-stat-icon">👥</div>
					<div class="mcn-stat-content">
						<span class="mcn-stat-value" id="stat-total"><?php echo esc_html( number_format_i18n( $stats['total_visitors'] ) ); ?></span>
						<span class="mcn-stat-label"><?php esc_html_e( 'Всього відвідувачів', 'medici-cookie-notice' ); ?></span>
						<?php $this->render_change_badge( $comparison['total_visitors']['change'] ?? 0 ); ?>
					</div>
				</div>

				<div class="mcn-stat-card mcn-stat-accepted">
					<div class="mcn-stat-icon">✅</div>
					<div class="mcn-stat-content">
						<span class="mcn-stat-value" id="stat-accepted"><?php echo esc_html( number_format_i18n( $stats['accepted_all'] ) ); ?></span>
						<span class="mcn-stat-label"><?php esc_html_e( 'Прийнято все', 'medici-cookie-notice' ); ?></span>
						<span class="mcn-stat-rate"><?php echo esc_html( $rates['accept_rate'] ); ?>%</span>
					</div>
				</div>

				<div class="mcn-stat-card mcn-stat-rejected">
					<div class="mcn-stat-icon">❌</div>
					<div class="mcn-stat-content">
						<span class="mcn-stat-value" id="stat-rejected"><?php echo esc_html( number_format_i18n( $stats['rejected_all'] ) ); ?></span>
						<span class="mcn-stat-label"><?php esc_html_e( 'Відхилено все', 'medici-cookie-notice' ); ?></span>
						<span class="mcn-stat-rate"><?php echo esc_html( $rates['reject_rate'] ); ?>%</span>
					</div>
				</div>

				<div class="mcn-stat-card mcn-stat-custom">
					<div class="mcn-stat-icon">⚙️</div>
					<div class="mcn-stat-content">
						<span class="mcn-stat-value" id="stat-custom"><?php echo esc_html( number_format_i18n( $stats['customized'] ) ); ?></span>
						<span class="mcn-stat-label"><?php esc_html_e( 'Вибіркова згода', 'medici-cookie-notice' ); ?></span>
						<span class="mcn-stat-rate"><?php echo esc_html( $rates['custom_rate'] ); ?>%</span>
					</div>
				</div>

				<div class="mcn-stat-card mcn-stat-overall">
					<div class="mcn-stat-icon">📈</div>
					<div class="mcn-stat-content">
						<span class="mcn-stat-value mcn-stat-highlight" id="stat-overall"><?php echo esc_html( $rates['overall_rate'] ); ?>%</span>
						<span class="mcn-stat-label"><?php esc_html_e( 'Загальний рівень згоди', 'medici-cookie-notice' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Charts Row -->
			<div class="mcn-charts-row">
				<!-- Consent Activity Chart -->
				<div class="mcn-chart-card mcn-chart-wide">
					<h3><?php esc_html_e( 'Активність згод', 'medici-cookie-notice' ); ?></h3>
					<div class="mcn-chart-container">
						<canvas id="mcn-consent-activity-chart"></canvas>
					</div>
				</div>
			</div>

			<div class="mcn-charts-row mcn-charts-row-2">
				<!-- Category Rates Chart -->
				<div class="mcn-chart-card">
					<h3><?php esc_html_e( 'Прийняття по категоріях', 'medici-cookie-notice' ); ?></h3>
					<div class="mcn-chart-container mcn-chart-doughnut">
						<canvas id="mcn-category-chart"></canvas>
					</div>
					<div class="mcn-chart-legend" id="mcn-category-legend">
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #10b981;"></span>
							<span><?php esc_html_e( 'Аналітика', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $cat_rates['analytics'] ); ?>%</span>
						</div>
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #f59e0b;"></span>
							<span><?php esc_html_e( 'Маркетинг', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $cat_rates['marketing'] ); ?>%</span>
						</div>
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #3b82f6;"></span>
							<span><?php esc_html_e( 'Вподобання', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $cat_rates['preferences'] ); ?>%</span>
						</div>
					</div>
				</div>

				<!-- Geo Distribution Chart -->
				<div class="mcn-chart-card">
					<h3><?php esc_html_e( 'Географія', 'medici-cookie-notice' ); ?></h3>
					<div class="mcn-chart-container mcn-chart-doughnut">
						<canvas id="mcn-geo-chart"></canvas>
					</div>
					<div class="mcn-chart-legend" id="mcn-geo-legend">
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #3b82f6;"></span>
							<span>🇪🇺 <?php esc_html_e( 'ЄС (GDPR)', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $geo_stats['eu']['percentage'] ); ?>%</span>
						</div>
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #ef4444;"></span>
							<span>🇺🇸 <?php esc_html_e( 'США', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $geo_stats['us']['percentage'] ); ?>%</span>
						</div>
						<div class="mcn-legend-item">
							<span class="mcn-legend-color" style="background: #6b7280;"></span>
							<span>🌍 <?php esc_html_e( 'Інші', 'medici-cookie-notice' ); ?>: <?php echo esc_html( $geo_stats['other']['percentage'] ); ?>%</span>
						</div>
					</div>
				</div>

				<!-- Consent Status Distribution -->
				<div class="mcn-chart-card">
					<h3><?php esc_html_e( 'Розподіл статусів', 'medici-cookie-notice' ); ?></h3>
					<div class="mcn-chart-container mcn-chart-doughnut">
						<canvas id="mcn-status-chart"></canvas>
					</div>
				</div>
			</div>

			<!-- Quick Actions -->
			<div class="mcn-quick-actions">
				<h3><?php esc_html_e( 'Швидкі дії', 'medici-cookie-notice' ); ?></h3>
				<div class="mcn-action-buttons">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=medici-cookie-notice' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Налаштування', 'medici-cookie-notice' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mcn-consent-logs' ) ); ?>" class="button">
						<?php esc_html_e( 'Журнал згод', 'medici-cookie-notice' ); ?>
					</a>
					<a href="#" id="mcn-clear-cache" class="button">
						<?php esc_html_e( 'Очистити кеш', 'medici-cookie-notice' ); ?>
					</a>
					<a href="#" id="mcn-test-banner" class="button" target="_blank">
						<?php esc_html_e( 'Тестувати банер', 'medici-cookie-notice' ); ?>
					</a>
				</div>
			</div>

			<?php
			/**
			 * Action after dashboard content
			 */
			do_action( 'mcn_admin_after_dashboard' );
			?>
		</div>
		<?php
	}

	/**
	 * Render change badge (positive/negative indicator)
	 *
	 * @param float $change Change percentage.
	 * @return void
	 */
	private function render_change_badge( float $change ): void {
		if ( 0.0 === $change ) {
			return;
		}

		$class = $change > 0 ? 'mcn-change-positive' : 'mcn-change-negative';
		$icon  = $change > 0 ? '↑' : '↓';

		printf(
			'<span class="mcn-stat-change %s">%s %s%%</span>',
			esc_attr( $class ),
			esc_html( $icon ),
			esc_html( abs( $change ) )
		);
	}

	/**
	 * AJAX: Get dashboard data for specific period
	 *
	 * @return void
	 */
	public function ajax_get_dashboard_data(): void {
		check_ajax_referer( 'mcn_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;
		$days = min( $days, 365 ); // Max 1 year

		$analytics = $this->plugin->analytics;

		if ( null === $analytics ) {
			wp_send_json_error( [ 'message' => 'Analytics not available' ] );
		}

		wp_send_json_success( [
			'daily_stats' => $analytics->get_daily_stats( $days ),
			'summary'     => $analytics->get_stats( $days ),
			'rates'       => $analytics->get_acceptance_rates( $days ),
			'cat_rates'   => $analytics->get_category_rates( $days ),
			'geo_stats'   => $analytics->get_geo_stats( $days ),
			'comparison'  => $analytics->get_period_comparison( $days ),
		] );
	}

	/**
	 * AJAX: Export analytics to CSV
	 *
	 * @return void
	 */
	public function ajax_export_analytics(): void {
		check_ajax_referer( 'mcn_dashboard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 30;

		$analytics = $this->plugin->analytics;

		if ( null === $analytics ) {
			wp_send_json_error( [ 'message' => 'Analytics not available' ] );
		}

		$csv = $analytics->export_csv( $days );

		wp_send_json_success( [
			'csv'      => $csv,
			'filename' => 'cookie-consent-analytics-' . gmdate( 'Y-m-d' ) . '.csv',
		] );
	}
}

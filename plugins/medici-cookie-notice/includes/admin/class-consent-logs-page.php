<?php
/**
 * Consent Logs Admin Page Controller
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
 * Consent Logs Page Class
 *
 * Handles the consent logs admin page.
 */
class Consent_Logs_Page {

	/**
	 * Plugin instance
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * List table instance
	 *
	 * @var Consent_Logs_List_Table|null
	 */
	private ?Consent_Logs_List_Table $list_table = null;

	/**
	 * Constructor
	 *
	 * @param Cookie_Notice $plugin Plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;

		// Register AJAX handlers
		add_action( 'wp_ajax_mcn_get_log_details', [ $this, 'ajax_get_log_details' ] );
		add_action( 'wp_ajax_mcn_delete_log', [ $this, 'ajax_delete_log' ] );
		add_action( 'wp_ajax_mcn_bulk_delete_logs', [ $this, 'ajax_bulk_delete_logs' ] );
	}

	/**
	 * Render the consent logs page
	 *
	 * @return void
	 */
	public function render(): void {
		// Handle actions
		$this->handle_actions();

		// Initialize list table
		if ( null === $this->list_table && null !== $this->plugin->consent_logs ) {
			$this->list_table = new Consent_Logs_List_Table( $this->plugin->consent_logs );
		}

		if ( null === $this->list_table ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Журнал згод не увімкнено.', 'medici-cookie-notice' ) . '</p></div>';
			return;
		}

		$this->list_table->prepare_items();
		?>
		<div class="wrap mcn-consent-logs">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Журнал згод', 'medici-cookie-notice' ); ?></h1>

			<?php
			// Stats summary
			$this->render_stats_summary();
			?>

			<form method="get" action="">
				<input type="hidden" name="page" value="mcn-consent-logs" />

				<?php
				$this->list_table->search_box( __( 'Пошук', 'medici-cookie-notice' ), 'mcn-search' );
				$this->list_table->display();
				?>
			</form>

			<!-- Log Details Modal -->
			<div id="mcn-log-details-modal" class="mcn-modal" style="display: none;">
				<div class="mcn-modal-content">
					<button type="button" class="mcn-modal-close">&times;</button>
					<h2><?php esc_html_e( 'Деталі згоди', 'medici-cookie-notice' ); ?></h2>
					<div id="mcn-log-details-content">
						<!-- Content loaded via AJAX -->
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render stats summary
	 *
	 * @return void
	 */
	private function render_stats_summary(): void {
		$consent_logs = $this->plugin->consent_logs;

		if ( null === $consent_logs ) {
			return;
		}

		$stats = $consent_logs->get_stats_by_status( 30 );
		$total = array_sum( $stats );

		if ( 0 === $total ) {
			return;
		}
		?>
		<div class="mcn-logs-summary">
			<span class="mcn-summary-item">
				<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
				<?php esc_html_e( 'записів за останні 30 днів', 'medici-cookie-notice' ); ?>
			</span>
			<span class="mcn-summary-sep">|</span>
			<span class="mcn-summary-item mcn-summary-accepted">
				✅ <?php echo esc_html( number_format_i18n( $stats['accepted'] ?? 0 ) ); ?>
				<?php esc_html_e( 'прийнято', 'medici-cookie-notice' ); ?>
			</span>
			<span class="mcn-summary-item mcn-summary-rejected">
				❌ <?php echo esc_html( number_format_i18n( $stats['rejected'] ?? 0 ) ); ?>
				<?php esc_html_e( 'відхилено', 'medici-cookie-notice' ); ?>
			</span>
			<span class="mcn-summary-item mcn-summary-custom">
				⚙️ <?php echo esc_html( number_format_i18n( $stats['custom'] ?? 0 ) ); ?>
				<?php esc_html_e( 'вибірково', 'medici-cookie-notice' ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Handle page actions (delete, export)
	 *
	 * @return void
	 */
	private function handle_actions(): void {
		// Export CSV
		if ( isset( $_GET['action'] ) && 'export_csv' === $_GET['action'] ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mcn_export_logs' ) ) {
				wp_die( esc_html__( 'Помилка безпеки.', 'medici-cookie-notice' ) );
			}

			$this->export_csv();
			exit;
		}

		// Single delete
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['log_id'] ) ) {
			$log_id = absint( $_GET['log_id'] );

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'mcn_delete_log_' . $log_id ) ) {
				wp_die( esc_html__( 'Помилка безпеки.', 'medici-cookie-notice' ) );
			}

			$this->delete_log( $log_id );

			wp_safe_redirect( admin_url( 'admin.php?page=mcn-consent-logs&deleted=1' ) );
			exit;
		}

		// Bulk actions
		if ( isset( $_POST['action'] ) && isset( $_POST['log_ids'] ) ) {
			$action  = sanitize_text_field( wp_unslash( $_POST['action'] ) );
			$log_ids = array_map( 'absint', (array) $_POST['log_ids'] );

			if ( 'delete' === $action ) {
				foreach ( $log_ids as $log_id ) {
					$this->delete_log( $log_id );
				}

				wp_safe_redirect( admin_url( 'admin.php?page=mcn-consent-logs&deleted=' . count( $log_ids ) ) );
				exit;
			}

			if ( 'export' === $action ) {
				$this->export_selected( $log_ids );
				exit;
			}
		}
	}

	/**
	 * Delete a single log entry
	 *
	 * @param int $log_id Log ID.
	 * @return bool
	 */
	private function delete_log( int $log_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'mcn_consent_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $table, [ 'id' => $log_id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Export all logs to CSV
	 *
	 * @return void
	 */
	private function export_csv(): void {
		$consent_logs = $this->plugin->consent_logs;

		if ( null === $consent_logs ) {
			wp_die( esc_html__( 'Журнал згод не доступний.', 'medici-cookie-notice' ) );
		}

		$filters = [
			'status'    => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'country'   => isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
		];

		$csv = $consent_logs->export_csv( $filters );

		// Send headers for download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-logs-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output BOM for Excel UTF-8 compatibility
		echo "\xEF\xBB\xBF";
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV content

		exit;
	}

	/**
	 * Export selected logs
	 *
	 * @param array<int, int> $log_ids Log IDs.
	 * @return void
	 */
	private function export_selected( array $log_ids ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'mcn_consent_logs';
		$ids   = implode( ',', array_map( 'absint', $log_ids ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$logs = $wpdb->get_results( "SELECT * FROM {$table} WHERE id IN ({$ids}) ORDER BY created_at DESC" );

		$csv = "Consent ID,Status,Categories,Country,Page URL,Created At\n";

		foreach ( $logs as $log ) {
			$categories = json_decode( $log->consent_categories, true );
			$cat_string = implode( '; ', array_keys( array_filter( $categories ?? [] ) ) );

			$csv .= sprintf(
				'"%s","%s","%s","%s","%s","%s"' . "\n",
				$log->consent_id,
				$log->consent_status,
				$cat_string,
				$log->geo_country ?? '',
				$log->page_url ?? '',
				$log->created_at
			);
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=consent-logs-selected-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo "\xEF\xBB\xBF";
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * AJAX: Get log details
	 *
	 * @return void
	 */
	public function ajax_get_log_details(): void {
		check_ajax_referer( 'mcn_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! $log_id ) {
			wp_send_json_error( [ 'message' => 'Invalid log ID' ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'mcn_consent_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$log = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $log_id )
		);

		if ( ! $log ) {
			wp_send_json_error( [ 'message' => 'Log not found' ] );
		}

		$categories = json_decode( $log->consent_categories, true );

		ob_start();
		?>
		<table class="mcn-details-table">
			<tr>
				<th><?php esc_html_e( 'Consent ID', 'medici-cookie-notice' ); ?></th>
				<td><code><?php echo esc_html( $log->consent_id ); ?></code></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Статус', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( ucfirst( $log->consent_status ) ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Категорії', 'medici-cookie-notice' ); ?></th>
				<td>
					<?php
					if ( is_array( $categories ) ) {
						foreach ( $categories as $cat => $enabled ) {
							$status = $enabled ? '✅' : '❌';
							echo esc_html( "{$status} " . ucfirst( $cat ) ) . '<br>';
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Країна', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( $log->geo_country ?? '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Регіон', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( $log->geo_region ?? '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Сторінка', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( $log->page_url ?? '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'User Agent', 'medici-cookie-notice' ); ?></th>
				<td><small><?php echo esc_html( $log->user_agent ?? '—' ); ?></small></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'IP адреса', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( $log->ip_address ?? '—' ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Дата', 'medici-cookie-notice' ); ?></th>
				<td><?php echo esc_html( $log->created_at ); ?></td>
			</tr>
		</table>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * AJAX: Delete log
	 *
	 * @return void
	 */
	public function ajax_delete_log(): void {
		check_ajax_referer( 'mcn_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! $log_id ) {
			wp_send_json_error( [ 'message' => 'Invalid log ID' ] );
		}

		if ( $this->delete_log( $log_id ) ) {
			wp_send_json_success( [ 'message' => __( 'Запис видалено.', 'medici-cookie-notice' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'medici-cookie-notice' ) ] );
		}
	}

	/**
	 * AJAX: Bulk delete logs
	 *
	 * @return void
	 */
	public function ajax_bulk_delete_logs(): void {
		check_ajax_referer( 'mcn_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$log_ids = isset( $_POST['log_ids'] ) ? array_map( 'absint', (array) $_POST['log_ids'] ) : [];

		if ( empty( $log_ids ) ) {
			wp_send_json_error( [ 'message' => 'No logs selected' ] );
		}

		$deleted = 0;
		foreach ( $log_ids as $log_id ) {
			if ( $this->delete_log( $log_id ) ) {
				++$deleted;
			}
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %d: number of deleted logs */
				__( 'Видалено %d записів.', 'medici-cookie-notice' ),
				$deleted
			),
			'deleted' => $deleted,
		] );
	}
}

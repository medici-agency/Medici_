<?php
/**
 * Consent Logs WP_List_Table
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Admin;

use Medici\CookieNotice\Consent_Logs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not already loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Consent Logs List Table
 *
 * Displays consent logs in a sortable, filterable table.
 */
class Consent_Logs_List_Table extends \WP_List_Table {

	/**
	 * Consent logs instance
	 *
	 * @var Consent_Logs
	 */
	private Consent_Logs $consent_logs;

	/**
	 * Constructor
	 *
	 * @param Consent_Logs $consent_logs Consent logs instance.
	 */
	public function __construct( Consent_Logs $consent_logs ) {
		parent::__construct( [
			'singular' => 'consent_log',
			'plural'   => 'consent_logs',
			'ajax'     => true,
		] );

		$this->consent_logs = $consent_logs;
	}

	/**
	 * Get columns
	 *
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return [
			'cb'              => '<input type="checkbox" />',
			'consent_id'      => __( 'Consent ID', 'medici-cookie-notice' ),
			'consent_status'  => __( 'Статус', 'medici-cookie-notice' ),
			'categories'      => __( 'Категорії', 'medici-cookie-notice' ),
			'geo_country'     => __( 'Країна', 'medici-cookie-notice' ),
			'page_url'        => __( 'Сторінка', 'medici-cookie-notice' ),
			'user_agent'      => __( 'Браузер', 'medici-cookie-notice' ),
			'created_at'      => __( 'Дата', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Get sortable columns
	 *
	 * @return array<string, array<int, string|bool>>
	 */
	public function get_sortable_columns(): array {
		return [
			'consent_status' => [ 'consent_status', false ],
			'geo_country'    => [ 'geo_country', false ],
			'created_at'     => [ 'created_at', true ],
		];
	}

	/**
	 * Get bulk actions
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return [
			'delete' => __( 'Видалити', 'medici-cookie-notice' ),
			'export' => __( 'Експортувати вибрані', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Prepare items
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page     = $this->get_items_per_page( 'mcn_logs_per_page', 20 );
		$current_page = $this->get_pagenum();
		$filters      = $this->get_current_filters();

		// Get total count
		$total_items = $this->consent_logs->count_logs( $filters );

		// Get items
		$offset      = ( $current_page - 1 ) * $per_page;
		$this->items = $this->consent_logs->get_logs( $filters, $per_page, $offset );

		// Set up pagination
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total_items / $per_page ),
		] );

		// Set up columns
		$this->_column_headers = [
			$this->get_columns(),
			[], // hidden columns
			$this->get_sortable_columns(),
		];
	}

	/**
	 * Get current filters from request
	 *
	 * @return array<string, string>
	 */
	protected function get_current_filters(): array {
		return [
			'status'    => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'country'   => isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '',
			'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		];
	}

	/**
	 * Render checkbox column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%d" />',
			absint( $item->id )
		);
	}

	/**
	 * Render consent_id column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_consent_id( $item ): string {
		$consent_id = esc_html( substr( $item->consent_id, 0, 8 ) . '...' );

		$actions = [
			'view'   => sprintf(
				'<a href="#" class="mcn-view-log" data-id="%d">%s</a>',
				absint( $item->id ),
				__( 'Переглянути', 'medici-cookie-notice' )
			),
			'delete' => sprintf(
				'<a href="%s" class="mcn-delete-log" onclick="return confirm(\'%s\');">%s</a>',
				wp_nonce_url(
					add_query_arg( [ 'action' => 'delete', 'log_id' => $item->id ], admin_url( 'admin.php?page=mcn-consent-logs' ) ),
					'mcn_delete_log_' . $item->id
				),
				esc_attr__( 'Ви впевнені?', 'medici-cookie-notice' ),
				__( 'Видалити', 'medici-cookie-notice' )
			),
		];

		return sprintf(
			'<strong><code title="%s">%s</code></strong>%s',
			esc_attr( $item->consent_id ),
			$consent_id,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Render consent_status column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_consent_status( $item ): string {
		$status = $item->consent_status;

		$classes = [
			'accepted' => 'mcn-status-accepted',
			'rejected' => 'mcn-status-rejected',
			'custom'   => 'mcn-status-custom',
		];

		$labels = [
			'accepted' => __( 'Прийнято все', 'medici-cookie-notice' ),
			'rejected' => __( 'Відхилено все', 'medici-cookie-notice' ),
			'custom'   => __( 'Вибіркова', 'medici-cookie-notice' ),
		];

		$class = $classes[ $status ] ?? 'mcn-status-custom';
		$label = $labels[ $status ] ?? ucfirst( $status );

		return sprintf(
			'<span class="mcn-status-badge %s">%s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	/**
	 * Render categories column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_categories( $item ): string {
		$categories = json_decode( $item->consent_categories, true );

		if ( ! is_array( $categories ) ) {
			return '—';
		}

		$icons = [
			'necessary'   => '🔒',
			'analytics'   => '📊',
			'marketing'   => '🎯',
			'preferences' => '⚙️',
		];

		$output = [];
		foreach ( $categories as $cat => $enabled ) {
			if ( $enabled ) {
				$icon     = $icons[ $cat ] ?? '✓';
				$output[] = sprintf(
					'<span class="mcn-category-badge" title="%s">%s</span>',
					esc_attr( ucfirst( $cat ) ),
					$icon
				);
			}
		}

		return implode( ' ', $output ) ?: '—';
	}

	/**
	 * Render geo_country column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_geo_country( $item ): string {
		if ( empty( $item->geo_country ) ) {
			return '—';
		}

		// Country code to flag emoji
		$country = strtoupper( $item->geo_country );
		$flag    = $this->country_to_flag( $country );

		return sprintf(
			'%s <span class="mcn-country-code">%s</span>',
			$flag,
			esc_html( $country )
		);
	}

	/**
	 * Render page_url column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_page_url( $item ): string {
		if ( empty( $item->page_url ) ) {
			return '—';
		}

		$url       = $item->page_url;
		$short_url = strlen( $url ) > 40 ? substr( $url, 0, 40 ) . '...' : $url;

		return sprintf(
			'<a href="%s" target="_blank" title="%s">%s</a>',
			esc_url( home_url( $url ) ),
			esc_attr( $url ),
			esc_html( $short_url )
		);
	}

	/**
	 * Render user_agent column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_user_agent( $item ): string {
		if ( empty( $item->user_agent ) ) {
			return '—';
		}

		$ua = $item->user_agent;

		// Detect browser
		$browser = $this->detect_browser( $ua );

		return sprintf(
			'<span title="%s">%s</span>',
			esc_attr( $ua ),
			esc_html( $browser )
		);
	}

	/**
	 * Render created_at column
	 *
	 * @param object $item The item.
	 * @return string
	 */
	public function column_created_at( $item ): string {
		$datetime = strtotime( $item->created_at );

		return sprintf(
			'<time datetime="%s" title="%s">%s</time>',
			esc_attr( gmdate( 'c', $datetime ) ),
			esc_attr( gmdate( 'Y-m-d H:i:s', $datetime ) ),
			esc_html( human_time_diff( $datetime, time() ) . ' ' . __( 'тому', 'medici-cookie-notice' ) )
		);
	}

	/**
	 * Default column handler
	 *
	 * @param object $item The item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return esc_html( $item->$column_name ?? '—' );
	}

	/**
	 * Extra table navigation (filters)
	 *
	 * @param string $which Top or bottom.
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$filters = $this->get_current_filters();
		?>
		<div class="alignleft actions mcn-filters">
			<select name="status" id="mcn-filter-status">
				<option value=""><?php esc_html_e( 'Всі статуси', 'medici-cookie-notice' ); ?></option>
				<option value="accepted" <?php selected( $filters['status'], 'accepted' ); ?>><?php esc_html_e( 'Прийнято все', 'medici-cookie-notice' ); ?></option>
				<option value="rejected" <?php selected( $filters['status'], 'rejected' ); ?>><?php esc_html_e( 'Відхилено все', 'medici-cookie-notice' ); ?></option>
				<option value="custom" <?php selected( $filters['status'], 'custom' ); ?>><?php esc_html_e( 'Вибіркова', 'medici-cookie-notice' ); ?></option>
			</select>

			<select name="country" id="mcn-filter-country">
				<option value=""><?php esc_html_e( 'Всі країни', 'medici-cookie-notice' ); ?></option>
				<?php
				$countries = $this->consent_logs->get_stats_by_country( 365 );
				foreach ( $countries as $country => $count ) {
					printf(
						'<option value="%s" %s>%s (%d)</option>',
						esc_attr( $country ),
						selected( $filters['country'], $country, false ),
						esc_html( $country ),
						absint( $count )
					);
				}
				?>
			</select>

			<input type="date" name="date_from" id="mcn-filter-date-from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" placeholder="<?php esc_attr_e( 'Від', 'medici-cookie-notice' ); ?>" />
			<input type="date" name="date_to" id="mcn-filter-date-to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" placeholder="<?php esc_attr_e( 'До', 'medici-cookie-notice' ); ?>" />

			<?php submit_button( __( 'Фільтрувати', 'medici-cookie-notice' ), '', 'filter_action', false ); ?>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mcn-consent-logs' ) ); ?>" class="button"><?php esc_html_e( 'Скинути', 'medici-cookie-notice' ); ?></a>
		</div>

		<div class="alignright actions">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mcn-consent-logs&action=export_csv' ), 'mcn_export_logs' ) ); ?>" class="button">
				<?php esc_html_e( 'Експортувати CSV', 'medici-cookie-notice' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * No items message
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'Логи згод не знайдено.', 'medici-cookie-notice' );
	}

	/**
	 * Convert country code to flag emoji
	 *
	 * @param string $country_code Two-letter country code.
	 * @return string
	 */
	private function country_to_flag( string $country_code ): string {
		if ( strlen( $country_code ) !== 2 ) {
			return '🌍';
		}

		$country_code = strtoupper( $country_code );
		$first        = ord( $country_code[0] ) - ord( 'A' ) + 0x1F1E6;
		$second       = ord( $country_code[1] ) - ord( 'A' ) + 0x1F1E6;

		return mb_chr( $first ) . mb_chr( $second );
	}

	/**
	 * Detect browser from user agent
	 *
	 * @param string $ua User agent string.
	 * @return string
	 */
	private function detect_browser( string $ua ): string {
		$browsers = [
			'Chrome'  => '/Chrome\/[\d.]+/',
			'Firefox' => '/Firefox\/[\d.]+/',
			'Safari'  => '/Safari\/[\d.]+/',
			'Edge'    => '/Edg\/[\d.]+/',
			'Opera'   => '/OPR\/[\d.]+/',
			'IE'      => '/MSIE [\d.]+|Trident/',
		];

		foreach ( $browsers as $browser => $pattern ) {
			if ( preg_match( $pattern, $ua ) ) {
				return $browser;
			}
		}

		// Mobile detection
		if ( preg_match( '/Mobile|Android|iPhone/', $ua ) ) {
			return __( 'Мобільний', 'medici-cookie-notice' );
		}

		return __( 'Інший', 'medici-cookie-notice' );
	}
}

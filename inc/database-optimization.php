<?php
/**
 * Database Optimization - Custom Indexes for Performance
 *
 * Creates database indexes for frequently queried meta keys and custom tables.
 * Indexes significantly improve query performance for:
 * - Post views queries (medici_views)
 * - Reading time queries (medici_reading_time)
 * - Featured posts queries (medici_featured)
 * - Events table queries (event_type, created_at)
 *
 * @package Medici
 * @since 1.6.0
 */

declare(strict_types=1);

namespace Medici;

/**
 * Database Optimization Manager
 *
 * Handles creation and management of custom database indexes.
 */
final class Database_Optimization {

	/**
	 * Option name for tracking installed indexes version.
	 */
	private const INDEXES_VERSION_OPTION = 'medici_db_indexes_version';

	/**
	 * Current indexes version - increment when adding new indexes.
	 */
	private const INDEXES_VERSION = '1.0.0';

	/**
	 * Initialize database optimization.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Run on theme activation
		add_action( 'after_switch_theme', array( __CLASS__, 'maybe_create_indexes' ) );

		// Run on admin init (for updates)
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_indexes' ) );

		// Add admin page for manual optimization
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
	}

	/**
	 * Create indexes if not already created or version changed.
	 *
	 * @return void
	 */
	public static function maybe_create_indexes(): void {
		$installed_version = get_option( self::INDEXES_VERSION_OPTION, '' );

		if ( $installed_version !== self::INDEXES_VERSION ) {
			self::create_indexes();
			update_option( self::INDEXES_VERSION_OPTION, self::INDEXES_VERSION );
		}
	}

	/**
	 * Create all custom indexes.
	 *
	 * @return array<string, bool> Results of index creation
	 */
	public static function create_indexes(): array {
		global $wpdb;

		$results = array();

		// =========================================
		// 1. Post Meta Indexes
		// =========================================

		// Index for post views queries
		$results['idx_medici_views'] = self::create_index(
			$wpdb->postmeta,
			'idx_medici_views',
			'meta_key(32), meta_value(10)',
			"meta_key = '_medici_post_views'"
		);

		// Index for reading time queries
		$results['idx_medici_reading_time'] = self::create_index(
			$wpdb->postmeta,
			'idx_medici_reading_time',
			'meta_key(32), meta_value(10)',
			"meta_key = '_medici_reading_time'"
		);

		// Index for featured posts queries
		$results['idx_medici_featured'] = self::create_index(
			$wpdb->postmeta,
			'idx_medici_featured',
			'meta_key(32), meta_value(10)',
			"meta_key = '_medici_featured'"
		);

		// Composite index for post_id + meta_key (common lookup pattern)
		$results['idx_postmeta_post_key'] = self::create_index(
			$wpdb->postmeta,
			'idx_postmeta_post_key',
			'post_id, meta_key(32)'
		);

		// =========================================
		// 2. Events Table Indexes
		// =========================================
		$events_table = $wpdb->prefix . 'medici_events';

		if ( self::table_exists( $events_table ) ) {
			// Index for event_type queries
			$results['idx_event_type'] = self::create_index(
				$events_table,
				'idx_event_type',
				'event_type(32), created_at'
			);

			// Index for date range queries
			$results['idx_events_date'] = self::create_index(
				$events_table,
				'idx_events_date',
				'created_at'
			);
		}

		// =========================================
		// 3. Term Relationships Index
		// =========================================

		// Index for taxonomy queries (blog categories)
		$results['idx_term_taxonomy'] = self::create_index(
			$wpdb->term_relationships,
			'idx_term_taxonomy',
			'term_taxonomy_id, object_id'
		);

		// =========================================
		// 4. Posts Table Indexes
		// =========================================

		// Index for post_type + post_status (common query pattern)
		$results['idx_posts_type_status'] = self::create_index(
			$wpdb->posts,
			'idx_posts_type_status',
			'post_type(20), post_status(20), post_date'
		);

		return $results;
	}

	/**
	 * Create a single index.
	 *
	 * @param string      $table      Table name
	 * @param string      $index_name Index name
	 * @param string      $columns    Columns to index
	 * @param string|null $condition  Optional WHERE condition for partial index
	 * @return bool Success
	 */
	private static function create_index( string $table, string $index_name, string $columns, ?string $condition = null ): bool {
		global $wpdb;

		// Check if index already exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW INDEX FROM {$table} WHERE Key_name = %s",
				$index_name
			)
		);

		if ( $existing ) {
			return true; // Already exists
		}

		// MySQL doesn't support partial indexes, so we ignore the condition
		// but keep it for documentation purposes
		$sql = "CREATE INDEX {$index_name} ON {$table} ({$columns})";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic table/index names
		$result = $wpdb->query( $sql );

		return false !== $result;
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table Table name
	 * @return bool
	 */
	private static function table_exists( string $table ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe
		$result = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );

		return $result === $table;
	}

	/**
	 * Drop all custom indexes (for cleanup).
	 *
	 * @return array<string, bool> Results
	 */
	public static function drop_indexes(): array {
		global $wpdb;

		$indexes = array(
			$wpdb->postmeta                 => array( 'idx_medici_views', 'idx_medici_reading_time', 'idx_medici_featured', 'idx_postmeta_post_key' ),
			$wpdb->prefix . 'medici_events' => array( 'idx_event_type', 'idx_events_date' ),
			$wpdb->term_relationships       => array( 'idx_term_taxonomy' ),
			$wpdb->posts                    => array( 'idx_posts_type_status' ),
		);

		$results = array();

		foreach ( $indexes as $table => $index_names ) {
			if ( ! self::table_exists( $table ) ) {
				continue;
			}

			foreach ( $index_names as $index_name ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result                 = $wpdb->query( "DROP INDEX IF EXISTS {$index_name} ON {$table}" );
				$results[ $index_name ] = false !== $result;
			}
		}

		delete_option( self::INDEXES_VERSION_OPTION );

		return $results;
	}

	/**
	 * Get index statistics.
	 *
	 * @return array<string, array<string, mixed>> Index info
	 */
	public static function get_index_stats(): array {
		global $wpdb;

		$stats  = array();
		$tables = array(
			$wpdb->postmeta,
			$wpdb->prefix . 'medici_events',
			$wpdb->term_relationships,
			$wpdb->posts,
		);

		foreach ( $tables as $table ) {
			if ( ! self::table_exists( $table ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );

			if ( $indexes ) {
				$stats[ $table ] = array();
				foreach ( $indexes as $index ) {
					$key_name = $index['Key_name'];
					if ( ! isset( $stats[ $table ][ $key_name ] ) ) {
						$stats[ $table ][ $key_name ] = array(
							'columns'     => array(),
							'unique'      => '0' === $index['Non_unique'],
							'cardinality' => (int) $index['Cardinality'],
						);
					}
					$stats[ $table ][ $key_name ]['columns'][] = $index['Column_name'];
				}
			}
		}

		return $stats;
	}

	/**
	 * Add admin menu for database optimization.
	 *
	 * @return void
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'Database Optimization', 'medici.agency' ),
			__( 'DB Optimization', 'medici.agency' ),
			'manage_options',
			'medici-db-optimization',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page(): void {
		// Handle form submission
		if ( isset( $_POST['medici_recreate_indexes'] ) && check_admin_referer( 'medici_db_optimization' ) ) {
			self::drop_indexes();
			$results = self::create_indexes();
			update_option( self::INDEXES_VERSION_OPTION, self::INDEXES_VERSION );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Indexes recreated successfully!', 'medici.agency' ) . '</p></div>';
		}

		$stats           = self::get_index_stats();
		$current_version = get_option( self::INDEXES_VERSION_OPTION, 'Not installed' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Database Optimization', 'medici.agency' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Index Status', 'medici.agency' ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'Installed Version:', 'medici.agency' ); ?></strong>
					<?php echo esc_html( $current_version ); ?>
				</p>
				<p>
					<strong><?php esc_html_e( 'Current Version:', 'medici.agency' ); ?></strong>
					<?php echo esc_html( self::INDEXES_VERSION ); ?>
				</p>
			</div>

			<div class="card" style="max-width: 100%;">
				<h2><?php esc_html_e( 'Custom Indexes', 'medici.agency' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Table', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Index Name', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Columns', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Cardinality', 'medici.agency' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats as $table => $indexes ) : ?>
							<?php foreach ( $indexes as $name => $info ) : ?>
								<?php if ( str_starts_with( $name, 'idx_' ) ) : ?>
									<tr>
										<td><code><?php echo esc_html( $table ); ?></code></td>
										<td><strong><?php echo esc_html( $name ); ?></strong></td>
										<td><?php echo esc_html( implode( ', ', $info['columns'] ) ); ?></td>
										<td><?php echo esc_html( (string) $info['cardinality'] ); ?></td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Actions', 'medici.agency' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'medici_db_optimization' ); ?>
					<p>
						<button type="submit" name="medici_recreate_indexes" class="button button-primary">
							<?php esc_html_e( 'Recreate All Indexes', 'medici.agency' ); ?>
						</button>
					</p>
					<p class="description">
						<?php esc_html_e( 'This will drop and recreate all custom indexes. Use if you experience performance issues.', 'medici.agency' ); ?>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
}

// Initialize
Database_Optimization::init();

<?php
/**
 * Cache Manager - Object Caching with Transients API
 *
 * Provides a fluent interface for caching expensive operations using
 * WordPress Transients API. Supports:
 * - Automatic cache invalidation
 * - Cache groups for bulk invalidation
 * - Statistics tracking
 * - Redis/Memcached compatible (via object-cache.php drop-in)
 *
 * @package Medici
 * @since 1.6.0
 */

declare(strict_types=1);

namespace Medici;

/**
 * Cache Manager Class
 *
 * Usage:
 *   $posts = Cache_Manager::remember('top_posts', fn() => get_posts(), HOUR_IN_SECONDS);
 *   Cache_Manager::forget('top_posts');
 *   Cache_Manager::flush_group('blog');
 */
final class Cache_Manager {

	/**
	 * Cache key prefix.
	 */
	private const PREFIX = 'medici_';

	/**
	 * Option name for cache statistics.
	 */
	private const STATS_OPTION = 'medici_cache_stats';

	/**
	 * Option name for cache groups registry.
	 */
	private const GROUPS_OPTION = 'medici_cache_groups';

	/**
	 * Default TTL (1 hour).
	 */
	public const DEFAULT_TTL = HOUR_IN_SECONDS;

	/**
	 * Short TTL (5 minutes).
	 */
	public const SHORT_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Long TTL (24 hours).
	 */
	public const LONG_TTL = DAY_IN_SECONDS;

	/**
	 * Initialize cache manager hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Invalidate caches on content changes
		add_action( 'save_post', array( __CLASS__, 'on_post_save' ), 10, 2 );
		add_action( 'delete_post', array( __CLASS__, 'on_post_delete' ) );
		add_action( 'edit_term', array( __CLASS__, 'on_term_edit' ), 10, 3 );
		add_action( 'delete_term', array( __CLASS__, 'on_term_delete' ), 10, 3 );

		// Admin menu for cache management
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );

		// Admin bar cache clear button
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_button' ), 100 );
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_bar_clear' ) );
	}

	/**
	 * Remember a value in cache.
	 *
	 * @template T
	 * @param string        $key      Cache key
	 * @param callable(): T $callback Function to generate value if not cached
	 * @param int           $ttl      Time to live in seconds
	 * @param string        $group    Optional cache group for bulk invalidation
	 * @return T Cached or fresh value
	 */
	public static function remember( string $key, callable $callback, int $ttl = self::DEFAULT_TTL, string $group = '' ): mixed {
		$prefixed_key = self::PREFIX . $key;

		// Try to get from cache
		$cached = get_transient( $prefixed_key );

		if ( false !== $cached ) {
			self::record_hit( $key );
			return $cached;
		}

		// Generate fresh value
		$value = $callback();

		// Store in cache
		set_transient( $prefixed_key, $value, $ttl );

		// Register with group if provided
		if ( $group ) {
			self::register_key_in_group( $key, $group );
		}

		self::record_miss( $key );

		return $value;
	}

	/**
	 * Remember forever (no expiration).
	 *
	 * @template T
	 * @param string        $key      Cache key
	 * @param callable(): T $callback Function to generate value
	 * @param string        $group    Optional cache group
	 * @return T Cached or fresh value
	 */
	public static function remember_forever( string $key, callable $callback, string $group = '' ): mixed {
		return self::remember( $key, $callback, 0, $group );
	}

	/**
	 * Get a cached value without regenerating.
	 *
	 * @param string $key     Cache key
	 * @param mixed  $default Default value if not found
	 * @return mixed Cached value or default
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$cached = get_transient( self::PREFIX . $key );

		if ( false !== $cached ) {
			self::record_hit( $key );
			return $cached;
		}

		return $default;
	}

	/**
	 * Store a value in cache.
	 *
	 * @param string $key   Cache key
	 * @param mixed  $value Value to store
	 * @param int    $ttl   Time to live in seconds
	 * @param string $group Optional cache group
	 * @return bool Success
	 */
	public static function put( string $key, mixed $value, int $ttl = self::DEFAULT_TTL, string $group = '' ): bool {
		$result = set_transient( self::PREFIX . $key, $value, $ttl );

		if ( $result && $group ) {
			self::register_key_in_group( $key, $group );
		}

		return $result;
	}

	/**
	 * Remove a cached value.
	 *
	 * @param string $key Cache key
	 * @return bool Success
	 */
	public static function forget( string $key ): bool {
		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * Check if a key exists in cache.
	 *
	 * @param string $key Cache key
	 * @return bool
	 */
	public static function has( string $key ): bool {
		return false !== get_transient( self::PREFIX . $key );
	}

	/**
	 * Flush all keys in a cache group.
	 *
	 * @param string $group Group name
	 * @return int Number of keys flushed
	 */
	public static function flush_group( string $group ): int {
		$groups = get_option( self::GROUPS_OPTION, array() );
		$count  = 0;

		if ( isset( $groups[ $group ] ) && is_array( $groups[ $group ] ) ) {
			foreach ( $groups[ $group ] as $key ) {
				if ( self::forget( $key ) ) {
					++$count;
				}
			}

			// Clear group registry
			unset( $groups[ $group ] );
			update_option( self::GROUPS_OPTION, $groups );
		}

		return $count;
	}

	/**
	 * Flush all Medici caches.
	 *
	 * @return int Number of keys flushed
	 */
	public static function flush_all(): int {
		global $wpdb;

		// Delete all transients with our prefix
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::PREFIX . '%',
				'_transient_timeout_' . self::PREFIX . '%'
			)
		);

		// Clear groups registry
		delete_option( self::GROUPS_OPTION );

		// Reset stats
		delete_option( self::STATS_OPTION );

		return (int) $count;
	}

	/**
	 * Register a key in a cache group.
	 *
	 * @param string $key   Cache key
	 * @param string $group Group name
	 * @return void
	 */
	private static function register_key_in_group( string $key, string $group ): void {
		$groups = get_option( self::GROUPS_OPTION, array() );

		if ( ! isset( $groups[ $group ] ) ) {
			$groups[ $group ] = array();
		}

		if ( ! in_array( $key, $groups[ $group ], true ) ) {
			$groups[ $group ][] = $key;
			update_option( self::GROUPS_OPTION, $groups );
		}
	}

	/**
	 * Record a cache hit.
	 *
	 * @param string $key Cache key
	 * @return void
	 */
	private static function record_hit( string $key ): void {
		if ( ! defined( 'MEDICI_CACHE_STATS' ) || ! MEDICI_CACHE_STATS ) {
			return;
		}

		$stats = get_option(
			self::STATS_OPTION,
			array(
				'hits'   => 0,
				'misses' => 0,
				'keys'   => array(),
			)
		);
		++$stats['hits'];

		if ( ! isset( $stats['keys'][ $key ] ) ) {
			$stats['keys'][ $key ] = array(
				'hits'   => 0,
				'misses' => 0,
			);
		}
		++$stats['keys'][ $key ]['hits'];

		update_option( self::STATS_OPTION, $stats );
	}

	/**
	 * Record a cache miss.
	 *
	 * @param string $key Cache key
	 * @return void
	 */
	private static function record_miss( string $key ): void {
		if ( ! defined( 'MEDICI_CACHE_STATS' ) || ! MEDICI_CACHE_STATS ) {
			return;
		}

		$stats = get_option(
			self::STATS_OPTION,
			array(
				'hits'   => 0,
				'misses' => 0,
				'keys'   => array(),
			)
		);
		++$stats['misses'];

		if ( ! isset( $stats['keys'][ $key ] ) ) {
			$stats['keys'][ $key ] = array(
				'hits'   => 0,
				'misses' => 0,
			);
		}
		++$stats['keys'][ $key ]['misses'];

		update_option( self::STATS_OPTION, $stats );
	}

	/**
	 * Get cache statistics.
	 *
	 * @return array{hits: int, misses: int, ratio: float, keys: array<string, array{hits: int, misses: int}>}
	 */
	public static function get_stats(): array {
		$stats = get_option(
			self::STATS_OPTION,
			array(
				'hits'   => 0,
				'misses' => 0,
				'keys'   => array(),
			)
		);

		if ( ! is_array( $stats ) ) {
			$stats = array(
				'hits'   => 0,
				'misses' => 0,
				'keys'   => array(),
			);
		}

		$hits   = (int) ( $stats['hits'] ?? 0 );
		$misses = (int) ( $stats['misses'] ?? 0 );
		$total  = $hits + $misses;

		$stats['hits']   = $hits;
		$stats['misses'] = $misses;
		$stats['ratio']  = $total > 0 ? round( $hits / $total * 100, 2 ) : 0.0;

		return $stats;
	}

	/**
	 * Invalidate caches on post save.
	 *
	 * @param int      $post_id Post ID
	 * @param \WP_Post $post    Post object
	 * @return void
	 */
	public static function on_post_save( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Flush blog-related caches
		if ( 'medici_blog' === $post->post_type ) {
			self::flush_group( 'blog' );
			self::forget( 'top_posts_10' );
			self::forget( 'featured_post' );
			self::forget( 'blog_stats' );
		}

		// Flush lead-related caches
		if ( 'medici_lead' === $post->post_type ) {
			self::flush_group( 'leads' );
			self::forget( 'lead_stats' );
		}
	}

	/**
	 * Invalidate caches on post delete.
	 *
	 * @param int $post_id Post ID
	 * @return void
	 */
	public static function on_post_delete( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		if ( 'medici_blog' === $post->post_type ) {
			self::flush_group( 'blog' );
		}

		if ( 'medici_lead' === $post->post_type ) {
			self::flush_group( 'leads' );
		}
	}

	/**
	 * Invalidate caches on term edit.
	 *
	 * @param int    $term_id  Term ID
	 * @param int    $tt_id    Term taxonomy ID
	 * @param string $taxonomy Taxonomy name
	 * @return void
	 */
	public static function on_term_edit( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( 'blog_category' === $taxonomy ) {
			self::flush_group( 'blog' );
			self::forget( 'blog_categories' );
		}
	}

	/**
	 * Invalidate caches on term delete.
	 *
	 * @param int    $term_id  Term ID
	 * @param int    $tt_id    Term taxonomy ID
	 * @param string $taxonomy Taxonomy name
	 * @return void
	 */
	public static function on_term_delete( int $term_id, int $tt_id, string $taxonomy ): void {
		self::on_term_edit( $term_id, $tt_id, $taxonomy );
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public static function add_admin_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'Cache Manager', 'medici.agency' ),
			__( 'Cache Manager', 'medici.agency' ),
			'manage_options',
			'medici-cache-manager',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Add admin bar button.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar instance
	 * @return void
	 */
	public static function add_admin_bar_button( \WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_node(
			array(
				'id'    => 'medici-clear-cache',
				'title' => '🗑️ ' . __( 'Clear Cache', 'medici.agency' ),
				'href'  => wp_nonce_url( admin_url( 'admin.php?action=medici_clear_cache' ), 'medici_clear_cache' ),
			)
		);
	}

	/**
	 * Handle admin bar cache clear.
	 *
	 * @return void
	 */
	public static function handle_admin_bar_clear(): void {
		if ( ! isset( $_GET['action'] ) || 'medici_clear_cache' !== $_GET['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'medici_clear_cache' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'medici.agency' ) );
		}

		self::flush_all();

		wp_safe_redirect( add_query_arg( 'cache_cleared', '1', wp_get_referer() ?: admin_url() ) );
		exit;
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page(): void {
		// Handle form submission
		if ( isset( $_POST['medici_flush_cache'] ) && check_admin_referer( 'medici_cache_manager' ) ) {
			$count = self::flush_all();
			echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Cache cleared! %d items removed.', 'medici.agency' ), $count ) ) . '</p></div>';
		}

		if ( isset( $_POST['medici_flush_group'] ) && check_admin_referer( 'medici_cache_manager' ) ) {
			$group = sanitize_key( $_POST['cache_group'] ?? '' );
			if ( $group ) {
				$count = self::flush_group( $group );
				echo '<div class="notice notice-success"><p>' . esc_html( sprintf( __( 'Group "%1$s" cleared! %2$d items removed.', 'medici.agency' ), $group, $count ) ) . '</p></div>';
			}
		}

		$stats  = self::get_stats();
		$groups = get_option( self::GROUPS_OPTION, array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cache Manager', 'medici.agency' ); ?></h1>

			<div class="card">
				<h2><?php esc_html_e( 'Cache Statistics', 'medici.agency' ); ?></h2>
				<table class="widefat striped">
					<tr>
						<th><?php esc_html_e( 'Hits', 'medici.agency' ); ?></th>
						<td><strong><?php echo esc_html( (string) $stats['hits'] ); ?></strong></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Misses', 'medici.agency' ); ?></th>
						<td><?php echo esc_html( (string) $stats['misses'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Hit Ratio', 'medici.agency' ); ?></th>
						<td><?php echo esc_html( $stats['ratio'] . '%' ); ?></td>
					</tr>
				</table>
				<p class="description">
					<?php esc_html_e( 'Enable MEDICI_CACHE_STATS in wp-config.php to track detailed statistics.', 'medici.agency' ); ?>
				</p>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Cache Groups', 'medici.agency' ); ?></h2>
				<?php if ( empty( $groups ) ) : ?>
					<p><?php esc_html_e( 'No cache groups registered.', 'medici.agency' ); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Group', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'Keys', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'Action', 'medici.agency' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $groups as $group_name => $keys ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $group_name ); ?></strong></td>
									<td><?php echo esc_html( (string) count( $keys ) ); ?></td>
									<td>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'medici_cache_manager' ); ?>
											<input type="hidden" name="cache_group" value="<?php echo esc_attr( $group_name ); ?>">
											<button type="submit" name="medici_flush_group" class="button button-small">
												<?php esc_html_e( 'Flush', 'medici.agency' ); ?>
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Actions', 'medici.agency' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'medici_cache_manager' ); ?>
					<p>
						<button type="submit" name="medici_flush_cache" class="button button-primary">
							<?php esc_html_e( 'Flush All Caches', 'medici.agency' ); ?>
						</button>
					</p>
				</form>
			</div>
		</div>
		<?php
	}
}

// Initialize
Cache_Manager::init();

// =========================================
// HELPER FUNCTIONS
// =========================================

/**
 * Get cached value or generate it.
 *
 * @template T
 * @param string        $key      Cache key
 * @param callable(): T $callback Generator function
 * @param int           $ttl      TTL in seconds
 * @param string        $group    Cache group
 * @return T
 */
function medici_cache_remember( string $key, callable $callback, int $ttl = HOUR_IN_SECONDS, string $group = '' ): mixed {
	return Cache_Manager::remember( $key, $callback, $ttl, $group );
}

/**
 * Forget a cached value.
 *
 * @param string $key Cache key
 * @return bool
 */
function medici_cache_forget( string $key ): bool {
	return Cache_Manager::forget( $key );
}

/**
 * Flush a cache group.
 *
 * @param string $group Group name
 * @return int Number of keys flushed
 */
function medici_cache_flush_group( string $group ): int {
	return Cache_Manager::flush_group( $group );
}

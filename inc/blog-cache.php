<?php
declare(strict_types=1);
/**
 * ============================================================================
 * MEDICI.AGENCY - BLOG OBJECT CACHING
 * File: inc/blog-cache.php
 * ============================================================================
 *
 * Handles:
 * • Object caching для blog queries (Transients API)
 * • Top viewed posts caching
 * • Featured posts caching
 * • Related posts caching
 * • Category metadata caching
 * • Cache invalidation hooks
 *
 * Depends: blog-meta-fields.php
 * Used by: templates, widgets
 *
 * ✅ PERFORMANCE:
 * • Transients API (підтримує Redis/Memcached якщо доступно)
 * • TTL: 1 hour для frequently changing data
 * • TTL: 12 hours для relatively static data
 * • Auto-invalidation при update_post
 *
 * @version 1.4.0
 * @since   1.4.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// CACHE CONFIGURATION
// ============================================================================

/**
 * Cache configuration constants
 */
final class Medici_Blog_Cache_Config {

	/**
	 * Cache group prefix
	 */
	public const CACHE_GROUP = 'medici_blog';

	/**
	 * Cache TTL (Time To Live) in seconds
	 */
	public const TTL_SHORT  = 3600;  // 1 hour - для часто змінюваних даних
	public const TTL_MEDIUM = 43200; // 12 hours - для відносно статичних даних
	public const TTL_LONG   = 86400; // 24 hours - для рідко змінюваних даних

	/**
	 * Cache keys
	 */
	public const KEY_TOP_VIEWED = 'top_viewed_posts';
	public const KEY_FEATURED   = 'featured_posts';
	public const KEY_RELATED    = 'related_posts';
	public const KEY_CATEGORIES = 'categories_with_colors';
}

// ============================================================================
// TOP VIEWED POSTS WITH CACHING
// ============================================================================

/**
 * Get top viewed posts (cached)
 *
 * Повертає найпопулярніші статті за кількістю переглядів.
 * Результати кешуються на 1 годину (TTL_SHORT).
 *
 * Performance:
 * • Without cache: ~50-200ms (залежить від кількості постів)
 * • With cache: ~1-5ms (миттєво з Transients)
 * • Cache invalidation: При оновленні будь-якого поста
 *
 * @since 1.4.0
 * @param int $limit Number of posts to retrieve (default: 10).
 * @return array Array of WP_Post objects
 */
function medici_get_top_viewed_posts_cached( int $limit = 10 ): array {
	$cache_key = Medici_Blog_Cache_Config::KEY_TOP_VIEWED . '_' . $limit;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	// Query top viewed posts
	$args = array(
		'post_type'      => 'medici_blog',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_key'       => Medici_Blog_Meta_Config::META_POST_VIEWS,
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'no_found_rows'  => true, // Performance optimization
		'fields'         => 'ids', // Тільки ID для economy memory
	);

	$query = new WP_Query( $args );
	$posts = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$posts[] = $post;
			}
		}
	}

	wp_reset_postdata();

	// Cache results
	set_transient( $cache_key, $posts, Medici_Blog_Cache_Config::TTL_SHORT );

	return $posts;
}

// ============================================================================
// FEATURED POSTS WITH CACHING
// ============================================================================

/**
 * Get featured posts (cached)
 *
 * Повертає рекомендовані статті (featured).
 * Результати кешуються на 12 годин (TTL_MEDIUM).
 *
 * Performance:
 * • Without cache: ~30-100ms
 * • With cache: ~1-5ms
 *
 * @since 1.4.0
 * @param int $limit Number of posts to retrieve (default: 6).
 * @return array Array of WP_Post objects
 */
function medici_get_featured_posts_cached( int $limit = 6 ): array {
	$cache_key = Medici_Blog_Cache_Config::KEY_FEATURED . '_' . $limit;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	// Query featured posts
	$args = array(
		'post_type'      => 'medici_blog',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'meta_key'       => Medici_Blog_Meta_Config::META_FEATURED,
		'meta_value'     => '1',
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );
	$posts = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$posts[] = $post;
			}
		}
	}

	wp_reset_postdata();

	// Cache results
	set_transient( $cache_key, $posts, Medici_Blog_Cache_Config::TTL_MEDIUM );

	return $posts;
}

// ============================================================================
// RELATED POSTS WITH CACHING
// ============================================================================

/**
 * Get related posts (cached)
 *
 * Повертає схожі статті на основі категорій та тегів.
 * Кешується окремо для кожного поста.
 *
 * Performance:
 * • Without cache: ~100-300ms (складний запит з taxonomy)
 * • With cache: ~1-5ms
 *
 * @since 1.4.0
 * @param int $post_id Current post ID.
 * @param int $limit   Number of posts to retrieve (default: 3).
 * @return array Array of WP_Post objects
 */
function medici_get_related_posts_cached( int $post_id, int $limit = 3 ): array {
	$cache_key = Medici_Blog_Cache_Config::KEY_RELATED . '_' . $post_id . '_' . $limit;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	// Get post categories
	$categories = wp_get_post_terms( $post_id, 'medici_blog_category', array( 'fields' => 'ids' ) );

	if ( is_wp_error( $categories ) || empty( $categories ) ) {
		return array();
	}

	// Query related posts by category
	$args = array(
		'post_type'      => 'medici_blog',
		'posts_per_page' => $limit,
		'post_status'    => 'publish',
		'post__not_in'   => array( $post_id ),
		'tax_query'      => array(
			array(
				'taxonomy' => 'medici_blog_category',
				'field'    => 'term_id',
				'terms'    => $categories,
			),
		),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );
	$posts = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $related_post_id ) {
			$post = get_post( $related_post_id );
			if ( $post ) {
				$posts[] = $post;
			}
		}
	}

	wp_reset_postdata();

	// Cache results
	set_transient( $cache_key, $posts, Medici_Blog_Cache_Config::TTL_MEDIUM );

	return $posts;
}

// ============================================================================
// CATEGORIES WITH COLORS (CACHED)
// ============================================================================

/**
 * Get all categories with color metadata (cached)
 *
 * Повертає всі категорії з їх кольорами та іконками.
 * Кешується на 24 години (TTL_LONG) - дуже рідко змінюється.
 *
 * Performance:
 * • Without cache: ~20-50ms
 * • With cache: ~1ms
 *
 * @since 1.4.0
 * @return array Array of term objects with color metadata
 */
function medici_get_categories_with_colors_cached(): array {
	$cache_key = Medici_Blog_Cache_Config::KEY_CATEGORIES;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached && is_array( $cached ) ) {
		return $cached;
	}

	// Get all blog categories
	$categories = get_terms(
		array(
			'taxonomy'   => 'medici_blog_category',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $categories ) ) {
		return array();
	}

	$categories_with_meta = array();

	foreach ( $categories as $category ) {
		$category_id = (int) $category->term_id;

		// Add color metadata if available
		if ( function_exists( 'medici_get_category_color' ) ) {
			$category->color = medici_get_category_color( $category_id );
		}

		$categories_with_meta[] = $category;
	}

	// Cache results
	set_transient( $cache_key, $categories_with_meta, Medici_Blog_Cache_Config::TTL_LONG );

	return $categories_with_meta;
}

// ============================================================================
// CACHE INVALIDATION
// ============================================================================

/**
 * Invalidate all blog caches when post is updated
 *
 * Видаляє всі кеші при оновленні поста для забезпечення актуальності даних.
 * Це гарантує що користувачі завжди бачать свіжі дані.
 *
 * Triggered by:
 * • save_post_medici_blog - при збереженні блог поста
 * • edit_medici_blog_category - при редагуванні категорії
 *
 * @since 1.4.0
 * @param int $post_id Post ID.
 * @return void
 */
function medici_invalidate_blog_cache( int $post_id ): void {
	// Clear top viewed posts cache (всі варіанти limit)
	foreach ( array( 5, 10, 15, 20 ) as $limit ) {
		delete_transient( Medici_Blog_Cache_Config::KEY_TOP_VIEWED . '_' . $limit );
	}

	// Clear featured posts cache
	foreach ( array( 3, 6, 9, 12 ) as $limit ) {
		delete_transient( Medici_Blog_Cache_Config::KEY_FEATURED . '_' . $limit );
	}

	// Clear related posts cache для цього поста
	foreach ( array( 3, 6, 9 ) as $limit ) {
		delete_transient( Medici_Blog_Cache_Config::KEY_RELATED . '_' . $post_id . '_' . $limit );
	}

	// Clear categories cache
	delete_transient( Medici_Blog_Cache_Config::KEY_CATEGORIES );

	// Log для debugging
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Medici: Blog cache invalidated for post ID ' . $post_id );
	}
}

// Invalidate cache on post save/update
add_action( 'save_post_medici_blog', 'medici_invalidate_blog_cache' );
add_action( 'transition_post_status', 'medici_invalidate_blog_cache_on_status_change', 10, 3 );

/**
 * Invalidate cache when post status changes
 *
 * @since 1.4.0
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 * @return void
 */
function medici_invalidate_blog_cache_on_status_change( string $new_status, string $old_status, WP_Post $post ): void {
	if ( 'medici_blog' === $post->post_type && $new_status !== $old_status ) {
		medici_invalidate_blog_cache( (int) $post->ID );
	}
}

/**
 * Invalidate categories cache when category is edited
 *
 * @since 1.4.0
 * @return void
 */
function medici_invalidate_categories_cache(): void {
	delete_transient( Medici_Blog_Cache_Config::KEY_CATEGORIES );
}

add_action( 'edit_medici_blog_category', 'medici_invalidate_categories_cache' );
add_action( 'create_medici_blog_category', 'medici_invalidate_categories_cache' );
add_action( 'delete_medici_blog_category', 'medici_invalidate_categories_cache' );

// ============================================================================
// ADMIN TOOLS - MANUAL CACHE CLEARING
// ============================================================================

/**
 * Clear all blog caches (admin tool)
 *
 * Корисно для debugging або якщо потрібно примусово оновити кеш.
 * Може викликатись вручну через WordPress admin.
 *
 * Usage: medici_clear_all_blog_cache();
 *
 * @since 1.4.0
 * @return bool True if cache cleared successfully
 */
function medici_clear_all_blog_cache(): bool {
	global $wpdb;

	// Clear all medici_blog transients using SQL
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$result = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			'_transient_' . Medici_Blog_Cache_Config::KEY_TOP_VIEWED . '%',
			'_transient_' . Medici_Blog_Cache_Config::KEY_FEATURED . '%'
		)
	);

	// Also clear timeout keys
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE %s
			OR option_name LIKE %s",
			'_transient_timeout_' . Medici_Blog_Cache_Config::KEY_TOP_VIEWED . '%',
			'_transient_timeout_' . Medici_Blog_Cache_Config::KEY_FEATURED . '%'
		)
	);

	return false !== $result;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get cache statistics (for debugging)
 *
 * Повертає статистику по кешах блогу.
 *
 * @since 1.4.0
 * @return array Cache statistics
 */
function medici_get_blog_cache_stats(): array {
	$stats = array();

	// Check top viewed posts cache
	foreach ( array( 5, 10, 15, 20 ) as $limit ) {
		$key           = Medici_Blog_Cache_Config::KEY_TOP_VIEWED . '_' . $limit;
		$stats[ $key ] = false !== get_transient( $key );
	}

	// Check featured posts cache
	foreach ( array( 3, 6, 9, 12 ) as $limit ) {
		$key           = Medici_Blog_Cache_Config::KEY_FEATURED . '_' . $limit;
		$stats[ $key ] = false !== get_transient( $key );
	}

	// Check categories cache
	$key           = Medici_Blog_Cache_Config::KEY_CATEGORIES;
	$stats[ $key ] = false !== get_transient( $key );

	return $stats;
}

// ============================================================================
// END OF BLOG-CACHE.PHP
// ============================================================================

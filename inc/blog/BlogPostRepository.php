<?php
/**
 * Blog Post Repository
 *
 * Repository pattern for blog post data access.
 * Abstracts WordPress database queries and provides caching.
 *
 * @package    Medici_Agency
 * @subpackage Blog
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Blog;

use WP_Post;
use WP_Query;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blog Post Repository Class
 *
 * Provides abstracted data access for blog posts with caching support.
 *
 * @since 2.0.0
 */
final class BlogPostRepository {

	/**
	 * Post type constant
	 */
	public const POST_TYPE = 'medici_blog';

	/**
	 * Meta field keys
	 */
	public const META_READING_TIME     = '_medici_reading_time';
	public const META_FEATURED         = '_medici_featured_article';
	public const META_PUBLICATION_DATE = '_medici_publication_date';
	public const META_AUTHOR_NAME      = '_medici_author_name';
	public const META_POST_VIEWS       = '_medici_post_views';
	public const META_CUSTOM_PUB_DATE  = '_medici_custom_publish_date';

	/**
	 * Cache group for transients
	 */
	private const CACHE_GROUP = 'medici_blog_';

	/**
	 * Cache TTL in seconds (1 hour)
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * wpdb instance
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Find post by ID
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return WP_Post|null Post object or null.
	 */
	public function find( int $post_id ): ?WP_Post {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return $post;
	}

	/**
	 * Find featured posts
	 *
	 * @since 2.0.0
	 * @param int $limit Maximum number of posts.
	 * @return array<WP_Post> Array of featured posts.
	 */
	public function findFeatured( int $limit = 6 ): array {
		$cache_key = self::CACHE_GROUP . 'featured_' . $limit;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'meta_key'       => self::META_FEATURED,
				'meta_value'     => '1',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
			)
		);

		$posts = $query->posts;
		set_transient( $cache_key, $posts, self::CACHE_TTL );

		return $posts;
	}

	/**
	 * Find popular posts by views
	 *
	 * @since 2.0.0
	 * @param int $limit Maximum number of posts.
	 * @return array<WP_Post> Array of popular posts.
	 */
	public function findPopular( int $limit = 10 ): array {
		$cache_key = self::CACHE_GROUP . 'popular_' . $limit;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => $limit,
				'post_status'    => 'publish',
				'meta_key'       => self::META_POST_VIEWS,
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
			)
		);

		$posts = $query->posts;
		set_transient( $cache_key, $posts, self::CACHE_TTL );

		return $posts;
	}

	/**
	 * Find related posts by category
	 *
	 * @since 2.0.0
	 * @param int $post_id  Current post ID.
	 * @param int $limit    Maximum number of posts.
	 * @return array<WP_Post> Array of related posts.
	 */
	public function findRelated( int $post_id, int $limit = 3 ): array {
		$cache_key = self::CACHE_GROUP . 'related_' . $post_id . '_' . $limit;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Get post categories.
		$categories = wp_get_post_terms( $post_id, 'medici_blog_category', array( 'fields' => 'ids' ) );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
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
				'orderby'        => 'rand',
				'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
			)
		);

		$posts = $query->posts;
		set_transient( $cache_key, $posts, self::CACHE_TTL );

		return $posts;
	}

	/**
	 * Get all meta values for a post
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return array<string, mixed> Meta values.
	 */
	public function getMeta( int $post_id ): array {
		return array(
			'reading_time'     => get_post_meta( $post_id, self::META_READING_TIME, true ),
			'featured'         => get_post_meta( $post_id, self::META_FEATURED, true ),
			'publication_date' => get_post_meta( $post_id, self::META_PUBLICATION_DATE, true ),
			'author_name'      => get_post_meta( $post_id, self::META_AUTHOR_NAME, true ),
			'post_views'       => get_post_meta( $post_id, self::META_POST_VIEWS, true ),
			'custom_pub_date'  => get_post_meta( $post_id, self::META_CUSTOM_PUB_DATE, true ),
		);
	}

	/**
	 * Update meta value
	 *
	 * @since 2.0.0
	 * @param int    $post_id   Post ID.
	 * @param string $meta_key  Meta key.
	 * @param mixed  $value     Meta value.
	 * @return bool True on success.
	 */
	public function updateMeta( int $post_id, string $meta_key, $value ): bool {
		$result = update_post_meta( $post_id, $meta_key, $value );
		$this->invalidateCache( $post_id );

		return false !== $result;
	}

	/**
	 * Get post views count
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int Views count.
	 */
	public function getViews( int $post_id ): int {
		$views = get_post_meta( $post_id, self::META_POST_VIEWS, true );

		return ( '' !== $views ) ? (int) $views : 0;
	}

	/**
	 * Increment post views atomically
	 *
	 * Uses direct SQL for atomic increment to prevent race conditions.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return bool True on success.
	 */
	public function incrementViews( int $post_id ): bool {
		// First, check if meta exists.
		$current_views = $this->getViews( $post_id );

		if ( 0 === $current_views ) {
			// Create meta if not exists.
			$result = add_post_meta( $post_id, self::META_POST_VIEWS, 1, true );

			if ( false === $result ) {
				// Meta already exists, do atomic update.
				return $this->atomicIncrement( $post_id );
			}

			return true;
		}

		return $this->atomicIncrement( $post_id );
	}

	/**
	 * Atomic increment using direct SQL
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return bool True on success.
	 */
	private function atomicIncrement( int $post_id ): bool {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->postmeta}
				SET meta_value = meta_value + 1
				WHERE post_id = %d AND meta_key = %s",
				$post_id,
				self::META_POST_VIEWS
			)
		);

		// Invalidate popular posts cache.
		delete_transient( self::CACHE_GROUP . 'popular_10' );
		delete_transient( self::CACHE_GROUP . 'popular_5' );

		return false !== $result;
	}

	/**
	 * Check if post is featured
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return bool True if featured.
	 */
	public function isFeatured( int $post_id ): bool {
		return '1' === get_post_meta( $post_id, self::META_FEATURED, true );
	}

	/**
	 * Get reading time
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int Reading time in minutes.
	 */
	public function getReadingTime( int $post_id ): int {
		$reading_time = get_post_meta( $post_id, self::META_READING_TIME, true );

		return ( '' !== $reading_time ) ? (int) $reading_time : 0;
	}

	/**
	 * Set reading time
	 *
	 * @since 2.0.0
	 * @param int $post_id      Post ID.
	 * @param int $reading_time Reading time in minutes.
	 * @return bool True on success.
	 */
	public function setReadingTime( int $post_id, int $reading_time ): bool {
		return $this->updateMeta( $post_id, self::META_READING_TIME, $reading_time );
	}

	/**
	 * Get publication date for display
	 *
	 * @since 2.0.0
	 * @param int    $post_id Post ID.
	 * @param string $format  Date format.
	 * @return string Formatted date.
	 */
	public function getPublicationDate( int $post_id, string $format = 'j F Y' ): string {
		$custom_date = get_post_meta( $post_id, self::META_PUBLICATION_DATE, true );

		if ( ! empty( $custom_date ) ) {
			$timestamp = strtotime( $custom_date );
			if ( false !== $timestamp ) {
				return date_i18n( $format, $timestamp );
			}
		}

		return get_the_date( $format, $post_id );
	}

	/**
	 * Get author name
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return string Author name.
	 */
	public function getAuthorName( int $post_id ): string {
		$custom_author = get_post_meta( $post_id, self::META_AUTHOR_NAME, true );

		if ( ! empty( $custom_author ) ) {
			return $custom_author;
		}

		$post = $this->find( $post_id );
		if ( ! $post ) {
			return '';
		}

		return get_the_author_meta( 'display_name', (int) $post->post_author );
	}

	/**
	 * Invalidate cache for a post
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function invalidateCache( int $post_id ): void {
		// Invalidate related cache.
		delete_transient( self::CACHE_GROUP . 'related_' . $post_id . '_3' );
		delete_transient( self::CACHE_GROUP . 'related_' . $post_id . '_5' );

		// Invalidate featured cache.
		delete_transient( self::CACHE_GROUP . 'featured_6' );
		delete_transient( self::CACHE_GROUP . 'featured_3' );
	}

	/**
	 * Get all meta keys
	 *
	 * @since 2.0.0
	 * @return array<string> Meta keys.
	 */
	public static function getMetaKeys(): array {
		return array(
			self::META_READING_TIME,
			self::META_FEATURED,
			self::META_PUBLICATION_DATE,
			self::META_AUTHOR_NAME,
			self::META_POST_VIEWS,
			self::META_CUSTOM_PUB_DATE,
		);
	}
}

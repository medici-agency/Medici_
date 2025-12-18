<?php
/**
 * XML Sitemap Optimization
 *
 * Optimizes WordPress core sitemap (WordPress 5.5+):
 * - Sets priority and changefreq for different content types
 * - Excludes admin/system pages
 * - Adds custom post types and taxonomies
 *
 * @package    Medici_Agency
 * @subpackage Sitemap
 * @since      1.3.5
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemap Priority Configuration
 *
 * @since 1.3.5
 */
final class Medici_Sitemap_Config {

	/**
	 * Priority values (0.0 - 1.0)
	 */
	public const PRIORITY_HOMEPAGE   = 1.0;
	public const PRIORITY_BLOG_HOME  = 0.9;
	public const PRIORITY_MAIN_PAGES = 0.8;
	public const PRIORITY_BLOG_POSTS = 0.7;
	public const PRIORITY_CATEGORIES = 0.6;
	public const PRIORITY_ARCHIVES   = 0.5;

	/**
	 * Change frequency values
	 */
	public const CHANGEFREQ_ALWAYS  = 'always';
	public const CHANGEFREQ_HOURLY  = 'hourly';
	public const CHANGEFREQ_DAILY   = 'daily';
	public const CHANGEFREQ_WEEKLY  = 'weekly';
	public const CHANGEFREQ_MONTHLY = 'monthly';
	public const CHANGEFREQ_YEARLY  = 'yearly';
	public const CHANGEFREQ_NEVER   = 'never';

	/**
	 * Pages to exclude from sitemap (slug patterns)
	 *
	 * @since 1.3.5
	 * @return array Page slugs to exclude
	 */
	public static function get_excluded_pages(): array {
		return array(
			'wp-admin',
			'wp-login',
			'wp-json',
			'xmlrpc',
			'wp-cron',
			'cart',
			'checkout',
			'my-account',
			'privacy-policy',
			'thank-you',
			'404',
		);
	}

	/**
	 * Post types to exclude from sitemap
	 *
	 * @since 1.3.5
	 * @return array Post type names to exclude
	 */
	public static function get_excluded_post_types(): array {
		return array(
			'attachment',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_global_styles',
			'wp_navigation',
		);
	}
}

/**
 * Add priority and changefreq to sitemap entries
 *
 * WordPress core sitemap doesn't include these by default.
 * This filter adds them for better SEO.
 *
 * @since 1.3.5
 * @param array  $entry Sitemap entry data
 * @param string $object_type Object type (post, term, user)
 * @param int    $object_id Object ID
 * @return array Modified sitemap entry
 */
function medici_add_sitemap_priority( array $entry, string $object_type, int $object_id ): array {
	// Homepage - highest priority
	if ( 'post' === $object_type && (int) get_option( 'page_on_front' ) === $object_id ) {
		$entry['priority']   = Medici_Sitemap_Config::PRIORITY_HOMEPAGE;
		$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_DAILY;
		return $entry;
	}

	// Blog home page
	if ( 'post' === $object_type && (int) get_option( 'page_for_posts' ) === $object_id ) {
		$entry['priority']   = Medici_Sitemap_Config::PRIORITY_BLOG_HOME;
		$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_DAILY;
		return $entry;
	}

	// Posts
	if ( 'post' === $object_type ) {
		$post = get_post( $object_id );

		if ( $post instanceof WP_Post ) {
			// Blog posts (medici_blog CPT)
			if ( 'medici_blog' === $post->post_type ) {
				$entry['priority']   = Medici_Sitemap_Config::PRIORITY_BLOG_POSTS;
				$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_MONTHLY;
			}
			// Regular pages
			elseif ( 'page' === $post->post_type ) {
				$entry['priority']   = Medici_Sitemap_Config::PRIORITY_MAIN_PAGES;
				$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_WEEKLY;
			}
			// Regular posts
			else {
				$entry['priority']   = Medici_Sitemap_Config::PRIORITY_BLOG_POSTS;
				$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_MONTHLY;
			}
		}
	}

	// Taxonomies (categories, tags)
	if ( 'term' === $object_type ) {
		$entry['priority']   = Medici_Sitemap_Config::PRIORITY_CATEGORIES;
		$entry['changefreq'] = Medici_Sitemap_Config::CHANGEFREQ_WEEKLY;
	}

	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'medici_add_sitemap_priority', 10, 3 );
add_filter( 'wp_sitemaps_taxonomies_entry', 'medici_add_sitemap_priority', 10, 3 );

/**
 * Exclude specific pages from sitemap
 *
 * Removes admin pages, utility pages, and system pages.
 *
 * @since 1.3.5
 * @param array $args Query arguments for sitemap
 * @return array Modified query arguments
 */
function medici_exclude_pages_from_sitemap( array $args ): array {
	$excluded_pages = Medici_Sitemap_Config::get_excluded_pages();

	// Get IDs of pages to exclude
	$excluded_ids = array();

	foreach ( $excluded_pages as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page instanceof WP_Post ) {
			$excluded_ids[] = $page->ID;
		}
	}

	// Add to existing post__not_in if exists
	if ( ! empty( $excluded_ids ) ) {
		if ( isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ) {
			$args['post__not_in'] = array_merge( $args['post__not_in'], $excluded_ids );
		} else {
			$args['post__not_in'] = $excluded_ids;
		}
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'medici_exclude_pages_from_sitemap' );

/**
 * Exclude post types from sitemap
 *
 * Removes system post types and attachments.
 *
 * @since 1.3.5
 * @param array $post_types Post types to include in sitemap
 * @return array Filtered post types
 */
function medici_exclude_post_types_from_sitemap( array $post_types ): array {
	$excluded = Medici_Sitemap_Config::get_excluded_post_types();

	foreach ( $excluded as $post_type ) {
		unset( $post_types[ $post_type ] );
	}

	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'medici_exclude_post_types_from_sitemap' );

/**
 * Add medici_blog custom post type to sitemap
 *
 * @since 1.3.5
 * @param array $post_types Post types in sitemap
 * @return array Modified post types
 */
function medici_add_blog_to_sitemap( array $post_types ): array {
	if ( post_type_exists( 'medici_blog' ) ) {
		$post_types['medici_blog'] = get_post_type_object( 'medici_blog' );
	}

	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'medici_add_blog_to_sitemap' );

/**
 * Add medici_blog_category taxonomy to sitemap
 *
 * @since 1.3.5
 * @param array $taxonomies Taxonomies in sitemap
 * @return array Modified taxonomies
 */
function medici_add_blog_taxonomy_to_sitemap( array $taxonomies ): array {
	if ( taxonomy_exists( 'medici_blog_category' ) ) {
		$taxonomies['medici_blog_category'] = get_taxonomy( 'medici_blog_category' );
	}

	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'medici_add_blog_taxonomy_to_sitemap' );

/**
 * Set maximum entries per sitemap
 *
 * Default is 2000, we set to 1000 for better performance.
 *
 * @since 1.3.5
 * @return int Maximum entries
 */
function medici_sitemap_max_entries(): int {
	return 1000;
}
add_filter( 'wp_sitemaps_max_urls', 'medici_sitemap_max_entries' );

/**
 * Exclude empty taxonomies from sitemap
 *
 * Don't include categories/tags with 0 posts.
 *
 * @since 1.3.5
 * @param array $args Taxonomy query arguments
 * @return array Modified arguments
 */
function medici_exclude_empty_terms_from_sitemap( array $args ): array {
	$args['hide_empty'] = true;

	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'medici_exclude_empty_terms_from_sitemap' );

/**
 * Add custom stylesheet to sitemap
 *
 * Optional: Style the sitemap for better human readability.
 *
 * @since 1.3.5
 * @param string $stylesheet Stylesheet URL
 * @return string Modified stylesheet URL
 */
function medici_sitemap_stylesheet( string $stylesheet ): string {
	// You can create a custom XSLT stylesheet in theme root
	$custom_stylesheet = get_stylesheet_directory_uri() . '/sitemap-stylesheet.xsl';

	if ( file_exists( get_stylesheet_directory() . '/sitemap-stylesheet.xsl' ) ) {
		return $custom_stylesheet;
	}

	return $stylesheet;
}
// Uncomment to enable custom stylesheet
// add_filter( 'wp_sitemaps_stylesheet_url', 'medici_sitemap_stylesheet' );

/**
 * Disable user sitemap
 *
 * Most sites don't need author pages in sitemap.
 *
 * @since 1.3.5
 * @param WP_Sitemaps_Provider $provider Sitemap provider object
 * @param string               $name     Provider name
 * @return WP_Sitemaps_Provider|false Provider object or false to exclude
 */
function medici_disable_user_sitemap( $provider, string $name ) {
	// Exclude 'users' provider (author pages)
	if ( 'users' === $name ) {
		return false;
	}

	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'medici_disable_user_sitemap', 10, 2 );

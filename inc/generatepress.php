<?php
/**
 * GeneratePress Theme Customizations
 *
 * Modifications and enhancements for the GeneratePress theme.
 *
 * @package    Medici_Agency
 * @subpackage Theme_Customizations
 * @since      1.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeneratePress Configuration Class
 *
 * @since 1.1.0
 */
final class Medici_GeneratePress_Config {

	/**
	 * Default container width
	 */
	public const CONTAINER_WIDTH = 1200;

	/**
	 * Body classes to add on specific pages
	 */
	public const BODY_CLASS_HOMEPAGE = array( 'homepage', 'full-width-page' );
	public const BODY_CLASS_THEME    = 'medici-theme';
}

/**
 * Add custom body classes
 *
 * @since 1.0.0
 * @param array $classes Body classes array
 * @return array Modified classes array
 */
function medici_body_classes( array $classes ): array {
	// Add homepage classes
	if ( is_front_page() ) {
		$classes = array_merge( $classes, Medici_GeneratePress_Config::BODY_CLASS_HOMEPAGE );
	}

	// Add full-width class for blog archive and category pages
	if ( is_post_type_archive( 'medici_blog' ) || is_tax( 'blog_category' ) ) {
		$classes[] = 'full-width-content';
		$classes[] = 'no-sidebar';
	}

	// Add theme identifier class
	$classes[] = Medici_GeneratePress_Config::BODY_CLASS_THEME;

	return $classes;
}
add_filter( 'body_class', 'medici_body_classes' );

/**
 * Set GenerateBlocks defaults
 *
 * Синхронізація стилів GenerateBlocks з темою Medici.
 * Патерн адаптовано з GeneratePress G-Child boilerplate.
 *
 * @see https://github.com/gaambo/generatepress_g-child
 * @since 1.0.0
 * @since 1.2.0 Розширено з патернами G-Child boilerplate
 * @param array $defaults GenerateBlocks defaults array
 * @return array Modified defaults array
 */
function medici_generateblocks_defaults( array $defaults ): array {
	// Кольори теми Medici (синхронізовано з css/core/variables.css)
	$theme_colors = array(
		'accent'         => '#2563eb',
		'accent_hover'   => '#3b82f6',
		'text_primary'   => '#1a1a1a',
		'text_secondary' => '#4b5563',
		'bg_primary'     => '#ffffff',
	);

	// ===== BUTTON BLOCK DEFAULTS =====
	// Синхронізація з GeneratePress button styles
	if ( ! isset( $defaults['button'] ) || ! is_array( $defaults['button'] ) ) {
		$defaults['button'] = array();
	}

	// Button colors (primary style)
	$defaults['button']['backgroundColor']      = $theme_colors['accent'];
	$defaults['button']['backgroundColorHover'] = $theme_colors['accent_hover'];
	$defaults['button']['textColor']            = '#ffffff';
	$defaults['button']['textColorHover']       = '#ffffff';

	// Button spacing (consistent with theme buttons)
	$defaults['button']['paddingTop']    = '12';
	$defaults['button']['paddingRight']  = '24';
	$defaults['button']['paddingBottom'] = '12';
	$defaults['button']['paddingLeft']   = '24';

	// Button border radius
	$defaults['button']['borderRadiusTopLeft']     = '8';
	$defaults['button']['borderRadiusTopRight']    = '8';
	$defaults['button']['borderRadiusBottomRight'] = '8';
	$defaults['button']['borderRadiusBottomLeft']  = '8';

	// ===== CONTAINER BLOCK DEFAULTS =====
	if ( ! isset( $defaults['container'] ) || ! is_array( $defaults['container'] ) ) {
		$defaults['container'] = array();
	}

	$defaults['container']['width'] = Medici_GeneratePress_Config::CONTAINER_WIDTH;

	// Container padding (Utopia spacing)
	$defaults['container']['paddingTop']    = '40';
	$defaults['container']['paddingRight']  = '40';
	$defaults['container']['paddingBottom'] = '40';
	$defaults['container']['paddingLeft']   = '40';

	// ===== HEADLINE BLOCK DEFAULTS =====
	if ( ! isset( $defaults['headline'] ) || ! is_array( $defaults['headline'] ) ) {
		$defaults['headline'] = array();
	}

	$defaults['headline']['color'] = $theme_colors['text_primary'];

	// ===== GRID BLOCK DEFAULTS =====
	if ( ! isset( $defaults['gridContainer'] ) || ! is_array( $defaults['gridContainer'] ) ) {
		$defaults['gridContainer'] = array();
	}

	$defaults['gridContainer']['horizontalGap'] = '30';
	$defaults['gridContainer']['verticalGap']   = '30';

	return $defaults;
}
add_filter( 'generateblocks_defaults', 'medici_generateblocks_defaults' );

/**
 * Disable GeneratePress author meta on single posts
 *
 * @since 1.0.0
 * @param bool $show Whether to show author meta
 * @return bool False to hide author meta on single posts
 */
function medici_disable_gp_author_meta( bool $show ): bool {
	return is_single() ? false : $show;
}
add_filter( 'generate_show_author', 'medici_disable_gp_author_meta' );

/**
 * Disable GeneratePress date meta on blog posts
 *
 * @since 1.1.0
 * @param bool $show Whether to show date meta
 * @return bool False to hide date meta on blog posts
 */
function medici_disable_gp_date_meta( bool $show ): bool {
	if ( is_singular( 'medici_blog' ) ) {
		return false;
	}
	return $show;
}
add_filter( 'generate_show_date', 'medici_disable_gp_date_meta' );

/**
 * REMOVED - medici_excerpt_length() is defined in performance.php
 * to avoid function redeclaration error
 */

/**
 * Disable sidebar on blog archive pages (full-width layout)
 *
 * @since 1.0.18
 * @param string $layout Current layout
 * @return string Modified layout
 */
function medici_disable_blog_archive_sidebar( string $layout ): string {
	if ( is_post_type_archive( 'medici_blog' ) || is_tax( 'blog_category' ) ) {
		return 'no-sidebar'; // Full width layout
	}
	return $layout;
}
add_filter( 'generate_sidebar_layout', 'medici_disable_blog_archive_sidebar' );

/**
 * Remove sidebar completely on blog archive pages
 *
 * @since 1.0.18
 * @return void
 */
function medici_remove_blog_archive_sidebar(): void {
	if ( is_post_type_archive( 'medici_blog' ) || is_tax( 'blog_category' ) ) {
		remove_action( 'generate_sidebars', 'generate_construct_sidebars' );
	}
}
add_action( 'wp', 'medici_remove_blog_archive_sidebar' );

/**
 * Get related blog posts helper function
 *
 * @since 1.1.0
 * @param int $post_id Post ID
 * @param int $limit Number of posts to return
 * @return array Related posts
 */
function medici_get_related_blog_posts( int $post_id, int $limit = 3 ): array {
	$categories = get_the_terms( $post_id, 'blog_category' );

	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return array();
	}

	$category_ids = wp_list_pluck( $categories, 'term_id' );

	$args = array(
		'post_type'      => 'medici_blog',
		'posts_per_page' => $limit,
		'post__not_in'   => array( $post_id ),
		'tax_query'      => array(
			array(
				'taxonomy' => 'blog_category',
				'field'    => 'term_id',
				'terms'    => $category_ids,
			),
		),
		'orderby'        => 'rand',
		'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
	);

	$query = new WP_Query( $args );

	return $query->posts;
}

/**
 * Get featured blog post
 *
 * @since 1.1.0
 * @return WP_Post|null Featured post or null
 */
function medici_get_featured_blog_post(): ?WP_Post {
	// Check for manually selected featured post
	$featured_post_id = (int) get_option( 'medici_blog_featured_post_id', 0 );

	if ( $featured_post_id > 0 ) {
		$featured_post = get_post( $featured_post_id );

		if ( $featured_post &&
			'medici_blog' === $featured_post->post_type &&
			'publish' === $featured_post->post_status ) {
			return $featured_post;
		}
	}

	// Try to get latest featured article
	$featured_query = new WP_Query(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_key'       => '_medici_featured_article',
			'meta_value'     => '1',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
		)
	);

	if ( $featured_query->have_posts() ) {
		$featured_query->the_post();
		$post = get_post();
		wp_reset_postdata();
		return $post;
	}

	// Fallback: get latest published post
	$latest_query = new WP_Query(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true, // Performance: skip SQL_CALC_FOUND_ROWS
		)
	);

	if ( $latest_query->have_posts() ) {
		$latest_query->the_post();
		$post = get_post();
		wp_reset_postdata();
		return $post;
	}

	return null;
}

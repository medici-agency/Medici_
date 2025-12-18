<?php
/**
 * Blog Custom Post Type Registration
 *
 * Registers the custom post type 'medici_blog', custom taxonomy 'blog_category',
 * and handles default categories creation with schema markup support.
 *
 * @package    Medici_Agency
 * @subpackage Custom_Post_Types
 * @since      1.1.0
 * @version    1.1.1
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blog CPT Configuration Class
 *
 * Centralizes all configuration for the blog custom post type
 *
 * @since 1.1.0
 */
final class Medici_Blog_CPT_Config {

	/**
	 * Post type slug
	 */
	public const POST_TYPE = 'medici_blog';

	/**
	 * Taxonomy slug
	 */
	public const TAXONOMY = 'blog_category';

	/**
	 * Text domain
	 */
	public const TEXT_DOMAIN = 'medici.agency';

	/**
	 * Get post type labels
	 *
	 * @since 1.1.0
	 * @return array Post type labels
	 */
	public static function get_post_type_labels(): array {
		return array(
			'name'                  => __( 'Статті блогу', self::TEXT_DOMAIN ),
			'singular_name'         => __( 'Стаття', self::TEXT_DOMAIN ),
			'menu_name'             => __( 'Блог', self::TEXT_DOMAIN ),
			'name_admin_bar'        => __( 'Стаття блогу', self::TEXT_DOMAIN ),
			'archives'              => __( 'Архів статей', self::TEXT_DOMAIN ),
			'attributes'            => __( 'Атрибути статті', self::TEXT_DOMAIN ),
			'parent_item_colon'     => __( 'Батьківська стаття:', self::TEXT_DOMAIN ),
			'all_items'             => __( 'Всі статті', self::TEXT_DOMAIN ),
			'add_new_item'          => __( 'Додати нову статтю', self::TEXT_DOMAIN ),
			'add_new'               => __( 'Додати статтю', self::TEXT_DOMAIN ),
			'new_item'              => __( 'Нова стаття', self::TEXT_DOMAIN ),
			'edit_item'             => __( 'Редагувати статтю', self::TEXT_DOMAIN ),
			'update_item'           => __( 'Оновити статтю', self::TEXT_DOMAIN ),
			'view_item'             => __( 'Переглянути статтю', self::TEXT_DOMAIN ),
			'view_items'            => __( 'Переглянути статті', self::TEXT_DOMAIN ),
			'search_items'          => __( 'Шукати статті', self::TEXT_DOMAIN ),
			'not_found'             => __( 'Статей не знайдено', self::TEXT_DOMAIN ),
			'not_found_in_trash'    => __( 'Статей не знайдено в кошику', self::TEXT_DOMAIN ),
			'featured_image'        => __( 'Зображення статті', self::TEXT_DOMAIN ),
			'set_featured_image'    => __( 'Встановити зображення статті', self::TEXT_DOMAIN ),
			'remove_featured_image' => __( 'Видалити зображення', self::TEXT_DOMAIN ),
			'use_featured_image'    => __( 'Використати як зображення статті', self::TEXT_DOMAIN ),
			'insert_into_item'      => __( 'Вставити в статтю', self::TEXT_DOMAIN ),
			'uploaded_to_this_item' => __( 'Завантажено до цієї статті', self::TEXT_DOMAIN ),
			'items_list'            => __( 'Список статей', self::TEXT_DOMAIN ),
			'items_list_navigation' => __( 'Навігація по статтях', self::TEXT_DOMAIN ),
			'filter_items_list'     => __( 'Фільтрувати список статей', self::TEXT_DOMAIN ),
		);
	}

	/**
	 * Get post type arguments
	 *
	 * @since 1.1.0
	 * @return array Post type arguments
	 */
	public static function get_post_type_args(): array {
		return array(
			'label'               => __( 'Стаття', self::TEXT_DOMAIN ),
			'description'         => __( 'Статті блогу Medici Agency', self::TEXT_DOMAIN ),
			'labels'              => self::get_post_type_labels(),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
			'taxonomies'          => array( self::TAXONOMY ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-book-alt',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'rewrite'             => array(
				'slug'       => 'blog',
				'with_front' => false,
			),
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true, // Gutenberg + GenerateBlocks support
		);
	}

	/**
	 * Get taxonomy labels
	 *
	 * @since 1.1.0
	 * @return array Taxonomy labels
	 */
	public static function get_taxonomy_labels(): array {
		return array(
			'name'                       => __( 'Категорії блогу', self::TEXT_DOMAIN ),
			'singular_name'              => __( 'Категорія', self::TEXT_DOMAIN ),
			'menu_name'                  => __( 'Категорії', self::TEXT_DOMAIN ),
			'all_items'                  => __( 'Всі категорії', self::TEXT_DOMAIN ),
			'parent_item'                => __( 'Батьківська категорія', self::TEXT_DOMAIN ),
			'parent_item_colon'          => __( 'Батьківська категорія:', self::TEXT_DOMAIN ),
			'new_item_name'              => __( 'Нова категорія', self::TEXT_DOMAIN ),
			'add_new_item'               => __( 'Додати нову категорію', self::TEXT_DOMAIN ),
			'edit_item'                  => __( 'Редагувати категорію', self::TEXT_DOMAIN ),
			'update_item'                => __( 'Оновити категорію', self::TEXT_DOMAIN ),
			'view_item'                  => __( 'Переглянути категорію', self::TEXT_DOMAIN ),
			'separate_items_with_commas' => __( 'Розділіть категорії комами', self::TEXT_DOMAIN ),
			'add_or_remove_items'        => __( 'Додати або видалити категорії', self::TEXT_DOMAIN ),
			'choose_from_most_used'      => __( 'Обрати з найбільш використовуваних', self::TEXT_DOMAIN ),
			'popular_items'              => __( 'Популярні категорії', self::TEXT_DOMAIN ),
			'search_items'               => __( 'Шукати категорії', self::TEXT_DOMAIN ),
			'not_found'                  => __( 'Категорій не знайдено', self::TEXT_DOMAIN ),
			'no_terms'                   => __( 'Немає категорій', self::TEXT_DOMAIN ),
			'items_list'                 => __( 'Список категорій', self::TEXT_DOMAIN ),
			'items_list_navigation'      => __( 'Навігація по категоріях', self::TEXT_DOMAIN ),
		);
	}

	/**
	 * Get taxonomy arguments
	 *
	 * @since 1.1.0
	 * @return array Taxonomy arguments
	 */
	public static function get_taxonomy_args(): array {
		return array(
			'labels'            => self::get_taxonomy_labels(),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
			'rewrite'           => array(
				'slug'       => 'blog-category',
				'with_front' => false,
			),
			'show_in_rest'      => true, // Gutenberg support
		);
	}

	/**
	 * Get default categories
	 *
	 * @since 1.1.0
	 * @return array Default categories data
	 */
	public static function get_default_categories(): array {
		return array(
			array(
				'name'        => __( 'Медичний маркетинг', self::TEXT_DOMAIN ),
				'slug'        => 'marketing',
				'description' => __( 'Статті про медичний маркетинг, SMM, реклама медичних послуг', self::TEXT_DOMAIN ),
			),
			array(
				'name'        => __( 'Юридична експертиза', self::TEXT_DOMAIN ),
				'slug'        => 'legal',
				'description' => __( 'Юридичні аспекти медичної реклами та маркетингу', self::TEXT_DOMAIN ),
			),
			array(
				'name'        => __( 'Кейси', self::TEXT_DOMAIN ),
				'slug'        => 'case',
				'description' => __( 'Реальні приклади успішних проєктів у медичному маркетингу', self::TEXT_DOMAIN ),
			),
			array(
				'name'        => __( 'SEO та контент', self::TEXT_DOMAIN ),
				'slug'        => 'seo',
				'description' => __( 'SEO оптимізація та контент-маркетинг для медичних сайтів', self::TEXT_DOMAIN ),
			),
			array(
				'name'        => 'SMM',
				'slug'        => 'smm',
				'description' => __( 'Соціальні мережі для медичного бізнесу', self::TEXT_DOMAIN ),
			),
		);
	}
}

/**
 * Register Custom Post Type 'Blog'
 *
 * @since 1.0.0
 * @return void
 */
function medici_register_blog_cpt(): void {
	register_post_type(
		Medici_Blog_CPT_Config::POST_TYPE,
		Medici_Blog_CPT_Config::get_post_type_args()
	);
}
add_action( 'init', 'medici_register_blog_cpt', 0 );

/**
 * Register Custom Taxonomy 'Blog Categories'
 *
 * @since 1.0.0
 * @return void
 */
function medici_register_blog_taxonomy(): void {
	register_taxonomy(
		Medici_Blog_CPT_Config::TAXONOMY,
		array( Medici_Blog_CPT_Config::POST_TYPE ),
		Medici_Blog_CPT_Config::get_taxonomy_args()
	);
}
add_action( 'init', 'medici_register_blog_taxonomy', 0 );

/**
 * Create default blog categories
 *
 * Викликається при активації (див. нижче) після реєстрації таксономії.
 *
 * @since 1.0.0
 * @return void
 */
function medici_create_default_blog_categories(): void {
	// Guarantee taxonomy is registered
	if ( ! taxonomy_exists( Medici_Blog_CPT_Config::TAXONOMY ) ) {
		medici_register_blog_taxonomy();
	}

	$existing_terms = get_terms(
		array(
			'taxonomy'   => Medici_Blog_CPT_Config::TAXONOMY,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $existing_terms ) || ! empty( $existing_terms ) ) {
		return;
	}

	foreach ( Medici_Blog_CPT_Config::get_default_categories() as $category ) {
		wp_insert_term(
			$category['name'],
			Medici_Blog_CPT_Config::TAXONOMY,
			array(
				'slug'        => $category['slug'],
				'description' => $category['description'],
			)
		);
	}
}

/**
 * Flush rewrite rules and create defaults on activation
 *
 * Використовуйте цей hook у головному файлі плагіна.
 *
 * @since 1.0.0
 * @return void
 */
function medici_flush_rewrite_rules_on_activation(): void {
	medici_register_blog_cpt();
	medici_register_blog_taxonomy();
	medici_create_default_blog_categories();
	flush_rewrite_rules();
}
if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook( __FILE__, 'medici_flush_rewrite_rules_on_activation' );
}

/**
 * Add BlogPosting schema markup for single blog posts
 *
 * Outputs JSON-LD structured data for blog posts to improve SEO.
 * Compatible with Yoast SEO and RankMath (won't duplicate schema).
 *
 * @since 1.0.0
 * @return void
 */
function medici_blog_schema_markup(): void {
	// Only for single blog posts
	if ( ! is_singular( Medici_Blog_CPT_Config::POST_TYPE ) ) {
		return;
	}

	// Skip if Yoast or RankMath is handling schema
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$schema = medici_build_blog_schema( (int) $post_id );

	if ( empty( $schema ) ) {
		return;
	}

	// Output JSON-LD
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'medici_blog_schema_markup' );

/**
 * Build blog post schema data
 *
 * @since 1.1.0
 * @param int $post_id Post ID
 * @return array Schema data
 */
function medici_build_blog_schema( int $post_id ): array {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return array();
	}

	// Get post data
	$post_title    = get_the_title( $post_id );
	$post_excerpt  = get_the_excerpt( $post_id );
	$post_content  = wp_strip_all_tags( get_the_content( null, false, $post ) );
	$post_url      = get_permalink( $post_id );
	$post_image    = get_the_post_thumbnail_url( $post_id, 'full' );
	$post_date     = get_the_date( 'c', $post_id ); // ISO 8601
	$post_modified = get_the_modified_date( 'c', $post_id );

	// Get custom meta fields
	$reading_time     = (int) get_post_meta( $post_id, '_medici_reading_time', true );
	$author_name      = get_post_meta( $post_id, '_medici_author_name', true );
	$publication_date = get_post_meta( $post_id, '_medici_publication_date', true );

	// Get author info
	if ( empty( $author_name ) ) {
		$author_name = get_the_author_meta( 'display_name', (int) $post->post_author );
	}

	// Get category
	$categories = get_the_terms( $post_id, Medici_Blog_CPT_Config::TAXONOMY );
	$category   = ( $categories && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';

	// Use custom publication date if set
	if ( ! empty( $publication_date ) ) {
		$timestamp = strtotime( $publication_date );
		if ( false !== $timestamp ) {
			$post_date = gmdate( 'c', $timestamp );
		}
	}

	// Build schema
	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => $post_title,
		'description'      => ! empty( $post_excerpt ) ? $post_excerpt : wp_trim_words( $post_content, 25 ),
		'url'              => $post_url,
		'datePublished'    => $post_date,
		'dateModified'     => $post_modified,
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author_name,
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url(),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => get_theme_file_uri( 'img/logo.svg' ),
			),
		),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => $post_url,
		),
	);

	// Add image if exists
	if ( $post_image ) {
		$schema['image'] = array(
			'@type' => 'ImageObject',
			'url'   => $post_image,
		);
	}

	// Add category as articleSection
	if ( ! empty( $category ) ) {
		$schema['articleSection'] = $category;
	}

	// Add reading time as timeRequired (ISO 8601 duration)
	if ( $reading_time > 0 ) {
		$schema['timeRequired'] = 'PT' . $reading_time . 'M'; // e.g., PT5M = 5 minutes
	}

	// Add word count (Unicode-aware)
	$word_count = 0;
	if ( preg_match_all( '/\p{L}+/u', $post_content, $matches ) ) {
		$word_count = count( $matches[0] );
	}
	if ( $word_count > 0 ) {
		$schema['wordCount'] = $word_count;
	}

	return $schema;
}

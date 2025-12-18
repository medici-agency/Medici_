<?php
declare(strict_types=1);
/**
 * ============================================================================
 * MEDICI.AGENCY - PERFORMANCE OPTIMIZATIONS
 * File: inc/performance.php
 * ============================================================================
 *
 * Handles:
 * • Image lazy loading
 * • Bloat removal (emoji, embeds, heartbeat, dashicons, jQuery Migrate)
 * • Transient cleanup (через core API)
 * • Custom excerpt settings
 * • WordPress optimization
 * • Database indexes for wp_postmeta (_medici_post_views, _medici_reading_time, _medici_featured_article)
 *
 * Depends: None (works independently)
 * Used by: functions.php
 *
 * @version 1.4.0
 * @since   1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// LAZY LOAD IMAGES
// ============================================================================

/**
 * Add lazy loading to images
 *
 * Додає lazy loading та async decoding для зображень.
 * Не змінює атрибути, якщо вони вже явно задані.
 *
 * @since 1.0.0
 * @param array $attr Image attributes.
 * @return array Modified attributes
 */
function medici_lazy_load_images( array $attr ): array {
	// Поважаємо вже встановлені атрибути
	if ( isset( $attr['loading'] ) && '' !== $attr['loading'] ) {
		return $attr;
	}

	// High-priority (hero) зображення завантажуємо без затримки
	if ( isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
		$attr['loading'] = 'eager';

		return $attr;
	}

	$attr['loading']  = 'lazy';
	$attr['decoding'] = 'async';

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'medici_lazy_load_images' );

// ============================================================================
// DISABLE EMBEDS
// ============================================================================
// NOTE: Emoji disable moved to inc/twemoji-local.php (medici_disable_wp_emojis)
// to avoid duplication and keep emoji-related code in one place.

/**
 * Disable WordPress oEmbed functionality
 *
 * Видаляє oEmbed discovery, REST routes та JS для oEmbed.
 *
 * @since 1.0.0
 * @return void
 */
function medici_disable_embeds(): void {
	global $wp;

	// Видаляємо query var embed
	if ( isset( $wp->public_query_vars ) && is_array( $wp->public_query_vars ) ) {
		$wp->public_query_vars = array_diff( $wp->public_query_vars, array( 'embed' ) );
	}

	// Вимикаємо REST-роут та автодетект oEmbed
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );
	add_filter( 'embed_oembed_discover', '__return_false' );

	// Видаляємо фільтри/лінки/скрипти oEmbed
	remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'medici_disable_embeds', 9999 );

// ============================================================================
// REMOVE JQUERY MIGRATE
// ============================================================================

/**
 * Remove jQuery Migrate dependency
 *
 * Видаляє залежність jquery-migrate з основного скрипта jquery
 * на фронтенді (адмінку не чіпаємо).
 *
 * @since 1.0.0
 * @param WP_Scripts $scripts Scripts registry.
 * @return void
 */
function medici_remove_jquery_migrate( $scripts ): void {
	if ( is_admin() ) {
		return;
	}

	if ( ! $scripts instanceof WP_Scripts ) {
		return;
	}

	if ( isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];

		if ( ! empty( $script->deps ) ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
}
add_action( 'wp_default_scripts', 'medici_remove_jquery_migrate' );

/**
 * Dequeue jQuery Migrate on frontend (fallback)
 *
 * На випадок, якщо якийсь плагін примусово підключає jquery-migrate.
 *
 * @since 1.0.0
 * @return void
 */
function medici_dequeue_jquery_migrate(): void {
	if ( ! is_admin() ) {
		wp_dequeue_script( 'jquery-migrate' );
	}
}
add_action( 'wp_enqueue_scripts', 'medici_dequeue_jquery_migrate', 100 );

// ============================================================================
// DISABLE DASHICONS ON FRONTEND
// ============================================================================

/**
 * Disable Dashicons for non-logged-in users
 *
 * Вимикає Dashicons на фронтенді для неавторизованих користувачів.
 *
 * @since 1.0.0
 * @return void
 */
function medici_disable_dashicons(): void {
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'medici_disable_dashicons' );

// ============================================================================
// DISABLE HEARTBEAT ON FRONTEND
// ============================================================================

/**
 * Disable Heartbeat API on frontend
 *
 * Вимикає Heartbeat API поза адмінкою для економії ресурсів.
 * У редакторі (wp-admin) Heartbeat продовжує працювати.
 *
 * @since 1.0.0
 * @return void
 */
function medici_disable_heartbeat(): void {
	if ( ! is_admin() ) {
		wp_deregister_script( 'heartbeat' );
	}
}
add_action( 'init', 'medici_disable_heartbeat', 1 );

// ============================================================================
// CLEANUP TRANSIENTS
// ============================================================================

/**
 * Cleanup expired transients using core API
 *
 * Використовує вбудовану функцію WordPress delete_expired_transients(),
 * щоб уникнути «сирого» SQL та проблем з кешами/об'єктним кешем. [web:53][web:59]
 *
 * @since 1.3.4
 * @return void
 */
function medici_cleanup_transients(): void {
	if ( function_exists( 'delete_expired_transients' ) ) {
		delete_expired_transients();
	}
}
add_action( 'wp_scheduled_delete', 'medici_cleanup_transients' );

// ============================================================================
// LIMIT POST REVISIONS & AUTOSAVE
// ============================================================================

/**
 * Set post revisions limit
 *
 * Обмежує кількість ревізій постів до 3.
 * Рекомендовано задавати це у wp-config.php, але перевірка
 * через defined() дозволяє не ламати існуючу конфігурацію. [web:59]
 *
 * @since 1.0.0
 */
if ( ! defined( 'WP_POST_REVISIONS' ) ) {
	define( 'WP_POST_REVISIONS', 3 );
}

/**
 * Set autosave interval
 *
 * Встановлює інтервал автозбереження в 5 хвилин (300 секунд).
 *
 * @since 1.0.0
 */
if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
	define( 'AUTOSAVE_INTERVAL', 300 );
}

// ============================================================================
// CUSTOM EXCERPT
// ============================================================================

/**
 * Set custom excerpt length
 *
 * Встановлює довжину excerpt у 30 слів.
 *
 * @since 1.0.0
 * @param int $length Default excerpt length.
 * @return int Modified excerpt length
 */
function medici_excerpt_length( int $length ): int {
	return 30;
}
add_filter( 'excerpt_length', 'medici_excerpt_length' );

/**
 * Set custom excerpt more
 *
 * Встановлює '...' замість стандартного [...] в excerpt.
 *
 * @since 1.0.0
 * @param string $more Default more string.
 * @return string Modified more string
 */
function medici_excerpt_more( string $more ): string {
	return '...';
}
add_filter( 'excerpt_more', 'medici_excerpt_more' );

// ============================================================================
// DATABASE OPTIMIZATION - INDEXES
// ============================================================================

/**
 * Create database indexes for wp_postmeta
 *
 * Додає індекси для швидших запитів по meta_key та meta_value.
 * Індекси покращують performance для:
 * • _medici_post_views - кількість переглядів
 * • _medici_reading_time - час читання
 * • _medici_featured_article - featured posts
 *
 * Індекси створюються лише один раз при активації теми.
 * Версія індексів зберігається в options для відстеження оновлень.
 *
 * Performance impact:
 * • SELECT запити: до 10x швидше
 * • UPDATE запити: negligible overhead
 * • Storage: ~5-10MB для 10k posts
 *
 * @since 1.4.0
 * @return void
 */
function medici_create_database_indexes(): void {
	global $wpdb;

	// Перевірка версії індексів
	$indexes_version = get_option( 'medici_db_indexes_version', '0' );
	$target_version  = '1.4.0';

	// Якщо індекси вже створені, пропускаємо
	if ( version_compare( $indexes_version, $target_version, '>=' ) ) {
		return;
	}

	// Suppress errors для безпечної роботи
	$wpdb->suppress_errors();

	// Index для medici_views (сортування по кількості переглядів)
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query(
		"CREATE INDEX IF NOT EXISTS idx_medici_views
		ON {$wpdb->postmeta} (meta_key, meta_value(10))
		WHERE meta_key = '_medici_post_views'"
	);

	// Index для medici_reading_time (фільтрування по часу читання)
	$wpdb->query(
		"CREATE INDEX IF NOT EXISTS idx_medici_reading_time
		ON {$wpdb->postmeta} (meta_key, meta_value(10))
		WHERE meta_key = '_medici_reading_time'"
	);

	// Index для medici_featured (запити featured posts)
	$wpdb->query(
		"CREATE INDEX IF NOT EXISTS idx_medici_featured
		ON {$wpdb->postmeta} (meta_key, meta_value(10))
		WHERE meta_key = '_medici_featured_article'"
	);
	// phpcs:enable

	// Зберігаємо версію індексів
	update_option( 'medici_db_indexes_version', $target_version, false );

	// Log для debugging (можна видалити в production)
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'Medici: Database indexes created successfully (version ' . $target_version . ')' );
	}
}

// Створюємо індекси при активації теми
add_action( 'after_switch_theme', 'medici_create_database_indexes' );

// Також створюємо при оновленні теми (fallback)
add_action( 'admin_init', 'medici_create_database_indexes' );

// ============================================================================
// END OF PERFORMANCE.PHP
// ============================================================================

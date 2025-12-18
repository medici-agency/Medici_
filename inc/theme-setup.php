<?php
declare(strict_types=1);

/**
 * Theme Setup Module - Theme Support Configuration
 *
 * Модуль ініціалізації теми:
 * - Theme Support (thumbnails, title-tag, HTML5, тощо)
 * - Image Sizes Registration
 *
 * @package    Medici
 * @subpackage ThemeSetup
 * @since      1.0.13
 * @version    1.0.1
 *
 * Changelog:
 * 1.0.1 - 2025-12-04 - Видалено blog функціонал (blog setup, categories, schema)
 * 1.0.0 - 2025-11-25 - Initial version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * =====================================================
 * THEME SUPPORT
 * =====================================================
 */

/**
 * Setup theme support features
 *
 * Реєструє підтримку WordPress features та image sizes.
 *
 * @since 1.0.0
 * @return void
 */
function medici_theme_support(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'script',
			'style',
		)
	);

	// Custom image sizes
	add_image_size( 'featured-large', 1200, 675, true );
	add_image_size( 'featured-medium', 800, 450, true );
	add_image_size( 'featured-small', 600, 400, true );
}
add_action( 'after_setup_theme', 'medici_theme_support' );

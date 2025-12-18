<?php
declare(strict_types=1);

/**
 * Twemoji Local - Local Twemoji Integration
 *
 * Підключає локальну копію Twemoji для відображення всіх емоджі як SVG картинок.
 * Повне покриття Unicode emoji стандарту (4009 емоджі).
 *
 * @package    Medici
 * @subpackage Core/Twemoji
 * @since      1.3.4
 * @version    1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// DISABLE WordPress DEFAULT EMOJI
// ============================================================================

/**
 * Вимкнути стандартні WordPress emoji скрипти
 *
 * WordPress за замовчуванням конвертує емоджі в свої власні зображення.
 * Це заважає Twemoji обробляти емоджі, тому потрібно вимкнути.
 */
function medici_disable_wp_emojis(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'medici_disable_wp_emojis' );

// ============================================================================
// ENQUEUE TWEMOJI ASSETS
// ============================================================================

/**
 * Підключити локальний Twemoji JavaScript
 *
 * Завантажує twemoji.min.js з локального сервера замість CDN.
 * Налаштовує base path для SVG assets.
 */
function medici_enqueue_twemoji_local(): void {
	// Twemoji JavaScript library (local)
	wp_enqueue_script(
		'medici-twemoji',
		get_stylesheet_directory_uri() . '/js/twemoji/twemoji.min.js',
		array(), // No dependencies
		'15.1.0',
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	// Twemoji initialization script - runs AFTER twemoji.min.js loads
	$twemoji_init = "
		(function() {
			// Wait for DOM to be ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initTwemoji);
			} else {
				initTwemoji();
			}

			function initTwemoji() {
				if (typeof twemoji !== 'undefined') {
					var baseUrl = '" . esc_js( get_stylesheet_directory_uri() . '/assets/twemoji/' ) . "';

					twemoji.parse(document.body, {
						folder: 'svg',
						ext: '.svg',
						base: baseUrl
					});
				}
			}
		})();
	";

	wp_add_inline_script( 'medici-twemoji', $twemoji_init, 'after' );

	// Twemoji CSS styles
	$twemoji_css = '
		img.emoji {
			height: 1em;
			width: 1em;
			margin: 0 0.05em 0 0.1em;
			vertical-align: -0.1em;
			display: inline-block;
		}
	';
	wp_add_inline_style( 'medici-child-theme', $twemoji_css );
}
add_action( 'wp_enqueue_scripts', 'medici_enqueue_twemoji_local', 10 );

// ============================================================================
// TWEMOJI CONFIGURATION
// ============================================================================

/**
 * Отримати Twemoji base URL для локальних assets
 *
 * @return string Base URL для Twemoji SVG файлів
 */
function medici_get_twemoji_base_url(): string {
	return get_stylesheet_directory_uri() . '/assets/twemoji/';
}

/**
 * Отримати Twemoji base path для серверних файлів
 *
 * @return string Base path для Twemoji SVG файлів
 */
function medici_get_twemoji_base_path(): string {
	return get_stylesheet_directory() . '/assets/twemoji/';
}

/**
 * Перевірити чи існують локальні Twemoji assets
 *
 * @return bool True якщо SVG директорія існує
 */
function medici_twemoji_assets_exist(): bool {
	$svg_path = medici_get_twemoji_base_path() . 'svg/';
	return file_exists( $svg_path ) && is_dir( $svg_path );
}

// ============================================================================
// ADMIN NOTICE
// ============================================================================

/**
 * Показати admin notice якщо Twemoji assets не знайдені
 */
function medici_twemoji_admin_notice(): void {
	if ( ! medici_twemoji_assets_exist() ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong>Medici Theme:</strong>
				Twemoji SVG assets не знайдені в <code>/assets/twemoji/svg/</code>.
				Емоджі не будуть відображатись коректно.
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'medici_twemoji_admin_notice' );

// ============================================================================
// DEBUG HELPER
// ============================================================================

/**
 * Отримати інформацію про Twemoji систему (для debug)
 *
 * @return array<string, mixed> Twemoji system info
 */
function medici_get_twemoji_info(): array {
	$svg_path = medici_get_twemoji_base_path() . 'svg/';

	return array(
		'base_url'     => medici_get_twemoji_base_url(),
		'base_path'    => medici_get_twemoji_base_path(),
		'svg_path'     => $svg_path,
		'assets_exist' => medici_twemoji_assets_exist(),
		'svg_count'    => medici_twemoji_assets_exist()
			? count( glob( $svg_path . '*.svg' ) )
			: 0,
		'version'      => '15.1.0',
	);
}

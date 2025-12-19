<?php
declare(strict_types=1);

/**
 * ============================================================================
 * MEDICI.AGENCY - ASSETS & PERFORMANCE OPTIMIZATION (FIXED)
 * File: inc/assets.php
 * ============================================================================
 *
 * Handles:
 * • Critical CSS loading
 * • Fonts preload (with crossorigin attribute)
 * • Local fonts @font-face declarations
 * • Script enqueuing (modular architecture)
 * • Script deferring (GeneratePress compatible)
 * • Resource hints management
 * • Google Fonts removal
 * • Exit-intent overlay popup (GenerateBlocks Pro 2.3+ Overlay Panel)
 *
 * @version 2.0.0
 * @since   1.3.2
 * @changelog 2.0.0 - Рефакторинг до GenerateBlocks Overlay Panel (exit-intent-overlay.css/js)
 * @changelog 1.5.2 - Видалено defer для medici-events та medici-exit-intent (dependency conflict)
 * @changelog 1.5.1 - Видалено wp_is_mobile() для exit-intent (JS сам перевіряє ширину екрану)
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// CONSTANTS SETUP
// ============================================================================

if ( ! defined( 'MEDICI_VERSION' ) ) {
	define( 'MEDICI_VERSION', '2.0.0' );
}

if ( ! defined( 'MEDICI_THEME_DIR' ) ) {
	define( 'MEDICI_THEME_DIR', get_stylesheet_directory() );
}

if ( ! defined( 'MEDICI_THEME_URI' ) ) {
	define( 'MEDICI_THEME_URI', get_stylesheet_directory_uri() );
}

// ============================================================================
// CRITICAL CSS INLINING
// ============================================================================

/**
 * Output critical CSS inline in <head>
 *
 * @return void
 */
function medici_critical_css(): void {
	$critical_css_path = MEDICI_THEME_DIR . '/css/critical.css';

	if ( ! file_exists( $critical_css_path ) ) {
		return;
	}

	$critical_css = file_get_contents( $critical_css_path );
	if ( false === $critical_css || '' === trim( $critical_css ) ) {
		return;
	}

	// CSS-файл контролюється темою, додаткове фільтрування не потрібне
	echo "<style id=\"medici-critical\">\n" . $critical_css . "\n</style>\n";
}
add_action( 'wp_head', 'medici_critical_css', 1 );

// ============================================================================
// ENQUEUE STYLES & SCRIPTS
// ============================================================================

/**
 * Enqueue theme styles and scripts
 *
 * @return void
 */
function medici_enqueue_assets(): void {
	$theme_dir = MEDICI_THEME_DIR;
	$theme_uri = MEDICI_THEME_URI;

	// Parent theme CSS
	$parent_theme   = wp_get_theme()->parent();
	$parent_version = $parent_theme ? $parent_theme->get( 'Version' ) : '1.0.0';

	wp_enqueue_style(
		'generate-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		$parent_version
	);

	// ===== CORE CSS =====
	$core_styles = array(
		'medici-variables' => '/css/core/variables.css',
		'medici-core'      => '/css/core/core.css',
	);

	$last_handle = 'generate-parent-style';

	foreach ( $core_styles as $handle => $path ) {
		$file_path = $theme_dir . $path;

		if ( file_exists( $file_path ) ) {
			wp_enqueue_style(
				$handle,
				$theme_uri . $path,
				array( $last_handle ),
				filemtime( $file_path )
			);
			$last_handle = $handle;
		}
	}

	// ===== COMPONENTS CSS (Always loaded) =====
	$component_styles = array(
		'medici-buttons'    => '/css/components/buttons.css',
		'medici-sections'   => '/css/components/sections.css',
		'medici-navigation' => '/css/components/navigation.css',
		'medici-lazy-load'  => '/css/components/lazy-load.css',
		'medici-animations' => '/css/components/animations.css',
	);

	foreach ( $component_styles as $handle => $path ) {
		$file_path = $theme_dir . $path;

		if ( file_exists( $file_path ) ) {
			wp_enqueue_style(
				$handle,
				$theme_uri . $path,
				array( $last_handle ),
				filemtime( $file_path )
			);
			$last_handle = $handle;
		}
	}

	// ===== CARDS CSS (Conditional - only on pages with cards) =====
	// Cards used on: homepage, services, single posts (NOT blog archive - uses blog-card classes)
	if ( is_front_page() || is_page( array( 'services', 'послуги' ) ) || is_singular( 'medici_blog' ) ) {
		$cards_css_path = $theme_dir . '/css/components/cards.css';

		if ( file_exists( $cards_css_path ) ) {
			wp_enqueue_style(
				'medici-cards',
				$theme_uri . '/css/components/cards.css',
				array( $last_handle ),
				filemtime( $cards_css_path )
			);
			$last_handle = 'medici-cards';
		}
	}

	// ===== FAQ CSS (Conditional - only on homepage or FAQ page) =====
	if ( is_front_page() || is_page( array( 'faq', 'faq-page', 'питання-відповіді' ) ) ) {
		$faq_css_path = $theme_dir . '/css/components/faq.css';

		if ( file_exists( $faq_css_path ) ) {
			wp_enqueue_style(
				'medici-faq',
				$theme_uri . '/css/components/faq.css',
				array( $last_handle ),
				filemtime( $faq_css_path )
			);
			$last_handle = 'medici-faq';
		}
	}

	// ===== LAYOUT CSS =====
	$layout_styles = array(
		'medici-layout' => '/css/layout/layout.css',
	);

	foreach ( $layout_styles as $handle => $path ) {
		$file_path = $theme_dir . $path;

		if ( file_exists( $file_path ) ) {
			wp_enqueue_style(
				$handle,
				$theme_uri . $path,
				array( $last_handle ),
				filemtime( $file_path )
			);
			$last_handle = $handle;
		}
	}

	// ===== WIDGETS CSS (Conditional - only if sidebars are active) =====
	if ( is_active_sidebar( 'sidebar-1' ) || is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) {
		$widgets_css_path = $theme_dir . '/inc/widgets/widget-styles.css';

		if ( file_exists( $widgets_css_path ) ) {
			wp_enqueue_style(
				'medici-widgets',
				$theme_uri . '/inc/widgets/widget-styles.css',
				array( $last_handle ),
				filemtime( $widgets_css_path )
			);
			$last_handle = 'medici-widgets';
		}
	}

	// ===== MAIN THEME CSS =====
	$child_css_path = $theme_dir . '/style.css';
	$css_ver        = file_exists( $child_css_path ) ? filemtime( $child_css_path ) : MEDICI_VERSION;

	wp_enqueue_style(
		'medici-child-theme',
		get_stylesheet_directory_uri() . '/style.css',
		array( $last_handle ),
		$css_ver
	);

	// ===== FORMS CSS (Conditional - only on pages with forms) =====
	// Forms used on: contact page, consultation page, single posts (comments), pages with shortcodes
	// Also loaded on desktop for exit-intent popup
	$has_forms = is_page( array( 'contact', 'контакти', 'consultation', 'консультація' ) )
		|| is_singular( 'medici_blog' )  // Single blog (newsletter form in sidebar)
		|| is_singular( 'post' )         // Regular posts
		|| is_page_template( 'contact-template.php' )
		|| ( ! wp_is_mobile() && is_front_page() ); // Exit-intent popup on homepage (desktop)

	// Also load forms.css on ALL desktop pages for exit-intent popup
	$exit_intent_active = ! wp_is_mobile();

	if ( $has_forms || $exit_intent_active ) {
		$forms_css_path = $theme_dir . '/css/components/forms.css';

		if ( file_exists( $forms_css_path ) ) {
			wp_enqueue_style(
				'medici-forms',
				$theme_uri . '/css/components/forms.css',
				array( 'medici-child-theme' ),
				filemtime( $forms_css_path )
			);
		}
	}

	// ===== BLOG MODULE CSS & JS (Conditional) =====
	if ( is_post_type_archive( 'medici_blog' ) || is_singular( 'medici_blog' ) || is_tax( 'blog_category' ) ) {
		$blog_css_path = $theme_dir . '/css/modules/blog/blog-new.css';

		if ( file_exists( $blog_css_path ) ) {
			wp_enqueue_style(
				'medici-blog',
				$theme_uri . '/css/modules/blog/blog-new.css',
				array( 'medici-child-theme' ),
				filemtime( $blog_css_path )
			);
		}

		// FIX: JS повинен бути в /js/, а не в /css/
		$blog_js_path = $theme_dir . '/js/modules/blog/blog-new.js';

		if ( file_exists( $blog_js_path ) ) {
			wp_enqueue_script(
				'medici-blog',
				$theme_uri . '/js/modules/blog/blog-new.js',
				array(),
				filemtime( $blog_js_path ),
				true
			);
		}

		// Single Post CSS & JS
		if ( is_singular( 'medici_blog' ) ) {
			$blog_single_css_path = $theme_dir . '/css/modules/blog/blog-single.css';

			if ( file_exists( $blog_single_css_path ) ) {
				wp_enqueue_style(
					'medici-blog-single',
					$theme_uri . '/css/modules/blog/blog-single.css',
					array( 'medici-blog' ),
					filemtime( $blog_single_css_path )
				);
			}

			$blog_single_js_path = $theme_dir . '/js/modules/blog/blog-single.js';

			if ( file_exists( $blog_single_js_path ) ) {
				wp_enqueue_script(
					'medici-blog-single',
					$theme_uri . '/js/modules/blog/blog-single.js',
					array( 'medici-twemoji' ), // Dependency: чекати поки Twemoji завантажиться
					filemtime( $blog_single_js_path ),
					true
				);
			}
		}
	}

	// ===== MODULE LOADER (Code Splitting) =====
	$module_loader_path = $theme_dir . '/js/module-loader.js';

	if ( file_exists( $module_loader_path ) ) {
		wp_enqueue_script(
			'medici-module-loader',
			get_stylesheet_directory_uri() . '/js/module-loader.js',
			array(), // No dependencies - loads first
			filemtime( $module_loader_path ),
			false // Load in head for early module loading
		);

		// Add module strategy attribute
		add_filter(
			'script_loader_tag',
			function ( $tag, $handle ) {
				if ( 'medici-module-loader' === $handle ) {
					return str_replace( '<script', '<script type="module" defer', $tag );
				}
				return $tag;
			},
			10,
			2
		);
	}

	// ===== MAIN SCRIPT =====
	$child_js_path = $theme_dir . '/js/scripts.js';
	$js_ver        = file_exists( $child_js_path ) ? filemtime( $child_js_path ) : MEDICI_VERSION;

	wp_enqueue_script(
		'medici-app',
		get_stylesheet_directory_uri() . '/js/scripts.js',
		array(),
		$js_ver,
		true
	);

	// ===== EVENTS API =====
	$events_js_path = $theme_dir . '/js/events.js';

	if ( file_exists( $events_js_path ) ) {
		wp_enqueue_script(
			'medici-events',
			get_stylesheet_directory_uri() . '/js/events.js',
			array( 'medici-app' ), // Dependency: medici-app must load first
			filemtime( $events_js_path ),
			true
		);
	}

	// ===== EVENTS FORMS =====
	$forms_newsletter_path   = $theme_dir . '/js/forms-newsletter.js';
	$forms_consultation_path = $theme_dir . '/js/forms-consultation.js';

	if ( file_exists( $forms_newsletter_path ) ) {
		wp_enqueue_script(
			'medici-forms-newsletter',
			get_stylesheet_directory_uri() . '/js/forms-newsletter.js',
			array( 'medici-events' ),
			filemtime( $forms_newsletter_path ),
			true
		);
	}

	if ( file_exists( $forms_consultation_path ) ) {
		wp_enqueue_script(
			'medici-forms-consultation',
			get_stylesheet_directory_uri() . '/js/forms-consultation.js',
			array( 'medici-events' ),
			filemtime( $forms_consultation_path ),
			true
		);
	}

	// ===== FAQ ACCORDION =====
	$faq_js_path = $theme_dir . '/js/faq-accordion.js';

	if ( file_exists( $faq_js_path ) ) {
		wp_enqueue_script(
			'medici-faq-accordion',
			get_stylesheet_directory_uri() . '/js/faq-accordion.js',
			array(),
			filemtime( $faq_js_path ),
			true
		);
	}

	// ===== LAZY LOAD (Intersection Observer API) =====
	$lazy_load_js_path = $theme_dir . '/js/lazy-load.js';

	if ( file_exists( $lazy_load_js_path ) ) {
		wp_enqueue_script(
			'medici-lazy-load',
			get_stylesheet_directory_uri() . '/js/lazy-load.js',
			array(), // No dependencies - standalone module
			filemtime( $lazy_load_js_path ),
			true // Load in footer for better performance
		);
	}

	// ===== EXIT-INTENT POPUP =====
	// Moved to OOP architecture (inc/exit-intent/class-exit-intent.php)
	// Initialized in functions.php

	// Pass data to JavaScript
	$env = defined( 'MEDICI_ENV' ) ? MEDICI_ENV : 'production';

	wp_localize_script(
		'medici-app',
		'mediciData',
		array(
			'version'     => MEDICI_VERSION,
			'environment' => $env,
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'medici_nonce' ),
			'eventNonce'  => wp_create_nonce( 'medici_event' ),
			'cssVersion'  => $css_ver,
			'jsVersion'   => $js_ver,
			'i18n'        => array(
				'newsletter'   => array(
					'sending'       => __( 'Відправка...', 'medici.agency' ),
					'submit'        => __( 'Підписатись', 'medici.agency' ),
					'errorEmpty'    => __( 'Будь ласка, введіть email', 'medici.agency' ),
					'errorInvalid'  => __( 'Будь ласка, введіть коректний email', 'medici.agency' ),
					'errorGeneral'  => __( 'Сталася помилка. Спробуйте ще раз.', 'medici.agency' ),
					'successSuffix' => __( 'Перевірте вашу пошту.', 'medici.agency' ),
				),
				'consultation' => array(
					'sending'           => __( 'Відправка...', 'medici.agency' ),
					'submit'            => __( 'Відправити', 'medici.agency' ),
					'errorName'         => __( 'Будь ласка, вкажіть ваше ім\'я', 'medici.agency' ),
					'errorEmail'        => __( 'Будь ласка, вкажіть email', 'medici.agency' ),
					'errorEmailInvalid' => __( 'Будь ласка, вкажіть коректний email', 'medici.agency' ),
					'errorPhone'        => __( 'Будь ласка, вкажіть номер телефону', 'medici.agency' ),
					'errorPhoneInvalid' => __( 'Будь ласка, вкажіть коректний номер телефону', 'medici.agency' ),
					'errorConsent'      => __( 'Для відправки потрібна ваша згода на обробку персональних даних', 'medici.agency' ),
					'errorGeneral'      => __( 'Сталася помилка. Спробуйте ще раз.', 'medici.agency' ),
				),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'medici_enqueue_assets' );

// ============================================================================
// PRELOAD CRITICAL RESOURCES (Fonts with crossorigin)
// ============================================================================

/**
 * Preload critical resources for performance optimization
 *
 * @return void
 */
function medici_preload_critical_assets(): void {
	$base = MEDICI_THEME_URI . '/fonts/';

	// Preload fonts with crossorigin attribute (only regular and 600 for above-the-fold)
	echo '<link rel="preload" as="font" href="' . esc_url( $base . 'montserrat-regular.woff2' ) . '" type="font/woff2" crossorigin="anonymous" />' . "\n";
	echo '<link rel="preload" as="font" href="' . esc_url( $base . 'montserrat-600.woff2' ) . '" type="font/woff2" crossorigin="anonymous" />' . "\n";

	// Preload critical CSS (core styles needed for above-the-fold)
	echo '<link rel="preload" as="style" href="' . esc_url( MEDICI_THEME_URI . '/css/core/variables.css' ) . '" />' . "\n";
	echo '<link rel="preload" as="style" href="' . esc_url( MEDICI_THEME_URI . '/css/core/core.css' ) . '" />' . "\n";
}
add_action( 'wp_head', 'medici_preload_critical_assets', 2 );

/**
 * Add resource hints for external resources
 *
 * @return void
 */
function medici_add_resource_hints(): void {
	// DNS Prefetch for common external services (analytics, etc)
	$dns_prefetch = array(
		'www.google-analytics.com',
		'www.googletagmanager.com',
	);

	foreach ( $dns_prefetch as $domain ) {
		echo '<link rel="dns-prefetch" href="//' . esc_attr( $domain ) . '" />' . "\n";
	}

	// Preconnect for critical third-party resources
	$preconnect = array(
		'https://www.google-analytics.com',
	);

	foreach ( $preconnect as $url ) {
		echo '<link rel="preconnect" href="' . esc_url( $url ) . '" crossorigin />' . "\n";
	}
}
add_action( 'wp_head', 'medici_add_resource_hints', 1 );

// ============================================================================
// LOCAL FONTS (Montserrat with inline styles)
// ============================================================================

/**
 * Output local Montserrat font-face declarations
 *
 * @return void
 */
function medici_local_fonts(): void {
	$base = MEDICI_THEME_URI . '/fonts/';
	?>
	<style id="medici-fonts">
		@font-face {
			font-family: 'Montserrat';
			src: url('<?php echo esc_url( $base . 'montserrat-regular.woff2' ); ?>') format('woff2');
			font-weight: 400;
			font-display: swap;
		}
		@font-face {
			font-family: 'Montserrat';
			src: url('<?php echo esc_url( $base . 'montserrat-600.woff2' ); ?>') format('woff2');
			font-weight: 600;
			font-display: swap;
		}
		@font-face {
			font-family: 'Montserrat';
			src: url('<?php echo esc_url( $base . 'montserrat-700.woff2' ); ?>') format('woff2');
			font-weight: 700;
			font-display: swap;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'medici_local_fonts', 5 );

// ============================================================================
// DEFER SCRIPTS (GeneratePress compatible)
// ============================================================================

/**
 * Add defer attribute to scripts for better performance
 *
 * GeneratePress scripts excluded from defer (compatibility).
 *
 * @param string $tag    Script HTML tag.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 * @return string Modified script tag with defer attribute
 */
function medici_defer_scripts( string $tag, string $handle, string $src ): string {
	if ( is_admin() ) {
		return $tag;
	}

	// Scripts that should NOT have defer
	$no_defer_handles = array(
		// WordPress Core
		'wp-polyfill',
		'wp-i18n',
		'wp-api-fetch',
		'wp-a11y',
		'wp-data',
		'wp-keycodes',
		'wp-date',
		'wp-rich-text',
		'wp-components',
		'wp-element',
		'wp-blocks',
		'wp-editor',
		'wp-block-library',
		'wp-block-editor',
		'wp-hooks',
		'wp-dom-ready',
		'moment',
		'lodash',
		'underscore',
		'jquery',
		'jquery-core',
		'jquery-migrate',
		// Admin
		'admin-bar',
		// GeneratePress
		'generate-main',
		'generate-smooth-scroll',
		'generate-back-to-top',
		'generate-navigation',
		'generate-menu-toggle',
		'generate-sticky',
		'generate-offside',
		'generatepress',
		'gp-premium',
		// Medici Theme - Scripts with dependencies must not have defer
		'medici-events',               // Events API - base dependency
		'medici-exit-intent-overlay', // Exit-intent overlay popup - depends on medici-events
	);

	if ( in_array( $handle, $no_defer_handles, true ) ) {
		return $tag;
	}

	// GeneratePress prefixes check
	if ( 0 === strpos( $handle, 'generate-' ) || 0 === strpos( $handle, 'gp-' ) || 0 === strpos( $handle, 'generatepress' ) ) {
		return $tag;
	}

	// Додаємо defer до всіх інших скриптів
	if ( false === strpos( $tag, ' defer ' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'medici_defer_scripts', 10, 3 );

// ============================================================================
// DISABLE GOOGLE FONTS (Use local fonts)
// ============================================================================

add_filter(
	'generate_typography_default_fonts',
	function ( array $fonts ): array {
		return array();
	}
);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		// Disable Google Fonts (using local Montserrat)
		wp_dequeue_style( 'generate-fonts' );
		wp_deregister_style( 'generate-fonts' );
		wp_dequeue_style( 'gp-premium-fonts' );
		wp_deregister_style( 'gp-premium-fonts' );

		// Disable GP Premium smooth-scroll (using native CSS scroll-behavior)
		// CSS alternative in css/core/core.css: html { scroll-behavior: smooth; }
		wp_dequeue_script( 'generate-smooth-scroll' );
		wp_deregister_script( 'generate-smooth-scroll' );
	},
	100
);

// ============================================================================
// RESOURCE HINTS MANAGEMENT
// ============================================================================

/**
 * Manage WordPress resource hints
 *
 * @param array  $urls          Resource URLs.
 * @param string $relation_type Resource hint type.
 * @return array Filtered URLs
 */
function medici_manage_resource_hints( array $urls, string $relation_type ): array {
	if ( 'dns-prefetch' === $relation_type ) {
		// Remove Google Fonts DNS prefetch (using local fonts)
		$urls = array_filter(
			$urls,
			function ( string $url ): bool {
				return false === strpos( $url, 'fonts.googleapis.com' )
					&& false === strpos( $url, 'fonts.gstatic.com' );
			}
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'medici_manage_resource_hints', 10, 2 );

// ============================================================================
// ASYNC CSS LOADING (Performance Optimization)
// ============================================================================

/**
 * List of CSS handles that should be loaded asynchronously
 *
 * Critical CSS (variables, core, navigation) remains render-blocking
 * because they're needed for above-the-fold content.
 *
 * NOTE: Blog CSS is NOT async because it's needed for above-the-fold on blog pages.
 *
 * @return array<string> List of CSS handles for async loading
 */
function medici_get_async_css_handles(): array {
	// Base async handles (truly non-critical)
	$async_handles = array(
		// Components (below-the-fold or optional)
		'medici-lazy-load',
		// 'medici-animations', // Removed from async - critical for scroll-triggered animations (initial opacity: 0 state)
		'medici-faq',
		'medici-widgets',
		// Exit-intent (loaded on demand)
		'medici-exit-intent-overlay',
	);

	// Forms are async only on pages without forms above-the-fold
	if ( ! is_page( array( 'contact', 'контакти', 'consultation', 'консультація' ) ) ) {
		$async_handles[] = 'medici-forms';
	}

	// Cards are async only on pages without cards above-the-fold
	if ( ! is_front_page() && ! is_page( array( 'services', 'послуги' ) ) ) {
		$async_handles[] = 'medici-cards';
	}

	return $async_handles;
}

/**
 * Convert render-blocking CSS to async loading
 *
 * Uses media="print" onload="this.media='all'" technique
 * for non-critical stylesheets. This technique is recommended
 * by Google and works in all modern browsers.
 *
 * @param string $tag    Style HTML tag.
 * @param string $handle Style handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media type.
 * @return string Modified style tag
 */
function medici_async_css_loading( string $tag, string $handle, string $href, string $media ): string {
	// Skip if admin
	if ( is_admin() ) {
		return $tag;
	}

	// Get async handles
	$async_handles = medici_get_async_css_handles();

	// Check if this handle should be async
	if ( ! in_array( $handle, $async_handles, true ) ) {
		return $tag;
	}

	// Convert to async loading
	// Pattern: <link rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
	// Fallback: <noscript><link rel="stylesheet"></noscript>
	$async_tag = sprintf(
		'<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" media="%s">' . "\n" .
		'<noscript><link rel="stylesheet" href="%s" media="%s"></noscript>' . "\n",
		esc_url( $href ),
		esc_attr( $media ),
		esc_url( $href ),
		esc_attr( $media )
	);

	return $async_tag;
}
add_filter( 'style_loader_tag', 'medici_async_css_loading', 10, 4 );

// ============================================================================
// BROWSER CACHING HEADERS (Performance Optimization)
// ============================================================================

/**
 * Add browser caching headers for theme assets
 *
 * Sets Cache-Control and Expires headers for static resources.
 * This helps browsers cache CSS, JS, fonts, and images.
 *
 * @return void
 */
function medici_add_cache_headers(): void {
	// Only for front-end
	if ( is_admin() ) {
		return;
	}

	// Check if this is a theme asset request
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	// Theme directory pattern
	$theme_pattern = '/wp-content/themes/medici/';

	// Check if request is for theme assets
	if ( false === strpos( $request_uri, $theme_pattern ) ) {
		return;
	}

	// Determine file type and set appropriate cache time
	$extension = strtolower( pathinfo( $request_uri, PATHINFO_EXTENSION ) );

	$cache_times = array(
		// Fonts - long cache (1 year)
		'woff'  => 31536000,
		'woff2' => 31536000,
		'ttf'   => 31536000,
		'eot'   => 31536000,
		// Images - long cache (1 year)
		'jpg'   => 31536000,
		'jpeg'  => 31536000,
		'png'   => 31536000,
		'gif'   => 31536000,
		'svg'   => 31536000,
		'webp'  => 31536000,
		'ico'   => 31536000,
		// CSS/JS - medium cache (1 week) - versioned via filemtime
		'css'   => 604800,
		'js'    => 604800,
	);

	if ( isset( $cache_times[ $extension ] ) ) {
		$max_age = $cache_times[ $extension ];

		// Set caching headers
		header( 'Cache-Control: public, max-age=' . $max_age . ', immutable' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT' );
	}
}
// Note: This hook runs early in WordPress lifecycle
// For better control, consider using .htaccess or server config
add_action( 'send_headers', 'medici_add_cache_headers' );
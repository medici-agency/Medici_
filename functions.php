<?php
declare(strict_types=1);

/**
 * ============================================================================
 * MEDICI.AGENCY - THEME FUNCTIONS.PHP
 * File: functions.php (Root theme file)
 * ============================================================================
 * 
 * Main entry point for Medici Agency theme.
 * Loads all modules from inc/ directory in correct order.
 * 
 * Module loading order (important for dependencies):
 * 1. theme-setup.php           - Theme setup & constants
 * 2. generatepress.php         - GeneratePress integration
 * 3. assets.php                - CSS/JS/fonts loading
 * 4. performance.php           - Performance optimizations
 * 5. security.php              - Security hardening
 * 6. database-optimization.php - Database indexes for performance
 * 7. class-cache-manager.php   - Object caching with Transients
 * 8. class-events.php          - Events API
 * 9. blog-cpt.php              - Custom post type registration
 * 10. blog-meta-fields.php     - Blog meta fields
 * 11. schema-medical.php       - Medical schema markup
 * 12. sitemap-optimization.php - XML Sitemap optimization
 * 13. transliteration.php      - Transliteration functions
 * 14. Other modules (auto-discovered)
 *
 * @version 2.0.0
 * @since 1.3.2
 *
 * @changelog 2.0.0 - OOP Refactoring with modern patterns:
 *   - Blog: Repository + Service + Cache (inc/blog/)
 *   - Lead: Interface + Adapter pattern (inc/lead/)
 *   - Events: Event Dispatcher + Observers (inc/events/)
 */

// ============================================================================
// PREVENT DIRECT ACCESS
// ============================================================================

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// MODULE LOADING SYSTEM
// ============================================================================

/**
 * Load theme modules in priority order
 * 
 * @return void
 */
function medici_load_modules(): void {
	$inc_dir = get_stylesheet_directory() . '/inc';

	// Module priority order (loaded first)
	$priority_modules = [
		'theme-setup.php',
		'generatepress.php',
		'assets.php',
		'performance.php',
		'security.php',
		'smtp-config.php',              // SMTP email configuration
		'database-optimization.php',
		'class-cache-manager.php',
		'analytics.php',
		// OOP Modules (v2.0.0)
		'blog/bootstrap.php',           // Blog: Repository + Service + Cache
		'lead/bootstrap.php',           // Lead: Scoring + Validation + Adapters
		'events/bootstrap.php',         // Events: Dispatcher + Observers
		'schema/bootstrap.php',         // Schema: Builder pattern
		'forms-advanced/bootstrap.php', // Forms Advanced: WPForms extension
		// Legacy modules (backwards compatibility)
		'class-events.php',
		'lead-cpt.php',
		'lead-integrations.php',
		'lead-scoring.php',
		'lead-validation.php',
		'lead-admin-settings.php',
		'zapier-integration.php',
		'dashboard-analytics.php',
		'rest-api.php',
		'widgets/widgets-init.php',
		'blog-cpt.php',
		'blog-meta-fields.php',
		'blog-cache.php',
		'blog-category-color.php',
		'blog-admin-settings.php',
		'blog-shortcodes.php',
		'blog-sidebar-settings.php',
		'blog-relevant-services.php',
		'blog-toc.php',
		'twemoji-local.php',
		'schema-medical.php',
		'sitemap-optimization.php',
		'transliteration.php',
		'class-webhook-sender.php',
		'webhook-settings.php',
		'theme-settings.php',
		
	];

	// Load priority modules in order
	foreach ( $priority_modules as $module ) {
		$module_path = $inc_dir . '/' . $module;
		if ( file_exists( $module_path ) ) {
			require_once $module_path;
		}
	}

	// Load all other .php files from inc/ directory (if any additional modules exist)
	if ( is_dir( $inc_dir ) ) {
		$files = scandir( $inc_dir );

		if ( $files && is_array( $files ) ) {
			sort( $files );

			foreach ( $files as $file ) {
				// Skip if already loaded in priority list
				if ( in_array( $file, $priority_modules, true ) ) {
					continue;
				}

				// Skip non-PHP files
				if ( '.php' !== substr( $file, -4 ) ) {
					continue;
				}

				// Skip dot files
				if ( '.' === $file[0] ) {
					continue;
				}

				$module_path = $inc_dir . '/' . $file;

				if ( file_exists( $module_path ) && is_file( $module_path ) ) {
					require_once $module_path;
				}
			}
		}
	}
}

// Execute module loader on init
add_action( 'after_setup_theme', 'medici_load_modules', 10 );

/**
 * Initialize Events API
 *
 * @since 1.4.0
 * @return void
 */
function medici_init_events_api(): void {
	if ( class_exists( 'Medici\Events' ) ) {
		\Medici\Events::init();
	}
}
add_action( 'init', 'medici_init_events_api', 5 );

/**
 * Initialize Lead CPT
 *
 * @since 1.4.0
 * @return void
 */
function medici_init_lead_cpt(): void {
	if ( class_exists( 'Medici\Lead_CPT' ) ) {
		\Medici\Lead_CPT::init();
	}
}
add_action( 'init', 'medici_init_lead_cpt', 6 );

/**
 * Initialize Exit-Intent Popup (OOP Architecture)
 *
 * HYBRID SOLUTION:
 * - bioEp (beeker1121) - exit-intent detection + cookie tracking
 * - GenerateBlocks Overlay Panel - design + animations
 * - Events API - form handling + Lead CPT integration
 *
 * @since 1.7.0
 * @return void
 */
function medici_init_exit_intent(): void {
	// Load Exit-Intent main class
	$exit_intent_path = get_stylesheet_directory() . '/inc/exit-intent/class-exit-intent.php';

	if ( file_exists( $exit_intent_path ) ) {
		require_once $exit_intent_path;

		// Initialize and run
		$exit_intent = new Exit_Intent();

		// Configure panel ID (avoid hardcoding in class constructor)
		$exit_intent->get_public()->set_config(
			array(
				'panel_id' => 'gb-overlay-424', // Data Attribute from GenerateBlocks Overlay Panel
			)
		);

		$exit_intent->run();
	}
}
add_action( 'after_setup_theme', 'medici_init_exit_intent', 15 );

/**
 * Initialize Lead Integrations
 *
 * @since 1.4.0
 * @return void
 */
function medici_init_lead_integrations(): void {
	if ( class_exists( 'Medici\Lead_Integrations' ) ) {
		\Medici\Lead_Integrations::init();
	}
}
add_action( 'init', 'medici_init_lead_integrations', 7 );

/**
 * Initialize Lead Admin Settings
 *
 * @since 1.4.0
 * @return void
 */
function medici_init_lead_admin_settings(): void {
	if ( class_exists( 'Medici\Lead_Admin_Settings' ) ) {
		\Medici\Lead_Admin_Settings::init();
	}
}
add_action( 'init', 'medici_init_lead_admin_settings', 8 );

/**
 * Initialize Zapier Integration
 *
 * @since 1.7.0
 * @return void
 */
function medici_init_zapier_integration(): void {
	if ( class_exists( 'Medici\Zapier_Integration' ) ) {
		\Medici\Zapier_Integration::init();
	}
}
add_action( 'init', 'medici_init_zapier_integration', 9 );

/**
 * Initialize Webhook Sender
 *
 * @since 1.5.0
 * @return void
 */
function medici_init_webhook_sender(): void {
	if ( class_exists( 'Medici\Webhook_Sender' ) ) {
		\Medici\Webhook_Sender::init();
	}
}
add_action( 'init', 'medici_init_webhook_sender', 10 );

/**
 * Initialize Webhook Admin Settings
 *
 * @since 1.5.0
 * @return void
 */
function medici_init_webhook_admin_settings(): void {
	if ( class_exists( 'Medici\Webhook_Admin_Settings' ) ) {
		\Medici\Webhook_Admin_Settings::init();
	}
}
add_action( 'init', 'medici_init_webhook_admin_settings', 11 );

/**
 * Initialize Theme Settings
 *
 * @since 1.5.0
 * @return void
 */
function medici_init_theme_settings(): void {
	if ( class_exists( 'Medici\Theme_Settings' ) ) {
		\Medici\Theme_Settings::init();
	}
}
add_action( 'init', 'medici_init_theme_settings', 11 );

/**
 * Initialize Dashboard Analytics
 *
 * @since 1.5.0
 * @return void
 */
function medici_init_dashboard_analytics(): void {
	if ( class_exists( 'Medici\Dashboard_Analytics' ) ) {
		\Medici\Dashboard_Analytics::init();
	}
}
add_action( 'init', 'medici_init_dashboard_analytics', 12 );

/**
 * Initialize REST API
 *
 * @since 1.5.0
 * @return void
 */
function medici_init_rest_api(): void {
	if ( class_exists( 'Medici\REST_API' ) ) {
		\Medici\REST_API::init();
	}
}
add_action( 'init', 'medici_init_rest_api', 13 );

/**
 * CRON endpoint для cleanup transients
 * Usage: wget https://www.medici.agency/?medici_cleanup_transients=1&secret=SECRET
 */
add_action('wp_enqueue_scripts', function () {
	$handle = 'medici-exit-intent-overlay';

	$cfg = [
		'overlayPanelId' => 'gb-overlay-424', // <-- ваш Data Attribute з Overlay Panel
		'cookieExp'      => 30,
		'delay'          => 2,
		'debug'          => false,
	];

	// Додаємо ПЕРЕД exit-intent-overlay.js. Якщо конфіг уже існує — ми його перезапишемо.
	wp_add_inline_script(
		$handle,
		'window.mediciExitIntentConfig = ' . wp_json_encode($cfg) . ';',
		'before'
	);
}, 999);

add_action('init', function() {
    if (isset($_GET['medici_cleanup_transients']) && $_GET['secret'] === 'ynww3jql0y0kb60qtmwa7pzgwita90qr') {
        // Cleanup transients
        global $wpdb;
        $time = time();
        $expired = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} 
                 WHERE option_name LIKE %s AND option_value < %d",
                $wpdb->esc_like('_transient_timeout_') . '%',
                $time
            )
        );

        $count = 0;
        foreach ($expired as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient);
            if (delete_transient($key)) {
                $count++;
            }
        }

        // Respond
        header('Content-Type: text/plain');
        die("Deleted {$count} expired transients\n");
    }
});

add_action('wp_enqueue_scripts', function () {
	$handle = 'medici-exit-intent-overlay-js'; // якщо у вас інший — див. примітку нижче

	// Додаємо/перезаписуємо конфіг перед основним скриптом
	if (wp_script_is($handle, 'registered') || wp_script_is($handle, 'enqueued')) {
		$cfg = [
			'overlayPanelId' => 'gb-overlay-424',
			'cookieExp'      => 30,
			'delay'          => 2,
			'debug'          => false,
		];

		wp_add_inline_script(
			$handle,
			'window.mediciExitIntentConfig = ' . wp_json_encode($cfg) . ';',
			'before'
		);
	}
}, 999);

// CRON Endpoints
require_once get_stylesheet_directory() . '/inc/cron-endpoints.php';
<?php
/**
 * Medici Exit-Intent Pro
 *
 * Advanced exit-intent popup plugin for marketing agencies.
 * Built on WP Mail SMTP architecture patterns.
 *
 * @package           Medici_Exit_Intent
 * @author            Medici Agency
 * @copyright         2025 Medici Agency
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Medici Exit-Intent Pro
 * Plugin URI:        https://medici.agency/plugins/exit-intent
 * Description:       Professional exit-intent popups with Twemoji, A/B testing, CRM integrations, and advanced analytics for marketing agencies.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Medici Agency
 * Author URI:        https://medici.agency
 * Text Domain:       medici-exit-intent
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'JEXI_VERSION', '1.0.0' );
define( 'JEXI_PLUGIN_FILE', __FILE__ );
define( 'JEXI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'JEXI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JEXI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum requirements.
define( 'JEXI_MIN_PHP_VERSION', '8.1' );
define( 'JEXI_MIN_WP_VERSION', '6.0' );

/**
 * PSR-4 Autoloader
 *
 * Maps Jexi\ namespace to src/ directory.
 */
spl_autoload_register(
	function ( string $class ): void {
		$prefix   = 'Jexi\\';
		$base_dir = JEXI_PLUGIN_DIR . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Get the main plugin instance.
 *
 * Singleton pattern - returns the same instance on every call.
 *
 * @since 1.0.0
 * @return Jexi\Core
 */
function jexi(): Jexi\Core {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Jexi\Core();
	}

	return $instance;
}

/**
 * Check minimum requirements before loading.
 *
 * @since 1.0.0
 * @return bool
 */
function jexi_check_requirements(): bool {
	$errors = array();

	// Check PHP version.
	if ( version_compare( PHP_VERSION, JEXI_MIN_PHP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current PHP version, 2: Required PHP version */
			__( 'Medici Exit-Intent Pro requires PHP %2$s or higher. You are running PHP %1$s.', 'medici-exit-intent' ),
			PHP_VERSION,
			JEXI_MIN_PHP_VERSION
		);
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), JEXI_MIN_WP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current WP version, 2: Required WP version */
			__( 'Medici Exit-Intent Pro requires WordPress %2$s or higher. You are running WordPress %1$s.', 'medici-exit-intent' ),
			get_bloginfo( 'version' ),
			JEXI_MIN_WP_VERSION
		);
	}

	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function () use ( $errors ): void {
				foreach ( $errors as $error ) {
					echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
				}
			}
		);
		return false;
	}

	return true;
}

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function jexi_init(): void {
	if ( ! jexi_check_requirements() ) {
		return;
	}

	// Load text domain.
	load_plugin_textdomain(
		'medici-exit-intent',
		false,
		dirname( JEXI_PLUGIN_BASENAME ) . '/languages'
	);

	// Initialize plugin.
	jexi()->init();
}
add_action( 'plugins_loaded', 'jexi_init', 10 );

/**
 * Activation hook.
 *
 * @since 1.0.0
 */
function jexi_activate(): void {
	if ( ! jexi_check_requirements() ) {
		return;
	}

	// Set default options.
	$defaults = Jexi\Options::get_defaults();
	if ( ! get_option( Jexi\Options::OPTION_KEY ) ) {
		add_option( Jexi\Options::OPTION_KEY, $defaults );
	}

	// Set activation flag for welcome screen.
	set_transient( 'jexi_activation_redirect', true, 30 );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( JEXI_PLUGIN_FILE, 'jexi_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function jexi_deactivate(): void {
	// Clean up scheduled events.
	wp_clear_scheduled_hook( 'jexi_daily_cleanup' );
	wp_clear_scheduled_hook( 'jexi_analytics_aggregate' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( JEXI_PLUGIN_FILE, 'jexi_deactivate' );

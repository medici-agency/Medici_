<?php
/**
 * Plugin Name:       Medici Forms Pro
 * Plugin URI:        https://medici.agency
 * Description:       Professional form builder for Medici Medical Marketing. Bypasses Gutenberg HTML sanitization with shortcodes.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Medici Agency
 * Author URI:        https://medici.agency
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       medici-forms-pro
 * Domain Path:       /languages
 *
 * @package MediciForms
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MEDICI_FORMS_VERSION', '1.1.0' );
define( 'MEDICI_FORMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEDICI_FORMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MEDICI_FORMS_PLUGIN_FILE', __FILE__ );
define( 'MEDICI_FORMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader (WordPress naming convention: class-{name}.php).
spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'MediciForms\\';
		$base_dir = MEDICI_FORMS_PLUGIN_DIR . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		// Convert namespace separators to directory separators.
		$path_parts = explode( '\\', $relative_class );

		// Get the class name (last part).
		$class_name = array_pop( $path_parts );

		// Convert class name to WordPress format: class-{lowercase}.php.
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Build the full path.
		$file = $base_dir;
		if ( ! empty( $path_parts ) ) {
			$file .= implode( '/', $path_parts ) . '/';
		}
		$file .= $file_name;

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Returns the main instance of Medici Forms.
 *
 * @since 1.0.0
 * @return MediciForms\Plugin
 */
function medici_forms(): MediciForms\Plugin {
	return MediciForms\Plugin::get_instance();
}

// Initialize plugin.
add_action( 'plugins_loaded', 'medici_forms', 10 );

// Activation hook.
register_activation_hook(
	__FILE__,
	static function (): void {
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/class-activator.php';
		MediciForms\Activator::activate();
	}
);

// Deactivation hook.
register_deactivation_hook(
	__FILE__,
	static function (): void {
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/class-deactivator.php';
		MediciForms\Deactivator::deactivate();
	}
);

<?php
/**
 * Medici Forms Advanced - Bootstrap
 *
 * Initialize Forms Advanced module
 *
 * @package    Medici_Agency
 * @subpackage Forms_Advanced
 * @since      1.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load main class
require_once __DIR__ . '/class-forms-advanced.php';

// Initialize on plugins_loaded hook (priority 15 to load after WPForms)
add_action(
	'plugins_loaded',
	static function (): void {
		medici_forms_advanced();
	},
	15
);

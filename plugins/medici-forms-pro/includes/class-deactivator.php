<?php
/**
 * Plugin Deactivator.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Deactivator Class.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Deactivate plugin.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate(): void {
		// Clear scheduled events.
		wp_clear_scheduled_hook( 'medici_forms_daily_cleanup' );
		wp_clear_scheduled_hook( 'medici_forms_hourly_tasks' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

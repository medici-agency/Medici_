<?php
/**
 * Widgets Registration
 *
 * Registers all custom widgets for Medici theme.
 *
 * @package    Medici_Agency
 * @subpackage Widgets
 * @since      1.4.0
 * @version    1.0.1
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom widgets
 *
 * Named function instead of anonymous callback for better debugging,
 * testing, and hook removal if needed.
 *
 * @since 1.4.0
 * @return void
 */
function medici_register_widgets(): void {
	// Load widget classes
	$widgets_dir = get_stylesheet_directory() . '/inc/widgets';

	$widget_files = array(
		'class-popular-posts-widget.php',
	);

	foreach ( $widget_files as $widget_file ) {
		$widget_path = $widgets_dir . '/' . $widget_file;

		if ( file_exists( $widget_path ) ) {
			require_once $widget_path;
		}
	}

	// Register widgets
	if ( class_exists( 'Medici_Popular_Posts_Widget' ) ) {
		register_widget( 'Medici_Popular_Posts_Widget' );
	}
}
add_action( 'widgets_init', 'medici_register_widgets' );

// NOTE: Widget CSS is loaded conditionally in inc/assets.php
// Only when sidebars are active (is_active_sidebar check)

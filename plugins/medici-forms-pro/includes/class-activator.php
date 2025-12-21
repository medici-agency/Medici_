<?php
/**
 * Plugin Activator.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Activator Class.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Activate plugin.
	 *
	 * @since 1.0.0
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		self::create_upload_dir();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		update_option( 'medici_forms_activated', time() );
		update_option( 'medici_forms_version', MEDICI_FORMS_VERSION );
	}

	/**
	 * Create database tables.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'medici_form_entries';

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT 0,
			user_ip varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			entry_data longtext NOT NULL,
			status varchar(20) DEFAULT 'unread',
			source_url text DEFAULT NULL,
			utm_source varchar(255) DEFAULT NULL,
			utm_medium varchar(255) DEFAULT NULL,
			utm_campaign varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Entry meta table.
		$meta_table = $wpdb->prefix . 'medici_form_entry_meta';

		$sql_meta = "CREATE TABLE IF NOT EXISTS {$meta_table} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			entry_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext DEFAULT NULL,
			PRIMARY KEY (meta_id),
			KEY entry_id (entry_id),
			KEY meta_key (meta_key(191))
		) {$charset_collate};";

		dbDelta( $sql_meta );
	}

	/**
	 * Set default options.
	 *
	 * @since 1.0.0
	 */
	private static function set_defaults(): void {
		$defaults = array(
			// General.
			'enable_ajax'            => true,
			'load_styles'            => true,
			'load_scripts'           => true,

			// Email.
			'admin_email'            => get_option( 'admin_email' ),
			'email_from_name'        => get_bloginfo( 'name' ),
			'email_from_address'     => get_option( 'admin_email' ),
			'email_template'         => 'default',

			// Anti-Spam.
			'enable_honeypot'        => true,
			'enable_time_check'      => true,
			'min_submission_time'    => 3,
			'enable_recaptcha'       => false,
			'recaptcha_site_key'     => '',
			'recaptcha_secret_key'   => '',
			'recaptcha_version'      => 'v3',
			'recaptcha_threshold'    => 0.5,

			// Integrations.
			'webhook_enabled'        => false,
			'webhook_url'            => '',
			'webhook_method'         => 'POST',
			'webhook_headers'        => array(),

			// Advanced.
			'delete_data_on_uninstall' => false,
			'log_entries'            => true,
			'entry_retention_days'   => 365,

			// Styling.
			'form_style'             => 'modern',
			'primary_color'          => '#2563eb',
			'success_color'          => '#16a34a',
			'error_color'            => '#dc2626',
			'border_radius'          => '8',
		);

		$existing = get_option( 'medici_forms_settings', array() );
		$merged   = wp_parse_args( $existing, $defaults );

		update_option( 'medici_forms_settings', $merged );
	}

	/**
	 * Create upload directory.
	 *
	 * @since 1.0.0
	 */
	private static function create_upload_dir(): void {
		$upload_dir = wp_upload_dir();
		$forms_dir  = $upload_dir['basedir'] . '/medici-forms';

		if ( ! file_exists( $forms_dir ) ) {
			wp_mkdir_p( $forms_dir );

			// Add index.php for security.
			$index_file = $forms_dir . '/index.php';
			if ( ! file_exists( $index_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $index_file, '<?php // Silence is golden.' );
			}

			// Add .htaccess for security.
			$htaccess_file = $forms_dir . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				$htaccess_content = "Options -Indexes\n<FilesMatch '\.(php|php\.|php3|php4|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$'>\nOrder Deny,Allow\nDeny from all\n</FilesMatch>";
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				file_put_contents( $htaccess_file, $htaccess_content );
			}
		}
	}
}

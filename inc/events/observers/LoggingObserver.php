<?php
/**
 * Logging Observer
 *
 * Logs all events to the database.
 *
 * @package    Medici_Agency
 * @subpackage Events\Observers
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Events\Observers;

use Medici\Events\EventInterface;
use Medici\Events\ObserverInterface;
use Medici\Events\NewsletterSubscribeEvent;
use Medici\Events\ConsultationRequestEvent;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logging Observer Class
 *
 * Logs events to wp_medici_events table.
 *
 * @since 2.0.0
 */
final class LoggingObserver implements ObserverInterface {

	/**
	 * Table name (without prefix)
	 */
	private const TABLE_NAME = 'medici_events';

	/**
	 * Table created option
	 */
	private const OPTION_TABLE_CREATED = 'medici_events_table_created';

	/**
	 * Get priority
	 *
	 * Logging should happen first (low priority number).
	 *
	 * @since 2.0.0
	 * @return int Priority level.
	 */
	public function getPriority(): int {
		return 1;
	}

	/**
	 * Get subscribed events
	 *
	 * @since 2.0.0
	 * @return array<string> Event names.
	 */
	public function getSubscribedEvents(): array {
		return array(
			NewsletterSubscribeEvent::NAME,
			ConsultationRequestEvent::NAME,
		);
	}

	/**
	 * Handle event
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event object.
	 * @return void
	 */
	public function handle( EventInterface $event ): void {
		$this->ensureTableExists();

		$event_id = $this->logEvent( $event );

		if ( $event_id > 0 ) {
			$event->setEventId( $event_id );
		}
	}

	/**
	 * Ensure table exists
	 *
	 * @since 2.0.0
	 * @return bool True if exists.
	 */
	private function ensureTableExists(): bool {
		if ( get_option( self::OPTION_TABLE_CREATED ) ) {
			return true;
		}

		$this->createTable();
		update_option( self::OPTION_TABLE_CREATED, '1', false );

		return true;
	}

	/**
	 * Create events table
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function createTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE {$table_name} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_type VARCHAR(100) NOT NULL,
				email VARCHAR(190) NULL,
				created_at DATETIME NOT NULL,
				payload LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY event_type (event_type),
				KEY email (email),
				KEY created_at (created_at)
			) {$charset_collate};
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log event to database
	 *
	 * @since 2.0.0
	 * @param EventInterface $event Event object.
	 * @return int Event ID or 0 on failure.
	 */
	private function logEvent( EventInterface $event ): int {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$payload    = $event->getPayload();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table_name,
			array(
				'event_type' => $event->getName(),
				'email'      => $payload['email'] ?? null,
				'created_at' => current_time( 'mysql', true ),
				'payload'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}
}

<?php
/**
 * Entries Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Entries;

/**
 * Entries Class.
 *
 * @since 1.0.0
 */
class Entries {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'medici_form_entries';
	}

	/**
	 * Get entry by ID.
	 *
	 * @since 1.0.0
	 * @param int $entry_id Entry ID.
	 * @return object|null
	 */
	public function get( int $entry_id ): ?object {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$entry_id
			)
		);

		return $entry ?? null;
	}

	/**
	 * Get entries for form.
	 *
	 * @since 1.0.0
	 * @param int   $form_id  Form ID.
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, object>
	 */
	public function get_by_form( int $form_id, array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'status'   => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args   = wp_parse_args( $args, $defaults );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		$where = 'WHERE form_id = %d';
		$params = array( $form_id );

		if ( ! empty( $args['status'] ) ) {
			$where .= ' AND status = %s';
			$params[] = $args['status'];
		}

		$orderby = in_array( $args['orderby'], array( 'id', 'created_at', 'status' ), true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( $params, array( $args['per_page'], $offset ) )
			)
		);
	}

	/**
	 * Update entry status.
	 *
	 * @since 1.0.0
	 * @param int    $entry_id Entry ID.
	 * @param string $status   New status.
	 * @return bool
	 */
	public function update_status( int $entry_id, string $status ): bool {
		global $wpdb;

		$valid_statuses = array( 'unread', 'read', 'starred', 'spam', 'completed' );

		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			array( 'status' => $status ),
			array( 'id' => $entry_id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete entry.
	 *
	 * @since 1.0.0
	 * @param int $entry_id Entry ID.
	 * @return bool
	 */
	public function delete( int $entry_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table,
			array( 'id' => $entry_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Get entries count for form.
	 *
	 * @since 1.0.0
	 * @param int    $form_id Form ID.
	 * @param string $status  Optional status filter.
	 * @return int
	 */
	public function count( int $form_id, string $status = '' ): int {
		global $wpdb;

		$where = 'WHERE form_id = %d';
		$params = array( $form_id );

		if ( ! empty( $status ) ) {
			$where .= ' AND status = %s';
			$params[] = $status;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} {$where}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$params
			)
		);

		return (int) $count;
	}

	/**
	 * Delete old entries.
	 *
	 * @since 1.0.0
	 * @param int $days Days to keep entries.
	 * @return int Number of deleted entries.
	 */
	public function cleanup_old_entries( int $days ): int {
		global $wpdb;

		if ( $days <= 0 ) {
			return 0;
		}

		$date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$date
			)
		);

		return is_int( $result ) ? $result : 0;
	}
}

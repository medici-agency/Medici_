<?php
/**
 * Analytics Tracker Class
 *
 * Tracks popup views, interactions, and conversions.
 *
 * @package Jexi\Analytics
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Analytics;

/**
 * Tracker Class
 */
final class Tracker {

	/**
	 * Option key for analytics data.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'jexi_analytics';

	/**
	 * Track an event.
	 *
	 * @since 1.0.0
	 * @param string               $event Event type (view, close, submit).
	 * @param array<string, mixed> $data  Additional data.
	 */
	public function track( string $event, array $data = array() ): void {
		$options = jexi()->get_options();

		if ( ! $options->get( 'analytics', 'enabled' ) ) {
			return;
		}

		$analytics = $this->get_data();
		$today     = gmdate( 'Y-m-d' );

		// Initialize today's data if needed.
		if ( ! isset( $analytics['daily'][ $today ] ) ) {
			$analytics['daily'][ $today ] = array(
				'views'   => 0,
				'closes'  => 0,
				'submits' => 0,
			);
		}

		// Increment counter.
		switch ( $event ) {
			case 'view':
				if ( $options->get( 'analytics', 'track_views' ) ) {
					++$analytics['daily'][ $today ]['views'];
					++$analytics['totals']['views'];
				}
				break;
			case 'close':
				if ( $options->get( 'analytics', 'track_closes' ) ) {
					++$analytics['daily'][ $today ]['closes'];
					++$analytics['totals']['closes'];
				}
				break;
			case 'submit':
				if ( $options->get( 'analytics', 'track_submits' ) ) {
					++$analytics['daily'][ $today ]['submits'];
					++$analytics['totals']['submits'];
				}
				break;
		}

		// Store event details.
		$analytics['events'][] = array(
			'type'      => $event,
			'timestamp' => current_time( 'mysql' ),
			'data'      => $data,
		);

		// Keep only last 1000 events.
		$analytics['events'] = array_slice( $analytics['events'], -1000 );

		$this->save_data( $analytics );

		/**
		 * Fires after an event is tracked.
		 *
		 * @since 1.0.0
		 * @param string               $event Event type.
		 * @param array<string, mixed> $data  Event data.
		 */
		do_action( 'jexi_event_tracked', $event, $data );
	}

	/**
	 * Handle AJAX tracking request.
	 *
	 * @since 1.0.0
	 */
	public function handle_tracking(): void {
		check_ajax_referer( 'jexi_frontend', 'nonce' );

		$event = isset( $_POST['event'] ) ? sanitize_key( $_POST['event'] ) : '';
		$data  = isset( $_POST['data'] ) ? array_map( 'sanitize_text_field', (array) $_POST['data'] ) : array();

		if ( in_array( $event, array( 'view', 'close', 'submit' ), true ) ) {
			$this->track( $event, $data );
		}

		wp_send_json_success();
	}

	/**
	 * Get analytics data.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		$default = array(
			'totals' => array(
				'views'   => 0,
				'closes'  => 0,
				'submits' => 0,
			),
			'daily'  => array(),
			'events' => array(),
		);

		$data = get_option( self::OPTION_KEY, $default );

		return is_array( $data ) ? array_merge( $default, $data ) : $default;
	}

	/**
	 * Save analytics data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Analytics data.
	 * @return bool
	 */
	private function save_data( array $data ): bool {
		return update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Get stats for a date range.
	 *
	 * @since 1.0.0
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array<string, mixed>
	 */
	public function get_stats( string $start_date, string $end_date ): array {
		$analytics = $this->get_data();
		$stats     = array(
			'views'           => 0,
			'closes'          => 0,
			'submits'         => 0,
			'conversion_rate' => 0,
			'daily'           => array(),
		);

		foreach ( $analytics['daily'] as $date => $day_stats ) {
			if ( $date >= $start_date && $date <= $end_date ) {
				$stats['views']   += $day_stats['views'];
				$stats['closes']  += $day_stats['closes'];
				$stats['submits'] += $day_stats['submits'];
				$stats['daily'][ $date ] = $day_stats;
			}
		}

		// Calculate conversion rate.
		if ( $stats['views'] > 0 ) {
			$stats['conversion_rate'] = round( ( $stats['submits'] / $stats['views'] ) * 100, 2 );
		}

		return $stats;
	}

	/**
	 * Get today's quick stats.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed>
	 */
	public function get_today_stats(): array {
		$today     = gmdate( 'Y-m-d' );
		$analytics = $this->get_data();

		$today_stats = $analytics['daily'][ $today ] ?? array(
			'views'   => 0,
			'closes'  => 0,
			'submits' => 0,
		);

		$conversion = $today_stats['views'] > 0
			? round( ( $today_stats['submits'] / $today_stats['views'] ) * 100, 2 )
			: 0;

		return array(
			'views'           => $today_stats['views'],
			'submits'         => $today_stats['submits'],
			'conversion_rate' => $conversion,
			'totals'          => $analytics['totals'],
		);
	}

	/**
	 * REST API: Get stats.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_get_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$start_date = $request->get_param( 'start' ) ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date   = $request->get_param( 'end' ) ?? gmdate( 'Y-m-d' );

		$stats = $this->get_stats( $start_date, $end_date );

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Daily cleanup (cron).
	 *
	 * @since 1.0.0
	 */
	public function daily_cleanup(): void {
		$options        = jexi()->get_options();
		$retention_days = $options->get( 'analytics', 'retention_days', 90 );
		$cutoff_date    = gmdate( 'Y-m-d', strtotime( "-{$retention_days} days" ) );

		$analytics = $this->get_data();

		// Remove old daily data.
		foreach ( array_keys( $analytics['daily'] ) as $date ) {
			if ( $date < $cutoff_date ) {
				unset( $analytics['daily'][ $date ] );
			}
		}

		// Remove old events.
		$analytics['events'] = array_filter(
			$analytics['events'],
			function ( array $event ) use ( $cutoff_date ): bool {
				return substr( $event['timestamp'], 0, 10 ) >= $cutoff_date;
			}
		);

		$this->save_data( $analytics );

		jexi()->get_debug()->info( 'Analytics cleanup completed', array( 'cutoff' => $cutoff_date ) );
	}

	/**
	 * Aggregate stats (cron).
	 *
	 * @since 1.0.0
	 */
	public function aggregate_stats(): void {
		// Future: aggregate weekly/monthly stats.
	}

	/**
	 * Export analytics data.
	 *
	 * @since 1.0.0
	 * @param string $format Export format (csv, json).
	 * @return string
	 */
	public function export( string $format = 'csv' ): string {
		$analytics = $this->get_data();

		if ( 'json' === $format ) {
			return (string) wp_json_encode( $analytics, JSON_PRETTY_PRINT );
		}

		// CSV format.
		$output = "Date,Views,Closes,Submits,Conversion Rate\n";

		foreach ( $analytics['daily'] as $date => $stats ) {
			$conversion = $stats['views'] > 0
				? round( ( $stats['submits'] / $stats['views'] ) * 100, 2 )
				: 0;

			$output .= sprintf(
				"%s,%d,%d,%d,%.2f%%\n",
				$date,
				$stats['views'],
				$stats['closes'],
				$stats['submits'],
				$conversion
			);
		}

		return $output;
	}

	/**
	 * Reset analytics data.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function reset(): bool {
		return delete_option( self::OPTION_KEY );
	}
}

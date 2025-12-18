<?php
/**
 * Клас аналітики згод
 *
 * @package Medici_Cookie_Notice
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас Analytics
 *
 * Агрегована статистика по згодам.
 */
class Analytics {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Назва таблиці
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Конструктор
	 *
	 * @param Cookie_Notice $plugin Головний клас плагіну
	 */
	public function __construct( Cookie_Notice $plugin ) {
		global $wpdb;

		$this->plugin     = $plugin;
		$this->table_name = $wpdb->prefix . 'mcn_analytics';

		// Scheduled cleanup
		if ( ! wp_next_scheduled( 'mcn_cleanup_analytics' ) ) {
			wp_schedule_event( time(), 'daily', 'mcn_cleanup_analytics' );
		}

		add_action( 'mcn_cleanup_analytics', [ $this, 'cleanup_old_data' ] );
	}

	/**
	 * Запис згоди в аналітику
	 *
	 * @param array<string, bool> $categories Категорії
	 * @param string              $status Статус (accepted, rejected, custom)
	 * @return void
	 */
	public function record_consent( array $categories, string $status ): void {
		if ( ! $this->plugin->get_option( 'enable_analytics' ) ) {
			return;
		}

		global $wpdb;

		$today = current_time( 'Y-m-d' );

		// Спробуємо оновити існуючий запис
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE date_recorded = %s",
				$today
			)
		);

		$update_data = [
			'total_visitors' => 1,
		];

		// Визначення типу згоди
		switch ( $status ) {
			case 'accepted':
				$update_data['accepted_all'] = 1;
				break;
			case 'rejected':
				$update_data['rejected_all'] = 1;
				break;
			default:
				$update_data['customized'] = 1;
		}

		// Категорії
		foreach ( $categories as $cat => $enabled ) {
			if ( $enabled ) {
				$column                 = 'category_' . sanitize_key( $cat );
				$update_data[ $column ] = 1;
			}
		}

		// Гео-дані
		$geo = $this->plugin->geo_detection ? $this->plugin->geo_detection->get_visitor_location() : null;
		if ( $geo && ! empty( $geo['country'] ) ) {
			$eu_countries = [ 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE' ];

			if ( in_array( $geo['country'], $eu_countries, true ) ) {
				$update_data['geo_eu'] = 1;
			} elseif ( 'US' === $geo['country'] ) {
				$update_data['geo_us'] = 1;
			} else {
				$update_data['geo_other'] = 1;
			}
		}

		if ( $existing ) {
			// Оновлення існуючого запису
			$set_parts = [];
			foreach ( $update_data as $col => $val ) {
				$set_parts[] = sprintf( '%s = %s + %d', $col, $col, $val );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table_name} SET " . implode( ', ', $set_parts ) . " WHERE id = %d",
					$existing->id
				)
			);
		} else {
			// Новий запис
			$insert_data                  = $update_data;
			$insert_data['date_recorded'] = $today;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $this->table_name, $insert_data );
		}
	}

	/**
	 * Отримання статистики за період
	 *
	 * @param int $days Кількість днів
	 * @return array<string, int>
	 */
	public function get_stats( int $days = 30 ): array {
		global $wpdb;

		$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE(SUM(total_visitors), 0) as total_visitors,
					COALESCE(SUM(accepted_all), 0) as accepted_all,
					COALESCE(SUM(rejected_all), 0) as rejected_all,
					COALESCE(SUM(customized), 0) as customized,
					COALESCE(SUM(category_necessary), 0) as category_necessary,
					COALESCE(SUM(category_analytics), 0) as category_analytics,
					COALESCE(SUM(category_marketing), 0) as category_marketing,
					COALESCE(SUM(category_preferences), 0) as category_preferences,
					COALESCE(SUM(geo_eu), 0) as geo_eu,
					COALESCE(SUM(geo_us), 0) as geo_us,
					COALESCE(SUM(geo_other), 0) as geo_other
				FROM {$this->table_name}
				WHERE date_recorded >= %s",
				$date_threshold
			),
			ARRAY_A
		);

		return $row ? array_map( 'intval', $row ) : [
			'total_visitors'       => 0,
			'accepted_all'         => 0,
			'rejected_all'         => 0,
			'customized'           => 0,
			'category_necessary'   => 0,
			'category_analytics'   => 0,
			'category_marketing'   => 0,
			'category_preferences' => 0,
			'geo_eu'               => 0,
			'geo_us'               => 0,
			'geo_other'            => 0,
		];
	}

	/**
	 * Отримання денної статистики
	 *
	 * @param int $days Кількість днів
	 * @return array<int, array<string, mixed>>
	 */
	public function get_daily_stats( int $days = 30 ): array {
		global $wpdb;

		$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE date_recorded >= %s
				ORDER BY date_recorded ASC",
				$date_threshold
			),
			ARRAY_A
		);

		return $results ?: [];
	}

	/**
	 * Розрахунок рівня прийняття
	 *
	 * @param int $days Кількість днів
	 * @return array<string, float>
	 */
	public function get_acceptance_rates( int $days = 30 ): array {
		$stats = $this->get_stats( $days );

		$total = $stats['total_visitors'];

		if ( 0 === $total ) {
			return [
				'accept_rate'  => 0.0,
				'reject_rate'  => 0.0,
				'custom_rate'  => 0.0,
				'overall_rate' => 0.0,
			];
		}

		return [
			'accept_rate'  => round( ( $stats['accepted_all'] / $total ) * 100, 2 ),
			'reject_rate'  => round( ( $stats['rejected_all'] / $total ) * 100, 2 ),
			'custom_rate'  => round( ( $stats['customized'] / $total ) * 100, 2 ),
			'overall_rate' => round( ( ( $stats['accepted_all'] + $stats['customized'] ) / $total ) * 100, 2 ),
		];
	}

	/**
	 * Рівень прийняття по категоріях
	 *
	 * @param int $days Кількість днів
	 * @return array<string, float>
	 */
	public function get_category_rates( int $days = 30 ): array {
		$stats = $this->get_stats( $days );

		$total = $stats['total_visitors'];

		if ( 0 === $total ) {
			return [
				'necessary'   => 0.0,
				'analytics'   => 0.0,
				'marketing'   => 0.0,
				'preferences' => 0.0,
			];
		}

		return [
			'necessary'   => round( ( $stats['category_necessary'] / $total ) * 100, 2 ),
			'analytics'   => round( ( $stats['category_analytics'] / $total ) * 100, 2 ),
			'marketing'   => round( ( $stats['category_marketing'] / $total ) * 100, 2 ),
			'preferences' => round( ( $stats['category_preferences'] / $total ) * 100, 2 ),
		];
	}

	/**
	 * Статистика по гео-регіонах
	 *
	 * @param int $days Кількість днів
	 * @return array<string, array<string, mixed>>
	 */
	public function get_geo_stats( int $days = 30 ): array {
		$stats = $this->get_stats( $days );

		$total = $stats['geo_eu'] + $stats['geo_us'] + $stats['geo_other'];

		if ( 0 === $total ) {
			return [
				'eu'    => [ 'count' => 0, 'percentage' => 0.0 ],
				'us'    => [ 'count' => 0, 'percentage' => 0.0 ],
				'other' => [ 'count' => 0, 'percentage' => 0.0 ],
			];
		}

		return [
			'eu'    => [
				'count'      => $stats['geo_eu'],
				'percentage' => round( ( $stats['geo_eu'] / $total ) * 100, 2 ),
			],
			'us'    => [
				'count'      => $stats['geo_us'],
				'percentage' => round( ( $stats['geo_us'] / $total ) * 100, 2 ),
			],
			'other' => [
				'count'      => $stats['geo_other'],
				'percentage' => round( ( $stats['geo_other'] / $total ) * 100, 2 ),
			],
		];
	}

	/**
	 * Порівняння періодів
	 *
	 * @param int $days Кількість днів
	 * @return array<string, array<string, mixed>>
	 */
	public function get_period_comparison( int $days = 30 ): array {
		$current  = $this->get_stats( $days );
		$previous = $this->get_stats_for_period(
			gmdate( 'Y-m-d', strtotime( "-" . ( $days * 2 ) . " days" ) ),
			gmdate( 'Y-m-d', strtotime( "-{$days} days" ) )
		);

		$calc_change = function ( int $current_val, int $previous_val ): float {
			if ( 0 === $previous_val ) {
				return $current_val > 0 ? 100.0 : 0.0;
			}
			return round( ( ( $current_val - $previous_val ) / $previous_val ) * 100, 2 );
		};

		return [
			'total_visitors' => [
				'current'  => $current['total_visitors'],
				'previous' => $previous['total_visitors'],
				'change'   => $calc_change( $current['total_visitors'], $previous['total_visitors'] ),
			],
			'accepted_all'   => [
				'current'  => $current['accepted_all'],
				'previous' => $previous['accepted_all'],
				'change'   => $calc_change( $current['accepted_all'], $previous['accepted_all'] ),
			],
			'rejected_all'   => [
				'current'  => $current['rejected_all'],
				'previous' => $previous['rejected_all'],
				'change'   => $calc_change( $current['rejected_all'], $previous['rejected_all'] ),
			],
		];
	}

	/**
	 * Статистика за період
	 *
	 * @param string $start_date Початкова дата
	 * @param string $end_date Кінцева дата
	 * @return array<string, int>
	 */
	private function get_stats_for_period( string $start_date, string $end_date ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE(SUM(total_visitors), 0) as total_visitors,
					COALESCE(SUM(accepted_all), 0) as accepted_all,
					COALESCE(SUM(rejected_all), 0) as rejected_all,
					COALESCE(SUM(customized), 0) as customized
				FROM {$this->table_name}
				WHERE date_recorded >= %s AND date_recorded < %s",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return $row ? array_map( 'intval', $row ) : [
			'total_visitors' => 0,
			'accepted_all'   => 0,
			'rejected_all'   => 0,
			'customized'     => 0,
		];
	}

	/**
	 * Видалення старих даних
	 *
	 * @return int Кількість видалених записів
	 */
	public function cleanup_old_data(): int {
		global $wpdb;

		$retention_days = (int) $this->plugin->get_option( 'analytics_retention' );
		$date_threshold = gmdate( 'Y-m-d', strtotime( "-{$retention_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE date_recorded < %s",
				$date_threshold
			)
		);

		return (int) $deleted;
	}

	/**
	 * Експорт аналітики у CSV
	 *
	 * @param int $days Кількість днів
	 * @return string CSV контент
	 */
	public function export_csv( int $days = 30 ): string {
		$data = $this->get_daily_stats( $days );

		$csv = "Date,Total Visitors,Accepted All,Rejected All,Customized,Analytics,Marketing,Preferences,EU,US,Other\n";

		foreach ( $data as $row ) {
			$csv .= sprintf(
				"%s,%d,%d,%d,%d,%d,%d,%d,%d,%d,%d\n",
				$row['date_recorded'],
				$row['total_visitors'],
				$row['accepted_all'],
				$row['rejected_all'],
				$row['customized'],
				$row['category_analytics'],
				$row['category_marketing'],
				$row['category_preferences'],
				$row['geo_eu'],
				$row['geo_us'],
				$row['geo_other']
			);
		}

		return $csv;
	}
}

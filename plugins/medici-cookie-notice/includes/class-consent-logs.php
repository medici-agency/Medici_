<?php
/**
 * Клас логування згод
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
 * Клас Consent_Logs
 *
 * Зберігає історію згод користувачів для аудиту та GDPR compliance.
 */
class Consent_Logs {

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
		$this->table_name = $wpdb->prefix . 'mcn_consent_logs';

		// Scheduled cleanup
		if ( ! wp_next_scheduled( 'mcn_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'mcn_cleanup_logs' );
		}

		add_action( 'mcn_cleanup_logs', [ $this, 'cleanup_old_logs' ] );
	}

	/**
	 * Логування згоди
	 *
	 * @param string               $consent_id ID згоди
	 * @param array<string, mixed> $categories Категорії
	 * @param string               $status Статус (accepted, rejected, custom)
	 * @return int|false ID запису або false при помилці
	 */
	public function log_consent( string $consent_id, array $categories, string $status ): int|false {
		global $wpdb;

		if ( ! $this->plugin->get_option( 'enable_consent_logs' ) ) {
			return false;
		}

		$data = [
			'consent_id'         => $consent_id,
			'user_id'            => get_current_user_id() ?: null,
			'consent_categories' => wp_json_encode( $categories ),
			'consent_status'     => $status,
			'page_url'           => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			'created_at'         => current_time( 'mysql' ),
		];

		// IP адреса (якщо увімкнено)
		if ( $this->plugin->get_option( 'log_ip_address' ) ) {
			$ip = $this->get_client_ip();

			// Анонімізація IP
			if ( $this->plugin->get_option( 'anonymize_ip' ) ) {
				$ip = $this->anonymize_ip( $ip );
			}

			$data['ip_address'] = $ip;
		}

		// User Agent (без персональних даних)
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$data['user_agent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		// Гео-дані (якщо доступні)
		$geo = $this->plugin->geo_detection ? $this->plugin->geo_detection->get_visitor_location() : null;
		if ( $geo ) {
			$data['geo_country'] = $geo['country'] ?? null;
			$data['geo_region']  = $geo['region'] ?? null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table_name,
			$data,
			[
				'%s', // consent_id
				'%d', // user_id
				'%s', // consent_categories
				'%s', // consent_status
				'%s', // page_url
				'%s', // created_at
				'%s', // ip_address
				'%s', // user_agent
				'%s', // geo_country
				'%s', // geo_region
			]
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Отримання згоди за ID
	 *
	 * @param string $consent_id ID згоди
	 * @return object|null
	 */
	public function get_consent( string $consent_id ): ?object {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE consent_id = %s ORDER BY created_at DESC LIMIT 1",
				$consent_id
			)
		);

		return $result ?: null;
	}

	/**
	 * Отримання останніх записів
	 *
	 * @param int $limit Ліміт
	 * @return array<int, object>
	 */
	public function get_recent_logs( int $limit = 50 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
				$limit
			)
		);

		return $results ?: [];
	}

	/**
	 * Отримання записів з фільтрацією
	 *
	 * @param array<string, mixed> $filters Фільтри
	 * @param int                  $limit Ліміт
	 * @param int                  $offset Зсув
	 * @return array<int, object>
	 */
	public function get_logs( array $filters = [], int $limit = 50, int $offset = 0 ): array {
		global $wpdb;

		$where = [ '1=1' ];
		$args  = [];

		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'consent_status = %s';
			$args[]  = $filters['status'];
		}

		if ( ! empty( $filters['country'] ) ) {
			$where[] = 'geo_country = %s';
			$args[]  = $filters['country'];
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where[] = 'created_at >= %s';
			$args[]  = $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where[] = 'created_at <= %s';
			$args[]  = $filters['date_to'];
		}

		$args[] = $limit;
		$args[] = $offset;

		$where_clause = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				...$args
			)
		);

		return $results ?: [];
	}

	/**
	 * Підрахунок записів
	 *
	 * @param array<string, mixed> $filters Фільтри
	 * @return int
	 */
	public function count_logs( array $filters = [] ): int {
		global $wpdb;

		$where = [ '1=1' ];
		$args  = [];

		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'consent_status = %s';
			$args[]  = $filters['status'];
		}

		if ( ! empty( $filters['country'] ) ) {
			$where[] = 'geo_country = %s';
			$args[]  = $filters['country'];
		}

		$where_clause = implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var(
			empty( $args )
				? "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}"
				: $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}", ...$args )
		);

		return (int) $count;
	}

	/**
	 * Експорт логів у CSV
	 *
	 * @param array<string, mixed> $filters Фільтри
	 * @return string CSV контент
	 */
	public function export_csv( array $filters = [] ): string {
		$logs = $this->get_logs( $filters, 10000, 0 );

		$csv = "Consent ID,Status,Categories,Country,Created At\n";

		foreach ( $logs as $log ) {
			$categories = json_decode( $log->consent_categories, true );
			$cat_string = implode( '; ', array_keys( array_filter( $categories ?? [] ) ) );

			$csv .= sprintf(
				'"%s","%s","%s","%s","%s"' . "\n",
				$log->consent_id,
				$log->consent_status,
				$cat_string,
				$log->geo_country ?? '',
				$log->created_at
			);
		}

		return $csv;
	}

	/**
	 * Видалення старих записів
	 *
	 * @return int Кількість видалених записів
	 */
	public function cleanup_old_logs(): int {
		global $wpdb;

		$retention_days = (int) $this->plugin->get_option( 'consent_logs_retention' );
		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE created_at < %s",
				$date_threshold
			)
		);

		return (int) $deleted;
	}

	/**
	 * Видалення даних користувача (GDPR right to erasure)
	 *
	 * @param string $consent_id ID згоди
	 * @return bool
	 */
	public function delete_user_data( string $consent_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$this->table_name,
			[ 'consent_id' => $consent_id ],
			[ '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Отримання IP клієнта
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$headers = [
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// X-Forwarded-For може містити кілька IP
				if ( str_contains( $ip, ',' ) ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Анонімізація IP адреси
	 *
	 * @param string $ip IP адреса
	 * @return string
	 */
	private function anonymize_ip( string $ip ): string {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			// IPv4: зберігаємо перші 3 октети
			$parts    = explode( '.', $ip );
			$parts[3] = '0';
			return implode( '.', $parts );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// IPv6: зберігаємо перші 3 групи
			$ip = inet_pton( $ip );
			if ( false !== $ip ) {
				// Маскуємо останні 10 байт
				$ip = substr( $ip, 0, 6 ) . str_repeat( "\x00", 10 );
				return inet_ntop( $ip );
			}
		}

		return '';
	}

	/**
	 * Статистика по країнах
	 *
	 * @param int $days Кількість днів
	 * @return array<string, int>
	 */
	public function get_stats_by_country( int $days = 30 ): array {
		global $wpdb;

		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT geo_country, COUNT(*) as count
				FROM {$this->table_name}
				WHERE created_at >= %s AND geo_country IS NOT NULL
				GROUP BY geo_country
				ORDER BY count DESC",
				$date_threshold
			)
		);

		$stats = [];
		foreach ( $results as $row ) {
			$stats[ $row->geo_country ] = (int) $row->count;
		}

		return $stats;
	}

	/**
	 * Статистика по статусах
	 *
	 * @param int $days Кількість днів
	 * @return array<string, int>
	 */
	public function get_stats_by_status( int $days = 30 ): array {
		global $wpdb;

		$date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT consent_status, COUNT(*) as count
				FROM {$this->table_name}
				WHERE created_at >= %s
				GROUP BY consent_status",
				$date_threshold
			)
		);

		$stats = [];
		foreach ( $results as $row ) {
			$stats[ $row->consent_status ] = (int) $row->count;
		}

		return $stats;
	}
}

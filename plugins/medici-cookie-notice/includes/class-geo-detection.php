<?php
/**
 * Клас гео-детекції
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
 * Клас Geo_Detection
 *
 * Визначення географічного розташування відвідувача для застосування
 * відповідних правил згоди (GDPR, CCPA, etc.).
 */
class Geo_Detection {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Кешоване розташування
	 *
	 * @var array<string, string>|null
	 */
	private ?array $cached_location = null;

	/**
	 * Країни ЄС (GDPR)
	 *
	 * @var array<int, string>
	 */
	private const EU_COUNTRIES = [
		'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
		'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
		'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
	];

	/**
	 * Країни EEA (European Economic Area)
	 *
	 * @var array<int, string>
	 */
	private const EEA_COUNTRIES = [
		'IS', 'LI', 'NO',
	];

	/**
	 * Штати США з законами про приватність
	 *
	 * @var array<string, string>
	 */
	private const US_PRIVACY_STATES = [
		'CA' => 'CCPA',    // California Consumer Privacy Act
		'VA' => 'VCDPA',   // Virginia Consumer Data Protection Act
		'CO' => 'CPA',     // Colorado Privacy Act
		'CT' => 'CTDPA',   // Connecticut Data Privacy Act
		'UT' => 'UCPA',    // Utah Consumer Privacy Act
	];

	/**
	 * Конструктор
	 *
	 * @param Cookie_Notice $plugin Головний клас плагіну
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Отримання розташування відвідувача
	 *
	 * @return array<string, string>|null
	 */
	public function get_visitor_location(): ?array {
		if ( null !== $this->cached_location ) {
			return $this->cached_location;
		}

		// Якщо гео-детекція вимкнена
		if ( ! $this->plugin->get_option( 'enable_geo_detection' ) ) {
			return null;
		}

		// Спробуємо отримати з кешу
		$cache_key = 'mcn_geo_' . $this->get_client_ip_hash();
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			$this->cached_location = $cached;
			return $cached;
		}

		// Отримання через API
		$provider = $this->plugin->get_option( 'geo_api_provider' );
		$location = null;

		switch ( $provider ) {
			case 'cloudflare':
				$location = $this->get_from_cloudflare();
				break;
			case 'geojs':
				$location = $this->get_from_geojs();
				break;
			case 'ipapi':
			default:
				$location = $this->get_from_ipapi();
				break;
		}

		if ( $location ) {
			// Кешуємо на 24 години
			set_transient( $cache_key, $location, DAY_IN_SECONDS );
			$this->cached_location = $location;
		}

		return $location;
	}

	/**
	 * Отримання з Cloudflare headers
	 *
	 * @return array<string, string>|null
	 */
	private function get_from_cloudflare(): ?array {
		// Cloudflare передає код країни в заголовку
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );

			// Cloudflare також може передавати регіон
			$region = '';
			if ( ! empty( $_SERVER['HTTP_CF_REGION'] ) ) {
				$region = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_REGION'] ) );
			}

			return [
				'country' => strtoupper( $country ),
				'region'  => $region,
			];
		}

		return null;
	}

	/**
	 * Отримання через ip-api.com
	 *
	 * @return array<string, string>|null
	 */
	private function get_from_ipapi(): ?array {
		$ip = $this->get_client_ip();

		if ( empty( $ip ) || $this->is_local_ip( $ip ) ) {
			return null;
		}

		$response = wp_remote_get(
			"http://ip-api.com/json/{$ip}?fields=countryCode,region",
			[
				'timeout'   => 5,
				'sslverify' => false,
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $data && isset( $data['countryCode'] ) ) {
			return [
				'country' => $data['countryCode'],
				'region'  => $data['region'] ?? '',
			];
		}

		return null;
	}

	/**
	 * Отримання через GeoJS
	 *
	 * @return array<string, string>|null
	 */
	private function get_from_geojs(): ?array {
		$ip = $this->get_client_ip();

		if ( empty( $ip ) || $this->is_local_ip( $ip ) ) {
			return null;
		}

		$response = wp_remote_get(
			"https://get.geojs.io/v1/ip/country/{$ip}.json",
			[ 'timeout' => 5 ]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $data && isset( $data['country'] ) ) {
			return [
				'country' => $data['country'],
				'region'  => '',
			];
		}

		return null;
	}

	/**
	 * Визначення режиму згоди для відвідувача
	 *
	 * @return string Режим (strict, ccpa, notice, implied)
	 */
	public function get_consent_mode(): string {
		$location = $this->get_visitor_location();
		$rules    = $this->plugin->get_option( 'geo_rules' );

		// Якщо не вдалось визначити локацію
		if ( null === $location ) {
			return $rules['default'] ?? 'notice';
		}

		$country = $location['country'];
		$region  = $location['region'];

		// Перевірка ЄС (GDPR)
		if ( in_array( $country, self::EU_COUNTRIES, true ) ) {
			return $rules['EU'] ?? 'strict';
		}

		// Перевірка EEA
		if ( in_array( $country, self::EEA_COUNTRIES, true ) ) {
			return $rules['EU'] ?? 'strict';
		}

		// Великобританія (UK GDPR)
		if ( 'GB' === $country ) {
			return $rules['UK'] ?? 'strict';
		}

		// США - перевірка штату
		if ( 'US' === $country && ! empty( $region ) ) {
			$state_code = strtoupper( $region );

			// Каліфорнія (CCPA)
			if ( 'CA' === $state_code && isset( $rules['US-CA'] ) ) {
				return $rules['US-CA'];
			}

			// Інші штати з законами про приватність
			if ( isset( self::US_PRIVACY_STATES[ $state_code ] ) ) {
				return $rules['US-CA'] ?? 'ccpa';
			}
		}

		// Бразилія (LGPD)
		if ( 'BR' === $country ) {
			return $rules['BR'] ?? 'strict';
		}

		// Канада (PIPEDA)
		if ( 'CA' === $country ) {
			return $rules['CA'] ?? 'notice';
		}

		// Австралія
		if ( 'AU' === $country ) {
			return $rules['AU'] ?? 'notice';
		}

		// За замовчуванням
		return $rules['default'] ?? 'notice';
	}

	/**
	 * Перевірка чи відвідувач з ЄС
	 *
	 * @return bool
	 */
	public function is_eu_visitor(): bool {
		$location = $this->get_visitor_location();

		if ( null === $location ) {
			return false;
		}

		return in_array( $location['country'], self::EU_COUNTRIES, true )
			   || in_array( $location['country'], self::EEA_COUNTRIES, true )
			   || 'GB' === $location['country'];
	}

	/**
	 * Перевірка чи відвідувач з Каліфорнії
	 *
	 * @return bool
	 */
	public function is_california_visitor(): bool {
		$location = $this->get_visitor_location();

		if ( null === $location ) {
			return false;
		}

		return 'US' === $location['country'] && 'CA' === strtoupper( $location['region'] );
	}

	/**
	 * Отримання відповідного закону
	 *
	 * @return string|null
	 */
	public function get_applicable_law(): ?string {
		$location = $this->get_visitor_location();

		if ( null === $location ) {
			return null;
		}

		$country = $location['country'];
		$region  = $location['region'];

		// ЄС
		if ( in_array( $country, self::EU_COUNTRIES, true ) || in_array( $country, self::EEA_COUNTRIES, true ) ) {
			return 'GDPR';
		}

		// Великобританія
		if ( 'GB' === $country ) {
			return 'UK GDPR';
		}

		// США
		if ( 'US' === $country && ! empty( $region ) ) {
			$state = strtoupper( $region );
			return self::US_PRIVACY_STATES[ $state ] ?? null;
		}

		// Бразилія
		if ( 'BR' === $country ) {
			return 'LGPD';
		}

		// Канада
		if ( 'CA' === $country ) {
			return 'PIPEDA';
		}

		return null;
	}

	/**
	 * Отримання IP клієнта
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$headers = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

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
	 * Отримання хешу IP
	 *
	 * @return string
	 */
	private function get_client_ip_hash(): string {
		return md5( $this->get_client_ip() );
	}

	/**
	 * Перевірка чи IP локальний
	 *
	 * @param string $ip IP адреса
	 * @return bool
	 */
	private function is_local_ip( string $ip ): bool {
		// Локальні та приватні діапазони
		$local_ranges = [
			'127.0.0.0/8',     // Localhost
			'10.0.0.0/8',      // Private Class A
			'172.16.0.0/12',   // Private Class B
			'192.168.0.0/16',  // Private Class C
			'169.254.0.0/16',  // Link-local
			'::1/128',         // IPv6 localhost
			'fc00::/7',        // IPv6 private
			'fe80::/10',       // IPv6 link-local
		];

		foreach ( $local_ranges as $range ) {
			if ( $this->ip_in_range( $ip, $range ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Перевірка чи IP в діапазоні
	 *
	 * @param string $ip IP адреса
	 * @param string $range CIDR діапазон
	 * @return bool
	 */
	private function ip_in_range( string $ip, string $range ): bool {
		if ( str_contains( $range, '/' ) ) {
			list( $subnet, $bits ) = explode( '/', $range );
			$bits = (int) $bits;

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$ip_long     = ip2long( $ip );
				$subnet_long = ip2long( $subnet );

				if ( false === $ip_long || false === $subnet_long ) {
					return false;
				}

				$mask = -1 << ( 32 - $bits );
				return ( $ip_long & $mask ) === ( $subnet_long & $mask );
			}

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$ip_bin     = inet_pton( $ip );
				$subnet_bin = inet_pton( $subnet );

				if ( false === $ip_bin || false === $subnet_bin ) {
					return false;
				}

				$mask = str_repeat( "\xff", (int) floor( $bits / 8 ) );
				if ( $bits % 8 ) {
					$mask .= chr( ( 0xff << ( 8 - ( $bits % 8 ) ) ) & 0xff );
				}
				$mask = str_pad( $mask, 16, "\x00" );

				return ( $ip_bin & $mask ) === ( $subnet_bin & $mask );
			}
		}

		return $ip === $range;
	}

	/**
	 * Отримання всіх країн ЄС
	 *
	 * @return array<int, string>
	 */
	public static function get_eu_countries(): array {
		return self::EU_COUNTRIES;
	}

	/**
	 * Отримання всіх штатів США з законами про приватність
	 *
	 * @return array<string, string>
	 */
	public static function get_us_privacy_states(): array {
		return self::US_PRIVACY_STATES;
	}
}

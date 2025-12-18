<?php
/**
 * Lead Validation Module
 *
 * Validates and sanitizes lead data to prevent garbage/spam submissions.
 * Implements UTM governance and data quality scoring.
 *
 * @package Medici
 * @since 1.6.0
 */

declare(strict_types=1);

namespace Medici;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Validation Class
 */
final class Lead_Validation {

	/**
	 * Valid UTM sources (lowercase only).
	 */
	private const VALID_SOURCES = array(
		'google',
		'facebook',
		'instagram',
		'linkedin',
		'telegram',
		'email',
		'direct',
		'referral',
		'viber',
		'youtube',
		'tiktok',
	);

	/**
	 * Valid UTM mediums (lowercase only).
	 */
	private const VALID_MEDIUMS = array(
		'cpc',
		'cpm',
		'organic',
		'social',
		'post',
		'story',
		'reel',
		'bio',
		'dm',
		'email',
		'referral',
		'video',
		'display',
	);

	/**
	 * Common UTM mistakes to auto-correct.
	 */
	private const SOURCE_CORRECTIONS = array(
		'insta'     => 'instagram',
		'ig'        => 'instagram',
		'fb'        => 'facebook',
		'ln'        => 'linkedin',
		'li'        => 'linkedin',
		'tg'        => 'telegram',
		'yt'        => 'youtube',
		'tt'        => 'tiktok',
		'ggl'       => 'google',
		'adwords'   => 'google',
		'googleads' => 'google',
	);

	/**
	 * Temporary/disposable email domains to block.
	 */
	private const BLOCKED_EMAIL_DOMAINS = array(
		'tempmail.com',
		'guerrillamail.com',
		'10minutemail.com',
		'mailinator.com',
		'throwaway.email',
		'temp-mail.org',
		'fakeinbox.com',
		'trashmail.com',
		'sharklasers.com',
		'guerrillamail.info',
		'grr.la',
		'dispostable.com',
		'yopmail.com',
		'getairmail.com',
		'mohmal.com',
	);

	/**
	 * Test email patterns to filter.
	 */
	private const TEST_EMAIL_PATTERNS = array(
		'/^test[@.]/',
		'/^demo[@.]/',
		'/^example[@.]/',
		'/^fake[@.]/',
		'/^asdf[@.]/',
		'/^qwerty[@.]/',
		'/[@.]test\./',
		'/[@.]example\./',
	);

	/**
	 * Suspicious name patterns.
	 */
	private const SUSPICIOUS_NAMES = array(
		'test',
		'testing',
		'demo',
		'asd',
		'asdf',
		'qwe',
		'qwerty',
		'xxx',
		'abc',
		'123',
		'admin',
		'null',
		'undefined',
	);

	/**
	 * Initialize validation hooks.
	 */
	public static function init(): void {
		// Validate lead data before saving.
		add_filter( 'medici_validate_lead_data', array( __CLASS__, 'validate_lead' ), 10, 1 );

		// Auto-correct UTM parameters.
		add_filter( 'medici_sanitize_utm', array( __CLASS__, 'sanitize_utm' ), 10, 2 );
	}

	/**
	 * Validate lead data.
	 *
	 * @param array<string, mixed> $data Lead data.
	 * @return array{valid: bool, errors: array<string>, warnings: array<string>, data: array<string, mixed>, quality_score: int}
	 */
	public static function validate_lead( array $data ): array {
		$errors   = array();
		$warnings = array();
		$score    = 0;

		// === EMAIL VALIDATION ===
		$email = sanitize_email( $data['email'] ?? '' );

		if ( empty( $email ) ) {
			$errors[] = 'Email є обов\'язковим полем';
		} elseif ( ! is_email( $email ) ) {
			$errors[] = 'Невірний формат email';
		} else {
			// Check for temp/disposable email.
			$domain = strtolower( substr( strrchr( $email, '@' ) ?: '', 1 ) );

			if ( in_array( $domain, self::BLOCKED_EMAIL_DOMAINS, true ) ) {
				$errors[] = 'Тимчасові email адреси не приймаються';
				$score   -= 50;
			}

			// Check for test patterns.
			foreach ( self::TEST_EMAIL_PATTERNS as $pattern ) {
				if ( preg_match( $pattern, strtolower( $email ) ) ) {
					$warnings[] = 'Email схожий на тестовий';
					$score     -= 20;
					break;
				}
			}

			// Business email bonus.
			$free_domains = array( 'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'ukr.net', 'i.ua', 'meta.ua' );
			if ( ! in_array( $domain, $free_domains, true ) ) {
				$score += 15; // Business email bonus.
			}

			$data['email'] = $email;
		}

		// === PHONE VALIDATION ===
		$phone = preg_replace( '/[^0-9+]/', '', $data['phone'] ?? '' );

		if ( ! empty( $phone ) ) {
			// Normalize Ukrainian phone.
			if ( preg_match( '/^0\d{9}$/', $phone ) ) {
				$phone = '+38' . $phone;
			} elseif ( preg_match( '/^380\d{9}$/', $phone ) ) {
				$phone = '+' . $phone;
			}

			// Validate format.
			if ( ! preg_match( '/^\+380\d{9}$/', $phone ) && strlen( $phone ) < 10 ) {
				$warnings[] = 'Телефон може бути невірним';
			}

			$score        += 20; // Has phone bonus.
			$data['phone'] = $phone;
		}

		// === NAME VALIDATION ===
		$name = sanitize_text_field( $data['name'] ?? '' );

		if ( empty( $name ) ) {
			$errors[] = 'Ім\'я є обов\'язковим полем';
		} elseif ( strlen( $name ) < 2 ) {
			$errors[] = 'Ім\'я занадто коротке';
		} else {
			// Check for suspicious names.
			$name_lower = strtolower( $name );
			if ( in_array( $name_lower, self::SUSPICIOUS_NAMES, true ) ) {
				$warnings[] = 'Ім\'я виглядає підозрілим';
				$score     -= 30;
			}

			// Check for repeated characters (spam indicator).
			if ( preg_match( '/(.)\1{4,}/', $name ) ) {
				$warnings[] = 'Ім\'я містить повторювані символи';
				$score     -= 20;
			}

			$data['name'] = $name;
		}

		// === MESSAGE VALIDATION ===
		$message = sanitize_textarea_field( $data['message'] ?? '' );

		if ( ! empty( $message ) ) {
			$score += 15; // Has message bonus.

			if ( strlen( $message ) > 100 ) {
				$score += 10; // Long message bonus.
			}

			// Check for spam links.
			if ( preg_match( '/(http|www\.|\.com|\.ru|\.ua)/i', $message ) ) {
				$warnings[] = 'Повідомлення містить посилання';
				$score     -= 10;
			}

			$data['message'] = $message;
		}

		// === SERVICE VALIDATION ===
		$valid_services = array( 'smm', 'seo', 'advertising', 'branding', 'consultation', 'other' );
		$service        = sanitize_text_field( $data['service'] ?? 'other' );

		if ( ! in_array( $service, $valid_services, true ) ) {
			$service = 'other';
		}
		$data['service'] = $service;

		// === UTM VALIDATION ===
		$utm_source   = self::validate_utm_source( $data['utm_source'] ?? '' );
		$utm_medium   = self::validate_utm_medium( $data['utm_medium'] ?? '' );
		$utm_campaign = sanitize_text_field( $data['utm_campaign'] ?? '' );

		$data['utm_source']   = $utm_source['value'];
		$data['utm_medium']   = $utm_medium['value'];
		$data['utm_campaign'] = $utm_campaign;

		if ( $utm_source['corrected'] ) {
			$warnings[] = sprintf( 'UTM source автоматично виправлено: %s → %s', $utm_source['original'], $utm_source['value'] );
		}

		if ( $utm_medium['corrected'] ) {
			$warnings[] = sprintf( 'UTM medium автоматично виправлено: %s → %s', $utm_medium['original'], $utm_medium['value'] );
		}

		// === CONSENT VALIDATION ===
		if ( empty( $data['consent'] ) ) {
			$errors[] = 'Необхідна згода на обробку персональних даних';
		}

		// === HONEYPOT CHECK ===
		if ( ! empty( $data['website'] ) || ! empty( $data['url'] ) || ! empty( $data['company_website'] ) ) {
			$errors[] = 'Spam detection triggered';
			$score   -= 100;
		}

		// === TIME CHECK (too fast = bot) ===
		$form_time = (int) ( $data['form_time'] ?? 0 );
		if ( $form_time > 0 && $form_time < 3 ) {
			$warnings[] = 'Форма заповнена занадто швидко';
			$score     -= 30;
		}

		// Normalize score to 0-100.
		$score = max( 0, min( 100, 50 + $score ) );

		return array(
			'valid'         => empty( $errors ),
			'errors'        => $errors,
			'warnings'      => $warnings,
			'data'          => $data,
			'quality_score' => $score,
		);
	}

	/**
	 * Validate and correct UTM source.
	 *
	 * @param string $source Raw UTM source.
	 * @return array{value: string, original: string, corrected: bool}
	 */
	public static function validate_utm_source( string $source ): array {
		$original  = $source;
		$source    = strtolower( trim( $source ) );
		$corrected = false;

		// Auto-correct common mistakes.
		if ( isset( self::SOURCE_CORRECTIONS[ $source ] ) ) {
			$source    = self::SOURCE_CORRECTIONS[ $source ];
			$corrected = true;
		}

		// Validate against whitelist.
		if ( ! in_array( $source, self::VALID_SOURCES, true ) ) {
			if ( ! empty( $source ) ) {
				$corrected = true;
				// Try to find closest match.
				foreach ( self::VALID_SOURCES as $valid ) {
					if ( str_contains( $source, $valid ) || str_contains( $valid, $source ) ) {
						$source = $valid;
						break;
					}
				}
				// Fallback to direct if no match.
				if ( ! in_array( $source, self::VALID_SOURCES, true ) ) {
					$source = 'direct';
				}
			} else {
				$source = 'direct';
			}
		}

		return array(
			'value'     => $source,
			'original'  => $original,
			'corrected' => $corrected && $original !== $source,
		);
	}

	/**
	 * Validate and correct UTM medium.
	 *
	 * @param string $medium Raw UTM medium.
	 * @return array{value: string, original: string, corrected: bool}
	 */
	public static function validate_utm_medium( string $medium ): array {
		$original  = $medium;
		$medium    = strtolower( trim( $medium ) );
		$corrected = false;

		// Common corrections.
		$medium_corrections = array(
			'paid'         => 'cpc',
			'ppc'          => 'cpc',
			'ads'          => 'cpc',
			'social-media' => 'social',
			'feed'         => 'post',
			'stories'      => 'story',
			'reels'        => 'reel',
			'newsletter'   => 'email',
			'mail'         => 'email',
		);

		if ( isset( $medium_corrections[ $medium ] ) ) {
			$medium    = $medium_corrections[ $medium ];
			$corrected = true;
		}

		// Validate against whitelist.
		if ( ! in_array( $medium, self::VALID_MEDIUMS, true ) ) {
			if ( ! empty( $medium ) ) {
				$corrected = true;
			}
			$medium = 'unknown';
		}

		return array(
			'value'     => $medium,
			'original'  => $original,
			'corrected' => $corrected && $original !== $medium,
		);
	}

	/**
	 * Sanitize UTM parameters (filter callback).
	 *
	 * @param string $value     UTM value.
	 * @param string $parameter Parameter name (source, medium, campaign, etc.).
	 * @return string Sanitized value.
	 */
	public static function sanitize_utm( string $value, string $parameter ): string {
		$value = strtolower( trim( sanitize_text_field( $value ) ) );

		switch ( $parameter ) {
			case 'source':
				$result = self::validate_utm_source( $value );
				return $result['value'];

			case 'medium':
				$result = self::validate_utm_medium( $value );
				return $result['value'];

			default:
				// For campaign, term, content - just sanitize.
				return preg_replace( '/[^a-z0-9_-]/', '', $value ) ?: '';
		}
	}

	/**
	 * Check for duplicate lead by email or phone.
	 *
	 * @param string $email Lead email.
	 * @param string $phone Lead phone.
	 * @param int    $hours Check within last X hours.
	 * @return int|null Existing lead ID if duplicate, null otherwise.
	 */
	public static function check_duplicate( string $email, string $phone, int $hours = 24 ): ?int {
		$args = array(
			'post_type'      => 'medici_lead',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after' => sprintf( '%d hours ago', $hours ),
				),
			),
			'meta_query'     => array(
				'relation' => 'OR',
			),
		);

		if ( ! empty( $email ) ) {
			$args['meta_query'][] = array(
				'key'   => '_lead_email',
				'value' => $email,
			);
		}

		if ( ! empty( $phone ) ) {
			$args['meta_query'][] = array(
				'key'   => '_lead_phone',
				'value' => $phone,
			);
		}

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			return (int) $query->posts[0];
		}

		return null;
	}

	/**
	 * Get validation statistics for monitoring.
	 *
	 * @param int $days Number of days to analyze.
	 * @return array<string, mixed> Statistics.
	 */
	public static function get_validation_stats( int $days = 30 ): array {
		global $wpdb;

		$date_from = gmdate( 'Y-m-d', strtotime( sprintf( '-%d days', $days ) ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE post_type = 'medici_lead'
				AND post_date >= %s",
				$date_from
			)
		);

		$without_utm = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_lead_utm_source'
				WHERE p.post_type = 'medici_lead'
				AND p.post_date >= %s
				AND (pm.meta_value IS NULL OR pm.meta_value = '' OR pm.meta_value = 'direct')",
				$date_from
			)
		);

		$spam_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'medici_lead'
				AND p.post_date >= %s
				AND pm.meta_key = '_lead_status'
				AND pm.meta_value = 'spam'",
				$date_from
			)
		);
		// phpcs:enable

		return array(
			'total_leads'     => $total,
			'without_utm'     => $without_utm,
			'without_utm_pct' => $total > 0 ? round( ( $without_utm / $total ) * 100, 1 ) : 0,
			'spam_count'      => $spam_count,
			'spam_pct'        => $total > 0 ? round( ( $spam_count / $total ) * 100, 1 ) : 0,
			'period_days'     => $days,
		);
	}
}

// Initialize.
Lead_Validation::init();

<?php
/**
 * Security Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Security Class - Anti-spam and validation.
 *
 * @since 1.0.0
 */
class Security {

	/**
	 * Verify honeypot field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Form data.
	 * @return bool
	 */
	public static function verify_honeypot( array $data ): bool {
		// Honeypot field should be empty.
		if ( isset( $data['mf_website_url'] ) && ! empty( $data['mf_website_url'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify submission time.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Form data.
	 * @param int                  $min_time Minimum time in seconds.
	 * @return bool
	 */
	public static function verify_time_check( array $data, int $min_time = 3 ): bool {
		if ( ! isset( $data['mf_timestamp'] ) ) {
			return false;
		}

		$timestamp = absint( $data['mf_timestamp'] );
		$current   = time();

		// Check if form was submitted too quickly (likely bot).
		if ( ( $current - $timestamp ) < $min_time ) {
			return false;
		}

		// Check if timestamp is not in the future or too old (24 hours).
		if ( $timestamp > $current || ( $current - $timestamp ) > 86400 ) {
			return false;
		}

		return true;
	}

	/**
	 * Verify reCAPTCHA.
	 *
	 * @since 1.0.0
	 * @param string $token reCAPTCHA token.
	 * @return bool
	 */
	public static function verify_recaptcha( string $token ): bool {
		$secret_key = Plugin::get_option( 'recaptcha_secret_key', '' );

		if ( empty( $secret_key ) || empty( $token ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $secret_key,
					'response' => $token,
					'remoteip' => Helpers::get_client_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			return false;
		}

		// For v3, check score.
		$version = Plugin::get_option( 'recaptcha_version', 'v3' );
		if ( 'v3' === $version ) {
			$threshold = (float) Plugin::get_option( 'recaptcha_threshold', 0.5 );
			if ( isset( $data['score'] ) && $data['score'] < $threshold ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check for spam patterns.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Form data.
	 * @return bool True if spam detected.
	 */
	public static function detect_spam_patterns( array $data ): bool {
		$spam_patterns = array(
			// URL patterns.
			'/\[url=/i',
			'/\[link=/i',
			'/\[\/url\]/i',
			'/\[\/link\]/i',
			'/href\s*=/i',

			// Common spam keywords.
			'/viagra/i',
			'/cialis/i',
			'/casino/i',
			'/poker/i',
			'/pharma/i',
			'/\bcrypto\b.*\bwallet\b/i',
		);

		$text_content = implode( ' ', array_map( 'strval', $data ) );

		foreach ( $spam_patterns as $pattern ) {
			if ( preg_match( $pattern, $text_content ) ) {
				return true;
			}
		}

		// Check for too many URLs.
		$url_count = preg_match_all( '/https?:\/\/[^\s]+/i', $text_content );
		if ( $url_count && $url_count > 3 ) {
			return true;
		}

		return false;
	}

	/**
	 * Rate limiting check.
	 *
	 * @since 1.0.0
	 * @param int    $form_id Form ID.
	 * @param string $ip      Client IP.
	 * @param int    $limit   Max submissions.
	 * @param int    $window  Time window in seconds.
	 * @return bool True if within limit.
	 */
	public static function check_rate_limit( int $form_id, string $ip, int $limit = 5, int $window = 3600 ): bool {
		$transient_key = 'mf_rate_' . md5( $form_id . $ip );
		$submissions   = get_transient( $transient_key );

		if ( false === $submissions ) {
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( (int) $submissions >= $limit ) {
			return false;
		}

		set_transient( $transient_key, (int) $submissions + 1, $window );
		return true;
	}

	/**
	 * Verify nonce.
	 *
	 * @since 1.0.0
	 * @param string $nonce  Nonce value.
	 * @param int    $form_id Form ID.
	 * @return bool
	 */
	public static function verify_nonce( string $nonce, int $form_id ): bool {
		return (bool) wp_verify_nonce( $nonce, 'medici_form_submit_' . $form_id );
	}

	/**
	 * Generate form nonce field.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return string
	 */
	public static function get_nonce_field( int $form_id ): string {
		return wp_nonce_field( 'medici_form_submit_' . $form_id, 'mf_nonce', true, false );
	}

	/**
	 * Get honeypot field HTML.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_honeypot_field(): string {
		// Use CSS to hide, not display:none (bots may ignore display:none).
		return '<div class="mf-hp-field" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;overflow:hidden;">
			<label for="mf_website_url">' . esc_html__( 'Website', 'medici-forms-pro' ) . '</label>
			<input type="text" name="mf_website_url" id="mf_website_url" value="" tabindex="-1" autocomplete="off">
		</div>';
	}

	/**
	 * Get timestamp field HTML.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_timestamp_field(): string {
		return '<input type="hidden" name="mf_timestamp" value="' . esc_attr( (string) time() ) . '">';
	}
}

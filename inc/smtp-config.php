<?php
/**
 * SMTP Configuration for WordPress Mail
 *
 * Configures wp_mail() to use custom SMTP server (mail.adm.tools)
 * for sending emails from info@medici.agency
 *
 * @package    Medici_Agency
 * @subpackage Email
 * @since      1.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configure PHPMailer for SMTP
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance
 * @return void
 */
add_action(
	'phpmailer_init',
	static function ( $phpmailer ): void {
		// Use SMTP
		$phpmailer->isSMTP();

		// SMTP Configuration
		$phpmailer->Host       = 'mail.adm.tools';
		$phpmailer->Port       = 465; // SSL port (recommended)
		$phpmailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL encryption
		$phpmailer->SMTPAuth   = true;

		// SMTP Credentials
		$phpmailer->Username = 'info@medici.agency';
		$phpmailer->Password = 'pQ7npG7qeC';

		// From Email and Name
		$phpmailer->From     = 'info@medici.agency';
		$phpmailer->FromName = 'Medici Agency';

		// Sender (optional, for Return-Path)
		$phpmailer->Sender = 'info@medici.agency';

		// SMTP Debug (only for WP_DEBUG mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$phpmailer->SMTPDebug   = 2; // 0 = off, 1 = client, 2 = client+server
			$phpmailer->Debugoutput = static function ( $str, $level ) {
				error_log( "[SMTP Debug] $str" );
			};
		}

		// Additional settings for better deliverability
		$phpmailer->CharSet  = 'UTF-8';
		$phpmailer->Encoding = 'base64';

		// SMTP Options (for SSL)
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => true,
				'verify_peer_name'  => true,
				'allow_self_signed' => false,
			),
		);
	}
);

/**
 * Set default FROM email for all WordPress emails
 *
 * @param string $email Original FROM email
 * @return string Modified FROM email
 */
add_filter(
	'wp_mail_from',
	static function ( string $email ): string {
		// Only change if it's the default WordPress email
		if ( strpos( $email, 'wordpress@' ) !== false ) {
			return 'info@medici.agency';
		}
		return $email;
	}
);

/**
 * Set default FROM name for all WordPress emails
 *
 * @param string $name Original FROM name
 * @return string Modified FROM name
 */
add_filter(
	'wp_mail_from_name',
	static function ( string $name ): string {
		// Only change if it's the default WordPress name
		if ( $name === 'WordPress' ) {
			return 'Medici Agency';
		}
		return $name;
	}
);

/**
 * Log email sending errors
 *
 * @param WP_Error $error WP_Error object
 * @return void
 */
add_action(
	'wp_mail_failed',
	static function ( $error ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[wp_mail] Failed to send email: ' . $error->get_error_message() );
		}
	}
);

/**
 * Test SMTP Configuration (admin only)
 *
 * Usage: Add ?test_smtp=1 to any admin page URL
 */
if ( is_admin() && isset( $_GET['test_smtp'] ) && current_user_can( 'manage_options' ) ) {
	add_action(
		'admin_init',
		static function (): void {
			$test_email = get_option( 'admin_email' );
			$subject    = '[Medici SMTP Test] ' . gmdate( 'Y-m-d H:i:s' );
			$message    = '<h2>SMTP Test Successful!</h2>';
			$message   .= '<p>This is a test email from Medici Agency SMTP configuration.</p>';
			$message   .= '<p><strong>Server:</strong> mail.adm.tools</p>';
			$message   .= '<p><strong>Port:</strong> 465 (SSL)</p>';
			$message   .= '<p><strong>From:</strong> info@medici.agency</p>';
			$message   .= '<p><strong>Time:</strong> ' . gmdate( 'Y-m-d H:i:s' ) . '</p>';

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			$result = wp_mail( $test_email, $subject, $message, $headers );

			if ( $result ) {
				wp_die(
					'<h1>✅ SMTP Test Successful!</h1>' .
					'<p>Email sent successfully to: <strong>' . esc_html( $test_email ) . '</strong></p>' .
					'<p>Check your inbox (and spam folder).</p>' .
					'<p><a href="' . esc_url( admin_url() ) . '">← Back to Dashboard</a></p>'
				);
			} else {
				wp_die(
					'<h1>❌ SMTP Test Failed!</h1>' .
					'<p>Failed to send email. Check error logs for details.</p>' .
					'<p>Enable WP_DEBUG in wp-config.php to see SMTP debug output.</p>' .
					'<p><a href="' . esc_url( admin_url() ) . '">← Back to Dashboard</a></p>'
				);
			}
		}
	);
}

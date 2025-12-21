<?php
/**
 * Lead Integrations
 *
 * Handles integrations for lead notifications:
 * - Email notifications to admin
 * - Telegram Bot API integration
 * - Google Sheets integration via Apps Script
 *
 * @package    Medici_Agency
 * @subpackage Leads
 * @since      1.4.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Integrations Handler Class
 *
 * @since 1.4.0
 */
final class Lead_Integrations {

	/**
	 * Option keys for integration settings
	 */
	private const OPTION_ADMIN_EMAIL        = 'medici_lead_admin_email';
	private const OPTION_TELEGRAM_BOT_TOKEN = 'medici_lead_telegram_bot_token';
	private const OPTION_TELEGRAM_CHAT_ID   = 'medici_lead_telegram_chat_id';
	private const OPTION_GOOGLE_SHEET_URL   = 'medici_lead_google_sheet_url';

	/**
	 * Initialize Lead Integrations
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function init(): void {
		// No hooks needed - this class provides static methods only
	}

	/**
	 * Send all integrations for a new lead
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @return void
	 */
	public static function send_all( array $data, int $lead_id ): void {
		// Send email notification
		self::send_email_notification( $data, $lead_id );

		// Send to Telegram
		self::send_to_telegram( $data, $lead_id );

		// Send to Google Sheets
		self::send_to_google_sheets( $data, $lead_id );
	}

	/**
	 * Send email notification to admin
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @return bool True on success
	 */
	public static function send_email_notification( array $data, int $lead_id ): bool {
		$admin_email = get_option( self::OPTION_ADMIN_EMAIL );

		if ( ! $admin_email ) {
			$admin_email = get_option( 'admin_email' ); // Fallback to WordPress admin email
		}

		if ( ! $admin_email || ! is_email( $admin_email ) ) {
			return false;
		}

		// Prepare email content
		$subject = sprintf(
			'[%s] Новий лід #%d - %s',
			get_bloginfo( 'name' ),
			$lead_id,
			$data['name'] ?? 'Без імені'
		);

		$message = self::build_email_message( $data, $lead_id );

		// Email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
		);

		// Add Reply-To if user provided email
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			$headers[] = sprintf( 'Reply-To: %s <%s>', $data['name'] ?? '', $data['email'] );
		}

		// Send email
		return wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * Build email message HTML
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @return string Email message HTML
	 */
	private static function build_email_message( array $data, int $lead_id ): string {
		$edit_url = admin_url( 'post.php?post=' . $lead_id . '&action=edit' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="uk">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
					line-height: 1.6;
					color: #333;
					max-width: 600px;
					margin: 0 auto;
					padding: 20px;
				}
				.header {
					background: #2563eb;
					color: white;
					padding: 20px;
					border-radius: 8px 8px 0 0;
					text-align: center;
				}
				.content {
					background: #f9fafb;
					padding: 30px;
					border: 1px solid #e5e7eb;
				}
				.field {
					margin-bottom: 20px;
				}
				.field-label {
					font-weight: 600;
					color: #6b7280;
					font-size: 0.875rem;
					text-transform: uppercase;
					letter-spacing: 0.05em;
					margin-bottom: 5px;
				}
				.field-value {
					font-size: 1rem;
					color: #111827;
				}
				.button {
					display: inline-block;
					padding: 12px 24px;
					background: #2563eb;
					color: white;
					text-decoration: none;
					border-radius: 6px;
					margin-top: 20px;
				}
				.footer {
					margin-top: 30px;
					padding-top: 20px;
					border-top: 1px solid #e5e7eb;
					font-size: 0.875rem;
					color: #6b7280;
					text-align: center;
				}
			</style>
		</head>
		<body>
			<div class="header">
				<h1 style="margin: 0; font-size: 1.5rem;">📞 Новий запит на консультацію</h1>
			</div>
			<div class="content">
				<div class="field">
					<div class="field-label">Ім'я</div>
					<div class="field-value"><strong><?php echo esc_html( $data['name'] ?? '—' ); ?></strong></div>
				</div>

				<div class="field">
					<div class="field-label">Email</div>
					<div class="field-value">
						<?php if ( ! empty( $data['email'] ) ) : ?>
							<a href="mailto:<?php echo esc_attr( $data['email'] ); ?>" style="color: #2563eb; text-decoration: none;">
								<?php echo esc_html( $data['email'] ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</div>
				</div>

				<div class="field">
					<div class="field-label">Телефон</div>
					<div class="field-value">
						<?php if ( ! empty( $data['phone'] ) ) : ?>
							<a href="tel:<?php echo esc_attr( $data['phone'] ); ?>" style="color: #2563eb; text-decoration: none;">
								<?php echo esc_html( $data['phone'] ); ?>
							</a>
						<?php else : ?>
							—
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! empty( $data['service'] ) ) : ?>
				<div class="field">
					<div class="field-label">Послуга</div>
					<div class="field-value"><?php echo esc_html( $data['service'] ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['message'] ) ) : ?>
				<div class="field">
					<div class="field-label">Повідомлення</div>
					<div class="field-value"><?php echo wp_kses_post( nl2br( $data['message'] ) ); ?></div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['page_url'] ) ) : ?>
				<div class="field">
					<div class="field-label">Сторінка</div>
					<div class="field-value">
						<a href="<?php echo esc_url( $data['page_url'] ); ?>" style="color: #2563eb; text-decoration: none;">
							<?php echo esc_html( $data['page_url'] ); ?>
						</a>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $data['utm_source'] ) || ! empty( $data['utm_campaign'] ) ) : ?>
				<div class="field">
					<div class="field-label">UTM параметри</div>
					<div class="field-value" style="font-size: 0.875rem;">
						<?php if ( ! empty( $data['utm_source'] ) ) : ?>
							Source: <strong><?php echo esc_html( $data['utm_source'] ); ?></strong><br>
						<?php endif; ?>
						<?php if ( ! empty( $data['utm_medium'] ) ) : ?>
							Medium: <strong><?php echo esc_html( $data['utm_medium'] ); ?></strong><br>
						<?php endif; ?>
						<?php if ( ! empty( $data['utm_campaign'] ) ) : ?>
							Campaign: <strong><?php echo esc_html( $data['utm_campaign'] ); ?></strong><br>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

				<a href="<?php echo esc_url( $edit_url ); ?>" class="button">
					Переглянути лід в адмінці
				</a>
			</div>
			<div class="footer">
				<p>Цей email надіслано автоматично з сайту <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<p><?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?></p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send lead to Telegram
	 *
	 * Uses Telegram Bot API to send message to specified chat.
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @return bool True on success
	 */
	public static function send_to_telegram( array $data, int $lead_id ): bool {
		$bot_token = get_option( self::OPTION_TELEGRAM_BOT_TOKEN );
		$chat_id   = get_option( self::OPTION_TELEGRAM_CHAT_ID );

		if ( ! $bot_token || ! $chat_id ) {
			return false; // Integration not configured
		}

		$edit_url = admin_url( 'post.php?post=' . $lead_id . '&action=edit' );

		// Build Telegram message (Markdown format)
		$message = self::build_telegram_message( $data, $lead_id, $edit_url );

		// Send to Telegram Bot API
		$url = sprintf(
			'https://api.telegram.org/bot%s/sendMessage',
			$bot_token
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'body'    => array(
					'chat_id'    => $chat_id,
					'text'       => $message,
					'parse_mode' => 'Markdown',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Log error
			error_log( 'Medici Lead Telegram Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		return 200 === $response_code;
	}

	/**
	 * Build Telegram message (Markdown)
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @param string               $edit_url Edit URL
	 * @return string Telegram message
	 */
	private static function build_telegram_message( array $data, int $lead_id, string $edit_url ): string {
		$lines = array();

		$lines[] = '📞 *Новий запит на консультацію*';
		$lines[] = '';
		$lines[] = sprintf( '*Лід #%d*', $lead_id );
		$lines[] = '';

		// Name
		if ( ! empty( $data['name'] ) ) {
			$lines[] = sprintf( '👤 *Ім\'я:* %s', self::escape_markdown( $data['name'] ) );
		}

		// Email
		if ( ! empty( $data['email'] ) ) {
			$lines[] = sprintf( '📧 *Email:* %s', self::escape_markdown( $data['email'] ) );
		}

		// Phone
		if ( ! empty( $data['phone'] ) ) {
			$lines[] = sprintf( '📱 *Телефон:* %s', self::escape_markdown( $data['phone'] ) );
		}

		// Service
		if ( ! empty( $data['service'] ) ) {
			$lines[] = sprintf( '🎯 *Послуга:* %s', self::escape_markdown( $data['service'] ) );
		}

		// Message
		if ( ! empty( $data['message'] ) ) {
			$lines[] = '';
			$lines[] = sprintf( '💬 *Повідомлення:*' );
			$lines[] = self::escape_markdown( $data['message'] );
		}

		// UTM
		if ( ! empty( $data['utm_source'] ) || ! empty( $data['utm_campaign'] ) ) {
			$lines[] = '';
			$lines[] = '📊 *UTM:*';

			if ( ! empty( $data['utm_source'] ) ) {
				$lines[] = sprintf( 'Source: %s', self::escape_markdown( $data['utm_source'] ) );
			}
			if ( ! empty( $data['utm_medium'] ) ) {
				$lines[] = sprintf( 'Medium: %s', self::escape_markdown( $data['utm_medium'] ) );
			}
			if ( ! empty( $data['utm_campaign'] ) ) {
				$lines[] = sprintf( 'Campaign: %s', self::escape_markdown( $data['utm_campaign'] ) );
			}
		}

		$lines[] = '';
		$lines[] = sprintf( '[Відкрити в адмінці](%s)', $edit_url );

		return implode( "\n", $lines );
	}

	/**
	 * Send lead to Google Sheets
	 *
	 * Uses Google Apps Script Web App as endpoint.
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @param int                  $lead_id Lead post ID
	 * @return bool True on success
	 */
	public static function send_to_google_sheets( array $data, int $lead_id ): bool {
		$sheet_url = get_option( self::OPTION_GOOGLE_SHEET_URL );

		if ( ! $sheet_url ) {
			return false; // Integration not configured
		}

		// Prepare data for Google Sheets
		$row_data = array(
			'lead_id'      => $lead_id,
			'date'         => current_time( 'Y-m-d H:i:s' ),
			'name'         => $data['name'] ?? '',
			'email'        => $data['email'] ?? '',
			'phone'        => $data['phone'] ?? '',
			'service'      => $data['service'] ?? '',
			'message'      => $data['message'] ?? '',
			'page_url'     => $data['page_url'] ?? '',
			'utm_source'   => $data['utm_source'] ?? '',
			'utm_medium'   => $data['utm_medium'] ?? '',
			'utm_campaign' => $data['utm_campaign'] ?? '',
			'utm_term'     => $data['utm_term'] ?? '',
			'utm_content'  => $data['utm_content'] ?? '',
			'status'       => 'new',
		);

		// Send to Google Apps Script
		$response = wp_remote_post(
			$sheet_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $row_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			)
		);

		if ( is_wp_error( $response ) ) {
			// Log error
			error_log( 'Medici Lead Google Sheets Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		return 200 === $response_code;
	}

	/**
	 * Get admin email option
	 *
	 * @since 1.4.0
	 * @return string Email address
	 */
	public static function get_admin_email(): string {
		return get_option( self::OPTION_ADMIN_EMAIL, get_option( 'admin_email' ) );
	}

	/**
	 * Get Telegram bot token option
	 *
	 * @since 1.4.0
	 * @return string Bot token
	 */
	public static function get_telegram_bot_token(): string {
		return get_option( self::OPTION_TELEGRAM_BOT_TOKEN, '' );
	}

	/**
	 * Get Telegram chat ID option
	 *
	 * @since 1.4.0
	 * @return string Chat ID
	 */
	public static function get_telegram_chat_id(): string {
		return get_option( self::OPTION_TELEGRAM_CHAT_ID, '' );
	}

	/**
	 * Get Google Sheets URL option
	 *
	 * @since 1.4.0
	 * @return string Google Apps Script URL
	 */
	public static function get_google_sheet_url(): string {
		return get_option( self::OPTION_GOOGLE_SHEET_URL, '' );
	}

	/**
	 * Update admin email option
	 *
	 * @since 1.4.0
	 * @param string $email Email address
	 * @return bool True on success
	 */
	public static function update_admin_email( string $email ): bool {
		if ( ! is_email( $email ) ) {
			return false;
		}

		return update_option( self::OPTION_ADMIN_EMAIL, $email );
	}

	/**
	 * Update Telegram bot token option
	 *
	 * @since 1.4.0
	 * @param string $token Bot token
	 * @return bool True on success
	 */
	public static function update_telegram_bot_token( string $token ): bool {
		return update_option( self::OPTION_TELEGRAM_BOT_TOKEN, $token );
	}

	/**
	 * Update Telegram chat ID option
	 *
	 * @since 1.4.0
	 * @param string $chat_id Chat ID
	 * @return bool True on success
	 */
	public static function update_telegram_chat_id( string $chat_id ): bool {
		return update_option( self::OPTION_TELEGRAM_CHAT_ID, $chat_id );
	}

	/**
	 * Update Google Sheets URL option
	 *
	 * @since 1.4.0
	 * @param string $url Google Apps Script URL
	 * @return bool True on success
	 */
	public static function update_google_sheet_url( string $url ): bool {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return update_option( self::OPTION_GOOGLE_SHEET_URL, $url );
	}

	/**
	 * Escape special characters for Telegram Markdown
	 *
	 * @since 1.4.1
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	private static function escape_markdown( string $text ): string {
		$special = array( '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!' );

		foreach ( $special as $char ) {
			$text = str_replace( $char, '\\' . $char, $text );
		}

		return $text;
	}
}

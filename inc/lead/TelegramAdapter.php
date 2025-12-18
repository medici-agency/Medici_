<?php
/**
 * Telegram Integration Adapter
 *
 * Sends lead notifications via Telegram Bot API.
 *
 * @package    Medici_Agency
 * @subpackage Lead
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Telegram Adapter Class
 *
 * Handles Telegram notifications for new leads.
 *
 * @since 2.0.0
 */
final class TelegramAdapter extends AbstractIntegration {

	/**
	 * Option keys
	 */
	private const OPTION_BOT_TOKEN = 'medici_lead_telegram_bot_token';
	private const OPTION_CHAT_ID   = 'medici_lead_telegram_chat_id';

	/**
	 * Telegram API URL
	 */
	private const API_URL = 'https://api.telegram.org/bot%s/sendMessage';

	/**
	 * Request timeout in seconds
	 */
	private const TIMEOUT = 10;

	/**
	 * Get integration name
	 *
	 * @since 2.0.0
	 * @return string Integration identifier.
	 */
	public function getName(): string {
		return 'Telegram';
	}

	/**
	 * Check if integration is enabled
	 *
	 * @since 2.0.0
	 * @return bool True if enabled.
	 */
	public function isEnabled(): bool {
		$bot_token = $this->getBotToken();
		$chat_id   = $this->getChatId();

		return ! empty( $bot_token ) && ! empty( $chat_id );
	}

	/**
	 * Get bot token
	 *
	 * @since 2.0.0
	 * @return string Bot token.
	 */
	public function getBotToken(): string {
		$token = $this->getOption( self::OPTION_BOT_TOKEN );
		return is_string( $token ) ? $token : '';
	}

	/**
	 * Set bot token
	 *
	 * @since 2.0.0
	 * @param string $token Bot token.
	 * @return bool True on success.
	 */
	public function setBotToken( string $token ): bool {
		return $this->updateOption( self::OPTION_BOT_TOKEN, $token );
	}

	/**
	 * Get chat ID
	 *
	 * @since 2.0.0
	 * @return string Chat ID.
	 */
	public function getChatId(): string {
		$chat_id = $this->getOption( self::OPTION_CHAT_ID );
		return is_string( $chat_id ) ? $chat_id : '';
	}

	/**
	 * Set chat ID
	 *
	 * @since 2.0.0
	 * @param string $chat_id Chat ID.
	 * @return bool True on success.
	 */
	public function setChatId( string $chat_id ): bool {
		return $this->updateOption( self::OPTION_CHAT_ID, $chat_id );
	}

	/**
	 * Send Telegram notification
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return bool True on success.
	 */
	public function send( array $data, int $lead_id ): bool {
		if ( ! $this->isEnabled() ) {
			$this->setError( 'Telegram integration not configured' );
			return false;
		}

		$url     = sprintf( self::API_URL, $this->getBotToken() );
		$message = $this->buildMessage( $data, $lead_id );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'body'    => array(
					'chat_id'    => $this->getChatId(),
					'text'       => $message,
					'parse_mode' => 'Markdown',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->setError( $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$body = wp_remote_retrieve_body( $response );
			$this->setError( sprintf( 'HTTP %d: %s', $response_code, $body ) );
			return false;
		}

		$this->logSuccess( sprintf( 'Telegram message sent for lead #%d', $lead_id ) );
		return true;
	}

	/**
	 * Build Telegram message (Markdown format)
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return string Telegram message.
	 */
	private function buildMessage( array $data, int $lead_id ): string {
		$edit_url = admin_url( 'post.php?post=' . $lead_id . '&action=edit' );
		$lines    = array();

		$lines[] = '📞 *Новий запит на консультацію*';
		$lines[] = '';
		$lines[] = sprintf( '*Лід #%d*', $lead_id );
		$lines[] = '';

		// Name.
		if ( ! empty( $data['name'] ) ) {
			$lines[] = sprintf( "👤 *Ім'я:* %s", $this->escapeMarkdown( $data['name'] ) );
		}

		// Email.
		if ( ! empty( $data['email'] ) ) {
			$lines[] = sprintf( '📧 *Email:* %s', $this->escapeMarkdown( $data['email'] ) );
		}

		// Phone.
		if ( ! empty( $data['phone'] ) ) {
			$lines[] = sprintf( '📱 *Телефон:* %s', $this->escapeMarkdown( $data['phone'] ) );
		}

		// Service.
		if ( ! empty( $data['service'] ) ) {
			$lines[] = sprintf( '🎯 *Послуга:* %s', $this->escapeMarkdown( $data['service'] ) );
		}

		// Message.
		if ( ! empty( $data['message'] ) ) {
			$lines[] = '';
			$lines[] = '💬 *Повідомлення:*';
			$lines[] = $this->escapeMarkdown( $data['message'] );
		}

		// UTM.
		if ( ! empty( $data['utm_source'] ) || ! empty( $data['utm_campaign'] ) ) {
			$lines[] = '';
			$lines[] = '📊 *UTM:*';

			if ( ! empty( $data['utm_source'] ) ) {
				$lines[] = sprintf( 'Source: %s', $this->escapeMarkdown( $data['utm_source'] ) );
			}
			if ( ! empty( $data['utm_medium'] ) ) {
				$lines[] = sprintf( 'Medium: %s', $this->escapeMarkdown( $data['utm_medium'] ) );
			}
			if ( ! empty( $data['utm_campaign'] ) ) {
				$lines[] = sprintf( 'Campaign: %s', $this->escapeMarkdown( $data['utm_campaign'] ) );
			}
		}

		$lines[] = '';
		$lines[] = sprintf( '[Відкрити в адмінці](%s)', $edit_url );

		return implode( "\n", $lines );
	}

	/**
	 * Escape special characters for Telegram Markdown
	 *
	 * @since 2.0.0
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	private function escapeMarkdown( string $text ): string {
		// Escape Markdown special characters.
		$special = array( '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!' );

		foreach ( $special as $char ) {
			$text = str_replace( $char, '\\' . $char, $text );
		}

		return $text;
	}

	/**
	 * Test the connection
	 *
	 * Sends a test message to verify configuration.
	 *
	 * @since 2.0.0
	 * @return bool True on success.
	 */
	public function testConnection(): bool {
		if ( ! $this->isEnabled() ) {
			$this->setError( 'Telegram integration not configured' );
			return false;
		}

		$url = sprintf( self::API_URL, $this->getBotToken() );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => self::TIMEOUT,
				'body'    => array(
					'chat_id'    => $this->getChatId(),
					'text'       => '✅ *Тестове повідомлення*\n\nІнтеграція з Medici Agency працює!',
					'parse_mode' => 'Markdown',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->setError( $response->get_error_message() );
			return false;
		}

		return 200 === wp_remote_retrieve_response_code( $response );
	}
}

<?php
/**
 * Google Sheets Integration Adapter
 *
 * Sends lead data to Google Sheets via Apps Script Web App.
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
 * Google Sheets Adapter Class
 *
 * Handles Google Sheets integration for lead tracking.
 *
 * @since 2.0.0
 */
final class GoogleSheetsAdapter extends AbstractIntegration {

	/**
	 * Option key for Apps Script URL
	 */
	private const OPTION_SHEET_URL = 'medici_lead_google_sheet_url';

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
		return 'GoogleSheets';
	}

	/**
	 * Check if integration is enabled
	 *
	 * @since 2.0.0
	 * @return bool True if enabled.
	 */
	public function isEnabled(): bool {
		$url = $this->getSheetUrl();
		return ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Get Apps Script URL
	 *
	 * @since 2.0.0
	 * @return string Apps Script URL.
	 */
	public function getSheetUrl(): string {
		$url = $this->getOption( self::OPTION_SHEET_URL );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Set Apps Script URL
	 *
	 * @since 2.0.0
	 * @param string $url Apps Script URL.
	 * @return bool True on success.
	 */
	public function setSheetUrl( string $url ): bool {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$this->setError( 'Invalid URL' );
			return false;
		}

		return $this->updateOption( self::OPTION_SHEET_URL, $url );
	}

	/**
	 * Send data to Google Sheets
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return bool True on success.
	 */
	public function send( array $data, int $lead_id ): bool {
		if ( ! $this->isEnabled() ) {
			$this->setError( 'Google Sheets integration not configured' );
			return false;
		}

		$row_data = $this->prepareRowData( $data, $lead_id );

		$response = wp_remote_post(
			$this->getSheetUrl(),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode(
					$row_data,
					JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
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

		$this->logSuccess( sprintf( 'Google Sheets row added for lead #%d', $lead_id ) );
		return true;
	}

	/**
	 * Prepare row data for Google Sheets
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return array<string, mixed> Row data.
	 */
	private function prepareRowData( array $data, int $lead_id ): array {
		return array(
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
	}

	/**
	 * Get Apps Script template code
	 *
	 * Returns the JavaScript code for Google Apps Script.
	 *
	 * @since 2.0.0
	 * @return string Apps Script code.
	 */
	public static function getAppsScriptTemplate(): string {
		return <<<'JAVASCRIPT'
function doPost(e) {
    try {
        const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
        const data = JSON.parse(e.postData.contents);

        sheet.appendRow([
            data.lead_id || '',
            data.date || new Date().toISOString(),
            data.name || '',
            data.email || '',
            data.phone || '',
            data.service || '',
            data.message || '',
            data.page_url || '',
            data.utm_source || '',
            data.utm_medium || '',
            data.utm_campaign || '',
            data.status || 'new'
        ]);

        return ContentService
            .createTextOutput(JSON.stringify({ success: true }))
            .setMimeType(ContentService.MimeType.JSON);
    } catch (error) {
        return ContentService
            .createTextOutput(JSON.stringify({ success: false, error: error.toString() }))
            .setMimeType(ContentService.MimeType.JSON);
    }
}
JAVASCRIPT;
	}
}

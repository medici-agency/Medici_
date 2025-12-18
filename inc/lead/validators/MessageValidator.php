<?php
/**
 * Message Validator
 *
 * Validates lead messages.
 *
 * @package    Medici_Agency
 * @subpackage Lead\Validation
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead\Validators;

use Medici\Lead\ValidatorInterface;
use Medici\Lead\ValidationResult;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Message Validator Class
 *
 * Validates message content and detects spam links.
 *
 * @since 2.0.0
 */
final class MessageValidator implements ValidatorInterface {

	/**
	 * Validator name
	 */
	private const NAME = 'message';

	/**
	 * Long message threshold
	 */
	private const LONG_MESSAGE_LENGTH = 100;

	/**
	 * Spam link pattern
	 */
	private const SPAM_LINK_PATTERN = '/(http|www\.|\.com|\.ru|\.ua)/i';

	/**
	 * Get name
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getName(): string {
		return self::NAME;
	}

	/**
	 * Validate message
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Data with 'message' key.
	 * @return ValidationResult
	 */
	public function validate( array $data ): ValidationResult {
		$message   = sanitize_textarea_field( (string) ( $data['message'] ?? '' ) );
		$warnings  = array();
		$score_mod = 0;

		// Message is optional.
		if ( empty( $message ) ) {
			return ValidationResult::success();
		}

		// Has message bonus.
		$score_mod += 15;

		// Long message bonus.
		if ( strlen( $message ) > self::LONG_MESSAGE_LENGTH ) {
			$score_mod += 10;
		}

		// Spam links detection.
		if ( preg_match( self::SPAM_LINK_PATTERN, $message ) ) {
			$warnings[] = __( 'Повідомлення містить посилання', 'medici.agency' );
			$score_mod -= 10;
		}

		return ValidationResult::success(
			array( 'message' => $message ),
			$score_mod,
			$warnings
		);
	}

	/**
	 * Sanitize message
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Input data.
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		if ( isset( $data['message'] ) ) {
			$data['message'] = sanitize_textarea_field( (string) $data['message'] );
		}
		return $data;
	}
}

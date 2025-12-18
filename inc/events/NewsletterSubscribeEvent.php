<?php
/**
 * Newsletter Subscribe Event
 *
 * Event triggered when user subscribes to newsletter.
 *
 * @package    Medici_Agency
 * @subpackage Events
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Events;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Newsletter Subscribe Event Class
 *
 * @since 2.0.0
 */
final class NewsletterSubscribeEvent extends AbstractEvent {

	/**
	 * Event name constant
	 */
	public const NAME = 'newsletter_subscribe';

	/**
	 * Get event name
	 *
	 * @since 2.0.0
	 * @return string Event name.
	 */
	public function getName(): string {
		return self::NAME;
	}

	/**
	 * Get subscriber source
	 *
	 * @since 2.0.0
	 * @return string Subscription source.
	 */
	public function getSource(): string {
		return $this->get( 'source', '' );
	}

	/**
	 * Get subscriber tags
	 *
	 * @since 2.0.0
	 * @return array<string> Tags.
	 */
	public function getTags(): array {
		$tags = $this->get( 'tags', array() );
		return is_array( $tags ) ? $tags : array();
	}

	/**
	 * Get page URL where subscription happened
	 *
	 * @since 2.0.0
	 * @return string Page URL.
	 */
	public function getPageUrl(): string {
		return $this->get( 'page_url', '' );
	}

	/**
	 * Create event from sanitized payload
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $payload Raw payload.
	 * @return self
	 */
	public static function fromPayload( array $payload ): self {
		$sanitized = array(
			'email'        => isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '',
			'source'       => isset( $payload['source'] ) ? sanitize_text_field( $payload['source'] ) : '',
			'tags'         => isset( $payload['tags'] ) && is_array( $payload['tags'] )
				? array_map( 'sanitize_text_field', $payload['tags'] )
				: array(),
			'page_url'     => isset( $payload['page_url'] ) ? esc_url_raw( $payload['page_url'] ) : '',
			'utm_source'   => isset( $payload['utm_source'] ) ? sanitize_text_field( $payload['utm_source'] ) : '',
			'utm_medium'   => isset( $payload['utm_medium'] ) ? sanitize_text_field( $payload['utm_medium'] ) : '',
			'utm_campaign' => isset( $payload['utm_campaign'] ) ? sanitize_text_field( $payload['utm_campaign'] ) : '',
		);

		return new self( $sanitized );
	}
}

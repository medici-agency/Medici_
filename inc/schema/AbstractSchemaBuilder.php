<?php
/**
 * Abstract Schema Builder
 *
 * Base class for schema builders with common functionality.
 *
 * @package    Medici_Agency
 * @subpackage Schema
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Schema;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Schema Builder
 *
 * Provides common methods for all schema builders.
 *
 * @since 2.0.0
 */
abstract class AbstractSchemaBuilder implements SchemaBuilderInterface {

	/**
	 * Default priority
	 */
	protected const DEFAULT_PRIORITY = 10;

	/**
	 * Schema context
	 */
	protected const CONTEXT = 'https://schema.org';

	/**
	 * Custom content (when not using current post)
	 *
	 * @var string|null
	 */
	protected ?string $content = null;

	/**
	 * Set custom content for parsing
	 *
	 * @since 2.0.0
	 * @param string $content Content to parse.
	 * @return self For chaining.
	 */
	public function setContent( string $content ): self {
		$this->content = $content;
		return $this;
	}

	/**
	 * Get content (custom or from current post)
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function getContent(): string {
		return $this->content ?? $this->getPostContent();
	}

	/**
	 * Get priority
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getPriority(): int {
		return static::DEFAULT_PRIORITY;
	}

	/**
	 * Add context to schema
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $schema Schema data.
	 * @return array<string, mixed> Schema with context.
	 */
	protected function withContext( array $schema ): array {
		return array_merge( array( '@context' => self::CONTEXT ), $schema );
	}

	/**
	 * Get current post
	 *
	 * @since 2.0.0
	 * @return \WP_Post|null
	 */
	protected function getCurrentPost(): ?\WP_Post {
		global $post;
		return $post instanceof \WP_Post ? $post : null;
	}

	/**
	 * Get post content
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function getPostContent(): string {
		$post = $this->getCurrentPost();
		return $post ? $post->post_content : '';
	}

	/**
	 * Strip HTML tags from text
	 *
	 * @since 2.0.0
	 * @param string $html HTML content.
	 * @return string Plain text.
	 */
	protected function stripHtml( string $html ): string {
		return trim( wp_strip_all_tags( $html ) );
	}

	/**
	 * Get home URL
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function getHomeUrl(): string {
		return home_url( '/' );
	}

	/**
	 * Get organization ID
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function getOrganizationId(): string {
		return $this->getHomeUrl() . '#organization';
	}
}

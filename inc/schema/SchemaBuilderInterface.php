<?php
/**
 * Schema Builder Interface
 *
 * Contract for schema.org markup builders (Builder Pattern).
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
 * Schema Builder Interface
 *
 * Each builder creates a specific schema.org type.
 *
 * @since 2.0.0
 */
interface SchemaBuilderInterface {

	/**
	 * Get schema type
	 *
	 * @since 2.0.0
	 * @return string Schema type (e.g., 'Organization', 'FAQPage').
	 */
	public function getType(): string;

	/**
	 * Check if schema should be rendered
	 *
	 * @since 2.0.0
	 * @return bool True if conditions met for rendering.
	 */
	public function shouldRender(): bool;

	/**
	 * Build schema data
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|null Schema data or null if cannot build.
	 */
	public function build(): ?array;

	/**
	 * Get priority for output ordering
	 *
	 * Lower number = earlier in output.
	 *
	 * @since 2.0.0
	 * @return int Priority (default 10).
	 */
	public function getPriority(): int;
}

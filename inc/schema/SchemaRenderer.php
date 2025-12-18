<?php
/**
 * Schema Renderer
 *
 * Collects and outputs all schema.org markup.
 *
 * @package    Medici_Agency
 * @subpackage Schema
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Schema;

use Medici\Schema\Builders\OrganizationBuilder;
use Medici\Schema\Builders\FaqBuilder;
use Medici\Schema\Builders\HowToBuilder;
use Medici\Schema\Builders\VideoBuilder;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Renderer
 *
 * Orchestrates schema builders and outputs JSON-LD.
 *
 * @since 2.0.0
 */
final class SchemaRenderer {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered builders
	 *
	 * @var array<SchemaBuilderInterface>
	 */
	private array $builders = array();

	/**
	 * Get singleton instance
	 *
	 * @since 2.0.0
	 * @return self
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - registers default builders
	 */
	private function __construct() {
		$this->registerDefaultBuilders();
	}

	/**
	 * Register default builders
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function registerDefaultBuilders(): void {
		$this->builders = array(
			new OrganizationBuilder(),
			new FaqBuilder(),
			new HowToBuilder(),
			new VideoBuilder(),
		);

		// Sort by priority.
		usort(
			$this->builders,
			fn( SchemaBuilderInterface $a, SchemaBuilderInterface $b ) => $a->getPriority() <=> $b->getPriority()
		);
	}

	/**
	 * Add custom builder
	 *
	 * @since 2.0.0
	 * @param SchemaBuilderInterface $builder Builder to add.
	 * @return self For chaining.
	 */
	public function addBuilder( SchemaBuilderInterface $builder ): self {
		$this->builders[] = $builder;

		// Re-sort by priority.
		usort(
			$this->builders,
			fn( SchemaBuilderInterface $a, SchemaBuilderInterface $b ) => $a->getPriority() <=> $b->getPriority()
		);

		return $this;
	}

	/**
	 * Remove builder by type
	 *
	 * @since 2.0.0
	 * @param string $type Schema type to remove.
	 * @return self For chaining.
	 */
	public function removeBuilder( string $type ): self {
		$this->builders = array_filter(
			$this->builders,
			fn( SchemaBuilderInterface $b ) => $b->getType() !== $type
		);
		return $this;
	}

	/**
	 * Get all schemas for current page
	 *
	 * @since 2.0.0
	 * @return array<array<string, mixed>> Array of schema data.
	 */
	public function getSchemas(): array {
		$schemas = array();

		foreach ( $this->builders as $builder ) {
			if ( ! $builder->shouldRender() ) {
				continue;
			}

			$schema = $builder->build();

			if ( null !== $schema ) {
				$schemas[] = $schema;
			}
		}

		/**
		 * Filter schemas before output
		 *
		 * @since 2.0.0
		 * @param array<array<string, mixed>> $schemas Array of schema data.
		 */
		return apply_filters( 'medici_schema_data', $schemas );
	}

	/**
	 * Get schema by type
	 *
	 * @since 2.0.0
	 * @param string $type Schema type (e.g., 'Organization', 'FAQPage').
	 * @return array<string, mixed>|null Schema data or null.
	 */
	public function getSchema( string $type ): ?array {
		foreach ( $this->builders as $builder ) {
			if ( $builder->getType() === $type && $builder->shouldRender() ) {
				return $builder->build();
			}
		}
		return null;
	}

	/**
	 * Render all schemas as JSON-LD
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render(): void {
		$schemas = $this->getSchemas();

		if ( empty( $schemas ) ) {
			return;
		}

		foreach ( $schemas as $schema ) {
			$this->outputJsonLd( $schema );
		}
	}

	/**
	 * Output single schema as JSON-LD
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $schema Schema data.
	 * @return void
	 */
	private function outputJsonLd( array $schema ): void {
		$json = wp_json_encode(
			$schema,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
		);

		if ( false === $json ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON is safe.
		);
	}

	/**
	 * Get JSON-LD string for all schemas
	 *
	 * @since 2.0.0
	 * @return string JSON-LD script tags.
	 */
	public function getJsonLd(): string {
		$schemas = $this->getSchemas();

		if ( empty( $schemas ) ) {
			return '';
		}

		$output = '';

		foreach ( $schemas as $schema ) {
			$json = wp_json_encode(
				$schema,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
			);

			if ( false !== $json ) {
				$output .= sprintf(
					'<script type="application/ld+json">%s</script>' . "\n",
					$json
				);
			}
		}

		return $output;
	}

	/**
	 * Get registered builder types
	 *
	 * @since 2.0.0
	 * @return array<string> Array of schema types.
	 */
	public function getRegisteredTypes(): array {
		return array_map(
			fn( SchemaBuilderInterface $b ) => $b->getType(),
			$this->builders
		);
	}

	/**
	 * Check if builder exists
	 *
	 * @since 2.0.0
	 * @param string $type Schema type.
	 * @return bool True if builder registered.
	 */
	public function hasBuilder( string $type ): bool {
		foreach ( $this->builders as $builder ) {
			if ( $builder->getType() === $type ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Output schemas in wp_head.
		add_action( 'wp_head', array( $this, 'render' ), 99 );
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

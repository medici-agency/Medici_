<?php
/**
 * Schema Module Bootstrap
 *
 * Initializes schema.org markup services with Builder pattern.
 *
 * @package    Medici_Agency
 * @subpackage Schema
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Schema Module Architecture (Builder Pattern)
|--------------------------------------------------------------------------
|
| Original: inc/schema-medical.php (666 lines, procedural)
| Refactored: 9 files with Builder pattern
|
| Structure:
| - SchemaBuilderInterface.php - Builder contract
| - AbstractSchemaBuilder.php  - Base builder with common methods
| - SchemaConfig.php           - Centralized configuration
| - SchemaRenderer.php         - Orchestrator and output service
| - builders/
|   - OrganizationBuilder.php  - Organization + ProfessionalService
|   - FaqBuilder.php           - FAQPage from details/summary
|   - HowToBuilder.php         - HowTo from ordered lists
|   - VideoBuilder.php         - VideoObject from YouTube/Vimeo
|
| Benefits:
| - Each schema type is isolated in its own builder
| - Easy to add new schema types (implement interface)
| - Configuration centralized in SchemaConfig
| - Testable (mock individual builders)
| - Extensible via addBuilder() method
|
*/

// Load interfaces and base classes.
require_once __DIR__ . '/SchemaBuilderInterface.php';
require_once __DIR__ . '/AbstractSchemaBuilder.php';
require_once __DIR__ . '/SchemaConfig.php';

// Load builders.
require_once __DIR__ . '/builders/OrganizationBuilder.php';
require_once __DIR__ . '/builders/FaqBuilder.php';
require_once __DIR__ . '/builders/HowToBuilder.php';
require_once __DIR__ . '/builders/VideoBuilder.php';

// Load renderer.
require_once __DIR__ . '/SchemaRenderer.php';

use Medici\Schema\SchemaRenderer;
use Medici\Schema\SchemaConfig;
use Medici\Schema\Builders\OrganizationBuilder;
use Medici\Schema\Builders\FaqBuilder;
use Medici\Schema\Builders\HowToBuilder;
use Medici\Schema\Builders\VideoBuilder;

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Get Schema Renderer instance
 *
 * @since 2.0.0
 * @return SchemaRenderer
 */
function medici_schema(): SchemaRenderer {
	return SchemaRenderer::getInstance();
}

/**
 * Get Schema Config instance
 *
 * @since 2.0.0
 * @return SchemaConfig
 */
function medici_schema_config(): SchemaConfig {
	return SchemaConfig::getInstance();
}

/*
 * Note: medici_build_organization_schema(), medici_build_faq_schema(),
 * medici_build_howto_schema(), medici_build_video_schema() are defined
 * in legacy inc/schema-medical.php with different signatures.
 *
 * Use medici_schema_build_*() functions for new Builder-based API.
 */

/**
 * Build Organization schema (new API)
 *
 * @since 2.0.0
 * @return array<string, mixed> Organization schema data.
 */
function medici_schema_build_organization(): array {
	$builder = new OrganizationBuilder();
	return $builder->build() ?? array();
}

/**
 * Build FAQ schema from content (new API)
 *
 * @since 2.0.0
 * @param string $content Post content to parse.
 * @return array<string, mixed>|null FAQ schema or null if no FAQs found.
 */
function medici_schema_build_faq( string $content ): ?array {
	$builder = new FaqBuilder();
	$builder->setContent( $content );
	return $builder->build();
}

/**
 * Build HowTo schema from content (new API)
 *
 * @since 2.0.0
 * @param string $content Post content to parse.
 * @return array<string, mixed>|null HowTo schema or null if no steps found.
 */
function medici_schema_build_howto( string $content ): ?array {
	$builder = new HowToBuilder();
	$builder->setContent( $content );
	return $builder->build();
}

/**
 * Build Video schema from content (new API)
 *
 * @since 2.0.0
 * @param string $content Post content to parse.
 * @return array<string, mixed>|null Video schema or null if no videos found.
 */
function medici_schema_build_video( string $content ): ?array {
	$builder = new VideoBuilder();
	$builder->setContent( $content );
	return $builder->build();
}

/*
|--------------------------------------------------------------------------
| Backwards Compatibility Layer
|--------------------------------------------------------------------------
|
| These classes provide compatibility with old function calls.
| Usage: Schema_Medical_Compat::build_organization_schema()
|
*/

/**
 * Backwards compatibility class for schema functions
 *
 * @since 2.0.0
 * @deprecated Use medici_schema() instead.
 */
class Schema_Medical_Compat {

	/**
	 * Build Organization schema
	 *
	 * @deprecated Use medici_schema_build_organization()
	 * @return array<string, mixed>
	 */
	public static function build_organization_schema(): array {
		return medici_schema_build_organization();
	}

	/**
	 * Build FAQ schema
	 *
	 * @deprecated Use medici_schema_build_faq()
	 * @param string $content Post content.
	 * @return array<string, mixed>|null
	 */
	public static function build_faq_schema( string $content ): ?array {
		return medici_schema_build_faq( $content );
	}

	/**
	 * Build HowTo schema
	 *
	 * @deprecated Use medici_schema_build_howto()
	 * @param string $content Post content.
	 * @return array<string, mixed>|null
	 */
	public static function build_howto_schema( string $content ): ?array {
		return medici_schema_build_howto( $content );
	}

	/**
	 * Build Video schema
	 *
	 * @deprecated Use medici_schema_build_video()
	 * @param string $content Post content.
	 * @return array<string, mixed>|null
	 */
	public static function build_video_schema( string $content ): ?array {
		return medici_schema_build_video( $content );
	}
}

/*
|--------------------------------------------------------------------------
| Initialize Schema Module
|--------------------------------------------------------------------------
*/

/**
 * Initialize Schema services
 *
 * @since 2.0.0
 * @return void
 */
function medici_init_schema(): void {
	// Initialize renderer with WordPress hooks.
	medici_schema()->init();

	/**
	 * Action fired when Schema module is initialized
	 *
	 * @since 2.0.0
	 * @param SchemaRenderer $renderer Schema renderer instance.
	 */
	do_action( 'medici_schema_init', medici_schema() );
}

// Initialize on WordPress init.
add_action( 'init', 'medici_init_schema', 5 );

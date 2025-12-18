<?php
/**
 * Blog Module Bootstrap
 *
 * Loads all blog module classes and provides backwards compatibility
 * with legacy function names.
 *
 * @package    Medici_Agency
 * @subpackage Blog
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Blog;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load module classes.
require_once __DIR__ . '/BlogPostRepository.php';
require_once __DIR__ . '/ReadingTimeService.php';
require_once __DIR__ . '/PostViewsService.php';

/**
 * Blog Module Container
 *
 * Simple service locator for blog module services.
 * Provides lazy loading and singleton instances.
 *
 * @since 2.0.0
 */
final class BlogModule {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Service instances
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

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
	 * Get repository instance
	 *
	 * @since 2.0.0
	 * @return BlogPostRepository
	 */
	public function getRepository(): BlogPostRepository {
		if ( ! isset( $this->services['repository'] ) ) {
			$this->services['repository'] = new BlogPostRepository();
		}

		return $this->services['repository'];
	}

	/**
	 * Get reading time service instance
	 *
	 * @since 2.0.0
	 * @return ReadingTimeService
	 */
	public function getReadingTimeService(): ReadingTimeService {
		if ( ! isset( $this->services['reading_time'] ) ) {
			$this->services['reading_time'] = new ReadingTimeService( $this->getRepository() );
		}

		return $this->services['reading_time'];
	}

	/**
	 * Get post views service instance
	 *
	 * @since 2.0.0
	 * @return PostViewsService
	 */
	public function getPostViewsService(): PostViewsService {
		if ( ! isset( $this->services['post_views'] ) ) {
			$this->services['post_views'] = new PostViewsService( $this->getRepository() );
		}

		return $this->services['post_views'];
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function init(): void {
		// Track views on single post pages.
		add_action( 'wp', array( $this->getPostViewsService(), 'trackCurrentPost' ) );

		// Auto-update reading time on post save.
		add_action( 'save_post_' . BlogPostRepository::POST_TYPE, array( $this, 'onPostSave' ), 15, 2 );
	}

	/**
	 * Handle post save
	 *
	 * @since 2.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function onPostSave( int $post_id, \WP_Post $post ): void {
		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip revisions.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only for published posts.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Update reading time.
		$this->getReadingTimeService()->update( $post_id );

		// Invalidate cache.
		$this->getRepository()->invalidateCache( $post_id );
	}

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}
}

// ============================================================================
// BACKWARDS COMPATIBILITY LAYER
// ============================================================================
// These functions provide compatibility with existing code that uses
// legacy function names. They delegate to the new OOP services.
// ============================================================================

/**
 * Get blog module instance
 *
 * Helper function for accessing the blog module.
 *
 * @since 2.0.0
 * @return BlogModule
 */
function medici_blog(): BlogModule {
	return BlogModule::getInstance();
}

// Initialize the module.
add_action(
	'init',
	function () {
		medici_blog()->init();
	},
	5
);

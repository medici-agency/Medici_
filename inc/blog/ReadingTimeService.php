<?php
/**
 * Reading Time Service
 *
 * Service for calculating and managing post reading times.
 * Supports Ukrainian/Cyrillic content with proper word counting.
 *
 * @package    Medici_Agency
 * @subpackage Blog
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Blog;

use WP_Post;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reading Time Service Class
 *
 * Calculates reading time for blog posts with support for
 * Ukrainian/Russian content using multibyte word counting.
 *
 * @since 2.0.0
 */
final class ReadingTimeService {

	/**
	 * Default words per minute reading speed
	 */
	public const DEFAULT_WPM = 200;

	/**
	 * Minimum reading time in minutes
	 */
	public const MIN_READING_TIME = 1;

	/**
	 * Words per minute setting
	 *
	 * @var int
	 */
	private int $wordsPerMinute;

	/**
	 * Blog post repository
	 *
	 * @var BlogPostRepository
	 */
	private BlogPostRepository $repository;

	/**
	 * Constructor
	 *
	 * @param BlogPostRepository|null $repository Repository instance.
	 * @param int                     $wpm        Words per minute.
	 */
	public function __construct( ?BlogPostRepository $repository = null, int $wpm = self::DEFAULT_WPM ) {
		$this->repository     = $repository ?? new BlogPostRepository();
		$this->wordsPerMinute = max( 1, $wpm );
	}

	/**
	 * Calculate reading time for post content
	 *
	 * Uses multibyte word counting for proper Ukrainian/Russian support.
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return int Reading time in minutes.
	 */
	public function calculate( string $content ): int {
		// Strip HTML tags.
		$text = wp_strip_all_tags( $content );

		// Count words using regex (supports any Unicode letters).
		$word_count = 0;
		if ( preg_match_all( '/\p{L}+/u', $text, $matches ) ) {
			$word_count = count( $matches[0] );
		}

		if ( 0 === $word_count ) {
			return self::MIN_READING_TIME;
		}

		$reading_time = (int) ceil( $word_count / $this->wordsPerMinute );

		return max( self::MIN_READING_TIME, $reading_time );
	}

	/**
	 * Calculate reading time for a post by ID
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int Reading time in minutes.
	 */
	public function calculateForPost( int $post_id ): int {
		$post = $this->repository->find( $post_id );

		if ( ! $post ) {
			return self::MIN_READING_TIME;
		}

		return $this->calculate( $post->post_content );
	}

	/**
	 * Get reading time for a post (with auto-calculation and caching)
	 *
	 * If reading time is not set, calculates and saves it.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int Reading time in minutes.
	 */
	public function get( int $post_id ): int {
		$reading_time = $this->repository->getReadingTime( $post_id );

		if ( 0 === $reading_time ) {
			$reading_time = $this->calculateForPost( $post_id );
			$this->repository->setReadingTime( $post_id, $reading_time );
		}

		return $reading_time;
	}

	/**
	 * Update reading time for a post
	 *
	 * Recalculates and saves the reading time.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int New reading time in minutes.
	 */
	public function update( int $post_id ): int {
		$reading_time = $this->calculateForPost( $post_id );
		$this->repository->setReadingTime( $post_id, $reading_time );

		return $reading_time;
	}

	/**
	 * Format reading time for display
	 *
	 * @since 2.0.0
	 * @param int $minutes Reading time in minutes.
	 * @return string Formatted reading time.
	 */
	public function format( int $minutes ): string {
		if ( 1 === $minutes ) {
			return sprintf(
				/* translators: %d: number of minutes */
				__( '%d хвилина', 'medici.agency' ),
				$minutes
			);
		}

		// Ukrainian pluralization.
		$last_digit      = $minutes % 10;
		$last_two_digits = $minutes % 100;

		if ( $last_digit >= 2 && $last_digit <= 4 && ( $last_two_digits < 10 || $last_two_digits >= 20 ) ) {
			return sprintf(
				/* translators: %d: number of minutes */
				__( '%d хвилини', 'medici.agency' ),
				$minutes
			);
		}

		return sprintf(
			/* translators: %d: number of minutes */
			__( '%d хвилин', 'medici.agency' ),
			$minutes
		);
	}

	/**
	 * Get formatted reading time for a post
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return string Formatted reading time.
	 */
	public function getFormatted( int $post_id ): string {
		return $this->format( $this->get( $post_id ) );
	}

	/**
	 * Get words per minute setting
	 *
	 * @since 2.0.0
	 * @return int Words per minute.
	 */
	public function getWordsPerMinute(): int {
		return $this->wordsPerMinute;
	}
}

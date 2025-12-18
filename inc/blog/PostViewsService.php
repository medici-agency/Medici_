<?php
/**
 * Post Views Service
 *
 * Service for tracking and retrieving blog post views.
 * Uses atomic increments to prevent race conditions.
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

/**
 * Post Views Service Class
 *
 * Handles view counting for blog posts with atomic increments
 * and caching for popular posts queries.
 *
 * @since 2.0.0
 */
final class PostViewsService {

	/**
	 * Session key for tracking viewed posts
	 */
	private const SESSION_KEY = 'medici_viewed_posts';

	/**
	 * Blog post repository
	 *
	 * @var BlogPostRepository
	 */
	private BlogPostRepository $repository;

	/**
	 * Whether to track logged-in users
	 *
	 * @var bool
	 */
	private bool $trackLoggedInUsers;

	/**
	 * Constructor
	 *
	 * @param BlogPostRepository|null $repository         Repository instance.
	 * @param bool                    $trackLoggedInUsers Whether to track logged-in users.
	 */
	public function __construct(
		?BlogPostRepository $repository = null,
		bool $trackLoggedInUsers = false
	) {
		$this->repository         = $repository ?? new BlogPostRepository();
		$this->trackLoggedInUsers = $trackLoggedInUsers;
	}

	/**
	 * Get views count for a post
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return int Views count.
	 */
	public function get( int $post_id ): int {
		return $this->repository->getViews( $post_id );
	}

	/**
	 * Increment views for a post
	 *
	 * Checks if user has already viewed this post in current session
	 * to prevent duplicate counting.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return bool True if view was counted.
	 */
	public function track( int $post_id ): bool {
		// Check if we should track this user.
		if ( ! $this->shouldTrack() ) {
			return false;
		}

		// Check if already viewed in this session (using transients for stateless tracking).
		if ( $this->hasViewedInSession( $post_id ) ) {
			return false;
		}

		// Increment view count.
		$result = $this->repository->incrementViews( $post_id );

		if ( $result ) {
			$this->markAsViewed( $post_id );
		}

		return $result;
	}

	/**
	 * Check if current request should be tracked
	 *
	 * @since 2.0.0
	 * @return bool True if should track.
	 */
	private function shouldTrack(): bool {
		// Don't track admin requests.
		if ( is_admin() ) {
			return false;
		}

		// Don't track AJAX requests.
		if ( wp_doing_ajax() ) {
			return false;
		}

		// Don't track cron jobs.
		if ( wp_doing_cron() ) {
			return false;
		}

		// Check if we should track logged-in users.
		if ( ! $this->trackLoggedInUsers && is_user_logged_in() ) {
			return false;
		}

		// Don't track bots (basic detection).
		if ( $this->isBot() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if request is from a bot
	 *
	 * Basic bot detection using User-Agent.
	 *
	 * @since 2.0.0
	 * @return bool True if likely a bot.
	 */
	private function isBot(): bool {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		if ( empty( $user_agent ) ) {
			return true;
		}

		$bot_patterns = array(
			'bot',
			'crawl',
			'spider',
			'slurp',
			'googlebot',
			'bingbot',
			'yandex',
			'baidu',
			'facebot',
			'ia_archiver',
		);

		$user_agent_lower = strtolower( $user_agent );

		foreach ( $bot_patterns as $pattern ) {
			if ( false !== strpos( $user_agent_lower, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if post was viewed in current session
	 *
	 * Uses IP-based transients for stateless session tracking.
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return bool True if already viewed.
	 */
	private function hasViewedInSession( int $post_id ): bool {
		$session_key = $this->getSessionKey();
		$viewed      = get_transient( $session_key );

		if ( false === $viewed || ! is_array( $viewed ) ) {
			return false;
		}

		return in_array( $post_id, $viewed, true );
	}

	/**
	 * Mark post as viewed in current session
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function markAsViewed( int $post_id ): void {
		$session_key = $this->getSessionKey();
		$viewed      = get_transient( $session_key );

		if ( false === $viewed || ! is_array( $viewed ) ) {
			$viewed = array();
		}

		$viewed[] = $post_id;

		// Keep only last 100 viewed posts to prevent memory issues.
		if ( count( $viewed ) > 100 ) {
			$viewed = array_slice( $viewed, -100 );
		}

		// Session expires after 1 hour.
		set_transient( $session_key, $viewed, HOUR_IN_SECONDS );
	}

	/**
	 * Get session key for current visitor
	 *
	 * Uses hashed IP + User-Agent for privacy.
	 *
	 * @since 2.0.0
	 * @return string Session key.
	 */
	private function getSessionKey(): string {
		$ip = $this->getClientIp();
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		// Hash for privacy.
		return self::SESSION_KEY . '_' . md5( $ip . $ua );
	}

	/**
	 * Get client IP address
	 *
	 * Handles proxies (Cloudflare, load balancers).
	 *
	 * @since 2.0.0
	 * @return string Client IP.
	 */
	private function getClientIp(): string {
		// Cloudflare.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		// Proxy.
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}

		// Direct.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}

	/**
	 * Format views count for display
	 *
	 * @since 2.0.0
	 * @param int $views Views count.
	 * @return string Formatted views.
	 */
	public function format( int $views ): string {
		if ( $views >= 1000000 ) {
			return sprintf( '%.1fM', $views / 1000000 );
		}

		if ( $views >= 1000 ) {
			return sprintf( '%.1fK', $views / 1000 );
		}

		return (string) $views;
	}

	/**
	 * Get formatted views for a post
	 *
	 * @since 2.0.0
	 * @param int $post_id Post ID.
	 * @return string Formatted views.
	 */
	public function getFormatted( int $post_id ): string {
		return $this->format( $this->get( $post_id ) );
	}

	/**
	 * Track view on single post page
	 *
	 * Hook this method to 'wp' action for automatic tracking.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function trackCurrentPost(): void {
		if ( ! is_singular( BlogPostRepository::POST_TYPE ) ) {
			return;
		}

		$post_id = get_the_ID();
		if ( $post_id ) {
			$this->track( (int) $post_id );
		}
	}
}

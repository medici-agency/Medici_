<?php
/**
 * Popular Posts Widget
 *
 * Displays most viewed blog posts with thumbnail fallback, caching, and exclude current post option.
 *
 * Features:
 * - View count tracking (with caching)
 * - Thumbnail fallback images
 * - Exclude current post option
 * - Custom thumbnail size
 * - Lazy loading images
 * - Responsive design
 *
 * @package    Medici_Agency
 * @subpackage Widgets
 * @since      1.4.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popular Posts Widget Class
 *
 * @since 1.4.0
 */
class Medici_Popular_Posts_Widget extends WP_Widget {

	/**
	 * Cache key prefix
	 */
	private const CACHE_KEY_PREFIX = 'medici_popular_posts_';

	/**
	 * Cache expiration time (12 hours)
	 */
	private const CACHE_EXPIRATION = 12 * HOUR_IN_SECONDS;

	/**
	 * Thumbnail size
	 */
	private const THUMBNAIL_SIZE = 'medici-widget-thumb';

	/**
	 * Fallback image path
	 */
	private const FALLBACK_IMAGE = '/img/fallback-post.svg';

	/**
	 * Widget constructor
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		parent::__construct(
			'medici_popular_posts',
			__( '📊 Medici - Popular Posts', 'medici.agency' ),
			array(
				'description' => __( 'Відображає найпопулярніші статті блогу з thumbnail fallback та кешуванням', 'medici.agency' ),
				'classname'   => 'medici-popular-posts-widget',
			)
		);

		// Register custom thumbnail size
		add_action( 'after_setup_theme', array( $this, 'register_thumbnail_size' ) );

		// Note: Cache invalidation removed from updated_post_meta hook to prevent
		// cache clear on every page view. Cache now relies on 12-hour expiration.
		// Manual cache clear available through widget settings update.
	}

	/**
	 * Register custom thumbnail size
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function register_thumbnail_size(): void {
		add_image_size( self::THUMBNAIL_SIZE, 80, 80, true );
	}

	/**
	 * Clear cache manually (called on widget settings update)
	 *
	 * Note: Automatic cache clearing on view count update is intentionally disabled
	 * to prevent cache invalidation on every page view. This would defeat the purpose
	 * of caching and cause unnecessary DB queries.
	 *
	 * Cache now uses 12-hour expiration (CACHE_EXPIRATION) and is only cleared when:
	 * - Widget settings are updated (update() method)
	 * - Manual cache flush is triggered
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function manual_cache_clear(): void {
		$this->clear_cache();
	}

	/**
	 * Widget output
	 *
	 * @since 1.4.0
	 * @param array $args     Widget arguments
	 * @param array $instance Widget instance
	 * @return void
	 */
	public function widget( $args, $instance ): void {
		$title           = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Популярні статті', 'medici.agency' );
		$number          = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$exclude_current = ! empty( $instance['exclude_current'] );
		$show_views      = ! empty( $instance['show_views'] );
		$show_date       = ! empty( $instance['show_date'] );

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// Get popular posts (cached)
		$posts = $this->get_popular_posts( $number, $exclude_current );

		if ( ! empty( $posts ) ) {
			echo '<ul class="medici-popular-posts-list">';

			foreach ( $posts as $post ) {
				$this->render_post_item( $post, $show_views, $show_date );
			}

			echo '</ul>';
		} else {
			echo '<p class="medici-no-posts">' . esc_html__( 'Немає популярних статей', 'medici.agency' ) . '</p>';
		}

		echo $args['after_widget'];
	}

	/**
	 * Get popular posts with caching
	 *
	 * @since 1.4.0
	 * @param int  $number          Number of posts
	 * @param bool $exclude_current Exclude current post
	 * @return array Popular posts
	 */
	private function get_popular_posts( int $number, bool $exclude_current ): array {
		// Generate cache key
		$cache_key = self::CACHE_KEY_PREFIX . $number . '_' . ( $exclude_current ? 'excl' : 'all' );

		// Try to get from cache
		$cached_posts = get_transient( $cache_key );

		if ( false !== $cached_posts && is_array( $cached_posts ) ) {
			return $cached_posts;
		}

		// Query popular posts
		$query_args = array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_medici_post_views',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);

		// Exclude current post
		if ( $exclude_current && is_singular( 'medici_blog' ) ) {
			$query_args['post__not_in'] = array( get_the_ID() );
		}

		$query = new WP_Query( $query_args );

		// Prepare posts data
		$posts = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$posts[] = array(
					'id'        => $post_id,
					'title'     => get_the_title( $post_id ),
					'permalink' => get_permalink( $post_id ),
					'views'     => (int) get_post_meta( $post_id, '_medici_post_views', true ),
					'date'      => get_the_date( 'j M Y', $post_id ),
					'thumbnail' => $this->get_post_thumbnail( $post_id ),
				);
			}
		}

		// Cache results
		set_transient( $cache_key, $posts, self::CACHE_EXPIRATION );

		return $posts;
	}

	/**
	 * Get post thumbnail with fallback
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID
	 * @return array Thumbnail data (url, alt)
	 */
	private function get_post_thumbnail( int $post_id ): array {
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			$thumbnail = wp_get_attachment_image_src( $thumbnail_id, self::THUMBNAIL_SIZE );

			if ( $thumbnail ) {
				return array(
					'url'    => $thumbnail[0],
					'width'  => $thumbnail[1],
					'height' => $thumbnail[2],
					'alt'    => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id ),
				);
			}
		}

		// Fallback image
		$fallback_url = get_stylesheet_directory_uri() . self::FALLBACK_IMAGE;

		return array(
			'url'    => $fallback_url,
			'width'  => 80,
			'height' => 80,
			'alt'    => get_the_title( $post_id ),
		);
	}

	/**
	 * Render post item
	 *
	 * @since 1.4.0
	 * @param array $post       Post data
	 * @param bool  $show_views Show view count
	 * @param bool  $show_date  Show date
	 * @return void
	 */
	private function render_post_item( array $post, bool $show_views, bool $show_date ): void {
		?>
		<li class="medici-popular-post-item">
			<a href="<?php echo esc_url( $post['permalink'] ); ?>" class="medici-popular-post-link">
				<div class="medici-popular-post-thumbnail">
					<img
						src="<?php echo esc_url( $post['thumbnail']['url'] ); ?>"
						alt="<?php echo esc_attr( $post['thumbnail']['alt'] ); ?>"
						width="<?php echo esc_attr( $post['thumbnail']['width'] ); ?>"
						height="<?php echo esc_attr( $post['thumbnail']['height'] ); ?>"
						loading="lazy"
					/>
				</div>
				<div class="medici-popular-post-content">
					<h4 class="medici-popular-post-title"><?php echo esc_html( $post['title'] ); ?></h4>
					<?php if ( $show_views || $show_date ) : ?>
						<div class="medici-popular-post-meta">
							<?php if ( $show_views ) : ?>
								<span class="medici-popular-post-views">👁 <?php echo esc_html( number_format_i18n( $post['views'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( $show_date ) : ?>
								<span class="medici-popular-post-date">📅 <?php echo esc_html( $post['date'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</a>
		</li>
		<?php
	}

	/**
	 * Widget form
	 *
	 * @since 1.4.0
	 * @param array $instance Widget instance
	 * @return void
	 */
	public function form( $instance ): void {
		$title           = isset( $instance['title'] ) ? $instance['title'] : __( 'Популярні статті', 'medici.agency' );
		$number          = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$exclude_current = isset( $instance['exclude_current'] ) ? (bool) $instance['exclude_current'] : true;
		$show_views      = isset( $instance['show_views'] ) ? (bool) $instance['show_views'] : true;
		$show_date       = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Заголовок:', 'medici.agency' ); ?>
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				<?php esc_html_e( 'Кількість статей:', 'medici.agency' ); ?>
			</label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>"
				type="number"
				step="1"
				min="1"
				max="10"
				value="<?php echo esc_attr( (string) $number ); ?>"
				size="3"
			/>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'exclude_current' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'exclude_current' ) ); ?>"
				<?php checked( $exclude_current ); ?>
			/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'exclude_current' ) ); ?>">
				<?php esc_html_e( 'Виключити поточну статтю', 'medici.agency' ); ?>
			</label>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_views' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_views' ) ); ?>"
				<?php checked( $show_views ); ?>
			/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_views' ) ); ?>">
				<?php esc_html_e( 'Показувати кількість переглядів', 'medici.agency' ); ?>
			</label>
		</p>

		<p>
			<input
				class="checkbox"
				type="checkbox"
				id="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_date' ) ); ?>"
				<?php checked( $show_date ); ?>
			/>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>">
				<?php esc_html_e( 'Показувати дату публікації', 'medici.agency' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Update widget instance
	 *
	 * @since 1.4.0
	 * @param array $new_instance New instance
	 * @param array $old_instance Old instance
	 * @return array Updated instance
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance = array();

		$instance['title']           = ! empty( $new_instance['title'] )
			? sanitize_text_field( $new_instance['title'] )
			: '';
		$instance['number']          = ! empty( $new_instance['number'] )
			? absint( $new_instance['number'] )
			: 5;
		$instance['exclude_current'] = ! empty( $new_instance['exclude_current'] );
		$instance['show_views']      = ! empty( $new_instance['show_views'] );
		$instance['show_date']       = ! empty( $new_instance['show_date'] );

		// Clear cache when settings changed
		$this->clear_cache();

		return $instance;
	}

	/**
	 * Clear all cached data
	 *
	 * @since 1.4.0
	 * @return void
	 */
	private function clear_cache(): void {
		global $wpdb;

		// Delete all transients with prefix
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				 WHERE option_name LIKE %s
				 OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::CACHE_KEY_PREFIX ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::CACHE_KEY_PREFIX ) . '%'
			)
		);
	}
}

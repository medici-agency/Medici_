<?php
/**
 * Blog Meta Fields
 *
 * Registers and manages custom meta fields for the blog post type.
 * Includes: reading time, featured status, publication date, author name, and post views.
 *
 * @package    Medici_Agency
 * @subpackage Meta_Fields
 * @since      1.1.0
 * @version    1.1.1
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blog Meta Fields Configuration Class
 *
 * @since 1.1.0
 */
final class Medici_Blog_Meta_Config {

	/**
	 * Meta field keys
	 */
	public const META_READING_TIME     = '_medici_reading_time';
	public const META_FEATURED         = '_medici_featured_article';
	public const META_PUBLICATION_DATE = '_medici_publication_date';
	public const META_AUTHOR_NAME      = '_medici_author_name';
	public const META_POST_VIEWS       = '_medici_post_views';
	public const META_CUSTOM_PUB_DATE  = '_medici_custom_publish_date';

	/**
	 * Configuration constants
	 */
	public const WORDS_PER_MINUTE = 200;
	public const MIN_READING_TIME = 1;

	/**
	 * Meta box ID
	 */
	public const META_BOX_ID = 'medici_blog_meta_box';

	/**
	 * Get all meta field keys
	 *
	 * @since 1.1.0
	 * @return array Meta field keys
	 */
	public static function get_meta_keys(): array {
		return array(
			self::META_READING_TIME,
			self::META_FEATURED,
			self::META_PUBLICATION_DATE,
			self::META_AUTHOR_NAME,
			self::META_POST_VIEWS,
			self::META_CUSTOM_PUB_DATE,
		);
	}
}

/**
 * Register meta box for blog posts
 *
 * @since 1.0.0
 * @return void
 */
function medici_add_blog_meta_box(): void {
	add_meta_box(
		Medici_Blog_Meta_Config::META_BOX_ID,
		__( 'Налаштування статті', 'medici.agency' ),
		'medici_render_blog_meta_box',
		'medici_blog',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'medici_add_blog_meta_box' );

/**
 * Render meta box content
 *
 * @since 1.0.0
 * @param WP_Post $post Current post object
 * @return void
 */
function medici_render_blog_meta_box( WP_Post $post ): void {
	// Add nonce for security
	wp_nonce_field( 'medici_blog_meta_box', 'medici_blog_meta_nonce' );

	// Get current meta values
	$meta_values = medici_get_blog_meta_values( (int) $post->ID );

	// Auto-calculate reading time if empty
	if ( empty( $meta_values['reading_time'] ) ) {
		$meta_values['reading_time'] = (string) medici_calculate_reading_time( (int) $post->ID );
	}

	// Default custom publish date to actual if empty
	if ( empty( $meta_values['custom_pub_date'] ) ) {
		$meta_values['custom_pub_date'] = get_the_date( 'Y-m-d', $post->ID );
	}

	// Render fields
	?>
	<div class="medici-meta-fields">

		<!-- Reading Time -->
		<p>
			<label for="medici_reading_time">
				<strong><?php esc_html_e( 'Час читання (хв)', 'medici.agency' ); ?></strong>
			</label>
			<input
				type="number"
				id="medici_reading_time"
				name="medici_reading_time"
				value="<?php echo esc_attr( $meta_values['reading_time'] ); ?>"
				min="1"
				step="1"
				class="widefat"
			/>
			<small><?php esc_html_e( 'Автоматично розраховується (200 слів/хв)', 'medici.agency' ); ?></small>
		</p>

		<!-- Featured Article -->
		<p>
			<label>
				<input
					type="checkbox"
					name="medici_featured_article"
					value="1"
					<?php checked( $meta_values['featured'], '1' ); ?>
				/>
				<strong><?php esc_html_e( 'Рекомендована стаття', 'medici.agency' ); ?></strong>
			</label>
			<br>
			<small><?php esc_html_e( 'Показати на головній сторінці', 'medici.agency' ); ?></small>
		</p>

		<!-- Publication Date -->
		<p>
			<label for="medici_publication_date">
				<strong><?php esc_html_e( 'Дата публікації (для відображення)', 'medici.agency' ); ?></strong>
			</label>
			<input
				type="date"
				id="medici_publication_date"
				name="medici_publication_date"
				value="<?php echo esc_attr( $meta_values['publication_date'] ); ?>"
				class="widefat"
			/>
			<small><?php esc_html_e( 'Залиште порожнім для фактичної дати', 'medici.agency' ); ?></small>
		</p>

		<!-- Author Name -->
		<p>
			<label for="medici_author_name">
				<strong><?php esc_html_e( "Ім'я автора", 'medici.agency' ); ?></strong>
			</label>
			<input
				type="text"
				id="medici_author_name"
				name="medici_author_name"
				value="<?php echo esc_attr( $meta_values['author_name'] ); ?>"
				class="widefat"
				placeholder="<?php echo esc_attr( get_the_author_meta( 'display_name', (int) $post->post_author ) ); ?>"
			/>
			<small><?php esc_html_e( 'Залиште порожнім для автора WordPress', 'medici.agency' ); ?></small>
		</p>

		<!-- Post Views (Read-only) -->
		<p>
			<label>
				<strong><?php esc_html_e( 'Перегляди', 'medici.agency' ); ?></strong>
			</label>
			<br>
			<code><?php echo esc_html( $meta_values['post_views'] ?: '0' ); ?></code>
		</p>

		<!-- Custom Publish Date -->
		<p>
			<label for="medici_custom_publish_date">
				<strong><?php esc_html_e( 'Користувацька дата публікації', 'medici.agency' ); ?></strong>
			</label>
			<input
				type="date"
				id="medici_custom_publish_date"
				name="medici_custom_publish_date"
				value="<?php echo esc_attr( $meta_values['custom_pub_date'] ); ?>"
				class="widefat"
			/>
			<small>
				<?php
				printf(
					/* translators: %s: Actual publish date */
					esc_html__( 'Фактична дата: %s', 'medici.agency' ),
					esc_html( get_the_date( 'j F Y', $post->ID ) )
				);
				?>
			</small>
		</p>

	</div>

	<style>
		.medici-meta-fields p { margin-bottom: 15px; }
		.medici-meta-fields label { display: block; margin-bottom: 5px; }
		.medici-meta-fields small { display: block; color: #666; font-style: italic; margin-top: 3px; }
		.medici-meta-fields code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
	</style>
	<?php
}

/**
 * Get all meta values for a post
 *
 * @since 1.1.0
 * @param int $post_id Post ID
 * @return array Meta values
 */
function medici_get_blog_meta_values( int $post_id ): array {
	return array(
		'reading_time'     => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_READING_TIME, true ),
		'featured'         => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_FEATURED, true ),
		'publication_date' => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_PUBLICATION_DATE, true ),
		'author_name'      => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_AUTHOR_NAME, true ),
		'post_views'       => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_POST_VIEWS, true ),
		'custom_pub_date'  => get_post_meta( $post_id, Medici_Blog_Meta_Config::META_CUSTOM_PUB_DATE, true ),
	);
}

/**
 * Save meta box data
 *
 * @since 1.0.0
 * @param int|string $post_id Post ID
 * @return void
 */
function medici_save_blog_meta_box( $post_id ): void {
	$post_id = (int) $post_id;

	// Verify nonce
	if (
		! isset( $_POST['medici_blog_meta_nonce'] ) ||
		! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['medici_blog_meta_nonce'] ) ),
			'medici_blog_meta_box'
		)
	) {
		return;
	}

	// Check autosave / revisions
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save meta fields
	$fields = array(
		'medici_reading_time'        => Medici_Blog_Meta_Config::META_READING_TIME,
		'medici_featured_article'    => Medici_Blog_Meta_Config::META_FEATURED,
		'medici_publication_date'    => Medici_Blog_Meta_Config::META_PUBLICATION_DATE,
		'medici_author_name'         => Medici_Blog_Meta_Config::META_AUTHOR_NAME,
		'medici_custom_publish_date' => Medici_Blog_Meta_Config::META_CUSTOM_PUB_DATE,
	);

	foreach ( $fields as $field_name => $meta_key ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
			update_post_meta( $post_id, $meta_key, $value );
		} elseif ( 'medici_featured_article' === $field_name ) {
			// Handle checkbox (featured article)
			delete_post_meta( $post_id, $meta_key );
		}
	}
}
add_action( 'save_post_medici_blog', 'medici_save_blog_meta_box' );

/**
 * Calculate reading time based on word count
 *
 * Використовує багатобайтовий підрахунок слів для коректної роботи
 * з українським/російським контентом.
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @return int Reading time in minutes
 */
function medici_calculate_reading_time( int $post_id ): int {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return Medici_Blog_Meta_Config::MIN_READING_TIME;
	}

	$content = $post->post_content;
	$text    = wp_strip_all_tags( $content );

	// Підрахунок слів для будь-яких мов (послідовності літер)
	$word_count = 0;
	if ( preg_match_all( '/\p{L}+/u', $text, $matches ) ) {
		$word_count = count( $matches[0] );
	}

	if ( 0 === $word_count ) {
		return Medici_Blog_Meta_Config::MIN_READING_TIME;
	}

	$reading_time = (int) ceil( $word_count / Medici_Blog_Meta_Config::WORDS_PER_MINUTE );

	return max( Medici_Blog_Meta_Config::MIN_READING_TIME, $reading_time );
}

/**
 * Get reading time for a post
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @return int Reading time in minutes
 */
function medici_get_blog_reading_time( int $post_id ): int {
	$reading_time = get_post_meta( $post_id, Medici_Blog_Meta_Config::META_READING_TIME, true );

	if ( '' === $reading_time ) {
		$reading_time = medici_calculate_reading_time( $post_id );
		update_post_meta( $post_id, Medici_Blog_Meta_Config::META_READING_TIME, $reading_time );
	}

	return (int) $reading_time;
}

/**
 * Check if post is featured
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @return bool True if featured, false otherwise
 */
function medici_is_blog_post_featured( int $post_id ): bool {
	return '1' === get_post_meta( $post_id, Medici_Blog_Meta_Config::META_FEATURED, true );
}

/**
 * Get publication date for display
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @return string Formatted date
 */
function medici_get_blog_publication_date( int $post_id ): string {
	$custom_date = get_post_meta( $post_id, Medici_Blog_Meta_Config::META_PUBLICATION_DATE, true );

	if ( ! empty( $custom_date ) ) {
		$timestamp = strtotime( $custom_date );
		if ( false !== $timestamp ) {
			return date_i18n( 'j F Y', $timestamp );
		}
	}

	return get_the_date( 'j F Y', $post_id );
}

/**
 * Get author name for display
 *
 * @since 1.0.0
 * @param int $post_id Post ID
 * @return string Author name
 */
function medici_get_blog_author_name( int $post_id ): string {
	$custom_author = get_post_meta( $post_id, Medici_Blog_Meta_Config::META_AUTHOR_NAME, true );

	if ( ! empty( $custom_author ) ) {
		return $custom_author;
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	return get_the_author_meta( 'display_name', (int) $post->post_author );
}

/**
 * Get post views count
 *
 * @since 1.1.0
 * @param int $post_id Post ID
 * @return int Views count
 */
function medici_get_blog_post_views( int $post_id ): int {
	$views = get_post_meta( $post_id, Medici_Blog_Meta_Config::META_POST_VIEWS, true );
	return ( '' !== $views ) ? (int) $views : 0;
}

/**
 * Increment post views count
 *
 * @since 1.1.0
 * @param int $post_id Post ID
 * @return void
 */
function medici_increment_blog_post_views( int $post_id ): void {
	$views = medici_get_blog_post_views( $post_id );
	update_post_meta( $post_id, Medici_Blog_Meta_Config::META_POST_VIEWS, $views + 1 );
}

/**
 * Track post views on single blog posts
 * Excludes logged-in users to prevent admin views
 *
 * @since 1.2.0
 * @return void
 */
function medici_track_blog_post_views(): void {
	if ( ! is_singular( 'medici_blog' ) || is_user_logged_in() ) {
		return;
	}

	$post_id = get_the_ID();
	if ( $post_id ) {
		medici_increment_blog_post_views( (int) $post_id );
	}
}

// REMOVED: Duplicate tracking - OOP PostViewsService handles this via wp hook
// @see inc/blog/bootstrap.php:116
// add_action( 'wp_head', 'medici_track_blog_post_views' );

/**
 * Auto-save reading time when post is published or updated
 *
 * Автоматично розраховує та зберігає час читання при:
 * - Публікації нової статті
 * - Оновленні існуючої статті
 * - Збереженні через Gutenberg/REST API
 *
 * @since 1.1.2
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 * @return void
 */
function medici_auto_save_reading_time( int $post_id, WP_Post $post, bool $update ): void {
	// Тільки для medici_blog
	if ( 'medici_blog' !== $post->post_type ) {
		return;
	}

	// Пропускаємо autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Пропускаємо revisions
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Тільки для опублікованих статей
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Розраховуємо reading time
	$reading_time = medici_calculate_reading_time( $post_id );

	// Зберігаємо тільки якщо ще немає значення або контент змінився
	$current_reading_time = get_post_meta( $post_id, Medici_Blog_Meta_Config::META_READING_TIME, true );

	if ( '' === $current_reading_time || $update ) {
		update_post_meta( $post_id, Medici_Blog_Meta_Config::META_READING_TIME, $reading_time );
	}
}
add_action( 'save_post_medici_blog', 'medici_auto_save_reading_time', 15, 3 );
<?php
/**
 * Archive Template: Blog (medici_blog)
 *
 * Template для відображення архіву блог статей.
 * Використовує GenerateBlocks та custom CSS/JS для інтерактивного функціоналу.
 *
 * @package    Medici
 * @subpackage Blog
 * @since      1.0.15
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Отримати налаштування блогу з Settings API
$blog_title         = get_option( 'medici_blog_hero_title', __( 'Блог про медичний маркетинг', 'medici.agency' ) );
$blog_subtitle      = get_option( 'medici_blog_hero_description', __( 'Експертні статті, юридичні роз\'яснення та практичні кейси від команди Medici Agency. Допомагаємо клінікам та лікарям залучати пацієнтів без порушення законодавства.', 'medici.agency' ) );
$posts_per_page     = (int) get_option( 'medici_blog_posts_per_page', 6 );
$enable_search      = (bool) get_option( 'medici_blog_enable_search', true );
$enable_filter      = (bool) get_option( 'medici_blog_enable_filter', true );
$enable_sort        = (bool) apply_filters( 'medici_blog_enable_sort', true );
$show_featured_card = true; // Завжди показувати featured card

// Отримати статистику блогу
$total_posts = wp_count_posts( 'medici_blog' );
$published   = $total_posts->publish ?? 0;
$categories  = get_terms(
	array(
		'taxonomy'   => 'blog_category',
		'hide_empty' => false,
	)
);
$category_count = is_array( $categories ) ? count( $categories ) : 0;

// Query для featured post
$featured_post    = null;
$featured_post_id = (int) get_option( 'medici_blog_featured_post_id', 0 );

if ( $featured_post_id > 0 ) {
	// Вручну обрана стаття
	$featured_post = get_post( $featured_post_id );
	if ( ! $featured_post || $featured_post->post_type !== 'medici_blog' || $featured_post->post_status !== 'publish' ) {
		$featured_post = null;
	}
}

if ( ! $featured_post ) {
	// Автоматично - найновіша рекомендована стаття
	$featured_query = new WP_Query(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_key'       => '_medici_featured_article',
			'meta_value'     => '1',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( $featured_query->have_posts() ) {
		$featured_query->the_post();
		$featured_post = get_post();
		wp_reset_postdata();
	}
}

// Fallback: якщо немає ні вручну обраної, ні рекомендованої - взяти останню опубліковану
if ( ! $featured_post ) {
	$latest_query = new WP_Query(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( $latest_query->have_posts() ) {
		$latest_query->the_post();
		$featured_post = get_post();
		wp_reset_postdata();
	}
}

// Main Query для всіх статей (крім featured)
$exclude_ids = array();
if ( $featured_post ) {
	$exclude_ids[] = $featured_post->ID;
}

$blog_query = new WP_Query(
	array(
		'post_type'      => 'medici_blog',
		'posts_per_page' => $posts_per_page,
		'post__not_in'   => $exclude_ids,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
	)
);
?>

<div class="medici-blog-page">

	<!-- ============================= -->
	<!-- HERO SECTION -->
	<!-- ============================= -->
	<section class="medici-blog-hero">
		<div class="medici-blog-container">
<div class="medici-blog-container" style="
    /* margin-right: 40px; */*
    margin-left: 40px; */
    /* width: 1580px; */
">
			<div class="medici-blog-hero-grid">
				<!-- Hero Content -->
				<div class="medici-blog-hero-content">
					<h1 class="medici-blog-hero-title">
						<?php echo esc_html( $blog_title ); ?>
					</h1>
					<p class="medici-blog-hero-description">
						<?php echo esc_html( $blog_subtitle ); ?>
					</p>

					<!-- Hero Buttons -->
					<div class="medici-blog-hero-buttons">
						<a href="#contact" class="medici-blog-btn-primary">
							<?php esc_html_e( 'Отримати консультацію', 'medici.agency' ); ?>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M5 12h14m-7-7l7 7-7 7"/>
							</svg>
						</a>
						<a href="<?php echo esc_url( home_url( '/services' ) ); ?>" class="medici-blog-btn-secondary">
							<?php esc_html_e( 'Наші послуги', 'medici.agency' ); ?>
						</a>
					</div>
				</div>

				<!-- Featured Post Card -->
				<?php if ( $featured_post ) : ?>
					<?php
					$featured_image    = get_the_post_thumbnail_url( $featured_post->ID, 'large' );
					$featured_category = get_the_terms( $featured_post->ID, 'blog_category' );
					$featured_cat_name = $featured_category && ! is_wp_error( $featured_category ) ? $featured_category[0]->name : '';
					$featured_excerpt  = get_the_excerpt( $featured_post->ID );
					$reading_time      = (int) get_post_meta( $featured_post->ID, '_medici_reading_time', true );
					$post_date_text    = get_the_date( 'j F Y', $featured_post->ID );
					?>
					<div class="medici-blog-featured-card">
						<span class="medici-blog-featured-badge">
							⭐ <?php esc_html_e( 'Рекомендовано', 'medici.agency' ); ?>
						</span>
						<h3 class="medici-blog-featured-title">
							<?php echo esc_html( get_the_title( $featured_post->ID ) ); ?>
						</h3>
						<?php if ( $featured_excerpt ) : ?>
							<p class="medici-blog-featured-excerpt">
								<?php echo esc_html( wp_trim_words( $featured_excerpt, 25 ) ); ?>
							</p>
						<?php endif; ?>
						<div class="medici-blog-featured-meta">
							<span>📅 <?php echo esc_html( $post_date_text ); ?></span>
							<?php if ( $reading_time > 0 ) : ?>
								<span>⏱ <?php echo esc_html( (string) $reading_time ); ?> хв читання</span>
							<?php endif; ?>
						</div>
						<a href="<?php echo esc_url( get_permalink( $featured_post->ID ) ); ?>" class="medici-blog-featured-link">
							<?php esc_html_e( 'Читати статтю', 'medici.agency' ); ?>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M5 12h14m-7-7l7 7-7 7"/>
							</svg>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- ============================= -->
	<!-- FILTER SECTION -->
	<!-- ============================= -->
	<section class="medici-blog-filter-section">
		<div class="medici-blog-container">
			<div class="medici-blog-filter-box">
				<!-- Single Row: Category Filters + Sort (новий компактний layout) -->
				<div class="medici-blog-filter-row-new">
					<!-- Category Filter Tags (якщо увімкнено) -->
					<?php if ( $enable_filter && ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
						<div class="medici-blog-filter-tags">
							<button
								type="button"
								class="medici-blog-filter-tag active"
								data-category="all"
							>
								<?php esc_html_e( 'Усі статті', 'medici.agency' ); ?>
							</button>
							<?php foreach ( $categories as $category ) : ?>
								<button
									type="button"
									class="medici-blog-filter-tag"
									data-category="<?php echo esc_attr( $category->slug ); ?>"
									style="<?php echo medici_get_category_style( $category->term_id ); ?>"
								>
									<?php echo esc_html( $category->name ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<!-- Sort Dropdown (якщо увімкнено) -->
					<?php if ( $enable_sort ) : ?>
						<select id="medici-blog-sort" class="medici-blog-sort-select">
							<option value="newest"><?php esc_html_e( 'Найновіші', 'medici.agency' ); ?></option>
							<option value="popular"><?php esc_html_e( 'Найпопулярніші', 'medici.agency' ); ?></option>
							<option value="alphabetical"><?php esc_html_e( 'За алфавітом', 'medici.agency' ); ?></option>
						</select>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- ============================= -->
	<!-- BLOG GRID (MAIN CONTENT) -->
	<!-- ============================= -->
	<section class="medici-blog-posts-section">
		<div class="medici-blog-container">
			<div class="medici-blog-grid">
				<?php if ( $blog_query->have_posts() ) : ?>
					<?php
					while ( $blog_query->have_posts() ) :
						$blog_query->the_post();

						// Get post data
						$post_image       = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
						$post_category    = get_the_terms( get_the_ID(), 'blog_category' );
						$post_cat_name    = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->name : '';
						$post_cat_slug    = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->slug : '';
						$post_cat_id      = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->term_id : 0;
						$post_excerpt     = get_the_excerpt();
						$post_reading_time = (int) get_post_meta( get_the_ID(), '_medici_reading_time', true );
						$post_views       = (int) get_post_meta( get_the_ID(), '_medici_post_views', true );
						$post_date        = get_the_date( 'c' ); // ISO 8601 format for data-date
						?>

						<article
							class="medici-blog-article-card"
							data-category="<?php echo esc_attr( $post_cat_slug ); ?>"
							data-date="<?php echo esc_attr( $post_date ); ?>"
							data-views="<?php echo esc_attr( (string) $post_views ); ?>"
						>
							<!-- Article Content -->
							<div class="medici-blog-card-content">
								<!-- Category Badge -->
								<?php if ( $post_cat_name && $post_cat_id > 0 ) : ?>
									<span class="medici-blog-card-category medici-blog-category-<?php echo esc_attr( $post_cat_slug ); ?>" style="<?php echo medici_get_category_style( $post_cat_id ); ?>">
										<?php echo esc_html( $post_cat_name ); ?>
									</span>
								<?php endif; ?>

								<!-- Title -->
								<h3 class="medici-blog-card-title">
									<a href="<?php the_permalink(); ?>">
										<?php the_title(); ?>
									</a>
								</h3>

								<!-- Excerpt -->
								<?php if ( $post_excerpt ) : ?>
									<p class="medici-blog-card-excerpt">
										<?php echo esc_html( wp_trim_words( $post_excerpt, 20 ) ); ?>
									</p>
								<?php endif; ?>

								<!-- Meta (Date + Reading Time) -->
								<div class="medici-blog-card-footer">
									<span>📅 <?php echo esc_html( get_the_date( 'j F' ) ); ?></span>
									<?php if ( $post_reading_time > 0 ) : ?>
										<span>⏱ <?php echo esc_html( (string) $post_reading_time ); ?> хв</span>
									<?php endif; ?>
								</div>
							</div>
						</article>

					<?php endwhile; ?>
					<?php wp_reset_postdata(); ?>

				<?php else : ?>
					<p class="medici-blog-no-posts">
						<?php esc_html_e( 'Статей поки що немає.', 'medici.agency' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Load More Button (AJAX) -->
			<?php
			$total_pages = $blog_query->max_num_pages;
			if ( $total_pages > 1 ) :
				?>
				<div class="medici-blog-load-more-wrap">
					<button
						type="button"
						class="medici-blog-load-more-btn"
						data-page="1"
						data-max-pages="<?php echo esc_attr( (string) $total_pages ); ?>"
					>
						<span class="load-more-text"><?php esc_html_e( 'Завантажити ще', 'medici.agency' ); ?></span>
						<span class="load-more-loader" style="display: none;">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
								<path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round">
									<animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
								</path>
							</svg>
							<?php esc_html_e( 'Завантаження...', 'medici.agency' ); ?>
						</span>
					</button>
				</div>
			<?php endif; ?>

			<!-- Numbered Pagination -->
			<?php
			if ( function_exists( 'medici_render_blog_pagination' ) ) {
				medici_render_blog_pagination( $blog_query );
			}
			?>
		</div>
	</section>

	<!-- ============================= -->
	<!-- CTA SECTION -->
	<!-- ============================= -->
	<section class="medici-blog-cta-section">
		<div class="medici-blog-container">
			<div class="medici-blog-cta-content">
				<h2><?php esc_html_e( 'Не знайшли відповідь?', 'medici.agency' ); ?></h2>
				<p>
					<?php esc_html_e( 'Отримайте персональну консультацію від експертів Medici Agency з медичного маркетингу та юридичного комплаєнсу', 'medici.agency' ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="medici-blog-btn-white">
					<?php esc_html_e( 'Записатися на консультацію', 'medici.agency' ); ?>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M5 12h14m-7-7l7 7-7 7"/>
					</svg>
				</a>
			</div>
		</div>
	</section>

</div>

<?php
get_footer();

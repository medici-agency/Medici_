<?php
/**
 * Single Template: Blog Post (medici_blog)
 *
 * Template для відображення окремої статті блогу з sidebar.
 * Включає Table of Contents, Newsletter форму, та Back to Blog кнопку.
 *
 * @package    Medici
 * @subpackage Blog
 * @since      1.0.16
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Get post data
$post_id          = get_the_ID();
$post_category    = get_the_terms( $post_id, 'blog_category' );
$category_name    = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->name : '';
$category_slug    = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->slug : '';
$category_id      = $post_category && ! is_wp_error( $post_category ) ? $post_category[0]->term_id : 0;
$reading_time     = (int) get_post_meta( $post_id, '_medici_reading_time', true );
$post_views       = (int) get_post_meta( $post_id, '_medici_post_views', true );
$author_id        = get_the_author_meta( 'ID' );
$author_name      = get_the_author();
$post_date        = get_the_date( 'j F Y' );

// Note: Views are tracked automatically by PostViewsService (inc/blog/PostViewsService.php)
// on 'wp' action hook. No manual increment needed here.
?>

<div class="medici-single-post-container">

	<!-- Breadcrumb -->
	<nav class="medici-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'medici.agency' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Головна', 'medici.agency' ); ?>
		</a>
		<span>/</span>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'medici_blog' ) ); ?>">
			<?php esc_html_e( 'Блог', 'medici.agency' ); ?>
		</a>
		<?php if ( $category_name ) : ?>
			<span>/</span>
			<a href="<?php echo esc_url( get_term_link( $category_id, 'blog_category' ) ); ?>">
				<?php echo esc_html( $category_name ); ?>
			</a>
		<?php endif; ?>
		<span>/</span>
		<span class="current"><?php the_title(); ?></span>
	</nav>

	<!-- Article Layout (3 columns) -->
	<div class="medici-article-layout">

		<!-- TOC Sidebar (ліворуч) -->
		<aside class="medici-toc-sidebar">
			<div class="sidebar-section">
				<h3><?php esc_html_e( 'Зміст статті', 'medici.agency' ); ?></h3>
				<div id="medici-toc-container">
					<!-- TOC буде згенеровано JavaScript (blog-single.js) -->
				</div>
			</div>
		</aside>

		<!-- Main Content (центр) -->
		<main class="medici-article-main">

			<!-- Article Header -->
			<header class="medici-article-header">
				<?php if ( $category_name && $category_id > 0 ) : ?>
					<span class="medici-article-category" style="<?php echo medici_get_category_style( $category_id ); ?>">
						<?php echo esc_html( $category_name ); ?>
					</span>
				<?php endif; ?>

				<h1 class="medici-article-title">
					<?php the_title(); ?>
				</h1>

				<!-- Article Meta -->
				<div class="medici-article-meta">
					<div class="meta-item">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="10"/>
							<path d="M12 6v6l4 2"/>
						</svg>
						<span><?php echo esc_html( $post_date ); ?></span>
					</div>
					<?php if ( $reading_time > 0 ) : ?>
						<div class="meta-item">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/>
							</svg>
							<span><?php echo esc_html( (string) $reading_time ); ?> хв читання</span>
						</div>
					<?php endif; ?>
					<div class="meta-item">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
							<circle cx="12" cy="12" r="3"/>
						</svg>
						<span><?php echo esc_html( (string) $post_views ); ?> переглядів</span>
					</div>
				</div>
			</header>

			<!-- Article Content -->
			<div class="js-article-content medici-article-content">
				<?php
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
				?>
			</div>

			<!-- Article Navigation (Prev/Next) -->
			<?php
			$prev_post = get_previous_post( true, '', 'blog_category' );
			$next_post = get_next_post( true, '', 'blog_category' );

			if ( $prev_post || $next_post ) :
			?>
				<nav class="medici-article-nav" aria-label="<?php esc_attr_e( 'Навігація по статтях', 'medici.agency' ); ?>">
					<?php if ( $prev_post ) : ?>
						<a href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>" class="nav-card prev">
							<div class="nav-label">← <?php esc_html_e( 'Попередня стаття', 'medici.agency' ); ?></div>
							<div class="nav-title"><?php echo esc_html( get_the_title( $prev_post->ID ) ); ?></div>
						</a>
					<?php endif; ?>

					<?php if ( $next_post ) : ?>
						<a href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>" class="nav-card next">
							<div class="nav-label"><?php esc_html_e( 'Наступна стаття', 'medici.agency' ); ?> →</div>
							<div class="nav-title"><?php echo esc_html( get_the_title( $next_post->ID ) ); ?></div>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

		</main>

		<!-- Right Sidebar (праворуч) -->
		<aside class="medici-right-sidebar">

			<!-- Relevant Services Widget -->
			<?php
			if ( function_exists( 'medici_render_relevant_services_widget' ) ) {
				medici_render_relevant_services_widget( $post_id );
			}
			?>

			<!-- Newsletter Form -->
			<?php if ( function_exists( 'medici_should_show_newsletter_widget' ) && medici_should_show_newsletter_widget() ) : ?>
			<div class="sidebar-section">
				<h3><?php esc_html_e( 'Підписка на розсилку', 'medici.agency' ); ?></h3>
				<p class="newsletter-description">
					<?php esc_html_e( 'Отримуйте нові статті та корисні матеріали на пошту', 'medici.agency' ); ?>
				</p>
				<form class="newsletter-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="medici_subscribe_newsletter">
					<?php wp_nonce_field( 'medici_newsletter_subscribe', 'newsletter_nonce' ); ?>
					<input
						type="email"
						name="newsletter_email"
						placeholder="<?php esc_attr_e( 'Ваш email', 'medici.agency' ); ?>"
						required
					>
					<button type="submit">
						<?php esc_html_e( 'Підписатися', 'medici.agency' ); ?>
					</button>
				</form>
				<p class="newsletter-note">
					<?php esc_html_e( 'Ви можете відписатися в будь-який момент', 'medici.agency' ); ?>
				</p>
			</div>
			<?php endif; ?>

			<!-- Back to Blog -->
			<?php if ( function_exists( 'medici_should_show_back_to_blog_widget' ) && medici_should_show_back_to_blog_widget() ) : ?>
			<div class="sidebar-section">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'medici_blog' ) ); ?>" class="btn-back-to-blog">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5m7 7l-7-7 7-7"/>
					</svg>
					<?php esc_html_e( 'Повернутися до блогу', 'medici.agency' ); ?>
				</a>
			</div>
			<?php endif; ?>

		</aside>

	</div>

	<!-- Related Articles Section -->
	<?php
	$related_posts = medici_get_related_blog_posts( $post_id, 3 );

	if ( ! empty( $related_posts ) ) :
	?>
		<section class="medici-related-section">
			<div class="medici-container">
				<h2><?php esc_html_e( 'Схожі статті', 'medici.agency' ); ?></h2>

				<div class="medici-related-grid">
					<?php foreach ( $related_posts as $related_post ) : ?>
						<?php
						$related_category = get_the_terms( $related_post->ID, 'blog_category' );
						$related_cat_name = $related_category && ! is_wp_error( $related_category ) ? $related_category[0]->name : '';
						$related_reading  = (int) get_post_meta( $related_post->ID, '_medici_reading_time', true );
						?>
						<a href="<?php echo esc_url( get_permalink( $related_post->ID ) ); ?>" class="related-card">
							<?php if ( $related_cat_name ) : ?>
								<div class="related-category"><?php echo esc_html( $related_cat_name ); ?></div>
							<?php endif; ?>
							<h3 class="related-title"><?php echo esc_html( get_the_title( $related_post->ID ) ); ?></h3>
							<div class="related-meta">
								<?php echo esc_html( get_the_date( 'j F Y', $related_post->ID ) ); ?>
								<?php if ( $related_reading > 0 ) : ?>
									• <?php echo esc_html( (string) $related_reading ); ?> хв
								<?php endif; ?>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

</div>

<?php
get_footer();

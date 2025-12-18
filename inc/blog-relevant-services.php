<?php
/**
 * Blog Relevant Services Module
 *
 * Автоматичне визначення релевантних послуг для статей блогу
 *
 * @package    Medici
 * @subpackage Blog/Services
 * @since      1.0.17
 * @version    1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Отримати список всіх послуг агенції
 *
 * @return array<int, array{id: string, title: string, description: string, keywords: array<string>, link: string, icon: string}>
 */
function medici_get_all_services(): array {
	return array(
		array(
			'id'          => 'smm-medical',
			'title'       => 'SMM для медичних клінік',
			'description' => 'Соціальні мережі для лікарень та клінік з дотриманням законодавства',
			'keywords'    => array( 'smm', 'соціальні мережі', 'instagram', 'facebook', 'контент', 'клініка' ),
			'link'        => home_url( '/services/smm-medical/' ),
			'icon'        => '📱',
		),
		array(
			'id'          => 'legal-advertising',
			'title'       => 'Юридична перевірка реклами',
			'description' => 'Перевірка рекламних матеріалів на відповідність законодавству',
			'keywords'    => array( 'юридична', 'закон', 'реклама', 'держлікслужба', 'дозвіл', 'лікарські засоби' ),
			'link'        => home_url( '/services/legal-advertising/' ),
			'icon'        => '⚖️',
		),
		array(
			'id'          => 'seo-medical',
			'title'       => 'SEO для медичних сайтів',
			'description' => 'Просування медичних сайтів у пошукових системах',
			'keywords'    => array( 'seo', 'google', 'просування', 'сайт', 'пошук', 'трафік' ),
			'link'        => home_url( '/services/seo-medical/' ),
			'icon'        => '🔍',
		),
		array(
			'id'          => 'content-marketing',
			'title'       => 'Контент-маркетинг',
			'description' => 'Створення корисного контенту для залучення пацієнтів',
			'keywords'    => array( 'контент', 'статті', 'блог', 'маркетинг', 'пацієнти' ),
			'link'        => home_url( '/services/content-marketing/' ),
			'icon'        => '✍️',
		),
		array(
			'id'          => 'brand-strategy',
			'title'       => 'Брендинг та стратегія',
			'description' => 'Розробка бренду та маркетингової стратегії для медичних закладів',
			'keywords'    => array( 'бренд', 'стратегія', 'позиціонування', 'логотип', 'фірмовий стиль' ),
			'link'        => home_url( '/services/brand-strategy/' ),
			'icon'        => '🎯',
		),
		array(
			'id'          => 'google-ads',
			'title'       => 'Google Ads для клінік',
			'description' => 'Налаштування та ведення контекстної реклами',
			'keywords'    => array( 'google ads', 'контекстна реклама', 'ppc', 'реклама', 'adwords' ),
			'link'        => home_url( '/services/google-ads/' ),
			'icon'        => '🎯',
		),
	);
}

/**
 * Визначити релевантні послуги для статті
 *
 * @param int $post_id ID статті
 * @param int $count Кількість послуг для повернення
 * @return array<int, array{id: string, title: string, description: string, link: string, icon: string, score: int}>
 */
function medici_get_relevant_services( int $post_id, int $count = 3 ): array {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return array();
	}

	// Отримати контент статті
	$content = strtolower( $post->post_title . ' ' . $post->post_content );
	$content = strip_tags( $content );

	// Отримати категорії
	$categories     = get_the_terms( $post_id, 'blog_category' );
	$category_names = array();
	if ( $categories && ! is_wp_error( $categories ) ) {
		$category_names = array_map(
			function ( $cat ) {
				return strtolower( $cat->name );
			},
			$categories
		);
	}

	// Отримати всі послуги
	$all_services = medici_get_all_services();

	// Розрахувати релевантність для кожної послуги
	$scored_services = array();
	foreach ( $all_services as $service ) {
		$score = 0;

		// Перевірити ключові слова в контенті
		foreach ( $service['keywords'] as $keyword ) {
			$keyword_lower = strtolower( $keyword );

			// Підрахувати кількість входжень
			$occurrences = substr_count( $content, $keyword_lower );
			$score      += $occurrences * 10;

			// Бонус якщо ключове слово в заголовку
			if ( stripos( $post->post_title, $keyword_lower ) !== false ) {
				$score += 20;
			}

			// Бонус якщо ключове слово в категорії
			foreach ( $category_names as $cat_name ) {
				if ( stripos( $cat_name, $keyword_lower ) !== false ) {
					$score += 15;
				}
			}
		}

		$scored_services[] = array_merge( $service, array( 'score' => $score ) );
	}

	// Сортувати за релевантністю
	usort(
		$scored_services,
		function ( $a, $b ) {
			return $b['score'] - $a['score'];
		}
	);

	// Повернути топ N найбільш релевантних
	$relevant = array_slice( $scored_services, 0, $count );

	// Якщо жодна послуга не має score > 0, повернути випадкові
	$has_relevant = false;
	foreach ( $relevant as $service ) {
		if ( $service['score'] > 0 ) {
			$has_relevant = true;
			break;
		}
	}

	if ( ! $has_relevant ) {
		// Повернути перші N послуг як fallback (додати score = 0)
		$fallback = array_slice( $all_services, 0, $count );
		return array_map(
			static fn( array $service ): array => array_merge( $service, array( 'score' => 0 ) ),
			$fallback
		);
	}

	return $relevant;
}

/**
 * Рендеринг віджета релевантних послуг
 *
 * @param int $post_id ID статті
 * @return void
 */
function medici_render_relevant_services_widget( int $post_id ): void {
	if ( ! medici_should_show_services_widget() ) {
		return;
	}

	$count    = medici_get_services_widget_count();
	$services = medici_get_relevant_services( $post_id, $count );

	if ( empty( $services ) ) {
		return;
	}
	?>
	<div class="sidebar-section relevant-services-widget">
		<h3><?php esc_html_e( 'Наші послуги', 'medici.agency' ); ?></h3>
		<p class="services-description">
			<?php esc_html_e( 'Ми можемо допомогти вам з цими послугами', 'medici.agency' ); ?>
		</p>

		<?php foreach ( $services as $service ) : ?>
			<div class="service-card">
				<h4>
					<span class="service-icon"><?php echo esc_html( $service['icon'] ); ?></span>
					<?php echo esc_html( $service['title'] ); ?>
				</h4>
				<p><?php echo esc_html( $service['description'] ); ?></p>
				<a href="<?php echo esc_url( $service['link'] ); ?>">
					<?php esc_html_e( 'Детальніше', 'medici.agency' ); ?>
					<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M5 12h14m-7-7l7 7-7 7"/>
					</svg>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

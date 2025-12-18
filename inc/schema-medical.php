<?php
/**
 * Medical Marketing Agency Schema Markup
 *
 * Organization schema for healthcare marketing agency
 * CRITICAL: This is a marketing agency FOR medical businesses, NOT a medical practice
 *
 * @package    Medici_Agency
 * @subpackage Schema_Markup
 * @since      1.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Configuration Class
 *
 * @since 1.1.0
 */
final class Medici_Schema_Config {

	/**
	 * Organization details
	 */
	public const ORG_NAME          = 'Medici Agency';
	public const ORG_ALT_NAME      = 'Медічі - Агенція медичного маркетингу';
	public const ORG_LEGAL_NAME    = 'ФОП Медічі Агенція';
	public const ORG_SLOGAN        = 'Маркетинг для медицини. Законно. Етично. Ефективно.';
	public const ORG_FOUNDING_YEAR = '2019';
	public const ORG_EMPLOYEES     = 10;

	/**
	 * Contact information
	 */
	public const PHONE    = '+380971234567';
	public const EMAIL    = 'info@medici.agency';
	public const LOCALITY = 'Київ';
	public const COUNTRY  = 'UA';

	/**
	 * Rating information
	 */
	public const RATING_VALUE = '4.9';
	public const RATING_COUNT = '47';
	public const RATING_BEST  = '5';
	public const RATING_WORST = '1';

	/**
	 * Get service offers data
	 *
	 * @since 1.1.0
	 * @return array Service offers
	 */
	public static function get_service_offers(): array {
		return array(
			array(
				'name'        => 'Compliance-аудит медичного маркетингу',
				'description' => 'Повна перевірка поточних маркетингових активностей на відповідність: ЗУ "Про рекламу", Google Healthcare Policy, Meta Health Products Policy. Детальний звіт з рекомендаціями та юридичним висновком.',
				'price'       => '200.00',
				'serviceType' => 'Healthcare Marketing Compliance Audit',
			),
			array(
				'name'        => 'Контент-маркетинг для медицини',
				'description' => 'Створення освітнього та інформаційного контенту з урахуванням медичної етики, E-A-T оптимізації та compliance вимог. Статті, відео, інфографіка.',
				'price'       => '400.00',
				'serviceType' => 'Healthcare Content Marketing',
			),
			array(
				'name'        => 'Google Ads для медичних закладів',
				'description' => 'Налаштування та управління рекламними кампаніями з проходженням модерації Google Healthcare Policy. Сертифікація кабінету, таргетинг, оптимізація.',
				'price'       => '500.00',
				'serviceType' => 'Google Ads Healthcare Management',
			),
			array(
				'name'        => 'Facebook/Instagram Ads для медицини',
				'description' => 'Таргетована реклама з урахуванням Meta Health Products Policy та відповідністю українському законодавству. Креативи, аудиторії, аналітика.',
				'price'       => '450.00',
				'serviceType' => 'Meta Ads Healthcare Management',
			),
			array(
				'name'        => 'Комплексний маркетинговий пакет',
				'description' => 'Повний маркетинговий супровід: стратегія, контент, Google/Meta реклама, SEO, соцмережі, аналітика та юридична підтримка. Від 1500$/міс.',
				'price'       => '1500.00',
				'serviceType' => 'Full-Service Healthcare Marketing',
			),
		);
	}

	/**
	 * Get social media profiles
	 *
	 * @since 1.1.0
	 * @return array Social profiles URLs
	 */
	public static function get_social_profiles(): array {
		return array(
			'https://www.linkedin.com/company/medici-agency',
			'https://www.facebook.com/medici.agency',
			'https://t.me/medici_agency',
		);
	}

	/**
	 * Get knowledge areas
	 *
	 * @since 1.1.0
	 * @return array Knowledge areas
	 */
	public static function get_knowledge_areas(): array {
		return array(
			'Healthcare Marketing',
			'Medical Advertising Compliance',
			'Google Healthcare Policy (2019)',
			'Meta Health Products Policy',
			'Законодавство України про рекламу',
			'E-A-T оптимізація для медичних сайтів',
			'AI Search Optimization (GEO)',
			'Medical SEO',
			'HIPAA Compliance Marketing',
			'Медична етика в рекламі',
		);
	}

	/**
	 * Get awards and certifications
	 *
	 * @since 1.1.0
	 * @return array Awards
	 */
	public static function get_awards(): array {
		return array(
			'Сертифікований партнер Google Ads',
		);
	}
}

/**
 * Output Organization Schema Markup
 *
 * @since 1.0.0
 * @return void
 */
function medici_output_organization_schema(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = medici_build_organization_schema();

	if ( empty( $schema ) ) {
		return;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'medici_output_organization_schema', 10 );

/**
 * Build Organization Schema Data
 *
 * @since 1.1.0
 * @return array Schema data
 */
function medici_build_organization_schema(): array {
	$home_url = home_url( '/' );
	$org_id   = $home_url . '#organization';

	// Build offers array
	$offers = array();
	foreach ( Medici_Schema_Config::get_service_offers() as $service ) {
		$offers[] = array(
			'@type'         => 'Offer',
			'name'          => $service['name'],
			'description'   => $service['description'],
			'price'         => $service['price'],
			'priceCurrency' => 'USD',
			'itemOffered'   => array(
				'@type'       => 'Service',
				'name'        => $service['name'],
				'serviceType' => $service['serviceType'],
				'provider'    => array( '@id' => $org_id ),
				'areaServed'  => array(
					'@type' => 'Country',
					'name'  => 'Ukraine',
				),
			),
		);
	}

	// Build main schema
	$schema = array(
		'@context'                  => 'https://schema.org',
		'@type'                     => array( 'Organization', 'ProfessionalService' ),
		'@id'                       => $org_id,
		'url'                       => $home_url,
		'name'                      => Medici_Schema_Config::ORG_NAME,
		'alternateName'             => Medici_Schema_Config::ORG_ALT_NAME,
		'legalName'                 => Medici_Schema_Config::ORG_LEGAL_NAME,
		'slogan'                    => Medici_Schema_Config::ORG_SLOGAN,
		'foundingDate'              => Medici_Schema_Config::ORG_FOUNDING_YEAR,
		'description'               => 'Професійна маркетингова агенція для медичних закладів України. Спеціалізуємося на compliance-маркетингу: Google Ads Healthcare Policy, Meta Health Products Policy, ЗУ "Про рекламу". Повна прозорість, юридична експертиза, український контекст.',
		'serviceType'               => 'Marketing & Advertising Services',
		'additionalType'            => array(
			'https://schema.org/MarketingAgency',
			'https://schema.org/AdvertisingAgency',
		),
		'logo'                      => array(
			'@type'  => 'ImageObject',
			'url'    => get_stylesheet_directory_uri() . '/img/logo.svg',
			'width'  => 200,
			'height' => 60,
		),
		'audience'                  => array(
			'@type'          => 'ProfessionalAudience',
			'audienceType'   => 'Medical clinics and healthcare facilities',
			'geographicArea' => array(
				'@type' => 'Country',
				'name'  => 'Ukraine',
			),
		),
		'areaServed'                => array(
			'@type' => 'Country',
			'name'  => 'Ukraine',
		),
		'availableLanguage'         => array( 'uk', 'ru' ),
		'knowsLanguage'             => array( 'uk', 'ru', 'en' ),
		'telephone'                 => Medici_Schema_Config::PHONE,
		'email'                     => Medici_Schema_Config::EMAIL,
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => Medici_Schema_Config::LOCALITY,
			'addressCountry'  => Medici_Schema_Config::COUNTRY,
		),
		'sameAs'                    => Medici_Schema_Config::get_social_profiles(),
		'knowsAbout'                => Medici_Schema_Config::get_knowledge_areas(),
		'priceRange'                => '$$',
		'openingHoursSpecification' => array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
				'opens'     => '09:00',
				'closes'    => '18:00',
			),
		),
		'makesOffer'                => $offers,
		'aggregateRating'           => array(
			'@type'       => 'AggregateRating',
			'ratingValue' => Medici_Schema_Config::RATING_VALUE,
			'ratingCount' => Medici_Schema_Config::RATING_COUNT,
			'bestRating'  => Medici_Schema_Config::RATING_BEST,
			'worstRating' => Medici_Schema_Config::RATING_WORST,
		),
		'award'                     => Medici_Schema_Config::get_awards(),
		'numberOfEmployees'         => array(
			'@type' => 'QuantitativeValue',
			'value' => Medici_Schema_Config::ORG_EMPLOYEES,
		),
	);

	return $schema;
}

/**
 * Output FAQPage Schema Markup
 *
 * Automatically detects FAQ blocks on page and generates FAQPage schema.
 * Compatible with core/details blocks or custom FAQ implementation.
 *
 * @since 1.3.5
 * @return void
 */
function medici_output_faq_schema(): void {
	if ( ! is_singular() ) {
		return;
	}

	$faq_items = medici_extract_faq_from_content();

	if ( empty( $faq_items ) ) {
		return;
	}

	$schema = medici_build_faq_schema( $faq_items );

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'medici_output_faq_schema', 11 );

/**
 * Extract FAQ items from post content
 *
 * Detects FAQ patterns from various sources:
 * - Core details/summary blocks
 * - Custom FAQ shortcodes
 * - Heading + paragraph patterns
 *
 * @since 1.3.5
 * @return array FAQ items with question/answer pairs
 */
function medici_extract_faq_from_content(): array {
	global $post;

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$content   = $post->post_content;
	$faq_items = array();

	// Pattern 1: Core details/summary blocks
	if ( preg_match_all( '/<details[^>]*>.*?<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$question = wp_strip_all_tags( $match[1] );
			$answer   = wp_strip_all_tags( $match[2] );

			if ( ! empty( $question ) && ! empty( $answer ) ) {
				$faq_items[] = array(
					'question' => trim( $question ),
					'answer'   => trim( $answer ),
				);
			}
		}
	}

	// Pattern 2: Heading followed by paragraph (H3/H4 + P)
	if ( empty( $faq_items ) ) {
		if ( preg_match_all( '/<h[34][^>]*>(.*?)<\/h[34]>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$question = wp_strip_all_tags( $match[1] );
				$answer   = wp_strip_all_tags( $match[2] );

				// Only count if question looks like a question (?, Як, Чому, Що, etc.)
				if ( ( strpos( $question, '?' ) !== false || preg_match( '/^(Як|Чому|Що|Де|Коли|Хто|Чи|Скільки)/u', $question ) ) && ! empty( $answer ) ) {
					$faq_items[] = array(
						'question' => trim( $question ),
						'answer'   => trim( $answer ),
					);
				}
			}
		}
	}

	return $faq_items;
}

/**
 * Build FAQPage Schema Data
 *
 * @since 1.3.5
 * @param array $faq_items FAQ items with question/answer pairs
 * @return array Schema data
 */
function medici_build_faq_schema( array $faq_items ): array {
	$main_entity = array();

	foreach ( $faq_items as $item ) {
		$main_entity[] = array(
			'@type'          => 'Question',
			'name'           => $item['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['answer'],
			),
		);
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $main_entity,
	);
}

/**
 * Output HowTo Schema Markup
 *
 * Detects step-by-step instructions and generates HowTo schema.
 * Looks for ordered lists, step headings, or custom step blocks.
 *
 * @since 1.3.5
 * @return void
 */
function medici_output_howto_schema(): void {
	if ( ! is_singular( array( 'post', 'page' ) ) ) {
		return;
	}

	$howto_data = medici_extract_howto_from_content();

	if ( empty( $howto_data ) ) {
		return;
	}

	$schema = medici_build_howto_schema( $howto_data );

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'medici_output_howto_schema', 12 );

/**
 * Extract HowTo instructions from post content
 *
 * Detects step-by-step patterns:
 * - Ordered lists (<ol><li>)
 * - Headings with "Крок", "Step", numbered patterns
 *
 * @since 1.3.5
 * @return array HowTo data with title, description, and steps
 */
function medici_extract_howto_from_content(): array {
	global $post;

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$content = $post->post_content;
	$steps   = array();

	// Pattern 1: Ordered list items
	if ( preg_match( '/<ol[^>]*>(.*?)<\/ol>/is', $content, $ol_match ) ) {
		if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $ol_match[1], $li_matches ) ) {
			foreach ( $li_matches[1] as $index => $step_html ) {
				$step_text = wp_strip_all_tags( $step_html );

				if ( ! empty( $step_text ) ) {
					$steps[] = array(
						'position' => $index + 1,
						'name'     => 'Крок ' . ( $index + 1 ),
						'text'     => trim( $step_text ),
					);
				}
			}
		}
	}

	// Pattern 2: Headings with "Крок X" or "Step X"
	if ( empty( $steps ) ) {
		if ( preg_match_all( '/<h[23][^>]*>(?:Крок|Step)\s*(\d+)[:\.\s]+(.*?)<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/ius', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$step_number = (int) $match[1];
				$step_title  = wp_strip_all_tags( $match[2] );
				$step_desc   = wp_strip_all_tags( $match[3] );

				$steps[] = array(
					'position' => $step_number,
					'name'     => trim( $step_title ),
					'text'     => trim( $step_desc ),
				);
			}
		}
	}

	// Minimum 3 steps required for HowTo schema
	if ( count( $steps ) < 3 ) {
		return array();
	}

	return array(
		'title'       => get_the_title(),
		'description' => get_the_excerpt(),
		'steps'       => $steps,
	);
}

/**
 * Build HowTo Schema Data
 *
 * @since 1.3.5
 * @param array $howto_data HowTo data with title, description, and steps
 * @return array Schema data
 */
function medici_build_howto_schema( array $howto_data ): array {
	$step_items = array();

	foreach ( $howto_data['steps'] as $step ) {
		$step_items[] = array(
			'@type'    => 'HowToStep',
			'position' => $step['position'],
			'name'     => $step['name'],
			'text'     => $step['text'],
		);
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'HowTo',
		'name'        => $howto_data['title'],
		'description' => $howto_data['description'],
		'step'        => $step_items,
	);

	// Add totalTime if available from reading time meta
	$reading_time = get_post_meta( get_the_ID(), 'reading_time', true );
	if ( ! empty( $reading_time ) && is_numeric( $reading_time ) ) {
		$schema['totalTime'] = 'PT' . $reading_time . 'M';
	}

	return $schema;
}

/**
 * Output VideoObject Schema Markup
 *
 * Detects embedded videos and generates VideoObject schema.
 * Supports YouTube, Vimeo, and self-hosted videos.
 *
 * @since 1.3.5
 * @return void
 */
function medici_output_video_schema(): void {
	if ( ! is_singular( array( 'post', 'page' ) ) ) {
		return;
	}

	$videos = medici_extract_videos_from_content();

	if ( empty( $videos ) ) {
		return;
	}

	foreach ( $videos as $video ) {
		$schema = medici_build_video_schema( $video );
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'medici_output_video_schema', 13 );

/**
 * Extract video embeds from post content
 *
 * Detects:
 * - YouTube embeds
 * - Vimeo embeds
 * - Core video blocks
 *
 * @since 1.3.5
 * @return array Video data (url, thumbnail, title, description, duration)
 */
function medici_extract_videos_from_content(): array {
	global $post;

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$content = $post->post_content;
	$videos  = array();

	// Pattern 1: YouTube embeds
	if ( preg_match_all( '/(?:https?:)?\/\/(?:www\.)?(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $content, $yt_matches ) ) {
		foreach ( $yt_matches[1] as $video_id ) {
			$videos[] = medici_get_youtube_video_data( $video_id );
		}
	}

	// Pattern 2: Vimeo embeds
	if ( preg_match_all( '/(?:https?:)?\/\/(?:www\.)?vimeo\.com\/(?:video\/)?(\d+)/i', $content, $vimeo_matches ) ) {
		foreach ( $vimeo_matches[1] as $video_id ) {
			$videos[] = medici_get_vimeo_video_data( $video_id );
		}
	}

	// Pattern 3: Core video blocks (self-hosted)
	if ( preg_match_all( '/<!-- wp:video.*?-->(.*?)<!-- \/wp:video -->/is', $content, $video_blocks ) ) {
		foreach ( $video_blocks[1] as $block_html ) {
			if ( preg_match( '/<video[^>]+src="([^"]+)"/', $block_html, $src_match ) ) {
				$videos[] = medici_get_selfhosted_video_data( $src_match[1] );
			}
		}
	}

	return array_filter( $videos );
}

/**
 * Get YouTube video data
 *
 * @since 1.3.5
 * @param string $video_id YouTube video ID
 * @return array Video data
 */
function medici_get_youtube_video_data( string $video_id ): array {
	return array(
		'type'        => 'youtube',
		'url'         => 'https://www.youtube.com/watch?v=' . $video_id,
		'embed_url'   => 'https://www.youtube.com/embed/' . $video_id,
		'thumbnail'   => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
		'title'       => get_the_title() . ' - Video',
		'description' => get_the_excerpt(),
		'upload_date' => get_the_date( 'c' ),
	);
}

/**
 * Get Vimeo video data
 *
 * @since 1.3.5
 * @param string $video_id Vimeo video ID
 * @return array Video data
 */
function medici_get_vimeo_video_data( string $video_id ): array {
	return array(
		'type'        => 'vimeo',
		'url'         => 'https://vimeo.com/' . $video_id,
		'embed_url'   => 'https://player.vimeo.com/video/' . $video_id,
		'title'       => get_the_title() . ' - Video',
		'description' => get_the_excerpt(),
		'upload_date' => get_the_date( 'c' ),
	);
}

/**
 * Get self-hosted video data
 *
 * @since 1.3.5
 * @param string $video_url Video file URL
 * @return array Video data
 */
function medici_get_selfhosted_video_data( string $video_url ): array {
	return array(
		'type'        => 'selfhosted',
		'url'         => $video_url,
		'title'       => get_the_title() . ' - Video',
		'description' => get_the_excerpt(),
		'upload_date' => get_the_date( 'c' ),
	);
}

/**
 * Build VideoObject Schema Data
 *
 * @since 1.3.5
 * @param array $video Video data
 * @return array Schema data
 */
function medici_build_video_schema( array $video ): array {
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'VideoObject',
		'name'        => $video['title'],
		'description' => $video['description'],
		'uploadDate'  => $video['upload_date'],
		'contentUrl'  => $video['url'],
	);

	// Add embed URL if available
	if ( ! empty( $video['embed_url'] ) ) {
		$schema['embedUrl'] = $video['embed_url'];
	}

	// Add thumbnail if available
	if ( ! empty( $video['thumbnail'] ) ) {
		$schema['thumbnailUrl'] = $video['thumbnail'];
	} elseif ( has_post_thumbnail() ) {
		$schema['thumbnailUrl'] = get_the_post_thumbnail_url( null, 'large' );
	}

	return $schema;
}

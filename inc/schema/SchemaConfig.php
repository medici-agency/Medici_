<?php
/**
 * Schema Configuration
 *
 * Centralized configuration for schema.org markup.
 *
 * @package    Medici_Agency
 * @subpackage Schema
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Schema;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema Configuration Class
 *
 * @since 2.0.0
 */
final class SchemaConfig {

	/**
	 * Singleton instance
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

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
	 * Private constructor for singleton
	 */
	private function __construct() {}

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
	 * @since 2.0.0
	 * @return array<array<string, string>>
	 */
	public static function getServiceOffers(): array {
		return array(
			array(
				'name'        => 'Compliance-аудит медичного маркетингу',
				'description' => 'Повна перевірка поточних маркетингових активностей на відповідність: ЗУ "Про рекламу", Google Healthcare Policy, Meta Health Products Policy.',
				'price'       => '200.00',
				'serviceType' => 'Healthcare Marketing Compliance Audit',
			),
			array(
				'name'        => 'Контент-маркетинг для медицини',
				'description' => 'Створення освітнього та інформаційного контенту з урахуванням медичної етики, E-A-T оптимізації та compliance вимог.',
				'price'       => '400.00',
				'serviceType' => 'Healthcare Content Marketing',
			),
			array(
				'name'        => 'Google Ads для медичних закладів',
				'description' => 'Налаштування та управління рекламними кампаніями з проходженням модерації Google Healthcare Policy.',
				'price'       => '500.00',
				'serviceType' => 'Google Ads Healthcare Management',
			),
			array(
				'name'        => 'Facebook/Instagram Ads для медицини',
				'description' => 'Таргетована реклама з урахуванням Meta Health Products Policy та відповідністю українському законодавству.',
				'price'       => '450.00',
				'serviceType' => 'Meta Ads Healthcare Management',
			),
			array(
				'name'        => 'Комплексний маркетинговий пакет',
				'description' => 'Повний маркетинговий супровід: стратегія, контент, Google/Meta реклама, SEO, соцмережі, аналітика.',
				'price'       => '1500.00',
				'serviceType' => 'Full-Service Healthcare Marketing',
			),
		);
	}

	/**
	 * Get social media profiles
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public static function getSocialProfiles(): array {
		return array(
			'https://www.linkedin.com/company/medici-agency',
			'https://www.facebook.com/medici.agency',
			'https://t.me/medici_agency',
		);
	}

	/**
	 * Get knowledge areas
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public static function getKnowledgeAreas(): array {
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
	 * Get awards
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public static function getAwards(): array {
		return array(
			'Сертифікований партнер Google Ads',
		);
	}

	/**
	 * Get organization description
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Професійна маркетингова агенція для медичних закладів України. Спеціалізуємося на compliance-маркетингу: Google Ads Healthcare Policy, Meta Health Products Policy, ЗУ "Про рекламу". Повна прозорість, юридична експертиза, український контекст.';
	}
}

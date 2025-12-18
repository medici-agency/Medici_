<?php
/**
 * Organization Schema Builder
 *
 * Builds Organization schema for the agency.
 *
 * @package    Medici_Agency
 * @subpackage Schema\Builders
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Schema\Builders;

use Medici\Schema\AbstractSchemaBuilder;
use Medici\Schema\SchemaConfig;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Organization Schema Builder
 *
 * @since 2.0.0
 */
final class OrganizationBuilder extends AbstractSchemaBuilder {

	/**
	 * Priority (render first on front page)
	 */
	protected const DEFAULT_PRIORITY = 1;

	/**
	 * Get type
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getType(): string {
		return 'Organization';
	}

	/**
	 * Should render on front page only
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function shouldRender(): bool {
		return is_front_page();
	}

	/**
	 * Build organization schema
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|null
	 */
	public function build(): ?array {
		$org_id = $this->getOrganizationId();

		return $this->withContext(
			array(
				'@type'                     => array( 'Organization', 'ProfessionalService' ),
				'@id'                       => $org_id,
				'url'                       => $this->getHomeUrl(),
				'name'                      => SchemaConfig::ORG_NAME,
				'alternateName'             => SchemaConfig::ORG_ALT_NAME,
				'legalName'                 => SchemaConfig::ORG_LEGAL_NAME,
				'slogan'                    => SchemaConfig::ORG_SLOGAN,
				'foundingDate'              => SchemaConfig::ORG_FOUNDING_YEAR,
				'description'               => SchemaConfig::getDescription(),
				'serviceType'               => 'Marketing & Advertising Services',
				'additionalType'            => array(
					'https://schema.org/MarketingAgency',
					'https://schema.org/AdvertisingAgency',
				),
				'logo'                      => $this->buildLogo(),
				'audience'                  => $this->buildAudience(),
				'areaServed'                => $this->buildAreaServed(),
				'availableLanguage'         => array( 'uk', 'ru' ),
				'knowsLanguage'             => array( 'uk', 'ru', 'en' ),
				'telephone'                 => SchemaConfig::PHONE,
				'email'                     => SchemaConfig::EMAIL,
				'address'                   => $this->buildAddress(),
				'sameAs'                    => SchemaConfig::getSocialProfiles(),
				'knowsAbout'                => SchemaConfig::getKnowledgeAreas(),
				'priceRange'                => '$$',
				'openingHoursSpecification' => $this->buildOpeningHours(),
				'makesOffer'                => $this->buildOffers( $org_id ),
				'aggregateRating'           => $this->buildRating(),
				'award'                     => SchemaConfig::getAwards(),
				'numberOfEmployees'         => array(
					'@type' => 'QuantitativeValue',
					'value' => SchemaConfig::ORG_EMPLOYEES,
				),
			)
		);
	}

	/**
	 * Build logo
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>
	 */
	private function buildLogo(): array {
		return array(
			'@type'  => 'ImageObject',
			'url'    => get_stylesheet_directory_uri() . '/img/logo.svg',
			'width'  => 200,
			'height' => 60,
		);
	}

	/**
	 * Build audience
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>
	 */
	private function buildAudience(): array {
		return array(
			'@type'          => 'ProfessionalAudience',
			'audienceType'   => 'Medical clinics and healthcare facilities',
			'geographicArea' => array(
				'@type' => 'Country',
				'name'  => 'Ukraine',
			),
		);
	}

	/**
	 * Build area served
	 *
	 * @since 2.0.0
	 * @return array<string, string>
	 */
	private function buildAreaServed(): array {
		return array(
			'@type' => 'Country',
			'name'  => 'Ukraine',
		);
	}

	/**
	 * Build address
	 *
	 * @since 2.0.0
	 * @return array<string, string>
	 */
	private function buildAddress(): array {
		return array(
			'@type'           => 'PostalAddress',
			'addressLocality' => SchemaConfig::LOCALITY,
			'addressCountry'  => SchemaConfig::COUNTRY,
		);
	}

	/**
	 * Build opening hours
	 *
	 * @since 2.0.0
	 * @return array<array<string, mixed>>
	 */
	private function buildOpeningHours(): array {
		return array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
				'opens'     => '09:00',
				'closes'    => '18:00',
			),
		);
	}

	/**
	 * Build offers
	 *
	 * @since 2.0.0
	 * @param string $org_id Organization ID.
	 * @return array<array<string, mixed>>
	 */
	private function buildOffers( string $org_id ): array {
		$offers = array();

		foreach ( SchemaConfig::getServiceOffers() as $service ) {
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
					'areaServed'  => $this->buildAreaServed(),
				),
			);
		}

		return $offers;
	}

	/**
	 * Build rating
	 *
	 * @since 2.0.0
	 * @return array<string, string>
	 */
	private function buildRating(): array {
		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => SchemaConfig::RATING_VALUE,
			'ratingCount' => SchemaConfig::RATING_COUNT,
			'bestRating'  => SchemaConfig::RATING_BEST,
			'worstRating' => SchemaConfig::RATING_WORST,
		);
	}
}

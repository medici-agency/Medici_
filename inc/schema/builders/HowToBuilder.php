<?php
/**
 * HowTo Schema Builder
 *
 * Builds HowTo schema from step-by-step instructions.
 *
 * @package    Medici_Agency
 * @subpackage Schema\Builders
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Schema\Builders;

use Medici\Schema\AbstractSchemaBuilder;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HowTo Schema Builder
 *
 * Detects step-by-step patterns and builds HowTo schema.
 *
 * @since 2.0.0
 */
final class HowToBuilder extends AbstractSchemaBuilder {

	/**
	 * Priority
	 */
	protected const DEFAULT_PRIORITY = 12;

	/**
	 * Minimum steps required
	 */
	private const MIN_STEPS = 3;

	/**
	 * Get type
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getType(): string {
		return 'HowTo';
	}

	/**
	 * Should render on posts and pages
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function shouldRender(): bool {
		return is_singular( array( 'post', 'page' ) );
	}

	/**
	 * Build HowTo schema
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|null
	 */
	public function build(): ?array {
		$steps = $this->extractSteps();

		if ( count( $steps ) < self::MIN_STEPS ) {
			return null;
		}

		$step_items = array();

		foreach ( $steps as $step ) {
			$step_items[] = array(
				'@type'    => 'HowToStep',
				'position' => $step['position'],
				'name'     => $step['name'],
				'text'     => $step['text'],
			);
		}

		$schema = $this->withContext(
			array(
				'@type'       => 'HowTo',
				'name'        => get_the_title(),
				'description' => get_the_excerpt(),
				'step'        => $step_items,
			)
		);

		// Add total time from reading time meta.
		$reading_time = get_post_meta( get_the_ID() ?: 0, 'reading_time', true );
		if ( ! empty( $reading_time ) && is_numeric( $reading_time ) ) {
			$schema['totalTime'] = 'PT' . (int) $reading_time . 'M';
		}

		return $schema;
	}

	/**
	 * Extract steps from content
	 *
	 * @since 2.0.0
	 * @return array<array{position: int, name: string, text: string}>
	 */
	private function extractSteps(): array {
		$content = $this->getPostContent();
		$steps   = array();

		// Pattern 1: Ordered list items.
		$steps = $this->extractFromOrderedList( $content );

		// Pattern 2: Step headings (fallback).
		if ( empty( $steps ) ) {
			$steps = $this->extractFromStepHeadings( $content );
		}

		return $steps;
	}

	/**
	 * Extract from ordered list
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return array<array{position: int, name: string, text: string}>
	 */
	private function extractFromOrderedList( string $content ): array {
		$steps = array();

		if ( preg_match( '/<ol[^>]*>(.*?)<\/ol>/is', $content, $ol_match ) ) {
			if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $ol_match[1], $li_matches ) ) {
				foreach ( $li_matches[1] as $index => $step_html ) {
					$step_text = $this->stripHtml( $step_html );

					if ( ! empty( $step_text ) ) {
						$steps[] = array(
							'position' => $index + 1,
							'name'     => 'Крок ' . ( $index + 1 ),
							'text'     => $step_text,
						);
					}
				}
			}
		}

		return $steps;
	}

	/**
	 * Extract from step headings
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return array<array{position: int, name: string, text: string}>
	 */
	private function extractFromStepHeadings( string $content ): array {
		$steps = array();

		if ( preg_match_all( '/<h[23][^>]*>(?:Крок|Step)\s*(\d+)[:\.\s]+(.*?)<\/h[23]>\s*<p[^>]*>(.*?)<\/p>/ius', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$steps[] = array(
					'position' => (int) $match[1],
					'name'     => $this->stripHtml( $match[2] ),
					'text'     => $this->stripHtml( $match[3] ),
				);
			}
		}

		return $steps;
	}
}

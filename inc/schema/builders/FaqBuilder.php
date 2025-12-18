<?php
/**
 * FAQ Schema Builder
 *
 * Builds FAQPage schema from post content.
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
 * FAQ Schema Builder
 *
 * Detects FAQ patterns and builds FAQPage schema.
 *
 * @since 2.0.0
 */
final class FaqBuilder extends AbstractSchemaBuilder {

	/**
	 * Priority
	 */
	protected const DEFAULT_PRIORITY = 11;

	/**
	 * Get type
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getType(): string {
		return 'FAQPage';
	}

	/**
	 * Should render on singular pages
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function shouldRender(): bool {
		return is_singular();
	}

	/**
	 * Build FAQ schema
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|null
	 */
	public function build(): ?array {
		$faq_items = $this->extractFaqItems();

		if ( empty( $faq_items ) ) {
			return null;
		}

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

		return $this->withContext(
			array(
				'@type'      => 'FAQPage',
				'mainEntity' => $main_entity,
			)
		);
	}

	/**
	 * Extract FAQ items from content
	 *
	 * @since 2.0.0
	 * @return array<array{question: string, answer: string}>
	 */
	private function extractFaqItems(): array {
		$content   = $this->getPostContent();
		$faq_items = array();

		// Pattern 1: Core details/summary blocks.
		$faq_items = $this->extractFromDetailsSummary( $content );

		// Pattern 2: Heading + paragraph (fallback).
		if ( empty( $faq_items ) ) {
			$faq_items = $this->extractFromHeadingParagraph( $content );
		}

		return $faq_items;
	}

	/**
	 * Extract from details/summary blocks
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return array<array{question: string, answer: string}>
	 */
	private function extractFromDetailsSummary( string $content ): array {
		$items = array();

		if ( preg_match_all( '/<details[^>]*>.*?<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$question = $this->stripHtml( $match[1] );
				$answer   = $this->stripHtml( $match[2] );

				if ( ! empty( $question ) && ! empty( $answer ) ) {
					$items[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}
		}

		return $items;
	}

	/**
	 * Extract from heading + paragraph patterns
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return array<array{question: string, answer: string}>
	 */
	private function extractFromHeadingParagraph( string $content ): array {
		$items = array();

		if ( preg_match_all( '/<h[34][^>]*>(.*?)<\/h[34]>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$question = $this->stripHtml( $match[1] );
				$answer   = $this->stripHtml( $match[2] );

				// Only count if question looks like a question.
				if ( $this->looksLikeQuestion( $question ) && ! empty( $answer ) ) {
					$items[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
			}
		}

		return $items;
	}

	/**
	 * Check if text looks like a question
	 *
	 * @since 2.0.0
	 * @param string $text Text to check.
	 * @return bool
	 */
	private function looksLikeQuestion( string $text ): bool {
		// Contains question mark.
		if ( strpos( $text, '?' ) !== false ) {
			return true;
		}

		// Starts with Ukrainian question words.
		return (bool) preg_match( '/^(Як|Чому|Що|Де|Коли|Хто|Чи|Скільки)/u', $text );
	}
}

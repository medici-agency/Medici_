<?php
/**
 * Video Schema Builder
 *
 * Builds VideoObject schema from embedded videos.
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
 * Video Schema Builder
 *
 * Detects video embeds and builds VideoObject schema.
 *
 * @since 2.0.0
 */
final class VideoBuilder extends AbstractSchemaBuilder {

	/**
	 * Priority
	 */
	protected const DEFAULT_PRIORITY = 13;

	/**
	 * Extracted videos (can have multiple)
	 *
	 * @var array<array<string, string>>
	 */
	private array $videos = array();

	/**
	 * Get type
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getType(): string {
		return 'VideoObject';
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
	 * Build video schema (returns first video only)
	 *
	 * Use buildAll() to get all videos.
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>|null
	 */
	public function build(): ?array {
		$this->extractVideos();

		if ( empty( $this->videos ) ) {
			return null;
		}

		return $this->buildVideoSchema( $this->videos[0] );
	}

	/**
	 * Build all video schemas
	 *
	 * @since 2.0.0
	 * @return array<array<string, mixed>>
	 */
	public function buildAll(): array {
		$this->extractVideos();

		$schemas = array();

		foreach ( $this->videos as $video ) {
			$schema = $this->buildVideoSchema( $video );
			if ( $schema ) {
				$schemas[] = $schema;
			}
		}

		return $schemas;
	}

	/**
	 * Build single video schema
	 *
	 * @since 2.0.0
	 * @param array<string, string> $video Video data.
	 * @return array<string, mixed>
	 */
	private function buildVideoSchema( array $video ): array {
		$schema = $this->withContext(
			array(
				'@type'       => 'VideoObject',
				'name'        => $video['title'],
				'description' => $video['description'],
				'uploadDate'  => $video['upload_date'],
				'contentUrl'  => $video['url'],
			)
		);

		if ( ! empty( $video['embed_url'] ) ) {
			$schema['embedUrl'] = $video['embed_url'];
		}

		if ( ! empty( $video['thumbnail'] ) ) {
			$schema['thumbnailUrl'] = $video['thumbnail'];
		} elseif ( has_post_thumbnail() ) {
			$schema['thumbnailUrl'] = get_the_post_thumbnail_url( null, 'large' );
		}

		return $schema;
	}

	/**
	 * Extract videos from content
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function extractVideos(): void {
		if ( ! empty( $this->videos ) ) {
			return;
		}

		$content = $this->getPostContent();

		// YouTube embeds.
		$this->extractYouTube( $content );

		// Vimeo embeds.
		$this->extractVimeo( $content );

		// Self-hosted videos.
		$this->extractSelfHosted( $content );
	}

	/**
	 * Extract YouTube videos
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return void
	 */
	private function extractYouTube( string $content ): void {
		if ( preg_match_all( '/(?:https?:)?\/\/(?:www\.)?(?:youtube\.com\/(?:embed\/|watch\?v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $content, $matches ) ) {
			foreach ( $matches[1] as $video_id ) {
				$this->videos[] = array(
					'type'        => 'youtube',
					'url'         => 'https://www.youtube.com/watch?v=' . $video_id,
					'embed_url'   => 'https://www.youtube.com/embed/' . $video_id,
					'thumbnail'   => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
					'title'       => get_the_title() . ' - Video',
					'description' => get_the_excerpt(),
					'upload_date' => get_the_date( 'c' ),
				);
			}
		}
	}

	/**
	 * Extract Vimeo videos
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return void
	 */
	private function extractVimeo( string $content ): void {
		if ( preg_match_all( '/(?:https?:)?\/\/(?:www\.)?vimeo\.com\/(?:video\/)?(\d+)/i', $content, $matches ) ) {
			foreach ( $matches[1] as $video_id ) {
				$this->videos[] = array(
					'type'        => 'vimeo',
					'url'         => 'https://vimeo.com/' . $video_id,
					'embed_url'   => 'https://player.vimeo.com/video/' . $video_id,
					'thumbnail'   => '',
					'title'       => get_the_title() . ' - Video',
					'description' => get_the_excerpt(),
					'upload_date' => get_the_date( 'c' ),
				);
			}
		}
	}

	/**
	 * Extract self-hosted videos
	 *
	 * @since 2.0.0
	 * @param string $content Post content.
	 * @return void
	 */
	private function extractSelfHosted( string $content ): void {
		if ( preg_match_all( '/<!-- wp:video.*?-->(.*?)<!-- \/wp:video -->/is', $content, $video_blocks ) ) {
			foreach ( $video_blocks[1] as $block_html ) {
				if ( preg_match( '/<video[^>]+src="([^"]+)"/', $block_html, $src_match ) ) {
					$this->videos[] = array(
						'type'        => 'selfhosted',
						'url'         => $src_match[1],
						'embed_url'   => '',
						'thumbnail'   => '',
						'title'       => get_the_title() . ' - Video',
						'description' => get_the_excerpt(),
						'upload_date' => get_the_date( 'c' ),
					);
				}
			}
		}
	}
}

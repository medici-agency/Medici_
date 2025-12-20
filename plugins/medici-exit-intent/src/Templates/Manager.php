<?php
/**
 * Templates Manager Class
 *
 * Manages popup templates and customization.
 *
 * @package Jexi\Templates
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Templates;

/**
 * Manager Class
 */
final class Manager {

	/**
	 * Available templates.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $templates = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_default_templates();
	}

	/**
	 * Register default templates.
	 *
	 * @since 1.0.0
	 */
	private function register_default_templates(): void {
		$this->templates = array(
			'modern'  => array(
				'name'        => __( 'Modern', 'medici-exit-intent' ),
				'description' => __( 'Clean and professional design with subtle shadows.', 'medici-exit-intent' ),
				'preview'     => JEXI_PLUGIN_URL . 'assets/images/templates/modern.png',
				'styles'      => array(
					'border_radius'    => '16px',
					'shadow'           => '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
					'padding'          => '48px',
					'icon_size'        => '72px',
					'title_size'       => '28px',
					'subtitle_size'    => '16px',
					'button_radius'    => '8px',
					'field_radius'     => '8px',
					'animation'        => 'scale',
				),
			),
			'minimal' => array(
				'name'        => __( 'Minimal', 'medici-exit-intent' ),
				'description' => __( 'Simple and elegant with minimal distractions.', 'medici-exit-intent' ),
				'preview'     => JEXI_PLUGIN_URL . 'assets/images/templates/minimal.png',
				'styles'      => array(
					'border_radius'    => '4px',
					'shadow'           => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
					'padding'          => '32px',
					'icon_size'        => '48px',
					'title_size'       => '24px',
					'subtitle_size'    => '14px',
					'button_radius'    => '4px',
					'field_radius'     => '4px',
					'animation'        => 'fade',
				),
			),
			'bold'    => array(
				'name'        => __( 'Bold', 'medici-exit-intent' ),
				'description' => __( 'Eye-catching design with strong colors.', 'medici-exit-intent' ),
				'preview'     => JEXI_PLUGIN_URL . 'assets/images/templates/bold.png',
				'styles'      => array(
					'border_radius'    => '24px',
					'shadow'           => '0 25px 50px -12px rgba(0, 0, 0, 0.35)',
					'padding'          => '56px',
					'icon_size'        => '96px',
					'title_size'       => '36px',
					'subtitle_size'    => '18px',
					'button_radius'    => '12px',
					'field_radius'     => '12px',
					'animation'        => 'bounce',
				),
			),
			'playful' => array(
				'name'        => __( 'Playful', 'medici-exit-intent' ),
				'description' => __( 'Fun and friendly design with rounded shapes.', 'medici-exit-intent' ),
				'preview'     => JEXI_PLUGIN_URL . 'assets/images/templates/playful.png',
				'styles'      => array(
					'border_radius'    => '32px',
					'shadow'           => '0 20px 40px rgba(0, 0, 0, 0.15)',
					'padding'          => '40px',
					'icon_size'        => '80px',
					'title_size'       => '32px',
					'subtitle_size'    => '16px',
					'button_radius'    => '999px',
					'field_radius'     => '999px',
					'animation'        => 'slide',
				),
			),
		);

		/**
		 * Filter available templates.
		 *
		 * @since 1.0.0
		 * @param array<string, array<string, mixed>> $templates Templates array.
		 */
		$this->templates = apply_filters( 'jexi_templates', $this->templates );
	}

	/**
	 * Get all templates.
	 *
	 * @since 1.0.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->templates;
	}

	/**
	 * Get template by ID.
	 *
	 * @since 1.0.0
	 * @param string $id Template ID.
	 * @return array<string, mixed>|null
	 */
	public function get( string $id ): ?array {
		return $this->templates[ $id ] ?? null;
	}

	/**
	 * Register custom template.
	 *
	 * @since 1.0.0
	 * @param string               $id       Template ID.
	 * @param array<string, mixed> $template Template data.
	 */
	public function register( string $id, array $template ): void {
		$this->templates[ $id ] = $template;
	}

	/**
	 * Get template CSS.
	 *
	 * @since 1.0.0
	 * @param string $id Template ID.
	 * @return string
	 */
	public function get_css( string $id ): string {
		$template = $this->get( $id );

		if ( ! $template ) {
			return '';
		}

		$styles = $template['styles'] ?? array();

		$css = ".jexi-popup--{$id} {\n";
		$css .= "  --jexi-popup-radius: {$styles['border_radius']};\n";
		$css .= "  --jexi-popup-shadow: {$styles['shadow']};\n";
		$css .= "  --jexi-popup-padding: {$styles['padding']};\n";
		$css .= "  --jexi-icon-size: {$styles['icon_size']};\n";
		$css .= "  --jexi-title-size: {$styles['title_size']};\n";
		$css .= "  --jexi-subtitle-size: {$styles['subtitle_size']};\n";
		$css .= "  --jexi-button-radius: {$styles['button_radius']};\n";
		$css .= "  --jexi-field-radius: {$styles['field_radius']};\n";
		$css .= "}\n";

		return $css;
	}

	/**
	 * REST API: Get templates.
	 *
	 * @since 1.0.0
	 * @return \WP_REST_Response
	 */
	public function rest_get_templates(): \WP_REST_Response {
		return new \WP_REST_Response( $this->get_all(), 200 );
	}
}

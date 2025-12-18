<?php
/**
 * Blog Shortcodes
 *
 * Shortcodes для елементів single blog post:
 * - [medici_warning] - Warning Box
 * - [medici_cta] - Inline CTA
 * - [medici_takeaways] - Key Takeaways
 *
 * @package    Medici
 * @subpackage Blog/Shortcodes
 * @since      1.0.16
 * @version    1.0.1
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Warning Box Shortcode
 *
 * Використання:
 * [medici_warning title="УВАГА" icon="⚠️"]
 * Текст попередження тут
 * [/medici_warning]
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content (може бути null).
 * @return string HTML output.
 */
function medici_warning_box_shortcode( array $atts, ?string $content = null ): string {
	$atts = shortcode_atts(
		array(
			'title' => 'УВАГА: Найпоширеніша помилка',
			'icon'  => '⚠️',
		),
		$atts,
		'medici_warning'
	);

	// Sanitize attributes
	$title = sanitize_text_field( $atts['title'] );
	$icon  = sanitize_text_field( $atts['icon'] );

	// Ensure string, allow nested shortcodes, then sanitize HTML
	$content = (string) $content;
	$content = do_shortcode( $content );
	$content = wp_kses_post( $content );

	// Build HTML
	ob_start();
	?>
	<div class="warning-box">
		<div class="warning-icon"><?php echo esc_html( $icon ); ?></div>
		<div class="warning-content">
			<?php if ( '' !== $title ) : ?>
				<h4><?php echo esc_html( $title ); ?></h4>
			<?php endif; ?>
			<div class="warning-text">
				<?php echo wpautop( $content ); ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'medici_warning', 'medici_warning_box_shortcode' );

/**
 * Inline CTA Shortcode
 *
 * Використання:
 * [medici_cta title="Потрібна юридична перевірка реклами?"
 *              text="Наші юристи з 10-річним досвідом перевірять ваші рекламні матеріали"
 *              button_text="Замовити перевірку"
 *              button_url="#contact"
 *              button_icon="→"]
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Не використовується, але залишено для сумісності.
 * @return string HTML output.
 */
function medici_inline_cta_shortcode( array $atts, ?string $content = null ): string {
	$atts = shortcode_atts(
		array(
			'title'       => 'Потрібна консультація?',
			'text'        => 'Наші експерти допоможуть вам вирішити будь-яке питання',
			'button_text' => 'Зв\'язатися з нами',
			'button_url'  => '#contact',
			'button_icon' => '→',
		),
		$atts,
		'medici_cta'
	);

	// Sanitize attributes
	$title       = sanitize_text_field( $atts['title'] );
	$text        = sanitize_text_field( $atts['text'] );
	$button_text = sanitize_text_field( $atts['button_text'] );
	$button_url  = esc_url( $atts['button_url'] );
	$button_icon = sanitize_text_field( $atts['button_icon'] );

	// Build HTML
	ob_start();
	?>
	<div class="inline-cta">
		<h3><?php echo esc_html( $title ); ?></h3>
		<p><?php echo esc_html( $text ); ?></p>
		<a href="<?php echo esc_url( $button_url ); ?>" class="btn-white">
			<?php echo esc_html( $button_text ); ?>
			<?php if ( '→' === $button_icon ) : ?>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M5 12h14m-7-7l7 7-7 7" />
				</svg>
			<?php else : ?>
				<span><?php echo esc_html( $button_icon ); ?></span>
			<?php endif; ?>
		</a>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'medici_cta', 'medici_inline_cta_shortcode' );

/**
 * Key Takeaways Shortcode
 *
 * Використання:
 * [medici_takeaways title="Що ви дізнаєтесь:"]
 * Перший пункт
 * Другий пункт
 * Третій пункт
 * [/medici_takeaways]
 *
 * Кожен новий рядок у контенті = окремий пункт списку.
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Shortcode content.
 * @return string HTML output.
 */
function medici_takeaways_shortcode( array $atts, ?string $content = null ): string {
	$atts = shortcode_atts(
		array(
			'title' => 'Що ви дізнаєтесь з цієї статті:',
		),
		$atts,
		'medici_takeaways'
	);

	// Sanitize title
	$title = sanitize_text_field( $atts['title'] );

	// Ensure string, дозволити вкладені шорткоди, потім розбити на рядки
	$content = (string) $content;
	$content = do_shortcode( $content );

	// Розбити по рядках (підтримка \r\n, \r, \n)
	$lines = preg_split( '/\R+/', trim( $content ) ?: '' );

	$items = array();
	if ( is_array( $lines ) ) {
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$items[] = $line;
			}
		}
	}

	if ( empty( $items ) ) {
		return '';
	}

	// Build HTML
	ob_start();
	?>
	<div class="takeaways">
		<h3><?php echo esc_html( $title ); ?></h3>
		<ul>
			<?php foreach ( $items as $item ) : ?>
				<li><?php echo wp_kses_post( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'medici_takeaways', 'medici_takeaways_shortcode' );
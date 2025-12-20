<?php
/**
 * Shortcodes Class
 *
 * Реєстрація та обробка shortcodes для Cookie Notice
 *
 * @package Medici_Cookie_Notice
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes class
 */
class Shortcodes {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Class constructor
	 *
	 * @param Cookie_Notice $plugin Main plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;

		// Реєстрація shortcodes
		add_action( 'init', [ $this, 'register_shortcodes' ] );
	}

	/**
	 * Реєстрація всіх shortcodes
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'medici_cookies_accepted', [ $this, 'shortcode_cookies_accepted' ] );
		add_shortcode( 'medici_cookies_revoke', [ $this, 'shortcode_cookies_revoke' ] );
		add_shortcode( 'medici_cookie_notice', [ $this, 'shortcode_cookie_notice' ] );
	}

	/**
	 * Shortcode: Контент тільки якщо cookies прийняті
	 *
	 * Використання: [medici_cookies_accepted]Контент тільки для тих, хто прийняв cookies[/medici_cookies_accepted]
	 *
	 * @param array<string, mixed> $atts    Shortcode attributes.
	 * @param string|null          $content Enclosed content.
	 * @return string
	 */
	public function shortcode_cookies_accepted( array $atts = [], ?string $content = null ): string {
		if ( null === $content || '' === trim( $content ) ) {
			return '';
		}

		// CSS class для контейнера
		$atts = shortcode_atts(
			[
				'class' => '',
			],
			$atts,
			'medici_cookies_accepted'
		);

		$class = sanitize_html_class( $atts['class'] );
		$class = '' !== $class ? ' ' . $class : '';

		// Обгортаємо контент у div з data-атрибутом
		// JavaScript буде показувати/ховати на основі статусу згоди
		return sprintf(
			'<div class="mcn-conditional-content%s" data-mcn-requires="cookies-accepted" style="display:none;">%s</div>',
			esc_attr( $class ),
			do_shortcode( $content )
		);
	}

	/**
	 * Shortcode: Кнопка відкликання згоди
	 *
	 * Використання: [medici_cookies_revoke]
	 * або [medici_cookies_revoke text="Керувати cookies"]
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_cookies_revoke( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'text'  => __( 'Керування cookies', 'medici-cookie-notice' ),
				'class' => '',
			],
			$atts,
			'medici_cookies_revoke'
		);

		$text  = sanitize_text_field( $atts['text'] );
		$class = sanitize_html_class( $atts['class'] );
		$class = '' !== $class ? ' ' . $class : '';

		return sprintf(
			'<button type="button" class="mcn-revoke-button%s" data-mcn-action="revoke" aria-label="%s">%s</button>',
			esc_attr( $class ),
			esc_attr( $text ),
			esc_html( $text )
		);
	}

	/**
	 * Shortcode: Показати cookie notice banner вручну
	 *
	 * Використання: [medici_cookie_notice]
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_cookie_notice( array $atts = [] ): string {
		// Перевірка чи плагін увімкнений
		if ( ! $this->plugin->get_option( 'enabled' ) ) {
			return '';
		}

		// Отримуємо frontend instance
		if ( null === $this->plugin->frontend ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'class' => '',
			],
			$atts,
			'medici_cookie_notice'
		);

		$class = sanitize_html_class( $atts['class'] );
		$class = '' !== $class ? ' ' . $class : '';

		// Рендеримо cookie notice inline
		ob_start();
		?>
		<div class="mcn-shortcode-wrapper<?php echo esc_attr( $class ); ?>">
			<div id="medici-cookie-notice" class="medici-cookie-notice medici-cookie-notice-shortcode"
				 data-position="<?php echo esc_attr( $this->plugin->get_option( 'position' ) ); ?>"
				 data-layout="<?php echo esc_attr( $this->plugin->get_option( 'layout' ) ); ?>"
				 role="dialog"
				 aria-labelledby="mcn-message"
				 aria-modal="true">

				<div class="mcn-container">
					<div class="mcn-message" id="mcn-message">
						<?php echo wp_kses_post( $this->plugin->get_option( 'message' ) ); ?>
					</div>

					<div class="mcn-buttons">
						<button type="button"
								class="mcn-button mcn-button-accept"
								data-mcn-action="accept-all"
								aria-label="<?php echo esc_attr( $this->plugin->get_option( 'accept_text' ) ); ?>">
							<?php echo esc_html( $this->plugin->get_option( 'accept_text' ) ); ?>
						</button>

						<?php if ( $this->plugin->get_option( 'show_reject_button' ) ) : ?>
							<button type="button"
									class="mcn-button mcn-button-reject"
									data-mcn-action="reject-all"
									aria-label="<?php echo esc_attr( $this->plugin->get_option( 'reject_text' ) ); ?>">
								<?php echo esc_html( $this->plugin->get_option( 'reject_text' ) ); ?>
							</button>
						<?php endif; ?>

						<?php if ( $this->plugin->get_option( 'show_settings_button' ) ) : ?>
							<button type="button"
									class="mcn-button mcn-button-settings"
									data-mcn-action="show-settings"
									aria-label="<?php echo esc_attr( $this->plugin->get_option( 'settings_text' ) ); ?>">
								<?php echo esc_html( $this->plugin->get_option( 'settings_text' ) ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

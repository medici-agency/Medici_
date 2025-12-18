<?php
/**
 * Клас блокування скриптів
 *
 * @package Medici_Cookie_Notice
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас Script_Blocker
 *
 * Відповідає за блокування сторонніх скриптів до отримання згоди користувача.
 * Працює з output buffering для модифікації HTML та заміни скриптів.
 */
class Script_Blocker {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Патерни для блокування за категоріями
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $patterns = [];

	/**
	 * Заблоковані скрипти
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $blocked_scripts = [];

	/**
	 * Чи активний output buffer
	 *
	 * @var bool
	 */
	private bool $buffer_active = false;

	/**
	 * Конструктор
	 *
	 * @param Cookie_Notice $plugin Головний клас плагіну
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin   = $plugin;
		$this->patterns = $this->plugin->get_option( 'blocked_patterns' ) ?: [];

		if ( $this->plugin->get_option( 'enable_script_blocking' ) ) {
			$this->init();
		}
	}

	/**
	 * Ініціалізація
	 *
	 * @return void
	 */
	private function init(): void {
		// Фільтрація тегів скриптів
		add_filter( 'script_loader_tag', [ $this, 'filter_script_tag' ], 10, 3 );

		// Output buffering для inline скриптів
		add_action( 'template_redirect', [ $this, 'start_output_buffer' ], 0 );

		// Вивід заблокованих скриптів для JS
		add_action( 'wp_footer', [ $this, 'output_blocked_scripts_data' ], 5 );
	}

	/**
	 * Фільтрація script тегів
	 *
	 * @param string $tag HTML тег скрипта
	 * @param string $handle Handle скрипта
	 * @param string $src URL скрипта
	 * @return string
	 */
	public function filter_script_tag( string $tag, string $handle, string $src ): string {
		// Перевірка чи скрипт потрібно блокувати
		$category = $this->get_script_category( $src );

		if ( null === $category ) {
			return $tag;
		}

		// Якщо категорія required - не блокуємо
		$categories = $this->plugin->cookie_categories;
		if ( isset( $categories[ $category ]['required'] ) && $categories[ $category ]['required'] ) {
			return $tag;
		}

		// Зберігаємо інформацію про заблокований скрипт
		$this->blocked_scripts[] = [
			'handle'   => $handle,
			'src'      => $src,
			'category' => $category,
		];

		// Модифікуємо тег скрипта
		return $this->modify_script_tag( $tag, $category, $src );
	}

	/**
	 * Отримання категорії скрипта за URL
	 *
	 * @param string $src URL скрипта
	 * @return string|null
	 */
	private function get_script_category( string $src ): ?string {
		if ( empty( $src ) ) {
			return null;
		}

		foreach ( $this->patterns as $category => $patterns_list ) {
			foreach ( $patterns_list as $pattern ) {
				if ( str_contains( $src, $pattern ) ) {
					return $category;
				}
			}
		}

		return null;
	}

	/**
	 * Модифікація тегу скрипта для блокування
	 *
	 * @param string $tag Оригінальний тег
	 * @param string $category Категорія
	 * @param string $src URL скрипта
	 * @return string
	 */
	private function modify_script_tag( string $tag, string $category, string $src ): string {
		// Замінюємо type на text/plain для блокування виконання
		$tag = preg_replace( '/type=["\']text\/javascript["\']/', '', $tag );

		// Замінюємо src на data-src
		$tag = str_replace( ' src=', ' data-mcn-src=', $tag );

		// Додаємо атрибути для ідентифікації
		$tag = str_replace(
			'<script',
			sprintf(
				'<script type="text/plain" data-mcn-category="%s" data-mcn-blocked="true"',
				esc_attr( $category )
			),
			$tag
		);

		return $tag;
	}

	/**
	 * Запуск output buffering
	 *
	 * @return void
	 */
	public function start_output_buffer(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		ob_start( [ $this, 'process_output_buffer' ] );
		$this->buffer_active = true;
	}

	/**
	 * Обробка output buffer
	 *
	 * @param string $buffer HTML буфер
	 * @return string
	 */
	public function process_output_buffer( string $buffer ): string {
		if ( empty( $buffer ) ) {
			return $buffer;
		}

		// Обробка inline скриптів
		$buffer = $this->process_inline_scripts( $buffer );

		// Обробка iframe (YouTube, Vimeo, Facebook, etc.)
		$buffer = $this->process_iframes( $buffer );

		return $buffer;
	}

	/**
	 * Обробка inline скриптів
	 *
	 * @param string $html HTML контент
	 * @return string
	 */
	private function process_inline_scripts( string $html ): string {
		// Патерн для знаходження inline скриптів
		$pattern = '/<script\b[^>]*>[\s\S]*?<\/script>/i';

		return preg_replace_callback( $pattern, function ( $matches ) {
			$script = $matches[0];

			// Пропускаємо якщо вже заблоковано
			if ( str_contains( $script, 'data-mcn-blocked' ) ) {
				return $script;
			}

			// Пропускаємо наші власні скрипти
			if ( str_contains( $script, 'mcn-frontend' ) || str_contains( $script, 'mcnConfig' ) ) {
				return $script;
			}

			// Перевіряємо вміст скрипта на патерни
			$category = $this->get_inline_script_category( $script );

			if ( null === $category ) {
				return $script;
			}

			return $this->modify_inline_script( $script, $category );
		}, $html );
	}

	/**
	 * Отримання категорії inline скрипта
	 *
	 * @param string $script HTML скрипта
	 * @return string|null
	 */
	private function get_inline_script_category( string $script ): ?string {
		foreach ( $this->patterns as $category => $patterns_list ) {
			foreach ( $patterns_list as $pattern ) {
				if ( str_contains( $script, $pattern ) ) {
					return $category;
				}
			}
		}

		return null;
	}

	/**
	 * Модифікація inline скрипта
	 *
	 * @param string $script Оригінальний скрипт
	 * @param string $category Категорія
	 * @return string
	 */
	private function modify_inline_script( string $script, string $category ): string {
		// Видаляємо існуючий type
		$script = preg_replace( '/type=["\'][^"\']*["\']/', '', $script );

		// Замінюємо <script на <script type="text/plain"
		$script = preg_replace(
			'/<script\b/',
			sprintf(
				'<script type="text/plain" data-mcn-category="%s" data-mcn-blocked="true"',
				esc_attr( $category )
			),
			$script,
			1
		);

		return $script;
	}

	/**
	 * Обробка iframes
	 *
	 * @param string $html HTML контент
	 * @return string
	 */
	private function process_iframes( string $html ): string {
		// Патерни для iframe сервісів
		$iframe_patterns = [
			'marketing'   => [
				'facebook.com/plugins',
				'connect.facebook.net',
				'platform.twitter.com',
				'linkedin.com/embed',
				'tiktok.com/embed',
			],
			'analytics'   => [
				'hotjar.com',
				'mouseflow.com',
			],
			'preferences' => [
				'youtube.com',
				'youtube-nocookie.com',
				'player.vimeo.com',
				'soundcloud.com',
				'spotify.com/embed',
				'google.com/maps',
				'maps.google.com',
			],
		];

		$pattern = '/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i';

		return preg_replace_callback( $pattern, function ( $matches ) use ( $iframe_patterns ) {
			$iframe = $matches[0];

			// Пропускаємо якщо вже заблоковано
			if ( str_contains( $iframe, 'data-mcn-blocked' ) ) {
				return $iframe;
			}

			// Визначаємо категорію
			$category = null;
			foreach ( $iframe_patterns as $cat => $patterns ) {
				foreach ( $patterns as $pattern ) {
					if ( str_contains( $iframe, $pattern ) ) {
						$category = $cat;
						break 2;
					}
				}
			}

			if ( null === $category ) {
				return $iframe;
			}

			return $this->modify_iframe( $iframe, $category );
		}, $html );
	}

	/**
	 * Модифікація iframe
	 *
	 * @param string $iframe Оригінальний iframe
	 * @param string $category Категорія
	 * @return string
	 */
	private function modify_iframe( string $iframe, string $category ): string {
		// Замінюємо src на data-src
		$iframe = preg_replace( '/\ssrc=/', ' data-mcn-src=', $iframe );

		// Додаємо атрибути
		$iframe = str_replace(
			'<iframe',
			sprintf(
				'<iframe data-mcn-category="%s" data-mcn-blocked="true" src="about:blank"',
				esc_attr( $category )
			),
			$iframe
		);

		// Додаємо placeholder
		$placeholder = $this->get_iframe_placeholder( $category );

		return '<div class="mcn-iframe-placeholder" data-mcn-category="' . esc_attr( $category ) . '">' .
			   $placeholder .
			   $iframe .
			   '</div>';
	}

	/**
	 * Отримання placeholder для iframe
	 *
	 * @param string $category Категорія
	 * @return string
	 */
	private function get_iframe_placeholder( string $category ): string {
		$categories = $this->plugin->cookie_categories;
		$cat_name   = $categories[ $category ]['name'] ?? $category;
		$cat_icon   = $categories[ $category ]['icon'] ?? '🔒';

		return sprintf(
			'<div class="mcn-placeholder">
				<span class="mcn-placeholder__icon mcn-emoji">%s</span>
				<p class="mcn-placeholder__text">%s</p>
				<button type="button" class="mcn-btn mcn-btn--accept mcn-placeholder__btn" data-action="accept-category" data-category="%s">
					<span class="mcn-emoji">✅</span> %s
				</button>
			</div>',
			esc_html( $cat_icon ),
			sprintf(
				/* translators: %s: category name */
				esc_html__( 'Цей контент заблоковано. Прийміть cookies категорії "%s" для перегляду.', 'medici-cookie-notice' ),
				esc_html( $cat_name )
			),
			esc_attr( $category ),
			esc_html__( 'Прийняти та показати', 'medici-cookie-notice' )
		);
	}

	/**
	 * Вивід даних заблокованих скриптів
	 *
	 * @return void
	 */
	public function output_blocked_scripts_data(): void {
		if ( empty( $this->blocked_scripts ) ) {
			return;
		}
		?>
		<script id="mcn-blocked-scripts-data" type="application/json">
			<?php echo wp_json_encode( $this->blocked_scripts ); ?>
		</script>
		<?php
	}

	/**
	 * Перевірка чи скрипт заблоковано
	 *
	 * @param string $src URL скрипта
	 * @return bool
	 */
	public function is_script_blocked( string $src ): bool {
		return null !== $this->get_script_category( $src );
	}

	/**
	 * Додавання патерну для блокування
	 *
	 * @param string $category Категорія
	 * @param string $pattern Патерн
	 * @return void
	 */
	public function add_pattern( string $category, string $pattern ): void {
		if ( ! isset( $this->patterns[ $category ] ) ) {
			$this->patterns[ $category ] = [];
		}

		if ( ! in_array( $pattern, $this->patterns[ $category ], true ) ) {
			$this->patterns[ $category ][] = $pattern;
		}
	}

	/**
	 * Видалення патерну
	 *
	 * @param string $category Категорія
	 * @param string $pattern Патерн
	 * @return void
	 */
	public function remove_pattern( string $category, string $pattern ): void {
		if ( isset( $this->patterns[ $category ] ) ) {
			$key = array_search( $pattern, $this->patterns[ $category ], true );
			if ( false !== $key ) {
				unset( $this->patterns[ $category ][ $key ] );
			}
		}
	}

	/**
	 * Отримання всіх патернів
	 *
	 * @return array<string, array<int, string>>
	 */
	public function get_patterns(): array {
		return $this->patterns;
	}

	/**
	 * Отримання заблокованих скриптів
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_blocked_scripts(): array {
		return $this->blocked_scripts;
	}
}

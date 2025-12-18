<?php
/**
 * Клас фронтенду плагіну
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
 * Клас Frontend
 */
class Frontend {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Конструктор
	 *
	 * @param Cookie_Notice $plugin Головний клас плагіну
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;

		if ( ! is_admin() || wp_doing_ajax() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
			add_action( 'wp_footer', [ $this, 'render_cookie_notice' ], 1000 );
			add_action( 'wp_head', [ $this, 'output_inline_styles' ], 100 );

			// Body classes для стилізації на основі статусу consent (як в оригінальному cookie-notice)
			add_filter( 'body_class', [ $this, 'add_body_classes' ] );

			// Google Consent Mode
			if ( $this->plugin->get_option( 'enable_gcm' ) ) {
				add_action( 'wp_head', [ $this, 'output_google_consent_mode' ], 1 );
			}
		}

		// Shortcodes
		add_shortcode( 'mcn_revoke_button', [ $this, 'shortcode_revoke_button' ] );
		add_shortcode( 'mcn_cookie_declaration', [ $this, 'shortcode_cookie_declaration' ] );
		add_shortcode( 'mcn_cookies_accepted', [ $this, 'shortcode_cookies_accepted' ] );
		add_shortcode( 'mcn_privacy_policy_link', [ $this, 'shortcode_privacy_policy_link' ] );
	}

	/**
	 * Підключення фронтенд ресурсів
	 *
	 * Використовує filemtime() для автоматичного cache busting
	 * (патерн з GeneratePress G-Child boilerplate)
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->should_display_banner() ) {
			return;
		}

		// CSS з filemtime() версіонуванням для автоматичного cache busting
		$css_file = MCN_PLUGIN_DIR . 'assets/css/frontend.css';
		$css_version = file_exists( $css_file ) ? (string) filemtime( $css_file ) : MCN_VERSION;

		wp_enqueue_style(
			'mcn-frontend',
			MCN_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			$css_version
		);

		// Inline CSS Custom Properties з налаштувань адмінки
		wp_add_inline_style( 'mcn-frontend', $this->generate_css_custom_properties() );

		// JavaScript з filemtime() версіонуванням
		$js_file = MCN_PLUGIN_DIR . 'assets/js/frontend.js';
		$js_version = file_exists( $js_file ) ? (string) filemtime( $js_file ) : MCN_VERSION;

		wp_enqueue_script(
			'mcn-frontend',
			MCN_PLUGIN_URL . 'assets/js/frontend.js',
			[],
			$js_version,
			true
		);

		// Twemoji (якщо увімкнено)
		if ( $this->plugin->get_option( 'use_twemoji' ) ) {
			// Спочатку перевіряємо чи є Twemoji в темі Medici
			$theme_twemoji = get_stylesheet_directory_uri() . '/js/twemoji/twemoji.min.js';
			$theme_twemoji_path = get_stylesheet_directory() . '/js/twemoji/twemoji.min.js';

			if ( file_exists( $theme_twemoji_path ) ) {
				// Використовуємо Twemoji з теми
				wp_enqueue_script(
					'twemoji',
					$theme_twemoji,
					[],
					'15.1.0',
					true
				);
			} else {
				// Fallback на CDN
				wp_enqueue_script(
					'twemoji',
					'https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js',
					[],
					'15.1.0',
					true
				);
			}
		}

		// Localize script
		wp_localize_script( 'mcn-frontend', 'mcnConfig', $this->get_js_config() );
	}

	/**
	 * Отримання конфігурації для JavaScript
	 *
	 * @return array<string, mixed>
	 */
	private function get_js_config(): array {
		$categories = [];
		foreach ( $this->plugin->cookie_categories as $key => $category ) {
			if ( $category['enabled'] ) {
				$categories[ $key ] = [
					'name'        => $category['name'],
					'description' => $category['description'],
					'icon'        => $category['icon'],
					'required'    => $category['required'],
				];
			}
		}

		return [
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'restUrl'             => esc_url_raw( rest_url( 'mcn/v1/consent' ) ),
			'restNonce'           => wp_create_nonce( 'wp_rest' ),
			'nonce'               => wp_create_nonce( 'mcn_consent_nonce' ),
			'cookieName'          => 'mcn_consent',
			'cookieExpiry'        => (int) $this->plugin->get_option( 'cookie_expiry' ),
			'cookieExpiryRejected' => (int) $this->plugin->get_option( 'cookie_expiry_rejected' ),
			'cookiePath'          => $this->plugin->get_option( 'cookie_path' ),
			'cookieDomain'        => $this->plugin->get_option( 'cookie_domain' ),
			'cookieSecure'        => $this->plugin->get_option( 'cookie_secure' ),
			'animation'           => $this->plugin->get_option( 'animation' ),
			'hideEffect'          => $this->plugin->get_option( 'hide_effect' ),
			'acceptOnScroll'      => $this->plugin->get_option( 'accept_on_scroll' ),
			'scrollOffset'        => (int) $this->plugin->get_option( 'scroll_offset' ),
			'acceptOnClick'       => $this->plugin->get_option( 'accept_on_click' ),
			'reloadOnChange'      => $this->plugin->get_option( 'reload_on_change' ),
			'enableCategories'    => $this->plugin->get_option( 'enable_categories' ),
			'categories'          => $categories,
			'enableGcm'           => $this->plugin->get_option( 'enable_gcm' ),
			'gcmWaitForUpdate'    => (int) $this->plugin->get_option( 'gcm_wait_for_update' ),
			'useTwemoji'          => $this->plugin->get_option( 'use_twemoji' ),
			'twemojiBase'         => $this->get_twemoji_base_url(),
			'debugMode'           => $this->plugin->get_option( 'debug_mode' ),
			'i18n'                => [
				'acceptAll'    => $this->plugin->get_option( 'accept_text' ),
				'rejectAll'    => $this->plugin->get_option( 'reject_text' ),
				'settings'     => $this->plugin->get_option( 'settings_text' ),
				'save'         => $this->plugin->get_option( 'save_text' ),
				'privacyPolicy' => $this->plugin->get_option( 'privacy_policy_text' ),
				'revoke'       => $this->plugin->get_option( 'revoke_text' ),
			],
		];
	}

	/**
	 * Отримання базового URL для Twemoji
	 *
	 * @return string
	 */
	private function get_twemoji_base_url(): string {
		// Перевіряємо чи є Twemoji assets в темі Medici
		$theme_twemoji_path = get_stylesheet_directory() . '/assets/twemoji/';

		if ( is_dir( $theme_twemoji_path ) ) {
			return get_stylesheet_directory_uri() . '/assets/twemoji/';
		}

		// Fallback на CDN
		return 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/';
	}

	/**
	 * Генерація CSS Custom Properties з налаштувань адмінки
	 * (патерн з GeneratePress G-Child boilerplate)
	 *
	 * Дозволяє повну кастомізацію стилів через :root змінні
	 *
	 * @return string CSS змінні
	 */
	private function generate_css_custom_properties(): string {
		$bar_opacity = (int) $this->plugin->get_option( 'bar_opacity' );
		$opacity_value = $bar_opacity / 100;

		$css = ':root {' . "\n";

		// Кольори банера
		$css .= sprintf(
			'  --mcn-bar-bg: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'bar_bg_color' ) )
		);
		$css .= sprintf(
			'  --mcn-bar-bg-opacity: %s;' . "\n",
			esc_attr( (string) $opacity_value )
		);
		$css .= sprintf(
			'  --mcn-bar-text: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'bar_text_color' ) )
		);

		// Кнопка Accept
		$css .= sprintf(
			'  --mcn-btn-accept-bg: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_accept_bg' ) )
		);
		$css .= sprintf(
			'  --mcn-btn-accept-text: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_accept_text' ) )
		);

		// Кнопка Reject
		$css .= sprintf(
			'  --mcn-btn-reject-bg: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_reject_bg' ) )
		);
		$css .= sprintf(
			'  --mcn-btn-reject-text: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_reject_text' ) )
		);

		// Кнопка Settings
		$css .= sprintf(
			'  --mcn-btn-settings-bg: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_settings_bg' ) )
		);
		$css .= sprintf(
			'  --mcn-btn-settings-text: %s;' . "\n",
			esc_attr( $this->plugin->get_option( 'btn_settings_text' ) )
		);

		// Border radius
		$css .= sprintf(
			'  --mcn-btn-border-radius: %spx;' . "\n",
			(int) $this->plugin->get_option( 'btn_border_radius' )
		);

		$css .= '}' . "\n";

		return $css;
	}

	/**
	 * Перевірка чи потрібно відображати банер
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @return bool
	 */
	private function should_display_banner(): bool {
		// Плагін вимкнено
		if ( ! $this->plugin->get_option( 'enabled' ) ) {
			return false;
		}

		// Режим налагодження - завжди показувати
		if ( $this->plugin->get_option( 'debug_mode' ) ) {
			return true;
		}

		// Перевірка preview режиму
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['mcn_preview'] ) ) {
			return true;
		}

		// Виключення для ботів (як в оригінальному плагіні)
		if ( $this->is_bot() ) {
			return false;
		}

		// Виключення для AMP
		if ( ! $this->plugin->get_option( 'amp_support' ) && $this->is_amp() ) {
			return false;
		}

		// Перевірка REST API requests
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		// Перевірка iframe
		if ( $this->is_iframe() ) {
			return false;
		}

		// Перевірка Customizer preview
		if ( is_customize_preview() ) {
			return false;
		}

		// Conditional Display Rules (як в оригінальному плагіні)
		if ( ! $this->check_conditional_display() ) {
			return false;
		}

		return true;
	}

	/**
	 * Додавання headers для сумісності з caching плагінами
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @return void
	 */
	public function add_cache_headers(): void {
		// Перевіряємо чи увімкнена сумісність з кешуванням
		if ( ! $this->plugin->get_option( 'cache_compatibility' ) ) {
			return;
		}

		// Встановлюємо заголовки для правильного кешування
		// Важливо для WP Rocket, Cloudflare, LiteSpeed Cache
		if ( ! headers_sent() ) {
			// Vary по Cookie для правильного кешування consent-у
			header( 'Vary: Cookie', false );
		}
	}

	/**
	 * Перевірка наявності кешуючих плагінів
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @return array<string, bool>
	 */
	public function detect_caching_plugins(): array {
		return [
			'wp_rocket'      => defined( 'WP_ROCKET_VERSION' ),
			'wp_super_cache' => defined( 'WPCACHEHOME' ),
			'w3_total_cache' => defined( 'W3TC' ),
			'litespeed'      => defined( 'LSCWP_V' ),
			'cloudflare'     => defined( 'CLOUDFLARE_PLUGIN_DIR' ) || isset( $_SERVER['HTTP_CF_RAY'] ),
			'autoptimize'    => defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ),
			'sg_optimizer'   => defined( 'SG_CACHEPRESS_VERSION' ),
		];
	}

	/**
	 * Перевірка чи це бот
	 *
	 * @return bool
	 */
	private function is_bot(): bool {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		$bots = [
			'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
			'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
			'mj12bot', 'semrushbot', 'ahrefsbot', 'dotbot', 'rogerbot',
			'screaming frog', 'uptimerobot', 'pingdom', 'gtmetrix',
		];

		$user_agent_lower = strtolower( $user_agent );

		foreach ( $bots as $bot ) {
			if ( str_contains( $user_agent_lower, $bot ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Перевірка чи це AMP сторінка
	 *
	 * @return bool
	 */
	private function is_amp(): bool {
		return function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
	}

	/**
	 * Перевірка чи сторінка в iframe
	 *
	 * @return bool
	 */
	private function is_iframe(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['iframe'] ) || isset( $_GET['elementor-preview'] );
	}

	/**
	 * Вивід inline стилів
	 *
	 * @return void
	 */
	public function output_inline_styles(): void {
		if ( ! $this->should_display_banner() ) {
			return;
		}

		$bg_color    = $this->plugin->get_option( 'bar_bg_color' );
		$text_color  = $this->plugin->get_option( 'bar_text_color' );
		$opacity     = (int) $this->plugin->get_option( 'bar_opacity' );
		$btn_radius  = (int) $this->plugin->get_option( 'btn_border_radius' );

		$accept_bg   = $this->plugin->get_option( 'btn_accept_bg' );
		$accept_text = $this->plugin->get_option( 'btn_accept_text' );
		$reject_bg   = $this->plugin->get_option( 'btn_reject_bg' );
		$reject_text = $this->plugin->get_option( 'btn_reject_text' );

		// Конвертація HEX в RGB для opacity
		$rgb = $this->hex_to_rgb( $bg_color );
		$bg_rgba = sprintf( 'rgba(%d, %d, %d, %s)', $rgb['r'], $rgb['g'], $rgb['b'], $opacity / 100 );

		$custom_css = $this->plugin->get_option( 'custom_css' );
		?>
		<style id="mcn-inline-styles">
			:root {
				--mcn-bg-color: <?php echo esc_attr( $bg_rgba ); ?>;
				--mcn-text-color: <?php echo esc_attr( $text_color ); ?>;
				--mcn-btn-radius: <?php echo esc_attr( (string) $btn_radius ); ?>px;
				--mcn-btn-accept-bg: <?php echo esc_attr( $accept_bg ); ?>;
				--mcn-btn-accept-text: <?php echo esc_attr( $accept_text ); ?>;
				--mcn-btn-reject-bg: <?php echo esc_attr( $reject_bg ); ?>;
				--mcn-btn-reject-text: <?php echo esc_attr( $reject_text ); ?>;
			}
			<?php if ( $custom_css ) : ?>
			/* Custom CSS */
			<?php echo wp_strip_all_tags( $custom_css ); ?>
			<?php endif; ?>
		</style>
		<?php
	}

	/**
	 * Вивід Google Consent Mode
	 *
	 * @return void
	 */
	public function output_google_consent_mode(): void {
		$default_analytics = $this->plugin->get_option( 'gcm_default_analytics' );
		$default_ads       = $this->plugin->get_option( 'gcm_default_ads' );
		$wait_for_update   = (int) $this->plugin->get_option( 'gcm_wait_for_update' );
		?>
		<script>
		// Google Consent Mode v2 - Default State
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}

		gtag('consent', 'default', {
			'ad_storage': '<?php echo esc_js( $default_ads ); ?>',
			'ad_user_data': '<?php echo esc_js( $default_ads ); ?>',
			'ad_personalization': '<?php echo esc_js( $default_ads ); ?>',
			'analytics_storage': '<?php echo esc_js( $default_analytics ); ?>',
			'functionality_storage': 'denied',
			'personalization_storage': 'denied',
			'security_storage': 'granted',
			'wait_for_update': <?php echo (int) $wait_for_update; ?>
		});

		// Set default ads data redaction
		gtag('set', 'ads_data_redaction', true);
		gtag('set', 'url_passthrough', true);
		</script>
		<?php
	}

	/**
	 * Рендер cookie notice
	 *
	 * @return void
	 */
	public function render_cookie_notice(): void {
		if ( ! $this->should_display_banner() ) {
			return;
		}

		$this->render_banner();

		// Кнопка відкликання
		if ( $this->plugin->get_option( 'show_revoke_button' ) ) {
			$this->render_revoke_button();
		}

		// Модальне вікно налаштувань
		if ( $this->plugin->get_option( 'enable_categories' ) ) {
			$this->render_settings_modal();
		}
	}

	/**
	 * Рендер банера
	 *
	 * @param array<string, mixed> $settings Налаштування для override
	 * @return void
	 */
	public function render_banner( array $settings = [] ): void {
		$position = $settings['position'] ?? $this->plugin->get_option( 'position' );
		$layout   = $settings['layout'] ?? $this->plugin->get_option( 'layout' );
		$message  = $settings['message'] ?? $this->plugin->get_option( 'message' );

		$classes = [
			'mcn-banner',
			'mcn-banner--' . $position,
			'mcn-banner--' . $layout,
			'mcn-banner--hidden',
		];

		$privacy_url = $this->get_privacy_policy_url();
		?>
		<div id="mcn-cookie-banner"
			 class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			 role="dialog"
			 aria-modal="true"
			 aria-labelledby="mcn-banner-title"
			 aria-describedby="mcn-banner-description">

			<div class="mcn-banner__container">
				<!-- Іконка -->
				<div class="mcn-banner__icon" aria-hidden="true">
					<span class="mcn-emoji">🍪</span>
				</div>

				<!-- Контент -->
				<div class="mcn-banner__content">
					<h2 id="mcn-banner-title" class="mcn-banner__title screen-reader-text">
						<?php esc_html_e( 'Налаштування cookies', 'medici-cookie-notice' ); ?>
					</h2>
					<p id="mcn-banner-description" class="mcn-banner__message">
						<?php echo wp_kses_post( $message ); ?>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>"
							   class="mcn-banner__privacy-link"
							   <?php echo $this->plugin->get_option( 'open_in_new_tab' ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php echo esc_html( $this->plugin->get_option( 'privacy_policy_text' ) ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>

				<!-- Кнопки -->
				<div class="mcn-banner__actions">
					<!-- Прийняти всі -->
					<button type="button"
							class="mcn-btn mcn-btn--accept"
							data-action="accept-all">
						<span class="mcn-emoji">✅</span>
						<?php echo esc_html( $this->plugin->get_option( 'accept_text' ) ); ?>
					</button>

					<!-- Відхилити всі -->
					<?php if ( $this->plugin->get_option( 'show_reject_button' ) ) : ?>
						<button type="button"
								class="mcn-btn mcn-btn--reject"
								data-action="reject-all">
							<span class="mcn-emoji">❌</span>
							<?php echo esc_html( $this->plugin->get_option( 'reject_text' ) ); ?>
						</button>
					<?php endif; ?>

					<!-- Налаштування -->
					<?php if ( $this->plugin->get_option( 'show_settings_button' ) && $this->plugin->get_option( 'enable_categories' ) ) : ?>
						<button type="button"
								class="mcn-btn mcn-btn--settings"
								data-action="open-settings">
							<span class="mcn-emoji">⚙️</span>
							<?php echo esc_html( $this->plugin->get_option( 'settings_text' ) ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Рендер кнопки відкликання
	 *
	 * @return void
	 */
	private function render_revoke_button(): void {
		$position = $this->plugin->get_option( 'position' );
		$btn_position = str_contains( $position, 'left' ) ? 'left' : 'right';
		?>
		<button type="button"
				id="mcn-revoke-button"
				class="mcn-revoke-btn mcn-revoke-btn--<?php echo esc_attr( $btn_position ); ?> mcn-revoke-btn--hidden"
				data-action="revoke"
				aria-label="<?php echo esc_attr( $this->plugin->get_option( 'revoke_text' ) ); ?>">
			<span class="mcn-emoji">🍪</span>
			<span class="mcn-revoke-btn__text"><?php echo esc_html( $this->plugin->get_option( 'revoke_text' ) ); ?></span>
		</button>
		<?php
	}

	/**
	 * Рендер модального вікна налаштувань
	 *
	 * @return void
	 */
	private function render_settings_modal(): void {
		$categories  = $this->plugin->cookie_categories;
		$privacy_url = $this->get_privacy_policy_url();
		?>
		<div id="mcn-settings-modal"
			 class="mcn-modal mcn-modal--hidden"
			 role="dialog"
			 aria-modal="true"
			 aria-labelledby="mcn-modal-title">

			<div class="mcn-modal__overlay" data-action="close-modal"></div>

			<div class="mcn-modal__container">
				<!-- Заголовок -->
				<div class="mcn-modal__header">
					<h2 id="mcn-modal-title" class="mcn-modal__title">
						<span class="mcn-emoji">⚙️</span>
						<?php esc_html_e( 'Налаштування cookies', 'medici-cookie-notice' ); ?>
					</h2>
					<button type="button"
							class="mcn-modal__close"
							data-action="close-modal"
							aria-label="<?php esc_attr_e( 'Закрити', 'medici-cookie-notice' ); ?>">
						<span class="mcn-emoji">✖️</span>
					</button>
				</div>

				<!-- Контент -->
				<div class="mcn-modal__content">
					<p class="mcn-modal__description">
						<?php esc_html_e( 'Ми використовуємо файли cookie для покращення вашого досвіду. Виберіть, які категорії cookies ви дозволяєте.', 'medici-cookie-notice' ); ?>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>"
							   <?php echo $this->plugin->get_option( 'open_in_new_tab' ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php echo esc_html( $this->plugin->get_option( 'privacy_policy_text' ) ); ?>
							</a>
						<?php endif; ?>
					</p>

					<!-- Категорії -->
					<div class="mcn-categories">
						<?php foreach ( $categories as $key => $category ) : ?>
							<?php if ( ! $category['enabled'] ) continue; ?>
							<div class="mcn-category" data-category="<?php echo esc_attr( $key ); ?>">
								<div class="mcn-category__header">
									<label class="mcn-category__label">
										<input type="checkbox"
											   class="mcn-category__checkbox"
											   name="mcn_category_<?php echo esc_attr( $key ); ?>"
											   value="1"
											   <?php checked( $category['required'] ); ?>
											   <?php disabled( $category['required'] ); ?>
											   data-category="<?php echo esc_attr( $key ); ?>"
										/>
										<span class="mcn-category__toggle"></span>
										<span class="mcn-category__icon mcn-emoji"><?php echo esc_html( $category['icon'] ); ?></span>
										<span class="mcn-category__name"><?php echo esc_html( $category['name'] ); ?></span>
										<?php if ( $category['required'] ) : ?>
											<span class="mcn-category__badge mcn-category__badge--required">
												<?php esc_html_e( "Обов'язкова", 'medici-cookie-notice' ); ?>
											</span>
										<?php endif; ?>
									</label>
									<button type="button"
											class="mcn-category__expand"
											aria-expanded="false"
											aria-label="<?php esc_attr_e( 'Показати деталі', 'medici-cookie-notice' ); ?>">
										<span class="mcn-emoji">▼</span>
									</button>
								</div>
								<div class="mcn-category__details" hidden>
									<p class="mcn-category__description">
										<?php echo wp_kses_post( $category['description'] ); ?>
									</p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Footer -->
				<div class="mcn-modal__footer">
					<button type="button"
							class="mcn-btn mcn-btn--reject"
							data-action="reject-all">
						<span class="mcn-emoji">❌</span>
						<?php echo esc_html( $this->plugin->get_option( 'reject_text' ) ); ?>
					</button>
					<button type="button"
							class="mcn-btn mcn-btn--save"
							data-action="save-preferences">
						<span class="mcn-emoji">💾</span>
						<?php echo esc_html( $this->plugin->get_option( 'save_text' ) ); ?>
					</button>
					<button type="button"
							class="mcn-btn mcn-btn--accept"
							data-action="accept-all">
						<span class="mcn-emoji">✅</span>
						<?php echo esc_html( $this->plugin->get_option( 'accept_text' ) ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Отримання URL політики конфіденційності
	 *
	 * @return string
	 */
	private function get_privacy_policy_url(): string {
		$page_id = (int) $this->plugin->get_option( 'privacy_policy_page' );

		if ( $page_id > 0 ) {
			$url = get_permalink( $page_id );
			return $url ? $url : '';
		}

		// WordPress Privacy Policy
		$wp_privacy_page = get_option( 'wp_page_for_privacy_policy' );

		if ( $wp_privacy_page ) {
			$url = get_permalink( (int) $wp_privacy_page );
			return $url ? $url : '';
		}

		return '';
	}

	/**
	 * Конвертація HEX в RGB
	 *
	 * @param string $hex HEX колір
	 * @return array<string, int>
	 */
	private function hex_to_rgb( string $hex ): array {
		$hex = ltrim( $hex, '#' );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return [
			'r' => (int) hexdec( substr( $hex, 0, 2 ) ),
			'g' => (int) hexdec( substr( $hex, 2, 2 ) ),
			'b' => (int) hexdec( substr( $hex, 4, 2 ) ),
		];
	}

	/**
	 * Shortcode: Кнопка відкликання
	 *
	 * @param array<string, string> $atts Атрибути
	 * @return string
	 */
	public function shortcode_revoke_button( array $atts = [] ): string {
		$atts = shortcode_atts( [
			'text'  => $this->plugin->get_option( 'revoke_text' ),
			'class' => '',
		], $atts, 'mcn_revoke_button' );

		$classes = 'mcn-revoke-inline';
		if ( ! empty( $atts['class'] ) ) {
			$classes .= ' ' . sanitize_html_class( $atts['class'] );
		}

		return sprintf(
			'<button type="button" class="%s" data-action="revoke"><span class="mcn-emoji">🍪</span> %s</button>',
			esc_attr( $classes ),
			esc_html( $atts['text'] )
		);
	}

	/**
	 * Shortcode: Декларація cookies
	 *
	 * @param array<string, string> $atts Атрибути
	 * @return string
	 */
	public function shortcode_cookie_declaration( array $atts = [] ): string {
		$atts = shortcode_atts( [
			'title' => __( 'Cookies на цьому сайті', 'medici-cookie-notice' ),
		], $atts, 'mcn_cookie_declaration' );

		$categories = $this->plugin->cookie_categories;
		$patterns   = $this->plugin->get_option( 'blocked_patterns' );

		ob_start();
		?>
		<div class="mcn-declaration">
			<h3 class="mcn-declaration__title"><?php echo esc_html( $atts['title'] ); ?></h3>

			<?php foreach ( $categories as $key => $category ) : ?>
				<?php if ( ! $category['enabled'] ) continue; ?>
				<div class="mcn-declaration__category">
					<h4 class="mcn-declaration__category-title">
						<span class="mcn-emoji"><?php echo esc_html( $category['icon'] ); ?></span>
						<?php echo esc_html( $category['name'] ); ?>
						<?php if ( $category['required'] ) : ?>
							<span class="mcn-declaration__badge"><?php esc_html_e( "Обов'язкова", 'medici-cookie-notice' ); ?></span>
						<?php endif; ?>
					</h4>
					<p class="mcn-declaration__category-desc">
						<?php echo wp_kses_post( $category['description'] ); ?>
					</p>

					<?php if ( isset( $patterns[ $key ] ) && ! empty( $patterns[ $key ] ) ) : ?>
						<details class="mcn-declaration__details">
							<summary><?php esc_html_e( 'Сервіси в цій категорії', 'medici-cookie-notice' ); ?></summary>
							<ul class="mcn-declaration__services">
								<?php foreach ( $patterns[ $key ] as $pattern ) : ?>
									<li><code><?php echo esc_html( $pattern ); ?></code></li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<p class="mcn-declaration__revoke">
				<?php esc_html_e( 'Ви можете змінити свій вибір у будь-який час:', 'medici-cookie-notice' ); ?>
				<?php echo do_shortcode( '[mcn_revoke_button]' ); ?>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode: Показати контент тільки якщо cookies прийнято
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @param array<string, string> $atts Атрибути
	 * @param string|null $content Контент
	 * @return string
	 */
	public function shortcode_cookies_accepted( array $atts = [], ?string $content = null ): string {
		$atts = shortcode_atts( [
			'category' => '', // Опціональна категорія для перевірки
		], $atts, 'mcn_cookies_accepted' );

		// Цей shortcode працює на клієнтській стороні через JavaScript
		// Серверна сторона завжди повертає контент з data-атрибутом для JS обробки
		$category_attr = ! empty( $atts['category'] ) ? ' data-mcn-category="' . esc_attr( $atts['category'] ) . '"' : '';

		return sprintf(
			'<span class="mcn-conditional-content" data-mcn-show-if-accepted="true"%s style="display:none;">%s</span>',
			$category_attr,
			do_shortcode( $content ?? '' )
		);
	}

	/**
	 * Shortcode: Посилання на політику конфіденційності
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @param array<string, string> $atts Атрибути
	 * @return string
	 */
	public function shortcode_privacy_policy_link( array $atts = [] ): string {
		$atts = shortcode_atts( [
			'text'   => $this->plugin->get_option( 'privacy_policy_text' ),
			'class'  => '',
			'target' => $this->plugin->get_option( 'open_in_new_tab' ) ? '_blank' : '_self',
		], $atts, 'mcn_privacy_policy_link' );

		$page_id = (int) $this->plugin->get_option( 'privacy_policy_page' );

		// Якщо сторінка не вказана, використовуємо стандартну WordPress Privacy Policy
		if ( ! $page_id ) {
			$page_id = (int) get_option( 'wp_page_for_privacy_policy', 0 );
		}

		if ( ! $page_id ) {
			return esc_html( $atts['text'] );
		}

		$url = get_permalink( $page_id );
		if ( ! $url ) {
			return esc_html( $atts['text'] );
		}

		$classes = 'mcn-privacy-link';
		if ( ! empty( $atts['class'] ) ) {
			$classes .= ' ' . sanitize_html_class( $atts['class'] );
		}

		$target_attr = '';
		$rel_attr    = '';
		if ( '_blank' === $atts['target'] ) {
			$target_attr = ' target="_blank"';
			$rel_attr    = ' rel="noopener noreferrer"';
		}

		return sprintf(
			'<a href="%s" class="%s"%s%s>%s</a>',
			esc_url( $url ),
			esc_attr( $classes ),
			$target_attr,
			$rel_attr,
			esc_html( $atts['text'] )
		);
	}

	/**
	 * Додавання body classes на основі статусу consent
	 * Адаптовано з оригінального cookie-notice плагіну (dfactory)
	 *
	 * Класи:
	 * - cookies-not-set: consent ще не дано
	 * - cookies-set: consent було дано
	 * - cookies-accepted: всі cookies прийнято
	 * - cookies-refused: cookies відхилено
	 *
	 * @param array<int, string> $classes Існуючі класи
	 * @return array<int, string>
	 */
	public function add_body_classes( array $classes ): array {
		// Читаємо cookie з PHP (серверна сторона)
		$cookie_name = 'mcn_consent';

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$consent_data = json_decode( sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ), true );

			if ( is_array( $consent_data ) && isset( $consent_data['status'] ) ) {
				$classes[] = 'cookies-set';

				switch ( $consent_data['status'] ) {
					case 'accepted':
						$classes[] = 'cookies-accepted';
						break;
					case 'rejected':
						$classes[] = 'cookies-refused';
						break;
					case 'custom':
						$classes[] = 'cookies-custom';
						break;
				}

				// Додаємо класи для конкретних категорій
				if ( isset( $consent_data['categories'] ) && is_array( $consent_data['categories'] ) ) {
					foreach ( $consent_data['categories'] as $category => $enabled ) {
						if ( $enabled ) {
							$classes[] = 'cookies-category-' . sanitize_html_class( $category );
						}
					}
				}
			}
		} else {
			$classes[] = 'cookies-not-set';
		}

		return $classes;
	}

	/**
	 * Перевірка conditional display rules
	 * Адаптовано з оригінального cookie-notice плагіну
	 *
	 * @return bool True якщо потрібно показувати банер
	 */
	private function check_conditional_display(): bool {
		$rules = $this->plugin->get_option( 'conditional_rules' );

		// Якщо правила не налаштовані - показуємо завжди
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return true;
		}

		$conditional_enabled = $this->plugin->get_option( 'conditional_enabled' );
		if ( ! $conditional_enabled ) {
			return true;
		}

		$conditional_action = $this->plugin->get_option( 'conditional_action' ); // 'show' або 'hide'

		foreach ( $rules as $rule ) {
			if ( ! isset( $rule['param'], $rule['operator'], $rule['value'] ) ) {
				continue;
			}

			$match = $this->evaluate_rule( $rule['param'], $rule['operator'], $rule['value'] );

			if ( $match ) {
				// Якщо знайдено відповідність
				return 'show' === $conditional_action;
			}
		}

		// Якщо жодне правило не спрацювало - інвертуємо action
		return 'hide' === $conditional_action;
	}

	/**
	 * Оцінка окремого правила
	 *
	 * @param string $param Параметр правила
	 * @param string $operator Оператор (equal, not_equal)
	 * @param string $value Значення для порівняння
	 * @return bool
	 */
	private function evaluate_rule( string $param, string $operator, string $value ): bool {
		$result = false;

		switch ( $param ) {
			case 'page_type':
				$result = $this->check_page_type( $value );
				break;

			case 'page':
				$result = is_page( (int) $value );
				break;

			case 'post_type':
				$result = is_singular( $value );
				break;

			case 'post_type_archive':
				$result = is_post_type_archive( $value );
				break;

			case 'user_type':
				$result = $this->check_user_type( $value );
				break;

			case 'taxonomy':
				$result = is_tax( $value ) || is_category( $value ) || is_tag( $value );
				break;
		}

		// Інверсія для not_equal
		if ( 'not_equal' === $operator ) {
			$result = ! $result;
		}

		return $result;
	}

	/**
	 * Перевірка типу сторінки
	 *
	 * @param string $type Тип сторінки
	 * @return bool
	 */
	private function check_page_type( string $type ): bool {
		switch ( $type ) {
			case 'front_page':
				return is_front_page();
			case 'home':
				return is_home();
			case 'singular':
				return is_singular();
			case 'archive':
				return is_archive();
			case 'search':
				return is_search();
			case '404':
				return is_404();
			default:
				return false;
		}
	}

	/**
	 * Перевірка типу користувача
	 *
	 * @param string $type Тип користувача
	 * @return bool
	 */
	private function check_user_type( string $type ): bool {
		switch ( $type ) {
			case 'logged_in':
				return is_user_logged_in();
			case 'logged_out':
				return ! is_user_logged_in();
			case 'admin':
				return current_user_can( 'manage_options' );
			default:
				return false;
		}
	}
}

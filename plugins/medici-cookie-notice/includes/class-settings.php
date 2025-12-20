<?php
/**
 * Клас налаштувань плагіну
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
 * Клас Settings
 */
class Settings {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Поточна вкладка
	 *
	 * @var string
	 */
	private string $current_tab = 'general';

	/**
	 * Доступні вкладки
	 *
	 * @var array<string, string>
	 */
	private array $tabs = [];

	/**
	 * Конструктор
	 *
	 * @param Cookie_Notice $plugin Головний клас плагіну
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;

		// Меню реєструється тільки якщо Admin_Menu не активний (для сумісності)
		if ( ! class_exists( 'Medici\CookieNotice\Admin\Admin_Menu' ) ) {
			add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		}
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'init_tabs' ], 1 ); // WordPress 6.7+ - after textdomain loaded
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// AJAX для preview
		add_action( 'wp_ajax_mcn_preview_banner', [ $this, 'ajax_preview_banner' ] );
	}

	/**
	 * Initialize tabs (after textdomain loaded on init)
	 *
	 * @return void
	 */
	public function init_tabs(): void {
		$this->tabs = [
			'general'     => __( '🍪 Загальні', 'medici-cookie-notice' ),
			'appearance'  => __( '🎨 Вигляд', 'medici-cookie-notice' ),
			'categories'  => __( '📋 Категорії', 'medici-cookie-notice' ),
			'blocking'    => __( '🚫 Блокування', 'medici-cookie-notice' ),
			'consent'     => __( '📝 Журнал згод', 'medici-cookie-notice' ),
			'analytics'   => __( '📊 Аналітика', 'medici-cookie-notice' ),
			'geo'         => __( '🌍 Гео-детекція', 'medici-cookie-notice' ),
			'integration' => __( '🔗 Інтеграції', 'medici-cookie-notice' ),
			'advanced'    => __( '⚙️ Додатково', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Додавання сторінки меню
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_options_page(
			__( 'Medici Cookie Notice', 'medici-cookie-notice' ),
			__( '🍪 Cookie Notice', 'medici-cookie-notice' ),
			'manage_options',
			'medici-cookie-notice',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Реєстрація налаштувань
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'medici_cookie_notice',
			'medici_cookie_notice',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->plugin->defaults,
			]
		);

		// Секції та поля для кожної вкладки
		$this->register_general_settings();
		$this->register_appearance_settings();
		$this->register_categories_settings();
		$this->register_blocking_settings();
		$this->register_consent_settings();
		$this->register_analytics_settings();
		$this->register_geo_settings();
		$this->register_integration_settings();
		$this->register_advanced_settings();
	}

	/**
	 * Реєстрація загальних налаштувань
	 *
	 * @return void
	 */
	private function register_general_settings(): void {
		add_settings_section(
			'mcn_general_section',
			__( 'Основні налаштування', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Налаштуйте основні параметри відображення банера cookies.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_general'
		);

		// Увімкнути/Вимкнути
		add_settings_field(
			'enabled',
			__( 'Увімкнути банер', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_general',
			'mcn_general_section',
			[
				'id'          => 'enabled',
				'description' => __( 'Показувати банер cookie notice на сайті', 'medici-cookie-notice' ),
			]
		);

		// Позиція
		add_settings_field(
			'position',
			__( 'Позиція банера', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_general',
			'mcn_general_section',
			[
				'id'      => 'position',
				'options' => [
					'bottom'         => __( '⬇️ Знизу', 'medici-cookie-notice' ),
					'top'            => __( '⬆️ Зверху', 'medici-cookie-notice' ),
					'floating-left'  => __( '↙️ Плаваючий зліва', 'medici-cookie-notice' ),
					'floating-right' => __( '↘️ Плаваючий справа', 'medici-cookie-notice' ),
				],
			]
		);

		// Макет
		add_settings_field(
			'layout',
			__( 'Макет', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_general',
			'mcn_general_section',
			[
				'id'      => 'layout',
				'options' => [
					'bar'   => __( '📊 Горизонтальний бар', 'medici-cookie-notice' ),
					'box'   => __( '📦 Блок', 'medici-cookie-notice' ),
					'modal' => __( '🪟 Модальне вікно', 'medici-cookie-notice' ),
				],
			]
		);

		// Анімація
		add_settings_field(
			'animation',
			__( 'Анімація', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_general',
			'mcn_general_section',
			[
				'id'      => 'animation',
				'options' => [
					'slide' => __( '📥 Slide', 'medici-cookie-notice' ),
					'fade'  => __( '✨ Fade', 'medici-cookie-notice' ),
					'none'  => __( '⏹️ Без анімації', 'medici-cookie-notice' ),
				],
			]
		);

		// Секція текстів
		add_settings_section(
			'mcn_texts_section',
			__( 'Тексти', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Налаштуйте тексти банера та кнопок.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_general'
		);

		// Повідомлення
		add_settings_field(
			'message',
			__( 'Повідомлення', 'medici-cookie-notice' ),
			[ $this, 'render_textarea_field' ],
			'mcn_general',
			'mcn_texts_section',
			[
				'id'          => 'message',
				'rows'        => 3,
				'description' => __( 'Основний текст банера', 'medici-cookie-notice' ),
			]
		);

		// Текст кнопки прийняття
		add_settings_field(
			'accept_text',
			__( 'Кнопка "Прийняти"', 'medici-cookie-notice' ),
			[ $this, 'render_text_field' ],
			'mcn_general',
			'mcn_texts_section',
			[ 'id' => 'accept_text' ]
		);

		// Текст кнопки відмови
		add_settings_field(
			'reject_text',
			__( 'Кнопка "Відхилити"', 'medici-cookie-notice' ),
			[ $this, 'render_text_field' ],
			'mcn_general',
			'mcn_texts_section',
			[ 'id' => 'reject_text' ]
		);

		// Текст кнопки налаштувань
		add_settings_field(
			'settings_text',
			__( 'Кнопка "Налаштування"', 'medici-cookie-notice' ),
			[ $this, 'render_text_field' ],
			'mcn_general',
			'mcn_texts_section',
			[ 'id' => 'settings_text' ]
		);

		// Секція кнопок
		add_settings_section(
			'mcn_buttons_section',
			__( 'Кнопки', 'medici-cookie-notice' ),
			null,
			'mcn_general'
		);

		add_settings_field(
			'show_reject_button',
			__( 'Показати "Відхилити"', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_general',
			'mcn_buttons_section',
			[ 'id' => 'show_reject_button' ]
		);

		add_settings_field(
			'show_settings_button',
			__( 'Показати "Налаштування"', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_general',
			'mcn_buttons_section',
			[ 'id' => 'show_settings_button' ]
		);

		add_settings_field(
			'show_revoke_button',
			__( 'Показати "Керування cookies"', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_general',
			'mcn_buttons_section',
			[
				'id'          => 'show_revoke_button',
				'description' => __( 'Плаваюча кнопка для зміни налаштувань після закриття банера', 'medici-cookie-notice' ),
			]
		);

		// Privacy Policy
		add_settings_section(
			'mcn_privacy_section',
			__( 'Політика конфіденційності', 'medici-cookie-notice' ),
			null,
			'mcn_general'
		);

		add_settings_field(
			'privacy_policy_page',
			__( 'Сторінка політики', 'medici-cookie-notice' ),
			[ $this, 'render_page_select_field' ],
			'mcn_general',
			'mcn_privacy_section',
			[
				'id'          => 'privacy_policy_page',
				'description' => __( 'Виберіть сторінку або залиште порожнім для використання WordPress Privacy Policy', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'open_in_new_tab',
			__( 'Відкривати в новій вкладці', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_general',
			'mcn_privacy_section',
			[ 'id' => 'open_in_new_tab' ]
		);
	}

	/**
	 * Реєстрація налаштувань вигляду
	 *
	 * @return void
	 */
	private function register_appearance_settings(): void {
		add_settings_section(
			'mcn_colors_section',
			__( 'Кольори', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Налаштуйте кольорову схему банера.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_appearance'
		);

		// Колір фону банера
		add_settings_field(
			'bar_bg_color',
			__( 'Фон банера', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'bar_bg_color' ]
		);

		// Колір тексту банера
		add_settings_field(
			'bar_text_color',
			__( 'Текст банера', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'bar_text_color' ]
		);

		// Прозорість
		add_settings_field(
			'bar_opacity',
			__( 'Прозорість фону', 'medici-cookie-notice' ),
			[ $this, 'render_range_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[
				'id'  => 'bar_opacity',
				'min' => 0,
				'max' => 100,
			]
		);

		// Кнопка прийняття
		add_settings_field(
			'btn_accept_bg',
			__( 'Фон кнопки "Прийняти"', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'btn_accept_bg' ]
		);

		add_settings_field(
			'btn_accept_text',
			__( 'Текст кнопки "Прийняти"', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'btn_accept_text' ]
		);

		// Кнопка відмови
		add_settings_field(
			'btn_reject_bg',
			__( 'Фон кнопки "Відхилити"', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'btn_reject_bg' ]
		);

		add_settings_field(
			'btn_reject_text',
			__( 'Текст кнопки "Відхилити"', 'medici-cookie-notice' ),
			[ $this, 'render_color_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[ 'id' => 'btn_reject_text' ]
		);

		// Радіус заокруглення
		add_settings_field(
			'btn_border_radius',
			__( 'Заокруглення кнопок (px)', 'medici-cookie-notice' ),
			[ $this, 'render_number_field' ],
			'mcn_appearance',
			'mcn_colors_section',
			[
				'id'  => 'btn_border_radius',
				'min' => 0,
				'max' => 50,
			]
		);

		// Кастомний CSS
		add_settings_section(
			'mcn_custom_css_section',
			__( 'Кастомний CSS', 'medici-cookie-notice' ),
			null,
			'mcn_appearance'
		);

		add_settings_field(
			'custom_css',
			__( 'CSS код', 'medici-cookie-notice' ),
			[ $this, 'render_code_field' ],
			'mcn_appearance',
			'mcn_custom_css_section',
			[
				'id'       => 'custom_css',
				'language' => 'css',
				'rows'     => 10,
			]
		);
	}

	/**
	 * Реєстрація налаштувань категорій
	 *
	 * @return void
	 */
	private function register_categories_settings(): void {
		add_settings_section(
			'mcn_categories_section',
			__( 'Категорії cookies', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Налаштуйте категорії cookies для гранулярного контролю згоди.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_categories'
		);

		add_settings_field(
			'enable_categories',
			__( 'Увімкнути категорії', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_categories',
			'mcn_categories_section',
			[
				'id'          => 'enable_categories',
				'description' => __( 'Дозволити користувачам обирати окремі категорії cookies', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'categories',
			__( 'Налаштування категорій', 'medici-cookie-notice' ),
			[ $this, 'render_categories_field' ],
			'mcn_categories',
			'mcn_categories_section'
		);
	}

	/**
	 * Реєстрація налаштувань блокування
	 *
	 * @return void
	 */
	private function register_blocking_settings(): void {
		add_settings_section(
			'mcn_blocking_section',
			__( 'Блокування скриптів', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Автоматичне блокування сторонніх скриптів до отримання згоди.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_blocking'
		);

		add_settings_field(
			'enable_script_blocking',
			__( 'Увімкнути блокування', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_blocking',
			'mcn_blocking_section',
			[
				'id'          => 'enable_script_blocking',
				'description' => __( 'Блокувати скрипти за категоріями до отримання згоди', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'blocked_patterns',
			__( 'Патерни для блокування', 'medici-cookie-notice' ),
			[ $this, 'render_blocked_patterns_field' ],
			'mcn_blocking',
			'mcn_blocking_section'
		);

		// Google Consent Mode
		add_settings_section(
			'mcn_gcm_section',
			__( 'Google Consent Mode v2', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Інтеграція з Google Consent Mode для GA4, GTM, Google Ads.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_blocking'
		);

		add_settings_field(
			'enable_gcm',
			__( 'Увімкнути Google Consent Mode', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_blocking',
			'mcn_gcm_section',
			[ 'id' => 'enable_gcm' ]
		);

		add_settings_field(
			'gcm_default_analytics',
			__( 'Аналітика за замовчуванням', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_blocking',
			'mcn_gcm_section',
			[
				'id'      => 'gcm_default_analytics',
				'options' => [
					'denied'  => __( '🚫 Denied (рекомендовано для GDPR)', 'medici-cookie-notice' ),
					'granted' => __( '✅ Granted', 'medici-cookie-notice' ),
				],
			]
		);

		add_settings_field(
			'gcm_default_ads',
			__( 'Реклама за замовчуванням', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_blocking',
			'mcn_gcm_section',
			[
				'id'      => 'gcm_default_ads',
				'options' => [
					'denied'  => __( '🚫 Denied (рекомендовано для GDPR)', 'medici-cookie-notice' ),
					'granted' => __( '✅ Granted', 'medici-cookie-notice' ),
				],
			]
		);
	}

	/**
	 * Реєстрація налаштувань журналу згод
	 *
	 * @return void
	 */
	private function register_consent_settings(): void {
		add_settings_section(
			'mcn_consent_section',
			__( 'Журнал згод', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Зберігайте історію згод для аудиту та compliance.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_consent'
		);

		add_settings_field(
			'enable_consent_logs',
			__( 'Увімкнути логування', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_consent',
			'mcn_consent_section',
			[ 'id' => 'enable_consent_logs' ]
		);

		add_settings_field(
			'consent_logs_retention',
			__( 'Зберігати записи (днів)', 'medici-cookie-notice' ),
			[ $this, 'render_number_field' ],
			'mcn_consent',
			'mcn_consent_section',
			[
				'id'          => 'consent_logs_retention',
				'min'         => 30,
				'max'         => 730,
				'description' => __( 'GDPR рекомендує мінімум 1 рік', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'log_ip_address',
			__( 'Логувати IP адреси', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_consent',
			'mcn_consent_section',
			[
				'id'          => 'log_ip_address',
				'description' => __( '⚠️ Може потребувати додаткової згоди', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'anonymize_ip',
			__( 'Анонімізувати IP', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_consent',
			'mcn_consent_section',
			[
				'id'          => 'anonymize_ip',
				'description' => __( 'Зберігати тільки перші 3 октети IP (рекомендовано)', 'medici-cookie-notice' ),
			]
		);
	}

	/**
	 * Реєстрація налаштувань аналітики
	 *
	 * @return void
	 */
	private function register_analytics_settings(): void {
		add_settings_section(
			'mcn_analytics_section',
			__( 'Аналітика згод', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Статистика та звіти по згодам.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_analytics'
		);

		add_settings_field(
			'enable_analytics',
			__( 'Увімкнути аналітику', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_analytics',
			'mcn_analytics_section',
			[ 'id' => 'enable_analytics' ]
		);

		add_settings_field(
			'analytics_retention',
			__( 'Зберігати дані (днів)', 'medici-cookie-notice' ),
			[ $this, 'render_number_field' ],
			'mcn_analytics',
			'mcn_analytics_section',
			[
				'id'  => 'analytics_retention',
				'min' => 7,
				'max' => 365,
			]
		);
	}

	/**
	 * Реєстрація налаштувань гео-детекції
	 *
	 * @return void
	 */
	private function register_geo_settings(): void {
		add_settings_section(
			'mcn_geo_section',
			__( 'Гео-детекція', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Автоматичне визначення юрисдикції для застосування відповідних правил (GDPR, CCPA).', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_geo'
		);

		add_settings_field(
			'enable_geo_detection',
			__( 'Увімкнути гео-детекцію', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_geo',
			'mcn_geo_section',
			[ 'id' => 'enable_geo_detection' ]
		);

		add_settings_field(
			'geo_api_provider',
			__( 'API провайдер', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_geo',
			'mcn_geo_section',
			[
				'id'      => 'geo_api_provider',
				'options' => [
					'ipapi'      => __( 'ip-api.com (безкоштовно)', 'medici-cookie-notice' ),
					'geojs'      => __( 'GeoJS (безкоштовно)', 'medici-cookie-notice' ),
					'cloudflare' => __( 'Cloudflare Headers (якщо доступно)', 'medici-cookie-notice' ),
				],
			]
		);

		add_settings_field(
			'geo_rules',
			__( 'Правила за регіонами', 'medici-cookie-notice' ),
			[ $this, 'render_geo_rules_field' ],
			'mcn_geo',
			'mcn_geo_section'
		);
	}

	/**
	 * Реєстрація налаштувань інтеграцій
	 *
	 * @return void
	 */
	private function register_integration_settings(): void {
		add_settings_section(
			'mcn_integration_section',
			__( 'Інтеграції', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Інтеграція з популярними плагінами та сервісами.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_integration'
		);

		add_settings_field(
			'wpml_support',
			__( 'WPML/Polylang підтримка', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_integration',
			'mcn_integration_section',
			[
				'id'          => 'wpml_support',
				'description' => __( 'Автоматичний переклад текстів через WPML або Polylang', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'cache_compatibility',
			__( 'Сумісність з кешем', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_integration',
			'mcn_integration_section',
			[
				'id'          => 'cache_compatibility',
				'description' => __( 'Оптимізація для WP Super Cache, W3 Total Cache, WP Rocket', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'amp_support',
			__( 'AMP підтримка', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_integration',
			'mcn_integration_section',
			[ 'id' => 'amp_support' ]
		);
	}

	/**
	 * Реєстрація додаткових налаштувань
	 *
	 * @return void
	 */
	private function register_advanced_settings(): void {
		add_settings_section(
			'mcn_cookies_section',
			__( 'Налаштування cookies', 'medici-cookie-notice' ),
			null,
			'mcn_advanced'
		);

		add_settings_field(
			'cookie_expiry',
			__( 'Термін дії (прийняття)', 'medici-cookie-notice' ),
			[ $this, 'render_number_field' ],
			'mcn_advanced',
			'mcn_cookies_section',
			[
				'id'          => 'cookie_expiry',
				'min'         => 1,
				'max'         => 365,
				'description' => __( 'Днів до закінчення згоди', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'cookie_expiry_rejected',
			__( 'Термін дії (відмова)', 'medici-cookie-notice' ),
			[ $this, 'render_number_field' ],
			'mcn_advanced',
			'mcn_cookies_section',
			[
				'id'          => 'cookie_expiry_rejected',
				'min'         => 1,
				'max'         => 365,
				'description' => __( 'Днів до повторного запиту при відмові', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'cookie_path',
			__( 'Cookie Path', 'medici-cookie-notice' ),
			[ $this, 'render_text_field' ],
			'mcn_advanced',
			'mcn_cookies_section',
			[
				'id'          => 'cookie_path',
				'description' => __( 'Залиште "/" для всього сайту', 'medici-cookie-notice' ),
			]
		);

		// Поведінка
		add_settings_section(
			'mcn_behavior_section',
			__( 'Поведінка', 'medici-cookie-notice' ),
			null,
			'mcn_advanced'
		);

		add_settings_field(
			'accept_on_scroll',
			__( 'Прийняти при скролі', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_behavior_section',
			[
				'id'          => 'accept_on_scroll',
				'description' => __( '⚠️ Не рекомендується для GDPR', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'accept_on_click',
			__( 'Прийняти при кліку поза банером', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_behavior_section',
			[
				'id'          => 'accept_on_click',
				'description' => __( '⚠️ Не рекомендується для GDPR', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'reload_on_change',
			__( 'Перезавантаження при зміні', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_behavior_section',
			[
				'id'          => 'reload_on_change',
				'description' => __( 'Перезавантажити сторінку при зміні налаштувань cookies', 'medici-cookie-notice' ),
			]
		);

		// Twemoji
		add_settings_section(
			'mcn_twemoji_section',
			__( 'Іконки Twemoji', 'medici-cookie-notice' ),
			null,
			'mcn_advanced'
		);

		add_settings_field(
			'use_twemoji',
			__( 'Використовувати Twemoji', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_twemoji_section',
			[
				'id'          => 'use_twemoji',
				'description' => __( 'Відображати емоджі як SVG іконки через Twemoji', 'medici-cookie-notice' ),
			]
		);

		// Bot Detection
		add_settings_section(
			'mcn_bot_detection_section',
			__( '🤖 Детекція ботів', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Автоматична детекція ботів/crawlers для покращення performance.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_advanced'
		);

		add_settings_field(
			'bot_detection',
			__( 'Увімкнути детекцію ботів', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_bot_detection_section',
			[
				'id'          => 'bot_detection',
				'description' => __( 'Не показувати банер для crawlers (Google, Bing, Facebook bot, тощо)', 'medici-cookie-notice' ),
			]
		);

		// Conditional Display
		add_settings_section(
			'mcn_conditional_display_section',
			__( '🎯 Умовний показ', 'medici-cookie-notice' ),
			function () {
				echo '<p>' . esc_html__( 'Налаштуйте правила показу банера на основі типу користувача, ролей та сторінок.', 'medici-cookie-notice' ) . '</p>';
			},
			'mcn_advanced'
		);

		add_settings_field(
			'user_type',
			__( 'Тип користувача', 'medici-cookie-notice' ),
			[ $this, 'render_select_field' ],
			'mcn_advanced',
			'mcn_conditional_display_section',
			[
				'id'      => 'user_type',
				'options' => [
					'all'        => __( 'Всі користувачі', 'medici-cookie-notice' ),
					'logged_in'  => __( 'Тільки залогінені', 'medici-cookie-notice' ),
					'guest'      => __( 'Тільки гості', 'medici-cookie-notice' ),
				],
			]
		);

		add_settings_field(
			'excluded_roles',
			__( 'Виключити ролі', 'medici-cookie-notice' ),
			[ $this, 'render_multiselect_field' ],
			'mcn_advanced',
			'mcn_conditional_display_section',
			[
				'id'          => 'excluded_roles',
				'description' => __( 'Не показувати банер для цих ролей користувачів', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'excluded_page_types',
			__( 'Виключити типи сторінок', 'medici-cookie-notice' ),
			[ $this, 'render_multiselect_field' ],
			'mcn_advanced',
			'mcn_conditional_display_section',
			[
				'id'          => 'excluded_page_types',
				'description' => __( 'Не показувати банер на цих типах сторінок', 'medici-cookie-notice' ),
			]
		);

		add_settings_field(
			'excluded_page_ids',
			__( 'Виключити сторінки за ID', 'medici-cookie-notice' ),
			[ $this, 'render_text_field' ],
			'mcn_advanced',
			'mcn_conditional_display_section',
			[
				'id'          => 'excluded_page_ids',
				'description' => __( 'ID сторінок/постів через кому (напр. 1,2,3)', 'medici-cookie-notice' ),
			]
		);

		// Debug
		add_settings_section(
			'mcn_debug_section',
			__( 'Налагодження', 'medici-cookie-notice' ),
			null,
			'mcn_advanced'
		);

		add_settings_field(
			'debug_mode',
			__( 'Режим налагодження', 'medici-cookie-notice' ),
			[ $this, 'render_checkbox_field' ],
			'mcn_advanced',
			'mcn_debug_section',
			[
				'id'          => 'debug_mode',
				'description' => __( 'Показувати банер навіть якщо згода вже надана', 'medici-cookie-notice' ),
			]
		);
	}

	/**
	 * Рендер checkbox поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_checkbox_field( array $args ): void {
		$id      = $args['id'];
		$value   = $this->plugin->get_option( $id );
		$desc    = $args['description'] ?? '';
		$checked = $value ? 'checked' : '';

		printf(
			'<label><input type="checkbox" name="medici_cookie_notice[%s]" value="1" %s /> %s</label>',
			esc_attr( $id ),
			$checked,
			esc_html( $desc )
		);
	}

	/**
	 * Рендер text поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$desc  = $args['description'] ?? '';
		$class = $args['class'] ?? 'regular-text';

		printf(
			'<input type="text" name="medici_cookie_notice[%s]" value="%s" class="%s" />',
			esc_attr( $id ),
			esc_attr( (string) $value ),
			esc_attr( $class )
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер textarea поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_textarea_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$rows  = $args['rows'] ?? 5;
		$desc  = $args['description'] ?? '';

		printf(
			'<textarea name="medici_cookie_notice[%s]" rows="%d" class="large-text">%s</textarea>',
			esc_attr( $id ),
			(int) $rows,
			esc_textarea( (string) $value )
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер select поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_select_field( array $args ): void {
		$id      = $args['id'];
		$value   = $this->plugin->get_option( $id );
		$options = $args['options'] ?? [];
		$desc    = $args['description'] ?? '';

		printf( '<select name="medici_cookie_notice[%s]">', esc_attr( $id ) );

		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}

		echo '</select>';

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер multiselect поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_multiselect_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$desc  = $args['description'] ?? '';

		// Приводимо до масиву якщо не масив
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		// Отримуємо опції залежно від id поля
		$options = [];
		if ( 'excluded_roles' === $id ) {
			// Отримуємо ролі з Conditional_Display класу
			if ( null !== $this->plugin->conditional_display ) {
				$options = $this->plugin->conditional_display->get_user_roles();
			}
		} elseif ( 'excluded_page_types' === $id ) {
			// Отримуємо типи сторінок з Conditional_Display класу
			if ( null !== $this->plugin->conditional_display ) {
				$options = $this->plugin->conditional_display->get_page_types();
			}
		}

		if ( empty( $options ) ) {
			echo '<p class="description">' . esc_html__( 'Немає доступних опцій', 'medici-cookie-notice' ) . '</p>';
			return;
		}

		printf( '<select name="medici_cookie_notice[%s][]" multiple size="5" style="min-width: 300px;">', esc_attr( $id ) );

		foreach ( $options as $key => $label ) {
			$selected = in_array( $key, $value, true ) ? 'selected' : '';
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				$selected,
				esc_html( $label )
			);
		}

		echo '</select>';

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер number поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_number_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$min   = $args['min'] ?? 0;
		$max   = $args['max'] ?? 9999;
		$desc  = $args['description'] ?? '';

		printf(
			'<input type="number" name="medici_cookie_notice[%s]" value="%s" min="%d" max="%d" class="small-text" />',
			esc_attr( $id ),
			esc_attr( (string) $value ),
			(int) $min,
			(int) $max
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер color поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_color_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );

		printf(
			'<input type="color" name="medici_cookie_notice[%s]" value="%s" class="mcn-color-picker" />',
			esc_attr( $id ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Рендер range поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_range_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$min   = $args['min'] ?? 0;
		$max   = $args['max'] ?? 100;

		printf(
			'<input type="range" name="medici_cookie_notice[%s]" value="%s" min="%d" max="%d" class="mcn-range" />
			<span class="mcn-range-value">%s%%</span>',
			esc_attr( $id ),
			esc_attr( (string) $value ),
			(int) $min,
			(int) $max,
			esc_html( (string) $value )
		);
	}

	/**
	 * Рендер page select поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_page_select_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$desc  = $args['description'] ?? '';

		wp_dropdown_pages( [
			'name'              => 'medici_cookie_notice[' . $id . ']',
			'selected'          => (int) $value,
			'show_option_none'  => __( '— Автоматично —', 'medici-cookie-notice' ),
			'option_none_value' => 0,
		] );

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Рендер code поля
	 *
	 * @param array<string, mixed> $args Аргументи
	 * @return void
	 */
	public function render_code_field( array $args ): void {
		$id    = $args['id'];
		$value = $this->plugin->get_option( $id );
		$rows  = $args['rows'] ?? 10;
		$lang  = $args['language'] ?? 'css';

		printf(
			'<textarea name="medici_cookie_notice[%s]" rows="%d" class="large-text code" data-language="%s">%s</textarea>',
			esc_attr( $id ),
			(int) $rows,
			esc_attr( $lang ),
			esc_textarea( (string) $value )
		);
	}

	/**
	 * Рендер поля категорій
	 *
	 * @return void
	 */
	public function render_categories_field(): void {
		$categories = $this->plugin->get_option( 'categories' );
		?>
		<div class="mcn-categories-list">
			<?php foreach ( $categories as $key => $category ) : ?>
				<div class="mcn-category-item" data-category="<?php echo esc_attr( $key ); ?>">
					<div class="mcn-category-header">
						<span class="mcn-category-icon"><?php echo esc_html( $category['icon'] ); ?></span>
						<strong><?php echo esc_html( $category['name'] ); ?></strong>
						<?php if ( $category['required'] ) : ?>
							<span class="mcn-badge mcn-badge-required"><?php esc_html_e( "Обов'язкова", 'medici-cookie-notice' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="mcn-category-fields">
						<label>
							<input type="checkbox"
								name="medici_cookie_notice[categories][<?php echo esc_attr( $key ); ?>][enabled]"
								value="1"
								<?php checked( $category['enabled'] ); ?>
								<?php disabled( $category['required'] ); ?>
							/>
							<?php esc_html_e( 'Увімкнена', 'medici-cookie-notice' ); ?>
						</label>
						<input type="hidden"
							name="medici_cookie_notice[categories][<?php echo esc_attr( $key ); ?>][required]"
							value="<?php echo $category['required'] ? '1' : '0'; ?>"
						/>
						<input type="text"
							name="medici_cookie_notice[categories][<?php echo esc_attr( $key ); ?>][name]"
							value="<?php echo esc_attr( $category['name'] ); ?>"
							class="regular-text"
							placeholder="<?php esc_attr_e( 'Назва', 'medici-cookie-notice' ); ?>"
						/>
						<input type="text"
							name="medici_cookie_notice[categories][<?php echo esc_attr( $key ); ?>][icon]"
							value="<?php echo esc_attr( $category['icon'] ); ?>"
							class="small-text"
							placeholder="<?php esc_attr_e( 'Іконка', 'medici-cookie-notice' ); ?>"
						/>
						<textarea
							name="medici_cookie_notice[categories][<?php echo esc_attr( $key ); ?>][description]"
							rows="2"
							class="large-text"
							placeholder="<?php esc_attr_e( 'Опис', 'medici-cookie-notice' ); ?>"
						><?php echo esc_textarea( $category['description'] ); ?></textarea>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Рендер поля патернів блокування
	 *
	 * @return void
	 */
	public function render_blocked_patterns_field(): void {
		$patterns   = $this->plugin->get_option( 'blocked_patterns' );
		$categories = $this->plugin->get_option( 'categories' );
		?>
		<div class="mcn-blocked-patterns">
			<?php foreach ( [ 'analytics', 'marketing', 'preferences' ] as $category ) : ?>
				<?php if ( isset( $categories[ $category ] ) ) : ?>
					<div class="mcn-pattern-group">
						<h4>
							<?php echo esc_html( $categories[ $category ]['icon'] ); ?>
							<?php echo esc_html( $categories[ $category ]['name'] ); ?>
						</h4>
						<textarea
							name="medici_cookie_notice[blocked_patterns][<?php echo esc_attr( $category ); ?>]"
							rows="5"
							class="large-text code"
							placeholder="<?php esc_attr_e( 'Один патерн на рядок', 'medici-cookie-notice' ); ?>"
						><?php echo esc_textarea( implode( "\n", $patterns[ $category ] ?? [] ) ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Домени або частини URL скриптів для блокування', 'medici-cookie-notice' ); ?>
						</p>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Рендер поля гео-правил
	 *
	 * @return void
	 */
	public function render_geo_rules_field(): void {
		$rules = $this->plugin->get_option( 'geo_rules' );
		$modes = [
			'strict'  => __( '🔒 Strict (вимагає згоди)', 'medici-cookie-notice' ),
			'ccpa'    => __( '🇺🇸 CCPA (opt-out)', 'medici-cookie-notice' ),
			'notice'  => __( '📋 Notice only', 'medici-cookie-notice' ),
			'implied' => __( '✅ Implied consent', 'medici-cookie-notice' ),
		];

		$regions = [
			'EU'      => __( '🇪🇺 Європейський Союз (GDPR)', 'medici-cookie-notice' ),
			'US-CA'   => __( '🇺🇸 Каліфорнія (CCPA)', 'medici-cookie-notice' ),
			'UK'      => __( '🇬🇧 Великобританія', 'medici-cookie-notice' ),
			'BR'      => __( '🇧🇷 Бразилія (LGPD)', 'medici-cookie-notice' ),
			'default' => __( '🌍 Інші регіони', 'medici-cookie-notice' ),
		];
		?>
		<table class="mcn-geo-rules widefat">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Регіон', 'medici-cookie-notice' ); ?></th>
					<th><?php esc_html_e( 'Режим', 'medici-cookie-notice' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $regions as $region => $label ) : ?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td>
							<select name="medici_cookie_notice[geo_rules][<?php echo esc_attr( $region ); ?>]">
								<?php foreach ( $modes as $mode => $mode_label ) : ?>
									<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $rules[ $region ] ?? 'notice', $mode ); ?>>
										<?php echo esc_html( $mode_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Санітизація налаштувань
	 *
	 * @param array<string, mixed> $input Вхідні дані
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		// Отримуємо існуючі налаштування для збереження значень з інших вкладок
		$existing = get_option( 'medici_cookie_notice', $this->plugin->defaults );
		$output   = is_array( $existing ) ? $existing : $this->plugin->defaults;

		// Визначаємо поточну вкладку для збереження тільки її полів
		$current_tab = isset( $input['active_tab'] ) ? sanitize_key( $input['active_tab'] ) : 'general';

		// Маппінг полів по вкладках
		$tab_fields = [
			'general'     => [
				'checkboxes' => [ 'enabled', 'show_reject_button', 'show_settings_button', 'show_revoke_button', 'open_in_new_tab' ],
				'text'       => [ 'message', 'accept_text', 'reject_text', 'settings_text', 'save_text', 'privacy_policy_text', 'revoke_text' ],
				'select'     => [ 'position', 'layout' ],
				'other'      => [ 'privacy_policy_page' ],
			],
			'appearance'  => [
				'checkboxes' => [],
				'text'       => [],
				'select'     => [ 'animation', 'hide_effect' ],
				'color'      => [ 'bar_bg_color', 'bar_text_color', 'btn_accept_bg', 'btn_accept_text', 'btn_reject_bg', 'btn_reject_text', 'btn_settings_bg', 'btn_settings_text' ],
				'number'     => [ 'bar_opacity', 'btn_border_radius' ],
			],
			'categories'  => [
				'checkboxes' => [ 'enable_categories' ],
				'complex'    => [ 'categories' ],
			],
			'blocking'    => [
				'checkboxes' => [ 'enable_script_blocking', 'enable_gcm' ],
				'select'     => [ 'gcm_default_analytics', 'gcm_default_ads' ],
				'number'     => [ 'gcm_wait_for_update' ],
				'complex'    => [ 'blocked_patterns' ],
			],
			'consent'     => [
				'checkboxes' => [ 'enable_consent_logs', 'log_ip_address', 'anonymize_ip' ],
				'number'     => [ 'consent_logs_retention' ],
			],
			'analytics'   => [
				'checkboxes' => [ 'enable_analytics' ],
				'number'     => [ 'analytics_retention' ],
			],
			'geo'         => [
				'checkboxes' => [ 'enable_geo_detection' ],
				'select'     => [ 'geo_api_provider' ],
				'complex'    => [ 'geo_rules' ],
			],
			'integration' => [
				'checkboxes' => [ 'wpml_support', 'cache_compatibility', 'amp_support' ],
			],
			'advanced'    => [
				'checkboxes' => [ 'bot_detection', 'accept_on_scroll', 'accept_on_click', 'reload_on_change', 'debug_mode' ],
				'text'       => [ 'cookie_path', 'custom_css', 'custom_js', 'excluded_page_ids' ],
				'select'     => [ 'user_type' ],
				'number'     => [ 'cookie_expiry', 'cookie_expiry_rejected', 'scroll_offset' ],
				'multiselect' => [ 'excluded_roles', 'excluded_page_types' ],
			],
		];

		// Отримуємо поля для поточної вкладки
		$fields = $tab_fields[ $current_tab ] ?? [];

		// Valid values для select полів
		$select_valid_values = [
			'position'             => [ 'bottom', 'top', 'floating-left', 'floating-right' ],
			'layout'               => [ 'bar', 'box', 'modal' ],
			'animation'            => [ 'slide', 'fade', 'none' ],
			'hide_effect'          => [ 'fade', 'slide', 'none' ],
			'gcm_default_analytics' => [ 'denied', 'granted' ],
			'gcm_default_ads'      => [ 'denied', 'granted' ],
			'geo_api_provider'     => [ 'ipapi', 'geojs', 'cloudflare' ],
			'user_type'            => [ 'all', 'logged_in', 'guest' ],
		];

		// Ranges для number полів
		$number_ranges = [
			'bar_opacity'            => [ 0, 100 ],
			'btn_border_radius'      => [ 0, 50 ],
			'cookie_expiry'          => [ 1, 365 ],
			'cookie_expiry_rejected' => [ 1, 365 ],
			'scroll_offset'          => [ 10, 1000 ],
			'consent_logs_retention' => [ 30, 730 ],
			'analytics_retention'    => [ 7, 365 ],
			'gcm_wait_for_update'    => [ 100, 10000 ],
		];

		// Обробляємо Boolean поля (тільки для поточної вкладки)
		if ( ! empty( $fields['checkboxes'] ) ) {
			foreach ( $fields['checkboxes'] as $key ) {
				$output[ $key ] = ! empty( $input[ $key ] );
			}
		}

		// Обробляємо текстові поля (тільки для поточної вкладки)
		if ( ! empty( $fields['text'] ) ) {
			foreach ( $fields['text'] as $key ) {
				if ( 'custom_css' === $key ) {
					$output[ $key ] = isset( $input[ $key ] ) ? wp_strip_all_tags( $input[ $key ] ) : ( $output[ $key ] ?? '' );
				} elseif ( 'custom_js' === $key ) {
					$output[ $key ] = isset( $input[ $key ] ) ? $input[ $key ] : ( $output[ $key ] ?? '' );
				} else {
					$output[ $key ] = isset( $input[ $key ] ) ? wp_kses_post( $input[ $key ] ) : ( $output[ $key ] ?? $this->plugin->defaults[ $key ] );
				}
			}
		}

		// Обробляємо select поля (тільки для поточної вкладки)
		if ( ! empty( $fields['select'] ) ) {
			foreach ( $fields['select'] as $key ) {
				$valid_values   = $select_valid_values[ $key ] ?? [];
				$output[ $key ] = isset( $input[ $key ] ) && in_array( $input[ $key ], $valid_values, true )
					? $input[ $key ]
					: ( $output[ $key ] ?? $this->plugin->defaults[ $key ] );
			}
		}

		// Обробляємо кольори (тільки для поточної вкладки)
		if ( ! empty( $fields['color'] ) ) {
			foreach ( $fields['color'] as $key ) {
				$output[ $key ] = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : ( $output[ $key ] ?? $this->plugin->defaults[ $key ] );
			}
		}

		// Обробляємо числові поля (тільки для поточної вкладки)
		if ( ! empty( $fields['number'] ) ) {
			foreach ( $fields['number'] as $key ) {
				$range          = $number_ranges[ $key ] ?? [ 0, PHP_INT_MAX ];
				$value          = isset( $input[ $key ] ) ? (int) $input[ $key ] : ( $output[ $key ] ?? $this->plugin->defaults[ $key ] );
				$output[ $key ] = max( $range[0], min( $range[1], $value ) );
			}
		}

		// Обробляємо інші поля
		if ( ! empty( $fields['other'] ) ) {
			foreach ( $fields['other'] as $key ) {
				if ( 'privacy_policy_page' === $key ) {
					$output[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : ( $output[ $key ] ?? 0 );
				}
			}
		}

		// Обробляємо складні поля
		if ( ! empty( $fields['complex'] ) ) {
			foreach ( $fields['complex'] as $key ) {
				if ( 'categories' === $key && isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
					$output['categories'] = [];
					foreach ( $input['categories'] as $cat_key => $category ) {
						$safe_key                          = sanitize_key( $cat_key );
						$output['categories'][ $safe_key ] = [
							'enabled'     => ! empty( $category['enabled'] ),
							'required'    => ! empty( $category['required'] ),
							'name'        => isset( $category['name'] ) ? sanitize_text_field( $category['name'] ) : '',
							'description' => isset( $category['description'] ) ? wp_kses_post( $category['description'] ) : '',
							'icon'        => isset( $category['icon'] ) ? sanitize_text_field( $category['icon'] ) : '',
						];
					}
				}

				if ( 'blocked_patterns' === $key && isset( $input['blocked_patterns'] ) && is_array( $input['blocked_patterns'] ) ) {
					$output['blocked_patterns'] = [];
					foreach ( $input['blocked_patterns'] as $category => $patterns ) {
						$safe_category = sanitize_key( $category );
						if ( is_string( $patterns ) ) {
							$patterns = explode( "\n", $patterns );
						}
						$output['blocked_patterns'][ $safe_category ] = array_filter( array_map( 'sanitize_text_field', (array) $patterns ) );
					}
				}

				if ( 'geo_rules' === $key && isset( $input['geo_rules'] ) && is_array( $input['geo_rules'] ) ) {
					$output['geo_rules'] = [];
					$valid_modes         = [ 'strict', 'ccpa', 'notice', 'implied' ];
					foreach ( $input['geo_rules'] as $region => $mode ) {
						$safe_region = sanitize_key( $region );
						if ( in_array( $mode, $valid_modes, true ) ) {
							$output['geo_rules'][ $safe_region ] = $mode;
						}
					}
				}
			}
		}

		// Обробляємо multiselect поля
		if ( ! empty( $fields['multiselect'] ) ) {
			foreach ( $fields['multiselect'] as $key ) {
				if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
					$output[ $key ] = array_map( 'sanitize_key', $input[ $key ] );
				} else {
					$output[ $key ] = [];
				}
			}
		}

		// Очистка кешу після збереження
		$this->plugin->clear_cache();

		return $output;
	}

	/**
	 * Підключення адмін ресурсів
	 *
	 * @param string $hook Поточний hook
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Support both old and new admin menu pages
		if ( 'settings_page_medici-cookie-notice' !== $hook && ! str_contains( $hook, 'mcn-' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'mcn-admin',
			MCN_PLUGIN_URL . 'assets/css/admin.css',
			[],
			MCN_VERSION
		);

		// Twemoji для preview та адмінки
		$theme_twemoji_path = get_stylesheet_directory() . '/js/twemoji/twemoji.min.js';
		if ( file_exists( $theme_twemoji_path ) ) {
			wp_enqueue_script(
				'twemoji',
				get_stylesheet_directory_uri() . '/js/twemoji/twemoji.min.js',
				[],
				'14.0.2',
				true
			);
		} else {
			wp_enqueue_script(
				'twemoji',
				'https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js',
				[],
				'15.0.0',
				true
			);
		}

		// Frontend styles for preview
		wp_enqueue_style(
			'mcn-frontend',
			MCN_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			MCN_VERSION
		);

		wp_enqueue_script(
			'mcn-admin',
			MCN_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery', 'wp-color-picker', 'twemoji' ],
			MCN_VERSION,
			true
		);

		// Get Twemoji base URL
		$twemoji_base = 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/';
		$theme_twemoji_assets = get_stylesheet_directory() . '/assets/twemoji/';
		if ( is_dir( $theme_twemoji_assets ) ) {
			$twemoji_base = get_stylesheet_directory_uri() . '/assets/twemoji/';
		}

		wp_localize_script( 'mcn-admin', 'mcnAdmin', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'mcn_admin_nonce' ),
			'useTwemoji'   => $this->plugin->get_option( 'use_twemoji' ),
			'twemojiBase'  => $twemoji_base,
			'options'      => $this->plugin->options,
			'defaults'     => $this->plugin->defaults,
			'i18n'         => [
				'saved'   => __( 'Налаштування збережено', 'medici-cookie-notice' ),
				'error'   => __( 'Помилка збереження', 'medici-cookie-notice' ),
				'confirm' => __( 'Ви впевнені?', 'medici-cookie-notice' ),
			],
		] );
	}

	/**
	 * Рендер сторінки налаштувань
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Поточна вкладка
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

		if ( ! array_key_exists( $this->current_tab, $this->tabs ) ) {
			$this->current_tab = 'general';
		}
		?>
		<div class="wrap mcn-settings-wrap">
			<h1>
				<span class="mcn-logo">🍪</span>
				<?php esc_html_e( 'Medici Cookie Notice', 'medici-cookie-notice' ); ?>
				<span class="mcn-version"><?php echo esc_html( 'v' . MCN_VERSION ); ?></span>
			</h1>

			<?php settings_errors(); ?>

			<nav class="nav-tab-wrapper mcn-tabs">
				<?php foreach ( $this->tabs as $tab_id => $tab_name ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>"
					   class="nav-tab <?php echo $this->current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="mcn-settings-form">
				<?php
				settings_fields( 'medici_cookie_notice' );
				?>
				<input type="hidden" name="medici_cookie_notice[active_tab]" value="<?php echo esc_attr( $this->current_tab ); ?>">
				<?php
				echo '<div class="mcn-tab-content">';

				switch ( $this->current_tab ) {
					case 'general':
						do_settings_sections( 'mcn_general' );
						break;
					case 'appearance':
						do_settings_sections( 'mcn_appearance' );
						$this->render_preview_section();
						break;
					case 'categories':
						do_settings_sections( 'mcn_categories' );
						break;
					case 'blocking':
						do_settings_sections( 'mcn_blocking' );
						break;
					case 'consent':
						do_settings_sections( 'mcn_consent' );
						$this->render_consent_logs_table();
						break;
					case 'analytics':
						do_settings_sections( 'mcn_analytics' );
						$this->render_analytics_dashboard();
						break;
					case 'geo':
						do_settings_sections( 'mcn_geo' );
						break;
					case 'integration':
						do_settings_sections( 'mcn_integration' );
						break;
					case 'advanced':
						do_settings_sections( 'mcn_advanced' );
						break;
				}

				echo '</div>';

				submit_button( __( 'Зберегти налаштування', 'medici-cookie-notice' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Рендер секції попереднього перегляду
	 *
	 * @return void
	 */
	private function render_preview_section(): void {
		$position = $this->plugin->get_option( 'position' );
		$layout   = $this->plugin->get_option( 'layout' );
		?>
		<div class="mcn-preview-section">
			<h3><?php esc_html_e( '👁️ Попередній перегляд', 'medici-cookie-notice' ); ?></h3>
			<div class="mcn-preview-info"></div>
			<div class="mcn-preview-container mcn-preview-<?php echo esc_attr( $layout ); ?>">
				<div id="mcn-banner-preview" class="mcn-preview-banner mcn-preview-<?php echo esc_attr( $position ); ?> mcn-preview-<?php echo esc_attr( $layout ); ?>">
					<div class="mcn-preview-content">
						<p class="mcn-preview-message"><?php echo esc_html( $this->plugin->get_option( 'message' ) ); ?></p>
						<div class="mcn-preview-buttons">
							<button type="button" class="mcn-preview-btn mcn-preview-btn-accept">
								<?php echo esc_html( $this->plugin->get_option( 'accept_text' ) ); ?>
							</button>
							<button type="button" class="mcn-preview-btn mcn-preview-btn-reject" <?php echo $this->plugin->get_option( 'show_reject_button' ) ? '' : 'style="display:none"'; ?>>
								<?php echo esc_html( $this->plugin->get_option( 'reject_text' ) ); ?>
							</button>
							<button type="button" class="mcn-preview-btn mcn-preview-btn-settings" <?php echo $this->plugin->get_option( 'show_settings_button' ) ? '' : 'style="display:none"'; ?>>
								<?php echo esc_html( $this->plugin->get_option( 'settings_text' ) ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<p class="mcn-preview-note">
				<em><?php esc_html_e( 'Попередній перегляд оновлюється в реальному часі при зміні налаштувань.', 'medici-cookie-notice' ); ?></em>
			</p>
		</div>
		<?php
	}

	/**
	 * Рендер таблиці логів згод
	 *
	 * @return void
	 */
	private function render_consent_logs_table(): void {
		if ( null === $this->plugin->consent_logs ) {
			return;
		}

		$logs = $this->plugin->consent_logs->get_recent_logs( 20 );
		?>
		<div class="mcn-logs-section">
			<h3><?php esc_html_e( '📝 Останні записи згод', 'medici-cookie-notice' ); ?></h3>
			<?php if ( empty( $logs ) ) : ?>
				<p class="mcn-no-data"><?php esc_html_e( 'Записи згод поки відсутні.', 'medici-cookie-notice' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'medici-cookie-notice' ); ?></th>
							<th><?php esc_html_e( 'Статус', 'medici-cookie-notice' ); ?></th>
							<th><?php esc_html_e( 'Категорії', 'medici-cookie-notice' ); ?></th>
							<th><?php esc_html_e( 'Країна', 'medici-cookie-notice' ); ?></th>
							<th><?php esc_html_e( 'Дата', 'medici-cookie-notice' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><code><?php echo esc_html( substr( $log->consent_id, 0, 8 ) ); ?>...</code></td>
								<td>
									<?php
									$status_icons = [
										'accepted' => '✅',
										'rejected' => '❌',
										'custom'   => '⚙️',
									];
									echo esc_html( ( $status_icons[ $log->consent_status ] ?? '❓' ) . ' ' . $log->consent_status );
									?>
								</td>
								<td>
									<?php
									$categories = json_decode( $log->consent_categories, true );
									echo esc_html( implode( ', ', array_keys( array_filter( $categories ?? [] ) ) ) );
									?>
								</td>
								<td><?php echo esc_html( $log->geo_country ?: '—' ); ?></td>
								<td><?php echo esc_html( wp_date( 'd.m.Y H:i', strtotime( $log->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Рендер дашборду аналітики
	 *
	 * @return void
	 */
	private function render_analytics_dashboard(): void {
		if ( null === $this->plugin->analytics ) {
			return;
		}

		$stats = $this->plugin->analytics->get_stats( 30 );
		?>
		<div class="mcn-analytics-dashboard">
			<h3><?php esc_html_e( '📊 Статистика за 30 днів', 'medici-cookie-notice' ); ?></h3>

			<div class="mcn-stats-grid">
				<div class="mcn-stat-card">
					<span class="mcn-stat-icon">👥</span>
					<span class="mcn-stat-value"><?php echo esc_html( number_format_i18n( $stats['total_visitors'] ) ); ?></span>
					<span class="mcn-stat-label"><?php esc_html_e( 'Відвідувачів', 'medici-cookie-notice' ); ?></span>
				</div>
				<div class="mcn-stat-card mcn-stat-accepted">
					<span class="mcn-stat-icon">✅</span>
					<span class="mcn-stat-value"><?php echo esc_html( number_format_i18n( $stats['accepted_all'] ) ); ?></span>
					<span class="mcn-stat-label"><?php esc_html_e( 'Прийняли всі', 'medici-cookie-notice' ); ?></span>
				</div>
				<div class="mcn-stat-card mcn-stat-rejected">
					<span class="mcn-stat-icon">❌</span>
					<span class="mcn-stat-value"><?php echo esc_html( number_format_i18n( $stats['rejected_all'] ) ); ?></span>
					<span class="mcn-stat-label"><?php esc_html_e( 'Відхилили всі', 'medici-cookie-notice' ); ?></span>
				</div>
				<div class="mcn-stat-card mcn-stat-custom">
					<span class="mcn-stat-icon">⚙️</span>
					<span class="mcn-stat-value"><?php echo esc_html( number_format_i18n( $stats['customized'] ) ); ?></span>
					<span class="mcn-stat-label"><?php esc_html_e( 'Налаштували', 'medici-cookie-notice' ); ?></span>
				</div>
			</div>

			<?php if ( $stats['total_visitors'] > 0 ) : ?>
				<div class="mcn-consent-rate">
					<h4><?php esc_html_e( '📈 Рівень згоди', 'medici-cookie-notice' ); ?></h4>
					<div class="mcn-rate-bar">
						<?php
						$accept_rate = round( ( $stats['accepted_all'] / $stats['total_visitors'] ) * 100, 1 );
						$reject_rate = round( ( $stats['rejected_all'] / $stats['total_visitors'] ) * 100, 1 );
						$custom_rate = round( ( $stats['customized'] / $stats['total_visitors'] ) * 100, 1 );
						?>
						<div class="mcn-rate-segment mcn-rate-accepted" style="width: <?php echo esc_attr( (string) $accept_rate ); ?>%;">
							<?php echo esc_html( $accept_rate ); ?>%
						</div>
						<div class="mcn-rate-segment mcn-rate-custom" style="width: <?php echo esc_attr( (string) $custom_rate ); ?>%;">
							<?php echo esc_html( $custom_rate ); ?>%
						</div>
						<div class="mcn-rate-segment mcn-rate-rejected" style="width: <?php echo esc_attr( (string) $reject_rate ); ?>%;">
							<?php echo esc_html( $reject_rate ); ?>%
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX: Попередній перегляд банера
	 *
	 * @return void
	 */
	public function ajax_preview_banner(): void {
		check_ajax_referer( 'mcn_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		// Отримання та санітизація параметрів
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$settings = isset( $_POST['settings'] ) ? (array) $_POST['settings'] : [];

		ob_start();
		$this->plugin->frontend->render_banner( $settings );
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}
}

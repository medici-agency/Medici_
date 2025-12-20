<?php
/**
 * Plugin Name: Medici Cookie Notice
 * Plugin URI: https://www.medici.agency
 * Description: Повноцінне рішення для управління згодою на cookies з підтримкою GDPR, CCPA, категорій згоди, блокування скриптів, аналітики та Twemoji іконок.
 * Version: 1.3.0
 * Author: Medici Agency
 * Author URI: https://www.medici.agency
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: medici-cookie-notice
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package Medici_Cookie_Notice
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

// Запобігання прямому доступу
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Константи плагіну
define( 'MCN_VERSION', '1.3.0' );
define( 'MCN_PLUGIN_FILE', __FILE__ );
define( 'MCN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MCN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MCN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Головний клас плагіну Cookie Notice
 *
 * @since 1.0.0
 */
final class Cookie_Notice {

	/**
	 * Singleton instance
	 *
	 * @var Cookie_Notice|null
	 */
	private static ?Cookie_Notice $instance = null;

	/**
	 * Об'єкт налаштувань
	 *
	 * @var Settings|null
	 */
	public ?Settings $settings = null;

	/**
	 * Об'єкт фронтенду
	 *
	 * @var Frontend|null
	 */
	public ?Frontend $frontend = null;

	/**
	 * Об'єкт блокувальника скриптів
	 *
	 * @var Script_Blocker|null
	 */
	public ?Script_Blocker $script_blocker = null;

	/**
	 * Об'єкт логування згод
	 *
	 * @var Consent_Logs|null
	 */
	public ?Consent_Logs $consent_logs = null;

	/**
	 * Об'єкт аналітики
	 *
	 * @var Analytics|null
	 */
	public ?Analytics $analytics = null;

	/**
	 * Об'єкт гео-детекції
	 *
	 * @var Geo_Detection|null
	 */
	public ?Geo_Detection $geo_detection = null;

	/**
	 * Об'єкт bot detection
	 *
	 * @var Bot_Detect|null
	 */
	public ?Bot_Detect $bot_detect = null;

	/**
	 * Об'єкт shortcodes
	 *
	 * @var Shortcodes|null
	 */
	public ?Shortcodes $shortcodes = null;

	/**
	 * Об'єкт conditional display
	 *
	 * @var Conditional_Display|null
	 */
	public ?Conditional_Display $conditional_display = null;

	/**
	 * Об'єкт conditional rules (advanced)
	 *
	 * @var Conditional_Rules|null
	 */
	public ?Conditional_Rules $conditional_rules = null;

	/**
	 * Об'єкт cache compatibility
	 *
	 * @var Modules\Cache_Compatibility|null
	 */
	public ?Modules\Cache_Compatibility $cache_compatibility = null;

	/**
	 * Об'єкт admin menu
	 *
	 * @var Admin\Admin_Menu|null
	 */
	public ?Admin\Admin_Menu $admin_menu = null;

	/**
	 * Loader для централізованого управління hooks
	 *
	 * @var Loader|null
	 */
	private ?Loader $loader = null;

	/**
	 * Налаштування за замовчуванням
	 *
	 * @var array<string, mixed>
	 */
	public array $defaults = [];

	/**
	 * Поточні налаштування
	 *
	 * @var array<string, mixed>
	 */
	public array $options = [];

	/**
	 * Категорії cookies
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $cookie_categories = [];

	/**
	 * Отримати singleton instance
	 *
	 * @return Cookie_Notice
	 */
	public static function get_instance(): Cookie_Notice {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Конструктор
	 */
	private function __construct() {
		$this->init_defaults();
		$this->load_options();
		$this->includes();

		// Ініціалізація Loader
		$this->loader = new Loader();

		// Визначення hooks
		$this->define_core_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// Activation/Deactivation (не йдуть через Loader)
		register_activation_hook( MCN_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( MCN_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Запуск всіх зареєстрованих hooks
		$this->loader->run();
	}

	/**
	 * Визначення core hooks (завантаження плагіну)
	 *
	 * @return void
	 */
	private function define_core_hooks(): void {
		$this->loader->add_action( 'init', $this, 'load_textdomain', 1 ); // WordPress 6.7+ requires init
		$this->loader->add_action( 'init', $this, 'translate_defaults', 5 ); // After textdomain loaded
		$this->loader->add_action( 'init', $this, 'init' );
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
	}

	/**
	 * Визначення admin hooks
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$this->loader->add_action( 'admin_init', $this, 'admin_init' );
		$this->loader->add_filter( 'plugin_action_links_' . MCN_PLUGIN_BASENAME, $this, 'plugin_action_links' );
	}

	/**
	 * Визначення public hooks (AJAX handlers)
	 *
	 * @return void
	 */
	private function define_public_hooks(): void {
		// AJAX handlers реєструються на 'init' в методі init()
		// Тут можна додати інші публічні hooks
	}

	/**
	 * Ініціалізація значень за замовчуванням
	 *
	 * @return void
	 */
	private function init_defaults(): void {
		$this->defaults = [
			// Загальні налаштування
			'enabled'                => true,
			'position'               => 'bottom', // bottom, top, floating-left, floating-right
			'layout'                 => 'bar', // bar, box, modal
			'animation'              => 'slide', // slide, fade, none

			// Тексти (переклади застосовуються в translate_defaults())
			'message'                => 'We use cookies to improve your experience on our website.',
			'accept_text'            => 'Accept All',
			'reject_text'            => 'Reject All',
			'settings_text'          => 'Settings',
			'save_text'              => 'Save Settings',
			'privacy_policy_text'    => 'Privacy Policy',
			'revoke_text'            => 'Manage Cookies',

			// Кнопки
			'show_reject_button'     => true,
			'show_settings_button'   => true,
			'show_revoke_button'     => true,

			// Стилі
			'bar_bg_color'           => '#1e293b',
			'bar_text_color'         => '#f8fafc',
			'bar_opacity'            => 100,
			'btn_accept_bg'          => '#10b981',
			'btn_accept_text'        => '#ffffff',
			'btn_reject_bg'          => '#6b7280',
			'btn_reject_text'        => '#ffffff',
			'btn_settings_bg'        => 'transparent',
			'btn_settings_text'      => '#f8fafc',
			'btn_border_radius'      => 8,

			// Поведінка
			'cookie_expiry'          => 365,
			'cookie_expiry_rejected' => 30,
			'cookie_path'            => '/',
			'cookie_domain'          => '',
			'cookie_secure'          => true,
			'accept_on_scroll'       => false,
			'scroll_offset'          => 100,
			'accept_on_click'        => false,
			'reload_on_change'       => false,
			'hide_effect'            => 'fade',

			// Privacy Policy
			'privacy_policy_link'    => '',
			'privacy_policy_page'    => 0,
			'open_in_new_tab'        => true,

			// Категорії cookies (переклади застосовуються в translate_defaults())
			'enable_categories'      => true,
			'categories'             => [
				'necessary'   => [
					'enabled'     => true,
					'required'    => true,
					'name'        => 'Necessary',
					'description' => 'These cookies are essential for the website to function and cannot be disabled.',
					'icon'        => '🔒',
				],
				'analytics'   => [
					'enabled'     => true,
					'required'    => false,
					'name'        => 'Analytics',
					'description' => 'Help us understand how visitors interact with the website.',
					'icon'        => '📊',
				],
				'marketing'   => [
					'enabled'     => true,
					'required'    => false,
					'name'        => 'Marketing',
					'description' => 'Used to display relevant advertising.',
					'icon'        => '🎯',
				],
				'preferences' => [
					'enabled'     => true,
					'required'    => false,
					'name'        => 'Preferences',
					'description' => 'Allow the website to remember your settings.',
					'icon'        => '⚙️',
				],
			],

			// Блокування скриптів
			'enable_script_blocking' => true,
			'blocked_scripts'        => [],
			'blocked_patterns'       => [
				'analytics'   => [
					'google-analytics.com',
					'googletagmanager.com',
					'analytics.google.com',
					'gtag/js',
					'clarity.ms',
					'hotjar.com',
				],
				'marketing'   => [
					'facebook.net',
					'connect.facebook.net',
					'fbevents.js',
					'doubleclick.net',
					'googlesyndication.com',
					'googleadservices.com',
					'linkedin.com/px',
					'ads.linkedin.com',
					'tiktok.com',
					'snap.licdn.com',
				],
				'preferences' => [
					'intercom.io',
					'crisp.chat',
					'drift.com',
					'livechat.com',
				],
			],

			// Google Consent Mode
			'enable_gcm'             => true,
			'gcm_default_analytics'  => 'denied',
			'gcm_default_ads'        => 'denied',
			'gcm_wait_for_update'    => 500,

			// Geo Detection
			'enable_geo_detection'   => false,
			'geo_api_provider'       => 'ipapi', // ipapi, geojs, cloudflare
			'geo_rules'              => [
				'EU' => 'strict', // GDPR
				'US-CA' => 'ccpa', // CCPA
				'default' => 'notice', // Just notice without blocking
			],

			// Журнал згод
			'enable_consent_logs'    => true,
			'consent_logs_retention' => 365, // днів
			'log_ip_address'         => false,
			'anonymize_ip'           => true,

			// Аналітика
			'enable_analytics'       => true,
			'analytics_retention'    => 90, // днів

			// Сумісність
			'cache_compatibility'    => true,
			'amp_support'            => false,
			'wpml_support'           => true,

			// Bot Detection
			'bot_detection'          => true,

			// Conditional Display
			'user_type'              => 'all', // all, logged_in, guest
			'excluded_roles'         => [], // array of role slugs
			'excluded_page_types'    => [], // array of page types
			'excluded_page_ids'      => '', // comma-separated IDs

			// Advanced Conditional Rules (v1.3.0)
			'enable_conditional_rules' => true,

			// Кастомний CSS/JS
			'custom_css'             => '',
			'custom_js'              => '',

			// Twemoji
			'use_twemoji'            => true,

			// Debug
			'debug_mode'             => false,
		];

		// Категорії cookies для легшого доступу
		$this->cookie_categories = $this->defaults['categories'];
	}

	/**
	 * Завантаження налаштувань
	 *
	 * @return void
	 */
	private function load_options(): void {
		$saved_options = get_option( 'medici_cookie_notice', [] );
		$this->options = wp_parse_args( $saved_options, $this->defaults );

		// Оновлення категорій
		if ( ! empty( $this->options['categories'] ) ) {
			$this->cookie_categories = $this->options['categories'];
		}
	}

	/**
	 * Включення файлів
	 *
	 * @return void
	 */
	private function includes(): void {
		// Ядро плагіну (Loader з WordPress Plugin Boilerplate)
		require_once MCN_PLUGIN_DIR . 'includes/class-loader.php';

		// Компоненти плагіну
		require_once MCN_PLUGIN_DIR . 'includes/class-settings.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-frontend.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-script-blocker.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-consent-logs.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-analytics.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-geo-detection.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-bot-detect.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-shortcodes.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-conditional-display.php';
		require_once MCN_PLUGIN_DIR . 'includes/class-conditional-rules.php';

		// Cache Modules (v1.3.0)
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/interface-cache-module.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/class-wp-rocket.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/class-litespeed.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/class-autoptimize.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/class-w3-total-cache.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/cache/class-wp-super-cache.php';
		require_once MCN_PLUGIN_DIR . 'includes/modules/class-cache-compatibility.php';

		// Rule Engine (v1.3.0)
		require_once MCN_PLUGIN_DIR . 'includes/rules/interface-rule-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/class-rule.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/class-rule-group.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-page-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-user-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-user-role-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-device-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-url-evaluator.php';
		require_once MCN_PLUGIN_DIR . 'includes/rules/evaluators/class-geo-evaluator.php';

		// Admin Components (v1.3.0)
		if ( is_admin() ) {
			require_once MCN_PLUGIN_DIR . 'includes/admin/class-admin-menu.php';
			require_once MCN_PLUGIN_DIR . 'includes/admin/class-dashboard.php';
			require_once MCN_PLUGIN_DIR . 'includes/admin/class-consent-logs-list-table.php';
			require_once MCN_PLUGIN_DIR . 'includes/admin/class-consent-logs-page.php';
		}
	}

	/**
	 * Завантаження перекладів
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'medici-cookie-notice',
			false,
			dirname( MCN_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Застосування перекладів до defaults після завантаження textdomain
	 *
	 * Викликається на init hook з пріоритетом 5 (після load_textdomain з пріоритетом 1).
	 * WordPress 6.7+ вимагає завантаження перекладів на init або пізніше.
	 *
	 * @since 1.1.1
	 * @return void
	 */
	public function translate_defaults(): void {
		// Застосовуємо переклади до текстових полів
		$this->defaults['message']             = __( 'Ми використовуємо файли cookie для покращення вашого досвіду на сайті.', 'medici-cookie-notice' );
		$this->defaults['accept_text']         = __( 'Прийняти всі', 'medici-cookie-notice' );
		$this->defaults['reject_text']         = __( 'Відхилити всі', 'medici-cookie-notice' );
		$this->defaults['settings_text']       = __( 'Налаштування', 'medici-cookie-notice' );
		$this->defaults['save_text']           = __( 'Зберегти налаштування', 'medici-cookie-notice' );
		$this->defaults['privacy_policy_text'] = __( 'Політика конфіденційності', 'medici-cookie-notice' );
		$this->defaults['revoke_text']         = __( 'Керування cookies', 'medici-cookie-notice' );

		// Застосовуємо переклади до категорій
		$this->defaults['categories']['necessary']['name']        = __( 'Необхідні', 'medici-cookie-notice' );
		$this->defaults['categories']['necessary']['description'] = __( 'Ці файли cookie необхідні для роботи сайту і не можуть бути вимкнені.', 'medici-cookie-notice' );

		$this->defaults['categories']['analytics']['name']        = __( 'Аналітика', 'medici-cookie-notice' );
		$this->defaults['categories']['analytics']['description'] = __( 'Допомагають нам зрозуміти, як відвідувачі взаємодіють з сайтом.', 'medici-cookie-notice' );

		$this->defaults['categories']['marketing']['name']        = __( 'Маркетинг', 'medici-cookie-notice' );
		$this->defaults['categories']['marketing']['description'] = __( 'Використовуються для показу релевантної реклами.', 'medici-cookie-notice' );

		$this->defaults['categories']['preferences']['name']        = __( 'Вподобання', 'medici-cookie-notice' );
		$this->defaults['categories']['preferences']['description'] = __( 'Дозволяють сайту запам\'ятовувати ваші налаштування.', 'medici-cookie-notice' );

		// Оновлюємо options якщо вони ще не збережені або використовують дефолтні англійські тексти
		$saved_options = get_option( 'medici_cookie_notice', [] );

		// Якщо options порожні або мають англійські тексти - застосовуємо переклади
		if ( empty( $saved_options ) || ( isset( $saved_options['message'] ) && 'We use cookies' === substr( $saved_options['message'], 0, 15 ) ) ) {
			$this->options = wp_parse_args( $saved_options, $this->defaults );
		}

		// Оновлюємо категорії
		if ( ! empty( $this->options['categories'] ) ) {
			$this->cookie_categories = $this->options['categories'];
		} else {
			$this->cookie_categories = $this->defaults['categories'];
		}
	}

	/**
	 * Ініціалізація плагіну
	 *
	 * @return void
	 */
	public function init(): void {
		// Ініціалізація компонентів
		$this->settings       = new Settings( $this );
		$this->frontend       = new Frontend( $this );
		$this->script_blocker = new Script_Blocker( $this );
		$this->consent_logs   = new Consent_Logs( $this );
		$this->analytics      = new Analytics( $this );
		$this->geo_detection  = new Geo_Detection( $this );
		$this->bot_detect           = new Bot_Detect( $this );
		$this->shortcodes           = new Shortcodes( $this );
		$this->conditional_display  = new Conditional_Display( $this );

		// v1.3.0: Нові компоненти
		$this->conditional_rules  = new Conditional_Rules( $this );
		$this->cache_compatibility = new Modules\Cache_Compatibility( $this );

		// Admin компоненти (v1.3.0)
		if ( is_admin() ) {
			$this->admin_menu = new Admin\Admin_Menu( $this );
			$this->admin_menu->init();
		}

		// Ініціалізація bot detection на after_setup_theme
		add_action( 'after_setup_theme', [ $this->bot_detect, 'init' ] );

		// Ініціалізація conditional display на after_setup_theme
		add_action( 'after_setup_theme', [ $this->conditional_display, 'init' ] );

		// AJAX handlers
		add_action( 'wp_ajax_mcn_save_consent', [ $this, 'ajax_save_consent' ] );
		add_action( 'wp_ajax_nopriv_mcn_save_consent', [ $this, 'ajax_save_consent' ] );
		add_action( 'wp_ajax_mcn_get_consent', [ $this, 'ajax_get_consent' ] );
		add_action( 'wp_ajax_nopriv_mcn_get_consent', [ $this, 'ajax_get_consent' ] );
	}

	/**
	 * Ініціалізація адмінки
	 *
	 * @return void
	 */
	public function admin_init(): void {
		// Перевірка версії та оновлення
		$current_version = get_option( 'mcn_version', '0.0.0' );
		if ( version_compare( $current_version, MCN_VERSION, '<' ) ) {
			$this->upgrade( $current_version );
			update_option( 'mcn_version', MCN_VERSION );
		}
	}

	/**
	 * Активація плагіну
	 *
	 * @return void
	 */
	public function activate(): void {
		// Створення таблиць БД
		$this->create_tables();

		// Збереження початкових налаштувань
		if ( false === get_option( 'medici_cookie_notice' ) ) {
			update_option( 'medici_cookie_notice', $this->defaults );
		}

		// Збереження версії
		update_option( 'mcn_version', MCN_VERSION );

		// Очистка кешу
		$this->clear_cache();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Деактивація плагіну
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// Очистка scheduled events
		wp_clear_scheduled_hook( 'mcn_cleanup_logs' );
		wp_clear_scheduled_hook( 'mcn_cleanup_analytics' );

		// Очистка кешу
		$this->clear_cache();

		flush_rewrite_rules();
	}

	/**
	 * Створення таблиць бази даних
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Таблиця логів згоди
		$table_consent_logs = $wpdb->prefix . 'mcn_consent_logs';
		$sql_consent_logs   = "CREATE TABLE IF NOT EXISTS {$table_consent_logs} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_id varchar(64) NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			consent_categories text NOT NULL,
			consent_status varchar(20) NOT NULL,
			geo_country varchar(2) DEFAULT NULL,
			geo_region varchar(10) DEFAULT NULL,
			page_url text DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY consent_id (consent_id),
			KEY user_id (user_id),
			KEY created_at (created_at),
			KEY geo_country (geo_country)
		) {$charset_collate};";

		// Таблиця аналітики
		$table_analytics = $wpdb->prefix . 'mcn_analytics';
		$sql_analytics   = "CREATE TABLE IF NOT EXISTS {$table_analytics} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			date_recorded date NOT NULL,
			total_visitors int(11) NOT NULL DEFAULT 0,
			accepted_all int(11) NOT NULL DEFAULT 0,
			rejected_all int(11) NOT NULL DEFAULT 0,
			customized int(11) NOT NULL DEFAULT 0,
			category_necessary int(11) NOT NULL DEFAULT 0,
			category_analytics int(11) NOT NULL DEFAULT 0,
			category_marketing int(11) NOT NULL DEFAULT 0,
			category_preferences int(11) NOT NULL DEFAULT 0,
			geo_eu int(11) NOT NULL DEFAULT 0,
			geo_us int(11) NOT NULL DEFAULT 0,
			geo_other int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY date_recorded (date_recorded)
		) {$charset_collate};";

		// Таблиця груп правил (v1.3.0)
		$table_rule_groups = $wpdb->prefix . 'mcn_rule_groups';
		$sql_rule_groups   = "CREATE TABLE IF NOT EXISTS {$table_rule_groups} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			operator enum('AND','OR') NOT NULL DEFAULT 'AND',
			action enum('show','hide') NOT NULL DEFAULT 'show',
			priority int(11) NOT NULL DEFAULT 10,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY is_active (is_active),
			KEY priority (priority)
		) {$charset_collate};";

		// Таблиця правил (v1.3.0)
		$table_rules = $wpdb->prefix . 'mcn_rules';
		$sql_rules   = "CREATE TABLE IF NOT EXISTS {$table_rules} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			rule_type varchar(50) NOT NULL,
			operator varchar(20) NOT NULL,
			value text NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY rule_type (rule_type),
			CONSTRAINT fk_rule_group FOREIGN KEY (group_id) REFERENCES {$table_rule_groups}(id) ON DELETE CASCADE
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_consent_logs );
		dbDelta( $sql_analytics );
		dbDelta( $sql_rule_groups );
		dbDelta( $sql_rules );
	}

	/**
	 * Оновлення плагіну
	 *
	 * @param string $old_version Стара версія
	 * @return void
	 */
	private function upgrade( string $old_version ): void {
		// Міграція з старих версій
		if ( version_compare( $old_version, '1.0.0', '<' ) ) {
			$this->create_tables();
		}

		// v1.3.0: Створення таблиць правил та нових опцій
		if ( version_compare( $old_version, '1.3.0', '<' ) ) {
			$this->create_tables();

			// Додаємо нові опції за замовчуванням
			$options = get_option( 'medici_cookie_notice', [] );
			if ( ! isset( $options['enable_conditional_rules'] ) ) {
				$options['enable_conditional_rules'] = true;
				update_option( 'medici_cookie_notice', $options );
			}
		}
	}

	/**
	 * Очистка кешу
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		// LiteSpeed Cache
		if ( class_exists( 'LiteSpeed_Cache_API' ) ) {
			\LiteSpeed_Cache_API::purge_all();
		}

		// Autoptimize
		if ( class_exists( 'autoptimizeCache' ) ) {
			\autoptimizeCache::clearall();
		}

		// WordPress Object Cache
		wp_cache_flush();
	}

	/**
	 * AJAX: Збереження згоди
	 *
	 * @return void
	 */
	public function ajax_save_consent(): void {
		// Перевірка nonce
		if ( ! check_ajax_referer( 'mcn_consent_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Помилка безпеки.', 'medici-cookie-notice' ) ] );
		}

		$consent_id = isset( $_POST['consent_id'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_id'] ) ) : '';
		$categories = isset( $_POST['categories'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['categories'] ) ) : [];
		$status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'custom';

		if ( empty( $consent_id ) ) {
			$consent_id = $this->generate_consent_id();
		}

		// Логування згоди
		if ( $this->options['enable_consent_logs'] && null !== $this->consent_logs ) {
			$this->consent_logs->log_consent( $consent_id, $categories, $status );
		}

		// Оновлення аналітики
		if ( $this->options['enable_analytics'] && null !== $this->analytics ) {
			$this->analytics->record_consent( $categories, $status );
		}

		wp_send_json_success( [
			'consent_id' => $consent_id,
			'categories' => $categories,
			'status'     => $status,
			'message'    => __( 'Налаштування збережено.', 'medici-cookie-notice' ),
		] );
	}

	/**
	 * AJAX: Отримання статусу згоди
	 *
	 * @return void
	 */
	public function ajax_get_consent(): void {
		// Перевірка nonce
		if ( ! check_ajax_referer( 'mcn_consent_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Помилка безпеки.', 'medici-cookie-notice' ) ] );
		}

		$consent_id = isset( $_GET['consent_id'] ) ? sanitize_text_field( wp_unslash( $_GET['consent_id'] ) ) : '';

		if ( empty( $consent_id ) ) {
			wp_send_json_error( [ 'message' => __( 'ID згоди не вказано.', 'medici-cookie-notice' ) ] );
		}

		$consent = null !== $this->consent_logs ? $this->consent_logs->get_consent( $consent_id ) : null;

		if ( $consent ) {
			wp_send_json_success( $consent );
		} else {
			wp_send_json_error( [ 'message' => __( 'Згоду не знайдено.', 'medici-cookie-notice' ) ] );
		}
	}

	/**
	 * Реєстрація REST API маршрутів
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'mcn/v1',
			'/consent',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_save_consent' ],
					'permission_callback' => '__return_true', // Публічний endpoint
					'args'                => [
						'consent_id' => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'categories' => [
							'type'              => 'object',
							'default'           => [],
						],
						'status'     => [
							'type'              => 'string',
							'default'           => 'custom',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_consent' ],
					'permission_callback' => '__return_true',
					'args'                => [
						'consent_id' => [
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * REST API: Збереження згоди
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_save_consent( \WP_REST_Request $request ): \WP_REST_Response {
		$consent_id = $request->get_param( 'consent_id' );
		$categories = $request->get_param( 'categories' );
		$status     = $request->get_param( 'status' );

		// Санітизація категорій
		if ( is_array( $categories ) ) {
			$categories = array_map( 'sanitize_text_field', $categories );
		} else {
			$categories = [];
		}

		if ( empty( $consent_id ) ) {
			$consent_id = $this->generate_consent_id();
		}

		// Логування згоди
		if ( $this->options['enable_consent_logs'] && null !== $this->consent_logs ) {
			$this->consent_logs->log_consent( $consent_id, $categories, $status );
		}

		// Оновлення аналітики
		if ( $this->options['enable_analytics'] && null !== $this->analytics ) {
			$this->analytics->record_consent( $categories, $status );
		}

		return new \WP_REST_Response(
			[
				'success'    => true,
				'consent_id' => $consent_id,
				'categories' => $categories,
				'status'     => $status,
				'message'    => __( 'Налаштування збережено.', 'medici-cookie-notice' ),
			],
			200
		);
	}

	/**
	 * REST API: Отримання статусу згоди
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_get_consent( \WP_REST_Request $request ): \WP_REST_Response {
		$consent_id = $request->get_param( 'consent_id' );

		if ( empty( $consent_id ) ) {
			return new \WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'ID згоди не вказано.', 'medici-cookie-notice' ),
				],
				400
			);
		}

		$consent = null !== $this->consent_logs ? $this->consent_logs->get_consent( $consent_id ) : null;

		if ( $consent ) {
			return new \WP_REST_Response(
				[
					'success' => true,
					'data'    => $consent,
				],
				200
			);
		}

		return new \WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Згоду не знайдено.', 'medici-cookie-notice' ),
			],
			404
		);
	}

	/**
	 * Генерація унікального ID згоди
	 *
	 * @return string
	 */
	public function generate_consent_id(): string {
		return wp_generate_uuid4();
	}

	/**
	 * Отримання опції
	 *
	 * @param string $key Ключ опції
	 * @param mixed  $default Значення за замовчуванням
	 * @return mixed
	 */
	public function get_option( string $key, mixed $default = null ): mixed {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}
		if ( null !== $default ) {
			return $default;
		}
		return $this->defaults[ $key ] ?? null;
	}

	/**
	 * Plugin action links
	 *
	 * @param array<string, string> $links Посилання
	 * @return array<string, string>
	 */
	public function plugin_action_links( array $links ): array {
		$plugin_links = [
			'<a href="' . admin_url( 'options-general.php?page=medici-cookie-notice' ) . '">' . __( 'Налаштування', 'medici-cookie-notice' ) . '</a>',
		];
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Отримати Loader для реєстрації hooks з інших компонентів
	 *
	 * @return Loader|null
	 */
	public function get_loader(): ?Loader {
		return $this->loader;
	}

	/**
	 * Заборона клонування
	 */
	private function __clone() {}

	/**
	 * Заборона десеріалізації
	 *
	 * @throws \Exception
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}

/**
 * Функція для отримання instance плагіну
 *
 * @return Cookie_Notice
 */
function mcn(): Cookie_Notice {
	return Cookie_Notice::get_instance();
}

// Ініціалізація плагіну
mcn();

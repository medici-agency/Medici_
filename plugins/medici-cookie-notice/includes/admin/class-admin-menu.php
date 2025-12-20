<?php
/**
 * Admin Menu Registration
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Admin;

use Medici\CookieNotice\Cookie_Notice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu Class
 *
 * Registers admin menu pages and handles routing.
 */
class Admin_Menu {

	/**
	 * Plugin instance
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Dashboard instance
	 *
	 * @var Dashboard|null
	 */
	private ?Dashboard $dashboard = null;

	/**
	 * Consent logs page instance
	 *
	 * @var Consent_Logs_Page|null
	 */
	private ?Consent_Logs_Page $consent_logs_page = null;

	/**
	 * Constructor
	 *
	 * @param Cookie_Notice $plugin Plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize admin menu
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_screen_options' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Register admin menu pages
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Main menu
		add_menu_page(
			__( 'Cookie Notice', 'medici-cookie-notice' ),
			__( 'Cookie Notice', 'medici-cookie-notice' ),
			'manage_options',
			'mcn-dashboard',
			[ $this, 'render_dashboard_page' ],
			'dashicons-shield',
			81
		);

		// Dashboard submenu
		add_submenu_page(
			'mcn-dashboard',
			__( 'Панель управління', 'medici-cookie-notice' ),
			__( 'Панель управління', 'medici-cookie-notice' ),
			'manage_options',
			'mcn-dashboard',
			[ $this, 'render_dashboard_page' ]
		);

		// Settings submenu
		add_submenu_page(
			'mcn-dashboard',
			__( 'Налаштування', 'medici-cookie-notice' ),
			__( 'Налаштування', 'medici-cookie-notice' ),
			'manage_options',
			'medici-cookie-notice',
			[ $this->plugin->settings, 'render_settings_page' ]
		);

		// Consent Logs submenu
		add_submenu_page(
			'mcn-dashboard',
			__( 'Журнал згод', 'medici-cookie-notice' ),
			__( 'Журнал згод', 'medici-cookie-notice' ),
			'manage_options',
			'mcn-consent-logs',
			[ $this, 'render_consent_logs_page' ]
		);

		// Conditional Rules submenu (if enabled)
		if ( $this->plugin->get_option( 'enable_conditional_rules' ) ) {
			add_submenu_page(
				'mcn-dashboard',
				__( 'Умовні правила', 'medici-cookie-notice' ),
				__( 'Умовні правила', 'medici-cookie-notice' ),
				'manage_options',
				'mcn-rules',
				[ $this, 'render_rules_page' ]
			);
		}
	}

	/**
	 * Register screen options
	 *
	 * @return void
	 */
	public function register_screen_options(): void {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		if ( 'cookie-notice_page_mcn-consent-logs' === $screen->id ) {
			add_screen_option(
				'per_page',
				[
					'label'   => __( 'Записів на сторінку', 'medici-cookie-notice' ),
					'default' => 20,
					'option'  => 'mcn_logs_per_page',
				]
			);
		}
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Only on our pages
		if ( ! str_contains( $hook, 'mcn-' ) && ! str_contains( $hook, 'medici-cookie-notice' ) ) {
			return;
		}

		// Common admin styles
		wp_enqueue_style(
			'mcn-admin',
			MCN_PLUGIN_URL . 'assets/css/admin.css',
			[],
			MCN_VERSION
		);

		// Dashboard specific
		if ( str_contains( $hook, 'mcn-dashboard' ) ) {
			// Chart.js
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
				[],
				'4.4.1',
				true
			);

			// Dashboard JS
			wp_enqueue_script(
				'mcn-admin-dashboard',
				MCN_PLUGIN_URL . 'assets/js/admin-dashboard.js',
				[ 'jquery', 'chart-js' ],
				MCN_VERSION,
				true
			);

			// Localize dashboard data
			wp_localize_script(
				'mcn-admin-dashboard',
				'mcnDashboardData',
				$this->get_dashboard_data()
			);
		}

		// Consent logs specific
		if ( str_contains( $hook, 'mcn-consent-logs' ) ) {
			wp_enqueue_script(
				'mcn-admin-logs',
				MCN_PLUGIN_URL . 'assets/js/admin-consent-logs.js',
				[ 'jquery' ],
				MCN_VERSION,
				true
			);

			wp_localize_script(
				'mcn-admin-logs',
				'mcnLogsData',
				[
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'mcn_admin_nonce' ),
					'i18n'     => [
						'confirm_delete' => __( 'Ви впевнені що хочете видалити цей запис?', 'medici-cookie-notice' ),
						'loading'        => __( 'Завантаження...', 'medici-cookie-notice' ),
					],
				]
			);
		}

		// Rules builder specific
		if ( str_contains( $hook, 'mcn-rules' ) ) {
			wp_enqueue_script(
				'mcn-admin-rules',
				MCN_PLUGIN_URL . 'assets/js/admin-rules-builder.js',
				[ 'jquery', 'jquery-ui-sortable' ],
				MCN_VERSION,
				true
			);

			wp_localize_script(
				'mcn-admin-rules',
				'mcnRulesData',
				[
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'mcn_rules_nonce' ),
					'evaluators' => $this->get_rule_evaluators(),
				]
			);
		}
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard_page(): void {
		if ( null === $this->dashboard ) {
			$this->dashboard = new Dashboard( $this->plugin );
		}

		$this->dashboard->render();
	}

	/**
	 * Render consent logs page
	 *
	 * @return void
	 */
	public function render_consent_logs_page(): void {
		if ( null === $this->consent_logs_page ) {
			$this->consent_logs_page = new Consent_Logs_Page( $this->plugin );
		}

		$this->consent_logs_page->render();
	}

	/**
	 * Render rules page
	 *
	 * @return void
	 */
	public function render_rules_page(): void {
		require_once MCN_PLUGIN_DIR . 'includes/admin/views/rules-builder.php';
	}

	/**
	 * Get dashboard data for JS
	 *
	 * @return array<string, mixed>
	 */
	private function get_dashboard_data(): array {
		$analytics = $this->plugin->analytics;

		if ( null === $analytics ) {
			return [ 'error' => true ];
		}

		$daily_stats = $analytics->get_daily_stats( 30 );
		$stats       = $analytics->get_stats( 30 );
		$rates       = $analytics->get_acceptance_rates( 30 );
		$cat_rates   = $analytics->get_category_rates( 30 );
		$geo_stats   = $analytics->get_geo_stats( 30 );
		$comparison  = $analytics->get_period_comparison( 30 );

		return [
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'mcn_dashboard_nonce' ),
			'daily_stats' => $daily_stats,
			'summary'     => $stats,
			'rates'       => $rates,
			'cat_rates'   => $cat_rates,
			'geo_stats'   => $geo_stats,
			'comparison'  => $comparison,
			'i18n'        => [
				'total_visitors'  => __( 'Всього відвідувачів', 'medici-cookie-notice' ),
				'accepted'        => __( 'Прийнято все', 'medici-cookie-notice' ),
				'rejected'        => __( 'Відхилено все', 'medici-cookie-notice' ),
				'customized'      => __( 'Вибіркова згода', 'medici-cookie-notice' ),
				'consent_rate'    => __( 'Рівень згоди', 'medici-cookie-notice' ),
				'vs_previous'     => __( 'vs попередній період', 'medici-cookie-notice' ),
				'analytics'       => __( 'Аналітика', 'medici-cookie-notice' ),
				'marketing'       => __( 'Маркетинг', 'medici-cookie-notice' ),
				'preferences'     => __( 'Вподобання', 'medici-cookie-notice' ),
				'necessary'       => __( 'Необхідні', 'medici-cookie-notice' ),
				'eu'              => __( 'ЄС (GDPR)', 'medici-cookie-notice' ),
				'us'              => __( 'США', 'medici-cookie-notice' ),
				'other'           => __( 'Інші', 'medici-cookie-notice' ),
			],
		];
	}

	/**
	 * Get rule evaluators for JS
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_rule_evaluators(): array {
		return [
			'page_type'  => [
				'label'     => __( 'Тип сторінки', 'medici-cookie-notice' ),
				'operators' => [
					'is'     => __( 'є', 'medici-cookie-notice' ),
					'is_not' => __( 'не є', 'medici-cookie-notice' ),
				],
				'options'   => [
					'front_page' => __( 'Головна сторінка', 'medici-cookie-notice' ),
					'home'       => __( 'Сторінка блогу', 'medici-cookie-notice' ),
					'single'     => __( 'Окремий запис', 'medici-cookie-notice' ),
					'page'       => __( 'Сторінка', 'medici-cookie-notice' ),
					'archive'    => __( 'Архів', 'medici-cookie-notice' ),
					'search'     => __( 'Результати пошуку', 'medici-cookie-notice' ),
					'404'        => __( '404 сторінка', 'medici-cookie-notice' ),
				],
			],
			'user_type'  => [
				'label'     => __( 'Тип користувача', 'medici-cookie-notice' ),
				'operators' => [
					'is'     => __( 'є', 'medici-cookie-notice' ),
					'is_not' => __( 'не є', 'medici-cookie-notice' ),
				],
				'options'   => [
					'logged_in' => __( 'Авторизований', 'medici-cookie-notice' ),
					'guest'     => __( 'Гість', 'medici-cookie-notice' ),
				],
			],
			'user_role'  => [
				'label'     => __( 'Роль користувача', 'medici-cookie-notice' ),
				'operators' => [
					'is'     => __( 'є', 'medici-cookie-notice' ),
					'is_not' => __( 'не є', 'medici-cookie-notice' ),
				],
				'options'   => $this->get_user_roles(),
			],
			'geo'        => [
				'label'     => __( 'Країна', 'medici-cookie-notice' ),
				'operators' => [
					'is'     => __( 'є', 'medici-cookie-notice' ),
					'is_not' => __( 'не є', 'medici-cookie-notice' ),
					'in'     => __( 'у списку', 'medici-cookie-notice' ),
				],
				'type'      => 'text',
			],
			'device'     => [
				'label'     => __( 'Пристрій', 'medici-cookie-notice' ),
				'operators' => [
					'is'     => __( 'є', 'medici-cookie-notice' ),
					'is_not' => __( 'не є', 'medici-cookie-notice' ),
				],
				'options'   => [
					'desktop' => __( 'Комп\'ютер', 'medici-cookie-notice' ),
					'mobile'  => __( 'Мобільний', 'medici-cookie-notice' ),
					'tablet'  => __( 'Планшет', 'medici-cookie-notice' ),
				],
			],
			'url'        => [
				'label'     => __( 'URL', 'medici-cookie-notice' ),
				'operators' => [
					'contains'     => __( 'містить', 'medici-cookie-notice' ),
					'not_contains' => __( 'не містить', 'medici-cookie-notice' ),
					'starts_with'  => __( 'починається з', 'medici-cookie-notice' ),
					'ends_with'    => __( 'закінчується на', 'medici-cookie-notice' ),
					'regex'        => __( 'відповідає regex', 'medici-cookie-notice' ),
				],
				'type'      => 'text',
			],
			'referrer'   => [
				'label'     => __( 'Referrer', 'medici-cookie-notice' ),
				'operators' => [
					'contains'     => __( 'містить', 'medici-cookie-notice' ),
					'not_contains' => __( 'не містить', 'medici-cookie-notice' ),
					'is_empty'     => __( 'порожній', 'medici-cookie-notice' ),
					'is_not_empty' => __( 'не порожній', 'medici-cookie-notice' ),
				],
				'type'      => 'text',
			],
			'date_range' => [
				'label'     => __( 'Діапазон дат', 'medici-cookie-notice' ),
				'operators' => [
					'between'    => __( 'між', 'medici-cookie-notice' ),
					'before'     => __( 'до', 'medici-cookie-notice' ),
					'after'      => __( 'після', 'medici-cookie-notice' ),
				],
				'type'      => 'date',
			],
		];
	}

	/**
	 * Get user roles for rule evaluator
	 *
	 * @return array<string, string>
	 */
	private function get_user_roles(): array {
		$roles = wp_roles()->get_names();
		return array_map( 'translate_user_role', $roles );
	}
}

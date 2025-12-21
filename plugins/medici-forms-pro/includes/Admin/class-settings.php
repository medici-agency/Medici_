<?php
/**
 * Settings Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Admin;

use MediciForms\Plugin;

/**
 * Settings Class - Admin settings page.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'medici_forms_settings';

	/**
	 * Settings tabs.
	 *
	 * @var array<string, string>
	 */
	private array $tabs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->tabs = array(
			'general'      => __( 'Загальні', 'medici-forms-pro' ),
			'email'        => __( 'Email', 'medici-forms-pro' ),
			'antispam'     => __( 'Захист від спаму', 'medici-forms-pro' ),
			'integrations' => __( 'Інтеграції', 'medici-forms-pro' ),
			'styling'      => __( 'Стилізація', 'medici-forms-pro' ),
			'advanced'     => __( 'Розширені', 'medici-forms-pro' ),
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings(): void {
		register_setting(
			'medici_forms_settings',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		// General settings.
		$this->add_general_settings();

		// Email settings.
		$this->add_email_settings();

		// Anti-spam settings.
		$this->add_antispam_settings();

		// Integrations settings.
		$this->add_integrations_settings();

		// Styling settings.
		$this->add_styling_settings();

		// Advanced settings.
		$this->add_advanced_settings();
	}

	/**
	 * Add general settings.
	 *
	 * @since 1.0.0
	 */
	private function add_general_settings(): void {
		add_settings_section(
			'medici_forms_general',
			__( 'Загальні налаштування', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Основні налаштування плагіна форм.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_general'
		);

		add_settings_field(
			'enable_ajax',
			__( 'AJAX відправка', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_general',
			'medici_forms_general',
			array(
				'id'          => 'enable_ajax',
				'description' => __( 'Відправляти форми без перезавантаження сторінки.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'load_styles',
			__( 'Завантажувати стилі', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_general',
			'medici_forms_general',
			array(
				'id'          => 'load_styles',
				'description' => __( 'Завантажувати CSS стилі плагіна на фронтенді.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'load_scripts',
			__( 'Завантажувати скрипти', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_general',
			'medici_forms_general',
			array(
				'id'          => 'load_scripts',
				'description' => __( 'Завантажувати JavaScript плагіна на фронтенді.', 'medici-forms-pro' ),
			)
		);
	}

	/**
	 * Add email settings.
	 *
	 * @since 1.0.0
	 */
	private function add_email_settings(): void {
		add_settings_section(
			'medici_forms_email',
			__( 'Email налаштування', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Налаштування email сповіщень для всіх форм.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_email'
		);

		add_settings_field(
			'admin_email',
			__( 'Email адміністратора', 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_email',
			'medici_forms_email',
			array(
				'id'          => 'admin_email',
				'type'        => 'email',
				'description' => __( 'Email за замовчуванням для отримання сповіщень.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'email_from_name',
			__( "Ім'я відправника", 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_email',
			'medici_forms_email',
			array(
				'id'          => 'email_from_name',
				'description' => __( "Ім'я, яке буде показане в полі \"Від кого\".", 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'email_from_address',
			__( 'Email відправника', 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_email',
			'medici_forms_email',
			array(
				'id'          => 'email_from_address',
				'type'        => 'email',
				'description' => __( 'Email адреса відправника.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'email_template',
			__( 'Шаблон листа', 'medici-forms-pro' ),
			array( $this, 'render_select_field' ),
			'medici_forms_email',
			'medici_forms_email',
			array(
				'id'      => 'email_template',
				'options' => array(
					'default' => __( 'Стандартний', 'medici-forms-pro' ),
					'modern'  => __( 'Сучасний', 'medici-forms-pro' ),
					'minimal' => __( 'Мінімалістичний', 'medici-forms-pro' ),
					'plain'   => __( 'Простий текст', 'medici-forms-pro' ),
				),
			)
		);
	}

	/**
	 * Add anti-spam settings.
	 *
	 * @since 1.0.0
	 */
	private function add_antispam_settings(): void {
		add_settings_section(
			'medici_forms_antispam',
			__( 'Захист від спаму', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Налаштування захисту форм від спаму.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_antispam'
		);

		add_settings_field(
			'enable_honeypot',
			__( 'Honeypot захист', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'enable_honeypot',
				'description' => __( 'Додати приховане поле для виявлення ботів.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'enable_time_check',
			__( 'Перевірка часу', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'enable_time_check',
				'description' => __( 'Перевіряти мінімальний час заповнення форми.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'min_submission_time',
			__( 'Мінімальний час (сек)', 'medici-forms-pro' ),
			array( $this, 'render_number_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'min_submission_time',
				'min'         => 1,
				'max'         => 60,
				'description' => __( 'Мінімальний час у секундах для заповнення форми.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'enable_recaptcha',
			__( 'Google reCAPTCHA', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'enable_recaptcha',
				'description' => __( 'Увімкнути Google reCAPTCHA для форм.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'recaptcha_version',
			__( 'Версія reCAPTCHA', 'medici-forms-pro' ),
			array( $this, 'render_select_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'      => 'recaptcha_version',
				'options' => array(
					'v2_checkbox'  => __( 'v2 Checkbox', 'medici-forms-pro' ),
					'v2_invisible' => __( 'v2 Invisible', 'medici-forms-pro' ),
					'v3'           => __( 'v3 (рекомендовано)', 'medici-forms-pro' ),
				),
			)
		);

		add_settings_field(
			'recaptcha_site_key',
			__( 'Site Key', 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'recaptcha_site_key',
				'description' => __( 'Ключ сайту з консолі Google reCAPTCHA.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'recaptcha_secret_key',
			__( 'Secret Key', 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'recaptcha_secret_key',
				'type'        => 'password',
				'description' => __( 'Секретний ключ з консолі Google reCAPTCHA.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'recaptcha_threshold',
			__( 'Поріг v3', 'medici-forms-pro' ),
			array( $this, 'render_number_field' ),
			'medici_forms_antispam',
			'medici_forms_antispam',
			array(
				'id'          => 'recaptcha_threshold',
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
				'description' => __( 'Мінімальний score для v3 (0.0 - 1.0, рекомендовано 0.5).', 'medici-forms-pro' ),
			)
		);
	}

	/**
	 * Add integrations settings.
	 *
	 * @since 1.0.0
	 */
	private function add_integrations_settings(): void {
		add_settings_section(
			'medici_forms_integrations',
			__( 'Інтеграції', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Налаштування інтеграцій з зовнішніми сервісами.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_integrations'
		);

		add_settings_field(
			'webhook_enabled',
			__( 'Webhook', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_integrations',
			'medici_forms_integrations',
			array(
				'id'          => 'webhook_enabled',
				'description' => __( 'Надсилати дані форми на зовнішній URL.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'webhook_url',
			__( 'Webhook URL', 'medici-forms-pro' ),
			array( $this, 'render_text_field' ),
			'medici_forms_integrations',
			'medici_forms_integrations',
			array(
				'id'          => 'webhook_url',
				'type'        => 'url',
				'description' => __( 'URL для надсилання даних форми (Make, Zapier, тощо).', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'webhook_method',
			__( 'HTTP метод', 'medici-forms-pro' ),
			array( $this, 'render_select_field' ),
			'medici_forms_integrations',
			'medici_forms_integrations',
			array(
				'id'      => 'webhook_method',
				'options' => array(
					'POST' => 'POST',
					'PUT'  => 'PUT',
				),
			)
		);

		add_settings_field(
			'webhook_headers',
			__( 'Заголовки', 'medici-forms-pro' ),
			array( $this, 'render_textarea_field' ),
			'medici_forms_integrations',
			'medici_forms_integrations',
			array(
				'id'          => 'webhook_headers',
				'description' => __( 'Додаткові HTTP заголовки (один на рядок: Header-Name: value).', 'medici-forms-pro' ),
				'rows'        => 3,
			)
		);
	}

	/**
	 * Add styling settings.
	 *
	 * @since 1.0.0
	 */
	private function add_styling_settings(): void {
		add_settings_section(
			'medici_forms_styling',
			__( 'Стилізація', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Налаштування зовнішнього вигляду форм.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_styling'
		);

		add_settings_field(
			'form_style',
			__( 'Стиль форми', 'medici-forms-pro' ),
			array( $this, 'render_select_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'      => 'form_style',
				'options' => array(
					'modern'  => __( 'Сучасний', 'medici-forms-pro' ),
					'classic' => __( 'Класичний', 'medici-forms-pro' ),
					'minimal' => __( 'Мінімалістичний', 'medici-forms-pro' ),
					'none'    => __( 'Без стилів', 'medici-forms-pro' ),
				),
			)
		);

		add_settings_field(
			'primary_color',
			__( 'Основний колір', 'medici-forms-pro' ),
			array( $this, 'render_color_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'          => 'primary_color',
				'description' => __( 'Колір кнопок та акцентів.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'success_color',
			__( 'Колір успіху', 'medici-forms-pro' ),
			array( $this, 'render_color_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'          => 'success_color',
				'description' => __( 'Колір повідомлень про успіх.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'error_color',
			__( 'Колір помилки', 'medici-forms-pro' ),
			array( $this, 'render_color_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'          => 'error_color',
				'description' => __( 'Колір повідомлень про помилку.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'border_radius',
			__( 'Заокруглення (px)', 'medici-forms-pro' ),
			array( $this, 'render_number_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'          => 'border_radius',
				'min'         => 0,
				'max'         => 50,
				'description' => __( 'Заокруглення кутів полів форми.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'enable_autogrow_textarea',
			__( 'Автозбільшення textarea', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_styling',
			'medici_forms_styling',
			array(
				'id'          => 'enable_autogrow_textarea',
				'description' => __( 'Textarea автоматично збільшується при введенні тексту.', 'medici-forms-pro' ),
			)
		);
	}

	/**
	 * Add advanced settings.
	 *
	 * @since 1.0.0
	 */
	private function add_advanced_settings(): void {
		add_settings_section(
			'medici_forms_advanced',
			__( 'Розширені налаштування', 'medici-forms-pro' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Додаткові налаштування плагіна.', 'medici-forms-pro' ) . '</p>';
			},
			'medici_forms_advanced'
		);

		add_settings_field(
			'log_entries',
			__( 'Зберігати заявки', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_advanced',
			'medici_forms_advanced',
			array(
				'id'          => 'log_entries',
				'description' => __( 'Зберігати всі відправлені заявки в базі даних.', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'entry_retention_days',
			__( 'Зберігати заявки (днів)', 'medici-forms-pro' ),
			array( $this, 'render_number_field' ),
			'medici_forms_advanced',
			'medici_forms_advanced',
			array(
				'id'          => 'entry_retention_days',
				'min'         => 0,
				'max'         => 3650,
				'description' => __( 'Кількість днів зберігання заявок (0 = безстроково).', 'medici-forms-pro' ),
			)
		);

		add_settings_field(
			'delete_data_on_uninstall',
			__( 'Видаляти дані', 'medici-forms-pro' ),
			array( $this, 'render_checkbox_field' ),
			'medici_forms_advanced',
			'medici_forms_advanced',
			array(
				'id'          => 'delete_data_on_uninstall',
				'description' => __( 'Видалити всі дані плагіна при деінсталяції.', 'medici-forms-pro' ),
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		?>
		<div class="wrap medici-forms-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $tab_id => $tab_name ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $tab_id ) ); ?>"
					   class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'medici_forms_settings' );
				// Hidden field to track current tab for proper checkbox handling.
				?>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[_current_tab]" value="<?php echo esc_attr( $current_tab ); ?>">
				<?php

				switch ( $current_tab ) {
					case 'email':
						do_settings_sections( 'medici_forms_email' );
						break;
					case 'antispam':
						do_settings_sections( 'medici_forms_antispam' );
						break;
					case 'integrations':
						do_settings_sections( 'medici_forms_integrations' );
						break;
					case 'styling':
						do_settings_sections( 'medici_forms_styling' );
						break;
					case 'advanced':
						do_settings_sections( 'medici_forms_advanced' );
						break;
					default:
						do_settings_sections( 'medici_forms_general' );
						break;
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render checkbox field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_checkbox_field( array $args ): void {
		$value = Plugin::get_option( $args['id'], false );
		?>
		<label>
			<input type="checkbox"
				   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>"
				   value="1"
				<?php checked( $value, true ); ?>>
			<?php
			if ( ! empty( $args['description'] ) ) {
				echo '<span class="description">' . esc_html( $args['description'] ) . '</span>';
			}
			?>
		</label>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_text_field( array $args ): void {
		$value = Plugin::get_option( $args['id'], '' );
		$type  = $args['type'] ?? 'text';
		?>
		<input type="<?php echo esc_attr( $type ); ?>"
			   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   class="regular-text">
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render number field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_number_field( array $args ): void {
		$value = Plugin::get_option( $args['id'], 0 );
		$min   = $args['min'] ?? 0;
		$max   = $args['max'] ?? 9999;
		$step  = $args['step'] ?? 1;
		?>
		<input type="number"
			   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   min="<?php echo esc_attr( (string) $min ); ?>"
			   max="<?php echo esc_attr( (string) $max ); ?>"
			   step="<?php echo esc_attr( (string) $step ); ?>"
			   class="small-text">
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render select field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_select_field( array $args ): void {
		$value   = Plugin::get_option( $args['id'], '' );
		$options = $args['options'] ?? array();
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>">
			<?php foreach ( $options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( (string) $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render textarea field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_textarea_field( array $args ): void {
		$value = Plugin::get_option( $args['id'], '' );
		$rows  = $args['rows'] ?? 5;

		// Convert array to string for headers.
		if ( is_array( $value ) ) {
			$lines = array();
			foreach ( $value as $key => $val ) {
				$lines[] = $key . ': ' . $val;
			}
			$value = implode( "\n", $lines );
		}
		?>
		<textarea name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>"
				  rows="<?php echo esc_attr( (string) $rows ); ?>"
				  class="large-text"><?php echo esc_textarea( (string) $value ); ?></textarea>
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render color field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Field arguments.
	 */
	public function render_color_field( array $args ): void {
		$value = Plugin::get_option( $args['id'], '#2563eb' );
		?>
		<input type="text"
			   name="<?php echo esc_attr( self::OPTION_NAME . '[' . $args['id'] . ']' ); ?>"
			   value="<?php echo esc_attr( (string) $value ); ?>"
			   class="medici-color-picker"
			   data-default-color="<?php echo esc_attr( (string) $value ); ?>">
		<?php
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed>|null $input Input data (can be null on first save).
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( ?array $input ): array {
		// Handle null input (can happen on first save or when options are cleared).
		if ( null === $input ) {
			return get_option( self::OPTION_NAME, array() );
		}

		// Get existing options to merge with.
		$existing = get_option( self::OPTION_NAME, array() );

		// Start with existing values to preserve other tabs' settings.
		$sanitized = $existing;

		// Get current tab to determine which checkboxes to update.
		$current_tab = $input['_current_tab'] ?? 'general';

		// Define checkboxes per tab.
		$checkboxes_by_tab = array(
			'general'      => array( 'enable_ajax', 'load_styles', 'load_scripts' ),
			'antispam'     => array( 'enable_honeypot', 'enable_time_check', 'enable_recaptcha' ),
			'integrations' => array( 'webhook_enabled' ),
			'styling'      => array( 'enable_autogrow_textarea' ),
			'advanced'     => array( 'log_entries', 'delete_data_on_uninstall' ),
		);

		// Only update checkboxes from the current tab.
		$current_checkboxes = $checkboxes_by_tab[ $current_tab ] ?? array();
		foreach ( $current_checkboxes as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}

		// Emails.
		$emails = array( 'admin_email', 'email_from_address' );
		foreach ( $emails as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_email( $input[ $key ] );
			}
		}

		// Text fields.
		$text_fields = array(
			'email_from_name',
			'recaptcha_site_key',
			'recaptcha_secret_key',
		);
		foreach ( $text_fields as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		// Select fields.
		$select_fields = array(
			'email_template'     => array( 'default', 'modern', 'minimal', 'plain' ),
			'recaptcha_version'  => array( 'v2_checkbox', 'v2_invisible', 'v3' ),
			'webhook_method'     => array( 'POST', 'PUT' ),
			'form_style'         => array( 'modern', 'classic', 'minimal', 'none' ),
		);

		foreach ( $select_fields as $key => $valid_values ) {
			if ( isset( $input[ $key ] ) && in_array( $input[ $key ], $valid_values, true ) ) {
				$sanitized[ $key ] = $input[ $key ];
			}
		}

		// Numbers.
		if ( isset( $input['min_submission_time'] ) ) {
			$sanitized['min_submission_time'] = absint( $input['min_submission_time'] );
		}

		if ( isset( $input['recaptcha_threshold'] ) ) {
			$sanitized['recaptcha_threshold'] = max( 0, min( 1, (float) $input['recaptcha_threshold'] ) );
		}

		if ( isset( $input['entry_retention_days'] ) ) {
			$sanitized['entry_retention_days'] = absint( $input['entry_retention_days'] );
		}

		if ( isset( $input['border_radius'] ) ) {
			$sanitized['border_radius'] = absint( $input['border_radius'] );
		}

		// URL.
		if ( isset( $input['webhook_url'] ) ) {
			$sanitized['webhook_url'] = esc_url_raw( $input['webhook_url'] );
		}

		// Webhook headers.
		if ( isset( $input['webhook_headers'] ) ) {
			$headers = array();
			$lines   = explode( "\n", $input['webhook_headers'] );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( str_contains( $line, ':' ) ) {
					list( $key, $value ) = explode( ':', $line, 2 );
					$headers[ trim( $key ) ] = trim( $value );
				}
			}
			$sanitized['webhook_headers'] = $headers;
		}

		// Colors.
		$colors = array( 'primary_color', 'success_color', 'error_color' );
		foreach ( $colors as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = sanitize_hex_color( $input[ $key ] );
			}
		}

		return $sanitized;
	}
}

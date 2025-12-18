<?php
/**
 * Lead Integrations Admin Settings Page
 *
 * Provides admin interface for configuring lead integrations:
 * - Admin email for notifications
 * - Telegram Bot API credentials
 * - Google Sheets Apps Script URL
 *
 * @package    Medici_Agency
 * @subpackage Leads
 * @since      1.4.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead Admin Settings Class
 *
 * @since 1.4.0
 */
final class Lead_Admin_Settings {

	/**
	 * Initialize admin settings
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Add settings page
		add_action( 'admin_menu', array( $self, 'add_settings_page' ) );

		// Register settings
		add_action( 'admin_init', array( $self, 'register_settings' ) );
	}

	/**
	 * Add settings page to WordPress admin
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'edit.php?post_type=medici_lead',
			__( 'Налаштування інтеграцій', 'medici.agency' ),
			__( 'Інтеграції', 'medici.agency' ),
			'manage_options',
			'medici-lead-integrations',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function register_settings(): void {
		// Register setting group
		register_setting(
			'medici_lead_integrations',
			'medici_lead_admin_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => get_option( 'admin_email' ),
			)
		);

		register_setting(
			'medici_lead_integrations',
			'medici_lead_telegram_bot_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'medici_lead_integrations',
			'medici_lead_telegram_chat_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'medici_lead_integrations',
			'medici_lead_google_sheet_url',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		// Email section
		add_settings_section(
			'medici_lead_email_section',
			__( '📧 Email сповіщення', 'medici.agency' ),
			array( $this, 'render_email_section' ),
			'medici-lead-integrations'
		);

		add_settings_field(
			'medici_lead_admin_email',
			__( 'Email адміністратора', 'medici.agency' ),
			array( $this, 'render_admin_email_field' ),
			'medici-lead-integrations',
			'medici_lead_email_section'
		);

		// Telegram section
		add_settings_section(
			'medici_lead_telegram_section',
			__( '📱 Telegram інтеграція', 'medici.agency' ),
			array( $this, 'render_telegram_section' ),
			'medici-lead-integrations'
		);

		add_settings_field(
			'medici_lead_telegram_bot_token',
			__( 'Bot Token', 'medici.agency' ),
			array( $this, 'render_telegram_bot_token_field' ),
			'medici-lead-integrations',
			'medici_lead_telegram_section'
		);

		add_settings_field(
			'medici_lead_telegram_chat_id',
			__( 'Chat ID', 'medici.agency' ),
			array( $this, 'render_telegram_chat_id_field' ),
			'medici-lead-integrations',
			'medici_lead_telegram_section'
		);

		// Google Sheets section
		add_settings_section(
			'medici_lead_google_sheets_section',
			__( '📊 Google Sheets інтеграція', 'medici.agency' ),
			array( $this, 'render_google_sheets_section' ),
			'medici-lead-integrations'
		);

		add_settings_field(
			'medici_lead_google_sheet_url',
			__( 'Apps Script URL', 'medici.agency' ),
			array( $this, 'render_google_sheet_url_field' ),
			'medici-lead-integrations',
			'medici_lead_google_sheets_section'
		);
	}

	/**
	 * Render settings page
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<p><?php esc_html_e( 'Налаштуйте інтеграції для автоматичної обробки лідів з форми консультації.', 'medici.agency' ); ?></p>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'medici_lead_integrations' );
				do_settings_sections( 'medici-lead-integrations' );

				// Hook for additional settings sections (Lead Scoring).
				do_action( 'medici_lead_integrations_settings' );

				submit_button( __( 'Зберегти налаштування', 'medici.agency' ) );
				?>
			</form>

			<?php $this->render_instructions(); ?>
		</div>
		<?php
	}

	/**
	 * Render email section description
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_email_section(): void {
		?>
		<p><?php esc_html_e( 'Email сповіщення надсилаються автоматично при отриманні нового ліда.', 'medici.agency' ); ?></p>
		<?php
	}

	/**
	 * Render admin email field
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_admin_email_field(): void {
		$value = Lead_Integrations::get_admin_email();
		?>
		<input
			type="email"
			name="medici_lead_admin_email"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
		/>
		<p class="description">
			<?php esc_html_e( 'Email для отримання сповіщень про нові ліди. За замовчуванням використовується email адміністратора WordPress.', 'medici.agency' ); ?>
		</p>
		<?php
	}

	/**
	 * Render Telegram section description
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_telegram_section(): void {
		?>
		<p><?php esc_html_e( 'Автоматичне надсилання повідомлень про нові ліди в Telegram.', 'medici.agency' ); ?></p>
		<?php
	}

	/**
	 * Render Telegram bot token field
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_telegram_bot_token_field(): void {
		$value = Lead_Integrations::get_telegram_bot_token();
		?>
		<input
			type="text"
			name="medici_lead_telegram_bot_token"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text code"
			placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
		/>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: BotFather link */
					__( 'Отримайте Bot Token від <a href="%s" target="_blank" rel="noopener">@BotFather</a> в Telegram.', 'medici.agency' ),
					'https://t.me/BotFather'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Telegram chat ID field
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_telegram_chat_id_field(): void {
		$value = Lead_Integrations::get_telegram_chat_id();
		?>
		<input
			type="text"
			name="medici_lead_telegram_chat_id"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text code"
			placeholder="-1001234567890"
		/>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: getidsbot link */
					__( 'Отримайте Chat ID від <a href="%s" target="_blank" rel="noopener">@getidsbot</a> в Telegram. Для груп використовуйте негативний ID (наприклад, -1001234567890).', 'medici.agency' ),
					'https://t.me/getidsbot'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render Google Sheets section description
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_google_sheets_section(): void {
		?>
		<p><?php esc_html_e( 'Автоматичне додавання лідів в Google Sheets таблицю.', 'medici.agency' ); ?></p>
		<?php
	}

	/**
	 * Render Google Sheets URL field
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function render_google_sheet_url_field(): void {
		$value = Lead_Integrations::get_google_sheet_url();
		?>
		<input
			type="url"
			name="medici_lead_google_sheet_url"
			value="<?php echo esc_attr( $value ); ?>"
			class="large-text code"
			placeholder="https://script.google.com/macros/s/ABC123.../exec"
		/>
		<p class="description">
			<?php esc_html_e( 'URL веб-апки Google Apps Script. Дивіться інструкцію нижче як створити Apps Script.', 'medici.agency' ); ?>
		</p>
		<?php
	}

	/**
	 * Render setup instructions
	 *
	 * @since 1.4.0
	 * @return void
	 */
	private function render_instructions(): void {
		?>
		<div class="card" style="margin-top: 30px;">
			<h2><?php esc_html_e( '📖 Інструкції з налаштування', 'medici.agency' ); ?></h2>

			<h3><?php esc_html_e( '1. Telegram Bot (5 хвилин)', 'medici.agency' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Відкрийте @BotFather в Telegram', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Створіть нового бота командою /newbot', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Скопіюйте Bot Token і вставте вище', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Додайте бота в групу або напишіть йому особисте повідомлення', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Використайте @getidsbot щоб отримати Chat ID', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Скопіюйте Chat ID і вставте вище', 'medici.agency' ); ?></li>
			</ol>

			<h3><?php esc_html_e( '2. Google Sheets (10 хвилин)', 'medici.agency' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Створіть нову таблицю в Google Sheets', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Додайте заголовки колонок: lead_id, date, name, email, phone, service, message, page_url, utm_source, utm_medium, utm_campaign, status', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Відкрийте Tools → Script editor (або Extensions → Apps Script)', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Вставте наступний код:', 'medici.agency' ); ?></li>
			</ol>

			<pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 4px; font-size: 13px;"><code>function doPost(e) {
	try {
	const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
	const data = JSON.parse(e.postData.contents);

	sheet.appendRow([
		data.lead_id || '',
		data.date || new Date().toISOString(),
		data.name || '',
		data.email || '',
		data.phone || '',
		data.service || '',
		data.message || '',
		data.page_url || '',
		data.utm_source || '',
		data.utm_medium || '',
		data.utm_campaign || '',
		data.status || 'new'
	]);

	return ContentService.createTextOutput(
		JSON.stringify({ success: true })
	).setMimeType(ContentService.MimeType.JSON);

	} catch (error) {
	return ContentService.createTextOutput(
		JSON.stringify({ success: false, error: error.toString() })
	).setMimeType(ContentService.MimeType.JSON);
	}
}</code></pre>

			<ol start="5">
				<li><?php esc_html_e( 'Збережіть проект', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Натисніть Deploy → New deployment', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Оберіть тип: Web app', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Execute as: Me', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Who has access: Anyone', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Deploy → Authorize access → Allow', 'medici.agency' ); ?></li>
				<li><?php esc_html_e( 'Скопіюйте Web app URL і вставте вище', 'medici.agency' ); ?></li>
			</ol>

			<p><strong><?php esc_html_e( 'Готово! Тепер всі ліди будуть автоматично надсилатись на email, в Telegram та Google Sheets.', 'medici.agency' ); ?></strong></p>
		</div>
		<?php
	}
}

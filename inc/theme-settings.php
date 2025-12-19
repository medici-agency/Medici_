<?php
/**
 * Theme Settings Page
 *
 * Centralized theme settings for Medici Agency.
 * Provides admin interface for branding, colors, contacts, social links, SEO, and analytics.
 *
 * @package    Medici_Agency
 * @subpackage Settings
 * @since      1.5.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme Settings Class
 *
 * @since 1.5.0
 */
final class Theme_Settings {

	/**
	 * Option group name
	 *
	 * @var string
	 */
	public const OPTION_GROUP = 'medici_theme_settings';

	/**
	 * Page slug
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'medici-theme-settings';

	/**
	 * Settings sections configuration
	 *
	 * @var array<string, array>
	 */
	private const SECTIONS = array(
		'branding'  => array(
			'title' => 'Брендинг',
			'icon'  => '🎨',
		),
		'colors'    => array(
			'title' => 'Кольори',
			'icon'  => '🎨',
		),
		'contacts'  => array(
			'title' => 'Контакти',
			'icon'  => '📞',
		),
		'social'    => array(
			'title' => 'Соціальні мережі',
			'icon'  => '🔗',
		),
		'seo'       => array(
			'title' => 'SEO',
			'icon'  => '🔍',
		),
		'analytics' => array(
			'title' => 'Аналітика',
			'icon'  => '📊',
		),
	);

	/**
	 * Initialize theme settings
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		add_action( 'admin_menu', array( $self, 'add_menu_page' ) );
		add_action( 'admin_init', array( $self, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $self, 'output_custom_colors' ), 5 );
		add_action( 'wp_head', array( $self, 'output_analytics_code' ), 1 );
	}

	/**
	 * Add menu page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'Налаштування теми', 'medici.agency' ),
			__( 'Medici Theme', 'medici.agency' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-admin-customizer',
			61
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.5.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		// Color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Media uploader.
		wp_enqueue_media();

		// Custom admin styles.
		wp_enqueue_style(
			'medici-theme-settings',
			get_stylesheet_directory_uri() . '/css/admin/theme-settings.css',
			array(),
			filemtime( get_stylesheet_directory() . '/css/admin/theme-settings.css' )
		);

		// Custom admin scripts.
		wp_enqueue_script(
			'medici-theme-settings',
			get_stylesheet_directory_uri() . '/js/admin/theme-settings.js',
			array( 'jquery', 'wp-color-picker' ),
			filemtime( get_stylesheet_directory() . '/js/admin/theme-settings.js' ),
			true
		);

		wp_localize_script(
			'medici-theme-settings',
			'mediciThemeSettings',
			array(
				'i18n' => array(
					'selectImage' => __( 'Вибрати зображення', 'medici.agency' ),
					'useImage'    => __( 'Використати', 'medici.agency' ),
					'removeImage' => __( 'Видалити', 'medici.agency' ),
				),
			)
		);
	}

	/**
	 * Register settings
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function register_settings(): void {
		// Branding settings.
		$this->register_setting( 'logo_url', 'esc_url_raw' );
		$this->register_setting( 'logo_dark_url', 'esc_url_raw' );
		$this->register_setting( 'favicon_url', 'esc_url_raw' );
		$this->register_setting( 'site_tagline', 'sanitize_text_field' );

		// Color settings.
		$this->register_setting( 'color_primary', 'sanitize_hex_color', '#10B981' );
		$this->register_setting( 'color_secondary', 'sanitize_hex_color', '#0F172A' );
		$this->register_setting( 'color_accent', 'sanitize_hex_color', '#3B82F6' );
		$this->register_setting( 'color_text', 'sanitize_hex_color', '#1E293B' );
		$this->register_setting( 'color_background', 'sanitize_hex_color', '#FFFFFF' );

		// Contact settings.
		$this->register_setting( 'contact_phone', 'sanitize_text_field' );
		$this->register_setting( 'contact_phone_display', 'sanitize_text_field' );
		$this->register_setting( 'contact_email', 'sanitize_email' );
		$this->register_setting( 'contact_address', 'sanitize_textarea_field' );
		$this->register_setting( 'contact_working_hours', 'sanitize_text_field' );

		// Social settings.
		$this->register_setting( 'social_facebook', 'esc_url_raw' );
		$this->register_setting( 'social_instagram', 'esc_url_raw' );
		$this->register_setting( 'social_linkedin', 'esc_url_raw' );
		$this->register_setting( 'social_telegram', 'esc_url_raw' );
		$this->register_setting( 'social_youtube', 'esc_url_raw' );

		// SEO settings.
		$this->register_setting( 'seo_meta_description', 'sanitize_textarea_field' );
		$this->register_setting( 'seo_og_image', 'esc_url_raw' );
		$this->register_setting( 'seo_schema_type', 'sanitize_text_field', 'MedicalBusiness' );

		// Analytics settings.
		$this->register_setting( 'analytics_ga4_id', 'sanitize_text_field' );
		$this->register_setting( 'analytics_gtm_id', 'sanitize_text_field' );
		$this->register_setting( 'analytics_fb_pixel', 'sanitize_text_field' );
	}

	/**
	 * Register a single setting
	 *
	 * @since 1.5.0
	 * @param string $name              Setting name.
	 * @param string $sanitize_callback Sanitization callback.
	 * @param mixed  $default           Default value.
	 * @return void
	 */
	private function register_setting( string $name, string $sanitize_callback, $default = '' ): void {
		register_setting(
			self::OPTION_GROUP,
			'medici_' . $name,
			array(
				'type'              => 'string',
				'sanitize_callback' => $sanitize_callback,
				'default'           => $default,
			)
		);
	}

	/**
	 * Render settings page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'branding';

		?>
		<div class="wrap medici-theme-settings">
			<h1>
				<span class="dashicons dashicons-admin-customizer"></span>
				<?php esc_html_e( 'Налаштування теми Medici', 'medici.agency' ); ?>
			</h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( self::SECTIONS as $tab_id => $tab_data ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&tab=' . $tab_id ) ); ?>"
						class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_data['icon'] . ' ' . $tab_data['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="theme-settings-form">
				<?php settings_fields( self::OPTION_GROUP ); ?>

				<div class="settings-content">
					<?php
					switch ( $active_tab ) {
						case 'branding':
							$this->render_branding_section();
							break;
						case 'colors':
							$this->render_colors_section();
							break;
						case 'contacts':
							$this->render_contacts_section();
							break;
						case 'social':
							$this->render_social_section();
							break;
						case 'seo':
							$this->render_seo_section();
							break;
						case 'analytics':
							$this->render_analytics_section();
							break;
					}
					?>
				</div>

				<?php submit_button( __( 'Зберегти налаштування', 'medici.agency' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render branding section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_branding_section(): void {
		?>
		<h2><?php esc_html_e( '🎨 Брендинг', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Налаштування логотипу та базової ідентичності бренду.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_image_field( 'logo_url', __( 'Логотип (світла тема)', 'medici.agency' ), __( 'Рекомендований розмір: 200x60 px, формат SVG або PNG з прозорим фоном', 'medici.agency' ) );
			$this->render_image_field( 'logo_dark_url', __( 'Логотип (темна тема)', 'medici.agency' ), __( 'Логотип для темного режиму. Якщо не вказано, використовується основний логотип', 'medici.agency' ) );
			$this->render_image_field( 'favicon_url', __( 'Favicon', 'medici.agency' ), __( 'Рекомендований розмір: 512x512 px, формат PNG', 'medici.agency' ) );
			$this->render_text_field( 'site_tagline', __( 'Слоган сайту', 'medici.agency' ), __( 'Короткий опис компанії для використання в header та meta tags', 'medici.agency' ), 'Медичний маркетинг, який працює' );
			?>
		</table>
		<?php
	}

	/**
	 * Render colors section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_colors_section(): void {
		?>
		<h2><?php esc_html_e( '🎨 Кольорова схема', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Налаштування кольорів теми. Ці кольори використовуються як CSS змінні на всьому сайті.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_color_field( 'color_primary', __( 'Primary (основний)', 'medici.agency' ), '#10B981', __( 'Основний колір бренду (кнопки, посилання, акценти)', 'medici.agency' ) );
			$this->render_color_field( 'color_secondary', __( 'Secondary (другорядний)', 'medici.agency' ), '#0F172A', __( 'Другорядний колір (заголовки, footer)', 'medici.agency' ) );
			$this->render_color_field( 'color_accent', __( 'Accent (акцентний)', 'medici.agency' ), '#3B82F6', __( 'Акцентний колір для виділення важливих елементів', 'medici.agency' ) );
			$this->render_color_field( 'color_text', __( 'Text (текст)', 'medici.agency' ), '#1E293B', __( 'Основний колір тексту', 'medici.agency' ) );
			$this->render_color_field( 'color_background', __( 'Background (фон)', 'medici.agency' ), '#FFFFFF', __( 'Колір фону сайту', 'medici.agency' ) );
			?>
		</table>

		<div class="color-preview">
			<h3><?php esc_html_e( 'Попередній перегляд', 'medici.agency' ); ?></h3>
			<div class="preview-container">
				<div class="preview-block primary" style="background-color: <?php echo esc_attr( self::get( 'color_primary', '#10B981' ) ); ?>">Primary</div>
				<div class="preview-block secondary" style="background-color: <?php echo esc_attr( self::get( 'color_secondary', '#0F172A' ) ); ?>">Secondary</div>
				<div class="preview-block accent" style="background-color: <?php echo esc_attr( self::get( 'color_accent', '#3B82F6' ) ); ?>">Accent</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render contacts section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_contacts_section(): void {
		?>
		<h2><?php esc_html_e( '📞 Контактна інформація', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Контактні дані компанії для header, footer та schema markup.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_text_field( 'contact_phone', __( 'Телефон (для дзвінків)', 'medici.agency' ), __( 'Формат: +380XXXXXXXXX (для tel: посилань)', 'medici.agency' ), '+380441234567' );
			$this->render_text_field( 'contact_phone_display', __( 'Телефон (для відображення)', 'medici.agency' ), __( 'Формат відображення номера на сайті', 'medici.agency' ), '+38 (044) 123-45-67' );
			$this->render_text_field( 'contact_email', __( 'Email', 'medici.agency' ), __( 'Основний email для контакту', 'medici.agency' ), 'hello@medici.agency', 'email' );
			$this->render_textarea_field( 'contact_address', __( 'Адреса', 'medici.agency' ), __( 'Повна адреса офісу', 'medici.agency' ), "вул. Хрещатик, 1\nКиїв, 01001, Україна" );
			$this->render_text_field( 'contact_working_hours', __( 'Години роботи', 'medici.agency' ), __( 'Наприклад: Пн-Пт 9:00-18:00', 'medici.agency' ), 'Пн-Пт 9:00-18:00' );
			?>
		</table>
		<?php
	}

	/**
	 * Render social section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_social_section(): void {
		?>
		<h2><?php esc_html_e( '🔗 Соціальні мережі', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Посилання на профілі в соціальних мережах.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_url_field( 'social_facebook', __( 'Facebook', 'medici.agency' ), 'https://facebook.com/mediciagency' );
			$this->render_url_field( 'social_instagram', __( 'Instagram', 'medici.agency' ), 'https://instagram.com/mediciagency' );
			$this->render_url_field( 'social_linkedin', __( 'LinkedIn', 'medici.agency' ), 'https://linkedin.com/company/mediciagency' );
			$this->render_url_field( 'social_telegram', __( 'Telegram', 'medici.agency' ), 'https://t.me/mediciagency' );
			$this->render_url_field( 'social_youtube', __( 'YouTube', 'medici.agency' ), 'https://youtube.com/@mediciagency' );
			?>
		</table>
		<?php
	}

	/**
	 * Render SEO section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_seo_section(): void {
		?>
		<h2><?php esc_html_e( '🔍 SEO налаштування', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Базові SEO налаштування для сайту.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_textarea_field( 'seo_meta_description', __( 'Meta Description за замовчуванням', 'medici.agency' ), __( 'Опис сайту для пошукових систем (150-160 символів)', 'medici.agency' ), 'Medici Agency — агенція медичного маркетингу. Допомагаємо медичним закладам залучати пацієнтів.' );
			$this->render_image_field( 'seo_og_image', __( 'OG Image за замовчуванням', 'medici.agency' ), __( 'Зображення для соціальних мереж. Рекомендований розмір: 1200x630 px', 'medici.agency' ) );
			?>
			<tr>
				<th scope="row">
					<label for="medici_seo_schema_type"><?php esc_html_e( 'Schema Type', 'medici.agency' ); ?></label>
				</th>
				<td>
					<select id="medici_seo_schema_type" name="medici_seo_schema_type" class="regular-text">
						<?php
						$schema_types = array(
							'MedicalBusiness'         => 'Medical Business',
							'HealthAndBeautyBusiness' => 'Health and Beauty Business',
							'ProfessionalService'     => 'Professional Service',
							'MarketingAgency'         => 'Marketing Agency',
							'Organization'            => 'Organization',
						);
						$current      = self::get( 'seo_schema_type', 'MedicalBusiness' );
						foreach ( $schema_types as $value => $label ) :
							?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Тип організації для Schema.org markup', 'medici.agency' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render analytics section
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_analytics_section(): void {
		?>
		<h2><?php esc_html_e( '📊 Аналітика', 'medici.agency' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Налаштування аналітики та відстеження.', 'medici.agency' ); ?></p>

		<table class="form-table">
			<?php
			$this->render_text_field( 'analytics_ga4_id', __( 'Google Analytics 4 ID', 'medici.agency' ), __( 'Формат: G-XXXXXXXXXX', 'medici.agency' ), 'G-XXXXXXXXXX' );
			$this->render_text_field( 'analytics_gtm_id', __( 'Google Tag Manager ID', 'medici.agency' ), __( 'Формат: GTM-XXXXXXX', 'medici.agency' ), 'GTM-XXXXXXX' );
			$this->render_text_field( 'analytics_fb_pixel', __( 'Facebook Pixel ID', 'medici.agency' ), __( 'Числовий ID пікселя', 'medici.agency' ), '1234567890' );
			?>
		</table>

		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Примітка:', 'medici.agency' ); ?></strong>
				<?php esc_html_e( 'Коди аналітики будуть автоматично додані в head сайту. Для розширеного налаштування рекомендуємо використовувати Google Tag Manager.', 'medici.agency' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render text field
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $description Field description.
	 * @param string $placeholder Placeholder text.
	 * @param string $type        Input type.
	 * @return void
	 */
	private function render_text_field( string $name, string $label, string $description = '', string $placeholder = '', string $type = 'text' ): void {
		$value = self::get( $name );
		?>
		<tr>
			<th scope="row">
				<label for="medici_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input type="<?php echo esc_attr( $type ); ?>"
						id="medici_<?php echo esc_attr( $name ); ?>"
						name="medici_<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="regular-text"
						placeholder="<?php echo esc_attr( $placeholder ); ?>">
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render URL field
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $placeholder Placeholder text.
	 * @return void
	 */
	private function render_url_field( string $name, string $label, string $placeholder = '' ): void {
		$value = self::get( $name );
		?>
		<tr>
			<th scope="row">
				<label for="medici_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input type="url"
						id="medici_<?php echo esc_attr( $name ); ?>"
						name="medici_<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="regular-text code"
						placeholder="<?php echo esc_attr( $placeholder ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Render textarea field
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $description Field description.
	 * @param string $placeholder Placeholder text.
	 * @return void
	 */
	private function render_textarea_field( string $name, string $label, string $description = '', string $placeholder = '' ): void {
		$value = self::get( $name );
		?>
		<tr>
			<th scope="row">
				<label for="medici_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<textarea id="medici_<?php echo esc_attr( $name ); ?>"
							name="medici_<?php echo esc_attr( $name ); ?>"
							class="large-text"
							rows="3"
							placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render color field
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $default     Default color.
	 * @param string $description Field description.
	 * @return void
	 */
	private function render_color_field( string $name, string $label, string $default = '#000000', string $description = '' ): void {
		$value = self::get( $name, $default );
		?>
		<tr>
			<th scope="row">
				<label for="medici_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<input type="text"
						id="medici_<?php echo esc_attr( $name ); ?>"
						name="medici_<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="medici-color-picker"
						data-default-color="<?php echo esc_attr( $default ); ?>">
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render image field
	 *
	 * @param string $name        Field name.
	 * @param string $label       Field label.
	 * @param string $description Field description.
	 * @return void
	 */
	private function render_image_field( string $name, string $label, string $description = '' ): void {
		$value = self::get( $name );
		?>
		<tr>
			<th scope="row">
				<label for="medici_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			</th>
			<td>
				<div class="image-upload-field">
					<input type="url"
							id="medici_<?php echo esc_attr( $name ); ?>"
							name="medici_<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( $value ); ?>"
							class="regular-text image-url-input">
					<button type="button" class="button upload-image-button">
						<?php esc_html_e( 'Вибрати', 'medici.agency' ); ?>
					</button>
					<button type="button" class="button remove-image-button" <?php echo empty( $value ) ? 'style="display:none;"' : ''; ?>>
						<?php esc_html_e( 'Видалити', 'medici.agency' ); ?>
					</button>
				</div>
				<?php if ( $value ) : ?>
					<div class="image-preview" style="margin-top: 10px;">
						<img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width: 200px; max-height: 100px;">
					</div>
				<?php endif; ?>
				<?php if ( $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output custom colors as CSS variables
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function output_custom_colors(): void {
		$primary    = self::get( 'color_primary', '#10B981' );
		$secondary  = self::get( 'color_secondary', '#0F172A' );
		$accent     = self::get( 'color_accent', '#3B82F6' );
		$text       = self::get( 'color_text', '#1E293B' );
		$background = self::get( 'color_background', '#FFFFFF' );

		// Only output if at least one color is customized.
		if ( '#10B981' === $primary && '#0F172A' === $secondary && '#3B82F6' === $accent ) {
			return;
		}

		?>
		<style id="medici-custom-colors">
			:root {
				--color-primary: <?php echo esc_attr( $primary ); ?>;
				--color-secondary: <?php echo esc_attr( $secondary ); ?>;
				--color-accent: <?php echo esc_attr( $accent ); ?>;
				--color-text: <?php echo esc_attr( $text ); ?>;
				--color-background: <?php echo esc_attr( $background ); ?>;
			}
		</style>
		<?php
	}

	/**
	 * Output analytics code
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function output_analytics_code(): void {
		// Skip in admin.
		if ( is_admin() ) {
			return;
		}

		$ga4_id   = self::get( 'analytics_ga4_id' );
		$gtm_id   = self::get( 'analytics_gtm_id' );
		$fb_pixel = self::get( 'analytics_fb_pixel' );

		// Check if Google Site Kit is active (to avoid duplicate GTM loading).
		// Site Kit manages GTM/GA4 itself, so we skip our deferred loading.
		$site_kit_active = defined( 'GOOGLESITEKIT_VERSION' ) || class_exists( 'Google\\Site_Kit\\Plugin' );

		// Google Tag Manager - DEFERRED loading for better performance.
		// GTM loads after user interaction (scroll, click, keypress) or after 3 seconds.
		// This improves FCP/LCP by not blocking initial render with heavy third-party scripts.
		// Skip if Site Kit is active (Site Kit handles GTM/GA4 itself).
		if ( $gtm_id && preg_match( '/^GTM-[A-Z0-9]+$/', $gtm_id ) && ! $site_kit_active ) {
			?>
			<!-- Google Tag Manager (Deferred) -->
			<script>
			(function() {
				var gtmLoaded = false;
				var gtmId = '<?php echo esc_js( $gtm_id ); ?>';

				function loadGTM() {
					if (gtmLoaded) return;
					gtmLoaded = true;

					// Initialize dataLayer
					window.dataLayer = window.dataLayer || [];
					window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});

					// Load GTM script
					var f = document.getElementsByTagName('script')[0];
					var j = document.createElement('script');
					j.async = true;
					j.src = 'https://www.googletagmanager.com/gtm.js?id=' + gtmId;
					f.parentNode.insertBefore(j, f);
				}

				// Load on user interaction (click, scroll, keypress, touch)
				var interactionEvents = ['scroll', 'click', 'keypress', 'touchstart', 'mousemove'];
				interactionEvents.forEach(function(event) {
					window.addEventListener(event, loadGTM, {once: true, passive: true});
				});

				// Fallback: load after 3 seconds if no interaction
				setTimeout(loadGTM, 3000);
			})();
			</script>
			<!-- End Google Tag Manager (Deferred) -->
			<?php
		}

		// Google Analytics 4 (only if GTM is not used) - also deferred.
		// Skip if Site Kit is active (Site Kit handles GA4 itself).
		if ( $ga4_id && preg_match( '/^G-[A-Z0-9]+$/', $ga4_id ) && ! $gtm_id && ! $site_kit_active ) {
			?>
			<!-- Google Analytics 4 (Deferred) -->
			<script>
			(function() {
				var ga4Loaded = false;
				var ga4Id = '<?php echo esc_js( $ga4_id ); ?>';

				function loadGA4() {
					if (ga4Loaded) return;
					ga4Loaded = true;

					// Initialize dataLayer and gtag
					window.dataLayer = window.dataLayer || [];
					function gtag(){dataLayer.push(arguments);}
					window.gtag = gtag;
					gtag('js', new Date());
					gtag('config', ga4Id);

					// Load gtag.js script
					var s = document.createElement('script');
					s.async = true;
					s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ga4Id;
					document.head.appendChild(s);
				}

				// Load on user interaction
				var interactionEvents = ['scroll', 'click', 'keypress', 'touchstart', 'mousemove'];
				interactionEvents.forEach(function(event) {
					window.addEventListener(event, loadGA4, {once: true, passive: true});
				});

				// Fallback: load after 3 seconds
				setTimeout(loadGA4, 3000);
			})();
			</script>
			<!-- End Google Analytics 4 (Deferred) -->
			<?php
		}

		// Facebook Pixel (only if GTM is not used) - also deferred.
		if ( $fb_pixel && preg_match( '/^[0-9]+$/', $fb_pixel ) && ! $gtm_id ) {
			?>
			<!-- Facebook Pixel (Deferred) -->
			<script>
			(function() {
				var fbLoaded = false;
				var fbPixelId = '<?php echo esc_js( $fb_pixel ); ?>';

				function loadFBPixel() {
					if (fbLoaded) return;
					fbLoaded = true;

					!function(f,b,e,v,n,t,s)
					{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
					n.callMethod.apply(n,arguments):n.queue.push(arguments)};
					if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
					n.queue=[];t=b.createElement(e);t.async=!0;
					t.src=v;s=b.getElementsByTagName(e)[0];
					s.parentNode.insertBefore(t,s)}(window, document,'script',
					'https://connect.facebook.net/en_US/fbevents.js');
					fbq('init', fbPixelId);
					fbq('track', 'PageView');
				}

				// Load on user interaction
				var interactionEvents = ['scroll', 'click', 'keypress', 'touchstart', 'mousemove'];
				interactionEvents.forEach(function(event) {
					window.addEventListener(event, loadFBPixel, {once: true, passive: true});
				});

				// Fallback: load after 3 seconds
				setTimeout(loadFBPixel, 3000);
			})();
			</script>
			<!-- End Facebook Pixel (Deferred) -->
			<?php
		}
	}

	/**
	 * Get setting value
	 *
	 * @since 1.5.0
	 * @param string $name    Setting name (without prefix).
	 * @param mixed  $default Default value.
	 * @return mixed Setting value.
	 */
	public static function get( string $name, $default = '' ) {
		return get_option( 'medici_' . $name, $default );
	}

	/**
	 * Get all settings
	 *
	 * @since 1.5.0
	 * @return array All settings.
	 */
	public static function get_all(): array {
		return array(
			// Branding.
			'logo_url'              => self::get( 'logo_url' ),
			'logo_dark_url'         => self::get( 'logo_dark_url' ),
			'favicon_url'           => self::get( 'favicon_url' ),
			'site_tagline'          => self::get( 'site_tagline' ),
			// Colors.
			'color_primary'         => self::get( 'color_primary', '#10B981' ),
			'color_secondary'       => self::get( 'color_secondary', '#0F172A' ),
			'color_accent'          => self::get( 'color_accent', '#3B82F6' ),
			'color_text'            => self::get( 'color_text', '#1E293B' ),
			'color_background'      => self::get( 'color_background', '#FFFFFF' ),
			// Contacts.
			'contact_phone'         => self::get( 'contact_phone' ),
			'contact_phone_display' => self::get( 'contact_phone_display' ),
			'contact_email'         => self::get( 'contact_email' ),
			'contact_address'       => self::get( 'contact_address' ),
			'contact_working_hours' => self::get( 'contact_working_hours' ),
			// Social.
			'social_facebook'       => self::get( 'social_facebook' ),
			'social_instagram'      => self::get( 'social_instagram' ),
			'social_linkedin'       => self::get( 'social_linkedin' ),
			'social_telegram'       => self::get( 'social_telegram' ),
			'social_youtube'        => self::get( 'social_youtube' ),
			// SEO.
			'seo_meta_description'  => self::get( 'seo_meta_description' ),
			'seo_og_image'          => self::get( 'seo_og_image' ),
			'seo_schema_type'       => self::get( 'seo_schema_type', 'MedicalBusiness' ),
			// Analytics.
			'analytics_ga4_id'      => self::get( 'analytics_ga4_id' ),
			'analytics_gtm_id'      => self::get( 'analytics_gtm_id' ),
			'analytics_fb_pixel'    => self::get( 'analytics_fb_pixel' ),
		);
	}
}

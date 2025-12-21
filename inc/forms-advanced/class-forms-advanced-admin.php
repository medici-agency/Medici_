<?php
/**
 * Medici Forms Advanced - Admin Class
 *
 * Admin панель з налаштуваннями у стилі WP Mail SMTP
 *
 * @package    Medici_Agency
 * @subpackage Forms_Advanced
 * @since      1.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class
 *
 * @since 1.0.0
 */
class Medici_Forms_Advanced_Admin {

	/**
	 * Settings instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced_Settings
	 */
	private Medici_Forms_Advanced_Settings $settings;

	/**
	 * Current tab
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $current_tab = 'layout';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param Medici_Forms_Advanced_Settings $settings Settings instance
	 */
	public function __construct( Medici_Forms_Advanced_Settings $settings ) {
		$this->settings = $settings;

		$this->hooks();
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function hooks(): void {
		// Add admin menu
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Process form submission
		add_action( 'admin_init', [ $this, 'process_form' ] );

		// Add settings link to WPForms menu
		add_action( 'admin_menu', [ $this, 'add_to_wpforms_menu' ], 20 );
	}

	/**
	 * Add admin menu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Medici Forms Advanced', 'medici' ),
			__( 'Forms Advanced', 'medici' ),
			'manage_options',
			Medici_Forms_Advanced::MENU_SLUG,
			[ $this, 'render_settings_page' ],
			'dashicons-forms',
			59
		);
	}

	/**
	 * Add settings link to WPForms menu
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_to_wpforms_menu(): void {
		add_submenu_page(
			'wpforms-overview',
			__( 'Advanced Settings', 'medici' ),
			__( 'Advanced Settings', 'medici' ),
			'manage_options',
			Medici_Forms_Advanced::MENU_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		// Only load on our settings page
		if ( ! str_contains( $hook, Medici_Forms_Advanced::MENU_SLUG ) ) {
			return;
		}

		// Color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Custom admin styles (inline для економії файлів)
		wp_add_inline_style( 'wp-color-picker', $this->get_admin_css() );

		// Custom admin scripts (inline для економії файлів)
		wp_add_inline_script( 'wp-color-picker', $this->get_admin_js(), 'after' );
	}

	/**
	 * Get admin CSS
	 *
	 * @since 1.0.0
	 * @return string CSS код
	 */
	private function get_admin_css(): string {
		return <<<'CSS'
		/* Medici Forms Advanced Admin Styles */
		.medici-forms-advanced {
			max-width: 1200px;
			margin: 20px 0;
		}

		.medici-forms-advanced__header {
			background: #fff;
			border-left: 4px solid #2563eb;
			padding: 20px 30px;
			margin-bottom: 20px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}

		.medici-forms-advanced__header h1 {
			margin: 0 0 10px;
			font-size: 24px;
			color: #1a1a1a;
		}

		.medici-forms-advanced__header p {
			margin: 0;
			color: #666;
		}

		.medici-forms-advanced__tabs {
			background: #fff;
			border-bottom: 1px solid #e5e7eb;
			margin-bottom: 0;
		}

		.medici-forms-advanced__tabs-nav {
			display: flex;
			gap: 0;
			margin: 0;
			padding: 0;
			list-style: none;
		}

		.medici-forms-advanced__tab-link {
			display: block;
			padding: 15px 25px;
			border-bottom: 3px solid transparent;
			color: #666;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.2s;
		}

		.medici-forms-advanced__tab-link:hover {
			color: #2563eb;
			background: #f9fafb;
		}

		.medici-forms-advanced__tab-link--active {
			color: #2563eb;
			border-bottom-color: #2563eb;
			background: #f9fafb;
		}

		.medici-forms-advanced__content {
			background: #fff;
			padding: 30px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}

		.medici-forms-advanced__section {
			margin-bottom: 40px;
		}

		.medici-forms-advanced__section:last-child {
			margin-bottom: 0;
		}

		.medici-forms-advanced__section-title {
			font-size: 18px;
			font-weight: 600;
			margin: 0 0 20px;
			padding-bottom: 10px;
			border-bottom: 2px solid #e5e7eb;
			color: #1a1a1a;
		}

		.medici-forms-advanced__field {
			margin-bottom: 25px;
		}

		.medici-forms-advanced__field-label {
			display: block;
			margin-bottom: 8px;
			font-weight: 600;
			color: #1a1a1a;
		}

		.medici-forms-advanced__field-description {
			display: block;
			margin-top: 5px;
			font-size: 13px;
			color: #666;
			font-style: italic;
		}

		.medici-forms-advanced__field input[type="text"],
		.medici-forms-advanced__field input[type="number"],
		.medici-forms-advanced__field input[type="url"],
		.medici-forms-advanced__field select,
		.medici-forms-advanced__field textarea {
			width: 100%;
			max-width: 500px;
		}

		.medici-forms-advanced__field textarea {
			min-height: 120px;
			font-family: monospace;
		}

		.medici-forms-advanced__grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
		}

		.medici-forms-advanced__submit {
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #e5e7eb;
		}

		.medici-forms-advanced__button {
			background: #2563eb;
			color: #fff;
			border: none;
			padding: 12px 30px;
			font-size: 14px;
			font-weight: 600;
			border-radius: 4px;
			cursor: pointer;
			transition: all 0.2s;
		}

		.medici-forms-advanced__button:hover {
			background: #1d4ed8;
			transform: translateY(-1px);
		}

		.medici-forms-advanced__button--secondary {
			background: #6b7280;
			margin-left: 10px;
		}

		.medici-forms-advanced__button--secondary:hover {
			background: #4b5563;
		}

		.medici-forms-advanced__preview {
			margin-top: 30px;
			padding: 20px;
			background: #f9fafb;
			border: 2px dashed #e5e7eb;
			border-radius: 4px;
		}

		.medici-forms-advanced__preview-title {
			font-weight: 600;
			margin-bottom: 15px;
			color: #1a1a1a;
		}

		.medici-forms-advanced__notice {
			padding: 12px 20px;
			margin: 20px 0;
			border-left: 4px solid;
			background: #f9fafb;
		}

		.medici-forms-advanced__notice--success {
			border-left-color: #22c55e;
			color: #16a34a;
		}

		.medici-forms-advanced__notice--error {
			border-left-color: #ef4444;
			color: #dc2626;
		}

		.medici-forms-advanced__notice--info {
			border-left-color: #3b82f6;
			color: #2563eb;
		}
		CSS;
	}

	/**
	 * Get admin JS
	 *
	 * @since 1.0.0
	 * @return string JavaScript код
	 */
	private function get_admin_js(): string {
		return <<<'JS'
		jQuery(document).ready(function($) {
			// Initialize color pickers
			$('.medici-color-picker').wpColorPicker();

			// Tab switching
			$('.medici-forms-advanced__tab-link').on('click', function(e) {
				e.preventDefault();
				var tab = $(this).data('tab');

				// Update URL without reload
				var url = new URL(window.location);
				url.searchParams.set('tab', tab);
				window.history.pushState({}, '', url);

				// Update active tab
				$('.medici-forms-advanced__tab-link').removeClass('medici-forms-advanced__tab-link--active');
				$(this).addClass('medici-forms-advanced__tab-link--active');

				// Show/hide content
				$('.medici-forms-advanced__tab-content').hide();
				$('#medici-tab-' + tab).show();
			});

			// Preview updates
			function updatePreview() {
				var formMaxWidth = $('[name="layout[form_max_width]"]').val();
				var buttonWidth = $('[name="layout[button_width]"]').val();
				var buttonAlignment = $('[name="layout[button_alignment]"]').val();

				// Update preview styles
				$('.medici-preview-form').css('max-width', formMaxWidth);
				$('.medici-preview-button').css('width', buttonWidth);

				var alignMap = {
					'left': 'flex-start',
					'center': 'center',
					'right': 'flex-end',
					'full': 'stretch'
				};

				$('.medici-preview-form').css('align-items', alignMap[buttonAlignment] || 'flex-start');
			}

			// Update preview on change
			$('[name^="layout["]').on('change input', updatePreview);
			$('[name^="styling["]').on('change input', updatePreview);

			// Reset confirmation
			$('.medici-forms-advanced__reset').on('click', function(e) {
				return confirm('Ви впевнені що хочете скинути всі налаштування до значень за замовчуванням?');
			});
		});
		JS;
	}

	/**
	 * Process form submission
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function process_form(): void {
		// Check if form was submitted
		if ( ! isset( $_POST['medici_forms_advanced_save'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'medici_forms_advanced_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'medici' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'medici' ) );
		}

		// Get settings from POST
		$settings = [];

		if ( isset( $_POST['layout'] ) && is_array( $_POST['layout'] ) ) {
			$settings['layout'] = wp_unslash( $_POST['layout'] );
		}

		if ( isset( $_POST['styling'] ) && is_array( $_POST['styling'] ) ) {
			$settings['styling'] = wp_unslash( $_POST['styling'] );
		}

		if ( isset( $_POST['antibot'] ) && is_array( $_POST['antibot'] ) ) {
			$settings['antibot'] = wp_unslash( $_POST['antibot'] );
		}

		if ( isset( $_POST['email'] ) && is_array( $_POST['email'] ) ) {
			$settings['email'] = wp_unslash( $_POST['email'] );
		}

		if ( isset( $_POST['file_upload'] ) && is_array( $_POST['file_upload'] ) ) {
			$settings['file_upload'] = wp_unslash( $_POST['file_upload'] );
		}

		if ( isset( $_POST['advanced'] ) && is_array( $_POST['advanced'] ) ) {
			$settings['advanced'] = wp_unslash( $_POST['advanced'] );
		}

		// Save settings
		$result = $this->settings->save( $settings );

		// Redirect with success message
		$redirect_url = add_query_arg(
			[
				'page'    => Medici_Forms_Advanced::MENU_SLUG,
				'updated' => $result ? '1' : '0',
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Render settings page
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		// Get current tab
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'layout';

		?>
		<div class="wrap medici-forms-advanced">
			<?php $this->render_header(); ?>
			<?php $this->render_notices(); ?>
			<?php $this->render_tabs(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'medici_forms_advanced_settings' ); ?>

				<div class="medici-forms-advanced__content">
					<?php $this->render_tab_content(); ?>

					<div class="medici-forms-advanced__submit">
						<button type="submit" name="medici_forms_advanced_save" class="medici-forms-advanced__button">
							<?php esc_html_e( 'Зберегти налаштування', 'medici' ); ?>
						</button>

						<button type="submit" name="medici_forms_advanced_reset" class="medici-forms-advanced__button medici-forms-advanced__button--secondary medici-forms-advanced__reset">
							<?php esc_html_e( 'Скинути до значень за замовчуванням', 'medici' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render header
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_header(): void {
		?>
		<div class="medici-forms-advanced__header">
			<h1><?php esc_html_e( 'Medici Forms Advanced', 'medici' ); ?></h1>
			<p><?php esc_html_e( 'Розширені налаштування для WPForms з сучасним дизайном та додатковими можливостями', 'medici' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render notices
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_notices(): void {
		if ( isset( $_GET['updated'] ) ) {
			$updated = sanitize_key( $_GET['updated'] );

			if ( '1' === $updated ) {
				?>
				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--success">
					<?php esc_html_e( 'Налаштування успішно збережено!', 'medici' ); ?>
				</div>
				<?php
			} else {
				?>
				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--error">
					<?php esc_html_e( 'Помилка при збереженні налаштувань.', 'medici' ); ?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Render tabs
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tabs(): void {
		$tabs = [
			'layout'      => __( 'Layout', 'medici' ),
			'styling'     => __( 'Styling', 'medici' ),
			'antibot'     => __( 'Anti-Bot', 'medici' ),
			'email'       => __( 'Email', 'medici' ),
			'file_upload' => __( 'File Upload', 'medici' ),
			'advanced'    => __( 'Advanced', 'medici' ),
		];

		?>
		<div class="medici-forms-advanced__tabs">
			<ul class="medici-forms-advanced__tabs-nav">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<li>
						<a
							href="<?php echo esc_url( add_query_arg( 'tab', $tab_key ) ); ?>"
							class="medici-forms-advanced__tab-link <?php echo $this->current_tab === $tab_key ? 'medici-forms-advanced__tab-link--active' : ''; ?>"
							data-tab="<?php echo esc_attr( $tab_key ); ?>"
						>
							<?php echo esc_html( $tab_label ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render tab content
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_content(): void {
		$method = 'render_tab_' . $this->current_tab;

		if ( method_exists( $this, $method ) ) {
			$this->$method();
		}
	}

	/**
	 * Render Layout tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_layout(): void {
		$layout = $this->settings->get_group( 'layout' );

		?>
		<div id="medici-tab-layout" class="medici-forms-advanced__tab-content">
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Налаштування Layout', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__grid">
					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Максимальна ширина форми', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="layout[form_max_width]"
							value="<?php echo esc_attr( $layout['form_max_width'] ?? '600px' ); ?>"
							class="regular-text"
						/>
						<span class="medici-forms-advanced__field-description">
							<?php esc_html_e( 'Наприклад: 600px, 100%, 50rem', 'medici' ); ?>
						</span>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Ширина полів форми', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="layout[field_width]"
							value="<?php echo esc_attr( $layout['field_width'] ?? '100%' ); ?>"
							class="regular-text"
						/>
						<span class="medici-forms-advanced__field-description">
							<?php esc_html_e( 'Наприклад: 100%, 400px, 80%', 'medici' ); ?>
						</span>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Ширина кнопки', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="layout[button_width]"
							value="<?php echo esc_attr( $layout['button_width'] ?? 'auto' ); ?>"
							class="regular-text"
						/>
						<span class="medici-forms-advanced__field-description">
							<?php esc_html_e( 'Наприклад: auto, 200px, 100%', 'medici' ); ?>
						</span>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Максимальна ширина кнопки', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="layout[button_max_width]"
							value="<?php echo esc_attr( $layout['button_max_width'] ?? '300px' ); ?>"
							class="regular-text"
						/>
						<span class="medici-forms-advanced__field-description">
							<?php esc_html_e( 'Обмеження ширини кнопки "Відправити заявку"', 'medici' ); ?>
						</span>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Відстань між полями', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="layout[field_gap]"
							value="<?php echo esc_attr( $layout['field_gap'] ?? '1.5rem' ); ?>"
							class="regular-text"
						/>
						<span class="medici-forms-advanced__field-description">
							<?php esc_html_e( 'Наприклад: 1.5rem, 20px, 1em', 'medici' ); ?>
						</span>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Вирівнювання кнопки', 'medici' ); ?>
						</label>
						<select name="layout[button_alignment]">
							<option value="left" <?php selected( $layout['button_alignment'] ?? 'left', 'left' ); ?>>
								<?php esc_html_e( 'Ліворуч', 'medici' ); ?>
							</option>
							<option value="center" <?php selected( $layout['button_alignment'] ?? 'left', 'center' ); ?>>
								<?php esc_html_e( 'По центру', 'medici' ); ?>
							</option>
							<option value="right" <?php selected( $layout['button_alignment'] ?? 'left', 'right' ); ?>>
								<?php esc_html_e( 'Праворуч', 'medici' ); ?>
							</option>
							<option value="full" <?php selected( $layout['button_alignment'] ?? 'left', 'full' ); ?>>
								<?php esc_html_e( 'На всю ширину', 'medici' ); ?>
							</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Styling tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_styling(): void {
		$styling = $this->settings->get_group( 'styling' );

		?>
		<div id="medici-tab-styling" class="medici-forms-advanced__tab-content">
			<!-- Fields Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Стилі полів форми', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__grid">
					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір фону', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_bg_color]"
							value="<?php echo esc_attr( $styling['field_bg_color'] ?? '#f9fafb' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір бордера', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_border_color]"
							value="<?php echo esc_attr( $styling['field_border_color'] ?? '#e5e7eb' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Товщина бордера', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_border_width]"
							value="<?php echo esc_attr( $styling['field_border_width'] ?? '2px' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Скруглення кутів', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_border_radius]"
							value="<?php echo esc_attr( $styling['field_border_radius'] ?? '4px' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір тексту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_text_color]"
							value="<?php echo esc_attr( $styling['field_text_color'] ?? '#111827' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Розмір шрифту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_font_size]"
							value="<?php echo esc_attr( $styling['field_font_size'] ?? '1rem' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Padding', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[field_padding]"
							value="<?php echo esc_attr( $styling['field_padding'] ?? '0.75rem 1rem' ); ?>"
							class="regular-text"
						/>
					</div>
				</div>
			</div>

			<!-- Buttons Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Стилі кнопок', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__grid">
					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір фону', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_bg_color]"
							value="<?php echo esc_attr( $styling['button_bg_color'] ?? '#2563eb' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір тексту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_text_color]"
							value="<?php echo esc_attr( $styling['button_text_color'] ?? '#ffffff' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір фону при hover', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_hover_bg_color]"
							value="<?php echo esc_attr( $styling['button_hover_bg_color'] ?? '#1d4ed8' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Скруглення кутів', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_border_radius]"
							value="<?php echo esc_attr( $styling['button_border_radius'] ?? '4px' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Розмір шрифту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_font_size]"
							value="<?php echo esc_attr( $styling['button_font_size'] ?? '1rem' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Вага шрифту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_font_weight]"
							value="<?php echo esc_attr( $styling['button_font_weight'] ?? '600' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Padding', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[button_padding]"
							value="<?php echo esc_attr( $styling['button_padding'] ?? '0.75rem 2rem' ); ?>"
							class="regular-text"
						/>
					</div>
				</div>
			</div>

			<!-- Labels Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Стилі міток (Labels)', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__grid">
					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір тексту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[label_color]"
							value="<?php echo esc_attr( $styling['label_color'] ?? '#111827' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Розмір шрифту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[label_font_size]"
							value="<?php echo esc_attr( $styling['label_font_size'] ?? '0.875rem' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Вага шрифту', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[label_font_weight]"
							value="<?php echo esc_attr( $styling['label_font_weight'] ?? '600' ); ?>"
							class="regular-text"
						/>
					</div>
				</div>
			</div>

			<!-- Dark Theme Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Темна тема', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__grid">
					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір фону полів', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[dark_field_bg_color]"
							value="<?php echo esc_attr( $styling['dark_field_bg_color'] ?? '#1f2937' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір бордера полів', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[dark_field_border_color]"
							value="<?php echo esc_attr( $styling['dark_field_border_color'] ?? 'rgba(255, 255, 255, 0.2)' ); ?>"
							class="regular-text"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір тексту полів', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[dark_field_text_color]"
							value="<?php echo esc_attr( $styling['dark_field_text_color'] ?? '#f9fafb' ); ?>"
							class="medici-color-picker"
						/>
					</div>

					<div class="medici-forms-advanced__field">
						<label class="medici-forms-advanced__field-label">
							<?php esc_html_e( 'Колір міток (labels)', 'medici' ); ?>
						</label>
						<input
							type="text"
							name="styling[dark_label_color]"
							value="<?php echo esc_attr( $styling['dark_label_color'] ?? '#f9fafb' ); ?>"
							class="medici-color-picker"
						/>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Anti-Bot tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_antibot(): void {
		$antibot = $this->settings->get_group( 'antibot' );

		?>
		<div id="medici-tab-antibot" class="medici-forms-advanced__tab-content">
			<!-- Enable/Disable Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Загальні налаштування', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="antibot[enabled]"
							value="1"
							<?php checked( $antibot['enabled'] ?? true ); ?>
						/>
						<?php esc_html_e( 'Увімкнути анти-бот захист', 'medici' ); ?>
					</label>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Захист форм від спаму та ботів за допомогою Cloudflare Turnstile або Google reCAPTCHA', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Провайдер захисту', 'medici' ); ?>
					</label>
					<select name="antibot[provider]">
						<option value="none" <?php selected( $antibot['provider'] ?? 'turnstile', 'none' ); ?>>
							<?php esc_html_e( 'Вимкнено', 'medici' ); ?>
						</option>
						<option value="turnstile" <?php selected( $antibot['provider'] ?? 'turnstile', 'turnstile' ); ?>>
							<?php esc_html_e( 'Cloudflare Turnstile (рекомендовано)', 'medici' ); ?>
						</option>
						<option value="recaptcha" <?php selected( $antibot['provider'] ?? 'turnstile', 'recaptcha' ); ?>>
							<?php esc_html_e( 'Google reCAPTCHA', 'medici' ); ?>
						</option>
					</select>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Cloudflare Turnstile безкоштовний та більш privacy-friendly ніж reCAPTCHA', 'medici' ); ?>
					</span>
				</div>
			</div>

			<!-- Cloudflare Turnstile Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title">
					<?php esc_html_e( 'Cloudflare Turnstile', 'medici' ); ?>
				</h2>

				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--info">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Cloudflare dashboard URL */
							__( 'Отримайте безкоштовні API ключі на <a href="%s" target="_blank">Cloudflare Dashboard</a>', 'medici' ),
							'https://dash.cloudflare.com/?to=/:account/turnstile'
						)
					);
					?>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Site Key', 'medici' ); ?>
					</label>
					<input
						type="text"
						name="antibot[turnstile_site_key]"
						value="<?php echo esc_attr( $antibot['turnstile_site_key'] ?? '' ); ?>"
						class="regular-text"
						placeholder="0x4AAAAAAA..."
					/>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Публічний ключ для відображення challenge на сайті', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Secret Key', 'medici' ); ?>
					</label>
					<input
						type="password"
						name="antibot[turnstile_secret_key]"
						value="<?php echo esc_attr( $antibot['turnstile_secret_key'] ?? '' ); ?>"
						class="regular-text"
						placeholder="0x4AAAAAAA..."
					/>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Приватний ключ для верифікації на сервері (тримайте в секреті!)', 'medici' ); ?>
					</span>
				</div>
			</div>

			<!-- Google reCAPTCHA Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title">
					<?php esc_html_e( 'Google reCAPTCHA', 'medici' ); ?>
				</h2>

				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--info">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Google reCAPTCHA admin URL */
							__( 'Зареєструйте сайт на <a href="%s" target="_blank">Google reCAPTCHA Admin</a>', 'medici' ),
							'https://www.google.com/recaptcha/admin/create'
						)
					);
					?>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Тип reCAPTCHA', 'medici' ); ?>
					</label>
					<select name="antibot[recaptcha_type]">
						<option value="v2" <?php selected( $antibot['recaptcha_type'] ?? 'v2', 'v2' ); ?>>
							<?php esc_html_e( 'v2 Checkbox', 'medici' ); ?>
						</option>
						<option value="v3" <?php selected( $antibot['recaptcha_type'] ?? 'v2', 'v3' ); ?>>
							<?php esc_html_e( 'v3 Invisible', 'medici' ); ?>
						</option>
					</select>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'v2 показує checkbox "I\'m not a robot", v3 працює непомітно у фоні', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Site Key', 'medici' ); ?>
					</label>
					<input
						type="text"
						name="antibot[recaptcha_site_key]"
						value="<?php echo esc_attr( $antibot['recaptcha_site_key'] ?? '' ); ?>"
						class="regular-text"
						placeholder="6Le..."
					/>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Публічний ключ для відображення reCAPTCHA на сайті', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Secret Key', 'medici' ); ?>
					</label>
					<input
						type="password"
						name="antibot[recaptcha_secret_key]"
						value="<?php echo esc_attr( $antibot['recaptcha_secret_key'] ?? '' ); ?>"
						class="regular-text"
						placeholder="6Le..."
					/>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Приватний ключ для верифікації на сервері (тримайте в секреті!)', 'medici' ); ?>
					</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Email tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_email(): void {
		$email = $this->settings->get_group( 'email' );

		?>
		<div id="medici-tab-email" class="medici-forms-advanced__tab-content">
			<!-- Custom Templates Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Кастомні шаблони листів', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="email[custom_templates]"
							value="1"
							<?php checked( $email['custom_templates'] ?? false ); ?>
						/>
						<?php esc_html_e( 'Використовувати кастомні HTML шаблони', 'medici' ); ?>
					</label>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Професійні HTML email шаблони замість стандартних WordPress листів', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Стиль шаблону', 'medici' ); ?>
					</label>
					<select name="email[template_style]">
						<option value="modern" <?php selected( $email['template_style'] ?? 'modern', 'modern' ); ?>>
							<?php esc_html_e( 'Modern (сучасний дизайн)', 'medici' ); ?>
						</option>
						<option value="classic" <?php selected( $email['template_style'] ?? 'modern', 'classic' ); ?>>
							<?php esc_html_e( 'Classic (класичний стиль)', 'medici' ); ?>
						</option>
						<option value="minimal" <?php selected( $email['template_style'] ?? 'modern', 'minimal' ); ?>>
							<?php esc_html_e( 'Minimal (мінімалістичний)', 'medici' ); ?>
						</option>
					</select>
				</div>
			</div>

			<!-- Branding Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Брендинг', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'URL логотипу компанії', 'medici' ); ?>
					</label>
					<input
						type="url"
						name="email[logo_url]"
						value="<?php echo esc_url( $email['logo_url'] ?? '' ); ?>"
						class="regular-text"
						placeholder="https://example.com/logo.png"
					/>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Логотип відображатиметься у верхній частині email листів', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Текст футера', 'medici' ); ?>
					</label>
					<textarea
						name="email[footer_text]"
						rows="3"
						class="large-text"
						placeholder="© 2024 Medici Agency. All rights reserved."
					><?php echo esc_textarea( $email['footer_text'] ?? '' ); ?></textarea>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Текст який відображається внизу кожного email листа. HTML дозволено.', 'medici' ); ?>
					</span>
				</div>
			</div>

			<!-- SMTP Info Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'SMTP налаштування', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--info">
					<?php
					echo wp_kses_post(
						__( 'SMTP налаштування знаходяться в файлі <code>inc/smtp-config.php</code>. Поточний SMTP сервер: <strong>mail.adm.tools:465</strong> (SSL)', 'medici' )
					);
					?>
				</div>

				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Test email URL */
							__( 'Для тестування SMTP відправки: <a href="%s" target="_blank">Відправити тестовий email</a>', 'medici' ),
							admin_url( '?test_smtp=1' )
						)
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render File Upload tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_file_upload(): void {
		$file_upload = $this->settings->get_group( 'file_upload' );

		?>
		<div id="medici-tab-file_upload" class="medici-forms-advanced__tab-content">
			<!-- Enable/Disable Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Загальні налаштування', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[enabled]"
							value="1"
							<?php checked( $file_upload['enabled'] ?? true ); ?>
						/>
						<?php esc_html_e( 'Дозволити завантаження файлів у формах', 'medici' ); ?>
					</label>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Користувачі зможуть прикріплювати файли до форм (CV, портфоліо, медичні документи тощо)', 'medici' ); ?>
					</span>
				</div>
			</div>

			<!-- Size Limits Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Обмеження розміру', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Максимальний розмір файлу (MB)', 'medici' ); ?>
					</label>
					<input
						type="number"
						name="file_upload[max_size]"
						value="<?php echo esc_attr( $file_upload['max_size'] ?? 5 ); ?>"
						min="1"
						max="50"
						class="small-text"
					/>
					<span class="medici-forms-advanced__field-description">
						<?php
						$server_max = ini_get( 'upload_max_filesize' );
						echo esc_html(
							sprintf(
								/* translators: %s: Server max upload size */
								__( 'Максимум дозволений сервером: %s', 'medici' ),
								$server_max
							)
						);
						?>
					</span>
				</div>
			</div>

			<!-- Allowed File Types Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Дозволені типи файлів', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[allowed_types][]"
							value="pdf"
							<?php checked( in_array( 'pdf', $file_upload['allowed_types'] ?? [ 'pdf', 'doc', 'docx' ], true ) ); ?>
						/>
						<?php esc_html_e( 'PDF документи (.pdf)', 'medici' ); ?>
					</label>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[allowed_types][]"
							value="doc"
							<?php checked( in_array( 'doc', $file_upload['allowed_types'] ?? [ 'pdf', 'doc', 'docx' ], true ) ); ?>
						/>
						<?php esc_html_e( 'Word документи (.doc)', 'medici' ); ?>
					</label>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[allowed_types][]"
							value="docx"
							<?php checked( in_array( 'docx', $file_upload['allowed_types'] ?? [ 'pdf', 'doc', 'docx' ], true ) ); ?>
						/>
						<?php esc_html_e( 'Word документи (.docx)', 'medici' ); ?>
					</label>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[allowed_types][]"
							value="jpg"
							<?php checked( in_array( 'jpg', $file_upload['allowed_types'] ?? [], true ) ); ?>
						/>
						<?php esc_html_e( 'JPEG зображення (.jpg, .jpeg)', 'medici' ); ?>
					</label>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="file_upload[allowed_types][]"
							value="png"
							<?php checked( in_array( 'png', $file_upload['allowed_types'] ?? [], true ) ); ?>
						/>
						<?php esc_html_e( 'PNG зображення (.png)', 'medici' ); ?>
					</label>
				</div>
			</div>

			<!-- Upload Path Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Шлях збереження', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Папка для завантажень', 'medici' ); ?>
					</label>
					<input
						type="text"
						name="file_upload[upload_path]"
						value="<?php echo esc_attr( $file_upload['upload_path'] ?? 'wpforms-uploads' ); ?>"
						class="regular-text"
					/>
					<span class="medici-forms-advanced__field-description">
						<?php
						$upload_dir = wp_upload_dir();
						echo esc_html(
							sprintf(
								/* translators: %s: Upload base directory */
								__( 'Відносно wp-content/uploads/. Повний шлях: %s/[папка]', 'medici' ),
								$upload_dir['basedir']
							)
						);
						?>
					</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Advanced tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_advanced(): void {
		$advanced = $this->settings->get_group( 'advanced' );

		?>
		<div id="medici-tab-advanced" class="medici-forms-advanced__tab-content">
			<!-- Custom CSS Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Власний CSS', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Custom CSS код', 'medici' ); ?>
					</label>
					<textarea
						name="advanced[custom_css]"
						rows="15"
						class="large-text code"
						style="font-family: monospace; font-size: 13px;"
						placeholder="/* Ваш кастомний CSS тут */"
					><?php echo esc_textarea( $advanced['custom_css'] ?? '' ); ?></textarea>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'CSS код буде додано до всіх форм на сайті. Не потрібні <style> теги.', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__notice medici-forms-advanced__notice--info">
					<strong><?php esc_html_e( 'Приклад:', 'medici' ); ?></strong>
					<pre style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-left: 3px solid #2563eb;">
.wpforms-form .wpforms-field-label {
    color: #1a1a1a;
    font-weight: 600;
}

.wpforms-form button[type="submit"] {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}</pre>
				</div>
			</div>

			<!-- Performance Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Продуктивність', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="advanced[load_css]"
							value="1"
							<?php checked( $advanced['load_css'] ?? true ); ?>
						/>
						<?php esc_html_e( 'Завантажувати CSS стилі модуля', 'medici' ); ?>
					</label>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Вимкніть якщо хочете використовувати тільки власні стилі', 'medici' ); ?>
					</span>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<input
							type="checkbox"
							name="advanced[load_js]"
							value="1"
							<?php checked( $advanced['load_js'] ?? true ); ?>
						/>
						<?php esc_html_e( 'Завантажувати JavaScript модуля', 'medici' ); ?>
					</label>
					<span class="medici-forms-advanced__field-description">
						<?php esc_html_e( 'Потрібно для роботи анті-бот захисту', 'medici' ); ?>
					</span>
				</div>
			</div>

			<!-- Debug Section -->
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Налагодження', 'medici' ); ?></h2>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'Поточна версія модуля', 'medici' ); ?>
					</label>
					<input
						type="text"
						value="<?php echo esc_attr( Medici_Forms_Advanced::VERSION ); ?>"
						class="regular-text"
						readonly
					/>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'WPForms версія', 'medici' ); ?>
					</label>
					<input
						type="text"
						value="<?php echo esc_attr( defined( 'WPFORMS_VERSION' ) ? WPFORMS_VERSION : 'Not installed' ); ?>"
						class="regular-text"
						readonly
					/>
				</div>

				<div class="medici-forms-advanced__field">
					<label class="medici-forms-advanced__field-label">
						<?php esc_html_e( 'PHP версія', 'medici' ); ?>
					</label>
					<input
						type="text"
						value="<?php echo esc_attr( PHP_VERSION ); ?>"
						class="regular-text"
						readonly
					/>
				</div>
			</div>
		</div>
		<?php
	}
}

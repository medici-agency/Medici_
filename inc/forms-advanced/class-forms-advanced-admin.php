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
	 * Render Anti-Bot tab (placeholder - буде розширено)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_antibot(): void {
		?>
		<div id="medici-tab-antibot" class="medici-forms-advanced__tab-content">
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Налаштування Anti-Bot', 'medici' ); ?></h2>
				<p><?php esc_html_e( 'Cloudflare Turnstile, reCAPTCHA...', 'medici' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Email tab (placeholder - буде розширено)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_email(): void {
		?>
		<div id="medici-tab-email" class="medici-forms-advanced__tab-content">
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Налаштування Email', 'medici' ); ?></h2>
				<p><?php esc_html_e( 'Кастомні шаблони, логотип...', 'medici' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render File Upload tab (placeholder - буде розширено)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_file_upload(): void {
		?>
		<div id="medici-tab-file_upload" class="medici-forms-advanced__tab-content">
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Налаштування File Upload', 'medici' ); ?></h2>
				<p><?php esc_html_e( 'Максимальний розмір, дозволені типи файлів...', 'medici' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Advanced tab (placeholder - буде розширено)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function render_tab_advanced(): void {
		?>
		<div id="medici-tab-advanced" class="medici-forms-advanced__tab-content">
			<div class="medici-forms-advanced__section">
				<h2 class="medici-forms-advanced__section-title"><?php esc_html_e( 'Розширені налаштування', 'medici' ); ?></h2>
				<p><?php esc_html_e( 'Custom CSS, налаштування завантаження скриптів...', 'medici' ); ?></p>
			</div>
		</div>
		<?php
	}
}

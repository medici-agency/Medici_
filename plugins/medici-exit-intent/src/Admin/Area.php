<?php
/**
 * Admin Area Class
 *
 * Handles all admin UI functionality.
 * Based on WP Mail SMTP Admin/Area.php architecture.
 *
 * @package Jexi\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi\Admin;

use Jexi\Assets;
use Jexi\Options;

/**
 * Area Class
 *
 * Manages admin pages, settings, and AJAX handlers.
 */
final class Area {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	public const SLUG = 'jexi-settings';

	/**
	 * Settings tabs.
	 *
	 * @var array<string, string>
	 */
	private array $tabs = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tabs = array(
			'general'      => __( 'General', 'medici-exit-intent' ),
			'design'       => __( 'Design', 'medici-exit-intent' ),
			'content'      => __( 'Content', 'medici-exit-intent' ),
			'form'         => __( 'Form', 'medici-exit-intent' ),
			'twemoji'      => __( 'Twemoji', 'medici-exit-intent' ),
			'integrations' => __( 'Integrations', 'medici-exit-intent' ),
			'analytics'    => __( 'Analytics', 'medici-exit-intent' ),
			'advanced'     => __( 'Advanced', 'medici-exit-intent' ),
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @since 1.0.0
	 */
	public function register_menu(): void {
		// Main menu page.
		add_menu_page(
			__( 'Exit-Intent Pro', 'medici-exit-intent' ),
			__( 'Exit-Intent', 'medici-exit-intent' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_settings_page' ),
			'dashicons-megaphone',
			30
		);

		// Settings submenu.
		add_submenu_page(
			self::SLUG,
			__( 'Settings', 'medici-exit-intent' ),
			__( 'Settings', 'medici-exit-intent' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_settings_page' )
		);

		// Analytics submenu.
		add_submenu_page(
			self::SLUG,
			__( 'Analytics', 'medici-exit-intent' ),
			__( 'Analytics', 'medici-exit-intent' ),
			'manage_options',
			'jexi-analytics',
			array( $this, 'render_analytics_page' )
		);

		// Templates submenu.
		add_submenu_page(
			self::SLUG,
			__( 'Templates', 'medici-exit-intent' ),
			__( 'Templates', 'medici-exit-intent' ),
			'manage_options',
			'jexi-templates',
			array( $this, 'render_templates_page' )
		);

		// Debug submenu (only if WP_DEBUG).
		if ( jexi()->is_debug() ) {
			add_submenu_page(
				self::SLUG,
				__( 'Debug Log', 'medici-exit-intent' ),
				__( 'Debug', 'medici-exit-intent' ),
				'manage_options',
				'jexi-debug',
				array( $this, 'render_debug_page' )
			);
		}
	}

	/**
	 * Register settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings(): void {
		register_setting(
			'jexi_settings',
			Options::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// General section.
		add_settings_section(
			'jexi_general',
			__( 'General Settings', 'medici-exit-intent' ),
			array( $this, 'render_general_section' ),
			self::SLUG . '_general'
		);

		// Design section.
		add_settings_section(
			'jexi_design',
			__( 'Design Settings', 'medici-exit-intent' ),
			array( $this, 'render_design_section' ),
			self::SLUG . '_design'
		);

		// Content section.
		add_settings_section(
			'jexi_content',
			__( 'Content Settings', 'medici-exit-intent' ),
			array( $this, 'render_content_section' ),
			self::SLUG . '_content'
		);

		// Form section.
		add_settings_section(
			'jexi_form',
			__( 'Form Settings', 'medici-exit-intent' ),
			array( $this, 'render_form_section' ),
			self::SLUG . '_form'
		);

		// Twemoji section.
		add_settings_section(
			'jexi_twemoji',
			__( 'Twemoji Settings', 'medici-exit-intent' ),
			array( $this, 'render_twemoji_section' ),
			self::SLUG . '_twemoji'
		);

		// Integrations section.
		add_settings_section(
			'jexi_integrations',
			__( 'Integration Settings', 'medici-exit-intent' ),
			array( $this, 'render_integrations_section' ),
			self::SLUG . '_integrations'
		);

		// Analytics section.
		add_settings_section(
			'jexi_analytics',
			__( 'Analytics Settings', 'medici-exit-intent' ),
			array( $this, 'render_analytics_section' ),
			self::SLUG . '_analytics'
		);

		// Advanced section.
		add_settings_section(
			'jexi_advanced',
			__( 'Advanced Settings', 'medici-exit-intent' ),
			array( $this, 'render_advanced_section' ),
			self::SLUG . '_advanced'
		);
	}

	/**
	 * Sanitize settings callback.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		// Delegate to Options class.
		return $input;
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $links Existing links.
	 * @return array<string, string>
	 */
	public function add_action_links( array $links ): array {
		$plugin_links = array(
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=' . self::SLUG ),
				__( 'Settings', 'medici-exit-intent' )
			),
			'docs'     => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://medici.agency/docs/exit-intent',
				__( 'Docs', 'medici-exit-intent' )
			),
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$options     = jexi()->get_options();
		?>
		<div class="wrap jexi-settings">
			<h1 class="jexi-settings__title">
				<span class="jexi-logo">🎯</span>
				<?php esc_html_e( 'Medici Exit-Intent Pro', 'medici-exit-intent' ); ?>
				<span class="jexi-version"><?php echo esc_html( JEXI_VERSION ); ?></span>
			</h1>

			<?php settings_errors( 'jexi_messages' ); ?>

			<nav class="jexi-tabs nav-tab-wrapper">
				<?php foreach ( $this->tabs as $tab_id => $tab_name ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $tab_id ) ); ?>"
					   class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_name ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="jexi-form">
				<?php
				settings_fields( 'jexi_settings' );

				switch ( $current_tab ) {
					case 'design':
						$this->render_design_fields( $options );
						break;
					case 'content':
						$this->render_content_fields( $options );
						break;
					case 'form':
						$this->render_form_fields( $options );
						break;
					case 'twemoji':
						$this->render_twemoji_fields( $options );
						break;
					case 'integrations':
						$this->render_integrations_fields( $options );
						break;
					case 'analytics':
						$this->render_analytics_fields( $options );
						break;
					case 'advanced':
						$this->render_advanced_fields( $options );
						break;
					default:
						$this->render_general_fields( $options );
						break;
				}

				submit_button( __( 'Save Settings', 'medici-exit-intent' ) );
				?>
			</form>

			<div class="jexi-sidebar">
				<?php $this->render_sidebar(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render general settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_general_fields( \Jexi\Options $options ): void {
		$general = $options->get( 'general' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_enabled"><?php esc_html_e( 'Enable Exit-Intent', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_enabled" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][enabled]" value="1" <?php checked( $general['enabled'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Enable or disable the exit-intent popup globally.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_trigger_type"><?php esc_html_e( 'Trigger Type', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_trigger_type" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][trigger_type]">
						<?php foreach ( Assets::get_trigger_types() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $general['trigger_type'] ?? 'exit', $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'When to show the popup.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_cookie_days"><?php esc_html_e( 'Cookie Duration (Days)', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_cookie_days" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][cookie_days]" value="<?php echo esc_attr( (string) ( $general['cookie_days'] ?? 30 ) ); ?>" min="1" max="365" class="small-text">
					<p class="description"><?php esc_html_e( 'How long to remember that user has seen the popup.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_delay_seconds"><?php esc_html_e( 'Delay (Seconds)', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_delay_seconds" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][delay_seconds]" value="<?php echo esc_attr( (string) ( $general['delay_seconds'] ?? 2 ) ); ?>" min="0" max="60" class="small-text">
					<p class="description"><?php esc_html_e( 'Minimum time on page before popup can appear.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_min_screen_width"><?php esc_html_e( 'Min Screen Width', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_min_screen_width" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][min_screen_width]" value="<?php echo esc_attr( (string) ( $general['min_screen_width'] ?? 1024 ) ); ?>" min="0" max="2560" class="small-text">
					<span>px</span>
					<p class="description"><?php esc_html_e( 'Popup will only show on screens wider than this. Set to 0 for all screens.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr class="jexi-trigger-option" data-trigger="scroll">
				<th scope="row">
					<label for="jexi_scroll_percent"><?php esc_html_e( 'Scroll Percentage', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_scroll_percent" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][scroll_percent]" value="<?php echo esc_attr( (string) ( $general['scroll_percent'] ?? 50 ) ); ?>" min="1" max="100" class="small-text">
					<span>%</span>
					<p class="description"><?php esc_html_e( 'Show popup after scrolling this percentage of the page.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr class="jexi-trigger-option" data-trigger="time">
				<th scope="row">
					<label for="jexi_time_seconds"><?php esc_html_e( 'Time on Page', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_time_seconds" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][time_seconds]" value="<?php echo esc_attr( (string) ( $general['time_seconds'] ?? 30 ) ); ?>" min="1" max="600" class="small-text">
					<span><?php esc_html_e( 'seconds', 'medici-exit-intent' ); ?></span>
					<p class="description"><?php esc_html_e( 'Show popup after this many seconds on the page.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr class="jexi-trigger-option" data-trigger="inactive">
				<th scope="row">
					<label for="jexi_inactive_seconds"><?php esc_html_e( 'Inactivity Timeout', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_inactive_seconds" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][inactive_seconds]" value="<?php echo esc_attr( (string) ( $general['inactive_seconds'] ?? 15 ) ); ?>" min="1" max="120" class="small-text">
					<span><?php esc_html_e( 'seconds', 'medici-exit-intent' ); ?></span>
					<p class="description"><?php esc_html_e( 'Show popup after user is inactive for this long.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_debug"><?php esc_html_e( 'Debug Mode', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_debug" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[general][debug]" value="1" <?php checked( $general['debug'] ?? false ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Enable debug logging (also enabled when WP_DEBUG is true).', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render design settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_design_fields( \Jexi\Options $options ): void {
		$design = $options->get( 'design' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_template"><?php esc_html_e( 'Template', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_template" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][template]">
						<option value="modern" <?php selected( $design['template'] ?? 'modern', 'modern' ); ?>><?php esc_html_e( 'Modern', 'medici-exit-intent' ); ?></option>
						<option value="minimal" <?php selected( $design['template'] ?? 'modern', 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'medici-exit-intent' ); ?></option>
						<option value="bold" <?php selected( $design['template'] ?? 'modern', 'bold' ); ?>><?php esc_html_e( 'Bold', 'medici-exit-intent' ); ?></option>
						<option value="playful" <?php selected( $design['template'] ?? 'modern', 'playful' ); ?>><?php esc_html_e( 'Playful', 'medici-exit-intent' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_icon"><?php esc_html_e( 'Icon (Emoji)', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_icon" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][icon]" value="<?php echo esc_attr( $design['icon'] ?? '👋' ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Enter an emoji to display at the top of the popup.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_icon_type"><?php esc_html_e( 'Icon Type', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_icon_type" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][icon_type]">
						<option value="twemoji" <?php selected( $design['icon_type'] ?? 'twemoji', 'twemoji' ); ?>><?php esc_html_e( 'Twemoji (SVG)', 'medici-exit-intent' ); ?></option>
						<option value="native" <?php selected( $design['icon_type'] ?? 'twemoji', 'native' ); ?>><?php esc_html_e( 'Native Emoji', 'medici-exit-intent' ); ?></option>
						<option value="custom" <?php selected( $design['icon_type'] ?? 'twemoji', 'custom' ); ?>><?php esc_html_e( 'Custom Image', 'medici-exit-intent' ); ?></option>
						<option value="none" <?php selected( $design['icon_type'] ?? 'twemoji', 'none' ); ?>><?php esc_html_e( 'None', 'medici-exit-intent' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_animation"><?php esc_html_e( 'Animation', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_animation" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][animation]">
						<?php foreach ( Assets::get_animations() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $design['animation'] ?? 'scale', $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_position"><?php esc_html_e( 'Position', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_position" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][position]">
						<?php foreach ( Assets::get_positions() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $design['position'] ?? 'center', $value ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Colors', 'medici-exit-intent' ); ?></th>
				<td>
					<fieldset>
						<p>
							<label for="jexi_primary_color"><?php esc_html_e( 'Primary', 'medici-exit-intent' ); ?></label>
							<input type="text" id="jexi_primary_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][primary_color]" value="<?php echo esc_attr( $design['primary_color'] ?? '#2563eb' ); ?>" class="jexi-color-picker">
						</p>
						<p>
							<label for="jexi_text_color"><?php esc_html_e( 'Text', 'medici-exit-intent' ); ?></label>
							<input type="text" id="jexi_text_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][text_color]" value="<?php echo esc_attr( $design['text_color'] ?? '#1f2937' ); ?>" class="jexi-color-picker">
						</p>
						<p>
							<label for="jexi_background_color"><?php esc_html_e( 'Background', 'medici-exit-intent' ); ?></label>
							<input type="text" id="jexi_background_color" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][background_color]" value="<?php echo esc_attr( $design['background_color'] ?? '#ffffff' ); ?>" class="jexi-color-picker">
						</p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_backdrop_blur"><?php esc_html_e( 'Backdrop Blur', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_backdrop_blur" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][backdrop_blur]" value="1" <?php checked( $design['backdrop_blur'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Apply blur effect to the backdrop.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_dark_mode"><?php esc_html_e( 'Dark Mode Support', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_dark_mode" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[design][dark_mode]" value="1" <?php checked( $design['dark_mode'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Automatically adapt colors for dark mode.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render content settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_content_fields( \Jexi\Options $options ): void {
		$content = $options->get( 'content' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_title"><?php esc_html_e( 'Title', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_title" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][title]" value="<?php echo esc_attr( $content['title'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_subtitle"><?php esc_html_e( 'Subtitle', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<textarea id="jexi_subtitle" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][subtitle]" rows="3" class="large-text"><?php echo esc_textarea( $content['subtitle'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'HTML allowed: <strong>, <em>, <br>.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_button_text"><?php esc_html_e( 'Button Text', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_button_text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][button_text]" value="<?php echo esc_attr( $content['button_text'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_decline_text"><?php esc_html_e( 'Decline Text', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_decline_text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][decline_text]" value="<?php echo esc_attr( $content['decline_text'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_success_message"><?php esc_html_e( 'Success Message', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_success_message" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][success_message]" value="<?php echo esc_attr( $content['success_message'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_error_message"><?php esc_html_e( 'Error Message', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_error_message" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[content][error_message]" value="<?php echo esc_attr( $content['error_message'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render form settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_form_fields( \Jexi\Options $options ): void {
		$form = $options->get( 'form' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Form Fields', 'medici-exit-intent' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][show_name]" value="1" <?php checked( $form['show_name'] ?? true ); ?>>
							<?php esc_html_e( 'Name field', 'medici-exit-intent' ); ?>
						</label>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][require_name]" value="1" <?php checked( $form['require_name'] ?? false ); ?>>
							<?php esc_html_e( '(required)', 'medici-exit-intent' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][show_email]" value="1" <?php checked( $form['show_email'] ?? true ); ?>>
							<?php esc_html_e( 'Email field', 'medici-exit-intent' ); ?>
						</label>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][require_email]" value="1" <?php checked( $form['require_email'] ?? true ); ?>>
							<?php esc_html_e( '(required)', 'medici-exit-intent' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][show_phone]" value="1" <?php checked( $form['show_phone'] ?? true ); ?>>
							<?php esc_html_e( 'Phone field', 'medici-exit-intent' ); ?>
						</label>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][require_phone]" value="1" <?php checked( $form['require_phone'] ?? false ); ?>>
							<?php esc_html_e( '(required)', 'medici-exit-intent' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_require_consent"><?php esc_html_e( 'Require Consent', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_require_consent" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][require_consent]" value="1" <?php checked( $form['require_consent'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_consent_text"><?php esc_html_e( 'Consent Text', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_consent_text" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][consent_text]" value="<?php echo esc_attr( $form['consent_text'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_honeypot"><?php esc_html_e( 'Honeypot Anti-Spam', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_honeypot" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][honeypot]" value="1" <?php checked( $form['honeypot'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Add invisible field to detect bots.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_recaptcha_enabled"><?php esc_html_e( 'Google reCAPTCHA', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_recaptcha_enabled" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][recaptcha_enabled]" value="1" <?php checked( $form['recaptcha_enabled'] ?? false ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr class="jexi-recaptcha-fields">
				<th scope="row">
					<label for="jexi_recaptcha_site_key"><?php esc_html_e( 'Site Key', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="text" id="jexi_recaptcha_site_key" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][recaptcha_site_key]" value="<?php echo esc_attr( $form['recaptcha_site_key'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr class="jexi-recaptcha-fields">
				<th scope="row">
					<label for="jexi_recaptcha_secret"><?php esc_html_e( 'Secret Key', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="password" id="jexi_recaptcha_secret" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[form][recaptcha_secret]" value="<?php echo esc_attr( $form['recaptcha_secret'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Twemoji settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_twemoji_fields( \Jexi\Options $options ): void {
		$twemoji = $options->get( 'twemoji' );
		?>
		<div class="jexi-notice jexi-notice--info">
			<p>
				<strong><?php esc_html_e( 'About Twemoji', 'medici-exit-intent' ); ?></strong><br>
				<?php esc_html_e( 'Twemoji provides consistent, high-quality emoji across all browsers and devices. Developed by Twitter.', 'medici-exit-intent' ); ?>
			</p>
		</div>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_twemoji_enabled"><?php esc_html_e( 'Enable Twemoji', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_twemoji_enabled" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[twemoji][enabled]" value="1" <?php checked( $twemoji['enabled'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Replace native emoji with Twemoji graphics.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_twemoji_size"><?php esc_html_e( 'Size', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_twemoji_size" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[twemoji][size]">
						<option value="72x72" <?php selected( $twemoji['size'] ?? '72x72', '72x72' ); ?>>72x72 (Retina)</option>
						<option value="36x36" <?php selected( $twemoji['size'] ?? '72x72', '36x36' ); ?>>36x36 (Standard)</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_twemoji_style"><?php esc_html_e( 'Format', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_twemoji_style" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[twemoji][style]">
						<option value="svg" <?php selected( $twemoji['style'] ?? 'svg', 'svg' ); ?>><?php esc_html_e( 'SVG (Recommended)', 'medici-exit-intent' ); ?></option>
						<option value="png" <?php selected( $twemoji['style'] ?? 'svg', 'png' ); ?>><?php esc_html_e( 'PNG', 'medici-exit-intent' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'SVG provides better quality at any size.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_twemoji_lazy"><?php esc_html_e( 'Lazy Loading', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_twemoji_lazy" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[twemoji][lazy_load]" value="1" <?php checked( $twemoji['lazy_load'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_twemoji_base_url"><?php esc_html_e( 'Custom CDN URL', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="url" id="jexi_twemoji_base_url" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[twemoji][base_url]" value="<?php echo esc_url( $twemoji['base_url'] ?? '' ); ?>" class="large-text">
					<p class="description"><?php esc_html_e( 'Leave empty to use default CDN (jsDelivr).', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Emoji Preview', 'medici-exit-intent' ); ?></h3>
		<div class="jexi-emoji-preview">
			<span class="jexi-emoji-preview__item">👋</span>
			<span class="jexi-emoji-preview__item">🎯</span>
			<span class="jexi-emoji-preview__item">🚀</span>
			<span class="jexi-emoji-preview__item">💡</span>
			<span class="jexi-emoji-preview__item">✨</span>
			<span class="jexi-emoji-preview__item">🔥</span>
			<span class="jexi-emoji-preview__item">💰</span>
			<span class="jexi-emoji-preview__item">🎁</span>
		</div>
		<?php
	}

	/**
	 * Render integrations settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_integrations_fields( \Jexi\Options $options ): void {
		$integrations = $options->get( 'integrations' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_lead_cpt"><?php esc_html_e( 'Save to Lead CPT', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_lead_cpt" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[integrations][lead_cpt]" value="1" <?php checked( $integrations['lead_cpt'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Create Lead custom post type entries for each submission.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_events_api"><?php esc_html_e( 'Use Medici Events API', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_events_api" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[integrations][events_api]" value="1" <?php checked( $integrations['events_api'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Integrate with theme Events API for form handling.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_email_provider"><?php esc_html_e( 'Email Provider', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<select id="jexi_email_provider" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[integrations][email_provider]">
						<option value="none" <?php selected( $integrations['email_provider'] ?? 'none', 'none' ); ?>><?php esc_html_e( 'None', 'medici-exit-intent' ); ?></option>
						<option value="mailchimp" <?php selected( $integrations['email_provider'] ?? 'none', 'mailchimp' ); ?>><?php esc_html_e( 'Mailchimp', 'medici-exit-intent' ); ?></option>
						<option value="sendgrid" <?php selected( $integrations['email_provider'] ?? 'none', 'sendgrid' ); ?>><?php esc_html_e( 'SendGrid', 'medici-exit-intent' ); ?></option>
						<option value="convertkit" <?php selected( $integrations['email_provider'] ?? 'none', 'convertkit' ); ?>><?php esc_html_e( 'ConvertKit', 'medici-exit-intent' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_webhook_url"><?php esc_html_e( 'Webhook URL', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="url" id="jexi_webhook_url" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[integrations][webhook_url]" value="<?php echo esc_url( $integrations['webhook_url'] ?? '' ); ?>" class="large-text">
					<p class="description"><?php esc_html_e( 'Send form data to this URL via POST request.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_zapier_webhook"><?php esc_html_e( 'Zapier Webhook', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="url" id="jexi_zapier_webhook" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[integrations][zapier_webhook]" value="<?php echo esc_url( $integrations['zapier_webhook'] ?? '' ); ?>" class="large-text">
					<p class="description"><?php esc_html_e( 'Integrate with Zapier for automation workflows.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render analytics settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_analytics_fields( \Jexi\Options $options ): void {
		$analytics = $options->get( 'analytics' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_analytics_enabled"><?php esc_html_e( 'Enable Analytics', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_analytics_enabled" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[analytics][enabled]" value="1" <?php checked( $analytics['enabled'] ?? true ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Track Events', 'medici-exit-intent' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[analytics][track_views]" value="1" <?php checked( $analytics['track_views'] ?? true ); ?>>
							<?php esc_html_e( 'Popup views', 'medici-exit-intent' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[analytics][track_closes]" value="1" <?php checked( $analytics['track_closes'] ?? true ); ?>>
							<?php esc_html_e( 'Popup closes', 'medici-exit-intent' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[analytics][track_submits]" value="1" <?php checked( $analytics['track_submits'] ?? true ); ?>>
							<?php esc_html_e( 'Form submissions', 'medici-exit-intent' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_retention_days"><?php esc_html_e( 'Data Retention', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<input type="number" id="jexi_retention_days" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[analytics][retention_days]" value="<?php echo esc_attr( (string) ( $analytics['retention_days'] ?? 90 ) ); ?>" min="7" max="365" class="small-text">
					<span><?php esc_html_e( 'days', 'medici-exit-intent' ); ?></span>
					<p class="description"><?php esc_html_e( 'Automatically delete analytics data older than this.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render advanced settings fields.
	 *
	 * @since 1.0.0
	 * @param \Jexi\Options $options Options instance.
	 */
	private function render_advanced_fields( \Jexi\Options $options ): void {
		$advanced = $options->get( 'advanced' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="jexi_exclude_urls"><?php esc_html_e( 'Exclude URLs', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<textarea id="jexi_exclude_urls" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[advanced][exclude_urls]" rows="5" class="large-text"><?php echo esc_textarea( $advanced['exclude_urls'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One URL pattern per line. Supports wildcards (*).', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_exclude_users"><?php esc_html_e( 'Exclude User Roles', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<?php
					global $wp_roles;
					foreach ( $wp_roles->roles as $role_key => $role ) :
						$checked = in_array( $role_key, (array) ( $advanced['exclude_users'] ?? array() ), true );
						?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[advanced][exclude_users][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( $checked ); ?>>
							<?php echo esc_html( $role['name'] ); ?>
						</label>
						<br>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_show_on_mobile"><?php esc_html_e( 'Show on Mobile', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<label class="jexi-toggle">
						<input type="checkbox" id="jexi_show_on_mobile" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[advanced][show_on_mobile]" value="1" <?php checked( $advanced['show_on_mobile'] ?? false ); ?>>
						<span class="jexi-toggle__slider"></span>
					</label>
					<p class="description"><?php esc_html_e( 'Show popup on mobile devices (not recommended).', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_custom_css"><?php esc_html_e( 'Custom CSS', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<textarea id="jexi_custom_css" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[advanced][custom_css]" rows="10" class="large-text code"><?php echo esc_textarea( $advanced['custom_css'] ?? '' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="jexi_custom_js"><?php esc_html_e( 'Custom JavaScript', 'medici-exit-intent' ); ?></label>
				</th>
				<td>
					<textarea id="jexi_custom_js" name="<?php echo esc_attr( Options::OPTION_KEY ); ?>[advanced][custom_js]" rows="10" class="large-text code"><?php echo esc_textarea( $advanced['custom_js'] ?? '' ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Executed after popup is loaded. Use with caution.', 'medici-exit-intent' ); ?></p>
				</td>
			</tr>
		</table>

		<hr>
		<h3><?php esc_html_e( 'Tools', 'medici-exit-intent' ); ?></h3>
		<p>
			<button type="button" class="button" id="jexi-export-settings"><?php esc_html_e( 'Export Settings', 'medici-exit-intent' ); ?></button>
			<button type="button" class="button" id="jexi-import-settings"><?php esc_html_e( 'Import Settings', 'medici-exit-intent' ); ?></button>
			<button type="button" class="button button-secondary" id="jexi-reset-settings"><?php esc_html_e( 'Reset to Defaults', 'medici-exit-intent' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Render sidebar.
	 *
	 * @since 1.0.0
	 */
	private function render_sidebar(): void {
		?>
		<div class="jexi-widget">
			<h3><?php esc_html_e( 'Quick Stats', 'medici-exit-intent' ); ?></h3>
			<div class="jexi-stats" id="jexi-quick-stats">
				<p><?php esc_html_e( 'Loading...', 'medici-exit-intent' ); ?></p>
			</div>
		</div>

		<div class="jexi-widget">
			<h3><?php esc_html_e( 'Preview', 'medici-exit-intent' ); ?></h3>
			<button type="button" class="button button-primary" id="jexi-preview-popup">
				<?php esc_html_e( 'Show Preview', 'medici-exit-intent' ); ?>
			</button>
		</div>

		<div class="jexi-widget">
			<h3><?php esc_html_e( 'Need Help?', 'medici-exit-intent' ); ?></h3>
			<ul>
				<li><a href="https://medici.agency/docs/exit-intent" target="_blank"><?php esc_html_e( 'Documentation', 'medici-exit-intent' ); ?></a></li>
				<li><a href="https://medici.agency/support" target="_blank"><?php esc_html_e( 'Support', 'medici-exit-intent' ); ?></a></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render analytics page.
	 *
	 * @since 1.0.0
	 */
	public function render_analytics_page(): void {
		?>
		<div class="wrap jexi-analytics">
			<h1><?php esc_html_e( 'Exit-Intent Analytics', 'medici-exit-intent' ); ?></h1>
			<div id="jexi-analytics-dashboard"></div>
		</div>
		<?php
	}

	/**
	 * Render templates page.
	 *
	 * @since 1.0.0
	 */
	public function render_templates_page(): void {
		?>
		<div class="wrap jexi-templates">
			<h1><?php esc_html_e( 'Popup Templates', 'medici-exit-intent' ); ?></h1>
			<div id="jexi-templates-gallery"></div>
		</div>
		<?php
	}

	/**
	 * Render debug page.
	 *
	 * @since 1.0.0
	 */
	public function render_debug_page(): void {
		$debug = jexi()->get_debug();
		$logs  = $debug->get_logs();
		?>
		<div class="wrap jexi-debug">
			<h1><?php esc_html_e( 'Debug Log', 'medici-exit-intent' ); ?></h1>

			<p>
				<button type="button" class="button" id="jexi-export-log"><?php esc_html_e( 'Export Log', 'medici-exit-intent' ); ?></button>
				<button type="button" class="button" id="jexi-clear-log"><?php esc_html_e( 'Clear Log', 'medici-exit-intent' ); ?></button>
			</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'medici-exit-intent' ); ?></th>
						<th><?php esc_html_e( 'Level', 'medici-exit-intent' ); ?></th>
						<th><?php esc_html_e( 'Message', 'medici-exit-intent' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="3"><?php esc_html_e( 'No log entries.', 'medici-exit-intent' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( array_reverse( $logs ) as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['timestamp'] ); ?></td>
								<td>
									<span class="jexi-log-level jexi-log-level--<?php echo esc_attr( $log['level'] ); ?>">
										<?php echo esc_html( strtoupper( $log['level'] ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render section callbacks (required by Settings API).
	 */
	public function render_general_section(): void {}
	public function render_design_section(): void {}
	public function render_content_section(): void {}
	public function render_form_section(): void {}
	public function render_twemoji_section(): void {}
	public function render_integrations_section(): void {}
	public function render_analytics_section(): void {}
	public function render_advanced_section(): void {}
}

<?php
/**
 * Medici Forms Advanced - Main Plugin Class
 *
 * Розширення для WPForms з додатковими налаштуваннями та можливостями:
 * - Розширені налаштування Layout (ширина форми, полів, кнопок)
 * - Розширені налаштування Styling (кольори, бордери, шрифти)
 * - Anti-Bot захист (Cloudflare Turnstile)
 * - Покращені email шаблони
 * - File Upload підтримка
 * - Контрастні стилі для світлої/темної теми
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
 * Main Medici Forms Advanced Class
 *
 * @since 1.0.0
 */
final class Medici_Forms_Advanced {

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const VERSION = '1.0.0';

	/**
	 * Admin menu slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const MENU_SLUG = 'medici-forms-advanced';

	/**
	 * Option name for settings
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public const OPTION_NAME = 'medici_forms_advanced_settings';

	/**
	 * Single instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced|null
	 */
	private static ?Medici_Forms_Advanced $instance = null;

	/**
	 * Settings instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced_Settings|null
	 */
	private ?Medici_Forms_Advanced_Settings $settings = null;

	/**
	 * Admin instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced_Admin|null
	 */
	private ?Medici_Forms_Advanced_Admin $admin = null;

	/**
	 * Frontend instance
	 *
	 * @since 1.0.0
	 * @var Medici_Forms_Advanced_Frontend|null
	 */
	private ?Medici_Forms_Advanced_Frontend $frontend = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return Medici_Forms_Advanced
	 */
	public static function instance(): Medici_Forms_Advanced {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->check_dependencies();
		$this->load_dependencies();
		$this->init();
	}

	/**
	 * Check if WPForms is active
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function check_dependencies(): bool {
		if ( ! function_exists( 'wpforms' ) ) {
			add_action( 'admin_notices', [ $this, 'wpforms_missing_notice' ] );
			return false;
		}

		return true;
	}

	/**
	 * Display admin notice if WPForms is not active
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wpforms_missing_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Medici Forms Advanced', 'medici' ); ?></strong>
				<?php esc_html_e( 'потребує активного плагіна WPForms. Будь ласка, встановіть та активуйте WPForms.', 'medici' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Load required files
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies(): void {
		$base_path = get_template_directory() . '/inc/forms-advanced/';

		// Load Settings class
		require_once $base_path . 'class-forms-advanced-settings.php';

		// Load Admin class
		require_once $base_path . 'class-forms-advanced-admin.php';

		// Load Frontend class
		require_once $base_path . 'class-forms-advanced-frontend.php';
	}

	/**
	 * Initialize plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init(): void {
		// Initialize settings
		$this->settings = new Medici_Forms_Advanced_Settings();

		// Initialize admin
		if ( is_admin() ) {
			$this->admin = new Medici_Forms_Advanced_Admin( $this->settings );
		}

		// Initialize frontend
		$this->frontend = new Medici_Forms_Advanced_Frontend( $this->settings );

		// Add hooks
		$this->hooks();
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function hooks(): void {
		// Add plugin action links
		add_filter(
			'plugin_action_links_medici-forms-advanced/medici-forms-advanced.php',
			[ $this, 'plugin_action_links' ]
		);
	}

	/**
	 * Add plugin action links
	 *
	 * @since 1.0.0
	 * @param array<string, string> $links Existing links
	 * @return array<string, string> Modified links
	 */
	public function plugin_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ),
			esc_html__( 'Налаштування', 'medici' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get settings instance
	 *
	 * @since 1.0.0
	 * @return Medici_Forms_Advanced_Settings
	 */
	public function get_settings(): Medici_Forms_Advanced_Settings {
		return $this->settings;
	}

	/**
	 * Get admin instance
	 *
	 * @since 1.0.0
	 * @return Medici_Forms_Advanced_Admin|null
	 */
	public function get_admin(): ?Medici_Forms_Advanced_Admin {
		return $this->admin;
	}

	/**
	 * Get frontend instance
	 *
	 * @since 1.0.0
	 * @return Medici_Forms_Advanced_Frontend
	 */
	public function get_frontend(): Medici_Forms_Advanced_Frontend {
		return $this->frontend;
	}
}

/**
 * Initialize plugin
 *
 * @since 1.0.0
 * @return Medici_Forms_Advanced
 */
function medici_forms_advanced(): Medici_Forms_Advanced {
	return Medici_Forms_Advanced::instance();
}

// Initialize
add_action( 'plugins_loaded', 'medici_forms_advanced', 15 );

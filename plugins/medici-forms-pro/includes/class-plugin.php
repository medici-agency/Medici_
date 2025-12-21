<?php
/**
 * Main Plugin Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Main Plugin Class - Singleton Pattern.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Form Post Type instance.
	 *
	 * @var Post_Types\Form|null
	 */
	public ?Post_Types\Form $form_cpt = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin\Admin|null
	 */
	public ?Admin\Admin $admin = null;

	/**
	 * Frontend instance.
	 *
	 * @var Frontend\Frontend|null
	 */
	public ?Frontend\Frontend $frontend = null;

	/**
	 * Form Processor instance.
	 *
	 * @var Processor\Form_Processor|null
	 */
	public ?Processor\Form_Processor $processor = null;

	/**
	 * Entries instance.
	 *
	 * @var Entries\Entries|null
	 */
	public ?Entries\Entries $entries = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 1.0.0
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->maybe_run_migrations();
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Run migrations if needed.
	 *
	 * @since 1.1.0
	 */
	private function maybe_run_migrations(): void {
		$current_version = get_option( 'medici_forms_version', '0.0.0' );

		if ( version_compare( $current_version, MEDICI_FORMS_VERSION, '<' ) ) {
			// Ensure defaults are set on upgrade.
			Activator::activate();
		}
	}

	/**
	 * Load dependencies.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies(): void {
		// Core classes.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/class-helpers.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/class-security.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/class-bot-detect.php';

		// Post Types.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Post_Types/class-form.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Post_Types/class-entry.php';

		// Admin.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Admin/class-admin.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Admin/class-settings.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Admin/class-form-builder.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Admin/class-entries-list.php';

		// Frontend.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Frontend/class-frontend.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Frontend/class-shortcode.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Frontend/class-ajax-handler.php';

		// Processor.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Processor/class-form-processor.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Processor/class-validator.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Processor/class-sanitizer.php';

		// Notifications.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Notifications/class-email.php';
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Notifications/class-webhook.php';

		// Entries.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Entries/class-entries.php';

		// Fields.
		require_once MEDICI_FORMS_PLUGIN_DIR . 'includes/Fields/class-field-factory.php';
	}

	/**
	 * Initialize components.
	 *
	 * @since 1.0.0
	 */
	private function init_components(): void {
		// Post Types.
		$this->form_cpt = new Post_Types\Form();

		// Entries.
		$this->entries = new Entries\Entries();

		// Admin (only in admin area).
		if ( is_admin() ) {
			$this->admin = new Admin\Admin();
		}

		// Frontend.
		$this->frontend = new Frontend\Frontend();

		// Processor.
		$this->processor = new Processor\Form_Processor();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_hooks(): void {
		// Load textdomain BEFORE post types (priority 1) to avoid _load_textdomain_just_in_time warning.
		add_action( 'init', array( $this, 'load_textdomain' ), 1 );
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'medici-forms-pro',
			false,
			dirname( MEDICI_FORMS_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Register post types.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types(): void {
		$this->form_cpt->register();
		( new Post_Types\Entry() )->register();
	}

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes(): void {
		$this->frontend->register_shortcodes();
	}

	/**
	 * Get plugin option.
	 *
	 * @since 1.0.0
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_option( string $key, mixed $default = null ): mixed {
		$options = get_option( 'medici_forms_settings', array() );
		return $options[ $key ] ?? $default;
	}
}

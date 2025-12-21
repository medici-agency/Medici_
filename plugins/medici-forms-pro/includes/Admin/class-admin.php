<?php
/**
 * Admin Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Admin;

use MediciForms\Post_Types\Form;

/**
 * Admin Class.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Form Builder instance.
	 *
	 * @var Form_Builder
	 */
	private Form_Builder $builder;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->settings = new Settings();
		$this->builder  = new Form_Builder();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'manage_' . Form::POST_TYPE . '_posts_columns', array( $this, 'add_form_columns' ) );
		add_action( 'manage_' . Form::POST_TYPE . '_posts_custom_column', array( $this, 'render_form_columns' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );
		add_action( 'admin_action_duplicate_form', array( $this, 'handle_duplicate_form' ) );
	}

	/**
	 * Add menu pages.
	 *
	 * @since 1.0.0
	 */
	public function add_menu_pages(): void {
		add_submenu_page(
			'edit.php?post_type=' . Form::POST_TYPE,
			__( 'Налаштування', 'medici-forms-pro' ),
			__( 'Налаштування', 'medici-forms-pro' ),
			'manage_options',
			'medici-forms-settings',
			array( $this->settings, 'render_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page.
	 */
	public function enqueue_scripts( string $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		// Global admin styles.
		wp_enqueue_style(
			'medici-forms-admin',
			MEDICI_FORMS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MEDICI_FORMS_VERSION
		);

		// Form builder page.
		if ( Form::POST_TYPE === $screen->post_type ) {
			wp_enqueue_style(
				'medici-forms-builder',
				MEDICI_FORMS_PLUGIN_URL . 'assets/css/builder.css',
				array(),
				MEDICI_FORMS_VERSION
			);

			wp_enqueue_script(
				'medici-forms-builder',
				MEDICI_FORMS_PLUGIN_URL . 'assets/js/builder.js',
				array( 'jquery', 'jquery-ui-sortable', 'wp-util' ),
				MEDICI_FORMS_VERSION,
				true
			);

			wp_localize_script(
				'medici-forms-builder',
				'mediciFormsBuilder',
				array(
					'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'medici_forms_builder' ),
					'fieldTypes' => \MediciForms\Helpers::get_field_types(),
					'i18n'       => array(
						'confirmDelete' => __( 'Ви впевнені, що хочете видалити це поле?', 'medici-forms-pro' ),
						'fieldAdded'    => __( 'Поле додано', 'medici-forms-pro' ),
						'fieldRemoved'  => __( 'Поле видалено', 'medici-forms-pro' ),
						'saving'        => __( 'Збереження...', 'medici-forms-pro' ),
						'saved'         => __( 'Збережено', 'medici-forms-pro' ),
						'error'         => __( 'Помилка', 'medici-forms-pro' ),
					),
				)
			);
		}

		// Settings page.
		if ( 'medici_form_page_medici-forms-settings' === $screen->id ) {
			wp_enqueue_style(
				'medici-forms-settings',
				MEDICI_FORMS_PLUGIN_URL . 'assets/css/settings.css',
				array(),
				MEDICI_FORMS_VERSION
			);

			wp_enqueue_script(
				'medici-forms-settings',
				MEDICI_FORMS_PLUGIN_URL . 'assets/js/settings.js',
				array( 'jquery', 'wp-color-picker' ),
				MEDICI_FORMS_VERSION,
				true
			);

			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Add form list columns.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_form_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['shortcode'] = __( 'Шорткод', 'medici-forms-pro' );
				$new_columns['entries']   = __( 'Заявки', 'medici-forms-pro' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render form list columns.
	 *
	 * @since 1.0.0
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_form_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'shortcode':
				$shortcode = '[medici_form id="' . $post_id . '"]';
				echo '<code class="mf-shortcode-copy" title="' . esc_attr__( 'Натисніть для копіювання', 'medici-forms-pro' ) . '">' .
					 esc_html( $shortcode ) . '</code>';
				break;

			case 'entries':
				$count = $this->get_entries_count( $post_id );
				$url   = admin_url( 'edit.php?post_type=medici_form_entry&form_id=' . $post_id );
				echo '<a href="' . esc_url( $url ) . '">' . esc_html( (string) $count ) . '</a>';
				break;
		}
	}

	/**
	 * Add row actions.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $actions Existing actions.
	 * @param \WP_Post              $post    Post object.
	 * @return array<string, string>
	 */
	public function add_row_actions( array $actions, \WP_Post $post ): array {
		if ( Form::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		$duplicate_url = wp_nonce_url(
			admin_url( 'admin.php?action=duplicate_form&form_id=' . $post->ID ),
			'duplicate_form_' . $post->ID
		);

		$actions['duplicate'] = '<a href="' . esc_url( $duplicate_url ) . '">' .
								__( 'Дублювати', 'medici-forms-pro' ) . '</a>';

		$preview_url = add_query_arg(
			array(
				'medici_form_preview' => $post->ID,
				'_wpnonce'            => wp_create_nonce( 'preview_form_' . $post->ID ),
			),
			home_url()
		);

		$actions['preview'] = '<a href="' . esc_url( $preview_url ) . '" target="_blank">' .
							  __( 'Попередній перегляд', 'medici-forms-pro' ) . '</a>';

		return $actions;
	}

	/**
	 * Handle form duplication.
	 *
	 * @since 1.0.0
	 */
	public function handle_duplicate_form(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['form_id'] ) ) {
			wp_die( esc_html__( 'ID форми не вказано.', 'medici-forms-pro' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = absint( $_GET['form_id'] );

		// Verify nonce.
		check_admin_referer( 'duplicate_form_' . $form_id );

		// Check capabilities.
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'У вас немає прав для виконання цієї дії.', 'medici-forms-pro' ) );
		}

		$new_form_id = Form::duplicate( $form_id );

		if ( $new_form_id ) {
			$edit_url = admin_url( 'post.php?post=' . $new_form_id . '&action=edit' );
			wp_safe_redirect( $edit_url );
			exit;
		}

		wp_die( esc_html__( 'Помилка при дублюванні форми.', 'medici-forms-pro' ) );
	}

	/**
	 * Get entries count for form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return int
	 */
	private function get_entries_count( int $form_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'medici_form_entries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE form_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$form_id
			)
		);

		return (int) $count;
	}
}

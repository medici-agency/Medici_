<?php
/**
 * Blog Sidebar Settings
 *
 * Налаштування для керування віджетами в Blog Single Sidebar
 *
 * @package    Medici
 * @subpackage Blog/Settings
 * @since      1.0.17
 * @version    1.0.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Реєстрація налаштувань sidebar
 */
function medici_register_sidebar_settings(): void {
	// Newsletter Widget
	register_setting(
		'medici_blog_sidebar',
		'medici_sidebar_newsletter_enabled',
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);

	// Back to Blog Widget
	register_setting(
		'medici_blog_sidebar',
		'medici_sidebar_back_to_blog_enabled',
		array(
			'type'              => 'boolean',
			'default'           => false, // За замовчуванням вимкнено
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);

	// Relevant Services Widget
	register_setting(
		'medici_blog_sidebar',
		'medici_sidebar_services_enabled',
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
		)
	);

	register_setting(
		'medici_blog_sidebar',
		'medici_sidebar_services_count',
		array(
			'type'              => 'integer',
			'default'           => 3,
			'sanitize_callback' => 'absint',
		)
	);
}
add_action( 'admin_init', 'medici_register_sidebar_settings' );

/**
 * Додати секцію налаштувань до Blog Settings
 */
function medici_sidebar_settings_section(): void {
	add_settings_section(
		'medici_sidebar_widgets_section',
		__( 'Налаштування Sidebar Віджетів', 'medici.agency' ),
		'medici_sidebar_widgets_section_callback',
		'medici-blog-settings'
	);

	// Newsletter Widget
	add_settings_field(
		'medici_sidebar_newsletter_enabled',
		__( 'Показувати Newsletter форму', 'medici.agency' ),
		'medici_sidebar_newsletter_enabled_callback',
		'medici-blog-settings',
		'medici_sidebar_widgets_section'
	);

	// Back to Blog Widget
	add_settings_field(
		'medici_sidebar_back_to_blog_enabled',
		__( 'Показувати "Повернутися до блогу"', 'medici.agency' ),
		'medici_sidebar_back_to_blog_enabled_callback',
		'medici-blog-settings',
		'medici_sidebar_widgets_section'
	);

	// Relevant Services Widget
	add_settings_field(
		'medici_sidebar_services_enabled',
		__( 'Показувати релевантні послуги', 'medici.agency' ),
		'medici_sidebar_services_enabled_callback',
		'medici-blog-settings',
		'medici_sidebar_widgets_section'
	);

	add_settings_field(
		'medici_sidebar_services_count',
		__( 'Кількість послуг для показу', 'medici.agency' ),
		'medici_sidebar_services_count_callback',
		'medici-blog-settings',
		'medici_sidebar_widgets_section'
	);
}
add_action( 'admin_init', 'medici_sidebar_settings_section', 20 );

/**
 * Callbacks для налаштувань
 */
function medici_sidebar_widgets_section_callback(): void {
	echo '<p>' . esc_html__( 'Налаштуйте які віджети показувати в sidebar на сторінках окремих статей.', 'medici.agency' ) . '</p>';
}

function medici_sidebar_newsletter_enabled_callback(): void {
	$enabled = (bool) get_option( 'medici_sidebar_newsletter_enabled', true );
	?>
	<label>
		<input type="checkbox" name="medici_sidebar_newsletter_enabled" value="1" <?php checked( $enabled ); ?>>
		<?php esc_html_e( 'Показувати форму підписки на розсилку', 'medici.agency' ); ?>
	</label>
	<?php
}

function medici_sidebar_back_to_blog_enabled_callback(): void {
	$enabled = (bool) get_option( 'medici_sidebar_back_to_blog_enabled', false );
	?>
	<label>
		<input type="checkbox" name="medici_sidebar_back_to_blog_enabled" value="1" <?php checked( $enabled ); ?>>
		<?php esc_html_e( 'Показувати кнопку "Повернутися до блогу"', 'medici.agency' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'За замовчуванням вимкнено. Увімкніть якщо потрібна кнопка повернення.', 'medici.agency' ); ?>
	</p>
	<?php
}

function medici_sidebar_services_enabled_callback(): void {
	$enabled = (bool) get_option( 'medici_sidebar_services_enabled', true );
	?>
	<label>
		<input type="checkbox" name="medici_sidebar_services_enabled" value="1" <?php checked( $enabled ); ?>>
		<?php esc_html_e( 'Показувати релевантні послуги агенції', 'medici.agency' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Автоматично визначає 2-3 найбільш релевантних послуг на основі контенту статті.', 'medici.agency' ); ?>
	</p>
	<?php
}

function medici_sidebar_services_count_callback(): void {
	$count = (int) get_option( 'medici_sidebar_services_count', 3 );
	?>
	<input type="number" name="medici_sidebar_services_count" value="<?php echo esc_attr( (string) $count ); ?>" min="2" max="4" step="1">
	<p class="description">
		<?php esc_html_e( 'Кількість послуг для показу (рекомендовано: 2-3)', 'medici.agency' ); ?>
	</p>
	<?php
}

/**
 * Helper функції для перевірки чи показувати віджет
 */
function medici_should_show_newsletter_widget(): bool {
	return (bool) get_option( 'medici_sidebar_newsletter_enabled', true );
}

function medici_should_show_back_to_blog_widget(): bool {
	return (bool) get_option( 'medici_sidebar_back_to_blog_enabled', false );
}

function medici_should_show_services_widget(): bool {
	return (bool) get_option( 'medici_sidebar_services_enabled', true );
}

function medici_get_services_widget_count(): int {
	return (int) get_option( 'medici_sidebar_services_count', 3 );
}

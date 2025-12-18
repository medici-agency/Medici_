<?php
/**
 * Blog Admin Settings Page
 *
 * Registers and displays admin settings for the blog functionality.
 *
 * @package    Medici_Agency
 * @subpackage Admin_Settings
 * @since      1.1.0
 * @version    1.1.1
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blog Settings Configuration Class
 *
 * @since 1.1.0
 */
final class Medici_Blog_Settings_Config {

	/**
	 * Settings page slug
	 */
	public const PAGE_SLUG = 'medici-blog-settings';

	/**
	 * Settings group
	 */
	public const SETTINGS_GROUP = 'medici_blog_settings_group';

	/**
	 * Option keys
	 */
	public const OPT_POSTS_PER_PAGE   = 'medici_blog_posts_per_page';
	public const OPT_ENABLE_FILTER    = 'medici_blog_enable_filter';
	public const OPT_ENABLE_SEARCH    = 'medici_blog_enable_search';
	public const OPT_DEFAULT_SORT     = 'medici_blog_default_sort';
	public const OPT_HERO_TITLE       = 'medici_blog_hero_title';
	public const OPT_HERO_DESCRIPTION = 'medici_blog_hero_description';
	public const OPT_HERO_CTA_TEXT    = 'medici_blog_hero_cta_text';
	public const OPT_FEATURED_POST_ID = 'medici_blog_featured_post_id';

	/**
	 * Section IDs
	 */
	public const SECTION_GENERAL = 'medici_blog_general_section';
	public const SECTION_HERO    = 'medici_blog_hero_section';

	/**
	 * Default values
	 */
	public const DEFAULT_POSTS_PER_PAGE = 6;
	public const DEFAULT_ENABLE_FILTER  = true;
	public const DEFAULT_ENABLE_SEARCH  = true;
	public const DEFAULT_SORT           = 'newest';

	/**
	 * Get default hero title
	 *
	 * @since 1.1.0
	 * @return string Default hero title
	 */
	public static function get_default_hero_title(): string {
		return __( 'Блог про медичний маркетинг', 'medici.agency' );
	}

	/**
	 * Get default hero description
	 *
	 * @since 1.1.0
	 * @return string Default hero description
	 */
	public static function get_default_hero_description(): string {
		return __( 'Експертні статті, юридичні роз\'яснення та практичні кейси від команди Medici Agency.', 'medici.agency' );
	}

	/**
	 * Get default CTA text
	 *
	 * @since 1.1.0
	 * @return string Default CTA text
	 */
	public static function get_default_cta_text(): string {
		return __( 'Отримати консультацію', 'medici.agency' );
	}

	/**
	 * Get sort options
	 *
	 * @since 1.1.0
	 * @return array Sort options
	 */
	public static function get_sort_options(): array {
		return array(
			'newest'  => __( 'Найновіші', 'medici.agency' ),
			'oldest'  => __( 'Найстаріші', 'medici.agency' ),
			'popular' => __( 'Популярні', 'medici.agency' ),
			'title'   => __( 'За назвою', 'medici.agency' ),
		);
	}
}

/**
 * Add admin menu item
 *
 * @since 1.0.0
 * @return void
 */
function medici_add_blog_settings_menu(): void {
	add_submenu_page(
		'edit.php?post_type=medici_blog',
		__( 'Налаштування блогу', 'medici.agency' ),
		__( 'Налаштування', 'medici.agency' ),
		'manage_options',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		'medici_render_blog_settings_page'
	);
}
add_action( 'admin_menu', 'medici_add_blog_settings_menu' );

/**
 * Render settings page
 *
 * @since 1.0.0
 * @return void
 */
function medici_render_blog_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<?php settings_errors( Medici_Blog_Settings_Config::SETTINGS_GROUP ); ?>

		<form method="post" action="options.php">
			<?php
			settings_fields( Medici_Blog_Settings_Config::SETTINGS_GROUP );
			do_settings_sections( Medici_Blog_Settings_Config::PAGE_SLUG );
			submit_button( __( 'Зберегти налаштування', 'medici.agency' ) );
			?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'Інформація', 'medici.agency' ); ?></h2>
		<p><?php esc_html_e( 'Ці налаштування керують відображенням блогу на вашому сайті.', 'medici.agency' ); ?></p>
	</div>
	<?php
}

/**
 * Register settings
 *
 * @since 1.0.0
 * @return void
 */
function medici_register_blog_settings(): void {
	// Register settings
	$settings = array(
		Medici_Blog_Settings_Config::OPT_POSTS_PER_PAGE   => array(
			'type'              => 'integer',
			'default'           => Medici_Blog_Settings_Config::DEFAULT_POSTS_PER_PAGE,
			'sanitize_callback' => 'absint',
		),
		Medici_Blog_Settings_Config::OPT_ENABLE_FILTER    => array(
			'type'              => 'boolean',
			'default'           => Medici_Blog_Settings_Config::DEFAULT_ENABLE_FILTER,
			'sanitize_callback' => 'rest_sanitize_boolean',
		),
		Medici_Blog_Settings_Config::OPT_ENABLE_SEARCH    => array(
			'type'              => 'boolean',
			'default'           => Medici_Blog_Settings_Config::DEFAULT_ENABLE_SEARCH,
			'sanitize_callback' => 'rest_sanitize_boolean',
		),
		Medici_Blog_Settings_Config::OPT_DEFAULT_SORT     => array(
			'type'              => 'string',
			'default'           => Medici_Blog_Settings_Config::DEFAULT_SORT,
			'sanitize_callback' => 'sanitize_text_field',
		),
		Medici_Blog_Settings_Config::OPT_HERO_TITLE       => array(
			'type'              => 'string',
			'default'           => Medici_Blog_Settings_Config::get_default_hero_title(),
			'sanitize_callback' => 'sanitize_text_field',
		),
		Medici_Blog_Settings_Config::OPT_HERO_DESCRIPTION => array(
			'type'              => 'string',
			'default'           => Medici_Blog_Settings_Config::get_default_hero_description(),
			'sanitize_callback' => 'wp_kses_post',
		),
		Medici_Blog_Settings_Config::OPT_HERO_CTA_TEXT    => array(
			'type'              => 'string',
			'default'           => Medici_Blog_Settings_Config::get_default_cta_text(),
			'sanitize_callback' => 'sanitize_text_field',
		),
		Medici_Blog_Settings_Config::OPT_FEATURED_POST_ID => array(
			'type'              => 'integer',
			'default'           => 0,
			'sanitize_callback' => 'absint',
		),
	);

	foreach ( $settings as $option_name => $args ) {
		register_setting( Medici_Blog_Settings_Config::SETTINGS_GROUP, $option_name, $args );
	}

	// Add sections
	add_settings_section(
		Medici_Blog_Settings_Config::SECTION_GENERAL,
		__( 'Загальні налаштування', 'medici.agency' ),
		'medici_blog_general_section_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG
	);

	add_settings_section(
		Medici_Blog_Settings_Config::SECTION_HERO,
		__( 'Hero-секція', 'medici.agency' ),
		'medici_blog_hero_section_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG
	);

	// Add fields - General
	add_settings_field(
		Medici_Blog_Settings_Config::OPT_POSTS_PER_PAGE,
		__( 'Кількість статей на сторінці', 'medici.agency' ),
		'medici_blog_posts_per_page_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_GENERAL
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_ENABLE_FILTER,
		__( 'Увімкнути фільтрацію', 'medici.agency' ),
		'medici_blog_enable_filter_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_GENERAL
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_ENABLE_SEARCH,
		__( 'Увімкнути пошук', 'medici.agency' ),
		'medici_blog_enable_search_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_GENERAL
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_DEFAULT_SORT,
		__( 'Сортування за замовчуванням', 'medici.agency' ),
		'medici_blog_default_sort_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_GENERAL
	);

	// Add fields - Hero
	add_settings_field(
		Medici_Blog_Settings_Config::OPT_HERO_TITLE,
		__( 'Заголовок Hero', 'medici.agency' ),
		'medici_blog_hero_title_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_HERO
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_HERO_DESCRIPTION,
		__( 'Опис Hero', 'medici.agency' ),
		'medici_blog_hero_description_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_HERO
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_HERO_CTA_TEXT,
		__( 'Текст кнопки CTA', 'medici.agency' ),
		'medici_blog_hero_cta_text_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_HERO
	);

	add_settings_field(
		Medici_Blog_Settings_Config::OPT_FEATURED_POST_ID,
		__( 'Рекомендована стаття (ID)', 'medici.agency' ),
		'medici_blog_featured_post_id_callback',
		Medici_Blog_Settings_Config::PAGE_SLUG,
		Medici_Blog_Settings_Config::SECTION_HERO
	);
}
add_action( 'admin_init', 'medici_register_blog_settings' );

/**
 * Section callbacks
 */
function medici_blog_general_section_callback(): void {
	echo '<p>' . esc_html__( 'Налаштуйте основні параметри відображення блогу.', 'medici.agency' ) . '</p>';
}

function medici_blog_hero_section_callback(): void {
	echo '<p>' . esc_html__( 'Налаштуйте hero-секцію на сторінці блогу.', 'medici.agency' ) . '</p>';
}

/**
 * Field callbacks
 */
function medici_blog_posts_per_page_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_POSTS_PER_PAGE, Medici_Blog_Settings_Config::DEFAULT_POSTS_PER_PAGE );
	?>
	<input
		type="number"
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_POSTS_PER_PAGE ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		min="1"
		max="24"
		step="1"
		class="small-text"
	/>
	<p class="description"><?php esc_html_e( 'Кількість статей на одній сторінці архіву (від 1 до 24).', 'medici.agency' ); ?></p>
	<?php
}

function medici_blog_enable_filter_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_ENABLE_FILTER, Medici_Blog_Settings_Config::DEFAULT_ENABLE_FILTER );
	?>
	<input type="hidden"
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_ENABLE_FILTER ); ?>"
		value="0"
	/>
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_ENABLE_FILTER ); ?>"
			value="1"
			<?php checked( (bool) $value, true ); ?>
		/>
		<?php esc_html_e( 'Показувати фільтр за категоріями', 'medici.agency' ); ?>
	</label>
	<?php
}

function medici_blog_enable_search_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_ENABLE_SEARCH, Medici_Blog_Settings_Config::DEFAULT_ENABLE_SEARCH );
	?>
	<input type="hidden"
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_ENABLE_SEARCH ); ?>"
		value="0"
	/>
	<label>
		<input
			type="checkbox"
			name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_ENABLE_SEARCH ); ?>"
			value="1"
			<?php checked( (bool) $value, true ); ?>
		/>
		<?php esc_html_e( 'Показувати поле пошуку', 'medici.agency' ); ?>
	</label>
	<?php
}

function medici_blog_default_sort_callback(): void {
	$value   = get_option( Medici_Blog_Settings_Config::OPT_DEFAULT_SORT, Medici_Blog_Settings_Config::DEFAULT_SORT );
	$options = Medici_Blog_Settings_Config::get_sort_options();
	?>
	<select name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_DEFAULT_SORT ); ?>">
		<?php foreach ( $options as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

function medici_blog_hero_title_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_HERO_TITLE, Medici_Blog_Settings_Config::get_default_hero_title() );
	?>
	<input
		type="text"
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_HERO_TITLE ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
	/>
	<?php
}

function medici_blog_hero_description_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_HERO_DESCRIPTION, Medici_Blog_Settings_Config::get_default_hero_description() );
	?>
	<textarea
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_HERO_DESCRIPTION ); ?>"
		rows="3"
		class="large-text"
	><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description"><?php esc_html_e( 'Короткий опис блогу (HTML дозволено, буде відфільтровано).', 'medici.agency' ); ?></p>
	<?php
}

function medici_blog_hero_cta_text_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_HERO_CTA_TEXT, Medici_Blog_Settings_Config::get_default_cta_text() );
	?>
	<input
		type="text"
		name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_HERO_CTA_TEXT ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
		class="regular-text"
	/>
	<?php
}

function medici_blog_featured_post_id_callback(): void {
	$value = get_option( Medici_Blog_Settings_Config::OPT_FEATURED_POST_ID, 0 );

	// Get all published blog posts (обмеження 100 для адмінки)
	$posts = get_posts(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	// Get featured posts count
	$featured_posts = get_posts(
		array(
			'post_type'      => 'medici_blog',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'   => '_medici_featured_article',
					'value' => '1',
				),
			),
			'no_found_rows'  => true,
			'fields'         => 'ids',
		)
	);
	$featured_count = is_array( $featured_posts ) ? count( $featured_posts ) : 0;
	?>

	<select name="<?php echo esc_attr( Medici_Blog_Settings_Config::OPT_FEATURED_POST_ID ); ?>">
		<option value="0"><?php esc_html_e( '— Автоматично —', 'medici.agency' ); ?></option>
		<?php foreach ( $posts as $post ) : ?>
			<option value="<?php echo esc_attr( (string) $post->ID ); ?>" <?php selected( $value, $post->ID ); ?>>
				<?php echo esc_html( $post->post_title ); ?>
				(ID: <?php echo esc_html( (string) $post->ID ); ?>)
			</option>
		<?php endforeach; ?>
	</select>

   
	<p class="description">
		<?php
		printf(
			/* translators: %d: number of featured posts */
			esc_html__( 'Виберіть статтю вручну або залиште "Автоматично" для відображення найновішої рекомендованої статті. Зараз у вас %d рекомендованих статей.', 'medici.agency' ),
			$featured_count
		);
		?>
	</p>
	<?php
}
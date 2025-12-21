<?php
/**
 * Form Post Type.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Post_Types;

/**
 * Form Post Type Class.
 *
 * @since 1.0.0
 */
class Form {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'medici_form';

	/**
	 * Register post type.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$labels = array(
			'name'                  => _x( 'Форми', 'Post type general name', 'medici-forms-pro' ),
			'singular_name'         => _x( 'Форма', 'Post type singular name', 'medici-forms-pro' ),
			'menu_name'             => _x( 'Medici Forms', 'Admin Menu text', 'medici-forms-pro' ),
			'name_admin_bar'        => _x( 'Форма', 'Add New on Toolbar', 'medici-forms-pro' ),
			'add_new'               => __( 'Додати нову', 'medici-forms-pro' ),
			'add_new_item'          => __( 'Додати нову форму', 'medici-forms-pro' ),
			'new_item'              => __( 'Нова форма', 'medici-forms-pro' ),
			'edit_item'             => __( 'Редагувати форму', 'medici-forms-pro' ),
			'view_item'             => __( 'Переглянути форму', 'medici-forms-pro' ),
			'all_items'             => __( 'Всі форми', 'medici-forms-pro' ),
			'search_items'          => __( 'Шукати форми', 'medici-forms-pro' ),
			'parent_item_colon'     => __( 'Батьківська форма:', 'medici-forms-pro' ),
			'not_found'             => __( 'Форми не знайдено.', 'medici-forms-pro' ),
			'not_found_in_trash'    => __( 'Форми не знайдено в кошику.', 'medici-forms-pro' ),
			'archives'              => __( 'Архів форм', 'medici-forms-pro' ),
			'filter_items_list'     => __( 'Фільтрувати список форм', 'medici-forms-pro' ),
			'items_list_navigation' => __( 'Навігація списком форм', 'medici-forms-pro' ),
			'items_list'            => __( 'Список форм', 'medici-forms-pro' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-feedback',
			'supports'            => array( 'title' ),
			'show_in_rest'        => true,
			'rest_base'           => 'medici-forms',
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Get all forms.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $args Query args.
	 * @return \WP_Post[]
	 */
	public static function get_forms( array $args = array() ): array {
		$defaults = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$query = new \WP_Query( wp_parse_args( $args, $defaults ) );

		return $query->posts;
	}

	/**
	 * Get form by ID.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 * @return \WP_Post|null
	 */
	public static function get( int $form_id ): ?\WP_Post {
		$form = get_post( $form_id );

		if ( ! $form || self::POST_TYPE !== $form->post_type ) {
			return null;
		}

		return $form;
	}

	/**
	 * Duplicate form.
	 *
	 * @since 1.0.0
	 * @param int $form_id Original form ID.
	 * @return int|false New form ID or false on failure.
	 */
	public static function duplicate( int $form_id ): int|false {
		$form = self::get( $form_id );

		if ( ! $form ) {
			return false;
		}

		$new_form_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_title'  => $form->post_title . ' ' . __( '(копія)', 'medici-forms-pro' ),
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $new_form_id ) ) {
			return false;
		}

		// Copy meta.
		$meta_keys = array( '_medici_form_fields', '_medici_form_settings' );
		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $form_id, $key, true );
			if ( $value ) {
				update_post_meta( $new_form_id, $key, $value );
			}
		}

		return $new_form_id;
	}
}

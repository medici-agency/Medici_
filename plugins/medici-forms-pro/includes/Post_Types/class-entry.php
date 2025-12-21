<?php
/**
 * Entry Post Type.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Post_Types;

/**
 * Entry Post Type Class.
 *
 * @since 1.0.0
 */
class Entry {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'medici_form_entry';

	/**
	 * Register post type.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		$labels = array(
			'name'                  => _x( 'Заявки', 'Post type general name', 'medici-forms-pro' ),
			'singular_name'         => _x( 'Заявка', 'Post type singular name', 'medici-forms-pro' ),
			'menu_name'             => _x( 'Заявки', 'Admin Menu text', 'medici-forms-pro' ),
			'add_new'               => __( 'Додати нову', 'medici-forms-pro' ),
			'add_new_item'          => __( 'Додати нову заявку', 'medici-forms-pro' ),
			'edit_item'             => __( 'Переглянути заявку', 'medici-forms-pro' ),
			'view_item'             => __( 'Переглянути заявку', 'medici-forms-pro' ),
			'all_items'             => __( 'Заявки', 'medici-forms-pro' ),
			'search_items'          => __( 'Шукати заявки', 'medici-forms-pro' ),
			'not_found'             => __( 'Заявки не знайдено.', 'medici-forms-pro' ),
			'not_found_in_trash'    => __( 'Заявки не знайдено в кошику.', 'medici-forms-pro' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=' . Form::POST_TYPE,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'show_in_rest'        => false,
		);

		register_post_type( self::POST_TYPE, $args );

		// Register entry statuses.
		$this->register_statuses();
	}

	/**
	 * Register custom entry statuses.
	 *
	 * @since 1.0.0
	 */
	private function register_statuses(): void {
		$statuses = array(
			'unread'    => __( 'Непрочитана', 'medici-forms-pro' ),
			'read'      => __( 'Прочитана', 'medici-forms-pro' ),
			'starred'   => __( 'Важлива', 'medici-forms-pro' ),
			'spam'      => __( 'Спам', 'medici-forms-pro' ),
			'completed' => __( 'Завершена', 'medici-forms-pro' ),
		);

		foreach ( $statuses as $status => $label ) {
			register_post_status(
				'mf_' . $status,
				array(
					'label'                     => $label,
					'public'                    => false,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: Number of entries. */
					'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'medici-forms-pro' ),
				)
			);
		}
	}
}

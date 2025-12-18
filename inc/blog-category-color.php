<?php
/**
 * Blog Category Color Picker
 *
 * Додає можливість обирати колір для кожної категорії блогу в адмінці.
 *
 * @package    Medici
 * @subpackage Blog
 * @since      1.0.16
 * @version    1.0.1
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Додати поле вибору кольору при створенні категорії
 */
function medici_blog_category_add_color_field(): void {
	?>
	<div class="form-field term-color-wrap">
		<label for="medici-category-color"><?php esc_html_e( 'Колір категорії', 'medici.agency' ); ?></label>
		<input type="color" name="medici_category_color" id="medici-category-color" value="#3B82F6" class="medici-color-picker">
		<p class="description"><?php esc_html_e( 'Оберіть колір для відображення цієї категорії на сайті.', 'medici.agency' ); ?></p>
	</div>
	<?php
}
add_action( 'blog_category_add_form_fields', 'medici_blog_category_add_color_field' );

/**
 * Додати поле вибору кольору при редагуванні категорії
 *
 * @param WP_Term $term Current taxonomy term object.
 */
function medici_blog_category_edit_color_field( WP_Term $term ): void {
	$color = get_term_meta( $term->term_id, 'medici_category_color', true );
	if ( empty( $color ) ) {
		$color = '#3B82F6';
	}
	?>
	<tr class="form-field term-color-wrap">
		<th scope="row">
			<label for="medici-category-color-<?php echo esc_attr( (string) $term->term_id ); ?>"><?php esc_html_e( 'Колір категорії', 'medici.agency' ); ?></label>
		</th>
		<td>
			<input type="text" name="medici_category_color" id="medici-category-color-<?php echo esc_attr( (string) $term->term_id ); ?>" value="<?php echo esc_attr( $color ); ?>" class="medici-color-field">
			<p class="description"><?php esc_html_e( 'Оберіть колір для відображення цієї категорії на сайті. Клацніть на поле для вибору кольору.', 'medici.agency' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'blog_category_edit_form_fields', 'medici_blog_category_edit_color_field' );

/**
 * Зберегти колір категорії
 *
 * @param int $term_id Term ID.
 */
function medici_blog_category_save_color( int $term_id ): void {
	if ( ! isset( $_POST['medici_category_color'] ) ) {
		return;
	}

	$color = sanitize_hex_color( wp_unslash( $_POST['medici_category_color'] ) );
	if ( ! $color ) {
		$color = '#3B82F6'; // Fallback to default blue
	}

	update_term_meta( $term_id, 'medici_category_color', $color );
}
add_action( 'created_blog_category', 'medici_blog_category_save_color' );
add_action( 'edited_blog_category', 'medici_blog_category_save_color' );

/**
 * Додати колонку "Колір" в список категорій
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function medici_blog_category_add_color_column( array $columns ): array {
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'name' === $key ) {
			$new_columns['color'] = __( 'Колір', 'medici.agency' );
		}
	}
	return $new_columns;
}
add_filter( 'manage_edit-blog_category_columns', 'medici_blog_category_add_color_column' );

/**
 * Відобразити колір в колонці
 *
 * @param string $content     Column content.
 * @param string $column_name Column name.
 * @param int    $term_id     Term ID.
 * @return string Modified content.
 */
function medici_blog_category_display_color_column( string $content, string $column_name, int $term_id ): string {
	if ( 'color' !== $column_name ) {
		return $content;
	}

	$color = get_term_meta( $term_id, 'medici_category_color', true );
	$color = $color ?: '#3B82F6';

	return sprintf(
		'<span style="display: inline-block; width: 30px; height: 30px; background: %s; border-radius: 4px; border: 2px solid #ddd; vertical-align: middle;"></span> <code>%s</code>',
		esc_attr( $color ),
		esc_html( $color )
	);
}
add_filter( 'manage_blog_category_custom_column', 'medici_blog_category_display_color_column', 10, 3 );

/**
 * Отримати колір категорії
 *
 * @param int $term_id Term ID.
 * @return string Hex color code.
 */
function medici_get_category_color( int $term_id ): string {
	$color = get_term_meta( $term_id, 'medici_category_color', true );
	return $color ?: '#3B82F6';
}

/**
 * Вивести inline style для категорії
 *
 * @param int $term_id Term ID.
 * @return string Inline style attribute.
 */
function medici_get_category_style( int $term_id ): string {
	$color = medici_get_category_color( $term_id );

	// Convert hex to rgba with 15% opacity for background
	$rgb = sscanf( $color, '#%02x%02x%02x' );
	if ( ! $rgb || count( $rgb ) !== 3 ) {
		$rgb = array( 59, 130, 246 ); // Fallback to blue
	}

	$background = sprintf( 'rgba(%d, %d, %d, 0.15)', $rgb[0], $rgb[1], $rgb[2] );

	return sprintf( 'background: %s; color: %s;', esc_attr( $background ), esc_attr( $color ) );
}

/**
 * Завантажити WordPress color picker
 *
 * @param string $hook Current admin page hook.
 */
function medici_blog_category_color_picker_assets( string $hook ): void {
	// Завантажувати тільки на сторінках edit-tags.php та term.php для blog_category
	if ( 'edit-tags.php' !== $hook && 'term.php' !== $hook ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'blog_category' !== $screen->taxonomy ) {
		return;
	}

	// Завантажити WordPress Iris color picker
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	// Ініціалізувати color picker
	$inline_js = "
		jQuery(document).ready(function($) {
			$('.medici-color-field').wpColorPicker({
				change: function(event, ui) {
					$(this).val(ui.color.toString());
				}
			});
		});
	";

	wp_add_inline_script( 'wp-color-picker', $inline_js );
}
add_action( 'admin_enqueue_scripts', 'medici_blog_category_color_picker_assets' );

<?php
/**
 * Blog Table of Contents (TOC) Module
 *
 * Серверна генерація TOC для статей блогу:
 * - Автоматичне створення TOC з H2/H3 заголовків
 * - Збереження TOC в post_meta при публікації
 * - Meta box для контролю TOC в адмін-панелі
 * - SEO-friendly серверний рендеринг
 *
 * @package    Medici
 * @subpackage Blog/TOC
 * @since      1.4.0
 * @version    1.0.1
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// CONSTANTS
// ============================================================================

define( 'MEDICI_TOC_META_KEY', '_medici_toc_data' );
define( 'MEDICI_TOC_ENABLED_KEY', '_medici_toc_enabled' );
define( 'MEDICI_TOC_MIN_HEADINGS', 2 );

// ============================================================================
// TOC GENERATION
// ============================================================================

/**
 * Парсить контент та витягує заголовки H2/H3 для TOC
 *
 * @param string $content HTML контент статті.
 * @return array<int, array{id: string, text: string, level: int}> Масив заголовків.
 */
function medici_parse_headings_from_content( string $content ): array {
	if ( empty( $content ) ) {
		return array();
	}

	$headings = array();

	// Використовуємо DOMDocument для безпечного парсингу HTML
	$dom = new DOMDocument();

	// Suppress warnings для invalid HTML
	libxml_use_internal_errors( true );

	// Додаємо UTF-8 meta для коректного парсингу кирилиці
	$content_with_meta = '<?xml encoding="UTF-8">' . $content;
	$dom->loadHTML( $content_with_meta, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	libxml_clear_errors();

	// Знаходимо всі H2 та H3
	$xpath         = new DOMXPath( $dom );
	$heading_nodes = $xpath->query( '//h2|//h3' );

	if ( false === $heading_nodes || 0 === $heading_nodes->length ) {
		return array();
	}

	$index = 0;
	foreach ( $heading_nodes as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}

		$text = trim( $node->textContent );

		// Пропускаємо пусті заголовки
		if ( '' === $text ) {
			continue;
		}

		// Отримуємо або генеруємо ID
		$id = $node->getAttribute( 'id' );
		if ( '' === $id ) {
			$id = 'heading-' . $index;
		}

		// Визначаємо рівень (2 для H2, 3 для H3)
		$level = (int) substr( $node->tagName, 1 );

		$headings[] = array(
			'id'    => $id,
			'text'  => $text,
			'level' => $level,
		);

		++$index;
	}

	return $headings;
}

/**
 * Додає ID атрибути до заголовків у контенті
 *
 * @param string $content HTML контент.
 * @return string Модифікований контент з ID.
 */
function medici_add_heading_ids_to_content( string $content ): string {
	if ( empty( $content ) ) {
		return $content;
	}

	$index = 0;

	// Regex для знаходження H2 та H3 без ID
	$pattern = '/<(h[23])([^>]*)>(.*?)<\/\1>/is';

	$content = preg_replace_callback(
		$pattern,
		function ( array $matches ) use ( &$index ): string {
			$tag        = $matches[1];
			$attributes = $matches[2];
			$text       = $matches[3];

			// Перевіряємо чи вже є id
			if ( preg_match( '/\bid=["\'][^"\']*["\']/', $attributes ) ) {
				return $matches[0]; // Залишаємо як є
			}

			// Генеруємо ID
			$id = 'heading-' . $index;
			++$index;

			// Додаємо id до атрибутів
			if ( '' !== trim( $attributes ) ) {
				$new_attributes = ' id="' . esc_attr( $id ) . '"' . $attributes;
			} else {
				$new_attributes = ' id="' . esc_attr( $id ) . '"';
			}

			return '<' . $tag . $new_attributes . '>' . $text . '</' . $tag . '>';
		},
		$content
	);

	return $content ?? '';
}

/**
 * Генерує TOC структуру для статті
 *
 * @param int $post_id ID статті.
 * @return array<int, array{id: string, text: string, level: int}> Структура TOC.
 */
function medici_generate_toc_for_post( int $post_id ): array {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	// Застосовуємо фільтри контенту (для shortcodes, blocks, тощо)
	$content = apply_filters( 'the_content', $post->post_content );

	return medici_parse_headings_from_content( $content );
}

// ============================================================================
// TOC STORAGE (AUTO-SAVE)
// ============================================================================

/**
 * Зберігає TOC при збереженні статті
 *
 * Працює з:
 * - Classic Editor (save_post)
 * - Gutenberg/Block Editor (REST API)
 * - Quick Edit та Bulk Edit
 *
 * @since 1.0.0
 * @param int     $post_id ID статті.
 * @param WP_Post $post    Об'єкт статті.
 * @return void
 */
function medici_save_toc_on_post_save( int $post_id, WP_Post $post ): void {
	// Пропускаємо autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Пропускаємо revisions
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// Тільки для опублікованих статей
	if ( 'publish' !== $post->post_status ) {
		return;
	}

	// Генеруємо TOC
	$toc_data = medici_generate_toc_for_post( $post_id );

	// Зберігаємо в meta
	if ( ! empty( $toc_data ) ) {
		update_post_meta( $post_id, MEDICI_TOC_META_KEY, wp_json_encode( $toc_data ) );
	} else {
		delete_post_meta( $post_id, MEDICI_TOC_META_KEY );
	}
}
// Використовуємо save_post_medici_blog замість save_post для гарантії типу
add_action( 'save_post_medici_blog', 'medici_save_toc_on_post_save', 20, 2 );

/**
 * Отримує збережену TOC структуру
 *
 * @param int $post_id ID статті.
 * @return array<int, array{id: string, text: string, level: int}> Структура TOC.
 */
function medici_get_saved_toc( int $post_id ): array {
	$toc_json = get_post_meta( $post_id, MEDICI_TOC_META_KEY, true );

	if ( empty( $toc_json ) || ! is_string( $toc_json ) ) {
		// Якщо немає збереженого TOC - генеруємо на льоту
		return medici_generate_toc_for_post( $post_id );
	}

	$toc_data = json_decode( $toc_json, true );

	if ( ! is_array( $toc_data ) ) {
		return medici_generate_toc_for_post( $post_id );
	}

	return $toc_data;
}

// ============================================================================
// META BOX (ADMIN CONTROL)
// ============================================================================

/**
 * Реєструє meta box для TOC налаштувань
 *
 * @return void
 */
function medici_register_toc_meta_box(): void {
	add_meta_box(
		'medici_toc_settings',
		__( 'Зміст статті (TOC)', 'medici.agency' ),
		'medici_render_toc_meta_box',
		'medici_blog',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'medici_register_toc_meta_box' );

/**
 * Рендерить meta box для TOC
 *
 * @param WP_Post $post Поточна стаття.
 * @return void
 */
function medici_render_toc_meta_box( WP_Post $post ): void {
	// Nonce для безпеки
	wp_nonce_field( 'medici_toc_meta_box', 'medici_toc_nonce' );

	// Отримуємо поточне значення (default = true/enabled)
	$toc_enabled = get_post_meta( $post->ID, MEDICI_TOC_ENABLED_KEY, true );

	// Якщо meta не встановлено - default enabled
	if ( '' === $toc_enabled ) {
		$toc_enabled = '1';
	}

	// Отримуємо збережену TOC структуру
	$toc_data    = medici_get_saved_toc( $post->ID );
	$toc_count   = count( $toc_data );
	$has_content = $toc_count >= MEDICI_TOC_MIN_HEADINGS;
	?>
	<div class="medici-toc-meta-box">
		<p>
			<label>
				<input type="checkbox"
						name="medici_toc_enabled"
						value="1"
						<?php checked( $toc_enabled, '1' ); ?>>
				<?php esc_html_e( 'Показувати зміст статті', 'medici.agency' ); ?>
			</label>
		</p>

		<div class="medici-toc-status" style="margin-top: 12px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
			<?php if ( $has_content ) : ?>
				<p style="margin: 0; color: #00a32a;">
					<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
					<?php
					printf(
						/* translators: %d: number of headings */
						esc_html__( 'Знайдено %d заголовків для змісту', 'medici.agency' ),
						$toc_count
					);
					?>
				</p>
			<?php else : ?>
				<p style="margin: 0; color: #d63638;">
					<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
					<?php
					printf(
						/* translators: %d: minimum headings required */
						esc_html__( 'Потрібно мінімум %d заголовки (H2/H3) для відображення змісту', 'medici.agency' ),
						MEDICI_TOC_MIN_HEADINGS
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( $has_content && ! empty( $toc_data ) ) : ?>
			<div class="medici-toc-preview" style="margin-top: 12px;">
				<p style="margin-bottom: 8px; font-weight: 600;">
					<?php esc_html_e( 'Попередній перегляд:', 'medici.agency' ); ?>
				</p>
				<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #50575e;">
					<?php foreach ( array_slice( $toc_data, 0, 5 ) as $item ) : ?>
						<li style="<?php echo 3 === $item['level'] ? 'margin-left: 16px;' : ''; ?>">
							<?php echo esc_html( mb_substr( $item['text'], 0, 40 ) ); ?>
							<?php echo mb_strlen( $item['text'] ) > 40 ? '...' : ''; ?>
						</li>
					<?php endforeach; ?>
					<?php if ( $toc_count > 5 ) : ?>
						<li style="color: #787c82; font-style: italic;">
							<?php
							printf(
								/* translators: %d: number of additional items */
								esc_html__( '...та ще %d пунктів', 'medici.agency' ),
								$toc_count - 5
							);
							?>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Зберігає налаштування TOC з meta box
 *
 * @param int $post_id ID статті.
 * @return void
 */
function medici_save_toc_meta_box( int $post_id ): void {
	// Перевіряємо nonce
	if ( ! isset( $_POST['medici_toc_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['medici_toc_nonce'] ) ), 'medici_toc_meta_box' ) ) {
		return;
	}

	// Пропускаємо autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Перевіряємо права
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Зберігаємо статус TOC
	$toc_enabled = isset( $_POST['medici_toc_enabled'] ) ? '1' : '0';
	update_post_meta( $post_id, MEDICI_TOC_ENABLED_KEY, $toc_enabled );
}
add_action( 'save_post_medici_blog', 'medici_save_toc_meta_box' );

// ============================================================================
// TOC RENDERING
// ============================================================================

/**
 * Перевіряє чи TOC увімкнено для статті
 *
 * @param int $post_id ID статті.
 * @return bool True якщо TOC увімкнено.
 */
function medici_is_toc_enabled( int $post_id ): bool {
	$enabled = get_post_meta( $post_id, MEDICI_TOC_ENABLED_KEY, true );

	// Default = enabled
	if ( '' === $enabled ) {
		return true;
	}

	return '1' === $enabled;
}

/**
 * Рендерить HTML для TOC
 *
 * @param int  $post_id      ID статті.
 * @param bool $force_render Примусовий рендеринг (ігнорує enabled статус).
 * @return string HTML код TOC або пустий рядок.
 */
function medici_render_toc( int $post_id, bool $force_render = false ): string {
	// Перевіряємо чи увімкнено
	if ( ! $force_render && ! medici_is_toc_enabled( $post_id ) ) {
		return '';
	}

	// Отримуємо TOC дані
	$toc_data = medici_get_saved_toc( $post_id );

	// Перевіряємо мінімальну кількість заголовків
	if ( count( $toc_data ) < MEDICI_TOC_MIN_HEADINGS ) {
		return '';
	}

	// Генеруємо HTML
	ob_start();
	?>
	<nav class="medici-toc" aria-label="<?php esc_attr_e( 'Зміст статті', 'medici.agency' ); ?>">
		<ul class="toc-list">
			<?php foreach ( $toc_data as $item ) : ?>
				<li class="toc-item toc-level-<?php echo esc_attr( (string) $item['level'] ); ?>">
					<a href="#<?php echo esc_attr( $item['id'] ); ?>"
						class="toc-link"
						data-target="<?php echo esc_attr( $item['id'] ); ?>">
						<?php echo esc_html( $item['text'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Shortcode для вставки TOC у контент
 *
 * Використання: [medici_toc]
 *
 * @param array $atts Атрибути shortcode (не використовуються).
 * @return string HTML код TOC.
 */
function medici_toc_shortcode( array $atts = array() ): string {
	// Тільки на single medici_blog
	if ( ! is_singular( 'medici_blog' ) ) {
		return '';
	}

	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	return medici_render_toc( $post_id );
}
add_shortcode( 'medici_toc', 'medici_toc_shortcode' );

// ============================================================================
// CONTENT FILTER (ADD IDs TO HEADINGS)
// ============================================================================

/**
 * Фільтр контенту для додавання ID до заголовків
 *
 * @param string $content Контент статті.
 * @return string Модифікований контент.
 */
function medici_add_toc_heading_ids( string $content ): string {
	// Тільки для single medici_blog
	if ( ! is_singular( 'medici_blog' ) ) {
		return $content;
	}

	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return $content;
	}

	// Перевіряємо чи TOC увімкнено
	if ( ! medici_is_toc_enabled( $post_id ) ) {
		return $content;
	}

	return medici_add_heading_ids_to_content( $content );
}
add_filter( 'the_content', 'medici_add_toc_heading_ids', 15 );

// ============================================================================
// BULK REGENERATION (ADMIN)
// ============================================================================

/**
 * Регенерує TOC для всіх статей блогу
 *
 * Використовується при оновленні модуля або масовому оновленні.
 *
 * @return int Кількість оновлених статей.
 */
function medici_regenerate_all_toc(): int {
	$posts = get_posts(
		array(
			'post_type'      => 'medici_blog',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$updated = 0;

	foreach ( $posts as $post_id ) {
		$toc_data = medici_generate_toc_for_post( $post_id );

		if ( ! empty( $toc_data ) ) {
			update_post_meta( $post_id, MEDICI_TOC_META_KEY, wp_json_encode( $toc_data ) );
			++$updated;
		}
	}

	return $updated;
}

/**
 * AJAX handler для регенерації TOC
 *
 * @return void
 */
function medici_ajax_regenerate_toc(): void {
	// Перевірка прав
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Недостатньо прав', 'medici.agency' ) );
	}

	// Перевірка nonce
	check_ajax_referer( 'medici_regenerate_toc', 'nonce' );

	$updated = medici_regenerate_all_toc();

	wp_send_json_success(
		array(
			'message' => sprintf(
				/* translators: %d: number of updated posts */
				__( 'TOC оновлено для %d статей', 'medici.agency' ),
				$updated
			),
			'count'   => $updated,
		)
	);
}
add_action( 'wp_ajax_medici_regenerate_toc', 'medici_ajax_regenerate_toc' );

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Отримує кількість заголовків у TOC
 *
 * @param int $post_id ID статті.
 * @return int Кількість заголовків.
 */
function medici_get_toc_headings_count( int $post_id ): int {
	$toc_data = medici_get_saved_toc( $post_id );
	return count( $toc_data );
}

/**
 * Перевіряє чи стаття має достатньо заголовків для TOC
 *
 * @param int $post_id ID статті.
 * @return bool True якщо є достатньо заголовків.
 */
function medici_has_toc_content( int $post_id ): bool {
	return medici_get_toc_headings_count( $post_id ) >= MEDICI_TOC_MIN_HEADINGS;
}

/**
 * Отримує TOC як масив для використання в шаблонах
 *
 * @param int $post_id ID статті.
 * @return array<int, array{id: string, text: string, level: int}> Масив TOC.
 */
function medici_get_toc_array( int $post_id ): array {
	if ( ! medici_is_toc_enabled( $post_id ) ) {
		return array();
	}

	return medici_get_saved_toc( $post_id );
}

// ============================================================================
// ADMIN NOTICE & BULK REGENERATION UI
// ============================================================================

/**
 * Показує admin notice для регенерації TOC (один раз)
 *
 * @return void
 */
function medici_toc_admin_notice(): void {
	// Тільки для адмінів
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Тільки на сторінках блогу
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'edit-medici_blog', 'medici_blog' ), true ) ) {
		return;
	}

	// Перевіряємо чи вже показували notice
	$notice_dismissed = get_option( 'medici_toc_notice_dismissed', false );
	if ( $notice_dismissed ) {
		return;
	}

	// Перевіряємо чи є статті без TOC
	$posts_without_toc = get_posts(
		array(
			'post_type'      => 'medici_blog',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'     => MEDICI_TOC_META_KEY,
					'compare' => 'NOT EXISTS',
				),
			),
			'fields'         => 'ids',
		)
	);

	if ( empty( $posts_without_toc ) ) {
		return;
	}

	$regenerate_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=medici_regenerate_all_toc' ),
		'medici_regenerate_toc_action'
	);

	$dismiss_url = wp_nonce_url(
		admin_url( 'admin-post.php?action=medici_dismiss_toc_notice' ),
		'medici_dismiss_toc_notice'
	);
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( '📋 Зміст статей (TOC)', 'medici.agency' ); ?></strong><br>
			<?php esc_html_e( 'Знайдено статті без автоматично згенерованого змісту. Бажаєте згенерувати TOC для всіх існуючих статей?', 'medici.agency' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( $regenerate_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Згенерувати TOC для всіх статей', 'medici.agency' ); ?>
			</a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button" style="margin-left: 10px;">
				<?php esc_html_e( 'Приховати', 'medici.agency' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'medici_toc_admin_notice' );

/**
 * Обробляє регенерацію TOC через admin-post.php
 *
 * @return void
 */
function medici_handle_regenerate_all_toc(): void {
	// Перевірка прав
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
	}

	// Перевірка nonce
	check_admin_referer( 'medici_regenerate_toc_action' );

	// Регенеруємо TOC
	$updated = medici_regenerate_all_toc();

	// Позначаємо notice як показану
	update_option( 'medici_toc_notice_dismissed', true );

	// Redirect з повідомленням
	$redirect_url = add_query_arg(
		array(
			'post_type'        => 'medici_blog',
			'medici_toc_regen' => $updated,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_medici_regenerate_all_toc', 'medici_handle_regenerate_all_toc' );

/**
 * Обробляє приховування notice
 *
 * @return void
 */
function medici_handle_dismiss_toc_notice(): void {
	// Перевірка прав
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
	}

	// Перевірка nonce
	check_admin_referer( 'medici_dismiss_toc_notice' );

	// Позначаємо notice як показану
	update_option( 'medici_toc_notice_dismissed', true );

	// Redirect назад
	wp_safe_redirect( admin_url( 'edit.php?post_type=medici_blog' ) );
	exit;
}
add_action( 'admin_post_medici_dismiss_toc_notice', 'medici_handle_dismiss_toc_notice' );

/**
 * Показує success notice після регенерації
 *
 * @return void
 */
function medici_toc_regeneration_success_notice(): void {
	if ( ! isset( $_GET['medici_toc_regen'] ) ) {
		return;
	}

	$count = absint( wp_unslash( $_GET['medici_toc_regen'] ) );
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php esc_html_e( '✅ TOC успішно згенеровано!', 'medici.agency' ); ?></strong><br>
			<?php
			printf(
				/* translators: %d: number of posts */
				esc_html__( 'Зміст статей оновлено для %d публікацій.', 'medici.agency' ),
				$count
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'medici_toc_regeneration_success_notice' );

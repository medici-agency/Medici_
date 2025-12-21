<?php
/**
 * Form Builder Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Admin;

use MediciForms\Post_Types\Form;
use MediciForms\Helpers;

/**
 * Form Builder Class - Drag & Drop form builder.
 *
 * @since 1.0.0
 */
class Form_Builder {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Form::POST_TYPE, array( $this, 'save_form' ), 10, 2 );
		add_action( 'wp_ajax_medici_forms_add_field', array( $this, 'ajax_add_field' ) );
	}

	/**
	 * Add meta boxes.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'medici_form_builder',
			__( 'Поля форми', 'medici-forms-pro' ),
			array( $this, 'render_builder_metabox' ),
			Form::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'medici_form_settings',
			__( 'Налаштування форми', 'medici-forms-pro' ),
			array( $this, 'render_settings_metabox' ),
			Form::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'medici_form_shortcode',
			__( 'Шорткод', 'medici-forms-pro' ),
			array( $this, 'render_shortcode_metabox' ),
			Form::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render builder meta box.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 */
	public function render_builder_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'medici_form_builder', 'medici_form_builder_nonce' );

		$fields      = Helpers::get_form_fields( $post->ID );
		$field_types = Helpers::get_field_types();
		?>
		<div class="medici-form-builder">
			<div class="mf-builder-sidebar">
				<h4><?php esc_html_e( 'Додати поле', 'medici-forms-pro' ); ?></h4>

				<div class="mf-field-groups">
					<?php
					$groups = array(
						'basic'    => __( 'Базові', 'medici-forms-pro' ),
						'choice'   => __( 'Вибір', 'medici-forms-pro' ),
						'advanced' => __( 'Розширені', 'medici-forms-pro' ),
						'layout'   => __( 'Макет', 'medici-forms-pro' ),
					);

					foreach ( $groups as $group_id => $group_name ) :
						?>
						<div class="mf-field-group">
							<h5><?php echo esc_html( $group_name ); ?></h5>
							<div class="mf-field-buttons">
								<?php
								foreach ( $field_types as $type => $config ) :
									if ( $config['group'] !== $group_id ) {
										continue;
									}
									?>
									<button type="button"
											class="mf-add-field button"
											data-type="<?php echo esc_attr( $type ); ?>">
										<span class="dashicons <?php echo esc_attr( $config['icon'] ); ?>"></span>
										<?php echo esc_html( $config['label'] ); ?>
									</button>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="mf-builder-canvas">
				<div class="mf-fields-list" id="mf-fields-list">
					<?php
					if ( empty( $fields ) ) :
						?>
						<div class="mf-empty-state">
							<span class="dashicons dashicons-plus-alt2"></span>
							<p><?php esc_html_e( 'Додайте поля з панелі зліва', 'medici-forms-pro' ); ?></p>
						</div>
					<?php else : ?>
						<?php foreach ( $fields as $index => $field ) : ?>
							<?php $this->render_field_item( $field, $index ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Field Template -->
		<script type="text/html" id="tmpl-mf-field-item">
			<?php $this->render_field_item_template(); ?>
		</script>
		<?php
	}

	/**
	 * Render single field item.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field Field data.
	 * @param int                  $index Field index.
	 */
	private function render_field_item( array $field, int $index ): void {
		$field_types = Helpers::get_field_types();
		$type        = $field['type'] ?? 'text';
		$type_config = $field_types[ $type ] ?? $field_types['text'];
		?>
		<div class="mf-field-item" data-index="<?php echo esc_attr( (string) $index ); ?>" data-type="<?php echo esc_attr( $type ); ?>">
			<div class="mf-field-header">
				<span class="mf-field-drag dashicons dashicons-move"></span>
				<span class="mf-field-type">
					<span class="dashicons <?php echo esc_attr( $type_config['icon'] ); ?>"></span>
					<?php echo esc_html( $type_config['label'] ); ?>
				</span>
				<span class="mf-field-label"><?php echo esc_html( $field['label'] ?? '' ); ?></span>
				<span class="mf-field-actions">
					<button type="button" class="mf-toggle-field button-link">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
					<button type="button" class="mf-duplicate-field button-link">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
					<button type="button" class="mf-remove-field button-link">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</span>
			</div>
			<div class="mf-field-content" style="display:none;">
				<?php $this->render_field_options( $field, $index ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render field options.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field Field data.
	 * @param int                  $index Field index.
	 */
	private function render_field_options( array $field, int $index ): void {
		$prefix = "medici_form_fields[{$index}]";
		$type   = $field['type'] ?? 'text';
		?>
		<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[id]"
			   value="<?php echo esc_attr( $field['id'] ?? Helpers::generate_field_id() ); ?>">
		<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[type]"
			   value="<?php echo esc_attr( $type ); ?>">

		<div class="mf-field-row">
			<label><?php esc_html_e( 'Назва поля', 'medici-forms-pro' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $prefix ); ?>[label]"
				   value="<?php echo esc_attr( $field['label'] ?? '' ); ?>"
				   class="mf-field-label-input">
		</div>

		<?php if ( ! in_array( $type, array( 'html', 'divider', 'heading' ), true ) ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'Placeholder', 'medici-forms-pro' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[placeholder]"
					   value="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>">
			</div>

			<div class="mf-field-row mf-field-row-inline">
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[required]"
						   value="1" <?php checked( ! empty( $field['required'] ) ); ?>>
					<?php esc_html_e( "Обов'язкове поле", 'medici-forms-pro' ); ?>
				</label>
			</div>
		<?php endif; ?>

		<?php if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'Варіанти (один на рядок)', 'medici-forms-pro' ); ?></label>
				<textarea name="<?php echo esc_attr( $prefix ); ?>[options]"
						  rows="5"><?php echo esc_textarea( $field['options'] ?? '' ); ?></textarea>
			</div>
		<?php endif; ?>

		<?php if ( 'textarea' === $type ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'Кількість рядків', 'medici-forms-pro' ); ?></label>
				<input type="number" name="<?php echo esc_attr( $prefix ); ?>[rows]"
					   value="<?php echo esc_attr( (string) ( $field['rows'] ?? 5 ) ); ?>"
					   min="2" max="20">
			</div>
		<?php endif; ?>

		<?php if ( 'html' === $type ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'HTML контент', 'medici-forms-pro' ); ?></label>
				<textarea name="<?php echo esc_attr( $prefix ); ?>[content]"
						  rows="5"><?php echo esc_textarea( $field['content'] ?? '' ); ?></textarea>
			</div>
		<?php endif; ?>

		<?php if ( 'heading' === $type ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'Рівень заголовка', 'medici-forms-pro' ); ?></label>
				<select name="<?php echo esc_attr( $prefix ); ?>[heading_level]">
					<?php for ( $i = 2; $i <= 6; $i++ ) : ?>
						<option value="<?php echo esc_attr( (string) $i ); ?>"
							<?php selected( $field['heading_level'] ?? 3, $i ); ?>>
							H<?php echo esc_html( (string) $i ); ?>
						</option>
					<?php endfor; ?>
				</select>
			</div>
		<?php endif; ?>

		<?php if ( 'hidden' === $type ) : ?>
			<div class="mf-field-row">
				<label><?php esc_html_e( 'Значення за замовчуванням', 'medici-forms-pro' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $prefix ); ?>[default_value]"
					   value="<?php echo esc_attr( $field['default_value'] ?? '' ); ?>">
				<p class="description">
					<?php esc_html_e( 'Підтримуються змінні: {page_url}, {page_title}, {user_ip}, {utm_source}, тощо.', 'medici-forms-pro' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="mf-field-row">
			<label><?php esc_html_e( 'CSS клас', 'medici-forms-pro' ); ?></label>
			<input type="text" name="<?php echo esc_attr( $prefix ); ?>[css_class]"
				   value="<?php echo esc_attr( $field['css_class'] ?? '' ); ?>">
		</div>

		<div class="mf-field-row">
			<label><?php esc_html_e( 'Ширина поля', 'medici-forms-pro' ); ?></label>
			<select name="<?php echo esc_attr( $prefix ); ?>[width]">
				<option value="100" <?php selected( $field['width'] ?? '100', '100' ); ?>>100%</option>
				<option value="50" <?php selected( $field['width'] ?? '100', '50' ); ?>>50%</option>
				<option value="33" <?php selected( $field['width'] ?? '100', '33' ); ?>>33%</option>
			</select>
		</div>
		<?php
	}

	/**
	 * Render field item template (for JS).
	 *
	 * @since 1.0.0
	 */
	private function render_field_item_template(): void {
		?>
		<div class="mf-field-item" data-index="{{ data.index }}" data-type="{{ data.type }}">
			<div class="mf-field-header">
				<span class="mf-field-drag dashicons dashicons-move"></span>
				<span class="mf-field-type">
					<span class="dashicons {{ data.icon }}"></span>
					{{ data.typeLabel }}
				</span>
				<span class="mf-field-label">{{ data.label }}</span>
				<span class="mf-field-actions">
					<button type="button" class="mf-toggle-field button-link">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
					<button type="button" class="mf-duplicate-field button-link">
						<span class="dashicons dashicons-admin-page"></span>
					</button>
					<button type="button" class="mf-remove-field button-link">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</span>
			</div>
			<div class="mf-field-content">
				{{{ data.optionsHtml }}}
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings meta box.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 */
	public function render_settings_metabox( \WP_Post $post ): void {
		$settings = Helpers::get_form_settings( $post->ID );
		?>
		<div class="medici-form-settings">
			<div class="mf-settings-tabs">
				<button type="button" class="mf-tab-button active" data-tab="submit">
					<?php esc_html_e( 'Кнопка', 'medici-forms-pro' ); ?>
				</button>
				<button type="button" class="mf-tab-button" data-tab="messages">
					<?php esc_html_e( 'Повідомлення', 'medici-forms-pro' ); ?>
				</button>
				<button type="button" class="mf-tab-button" data-tab="email">
					<?php esc_html_e( 'Email', 'medici-forms-pro' ); ?>
				</button>
				<button type="button" class="mf-tab-button" data-tab="advanced">
					<?php esc_html_e( 'Розширені', 'medici-forms-pro' ); ?>
				</button>
			</div>

			<div class="mf-settings-content">
				<!-- Submit Button Tab -->
				<div class="mf-tab-content active" data-tab="submit">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Текст кнопки', 'medici-forms-pro' ); ?></th>
							<td>
								<input type="text" name="medici_form_settings[submit_text]"
									   value="<?php echo esc_attr( $settings['submit_text'] ); ?>"
									   class="regular-text">
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Текст при відправці', 'medici-forms-pro' ); ?></th>
							<td>
								<input type="text" name="medici_form_settings[submit_processing]"
									   value="<?php echo esc_attr( $settings['submit_processing'] ); ?>"
									   class="regular-text">
							</td>
						</tr>
					</table>
				</div>

				<!-- Messages Tab -->
				<div class="mf-tab-content" data-tab="messages">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Повідомлення успіху', 'medici-forms-pro' ); ?></th>
							<td>
								<textarea name="medici_form_settings[success_message]"
										  rows="3" class="large-text"><?php echo esc_textarea( $settings['success_message'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Повідомлення помилки', 'medici-forms-pro' ); ?></th>
							<td>
								<textarea name="medici_form_settings[error_message]"
										  rows="3" class="large-text"><?php echo esc_textarea( $settings['error_message'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Переадресація', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[enable_redirect]"
										   value="1" <?php checked( $settings['enable_redirect'] ); ?>>
									<?php esc_html_e( 'Переадресувати після успішної відправки', 'medici-forms-pro' ); ?>
								</label>
								<br><br>
								<input type="url" name="medici_form_settings[redirect_url]"
									   value="<?php echo esc_url( $settings['redirect_url'] ); ?>"
									   class="regular-text" placeholder="https://">
							</td>
						</tr>
					</table>
				</div>

				<!-- Email Tab -->
				<div class="mf-tab-content" data-tab="email">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Email адміністратору', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[enable_admin_email]"
										   value="1" <?php checked( $settings['enable_admin_email'] ); ?>>
									<?php esc_html_e( 'Надсилати сповіщення адміністратору', 'medici-forms-pro' ); ?>
								</label>
								<br><br>
								<input type="email" name="medici_form_settings[admin_email_to]"
									   value="<?php echo esc_attr( $settings['admin_email_to'] ); ?>"
									   class="regular-text" placeholder="admin@example.com">
								<p class="description">
									<?php esc_html_e( 'Можна вказати кілька адрес через кому.', 'medici-forms-pro' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Тема листа', 'medici-forms-pro' ); ?></th>
							<td>
								<input type="text" name="medici_form_settings[admin_email_subject]"
									   value="<?php echo esc_attr( $settings['admin_email_subject'] ); ?>"
									   class="regular-text">
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Email користувачу', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[enable_user_email]"
										   value="1" <?php checked( $settings['enable_user_email'] ); ?>>
									<?php esc_html_e( 'Надсилати підтвердження користувачу', 'medici-forms-pro' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>

				<!-- Advanced Tab -->
				<div class="mf-tab-content" data-tab="advanced">
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Захист від спаму', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[enable_honeypot]"
										   value="1" <?php checked( $settings['enable_honeypot'] ); ?>>
									<?php esc_html_e( 'Honeypot захист', 'medici-forms-pro' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="medici_form_settings[enable_time_check]"
										   value="1" <?php checked( $settings['enable_time_check'] ); ?>>
									<?php esc_html_e( 'Перевірка часу заповнення', 'medici-forms-pro' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Згода на обробку даних', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[require_consent]"
										   value="1" <?php checked( $settings['require_consent'] ); ?>>
									<?php esc_html_e( 'Вимагати згоду', 'medici-forms-pro' ); ?>
								</label>
								<br><br>
								<textarea name="medici_form_settings[consent_text]"
										  rows="2" class="large-text"><?php echo esc_textarea( $settings['consent_text'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'CSS клас форми', 'medici-forms-pro' ); ?></th>
							<td>
								<input type="text" name="medici_form_settings[form_class]"
									   value="<?php echo esc_attr( $settings['form_class'] ); ?>"
									   class="regular-text">
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Позиція міток', 'medici-forms-pro' ); ?></th>
							<td>
								<select name="medici_form_settings[label_position]">
									<option value="top" <?php selected( $settings['label_position'], 'top' ); ?>>
										<?php esc_html_e( 'Зверху', 'medici-forms-pro' ); ?>
									</option>
									<option value="inline" <?php selected( $settings['label_position'], 'inline' ); ?>>
										<?php esc_html_e( 'В рядок', 'medici-forms-pro' ); ?>
									</option>
									<option value="hidden" <?php selected( $settings['label_position'], 'hidden' ); ?>>
										<?php esc_html_e( 'Приховані', 'medici-forms-pro' ); ?>
									</option>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Зберігати заявки', 'medici-forms-pro' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="medici_form_settings[store_entries]"
										   value="1" <?php checked( $settings['store_entries'] ); ?>>
									<?php esc_html_e( 'Зберігати в базі даних', 'medici-forms-pro' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render shortcode meta box.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post Post object.
	 */
	public function render_shortcode_metabox( \WP_Post $post ): void {
		$shortcode = '[medici_form id="' . $post->ID . '"]';
		?>
		<div class="mf-shortcode-box">
			<p><?php esc_html_e( 'Використовуйте цей шорткод для вставки форми:', 'medici-forms-pro' ); ?></p>
			<code class="mf-shortcode-copy" id="mf-shortcode-code"><?php echo esc_html( $shortcode ); ?></code>
			<button type="button" class="button button-small" id="mf-copy-shortcode">
				<?php esc_html_e( 'Копіювати', 'medici-forms-pro' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Save form data.
	 *
	 * @since 1.0.0
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_form( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['medici_form_builder_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_key( $_POST['medici_form_builder_nonce'] ), 'medici_form_builder' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save fields.
		if ( isset( $_POST['medici_form_fields'] ) && is_array( $_POST['medici_form_fields'] ) ) {
			$fields = $this->sanitize_fields( $_POST['medici_form_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			update_post_meta( $post_id, '_medici_form_fields', $fields );
		} else {
			delete_post_meta( $post_id, '_medici_form_fields' );
		}

		// Save settings.
		if ( isset( $_POST['medici_form_settings'] ) && is_array( $_POST['medici_form_settings'] ) ) {
			$settings = $this->sanitize_settings( $_POST['medici_form_settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			update_post_meta( $post_id, '_medici_form_settings', $settings );
		}
	}

	/**
	 * Sanitize fields data.
	 *
	 * @since 1.0.0
	 * @param array<int, array<string, mixed>> $fields Fields data.
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_fields( array $fields ): array {
		$sanitized = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['type'] ) ) {
				continue;
			}

			$sanitized_field = array(
				'id'          => sanitize_key( $field['id'] ?? Helpers::generate_field_id() ),
				'type'        => sanitize_key( $field['type'] ),
				'label'       => sanitize_text_field( $field['label'] ?? '' ),
				'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
				'required'    => ! empty( $field['required'] ),
				'css_class'   => sanitize_text_field( $field['css_class'] ?? '' ),
				'width'       => sanitize_text_field( $field['width'] ?? '100' ),
			);

			// Type-specific options.
			if ( isset( $field['options'] ) ) {
				$sanitized_field['options'] = sanitize_textarea_field( $field['options'] );
			}

			if ( isset( $field['rows'] ) ) {
				$sanitized_field['rows'] = absint( $field['rows'] );
			}

			if ( isset( $field['content'] ) ) {
				$sanitized_field['content'] = wp_kses_post( $field['content'] );
			}

			if ( isset( $field['heading_level'] ) ) {
				$sanitized_field['heading_level'] = absint( $field['heading_level'] );
			}

			if ( isset( $field['default_value'] ) ) {
				$sanitized_field['default_value'] = sanitize_text_field( $field['default_value'] );
			}

			$sanitized[] = $sanitized_field;
		}

		return $sanitized;
	}

	/**
	 * Sanitize settings data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $settings Settings data.
	 * @return array<string, mixed>
	 */
	private function sanitize_settings( array $settings ): array {
		$defaults  = Helpers::get_default_form_settings();
		$sanitized = array();

		// Text fields.
		$text_fields = array(
			'submit_text',
			'submit_processing',
			'admin_email_subject',
			'user_email_subject',
			'form_class',
			'entry_title_format',
		);

		foreach ( $text_fields as $key ) {
			$sanitized[ $key ] = isset( $settings[ $key ] )
				? sanitize_text_field( $settings[ $key ] )
				: $defaults[ $key ];
		}

		// Textarea fields.
		$textarea_fields = array(
			'success_message',
			'error_message',
			'consent_text',
			'user_email_message',
		);

		foreach ( $textarea_fields as $key ) {
			$sanitized[ $key ] = isset( $settings[ $key ] )
				? sanitize_textarea_field( $settings[ $key ] )
				: $defaults[ $key ];
		}

		// Email fields.
		$sanitized['admin_email_to'] = isset( $settings['admin_email_to'] )
			? sanitize_text_field( $settings['admin_email_to'] )
			: $defaults['admin_email_to'];

		// URL fields.
		$sanitized['redirect_url'] = isset( $settings['redirect_url'] )
			? esc_url_raw( $settings['redirect_url'] )
			: '';

		// Checkbox fields.
		$checkbox_fields = array(
			'enable_redirect',
			'enable_admin_email',
			'enable_user_email',
			'enable_honeypot',
			'enable_time_check',
			'require_consent',
			'enable_ajax',
			'store_entries',
		);

		foreach ( $checkbox_fields as $key ) {
			$sanitized[ $key ] = ! empty( $settings[ $key ] );
		}

		// Select fields.
		$sanitized['label_position'] = isset( $settings['label_position'] )
									   && in_array( $settings['label_position'], array( 'top', 'inline', 'hidden' ), true )
			? $settings['label_position']
			: 'top';

		$sanitized['field_size'] = isset( $settings['field_size'] )
								   && in_array( $settings['field_size'], array( 'small', 'medium', 'large' ), true )
			? $settings['field_size']
			: 'medium';

		return $sanitized;
	}

	/**
	 * AJAX: Add new field.
	 *
	 * @since 1.0.0
	 */
	public function ajax_add_field(): void {
		check_ajax_referer( 'medici_forms_builder', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Доступ заборонено.', 'medici-forms-pro' ) ) );
		}

		$type        = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : 'text';
		$index       = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		$field_types = Helpers::get_field_types();

		if ( ! isset( $field_types[ $type ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Невідомий тип поля.', 'medici-forms-pro' ) ) );
		}

		$field = array(
			'id'          => Helpers::generate_field_id(),
			'type'        => $type,
			'label'       => $field_types[ $type ]['label'],
			'placeholder' => '',
			'required'    => false,
			'css_class'   => '',
			'width'       => '100',
		);

		ob_start();
		$this->render_field_item( $field, $index );
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'  => $html,
				'field' => $field,
			)
		);
	}
}

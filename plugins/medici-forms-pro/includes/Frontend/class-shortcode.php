<?php
/**
 * Shortcode Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Frontend;

use MediciForms\Helpers;
use MediciForms\Security;
use MediciForms\Plugin;
use MediciForms\Post_Types\Form;

/**
 * Shortcode Class - Renders forms via shortcode.
 * This bypasses Gutenberg HTML sanitization.
 *
 * @since 1.0.0
 */
class Shortcode {

	/**
	 * Register shortcode.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_shortcode( 'medici_form', array( $this, 'render' ) );
	}

	/**
	 * Render form shortcode.
	 *
	 * @since 1.0.0
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'title' => '',
				'class' => '',
			),
			$atts,
			'medici_form'
		);

		$form_id = absint( $atts['id'] );

		if ( 0 === $form_id ) {
			return $this->render_error( __( 'ID форми не вказано.', 'medici-forms-pro' ) );
		}

		$form = Form::get( $form_id );

		if ( ! $form || 'publish' !== $form->post_status ) {
			return $this->render_error( __( 'Форма не знайдена.', 'medici-forms-pro' ) );
		}

		$fields   = Helpers::get_form_fields( $form_id );
		$settings = Helpers::get_form_settings( $form_id );

		if ( empty( $fields ) ) {
			return $this->render_error( __( 'Форма не містить полів.', 'medici-forms-pro' ) );
		}

		ob_start();
		$this->render_form( $form_id, $fields, $settings, $atts );
		return (string) ob_get_clean();
	}

	/**
	 * Render the form.
	 *
	 * @since 1.0.0
	 * @param int                    $form_id  Form ID.
	 * @param array<int, array<string, mixed>> $fields   Form fields.
	 * @param array<string, mixed>   $settings Form settings.
	 * @param array<string, string>  $atts     Shortcode attributes.
	 */
	private function render_form( int $form_id, array $fields, array $settings, array $atts ): void {
		$form_class = 'medici-form';
		$form_class .= ' medici-form--' . esc_attr( Plugin::get_option( 'form_style', 'modern' ) );
		$form_class .= ' medici-form--labels-' . esc_attr( $settings['label_position'] );

		if ( ! empty( $settings['form_class'] ) ) {
			$form_class .= ' ' . esc_attr( $settings['form_class'] );
		}

		if ( ! empty( $atts['class'] ) ) {
			$form_class .= ' ' . esc_attr( $atts['class'] );
		}

		$enable_ajax = $settings['enable_ajax'] ?? true;
		?>
		<div class="medici-form-wrapper" id="medici-form-<?php echo esc_attr( (string) $form_id ); ?>">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="medici-form-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<form class="<?php echo esc_attr( $form_class ); ?>"
				  method="post"
				  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				  data-form-id="<?php echo esc_attr( (string) $form_id ); ?>"
				  data-ajax="<?php echo $enable_ajax ? 'true' : 'false'; ?>"
				  novalidate>

				<?php echo Security::get_nonce_field( $form_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<input type="hidden" name="action" value="medici_form_submit">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
				<input type="hidden" name="page_url" value="<?php echo esc_url( $this->get_current_url() ); ?>">

				<?php
				// Honeypot.
				if ( ! empty( $settings['enable_honeypot'] ) ) {
					echo Security::get_honeypot_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				// Timestamp.
				if ( ! empty( $settings['enable_time_check'] ) ) {
					echo Security::get_timestamp_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<div class="medici-form-fields">
					<?php foreach ( $fields as $field ) : ?>
						<?php $this->render_field( $field, $settings ); ?>
					<?php endforeach; ?>
				</div>

				<?php if ( ! empty( $settings['require_consent'] ) ) : ?>
					<div class="medici-form-field medici-form-field--consent">
						<label class="medici-form-checkbox">
							<input type="checkbox" name="consent" value="1" required>
							<span class="medici-form-checkbox__text">
								<?php echo wp_kses_post( $settings['consent_text'] ); ?>
								<span class="medici-form-required">*</span>
							</span>
						</label>
					</div>
				<?php endif; ?>

				<?php $this->render_recaptcha( $form_id ); ?>

				<div class="medici-form-submit">
					<button type="submit" class="medici-form-button">
						<span class="medici-form-button__text">
							<?php echo esc_html( $settings['submit_text'] ); ?>
						</span>
						<span class="medici-form-button__loading" style="display:none;">
							<?php echo esc_html( $settings['submit_processing'] ); ?>
						</span>
					</button>
				</div>

				<div class="medici-form-messages" aria-live="polite">
					<div class="medici-form-message medici-form-message--success" style="display:none;">
						<?php echo wp_kses_post( $settings['success_message'] ); ?>
					</div>
					<div class="medici-form-message medici-form-message--error" style="display:none;">
						<?php echo wp_kses_post( $settings['error_message'] ); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render single field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_field( array $field, array $settings ): void {
		$type  = $field['type'] ?? 'text';
		$id    = $field['id'] ?? '';
		$name  = 'fields[' . esc_attr( $id ) . ']';
		$width = $field['width'] ?? '100';

		$field_class = 'medici-form-field';
		$field_class .= ' medici-form-field--' . esc_attr( $type );
		$field_class .= ' medici-form-field--width-' . esc_attr( $width );

		if ( ! empty( $field['css_class'] ) ) {
			$field_class .= ' ' . esc_attr( $field['css_class'] );
		}

		if ( ! empty( $field['required'] ) ) {
			$field_class .= ' medici-form-field--required';
		}
		?>
		<div class="<?php echo esc_attr( $field_class ); ?>">
			<?php
			switch ( $type ) {
				case 'text':
				case 'email':
				case 'phone':
				case 'url':
				case 'number':
				case 'date':
				case 'time':
					$this->render_input_field( $field, $name, $settings );
					break;

				case 'textarea':
					$this->render_textarea_field( $field, $name, $settings );
					break;

				case 'select':
					$this->render_select_field( $field, $name, $settings );
					break;

				case 'radio':
				case 'checkbox':
					$this->render_choice_field( $field, $name, $settings );
					break;

				case 'name':
					$this->render_name_field( $field, $name, $settings );
					break;

				case 'hidden':
					$this->render_hidden_field( $field, $name );
					break;

				case 'file':
					$this->render_file_field( $field, $name, $settings );
					break;

				case 'html':
					$this->render_html_field( $field );
					break;

				case 'divider':
					$this->render_divider_field();
					break;

				case 'heading':
					$this->render_heading_field( $field );
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render input field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_input_field( array $field, string $name, array $settings ): void {
		$type        = $field['type'] ?? 'text';
		$input_type  = $type;
		$input_attrs = array();

		// Map field types to HTML input types.
		$type_map = array(
			'phone' => 'tel',
		);

		if ( isset( $type_map[ $type ] ) ) {
			$input_type = $type_map[ $type ];
		}

		// Build attributes.
		if ( ! empty( $field['required'] ) ) {
			$input_attrs[] = 'required';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$input_attrs[] = 'placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		}

		// Phone pattern.
		if ( 'phone' === $type ) {
			$input_attrs[] = 'pattern="[0-9+\-\s\(\)]{10,}"';
		}

		$this->render_label( $field, $settings );
		?>
		<input type="<?php echo esc_attr( $input_type ); ?>"
			   name="<?php echo esc_attr( $name ); ?>"
			   id="field-<?php echo esc_attr( $field['id'] ); ?>"
			   class="medici-form-input"
			<?php echo implode( ' ', $input_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
	}

	/**
	 * Render textarea field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_textarea_field( array $field, string $name, array $settings ): void {
		$rows = $field['rows'] ?? 5;

		$this->render_label( $field, $settings );
		?>
		<textarea name="<?php echo esc_attr( $name ); ?>"
				  id="field-<?php echo esc_attr( $field['id'] ); ?>"
				  class="medici-form-textarea"
				  rows="<?php echo esc_attr( (string) $rows ); ?>"
			<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
			<?php echo ! empty( $field['placeholder'] ) ? 'placeholder="' . esc_attr( $field['placeholder'] ) . '"' : ''; ?>></textarea>
		<?php
	}

	/**
	 * Render select field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_select_field( array $field, string $name, array $settings ): void {
		$options = $this->parse_options( $field['options'] ?? '' );

		$this->render_label( $field, $settings );
		?>
		<select name="<?php echo esc_attr( $name ); ?>"
				id="field-<?php echo esc_attr( $field['id'] ); ?>"
				class="medici-form-select"
			<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
			<?php if ( ! empty( $field['placeholder'] ) ) : ?>
				<option value=""><?php echo esc_html( $field['placeholder'] ); ?></option>
			<?php endif; ?>
			<?php foreach ( $options as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>">
					<?php echo esc_html( $option ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render choice field (radio/checkbox).
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_choice_field( array $field, string $name, array $settings ): void {
		$type    = $field['type'];
		$options = $this->parse_options( $field['options'] ?? '' );

		// For checkbox, use array name.
		if ( 'checkbox' === $type ) {
			$name .= '[]';
		}

		$this->render_label( $field, $settings, false );
		?>
		<div class="medici-form-choices medici-form-choices--<?php echo esc_attr( $type ); ?>">
			<?php foreach ( $options as $index => $option ) : ?>
				<label class="medici-form-choice">
					<input type="<?php echo esc_attr( $type ); ?>"
						   name="<?php echo esc_attr( $name ); ?>"
						   value="<?php echo esc_attr( $option ); ?>"
						<?php echo ( 'radio' === $type && ! empty( $field['required'] ) ) ? 'required' : ''; ?>>
					<span class="medici-form-choice__text"><?php echo esc_html( $option ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render name field (first + last name).
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_name_field( array $field, string $name, array $settings ): void {
		$this->render_label( $field, $settings );
		?>
		<div class="medici-form-name-fields">
			<div class="medici-form-name-field">
				<input type="text"
					   name="<?php echo esc_attr( $name ); ?>[first]"
					   id="field-<?php echo esc_attr( $field['id'] ); ?>-first"
					   class="medici-form-input"
					   placeholder="<?php esc_attr_e( "Ім'я", 'medici-forms-pro' ); ?>"
					<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
			</div>
			<div class="medici-form-name-field">
				<input type="text"
					   name="<?php echo esc_attr( $name ); ?>[last]"
					   id="field-<?php echo esc_attr( $field['id'] ); ?>-last"
					   class="medici-form-input"
					   placeholder="<?php esc_attr_e( 'Прізвище', 'medici-forms-pro' ); ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * Render hidden field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field Field data.
	 * @param string               $name  Field name.
	 */
	private function render_hidden_field( array $field, string $name ): void {
		$value = $this->parse_dynamic_value( $field['default_value'] ?? '' );
		?>
		<input type="hidden"
			   name="<?php echo esc_attr( $name ); ?>"
			   value="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	/**
	 * Render file field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param string               $name     Field name.
	 * @param array<string, mixed> $settings Form settings.
	 */
	private function render_file_field( array $field, string $name, array $settings ): void {
		$this->render_label( $field, $settings );
		?>
		<input type="file"
			   name="<?php echo esc_attr( $name ); ?>"
			   id="field-<?php echo esc_attr( $field['id'] ); ?>"
			   class="medici-form-file"
			<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
		<?php
	}

	/**
	 * Render HTML field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field Field data.
	 */
	private function render_html_field( array $field ): void {
		echo '<div class="medici-form-html">';
		echo wp_kses_post( $field['content'] ?? '' );
		echo '</div>';
	}

	/**
	 * Render divider field.
	 *
	 * @since 1.0.0
	 */
	private function render_divider_field(): void {
		echo '<hr class="medici-form-divider">';
	}

	/**
	 * Render heading field.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field Field data.
	 */
	private function render_heading_field( array $field ): void {
		$level = absint( $field['heading_level'] ?? 3 );
		$level = max( 2, min( 6, $level ) );
		$tag   = 'h' . $level;

		echo '<' . esc_attr( $tag ) . ' class="medici-form-heading">';
		echo esc_html( $field['label'] ?? '' );
		echo '</' . esc_attr( $tag ) . '>';
	}

	/**
	 * Render field label.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $field    Field data.
	 * @param array<string, mixed> $settings Form settings.
	 * @param bool                 $for_attr Include for attribute.
	 */
	private function render_label( array $field, array $settings, bool $for_attr = true ): void {
		if ( 'hidden' === $settings['label_position'] || empty( $field['label'] ) ) {
			return;
		}

		$for = $for_attr ? 'for="field-' . esc_attr( $field['id'] ) . '"' : '';
		?>
		<label class="medici-form-label" <?php echo $for; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php echo esc_html( $field['label'] ); ?>
			<?php if ( ! empty( $field['required'] ) ) : ?>
				<span class="medici-form-required">*</span>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render reCAPTCHA.
	 *
	 * @since 1.0.0
	 * @param int $form_id Form ID.
	 */
	private function render_recaptcha( int $form_id ): void {
		if ( ! Plugin::get_option( 'enable_recaptcha', false ) ) {
			return;
		}

		$site_key = Plugin::get_option( 'recaptcha_site_key', '' );
		$version  = Plugin::get_option( 'recaptcha_version', 'v3' );

		if ( empty( $site_key ) ) {
			return;
		}

		if ( 'v2_checkbox' === $version ) {
			?>
			<div class="medici-form-recaptcha">
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			</div>
			<?php
		} elseif ( 'v3' === $version ) {
			?>
			<input type="hidden" name="recaptcha_token" class="mf-recaptcha-token">
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					var form = document.querySelector('[data-form-id="<?php echo esc_js( (string) $form_id ); ?>"]');
					if (form && typeof grecaptcha !== 'undefined') {
						form.addEventListener('submit', function(e) {
							e.preventDefault();
							grecaptcha.ready(function() {
								grecaptcha.execute('<?php echo esc_js( $site_key ); ?>', {action: 'submit'}).then(function(token) {
									form.querySelector('.mf-recaptcha-token').value = token;
									form.submit();
								});
							});
						});
					}
				});
			</script>
			<?php
		}
	}

	/**
	 * Parse options string to array.
	 *
	 * @since 1.0.0
	 * @param string $options Options string (one per line).
	 * @return array<int, string>
	 */
	private function parse_options( string $options ): array {
		if ( empty( $options ) ) {
			return array();
		}

		$lines = explode( "\n", $options );
		return array_filter( array_map( 'trim', $lines ) );
	}

	/**
	 * Parse dynamic value.
	 *
	 * @since 1.0.0
	 * @param string $value Value with possible placeholders.
	 * @return string
	 */
	private function parse_dynamic_value( string $value ): string {
		$replacements = array(
			'{page_url}'    => $this->get_current_url(),
			'{page_title}'  => wp_title( '', false ),
			'{user_ip}'     => Helpers::get_client_ip(),
			'{user_id}'     => (string) get_current_user_id(),
			'{date}'        => wp_date( get_option( 'date_format' ) ),
			'{time}'        => wp_date( get_option( 'time_format' ) ),
		);

		// UTM parameters.
		$utm = Helpers::get_utm_params();
		foreach ( $utm as $key => $utm_value ) {
			$replacements[ '{' . $key . '}' ] = $utm_value;
		}

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $value );
	}

	/**
	 * Get current page URL.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_current_url(): string {
		global $wp;
		return home_url( add_query_arg( array(), $wp->request ) );
	}

	/**
	 * Render error message.
	 *
	 * @since 1.0.0
	 * @param string $message Error message.
	 * @return string
	 */
	private function render_error( string $message ): string {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<div class="medici-form-error">' . esc_html( $message ) . '</div>';
		}

		return '';
	}
}

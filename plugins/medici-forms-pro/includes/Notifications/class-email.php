<?php
/**
 * Email Notifications Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Notifications;

use MediciForms\Plugin;
use MediciForms\Helpers;

/**
 * Email Class.
 *
 * @since 1.0.0
 */
class Email {

	/**
	 * Send admin notification email.
	 *
	 * @since 1.0.0
	 * @param \WP_Post             $form       Form post object.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param array<string, mixed> $settings   Form settings.
	 * @param int                  $entry_id   Entry ID.
	 * @return bool
	 */
	public function send_admin_notification( \WP_Post $form, array $entry_data, array $settings, int $entry_id ): bool {
		$to = $settings['admin_email_to'] ?? get_option( 'admin_email' );

		if ( empty( $to ) ) {
			return false;
		}

		$subject = $this->parse_subject( $settings['admin_email_subject'] ?? __( 'Нова заявка з форми', 'medici-forms-pro' ), $entry_data, $form );
		$body    = $this->build_admin_email_body( $form, $entry_data, $entry_id );
		$headers = $this->get_headers();

		return wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Send user notification email.
	 *
	 * @since 1.0.0
	 * @param \WP_Post             $form       Form post object.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param array<string, mixed> $settings   Form settings.
	 * @return bool
	 */
	public function send_user_notification( \WP_Post $form, array $entry_data, array $settings ): bool {
		$user_email = $this->get_user_email( $entry_data );

		if ( empty( $user_email ) ) {
			return false;
		}

		$subject = $this->parse_subject( $settings['user_email_subject'] ?? __( 'Дякуємо за вашу заявку', 'medici-forms-pro' ), $entry_data, $form );
		$body    = $this->build_user_email_body( $form, $entry_data, $settings );
		$headers = $this->get_headers();

		return wp_mail( $user_email, $subject, $body, $headers );
	}

	/**
	 * Get email headers.
	 *
	 * @since 1.0.0
	 * @return array<int, string>
	 */
	private function get_headers(): array {
		$from_name  = Plugin::get_option( 'email_from_name', get_bloginfo( 'name' ) );
		$from_email = Plugin::get_option( 'email_from_address', get_option( 'admin_email' ) );

		return array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);
	}

	/**
	 * Parse email subject with placeholders.
	 *
	 * @since 1.0.0
	 * @param string               $subject    Subject template.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param \WP_Post             $form       Form post object.
	 * @return string
	 */
	private function parse_subject( string $subject, array $entry_data, \WP_Post $form ): string {
		$replacements = array(
			'{form_name}' => $form->post_title,
			'{site_name}' => get_bloginfo( 'name' ),
			'{date}'      => wp_date( get_option( 'date_format' ) ),
		);

		// Add field values.
		foreach ( $entry_data as $field_id => $field ) {
			if ( '_meta' === $field_id || ! is_array( $field ) ) {
				continue;
			}
			$value = $field['value'] ?? '';
			$replacements[ '{' . $field_id . '}' ] = is_array( $value ) ? implode( ', ', $value ) : $value;
		}

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
	}

	/**
	 * Build admin email body.
	 *
	 * @since 1.0.0
	 * @param \WP_Post             $form       Form post object.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param int                  $entry_id   Entry ID.
	 * @return string
	 */
	private function build_admin_email_body( \WP_Post $form, array $entry_data, int $entry_id ): string {
		$template = Plugin::get_option( 'email_template', 'default' );
		$meta     = $entry_data['_meta'] ?? array();

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1f2937; margin: 0; padding: 0; background-color: #f3f4f6; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 30px; margin: 20px 0; }
				.header { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; margin: -30px -30px 30px; }
				.header h1 { margin: 0; font-size: 24px; font-weight: 600; }
				.header p { margin: 10px 0 0; opacity: 0.9; }
				.field { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
				.field:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
				.field-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 5px; }
				.field-value { font-size: 16px; color: #1f2937; }
				.meta { background: #f9fafb; border-radius: 6px; padding: 15px; margin-top: 20px; font-size: 13px; color: #6b7280; }
				.meta-item { margin: 5px 0; }
				.meta-label { font-weight: 500; }
				.button { display: inline-block; background: #2563eb; color: white !important; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 500; margin-top: 20px; }
				.footer { text-align: center; padding: 20px; color: #9ca3af; font-size: 13px; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="card">
					<div class="header">
						<h1><?php echo esc_html__( 'Нова заявка', 'medici-forms-pro' ); ?></h1>
						<p><?php echo esc_html( $form->post_title ); ?></p>
					</div>

					<?php foreach ( $entry_data as $field_id => $field ) : ?>
						<?php
						if ( '_meta' === $field_id || ! is_array( $field ) ) {
							continue;
						}
						$value = $field['value'] ?? '';
						if ( is_array( $value ) ) {
							$value = implode( ', ', $value );
						}
						if ( '' === $value ) {
							continue;
						}
						?>
						<div class="field">
							<div class="field-label"><?php echo esc_html( $field['label'] ?? $field_id ); ?></div>
							<div class="field-value"><?php echo esc_html( $value ); ?></div>
						</div>
					<?php endforeach; ?>

					<div class="meta">
						<div class="meta-item">
							<span class="meta-label"><?php echo esc_html__( 'Дата:', 'medici-forms-pro' ); ?></span>
							<?php echo esc_html( wp_date( 'd.m.Y H:i:s' ) ); ?>
						</div>
						<?php if ( ! empty( $meta['page_url'] ) ) : ?>
							<div class="meta-item">
								<span class="meta-label"><?php echo esc_html__( 'Сторінка:', 'medici-forms-pro' ); ?></span>
								<?php echo esc_html( $meta['page_url'] ); ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $meta['user_ip'] ) ) : ?>
							<div class="meta-item">
								<span class="meta-label"><?php echo esc_html__( 'IP:', 'medici-forms-pro' ); ?></span>
								<?php echo esc_html( $meta['user_ip'] ); ?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $meta['utm']['utm_source'] ) ) : ?>
							<div class="meta-item">
								<span class="meta-label"><?php echo esc_html__( 'UTM Source:', 'medici-forms-pro' ); ?></span>
								<?php echo esc_html( $meta['utm']['utm_source'] ); ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $entry_id > 0 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $entry_id . '&action=edit' ) ); ?>" class="button">
							<?php echo esc_html__( 'Переглянути в адмінці', 'medici-forms-pro' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="footer">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: site name */
							__( 'Це повідомлення надіслано з сайту %s', 'medici-forms-pro' ),
							get_bloginfo( 'name' )
						)
					);
					?>
				</div>
			</div>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build user email body.
	 *
	 * @since 1.0.0
	 * @param \WP_Post             $form       Form post object.
	 * @param array<string, mixed> $entry_data Entry data.
	 * @param array<string, mixed> $settings   Form settings.
	 * @return string
	 */
	private function build_user_email_body( \WP_Post $form, array $entry_data, array $settings ): string {
		$custom_message = $settings['user_email_message'] ?? '';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #1f2937; margin: 0; padding: 0; background-color: #f3f4f6; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 30px; }
				.header { text-align: center; margin-bottom: 30px; }
				.header h1 { color: #2563eb; margin: 0; }
				.content { color: #4b5563; }
				.summary { background: #f9fafb; border-radius: 6px; padding: 20px; margin: 20px 0; }
				.summary-item { margin: 10px 0; }
				.summary-label { font-weight: 500; color: #374151; }
				.footer { text-align: center; padding: 20px; color: #9ca3af; font-size: 13px; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="card">
					<div class="header">
						<h1><?php echo esc_html__( 'Дякуємо за вашу заявку!', 'medici-forms-pro' ); ?></h1>
					</div>

					<div class="content">
						<?php if ( ! empty( $custom_message ) ) : ?>
							<?php echo wp_kses_post( wpautop( $custom_message ) ); ?>
						<?php else : ?>
							<p><?php echo esc_html__( 'Ми отримали вашу заявку і зв\'яжемося з вами найближчим часом.', 'medici-forms-pro' ); ?></p>
						<?php endif; ?>

						<div class="summary">
							<h3><?php echo esc_html__( 'Ваша заявка:', 'medici-forms-pro' ); ?></h3>
							<?php foreach ( $entry_data as $field_id => $field ) : ?>
								<?php
								if ( '_meta' === $field_id || ! is_array( $field ) ) {
									continue;
								}
								$value = $field['value'] ?? '';
								if ( is_array( $value ) ) {
									$value = implode( ', ', $value );
								}
								if ( '' === $value ) {
									continue;
								}
								?>
								<div class="summary-item">
									<span class="summary-label"><?php echo esc_html( $field['label'] ?? $field_id ); ?>:</span>
									<?php echo esc_html( $value ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="footer">
					<?php echo esc_html( get_bloginfo( 'name' ) ); ?><br>
					<a href="<?php echo esc_url( home_url() ); ?>"><?php echo esc_html( home_url() ); ?></a>
				</div>
			</div>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Get user email from entry data.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $entry_data Entry data.
	 * @return string
	 */
	private function get_user_email( array $entry_data ): string {
		foreach ( $entry_data as $field_id => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			// Check if it's an email field.
			$type  = $field['type'] ?? '';
			$value = $field['value'] ?? '';

			if ( 'email' === $type && is_email( $value ) ) {
				return $value;
			}
		}

		return '';
	}
}

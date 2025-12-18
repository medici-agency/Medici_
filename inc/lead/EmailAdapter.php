<?php
/**
 * Email Integration Adapter
 *
 * Sends lead notifications via WordPress email.
 *
 * @package    Medici_Agency
 * @subpackage Lead
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email Adapter Class
 *
 * Handles email notifications for new leads.
 *
 * @since 2.0.0
 */
final class EmailAdapter extends AbstractIntegration {

	/**
	 * Option key for admin email
	 */
	private const OPTION_ADMIN_EMAIL = 'medici_lead_admin_email';

	/**
	 * Get integration name
	 *
	 * @since 2.0.0
	 * @return string Integration identifier.
	 */
	public function getName(): string {
		return 'Email';
	}

	/**
	 * Check if integration is enabled
	 *
	 * @since 2.0.0
	 * @return bool True if enabled.
	 */
	public function isEnabled(): bool {
		$email = $this->getAdminEmail();
		return ! empty( $email ) && is_email( $email );
	}

	/**
	 * Get admin email
	 *
	 * @since 2.0.0
	 * @return string Admin email.
	 */
	public function getAdminEmail(): string {
		$email = $this->getOption( self::OPTION_ADMIN_EMAIL );

		if ( empty( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return is_string( $email ) ? $email : '';
	}

	/**
	 * Set admin email
	 *
	 * @since 2.0.0
	 * @param string $email Email address.
	 * @return bool True on success.
	 */
	public function setAdminEmail( string $email ): bool {
		if ( ! is_email( $email ) ) {
			$this->setError( 'Invalid email address' );
			return false;
		}

		return $this->updateOption( self::OPTION_ADMIN_EMAIL, $email );
	}

	/**
	 * Send email notification
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return bool True on success.
	 */
	public function send( array $data, int $lead_id ): bool {
		if ( ! $this->isEnabled() ) {
			$this->setError( 'Email integration not configured' );
			return false;
		}

		$admin_email = $this->getAdminEmail();

		// Build email.
		$subject = $this->buildSubject( $data, $lead_id );
		$message = $this->buildMessage( $data, $lead_id );
		$headers = $this->buildHeaders( $data );

		// Send email.
		$result = wp_mail( $admin_email, $subject, $message, $headers );

		if ( ! $result ) {
			$this->setError( 'Failed to send email notification' );
			return false;
		}

		$this->logSuccess( sprintf( 'Email sent for lead #%d', $lead_id ) );
		return true;
	}

	/**
	 * Build email subject
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return string Email subject.
	 */
	private function buildSubject( array $data, int $lead_id ): string {
		return sprintf(
			'[%s] Новий лід #%d - %s',
			get_bloginfo( 'name' ),
			$lead_id,
			$data['name'] ?? 'Без імені'
		);
	}

	/**
	 * Build email headers
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data Lead data.
	 * @return array<string> Email headers.
	 */
	private function buildHeaders( array $data ): array {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
		);

		// Add Reply-To if user provided email.
		if ( ! empty( $data['email'] ) && is_email( $data['email'] ) ) {
			$headers[] = sprintf( 'Reply-To: %s <%s>', $data['name'] ?? '', $data['email'] );
		}

		return $headers;
	}

	/**
	 * Build email message HTML
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data    Lead data.
	 * @param int                  $lead_id Lead post ID.
	 * @return string Email message HTML.
	 */
	private function buildMessage( array $data, int $lead_id ): string {
		$edit_url = admin_url( 'post.php?post=' . $lead_id . '&action=edit' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html lang="uk">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
					line-height: 1.6;
					color: #333;
					max-width: 600px;
					margin: 0 auto;
					padding: 20px;
				}
				.header {
					background: #2563eb;
					color: white;
					padding: 20px;
					border-radius: 8px 8px 0 0;
					text-align: center;
				}
				.content {
					background: #f9fafb;
					padding: 30px;
					border: 1px solid #e5e7eb;
				}
				.field {
					margin-bottom: 20px;
				}
				.field-label {
					font-weight: 600;
					color: #6b7280;
					font-size: 0.875rem;
					text-transform: uppercase;
					margin-bottom: 5px;
				}
				.field-value {
					font-size: 1rem;
					color: #111827;
				}
				.button {
					display: inline-block;
					padding: 12px 24px;
					background: #2563eb;
					color: white;
					text-decoration: none;
					border-radius: 6px;
					margin-top: 20px;
				}
				.footer {
					margin-top: 30px;
					padding-top: 20px;
					border-top: 1px solid #e5e7eb;
					font-size: 0.875rem;
					color: #6b7280;
					text-align: center;
				}
			</style>
		</head>
		<body>
			<div class="header">
				<h1 style="margin: 0; font-size: 1.5rem;">📞 Новий запит на консультацію</h1>
			</div>
			<div class="content">
				<?php $this->renderField( "Ім'я", $data['name'] ?? '' ); ?>
				<?php $this->renderEmailField( $data['email'] ?? '' ); ?>
				<?php $this->renderPhoneField( $data['phone'] ?? '' ); ?>
				<?php if ( ! empty( $data['service'] ) ) : ?>
					<?php $this->renderField( 'Послуга', $data['service'] ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $data['message'] ) ) : ?>
					<?php $this->renderField( 'Повідомлення', nl2br( esc_html( $data['message'] ) ), false ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $data['page_url'] ) ) : ?>
					<?php $this->renderLinkField( 'Сторінка', $data['page_url'] ); ?>
				<?php endif; ?>
				<?php $this->renderUtmFields( $data ); ?>

				<a href="<?php echo esc_url( $edit_url ); ?>" class="button">
					Переглянути лід в адмінці
				</a>
			</div>
			<div class="footer">
				<p>Цей email надіслано автоматично з сайту <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<p><?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?></p>
			</div>
		</body>
		</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a field in email template
	 *
	 * @param string $label Label text.
	 * @param string $value Field value.
	 * @param bool   $escape Whether to escape value.
	 * @return void
	 */
	private function renderField( string $label, string $value, bool $escape = true ): void {
		if ( empty( $value ) ) {
			$value = '—';
		}
		?>
		<div class="field">
			<div class="field-label"><?php echo esc_html( $label ); ?></div>
			<div class="field-value"><?php echo $escape ? esc_html( $value ) : $value; ?></div>
		</div>
		<?php
	}

	/**
	 * Render email field with mailto link
	 *
	 * @param string $email Email address.
	 * @return void
	 */
	private function renderEmailField( string $email ): void {
		?>
		<div class="field">
			<div class="field-label">Email</div>
			<div class="field-value">
				<?php if ( ! empty( $email ) ) : ?>
					<a href="mailto:<?php echo esc_attr( $email ); ?>" style="color: #2563eb;">
						<?php echo esc_html( $email ); ?>
					</a>
				<?php else : ?>
					—
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render phone field with tel link
	 *
	 * @param string $phone Phone number.
	 * @return void
	 */
	private function renderPhoneField( string $phone ): void {
		?>
		<div class="field">
			<div class="field-label">Телефон</div>
			<div class="field-value">
				<?php if ( ! empty( $phone ) ) : ?>
					<a href="tel:<?php echo esc_attr( $phone ); ?>" style="color: #2563eb;">
						<?php echo esc_html( $phone ); ?>
					</a>
				<?php else : ?>
					—
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render link field
	 *
	 * @param string $label Label text.
	 * @param string $url   URL.
	 * @return void
	 */
	private function renderLinkField( string $label, string $url ): void {
		?>
		<div class="field">
			<div class="field-label"><?php echo esc_html( $label ); ?></div>
			<div class="field-value">
				<a href="<?php echo esc_url( $url ); ?>" style="color: #2563eb;">
					<?php echo esc_html( $url ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render UTM fields
	 *
	 * @param array<string, mixed> $data Lead data.
	 * @return void
	 */
	private function renderUtmFields( array $data ): void {
		if ( empty( $data['utm_source'] ) && empty( $data['utm_campaign'] ) ) {
			return;
		}
		?>
		<div class="field">
			<div class="field-label">UTM параметри</div>
			<div class="field-value" style="font-size: 0.875rem;">
				<?php if ( ! empty( $data['utm_source'] ) ) : ?>
					Source: <strong><?php echo esc_html( $data['utm_source'] ); ?></strong><br>
				<?php endif; ?>
				<?php if ( ! empty( $data['utm_medium'] ) ) : ?>
					Medium: <strong><?php echo esc_html( $data['utm_medium'] ); ?></strong><br>
				<?php endif; ?>
				<?php if ( ! empty( $data['utm_campaign'] ) ) : ?>
					Campaign: <strong><?php echo esc_html( $data['utm_campaign'] ); ?></strong><br>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

<?php
/**
 * Email Template - Modern Style
 *
 * Сучасний дизайн email з градієнтами, тінями та rounded corners
 *
 * @package    Medici_Agency
 * @subpackage Forms_Advanced/Templates
 * @since      1.0.0
 * @version    1.0.0
 *
 * Available variables:
 * @var string $logo_url Company logo URL
 * @var string $form_title Form title
 * @var array<string, mixed> $fields Form fields data
 * @var string $footer_text Custom footer text
 * @var string $site_name Site name
 * @var string $site_url Site URL
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html lang="uk">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $form_title ?? 'Нова заявка' ); ?></title>
	<style>
		body {
			margin: 0;
			padding: 0;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			line-height: 1.6;
		}
		.email-wrapper {
			max-width: 600px;
			margin: 40px auto;
			background: #ffffff;
			border-radius: 16px;
			overflow: hidden;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
		}
		.email-header {
			background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
			padding: 40px 32px;
			text-align: center;
		}
		.email-logo {
			max-width: 180px;
			height: auto;
			margin-bottom: 16px;
		}
		.email-title {
			color: #ffffff;
			font-size: 28px;
			font-weight: 700;
			margin: 0;
			text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}
		.email-body {
			padding: 40px 32px;
			background: #ffffff;
		}
		.email-intro {
			font-size: 16px;
			color: #4b5563;
			margin-bottom: 32px;
			padding: 16px;
			background: #f3f4f6;
			border-left: 4px solid #2563eb;
			border-radius: 4px;
		}
		.field-group {
			margin-bottom: 24px;
			padding-bottom: 24px;
			border-bottom: 1px solid #e5e7eb;
		}
		.field-group:last-child {
			border-bottom: none;
		}
		.field-label {
			display: block;
			font-size: 12px;
			font-weight: 600;
			color: #6b7280;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 6px;
		}
		.field-value {
			font-size: 16px;
			color: #111827;
			font-weight: 500;
		}
		.email-footer {
			background: #f9fafb;
			padding: 32px;
			text-align: center;
			border-top: 1px solid #e5e7eb;
		}
		.email-footer-text {
			font-size: 14px;
			color: #6b7280;
			margin: 0 0 16px 0;
		}
		.email-footer-meta {
			font-size: 12px;
			color: #9ca3af;
		}
		.email-footer-link {
			color: #2563eb;
			text-decoration: none;
			font-weight: 600;
		}
		.email-footer-link:hover {
			text-decoration: underline;
		}
		@media (max-width: 600px) {
			.email-wrapper {
				margin: 0;
				border-radius: 0;
			}
			.email-header,
			.email-body,
			.email-footer {
				padding: 24px 16px;
			}
		}
	</style>
</head>
<body>
	<div class="email-wrapper">
		<!-- Header -->
		<div class="email-header">
			<?php if ( ! empty( $logo_url ) ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ?? 'Logo' ); ?>" class="email-logo">
			<?php endif; ?>
			<h1 class="email-title"><?php echo esc_html( $form_title ?? 'Нова заявка з форми' ); ?></h1>
		</div>

		<!-- Body -->
		<div class="email-body">
			<div class="email-intro">
				<strong>📩 Отримано нову заявку</strong><br>
				Дата: <?php echo esc_html( gmdate( 'd.m.Y H:i' ) ); ?>
			</div>

			<?php if ( ! empty( $fields ) && is_array( $fields ) ) : ?>
				<?php foreach ( $fields as $field_label => $field_value ) : ?>
					<div class="field-group">
						<span class="field-label"><?php echo esc_html( $field_label ); ?></span>
						<div class="field-value"><?php echo wp_kses_post( nl2br( $field_value ) ); ?></div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<!-- Footer -->
		<div class="email-footer">
			<?php if ( ! empty( $footer_text ) ) : ?>
				<p class="email-footer-text"><?php echo wp_kses_post( $footer_text ); ?></p>
			<?php endif; ?>

			<div class="email-footer-meta">
				Відправлено з <a href="<?php echo esc_url( $site_url ?? home_url() ); ?>" class="email-footer-link"><?php echo esc_html( $site_name ?? get_bloginfo( 'name' ) ); ?></a><br>
				<small>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Всі права захищені</small>
			</div>
		</div>
	</div>
</body>
</html>

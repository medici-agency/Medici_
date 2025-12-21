<?php
/**
 * Email Template - Classic Style
 *
 * Класичний професійний дизайн email
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
			font-family: Georgia, 'Times New Roman', Times, serif;
			background-color: #f5f5f5;
			line-height: 1.8;
		}
		.email-wrapper {
			max-width: 650px;
			margin: 30px auto;
			background: #ffffff;
			border: 2px solid #d4d4d4;
		}
		.email-header {
			background: #ffffff;
			padding: 32px 40px;
			border-bottom: 3px solid #2563eb;
			text-align: center;
		}
		.email-logo {
			max-width: 200px;
			height: auto;
			margin-bottom: 20px;
		}
		.email-title {
			color: #1f2937;
			font-size: 24px;
			font-weight: 700;
			margin: 0;
			font-family: 'Times New Roman', Times, serif;
		}
		.email-body {
			padding: 40px;
			background: #ffffff;
		}
		.email-intro {
			font-size: 15px;
			color: #374151;
			margin-bottom: 30px;
			padding: 20px;
			background: #fafafa;
			border: 1px solid #e5e7eb;
		}
		.email-table {
			width: 100%;
			border-collapse: collapse;
			margin: 20px 0;
		}
		.email-table tr {
			border-bottom: 1px solid #e5e7eb;
		}
		.email-table td {
			padding: 16px 12px;
			vertical-align: top;
		}
		.email-table td:first-child {
			width: 35%;
			font-weight: 600;
			color: #4b5563;
			font-size: 14px;
		}
		.email-table td:last-child {
			color: #111827;
			font-size: 15px;
		}
		.email-footer {
			background: #fafafa;
			padding: 30px 40px;
			text-align: center;
			border-top: 2px solid #e5e7eb;
		}
		.email-footer-text {
			font-size: 14px;
			color: #6b7280;
			margin: 0 0 16px 0;
			font-style: italic;
		}
		.email-footer-meta {
			font-size: 13px;
			color: #9ca3af;
		}
		.email-footer-link {
			color: #2563eb;
			text-decoration: none;
		}
		.email-footer-link:hover {
			text-decoration: underline;
		}
		@media (max-width: 600px) {
			.email-wrapper {
				margin: 0;
				border: none;
			}
			.email-header,
			.email-body,
			.email-footer {
				padding: 20px;
			}
			.email-table td {
				display: block;
				width: 100% !important;
			}
			.email-table td:first-child {
				padding-bottom: 4px;
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
				<strong>Шановний адміністраторе,</strong><br><br>
				Отримано нову заявку через контактну форму на вашому сайті.<br>
				<strong>Дата та час:</strong> <?php echo esc_html( gmdate( 'd.m.Y о H:i' ) ); ?>
			</div>

			<?php if ( ! empty( $fields ) && is_array( $fields ) ) : ?>
				<table class="email-table">
					<?php foreach ( $fields as $field_label => $field_value ) : ?>
						<tr>
							<td><?php echo esc_html( $field_label ); ?>:</td>
							<td><?php echo wp_kses_post( nl2br( $field_value ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
		</div>

		<!-- Footer -->
		<div class="email-footer">
			<?php if ( ! empty( $footer_text ) ) : ?>
				<p class="email-footer-text"><?php echo wp_kses_post( $footer_text ); ?></p>
			<?php endif; ?>

			<div class="email-footer-meta">
				Відправлено з <a href="<?php echo esc_url( $site_url ?? home_url() ); ?>" class="email-footer-link"><?php echo esc_html( $site_name ?? get_bloginfo( 'name' ) ); ?></a><br>
				© <?php echo esc_html( gmdate( 'Y' ) ); ?> Всі права захищені
			</div>
		</div>
	</div>
</body>
</html>

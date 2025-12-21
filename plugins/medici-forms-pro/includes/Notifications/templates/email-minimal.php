<?php
/**
 * Email Template - Minimal Style
 *
 * Мінімалістичний дизайн email (text-first, clean, fast)
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
			font-family: 'Courier New', Courier, monospace;
			background-color: #ffffff;
			line-height: 1.6;
			color: #000000;
		}
		.email-wrapper {
			max-width: 600px;
			margin: 0 auto;
			padding: 40px 20px;
		}
		.email-header {
			margin-bottom: 32px;
			padding-bottom: 16px;
			border-bottom: 2px solid #000000;
		}
		.email-logo {
			max-width: 120px;
			height: auto;
			margin-bottom: 16px;
		}
		.email-title {
			color: #000000;
			font-size: 18px;
			font-weight: 700;
			margin: 0;
			text-transform: uppercase;
			letter-spacing: 1px;
		}
		.email-body {
			margin-bottom: 32px;
		}
		.email-intro {
			font-size: 14px;
			color: #000000;
			margin-bottom: 24px;
			font-weight: 600;
		}
		.field-group {
			margin-bottom: 20px;
		}
		.field-label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			color: #000000;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 4px;
		}
		.field-value {
			font-size: 14px;
			color: #000000;
			padding-left: 8px;
			border-left: 3px solid #000000;
		}
		.email-footer {
			padding-top: 24px;
			border-top: 1px solid #cccccc;
			text-align: left;
		}
		.email-footer-text {
			font-size: 12px;
			color: #666666;
			margin: 0 0 12px 0;
		}
		.email-footer-meta {
			font-size: 11px;
			color: #999999;
		}
		.email-footer-link {
			color: #000000;
			text-decoration: underline;
		}
		@media (max-width: 600px) {
			.email-wrapper {
				padding: 20px 16px;
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
			<h1 class="email-title"><?php echo esc_html( $form_title ?? 'Нова заявка' ); ?></h1>
		</div>

		<!-- Body -->
		<div class="email-body">
			<div class="email-intro">
				→ Нова заявка отримана<?php echo esc_html( gmdate( ' — d.m.Y H:i' ) ); ?>
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
				<?php echo esc_html( $site_name ?? get_bloginfo( 'name' ) ); ?> · <a href="<?php echo esc_url( $site_url ?? home_url() ); ?>" class="email-footer-link"><?php echo esc_html( parse_url( $site_url ?? home_url(), PHP_URL_HOST ) ); ?></a> · © <?php echo esc_html( gmdate( 'Y' ) ); ?>
			</div>
		</div>
	</div>
</body>
</html>

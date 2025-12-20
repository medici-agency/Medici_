<?php
/**
 * Rules Builder Admin View
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mcn-rules-page">
	<h1><?php esc_html_e( 'Умовні правила відображення', 'medici-cookie-notice' ); ?></h1>

	<div class="mcn-rules-description">
		<p>
			<?php esc_html_e( 'Створіть правила для контролю коли банер cookie буде показуватися або приховуватися. Правила оцінюються зверху вниз, перше спрацьоване правило визначає поведінку.', 'medici-cookie-notice' ); ?>
		</p>
	</div>

	<div class="mcn-rules-toolbar">
		<button type="button" class="button button-primary" id="mcn-add-group-btn">
			<span class="dashicons dashicons-plus-alt2"></span>
			<?php esc_html_e( 'Додати групу правил', 'medici-cookie-notice' ); ?>
		</button>

		<button type="button" class="button button-secondary" id="mcn-save-rules">
			<span class="dashicons dashicons-cloud-upload"></span>
			<?php esc_html_e( 'Зберегти правила', 'medici-cookie-notice' ); ?>
		</button>
	</div>

	<div class="mcn-rules-container" id="mcn-rule-groups">
		<!-- Rule groups will be loaded via AJAX -->
		<div class="mcn-loading">
			<span class="spinner is-active"></span>
			<?php esc_html_e( 'Завантаження правил...', 'medici-cookie-notice' ); ?>
		</div>
	</div>

	<div class="mcn-rules-info">
		<h3><?php esc_html_e( 'Як це працює', 'medici-cookie-notice' ); ?></h3>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Група правил', 'medici-cookie-notice' ); ?>:</strong>
				<?php esc_html_e( 'Контейнер для кількох умов з одною дією (показати/приховати).', 'medici-cookie-notice' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Оператор AND', 'medici-cookie-notice' ); ?>:</strong>
				<?php esc_html_e( 'Всі правила в групі мають бути істинними.', 'medici-cookie-notice' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Оператор OR', 'medici-cookie-notice' ); ?>:</strong>
				<?php esc_html_e( 'Достатньо одного істинного правила в групі.', 'medici-cookie-notice' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Пріоритет', 'medici-cookie-notice' ); ?>:</strong>
				<?php esc_html_e( 'Перетягуйте групи для зміни порядку. Перша спрацьована група визначає результат.', 'medici-cookie-notice' ); ?>
			</li>
		</ul>

		<h4><?php esc_html_e( 'Доступні типи правил', 'medici-cookie-notice' ); ?></h4>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Тип', 'medici-cookie-notice' ); ?></th>
					<th><?php esc_html_e( 'Опис', 'medici-cookie-notice' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>page_type</code></td>
					<td><?php esc_html_e( 'Тип сторінки (головна, блог, архів, 404 тощо)', 'medici-cookie-notice' ); ?></td>
				</tr>
				<tr>
					<td><code>user_type</code></td>
					<td><?php esc_html_e( 'Авторизований користувач або гість', 'medici-cookie-notice' ); ?></td>
				</tr>
				<tr>
					<td><code>user_role</code></td>
					<td><?php esc_html_e( 'Роль користувача (адміністратор, редактор тощо)', 'medici-cookie-notice' ); ?></td>
				</tr>
				<tr>
					<td><code>device</code></td>
					<td><?php esc_html_e( 'Тип пристрою (комп\'ютер, мобільний, планшет)', 'medici-cookie-notice' ); ?></td>
				</tr>
				<tr>
					<td><code>url</code></td>
					<td><?php esc_html_e( 'URL сторінки (містить, починається з, regex)', 'medici-cookie-notice' ); ?></td>
				</tr>
				<tr>
					<td><code>geo</code></td>
					<td><?php esc_html_e( 'Геолокація (країна, регіон)', 'medici-cookie-notice' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<!-- Modal for log details -->
<div id="mcn-log-details-modal" class="mcn-modal" style="display: none;">
	<div class="mcn-modal-content">
		<button type="button" class="mcn-modal-close">&times;</button>
		<div id="mcn-log-details-content"></div>
	</div>
</div>

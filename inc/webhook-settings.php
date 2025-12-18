<?php
/**
 * Webhook Admin Settings Page
 *
 * Provides admin interface for managing webhook endpoints.
 *
 * @package    Medici_Agency
 * @subpackage Webhooks
 * @since      1.5.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook Admin Settings Class
 *
 * @since 1.5.0
 */
final class Webhook_Admin_Settings {

	/**
	 * Page slug
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'medici-webhooks';

	/**
	 * Initialize admin settings
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		add_action( 'admin_menu', array( $self, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_assets' ) );
		add_action( 'admin_post_medici_save_webhook', array( $self, 'handle_save_webhook' ) );
		add_action( 'admin_post_medici_delete_webhook', array( $self, 'handle_delete_webhook' ) );
		add_action( 'admin_post_medici_clear_webhook_logs', array( $self, 'handle_clear_logs' ) );
	}

	/**
	 * Add menu page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'edit.php?post_type=medici_lead',
			__( 'Webhooks', 'medici.agency' ),
			__( '🔗 Webhooks', 'medici.agency' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 1.5.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'medici_lead_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'medici-webhook-admin',
			get_stylesheet_directory_uri() . '/css/admin/webhook-admin.css',
			array(),
			filemtime( get_stylesheet_directory() . '/css/admin/webhook-admin.css' )
		);

		wp_enqueue_script(
			'medici-webhook-admin',
			get_stylesheet_directory_uri() . '/js/admin/webhook-admin.js',
			array(), // Vanilla JS - no jQuery dependency.
			filemtime( get_stylesheet_directory() . '/js/admin/webhook-admin.js' ),
			true
		);

		wp_localize_script(
			'medici-webhook-admin',
			'mediciWebhook',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'medici_webhook_test' ),
				'i18n'    => array(
					'testing'     => __( 'Тестування...', 'medici.agency' ),
					'testSuccess' => __( 'Успішно!', 'medici.agency' ),
					'testError'   => __( 'Помилка', 'medici.agency' ),
					'confirm'     => __( 'Ви впевнені?', 'medici.agency' ),
				),
			)
		);
	}

	/**
	 * Render settings page
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action   = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		$edit_id  = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
		$tab      = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'webhooks';
		$webhooks = Webhook_Sender::get_webhooks();

		?>
		<div class="wrap medici-webhook-settings">
			<h1><?php esc_html_e( '🔗 Webhooks', 'medici.agency' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&tab=webhooks' ) ); ?>"
					class="nav-tab <?php echo 'webhooks' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Вебхуки', 'medici.agency' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&tab=logs' ) ); ?>"
					class="nav-tab <?php echo 'logs' === $tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Логи', 'medici.agency' ); ?>
				</a>
			</nav>

			<?php
			if ( 'logs' === $tab ) {
				$this->render_logs_tab();
			} elseif ( 'add' === $action || 'edit' === $action ) {
				$this->render_webhook_form( $action, $edit_id );
			} else {
				$this->render_webhooks_list( $webhooks );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render webhooks list
	 *
	 * @since 1.5.0
	 * @param array $webhooks Array of webhooks.
	 * @return void
	 */
	private function render_webhooks_list( array $webhooks ): void {
		?>
		<div class="tablenav top">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&action=add' ) ); ?>"
				class="button button-primary">
				<?php esc_html_e( '+ Додати вебхук', 'medici.agency' ); ?>
			</a>
		</div>

		<?php if ( empty( $webhooks ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Вебхуки не налаштовані. Додайте перший вебхук для автоматичної відправки даних на зовнішні сервіси.', 'medici.agency' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Назва', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'URL', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Події', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Дії', 'medici.agency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $webhooks as $webhook ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $webhook['name'] ?? __( 'Без назви', 'medici.agency' ) ); ?></strong>
							</td>
							<td>
								<code><?php echo esc_html( $this->truncate_url( $webhook['url'] ?? '' ) ); ?></code>
							</td>
							<td>
								<?php
								$event_labels = array();
								foreach ( $webhook['events'] ?? array() as $event ) {
									$event_labels[] = Webhook_Sender::EVENTS[ $event ] ?? $event;
								}
								echo esc_html( implode( ', ', $event_labels ) );
								?>
							</td>
							<td>
								<?php if ( ! empty( $webhook['enabled'] ) ) : ?>
									<span class="status-badge status-active"><?php esc_html_e( '✅ Активний', 'medici.agency' ); ?></span>
								<?php else : ?>
									<span class="status-badge status-inactive"><?php esc_html_e( '⏸ Вимкнено', 'medici.agency' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<button type="button"
										class="button button-small test-webhook"
										data-webhook-id="<?php echo esc_attr( $webhook['id'] ?? '' ); ?>">
									<?php esc_html_e( '🧪 Тест', 'medici.agency' ); ?>
								</button>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&action=edit&id=' . ( $webhook['id'] ?? '' ) ) ); ?>"
									class="button button-small">
									<?php esc_html_e( '✏️ Редагувати', 'medici.agency' ); ?>
								</a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=medici_delete_webhook&id=' . ( $webhook['id'] ?? '' ) ), 'delete_webhook_' . ( $webhook['id'] ?? '' ) ) ); ?>"
									class="button button-small button-link-delete delete-webhook"
									onclick="return confirm('<?php esc_attr_e( 'Видалити цей вебхук?', 'medici.agency' ); ?>');">
									<?php esc_html_e( '🗑️ Видалити', 'medici.agency' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<div class="card" style="margin-top: 20px;">
			<h3><?php esc_html_e( '📖 Доступні події', 'medici.agency' ); ?></h3>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Подія', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Опис', 'medici.agency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>new_lead</code></td>
						<td><?php esc_html_e( 'Викликається при створенні нового ліда з форми консультації', 'medici.agency' ); ?></td>
					</tr>
					<tr>
						<td><code>lead_status_changed</code></td>
						<td><?php esc_html_e( 'Викликається при зміні статусу ліда (new → contacted → closed)', 'medici.agency' ); ?></td>
					</tr>
					<tr>
						<td><code>newsletter_subscribe</code></td>
						<td><?php esc_html_e( 'Викликається при підписці на розсилку через форму newsletter', 'medici.agency' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render webhook form
	 *
	 * @since 1.5.0
	 * @param string $action  Form action (add/edit).
	 * @param string $edit_id Webhook ID for editing.
	 * @return void
	 */
	private function render_webhook_form( string $action, string $edit_id ): void {
		$webhook = array(
			'name'       => '',
			'url'        => '',
			'events'     => array(),
			'enabled'    => true,
			'auth_type'  => '',
			'auth_value' => '',
		);

		if ( 'edit' === $action && $edit_id ) {
			$webhooks = Webhook_Sender::get_webhooks();
			foreach ( $webhooks as $wh ) {
				if ( ( $wh['id'] ?? '' ) === $edit_id ) {
					$webhook = $wh;
					break;
				}
			}
		}

		$form_title = 'edit' === $action
			? __( 'Редагувати вебхук', 'medici.agency' )
			: __( 'Додати вебхук', 'medici.agency' );
		?>
		<h2><?php echo esc_html( $form_title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="webhook-form">
			<input type="hidden" name="action" value="medici_save_webhook">
			<input type="hidden" name="webhook_id" value="<?php echo esc_attr( $edit_id ); ?>">
			<?php wp_nonce_field( 'save_webhook', 'webhook_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="webhook_name"><?php esc_html_e( 'Назва', 'medici.agency' ); ?></label>
					</th>
					<td>
						<input type="text"
								id="webhook_name"
								name="webhook_name"
								value="<?php echo esc_attr( $webhook['name'] ); ?>"
								class="regular-text"
								placeholder="<?php esc_attr_e( 'Наприклад: CRM Integration', 'medici.agency' ); ?>"
								required>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="webhook_url"><?php esc_html_e( 'URL', 'medici.agency' ); ?></label>
					</th>
					<td>
						<input type="url"
								id="webhook_url"
								name="webhook_url"
								value="<?php echo esc_attr( $webhook['url'] ); ?>"
								class="large-text code"
								placeholder="https://example.com/webhook"
								required>
						<p class="description">
							<?php esc_html_e( 'URL повинен приймати POST запити з JSON body', 'medici.agency' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Події', 'medici.agency' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( Webhook_Sender::EVENTS as $event_key => $event_label ) : ?>
								<label style="display: block; margin-bottom: 8px;">
									<input type="checkbox"
											name="webhook_events[]"
											value="<?php echo esc_attr( $event_key ); ?>"
											<?php checked( in_array( $event_key, $webhook['events'] ?? array(), true ) ); ?>>
									<?php echo esc_html( $event_label ); ?>
									<code style="margin-left: 8px;"><?php echo esc_html( $event_key ); ?></code>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="webhook_auth_type"><?php esc_html_e( 'Авторизація', 'medici.agency' ); ?></label>
					</th>
					<td>
						<select id="webhook_auth_type" name="webhook_auth_type" class="regular-text">
							<option value="" <?php selected( $webhook['auth_type'], '' ); ?>>
								<?php esc_html_e( 'Без авторизації', 'medici.agency' ); ?>
							</option>
							<option value="bearer" <?php selected( $webhook['auth_type'], 'bearer' ); ?>>
								<?php esc_html_e( 'Bearer Token', 'medici.agency' ); ?>
							</option>
							<option value="basic" <?php selected( $webhook['auth_type'], 'basic' ); ?>>
								<?php esc_html_e( 'Basic Auth (user:password)', 'medici.agency' ); ?>
							</option>
							<option value="api_key" <?php selected( $webhook['auth_type'], 'api_key' ); ?>>
								<?php esc_html_e( 'API Key (X-API-Key header)', 'medici.agency' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr id="auth_value_row" style="<?php echo empty( $webhook['auth_type'] ) ? 'display:none;' : ''; ?>">
					<th scope="row">
						<label for="webhook_auth_value"><?php esc_html_e( 'Значення авторизації', 'medici.agency' ); ?></label>
					</th>
					<td>
						<input type="text"
								id="webhook_auth_value"
								name="webhook_auth_value"
								value="<?php echo esc_attr( $webhook['auth_value'] ?? '' ); ?>"
								class="large-text code"
								placeholder="<?php esc_attr_e( 'Token або user:password', 'medici.agency' ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
									name="webhook_enabled"
									value="1"
									<?php checked( $webhook['enabled'] ?? true ); ?>>
							<?php esc_html_e( 'Вебхук активний', 'medici.agency' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit"
						class="button button-primary"
						value="<?php echo esc_attr( 'edit' === $action ? __( 'Оновити вебхук', 'medici.agency' ) : __( 'Створити вебхук', 'medici.agency' ) ); ?>">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG ) ); ?>"
					class="button">
					<?php esc_html_e( 'Скасувати', 'medici.agency' ); ?>
				</a>
			</p>
		</form>
		<?php
	}

	/**
	 * Render logs tab
	 *
	 * @since 1.5.0
	 * @return void
	 */
	private function render_logs_tab(): void {
		$logs = Webhook_Sender::get_logs();
		?>
		<div class="tablenav top">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=medici_clear_webhook_logs' ), 'clear_webhook_logs' ) ); ?>"
				class="button"
				onclick="return confirm('<?php esc_attr_e( 'Очистити всі логи?', 'medici.agency' ); ?>');">
				<?php esc_html_e( '🗑️ Очистити логи', 'medici.agency' ); ?>
			</a>
		</div>

		<?php if ( empty( $logs ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Логи відправки вебхуків порожні.', 'medici.agency' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Час', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Подія', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'URL', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Спроба', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
						<th><?php esc_html_e( 'HTTP код', 'medici.agency' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log['timestamp'] ?? '' ); ?></td>
							<td><code><?php echo esc_html( $log['event'] ?? '' ); ?></code></td>
							<td><code><?php echo esc_html( $this->truncate_url( $log['webhook_url'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( $log['attempt'] ?? '' ); ?></td>
							<td>
								<?php if ( ! empty( $log['success'] ) ) : ?>
									<span class="status-badge status-active"><?php esc_html_e( '✅ OK', 'medici.agency' ); ?></span>
								<?php else : ?>
									<span class="status-badge status-error"><?php esc_html_e( '❌ Помилка', 'medici.agency' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( $log['status_code'] ?? '' ); ?>
								<?php if ( ! empty( $log['error'] ) ) : ?>
									<br><small style="color: #d63638;"><?php echo esc_html( $log['error'] ); ?></small>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Handle save webhook form submission
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function handle_save_webhook(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		check_admin_referer( 'save_webhook', 'webhook_nonce' );

		$webhook_id = isset( $_POST['webhook_id'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_id'] ) ) : '';
		$webhook    = array(
			'name'       => isset( $_POST['webhook_name'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_name'] ) ) : '',
			'url'        => isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '',
			'events'     => isset( $_POST['webhook_events'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['webhook_events'] ) ) : array(),
			'enabled'    => ! empty( $_POST['webhook_enabled'] ),
			'auth_type'  => isset( $_POST['webhook_auth_type'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_auth_type'] ) ) : '',
			'auth_value' => isset( $_POST['webhook_auth_value'] ) ? sanitize_text_field( wp_unslash( $_POST['webhook_auth_value'] ) ) : '',
		);

		if ( $webhook_id ) {
			Webhook_Sender::update_webhook( $webhook_id, $webhook );
		} else {
			Webhook_Sender::add_webhook( $webhook );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&updated=1' ) );
		exit;
	}

	/**
	 * Handle delete webhook
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function handle_delete_webhook(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		$webhook_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';

		check_admin_referer( 'delete_webhook_' . $webhook_id );

		if ( $webhook_id ) {
			Webhook_Sender::delete_webhook( $webhook_id );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&deleted=1' ) );
		exit;
	}

	/**
	 * Handle clear logs
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function handle_clear_logs(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		check_admin_referer( 'clear_webhook_logs' );

		Webhook_Sender::clear_logs();

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=' . self::PAGE_SLUG . '&tab=logs&cleared=1' ) );
		exit;
	}

	/**
	 * Truncate URL for display
	 *
	 * @since 1.5.0
	 * @param string $url    URL to truncate.
	 * @param int    $length Maximum length.
	 * @return string Truncated URL.
	 */
	private function truncate_url( string $url, int $length = 50 ): string {
		if ( strlen( $url ) <= $length ) {
			return $url;
		}

		return substr( $url, 0, $length ) . '...';
	}
}

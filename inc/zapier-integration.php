<?php
/**
 * Zapier Integration - Inbound Webhook
 *
 * Provides REST API endpoint for receiving leads from Zapier.
 * Use this to connect external services (Google Forms, Calendly, etc.) to create leads.
 *
 * Endpoint: POST /wp-json/medici/v1/zapier/lead
 * Authentication: Secret key via X-Zapier-Secret header or ?secret= query param
 *
 * @package    Medici_Agency
 * @subpackage Integrations
 * @since      1.7.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Zapier Integration Class
 *
 * Handles inbound webhooks from Zapier to create leads.
 *
 * @since 1.7.0
 */
final class Zapier_Integration {

	/**
	 * API namespace
	 */
	private const NAMESPACE = 'medici/v1';

	/**
	 * Option key for secret
	 */
	private const OPTION_SECRET = 'medici_zapier_secret';

	/**
	 * Option key for enabled status
	 */
	private const OPTION_ENABLED = 'medici_zapier_enabled';

	/**
	 * Option key for log
	 */
	private const OPTION_LOG = 'medici_zapier_log';

	/**
	 * Maximum log entries
	 */
	private const MAX_LOG_ENTRIES = 50;

	/**
	 * Initialize Zapier Integration
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Register REST routes.
		add_action( 'rest_api_init', array( $self, 'register_routes' ) );

		// Add admin settings.
		add_action( 'admin_menu', array( $self, 'add_admin_menu' ) );
		add_action( 'admin_post_medici_save_zapier_settings', array( $self, 'handle_save_settings' ) );
		add_action( 'admin_post_medici_regenerate_zapier_secret', array( $self, 'handle_regenerate_secret' ) );
		add_action( 'admin_post_medici_clear_zapier_log', array( $self, 'handle_clear_log' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function register_routes(): void {
		// POST /zapier/lead - Create lead from Zapier
		register_rest_route(
			self::NAMESPACE,
			'/zapier/lead',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_lead' ),
				'permission_callback' => array( $this, 'verify_secret' ),
				'args'                => array(
					'name'         => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'        => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => fn( $v ) => is_email( $v ),
					),
					'phone'        => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'service'      => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message'      => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'source'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'zapier',
					),
					'utm_source'   => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_medium'   => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'utm_campaign' => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /zapier/status - Check integration status (for Zapier testing)
		register_rest_route(
			self::NAMESPACE,
			'/zapier/status',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'verify_secret' ),
			)
		);
	}

	/**
	 * Verify secret key from request
	 *
	 * Checks X-Zapier-Secret header or ?secret= query parameter.
	 *
	 * @since 1.7.0
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function verify_secret( \WP_REST_Request $request ) {
		// Check if integration is enabled.
		if ( ! self::is_enabled() ) {
			$this->log_request( $request, false, 'Integration disabled' );
			return new \WP_Error(
				'zapier_disabled',
				__( 'Zapier інтеграція вимкнена', 'medici.agency' ),
				array( 'status' => 403 )
			);
		}

		$stored_secret = self::get_secret();

		if ( empty( $stored_secret ) ) {
			$this->log_request( $request, false, 'Secret not configured' );
			return new \WP_Error(
				'zapier_not_configured',
				__( 'Zapier інтеграція не налаштована', 'medici.agency' ),
				array( 'status' => 500 )
			);
		}

		// Get secret from header or query param.
		$provided_secret = $request->get_header( 'X-Zapier-Secret' );

		if ( empty( $provided_secret ) ) {
			$provided_secret = $request->get_param( 'secret' );
		}

		if ( empty( $provided_secret ) ) {
			$this->log_request( $request, false, 'No secret provided' );
			return new \WP_Error(
				'zapier_unauthorized',
				__( 'Secret key не надано. Використовуйте X-Zapier-Secret header або ?secret= параметр.', 'medici.agency' ),
				array( 'status' => 401 )
			);
		}

		// Timing-safe comparison.
		if ( ! hash_equals( $stored_secret, $provided_secret ) ) {
			$this->log_request( $request, false, 'Invalid secret' );
			return new \WP_Error(
				'zapier_forbidden',
				__( 'Невірний secret key', 'medici.agency' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Create lead from Zapier request
	 *
	 * @since 1.7.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response or error.
	 */
	public function create_lead( \WP_REST_Request $request ) {
		// Prepare lead data.
		$data = array(
			'name'         => $request->get_param( 'name' ) ?: '',
			'email'        => $request->get_param( 'email' ),
			'phone'        => $request->get_param( 'phone' ) ?: '',
			'service'      => $request->get_param( 'service' ) ?: '',
			'message'      => $request->get_param( 'message' ) ?: '',
			'page_url'     => '',
			'utm_source'   => $request->get_param( 'utm_source' ) ?: $request->get_param( 'source' ) ?: 'zapier',
			'utm_medium'   => $request->get_param( 'utm_medium' ) ?: 'webhook',
			'utm_campaign' => $request->get_param( 'utm_campaign' ) ?: '',
		);

		// Check if Lead_CPT class exists.
		if ( ! class_exists( '\Medici\Lead_CPT' ) ) {
			$this->log_request( $request, false, 'Lead_CPT class not found' );
			return new \WP_Error(
				'lead_cpt_missing',
				__( 'Lead система не доступна', 'medici.agency' ),
				array( 'status' => 500 )
			);
		}

		// Create lead.
		$lead_id = Lead_CPT::create_lead( $data );

		if ( ! $lead_id ) {
			$this->log_request( $request, false, 'Failed to create lead' );
			return new \WP_Error(
				'lead_creation_failed',
				__( 'Не вдалося створити лід', 'medici.agency' ),
				array( 'status' => 500 )
			);
		}

		// Trigger integrations (Email, Telegram, Google Sheets).
		if ( class_exists( '\Medici\Lead_Integrations' ) ) {
			Lead_Integrations::send_all( $data, $lead_id );
		}

		// Trigger webhook event for outbound webhooks.
		do_action( 'medici_lead_created', $lead_id, $data );

		// Log success.
		$this->log_request( $request, true, 'Lead #' . $lead_id . ' created' );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'lead_id' => $lead_id,
				'message' => sprintf(
					/* translators: %d: lead ID */
					__( 'Лід #%d успішно створено', 'medici.agency' ),
					$lead_id
				),
			),
			201
		);
	}

	/**
	 * Get integration status
	 *
	 * @since 1.7.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response.
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$this->log_request( $request, true, 'Status check' );

		return new \WP_REST_Response(
			array(
				'status'    => 'ok',
				'enabled'   => self::is_enabled(),
				'site_name' => get_bloginfo( 'name' ),
				'site_url'  => home_url(),
				'timestamp' => current_time( 'c' ),
				'version'   => '1.0.0',
			),
			200
		);
	}

	/**
	 * Log request
	 *
	 * @since 1.7.0
	 * @param \WP_REST_Request $request Request object.
	 * @param bool             $success Whether request was successful.
	 * @param string           $message Log message.
	 * @return void
	 */
	private function log_request( \WP_REST_Request $request, bool $success, string $message ): void {
		$logs = get_option( self::OPTION_LOG, array() );

		$log_entry = array(
			'timestamp' => current_time( 'c' ),
			'success'   => $success,
			'message'   => $message,
			'method'    => $request->get_method(),
			'route'     => $request->get_route(),
			'ip'        => $this->get_client_ip(),
			'email'     => $request->get_param( 'email' ) ?: '',
		);

		// Prepend new log.
		array_unshift( $logs, $log_entry );

		// Trim to max entries.
		$logs = array_slice( $logs, 0, self::MAX_LOG_ENTRIES );

		update_option( self::OPTION_LOG, $logs, false );
	}

	/**
	 * Get client IP address
	 *
	 * @since 1.7.0
	 * @return string Client IP.
	 */
	private function get_client_ip(): string {
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// Handle comma-separated IPs (X-Forwarded-For).
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return 'unknown';
	}

	// ========================================================================
	// ADMIN SETTINGS
	// ========================================================================

	/**
	 * Add admin menu page
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'edit.php?post_type=medici_lead',
			__( 'Zapier', 'medici.agency' ),
			__( '⚡ Zapier', 'medici.agency' ),
			'manage_options',
			'medici-zapier',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin settings page
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$secret  = self::get_secret();
		$enabled = self::is_enabled();
		$logs    = self::get_logs();

		// Generate secret if not exists.
		if ( empty( $secret ) ) {
			$secret = self::generate_secret();
			update_option( self::OPTION_SECRET, $secret );
		}

		$endpoint_url = rest_url( self::NAMESPACE . '/zapier/lead' );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( '⚡ Zapier Інтеграція', 'medici.agency' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Налаштування збережено!', 'medici.agency' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['regenerated'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Secret key перегенеровано!', 'medici.agency' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Settings Form -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="medici_save_zapier_settings">
				<?php wp_nonce_field( 'medici_zapier_settings', 'zapier_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
						<td>
							<label>
								<input type="checkbox"
										name="zapier_enabled"
										value="1"
										<?php checked( $enabled ); ?>>
								<?php esc_html_e( 'Zapier інтеграція активна', 'medici.agency' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Webhook URL', 'medici.agency' ); ?></th>
						<td>
							<code style="display: block; padding: 10px; background: #f0f0f0; word-break: break-all;">
								<?php echo esc_html( $endpoint_url ); ?>
							</code>
							<p class="description">
								<?php esc_html_e( 'Використовуйте цей URL в Zapier як Webhook destination', 'medici.agency' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Secret Key', 'medici.agency' ); ?></th>
						<td>
							<input type="text"
									value="<?php echo esc_attr( $secret ); ?>"
									class="large-text code"
									readonly
									onclick="this.select();">
							<p class="description">
								<?php esc_html_e( 'Додайте як X-Zapier-Secret header або ?secret= параметр', 'medici.agency' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<p class="submit">
					<input type="submit"
							class="button button-primary"
							value="<?php esc_attr_e( 'Зберегти налаштування', 'medici.agency' ); ?>">
				</p>
			</form>

			<!-- Regenerate Secret -->
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: -10px;">
				<input type="hidden" name="action" value="medici_regenerate_zapier_secret">
				<?php wp_nonce_field( 'medici_regenerate_zapier_secret', 'regenerate_nonce' ); ?>
				<input type="submit"
						class="button"
						value="<?php esc_attr_e( '🔄 Перегенерувати Secret', 'medici.agency' ); ?>"
						onclick="return confirm('<?php esc_attr_e( 'Це інвалідує поточний secret. Продовжити?', 'medici.agency' ); ?>');">
			</form>

			<!-- Zapier Setup Instructions -->
			<div class="card" style="max-width: 800px; margin-top: 30px;">
				<h2><?php esc_html_e( '📖 Налаштування в Zapier', 'medici.agency' ); ?></h2>

				<h3><?php esc_html_e( '1. Створіть новий Zap', 'medici.agency' ); ?></h3>
				<p><?php esc_html_e( 'В Zapier створіть новий Zap з тригером (наприклад, Google Forms, Calendly, тощо).', 'medici.agency' ); ?></p>

				<h3><?php esc_html_e( '2. Додайте Action: Webhooks by Zapier', 'medici.agency' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Оберіть "Webhooks by Zapier"', 'medici.agency' ); ?></li>
					<li><?php esc_html_e( 'Event: POST', 'medici.agency' ); ?></li>
					<li><strong>URL:</strong> <code><?php echo esc_html( $endpoint_url ); ?></code></li>
					<li><strong>Payload Type:</strong> <code>json</code></li>
				</ol>

				<h3><?php esc_html_e( '3. Налаштуйте Headers', 'medici.agency' ); ?></h3>
				<pre style="background: #1d2327; color: #50fa7b; padding: 15px; border-radius: 4px; overflow-x: auto;">
X-Zapier-Secret: <?php echo esc_html( $secret ); ?>

Content-Type: application/json</pre>

				<h3><?php esc_html_e( '4. Налаштуйте Data', 'medici.agency' ); ?></h3>
				<p><?php esc_html_e( 'Мапінг полів з вашого тригера:', 'medici.agency' ); ?></p>
				<table class="widefat" style="margin-bottom: 20px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Поле', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Обов\'язкове', 'medici.agency' ); ?></th>
							<th><?php esc_html_e( 'Опис', 'medici.agency' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>email</code></td>
							<td>✅ Так</td>
							<td><?php esc_html_e( 'Email адреса ліда', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>name</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'Ім\'я ліда', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>phone</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'Телефон', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>service</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'Послуга (smm, seo, advertising, тощо)', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>message</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'Повідомлення/коментар', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>source</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'Джерело (за замовчуванням: zapier)', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>utm_source</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'UTM source', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>utm_medium</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'UTM medium', 'medici.agency' ); ?></td>
						</tr>
						<tr>
							<td><code>utm_campaign</code></td>
							<td>Ні</td>
							<td><?php esc_html_e( 'UTM campaign', 'medici.agency' ); ?></td>
						</tr>
					</tbody>
				</table>

				<h3><?php esc_html_e( '5. Приклад запиту', 'medici.agency' ); ?></h3>
				<pre style="background: #1d2327; color: #50fa7b; padding: 15px; border-radius: 4px; overflow-x: auto;">
curl -X POST "<?php echo esc_html( $endpoint_url ); ?>" \
	-H "Content-Type: application/json" \
	-H "X-Zapier-Secret: <?php echo esc_html( $secret ); ?>" \
	-d '{
	"email": "client@example.com",
	"name": "Іван Петренко",
	"phone": "+380991234567",
	"service": "smm",
	"message": "Цікавить SMM для клініки",
	"source": "google_forms"
	}'</pre>
			</div>

			<!-- Request Logs -->
			<div class="card" style="max-width: 800px; margin-top: 30px;">
				<h2>
					<?php esc_html_e( '📋 Логи запитів', 'medici.agency' ); ?>
					<form method="post"
							action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
							style="display: inline; float: right;">
						<input type="hidden" name="action" value="medici_clear_zapier_log">
						<?php wp_nonce_field( 'medici_clear_zapier_log', 'clear_log_nonce' ); ?>
						<input type="submit"
								class="button button-small"
								value="<?php esc_attr_e( '🗑️ Очистити', 'medici.agency' ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Очистити всі логи?', 'medici.agency' ); ?>');">
					</form>
				</h2>

				<?php if ( empty( $logs ) ) : ?>
					<p><em><?php esc_html_e( 'Запитів ще не було.', 'medici.agency' ); ?></em></p>
				<?php else : ?>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Час', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'Статус', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'Повідомлення', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'Email', 'medici.agency' ); ?></th>
								<th><?php esc_html_e( 'IP', 'medici.agency' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><small><?php echo esc_html( $log['timestamp'] ?? '' ); ?></small></td>
									<td>
										<?php if ( ! empty( $log['success'] ) ) : ?>
											<span style="color: #00a32a;">✅</span>
										<?php else : ?>
											<span style="color: #d63638;">❌</span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $log['message'] ?? '' ); ?></td>
									<td><small><?php echo esc_html( $log['email'] ?? '—' ); ?></small></td>
									<td><small><?php echo esc_html( $log['ip'] ?? '' ); ?></small></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle save settings form
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		check_admin_referer( 'medici_zapier_settings', 'zapier_nonce' );

		$enabled = ! empty( $_POST['zapier_enabled'] );
		update_option( self::OPTION_ENABLED, $enabled );

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=medici-zapier&updated=1' ) );
		exit;
	}

	/**
	 * Handle regenerate secret
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function handle_regenerate_secret(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		check_admin_referer( 'medici_regenerate_zapier_secret', 'regenerate_nonce' );

		$new_secret = self::generate_secret();
		update_option( self::OPTION_SECRET, $new_secret );

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=medici-zapier&regenerated=1' ) );
		exit;
	}

	/**
	 * Handle clear log
	 *
	 * @since 1.7.0
	 * @return void
	 */
	public function handle_clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостатньо прав', 'medici.agency' ) );
		}

		check_admin_referer( 'medici_clear_zapier_log', 'clear_log_nonce' );

		delete_option( self::OPTION_LOG );

		wp_safe_redirect( admin_url( 'edit.php?post_type=medici_lead&page=medici-zapier&cleared=1' ) );
		exit;
	}

	// ========================================================================
	// STATIC HELPERS
	// ========================================================================

	/**
	 * Get stored secret
	 *
	 * @since 1.7.0
	 * @return string Secret key.
	 */
	public static function get_secret(): string {
		return get_option( self::OPTION_SECRET, '' );
	}

	/**
	 * Check if integration is enabled
	 *
	 * @since 1.7.0
	 * @return bool True if enabled.
	 */
	public static function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}

	/**
	 * Get request logs
	 *
	 * @since 1.7.0
	 * @return array Log entries.
	 */
	public static function get_logs(): array {
		return get_option( self::OPTION_LOG, array() );
	}

	/**
	 * Generate new secret key
	 *
	 * @since 1.7.0
	 * @return string Generated secret (32 characters).
	 */
	public static function generate_secret(): string {
		return wp_generate_password( 32, false, false );
	}
}

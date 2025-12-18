<?php
/**
 * Analytics Integration Module
 *
 * Handles Microsoft Clarity, GA4 Events, and UTM tracking.
 *
 * @package Medici
 * @since 1.6.0
 */

declare(strict_types=1);

namespace Medici;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics Integration Class
 */
final class Analytics {

	/**
	 * Option name for settings.
	 */
	private const OPTION_NAME = 'medici_analytics_settings';

	/**
	 * Default settings.
	 */
	private const DEFAULTS = array(
		'clarity_enabled'    => false,
		'clarity_project_id' => '',
		'ga4_events_enabled' => true,
		'utm_storage'        => true,
	);

	/**
	 * Initialize analytics.
	 */
	public static function init(): void {
		// Frontend scripts.
		add_action( 'wp_head', array( __CLASS__, 'render_clarity_script' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_analytics_scripts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_analytics_config' ), 5 );

		// Admin settings.
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// UTM tracking via AJAX.
		add_action( 'wp_ajax_medici_save_utm', array( __CLASS__, 'ajax_save_utm' ) );
		add_action( 'wp_ajax_nopriv_medici_save_utm', array( __CLASS__, 'ajax_save_utm' ) );
	}

	/**
	 * Get settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge( self::DEFAULTS, $settings );
	}

	/**
	 * Update setting.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public static function update_setting( string $key, $value ): bool {
		$settings         = self::get_settings();
		$settings[ $key ] = $value;

		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Render Microsoft Clarity script.
	 */
	public static function render_clarity_script(): void {
		$settings = self::get_settings();

		if ( ! $settings['clarity_enabled'] || empty( $settings['clarity_project_id'] ) ) {
			return;
		}

		// Don't track admins.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		$project_id = sanitize_text_field( $settings['clarity_project_id'] );
		?>
		<!-- Microsoft Clarity -->
		<script type="text/javascript">
			(function(c,l,a,r,i,t,y){
				c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
				t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
				y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
			})(window, document, "clarity", "script", "<?php echo esc_js( $project_id ); ?>");
		</script>
		<?php
	}

	/**
	 * Enqueue analytics scripts.
	 */
	public static function enqueue_analytics_scripts(): void {
		$settings = self::get_settings();

		if ( ! $settings['ga4_events_enabled'] ) {
			return;
		}

		// Don't track admins.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script(
			'medici-analytics',
			get_stylesheet_directory_uri() . '/js/analytics.js',
			array(),
			MEDICI_VERSION,
			true
		);

		wp_localize_script(
			'medici-analytics',
			'mediciAnalytics',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'medici_analytics' ),
				'utmStorage' => $settings['utm_storage'],
			)
		);
	}

	/**
	 * Render analytics config in footer.
	 */
	public static function render_analytics_config(): void {
		$settings = self::get_settings();

		if ( ! $settings['ga4_events_enabled'] ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<!-- Medici Analytics Config -->
		<script type="text/javascript">
			window.mediciAnalyticsConfig = {
				scrollDepth: [25, 50, 75, 100],
				timeOnPage: [30, 60, 120, 300],
				ctaSelector: '[data-track-cta]',
				formSelector: 'form[data-track-form]'
			};
		</script>
		<?php
	}

	/**
	 * AJAX handler for saving UTM parameters.
	 */
	public static function ajax_save_utm(): void {
		check_ajax_referer( 'medici_analytics', 'nonce' );

		$utm_data = array(
			'utm_source'   => sanitize_text_field( $_POST['utm_source'] ?? '' ),
			'utm_medium'   => sanitize_text_field( $_POST['utm_medium'] ?? '' ),
			'utm_campaign' => sanitize_text_field( $_POST['utm_campaign'] ?? '' ),
			'utm_term'     => sanitize_text_field( $_POST['utm_term'] ?? '' ),
			'utm_content'  => sanitize_text_field( $_POST['utm_content'] ?? '' ),
			'landing_page' => esc_url_raw( $_POST['landing_page'] ?? '' ),
			'referrer'     => esc_url_raw( $_POST['referrer'] ?? '' ),
			'timestamp'    => current_time( 'mysql' ),
		);

		// Store in session/transient for later use when creating lead.
		$session_id = sanitize_text_field( $_POST['session_id'] ?? wp_generate_uuid4() );
		set_transient( 'medici_utm_' . $session_id, $utm_data, HOUR_IN_SECONDS );

		wp_send_json_success( array( 'session_id' => $session_id ) );
	}

	/**
	 * Get UTM data from session.
	 *
	 * @param string $session_id Session ID.
	 * @return array<string, string>
	 */
	public static function get_utm_data( string $session_id ): array {
		$data = get_transient( 'medici_utm_' . $session_id );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Build UTM URL from base URL and parameters.
	 *
	 * @param string $base_url Base URL.
	 * @param string $source   UTM source (instagram, facebook, etc).
	 * @param string $medium   UTM medium (post, story, bio, etc).
	 * @param string $campaign UTM campaign name (optional).
	 * @param string $content  UTM content for A/B tests (optional).
	 * @param string $term     UTM term for paid search (optional).
	 * @return string Full URL with UTM parameters.
	 */
	public static function build_utm_url(
		string $base_url,
		string $source,
		string $medium,
		string $campaign = '',
		string $content = '',
		string $term = ''
	): string {
		$params = array(
			'utm_source' => strtolower( sanitize_text_field( $source ) ),
			'utm_medium' => strtolower( sanitize_text_field( $medium ) ),
		);

		if ( ! empty( $campaign ) ) {
			$params['utm_campaign'] = strtolower( str_replace( ' ', '_', sanitize_text_field( $campaign ) ) );
		}

		if ( ! empty( $content ) ) {
			$params['utm_content'] = strtolower( str_replace( ' ', '_', sanitize_text_field( $content ) ) );
		}

		if ( ! empty( $term ) ) {
			$params['utm_term'] = strtolower( str_replace( ' ', '_', sanitize_text_field( $term ) ) );
		}

		return add_query_arg( $params, $base_url );
	}

	/**
	 * Get preset UTM parameters for common channels.
	 *
	 * @param string $channel Channel name (instagram_bio, facebook_post, etc).
	 * @param string $campaign Campaign name (optional).
	 * @return array<string, string> UTM parameters.
	 */
	public static function get_utm_preset( string $channel, string $campaign = '' ): array {
		$presets = array(
			// Instagram
			'instagram_bio'       => array(
				'source' => 'instagram',
				'medium' => 'bio',
			),
			'instagram_story'     => array(
				'source' => 'instagram',
				'medium' => 'story',
			),
			'instagram_reel'      => array(
				'source' => 'instagram',
				'medium' => 'reel',
			),
			'instagram_dm'        => array(
				'source' => 'instagram',
				'medium' => 'dm',
			),
			'instagram_post'      => array(
				'source' => 'instagram',
				'medium' => 'post',
			),

			// Facebook
			'facebook_post'       => array(
				'source' => 'facebook',
				'medium' => 'post',
			),
			'facebook_ads'        => array(
				'source' => 'facebook',
				'medium' => 'cpc',
			),
			'facebook_messenger'  => array(
				'source' => 'facebook',
				'medium' => 'messenger',
			),
			'facebook_group'      => array(
				'source' => 'facebook',
				'medium' => 'group',
			),
			'facebook_page_bio'   => array(
				'source' => 'facebook',
				'medium' => 'page_bio',
			),

			// LinkedIn
			'linkedin_profile'    => array(
				'source' => 'linkedin',
				'medium' => 'profile',
			),
			'linkedin_post'       => array(
				'source' => 'linkedin',
				'medium' => 'post',
			),
			'linkedin_article'    => array(
				'source' => 'linkedin',
				'medium' => 'article',
			),
			'linkedin_dm'         => array(
				'source' => 'linkedin',
				'medium' => 'dm',
			),
			'linkedin_ads'        => array(
				'source' => 'linkedin',
				'medium' => 'cpc',
			),
			'linkedin_company'    => array(
				'source' => 'linkedin',
				'medium' => 'company',
			),

			// Telegram
			'telegram_channel'    => array(
				'source' => 'telegram',
				'medium' => 'channel',
			),
			'telegram_bot'        => array(
				'source' => 'telegram',
				'medium' => 'bot',
			),
			'telegram_dm'         => array(
				'source' => 'telegram',
				'medium' => 'dm',
			),
			'telegram_group'      => array(
				'source' => 'telegram',
				'medium' => 'group',
			),
			'telegram_bio'        => array(
				'source' => 'telegram',
				'medium' => 'channel_bio',
			),

			// Email
			'email_newsletter'    => array(
				'source' => 'email',
				'medium' => 'newsletter',
			),
			'email_transactional' => array(
				'source' => 'email',
				'medium' => 'transactional',
			),
			'email_promo'         => array(
				'source' => 'email',
				'medium' => 'promo',
			),
			'email_welcome'       => array(
				'source' => 'email',
				'medium' => 'welcome',
			),
			'email_signature'     => array(
				'source' => 'email',
				'medium' => 'signature',
			),
		);

		if ( ! isset( $presets[ $channel ] ) ) {
			return array();
		}

		$preset = $presets[ $channel ];

		if ( ! empty( $campaign ) ) {
			$preset['campaign'] = strtolower( str_replace( ' ', '_', $campaign ) );
		}

		return $preset;
	}

	/**
	 * Generate UTM URL using preset channel.
	 *
	 * Usage:
	 *   Analytics::get_utm_url( 'https://medici.agency/', 'instagram_story', 'winter_promo' );
	 *   Analytics::get_utm_url( home_url( '/blog/' ), 'telegram_channel', 'new_post' );
	 *
	 * @param string $base_url Base URL.
	 * @param string $channel  Channel preset name.
	 * @param string $campaign Campaign name (optional).
	 * @param string $content  UTM content (optional).
	 * @return string Full URL with UTM parameters.
	 */
	public static function get_utm_url( string $base_url, string $channel, string $campaign = '', string $content = '' ): string {
		$preset = self::get_utm_preset( $channel, $campaign );

		if ( empty( $preset ) ) {
			return $base_url;
		}

		return self::build_utm_url(
			$base_url,
			$preset['source'],
			$preset['medium'],
			$preset['campaign'] ?? '',
			$content
		);
	}

	/**
	 * Add settings page.
	 */
	public static function add_settings_page(): void {
		add_submenu_page(
			'options-general.php',
			__( 'Аналітика', 'flavor' ),
			__( 'Аналітика', 'flavor' ),
			'manage_options',
			'medici-analytics',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'medici_analytics',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		// Clarity section.
		add_settings_section(
			'medici_clarity_section',
			__( 'Microsoft Clarity', 'flavor' ),
			function () {
				echo '<p>' . esc_html__( 'Безкоштовні heatmaps та session recordings.', 'flavor' ) . '</p>';
				echo '<p><a href="https://clarity.microsoft.com" target="_blank">Отримати Project ID →</a></p>';
			},
			'medici-analytics'
		);

		add_settings_field(
			'clarity_enabled',
			__( 'Увімкнути Clarity', 'flavor' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'medici-analytics',
			'medici_clarity_section',
			array( 'field' => 'clarity_enabled' )
		);

		add_settings_field(
			'clarity_project_id',
			__( 'Project ID', 'flavor' ),
			array( __CLASS__, 'render_text_field' ),
			'medici-analytics',
			'medici_clarity_section',
			array(
				'field'       => 'clarity_project_id',
				'placeholder' => 'abc123xyz',
			)
		);

		// GA4 Events section.
		add_settings_section(
			'medici_ga4_section',
			__( 'GA4 Events', 'flavor' ),
			function () {
				echo '<p>' . esc_html__( 'Автоматичне відстеження scroll depth, time on page, CTA clicks.', 'flavor' ) . '</p>';
			},
			'medici-analytics'
		);

		add_settings_field(
			'ga4_events_enabled',
			__( 'Увімкнути GA4 Events', 'flavor' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'medici-analytics',
			'medici_ga4_section',
			array( 'field' => 'ga4_events_enabled' )
		);

		// UTM section.
		add_settings_section(
			'medici_utm_section',
			__( 'UTM Tracking', 'flavor' ),
			function () {
				echo '<p>' . esc_html__( 'Автоматичне збереження UTM параметрів для атрибуції лідів.', 'flavor' ) . '</p>';
			},
			'medici-analytics'
		);

		add_settings_field(
			'utm_storage',
			__( 'Зберігати UTM', 'flavor' ),
			array( __CLASS__, 'render_checkbox_field' ),
			'medici-analytics',
			'medici_utm_section',
			array( 'field' => 'utm_storage' )
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Input data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return self::DEFAULTS;
		}

		return array(
			'clarity_enabled'    => ! empty( $input['clarity_enabled'] ),
			'clarity_project_id' => sanitize_text_field( $input['clarity_project_id'] ?? '' ),
			'ga4_events_enabled' => ! empty( $input['ga4_events_enabled'] ),
			'utm_storage'        => ! empty( $input['utm_storage'] ),
		);
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array<string, string> $args Field arguments.
	 */
	public static function render_checkbox_field( array $args ): void {
		$settings = self::get_settings();
		$field    = $args['field'];
		$checked  = ! empty( $settings[ $field ] );
		?>
		<input type="checkbox"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>"
				id="<?php echo esc_attr( $field ); ?>"
				value="1"
				<?php checked( $checked ); ?>>
		<?php
	}

	/**
	 * Render text field.
	 *
	 * @param array<string, string> $args Field arguments.
	 */
	public static function render_text_field( array $args ): void {
		$settings    = self::get_settings();
		$field       = $args['field'];
		$value       = $settings[ $field ] ?? '';
		$placeholder = $args['placeholder'] ?? '';
		?>
		<input type="text"
				name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>"
				id="<?php echo esc_attr( $field ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="regular-text">
		<?php
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'medici_analytics' );
				do_settings_sections( 'medici-analytics' );
				submit_button( __( 'Зберегти', 'flavor' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'UTM Шаблони', 'flavor' ); ?></h2>
			<p><?php esc_html_e( 'Копіюйте та використовуйте для посилань. Замініть НАЗВА на назву кампанії (латиницею, без пробілів).', 'flavor' ); ?></p>

			<!-- Instagram -->
			<h3 style="margin-top: 2rem;">📸 Instagram</h3>
			<table class="widefat" style="max-width: 900px; margin-bottom: 1.5rem;">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'Канал', 'flavor' ); ?></th>
						<th><?php esc_html_e( 'UTM параметри', 'flavor' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Опис', 'flavor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Bio Link</strong></td>
						<td><code>?utm_source=instagram&amp;utm_medium=bio</code></td>
						<td>Посилання в профілі</td>
					</tr>
					<tr>
						<td><strong>Stories</strong></td>
						<td><code>?utm_source=instagram&amp;utm_medium=story&amp;utm_campaign=НАЗВА</code></td>
						<td>Stories зі свайпом</td>
					</tr>
					<tr>
						<td><strong>Reels</strong></td>
						<td><code>?utm_source=instagram&amp;utm_medium=reel&amp;utm_campaign=НАЗВА</code></td>
						<td>Посилання в описі Reels</td>
					</tr>
					<tr>
						<td><strong>Direct Message</strong></td>
						<td><code>?utm_source=instagram&amp;utm_medium=dm&amp;utm_campaign=НАЗВА</code></td>
						<td>Особисті повідомлення</td>
					</tr>
					<tr>
						<td><strong>Post Caption</strong></td>
						<td><code>?utm_source=instagram&amp;utm_medium=post&amp;utm_campaign=НАЗВА</code></td>
						<td>Посилання в описі поста</td>
					</tr>
				</tbody>
			</table>

			<!-- Facebook -->
			<h3>📘 Facebook</h3>
			<table class="widefat" style="max-width: 900px; margin-bottom: 1.5rem;">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'Канал', 'flavor' ); ?></th>
						<th><?php esc_html_e( 'UTM параметри', 'flavor' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Опис', 'flavor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Post (Organic)</strong></td>
						<td><code>?utm_source=facebook&amp;utm_medium=post&amp;utm_campaign=НАЗВА</code></td>
						<td>Пост на сторінці</td>
					</tr>
					<tr>
						<td><strong>Ads (Paid)</strong></td>
						<td><code>?utm_source=facebook&amp;utm_medium=cpc&amp;utm_campaign=НАЗВА&amp;utm_content=AD_NAME</code></td>
						<td>Платна реклама</td>
					</tr>
					<tr>
						<td><strong>Messenger</strong></td>
						<td><code>?utm_source=facebook&amp;utm_medium=messenger&amp;utm_campaign=НАЗВА</code></td>
						<td>Посилання в Messenger</td>
					</tr>
					<tr>
						<td><strong>Group Post</strong></td>
						<td><code>?utm_source=facebook&amp;utm_medium=group&amp;utm_campaign=НАЗВА</code></td>
						<td>Пост у групі</td>
					</tr>
					<tr>
						<td><strong>Page Bio</strong></td>
						<td><code>?utm_source=facebook&amp;utm_medium=page_bio</code></td>
						<td>Посилання в профілі сторінки</td>
					</tr>
				</tbody>
			</table>

			<!-- LinkedIn -->
			<h3>💼 LinkedIn</h3>
			<table class="widefat" style="max-width: 900px; margin-bottom: 1.5rem;">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'Канал', 'flavor' ); ?></th>
						<th><?php esc_html_e( 'UTM параметри', 'flavor' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Опис', 'flavor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Profile Link</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=profile</code></td>
						<td>Посилання в профілі</td>
					</tr>
					<tr>
						<td><strong>Post (Organic)</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=post&amp;utm_campaign=НАЗВА</code></td>
						<td>Публікація в стрічці</td>
					</tr>
					<tr>
						<td><strong>Article</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=article&amp;utm_campaign=НАЗВА</code></td>
						<td>LinkedIn стаття</td>
					</tr>
					<tr>
						<td><strong>Direct Message</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=dm&amp;utm_campaign=НАЗВА</code></td>
						<td>Особисті повідомлення</td>
					</tr>
					<tr>
						<td><strong>Ads (Paid)</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=cpc&amp;utm_campaign=НАЗВА&amp;utm_content=AD_NAME</code></td>
						<td>LinkedIn Ads</td>
					</tr>
					<tr>
						<td><strong>Company Page</strong></td>
						<td><code>?utm_source=linkedin&amp;utm_medium=company&amp;utm_campaign=НАЗВА</code></td>
						<td>Пост компанії</td>
					</tr>
				</tbody>
			</table>

			<!-- Telegram -->
			<h3>📱 Telegram</h3>
			<table class="widefat" style="max-width: 900px; margin-bottom: 1.5rem;">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'Канал', 'flavor' ); ?></th>
						<th><?php esc_html_e( 'UTM параметри', 'flavor' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Опис', 'flavor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Channel Post</strong></td>
						<td><code>?utm_source=telegram&amp;utm_medium=channel&amp;utm_campaign=НАЗВА</code></td>
						<td>Пост у каналі</td>
					</tr>
					<tr>
						<td><strong>Bot Message</strong></td>
						<td><code>?utm_source=telegram&amp;utm_medium=bot&amp;utm_campaign=НАЗВА</code></td>
						<td>Повідомлення від бота</td>
					</tr>
					<tr>
						<td><strong>Direct Message</strong></td>
						<td><code>?utm_source=telegram&amp;utm_medium=dm&amp;utm_campaign=НАЗВА</code></td>
						<td>Особисті повідомлення</td>
					</tr>
					<tr>
						<td><strong>Group Post</strong></td>
						<td><code>?utm_source=telegram&amp;utm_medium=group&amp;utm_campaign=НАЗВА</code></td>
						<td>Пост у групі</td>
					</tr>
					<tr>
						<td><strong>Channel Bio</strong></td>
						<td><code>?utm_source=telegram&amp;utm_medium=channel_bio</code></td>
						<td>Посилання в описі каналу</td>
					</tr>
				</tbody>
			</table>

			<!-- Email -->
			<h3>📧 Email</h3>
			<table class="widefat" style="max-width: 900px; margin-bottom: 1.5rem;">
				<thead>
					<tr>
						<th style="width: 180px;"><?php esc_html_e( 'Канал', 'flavor' ); ?></th>
						<th><?php esc_html_e( 'UTM параметри', 'flavor' ); ?></th>
						<th style="width: 200px;"><?php esc_html_e( 'Опис', 'flavor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>Newsletter</strong></td>
						<td><code>?utm_source=email&amp;utm_medium=newsletter&amp;utm_campaign=НАЗВА</code></td>
						<td>Регулярна розсилка</td>
					</tr>
					<tr>
						<td><strong>Transactional</strong></td>
						<td><code>?utm_source=email&amp;utm_medium=transactional&amp;utm_campaign=НАЗВА</code></td>
						<td>Сервісні листи</td>
					</tr>
					<tr>
						<td><strong>Promo Campaign</strong></td>
						<td><code>?utm_source=email&amp;utm_medium=promo&amp;utm_campaign=НАЗВА</code></td>
						<td>Промо розсилка</td>
					</tr>
					<tr>
						<td><strong>Welcome Email</strong></td>
						<td><code>?utm_source=email&amp;utm_medium=welcome&amp;utm_campaign=onboarding</code></td>
						<td>Welcome серія</td>
					</tr>
					<tr>
						<td><strong>Email Signature</strong></td>
						<td><code>?utm_source=email&amp;utm_medium=signature</code></td>
						<td>Підпис у листах</td>
					</tr>
				</tbody>
			</table>

			<!-- UTM Builder -->
			<hr style="margin: 2rem 0;">
			<h2><?php esc_html_e( 'UTM Builder', 'flavor' ); ?></h2>
			<p><?php esc_html_e( 'Генеруйте UTM посилання автоматично:', 'flavor' ); ?></p>

			<div style="max-width: 600px; background: #f9f9f9; padding: 1.5rem; border-radius: 8px;">
				<div style="margin-bottom: 1rem;">
					<label for="utm-base-url"><strong>URL сторінки:</strong></label><br>
					<input type="url" id="utm-base-url" value="https://www.medici.agency/" style="width: 100%; padding: 8px; margin-top: 4px;">
				</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
					<div>
						<label for="utm-source"><strong>utm_source:</strong></label><br>
						<select id="utm-source" style="width: 100%; padding: 8px; margin-top: 4px;">
							<option value="">Оберіть...</option>
							<option value="instagram">instagram</option>
							<option value="facebook">facebook</option>
							<option value="linkedin">linkedin</option>
							<option value="telegram">telegram</option>
							<option value="email">email</option>
							<option value="google">google</option>
						</select>
					</div>
					<div>
						<label for="utm-medium"><strong>utm_medium:</strong></label><br>
						<select id="utm-medium" style="width: 100%; padding: 8px; margin-top: 4px;">
							<option value="">Оберіть...</option>
							<option value="bio">bio</option>
							<option value="post">post</option>
							<option value="story">story</option>
							<option value="reel">reel</option>
							<option value="dm">dm</option>
							<option value="channel">channel</option>
							<option value="bot">bot</option>
							<option value="newsletter">newsletter</option>
							<option value="cpc">cpc (paid)</option>
							<option value="profile">profile</option>
						</select>
					</div>
				</div>
				<div style="margin-bottom: 1rem;">
					<label for="utm-campaign"><strong>utm_campaign:</strong> (опціонально)</label><br>
					<input type="text" id="utm-campaign" placeholder="winter_sale_2024" style="width: 100%; padding: 8px; margin-top: 4px;">
				</div>
				<div style="margin-bottom: 1rem;">
					<label for="utm-content"><strong>utm_content:</strong> (опціонально, для A/B тестів)</label><br>
					<input type="text" id="utm-content" placeholder="banner_blue" style="width: 100%; padding: 8px; margin-top: 4px;">
				</div>
				<button type="button" id="utm-generate" class="button button-primary" style="margin-right: 10px;">Генерувати</button>
				<button type="button" id="utm-copy" class="button" style="display: none;">📋 Копіювати</button>
				<div id="utm-result" style="margin-top: 1rem; padding: 12px; background: #fff; border: 1px solid #ddd; border-radius: 4px; word-break: break-all; display: none;"></div>
			</div>

			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const baseUrl = document.getElementById('utm-base-url');
				const source = document.getElementById('utm-source');
				const medium = document.getElementById('utm-medium');
				const campaign = document.getElementById('utm-campaign');
				const content = document.getElementById('utm-content');
				const generateBtn = document.getElementById('utm-generate');
				const copyBtn = document.getElementById('utm-copy');
				const result = document.getElementById('utm-result');

				generateBtn.addEventListener('click', function() {
					if (!baseUrl.value || !source.value || !medium.value) {
						alert('Заповніть URL, utm_source та utm_medium');
						return;
					}

					let url = baseUrl.value.trim();
					if (!url.includes('?')) {
						url += '?';
					} else {
						url += '&';
					}

					url += 'utm_source=' + encodeURIComponent(source.value);
					url += '&utm_medium=' + encodeURIComponent(medium.value);

					if (campaign.value.trim()) {
						url += '&utm_campaign=' + encodeURIComponent(campaign.value.trim().toLowerCase().replace(/\s+/g, '_'));
					}

					if (content.value.trim()) {
						url += '&utm_content=' + encodeURIComponent(content.value.trim().toLowerCase().replace(/\s+/g, '_'));
					}

					result.textContent = url;
					result.style.display = 'block';
					copyBtn.style.display = 'inline-block';
				});

				copyBtn.addEventListener('click', function() {
					navigator.clipboard.writeText(result.textContent).then(function() {
						copyBtn.textContent = '✅ Скопійовано!';
						setTimeout(function() {
							copyBtn.textContent = '📋 Копіювати';
						}, 2000);
					});
				});
			});
			</script>

			<!-- Best Practices -->
			<hr style="margin: 2rem 0;">
			<h2><?php esc_html_e( 'Best Practices', 'flavor' ); ?></h2>
			<ul style="max-width: 800px;">
				<li>✅ <strong>utm_source</strong> — завжди вказуйте платформу (instagram, facebook, email)</li>
				<li>✅ <strong>utm_medium</strong> — тип контенту (post, story, newsletter, cpc)</li>
				<li>✅ <strong>utm_campaign</strong> — назва кампанії латиницею без пробілів</li>
				<li>✅ <strong>utm_content</strong> — для A/B тестування різних варіантів</li>
				<li>✅ <strong>utm_term</strong> — для платної реклами (ключові слова)</li>
				<li>⚠️ Використовуйте <strong>тільки lowercase</strong> для консистентності даних</li>
				<li>⚠️ Замінюйте пробіли на <strong>underscore (_)</strong></li>
				<li>⚠️ Не використовуйте UTM для внутрішніх посилань на сайті</li>
			</ul>
		</div>
		<?php
	}
}

// Initialize.
Analytics::init();

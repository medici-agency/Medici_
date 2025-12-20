<?php
/**
 * Frontend Class
 *
 * Handles all public-facing functionality.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Frontend Class
 *
 * Manages popup rendering, form handling, and AJAX endpoints.
 */
final class Frontend {

	/**
	 * Register shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'jexi_popup', array( $this, 'render_shortcode' ) );
		add_shortcode( 'jexi_trigger', array( $this, 'render_trigger_shortcode' ) );
	}

	/**
	 * Render popup shortcode.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( array $atts = array() ): string {
		$options = jexi()->get_options();

		$atts = shortcode_atts(
			array(
				'title'       => $options->get( 'content', 'title' ),
				'subtitle'    => $options->get( 'content', 'subtitle' ),
				'button_text' => $options->get( 'content', 'button_text' ),
				'template'    => $options->get( 'design', 'template' ),
			),
			$atts,
			'jexi_popup'
		);

		return $this->render_popup_html( $atts );
	}

	/**
	 * Render trigger button shortcode.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_trigger_shortcode( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'text'  => __( 'Show Offer', 'medici-exit-intent' ),
				'class' => '',
			),
			$atts,
			'jexi_trigger'
		);

		return sprintf(
			'<button type="button" class="jexi-trigger %s" data-jexi-trigger>%s</button>',
			esc_attr( $atts['class'] ),
			esc_html( $atts['text'] )
		);
	}

	/**
	 * Render popup in footer.
	 *
	 * @since 1.0.0
	 */
	public function render_popup(): void {
		$options = jexi()->get_options();

		if ( ! $options->get( 'general', 'enabled' ) ) {
			return;
		}

		/**
		 * Fires before popup is rendered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'jexi_before_popup_render' );

		echo $this->render_popup_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Fires after popup is rendered.
		 *
		 * @since 1.0.0
		 */
		do_action( 'jexi_after_popup_render' );
	}

	/**
	 * Render popup HTML.
	 *
	 * @since 1.0.0
	 * @param array<string, string> $overrides Content overrides.
	 * @return string
	 */
	private function render_popup_html( array $overrides = array() ): string {
		$options = jexi()->get_options();
		$design  = $options->get( 'design' );
		$content = $options->get( 'content' );
		$form    = $options->get( 'form' );
		$twemoji = $options->get( 'twemoji' );

		// Merge overrides.
		$content = array_merge( $content, $overrides );

		// Build classes.
		$classes = array(
			'jexi-popup',
			'jexi-popup--' . ( $design['template'] ?? 'modern' ),
			'jexi-popup--' . ( $design['position'] ?? 'center' ),
			'jexi-popup--' . ( $design['animation'] ?? 'scale' ),
		);

		if ( $design['backdrop_blur'] ?? true ) {
			$classes[] = 'jexi-popup--blur';
		}

		if ( $design['dark_mode'] ?? true ) {
			$classes[] = 'jexi-popup--dark-support';
		}

		/**
		 * Filter popup classes.
		 *
		 * @since 1.0.0
		 * @param array<string> $classes CSS classes.
		 */
		$classes = apply_filters( 'jexi_popup_classes', $classes );

		ob_start();
		?>
		<div id="jexi-popup" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="dialog" aria-modal="true" aria-labelledby="jexi-title" hidden>
			<div class="jexi-popup__backdrop" data-jexi-close></div>
			<div class="jexi-popup__container">
				<button type="button" class="jexi-popup__close" data-jexi-close aria-label="<?php esc_attr_e( 'Close', 'medici-exit-intent' ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M18 6L6 18M6 6l12 12"/>
					</svg>
				</button>

				<?php if ( ( $design['icon_type'] ?? 'twemoji' ) !== 'none' ) : ?>
					<div class="jexi-popup__icon">
						<?php if ( ( $design['icon_type'] ?? 'twemoji' ) === 'custom' && ! empty( $design['custom_icon_url'] ) ) : ?>
							<img src="<?php echo esc_url( $design['custom_icon_url'] ); ?>" alt="" class="jexi-popup__icon-img">
						<?php else : ?>
							<span class="jexi-popup__emoji"><?php echo wp_kses_post( $design['icon'] ?? '👋' ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<h2 id="jexi-title" class="jexi-popup__title">
					<?php echo esc_html( $content['title'] ?? '' ); ?>
				</h2>

				<?php if ( ! empty( $content['subtitle'] ) ) : ?>
					<p class="jexi-popup__subtitle">
						<?php echo wp_kses_post( $content['subtitle'] ); ?>
					</p>
				<?php endif; ?>

				<form class="jexi-popup__form" id="jexi-form" method="post">
					<?php wp_nonce_field( 'jexi_submit', 'jexi_nonce' ); ?>

					<?php if ( $form['show_name'] ?? true ) : ?>
						<div class="jexi-popup__field">
							<label for="jexi-name" class="screen-reader-text"><?php esc_html_e( 'Name', 'medici-exit-intent' ); ?></label>
							<input
								type="text"
								id="jexi-name"
								name="name"
								placeholder="<?php esc_attr_e( 'Your name', 'medici-exit-intent' ); ?>"
								autocomplete="name"
								<?php echo ( $form['require_name'] ?? false ) ? 'required' : ''; ?>
							>
						</div>
					<?php endif; ?>

					<?php if ( $form['show_email'] ?? true ) : ?>
						<div class="jexi-popup__field">
							<label for="jexi-email" class="screen-reader-text"><?php esc_html_e( 'Email', 'medici-exit-intent' ); ?></label>
							<input
								type="email"
								id="jexi-email"
								name="email"
								placeholder="<?php esc_attr_e( 'your@email.com', 'medici-exit-intent' ); ?>"
								autocomplete="email"
								<?php echo ( $form['require_email'] ?? true ) ? 'required' : ''; ?>
							>
						</div>
					<?php endif; ?>

					<?php if ( $form['show_phone'] ?? true ) : ?>
						<div class="jexi-popup__field">
							<label for="jexi-phone" class="screen-reader-text"><?php esc_html_e( 'Phone', 'medici-exit-intent' ); ?></label>
							<input
								type="tel"
								id="jexi-phone"
								name="phone"
								placeholder="<?php esc_attr_e( '+380 XX XXX XX XX', 'medici-exit-intent' ); ?>"
								autocomplete="tel"
								<?php echo ( $form['require_phone'] ?? false ) ? 'required' : ''; ?>
							>
						</div>
					<?php endif; ?>

					<?php if ( $form['honeypot'] ?? true ) : ?>
						<div class="jexi-popup__field jexi-popup__field--hp" aria-hidden="true">
							<input type="text" name="website" tabindex="-1" autocomplete="off">
						</div>
					<?php endif; ?>

					<?php if ( $form['require_consent'] ?? true ) : ?>
						<div class="jexi-popup__consent">
							<label>
								<input type="checkbox" name="consent" value="1" required>
								<span><?php echo esc_html( $form['consent_text'] ?? __( 'I agree to the processing of personal data', 'medici-exit-intent' ) ); ?></span>
							</label>
						</div>
					<?php endif; ?>

					<button type="submit" class="jexi-popup__submit">
						<?php echo esc_html( $content['button_text'] ?? __( 'Submit', 'medici-exit-intent' ) ); ?>
					</button>

					<div class="jexi-popup__message" role="status" aria-live="polite"></div>
				</form>

				<?php if ( ! empty( $content['decline_text'] ) ) : ?>
					<button type="button" class="jexi-popup__decline" data-jexi-close>
						<?php echo esc_html( $content['decline_text'] ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php

		$html = ob_get_clean();

		/**
		 * Filter the popup HTML.
		 *
		 * @since 1.0.0
		 * @param string $html The popup HTML.
		 */
		return apply_filters( 'jexi_popup_html', (string) $html );
	}

	/**
	 * Handle AJAX form submission.
	 *
	 * @since 1.0.0
	 */
	public function handle_submission(): void {
		// Verify nonce.
		if ( ! isset( $_POST['jexi_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['jexi_nonce'] ), 'jexi_submit' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'medici-exit-intent' ) ), 403 );
		}

		$options = jexi()->get_options();
		$form    = $options->get( 'form' );

		// Honeypot check.
		if ( ( $form['honeypot'] ?? true ) && ! empty( $_POST['website'] ) ) {
			jexi()->get_debug()->warning( 'Honeypot triggered', array( 'ip' => $this->get_client_ip() ) );
			wp_send_json_error( array( 'message' => __( 'Spam detected.', 'medici-exit-intent' ) ), 400 );
		}

		// Sanitize input.
		$data = array(
			'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'email'   => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'   => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'consent' => isset( $_POST['consent'] ) && '1' === $_POST['consent'],
			'page_url' => isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '',
			'referrer' => isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( $_POST['referrer'] ) ) : '',
		);

		// Validate required fields.
		if ( ( $form['require_email'] ?? true ) && ! is_email( $data['email'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'medici-exit-intent' ) ), 400 );
		}

		if ( ( $form['require_consent'] ?? true ) && ! $data['consent'] ) {
			wp_send_json_error( array( 'message' => __( 'Please accept the consent checkbox.', 'medici-exit-intent' ) ), 400 );
		}

		/**
		 * Fires before form data is processed.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $data Form data.
		 */
		do_action( 'jexi_before_form_process', $data );

		// Process integrations.
		$integrations = $options->get( 'integrations' );

		// Save to Lead CPT.
		if ( $integrations['lead_cpt'] ?? true ) {
			$this->create_lead( $data );
		}

		// Send to email provider.
		if ( ( $integrations['email_provider'] ?? 'none' ) !== 'none' ) {
			jexi()->get_providers()->send( $data );
		}

		// Send to webhook.
		if ( ! empty( $integrations['webhook_url'] ) ) {
			$this->send_webhook( $integrations['webhook_url'], $data );
		}

		// Track analytics.
		if ( $options->get( 'analytics', 'enabled' ) && $options->get( 'analytics', 'track_submits' ) ) {
			jexi()->get_analytics()->track( 'submit', $data );
		}

		/**
		 * Fires after form data is processed.
		 *
		 * @since 1.0.0
		 * @param array<string, mixed> $data Form data.
		 */
		do_action( 'jexi_after_form_process', $data );

		jexi()->get_debug()->info( 'Form submitted successfully', array( 'email' => $data['email'] ) );

		wp_send_json_success(
			array(
				'message' => $options->get( 'content', 'success_message' ),
			)
		);
	}

	/**
	 * Handle REST API submission.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_submit( \WP_REST_Request $request ): \WP_REST_Response {
		$data = array(
			'name'    => $request->get_param( 'name' ) ?? '',
			'email'   => $request->get_param( 'email' ) ?? '',
			'phone'   => $request->get_param( 'phone' ) ?? '',
			'consent' => (bool) $request->get_param( 'consent' ),
		);

		// Same processing as AJAX.
		// ...

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Thank you!', 'medici-exit-intent' ),
			),
			200
		);
	}

	/**
	 * Create Lead custom post.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $data Form data.
	 * @return int|false Post ID or false on failure.
	 */
	private function create_lead( array $data ): int|false {
		// Check if Lead CPT exists.
		if ( ! post_type_exists( 'lead' ) ) {
			$this->register_lead_cpt();
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'lead',
				'post_status' => 'publish',
				'post_title'  => $data['email'] ?: $data['name'] ?: __( 'Exit-Intent Lead', 'medici-exit-intent' ),
				'meta_input'  => array(
					'_jexi_name'     => $data['name'],
					'_jexi_email'    => $data['email'],
					'_jexi_phone'    => $data['phone'],
					'_jexi_consent'  => $data['consent'],
					'_jexi_page_url' => $data['page_url'] ?? '',
					'_jexi_referrer' => $data['referrer'] ?? '',
					'_jexi_source'   => 'exit-intent',
					'_jexi_ip'       => $this->get_client_ip(),
					'_jexi_ua'       => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
				),
			)
		);

		if ( is_wp_error( $post_id ) ) {
			jexi()->get_debug()->error( 'Failed to create lead', array( 'error' => $post_id->get_error_message() ) );
			return false;
		}

		/**
		 * Fires after a lead is created.
		 *
		 * @since 1.0.0
		 * @param int                  $post_id Lead post ID.
		 * @param array<string, mixed> $data    Form data.
		 */
		do_action( 'jexi_lead_created', $post_id, $data );

		return $post_id;
	}

	/**
	 * Register Lead custom post type.
	 *
	 * @since 1.0.0
	 */
	private function register_lead_cpt(): void {
		register_post_type(
			'lead',
			array(
				'labels'       => array(
					'name'          => __( 'Leads', 'medici-exit-intent' ),
					'singular_name' => __( 'Lead', 'medici-exit-intent' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'jexi-settings',
				'supports'     => array( 'title', 'custom-fields' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Send webhook request.
	 *
	 * @since 1.0.0
	 * @param string               $url  Webhook URL.
	 * @param array<string, mixed> $data Form data.
	 */
	private function send_webhook( string $url, array $data ): void {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'event'     => 'exit_intent_submit',
						'timestamp' => current_time( 'c' ),
						'data'      => $data,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			jexi()->get_debug()->error( 'Webhook failed', array( 'url' => $url, 'error' => $response->get_error_message() ) );
		}
	}

	/**
	 * Get client IP address.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs.
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}

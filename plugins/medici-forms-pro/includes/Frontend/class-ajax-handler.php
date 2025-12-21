<?php
/**
 * AJAX Handler Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Frontend;

use MediciForms\Processor\Form_Processor;

/**
 * AJAX Handler Class.
 *
 * @since 1.0.0
 */
class Ajax_Handler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_medici_form_submit', array( $this, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_medici_form_submit', array( $this, 'handle_submit' ) );
		add_action( 'admin_post_medici_form_submit', array( $this, 'handle_post_submit' ) );
		add_action( 'admin_post_nopriv_medici_form_submit', array( $this, 'handle_post_submit' ) );
	}

	/**
	 * Handle AJAX form submission.
	 *
	 * @since 1.0.0
	 */
	public function handle_submit(): void {
		$processor = new Form_Processor();
		$result    = $processor->process();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Handle POST form submission (non-AJAX).
	 *
	 * @since 1.0.0
	 */
	public function handle_post_submit(): void {
		$processor = new Form_Processor();
		$result    = $processor->process();

		// Get redirect URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_id  = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$settings = \MediciForms\Helpers::get_form_settings( $form_id );

		if ( $result['success'] && ! empty( $settings['enable_redirect'] ) && ! empty( $settings['redirect_url'] ) ) {
			wp_safe_redirect( esc_url_raw( $settings['redirect_url'] ) );
			exit;
		}

		// Get page URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : home_url();

		// Add result to URL.
		$redirect_url = add_query_arg(
			array(
				'mf_result' => $result['success'] ? 'success' : 'error',
				'mf_form'   => $form_id,
			),
			$page_url
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

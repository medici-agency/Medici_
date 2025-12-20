<?php
/**
 * Debug Class
 *
 * Handles debug logging and error tracking.
 * Based on WP Mail SMTP Debug.php architecture.
 *
 * @package Jexi
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Jexi;

/**
 * Debug Class
 */
final class Debug {

	/**
	 * Option key for debug logs.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'jexi_debug_log';

	/**
	 * Maximum log entries.
	 *
	 * @var int
	 */
	private const MAX_ENTRIES = 100;

	/**
	 * Log a debug message.
	 *
	 * @since 1.0.0
	 * @param string               $message Log message.
	 * @param string               $level   Log level (info, warning, error).
	 * @param array<string, mixed> $context Additional context.
	 */
	public function log( string $message, string $level = 'info', array $context = array() ): void {
		if ( ! jexi()->is_debug() ) {
			return;
		}

		$logs   = $this->get_logs();
		$logs[] = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		);

		// Keep only last N entries.
		$logs = array_slice( $logs, -self::MAX_ENTRIES );

		update_option( self::OPTION_KEY, $logs );

		/**
		 * Fires after a debug log entry is added.
		 *
		 * @since 1.0.0
		 * @param string               $message Log message.
		 * @param string               $level   Log level.
		 * @param array<string, mixed> $context Context data.
		 */
		do_action( 'jexi_debug_logged', $message, $level, $context );
	}

	/**
	 * Log info message.
	 *
	 * @since 1.0.0
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public function info( string $message, array $context = array() ): void {
		$this->log( $message, 'info', $context );
	}

	/**
	 * Log warning message.
	 *
	 * @since 1.0.0
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public function warning( string $message, array $context = array() ): void {
		$this->log( $message, 'warning', $context );
	}

	/**
	 * Log error message.
	 *
	 * @since 1.0.0
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public function error( string $message, array $context = array() ): void {
		$this->log( $message, 'error', $context );
	}

	/**
	 * Get all debug logs.
	 *
	 * @since 1.0.0
	 * @return array<int, array{timestamp: string, level: string, message: string, context: array<string, mixed>}>
	 */
	public function get_logs(): array {
		$logs = get_option( self::OPTION_KEY, array() );
		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * Get logs filtered by level.
	 *
	 * @since 1.0.0
	 * @param string $level Log level.
	 * @return array<int, array{timestamp: string, level: string, message: string, context: array<string, mixed>}>
	 */
	public function get_logs_by_level( string $level ): array {
		return array_filter(
			$this->get_logs(),
			fn( array $log ): bool => $log['level'] === $level
		);
	}

	/**
	 * Clear all debug logs.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function clear(): bool {
		return delete_option( self::OPTION_KEY );
	}

	/**
	 * Export logs as text.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function export(): string {
		$logs   = $this->get_logs();
		$output = "Jexi Debug Log\n";
		$output .= "Generated: " . current_time( 'mysql' ) . "\n";
		$output .= str_repeat( '=', 50 ) . "\n\n";

		foreach ( $logs as $log ) {
			$output .= sprintf(
				"[%s] [%s] %s\n",
				$log['timestamp'],
				strtoupper( $log['level'] ),
				$log['message']
			);

			if ( ! empty( $log['context'] ) ) {
				$output .= "Context: " . wp_json_encode( $log['context'] ) . "\n";
			}

			$output .= "\n";
		}

		return $output;
	}
}

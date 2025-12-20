<?php
/**
 * Conditional Rules Engine
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

use Medici\CookieNotice\Rules\Rule;
use Medici\CookieNotice\Rules\Rule_Group;
use Medici\CookieNotice\Rules\Rule_Evaluator_Interface;
use Medici\CookieNotice\Rules\Evaluators\Page_Evaluator;
use Medici\CookieNotice\Rules\Evaluators\User_Evaluator;
use Medici\CookieNotice\Rules\Evaluators\User_Role_Evaluator;
use Medici\CookieNotice\Rules\Evaluators\Device_Evaluator;
use Medici\CookieNotice\Rules\Evaluators\URL_Evaluator;
use Medici\CookieNotice\Rules\Evaluators\Geo_Evaluator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditional Rules Engine
 *
 * Manages rule groups and evaluators for advanced conditional display.
 */
class Conditional_Rules {

	/**
	 * Plugin instance
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Table name for rule groups
	 *
	 * @var string
	 */
	private string $groups_table;

	/**
	 * Table name for rules
	 *
	 * @var string
	 */
	private string $rules_table;

	/**
	 * Registered evaluators
	 *
	 * @var array<string, Rule_Evaluator_Interface>
	 */
	private array $evaluators = [];

	/**
	 * Loaded rule groups
	 *
	 * @var array<int, Rule_Group>|null
	 */
	private ?array $groups = null;

	/**
	 * Cached display result
	 *
	 * @var bool|null
	 */
	private ?bool $cached_result = null;

	/**
	 * Constructor
	 *
	 * @param Cookie_Notice $plugin Plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		global $wpdb;

		$this->plugin       = $plugin;
		$this->groups_table = $wpdb->prefix . 'mcn_rule_groups';
		$this->rules_table  = $wpdb->prefix . 'mcn_rules';
	}

	/**
	 * Initialize the rules engine
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_default_evaluators();

		// Register AJAX handlers
		add_action( 'wp_ajax_mcn_save_rule_group', [ $this, 'ajax_save_rule_group' ] );
		add_action( 'wp_ajax_mcn_delete_rule_group', [ $this, 'ajax_delete_rule_group' ] );
		add_action( 'wp_ajax_mcn_get_rule_groups', [ $this, 'ajax_get_rule_groups' ] );
		add_action( 'wp_ajax_mcn_reorder_groups', [ $this, 'ajax_reorder_groups' ] );

		/**
		 * Action for third-party evaluator registration
		 *
		 * @param Conditional_Rules $this The rules engine instance.
		 */
		do_action( 'mcn_register_rule_evaluators', $this );
	}

	/**
	 * Register default evaluators
	 *
	 * @return void
	 */
	private function register_default_evaluators(): void {
		$this->register_evaluator( new Page_Evaluator() );
		$this->register_evaluator( new User_Evaluator() );
		$this->register_evaluator( new User_Role_Evaluator() );
		$this->register_evaluator( new Device_Evaluator() );
		$this->register_evaluator( new URL_Evaluator() );
		$this->register_evaluator( new Geo_Evaluator() );
	}

	/**
	 * Register an evaluator
	 *
	 * @param Rule_Evaluator_Interface $evaluator Evaluator to register.
	 * @return void
	 */
	public function register_evaluator( Rule_Evaluator_Interface $evaluator ): void {
		$this->evaluators[ $evaluator->get_type() ] = $evaluator;
	}

	/**
	 * Get all registered evaluators
	 *
	 * @return array<string, Rule_Evaluator_Interface>
	 */
	public function get_evaluators(): array {
		return $this->evaluators;
	}

	/**
	 * Get evaluators info for JavaScript
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_evaluators_for_js(): array {
		$result = [];

		foreach ( $this->evaluators as $type => $evaluator ) {
			$result[ $type ] = [
				'label'     => $evaluator->get_label(),
				'operators' => $evaluator->get_operators(),
				'fieldType' => $evaluator->get_value_field_type(),
				'options'   => $evaluator->get_value_options(),
			];
		}

		return $result;
	}

	/**
	 * Load all rule groups from database
	 *
	 * @param bool $force Force reload.
	 * @return array<int, Rule_Group>
	 */
	public function load_groups( bool $force = false ): array {
		if ( null !== $this->groups && ! $force ) {
			return $this->groups;
		}

		global $wpdb;

		// Load groups
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$group_rows = $wpdb->get_results(
			"SELECT * FROM {$this->groups_table} WHERE is_active = 1 ORDER BY priority ASC"
		);

		$this->groups = [];

		if ( empty( $group_rows ) ) {
			return $this->groups;
		}

		foreach ( $group_rows as $row ) {
			$group = Rule_Group::from_db( $row );

			// Load rules for this group
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rule_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->rules_table} WHERE group_id = %d AND is_active = 1 ORDER BY sort_order ASC",
					$group->id
				)
			);

			foreach ( $rule_rows as $rule_row ) {
				$group->add_rule( Rule::from_db( $rule_row ) );
			}

			$this->groups[] = $group;
		}

		return $this->groups;
	}

	/**
	 * Evaluate all rules and determine if banner should display
	 *
	 * @return bool True = show banner, False = hide banner.
	 */
	public function should_display(): bool {
		// Return cached result if available
		if ( null !== $this->cached_result ) {
			return $this->cached_result;
		}

		// If advanced rules are disabled, use simple conditional display
		if ( ! $this->plugin->get_option( 'enable_conditional_rules' ) ) {
			$this->cached_result = true;
			return true;
		}

		$groups = $this->load_groups();

		// No groups = show banner (default)
		if ( empty( $groups ) ) {
			$this->cached_result = true;
			return true;
		}

		// Evaluate each group in priority order
		foreach ( $groups as $group ) {
			$matches = $group->evaluate( $this->evaluators );
			$result  = $group->get_action_result( $matches );

			// If group matched and has a decision, use it
			if ( null !== $result ) {
				$this->cached_result = $result;
				return $result;
			}
		}

		// No groups matched, default to show
		$this->cached_result = true;
		return true;
	}

	/**
	 * Create database tables
	 *
	 * @return void
	 */
	public function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Rule groups table
		$sql_groups = "CREATE TABLE IF NOT EXISTS {$this->groups_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			operator enum('AND','OR') NOT NULL DEFAULT 'AND',
			action enum('show','hide') NOT NULL DEFAULT 'show',
			priority int(11) NOT NULL DEFAULT 10,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_active_priority (is_active, priority)
		) {$charset_collate};";

		// Rules table
		$sql_rules = "CREATE TABLE IF NOT EXISTS {$this->rules_table} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			group_id bigint(20) UNSIGNED NOT NULL,
			rule_type varchar(50) NOT NULL,
			operator varchar(20) NOT NULL,
			value text NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY idx_group (group_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_groups );
		dbDelta( $sql_rules );
	}

	/**
	 * Create a rule group
	 *
	 * @param array<string, mixed> $data Group data.
	 * @return int|false Group ID or false on failure.
	 */
	public function create_group( array $data ): int|false {
		global $wpdb;

		$insert_data = [
			'name'      => sanitize_text_field( $data['name'] ?? '' ),
			'operator'  => in_array( $data['operator'] ?? 'AND', [ 'AND', 'OR' ], true ) ? $data['operator'] : 'AND',
			'action'    => in_array( $data['action'] ?? 'show', [ 'show', 'hide' ], true ) ? $data['action'] : 'show',
			'priority'  => absint( $data['priority'] ?? 10 ),
			'is_active' => isset( $data['is_active'] ) ? ( $data['is_active'] ? 1 : 0 ) : 1,
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $this->groups_table, $insert_data );

		if ( false === $result ) {
			return false;
		}

		$group_id = (int) $wpdb->insert_id;

		// Add rules if provided
		if ( ! empty( $data['rules'] ) && is_array( $data['rules'] ) ) {
			foreach ( $data['rules'] as $index => $rule_data ) {
				$rule_data['group_id']   = $group_id;
				$rule_data['sort_order'] = $index;
				$this->create_rule( $rule_data );
			}
		}

		// Clear cache
		$this->groups        = null;
		$this->cached_result = null;

		return $group_id;
	}

	/**
	 * Update a rule group
	 *
	 * @param int                  $id Group ID.
	 * @param array<string, mixed> $data Group data.
	 * @return bool
	 */
	public function update_group( int $id, array $data ): bool {
		global $wpdb;

		$update_data = [];

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
		}
		if ( isset( $data['operator'] ) && in_array( $data['operator'], [ 'AND', 'OR' ], true ) ) {
			$update_data['operator'] = $data['operator'];
		}
		if ( isset( $data['action'] ) && in_array( $data['action'], [ 'show', 'hide' ], true ) ) {
			$update_data['action'] = $data['action'];
		}
		if ( isset( $data['priority'] ) ) {
			$update_data['priority'] = absint( $data['priority'] );
		}
		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = $data['is_active'] ? 1 : 0;
		}

		if ( empty( $update_data ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $this->groups_table, $update_data, [ 'id' => $id ] );

		// Update rules if provided
		if ( isset( $data['rules'] ) && is_array( $data['rules'] ) ) {
			// Delete existing rules
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $this->rules_table, [ 'group_id' => $id ] );

			// Add new rules
			foreach ( $data['rules'] as $index => $rule_data ) {
				$rule_data['group_id']   = $id;
				$rule_data['sort_order'] = $index;
				$this->create_rule( $rule_data );
			}
		}

		// Clear cache
		$this->groups        = null;
		$this->cached_result = null;

		return false !== $result;
	}

	/**
	 * Delete a rule group
	 *
	 * @param int $id Group ID.
	 * @return bool
	 */
	public function delete_group( int $id ): bool {
		global $wpdb;

		// Delete rules first
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->rules_table, [ 'group_id' => $id ] );

		// Delete group
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $this->groups_table, [ 'id' => $id ] );

		// Clear cache
		$this->groups        = null;
		$this->cached_result = null;

		return false !== $result;
	}

	/**
	 * Create a rule
	 *
	 * @param array<string, mixed> $data Rule data.
	 * @return int|false Rule ID or false on failure.
	 */
	public function create_rule( array $data ): int|false {
		global $wpdb;

		$value = $data['value'] ?? '';
		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$insert_data = [
			'group_id'   => absint( $data['group_id'] ?? 0 ),
			'rule_type'  => sanitize_key( $data['rule_type'] ?? '' ),
			'operator'   => sanitize_key( $data['operator'] ?? 'is' ),
			'value'      => sanitize_text_field( (string) $value ),
			'is_active'  => isset( $data['is_active'] ) ? ( $data['is_active'] ? 1 : 0 ) : 1,
			'sort_order' => absint( $data['sort_order'] ?? 0 ),
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $this->rules_table, $insert_data );

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get all groups for admin display
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_groups(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$groups = $wpdb->get_results(
			"SELECT * FROM {$this->groups_table} ORDER BY priority ASC",
			ARRAY_A
		);

		if ( empty( $groups ) ) {
			return [];
		}

		foreach ( $groups as &$group ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$group['rules'] = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->rules_table} WHERE group_id = %d ORDER BY sort_order ASC",
					$group['id']
				),
				ARRAY_A
			);
		}

		return $groups;
	}

	/**
	 * AJAX: Save rule group
	 *
	 * @return void
	 */
	public function ajax_save_rule_group(): void {
		check_ajax_referer( 'mcn_rules_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$data = isset( $_POST['group'] ) ? json_decode( wp_unslash( $_POST['group'] ), true ) : [];

		if ( empty( $data ) ) {
			wp_send_json_error( [ 'message' => 'Invalid data' ] );
		}

		if ( ! empty( $data['id'] ) ) {
			$result = $this->update_group( (int) $data['id'], $data );
			$id     = (int) $data['id'];
		} else {
			$id     = $this->create_group( $data );
			$result = false !== $id;
		}

		if ( $result ) {
			wp_send_json_success( [
				'message' => __( 'Групу збережено.', 'medici-cookie-notice' ),
				'id'      => $id,
			] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Помилка збереження.', 'medici-cookie-notice' ) ] );
		}
	}

	/**
	 * AJAX: Delete rule group
	 *
	 * @return void
	 */
	public function ajax_delete_rule_group(): void {
		check_ajax_referer( 'mcn_rules_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}

		if ( $this->delete_group( $id ) ) {
			wp_send_json_success( [ 'message' => __( 'Групу видалено.', 'medici-cookie-notice' ) ] );
		} else {
			wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'medici-cookie-notice' ) ] );
		}
	}

	/**
	 * AJAX: Get all rule groups
	 *
	 * @return void
	 */
	public function ajax_get_rule_groups(): void {
		check_ajax_referer( 'mcn_rules_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$groups = $this->get_all_groups();

		wp_send_json_success( [
			'groups'     => $groups,
			'evaluators' => $this->get_evaluators_for_js(),
		] );
	}

	/**
	 * AJAX: Reorder groups
	 *
	 * @return void
	 */
	public function ajax_reorder_groups(): void {
		check_ajax_referer( 'mcn_rules_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ] );
		}

		$order = isset( $_POST['order'] ) ? array_map( 'absint', (array) $_POST['order'] ) : [];

		global $wpdb;

		foreach ( $order as $priority => $group_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$this->groups_table,
				[ 'priority' => $priority ],
				[ 'id' => $group_id ]
			);
		}

		// Clear cache
		$this->groups        = null;
		$this->cached_result = null;

		wp_send_json_success( [ 'message' => __( 'Порядок збережено.', 'medici-cookie-notice' ) ] );
	}
}

<?php
/**
 * Lead Custom Post Type
 *
 * Stores consultation requests as custom post type for better management.
 * Each lead contains: name, email, phone, service, message, UTM params, etc.
 *
 * @package    Medici_Agency
 * @subpackage Leads
 * @since      1.4.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lead CPT Handler Class
 *
 * @since 1.4.0
 */
final class Lead_CPT {

	/**
	 * Post type slug
	 */
	private const POST_TYPE = 'medici_lead';

	/**
	 * Meta key prefix
	 */
	private const META_PREFIX = '_medici_lead_';

	/**
	 * Initialize Lead CPT
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		// Register custom post type
		add_action( 'init', array( $self, 'register_post_type' ) );

		// Add meta boxes
		add_action( 'add_meta_boxes', array( $self, 'add_meta_boxes' ) );

		// Customize admin columns
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $self, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $self, 'admin_column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( $self, 'sortable_columns' ) );

		// Make columns sortable
		add_action( 'pre_get_posts', array( $self, 'sortable_columns_orderby' ) );

		// Update post title on save
		add_action( 'save_post_' . self::POST_TYPE, array( $self, 'update_post_title' ), 10, 2 );
	}

	/**
	 * Register custom post type
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Ліди', 'medici.agency' ),
			'singular_name'      => __( 'Лід', 'medici.agency' ),
			'menu_name'          => __( 'Ліди', 'medici.agency' ),
			'all_items'          => __( 'Всі ліди', 'medici.agency' ),
			'add_new'            => __( 'Додати новий', 'medici.agency' ),
			'add_new_item'       => __( 'Додати новий лід', 'medici.agency' ),
			'edit_item'          => __( 'Редагувати лід', 'medici.agency' ),
			'new_item'           => __( 'Новий лід', 'medici.agency' ),
			'view_item'          => __( 'Переглянути лід', 'medici.agency' ),
			'search_items'       => __( 'Знайти ліди', 'medici.agency' ),
			'not_found'          => __( 'Лідів не знайдено', 'medici.agency' ),
			'not_found_in_trash' => __( 'Лідів у кошику не знайдено', 'medici.agency' ),
			'parent_item_colon'  => __( 'Батьківський лід:', 'medici.agency' ),
		);

		$args = array(
			'labels'           => $labels,
			'public'           => false,
			'show_ui'          => true,
			'show_in_menu'     => true,
			'menu_position'    => 25,
			'menu_icon'        => 'dashicons-email',
			'capability_type'  => 'post',
			'capabilities'     => array(
				'create_posts' => 'do_not_allow', // Prevent manual creation
			),
			'map_meta_cap'     => true,
			'supports'         => array( 'title', 'custom-fields' ),
			'has_archive'      => false,
			'rewrite'          => false,
			'query_var'        => false,
			'can_export'       => true,
			'delete_with_user' => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add meta boxes
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function add_meta_boxes(): void {
		// Lead details
		add_meta_box(
			'medici_lead_details',
			__( 'Деталі ліда', 'medici.agency' ),
			array( $this, 'render_lead_details_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);

		// UTM parameters
		add_meta_box(
			'medici_lead_utm',
			__( 'UTM параметри', 'medici.agency' ),
			array( $this, 'render_utm_meta_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);

		// Lead status
		add_meta_box(
			'medici_lead_status',
			__( 'Статус ліда', 'medici.agency' ),
			array( $this, 'render_status_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render lead details meta box
	 *
	 * @since 1.4.0
	 * @param \WP_Post $post Current post object
	 * @return void
	 */
	public function render_lead_details_meta_box( \WP_Post $post ): void {
		$name     = get_post_meta( $post->ID, self::META_PREFIX . 'name', true );
		$email    = get_post_meta( $post->ID, self::META_PREFIX . 'email', true );
		$phone    = get_post_meta( $post->ID, self::META_PREFIX . 'phone', true );
		$service  = get_post_meta( $post->ID, self::META_PREFIX . 'service', true );
		$message  = get_post_meta( $post->ID, self::META_PREFIX . 'message', true );
		$page_url = get_post_meta( $post->ID, self::META_PREFIX . 'page_url', true );

		?>
		<table class="form-table">
			<tr>
				<th><label><?php esc_html_e( 'Ім\'я:', 'medici.agency' ); ?></label></th>
				<td><strong><?php echo esc_html( $name ?: '—' ); ?></strong></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Email:', 'medici.agency' ); ?></label></th>
				<td>
					<?php if ( $email ) : ?>
						<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Телефон:', 'medici.agency' ); ?></label></th>
				<td>
					<?php if ( $phone ) : ?>
						<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Послуга:', 'medici.agency' ); ?></label></th>
				<td><?php echo esc_html( $service ?: '—' ); ?></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Повідомлення:', 'medici.agency' ); ?></label></th>
				<td><?php echo $message ? wp_kses_post( nl2br( $message ) ) : '—'; ?></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Сторінка:', 'medici.agency' ); ?></label></th>
				<td>
					<?php if ( $page_url ) : ?>
						<a href="<?php echo esc_url( $page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $page_url ); ?></a>
					<?php else : ?>
						—
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render UTM meta box
	 *
	 * @since 1.4.0
	 * @param \WP_Post $post Current post object
	 * @return void
	 */
	public function render_utm_meta_box( \WP_Post $post ): void {
		$utm_source   = get_post_meta( $post->ID, self::META_PREFIX . 'utm_source', true );
		$utm_medium   = get_post_meta( $post->ID, self::META_PREFIX . 'utm_medium', true );
		$utm_campaign = get_post_meta( $post->ID, self::META_PREFIX . 'utm_campaign', true );
		$utm_term     = get_post_meta( $post->ID, self::META_PREFIX . 'utm_term', true );
		$utm_content  = get_post_meta( $post->ID, self::META_PREFIX . 'utm_content', true );

		?>
		<table class="form-table">
			<?php if ( $utm_source ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Source:', 'medici.agency' ); ?></label></th>
					<td><?php echo esc_html( $utm_source ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $utm_medium ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Medium:', 'medici.agency' ); ?></label></th>
					<td><?php echo esc_html( $utm_medium ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $utm_campaign ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Campaign:', 'medici.agency' ); ?></label></th>
					<td><?php echo esc_html( $utm_campaign ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $utm_term ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Term:', 'medici.agency' ); ?></label></th>
					<td><?php echo esc_html( $utm_term ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( $utm_content ) : ?>
				<tr>
					<th><label><?php esc_html_e( 'Content:', 'medici.agency' ); ?></label></th>
					<td><?php echo esc_html( $utm_content ); ?></td>
				</tr>
			<?php endif; ?>
			<?php if ( ! $utm_source && ! $utm_medium && ! $utm_campaign && ! $utm_term && ! $utm_content ) : ?>
				<tr>
					<td><em><?php esc_html_e( 'UTM параметри відсутні', 'medici.agency' ); ?></em></td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * Render status meta box
	 *
	 * @since 1.4.0
	 * @param \WP_Post $post Current post object
	 * @return void
	 */
	public function render_status_meta_box( \WP_Post $post ): void {
		$status   = get_post_meta( $post->ID, self::META_PREFIX . 'status', true ) ?: 'new';
		$statuses = $this->get_lead_statuses();

		wp_nonce_field( 'medici_lead_status_nonce', 'medici_lead_status_nonce' );

		?>
		<p>
			<label for="medici_lead_status"><?php esc_html_e( 'Статус:', 'medici.agency' ); ?></label>
			<select id="medici_lead_status" name="medici_lead_status" style="width: 100%; margin-top: 5px;">
				<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Get lead statuses
	 *
	 * @since 1.4.0
	 * @return array<string, string> Status key => label
	 */
	private function get_lead_statuses(): array {
		return array(
			'new'       => __( '🆕 Новий', 'medici.agency' ),
			'contacted' => __( '📞 Зв\'язались', 'medici.agency' ),
			'qualified' => __( '✅ Кваліфікований', 'medici.agency' ),
			'closed'    => __( '🎉 Закритий', 'medici.agency' ),
			'lost'      => __( '❌ Втрачений', 'medici.agency' ),
		);
	}

	/**
	 * Customize admin columns
	 *
	 * @since 1.4.0
	 * @param array<string, string> $columns Existing columns
	 * @return array<string, string> Modified columns
	 */
	public function admin_columns( array $columns ): array {
		$new_columns = array(
			'cb'      => $columns['cb'],
			'title'   => __( 'Лід', 'medici.agency' ),
			'name'    => __( 'Ім\'я', 'medici.agency' ),
			'email'   => __( 'Email', 'medici.agency' ),
			'phone'   => __( 'Телефон', 'medici.agency' ),
			'service' => __( 'Послуга', 'medici.agency' ),
			'status'  => __( 'Статус', 'medici.agency' ),
			'date'    => __( 'Дата', 'medici.agency' ),
		);

		return $new_columns;
	}

	/**
	 * Render admin column content
	 *
	 * @since 1.4.0
	 * @param string $column  Column name
	 * @param int    $post_id Post ID
	 * @return void
	 */
	public function admin_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'name':
				$name = get_post_meta( $post_id, self::META_PREFIX . 'name', true );
				echo esc_html( $name ?: '—' );
				break;

			case 'email':
				$email = get_post_meta( $post_id, self::META_PREFIX . 'email', true );
				if ( $email ) {
					echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
				} else {
					echo '—';
				}
				break;

			case 'phone':
				$phone = get_post_meta( $post_id, self::META_PREFIX . 'phone', true );
				if ( $phone ) {
					echo '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>';
				} else {
					echo '—';
				}
				break;

			case 'service':
				$service = get_post_meta( $post_id, self::META_PREFIX . 'service', true );
				echo esc_html( $service ?: '—' );
				break;

			case 'status':
				$status   = get_post_meta( $post_id, self::META_PREFIX . 'status', true ) ?: 'new';
				$statuses = $this->get_lead_statuses();
				echo esc_html( $statuses[ $status ] ?? $status );
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @since 1.4.0
	 * @param array<string, string> $columns Sortable columns
	 * @return array<string, string> Modified columns
	 */
	public function sortable_columns( array $columns ): array {
		$columns['name']    = 'name';
		$columns['email']   = 'email';
		$columns['status']  = 'status';
		$columns['service'] = 'service';

		return $columns;
	}

	/**
	 * Handle sortable columns orderby
	 *
	 * @since 1.4.0
	 * @param \WP_Query $query Current query
	 * @return void
	 */
	public function sortable_columns_orderby( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( in_array( $orderby, array( 'name', 'email', 'status', 'service' ), true ) ) {
			$query->set( 'meta_key', self::META_PREFIX . $orderby );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Update post title on save
	 *
	 * Automatically set post title to "Lead #{ID} - {Name}"
	 *
	 * @since 1.4.0
	 * @param int      $post_id Post ID
	 * @param \WP_Post $post    Post object
	 * @return void
	 */
	public function update_post_title( int $post_id, \WP_Post $post ): void {
		// Avoid infinite loop
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Update status if submitted
		if ( isset( $_POST['medici_lead_status_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['medici_lead_status_nonce'] ) ), 'medici_lead_status_nonce' ) ) {
			if ( isset( $_POST['medici_lead_status'] ) ) {
				$new_status = sanitize_key( wp_unslash( $_POST['medici_lead_status'] ) );
				update_post_meta( $post_id, self::META_PREFIX . 'status', $new_status );
			}
		}

		// Update title if needed
		$name      = get_post_meta( $post_id, self::META_PREFIX . 'name', true );
		$email     = get_post_meta( $post_id, self::META_PREFIX . 'email', true );
		$new_title = sprintf(
			'Лід #%d - %s',
			$post_id,
			$name ?: $email ?: 'Без імені'
		);

		if ( $post->post_title !== $new_title ) {
			remove_action( 'save_post_' . self::POST_TYPE, array( $this, 'update_post_title' ), 10 );

			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $new_title,
				)
			);

			add_action( 'save_post_' . self::POST_TYPE, array( $this, 'update_post_title' ), 10, 2 );
		}
	}

	/**
	 * Create lead from consultation data
	 *
	 * Static helper method to create lead post from form submission.
	 *
	 * @since 1.4.0
	 * @param array<string, mixed> $data Lead data
	 * @return int Lead post ID or 0 on failure
	 */
	public static function create_lead( array $data ): int {
		// Create post
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sprintf(
					'Лід - %s',
					$data['name'] ?? $data['email'] ?? 'Без імені'
				),
			)
		);

		// wp_insert_post returns 0 on failure (WP_Error only with $wp_error=true)
		if ( ! $post_id ) {
			return 0;
		}

		// Save meta fields
		$meta_fields = array(
			'name',
			'email',
			'phone',
			'service',
			'message',
			'page_url',
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_term',
			'utm_content',
		);

		foreach ( $meta_fields as $field ) {
			if ( isset( $data[ $field ] ) && '' !== $data[ $field ] ) {
				update_post_meta( $post_id, self::META_PREFIX . $field, $data[ $field ] );
			}
		}

		// Set initial status
		update_post_meta( $post_id, self::META_PREFIX . 'status', 'new' );

		return $post_id;
	}
}

<?php
/**
 * REST API Endpoints
 *
 * Provides REST API endpoints for leads and blog analytics.
 * All endpoints require authentication and appropriate capabilities.
 *
 * Endpoints:
 * - GET  /medici/v1/leads                - List leads with filters
 * - GET  /medici/v1/leads/{id}           - Get single lead
 * - PUT  /medici/v1/leads/{id}/status    - Update lead status
 * - GET  /medici/v1/leads/stats          - Get lead statistics
 * - GET  /medici/v1/leads/export         - Export leads to CSV
 * - GET  /medici/v1/blog/stats           - Get blog statistics
 * - GET  /medici/v1/blog/posts           - List blog posts with views
 * - GET  /medici/v1/blog/{id}/seo        - Get SEO audit for post
 *
 * @package    Medici_Agency
 * @subpackage REST_API
 * @since      1.5.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Controller Class
 *
 * @since 1.5.0
 */
final class REST_API {

	/**
	 * API namespace
	 */
	private const NAMESPACE = 'medici/v1';

	/**
	 * Initialize REST API
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public static function init(): void {
		$self = new self();

		add_action( 'rest_api_init', array( $self, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function register_routes(): void {
		// ====================================================================
		// LEADS ENDPOINTS
		// ====================================================================

		// GET /leads - List leads with filters
		register_rest_route(
			self::NAMESPACE,
			'/leads',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_leads' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'per_page' => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0 && $v <= 100,
					),
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0,
					),
					'status'   => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => fn( $v ) => empty( $v ) || in_array( $v, array( 'new', 'contacted', 'qualified', 'closed', 'lost' ), true ),
					),
					'service'  => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search'   => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'orderby'  => array(
						'default'           => 'date',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => fn( $v ) => in_array( $v, array( 'date', 'name', 'email', 'status' ), true ),
					),
					'order'    => array(
						'default'           => 'DESC',
						'sanitize_callback' => 'strtoupper',
						'validate_callback' => fn( $v ) => in_array( $v, array( 'ASC', 'DESC' ), true ),
					),
				),
			)
		);

		// GET /leads/{id} - Get single lead
		register_rest_route(
			self::NAMESPACE,
			'/leads/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_lead' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0,
					),
				),
			)
		);

		// PUT /leads/{id}/status - Update lead status
		register_rest_route(
			self::NAMESPACE,
			'/leads/(?P<id>\d+)/status',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_lead_status' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'id'     => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0,
					),
					'status' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => fn( $v ) => in_array( $v, array( 'new', 'contacted', 'qualified', 'closed', 'lost' ), true ),
					),
				),
			)
		);

		// GET /leads/stats - Get lead statistics
		register_rest_route(
			self::NAMESPACE,
			'/leads/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_lead_stats' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'days' => array(
						'default'           => 30,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0 && $v <= 365,
					),
				),
			)
		);

		// GET /leads/export - Export leads to JSON
		register_rest_route(
			self::NAMESPACE,
			'/leads/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_leads' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'status'    => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					),
					'date_from' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date_to'   => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// ====================================================================
		// BLOG ENDPOINTS
		// ====================================================================

		// GET /blog/stats - Get blog statistics
		register_rest_route(
			self::NAMESPACE,
			'/blog/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_blog_stats' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		// GET /blog/posts - List blog posts with views
		register_rest_route(
			self::NAMESPACE,
			'/blog/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_blog_posts' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'per_page' => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0 && $v <= 100,
					),
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'orderby'  => array(
						'default'           => 'date',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => fn( $v ) => in_array( $v, array( 'date', 'views', 'title' ), true ),
					),
					'order'    => array(
						'default'           => 'DESC',
						'sanitize_callback' => 'strtoupper',
						'validate_callback' => fn( $v ) => in_array( $v, array( 'ASC', 'DESC' ), true ),
					),
				),
			)
		);

		// GET /blog/{id}/seo - Get SEO audit for post
		register_rest_route(
			self::NAMESPACE,
			'/blog/(?P<id>\d+)/seo',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_seo' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0,
					),
				),
			)
		);

		// ====================================================================
		// PUBLIC ENDPOINTS (for frontend)
		// ====================================================================

		// GET /blog/popular - Get popular posts (public)
		register_rest_route(
			self::NAMESPACE,
			'/blog/popular',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_popular_posts' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'default'           => 5,
						'sanitize_callback' => 'absint',
						'validate_callback' => fn( $v ) => $v > 0 && $v <= 20,
					),
				),
			)
		);
	}

	/**
	 * Check admin permission
	 *
	 * @since 1.5.0
	 * @return bool|\WP_Error
	 */
	public function check_admin_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Доступ заборонено. Потрібні права адміністратора.', 'medici.agency' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	// ========================================================================
	// LEADS ENDPOINTS
	// ========================================================================

	/**
	 * Get leads list
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function get_leads( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$status   = $request->get_param( 'status' );
		$service  = $request->get_param( 'service' );
		$search   = $request->get_param( 'search' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = $request->get_param( 'order' );

		$args = array(
			'post_type'      => 'medici_lead',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'order'          => $order,
		);

		// Search
		if ( $search ) {
			$args['s'] = $search;
		}

		// Order by
		if ( 'date' === $orderby ) {
			$args['orderby'] = 'date';
		} else {
			$args['meta_key'] = '_medici_lead_' . $orderby;
			$args['orderby']  = 'meta_value';
		}

		// Meta query for filters
		$meta_query = array();

		if ( $status ) {
			$meta_query[] = array(
				'key'   => '_medici_lead_status',
				'value' => $status,
			);
		}

		if ( $service ) {
			$meta_query[] = array(
				'key'   => '_medici_lead_service',
				'value' => $service,
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$query = new \WP_Query( $args );
		$leads = array();

		foreach ( $query->posts as $post ) {
			$leads[] = $this->format_lead( $post );
		}

		return new \WP_REST_Response(
			array(
				'leads'       => $leads,
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'page'        => $page,
				'per_page'    => $per_page,
			),
			200
		);
	}

	/**
	 * Get single lead
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error Response or error
	 */
	public function get_lead( \WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || 'medici_lead' !== $post->post_type ) {
			return new \WP_Error(
				'lead_not_found',
				__( 'Лід не знайдено', 'medici.agency' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( $this->format_lead( $post, true ), 200 );
	}

	/**
	 * Update lead status
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error Response or error
	 */
	public function update_lead_status( \WP_REST_Request $request ) {
		$post_id    = $request->get_param( 'id' );
		$new_status = $request->get_param( 'status' );
		$post       = get_post( $post_id );

		if ( ! $post || 'medici_lead' !== $post->post_type ) {
			return new \WP_Error(
				'lead_not_found',
				__( 'Лід не знайдено', 'medici.agency' ),
				array( 'status' => 404 )
			);
		}

		$old_status = get_post_meta( $post_id, '_medici_lead_status', true );
		update_post_meta( $post_id, '_medici_lead_status', $new_status );

		return new \WP_REST_Response(
			array(
				'success'    => true,
				'lead_id'    => $post_id,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'message'    => __( 'Статус оновлено', 'medici.agency' ),
			),
			200
		);
	}

	/**
	 * Get lead statistics
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function get_lead_stats( \WP_REST_Request $request ): \WP_REST_Response {
		$days = $request->get_param( 'days' );

		// Get all leads
		$leads = get_posts(
			array(
				'post_type'      => 'medici_lead',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$stats = array(
			'total'     => count( $leads ),
			'new'       => 0,
			'contacted' => 0,
			'qualified' => 0,
			'closed'    => 0,
			'lost'      => 0,
		);

		foreach ( $leads as $lead_id ) {
			$status = get_post_meta( $lead_id, '_medici_lead_status', true ) ?: 'new';
			if ( isset( $stats[ $status ] ) ) {
				++$stats[ $status ];
			}
		}

		// Conversion rate
		$stats['conversion_rate'] = $stats['total'] > 0
			? round( ( $stats['closed'] / $stats['total'] ) * 100, 2 )
			: 0;

		// Get by service
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_service = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value as service, COUNT(*) as count
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status = %s
				GROUP BY meta_value
				ORDER BY count DESC",
				'_medici_lead_service',
				'medici_lead',
				'publish'
			),
			ARRAY_A
		);

		// Get by UTM source
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_source = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value as source, COUNT(*) as count
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status = %s
				GROUP BY meta_value
				ORDER BY count DESC",
				'_medici_lead_utm_source',
				'medici_lead',
				'publish'
			),
			ARRAY_A
		);

		// Get by date (last N days)
		$by_date = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(post_date) as date, COUNT(*) as count
				FROM {$wpdb->posts}
				WHERE post_type = 'medici_lead'
				AND post_status = 'publish'
				AND post_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				GROUP BY DATE(post_date)
				ORDER BY date ASC",
				$days
			),
			ARRAY_A
		);

		return new \WP_REST_Response(
			array(
				'summary'    => $stats,
				'by_service' => $by_service,
				'by_source'  => $by_source,
				'by_date'    => $by_date,
			),
			200
		);
	}

	/**
	 * Export leads
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function export_leads( \WP_REST_Request $request ): \WP_REST_Response {
		$status    = $request->get_param( 'status' );
		$date_from = $request->get_param( 'date_from' );
		$date_to   = $request->get_param( 'date_to' );

		$args = array(
			'post_type'      => 'medici_lead',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$meta_query = array();

		if ( $status ) {
			$meta_query[] = array(
				'key'   => '_medici_lead_status',
				'value' => $status,
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		if ( $date_from || $date_to ) {
			$args['date_query'] = array();
			if ( $date_from ) {
				$args['date_query']['after'] = $date_from;
			}
			if ( $date_to ) {
				$args['date_query']['before'] = $date_to;
			}
			$args['date_query']['inclusive'] = true;
		}

		$leads = get_posts( $args );
		$data  = array();

		foreach ( $leads as $post ) {
			$data[] = $this->format_lead( $post, true );
		}

		return new \WP_REST_Response(
			array(
				'count'       => count( $data ),
				'leads'       => $data,
				'exported_at' => current_time( 'c' ),
			),
			200
		);
	}

	// ========================================================================
	// BLOG ENDPOINTS
	// ========================================================================

	/**
	 * Get blog statistics
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function get_blog_stats( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'medici_blog',
				'publish'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status = %s",
				'_medici_post_views',
				'medici_blog',
				'publish'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$featured = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = %s
				AND p.post_status = %s",
				'_medici_featured_article',
				'1',
				'medici_blog',
				'publish'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_reading_time = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND p.post_type = %s
				AND p.post_status = %s",
				'_medici_reading_time',
				'medici_blog',
				'publish'
			)
		);

		// Get by category
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_category = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.name, COUNT(*) as count
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				WHERE tt.taxonomy = %s
				AND p.post_type = %s
				AND p.post_status = %s
				GROUP BY t.term_id
				ORDER BY count DESC",
				'medici_blog_category',
				'medici_blog',
				'publish'
			),
			ARRAY_A
		);

		return new \WP_REST_Response(
			array(
				'total_posts'      => $total,
				'total_views'      => $total_views ?: 0,
				'featured_posts'   => $featured,
				'avg_reading_time' => round( $avg_reading_time ?: 0, 1 ),
				'by_category'      => $by_category,
			),
			200
		);
	}

	/**
	 * Get blog posts list
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function get_blog_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = $request->get_param( 'order' );

		$args = array(
			'post_type'      => 'medici_blog',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'order'          => $order,
		);

		if ( 'views' === $orderby ) {
			$args['meta_key'] = '_medici_post_views';
			$args['orderby']  = 'meta_value_num';
		} else {
			$args['orderby'] = $orderby;
		}

		$query = new \WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$posts[] = $this->format_blog_post( $post );
		}

		return new \WP_REST_Response(
			array(
				'posts'       => $posts,
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'page'        => $page,
				'per_page'    => $per_page,
			),
			200
		);
	}

	/**
	 * Get SEO audit for post
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error Response or error
	 */
	public function get_post_seo( \WP_REST_Request $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || 'medici_blog' !== $post->post_type ) {
			return new \WP_Error(
				'post_not_found',
				__( 'Статтю не знайдено', 'medici.agency' ),
				array( 'status' => 404 )
			);
		}

		$audit = $this->perform_seo_audit( $post );

		return new \WP_REST_Response( $audit, 200 );
	}

	/**
	 * Get popular posts (public endpoint)
	 *
	 * @since 1.5.0
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response Response
	 */
	public function get_popular_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = $request->get_param( 'limit' );

		$posts = get_posts(
			array(
				'post_type'      => 'medici_blog',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'meta_key'       => '_medici_post_views',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);

		$data = array();
		foreach ( $posts as $post ) {
			$data[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'url'          => get_permalink( $post->ID ),
				'excerpt'      => get_the_excerpt( $post ),
				'views'        => (int) get_post_meta( $post->ID, '_medici_post_views', true ) ?: 0,
				'reading_time' => (int) get_post_meta( $post->ID, '_medici_reading_time', true ) ?: 1,
				'thumbnail'    => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	// ========================================================================
	// HELPER METHODS
	// ========================================================================

	/**
	 * Format lead data for API response
	 *
	 * @since 1.5.0
	 * @param \WP_Post $post    Post object
	 * @param bool     $full    Include all fields
	 * @return array Formatted lead data
	 */
	private function format_lead( \WP_Post $post, bool $full = false ): array {
		$data = array(
			'id'       => $post->ID,
			'date'     => get_the_date( 'c', $post ),
			'name'     => get_post_meta( $post->ID, '_medici_lead_name', true ),
			'email'    => get_post_meta( $post->ID, '_medici_lead_email', true ),
			'phone'    => get_post_meta( $post->ID, '_medici_lead_phone', true ),
			'service'  => get_post_meta( $post->ID, '_medici_lead_service', true ),
			'status'   => get_post_meta( $post->ID, '_medici_lead_status', true ) ?: 'new',
			'edit_url' => get_edit_post_link( $post->ID, 'raw' ),
		);

		if ( $full ) {
			$data['message']      = get_post_meta( $post->ID, '_medici_lead_message', true );
			$data['page_url']     = get_post_meta( $post->ID, '_medici_lead_page_url', true );
			$data['utm_source']   = get_post_meta( $post->ID, '_medici_lead_utm_source', true );
			$data['utm_medium']   = get_post_meta( $post->ID, '_medici_lead_utm_medium', true );
			$data['utm_campaign'] = get_post_meta( $post->ID, '_medici_lead_utm_campaign', true );
			$data['utm_term']     = get_post_meta( $post->ID, '_medici_lead_utm_term', true );
			$data['utm_content']  = get_post_meta( $post->ID, '_medici_lead_utm_content', true );
		}

		return $data;
	}

	/**
	 * Format blog post data for API response
	 *
	 * @since 1.5.0
	 * @param \WP_Post $post Post object
	 * @return array Formatted post data
	 */
	private function format_blog_post( \WP_Post $post ): array {
		$seo_score = $this->calculate_seo_score( $post );

		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'date'         => get_the_date( 'c', $post ),
			'url'          => get_permalink( $post->ID ),
			'excerpt'      => get_the_excerpt( $post ),
			'views'        => (int) get_post_meta( $post->ID, '_medici_post_views', true ) ?: 0,
			'reading_time' => (int) get_post_meta( $post->ID, '_medici_reading_time', true ) ?: 1,
			'featured'     => '1' === get_post_meta( $post->ID, '_medici_featured_article', true ),
			'thumbnail'    => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
			'seo_score'    => $seo_score,
			'edit_url'     => get_edit_post_link( $post->ID, 'raw' ),
		);
	}

	/**
	 * Perform SEO audit for a post
	 *
	 * @since 1.5.0
	 * @param \WP_Post $post Post object
	 * @return array Audit results
	 */
	private function perform_seo_audit( \WP_Post $post ): array {
		$checks          = array();
		$recommendations = array();
		$score           = 0;
		$max_score       = 0;

		// 1. Title check (15 points)
		$max_score   += 15;
		$title_length = mb_strlen( $post->post_title );
		if ( $title_length >= 30 && $title_length <= 60 ) {
			$checks['title'] = array(
				'passed' => true,
				'value'  => $title_length,
			);
			$score          += 15;
		} else {
			$checks['title']   = array(
				'passed' => false,
				'value'  => $title_length,
			);
			$recommendations[] = $title_length < 30
				? __( 'Заголовок занадто короткий (мін. 30 символів)', 'medici.agency' )
				: __( 'Заголовок занадто довгий (макс. 60 символів)', 'medici.agency' );
		}

		// 2. Meta description check (15 points)
		$max_score       += 15;
		$meta_description = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );
		if ( ! $meta_description ) {
			$meta_description = get_post_meta( $post->ID, '_medici_meta_description', true );
		}
		$meta_length = mb_strlen( $meta_description ?: '' );
		if ( $meta_length >= 120 && $meta_length <= 160 ) {
			$checks['meta_description'] = array(
				'passed' => true,
				'value'  => $meta_length,
			);
			$score                     += 15;
		} else {
			$checks['meta_description'] = array(
				'passed' => false,
				'value'  => $meta_length,
			);
			if ( $meta_length === 0 ) {
				$recommendations[] = __( 'Додайте мета-опис (120-160 символів)', 'medici.agency' );
			}
		}

		// 3. Featured image check (15 points)
		$max_score               += 15;
		$has_thumbnail            = has_post_thumbnail( $post->ID );
		$checks['featured_image'] = array( 'passed' => $has_thumbnail );
		if ( $has_thumbnail ) {
			$score += 15;
		} else {
			$recommendations[] = __( 'Додайте зображення статті', 'medici.agency' );
		}

		// 4. Content length check (20 points)
		$max_score               += 20;
		$content                  = wp_strip_all_tags( $post->post_content );
		$word_count               = str_word_count( $content );
		$checks['content_length'] = array(
			'passed' => $word_count >= 800,
			'value'  => $word_count,
		);
		if ( $word_count >= 800 ) {
			$score += 20;
		} else {
			$recommendations[] = sprintf(
				__( 'Контент занадто короткий (%d слів, рекомендовано 800+)', 'medici.agency' ),
				$word_count
			);
		}

		// 5. Headings check (15 points)
		$max_score         += 15;
		$has_h2             = preg_match( '/<h2/i', $post->post_content );
		$checks['headings'] = array( 'passed' => (bool) $has_h2 );
		if ( $has_h2 ) {
			$score += 15;
		} else {
			$recommendations[] = __( 'Додайте підзаголовки H2', 'medici.agency' );
		}

		// 6. Internal links check (10 points)
		$max_score               += 10;
		$internal_links           = preg_match_all( '/href=["\']' . preg_quote( home_url(), '/' ) . '/i', $post->post_content );
		$checks['internal_links'] = array(
			'passed' => $internal_links >= 2,
			'value'  => $internal_links,
		);
		if ( $internal_links >= 2 ) {
			$score += 10;
		} else {
			$recommendations[] = __( 'Додайте більше внутрішніх посилань (мін. 2)', 'medici.agency' );
		}

		// 7. Images alt text check (10 points)
		$max_score           += 10;
		$images               = preg_match_all( '/<img[^>]+>/i', $post->post_content, $img_matches );
		$images_alt           = preg_match_all( '/<img[^>]+alt=["\'][^"\']+["\']/i', $post->post_content );
		$all_have_alt         = $images === 0 || $images_alt === $images;
		$checks['images_alt'] = array(
			'passed'   => $all_have_alt,
			'images'   => $images,
			'with_alt' => $images_alt,
		);
		if ( $all_have_alt ) {
			$score += 10;
		} else {
			$recommendations[] = __( 'Додайте alt-текст до всіх зображень', 'medici.agency' );
		}

		// max_score is always > 0 after all checks
		$percentage = (int) round( ( $score / $max_score ) * 100 );

		return array(
			'post_id'         => $post->ID,
			'title'           => $post->post_title,
			'url'             => get_permalink( $post->ID ),
			'score'           => $percentage,
			'level'           => $this->get_score_level( $percentage ),
			'checks'          => $checks,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Calculate SEO score
	 *
	 * @since 1.5.0
	 * @param \WP_Post $post Post object
	 * @return array Score data
	 */
	private function calculate_seo_score( \WP_Post $post ): array {
		$audit = $this->perform_seo_audit( $post );
		return array(
			'score' => $audit['score'],
			'level' => $audit['level'],
		);
	}

	/**
	 * Get score level
	 *
	 * @since 1.5.0
	 * @param int $score Score percentage
	 * @return string Level
	 */
	private function get_score_level( int $score ): string {
		if ( $score >= 80 ) {
			return 'good';
		}
		if ( $score >= 50 ) {
			return 'warning';
		}
		return 'bad';
	}
}

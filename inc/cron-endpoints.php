<?php
/**
 * CRON Endpoints для Medici Theme
 *
 * @package Medici
 * @version 1.0.1
 */

declare(strict_types=1);

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verify CRON request
 *
 * @param string $task Task name (e.g., 'medici_cleanup_transients')
 * @return bool
 */
function medici_verify_cron_request( string $task ): bool {
	// Check if constants defined
	if ( ! defined( 'MEDICI_CRON_SECRET' ) ) {
		return false;
	}

	// Verify task parameter and secret
	return isset( $_GET[ $task ] )
		&& isset( $_GET['secret'] )
		&& hash_equals( MEDICI_CRON_SECRET, $_GET['secret'] );
}

/**
 * CRON: Cleanup expired transients
 * URL: /?medici_cleanup_transients=1&secret=SECRET
 */
add_action(
	'init',
	function (): void {
		if ( ! medici_verify_cron_request( 'medici_cleanup_transients' ) ) {
			return;
		}

		global $wpdb;

		$time    = time();
		$expired = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$time
			)
		);

		$count = 0;
		foreach ( $expired as $transient ) {
			$key = str_replace( '_transient_timeout_', '', $transient );
			if ( delete_transient( $key ) ) {
				$count++;
			}
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "✅ Medici CRON: Deleted {$count} expired transients\n";
		exit;
	}
);

/**
 * CRON: Database optimization
 * URL: /?medici_optimize_db=1&secret=SECRET
 */
add_action(
	'init',
	function (): void {
		if ( ! medici_verify_cron_request( 'medici_optimize_db' ) ) {
			return;
		}

		global $wpdb;

		// Delete post revisions
		$revisions = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );

		// Delete trashed posts (older than 30 days)
		$trashed = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->posts} 
             WHERE post_status = 'trash' 
             AND post_modified < %s",
				date( 'Y-m-d', strtotime( '-30 days' ) )
			)
		);

		// Delete spam comments
		$spam = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'" );

		// Delete orphaned postmeta
		$orphans = $wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
         LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.ID IS NULL"
		);

		// Optimize tables
		$tables = $wpdb->get_col( 'SHOW TABLES' );
		foreach ( $tables as $table ) {
			$wpdb->query( "OPTIMIZE TABLE `{$table}`" );
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		printf(
			"✅ Medici CRON: DB optimized\n- Revisions: %d\n- Trashed: %d\n- Spam: %d\n- Orphans: %d\n- Tables: %d\n",
			(int) $revisions,
			(int) $trashed,
			(int) $spam,
			(int) $orphans,
			count( $tables )
		);
		exit;
	}
);

/**
 * CRON: Aggregate blog statistics (DEBUG VERSION)
 * URL: /?medici_blog_stats=1&secret=SECRET
 */
add_action(
	'init',
	function (): void {
		if ( ! medici_verify_cron_request( 'medici_blog_stats' ) ) {
			return;
		}

		global $wpdb;

		// Try both post types
		$total_medici_blog = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'medici_blog' AND post_status = 'publish'"
		);

		$total_post = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'post' AND post_status = 'publish'"
		);

		// Check posts with views
		$posts_with_views = $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
         WHERE meta_key = 'medici_views'"
		);

		// Total views
		$total_views = $wpdb->get_var(
			"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} 
         WHERE meta_key = 'medici_views'"
		);

		// Determine which post_type to use
		$post_type = $total_medici_blog > 0 ? 'medici_blog' : 'post';

		// Query top posts
		$top_posts = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'meta_key'       => 'medici_views',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
			)
		);

		// Cache results
		if ( $top_posts->found_posts > 0 ) {
			set_transient( 'medici_top_blog_posts', $top_posts->posts, DAY_IN_SECONDS );
			set_transient( 'medici_total_blog_views', (int) $total_views, DAY_IN_SECONDS );
		}

		// Response
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );

		echo "🔍 MEDICI BLOG STATS DEBUG\n";
		echo str_repeat( '=', 60 ) . "\n\n";

		echo "📊 POSTS COUNT:\n";
		echo "- medici_blog (published): {$total_medici_blog}\n";
		echo "- post (published): {$total_post}\n";
		echo "- Posts with 'medici_views': {$posts_with_views}\n";
		echo '- Total views: ' . number_format( (int) $total_views ) . "\n\n";

		echo "✅ USING post_type: {$post_type}\n";
		echo "✅ Top posts found: {$top_posts->found_posts}\n\n";

		if ( $top_posts->found_posts > 0 ) {
			echo "🏆 TOP 5 POSTS:\n";
			$i = 1;
			foreach ( array_slice( $top_posts->posts, 0, 5 ) as $post ) {
				$views = get_post_meta( $post->ID, 'medici_views', true );
				echo "{$i}. {$post->post_title} - {$views} views\n";
				$i++;
			}
			echo "\n✅ Transients cached!\n";
		} else {
			echo "⚠️ NO POSTS WITH VIEWS FOUND\n";
			echo "\nℹ️ POSSIBLE REASONS:\n";
			echo "1. Posts created as '{$post_type}' but expected different type\n";
			echo "2. No 'medici_views' meta field on posts\n";
			echo "3. View tracking not working (check inc/blog-meta-fields.php)\n";
		}

		exit;
	},
	5
); // Priority 5 (early)

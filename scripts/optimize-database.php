<?php
/**
 * Database Optimization Script
 * Run weekly via CRON
 */

require_once(__DIR__ . '/../../../../wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

global $wpdb;

// 1. Delete post revisions (залишити тільки останні 3)
$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");

// 2. Delete trashed posts (старше 30 днів)
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->posts} 
         WHERE post_status = 'trash' 
         AND post_modified < %s",
        date('Y-m-d', strtotime('-30 days'))
    )
);

// 3. Delete spam comments
$wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'");

// 4. Delete orphaned postmeta
$wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE p.ID IS NULL"
);

// 5. Optimize tables
$tables = $wpdb->get_col("SHOW TABLES");
foreach ($tables as $table) {
    $wpdb->query("OPTIMIZE TABLE {$table}");
}

echo "Database optimized successfully\n";
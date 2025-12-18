<?php
/**
 * Aggregate Blog Statistics
 * Update top viewed posts cache
 */

require_once(__DIR__ . '/../../../../wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded');
}

// Update top viewed posts cache (для Dashboard widget)
$top_posts = new WP_Query([
    'post_type' => 'medici_blog',
    'posts_per_page' => 10,
    'meta_key' => 'medici_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]);

set_transient('medici_top_blog_posts', $top_posts->posts, DAY_IN_SECONDS);

echo "Blog stats aggregated: " . $top_posts->found_posts . " top posts cached\n";
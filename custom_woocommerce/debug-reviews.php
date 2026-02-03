<?php
/**
 * Debug script to check reviews in database
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

global $wpdb;

echo "=== DEBUGGING REVIEWS ===\n\n";

// Check 1: Total comments on products
echo "1. Total comments on products:\n";
$total = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->comments} c
    INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
    WHERE p.post_type = 'product'"
);
echo "Total: $total\n\n";

// Check 2: All comments on products with details
echo "2. All comments on products:\n";
$comments = $wpdb->get_results(
    "SELECT c.comment_ID, c.comment_post_ID, c.comment_author, c.comment_approved, 
            c.comment_type, c.comment_date, p.post_title
    FROM {$wpdb->comments} c
    INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
    WHERE p.post_type = 'product'
    ORDER BY c.comment_date DESC"
);

if ($comments) {
    foreach ($comments as $comment) {
        echo "ID: {$comment->comment_ID}, Author: {$comment->comment_author}, Post: {$comment->post_title}, Type: '{$comment->comment_type}', Approved: {$comment->comment_approved}\n";
        
        // Check rating
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);
        echo "  → Rating: " . ($rating ? $rating : 'NO RATING') . "\n";
    }
}

// Check 3: Approved comments with ratings
echo "\n3. Approved comments with ratings:\n";
$approved = $wpdb->get_results(
    "SELECT c.comment_ID, c.comment_author, p.post_title
    FROM {$wpdb->comments} c
    INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
    INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id
    WHERE p.post_type = 'product'
    AND c.comment_approved = 1
    AND cm.meta_key = 'rating'
    AND cm.meta_value > 0"
);

if ($approved) {
    echo "Found: " . count($approved) . " approved reviews with ratings\n";
    foreach ($approved as $r) {
        echo "  - {$r->comment_author}: {$r->post_title}\n";
    }
} else {
    echo "No approved reviews with ratings found!\n";
}

// Check 4: Test the helper function
echo "\n4. Testing helper function:\n";
if (function_exists('custom_woocommerce_get_product_reviews')) {
    $reviews = custom_woocommerce_get_product_reviews(12);
    echo "Helper function returned: " . count($reviews) . " reviews\n";
    foreach ($reviews as $r) {
        echo "  - {$r->comment_author}: " . get_the_title($r->comment_post_ID) . "\n";
    }
} else {
    echo "ERROR: Helper function not found!\n";
}

echo "\n=== END DEBUG ===\n";
?>

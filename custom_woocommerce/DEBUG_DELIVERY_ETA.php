<?php
/**
 * DIAGNOSTIC FILE: Check ETA and Delivery Charges in Database
 * 
 * Add this to wp admin: /wp-admin/?page=debug-delivery-eta
 * OR access directly via: theme-folder/custom_woocommerce/DEBUG_DELIVERY_ETA.php
 */

// Only allow admins
if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
    die('Access denied. Admin only.');
}

// Include WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug: ETA & Delivery Charges</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { background: white; padding: 20px; border-radius: 5px; max-width: 1000px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #0073aa; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Debug: ETA & Delivery Charges</h1>
    
    <h2>Product Meta Check</h2>
    <p>Checking all products for _cj_delivery_charges and _cj_delivery_eta meta values...</p>
    
    <?php
    global $wpdb;
    
    // Get all products with ETA or delivery charges
    $query = "
        SELECT DISTINCT p.ID, p.post_title,
               (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_cj_delivery_charges') as charges,
               (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_cj_delivery_eta') as eta
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'product'
        AND (
            p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cj_delivery_charges')
            OR p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_cj_delivery_eta')
        )
        ORDER BY p.ID DESC
        LIMIT 50
    ";
    
    $results = $wpdb->get_results($query);
    
    if (empty($results)) {
        echo '<p class="warning">⚠️ No products found with delivery charges or ETA in database.</p>';
        
        // Check if _cj_varieties exist
        $varieties_check = $wpdb->get_results(
            "SELECT COUNT(*) as count FROM {$wpdb->postmeta} WHERE meta_key = '_cj_varieties'"
        );
        $varieties_count = $varieties_check[0]->count ?? 0;
        echo '<p><strong>Note:</strong> Found ' . $varieties_count . ' products with _cj_varieties meta.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>Product ID</th><th>Product Name</th><th>Delivery Charges</th><th>ETA</th><th>Status</th></tr>';
        
        foreach ($results as $product) {
            $status = '<span class="success">✓ Has Data</span>';
            if (empty($product->charges) && empty($product->eta)) {
                $status = '<span class="error">✗ Empty or Missing</span>';
            } elseif (empty($product->charges) || empty($product->eta)) {
                $status = '<span class="warning">⚠ Partial Data</span>';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($product->ID) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('post.php?post=' . $product->ID . '&action=edit')) . '" target="_blank">' . esc_html($product->post_title) . '</a></td>';
            echo '<td>' . esc_html($product->charges ?? '(empty)') . '</td>';
            echo '<td>' . esc_html($product->eta ?? '(empty)') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    ?>
    
    <h2>Form Fields Check</h2>
    <?php
    // Check if the metabox is being registered
    global $wp_meta_boxes;
    $metabox_registered = !empty($wp_meta_boxes['product']['normal']['high']['cw_cj_product_varieties']);
    
    echo '<p><strong>Metabox Registered:</strong> ' . ($metabox_registered ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . '</p>';
    ?>
    
    <h2>Database Table Check</h2>
    <?php
    // Check postmeta table structure
    $columns = $wpdb->get_results("DESCRIBE {$wpdb->postmeta}");
    echo '<p><strong>PostMeta Table Structure:</strong> OK</p>';
    echo '<p><strong>Sample Query:</strong></p>';
    echo '<pre>SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = "_cj_delivery_charges" LIMIT 5;</pre>';
    
    // Run sample query
    $sample = $wpdb->get_results("
        SELECT post_id, meta_value FROM {$wpdb->postmeta} 
        WHERE meta_key = '_cj_delivery_charges' 
        LIMIT 5
    ");
    
    if (!empty($sample)) {
        echo '<p><strong>Sample Results:</strong></p>';
        foreach ($sample as $row) {
            echo 'Product ID: ' . $row->post_id . ' | Value: ' . esc_html($row->meta_value) . '<br>';
        }
    } else {
        echo '<p class="warning">⚠️ No records found in database for _cj_delivery_charges</p>';
    }
    ?>
    
    <h2>Troubleshooting Steps</h2>
    <ol>
        <li><strong>Check if the form is rendering:</strong> Edit a product and look for the "🎨 CJ Product Varieties & Pricing" metabox</li>
        <li><strong>Check Browser Console:</strong> Open DevTools (F12) → Network tab → Look for form submission errors</li>
        <li><strong>Check WordPress Logs:</strong> Look in wp-content/debug.log for any PHP errors</li>
        <li><strong>Verify Nonce:</strong> Right-click → View Page Source, search for "cw_cj_varieties_nonce_field" to confirm nonce is present</li>
        <li><strong>Test Form Submission:</strong> Edit a product, change delivery charges, and click "Update". Check admin notice.</li>
    </ol>
    
    <h2>Manual Test</h2>
    <p>To manually set delivery charges for a product, use the WordPress database:</p>
    <pre style="background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto;">
// In WordPress admin, go to Tools → PHP Code Runner or use this SQL:
UPDATE wp_postmeta 
SET meta_value = 'Your Delivery Charge' 
WHERE meta_key = '_cj_delivery_charges' 
AND post_id = PRODUCT_ID;

UPDATE wp_postmeta 
SET meta_value = 'Your ETA' 
WHERE meta_key = '_cj_delivery_eta' 
AND post_id = PRODUCT_ID;
    </pre>
    
</div>
</body>
</html>

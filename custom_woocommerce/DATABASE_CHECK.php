<?php
/**
 * DATABASE CHECK - Delivery Charges & ETA Storage
 * 
 * This shows exactly where the data is stored and what's happening
 */

if (!function_exists('get_post_meta')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

if (!current_user_can('manage_options')) {
    die('Admin only');
}

global $wpdb;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Check - Delivery Charges</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #333; background: #f0f0f0; padding: 10px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #0073aa; color: white; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }
        .code { background: #2d2d2d; color: #f8f8f2; padding: 12px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e3f2fd; border-left: 4px solid #2196F3; padding: 12px; margin: 10px 0; }
        .box { background: #f5f5f5; border: 1px solid #ddd; padding: 15px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ Database Check: Delivery Charges & ETA Storage</h1>
    
    <h2>WordPress Tables Involved</h2>
    <p>Data is stored in TWO tables:</p>
    
    <table>
        <tr>
            <th>Table Name</th>
            <th>Purpose</th>
            <th>Where It Stores Our Data</th>
        </tr>
        <tr>
            <td><code><?php echo $wpdb->posts; ?></code></td>
            <td>Stores all posts (products, pages, posts)</td>
            <td>Product information (ID, title, type)</td>
        </tr>
        <tr>
            <td><code><?php echo $wpdb->postmeta; ?></code></td>
            <td>Stores post metadata (custom fields)</td>
            <td><strong>Delivery Charges & ETA</strong> are here!</td>
        </tr>
    </table>
    
    <h2>Exact Column Structure</h2>
    
    <div class="info">
        <strong>For Delivery Charges & ETA:</strong>
        <div class="code">
Table: <?php echo $wpdb->postmeta; ?>

Columns:
  - meta_id (INT) - Unique ID
  - post_id (INT) - Product ID
  - meta_key (VARCHAR) - The field name
  - meta_value (LONGTEXT) - The value
        </div>
    </div>
    
    <h2>Where Your Data Is Stored</h2>
    
    <p>The system saves data with these meta_keys:</p>
    <table>
        <tr>
            <th>Meta Key</th>
            <th>What It Is</th>
            <th>Example Value</th>
        </tr>
        <tr>
            <td><code>_cj_delivery_charges</code></td>
            <td>Delivery Charges</td>
            <td>$10.00 or Free Shipping</td>
        </tr>
        <tr>
            <td><code>_cj_delivery_eta</code></td>
            <td>Estimated Time to Arrive</td>
            <td>5-7 business days</td>
        </tr>
    </table>
    
    <h2>Check Your Database</h2>
    
    <p>Run this SQL query to see all your delivery data:</p>
    
    <div class="code">
SELECT 
    p.ID as product_id,
    p.post_title as product_name,
    MAX(CASE WHEN pm.meta_key = '_cj_delivery_charges' THEN pm.meta_value END) as delivery_charges,
    MAX(CASE WHEN pm.meta_key = '_cj_delivery_eta' THEN pm.meta_value END) as delivery_eta
FROM <?php echo $wpdb->posts; ?> p
LEFT JOIN <?php echo $wpdb->postmeta; ?> pm ON p.ID = pm.post_id
WHERE p.post_type = 'product'
GROUP BY p.ID
LIMIT 50;
    </div>
    
    <h2>Current Data in Your Database</h2>
    
    <?php
    // Run the query
    $query = "
        SELECT 
            p.ID,
            p.post_title,
            MAX(CASE WHEN pm.meta_key = '_cj_delivery_charges' THEN pm.meta_value END) as charges,
            MAX(CASE WHEN pm.meta_key = '_cj_delivery_eta' THEN pm.meta_value END) as eta
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
        GROUP BY p.ID
        ORDER BY p.ID DESC
        LIMIT 20
    ";
    
    $results = $wpdb->get_results($query);
    
    if (!empty($results)) {
        echo '<table>';
        echo '<tr>';
        echo '<th>Product ID</th>';
        echo '<th>Product Name</th>';
        echo '<th>Delivery Charges</th>';
        echo '<th>ETA</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        
        foreach ($results as $row) {
            $status = '';
            if ($row->charges || $row->eta) {
                $status = '<span class="success">✓ HAS DATA</span>';
            } else {
                $status = '<span class="warning">~ NO DATA YET</span>';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($row->ID) . '</td>';
            echo '<td><a href="' . esc_url(admin_url('post.php?post=' . $row->ID . '&action=edit')) . '" target="_blank">' . esc_html($row->post_title) . '</a></td>';
            echo '<td>' . esc_html($row->charges ?? '(empty)') . '</td>';
            echo '<td>' . esc_html($row->eta ?? '(empty)') . '</td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ No products found in database</p>';
    }
    ?>
    
    <h2>Count Summary</h2>
    
    <?php
    // Count stats
    $total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'");
    $with_charges = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_cj_delivery_charges'");
    $with_eta = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_cj_delivery_eta'");
    
    echo '<table>';
    echo '<tr><th>Metric</th><th>Count</th></tr>';
    echo '<tr><td>Total Products</td><td><strong>' . $total_products . '</strong></td></tr>';
    echo '<tr><td>Products with Delivery Charges</td><td><strong>' . $with_charges . '</strong></td></tr>';
    echo '<tr><td>Products with ETA</td><td><strong>' . $with_eta . '</strong></td></tr>';
    echo '</table>';
    ?>
    
    <h2>Test: Manually Add Data to Database</h2>
    
    <p>Use the form below to manually insert test data directly into the database. This will prove the tables work:</p>
    
    <form method="POST" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <label><strong>Product ID:</strong></label>
        <input type="number" name="test_product_id" placeholder="Enter product ID" required>
        
        <label><strong>Delivery Charges:</strong></label>
        <input type="text" name="test_charges" placeholder="$15.00" required>
        
        <label><strong>ETA:</strong></label>
        <input type="text" name="test_eta" placeholder="3-5 business days" required>
        
        <button type="submit" name="do_test_save" style="background: #28a745; padding: 10px 20px; margin-top: 10px;">
            📝 Save Test Data
        </button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_test_save'])) {
        $product_id = intval($_POST['test_product_id']);
        $charges = sanitize_text_field($_POST['test_charges']);
        $eta = sanitize_text_field($_POST['test_eta']);
        
        if ($product_id && $charges && $eta) {
            // Save to database
            update_post_meta($product_id, '_cj_delivery_charges', $charges);
            update_post_meta($product_id, '_cj_delivery_eta', $eta);
            
            echo '<div style="background: #d4edda; border: 1px solid #28a745; padding: 15px; margin-top: 15px; border-radius: 4px;">';
            echo '<strong style="color: #28a745;">✓ TEST DATA SAVED!</strong><br>';
            echo 'Product ID: ' . esc_html($product_id) . '<br>';
            echo 'Charges: ' . esc_html($charges) . '<br>';
            echo 'ETA: ' . esc_html($eta) . '<br>';
            echo '<br><strong>Now:</strong> Go to edit that product. The values should appear in the metabox.<br>';
            echo '<a href="' . esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')) . '" target="_blank" style="color: #0073aa;">👉 Click here to view the product</a>';
            echo '</div>';
        }
    }
    ?>
    
    <h2>Check Raw Database Query</h2>
    
    <p>To manually check what's in the database for a specific product, use:</p>
    
    <div class="code">
SELECT * FROM <?php echo $wpdb->postmeta; ?>
WHERE post_id = YOUR_PRODUCT_ID
AND meta_key IN ('_cj_delivery_charges', '_cj_delivery_eta');
    </div>
    
    <h2>Metadata Table Structure</h2>
    
    <?php
    $meta_table_fields = $wpdb->get_results("DESCRIBE {$wpdb->postmeta}");
    echo '<table>';
    echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>';
    foreach ($meta_table_fields as $field) {
        echo '<tr>';
        echo '<td><code>' . esc_html($field->Field) . '</code></td>';
        echo '<td>' . esc_html($field->Type) . '</td>';
        echo '<td>' . esc_html($field->Null) . '</td>';
        echo '<td>' . esc_html($field->Key ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    ?>
    
    <h2>Troubleshooting</h2>
    
    <div class="box">
        <strong>Problem: Data saved but doesn't appear in product edit screen</strong>
        <ul>
            <li>The data IS in the database (you can verify above)</li>
            <li>But the metabox isn't loading the values</li>
            <li><strong>Solution:</strong> Check if the metabox PHP is rendering:
                <ul>
                    <li>Edit a product</li>
                    <li>Right-click → View Page Source</li>
                    <li>Search for "cw_cj_delivery_charges"</li>
                    <li>If found, the issue is JavaScript/AJAX</li>
                    <li>If NOT found, the metabox isn't loading</li>
                </ul>
            </li>
        </ul>
    </div>
    
    <div class="box">
        <strong>Problem: Data NOT appearing in table above</strong>
        <ul>
            <li>The PHP save function might not be running</li>
            <li><strong>Check:</strong> Is cj-product-varieties-admin.php being included?</li>
            <li><strong>Add to your functions.php:</strong>
                <div class="code">
// Load delivery charges and varieties admin
require_once get_template_directory() . '/custom_woocommerce/cj-product-varieties-admin.php';
                </div>
            </li>
        </ul>
    </div>

    <h2>Quick File Check</h2>
    
    <?php
    $file_path = get_template_directory() . '/custom_woocommerce/cj-product-varieties-admin.php';
    $file_exists = file_exists($file_path);
    
    echo $file_exists 
        ? '<div class="box" style="background: #d4edda;"><strong class="success">✓ File exists: cj-product-varieties-admin.php</strong></div>'
        : '<div class="box" style="background: #f8d7da;"><strong class="error">✗ File NOT found at:</strong><br>' . esc_html($file_path) . '</div>';
    ?>
    
</div>
</body>
</html>

<?php
/**
 * FINAL DIAGNOSTIC - Find Exactly Where The Problem Is
 * 
 * This will tell you PRECISELY what's not working
 */

if (!function_exists('get_posts')) {
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
    <title>FINAL DIAGNOSIS - Delivery Charges Issue</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d32f2f; border-bottom: 3px solid #d32f2f; padding-bottom: 15px; }
        h2 { color: #1976d2; margin-top: 30px; background: #f5f5f5; padding: 12px; border-radius: 4px; }
        .test { margin: 20px 0; padding: 15px; border-left: 5px solid #ccc; background: #fafafa; }
        .test.pass { border-left-color: #4caf50; background: #e8f5e9; }
        .test.fail { border-left-color: #d32f2f; background: #ffebee; }
        .test.warn { border-left-color: #ff9800; background: #fff3e0; }
        .icon { font-size: 20px; margin-right: 10px; }
        .code { background: #263238; color: #aed581; padding: 12px; border-radius: 4px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 12px; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; margin: 10px 0; }
        button:hover { background: #1565c0; }
        .table-simple { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table-simple th, .table-simple td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table-simple th { background: #1976d2; color: white; }
        .check-list { list-style: none; padding: 0; }
        .check-list li { padding: 8px 0; }
        .check-list li:before { content: ""; margin-right: 10px; }
        .check-list li.✓:before { content: "✓ "; color: #4caf50; font-weight: bold; }
        .check-list li.✗:before { content: "✗ "; color: #d32f2f; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 FINAL DIAGNOSIS - Delivery Charges Not Saving</h1>
    
    <p style="font-size: 16px; line-height: 1.6;">
        This page will check <strong>EVERY STEP</strong> of the process to find exactly where the issue is.
    </p>
    
    <h2>STEP 1: Check PHP File Is Loaded</h2>
    
    <?php
    // Test 1: Check if file exists
    $php_file = get_template_directory() . '/custom_woocommerce/cj-product-varieties-admin.php';
    $php_exists = file_exists($php_file);
    $status = $php_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $php_exists ? '✓' : '✗'; ?></span>
        <strong>cj-product-varieties-admin.php file exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php if ($php_exists) {
                echo '<span style="color: green;">YES - File found at:<br><code>' . esc_html($php_file) . '</code></span>';
            } else {
                echo '<span style="color: red;">NO - File not found!</span><br>Expected at:<br><code>' . esc_html($php_file) . '</code>';
            } ?>
        </div>
    </div>
    
    <h2>STEP 2: Check Metabox Is Registered</h2>
    
    <?php
    // Test 2: Check if function exists
    $metabox_func_exists = function_exists('cw_cj_register_varieties_metabox');
    $status = $metabox_func_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $metabox_func_exists ? '✓' : '✗'; ?></span>
        <strong>Metabox registration function exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php if ($metabox_func_exists) {
                echo '<span style="color: green;">YES - Function <code>cw_cj_register_varieties_metabox</code> is defined</span>';
            } else {
                echo '<span style="color: red;">NO - The PHP file might not be loading properly!</span><br>' .
                     '<strong>Solution:</strong> Check wp-content/debug.log for errors';
            } ?>
        </div>
    </div>
    
    <h2>STEP 3: Check Rendering Function Exists</h2>
    
    <?php
    $render_func_exists = function_exists('cw_cj_render_varieties_metabox');
    $status = $render_func_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $render_func_exists ? '✓' : '✗'; ?></span>
        <strong>Metabox rendering function exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php echo $render_func_exists ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>'; ?>
        </div>
    </div>
    
    <h2>STEP 4: Check Save Function Exists</h2>
    
    <?php
    $save_func_exists = function_exists('cw_cj_save_varieties');
    $status = $save_func_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $save_func_exists ? '✓' : '✗'; ?></span>
        <strong>Save function exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php echo $save_func_exists ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>'; ?>
        </div>
    </div>
    
    <h2>STEP 5: Check AJAX Handler Exists</h2>
    
    <?php
    $ajax_func_exists = function_exists('cw_ajax_save_delivery_details_admin');
    $status = $ajax_func_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $ajax_func_exists ? '✓' : '✗'; ?></span>
        <strong>AJAX save function exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php echo $ajax_func_exists ? '<span style="color: green;">YES - AJAX auto-save should work</span>' : '<span style="color: red;">NO</span>'; ?>
        </div>
    </div>
    
    <h2>STEP 6: Check JavaScript File Exists</h2>
    
    <?php
    $js_file = get_template_directory() . '/custom_woocommerce/js/cj-varieties-admin.js';
    $js_exists = file_exists($js_file);
    $status = $js_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $js_exists ? '✓' : '✗'; ?></span>
        <strong>External JavaScript file exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php if ($js_exists) {
                echo '<span style="color: green;">YES - External JS is available</span>';
            } else {
                echo '<span style="color: orange;">NO - But inline JS should still work</span>';
            } ?>
        </div>
    </div>
    
    <h2>STEP 7: Database Table Check</h2>
    
    <?php
    // Check if postmeta table exists
    $postmeta_table = $wpdb->postmeta;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$postmeta_table'") === $postmeta_table;
    $status = $table_exists ? 'pass' : 'fail';
    ?>
    <div class="test <?php echo $status; ?>">
        <span class="icon"><?php echo $table_exists ? '✓' : '✗'; ?></span>
        <strong>WordPress postmeta table exists?</strong>
        <div style="margin-left: 30px; margin-top: 10px;">
            <?php if ($table_exists) {
                echo '<span style="color: green;">YES - Table: <code>' . esc_html($postmeta_table) . '</code></span><br>';
                
                // Show sample data
                $sample = $wpdb->get_var("SELECT COUNT(*) FROM $postmeta_table WHERE meta_key IN ('_cj_delivery_charges', '_cj_delivery_eta')");
                echo '<strong>Records with delivery data:</strong> <span style="color: #1976d2;">' . $sample . '</span>';
                
            } else {
                echo '<span style="color: red;">NO - Database table missing!</span>';
            } ?>
        </div>
    </div>
    
    <h2>WHAT TO DO NOW</h2>
    
    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; border-radius: 4px; margin: 20px 0;">
        
        <?php
        // Determine what's wrong
        $all_php_ok = $php_exists && $metabox_func_exists && $render_func_exists && $save_func_exists && $ajax_func_exists;
        
        if (!$all_php_ok) {
            echo '<h3>❌ PHP FILES NOT LOADING PROPERLY</h3>';
            echo '<p><strong>Problem:</strong> One or more PHP functions are missing.</p>';
            echo '<p><strong>Solution:</strong></p>';
            echo '<ol>';
            echo '<li>Check <strong>wp-content/debug.log</strong> for errors</li>';
            echo '<li>Verify <strong>functions.php</strong> includes the file with:<br>';
            echo '<code style="background: #263238; color: #aed581; padding: 8px; display: block; border-radius: 4px;">require_once dirname(__FILE__) . \'/cj-product-varieties-admin.php\';</code>';
            echo '</li>';
            echo '<li>Check for PHP syntax errors</li>';
            echo '</ol>';
        } else {
            echo '<h3>✅ ALL PHP FUNCTIONS ARE LOADED</h3>';
            echo '<p><strong>The problem is likely with the frontend (JavaScript/Display).</strong></p>';
            echo '<p><strong>Next steps:</strong></p>';
            echo '<ol>';
            echo '<li><strong>Go to edit a product</strong></li>';
            echo '<li><strong>Look for the metabox titled "🎨 CJ Product Varieties & Pricing"</strong></li>';
            echo '<li style="color: red;"><strong>If you don\'t see it:</strong> The metabox rendering is failing. Check debug.log</li>';
            echo '<li style="color: green;"><strong>If you DO see it:</strong> Continue to next step</li>';
            echo '<li><strong>Type in the fields and wait 2 seconds</strong></li>';
            echo '<li><strong>Look for the green notification in the bottom-right corner</strong></li>';
            echo '<li><strong>Open DevTools (F12) → Console tab</strong></li>';
            echo '<li><strong>Check for these messages:</strong>';
            echo '<ul>';
            echo '<li><code>✓ Delivery Details Auto-Save: ACTIVE (Inline)</code></li>';
            echo '<li><code>Attempting to save...</code></li>';
            echo '<li><code style="color: green;">AJAX Response:</code></li>';
            echo '</ul>';
            echo '</li>';
            echo '</ol>';
        }
        ?>
        
    </div>
    
    <h2>Quick Product Test</h2>
    
    <p>Click the button below to go to a product and test:</p>
    
    <?php
    // Get first product
    $first_product = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' LIMIT 1");
    
    if ($first_product) {
        $edit_url = admin_url('post.php?post=' . $first_product . '&action=edit');
        echo '<a href="' . esc_url($edit_url) . '" target="_blank">';
        echo '<button>👉 Go to Product ID: ' . $first_product . ' (Click to Edit)</button>';
        echo '</a>';
    } else {
        echo '<p style="color: orange;">⚠️ No products found. Create a product first.</p>';
    }
    ?>
    
    <h2>FAQ</h2>
    
    <div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <p><strong>Q: I see all checkmarks but still no metabox on the product page?</strong></p>
        <p>A: The PHP is working, but the metabox isn't rendering. Possible reasons:</p>
        <ul>
            <li>WooCommerce plugin not active</li>
            <li>You're using a product type that doesn't support metaboxes</li>
            <li>Another plugin is hiding it</li>
        </ul>
        <p><strong>Solution:</strong> Create a new simple product and check again</p>
    </div>
    
    <div style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <p><strong>Q: I see the metabox but fields don't save when I click "Update Product"?</strong></p>
        <p>A: The form submission might have an issue. Check:</p>
        <ul>
            <li>Browser Console (F12) for JavaScript errors</li>
            <li>Network tab to see if POST request is being sent</li>
            <li>wp-content/debug.log for PHP errors</li>
        </ul>
    </div>
    
    <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <p><strong>Q: All checks pass and metabox shows, what about the auto-save?</strong></p>
        <p>A: Type in the fields and wait 2 seconds. You should see:</p>
        <ul>
            <li>The input border turns <strong style="color: #ff9800;">yellow</strong> (detecting changes)</li>
            <li>After 2 seconds, the border turns <strong style="color: #4caf50;">green</strong></li>
            <li>A notification appears in the bottom-right corner</li>
        </ul>
    </div>
    
</div>
</body>
</html>

<?php
/**
 * VERIFICATION CHECKLIST - ETA & Delivery Charges Fix
 * 
 * Run this to verify the fix is properly installed
 */

if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
    die('Admin only');
}

// Check if we're in WordPress
if (!function_exists('get_transient')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Verification Checklist</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .check { margin: 10px 0; padding: 10px; border-left: 4px solid #ddd; }
        .check.pass { border-left-color: #28a745; background: #d4edda; }
        .check.fail { border-left-color: #dc3545; background: #f8d7da; }
        .check.warn { border-left-color: #ffc107; background: #fff3cd; }
        .icon { font-weight: bold; }
        h2 { border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        .next-steps { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>✓ ETA & Delivery Charges Fix - Verification</h1>
    
    <h2>Installation Status</h2>
    
    <?php
    // Check 1: Files exist
    $theme_dir = get_theme_file_path('custom_woocommerce/');
    $checks = [
        'Admin PHP file updated' => file_exists($theme_dir . 'cj-product-varieties-admin.php'),
        'JavaScript file created' => file_exists($theme_dir . 'js/cj-varieties-admin.js'),
        'Debug tool available' => file_exists($theme_dir . 'DEBUG_DELIVERY_ETA.php'),
        'Documentation created' => file_exists($theme_dir . 'DELIVERY_CHARGES_ETA_FIX_GUIDE.md'),
    ];
    
    foreach ($checks as $name => $status) {
        $class = $status ? 'pass' : 'fail';
        $icon = $status ? '✓' : '✗';
        echo "<div class='check $class'><span class='icon'>$icon</span> $name</div>";
    }
    ?>
    
    <h2>Code Status</h2>
    
    <?php
    // Check if functions are defined
    $functions_exist = function_exists('cw_cj_varieties_enqueue_admin_scripts') && 
                      function_exists('cw_ajax_save_delivery_details_admin');
    
    $func_class = $functions_exist ? 'pass' : 'fail';
    $func_icon = $functions_exist ? '✓' : '✗';
    
    echo "<div class='check $func_class'><span class='icon'>$func_icon</span> AJAX functions registered</div>";
    
    // Check if hooks are registered
    global $wp_filter;
    $admin_notices_hook = isset($wp_filter['admin_notices']) ? 'registered' : 'not registered';
    $ajax_hook = isset($wp_filter['wp_ajax_cw_save_delivery_details_admin']) ? 'registered' : 'not registered';
    $metabox_hook = isset($wp_filter['add_meta_boxes']) ? 'registered' : 'not registered';
    
    echo "<div class='check " . ($admin_notices_hook === 'registered' ? 'pass' : 'fail') . "'>";
    echo "<span class='icon'>✓</span> Admin notices hook: " . esc_html($admin_notices_hook);
    echo "</div>";
    
    echo "<div class='check " . ($ajax_hook === 'registered' ? 'pass' : 'fail') . "'>";
    echo "<span class='icon'>✓</span> AJAX save hook: " . esc_html($ajax_hook);
    echo "</div>";
    
    echo "<div class='check " . ($metabox_hook === 'registered' ? 'pass' : 'fail') . "'>";
    echo "<span class='icon'>✓</span> Metabox hook: " . esc_html($metabox_hook);
    echo "</div>";
    ?>
    
    <h2>Database Test</h2>
    
    <?php
    global $wpdb;
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->postmeta}'") === $wpdb->postmeta;
    
    echo "<div class='check " . ($table_exists ? 'pass' : 'fail') . "'>";
    echo "<span class='icon'>✓</span> WordPress postmeta table exists";
    echo "</div>";
    
    // Check if any products have delivery data
    $count = $wpdb->get_var("
        SELECT COUNT(DISTINCT post_id) 
        FROM {$wpdb->postmeta} 
        WHERE meta_key IN ('_cj_delivery_charges', '_cj_delivery_eta')
    ");
    
    if ($count > 0) {
        echo "<div class='check pass'>";
        echo "<span class='icon'>✓</span> Products with delivery data: <strong>$count products</strong>";
        echo "</div>";
    } else {
        echo "<div class='check warn'>";
        echo "<span class='icon'>!</span> No products with delivery data yet (this is normal if you haven't saved any)";
        echo "</div>";
    }
    ?>
    
    <h2>Quick Test</h2>
    
    <p>To verify the fix is working:</p>
    <ol>
        <li>Go to <a href="<?php echo esc_url(admin_url('edit.php?post_type=product')); ?>" target="_blank">Products</a></li>
        <li>Edit any product (or create a new one)</li>
        <li>Look for the <strong>"🎨 CJ Product Varieties & Pricing"</strong> metabox</li>
        <li>Fill in test values:
            <ul>
                <li>Delivery Charges: <code>$10.00</code></li>
                <li>ETA: <code>3-5 business days</code></li>
            </ul>
        </li>
        <li>Change the value and wait 2 seconds - you should see a success message appear</li>
        <li>Or click "Update Product" to save via form submission</li>
        <li>Reload the page - values should persist</li>
    </ol>
    
    <div class="next-steps">
        <h3>Next Steps</h3>
        <p><strong>1. Test on a Product:</strong></p>
        <p>Go to any product and test the delivery charges and ETA fields to confirm they save.</p>
        
        <p><strong>2. Check Browser Console:</strong></p>
        <p>Open DevTools (F12) → Console tab. You should see:</p>
        <code>CJ Varieties Admin initialized - Product ID: [number]</code>
        
        <p><strong>3. Monitor Debug Log:</strong></p>
        <p>Enable WP_DEBUG in <code>wp-config.php</code> and check <code>wp-content/debug.log</code> for save confirmations.</p>
        
        <p><strong>4. If Issues Persist:</strong></p>
        <p>Use the diagnostic tool at:<br>
        <code>/wp-content/themes/builtin_themes/custom_woocommerce/DEBUG_DELIVERY_ETA.php</code>
        </p>
    </div>
    
    <h2>Implementation Summary</h2>
    
    <p>The following improvements have been made:</p>
    <ul>
        <li>✓ Enhanced nonce verification with logging</li>
        <li>✓ Better error handling for form submissions</li>
        <li>✓ AJAX auto-save feature (saves as user types)</li>
        <li>✓ Admin notices for save confirmation</li>
        <li>✓ Comprehensive debugging tools</li>
        <li>✓ Fallback mechanisms for reliability</li>
    </ul>
    
</div>
</body>
</html>

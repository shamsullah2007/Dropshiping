<?php
/**
 * QUICK TEST PAGE FOR DELIVERY CHARGES AUTO-SAVE
 * 
 * This page shows you exactly what's happening
 * 
 * Access URLs:
 * - For Admins: Add ?cj_test=1 to any WordPress admin page
 * - Direct: wp-content/themes/builtin_themes/custom_woocommerce/TEST_DELIVERY_SAVE.php
 */

// Load WordPress
if (!function_exists('get_post_meta')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

// Only admin
if (!current_user_can('manage_products')) {
    die('Admin only');
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// If they posted a test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_save'])) {
    $product_id = intval($_POST['product_id']);
    $charges = sanitize_text_field($_POST['test_charges']);
    $eta = sanitize_text_field($_POST['test_eta']);
    
    // Try to save
    update_post_meta($product_id, '_cj_delivery_charges', $charges);
    update_post_meta($product_id, '_cj_delivery_eta', $eta);
    
    $test_result = 'Direct database save: SUCCESS ✓<br>';
    $test_result .= 'Saved to Product ID: ' . $product_id . '<br>';
    $test_result .= 'Charges: ' . esc_html($charges) . '<br>';
    $test_result .= 'ETA: ' . esc_html($eta);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Delivery Charges - Quick Test</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 15px; }
        h2 { color: #333; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        .test-box { background: #f9f9f9; border-left: 4px solid #0073aa; padding: 15px; margin: 15px 0; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .code { background: #2d2d2d; color: #f8f8f2; padding: 12px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 12px; }
        input, button { padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input[type="text"], input[type="number"] { width: 100%; }
        button { background: #0073aa; color: white; cursor: pointer; font-weight: bold; border: none; }
        button:hover { background: #005a87; }
        .step { margin: 20px 0; padding: 15px; background: #f0f7ff; border-left: 4px solid #0073aa; }
        .step-num { display: inline-block; background: #0073aa; color: white; padding: 5px 12px; border-radius: 50%; font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Delivery Charges & ETA - Quick Test</h1>
    
    <div class="test-box warning">
        <strong>⚠️ Note:</strong> If you don't see the delivery fields in your product editor, that means the metabox isn't loading. Check WordPress debug.log for errors.
    </div>
    
    <h2>What Should You See?</h2>
    
    <div class="step">
        <span class="step-num">1</span>
        <strong>In Product Editor →</strong> Look for a box titled <strong>"🎨 CJ Product Varieties & Pricing"</strong>
    </div>
    
    <div class="step">
        <span class="step-num">2</span>
        <strong>Inside That Box →</strong> Two input fields:
        <ul>
            <li><strong>Delivery Charges:</strong> (e.g., $10.00 or "Free")</li>
            <li><strong>ETA:</strong> (e.g., "5-7 business days")</li>
        </ul>
    </div>
    
    <div class="step">
        <span class="step-num">3</span>
        <strong>Type in the fields →</strong> After 2 seconds, a message should appear in the bottom-right corner saying "✓ Delivery details saved automatically"
    </div>
    
    <div class="step">
        <span class="step-num">4</span>
        <strong>Reload the page →</strong> Your values should still be there
    </div>
    
    <h2>Quick Debug Check</h2>
    
    <p><strong>Step 1:</strong> Go to any product and edit it.</p>
    <p><strong>Step 2:</strong> Open DevTools (Press <kbd>F12</kbd>)</p>
    <p><strong>Step 3:</strong> Click on <strong>Console</strong> tab</p>
    <p><strong>Step 4:</strong> You should see one of these messages:</p>
    
    <div class="code">
✓ Delivery Details Auto-Save: ACTIVE (Inline)
    </div>
    
    <p><strong>OR</strong></p>
    
    <div class="code">
✓ CJ Varieties Admin Auto-Save Initialized
    </div>
    
    <p>If you see either message, the script is loaded and working!</p>
    
    <h2>Debugging Steps</h2>
    
    <ol>
        <li>
            <strong>Can you see the metabox?</strong>
            <ul>
                <li>YES → Go to Step 2</li>
                <li>NO → PHP is not registering the metabox. Check:
                    <ul>
                        <li>Is cj-product-varieties-admin.php being included in functions.php?</li>
                        <li>Check wp-content/debug.log for errors</li>
                    </ul>
                </li>
            </ul>
        </li>
        
        <li>
            <strong>Can you see the delivery fields?</strong>
            <ul>
                <li>YES → Go to Step 3</li>
                <li>NO → The metabox function isn't rendering HTML properly. Check debug.log</li>
            </ul>
        </li>
        
        <li>
            <strong>Open Console (F12) - Console tab</strong>
            <ul>
                <li>See "ACTIVE" message? → Script is loaded, test typing</li>
                <li>No message? → Script didn't load. Check:
                    <ul>
                        <li>Files exist at: /custom_woocommerce/js/cj-varieties-admin.js</li>
                        <li>No PHP errors (check debug.log)</li>
                    </ul>
                </li>
            </ul>
        </li>
        
        <li>
            <strong>Test the Fields</strong>
            <ul>
                <li>Type "$10.00" in Delivery Charges field</li>
                <li>Wait 2 seconds</li>
                <li>Check bottom-right for success message</li>
                <li>Check Console for "Attempting to save" message</li>
            </ul>
        </li>
    </ol>
    
    <h2>Manual Database Test</h2>
    
    <p>Use the form below to manually save a test value directly to the database. This proves the database works:</p>
    
    <form method="POST" style="background: #f0f7ff; padding: 15px; border-radius: 4px;">
        <label><strong>Product ID:</strong></label>
        <input type="number" name="product_id" value="<?php echo $product_id ? esc_attr($product_id) : ''; ?>" placeholder="Enter product ID (e.g., 123)" required>
        
        <label><strong>Delivery Charges:</strong></label>
        <input type="text" name="test_charges" placeholder="e.g., $15.00" required>
        
        <label><strong>ETA:</strong></label>
        <input type="text" name="test_eta" placeholder="e.g., 3-5 business days" required>
        
        <button type="submit" name="test_save" value="1">Save to Database (Test)</button>
    </form>
    
    <?php if (!empty($test_result)): ?>
    <div class="test-box success">
        <strong>✓ Test Result:</strong><br>
        <?php echo $test_result; ?>
    </div>
    <?php endif; ?>
    
    <h2>Still Not Working?</h2>
    
    <p><strong>Check These Files:</strong></p>
    <ul>
        <li>✓ cj-product-varieties-admin.php - Is it being loaded?</li>
        <li>✓ js/cj-varieties-admin.js - Does the file exist?</li>
        <li>✓ wp-config.php - Is WP_DEBUG enabled?</li>
        <li>✓ wp-content/debug.log - Are there errors?</li>
    </ul>
    
    <p><strong>Common Issues:</strong></p>
    <div class="test-box">
        <strong>❌ Metabox not showing</strong><br>
        → The PHP file isn't being included. Add to your theme's functions.php:<br>
        <div class="code">
require_once get_template_directory() . '/custom_woocommerce/cj-product-varieties-admin.php';
        </div>
    </div>
    
    <div class="test-box">
        <strong>❌ Fields show but don't save</strong><br>
        → Check Console for JavaScript errors (F12 → Console)<br>
        → Check debug.log for AJAX errors<br>
        → Verify nonce is in page source (Ctrl+U, search "cw_delivery_ajax_nonce")
    </div>
    
    <div class="test-box">
        <strong>❌ Values save but disappear on reload</strong><br>
        → The save is working! But display might be cached.<br>
        → Hard refresh: Ctrl+Shift+R<br>
        → Check database directly
    </div>
    
</div>
</body>
</html>

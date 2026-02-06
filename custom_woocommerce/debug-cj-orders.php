<?php
/**
 * CJ Dropshipping Order Debugging Tool
 * 
 * Run from browser: /wp-content/themes/custom_woocommerce/debug-cj-orders.php
 * Or from command line: php debug-cj-orders.php
 */

// Load WordPress
$wp_load = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('ERROR: Could not find wp-load.php. Make sure this file is in the theme directory.');
}

require_once $wp_load;

// Security check for web access
if (!defined('DOING_AJAX') && !defined('WP_CLI')) {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this debug tool.');
    }
}

echo "=== CJ DROPSHIPPING DEBUG TOOL ===\n\n";

// 1. Check CJ Credentials
echo "1. CJ API CREDENTIALS\n";
echo str_repeat("-", 50) . "\n";
$api_key = get_option('cw_cj_api_key', '');
$platform_token = get_option('cw_cj_platform_token', '');
$access_token = get_option('cw_cj_access_token', '');
$token_expiry = get_option('cw_cj_token_expiry', 0);

if (empty($api_key)) {
    echo "❌ API Key: NOT CONFIGURED\n";
} else {
    echo "✓ API Key: " . substr($api_key, 0, 10) . "..." . substr($api_key, -10) . "\n";
}

if (empty($access_token)) {
    echo "❌ Access Token: NOT SET\n";
} else {
    echo "✓ Access Token: " . substr($access_token, 0, 10) . "...\n";
    $is_expired = time() >= $token_expiry;
    echo "  Token Expiry: " . date('Y-m-d H:i:s', $token_expiry) . ($is_expired ? " ❌ EXPIRED" : " ✓ Valid") . "\n";
}

echo "\n";

// 2. Check Recent Orders
echo "2. RECENT WOOCOMMERCE ORDERS\n";
echo str_repeat("-", 50) . "\n";

global $wpdb;
$orders = wc_get_orders([
    'limit' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]);

if (empty($orders)) {
    echo "No orders found.\n";
} else {
    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_number = $order->get_order_number();
        $status = $order->get_status();
        $total = $order->get_total();
        $created = $order->get_date_created();
        
        $cj_order_id = $order->get_meta('_cj_order_id');
        $cj_status = empty($cj_order_id) ? '❌ NOT SYNCED' : '✓ SYNCED (ID: ' . $cj_order_id . ')';
        
        echo "\nOrder #{$order_number} (ID: {$order_id})\n";
        echo "  Created: " . $created->format('Y-m-d H:i:s') . "\n";
        echo "  Status: {$status}\n";
        echo "  Total: \${$total}\n";
        echo "  CJ Status: {$cj_status}\n";
        
        // Check items
        $has_cj_products = false;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $cj_pid = $product->get_meta('_cj_product_id');
                $cj_vid = $product->get_meta('_cj_variant_id');
                
                echo "  Product: " . $product->get_name() . "\n";
                if (empty($cj_pid) || empty($cj_vid)) {
                    echo "    ❌ Missing CJ IDs (PID: {$cj_pid}, VID: {$cj_vid})\n";
                } else {
                    echo "    ✓ CJ Product ID: {$cj_pid}, Variant ID: {$cj_vid}\n";
                    $has_cj_products = true;
                }
            }
        }
        
        if (!$has_cj_products && $status === 'processing') {
            echo "  ⚠️  WARNING: Order is in 'processing' but has no CJ products!\n";
        }
    }
}

echo "\n";

// 3. Check CJ API Connection
echo "3. CJ API CONNECTION TEST\n";
echo str_repeat("-", 50) . "\n";

if (empty($api_key)) {
    echo "❌ Cannot test: No API key configured\n";
} else {
    require_once dirname(__FILE__) . '/class-cj-dropshipping.php';
    
    if (function_exists('cw_cj_dropshipping')) {
        $cj = cw_cj_dropshipping();
        $balance = $cj->get_balance();
        
        if ($balance === false) {
            echo "❌ Failed to connect to CJ API\n";
            echo "  Check: API key validity, network access, API server status\n";
        } else {
            echo "✓ Connected to CJ API\n";
            echo "  Account Balance: \${$balance}\n";
        }
    }
}

echo "\n";

// 4. Check WordPress Error Log
echo "4. WORDPRESS ERROR LOG\n";
echo str_repeat("-", 50) . "\n";

$wp_content = WP_CONTENT_DIR;
$debug_log = $wp_content . '/debug.log';

if (file_exists($debug_log)) {
    echo "Debug log found: {$debug_log}\n";
    $lines = file($debug_log);
    $cj_lines = [];
    
    foreach ($lines as $line) {
        if (stripos($line, 'CJ') !== false || stripos($line, 'dropshipping') !== false) {
            $cj_lines[] = $line;
        }
    }
    
    if (empty($cj_lines)) {
        echo "No CJ-related errors found in debug.log\n";
    } else {
        echo "Found " . count($cj_lines) . " CJ-related log entries (last 10):\n\n";
        $recent = array_slice($cj_lines, -10);
        foreach ($recent as $line) {
            echo $line;
        }
    }
} else {
    echo "❌ Debug log not found at {$debug_log}\n";
    echo "   To enable debug logging, add to wp-config.php:\n";
    echo "   define('WP_DEBUG', true);\n";
    echo "   define('WP_DEBUG_LOG', true);\n";
}

echo "\n";

// 5. Test Manual Order Sync
echo "5. MANUAL ORDER SYNC TEST\n";
echo str_repeat("-", 50) . "\n";

if (isset($_GET['sync_order']) && !empty($_GET['sync_order'])) {
    $order_id = intval($_GET['sync_order']);
    echo "Attempting to sync order {$order_id}...\n\n";
    
    $order = wc_get_order($order_id);
    if (!$order) {
        echo "❌ Order not found\n";
    } else {
        // Manually trigger the hook
        do_action('woocommerce_order_status_processing', $order_id);
        
        $cj_order_id = $order->get_meta('_cj_order_id');
        if ($cj_order_id) {
            echo "✓ Order successfully synced!\n";
            echo "  CJ Order ID: {$cj_order_id}\n";
            echo "  Check: https://developers.cjdropshipping.com/ for order details\n";
        } else {
            echo "❌ Order sync failed. Check WordPress error log for details.\n";
        }
    }
} else {
    echo "Use URL parameter to sync a specific order:\n";
    echo "?sync_order=123\n\n";
    
    echo "Example: {$_SERVER['REQUEST_URI']}?sync_order=1\n";
}

echo "\n";
echo "=== END DEBUG ===\n";
?>

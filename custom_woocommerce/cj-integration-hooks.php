<?php
/**
 * CJ Dropshipping Integration Hooks & Admin
 * 
 * Handles WordPress integration:
 * - Admin settings page
 * - WooCommerce order processing
 * - Webhook receiver
 * - Testing utilities
 * 
 * @package CustomWoocommerce
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ==================== LOAD CLASS & ADMIN ====================

require_once dirname(__FILE__) . '/class-cj-dropshipping.php';
require_once dirname(__FILE__) . '/cj-admin-page.php';
require_once dirname(__FILE__) . '/cj-variable-products.php';

// ==================== ADMIN SETTINGS ====================

/**
 * Add CJ settings to WordPress admin
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'CJ Dropshipping',
        'CJ Dropshipping',
        'manage_options',
        'cj-dropshipping-settings',
        'cw_cj_admin_page'
    );

    add_submenu_page(
        'woocommerce',
        'CJ Dropshipping V2',
        'CJ Dropshipping V2',
        'manage_options',
        'cj-dropshipping-v2',
        'cw_cj_admin_page_v2'
    );
});


// ==================== REST API WEBHOOK ====================

/**
 * Register REST API webhook endpoint for CJ notifications
 */
add_action('rest_api_init', function() {
    register_rest_route('cj-dropshipping/v1', '/webhook', [
        'methods' => 'POST',
        'callback' => 'cw_cj_webhook_handler',
        'permission_callback' => '__return_true', // CJ doesn't use WP auth
    ]);
});

/**
 * Handle CJ webhook notifications
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function cw_cj_webhook_handler($request) {
    $body = json_decode($request->get_body(), true);
    
    if (empty($body) || !isset($body['eventType'])) {
        return new WP_REST_Response(['success' => false], 400);
    }
    
    do_action('cj_webhook_received', $body);
    
    switch ($body['eventType'] ?? '') {
        case 'LOGISTICS':
            cw_cj_handle_logistics_webhook($body);
            break;
        case 'ORDER':
            cw_cj_handle_order_webhook($body);
            break;
    }
    
    return new WP_REST_Response(['success' => true], 200);
}

/**
 * Handle logistics/tracking webhook
 */
function cw_cj_handle_logistics_webhook($webhook_data) {
    $cj_order_id = $webhook_data['data']['cjOrderId'] ?? null;
    $tracking_number = $webhook_data['data']['trackingNumber'] ?? null;
    $status = $webhook_data['data']['status'] ?? null;
    
    if (!$cj_order_id) {
        return;
    }
    
    // Find WooCommerce order by CJ order ID
    $args = [
        'meta_query' => [
            [
                'key' => '_cj_order_id',
                'value' => $cj_order_id,
            ]
        ],
        'limit' => 1,
    ];
    
    $orders = wc_get_orders($args);
    
    if (empty($orders)) {
        return;
    }
    
    $order = $orders[0];
    
    // Update tracking number
    if ($tracking_number) {
        $order->update_meta_data('_shipping_tracking_number', $tracking_number);
    }
    
    // Update order status based on CJ status
    $status_map = [
        'SHIPPED' => 'wc-shipped',
        'DELIVERED' => 'wc-completed',
        'RETURNED' => 'wc-cancelled',
    ];
    
    if (isset($status_map[$status])) {
        $order->set_status($status_map[$status]);
    }
    
    // Add order note
    $order->add_order_note(sprintf(
        'CJ Tracking Update: Status=%s, Tracking=%s (from webhook)',
        $status,
        $tracking_number
    ));
    
    $order->save();
}

/**
 * Handle order status webhook
 */
function cw_cj_handle_order_webhook($webhook_data) {
    // Similar to logistics handling but for order status changes
    $cj_order_id = $webhook_data['data']['cjOrderId'] ?? null;
    $order_status = $webhook_data['data']['status'] ?? null;
    
    if (!$cj_order_id) {
        return;
    }
    
    $args = [
        'meta_query' => [
            [
                'key' => '_cj_order_id',
                'value' => $cj_order_id,
            ]
        ],
        'limit' => 1,
    ];
    
    $orders = wc_get_orders($args);
    
    if (!empty($orders)) {
        $order = $orders[0];
        $order->add_order_note('CJ Order Status: ' . $order_status . ' (from webhook)');
        $order->save();
    }
}

// ==================== WOOCOMMERCE ORDER PROCESSING ====================

/**
 * Helper function to process CJ order creation
 * Reusable by multiple hooks
 */
function cw_cj_process_order_sync($order_id) {
    error_log('=== CJ ORDER PROCESSING START === Order ID: ' . $order_id);
    
    $order = wc_get_order($order_id);
    
    if (!$order || CJ_Dropshipping::get_cj_order_id($order_id)) {
        error_log('CJ Order: Skipping - Invalid order or already has CJ order ID');
        return; // Already has CJ order or invalid order
    }
    
    if (!CJ_Dropshipping::has_credentials()) {
        error_log('CJ Order: Skipping - No CJ credentials configured');
        return; // CJ not configured
    }
    
    // Build CJ order data from WooCommerce order
    $cj = cw_cj_dropshipping();
    
    $order_data = [
        'order_number' => $order->get_order_number(),
        'country_code' => $order->get_billing_country(),
        'country' => WC()->countries->countries[$order->get_billing_country()] ?? '',
        'state' => $order->get_billing_state(),
        'city' => $order->get_billing_city(),
        'phone' => $order->get_billing_phone(),
        'first_name' => $order->get_billing_first_name(),
        'last_name' => $order->get_billing_last_name(),
        'address_1' => $order->get_billing_address_1(),
        'address_2' => $order->get_billing_address_2(),
        'postcode' => $order->get_billing_postcode(),
        'email' => $order->get_billing_email(),
    ];
    
    // Build products array
    $products = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        
        if (!$product) {
            error_log('CJ Order: Item has no product object');
            continue;
        }
        
        $cj_product_id = $product->get_meta('_cj_product_id');
        $cj_variant_id = $product->get_meta('_cj_variant_id');
        
        error_log(sprintf(
            'CJ Order: Item "%s" - Product ID: %s, Variant ID: %s',
            $product->get_name(),
            $cj_product_id ? $cj_product_id : 'NOT SET',
            $cj_variant_id ? $cj_variant_id : 'NOT SET'
        ));
        
        if ($cj_variant_id || $cj_product_id) {
            $products[] = [
                'vid' => $cj_variant_id,
                'quantity' => $item->get_quantity(),
                'storeLineItemId' => 'WOO-' . $order_id . '-' . $item->get_id(),
            ];
        }
    }
    
    error_log('CJ Order: Found ' . count($products) . ' CJ products in order');
    
    if (empty($products)) {
        $order->add_order_note('No CJ products found in order - skipping CJ order creation');
        error_log('CJ Order: SKIPPING - No CJ products found');
        return;
    }
    
    // Create CJ order
    error_log('CJ Order: Creating CJ order with ' . count($products) . ' products');
    $result = $cj->create_order($order_data, $products, 2); // payType=2 for balance payment
    
    if (is_wp_error($result) || !isset($result['data']['orderId'])) {
        $error_msg = is_wp_error($result) ? $result->get_error_message() : ($result['message'] ?? 'Unknown error');
        $order->add_order_note('CJ order creation failed: ' . $error_msg);
        error_log('CJ Order: FAILED to create order - ' . $error_msg);
        return;
    }
    
    $cj_order_id = $result['data']['orderId'];
    error_log('CJ Order: Created successfully - CJ Order ID: ' . $cj_order_id);
    
    // Map CJ order to WooCommerce order
    CJ_Dropshipping::map_woo_to_cj_order($order_id, $cj_order_id);
    error_log('CJ Order: Mapped WC Order ' . $order_id . ' to CJ Order ' . $cj_order_id);
    
    // Add cart and confirm
    error_log('CJ Order: Adding to cart...');
    $cart_result = $cj->add_to_cart($cj_order_id);
    
    if (isset($cart_result['data']['successCount']) && $cart_result['data']['successCount'] > 0) {
        error_log('CJ Order: Added to cart successfully. Confirming...');
        $cj->confirm_cart($cj_order_id);
        error_log('CJ Order: Cart confirmed');
    } else {
        error_log('CJ Order: Failed to add to cart - ' . json_encode($cart_result));
    }
    
    // Generate parent order for payment
    error_log('CJ Order: Generating parent order...');
    $shipment_result = $cj->generate_parent_order($cj_order_id);
    
    if (isset($shipment_result['payId']) && $shipment_result['canDeduct']) {
        error_log('CJ Order: Attempting balance payment...');
        // Auto-pay from balance
        $pay_result = $cj->pay_balance_v2($cj_order_id, $shipment_result['payId']);
        
        if ($pay_result) {
            $order->add_order_note(sprintf(
                'CJ Order Created & Paid: Order ID=%s, Amount=$%s',
                $cj_order_id,
                $shipment_result['actualPayment']
            ));
            error_log('CJ Order: Payment successful! Amount: $' . $shipment_result['actualPayment']);
        } else {
            error_log('CJ Order: Payment FAILED');
        }
    } else {
        error_log('CJ Order: Unable to auto-pay - canDeduct: ' . ($shipment_result['canDeduct'] ?? 'N/A'));
    }
    
    error_log('=== CJ ORDER PROCESSING END ===');
}

/**
 * Trigger CJ order processing when WooCommerce order reaches processing status
 */
add_action('woocommerce_order_status_processing', 'cw_cj_process_order_sync', 10, 1);

/**
 * Also trigger on payment_complete for payment gateway compatibility
 * This catches orders that go directly to processing via payment gateway
 */
add_action('woocommerce_payment_complete', 'cw_cj_process_order_sync', 10, 1);

/**
 * Manual CJ order sync action for admin
 * Accessible via: do_action('cw_cj_manual_sync_order', $order_id);
 */
add_action('cw_cj_manual_sync_order', 'cw_cj_process_order_sync', 10, 1);

// ==================== ADMIN TOOLS ====================

/**
 * Add manual sync button to WooCommerce order page
 */
add_action('woocommerce_order_item_add_action_buttons', function() {
    $order = wc_get_order(get_the_ID());
    
    if (!$order) {
        return;
    }
    
    // Only show if order is in processing and has no CJ order ID
    if ($order->get_status() !== 'processing') {
        return;
    }
    
    if (CJ_Dropshipping::get_cj_order_id($order->get_id())) {
        // Already synced
        echo '<div class="notice notice-success"><p>✓ Order already synced to CJ (ID: ' . CJ_Dropshipping::get_cj_order_id($order->get_id()) . ')</p></div>';
        return;
    }
    
    // Show button to manually sync
    echo '<button type="button" class="button button-primary" id="cw-cj-sync-btn" name="cw_cj_sync">Sync to CJ Dropshipping</button>';
    echo '<span id="cw-cj-sync-spinner" class="spinner" style="display: none; float: none; margin: 5px 10px;"></span>';
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#cw-cj-sync-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $spinner = $('#cw-cj-sync-spinner');
            
            $btn.prop('disabled', true);
            $spinner.show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cw_cj_sync_order',
                    order_id: <?php echo get_the_ID(); ?>,
                    nonce: '<?php echo wp_create_nonce("cw_cj_sync_nonce"); ?>'
                },
                success: function(response) {
                    $spinner.hide();
                    if (response.success) {
                        alert('✓ Order synced successfully to CJ!\nCJ Order ID: ' + response.data.cj_order_id);
                        location.reload();
                    } else {
                        alert('✗ Sync failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    $spinner.hide();
                    $btn.prop('disabled', false);
                    alert('✗ AJAX request failed');
                }
            });
        });
    });
    </script>
    <?php
}, 10);

/**
 * AJAX handler for manual order sync
 */
add_action('wp_ajax_cw_cj_sync_order', function() {
    check_ajax_referer('cw_cj_sync_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce_orders')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        wp_send_json_error(['message' => 'Invalid order ID']);
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Order not found']);
    }
    
    // Check if already synced
    $existing_cj_id = CJ_Dropshipping::get_cj_order_id($order_id);
    if ($existing_cj_id) {
        wp_send_json_error(['message' => 'Order already synced to CJ (ID: ' . $existing_cj_id . ')']);
    }
    
    // Check CJ credentials
    if (!CJ_Dropshipping::has_credentials()) {
        wp_send_json_error(['message' => 'CJ credentials not configured']);
    }
    
    // Perform the sync
    cw_cj_process_order_sync($order_id);
    
    // Check result
    $cj_order_id = CJ_Dropshipping::get_cj_order_id($order_id);
    
    if ($cj_order_id) {
        wp_send_json_success([
            'message' => 'Order synced successfully',
            'cj_order_id' => $cj_order_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Sync completed but CJ order not found. Check debug log for details.']);
    }
});

// ==================== PRODUCT IMPORT UTILITIES ====================

/**
 * Save CJ product IDs to WooCommerce product
 * Called when product is added directly from CJ catalog
 * 
 * @param int $woo_product_id WooCommerce product ID
 * @param string $cj_product_id CJ product ID
 * @param string $cj_variant_id CJ variant ID
 */
function cw_cj_save_product_mapping($woo_product_id, $cj_product_id, $cj_variant_id) {
    $product = wc_get_product($woo_product_id);
    
    if ($product) {
        $product->update_meta_data('_cj_product_id', $cj_product_id);
        $product->update_meta_data('_cj_variant_id', $cj_variant_id);
        $product->save();
    }
}

/**
 * Get live inventory from CJ
 * 
 * @param string $cj_variant_id CJ variant ID
 * @return int Inventory count
 */
function cw_cj_get_live_inventory($cj_variant_id) {
    if (!CJ_Dropshipping::has_credentials()) {
        return 0;
    }
    
    $cj = cw_cj_dropshipping();
    $inventory = $cj->get_inventory_by_vid($cj_variant_id);
    
    if (empty($inventory)) {
        return 0;
    }
    
    // Sum all warehouse inventory
    $total = 0;
    foreach ($inventory as $warehouse) {
        $total += $warehouse['totalInventoryNum'] ?? 0;
    }
    
    return $total;
}

/**
 * Refresh variants for an existing WooCommerce product from CJ
 * Updates variant images and prices without deleting the product
 * 
 * @param int $product_id WooCommerce product ID
 * @return array Status array with 'success' and 'message'
 */
function cw_cj_refresh_product_variants($product_id) {
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }
    
    $cj_product_id = $product->get_meta('_cj_product_id');
    if (!$cj_product_id) {
        return ['success' => false, 'message' => 'Product is not linked to CJ'];
    }
    
    if (!CJ_Dropshipping::has_credentials()) {
        return ['success' => false, 'message' => 'CJ credentials not configured'];
    }
    
    try {
        $cj = cw_cj_dropshipping();
        
        // Get product details from CJ
        $details = $cj->get_product_details($cj_product_id, true);
        if (empty($details)) {
            return ['success' => false, 'message' => 'Failed to fetch product from CJ'];
        }
        
        // Get variants from CJ
        $variants = $cj->get_variants($cj_product_id);
        if (empty($variants)) {
            return ['success' => false, 'message' => 'No variants found on CJ'];
        }
        
        // Get existing WC variations
        $wc_variations = $product->get_children();
        if (empty($wc_variations)) {
            return ['success' => false, 'message' => 'Product has no variations'];
        }
        
        // Update each WC variation with CJ data
        $updated_count = 0;
        foreach ($wc_variations as $var_id) {
            $wc_variation = wc_get_product($var_id);
            if (!$wc_variation) {
                continue;
            }
            
            // Find matching CJ variant by SKU or name
            $cj_variant = null;
            $wc_sku = $wc_variation->get_sku();
            
            foreach ($variants as $v) {
                if ($wc_sku && ($v['variantSku'] ?? '' ) === $wc_sku) {
                    $cj_variant = $v;
                    break;
                }
            }
            
            if (!$cj_variant && !empty($variants[0])) {
                $cj_variant = $variants[0];
            }
            
            if (!$cj_variant) {
                continue;
            }
            
            // Update variant price
            $base_price = $cj_variant['variantSellPrice'] ?? $cj_variant['sellPrice'] ?? 0;
            $markup = 0.5; // 50% markup
            if ($base_price > 0) {
                $price = round($base_price * (1 + $markup), 2);
                $wc_variation->set_regular_price((string) $price);
            }
            
            // Update variant image
            $variant_image_url = '';
            if (!empty($cj_variant['variantImage'])) {
                $variant_image_url = $cj_variant['variantImage'];
            }
            
            if ($variant_image_url) {
                $wc_variation->update_meta_data('_cj_variant_image_url', $variant_image_url);
                
                // Try to download and attach image
                if (function_exists('cw_cj_sideload_image')) {
                    $img_id = cw_cj_sideload_image($variant_image_url, $product_id, $product->get_name(), true);
                    if (!is_wp_error($img_id)) {
                        $wc_variation->set_image_id($img_id);
                    }
                }
            }
            
            // Update CJ variant ID
            $cj_variant_id = $cj_variant['vid'] ?? '';
            if ($cj_variant_id) {
                $wc_variation->update_meta_data('_cj_variant_id', $cj_variant_id);
            }
            
            $wc_variation->save();
            $updated_count++;
        }
        
        if ($updated_count === 0) {
            return ['success' => false, 'message' => 'No variants were updated'];
        }
        
        return [
            'success' => true,
            'message' => sprintf('Successfully refreshed %d variant(s)', $updated_count),
            'updated' => $updated_count,
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
        ];
    }
}

/**
 * AJAX handler for refreshing product variants
 */
add_action('wp_ajax_cw_cj_refresh_variants', function() {
    check_ajax_referer('cw_cj_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Product ID required']);
    }
    
    $result = cw_cj_refresh_product_variants($product_id);
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
});

/**
 * Register product metabox for refresh variants
 */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'cw_cj_refresh_variants_box',
        'CJ Dropshipping',
        'cw_cj_refresh_variants_metabox',
        'product',
        'normal',
        'high'
    );
});

/**
 * Render refresh variants metabox
 */
function cw_cj_refresh_variants_metabox($post) {
    $product = wc_get_product($post->ID);
    if (!$product) {
        echo '<p>Product not found</p>';
        return;
    }
    
    $cj_product_id = $product->get_meta('_cj_product_id');
    if (!$cj_product_id) {
        echo '<p style="color: #666; padding: 10px 0;">This product is not linked to CJ Dropshipping.</p>';
        return;
    }
    
    wp_nonce_field('cw_cj_admin_nonce', 'cw_cj_admin_nonce');
    ?>
    <div style="padding: 10px 0; border-top: 1px solid #eee; padding-top: 15px;">
        <p style="margin: 0 0 15px 0;">
            <strong>CJ Product ID:</strong> <?php echo esc_html($cj_product_id); ?>
        </p>
        
        <button type="button" id="cw-cj-refresh-btn" class="button button-primary" style="padding: 6px 12px;">
            <span id="cw-cj-refresh-text">🔄 Refresh Variants from CJ</span>
        </button>
        
        <div id="cw-cj-refresh-status" style="margin-top: 12px; display: none;">
            <p id="cw-cj-refresh-message" style="margin: 0; padding: 10px; border-radius: 4px; background-color: #f0f0f0;"></p>
        </div>
    </div>
    
    <script>
    jQuery(function($) {
        $('#cw-cj-refresh-btn').on('click', function() {
            var btn = $(this);
            var statusDiv = $('#cw-cj-refresh-status');
            var statusMsg = $('#cw-cj-refresh-message');
            
            // Disable button and show loading
            btn.prop('disabled', true);
            $('#cw-cj-refresh-text').text('⏳ Refreshing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cw_cj_refresh_variants',
                    product_id: <?php echo intval($post->ID); ?>,
                    nonce: $('input[name="cw_cj_admin_nonce"]').val()
                },
                success: function(response) {
                    statusDiv.show();
                    if (response.success) {
                        statusMsg.css('background-color', '#d4edda').css('color', '#155724').html(
                            '✓ ' + response.data.message
                        );
                        $('#cw-cj-refresh-text').text('✓ Variants Refreshed');
                    } else {
                        statusMsg.css('background-color', '#f8d7da').css('color', '#721c24').html(
                            '✗ ' + response.data.message
                        );
                        $('#cw-cj-refresh-text').text('🔄 Refresh Variants from CJ');
                    }
                },
                error: function() {
                    statusDiv.show();
                    statusMsg.css('background-color', '#f8d7da').css('color', '#721c24').html(
                        '✗ Request failed. Please check your connection.'
                    );
                    $('#cw-cj-refresh-text').text('🔄 Refresh Variants from CJ');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

// ==================== TESTING & DEBUG ====================

/**
 * Test CJ connection
 */
function cw_cj_test_connection() {
    if (!CJ_Dropshipping::has_credentials()) {
        return ['success' => false, 'message' => 'No CJ credentials configured'];
    }
    
    $cj = cw_cj_dropshipping();
    $balance = $cj->get_balance();
    
    if ($balance === false) {
        return ['success' => false, 'message' => 'Failed to connect to CJ API'];
    }
    
    return [
        'success' => true,
        'message' => 'Connected to CJ API',
        'balance' => $balance,
    ];
}

// Add debug endpoint (accessible via admin)
add_action('wp_ajax_cw_cj_test', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    wp_send_json(cw_cj_test_connection());
});

// Expose test function
add_action('admin_init', function() {
    if (isset($_GET['cw_cj_test']) && current_user_can('manage_options')) {
        wp_send_json(cw_cj_test_connection());
    }
});

// ==================== PRODUCT IMPORT ====================

/**
 * Download and attach an image from a URL without checksum validation.
 * Returns attachment ID or WP_Error.
 */
function cw_cj_sideload_image($url, $post_id, $desc = '', $skip_metadata = false) {
    if (empty($url)) {
        return new WP_Error('cj_image_empty', 'Image URL is empty.');
    }

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'redirection' => 3,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('cj_image_http', 'Image download failed with HTTP ' . $code);
    }

    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        return new WP_Error('cj_image_empty_body', 'Image download returned empty body.');
    }

    $filename = basename(parse_url($url, PHP_URL_PATH));
    if (empty($filename)) {
        $filename = 'cj-image-' . time() . '.jpg';
    }

    $upload = wp_upload_bits($filename, null, $body);
    if (!empty($upload['error'])) {
        return new WP_Error('cj_image_upload', $upload['error']);
    }

    $filetype = wp_check_filetype($upload['file'], null);
    $attachment = [
        'post_mime_type' => $filetype['type'] ?? 'image/jpeg',
        'post_title' => sanitize_text_field($desc ?: $filename),
        'post_content' => '',
        'post_status' => 'inherit',
    ];

    $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }

    // Skip generating thumbnails during import to speed up (can be regenerated later)
    if (!$skip_metadata) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
    }

    return $attachment_id;
}

/**
 * Extract product ID from CJ product URL
 * Supports both UUID and numeric formats
 */
function cw_cj_extract_product_id_from_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    // PRIORITY 1: UUID format at end of URL before .html (e.g., -p-4BB39C1C-2AF0-4CC3-9D66-BAA427505625.html)
    // UUIDs have format: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
    if (preg_match('/-p-([A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})\.html/i', $url, $match)) {
        error_log('CJ Extract: Got UUID from -p- format: ' . $match[1]);
        return $match[1];
    }

    // PRIORITY 2: Legacy numeric format: ...-p-123456789.html
    if (preg_match('/-p-(\d+)\.html/i', $url, $match)) {
        error_log('CJ Extract: Got numeric ID from -p- format: ' . $match[1]);
        return $match[1];
    }

    // PRIORITY 3: UUID directly after /product/ path (before query or end)
    if (preg_match('/\/product\/([A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})/i', $url, $match)) {
        error_log('CJ Extract: Got UUID from /product/ path: ' . $match[1]);
        return $match[1];
    }

    // PRIORITY 4: Product name with UUID may appear, try to extract UUID-like pattern
    // This handles URLs where the UUID or ID comes after /product/
    if (preg_match('/\/product\/[^\/]*-p-([A-Fa-f0-9\-]+)(?:\.html|\/|$|\?)/i', $url, $match)) {
        $id = $match[1];
        // Validate it looks like an ID (has hyphens for UUID or all digits)
        if (preg_match('/^[A-Fa-f0-9\-]+$/', $id)) {
            error_log('CJ Extract: Got ID from /product/ with -p-: ' . $id);
            return $id;
        }
    }

    // PRIORITY 5: Query params: ?id=ID, ?pid=ID, ?productId=ID
    if (preg_match('/[?&](?:id|pid|productId|product_id)=([a-zA-Z0-9\-]+)/i', $url, $match)) {
        error_log('CJ Extract: Got ID from query params: ' . $match[1]);
        return $match[1];
    }

    // PRIORITY 6: Hash params: #id=ID
    if (preg_match('/#.*id=([a-zA-Z0-9\-]+)/i', $url, $match)) {
        error_log('CJ Extract: Got ID from hash params: ' . $match[1]);
        return $match[1];
    }

    error_log('CJ Extract: Failed to extract ID from URL: ' . $url);
    return '';
}

add_action('wp_ajax_cw_cj_import_ajax', function() {
    if (function_exists('set_time_limit')) {
        set_time_limit(0);
    }
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    // Verify nonce
    check_ajax_referer('cw_cj_import', 'cw_cj_import_nonce');
    
    $mode = sanitize_text_field($_POST['mode'] ?? 'search');
    $markup = intval($_POST['markup'] ?? 50) / 100;
    $search = sanitize_text_field($_POST['search'] ?? '');
    $skip_images = isset($_POST['skip_images']) && $_POST['skip_images'] === 'true';
    
    if (!CJ_Dropshipping::has_credentials()) {
        wp_send_json_error(['message' => 'CJ credentials not configured']);
    }
    
    $cj = cw_cj_dropshipping();
    
    $imported = 0;
    $skipped = 0;
    
    if ($mode === 'search') {
        if ($search && strpos($search, 'cjdropshipping.com') !== false) {
            wp_send_json_error(['message' => 'Looks like a CJ product link. Use Method 2: Import by Product Links.']);
        }
    }

    if ($mode === 'links') {
        // Import by product links
        $product_ids = [];
        
        // Log raw POST data
        error_log('🔍 CJ Import AJAX: Raw $_POST keys: ' . json_encode(array_keys($_POST)));
        error_log('🔍 CJ Import AJAX: Raw $_FILES: ' . json_encode($_FILES));
        
        // Handle both product_ids and product_ids[] formats
        if (isset($_POST['product_ids'])) {
            $raw_ids = $_POST['product_ids'];
            error_log('🔍 CJ Import: Found product_ids in POST');
            error_log('🔍 CJ Import: Raw product_ids type: ' . gettype($raw_ids));
            error_log('🔍 CJ Import: Raw product_ids value: ' . json_encode($raw_ids));
            
            // Handle both array and string formats
            if (is_array($raw_ids)) {
                error_log('🔍 CJ Import: product_ids is array with ' . count($raw_ids) . ' items');
                $product_ids = array_map('sanitize_text_field', $raw_ids);
            } else {
                error_log('🔍 CJ Import: product_ids is string, converting to array');
                $product_ids = [sanitize_text_field($raw_ids)];
            }
        } elseif (isset($_POST['product_ids[]'])) {
            // Try array format
            $raw_ids = $_POST['product_ids[]'];
            error_log('🔍 CJ Import: Found product_ids[] in POST');
            error_log('🔍 CJ Import: product_ids[] type: ' . gettype($raw_ids));
            
            if (is_array($raw_ids)) {
                error_log('🔍 CJ Import: product_ids[] is array with ' . count($raw_ids) . ' items');
                $product_ids = array_map('sanitize_text_field', $raw_ids);
            } else {
                error_log('🔍 CJ Import: product_ids[] is string');
                $product_ids = [sanitize_text_field($raw_ids)];
            }
        } else {
            error_log('🔍 CJ Import: NO product_ids found in POST!');
            wp_send_json_error(['message' => 'No product IDs provided']);
            return;
        }
        
        error_log('🔍 CJ Import: After initial processing - product_ids count: ' . count($product_ids));
        error_log('🔍 CJ Import: product_ids: ' . json_encode($product_ids));
        
        $normalized_ids = [];
        foreach ($product_ids as $raw_id) {
            $raw_id = trim((string) $raw_id);
            error_log('🔍 CJ Import: Processing raw_id: "' . $raw_id . '"');
            if ($raw_id === '') {
                error_log('🔍 CJ Import: Skipping empty raw_id');
                continue;
            }

            $extracted = '';
            if (strpos($raw_id, 'http') === 0 || strpos($raw_id, 'cjdropshipping.com') !== false) {
                error_log('🔍 CJ Import: raw_id looks like URL, extracting product ID');
                $extracted = cw_cj_extract_product_id_from_url($raw_id);
                error_log('🔍 CJ Import: Extracted from URL: "' . $extracted . '"');
            } else {
                error_log('🔍 CJ Import: raw_id does not look like URL, using as-is');
                $extracted = $raw_id;
            }

            $normalized_ids[] = $extracted !== '' ? $extracted : $raw_id;
        }

        $product_ids = array_values(array_unique(array_filter($normalized_ids)));

        error_log('🔍 CJ Import: Final normalized product_ids: ' . json_encode($product_ids));
        error_log('CJ Import Links Mode: Received ' . count($product_ids) . ' product IDs: ' . json_encode($product_ids));
        error_log('CJ Import: Raw POST product_ids: ' . json_encode($_POST['product_ids'] ?? []));
        
        if (empty($product_ids)) {
            error_log('CJ Import: NO product IDs after normalization');
            wp_send_json_error(['message' => 'No valid CJ product links or IDs provided.']);
        }
        
        foreach ($product_ids as $product_id) {
            error_log('CJ Import Links: Processing product ID ' . $product_id);
            
            if (empty($product_id)) {
                error_log('CJ Import Links: Skipping empty product ID');
                $skipped++;
                continue;
            }
            
            // Fetch product details directly
            error_log('CJ Import: Calling get_product_details() for product: ' . $product_id);
            $details = $cj->get_product_details($product_id, true);
            
            if (is_wp_error($details)) {
                error_log('CJ API Error [get_product_details]: ' . $details->get_error_message());
                $skipped++;
                continue;
            }
            
            if (empty($details)) {
                error_log('CJ Import: ⚠️ No details returned for product ' . $product_id);
                // Try fetching without inventory features as fallback
                error_log('CJ Import: Retrying without inventory features...');
                $details = $cj->get_product_details($product_id, false);
                
                if (empty($details)) {
                    error_log('CJ Import: Still no data. Skipping product ' . $product_id);
                    $skipped++;
                    continue;
                }
                
                error_log('CJ Import: ✓ Got details on retry for product ' . $product_id);
            } else {
                error_log('CJ Import: ✓ Got details for product ' . $product_id . ', name: ' . ($details['productName'] ?? 'unknown'));
            }
            
            // Get all variants for this product
            error_log('CJ Import: Fetching variants for product ' . $product_id);
            $variants = $cj->get_variants($product_id);
            
            if (empty($variants)) {
                error_log('CJ Import: No variants returned for product ' . $product_id);
                $skipped++;
                continue;
            }
            
            error_log('CJ Import: ✓ Found ' . count($variants) . ' variant(s) for product ' . $product_id);
            
            // Check if product already exists (by CJ product ID)
            $existing = wc_get_products([
                'meta_key' => '_cj_product_id',
                'meta_value' => $product_id,
                'limit' => 1,
            ]);
            
            if (!empty($existing)) {
                error_log('CJ Import: Skipping duplicate - CJ product ' . $product_id . ' already exists (ID: ' . $existing[0]->get_id() . ')');
                $skipped++;
                continue;
            }
            
            // Prepare product data with all available info
            $product = [
                'id' => $product_id,
                'pid' => $product_id,
                'nameEn' => $details['productName'] ?? $details['productNameEn'] ?? $details['name'] ?? 'CJ Product',
                'productName' => $details['productName'] ?? $details['productNameEn'] ?? $details['name'] ?? 'CJ Product',
                'productDescribeEn' => $details['productDescribeEn'] ?? $details['description'] ?? $details['descriptionEn'] ?? '',
                'description' => $details['productDescribeEn'] ?? $details['description'] ?? $details['descriptionEn'] ?? '',
                'productImage' => $details['productImage'] ?? $details['mainImage'] ?? '',
                'bigImage' => $details['productImage'] ?? $details['mainImage'] ?? '',
                'variants' => $variants,
            ];
            
            // Extract category
            $category_name = $details['categoryName'] ?? $details['categoryNameEn'] ?? '';
            if (empty($category_name) && !empty($details['categoryList']) && is_array($details['categoryList'])) {
                $first_category = reset($details['categoryList']);
                if (is_array($first_category)) {
                    $category_name = $first_category['name'] ?? $first_category['categoryName'] ?? $first_category['categoryNameEn'] ?? '';
                }
            }
            
            $category_id = 0;
            if (!empty($category_name)) {
                $term = term_exists($category_name, 'product_cat');
                if (!$term) {
                    $term = wp_insert_term($category_name, 'product_cat');
                }
                if (!is_wp_error($term)) {
                    $category_id = is_array($term) ? $term['term_id'] : $term;
                }
            }
            
            // Create variable product with all variants
            try {
                error_log('CJ Import: Creating variable product for "' . $product['productName'] . '" with ' . count($variants) . ' variants');
                error_log('CJ Import: Product data keys: ' . implode(', ', array_keys($product)));
                $created_product_id = cw_cj_create_variable_product($product, $variants, $markup, $category_id);
                
                if (is_wp_error($created_product_id)) {
                    error_log('CJ Import: ✗ Failed to create variable product - ' . $created_product_id->get_error_message());
                    $skipped++;
                    continue;
                }
                
                if (empty($created_product_id) || !is_numeric($created_product_id)) {
                    error_log('CJ Import: ✗ Invalid product ID returned: ' . var_export($created_product_id, true));
                    $skipped++;
                    continue;
                }
                
                error_log('CJ Import: ✓ Created WC product ID: ' . $created_product_id);
                
                // Download and attach main product images
                if (!$skip_images) {
                    $image_urls = [];
                    if (!empty($details['productImageSet']) && is_array($details['productImageSet'])) {
                        $image_urls = $details['productImageSet'];
                    } elseif (!empty($details['productImages']) && is_array($details['productImages'])) {
                        $image_urls = $details['productImages'];
                    } elseif (!empty($details['productImage']) && is_string($details['productImage'])) {
                        $image_urls = [$details['productImage']];
                    }
                    
                    if (!empty($image_urls)) {
                        error_log('CJ Import: Attaching ' . count($image_urls) . ' image(s) to product ' . $created_product_id);
                        // Skip metadata generation during bulk import for speed (use true as 4th param)
                        $featured_id = cw_cj_sideload_image($image_urls[0], $created_product_id, $product['nameEn'], true);
                        if (!is_wp_error($featured_id)) {
                            set_post_thumbnail($created_product_id, $featured_id);
                            error_log('CJ Import: Featured image attached (ID: ' . $featured_id . ') to product ' . $created_product_id);
                            
                            // Add remaining images as gallery images
                            $gallery_ids = [];
                            for ($i = 1; $i < count($image_urls); $i++) {
                                $gallery_img_id = cw_cj_sideload_image($image_urls[$i], $created_product_id, $product['nameEn'], true);
                                if (!is_wp_error($gallery_img_id)) {
                                    $gallery_ids[] = $gallery_img_id;
                                }
                            }
                            
                            // Set gallery images meta
                            if (!empty($gallery_ids)) {
                                update_post_meta($created_product_id, '_product_image_gallery', implode(',', $gallery_ids));
                                error_log('CJ Import: Added ' . count($gallery_ids) . ' gallery images to product ' . $created_product_id);
                            }
                        } else {
                            error_log('CJ Import: Image download failed - ' . $featured_id->get_error_message());
                        }
                    }
                }
                
                $imported++;
                error_log('CJ Import: Successfully created product from link! Product: ' . $created_product_id);
                
            } catch (Exception $e) {
                error_log('CJ Product Import Error: ' . $e->getMessage());
                $skipped++;
            }
        }
        
        $message = 'Successfully imported ' . $imported . ' product' . ($imported !== 1 ? 's' : '');
        if ($skipped > 0) {
            $message .= ', skipped ' . $skipped;
        }
        wp_send_json_success(['message' => $message]);
    }
    
    // Original search mode
    $limit = intval($_POST['limit'] ?? 10);
    
    $page = 1;
    $max_pages = 20;
    $total_pages = null;
    $total_records = null;
    
    while ($imported < $limit) {
        // Get products from CJ
        $result = $cj->list_products([
            'keyWord' => $search,
            'page' => $page,
            'size' => $limit,
            'countryCode' => 'US',
        ]);
        
        // Debug logging
        error_log('CJ Import Response (page ' . $page . '): ' . json_encode($result));
        $total_records = $result['data']['totalRecords'] ?? $result['totalRecords'] ?? $total_records;
        $total_pages = $result['data']['totalPages'] ?? $result['totalPages'] ?? $total_pages;
        error_log('CJ Import: Requested size ' . $limit . ' | Total records ' . (is_null($total_records) ? 'unknown' : $total_records) . ' | Page ' . $page . (is_null($total_pages) ? '' : ' of ' . $total_pages));
        
        // Check for errors
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'CJ API Error: ' . $result->get_error_message()]);
        }
        
        if (empty($result)) {
            wp_send_json_error(['message' => 'No response from CJ API']);
        }
        
        // Handle different response structures
        $content = [];
        if (isset($result['data']['content'])) {
            $content = $result['data']['content'];
        } elseif (isset($result['content'])) {
            $content = $result['content'];
        } else {
            error_log('CJ Response structure: ' . json_encode($result));
            wp_send_json_error(['message' => 'Invalid response format from CJ']);
        }
        
        $found_any = false;
        foreach ($content as $item) {
            if (!isset($item['productList'])) {
                continue;
            }
            
            foreach ($item['productList'] as $product) {
                $found_any = true;
                
                // Get all variants for this product
                $variants = $product['variants'] ?? [];
                if (empty($variants)) {
                    $variants = $cj->get_variants($product['id'] ?? '');
                }

                if (empty($variants)) {
                    error_log('CJ Import: No variants for product ' . ($product['id'] ?? 'unknown'));
                    $skipped++;
                    continue;
                }
                
                error_log('CJ Import: Found ' . count($variants) . ' variants for product ' . ($product['id'] ?? 'unknown'));
                if (!empty($variants[0]) && is_array($variants[0])) {
                    $variant_keys = implode(', ', array_keys($variants[0]));
                    $variant_image = function_exists('cw_cj_get_variant_image_url') ? cw_cj_get_variant_image_url($variants[0]) : '';
                    error_log('CJ Import: Variant keys for product ' . ($product['id'] ?? 'unknown') . ' -> ' . $variant_keys);
                    if (!empty($variant_image)) {
                        error_log('CJ Import: Variant image url sample for product ' . ($product['id'] ?? 'unknown') . ' -> ' . $variant_image);
                    }
                }
                
                // Check if product already exists (by CJ product ID)
                if (!empty($product['id'])) {
                    $existing = wc_get_products([
                        'meta_key' => '_cj_product_id',
                        'meta_value' => $product['id'],
                        'limit' => 1,
                    ]);
                    
                    if (!empty($existing)) {
                        error_log('CJ Import: Skipping duplicate - CJ product ' . $product['id'] . ' already exists (ID: ' . $existing[0]->get_id() . ')');
                        $skipped++;
                        continue;
                    }
                }

                // Fetch product details (description, images, category)
                $details = [];
                if (!empty($product['id'])) {
                    $details = $cj->get_product_details($product['id'], true);
                }

                $description = $product['productDescribeEn'] ?? $details['description'] ?? $details['productDescribeEn'] ?? '';

                // Extract category
                $category_name = $details['categoryName'] ?? $details['categoryNameEn'] ?? '';
                if (empty($category_name) && !empty($details['categoryList']) && is_array($details['categoryList'])) {
                    $first_category = reset($details['categoryList']);
                    if (is_array($first_category)) {
                        $category_name = $first_category['name'] ?? $first_category['categoryName'] ?? $first_category['categoryNameEn'] ?? '';
                    }
                }
                if (empty($category_name)) {
                    $category_name = $product['threeCategoryName'] ?? $product['twoCategoryName'] ?? $product['oneCategoryName'] ?? '';
                }
                
                $category_id = 0;
                if (!empty($category_name)) {
                    $term = term_exists($category_name, 'product_cat');
                    if (!$term) {
                        $term = wp_insert_term($category_name, 'product_cat');
                    }
                    if (!is_wp_error($term)) {
                        $category_id = is_array($term) ? $term['term_id'] : $term;
                    }
                }

                // Set product description in meta (for variable product)
                $product['productDescribeEn'] = $description;

                // Create variable product with all variants
                try {
                    $product_id = cw_cj_create_variable_product($product, $variants, $markup, $category_id);
                    
                    if (is_wp_error($product_id)) {
                        error_log('CJ Import: Failed to create variable product - ' . $product_id->get_error_message());
                        $skipped++;
                        continue;
                    }

                    // Download and attach main product images (featured image from first valid image)
                    if (!$skip_images) {
                        $image_urls = [];
                        if (!empty($details['productImageSet']) && is_array($details['productImageSet'])) {
                            $image_urls = $details['productImageSet'];
                        } elseif (!empty($details['productImages']) && is_array($details['productImages'])) {
                            $image_urls = $details['productImages'];
                        } elseif (!empty($details['productImage']) && is_string($details['productImage'])) {
                            $image_urls = [$details['productImage']];
                        } elseif (!empty($product['bigImage'])) {
                            $image_urls = [$product['bigImage']];
                        }

                        error_log('CJ Import: Found ' . count($image_urls) . ' images for variable product ' . $product_id);

                        if (!empty($image_urls)) {
                            // Skip metadata generation during bulk import for speed
                            $featured_id = cw_cj_sideload_image($image_urls[0], $product_id, $product['nameEn'] ?? 'CJ Product', true);
                            if (!is_wp_error($featured_id)) {
                                set_post_thumbnail($product_id, $featured_id);
                                error_log('CJ Import: Featured image attached (ID: ' . $featured_id . ') to variable product ' . $product_id);
                                
                                // Add remaining images as gallery/attachment images
                                $gallery_ids = [];
                                for ($i = 1; $i < count($image_urls); $i++) {
                                    $gallery_img_id = cw_cj_sideload_image($image_urls[$i], $product_id, $product['nameEn'] ?? 'CJ Product', true);
                                    if (!is_wp_error($gallery_img_id)) {
                                        $gallery_ids[] = $gallery_img_id;
                                    }
                                }
                                
                                // Set gallery images meta
                                if (!empty($gallery_ids)) {
                                    update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
                                    error_log('CJ Import: Added ' . count($gallery_ids) . ' gallery images to product ' . $product_id);
                                }
                            } else {
                                error_log('CJ Import: Image download failed - ' . $featured_id->get_error_message());
                            }
                        } else {
                            error_log('CJ Import: No images found for product ' . $product_id);
                        }
                    }

                    $imported++;
                    error_log('CJ Import: Successfully created variable product! Total imported: ' . $imported);
                    
                    if ($imported >= $limit) {
                        break 2;
                    }
                    
                } catch (Exception $e) {
                    error_log('CJ Product Import Error: ' . $e->getMessage());
                    $skipped++;
                }
            }
        }

        if (!$found_any) {
            break;
        }

        $page++;
        if (!is_null($total_pages) && $page > $total_pages) {
            break;
        }
        if ($page > $max_pages) {
            break;
        }
    }
    
    if ($imported === 0 && $skipped === 0) {
        wp_send_json_error(['message' => 'No products found in response']);
    }
    
    $message = sprintf(
        'Success! Created %d variable products. Skipped %d (duplicates/errors).',
        $imported,
        $skipped
    );
    
    wp_send_json_success([
        'message' => $message,
        'imported' => $imported,
        'skipped' => $skipped,
    ]);
});

// ==================== FRONTEND SHORTCODE FOR ADMIN IMPORT ====================

/**
 * Shortcode: [cj_import_dashboard]
 * Displays product import dashboard on frontend (admin only)
 */
function cw_cj_import_dashboard_shortcode($atts) {
    // Security check - only admins
    if (!is_user_logged_in()) {
        return '<div style="padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; color: #991b1b; margin: 20px;"><strong>⚠️ Login Required:</strong> Please log in as an administrator to access the import dashboard.</div>';
    }
    
    if (!current_user_can('manage_options')) {
        return '<div style="padding: 20px; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; color: #991b1b; margin: 20px;"><strong>❌ Access Denied:</strong> Only administrators can access this page.</div>';
    }
    
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>
    <style>
        .cj-frontend-dashboard {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        .cj-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.25);
        }
        
        .cj-header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .cj-header p {
            margin: 8px 0 0 0;
            opacity: 0.95;
            font-size: 16px;
        }
        
        .cj-section {
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .cj-section h2 {
            margin-top: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .cj-form-group {
            margin-bottom: 24px;
        }
        
        .cj-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        
        .cj-form-group input[type="text"],
        .cj-form-group input[type="number"],
        .cj-form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
            background: white;
            font-family: inherit;
        }
        
        .cj-form-group input:focus,
        .cj-form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #f9fafb;
        }
        
        .cj-description {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
            line-height: 1.5;
        }
        
        .cj-description a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .cj-description a:hover {
            color: #764ba2;
        }
        
        .cj-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 13px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .cj-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.35);
        }
        
        .cj-button:active {
            transform: translateY(0);
        }
        
        .cj-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cj-import-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .cj-notice {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .cj-notice-success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #10b981;
        }
        
        .cj-notice-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #ef4444;
        }
        
        .cj-import-status {
            display: none;
            padding: 24px;
            background: linear-gradient(135deg, #f3f4f6 0%, #f9fafb 100%);
            border-radius: 10px;
            text-align: center;
            margin-top: 25px;
            border: 2px solid #e5e7eb;
        }
        
        .cj-spinner {
            display: inline-block;
            width: 28px;
            height: 28px;
            border: 3px solid #d1d5db;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 12px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .cj-import-form .cj-form-group {
            margin-bottom: 0;
        }
    </style>
    
    <div class="cj-frontend-dashboard">
        <div class="cj-header">
            <h1>🚀 CJ Product Import Dashboard</h1>
            <p>Import products from CJ Dropshipping catalog using keywords or direct product links</p>
        </div>
        
        <!-- Product Import -->
        <div class="cj-section">
            <h2>📥 Import Products</h2>
            <p style="color: #6b7280; margin-bottom: 20px; line-height: 1.6;">
                Automatically import CJ products with titles, descriptions, and pricing. Products are instantly linked to CJ variants for automatic order creation.
            </p>
            
            <!-- Search Import Form -->
            <h3 style="color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Method 1: Search by Keyword</h3>
            <form id="cj-import-form-search-frontend" class="cj-import-form" style="margin-bottom: 40px;">
                <?php wp_nonce_field('cw_cj_import', 'cw_cj_import_nonce'); ?>
                
                <div class="cj-form-group">
                    <label for="import_search_fe">Search Products</label>
                    <input type="text" 
                           id="import_search_fe" 
                           name="import_search" 
                           placeholder="hoodie, mug, shirt...">
                    <div class="cj-description">Leave empty to import all</div>
                </div>
                
                <div class="cj-form-group">
                    <label for="import_markup_fe">Price Markup (%)</label>
                    <input type="number" 
                           id="import_markup_fe" 
                           name="import_markup" 
                           value="50" 
                           min="0" 
                           max="500">
                    <div class="cj-description">50 = 50% markup</div>
                </div>
                
                <div class="cj-form-group">
                    <label for="import_limit_fe">Max Products</label>
                    <input type="number" 
                           id="import_limit_fe" 
                           name="import_limit" 
                           value="10" 
                           min="1" 
                           max="500">
                    <div class="cj-description">Start with 10</div>
                </div>
                
                <button type="submit" class="cj-button" id="cj-import-btn-search-fe">
                    <span id="cj-import-btn-text-search-fe">Start Search Import</span>
                </button>
            </form>
            
            <!-- Link Import Form -->
            <h3 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px;">Method 2: Import by Product Links</h3>
            <form id="cj-import-form-links-frontend" style="margin-bottom: 20px;">
                <?php wp_nonce_field('cw_cj_import', 'cw_cj_import_nonce'); ?>
                
                <div class="cj-form-group">
                    <label for="import_single_link_fe">Single Product Link</label>
                    <input type="text" 
                           id="import_single_link_fe" 
                           name="import_single_link" 
                           placeholder="https://cjdropshipping.com/product/...">
                    <div class="cj-description">Paste a single CJ product link to import one product</div>
                </div>
                
                <div class="cj-form-group">
                    <label for="import_bulk_links_fe">Bulk Product Links</label>
                    <textarea id="import_bulk_links_fe" 
                              name="import_bulk_links" 
                              placeholder="Paste multiple CJ product links (one per line)&#10;https://cjdropshipping.com/product/...&#10;https://cjdropshipping.com/product/..."
                              style="min-height: 120px;"></textarea>
                    <div class="cj-description">One link per line. Single link will be imported first if provided.</div>
                </div>
                
                <div style="display: grid; grid-template-columns: auto auto; gap: 20px; align-items: end;">
                    <div class="cj-form-group">
                        <label for="import_link_markup_fe">Price Markup (%)</label>
                        <input type="number" 
                               id="import_link_markup_fe" 
                               name="import_link_markup" 
                               value="50" 
                               min="0" 
                               max="500">
                        <div class="cj-description">50 = 50% markup</div>
                    </div>
                    
                    <button type="submit" class="cj-button" id="cj-import-btn-links-fe">
                        <span id="cj-import-btn-text-links-fe">Start Link Import</span>
                    </button>
                </div>
            </form>
            
            <div class="cj-import-status" id="cj-import-status-fe">
                <div class="cj-spinner"></div>
                <strong>Importing...</strong> <span id="cj-import-count-fe">0</span> products
            </div>
            
            <div id="cj-import-results-fe" style="display: none; margin-top: 20px;">
                <div class="cj-notice cj-notice-success">
                    <p id="cj-import-success-msg-fe"></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(function($) {
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // Handle Search Import
        $('#cj-import-form-search-frontend').on('submit', function(e) {
            e.preventDefault();
            
            const search = $('#import_search_fe').val();
            const markup = $('#import_markup_fe').val();
            const limit = $('#import_limit_fe').val();
            const nonce = $('input[name="cw_cj_import_nonce"]').val();
            
            $('#cj-import-status-fe').show();
            $('#cj-import-results-fe').hide();
            $('#cj-import-btn-text-search-fe').text('Processing...');
            $('#cj-import-btn-search-fe').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cw_cj_import_ajax',
                    cw_cj_import_nonce: nonce,
                    mode: 'search',
                    search: search,
                    markup: markup,
                    limit: limit
                },
                success: function(response) {
                    $('#cj-import-status-fe').hide();
                    $('#cj-import-btn-text-search-fe').text('Start Search Import');
                    $('#cj-import-btn-search-fe').prop('disabled', false);
                    
                    if (response.success) {
                        $('#cj-import-success-msg-fe').html('✓ ' + response.data.message);
                        $('#cj-import-results-fe').show();
                    } else {
                        alert('❌ ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    $('#cj-import-status-fe').hide();
                    $('#cj-import-btn-text-search-fe').text('Start Search Import');
                    $('#cj-import-btn-search-fe').prop('disabled', false);
                    const message = xhr?.responseJSON?.data?.message || xhr?.responseText || 'Request failed.';
                    alert('❌ ' + message);
                }
            });
        });
        
        // Handle Link Import
        $('#cj-import-form-links-frontend').on('submit', function(e) {
            e.preventDefault();
            
            // Extract CJ product ID from URL
            function extractProductId(url) {
                url = url.trim();

                // PRIORITY 1: UUID format at end of URL before .html (e.g., -p-4BB39C1C-2AF0-4CC3-9D66-BAA427505625.html)
                // UUIDs have format: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
                let match = url.match(/-p-([A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})\.html/i);
                if (match && match[1]) {
                    console.log('Extracted UUID from -p- format:', match[1]);
                    return match[1];
                }

                // PRIORITY 2: Legacy numeric format: ...-p-123456789.html
                match = url.match(/-p-(\d+)\.html/i);
                if (match && match[1]) {
                    console.log('Extracted numeric ID from -p- format:', match[1]);
                    return match[1];
                }

                // PRIORITY 3: UUID directly after /product/ path
                match = url.match(/\/product\/([A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12})/i);
                if (match && match[1]) {
                    console.log('Extracted UUID from /product/ path:', match[1]);
                    return match[1];
                }

                // PRIORITY 4: Extract everything from /product/ to first query string or end
                // This avoids extracting just "2" from "2-in-1-product-name"
                match = url.match(/\/product\/([^\/?#]+?)(?:\-p\-|\/|$)/i);
                if (match && match[1]) {
                    // Only use this if it looks like a product ID (UUID or numeric), not a product name
                    const id = match[1];
                    if (/^[A-Fa-f0-9\-]+$/.test(id) || /^\d+$/.test(id)) {
                        console.log('Extracted ID from /product/ path:', id);
                        return id;
                    }
                }

                // PRIORITY 5: Query params: ?id=ID, ?pid=ID, ?productId=ID
                match = url.match(/[?&](?:id|pid|productId|product_id)=([a-zA-Z0-9\-]+)/i);
                if (match && match[1]) {
                    console.log('Extracted ID from query params:', match[1]);
                    return match[1];
                }

                // PRIORITY 6: Hash params: #id=ID
                match = url.match(/#.*id=([a-zA-Z0-9\-]+)/i);
                if (match && match[1]) {
                    console.log('Extracted ID from hash params:', match[1]);
                    return match[1];
                }

                console.log('Failed to extract product ID from:', url);
                return null;
            }
            
            const singleLink = $('#import_single_link_fe').val().trim();
            const bulkLinks = $('#import_bulk_links_fe').val().trim();
            const markup = $('#import_link_markup_fe').val();
            const nonce = $('input[name="cw_cj_import_nonce"]').val();
            
            console.log('========== CJ LINK IMPORT DEBUG ==========');
            console.log('🔍 Form submission started');
            console.log('🔍 Single Link field value:', singleLink);
            console.log('🔍 Single Link length:', singleLink.length);
            console.log('🔍 Bulk Links field value:', bulkLinks);
            console.log('🔍 Bulk Links length:', bulkLinks.length);
            
            let productIds = [];
            
            // Add single link if provided
            if (singleLink) {
                console.log('🔍 STEP 1: Processing single link...');
                console.log('   Input URL:', singleLink);
                
                const id = extractProductId(singleLink);
                console.log('   Extracted ID:', id);
                
                if (id && id.length > 0) {
                    console.log('   ✓ Valid ID extracted');
                    productIds.push(id);
                } else {
                    console.log('   ✗ FAILED - No valid ID extracted!');
                    alert('❌ Invalid single product link. Could not extract product ID.\n\nMake sure you pasted the complete URL from CJ Dropshipping.');
                    return;
                }
            }
            
            // Add bulk links
            if (bulkLinks) {
                console.log('🔍 STEP 2: Processing bulk links...');
                const links = bulkLinks.split('\n');
                console.log('   Found ' + links.length + ' line(s)');
                
                for (let i = 0; i < links.length; i++) {
                    const link = links[i].trim();
                    if (link) {
                        console.log('   Line ' + (i + 1) + ': ' + link.substring(0, 50) + '...');
                        const id = extractProductId(link);
                        console.log('   → Extracted: ' + id);
                        
                        if (id) {
                            if (!productIds.includes(id)) {
                                productIds.push(id);
                            }
                        } else {
                            console.log('   ✗ FAILED to extract ID from line ' + (i + 1));
                            alert('❌ Invalid product link at line ' + (i + 1) + ': ' + link);
                            return;
                        }
                    }
                }
            }
            
            console.log('🔍 FINAL: productIds array:', productIds);
            console.log('========== END DEBUG ==========');
            
            if (productIds.length === 0) {
                alert('❌ Please provide at least one product link');
                return;
            }
            
            $('#cj-import-status-fe').show();
            $('#cj-import-results-fe').hide();
            $('#cj-import-btn-text-links-fe').text('Processing...');
            $('#cj-import-btn-links-fe').prop('disabled', true);
            
            // Build form data object
            const formData = new FormData();
            formData.append('action', 'cw_cj_import_ajax');
            formData.append('cw_cj_import_nonce', nonce);
            formData.append('mode', 'links');
            formData.append('markup', markup);
            
            // Add each product ID as separate parameter
            for (let i = 0; i < productIds.length; i++) {
                formData.append('product_ids[]', productIds[i]);
            }
            
            console.log('🔍 AJAX: Sending FormData with:');
            console.log('   productIds array: ' + JSON.stringify(productIds));
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#cj-import-status-fe').hide();
                    $('#cj-import-btn-text-links-fe').text('Start Link Import');
                    $('#cj-import-btn-links-fe').prop('disabled', false);
                    
                    if (response.success) {
                        $('#cj-import-success-msg-fe').html('✓ ' + response.data.message);
                        $('#cj-import-results-fe').show();
                        
                        // Reset form
                        $('#import_single_link_fe').val('');
                        $('#import_bulk_links_fe').val('');
                    } else {
                        alert('❌ ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    $('#cj-import-status-fe').hide();
                    $('#cj-import-btn-text-links-fe').text('Start Link Import');
                    $('#cj-import-btn-links-fe').prop('disabled', false);
                    const message = xhr?.responseJSON?.data?.message || xhr?.responseText || 'Request failed.';
                    alert('❌ ' + message);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('cj_import_dashboard', 'cw_cj_import_dashboard_shortcode');

// Test shortcode to verify
add_shortcode('cj_test', function() {
    return 'CJ Integration is loaded! (Test shortcode works)';
});


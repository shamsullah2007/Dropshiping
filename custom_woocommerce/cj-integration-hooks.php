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
 * Import products from CJ catalog (Simplified - no images)
 */
function cw_cj_extract_product_id_from_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (preg_match('/-p-(\d+)\.html/i', $url, $match)) {
        return $match[1];
    }

    if (preg_match('/\/product\/([a-zA-Z0-9]+)/i', $url, $match)) {
        return $match[1];
    }

    if (preg_match('/[?&](?:id|pid|productId|product_id)=([a-zA-Z0-9]+)/i', $url, $match)) {
        return $match[1];
    }

    if (preg_match('/#.*id=([a-zA-Z0-9]+)/i', $url, $match)) {
        return $match[1];
    }

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
        
        if (isset($_POST['product_ids'])) {
            $raw_ids = $_POST['product_ids'];
            
            // Handle both array and string formats
            if (is_array($raw_ids)) {
                $product_ids = array_map('sanitize_text_field', $raw_ids);
            } else {
                // If it's a single string (shouldn't happen but just in case)
                $product_ids = [sanitize_text_field($raw_ids)];
            }
        }
        
        $normalized_ids = [];
        foreach ($product_ids as $raw_id) {
            $raw_id = trim((string) $raw_id);
            if ($raw_id === '') {
                continue;
            }

            $extracted = '';
            if (strpos($raw_id, 'http') === 0 || strpos($raw_id, 'cjdropshipping.com') !== false) {
                $extracted = cw_cj_extract_product_id_from_url($raw_id);
            }

            $normalized_ids[] = $extracted !== '' ? $extracted : $raw_id;
        }

        $product_ids = array_values(array_unique(array_filter($normalized_ids)));

        error_log('CJ Import Links Mode: Received ' . count($product_ids) . ' product IDs: ' . json_encode($product_ids));
        
        if (empty($product_ids)) {
            wp_send_json_error(['message' => 'No valid CJ product links or IDs provided.']);
        }
        
        foreach ($product_ids as $product_id) {
            // Fetch product details directly
            $details = $cj->get_product_details($product_id, true);
            
            if (is_wp_error($details)) {
                error_log('CJ Import: Failed to fetch product ' . $product_id . ' - ' . $details->get_error_message());
                $skipped++;
                continue;
            }
            
            if (empty($details)) {
                error_log('CJ Import: No data returned for product ' . $product_id);
                $skipped++;
                continue;
            }
            
            // Get all variants for this product
            $variants = $cj->get_variants($product_id, 'US');
            
            if (empty($variants)) {
                error_log('CJ Import: No variants for product ' . $product_id);
                $skipped++;
                continue;
            }
            
            error_log('CJ Import: Found ' . count($variants) . ' variants for product ' . $product_id);
            
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
            
            // Prepare product data
            $product = [
                'id' => $product_id,
                'nameEn' => $details['productName'] ?? $details['productNameEn'] ?? 'Imported Product',
                'productDescribeEn' => $details['productDescribeEn'] ?? $details['description'] ?? '',
                'bigImage' => $details['productImage'] ?? '',
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
                $created_product_id = cw_cj_create_variable_product($product, $variants, $markup, $category_id);
                
                if (is_wp_error($created_product_id)) {
                    error_log('CJ Import: Failed to create variable product - ' . $created_product_id->get_error_message());
                    $skipped++;
                    continue;
                }
                
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
                    $variants = $cj->get_variants($product['id'] ?? '', 'US');
                }

                if (empty($variants)) {
                    error_log('CJ Import: No variants for product ' . ($product['id'] ?? 'unknown'));
                    $skipped++;
                    continue;
                }
                
                error_log('CJ Import: Found ' . count($variants) . ' variants for product ' . ($product['id'] ?? 'unknown'));
                
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

                // Common CJ format: ...-p-123456789.html
                let match = url.match(/-p-(\d+)\.html/i);
                if (match && match[1]) return match[1];

                // Alternate format: /product/ID
                match = url.match(/\/product\/([a-zA-Z0-9]+)/i);
                if (match && match[1]) return match[1];

                // Query params: ?id=ID, ?pid=ID, ?productId=ID
                match = url.match(/[?&](?:id|pid|productId|product_id)=([a-zA-Z0-9]+)/i);
                if (match && match[1]) return match[1];

                // Hash params: #id=ID
                match = url.match(/#.*id=([a-zA-Z0-9]+)/i);
                if (match && match[1]) return match[1];

                return null;
            }
            
            const singleLink = $('#import_single_link_fe').val().trim();
            const bulkLinks = $('#import_bulk_links_fe').val().trim();
            const markup = $('#import_link_markup_fe').val();
            const nonce = $('input[name="cw_cj_import_nonce"]').val();
            
            let productIds = [];
            
            // Add single link if provided
            if (singleLink) {
                const id = extractProductId(singleLink);
                if (id) {
                    productIds.push(id);
                } else {
                    alert('❌ Invalid single product link. Please check the URL format.');
                    return;
                }
            }
            
            // Add bulk links
            if (bulkLinks) {
                const links = bulkLinks.split('\n');
                for (let i = 0; i < links.length; i++) {
                    const link = links[i].trim();
                    if (link) {
                        const id = extractProductId(link);
                        if (id) {
                            if (!productIds.includes(id)) {
                                productIds.push(id);
                            }
                        } else {
                            alert('❌ Invalid product link at line ' + (i + 1) + ': ' + link);
                            return;
                        }
                    }
                }
            }
            
            if (productIds.length === 0) {
                alert('❌ Please provide at least one product link');
                return;
            }
            
            $('#cj-import-status-fe').show();
            $('#cj-import-results-fe').hide();
            $('#cj-import-btn-text-links-fe').text('Processing...');
            $('#cj-import-btn-links-fe').prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $.param({
                    action: 'cw_cj_import_ajax',
                    cw_cj_import_nonce: nonce,
                    mode: 'links',
                    product_ids: productIds,
                    markup: markup
                }, true),
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


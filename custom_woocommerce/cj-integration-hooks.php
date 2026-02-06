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
 * Create CJ order when WooCommerce order is placed
 * This is a template for automatic order forwarding
 */
add_action('woocommerce_order_status_processing', function($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order || CJ_Dropshipping::get_cj_order_id($order_id)) {
        return; // Already has CJ order or invalid order
    }
    
    if (!CJ_Dropshipping::has_credentials()) {
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
        $cj_product_id = $product->get_meta('_cj_product_id');
        $cj_variant_id = $product->get_meta('_cj_variant_id');
        
        if ($cj_variant_id || $cj_product_id) {
            $products[] = [
                'vid' => $cj_variant_id,
                'quantity' => $item->get_quantity(),
                'storeLineItemId' => 'WOO-' . $order_id . '-' . $item->get_id(),
            ];
        }
    }
    
    if (empty($products)) {
        $order->add_order_note('No CJ products found in order - skipping CJ order creation');
        return;
    }
    
    // Create CJ order
    $result = $cj->create_order($order_data, $products, 2); // payType=2 for balance payment
    
    if (is_wp_error($result) || !isset($result['data']['orderId'])) {
        $error_msg = is_wp_error($result) ? $result->get_error_message() : ($result['message'] ?? 'Unknown error');
        $order->add_order_note('CJ order creation failed: ' . $error_msg);
        return;
    }
    
    $cj_order_id = $result['data']['orderId'];
    
    // Map CJ order to WooCommerce order
    CJ_Dropshipping::map_woo_to_cj_order($order_id, $cj_order_id);
    
    // Add cart and confirm
    $cart_result = $cj->add_to_cart($cj_order_id);
    if (isset($cart_result['data']['successCount']) && $cart_result['data']['successCount'] > 0) {
        $cj->confirm_cart($cj_order_id);
    }
    
    // Generate parent order for payment
    $shipment_result = $cj->generate_parent_order($cj_order_id);
    
    if (isset($shipment_result['payId']) && $shipment_result['canDeduct']) {
        // Auto-pay from balance
        $pay_result = $cj->pay_balance_v2($cj_order_id, $shipment_result['payId']);
        
        if ($pay_result) {
            $order->add_order_note(sprintf(
                'CJ Order Created & Paid: Order ID=%s, Amount=$%s',
                $cj_order_id,
                $shipment_result['actualPayment']
            ));
        }
    }
}
);

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
function cw_cj_sideload_image($url, $post_id, $desc = '') {
    if (empty($url)) {
        return new WP_Error('cj_image_empty', 'Image URL is empty.');
    }

    $response = wp_remote_get($url, [
        'timeout' => 30,
        'redirection' => 5,
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

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id;
}

/**
 * Import products from CJ catalog (Simplified - no images)
 */
add_action('wp_ajax_cw_cj_import_ajax', function() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    
    // Verify nonce
    check_ajax_referer('cw_cj_import', 'cw_cj_import_nonce');
    
    $search = sanitize_text_field($_POST['search'] ?? '');
    $markup = intval($_POST['markup'] ?? 50) / 100;
    $limit = intval($_POST['limit'] ?? 10);
    
    if (!CJ_Dropshipping::has_credentials()) {
        wp_send_json_error(['message' => 'CJ credentials not configured']);
    }
    
    $cj = cw_cj_dropshipping();
    
    // Get products from CJ
    $result = $cj->list_products([
        'keyWord' => $search,
        'page' => 1,
        'size' => $limit,
        'countryCode' => 'US',
    ]);
    
    // Debug logging
    error_log('CJ Import Response: ' . json_encode($result));
    
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
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($content as $item) {
        if (!isset($item['productList'])) {
            continue;
        }
        
        foreach ($item['productList'] as $product) {
            $variants = $product['variants'] ?? [];
            if (empty($variants)) {
                $variants = $cj->get_variants($product['id'] ?? '', 'US');
            }

            if (empty($variants)) {
                error_log('CJ Import: No variants for product ' . ($product['id'] ?? 'unknown'));
                $skipped++;
                continue;
            }
            
            // Get first variant
            $variant = reset($variants);
            if (empty($variant['vid'])) {
                error_log('CJ Import: Missing vid for product ' . ($product['id'] ?? 'unknown'));
                $skipped++;
                continue;
            }
            
            // Check if product already exists
            $existing = wc_get_products([
                'meta_key' => '_cj_variant_id',
                'meta_value' => $variant['vid'],
                'limit' => 1,
            ]);
            
            if (!empty($existing)) {
                $skipped++;
                continue; // Skip duplicates
            }
            
            // Fetch details for description and images
            $details = [];
            if (!empty($product['id'])) {
                $details = $cj->get_product_details($product['id'], true);
            }

            $description = $product['productDescribeEn'] ?? $details['description'] ?? $details['productDescribeEn'] ?? '';

            $category_name = $product['threeCategoryName'] ?? $product['twoCategoryName'] ?? $product['oneCategoryName'] ?? '';
            $category_ids = [];
            if (!empty($category_name)) {
                $term = term_exists($category_name, 'product_cat');
                if (!$term) {
                    $term = wp_insert_term($category_name, 'product_cat');
                }
                if (!is_wp_error($term)) {
                    $category_ids[] = is_array($term) ? $term['term_id'] : $term;
                }
            }

            // Calculate price with markup
            $raw_price = $variant['salePrice'] ?? $variant['sellPrice'] ?? $product['nowPrice'] ?? $product['sellPrice'] ?? '10';
            if (is_string($raw_price)) {
                $raw_price = preg_replace('/[^0-9\.\-]/', '', $raw_price);
                $raw_price = explode('-', $raw_price)[0];
                $raw_price = explode('--', $raw_price)[0];
            }
            $cj_price = floatval($raw_price ?: 10);
            $your_price = $cj_price * (1 + $markup);
            
            // Create WooCommerce product
            $product_data = [
                'name' => sanitize_text_field($product['nameEn'] ?? 'Unnamed Product'),
                'type' => 'simple',
                'status' => 'publish',
                'description' => wp_kses_post($description),
                'regular_price' => (string) round($your_price, 2),
                'meta_data' => [
                    [
                        'key' => '_cj_variant_id',
                        'value' => $variant['vid'],
                    ],
                    [
                        'key' => '_cj_product_name',
                        'value' => $product['nameEn'],
                    ],
                    [
                        'key' => '_cj_cost_price',
                        'value' => $cj_price,
                    ],
                ],
            ];
            
            // Try to create product
            $wc_product = new WC_Product_Simple();
            $wc_product->set_props($product_data);
            if (!empty($category_ids)) {
                $wc_product->set_category_ids($category_ids);
            }
            
            try {
                $product_id = $wc_product->save();
                
                if (!$product_id) {
                    $skipped++;
                    continue;
                }
                
                // Download and attach images
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

                if (!empty($image_urls)) {
                    $featured_id = cw_cj_sideload_image($image_urls[0], $product_id, $product['nameEn'] ?? 'CJ Product');
                    if (!is_wp_error($featured_id)) {
                        set_post_thumbnail($product_id, $featured_id);
                    } else {
                        error_log('CJ Import: Image download failed - ' . $featured_id->get_error_message());
                    }

                    $gallery_ids = [];
                    for ($i = 1; $i < count($image_urls); $i++) {
                        $image_id = cw_cj_sideload_image($image_urls[$i], $product_id, $product['nameEn'] ?? 'CJ Product');
                        if (!is_wp_error($image_id)) {
                            $gallery_ids[] = $image_id;
                        }
                    }
                    if (!empty($gallery_ids)) {
                        update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
                    }
                }

                $imported++;
                
            } catch (Exception $e) {
                error_log('CJ Product Import Error: ' . $e->getMessage());
                $skipped++;
            }
        }
    }
    
    if ($imported === 0 && $skipped === 0) {
        wp_send_json_error(['message' => 'No products found in response']);
    }
    
    $message = sprintf(
        'Success! Created %d products. Skipped %d (duplicates/errors).',
        $imported,
        $skipped
    );
    
    wp_send_json_success([
        'message' => $message,
        'imported' => $imported,
        'skipped' => $skipped,
    ]);
});


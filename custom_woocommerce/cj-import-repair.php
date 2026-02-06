<?php
/**
 * CJ Dropshipping Import Repair Utility
 * 
 * Fixes incomplete or failed imports and validates product data
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check CJ imported products for issues and generate repair report
 */
function cw_cj_check_product_integrity() {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_cj_product_id',
                'compare' => 'EXISTS',
            ]
        ],
    ];

    $products = get_posts($args);
    $report = [
        'total' => count($products),
        'missing_price' => [],
        'missing_variations' => [],
        'missing_images' => [],
        'incomplete_variants' => [],
        'ok' => 0,
    ];

    foreach ($products as $product) {
        $product_id = $product->ID;
        $wc_product = wc_get_product($product_id);

        if (!$wc_product) {
            continue;
        }

        $issues = [];

        // Check pricing
        if (!$wc_product->get_price()) {
            $issues['missing_price'] = true;
        }

        // Check variations (only for variable products)
        if ($wc_product->is_type('variable')) {
            $variations = $wc_product->get_children();
            if (empty($variations)) {
                $issues['missing_variations'] = true;
            } else {
                // Check each variation
                foreach ($variations as $var_id) {
                    $variation = wc_get_product($var_id);
                    if (!$variation || !$variation->get_price()) {
                        $issues['incomplete_variants'] = true;
                        break;
                    }
                }
            }
        }

        // Check images
        if (!$wc_product->get_image_id()) {
            $issues['missing_images'] = true;
        }

        if (!empty($issues)) {
            foreach ($issues as $issue => $true) {
                if (isset($report[$issue])) {
                    $report[$issue][] = [
                        'id' => $product_id,
                        'name' => $product->post_title,
                        'type' => $wc_product->get_type(),
                    ];
                }
            }
        } else {
            $report['ok']++;
        }
    }

    return $report;
}

/**
 * Repair product with missing pricing
 */
function cw_cj_repair_product_pricing($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error('invalid_product', 'Product not found');
    }

    $cj_product_id = get_post_meta($product_id, '_cj_product_id', true);
    if (empty($cj_product_id)) {
        return new WP_Error('no_cj_id', 'Product has no CJ ID');
    }

    // Try to fetch pricing from CJ
    if (!class_exists('CJ_Dropshipping')) {
        require_once dirname(__FILE__) . '/class-cj-dropshipping.php';
    }

    $cj = cw_cj_dropshipping();
    $details = $cj->get_product_details($cj_product_id, false);

    if (empty($details)) {
        return new WP_Error('fetch_failed', 'Could not fetch product details from CJ');
    }

    $base_price = $details['sellPrice'] ?? $details['price'] ?? 0;
    if (!$base_price) {
        return new WP_Error('no_price', 'CJ product has no pricing info');
    }

    $markup = 0.5; // Default markup
    $price = round($base_price * (1 + $markup), 2);

    if ($product->is_type('variable')) {
        $product->set_regular_price((string) $price);
        $product->save();

        // Also set prices on variations
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation->get_price()) {
                $variation->set_regular_price((string) $price);
                $variation->save();
            }
        }
    } else {
        $product->set_regular_price((string) $price);
        $product->save();
    }

    return true;
}

/**
 * Repair product with missing variations
 */
function cw_cj_repair_missing_variations($product_id) {
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) {
        return new WP_Error('invalid', 'Not a variable product');
    }

    $cj_product_id = get_post_meta($product_id, '_cj_product_id', true);
    if (empty($cj_product_id)) {
        return new WP_Error('no_cj_id', 'Product has no CJ ID');
    }

    if (!class_exists('CJ_Dropshipping')) {
        require_once dirname(__FILE__) . '/class-cj-dropshipping.php';
    }

    $cj = cw_cj_dropshipping();
    $variants = $cj->get_variants($cj_product_id);

    if (empty($variants)) {
        return new WP_Error('no_variants', 'Could not fetch variants from CJ');
    }

    // Remove old variations
    wp_delete_post($product_id, false); // Don't delete, just trash
    wp_untrash_post($product_id);

    foreach ($product->get_children() as $var_id) {
        wp_delete_post($var_id, true); // Force delete variations
    }

    // Re-create variations
    $details = $cj->get_product_details($cj_product_id, false);
    require_once dirname(__FILE__) . '/cj-variable-products.php';

    $product_data = [
        'id' => $cj_product_id,
        'productName' => $product->get_name(),
        'description' => $product->get_description(),
    ];

    $markup = 0.5;
    return cw_cj_create_variable_product($product_data, $variants, $markup);
}

/**
 * Register AJAX endpoint for health check
 */
add_action('wp_ajax_cw_cj_check_health', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    check_ajax_referer('cw_cj_import', 'cw_cj_import_nonce');

    $report = cw_cj_check_product_integrity();
    wp_send_json_success($report);
});

/**
 * Register AJAX endpoint for repair
 */
add_action('wp_ajax_cw_cj_repair_product', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    check_ajax_referer('cw_cj_import', 'cw_cj_import_nonce');

    $product_id = intval($_POST['product_id'] ?? 0);
    $repair_type = sanitize_text_field($_POST['repair_type'] ?? '');

    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID']);
    }

    $result = null;
    switch ($repair_type) {
        case 'pricing':
            $result = cw_cj_repair_product_pricing($product_id);
            break;
        case 'variations':
            $result = cw_cj_repair_missing_variations($product_id);
            break;
        default:
            wp_send_json_error(['message' => 'Unknown repair type']);
    }

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'Product repaired successfully']);
});

<?php
/**
 * CJ variable product creation helpers
 *
 * Creates WooCommerce variable products from CJ data and
 * ensures CJ IDs are stored on parent and variations.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Parse CJ variant attributes into key/value pairs.
 *
 * @param array $variant CJ variant data
 * @return array<string, string>
 */
function cw_cj_parse_variant_attributes($variant) {
    $raw = $variant['variantName'] ?? $variant['variantNameEn'] ?? $variant['variantKey'] ?? $variant['variantKeyEn'] ?? '';
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[;|]/', $raw);
    $attrs = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }

        if (strpos($part, ':') !== false) {
            $pair = array_map('trim', explode(':', $part, 2));
        } elseif (strpos($part, '=') !== false) {
            $pair = array_map('trim', explode('=', $part, 2));
        } else {
            $pair = [];
        }

        if (count($pair) === 2 && $pair[0] !== '' && $pair[1] !== '') {
            $attrs[$pair[0]] = $pair[1];
        }
    }

    if (empty($attrs)) {
        $attrs['Option'] = $raw;
    }

    return $attrs;
}

/**
 * Create a WooCommerce variable product from CJ data.
 *
 * @param array $product CJ product data
 * @param array $variants CJ variants
 * @param float $markup Markup rate (0.5 = 50%)
 * @param int $category_id Optional category ID
 * @return int|WP_Error Product ID or error
 */
function cw_cj_create_variable_product($product, $variants, $markup = 0.5, $category_id = 0) {
    if (!class_exists('WC_Product_Variable')) {
        return new WP_Error('cj_no_wc', 'WooCommerce is not available.');
    }

    if (empty($product) || empty($variants)) {
        return new WP_Error('cj_invalid_data', 'Missing product or variant data.');
    }

    $product_name = $product['nameEn'] ?? $product['productNameEn'] ?? $product['productName'] ?? 'CJ Product';
    $description = $product['productDescribeEn'] ?? $product['description'] ?? '';
    $cj_product_id = $product['id'] ?? $product['pid'] ?? '';

    $wc_product = new WC_Product_Variable();
    $wc_product->set_name($product_name);
    $wc_product->set_description($description);
    $wc_product->set_status('publish');
    $wc_product->set_catalog_visibility('visible');

    if (!empty($category_id)) {
        $wc_product->set_category_ids([$category_id]);
    }

    $product_id = $wc_product->save();

    if (!$product_id) {
        return new WP_Error('cj_create_failed', 'Failed to create WooCommerce product.');
    }

    if (!empty($cj_product_id)) {
        update_post_meta($product_id, '_cj_product_id', $cj_product_id);
    }

    // Build attributes for variations
    $all_attributes = [];
    $variant_attributes = [];

    foreach ($variants as $index => $variant) {
        $attrs = cw_cj_parse_variant_attributes($variant);

        if (empty($attrs)) {
            $fallback = $variant['variantName'] ?? $variant['variantKey'] ?? $variant['vid'] ?? 'Default';
            $attrs = ['Option' => $fallback];
        }

        $variant_attributes[$index] = $attrs;

        foreach ($attrs as $name => $value) {
            if (!isset($all_attributes[$name])) {
                $all_attributes[$name] = [];
            }
            $all_attributes[$name][] = $value;
        }
    }

    $product_attributes = [];
    foreach ($all_attributes as $name => $values) {
        $attribute = new WC_Product_Attribute();
        $attribute->set_name($name);
        $attribute->set_options(array_values(array_unique($values)));
        $attribute->set_visible(true);
        $attribute->set_variation(true);
        $product_attributes[] = $attribute;
    }

    $wc_product->set_attributes($product_attributes);
    $wc_product->save();

    // Create variations
    foreach ($variants as $index => $variant) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);

        $base_price = $variant['sellPrice'] ?? $variant['variantSellPrice'] ?? $variant['price'] ?? $variant['originalPrice'] ?? 0;
        $base_price = is_numeric($base_price) ? (float) $base_price : 0.0;
        $price = $base_price > 0 ? round($base_price * (1 + (float) $markup), 2) : 0;

        if ($price > 0) {
            $variation->set_regular_price((string) $price);
        }

        $variation_attrs = [];
        $attrs = $variant_attributes[$index] ?? [];
        foreach ($attrs as $name => $value) {
            $slug = sanitize_title($name);
            $variation_attrs['attribute_' . $slug] = $value;
        }

        $variation->set_attributes($variation_attrs);

        $cj_variant_id = $variant['vid'] ?? $variant['variantId'] ?? $variant['id'] ?? '';
        if (!empty($cj_product_id)) {
            $variation->update_meta_data('_cj_product_id', $cj_product_id);
        }
        if (!empty($cj_variant_id)) {
            $variation->update_meta_data('_cj_variant_id', $cj_variant_id);
        }

        $variation->save();
    }

    return $product_id;
}

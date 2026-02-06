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
 * Common color names for detection.
 *
 * @return array
 */
function cw_cj_get_common_colors() {
    return [
        'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'black',
        'white', 'gray', 'grey', 'brown', 'gold', 'silver', 'beige', 'tan',
        'navy', 'maroon', 'burgundy', 'rose', 'teal', 'turquoise', 'cyan',
        'magenta', 'coral', 'salmon', 'khaki', 'olive', 'lime', 'mint',
    ];
}

/**
 * Check if a string appears to be a size value.
 *
 * @param string $value Value to check
 * @return bool
 */
function cw_cj_is_size_value($value) {
    $value = strtolower(trim($value));
    
    // Common size patterns
    if (preg_match('/^(xs|s|m|l|xl|xxl|xxxl|one\s*size)$/', $value)) {
        return true;
    }
    // Numeric sizes with units: 2000ml, 500L, 32oz, 10cm, 5m, 10ft, etc.
    if (preg_match('/^\d+\.?\d*\s*(ml|l|oz|cm|mm|m|inch|in|ft|\"|\')?$/i', $value)) {
        return true;
    }
    // Dimension sizes: 10x20, 5x7x8
    if (preg_match('/^\d+x\d+/', $value)) {
        return true;
    }
    return false;
}

/**
 * Check if a string appears to be a color value.
 *
 * @param string $value Value to check
 * @return bool
 */
function cw_cj_is_color_value($value) {
    $value = strtolower(trim($value));
    $colors = cw_cj_get_common_colors();
    
    foreach ($colors as $color) {
        if (stripos($value, $color) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Try to auto-split a variant string into Color and Size.
 *
 * @param string $raw Raw variant string
 * @return array Parsed attributes
 */
function cw_cj_auto_split_attributes($raw) {
    $raw = trim($raw);
    if (empty($raw)) {
        return [];
    }
    
    // Already structured (Color: value; Size: value)
    if (preg_match('/color|size|length|width/i', $raw)) {
        return [];
    }
    
    // Try splitting by common delimiters
    $parts = preg_split('/[\s,;|\-]/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    $parts = array_map('trim', $parts);
    $parts = array_filter($parts);
    
    if (empty($parts)) {
        return [];
    }
    
    error_log('[CJ Auto-Split] Raw: "' . $raw . '" → Parts: ' . json_encode($parts));
    
    $attrs = [];
    $color = null;
    $size = null;
    
    foreach ($parts as $part) {
        $is_color = cw_cj_is_color_value($part);
        $is_size = cw_cj_is_size_value($part);
        error_log('[CJ Auto-Split] Testing "' . $part . '" → isColor: ' . ($is_color ? 'yes' : 'no') . ', isSize: ' . ($is_size ? 'yes' : 'no'));
        
        if ($is_color && !$color) {
            $color = $part;
        } elseif ($is_size && !$size) {
            $size = $part;
        }
    }
    
    if ($color) {
        $attrs['Color'] = $color;
    }
    if ($size) {
        $attrs['Size'] = $size;
    }
    
    // If we found Color and Size, return them
    if (!empty($attrs)) {
        error_log('[CJ Auto-Split] Result: ' . json_encode($attrs));
        return $attrs;
    }
    
    // If only 2 parts and both look like attributes, try to guess
    if (count($parts) === 2) {
        if (cw_cj_is_color_value($parts[0])) {
            error_log('[CJ Auto-Split] Guessing: ' . json_encode(['Color' => $parts[0], 'Size' => $parts[1]]));
            return ['Color' => $parts[0], 'Size' => $parts[1]];
        } elseif (cw_cj_is_color_value($parts[1])) {
            error_log('[CJ Auto-Split] Guessing: ' . json_encode(['Size' => $parts[0], 'Color' => $parts[1]]));
            return ['Size' => $parts[0], 'Color' => $parts[1]];
        }
    }
    
    return [];
}

/**
 * Parse CJ variant attributes into key/value pairs.
 *
 * @param array $variant CJ variant data
 * @return array<string, string>
 */
function cw_cj_parse_variant_attributes($variant) {
    if (!empty($variant['variantProperty']) && is_array($variant['variantProperty'])) {
        $attrs = [];

        foreach ($variant['variantProperty'] as $prop) {
            if (!is_array($prop)) {
                continue;
            }

            $name = $prop['propertyName'] ?? $prop['propertyNameEn'] ?? $prop['name'] ?? $prop['attrName'] ?? '';
            $value = $prop['propertyValue'] ?? $prop['propertyValueEn'] ?? $prop['value'] ?? $prop['attrValue'] ?? '';

            $name = is_string($name) ? trim($name) : '';
            $value = is_string($value) ? trim($value) : '';

            if ($name !== '' && $value !== '') {
                $attrs[$name] = $value;
            }
        }

        if (!empty($attrs)) {
            return $attrs;
        }
    }

    $raw = $variant['variantName'] ?? $variant['variantNameEn'] ?? $variant['variantKey'] ?? $variant['variantKeyEn'] ?? '';
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw === '') {
        return [];
    }

    // Try explicit key:value pairs first
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

    if (!empty($attrs)) {
        return $attrs;
    }
    
    // Try auto-split Color/Size
    $auto_attrs = cw_cj_auto_split_attributes($raw);
    if (!empty($auto_attrs)) {
        error_log('[CJ Import] Auto-split detected from "' . $raw . '": ' . json_encode($auto_attrs));
        return $auto_attrs;
    }

    // Fallback: single Option attribute
    error_log('[CJ Import] No attributes detected for variant "' . $raw . '" - using fallback Option');
    return ['Option' => $raw];
}

/**
 * Extract a usable variant image URL from CJ data.
 *
 * @param array $variant CJ variant data
 * @return string
 */
function cw_cj_get_variant_image_url($variant) {
    $keys = [
        'variantImage',
        'variantImageUrl',
        'variantImg',
        'image',
        'imageUrl',
        'img',
        'variantImageThumb',
    ];

    foreach ($keys as $key) {
        if (!empty($variant[$key]) && is_string($variant[$key])) {
            return $variant[$key];
        }
    }

    return '';
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
        
        error_log('[CJ Import] Attribute "' . $name . '" created with ' . count(array_unique($values)) . ' values: ' . implode(', ', array_unique($values)));
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

        $sku = $variant['variantSku'] ?? $variant['sku'] ?? $variant['SKU'] ?? '';
        if (!empty($sku)) {
            $variation->set_sku($sku);
        }

        $cj_variant_id = $variant['vid'] ?? $variant['variantId'] ?? $variant['id'] ?? '';
        if (!empty($cj_product_id)) {
            $variation->update_meta_data('_cj_product_id', $cj_product_id);
        }
        if (!empty($cj_variant_id)) {
            $variation->update_meta_data('_cj_variant_id', $cj_variant_id);
        }

        $variant_image_url = cw_cj_get_variant_image_url($variant);
        if (!empty($variant_image_url)) {
            $variation->update_meta_data('_cj_variant_image_url', $variant_image_url);

            // Try to attach variation image for WooCommerce compatibility
            if (function_exists('cw_cj_sideload_image')) {
                $variant_img_id = cw_cj_sideload_image($variant_image_url, $product_id, $product_name, true);
                if (!is_wp_error($variant_img_id)) {
                    $variation->set_image_id($variant_img_id);
                }
            }
        }

        $variation->save();
    }

    return $product_id;
}

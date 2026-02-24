<?php
/**
 * CJ Product Varieties Admin Manager
 * 
 * Allows admins to manually add varieties (colors, sizes, prices, images)
 * to imported CJ products through a custom metabox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register metabox for product varieties
 */
add_action('add_meta_boxes', 'cw_cj_register_varieties_metabox');
function cw_cj_register_varieties_metabox() {
    if (!function_exists('wc_get_product')) {
        return;
    }
    
    add_meta_box(
        'cw_cj_product_varieties',
        '🎨 CJ Product Varieties & Pricing',
        'cw_cj_render_varieties_metabox',
        'product',
        'normal',
        'high'
    );
}

/**
 * Render the varieties metabox
 */
function cw_cj_render_varieties_metabox($post) {
    $product_id = $post->ID;
    $varieties = get_post_meta($product_id, '_cj_varieties', true);
    
    if (!is_array($varieties)) {
        $varieties = [];
    }
    
    wp_nonce_field('cw_cj_varieties_nonce', 'cw_cj_varieties_nonce_field');
    
    ?>
    <div id="cw-cj-varieties" style="padding: 15px; background: #f9f9f9;">
        
        <!-- Instructions -->
        <div style="margin-bottom: 20px; padding: 12px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 3px;">
            <p style="margin: 0;"><strong>📝 How to use:</strong></p>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #555;">
                1. Click <strong>"+ Add Variety"</strong> below<br>
                2. Upload an image for this variety (shows color/variant)<br>
                3. Enter color/variant name (e.g., "Red", "Size L", "Blue XL")<br>
                4. Set the price for this specific variety<br>
                5. Repeat for all varieties<br>
                6. On the product page, customers click images to see prices
            </p>
        </div>
        
        <!-- Varieties Container -->
        <div id="cw-cj-varieties-list">
            <?php
            if (!empty($varieties)) {
                foreach ($varieties as $index => $variety) {
                    cw_cj_render_variety_row($product_id, $index, $variety);
                }
            }
            ?>
        </div>
        
        <!-- Add Button -->
        <button type="button" id="cw-cj-add-variety-btn" class="button button-primary" style="margin-top: 15px;">
            + Add Variety
        </button>
        
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let varietyCount = <?php echo count($varieties); ?>;
        
        // Add variety handler
        $('#cw-cj-add-variety-btn').on('click', function(e) {
            e.preventDefault();
            
            const html = `
            <div class="cw-cj-variety-row" style="margin-bottom: 20px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px;">
                <input type="hidden" name="cw_cj_variety_index[]" value="${varietyCount}">
                
                <!-- Image Upload -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Variety Image:</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <img class="cw-cj-variety-preview" src="" alt="Preview" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 3px; display: none;">
                        <input type="hidden" class="cw-cj-variety-image-id" name="cw_cj_variety_image_id[${varietyCount}]" value="">
                        <button type="button" class="button cw-cj-variety-upload-btn" data-index="${varietyCount}">
                            Upload Image
                        </button>
                        <button type="button" class="button cw-cj-variety-remove-image-btn" data-index="${varietyCount}" style="display: none;">
                            Remove Image
                        </button>
                    </div>
                </div>
                
                <!-- Color/Variant Name -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Color/Variant Name:</label>
                    <input type="text" class="cw-cj-variety-name" name="cw_cj_variety_name[${varietyCount}]" placeholder="e.g., Red, Size L, Blue XL" value="" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                    <small style="color: #666;">What customers will see (e.g., "Red", "Size M", "Black with Gold")</small>
                </div>
                
                <!-- Price -->
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Price ($):</label>
                    <input type="number" class="cw-cj-variety-price" name="cw_cj_variety_price[${varietyCount}]" placeholder="29.99" value="" step="0.01" min="0" style="width: 150px; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
                </div>
                
                <!-- Delete Button -->
                <button type="button" class="button button-link-delete cw-cj-variety-delete-btn" data-index="${varietyCount}" style="color: #a00;">
                    Delete This Variety
                </button>
            </div>
            `;
            
            $('#cw-cj-varieties-list').append(html);
            varietyCount++;
        });
        
        // Image upload handler
        $(document).on('click', '.cw-cj-variety-upload-btn', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            const frame = wp.media({
                title: 'Select Variety Image',
                button: { text: 'Select' },
                multiple: false
            });
            
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $('.cw-cj-variety-image-id').filter('[name="cw_cj_variety_image_id[' + index + ']"]').val(attachment.id);
                $('.cw-cj-variety-preview').filter('[style*="width: 80px"]').eq(index).attr('src', attachment.url).show();
                $('[data-index="' + index + '"].cw-cj-variety-remove-image-btn').show();
                $('[data-index="' + index + '"].cw-cj-variety-upload-btn').text('Change Image');
            });
            
            frame.open();
        });
        
        // Remove image handler
        $(document).on('click', '.cw-cj-variety-remove-image-btn', function(e) {
            e.preventDefault();
            const index = $(this).data('index');
            $('.cw-cj-variety-image-id').filter('[name="cw_cj_variety_image_id[' + index + ']"]').val('');
            $('.cw-cj-variety-preview').filter('[style*="width: 80px"]').eq(index).hide();
            $(this).hide();
            $('[data-index="' + index + '"].cw-cj-variety-upload-btn').text('Upload Image');
        });
        
        // Delete variety handler
        $(document).on('click', '.cw-cj-variety-delete-btn', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this variety?')) {
                $(this).closest('.cw-cj-variety-row').remove();
            }
        });
    });
    </script>
    
    <style>
        .cw-cj-variety-row {
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <?php
}

/**
 * Helper: Render a single variety row
 */
function cw_cj_render_variety_row($product_id, $index, $variety) {
    $image_id = $variety['image_id'] ?? 0;
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    $name = $variety['name'] ?? '';
    $price = $variety['price'] ?? '';
    ?>
    <div class="cw-cj-variety-row" style="margin-bottom: 20px; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 5px;">
        <input type="hidden" name="cw_cj_variety_index[]" value="<?php echo $index; ?>">
        
        <!-- Image Upload -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Variety Image:</label>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <img class="cw-cj-variety-preview" src="<?php echo esc_url($image_url); ?>" alt="Preview" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 3px; <?php echo $image_id ? '' : 'display: none;'; ?>">
                <input type="hidden" class="cw-cj-variety-image-id" name="cw_cj_variety_image_id[<?php echo $index; ?>]" value="<?php echo esc_attr($image_id); ?>">
                <button type="button" class="button cw-cj-variety-upload-btn" data-index="<?php echo $index; ?>">
                    <?php echo $image_id ? 'Change Image' : 'Upload Image'; ?>
                </button>
                <?php if ($image_id): ?>
                <button type="button" class="button cw-cj-variety-remove-image-btn" data-index="<?php echo $index; ?>">
                    Remove Image
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Color/Variant Name -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Color/Variant Name:</label>
            <input type="text" class="cw-cj-variety-name" name="cw_cj_variety_name[<?php echo $index; ?>]" placeholder="e.g., Red, Size L, Blue XL" value="<?php echo esc_attr($name); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            <small style="color: #666;">What customers will see</small>
        </div>
        
        <!-- Price -->
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Price ($):</label>
            <input type="number" class="cw-cj-variety-price" name="cw_cj_variety_price[<?php echo $index; ?>]" placeholder="29.99" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" style="width: 150px; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
        </div>
        
        <!-- Delete Button -->
        <button type="button" class="button button-link-delete cw-cj-variety-delete-btn" data-index="<?php echo $index; ?>" style="color: #a00;">
            Delete This Variety
        </button>
    </div>
    <?php
}

/**
 * Save varieties when product is saved
 */
add_action('save_post_product', 'cw_cj_save_varieties', 20, 1);
function cw_cj_save_varieties($post_id) {
    // Security check
    if (!isset($_POST['cw_cj_varieties_nonce_field'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['cw_cj_varieties_nonce_field'], 'cw_cj_varieties_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    // Get all variety data
    $indices = isset($_POST['cw_cj_variety_index']) ? (array) $_POST['cw_cj_variety_index'] : [];
    $image_ids = isset($_POST['cw_cj_variety_image_id']) ? (array) $_POST['cw_cj_variety_image_id'] : [];
    $names = isset($_POST['cw_cj_variety_name']) ? (array) $_POST['cw_cj_variety_name'] : [];
    $prices = isset($_POST['cw_cj_variety_price']) ? (array) $_POST['cw_cj_variety_price'] : [];
    
    $varieties = [];
    
    foreach ($indices as $index) {
        $index = intval($index);
        $image_id = isset($image_ids[$index]) ? intval($image_ids[$index]) : 0;
        $name = isset($names[$index]) ? sanitize_text_field($names[$index]) : '';
        $price = isset($prices[$index]) ? floatval($prices[$index]) : 0;
        
        // Only save if variety has either image or name or price
        if ($image_id || $name || $price > 0) {
            $variety = [
                'image_id' => $image_id,
                'name' => $name,
                'price' => $price,
            ];
            
            if ($image_id) {
                $variety['image_url'] = wp_get_attachment_url($image_id);
            }
            
            $varieties[$index] = $variety;
        }
    }
    
    // Save to post meta
    if (!empty($varieties)) {
        update_post_meta($post_id, '_cj_varieties', $varieties);
        // Also set lowest price as product regular price
        $prices = array_filter(array_column($varieties, 'price'));
        if (!empty($prices)) {
            $min_price = min($prices);
            $product = wc_get_product($post_id);
            if ($product) {
                $product->set_regular_price((string) $min_price);
                $product->save();
            }
        }
    } else {
        delete_post_meta($post_id, '_cj_varieties');
    }
}

?>

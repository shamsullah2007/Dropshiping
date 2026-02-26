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
 * Admin notice for successful saves
 */
add_action('admin_notices', 'cw_cj_varieties_admin_notices');
function cw_cj_varieties_admin_notices() {
    if (get_transient('cw_cj_varieties_saved')) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Delivery details and varieties saved successfully.</strong></p></div>';
        delete_transient('cw_cj_varieties_saved');
    }
}

/**
 * Enqueue admin scripts and styles
 */
add_action('admin_enqueue_scripts', 'cw_cj_varieties_enqueue_admin_scripts');
function cw_cj_varieties_enqueue_admin_scripts($hook_suffix) {
    global $post;
    
    // Only load on product edit page
    if ($hook_suffix !== 'post.php' || !isset($post) || 'product' !== get_post_type($post)) {
        return;
    }
    
    // Use theme directory path for reliability
    $script_path = get_template_directory() . '/custom_woocommerce/js/cj-varieties-admin.js';
    $script_url = get_template_directory_uri() . '/custom_woocommerce/js/cj-varieties-admin.js';
    
    // Only enqueue if file exists
    if (file_exists($script_path)) {
        wp_enqueue_script('cw-cj-varieties-admin', $script_url, ['jquery'], filemtime($script_path), true);
        wp_localize_script('cw-cj-varieties-admin', 'cwCJVarietiesAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cw_cj_varieties_admin_nonce'),
            'product_id' => $post->ID,
        ]);
        
        error_log("CJ Varieties Admin Script Enqueued - Product: {$post->ID}");
    } else {
        error_log("CJ Varieties Admin Script NOT FOUND at: $script_path");
    }
}

/**
 * AJAX: Save delivery details from admin form
 */
add_action('wp_ajax_cw_save_delivery_details_admin', 'cw_ajax_save_delivery_details_admin');
function cw_ajax_save_delivery_details_admin() {
    // Verify nonce
    check_ajax_referer('cw_cj_varieties_admin_nonce', 'nonce');
    
    // Check permissions
    if (!current_user_can('edit_post', intval($_POST['product_id']))) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $product_id = intval($_POST['product_id']);
    $delivery_charges = sanitize_text_field($_POST['delivery_charges'] ?? '');
    $delivery_eta = sanitize_text_field($_POST['delivery_eta'] ?? '');
    
    // Save data
    if ($delivery_charges !== '') {
        update_post_meta($product_id, '_cj_delivery_charges', $delivery_charges);
    } else {
        delete_post_meta($product_id, '_cj_delivery_charges');
    }
    
    if ($delivery_eta !== '') {
        update_post_meta($product_id, '_cj_delivery_eta', $delivery_eta);
    } else {
        delete_post_meta($product_id, '_cj_delivery_eta');
    }
    
    error_log("AJAX Save - Product ID: $product_id, Charges: $delivery_charges, ETA: $delivery_eta");
    
    wp_send_json_success([
        'message' => 'Delivery details saved successfully',
        'delivery_charges' => $delivery_charges,
        'delivery_eta' => $delivery_eta,
    ]);
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
    $delivery_charges = get_post_meta($product_id, '_cj_delivery_charges', true);
    $delivery_eta = get_post_meta($product_id, '_cj_delivery_eta', true);
    
    if (!is_array($varieties)) {
        $varieties = [];
    }
    
    wp_nonce_field('cw_cj_varieties_nonce', 'cw_cj_varieties_nonce_field');
    
    // Add AJAX nonce as hidden field for inline script
    $ajax_nonce = wp_create_nonce('cw_cj_varieties_admin_nonce');
    echo '<input type="hidden" id="cw_delivery_ajax_nonce" value="' . esc_attr($ajax_nonce) . '">';
    
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
        
        <!-- Delivery Details -->
        <div style="margin-bottom: 20px; padding: 12px; background: #fff; border: 1px solid #e0e0e0; border-radius: 5px;">
            <div style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Delivery Charges:</label>
                <input type="text" name="cw_cj_delivery_charges" value="<?php echo esc_attr($delivery_charges); ?>" placeholder="e.g., $5.99 or Free" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">ETA:</label>
                <input type="text" name="cw_cj_delivery_eta" value="<?php echo esc_attr($delivery_eta); ?>" placeholder="e.g., 7-12 business days" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
            </div>
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
        
        // ============================================================================
        // INLINE AUTO-SAVE FOR DELIVERY DETAILS (Fallback if external JS doesn't load)
        // ============================================================================
        
        let deliveryAutoSaveTimeout = null;
        const deliveryAjaxNonce = jQuery('#cw_delivery_ajax_nonce').val();
        const deliveryProductId = jQuery('input[name="post_ID"]').val();
        
        // Monitor delivery fields for changes
        $(document).on('change input', 'input[name="cw_cj_delivery_charges"], input[name="cw_cj_delivery_eta"]', function() {
            clearTimeout(deliveryAutoSaveTimeout);
            
            // Show status that changes were detected
            $('input[name="cw_cj_delivery_charges"], input[name="cw_cj_delivery_eta"]').css('border-color', '#ffc107');
            
            // Auto-save after 2 seconds
            deliveryAutoSaveTimeout = setTimeout(function() {
                const charges = $('input[name="cw_cj_delivery_charges"]').val().trim();
                const eta = $('input[name="cw_cj_delivery_eta"]').val().trim();
                
                console.log('Attempting to save - Charges:', charges, 'ETA:', eta);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cw_save_delivery_details_admin',
                        nonce: deliveryAjaxNonce,
                        product_id: deliveryProductId,
                        delivery_charges: charges,
                        delivery_eta: eta
                    },
                    success: function(response) {
                        console.log('AJAX Response:', response);
                        
                        // Green border to indicate saved
                        $('input[name="cw_cj_delivery_charges"], input[name="cw_cj_delivery_eta"]').css('border-color', '#28a745');
                        
                        // Show temporary message
                        showDeliveryNotice('✓ Delivery details saved automatically');
                        
                        // Reset color after 2 seconds
                        setTimeout(function() {
                            $('input[name="cw_cj_delivery_charges"], input[name="cw_cj_delivery_eta"]').css('border-color', '#ddd');
                        }, 2000);
                    },
                    error: function(error) {
                        console.error('AJAX Error:', error);
                        showDeliveryNotice('Error saving delivery details', 'error');
                    }
                });
            }, 2000);
        });
        
        // Show/hide delivery notice
        function showDeliveryNotice(message, type) {
            type = type || 'success';
            const bgColor = type === 'success' ? '#4CAF50' : '#f44336';
            
            $('.cw-delivery-inline-notice').remove();
            
            const notice = $(`
                <div class="cw-delivery-inline-notice" style="
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: ${bgColor};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 4px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    z-index: 99999;
                    font-size: 13px;
                    font-weight: bold;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append(notice);
            
            setTimeout(function() {
                notice.fadeOut(300, function() { $(this).remove(); });
            }, 3000);
        }
        
        console.log('✓ Delivery Details Auto-Save: ACTIVE (Inline)');
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
add_action('save_post_product', 'cw_cj_save_varieties', 10, 1);
function cw_cj_save_varieties($post_id) {
    // Skip if no nonce field
    if (!isset($_POST['cw_cj_varieties_nonce_field'])) {
        return;
    }
    
    // Verify nonce
    $nonce = sanitize_text_field($_POST['cw_cj_varieties_nonce_field']);
    if (!wp_verify_nonce($nonce, 'cw_cj_varieties_nonce')) {
        // Nonce failed - log for debugging
        error_log('Nonce verification failed for delivery details save on product ' . $post_id);
        return;
    }
    
    // Skip autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save delivery details
    $delivery_charges = isset($_POST['cw_cj_delivery_charges']) ? sanitize_text_field($_POST['cw_cj_delivery_charges']) : '';
    $delivery_eta = isset($_POST['cw_cj_delivery_eta']) ? sanitize_text_field($_POST['cw_cj_delivery_eta']) : '';

    // Always save values (even if empty, to ensure consistency)
    if ($delivery_charges !== '') {
        update_post_meta($post_id, '_cj_delivery_charges', $delivery_charges);
    } else {
        delete_post_meta($post_id, '_cj_delivery_charges');
    }

    if ($delivery_eta !== '') {
        update_post_meta($post_id, '_cj_delivery_eta', $delivery_eta);
    } else {
        delete_post_meta($post_id, '_cj_delivery_eta');
    }
    
    // Log successful save
    error_log('Delivery details saved - Charges: ' . $delivery_charges . ', ETA: ' . $delivery_eta);

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
    
    // Set transient to show success notice
    set_transient('cw_cj_varieties_saved', true, 30);
}

?>

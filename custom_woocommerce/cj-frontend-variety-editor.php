<?php
/**
 * Front-End Variety Editor (Admin Only)
 * Allows admins to edit product varieties directly from the product manager page
 */

// AJAX: Get product varieties for editing
add_action('wp_ajax_cw_get_product_varieties_edit', 'cw_ajax_get_product_varieties_edit');
function cw_ajax_get_product_varieties_edit() {
    check_ajax_referer('cw_variety_editor_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error('Invalid product ID');
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error('Product not found');
    }
    
    $varieties = get_post_meta($product_id, '_cj_varieties', true) ?: [];
    
    wp_send_json_success([
        'product_id' => $product_id,
        'product_name' => $product->get_name(),
        'varieties' => $varieties,
    ]);
}

// AJAX: Save variety (add or update)
add_action('wp_ajax_cw_save_variety_frontend', 'cw_ajax_save_variety_frontend');
function cw_ajax_save_variety_frontend() {
    check_ajax_referer('cw_variety_editor_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $variety_index = intval($_POST['variety_index'] ?? -1);
    $color_name = sanitize_text_field($_POST['color_name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image_id = intval($_POST['image_id'] ?? 0);
    
    if (!$product_id || !$color_name) {
        wp_send_json_error('Missing required fields');
    }
    
    $varieties = get_post_meta($product_id, '_cj_varieties', true) ?: [];
    
    // Create or update variety
    $variety_data = [
        'color_name' => $color_name,
        'price' => $price,
        'image_id' => $image_id,
    ];
    
    if ($variety_index >= 0 && isset($varieties[$variety_index])) {
        $varieties[$variety_index] = $variety_data;
    } else {
        $varieties[] = $variety_data;
    }
    
    update_post_meta($product_id, '_cj_varieties', $varieties);
    
    wp_send_json_success([
        'message' => 'Variety saved successfully',
        'varieties' => $varieties,
    ]);
}

// AJAX: Delete variety
add_action('wp_ajax_cw_delete_variety_frontend', 'cw_ajax_delete_variety_frontend');
function cw_ajax_delete_variety_frontend() {
    check_ajax_referer('cw_variety_editor_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $variety_index = intval($_POST['variety_index'] ?? -1);
    
    if (!$product_id || $variety_index < 0) {
        wp_send_json_error('Invalid data');
    }
    
    $varieties = get_post_meta($product_id, '_cj_varieties', true) ?: [];
    
    if (isset($varieties[$variety_index])) {
        unset($varieties[$variety_index]);
        $varieties = array_values($varieties); // Re-index array
    }
    
    update_post_meta($product_id, '_cj_varieties', $varieties);
    
    wp_send_json_success([
        'message' => 'Variety deleted successfully',
        'varieties' => $varieties,
    ]);
}

// Enqueue frontend variety editor script and styles
add_action('wp_enqueue_scripts', 'cw_enqueue_variety_editor_assets');
function cw_enqueue_variety_editor_assets() {
    if (!is_page_template('template-product-manager.php')) {
        return;
    }
    
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    wp_enqueue_script('cw-variety-editor', get_template_directory_uri() . '/assets/js/variety-editor-frontend.js', ['jquery'], time(), true);
    wp_enqueue_style('cw-variety-editor-frontend', get_template_directory_uri() . '/assets/css/variety-editor-frontend.css', [], time());
    
    wp_localize_script('cw-variety-editor', 'cwVarietyEditor', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cw_variety_editor_nonce'),
    ]);
}

// Render variety editor UI in product manager
add_action('wp_footer', 'cw_render_variety_editor_modal');
function cw_render_variety_editor_modal() {
    if (!is_page_template('template-product-manager.php')) {
        return;
    }
    
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    // Media uploader can be used for variety images
    wp_enqueue_media();
    ?>
    
    <div id="cwVarietyEditorModal" class="cw-variety-editor-modal" style="display: none;">
        <div class="cw-variety-editor-content">
            <div class="cw-variety-editor-header">
                <h2><?php esc_html_e('Edit Product Varieties', 'custom-woocommerce'); ?></h2>
                <button type="button" class="cw-close-editor" aria-label="<?php esc_attr_e('Close', 'custom-woocommerce'); ?>">×</button>
            </div>
            
            <div class="cw-variety-editor-body">
                <div id="cwVarietiesList" class="cw-varieties-list"></div>
                
                <button type="button" class="button button-primary" id="cwAddVarietyBtn">
                    <?php esc_html_e('+ Add Variety', 'custom-woocommerce'); ?>
                </button>
            </div>
            
            <div class="cw-variety-editor-footer">
                <p class="cw-auto-save-notice" style="display: none; color: #22863a;">
                    ✓ <?php esc_html_e('Changes saved automatically', 'custom-woocommerce'); ?>
                </p>
                <button type="button" class="button cw-close-editor">
                    <?php esc_html_e('Done', 'custom-woocommerce'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <?php
}

<?php
/**
 * Template Name: Product Manager
 */

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Enqueue scripts and styles
wp_enqueue_script('cw-product-manager', get_template_directory_uri() . '/assets/js/product-manager.js', ['jquery'], time(), true);
wp_enqueue_script('cw-variety-form', get_template_directory_uri() . '/assets/js/variety-form.js', ['jquery', 'media'], time(), true);
wp_enqueue_style('cw-product-manager', get_template_directory_uri() . '/assets/css/product-manager.css', [], time());
wp_enqueue_style('cw-variety-form', get_template_directory_uri() . '/assets/css/variety-form.css', [], time());
wp_enqueue_media();

get_header();
?>

<main id="primary" class="site-main product-manager-page">
    <div class="container">
        <h1><?php esc_html_e('Product Manager', 'custom-woocommerce'); ?></h1>
        
        <div class="pm-tabs">
            <button class="pm-tab-btn active" data-tab="all-products">
                <?php esc_html_e('All Products', 'custom-woocommerce'); ?>
            </button>
            <button class="pm-tab-btn" data-tab="add-single">
                <?php esc_html_e('Add Single Item', 'custom-woocommerce'); ?>
            </button>
            <button class="pm-tab-btn" data-tab="add-bulk">
                <?php esc_html_e('Add Bulk Items', 'custom-woocommerce'); ?>
            </button>
            <button class="pm-tab-btn" data-tab="edit-product" style="display: none;" id="edit-product-tab">
                <?php esc_html_e('Edit Product', 'custom-woocommerce'); ?>
            </button>
        </div>

        <!-- Tab 1: All Products -->
        <div class="pm-tab-content active" id="all-products">
            <div class="products-grid">
                <?php
                $all_products = wc_get_products([
                    'limit' => -1,
                    'status' => 'publish',
                ]);
                
                if (empty($all_products)) {
                    echo '<p>' . esc_html__('No products yet.', 'custom-woocommerce') . '</p>';
                } else {
                    foreach ($all_products as $product) :
                ?>
                    <div class="product-item">
                        <div class="product-image">
                            <?php echo $product->get_image('medium'); ?>
                        </div>
                        <div class="product-details">
                            <h3><?php echo esc_html($product->get_name()); ?></h3>
                            <p class="price"><?php echo $product->get_price_html(); ?></p>
                            <p class="sku"><?php echo esc_html__('SKU:', 'custom-woocommerce') . ' ' . esc_html($product->get_sku()); ?></p>
                            <div class="product-actions">
                                <button type="button" class="button button-outline edit-product-btn" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                                    <?php esc_html_e('Edit', 'custom-woocommerce'); ?>
                                </button>
                                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="button button-outline" target="_blank">
                                    <?php esc_html_e('View', 'custom-woocommerce'); ?>
                                </a>
                                <button type="button" class="button button-outline delete-product-btn" data-product-id="<?php echo esc_attr($product->get_id()); ?>" style="background: #ef4444; color: #fff;">
                                    <?php esc_html_e('Delete', 'custom-woocommerce'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php
                    endforeach;
                }
                ?>
            </div>
        </div>

        <!-- Tab 2: Add Single Item -->
        <div class="pm-tab-content" id="add-single">
            <?php echo do_shortcode('[cw_add_product_form]'); ?>
        </div>

        <!-- Tab 3: Edit Product -->
        <div class="pm-tab-content" id="edit-product">
            <div id="editProductContainer"></div>
        </div>

        <!-- Tab 4: Add Bulk Items -->
        <div class="pm-tab-content" id="add-bulk">
            <div class="bulk-upload-container">
                <?php wp_nonce_field('cw_add_bulk_product', 'cw_add_bulk_nonce'); ?>
                <script>
                    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                </script>
                <label for="bulk-images" class="bulk-upload-label">
                    <?php esc_html_e('Select Multiple Images', 'custom-woocommerce'); ?>
                </label>
                <input type="file" id="bulk-images" multiple accept="image/*">
                
                <div class="bulk-images-preview" id="bulkImagesPreview"></div>
                
                <div class="bulk-forms-container" id="bulkFormsContainer"></div>
                
                <button type="button" class="button button-accent" id="bulkAddAllBtn" style="display: none;">
                    <?php esc_html_e('Add All Products', 'custom-woocommerce'); ?>
                </button>
            </div>
        </div>
    </div>
</main>

<?php
get_footer();

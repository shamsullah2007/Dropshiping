<?php
/**
 * Template Name: Product Manager
 */

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_redirect(home_url('/login/'));
    exit;
}

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
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $product->get_id() . '&action=edit')); ?>" class="button button-outline">
                                    <?php esc_html_e('Edit', 'custom-woocommerce'); ?>
                                </a>
                                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="button button-outline">
                                    <?php esc_html_e('View', 'custom-woocommerce'); ?>
                                </a>
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

        <!-- Tab 3: Add Bulk Items -->
        <div class="pm-tab-content" id="add-bulk">
            <div class="bulk-upload-container">
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

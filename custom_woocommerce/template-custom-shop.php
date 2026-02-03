<?php
/**
 * Template Name: Custom Shop Page
 */

get_header();
?>

<main id="primary" class="site-main custom-shop-page">
    <section class="custom-shop-section">
        <div class="custom-shop-container">
            <div class="custom-shop-header">
                <h1><?php esc_html_e('Shop', 'custom-woocommerce'); ?></h1>
            </div>
            
            <?php echo do_shortcode('[custom_products_grid]'); ?>
        </div>
    </section>
</main>

<?php
get_footer();

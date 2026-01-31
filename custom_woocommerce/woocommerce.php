<?php
get_header();
?>
<main id="primary" class="site-main shop-page">
    <section class="products-section shop-section">
        <div class="container shop-layout">
            <aside class="shop-filters">
                <form class="filter-form" method="get" action="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
                    <div class="filter-group">
                        <h3><?php esc_html_e('Filter Products', 'custom-woocommerce'); ?></h3>
                        
                        <div class="filter-item">
                            <label for="min-price"><?php esc_html_e('Price Range', 'custom-woocommerce'); ?></label>
                            <div class="price-filter">
                                <input type="number" id="min-price" name="min_price" step="0.01" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? esc_attr($_GET['min_price']) : ''; ?>" />
                                <span class="to">-</span>
                                <input type="number" id="max-price" name="max_price" step="0.01" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? esc_attr($_GET['max_price']) : ''; ?>" />
                            </div>
                        </div>

                        <div class="filter-item">
                            <label for="rating"><?php esc_html_e('Minimum Rating', 'custom-woocommerce'); ?></label>
                            <select name="rating" id="rating">
                                <option value=""><?php esc_html_e('Any', 'custom-woocommerce'); ?></option>
                                <option value="4" <?php selected(isset($_GET['rating']) && '4' === $_GET['rating']); ?>>★★★★+ (4+)</option>
                                <option value="3" <?php selected(isset($_GET['rating']) && '3' === $_GET['rating']); ?>>★★★+ (3+)</option>
                                <option value="2" <?php selected(isset($_GET['rating']) && '2' === $_GET['rating']); ?>>★★+ (2+)</option>
                            </select>
                        </div>

                        <button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'custom-woocommerce'); ?></button>
                        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="button button-outline"><?php esc_html_e('Clear Filters', 'custom-woocommerce'); ?></a>
                    </div>
                </form>

                <?php if (is_active_sidebar('shop-filters')) : ?>
                    <div class="shop-widgets">
                        <?php dynamic_sidebar('shop-filters'); ?>
                    </div>
                <?php endif; ?>
            </aside>

            <section class="shop-content">
                <?php woocommerce_content(); ?>
            </section>
        </div>
    </section>
</main>
<?php
get_footer();

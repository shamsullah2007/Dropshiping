<?php
/**
 * Template Name: Home Page
 */

get_header();
?>

<main id="primary" class="site-main home-page">
    <!-- Hero Section -->
    <section class="hero-section" data-animate="fade-up">
        <div class="container">
            <h1><?php esc_html_e('Welcome to', 'custom-woocommerce'); ?> <?php bloginfo('name'); ?></h1>
            <p><?php esc_html_e('Discover amazing products at great prices', 'custom-woocommerce'); ?></p>
        </div>
    </section>

    <!-- Latest Products Section -->
    <section class="products-section latest-products" data-animate="fade-up">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Latest Products', 'custom-woocommerce'); ?></h2>
            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-prev" data-carousel="latest">‹</button>
                <div class="product-carousel" data-carousel-id="latest">
                    <div class="carousel-track">
                        <?php
                        $latest_products = wc_get_products([
                            'limit' => 12,
                            'orderby' => 'date',
                            'order' => 'DESC',
                            'status' => 'publish',
                        ]);
                        
                        // Debug logging
                        error_log("Latest Products Count: " . count($latest_products));
                        if (count($latest_products) === 0) {
                            error_log("No products found. Trying alternative query...");
                            $latest_products = wc_get_products([
                                'limit' => 12,
                                'orderby' => 'date',
                                'order' => 'DESC',
                            ]);
                            error_log("Alternative query count: " . count($latest_products));
                        }
                        
                        foreach ($latest_products as $product) :
                        ?>
                            <div class="carousel-item">
                                <div class="product-card">
                                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="product-image">
                                        <?php echo $product->get_image('medium'); ?>
                                    </a>
                                    <div class="product-info">
                                        <h3 class="product-title">
                                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a>
                                        </h3>
                                        <div class="product-price">
                                            <?php echo $product->get_price_html(); ?>
                                        </div>
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="button button-accent add-to-cart">
                                            <?php esc_html_e('Add to Cart', 'custom-woocommerce'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <button class="carousel-btn carousel-next" data-carousel="latest">›</button>
            </div>
        </div>
    </section>

    <!-- Trending Products Section -->
    <section class="products-section trending-products" data-animate="fade-up">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Trending Products', 'custom-woocommerce'); ?></h2>
            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-prev" data-carousel="trending">‹</button>
                <div class="product-carousel" data-carousel-id="trending">
                    <div class="carousel-track">
                        <?php
                        // Get products ordered by review count (most reviews = trending)
                        global $wpdb;
                        $products_with_reviews = $wpdb->get_results(
                            "SELECT p.ID, COUNT(c.comment_ID) as review_count
                            FROM {$wpdb->posts} p
                            LEFT JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID 
                                AND c.comment_approved = 1 
                                AND c.comment_type IN ('review', '')
                            WHERE p.post_type = 'product' 
                                AND p.post_status = 'publish'
                            GROUP BY p.ID
                            HAVING review_count > 0
                            ORDER BY review_count DESC
                            LIMIT 12"
                        );
                        
                        $trending_products = [];
                        if ($products_with_reviews) {
                            foreach ($products_with_reviews as $item) {
                                $product = wc_get_product($item->ID);
                                if ($product) {
                                    $trending_products[] = $product;
                                }
                            }
                        }
                        
                        // If no products with reviews, fall back to popular products
                        if (empty($trending_products)) {
                            $trending_products = wc_get_products([
                                'limit' => 12,
                                'orderby' => 'popularity',
                                'order' => 'DESC',
                                'status' => 'publish',
                            ]);
                        }
                        
                        foreach ($trending_products as $product) :
                        ?>
                            <div class="carousel-item">
                                <div class="product-card">
                                    <a href="<?php echo esc_url($product->get_permalink()); ?>" class="product-image">
                                        <?php echo $product->get_image('medium'); ?>
                                    </a>
                                    <div class="product-info">
                                        <h3 class="product-title">
                                            <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </a>
                                        </h3>
                                        <div class="product-price">
                                            <?php echo $product->get_price_html(); ?>
                                        </div>
                                        <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="button button-accent add-to-cart">
                                            <?php esc_html_e('Add to Cart', 'custom-woocommerce'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <button class="carousel-btn carousel-next" data-carousel="trending">›</button>
            </div>
        </div>
    </section>

    <!-- Customer Reviews Section -->
    <section class="reviews-section" data-animate="fade-up">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e('Customer Reviews', 'custom-woocommerce'); ?></h2>
            <div class="carousel-wrapper">
                <button class="carousel-btn carousel-prev" data-carousel="reviews">‹</button>
                <div class="reviews-carousel" data-carousel-id="reviews">
                    <div class="carousel-track">
                        <?php
                        // Use the helper function from functions.php
                        $reviews = custom_woocommerce_get_product_reviews(12);
                        
                        if (empty($reviews)) :
                        ?>
                            <div class="no-reviews-message">
                                <p><?php esc_html_e('No approved customer reviews yet. Be the first to leave a review!', 'custom-woocommerce'); ?></p>
                            </div>
                        <?php
                        endif;
                        
                        foreach ($reviews as $review) :
                            $rating = intval(get_comment_meta($review->comment_ID, 'rating', true));
                        ?>
                            <div class="carousel-item">
                                <div class="review-card">
                                    <div class="review-header">
                                        <img src="<?php echo esc_url(get_avatar_url($review->comment_author_email, ['size' => 64])); ?>" 
                                             alt="<?php echo esc_attr($review->comment_author); ?>" 
                                             class="review-avatar">
                                        <div class="review-meta">
                                            <h4 class="review-author"><?php echo esc_html($review->comment_author); ?></h4>
                                            <div class="review-stars">
                                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p><?php echo esc_html(wp_trim_words($review->comment_content, 20)); ?></p>
                                    </div>
                                    <div class="review-product">
                                        <a href="<?php echo esc_url(get_permalink($review->comment_post_ID)); ?>">
                                            <?php echo esc_html(get_the_title($review->comment_post_ID)); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="carousel-btn carousel-next" data-carousel="reviews">›</button>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

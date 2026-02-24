<?php
/**
 * CJ Product Varieties Frontend Display
 * 
 * Displays varieties on single product page with:
 * - Small image boxes for each variety
 * - Color/variant name above images
 * - Dynamic price display when variety is clicked
 * - Beautiful button-like UI for sizes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display varieties on single product page
 */
function cw_cj_display_product_varieties() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $varieties = get_post_meta($product_id, '_cj_varieties', true);
    
    if (!is_array($varieties) || empty($varieties)) {
        return;
    }
    
    wp_enqueue_style('cw-cj-varieties-frontend', get_theme_file_uri('assets/css/cj-varieties-frontend.css'), [], '1.0');
    ?>
    <div class="cw-cj-varieties-section" style="margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 8px;">
        <h3 style="margin-top: 0; margin-bottom: 15px; color: #333;">Choose Your Variety</h3>
        
        <!-- Varieties Grid -->
        <div class="cw-cj-varieties-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <?php foreach ($varieties as $index => $variety): ?>
                <?php
                $image_id = $variety['image_id'] ?? 0;
                $image_url = $variety['image_url'] ?? ($image_id ? wp_get_attachment_url($image_id) : '');
                $name = $variety['name'] ?? '';
                $price = $variety['price'] ?? 0;
                ?>
                <div class="cw-cj-variety-item" 
                     data-index="<?php echo $index; ?>" 
                     data-name="<?php echo esc_attr($name); ?>" 
                     data-price="<?php echo esc_attr($price); ?>"
                     style="cursor: pointer; text-align: center; padding: 10px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s ease; background: white;">
                    
                    <!-- Color Name Above Image -->
                    <div style="font-size: 12px; font-weight: bold; color: #333; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo esc_html($name); ?>
                    </div>
                    
                    <!-- Small Image Box -->
                    <?php if ($image_url): ?>
                        <img class="cw-cj-variety-thumb" 
                             src="<?php echo esc_url($image_url); ?>" 
                             alt="<?php echo esc_attr($name); ?>"
                             style="width: 100%; height: 100px; object-fit: cover; border-radius: 5px; margin-bottom: 8px; border: 1px solid #eee;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100px; background: #e9ecef; border-radius: 5px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">
                            No Image
                        </div>
                    <?php endif; ?>
                    
                    <!-- Price Display -->
                    <div style="font-size: 14px; font-weight: bold; color: #ff4d4f;">
                        $<?php echo number_format($price, 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Size Reference (Optional: if sizes detected) -->
        <?php if (cw_cj_has_sizes_in_varieties($varieties)): ?>
            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
                <small style="color: #1976d2;">
                    <strong>Size Guide:</strong> Common sizes - XS, S, M, L, XL, XXL. Hover over sizes for details.
                </small>
            </div>
        <?php endif; ?>
        
        <!-- Selected Variety Display -->
        <div id="cw-cj-selected-variety" style="margin-top: 15px; padding: 12px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 3px; display: none;">
            <strong>Selected:</strong> <span id="cw-cj-selected-name"></span> - 
            <span style="color: #ff4d4f; font-weight: bold;">$<span id="cw-cj-selected-price"></span></span>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Variety item click handler
        $('.cw-cj-variety-item').on('click', function() {
            const $this = $(this);
            const name = $this.data('name');
            const price = parseFloat($this.data('price')).toFixed(2);
            
            // Update selected display
            $('#cw-cj-selected-name').text(name);
            $('#cw-cj-selected-price').text(price);
            $('#cw-cj-selected-variety').slideDown(300);
            
            // Update border highlight
            $('.cw-cj-variety-item').css({'border': '2px solid #ddd', 'background': 'white', 'transform': 'scale(1)'});
            $this.css({'border': '2px solid #ff4d4f', 'background': '#fff5f5', 'transform': 'scale(1.05)'});
            
            // Update product price if needed
            const originalPrice = $('p.price');
            if (originalPrice.length) {
                originalPrice.html('<span class="woocommerce-Price-amount">$' + price + '</span>');
            }
        });
        
        // Trigger first variety on page load
        setTimeout(function() {
            $('.cw-cj-variety-item').first().trigger('click');
        }, 300);
    });
    </script>
    <?php
}

/**
 * Check if varieties contain sizes
 */
function cw_cj_has_sizes_in_varieties($varieties) {
    $size_keywords = ['s', 'm', 'l', 'xl', 'xxl', 'size', 'xs', '32', '34', '36'];
    foreach ($varieties as $variety) {
        $name = strtolower($variety['name'] ?? '');
        foreach ($size_keywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return true;
            }
        }
    }
    return false;
}

?>

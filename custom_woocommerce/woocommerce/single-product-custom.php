<?php
/**
 * Custom Single Product Page - CJ Dropshipping Style
 * Clean layout: Gallery + Product Info (No tabs, no shipping details)
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<div id="primary" class="site-main custom-single-product-page">
	<?php do_action( 'woocommerce_before_main_content' ); ?>

	<?php while ( have_posts() ) : the_post(); ?>
		<?php global $product; ?>
		
		<div class="csp-container">
			<!-- LEFT: GALLERY SECTION -->
			<div class="csp-gallery-section">
				<!-- Thumbnails Column -->
				<div class="csp-thumbnails">
					<?php
					$product_id = $product->get_id();
					$image_id = $product->get_image_id();
					$gallery_ids = $product->get_gallery_image_ids();
					$video_ids = get_post_meta($product_id, '_product_video_ids', true);
					
					$all_items = [];
					if ($image_id) {
						$all_items[] = ['type' => 'image', 'id' => $image_id];
					}
					
					if (!empty($gallery_ids)) {
						foreach ($gallery_ids as $gid) {
							$all_items[] = ['type' => 'image', 'id' => $gid];
						}
					}
					
					if (!empty($video_ids) && is_array($video_ids)) {
						foreach ($video_ids as $vid) {
							$all_items[] = ['type' => 'video', 'id' => $vid];
						}
					}
					
					if (!empty($all_items)) {
						foreach ($all_items as $index => $item) {
							if ($item['type'] === 'image') {
								$thumb_url = wp_get_attachment_image_url($item['id'], 'thumbnail');
								$full_url = wp_get_attachment_image_url($item['id'], 'large');
								$active_class = ($index === 0) ? 'active' : '';
								echo '<div class="csp-thumb ' . $active_class . '" data-index="' . $index . '" data-type="image" data-id="' . $item['id'] . '" data-url="' . esc_attr($full_url) . '">';
								echo '<img src="' . esc_attr($thumb_url) . '" alt="Product thumbnail">';
								echo '</div>';
							} else if ($item['type'] === 'video') {
								$video_url = wp_get_attachment_url($item['id']);
								echo '<div class="csp-thumb video" data-index="' . $index . '" data-type="video" data-id="' . $item['id'] . '" data-url="' . esc_attr($video_url) . '">';
								echo '<div class="csp-video-icon">▶</div>';
								echo '</div>';
							}
						}
					}
					?>
				</div>

				<!-- Main Display -->
				<div class="csp-main-display">
					<?php
					if (!empty($all_items)) {
						$first_item = $all_items[0];
						if ($first_item['type'] === 'image') {
							$main_img = wp_get_attachment_image_url($first_item['id'], 'large');
							echo '<img id="csp-main-image" src="' . esc_attr($main_img) . '" alt="' . esc_attr(get_the_title()) . '" class="csp-main-img">';
						} else if ($first_item['type'] === 'video') {
							$video_url = wp_get_attachment_url($first_item['id']);
							echo '<video id="csp-main-video" controls muted loop>';
							echo '<source src="' . esc_url($video_url) . '" type="' . esc_attr(get_post_mime_type($first_item['id'])) . '">';
							echo 'Video not supported';
							echo '</video>';
						}
					} else {
						echo '<div class="csp-no-image">' . esc_html__('No images available', 'woocommerce') . '</div>';
					}
					?>
				</div>

				<!-- Image Counter -->
				<div class="csp-counter">
					<span id="csp-current">1</span>/<span id="csp-total"><?php echo count($all_items); ?></span>
				</div>
			</div>

			<!-- RIGHT: PRODUCT INFO SECTION -->
			<div class="csp-info-section">
				<!-- Title -->
				<h1 class="csp-title"><?php the_title(); ?></h1>

				<!-- SKU & Lists -->
				<div class="csp-meta-info">
					<?php
					$sku = $product->get_sku();
					if ($sku) {
						echo '<div class="csp-sku"><strong>SKU:</strong> ' . esc_html($sku) . '</div>';
					}
					?>
				</div>

				<!-- Price -->
				<div class="csp-price">
					<?php echo wp_kses_post($product->get_price_html()); ?>
				</div>

				<!-- VARIETIES/COLOR SWATCHES -->
				<?php
				$varieties = get_post_meta($product_id, '_cj_varieties', true);
				if (!empty($varieties) && is_array($varieties)) {
					?>
					<div class="csp-varieties-section">
						<label class="csp-variety-label">Color <span class="csp-variety-selected" id="csp-variety-selected"></span></label>
						<div class="csp-varieties-grid">
							<?php
							foreach ($varieties as $index => $variety) {
								$image_url = !empty($variety['image_id']) ? wp_get_attachment_image_url($variety['image_id'], 'thumbnail') : '';
								$active_class = ($index === 0) ? 'active' : '';
								$variety_name = '';
								if (!empty($variety['color_name'])) {
									$variety_name = $variety['color_name'];
								} else if (!empty($variety['name'])) {
									$variety_name = $variety['name'];
								} else if (!empty($variety['color'])) {
									$variety_name = $variety['color'];
								} else if (!empty($variety['variant_name'])) {
									$variety_name = $variety['variant_name'];
								} else if (!empty($variety['variant'])) {
									$variety_name = $variety['variant'];
								} else if (!empty($variety['option'])) {
									$variety_name = $variety['option'];
								} else if (!empty($variety['title'])) {
									$variety_name = $variety['title'];
								}
								if ($variety_name === '') {
									$variety_name = 'Option ' . ($index + 1);
								}
								
								echo '<div class="csp-variety-swatch ' . $active_class . '" data-variety-index="' . $index . '" data-variety-name="' . esc_attr($variety_name) . '" data-variety-price="' . esc_attr($variety['price']) . '" title="' . esc_attr($variety_name) . '">';
								
								if ($image_url) {
									echo '<img src="' . esc_attr($image_url) . '" alt="' . esc_attr($variety_name) . '">';
								} else {
									echo '<span class="csp-variety-name">' . esc_html(substr($variety_name, 0, 2)) . '</span>';
								}
								
								echo '</div>';
							}
							?>
						</div>
					</div>
					<?php
				}
				?>

				<!-- Quantity Selector -->
				<div class="csp-quantity-section">
					<label class="csp-qty-label">QTY:</label>
					<div class="csp-qty-input-wrapper">
						<button class="csp-qty-btn minus" id="csp-qty-minus">−</button>
						<input type="number" id="csp-qty-input" name="quantity" value="1" min="1" max="999">
						<button class="csp-qty-btn plus" id="csp-qty-plus">+</button>
						<span class="csp-weight" id="csp-weight-display">270g</span>
					</div>
				</div>

				<!-- Total Price -->
				<div class="csp-total-section">
					<span class="csp-total-label">Total:</span>
					<span class="csp-total-price" id="csp-total-price">
						<?php echo wp_kses_post($product->get_price_html()); ?>
					</span>
				</div>

				<!-- Add to Cart Button -->
				<div class="csp-add-to-cart-wrapper">
					<?php
					woocommerce_template_single_add_to_cart();
					?>
				</div>
			</div>
		</div>

		<!-- DESCRIPTION & DETAILS BELOW -->
		<div class="csp-details-section">
			<?php
			do_action( 'woocommerce_after_single_product_summary' );
			?>
		</div>

	<?php endwhile; ?>

	<?php do_action( 'woocommerce_after_main_content' ); ?>
</div>

<?php
get_footer( 'shop' );
?>

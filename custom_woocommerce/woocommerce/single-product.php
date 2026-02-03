<?php
/**
 * The Template for displaying all single products - CJ Dropshipping Style
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<div id="primary" class="site-main cj-single-product-page">
	<?php
	do_action( 'woocommerce_before_main_content' );
	?>

	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>

		<div class="cj-product-container">
			<!-- LEFT SECTION: GALLERY & MEDIA -->
			<div class="cj-media-section">
				<!-- Gallery Thumbnails Column -->
				<div class="cj-gallery-thumbnails">
					<?php
					global $product;
					$product_id = $product->get_id();
					$image_id = $product->get_image_id();
					$gallery_ids = $product->get_gallery_image_ids();
					$video_ids = get_post_meta($product_id, '_product_video_ids', true);
					
					// Add main image to gallery
					$all_items = [];
					if ($image_id) {
						$all_items[] = ['type' => 'image', 'id' => $image_id];
					}
					
					// Add gallery images
					if (!empty($gallery_ids)) {
						foreach ($gallery_ids as $gid) {
							$all_items[] = ['type' => 'image', 'id' => $gid];
						}
					}
					
					// Add videos
					if (!empty($video_ids) && is_array($video_ids)) {
						foreach ($video_ids as $vid) {
							$all_items[] = ['type' => 'video', 'id' => $vid];
						}
					}
					
					// Display thumbnails
					if (!empty($all_items)) {
						foreach ($all_items as $index => $item) {
							if ($item['type'] === 'image') {
								$thumb_url = wp_get_attachment_image_url($item['id'], 'thumbnail');
								$full_url = wp_get_attachment_image_url($item['id'], 'large');
								echo '<div class="cj-thumb-item active-thumb" data-index="' . $index . '" data-type="image" data-id="' . $item['id'] . '" data-url="' . esc_attr($full_url) . '">';
								echo '<img src="' . esc_attr($thumb_url) . '" alt="Product" class="cj-thumb-img">';
								echo '</div>';
							} else if ($item['type'] === 'video') {
								$video_url = wp_get_attachment_url($item['id']);
								echo '<div class="cj-thumb-item video-thumb" data-index="' . $index . '" data-type="video" data-id="' . $item['id'] . '" data-url="' . esc_attr($video_url) . '">';
								echo '<div class="cj-thumb-img cj-video-thumb">';
								echo '<span class="cj-play-icon">▶</span>';
								echo '</div>';
								echo '</div>';
							}
						}
					}
					?>
				</div>

				<!-- Main Image/Video Display -->
				<div class="cj-main-media">
					<div class="cj-image-container">
						<?php if (!empty($all_items)) { ?>
							<?php 
							$first_item = $all_items[0];
							if ($first_item['type'] === 'image') {
								$main_img = wp_get_attachment_image_url($first_item['id'], 'large');
								echo '<img id="cj-main-image" src="' . esc_attr($main_img) . '" alt="Product" class="cj-main-img">';
								echo '<div class="cj-image-zoom" id="cj-image-zoom"></div>';
							} else if ($first_item['type'] === 'video') {
								$video_url = wp_get_attachment_url($first_item['id']);
								echo '<video id="cj-main-video" controls muted loop class="cj-main-video">';
								echo '<source src="' . esc_url($video_url) . '" type="' . esc_attr(get_post_mime_type($first_item['id'])) . '">';
								echo '</video>';
							}
							?>
						<?php } else {
							echo '<div class="cj-no-image">No images available</div>';
						} ?>
					</div>
				</div>
			</div>

			<!-- RIGHT SECTION: PRODUCT INFO -->
			<div class="cj-info-section">
				<?php
				do_action( 'woocommerce_single_product_summary' );
				?>
			</div>
		</div>

		<!-- BELOW SECTION: DESCRIPTION, SPECS, REVIEWS -->
		<div class="cj-product-below">
			<?php
			do_action( 'woocommerce_after_single_product_summary' );
			?>
		</div>

	<?php endwhile; ?>

	<?php
	do_action( 'woocommerce_after_main_content' );
	?>
</div>

<?php
get_footer( 'shop' );

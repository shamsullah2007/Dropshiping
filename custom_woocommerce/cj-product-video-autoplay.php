<?php
/**
 * Product Video Auto-play Display
 * 
 * When admin adds a video while editing a product,
 * the video is displayed on the single product page
 * with auto-play, no controls, and muted audio
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display product video on single product page
 * Plays before product description
 */
add_action('woocommerce_before_single_product_summary', 'cw_cj_display_product_video', 8);
function cw_cj_display_product_video() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $product_id = $product->get_id();
    $video_url = get_post_meta($product_id, '_product_video_url', true);
    
    if (!$video_url) {
        return;
    }
    
    // Determine video type
    $is_youtube = preg_match('/(youtube|youtu\.be)/', $video_url);
    $is_vimeo = preg_match('/(vimeo)/', $video_url);
    $is_html5 = preg_match('/\.(mp4|webm|ogg)$/i', $video_url);
    
    ?>
    <div class="cw-cj-product-video-container" style="margin-bottom: 30px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <div style="position: relative; width: 100%; max-width: 600px; margin: 0 auto;">
            
            <?php if ($is_youtube): ?>
                <!-- YouTube Video -->
                <?php
                $video_id = cw_cj_extract_youtube_id($video_url);
                if ($video_id):
                ?>
                    <iframe 
                        width="100%" 
                        height="360" 
                        src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>?autoplay=1&mute=1&controls=0" 
                        frameborder="0" 
                        allow="autoplay; muted" 
                        style="border-radius: 8px; display: block;">
                    </iframe>
                <?php endif; ?>
            
            <?php elseif ($is_vimeo): ?>
                <!-- Vimeo Video -->
                <?php
                $vimeo_id = cw_cj_extract_vimeo_id($video_url);
                if ($vimeo_id):
                ?>
                    <iframe 
                        src="https://player.vimeo.com/video/<?php echo esc_attr($vimeo_id); ?>?autoplay=1&muted=1&controls=0" 
                        width="100%" 
                        height="360" 
                        frameborder="0" 
                        allow="autoplay; fullscreen; muted"
                        style="border-radius: 8px; display: block;">
                    </iframe>
                <?php endif; ?>
            
            <?php elseif ($is_html5): ?>
                <!-- HTML5 Video (MP4, WebM, OGG) -->
                <video 
                    width="100%" 
                    height="360" 
                    autoplay 
                    muted 
                    loop
                    style="border-radius: 8px; display: block; background: #000;">
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    Your browser does not support HTML5 video.
                </video>
            <?php else: ?>
                <!-- Direct Video URL (attempt to embed) -->
                <video 
                    width="100%" 
                    height="360" 
                    autoplay 
                    muted 
                    loop
                    style="border-radius: 8px; display: block; background: #000;">
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    Your browser does not support HTML5 video.
                </video>
            <?php endif; ?>
            
        </div>
    </div>
    
    <?php
}

/**
 * Extract YouTube video ID from various URL formats
 */
function cw_cj_extract_youtube_id($url) {
    // Formats: https://youtube.com/watch?v=ID
    //          https://www.youtube.com/watch?v=ID
    //          https://youtu.be/ID
    //          youtu.be/ID
    //          youtube.com/watch?v=ID
    
    $patterns = [
        '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/youtu\.be?\/(?:watch\?v=)?([a-zA-Z0-9_-]{11})/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return '';
}

/**
 * Extract Vimeo video ID from various URL formats
 */
function cw_cj_extract_vimeo_id($url) {
    // Formats: https://vimeo.com/IDNUMBER
    //          https://player.vimeo.com/video/IDNUMBER
    
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    
    return '';
}

/**
 * Register metabox for product video URL in admin
 */
add_action('add_meta_boxes', 'cw_cj_register_video_metabox');
function cw_cj_register_video_metabox() {
    add_meta_box(
        'cw_cj_product_video',
        '🎬 Product Video (Auto-play)',
        'cw_cj_render_video_metabox',
        'product',
        'normal',
        'default'
    );
}

/**
 * Render video metabox
 */
function cw_cj_render_video_metabox($post) {
    $product_id = $post->ID;
    $video_url = get_post_meta($product_id, '_product_video_url', true);
    
    wp_nonce_field('cw_cj_video_nonce', 'cw_cj_video_nonce_field');
    
    ?>
    <div style="padding: 15px; background: #f9f9f9;">
        
        <div style="margin-bottom: 15px; padding: 12px; background: #e3f2fd; border-left: 4px solid #2196F3; border-radius: 3px;">
            <p style="margin: 0;"><strong>📹 Supported Video Sources:</strong></p>
            <ul style="margin: 8px 0 0 20px; padding: 0;">
                <li><strong>YouTube:</strong> https://youtube.com/watch?v=VIDEOID or youtu.be/VIDEOID</li>
                <li><strong>Vimeo:</strong> https://vimeo.com/VIDEOID</li>
                <li><strong>Direct Upload:</strong> https://yoursite.com/video.mp4 (hosted on your server)</li>
            </ul>
            <p style="margin: 8px 0 0 0; font-size: 12px; color: #555;">
                Video will auto-play on product page with no controls (muted audio for auto-play)
            </p>
        </div>
        
        <label style="display: block; margin-bottom: 8px; font-weight: bold;">Video URL:</label>
        <input 
            type="url" 
            id="cw_cj_product_video_url" 
            name="cw_cj_product_video_url" 
            value="<?php echo esc_url($video_url); ?>" 
            placeholder="https://youtube.com/watch?v=... or https://vimeo.com/123456 or https://yoursite.com/video.mp4"
            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 3px; margin-bottom: 10px; font-family: monospace;">
        
        <p style="margin: 0; font-size: 12px; color: #666;">
            <strong>Pro Tips:</strong>
            <br>• Leave blank to hide video
            <br>• Video will mute automatically on auto-play (YouTube/Vimeo requirement)
            <br>• Displays before product description on single product page
        </p>
    </div>
    <?php
}

/**
 * Save video URL when product is saved
 */
add_action('save_post_product', 'cw_cj_save_product_video', 10, 1);
function cw_cj_save_product_video($post_id) {
    if (!isset($_POST['cw_cj_video_nonce_field'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['cw_cj_video_nonce_field'], 'cw_cj_video_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    $video_url = isset($_POST['cw_cj_product_video_url']) ? esc_url_raw($_POST['cw_cj_product_video_url']) : '';
    
    if (!empty($video_url)) {
        update_post_meta($post_id, '_product_video_url', $video_url);
    } else {
        delete_post_meta($post_id, '_product_video_url');
    }
}

?>

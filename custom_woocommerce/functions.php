<?php

if (!defined('ABSPATH')) {
    exit;
}

function custom_woocommerce_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    add_theme_support('custom-logo', [
        'height' => 80,
        'width' => 240,
        'flex-height' => true,
        'flex-width' => true,
    ]);

    add_theme_support('woocommerce');

    register_nav_menus([
        'primary' => __('Primary Menu', 'custom-woocommerce'),
        'footer' => __('Footer Menu', 'custom-woocommerce'),
        'myaccount' => __('My Account Menu', 'custom-woocommerce'),
    ]);
}
add_action('after_setup_theme', 'custom_woocommerce_theme_setup');

function custom_woocommerce_widgets_init()
{
    register_sidebar([
        'name' => __('Sidebar', 'custom-woocommerce'),
        'id' => 'sidebar-1',
        'description' => __('Main sidebar area', 'custom-woocommerce'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ]);

    register_sidebar([
        'name' => __('Footer Widgets', 'custom-woocommerce'),
        'id' => 'footer-1',
        'description' => __('Footer widget area', 'custom-woocommerce'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ]);
}
add_action('widgets_init', 'custom_woocommerce_widgets_init');

function custom_woocommerce_enqueue_assets()
{
    wp_enqueue_style('custom-woocommerce-style', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_script(
        'custom-woocommerce-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [],
        '1.0.0',
        true
    );

    if (is_singular()) {
        global $post;
        if ($post && has_shortcode($post->post_content, 'cw_add_product_form')) {
            wp_enqueue_script(
                'custom-woocommerce-add-product',
                get_template_directory_uri() . '/assets/js/add-product.js',
                [],
                '1.0.0',
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'custom_woocommerce_enqueue_assets');

function custom_woocommerce_get_user_avatar_url($user_id)
{
    $avatar_id = (int) get_user_meta($user_id, 'cw_profile_avatar_id', true);
    if ($avatar_id) {
        $url = wp_get_attachment_image_url($avatar_id, 'thumbnail');
        if ($url) {
            return $url;
        }
    }
    return get_avatar_url($user_id, ['size' => 96]);
}

function custom_woocommerce_handle_avatar_upload()
{
    if (!is_user_logged_in() || !is_account_page()) {
        return;
    }

    if (!isset($_POST['cw_avatar_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['cw_avatar_nonce'], 'cw_avatar_upload')) {
        return;
    }

    if (!current_user_can('read')) {
        return;
    }

    if (empty($_FILES['cw_profile_avatar']['name'])) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $user_id = get_current_user_id();
    $attachment_id = media_handle_upload('cw_profile_avatar', 0);
    if (!is_wp_error($attachment_id)) {
        update_user_meta($user_id, 'cw_profile_avatar_id', (int) $attachment_id);
        wp_safe_redirect(wc_get_account_endpoint_url('dashboard'));
        exit;
    }
}
add_action('template_redirect', 'custom_woocommerce_handle_avatar_upload');

function custom_woocommerce_remove_account_dashboard_notice($content)
{
    return '';
}
add_filter('woocommerce_account_dashboard', 'custom_woocommerce_remove_account_dashboard_notice', 10, 1);

function custom_woocommerce_add_product_form_shortcode()
{
    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('You do not have permission to access this page.', 'custom-woocommerce') . '</p>';
    }

    if (!class_exists('WooCommerce')) {
        return '<p>' . esc_html__('WooCommerce is required.', 'custom-woocommerce') . '</p>';
    }

    $message = '';

    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['cw_add_product_nonce'])) {
        if (!wp_verify_nonce($_POST['cw_add_product_nonce'], 'cw_add_product')) {
            $message = '<p class="cw-form-error">' . esc_html__('Security check failed.', 'custom-woocommerce') . '</p>';
        } else {
            $title = isset($_POST['cw_product_title']) ? sanitize_text_field($_POST['cw_product_title']) : '';
            $price = isset($_POST['cw_product_price']) ? wc_format_decimal($_POST['cw_product_price']) : '';
            $sku = isset($_POST['cw_product_sku']) ? sanitize_text_field($_POST['cw_product_sku']) : '';
            $description = isset($_POST['cw_product_description']) ? wp_kses_post($_POST['cw_product_description']) : '';
            $category = isset($_POST['cw_product_category']) ? sanitize_text_field($_POST['cw_product_category']) : '';

            if (empty($title) || empty($price)) {
                $message = '<p class="cw-form-error">' . esc_html__('Title and price are required.', 'custom-woocommerce') . '</p>';
            } else {
                $product_id = wp_insert_post([
                    'post_title' => $title,
                    'post_content' => $description,
                    'post_status' => 'publish',
                    'post_type' => 'product',
                ]);

                if (is_wp_error($product_id)) {
                    $message = '<p class="cw-form-error">' . esc_html__('Failed to create product.', 'custom-woocommerce') . '</p>';
                } else {
                    update_post_meta($product_id, '_regular_price', $price);
                    update_post_meta($product_id, '_price', $price);
                    if (!empty($sku)) {
                        update_post_meta($product_id, '_sku', $sku);
                    }

                    if (!empty($category)) {
                        $term = term_exists($category, 'product_cat');
                        if (!$term) {
                            $term = wp_insert_term($category, 'product_cat');
                        }
                        if (!is_wp_error($term)) {
                            $term_id = is_array($term) ? $term['term_id'] : $term;
                            wp_set_object_terms($product_id, (int) $term_id, 'product_cat');
                        }
                    }

                    if (!empty($_FILES['cw_product_image']['name'])) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $attachment_id = media_handle_upload('cw_product_image', $product_id);
                        if (!is_wp_error($attachment_id)) {
                            set_post_thumbnail($product_id, $attachment_id);
                        }
                    }

                    $message = '<p class="cw-form-success">' . esc_html__('Product created successfully.', 'custom-woocommerce') . '</p>';
                }
            }
        }
    }

    ob_start();
    echo $message;
    ?>
    <form class="cw-add-product-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('cw_add_product', 'cw_add_product_nonce'); ?>
        <div class="cw-image-preview" aria-hidden="true"></div>
        <label for="cw-product-image" class="cw-image-label">
            <?php esc_html_e('Product Image', 'custom-woocommerce'); ?>
        </label>
        <input type="file" id="cw-product-image" name="cw_product_image" accept="image/*">

        <label for="cw-product-title"><?php esc_html_e('Title', 'custom-woocommerce'); ?></label>
        <input type="text" id="cw-product-title" name="cw_product_title" required>

        <label for="cw-product-price"><?php esc_html_e('Price', 'custom-woocommerce'); ?></label>
        <input type="number" step="0.01" id="cw-product-price" name="cw_product_price" required>

        <label for="cw-product-sku"><?php esc_html_e('SKU', 'custom-woocommerce'); ?></label>
        <input type="text" id="cw-product-sku" name="cw_product_sku">

        <label for="cw-product-category"><?php esc_html_e('Category', 'custom-woocommerce'); ?></label>
        <input type="text" id="cw-product-category" name="cw_product_category">

        <label for="cw-product-description"><?php esc_html_e('Description', 'custom-woocommerce'); ?></label>
        <textarea id="cw-product-description" name="cw_product_description" rows="6"></textarea>

        <button type="submit" class="button button-accent">
            <?php esc_html_e('Create Product', 'custom-woocommerce'); ?>
        </button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('cw_add_product_form', 'custom_woocommerce_add_product_form_shortcode');

function custom_woocommerce_woocommerce_wrapper_start()
{
    echo '<main id="primary" class="content"><div class="container">';
}
function custom_woocommerce_woocommerce_wrapper_end()
{
    echo '</div></main>';
}

remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
add_action('woocommerce_before_main_content', 'custom_woocommerce_woocommerce_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'custom_woocommerce_woocommerce_wrapper_end', 10);

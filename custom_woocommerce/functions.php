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

function custom_woocommerce_flush_rewrite_rules()
{
    flush_rewrite_rules(false);
}
add_action('init', 'custom_woocommerce_flush_rewrite_rules', 999);

/**
 * Get product reviews for display
 * 
 * @param int $limit Number of reviews to fetch
 * @return array Array of comment objects representing product reviews
 */
function custom_woocommerce_get_product_reviews($limit = 12) {
    global $wpdb;
    
    // Query for WooCommerce product reviews with ratings
    // WooCommerce stores reviews in comments table but filters them with comment_type
    $reviews = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT c.* FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            WHERE p.post_type = 'product'
            AND c.comment_approved = 1
            AND (c.comment_type = 'review' OR c.comment_type = '')
            ORDER BY c.comment_date DESC
            LIMIT %d",
            $limit
        )
    );
    
    // Convert to comment objects and filter by rating
    if ( ! empty( $reviews ) ) {
        $reviews = array_map( function( $review ) {
            return get_comment( $review->comment_ID );
        }, $reviews );
        
        // Filter to only include comments with ratings >= 4
        $reviews = array_filter( $reviews, function( $review ) {
            $rating = get_comment_meta( $review->comment_ID, 'rating', true );
            return ! empty( $rating ) && $rating >= 4;
        });
        
        $reviews = array_values( $reviews );
    }
    
    // Debug: Log the review count
    error_log( "Custom WooCommerce: Found " . count( $reviews ) . " approved reviews with 4+ star ratings" );
    
    return $reviews;
}

// Restrict reviews to verified purchasers only
function restrict_reviews_to_purchasers($open, $post_id) {
    global $post;
    
    if (get_post_type($post_id) !== 'product') {
        return $open;
    }
    
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Check if user has purchased this product
    $current_user = wp_get_current_user();
    $customer_orders = wc_get_orders(array(
        'customer' => $current_user->ID,
        'status' => 'completed',
        'limit' => -1,
    ));
    
    $purchased = false;
    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $post_id) {
                $purchased = true;
                break 2;
            }
        }
    }
    
    return $purchased ? $open : false;
}
add_filter('comments_open', 'restrict_reviews_to_purchasers', 10, 2);

// Add custom message for users who haven't purchased
function show_purchase_required_message() {
    global $product;
    
    if (!is_user_logged_in()) {
        echo '<p class="review-restriction-notice">You must be <a href="' . wc_get_page_permalink('myaccount') . '">logged in</a> to submit a review.</p>';
        return;
    }
    
    $current_user = wp_get_current_user();
    $customer_orders = wc_get_orders(array(
        'customer' => $current_user->ID,
        'status' => 'completed',
        'limit' => -1,
    ));
    
    $purchased = false;
    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product->get_id()) {
                $purchased = true;
                break 2;
            }
        }
    }
    
    if (!$purchased) {
        echo '<p class="review-restriction-notice">Only customers who have purchased this product can leave a review.</p>';
    }
}
add_action('comment_form_before', 'show_purchase_required_message');

// Redirect default WordPress login to custom login page
function redirect_wp_login_to_custom_page() {
    global $post;
    
    // Don't redirect if we're already on login or registration page
    if (is_page() && $post && in_array($post->post_name, ['login', 'registeration'])) {
        return;
    }
    
    $login_page = home_url('/login/');

    if (basename($_SERVER['REQUEST_URI']) == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
        wp_redirect($login_page);
        exit;
    }
}
add_action('init', 'redirect_wp_login_to_custom_page');

// Redirect logout to custom login page
function custom_woocommerce_logout_redirect($redirect_to, $requested_redirect_to, $user)
{
    return home_url('/login/');
}
add_filter('logout_redirect', 'custom_woocommerce_logout_redirect', 10, 3);

// Redirect WooCommerce logout to custom login page
function custom_woocommerce_wc_logout_redirect()
{
    wp_redirect(home_url('/login/'));
    exit;
}
add_action('wp_logout', 'custom_woocommerce_wc_logout_redirect');

// Redirect wp-admin to custom login for logged-out users
function custom_woocommerce_redirect_admin()
{
    if (!is_user_logged_in() && is_admin() && !wp_doing_ajax()) {
        wp_redirect(home_url('/login/'));
        exit;
    }
}
add_action('admin_init', 'custom_woocommerce_redirect_admin');

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

    register_sidebar([
        'name' => __('Shop Filters', 'custom-woocommerce'),
        'id' => 'shop-filters',
        'description' => __('Shop page filter widgets', 'custom-woocommerce'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ]);
}
add_action('widgets_init', 'custom_woocommerce_widgets_init');

// Fallback primary menu with Shop and Checkout links
function custom_woocommerce_primary_menu_fallback() {
    $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $checkout_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : home_url('/checkout/');
    
    echo '<ul class="menu nav-menu">';
    echo '<li class="menu-item"><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'custom-woocommerce') . '</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url($shop_url) . '">' . esc_html__('Shop', 'custom-woocommerce') . '</a></li>';
    echo '<li class="menu-item"><a href="' . esc_url($checkout_url) . '">' . esc_html__('Checkout', 'custom-woocommerce') . '</a></li>';
    echo '</ul>';
}

// Redirect default WooCommerce shop to custom shop page
add_action('template_redirect', 'custom_redirect_shop_to_custom');
function custom_redirect_shop_to_custom() {
    if (function_exists('is_shop') && is_shop() && !is_page('shop-2')) {
        $shop_page = get_page_by_path('shop-2');
        if ($shop_page) {
            wp_redirect(get_permalink($shop_page->ID), 301);
            exit;
        }
    }
}

// Add Shop and Checkout links to the primary menu
add_filter('wp_nav_menu_items', 'custom_woocommerce_add_shop_checkout_to_menu', 10, 2);
function custom_woocommerce_add_shop_checkout_to_menu($items, $args) {
    // Only add to primary menu
    if ($args->theme_location !== 'primary') {
        return $items;
    }
    
    // Use custom shop page instead of WooCommerce shop
    $shop_page = get_page_by_path('shop-2'); // Get the custom shop page
    $shop_url = $shop_page ? get_permalink($shop_page->ID) : home_url('/shop-2/');
    $checkout_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : home_url('/checkout/');
    
    // Check if we're on shop or checkout page for active state
    $shop_active = is_page('shop-2') ? 'current-menu-item' : '';
    $checkout_active = (function_exists('is_checkout') && is_checkout()) || is_page('checkout') ? 'current-menu-item' : '';
    
    // Add Shop link at the beginning
    $shop_link = '<li class="menu-item ' . $shop_active . '"><a href="' . esc_url($shop_url) . '">' . esc_html__('Shop', 'custom-woocommerce') . '</a></li>';
    
    // Add Checkout link at the end
    $checkout_link = '<li class="menu-item ' . $checkout_active . '"><a href="' . esc_url($checkout_url) . '">' . esc_html__('Checkout', 'custom-woocommerce') . '</a></li>';
    
    return $shop_link . $items . $checkout_link;
}

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

    if (is_product()) {
        wp_enqueue_script(
            'custom-woocommerce-single-product',
            get_template_directory_uri() . '/assets/js/single-product.js',
            [],
            '1.0.0',
            true
        );
    }

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

// Remove WooCommerce shop elements
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

// Custom Products Grid Shortcode
function custom_products_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page' => 40, // 8 rows × 5 columns = 40 products
    ], $atts);

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $args = [
        'post_type' => 'product',
        'posts_per_page' => $atts['per_page'],
        'paged' => $paged,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    $products = new WP_Query($args);

    ob_start();

    if ($products->have_posts()) {
        echo '<div class="custom-products-grid">';
        
        while ($products->have_posts()) {
            $products->the_post();
            global $product;
            
            // Get product discount percentage
            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $discount = 0;
            
            if ($sale_price && $regular_price) {
                $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
            }
            
            echo '<div class="custom-product-item">';
            
            // Discount badge
            if ($discount > 0) {
                echo '<div class="discount-badge">Up to ' . $discount . '% off</div>';
            }
            
            // Product image
            echo '<a href="' . esc_url(get_permalink()) . '" class="custom-product-image">';
            echo $product->get_image('full');
            echo '</a>';
            
            // Product info
            echo '<div class="custom-product-info">';
            echo '<h3 class="custom-product-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h3>';
            
            // Lists count (SKU display)
            $sku = $product->get_sku();
            if ($sku) {
                echo '<div class="custom-product-lists">Lists: ' . esc_html($sku) . '</div>';
            }
            
            // Price
            echo '<div class="custom-product-price">' . $product->get_price_html() . '</div>';
            
            // Product rating
            if (wc_product_sku_enabled() || $product->get_rating_count()) {
                echo '<div class="custom-product-rating">';
                echo wc_get_rating_html($product->get_average_rating(), $product->get_review_count());
                echo '</div>';
            }
            
            // Add to cart button with WooCommerce AJAX functionality
            $add_to_cart_url = $product->add_to_cart_url();
            $add_to_cart_classes = 'custom-add-to-cart button add_to_cart_button ajax_add_to_cart';
            
            echo '<a href="' . esc_url($add_to_cart_url) . '" 
                     class="' . esc_attr($add_to_cart_classes) . '" 
                     data-product_id="' . esc_attr($product->get_id()) . '" 
                     data-product_sku="' . esc_attr($product->get_sku()) . '" 
                     data-quantity="1" 
                     aria-label="' . esc_attr($product->add_to_cart_description()) . '" 
                     rel="nofollow">Add to cart</a>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Pagination
        if ($products->max_num_pages > 1) {
            echo '<div class="custom-pagination">';
            echo paginate_links([
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '?paged=%#%',
                'current' => max(1, $paged),
                'total' => $products->max_num_pages,
                'prev_text' => '<<',
                'next_text' => '>>',
                'type' => 'list',
            ]);
            echo '</div>';
        }
    } else {
        echo '<p>' . esc_html__('No products found.', 'custom-woocommerce') . '</p>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('custom_products_grid', 'custom_products_grid_shortcode');

function custom_woocommerce_send_otp_email($email, $otp, $subject)
{
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $message = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #111;">'
        . '<h2 style="margin:0 0 12px;">' . esc_html($site_name) . '</h2>'
        . '<p>Use the verification code below:</p>'
        . '<div style="font-size: 28px; font-weight: 700; letter-spacing: 4px; padding: 12px 16px; background: #f3f4f6; display: inline-block; border-radius: 8px;">'
        . esc_html($otp)
        . '</div>'
        . '<p style="margin-top: 12px;">This code expires in 10 minutes.</p>'
        . '<p>If you did not request this, please ignore this email.</p>'
        . '</div>';

    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'shamsullahd9999@gmail.com';
        $mail->Password = 'zipp fwkq oyeo atnh';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('shamsullahd9999@gmail.com', $site_name);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email failed: {$mail->ErrorInfo}");
        return false;
    }
}

function custom_woocommerce_generate_otp()
{
    return (string) wp_rand(100000, 999999);
}

function custom_woocommerce_store_otp($context, $email, array $data)
{
    $otp = custom_woocommerce_generate_otp();
    $normalized_email = strtolower(trim($email));
    $key = 'cw_otp_' . $context . '_' . md5($normalized_email);
    $payload = [
        'otp_hash' => wp_hash_password($otp),
        'data' => $data,
        'email' => $email,
    ];
    set_transient($key, $payload, 10 * MINUTE_IN_SECONDS);
    
    // Debug: Log OTP storage
    error_log("Stored OTP for context: $context, email: $email, key: $key, OTP: $otp");
    
    return [$key, $otp];
}

function custom_woocommerce_verify_otp($context, $email, $otp)
{
    $normalized_email = strtolower(trim($email));
    $key = 'cw_otp_' . $context . '_' . md5($normalized_email);
    $payload = get_transient($key);
    
    // Debug: Log verification attempt
    error_log("Verifying OTP - context: $context, email: $email, key: $key, provided OTP: $otp");
    
    if (!$payload) {
        error_log("No transient found for key: $key");
        return [false, null];
    }
    
    error_log("Found transient with hash: " . $payload['otp_hash']);
    
    $valid = wp_check_password($otp, $payload['otp_hash']);
    
    error_log("Password check result: " . ($valid ? 'valid' : 'invalid'));
    
    if (!$valid) {
        return [false, null];
    }
    delete_transient($key);
    return [true, $payload['data']];
}

function custom_woocommerce_register_shortcode()
{
    $message = '';
    $step = isset($_POST['cw_step']) ? sanitize_text_field($_POST['cw_step']) : 'start';

    if ('send' === $step && isset($_POST['cw_register_nonce']) && wp_verify_nonce($_POST['cw_register_nonce'], 'cw_register')) {
        $email = sanitize_email($_POST['cw_email'] ?? '');
        $username = sanitize_user($_POST['cw_username'] ?? '');
        $password = $_POST['cw_password'] ?? '';

        if (empty($email) || empty($username) || empty($password)) {
            $message = '<p class="cw-form-error">' . esc_html__('All fields are required.', 'custom-woocommerce') . '</p>';
        } elseif (!is_email($email)) {
            $message = '<p class="cw-form-error">' . esc_html__('Invalid email address.', 'custom-woocommerce') . '</p>';
        } elseif (username_exists($username) || email_exists($email)) {
            $message = '<p class="cw-form-error">' . esc_html__('Username or email already exists.', 'custom-woocommerce') . '</p>';
        } else {
            [$key, $otp] = custom_woocommerce_store_otp('register', $email, [
                'email' => $email,
                'username' => $username,
                'password' => $password,
            ]);
            custom_woocommerce_send_otp_email($email, $otp, __('Your registration code', 'custom-woocommerce'));
            $message = '<p class="cw-form-success">' . esc_html__('OTP sent to your email.', 'custom-woocommerce') . '</p>';
            $step = 'verify';
        }
    } elseif ('verify' === $step && isset($_POST['cw_register_verify_nonce']) && wp_verify_nonce($_POST['cw_register_verify_nonce'], 'cw_register_verify')) {
        $email = sanitize_email($_POST['cw_email'] ?? '');
        $otp = sanitize_text_field($_POST['cw_otp'] ?? '');
        [$valid, $data] = custom_woocommerce_verify_otp('register', $email, $otp);
        if (!$valid || !$data) {
            $message = '<p class="cw-form-error">' . esc_html__('Invalid or expired OTP.', 'custom-woocommerce') . '</p>';
        } else {
            $user_id = wp_create_user($data['username'], $data['password'], $data['email']);
            if (is_wp_error($user_id)) {
                $message = '<p class="cw-form-error">' . esc_html__('Registration failed.', 'custom-woocommerce') . '</p>';
            } else {
                $user = new WP_User($user_id);
                $user->set_role('customer');
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_safe_redirect(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/'));
                exit;
            }
        }
    }

    ob_start();
    echo $message;
    ?>
    <form class="cw-auth-form" method="post">
        <?php if ('verify' !== $step) : ?>
            <?php wp_nonce_field('cw_register', 'cw_register_nonce'); ?>
            <input type="hidden" name="cw_step" value="send">
            <label for="cw-username"><?php esc_html_e('Username', 'custom-woocommerce'); ?></label>
            <input type="text" id="cw-username" name="cw_username" required>

            <label for="cw-email"><?php esc_html_e('Email', 'custom-woocommerce'); ?></label>
            <input type="email" id="cw-email" name="cw_email" required>

            <label for="cw-password"><?php esc_html_e('Password', 'custom-woocommerce'); ?></label>
            <input type="password" id="cw-password" name="cw_password" required>

            <button type="submit" class="button button-accent"><?php esc_html_e('Send OTP', 'custom-woocommerce'); ?></button>
        <?php else : ?>
            <?php wp_nonce_field('cw_register_verify', 'cw_register_verify_nonce'); ?>
            <input type="hidden" name="cw_step" value="verify">
            <input type="hidden" name="cw_email" value="<?php echo esc_attr($_POST['cw_email'] ?? ''); ?>">

            <label for="cw-otp"><?php esc_html_e('OTP Code', 'custom-woocommerce'); ?></label>
            <input type="text" id="cw-otp" name="cw_otp" required>

            <button type="submit" class="button button-accent"><?php esc_html_e('Verify & Register', 'custom-woocommerce'); ?></button>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('cw_register', 'custom_woocommerce_register_shortcode');

function custom_woocommerce_login_shortcode()
{
    $message = '';
    $step = isset($_POST['cw_login_step']) ? sanitize_text_field($_POST['cw_login_step']) : 'start';

    if ('login' === $step && isset($_POST['cw_login_nonce']) && wp_verify_nonce($_POST['cw_login_nonce'], 'cw_login')) {
        $creds = [
            'user_login' => sanitize_text_field($_POST['cw_login'] ?? ''),
            'user_password' => $_POST['cw_password'] ?? '',
            'remember' => true,
        ];
        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            $message = '<p class="cw-form-error">' . esc_html__('Invalid credentials.', 'custom-woocommerce') . '</p>';
        } else {
            wp_safe_redirect(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/'));
            exit;
        }
    } elseif ('forgot_send' === $step && isset($_POST['cw_forgot_nonce']) && wp_verify_nonce($_POST['cw_forgot_nonce'], 'cw_forgot')) {
        $email = sanitize_email($_POST['cw_forgot_email'] ?? '');
        if (!is_email($email) || !email_exists($email)) {
            $message = '<p class="cw-form-error">' . esc_html__('Email not found.', 'custom-woocommerce') . '</p>';
        } else {
            [$key, $otp] = custom_woocommerce_store_otp('forgot', $email, [
                'email' => $email,
            ]);
            custom_woocommerce_send_otp_email($email, $otp, __('Your password reset code', 'custom-woocommerce'));
            $message = '<p class="cw-form-success">' . esc_html__('OTP sent to your email.', 'custom-woocommerce') . '</p>';
            $step = 'forgot_verify';
            $_POST['cw_email'] = $email; // Preserve email for next step
        }
    } elseif ('forgot_verify' === $step && isset($_POST['cw_forgot_verify_nonce']) && wp_verify_nonce($_POST['cw_forgot_verify_nonce'], 'cw_forgot_verify')) {
        $email = sanitize_email($_POST['cw_email'] ?? '');
        $otp = sanitize_text_field($_POST['cw_otp'] ?? '');
        $new_password = $_POST['cw_new_password'] ?? '';
        
        // Debug: Log the verification attempt
        error_log("Verifying OTP for email: $email, OTP: $otp");
        
        [$valid, $data] = custom_woocommerce_verify_otp('forgot', $email, $otp);
        
        // Debug: Log the result
        error_log("OTP verification result: " . ($valid ? 'valid' : 'invalid'));
        
        if (!$valid) {
            $message = '<p class="cw-form-error">' . esc_html__('Invalid or expired OTP.', 'custom-woocommerce') . '</p>';
            $step = 'forgot_verify'; // Keep on verify step
        } else {
            $user = get_user_by('email', $email);
            if ($user && !empty($new_password)) {
                wp_set_password($new_password, $user->ID);
            }
            if ($user) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                wp_safe_redirect(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/'));
                exit;
            }
        }
    }

    ob_start();
    echo $message;
    ?>
    <form class="cw-auth-form" method="post">
        <?php if ('forgot_verify' !== $step) : ?>
            <?php wp_nonce_field('cw_login', 'cw_login_nonce'); ?>
            <input type="hidden" name="cw_login_step" value="login">
            <label for="cw-login"><?php esc_html_e('Email or Username', 'custom-woocommerce'); ?></label>
            <input type="text" id="cw-login" name="cw_login" required>

            <label for="cw-login-password"><?php esc_html_e('Password', 'custom-woocommerce'); ?></label>
            <input type="password" id="cw-login-password" name="cw_password" required>

            <button type="submit" class="button button-accent"><?php esc_html_e('Login', 'custom-woocommerce'); ?></button>
        <?php elseif ('forgot_verify' === $step) : ?>
            <?php wp_nonce_field('cw_forgot_verify', 'cw_forgot_verify_nonce'); ?>
            <input type="hidden" name="cw_login_step" value="forgot_verify">
            <input type="hidden" name="cw_email" value="<?php echo esc_attr($_POST['cw_email'] ?? ''); ?>">

            <label for="cw-forgot-otp"><?php esc_html_e('OTP Code', 'custom-woocommerce'); ?></label>
            <input type="text" id="cw-forgot-otp" name="cw_otp" required>

            <label for="cw-new-password"><?php esc_html_e('New Password (optional)', 'custom-woocommerce'); ?></label>
            <input type="password" id="cw-new-password" name="cw_new_password">

            <button type="submit" class="button button-accent"><?php esc_html_e('Verify & Continue', 'custom-woocommerce'); ?></button>
        <?php endif; ?>
    </form>

    <?php if ('forgot_verify' !== $step) : ?>
        <form class="cw-auth-form cw-auth-alt" method="post">
            <?php wp_nonce_field('cw_forgot', 'cw_forgot_nonce'); ?>
            <input type="hidden" name="cw_login_step" value="forgot_send">
            <label for="cw-forgot-email"><?php esc_html_e('Forgot password? Enter your email', 'custom-woocommerce'); ?></label>
            <input type="email" id="cw-forgot-email" name="cw_forgot_email" required>
            <button type="submit" class="button"><?php esc_html_e('Send OTP', 'custom-woocommerce'); ?></button>
        </form>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('cw_login', 'custom_woocommerce_login_shortcode');

function custom_woocommerce_add_product_form_shortcode()
{
    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        return '<p>' . esc_html__('You do not have permission to access this page.', 'custom-woocommerce') . '</p>';
    }

    if (!class_exists('WooCommerce')) {
        return '<p>' . esc_html__('WooCommerce is required.', 'custom-woocommerce') . '</p>';
    }

    $message = '';
    $redirect_after_submit = false;

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
                try {
                    // Use WooCommerce Product class
                    $product = new WC_Product_Simple();
                    $product->set_name($title);
                    $product->set_description($description);
                    $product->set_regular_price($price);
                    $product->set_status('publish');
                    
                    if (!empty($sku)) {
                        $product->set_sku($sku);
                    }
                    
                    // Set stock management
                    $product->set_manage_stock(false);
                    $product->set_stock_status('instock');
                    
                    // Save product first to get ID
                    $product_id = $product->save();

                    // Handle category
                    if (!empty($category)) {
                        $term = term_exists($category, 'product_cat');
                        if (!$term) {
                            $term = wp_insert_term($category, 'product_cat');
                        }
                        if (!is_wp_error($term)) {
                            $term_id = is_array($term) ? $term['term_id'] : $term;
                            $product->set_category_ids([(int) $term_id]);
                        }
                    }

                    // Handle main image upload
                    if (!empty($_FILES['cw_product_image']['name'])) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $attachment_id = media_handle_upload('cw_product_image', $product_id);
                        if (!is_wp_error($attachment_id)) {
                            $product->set_image_id($attachment_id);
                        }
                    }
                    
                    // Handle gallery images upload
                    if (!empty($_FILES['cw_product_gallery']['name'][0])) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $gallery_ids = [];
                        $files = $_FILES['cw_product_gallery'];
                        
                        foreach ($files['name'] as $key => $value) {
                            if ($files['name'][$key]) {
                                $file = [
                                    'name'     => $files['name'][$key],
                                    'type'     => $files['type'][$key],
                                    'tmp_name' => $files['tmp_name'][$key],
                                    'error'    => $files['error'][$key],
                                    'size'     => $files['size'][$key]
                                ];
                                
                                $_FILES = ['upload_file' => $file];
                                
                                $gallery_attachment_id = media_handle_upload('upload_file', $product_id);
                                
                                if (!is_wp_error($gallery_attachment_id)) {
                                    $gallery_ids[] = $gallery_attachment_id;
                                }
                            }
                        }
                        
                        if (!empty($gallery_ids)) {
                            $product->set_gallery_image_ids($gallery_ids);
                        }
                    }
                    
                    // Handle video upload
                    if (!empty($_FILES['cw_product_video']['name'])) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        require_once ABSPATH . 'wp-admin/includes/media.php';

                        $video_attachment_id = media_handle_upload('cw_product_video', $product_id);
                        if (!is_wp_error($video_attachment_id)) {
                            update_post_meta($product_id, '_product_video_id', $video_attachment_id);
                        }
                    }
                    
                    // Save again with all updates
                    $product->save();

                    $message = '<p class="cw-form-success">' . esc_html__('Product created successfully! ', 'custom-woocommerce') . '<a href="' . esc_url(get_permalink($product_id)) . '">' . esc_html__('View Product', 'custom-woocommerce') . '</a></p>';
                    $redirect_after_submit = true;
                } catch (Exception $e) {
                    $message = '<p class="cw-form-error">' . esc_html__('Failed to create product: ', 'custom-woocommerce') . esc_html($e->getMessage()) . '</p>';
                }
            }
        }
    }

    // Redirect to refresh the form after successful submission
    if ($redirect_after_submit) {
        wp_safe_redirect(add_query_arg('product_added', '1', $_SERVER['REQUEST_URI']));
        exit;
    }

    // Show success message if redirected after adding product
    if (isset($_GET['product_added']) && $_GET['product_added'] == '1') {
        $message = '<p class="cw-form-success">' . esc_html__('Product created successfully!', 'custom-woocommerce') . '</p>';
    }

    ob_start();
    echo $message;
    ?>
    <form class="cw-add-product-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('cw_add_product', 'cw_add_product_nonce'); ?>
        
        <div class="cw-image-upload-section">
            <label class="cw-image-label">
                <?php esc_html_e('Main Product Image', 'custom-woocommerce'); ?>
            </label>
            <div class="cw-image-preview" id="cw-main-image-preview" aria-hidden="true"></div>
            <input type="file" id="cw-product-image" name="cw_product_image" accept="image/*" required>
            
            <label class="cw-image-label" style="margin-top: 20px;">
                <?php esc_html_e('Product Gallery Images (Optional)', 'custom-woocommerce'); ?>
            </label>
            <input type="file" id="cw-product-gallery" name="cw_product_gallery[]" accept="image/*" multiple>
            <div class="cw-gallery-preview" id="cw-gallery-preview"></div>
            <p style="color: #666; font-size: 0.9rem; margin: 8px 0 0;">
                <?php esc_html_e('You can select multiple images for the product gallery', 'custom-woocommerce'); ?>
            </p>
            
            <label class="cw-image-label" style="margin-top: 20px;">
                <?php esc_html_e('Product Video (Optional)', 'custom-woocommerce'); ?>
            </label>
            <input type="file" id="cw-product-video" name="cw_product_video" accept="video/*">
            <div class="cw-video-preview" id="cw-video-preview"></div>
            <p style="color: #666; font-size: 0.9rem; margin: 8px 0 0;">
                <?php esc_html_e('Upload a video to showcase your product', 'custom-woocommerce'); ?>
            </p>
        </div>

        <label for="cw-product-title"><?php esc_html_e('Title', 'custom-woocommerce'); ?></label>
        <input type="text" id="cw-product-title" name="cw_product_title" required>

        <label for="cw-product-price"><?php esc_html_e('Price', 'custom-woocommerce'); ?></label>
        <input type="number" step="0.01" id="cw-product-price" name="cw_product_price" required>

        <label for="cw-product-sku"><?php esc_html_e('SKU', 'custom-woocommerce'); ?></label>
        <input type="text" id="cw-product-sku" name="cw_product_sku">

        <label for="cw-product-category"><?php esc_html_e('Category', 'custom-woocommerce'); ?></label>
        <select id="cw-product-category" name="cw_product_category">
            <option value=""><?php esc_html_e('Select a category', 'custom-woocommerce'); ?></option>
            <?php
            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);
            foreach ($categories as $cat) {
                echo '<option value="' . esc_attr($cat->name) . '">' . esc_html($cat->name) . '</option>';
            }
            ?>
        </select>

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

// AJAX handler for bulk product upload
function custom_woocommerce_handle_bulk_product()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cw_add_bulk_product')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    // Check permissions
    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        return;
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(['message' => 'WooCommerce is not active.']);
        return;
    }

    // Get form data
    $title = isset($_POST['cw_product_title']) ? sanitize_text_field($_POST['cw_product_title']) : '';
    $price = isset($_POST['cw_product_price']) ? wc_format_decimal($_POST['cw_product_price']) : '';
    $sku = isset($_POST['cw_product_sku']) ? sanitize_text_field($_POST['cw_product_sku']) : '';
    $description = isset($_POST['cw_product_description']) ? wp_kses_post($_POST['cw_product_description']) : '';
    $category = isset($_POST['cw_product_category']) ? sanitize_text_field($_POST['cw_product_category']) : '';
    $image_data = isset($_POST['cw_bulk_image_data']) ? $_POST['cw_bulk_image_data'] : '';

    // Validate required fields
    if (empty($title) || empty($price)) {
        wp_send_json_error(['message' => 'Title and price are required.']);
        return;
    }

    try {
        // Use WooCommerce Product class
        $product = new WC_Product_Simple();
        $product->set_name($title);
        $product->set_description($description);
        $product->set_regular_price($price);
        $product->set_status('publish');
        
        if (!empty($sku)) {
            $product->set_sku($sku);
        }
        
        // Set stock management
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        // Save product first to get ID
        $product_id = $product->save();

        // Handle category
        if (!empty($category)) {
            $term = term_exists($category, 'product_cat');
            if (!$term) {
                $term = wp_insert_term($category, 'product_cat');
            }
            if (!is_wp_error($term)) {
                $term_id = is_array($term) ? $term['term_id'] : $term;
                $product->set_category_ids([(int) $term_id]);
            }
        }

        // Handle image upload from base64 data
        if (!empty($image_data)) {
            // Extract base64 data
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $image_data, $matches)) {
                $image_type = $matches[1];
                $image_base64 = $matches[2];
                $image_decoded = base64_decode($image_base64);

                if ($image_decoded !== false) {
                    // Create unique filename
                    $filename = 'product-' . $product_id . '-' . time() . '.' . $image_type;
                    $upload_dir = wp_upload_dir();
                    $upload_path = $upload_dir['path'] . '/' . $filename;

                    // Save image file
                    if (file_put_contents($upload_path, $image_decoded)) {
                        // Create attachment
                        $attachment_id = wp_insert_attachment([
                            'post_mime_type' => 'image/' . $image_type,
                            'post_title' => sanitize_file_name($filename),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        ], $upload_path, $product_id);

                        if (!is_wp_error($attachment_id)) {
                            require_once ABSPATH . 'wp-admin/includes/image.php';
                            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_path);
                            wp_update_attachment_metadata($attachment_id, $attach_data);
                            $product->set_image_id($attachment_id);
                        }
                    }
                }
            }
        }
        
        // Save again with all updates
        $product->save();

        wp_send_json_success([
            'message' => 'Product created successfully.',
            'product_id' => $product_id
        ]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Failed to create product: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_cw_add_bulk_product', 'custom_woocommerce_handle_bulk_product');

// AJAX handler to get product data for editing
function custom_woocommerce_get_product_for_edit()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cw_add_bulk_product')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found.']);
        return;
    }

    $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
    
    wp_send_json_success([
        'id' => $product->get_id(),
        'title' => $product->get_name(),
        'price' => $product->get_regular_price(),
        'sku' => $product->get_sku(),
        'description' => $product->get_description(),
        'category' => !empty($categories) ? $categories[0] : '',
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium')
    ]);
}
add_action('wp_ajax_cw_get_product_for_edit', 'custom_woocommerce_get_product_for_edit');

// AJAX handler to update product
function custom_woocommerce_update_product()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cw_add_bulk_product')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found.']);
        return;
    }

    $title = isset($_POST['product_title']) ? sanitize_text_field($_POST['product_title']) : '';
    $price = isset($_POST['product_price']) ? wc_format_decimal($_POST['product_price']) : '';
    $sku = isset($_POST['product_sku']) ? sanitize_text_field($_POST['product_sku']) : '';
    $description = isset($_POST['product_description']) ? wp_kses_post($_POST['product_description']) : '';
    $category = isset($_POST['product_category']) ? sanitize_text_field($_POST['product_category']) : '';

    if (empty($title) || empty($price)) {
        wp_send_json_error(['message' => 'Title and price are required.']);
        return;
    }

    // Update product
    wp_update_post([
        'ID' => $product_id,
        'post_title' => $title,
        'post_content' => $description,
    ]);

    update_post_meta($product_id, '_regular_price', $price);
    update_post_meta($product_id, '_price', $price);
    update_post_meta($product_id, '_sku', $sku);

    // Update category
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

    // Handle image upload
    if (!empty($_FILES['product_image']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('product_image', $product_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    wp_send_json_success(['message' => 'Product updated successfully.']);
}
add_action('wp_ajax_cw_update_product', 'custom_woocommerce_update_product');

// AJAX handler to delete product
function custom_woocommerce_delete_product()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cw_add_bulk_product')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found.']);
        return;
    }

    // Delete product
    $deleted = wp_delete_post($product_id, true);
    
    if (!$deleted) {
        wp_send_json_error(['message' => 'Failed to delete product.']);
        return;
    }

    wp_send_json_success(['message' => 'Product deleted successfully.']);
}
add_action('wp_ajax_cw_delete_product', 'custom_woocommerce_delete_product');

// AJAX handler to get product categories
function custom_woocommerce_get_categories()
{
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);

    $cat_list = [];
    foreach ($categories as $cat) {
        $cat_list[] = [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
        ];
    }

    wp_send_json_success($cat_list);
}
add_action('wp_ajax_cw_get_categories', 'custom_woocommerce_get_categories');

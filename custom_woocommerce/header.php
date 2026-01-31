<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header" data-animate="fade-down">
    <div class="top-bar">
        <div class="container top-bar-inner">
            <div class="brand" data-animate="fade-right">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php endif; ?>
                <div class="brand-text">
                    <p class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></p>
                    <p class="site-description"><?php bloginfo('description'); ?></p>
                </div>
            </div>
            <div class="header-actions" data-animate="fade-left">
                <div class="header-search">
                    <?php
                    if (function_exists('get_product_search_form')) {
                        get_product_search_form();
                    } else {
                        get_search_form();
                    }
                    ?>
                </div>
                <?php if (current_user_can('manage_options')) : ?>
                    <a class="button button-accent" href="<?php echo esc_url(home_url('/product-manager/')); ?>">
                        <?php esc_html_e('Add Product', 'custom-woocommerce'); ?>
                    </a>
                <?php endif; ?>
                <?php
                $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
                $login_url = home_url('/login/');
                $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
                $cart_count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
                $avatar_url = is_user_logged_in() ? custom_woocommerce_get_user_avatar_url(get_current_user_id()) : get_avatar_url(0, ['size' => 48]);
                $avatar_link = is_user_logged_in() ? $account_url : $login_url;
                $register_url = home_url('/registeration/');
                ?>
                <?php if (!is_user_logged_in()) : ?>
                    <a class="button button-outline" href="<?php echo esc_url($login_url); ?>">
                        <?php esc_html_e('Login', 'custom-woocommerce'); ?>
                    </a>
                    <a class="button button-accent" href="<?php echo esc_url($register_url); ?>">
                        <?php esc_html_e('Sign Up', 'custom-woocommerce'); ?>
                    </a>
                <?php endif; ?>
                <a class="header-avatar" href="<?php echo esc_url($avatar_link); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('My Account', 'custom-woocommerce'); ?></span>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php esc_attr_e('Profile', 'custom-woocommerce'); ?>">
                </a>
                <a class="header-link cart-link" href="<?php echo esc_url($cart_url); ?>">
                    <span class="icon">🛒</span>
                    <span class="cart-count"><?php echo esc_html($cart_count); ?></span>
                </a>
            </div>
        </div>
    </div>
    <div class="nav-bar">
        <div class="container">
            <nav class="main-nav" aria-label="Primary">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_class' => 'menu nav-menu',
                    'container' => false,
                    'fallback_cb' => 'custom_woocommerce_primary_menu_fallback',
                ]);
                ?>
            </nav>
        </div>
    </div>
</header>

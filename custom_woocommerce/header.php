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
                <a class="button button-accent" href="<?php echo esc_url(home_url('/add-product/')); ?>">
                    <?php esc_html_e('Add Product', 'custom-woocommerce'); ?>
                </a>
                <?php
                $account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
                $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
                $cart_count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
                ?>
                <a class="header-link" href="<?php echo esc_url($account_url); ?>">
                    <span class="icon">👤</span>
                    <?php esc_html_e('Account', 'custom-woocommerce'); ?>
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
                    'fallback_cb' => '__return_false',
                ]);
                ?>
            </nav>
        </div>
    </div>
</header>

<?php
/**
 * My Account navigation
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<nav class="cw-account-nav" aria-label="<?php esc_attr_e('My Account', 'custom-woocommerce'); ?>">
    <div class="cw-account-card">
        <div class="cw-account-header"></div>
        <div class="cw-account-avatar" aria-hidden="true"></div>
        <?php
        wp_nav_menu([
            'theme_location' => 'myaccount',
            'container' => false,
            'fallback_cb' => false,
            'items_wrap' => '<ul class="cw-account-menu">%3$s</ul>',
        ]);
        if (!has_nav_menu('myaccount')) :
            ?>
            <ul class="cw-account-menu">
                <?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
                    <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
                            <?php echo esc_html($label); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</nav>

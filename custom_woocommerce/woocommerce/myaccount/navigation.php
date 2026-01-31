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
        <?php
        $user_id = get_current_user_id();
        $avatar_url = $user_id ? custom_woocommerce_get_user_avatar_url($user_id) : '';
        ?>
        <form class="cw-avatar-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('cw_avatar_upload', 'cw_avatar_nonce'); ?>
            <label class="cw-account-avatar" for="cw-profile-avatar" style="background-image: url('<?php echo esc_url($avatar_url); ?>');">
                <span class="cw-avatar-overlay">+</span>
            </label>
            <input type="file" id="cw-profile-avatar" name="cw_profile_avatar" accept="image/*" aria-label="<?php esc_attr_e('Upload profile picture', 'custom-woocommerce'); ?>">
        </form>
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
        <div class="cw-account-panel"></div>
    </div>
</nav>

<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand" data-animate="fade-up">
            <p class="footer-title"><?php bloginfo('name'); ?></p>
            <p class="footer-description"><?php bloginfo('description'); ?></p>
        </div>
        <div class="footer-links" data-animate="fade-up">
            <p class="footer-heading"><?php esc_html_e('Quick Links', 'custom-woocommerce'); ?></p>
            <nav aria-label="Footer">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'menu_class' => 'menu footer-menu',
                    'container' => false,
                    'fallback_cb' => '__return_false',
                ]);
                ?>
            </nav>
        </div>
        <div class="footer-meta" data-animate="fade-up">
            <p>&copy; <?php echo esc_html(date('Y')); ?> <?php bloginfo('name'); ?></p>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

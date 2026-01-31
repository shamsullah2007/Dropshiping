<?php
get_header();
?>
<main id="primary" class="content">
    <div class="container">
        <h1><?php esc_html_e('Page not found', 'custom-woocommerce'); ?></h1>
        <p><?php esc_html_e('Sorry, the page you are looking for does not exist.', 'custom-woocommerce'); ?></p>
        <?php get_search_form(); ?>
    </div>
</main>
<?php
get_footer();

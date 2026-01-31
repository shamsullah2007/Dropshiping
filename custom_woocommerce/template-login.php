<?php
/**
 * Template Name: Login Page
 */

if (is_user_logged_in()) {
    wp_redirect(home_url('/my-account/'));
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    <div class="container" style="max-width: 500px; margin: 60px auto; padding: 0 20px;">
        <?php echo do_shortcode('[cw_login]'); ?>
    </div>
</main>

<?php
get_footer();

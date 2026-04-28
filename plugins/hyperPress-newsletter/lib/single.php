<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<?php get_template_part( 'template-parts/banner' ); ?>
    <div class="main-container">
        <div class="main-grid">
            <main class="main-content">
                <?php get_template_part( 'template-parts/breadcrumb' ); ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php include_once( 'content.php' ); ?>
                    <?php get_template_part( 'template-parts/edit-link' ); ?>
                    <?php get_template_part( 'template-parts/post-navigation' ) ?>
                <?php endwhile; ?>
            </main>
            <?php get_sidebar(); ?>
        </div>
    </div>
<?php get_footer();

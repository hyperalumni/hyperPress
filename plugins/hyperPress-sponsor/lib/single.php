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
                    <div class="entry-content">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( '', array('class' => 'thumbnail') ); ?>
                        <?php endif; ?>
                        <?php the_content(); ?>
                    </div>
                    <?php get_template_part( 'template-parts/edit-link' ); ?>
                <?php endwhile; ?>
            </main>
        </div>
    </div>
<?php get_footer();
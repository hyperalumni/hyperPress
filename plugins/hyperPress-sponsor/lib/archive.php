<?php

get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<div class="main-container">
	<div class="main-grid">
		<main class="main-content">
			<?php get_template_part( 'template-parts/breadcrumb' ); ?>
			<?php if ( have_posts() ) : ?>
                <?php /* Start the Loop */ ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php if ( ! empty( get_post_type() ) ) : ?>
                        <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
                    <?php elseif ( has_post_format() )  : ?>
                        <?php get_template_part( 'template-parts/content', get_post_format() ); ?>
                    <?php else : ?>
                        <?php get_template_part( 'template-parts/content' ); ?>
                    <?php endif; ?>
                    <hr/>
                <?php endwhile; ?>

            <?php else : ?>
                <?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; // End have_posts() check. ?>
			<?php /* Display navigation to next/previous pages when applicable */ ?>
			<?php
			if ( function_exists( 'foundationpress_pagination' ) ) :
				foundationpress_pagination();
			elseif ( is_paged() ) :
				?>
				<nav id="post-nav">
					<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'foundationpress' ) ); ?></div>
					<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'foundationpress' ) ); ?></div>
				</nav>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>

			<?php endif; // End have_posts() check. ?>

		</main>
		<?php get_sidebar(); ?>

	</div>
</div>

<?php get_footer();

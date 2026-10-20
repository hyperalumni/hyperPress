<?php
/**
 * The template for displaying search results pages.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php do_action( 'hyperpress_before_content' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<main id="search-results" class="main-content">
				<?php do_action( 'hyperpress_before_main' ); ?>
				<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
				<?php if ( have_posts() ) : ?>

					<?php
                    while ( have_posts() ) :
the_post();
?>
						<?php get_template_part( 'template-parts/content', get_post_format() ); ?>

					<?php endwhile; ?>

				<?php else : ?>
					<?php get_template_part( 'template-parts/content', 'none' ); ?>

				<?php endif; ?>

				<?php
				if ( function_exists( 'hyperpress_pagination' ) ) :
					hyperpress_pagination();
				elseif ( is_paged() ) :
					?>
					<nav id="post-nav">
						<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'hyperpress' ) ); ?></div>
						<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'hyperpress' ) ); ?></div>
					</nav>
				<?php endif; ?>

				<?php do_action( 'hyperpress_after_main' ); ?>
			</main>
			<?php get_sidebar(); ?>

		</div>
	</div>
<?php do_action( 'hyperpress_after_content' ); ?>
<?php
get_footer();

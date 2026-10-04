<?php
/**
 * The template for displaying search results pages.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<main id="search-results" class="main-content">
				<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
				<?php if ( have_posts() ) : ?>

					<?php while ( have_posts() ) : the_post(); ?>
						<?php get_template_part( 'template-parts/content', get_post_format() ); ?>

					<?php endwhile; ?>

				<?php else : ?>
					<?php get_template_part( 'template-parts/content', 'none' ); ?>

				<?php endif; ?>

				<?php
				if ( function_exists( 'foundationpress_pagination' ) ) :
					foundationpress_pagination();
				elseif ( is_paged() ) :
					?>
					<nav id="post-nav">
						<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'foundationpress' ) ); ?></div>
						<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'foundationpress' ) ); ?></div>
					</nav>
				<?php endif; ?>

			</main>
			<?php get_sidebar(); ?>

		</div>
	</div>
<?php get_footer();

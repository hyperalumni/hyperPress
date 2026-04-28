<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<main class="main-content">
				<?php get_template_part( 'template-parts/breadcrumb' ); ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/content', '' ); ?>
					<?php get_template_part( 'template-parts/post-navigation' ) ?>
					<?php comments_template(); ?>
				<?php endwhile; ?>
			</main>
			<?php get_sidebar(); ?>
		</div>
	</div>
<?php get_footer();

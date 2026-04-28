<?php
/*
Template Name: Full Width
*/
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php get_template_part( 'template-parts/featured-image' ); ?>
<div class="main-container full-width">
	<div class="main-grid">
		<main class="main-content">
			<?php get_template_part( 'template-parts/breadcrumb' ); ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'template-parts/content', 'page' ); ?>
				<?php comments_template(); ?>
			<?php endwhile; ?>
		</main>
	</div>
</div>
<?php get_footer();

<?php
/*
Template Name: Full Screen
*/

// Don't load directly.
defined( 'ABSPATH' ) || exit;

get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php get_template_part( 'template-parts/featured-image' ); ?>
<?php do_action( 'hyperpress_before_content' ); ?>
	<div class="main-container full-screen">
		<div class="main-grid">
			<main class="main-content">
				<?php do_action( 'hyperpress_before_main' ); ?>
				<?php
                while ( have_posts() ) :
the_post();
?>
					<?php get_template_part( 'template-parts/content', 'page' ); ?>
					<?php comments_template(); ?>
				<?php endwhile; ?>
				<?php do_action( 'hyperpress_after_main' ); ?>
			</main>
		</div>
	</div>
<?php do_action( 'hyperpress_after_content' ); ?>
<?php
get_footer();

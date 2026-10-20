<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php get_template_part( 'template-parts/featured-image' ); ?>
<?php do_action( 'hyperpress_before_content' ); ?>
<div class="main-container">
	<div class="main-grid">
		<main class="main-content">
			<?php do_action( 'hyperpress_before_main' ); ?>
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<?php
            while ( have_posts() ) :
the_post();
?>
				<?php get_template_part( 'template-parts/content', 'page' ); ?>
				<?php comments_template(); ?>
			<?php endwhile; ?>
			<?php do_action( 'hyperpress_after_main' ); ?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>
<?php do_action( 'hyperpress_after_content' ); ?>
<?php
get_footer();

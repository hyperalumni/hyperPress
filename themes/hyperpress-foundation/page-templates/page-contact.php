<?php
/*
Template Name: Contact
*/

// Don't load directly.
defined( 'ABSPATH' ) || exit;

get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php
if ( shortcode_exists( 'hyperpress_map' ) ) {
	echo do_shortcode( '[hyperpress_map height="300"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is escaped by the plugin
}
?>
<?php do_action( 'hyperpress_before_content' ); ?>
<div class="main-container full-width">
	<div class="main-grid">
		<main class="main-content">
			<?php do_action( 'hyperpress_before_main' ); ?>
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
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

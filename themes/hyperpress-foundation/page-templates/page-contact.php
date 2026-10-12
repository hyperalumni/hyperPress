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
<div class="main-container full-width">
	<div class="main-grid">
		<main class="main-content">
			<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<?php get_template_part( 'template-parts/content', 'page' ); ?>
				<?php comments_template(); ?>
			<?php endwhile; ?>
		</main>
	</div>
</div>
<?php
get_footer();

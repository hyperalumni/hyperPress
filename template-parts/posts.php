<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); /* Start the Loop */ ?>
		<?php if ( ! empty( get_post_type() ) ) : ?>
			<?php
			$content_template = apply_filters( 'content_template', '' );
			if ( ! empty( $content_template ) ) {
				include $content_template;
			} else {
				get_template_part( 'template-parts/content', get_post_type() );
			}
			?>

		<?php elseif ( has_post_format() )  : ?>
			<?php get_template_part( 'template-parts/content', get_post_format() ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content' ); ?>
		<?php endif; ?>
		<div class="section-divider">
			<hr/>
		</div>
	<?php endwhile; ?>

<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>

<?php endif; // End have_posts() check. ?>

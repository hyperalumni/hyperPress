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
		<div class="section-divider">
			<hr/>
		</div>
	<?php endwhile; ?>

<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>

<?php endif; // End have_posts() check. ?>

<?php
/**
 * The default template for displaying content
 *
 * Used for both single and index/archive/search.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header>
	<?php
		if ( is_single() ) {
		the_title( '<h1 class="entry-title">', '</h1>' );
		} else {
		the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		}
	?>
	<?php do_action( 'hyperpress_labels' ); ?>
	</header>
	<div class="entry-content">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'full', array( 'class' => 'thumbnail' ) ); ?>
		<?php endif; ?>
		<?php the_content(); ?>
		<?php get_template_part( 'template-parts/edit-link' ); ?>
	</div>
	<footer>
		<?php
			wp_link_pages(
				array(
					'before' => '<nav id="page-nav"><p>' . __( 'Pages:', 'hyperpress' ),
					'after'  => '</p></nav>',
				)
			);
		?>
		<?php
        $post_tags = get_the_tags(); if ( $post_tags ) {
?>
<p><?php the_tags(); ?></p><?php } ?>
	</footer>
</article>

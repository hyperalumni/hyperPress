<?php
/**
 * The default template for displaying page content
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="entry-content">
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
        $tag = get_the_tags(); if ( $tag ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- theme audit
?>
<p><?php the_tags(); ?></p><?php } ?>
	</footer>
</article>

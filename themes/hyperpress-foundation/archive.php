<?php
/**
 * The template for displaying archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each one. For example, tag.php (Tag archives),
 * category.php (Category archives), author.php (Author archives), etc.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<?php get_template_part( 'template-parts/banner' ); ?>
<?php do_action( 'hyperpress_before_content' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<main class="main-content">
				<?php do_action( 'hyperpress_before_main' ); ?>
				<?php get_template_part( 'template-parts/breadcrumbs' ); ?>
				<?php
				$posts_template = apply_filters( 'hyperpress_posts_template', '' );
				if ( is_string( $posts_template ) && '' !== $posts_template && is_readable( $posts_template ) ) {
					include $posts_template;
				} else {
					get_template_part( 'template-parts/posts' );
				}
				?>

				<?php /* Display navigation to next/previous pages when applicable */ ?>
				<?php
				if ( function_exists( 'hyperpress_pagination' ) ) :
					hyperpress_pagination();
				elseif ( is_paged() ) :
					?>
					<nav id="post-nav">
						<div class="post-previous"><?php next_posts_link( __( '&larr; Older posts', 'hyperpress' ) ); ?></div>
						<div class="post-next"><?php previous_posts_link( __( 'Newer posts &rarr;', 'hyperpress' ) ); ?></div>
					</nav>
				<?php endif; ?>

				<?php do_action( 'hyperpress_after_main' ); ?>
			</main>
			<?php get_sidebar(); ?>

		</div>
	</div>
<?php do_action( 'hyperpress_after_content' ); ?>

<?php
get_footer();

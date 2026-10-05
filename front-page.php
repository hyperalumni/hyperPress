<?php
/*
Template Name: Front
*/

// Don't load directly.
defined( 'ABSPATH' ) || exit;
get_header(); ?>

<?php
// Slider Revolution plugin provides add_revslider(); it may not be active.
if ( function_exists( 'add_revslider' ) ) {
	add_revslider( 'homepage' );
}
?>

<?php get_template_part( 'template-parts/banner' ); ?>
<?php do_action( 'foundationpress_before_content' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<?php $countdowns = get_theme_mod( 'hyperpress_homepage_customize_countdowns' ); ?>
			<?php if ( ! empty( $countdowns ) ) : ?>
				<?php
                while ( have_posts() ) :
the_post();
?>
					<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
						<?php do_action( 'foundationpress_page_before_entry_content' ); ?>
						<div class="entry-content">
							<?php
		foreach ( (array) $countdowns as $countdown_id ) :
				$countdown_id = absint( $countdown_id );
				if ( 0 === $countdown_id ) {
					continue;
				}
				echo do_shortcode( '[hyperpress_countdown id="' . $countdown_id . '" /]' );
			endforeach;
							?>
						</div>
						<?php do_action( 'foundationpress_page_after_entry_content' ); ?>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
			<main class="main-content">
				<?php
				// query posts by the categories selected in the customizer
				$the_query       = new WP_Query(
                    array(
						'category__in' => get_theme_mod( 'hyperpress_home_blog_categories' ),
					)
                    );
				$blog_post_types = get_theme_mod( 'hyperpress_home_blog_post_types' );

				if ( ! empty( $blog_post_types ) ) {
					$blog_custom_post_types = array_filter(
                        $blog_post_types,
                        function ( $post_type ) {
						// filter out custom post types that do not exist
						// filter out 'post' type as that is covered by the query above
						return ! empty( $post_type ) && post_type_exists( $post_type ) && 'post' !== $post_type;
					}
                        );
					if ( ! empty( $blog_custom_post_types ) ) {
						// also include any custom post types selected in the customizer
						$custom_post_types_query = new WP_Query(
                            array(
								'post_type' => $blog_custom_post_types,
							)
                            );
						// combine the two query results
						$combined_post_types = array_merge( $the_query->posts, $custom_post_types_query->posts );
						// then sort them by date
						usort(
                            $combined_post_types,
                            function ( $a, $b ) {
							return strtotime( $b->post_date ) - strtotime( $a->post_date );
						}
                            );
						// update the initial query with the combined results
						$the_query->posts      = $combined_post_types;
						$the_query->post_count = count( $the_query->posts );
					}
				}

				?>
				<?php if ( $the_query->have_posts() ) : ?>
				<?php
                while ( $the_query->have_posts() ) :
						$the_query->the_post();
						?>
						<?php if ( ! empty( get_post_type() ) ) : ?>
						<?php if ( 'post' !== get_post_type() ) : ?>
						<?php
						$content_template = apply_filters( 'content_template', '' );
						if ( is_string( $content_template ) && '' !== $content_template && is_readable( $content_template ) ) {
							include $content_template;
						} else {
							get_template_part( 'template-parts/content', get_post_type() );
						}
						?>
			<?php
                        else :
get_template_part( 'template-parts/content', get_post_type() );
?>
						<?php endif; ?>
					<?php elseif ( has_post_format() ) : ?>
						<?php get_template_part( 'template-parts/content', get_post_format() ); ?>
					<?php else : ?>
						<?php get_template_part( 'template-parts/content' ); ?>
					<?php endif; ?>
					<div class="section-divider">
						<hr/>
					</div>
				<?php endwhile; ?>
				<div class="grid-x align-center">
					<div class="cell small-12 medium-8 large-6 xlarge-4">
						<a class="button hollow large expanded primary"
							href="<?php echo esc_url( (string) get_permalink( get_option( 'page_for_posts' ) ) ); ?>">Read
							More</a>
					</div>
				</div>

			</main>
		<?php get_sidebar(); ?>


		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
		</div>
	</div>
<?php do_action( 'foundationpress_after_content' ); ?>
<?php
get_footer();

<?php
/*
Template Name: Front
*/
get_header(); ?>

<?php add_revslider('homepage'); ?>

<?php get_template_part( 'template-parts/banner' ); ?>
<?php do_action( 'foundationpress_before_content' ); ?>
	<div class="main-container">
		<div class="main-grid">
			<?php while ( have_posts() ) : the_post(); ?>
				<div <?php post_class(); ?> id="post-<?php the_ID(); ?>">
					<?php do_action( 'foundationpress_page_before_entry_content' ); ?>
					<div class="entry-content">
						<?php
						foreach ( rwmb_meta( 'homepage_countdown' ) as $countdown_id ) :
							echo do_shortcode( '[hyperpress_countdown id="' . $countdown_id . '" /]' );
						endforeach;
						?>
						<?php the_content(); ?>
					</div>
					<?php do_action( 'foundationpress_page_after_entry_content' ); ?>
				</div>
			<?php endwhile; ?>
			<main class="main-content">
				<?php
				// query posts by the categories selected in the customizer
				$the_query = new WP_Query( array(
					'category__in' => get_theme_mod( 'hyperpress_home_blog_categories' ),
				) );

				// also include any custom post types selected in the customizer
				$custom_post_types_query = new WP_Query( array(
					'post_type' => array_filter( get_theme_mod( 'hyperpress_home_blog_post_types' ), function ( $post_type ) {
						// filter out 'post' type as that is covered by the query above
						return ! empty( $post_type ) && $post_type != 'post';
					} ),
				) );
				// combine the two query results
				$combined_post_types = array_merge( $the_query->posts, $custom_post_types_query->posts );
				// then sort them by date
				usort( $combined_post_types, function ( $a, $b ) {
					return strtotime( $b->post_date ) - strtotime( $a->post_date );
				} );
				// update the initial query with the combined results
				$the_query->posts      = $combined_post_types;
				$the_query->post_count = count( $the_query->posts );
				?>
				<?php if ( $the_query->have_posts() ) : ?>
				<?php while ( $the_query->have_posts() ) : $the_query->the_post(); ?>
					<?php if ( ! empty( get_post_type() ) ) : ?>
						<?php if ( get_post_type() != 'post' ) : ?>
							<?php include apply_filters( 'content_template', $post ); ?>
						<?php else : get_template_part( 'template-parts/content', get_post_type() ); ?>
						<?php endif; ?>
					<?php elseif ( has_post_format() )  : ?>
						<?php get_template_part( 'template-parts/content', get_post_format() ); ?>
					<?php else : ?>
						<?php get_template_part( 'template-parts/content' ); ?>
					<?php endif; ?>
					<div class="section-divider">
						<hr/>
					</div>
				<?php endwhile; ?>
				<a class="button expanded large primary" href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>">Read
					More</a>
			</main>
		<?php get_sidebar(); ?>


		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
		</div>
	</div>
<?php do_action( 'foundationpress_after_content' ); ?>
<?php get_footer();

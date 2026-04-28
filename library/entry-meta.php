<?php
/**
 * Entry meta information for posts
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

if ( ! function_exists( 'foundationpress_entry_meta' ) ) :
	function foundationpress_entry_meta() { ?>
		<div class="entry-meta">
			<div class="grid-x">
				<div class="cell shrink label dark">
					<time class="updated" datetime="<?php echo get_the_time( 'c' ); ?>">
						<?php echo sprintf( __( '%1$s @ %2$s', 'foundationpress' ), get_the_date(), get_the_time() ) ?>
					</time>
				</div>
				<div class="cell shrink label primary byline author">
					<a href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ) ?>" rel="author" title="Posted By">
						<?php echo get_the_author() ?>
					</a>
				</div>
				<?php $categories = get_the_category(); ?>
				<?php if ( $categories ) : ?>
					<div class="cell shrink label warning">
						<a href="<?php echo get_category_link( $categories[0]->term_id ) ?>" title="Posted In">
							<?php echo $categories[0]->cat_name; ?>
						</a>
					</div>
				<?php endif; ?>
				<div class="cell shrink label neutral">
					<a href="<?php echo get_comments_link() ?>">
						<?php echo get_comments_number() . ( get_comments_number() == 1 ? ' comment' : ' comments' ) ?>
					</a>
				</div>
			</div>
		</div>
	<?php }
endif;

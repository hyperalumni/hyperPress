<?php
/**
 * Sponsor archive loop: sponsors grouped under season and tier headings.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

use HyperPress\Sponsor\ArchiveLoop;
use HyperPress\Sponsor\Grouping;
use HyperPress\Sponsor\Ordering;

global $wp_query;
?>
<?php if ( have_posts() ) : ?>
	<?php
	$hyperpress_scoped   = Ordering::is_season_scoped( $wp_query );
	$hyperpress_previous = null;
	$hyperpress_first    = true;
	while ( have_posts() ) :
		the_post();
		$hyperpress_current = Grouping::keys( get_the_ID() );
		if ( $hyperpress_first && (int) get_query_var( 'paged' ) > 1 ) {
			$hyperpress_before   = ArchiveLoop::previous_sponsor_id( $wp_query );
			$hyperpress_previous = $hyperpress_before ? Grouping::keys( $hyperpress_before ) : null;
		}
		foreach ( Grouping::headings( $hyperpress_current, $hyperpress_previous, $hyperpress_scoped, $hyperpress_first ) as $hyperpress_heading ) {
			printf(
				'<%1$s class="sponsor-group-heading">%2$s</%1$s>',
				esc_attr( $hyperpress_heading['tag'] ),
				esc_html( $hyperpress_heading['text'] )
			);
		}
		$hyperpress_title_tag = 'h4';
		$content_template     = apply_filters( 'content_template', '' );
		if ( is_string( $content_template ) && '' !== $content_template && is_readable( $content_template ) ) {
			include $content_template;
		} else {
			get_template_part( 'template-parts/content', get_post_type() );
		}
		unset( $hyperpress_title_tag );
		$hyperpress_previous = $hyperpress_current;
		$hyperpress_first    = false;
		?>
		<div class="section-divider">
			<hr/>
		</div>
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>
<?php endif; ?>

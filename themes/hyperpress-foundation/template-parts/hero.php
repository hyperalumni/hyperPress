<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

// The hero image, selected per named media query by Interchange. Expects $args['attachment_id'] (an image
// attachment ID); prints nothing for any other value.
$hyperpress_hero_id = ( isset( $args['attachment_id'] ) && is_scalar( $args['attachment_id'] ) ) ? absint( $args['attachment_id'] ) : 0;

if ( $hyperpress_hero_id > 0 && wp_attachment_is_image( $hyperpress_hero_id ) ) :
	$hyperpress_hero_sources = array();
	foreach ( array( 'small', 'medium', 'large', 'xlarge' ) as $hyperpress_hero_name ) {
		$hyperpress_hero_sources[] = '[' . esc_url( (string) wp_get_attachment_image_url( $hyperpress_hero_id, 'featured-' . $hyperpress_hero_name ) ) . ', ' . $hyperpress_hero_name . ']';
	}
	?>
	<header class="featured-hero" role="banner" data-interchange="<?php echo esc_attr( implode( ', ', $hyperpress_hero_sources ) ); ?>">
	</header>
	<?php
endif;

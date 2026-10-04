<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_year' );

function hyperpress_banner_year( $banner ) {
	if ( is_year() ) {
		$banner['type']     = 'year';
		$banner['subtitle'] = __( 'Year', 'hyperpress' ) . ': <span class="emphasis">' . get_the_time( 'Y' ) . '</span>';
	}

	return $banner;
}

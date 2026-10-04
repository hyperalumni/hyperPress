<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_month' );

function hyperpress_banner_month( $banner ) {
	if ( is_month() ) {
		$banner['type']     = 'month';
		$banner['subtitle'] = __( 'Month', 'hyperpress' ) . ': <span class="emphasis">' . get_the_time( 'F Y' ) . '</span>';
	}

	return $banner;
}

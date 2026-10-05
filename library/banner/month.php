<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_month' );

function hyperpress_banner_month( $banner ) {
	if ( is_month() ) {
		$banner['type']     = 'month';
		$banner['subtitle'] = __( 'Month', 'hyperpress' ) . ': <span class="emphasis">' . esc_html( get_the_time( 'F Y' ) ) . '</span>';
	}

	return $banner;
}

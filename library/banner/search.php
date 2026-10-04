<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_search' );

function hyperpress_banner_search( $banner ) {
	if ( is_search() ) {
		$banner['type']     = 'search';
		$banner['title']    = __( 'Search', 'hyperpress' );
		$banner['subtitle'] = __( 'Results for', 'hyperpress' ) . ' <span class="emphasis">"' . get_search_query() . '"</span>';
	}

	return $banner;
}

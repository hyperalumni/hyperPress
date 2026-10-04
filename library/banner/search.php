<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_search' );

function hyperpress_banner_search( $banner ) {
	if ( is_search() ) {
		// banner.php runs do_shortcode() on the subtitle, so neutralise shortcode brackets in the visitor's query.
		$query = str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), esc_html( get_search_query() ) );

		$banner['type']     = 'search';
		$banner['title']    = __( 'Search', 'hyperpress' );
		$banner['subtitle'] = __( 'Results for', 'hyperpress' ) . ' <span class="emphasis">"' . $query . '"</span>';
	}

	return $banner;
}

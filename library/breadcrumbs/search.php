<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_search' );

function hyperpress_breadcrumbs_search( $breadcrumbs ) {
	if ( is_search() ) {
		$breadcrumbs[] = array(
			'title'   => __( 'Search', 'hyperpress' ),
			'classes' => array(
				'li'    => array( 'item-search' ),
				'bread' => array( 'bread-search' ),
			),
		);

		$search        = get_search_query();
		$breadcrumbs[] = array(
			'title'   => __( 'Results for', 'hyperpress' ) . ': ' . $search,
			'url'     => get_search_link(),
			'classes' => array(
				'li'    => array( 'item-search', 'item-search-' . $search ),
				'bread' => array( 'bread-search', 'bread-search-' . $search ),
			),
		);
	}

	return $breadcrumbs;
}

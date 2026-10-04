<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_breadcrumbs_search' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_search' );

	function hyperpress_breadcrumbs_search( $breadcrumbs ) {
		if ( is_search() ) {
			$breadcrumbs[] = [
				"title"   => __( 'Search', 'hyperpress' ),
				"classes" => [
					'li'    => [ 'item-search' ],
					'bread' => [ 'bread-search' ]
				]
			];

			$search        = get_search_query();
			$breadcrumbs[] = [
				"title"   => __( 'Results for', 'hyperpress' ) . ': ' . $search,
				"url"     => get_search_link(),
				"classes" => [
					'li'    => [ 'item-search', 'item-search-' . $search ],
					'bread' => [ 'bread-search', 'bread-search-' . $search ]
				]
			];
		}

		return $breadcrumbs;
	}
endif;

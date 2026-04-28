<?php
if ( ! function_exists( 'hyperpress_breadcrumb_search' ) ) :
	function hyperpress_breadcrumb_search( $breadcrumbs ) {
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

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_search' );
endif;

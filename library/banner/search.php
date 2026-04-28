<?php
if ( ! function_exists( 'hyperpress_banner_404' ) ) :
	function hyperpress_banner_search( $banner ) {
		if ( is_search() ) {
			$banner['type']            = 'search';
			$banner['title']           = __( 'Search', 'hyperpress' );
			$banner['subtitle']        = __( 'Results for', 'hyperpress' ) . ': ' . get_search_query();
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_404' );
endif;

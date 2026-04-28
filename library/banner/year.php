<?php
if ( ! function_exists( 'hyperpress_banner_year' ) ) :
	function hyperpress_banner_year( $banner ) {
		if ( is_year() ) {
			$banner['type']     = 'year';
			$banner['subtitle'] = __( 'Year', 'hyperpress' ) . ': <span class="emphasis">' . get_the_time( 'Y' ) . '</span>';
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_year' );
endif;

<?php
if ( ! function_exists( 'hyperpress_banner_day' ) ) :
	function hyperpress_banner_day( $banner ) {
		if ( is_day() ) {
			$banner['type']     = 'day';
			$banner['subtitle'] = __( 'Day', 'hyperpress' ) . ': <span class="emphasis">' . get_the_time( 'F d, Y' ) . '</span>';
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_day' );
endif;

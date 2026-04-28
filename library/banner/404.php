<?php
if ( ! function_exists( 'hyperpress_banner_404' ) ) :
	function hyperpress_banner_404( $banner ) {
		if ( is_404() ) {
			$banner['type']            = 'error';
			$banner['title']           = __( 'Error 404', 'hyperpress' );
			$banner['subtitle']        = __( 'Content not found', 'hyperpress' );
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_hyper_red' );
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_404' );
endif;

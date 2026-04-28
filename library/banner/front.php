<?php
if ( ! function_exists( 'hyperpress_banner_front' ) ) :
	function hyperpress_banner_front( $banner ) {
		if ( is_front_page() ) {
			$banner['type']            = 'front';
			$banner['title']           = get_theme_mod( 'hyperpress_home_banner_title' );
			$banner['subtitle']        = get_theme_mod( 'hyperpress_home_banner_subtitle' );
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_home_banner_background_color' );
			$banner['buttonText']      = get_theme_mod( 'hyperpress_home_banner_button_text' );
			$banner['buttonLink']      = get_theme_mod( 'hyperpress_home_banner_button_link' );
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_front' );
endif;

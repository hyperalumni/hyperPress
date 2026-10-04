<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_banner_front' ) ) :
	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_front' );

	function hyperpress_banner_front( $banner ) {
		if ( is_front_page() ) {
			$banner['type']            = 'front';
			$banner['title']           = get_theme_mod( 'hyperpress_homepage_customize_banner_title' );
			$banner['subtitle']        = get_theme_mod( 'hyperpress_homepage_customize_banner_subtitle' );
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_homepage_customize_banner_background_color' );
			$banner['buttonText']      = get_theme_mod( 'hyperpress_homepage_customize_banner_button_text' );
			$banner['buttonLink']      = get_post_permalink( get_theme_mod( 'hyperpress_homepage_customize_banner_button_page' ) );
		}

		return $banner;
	}
endif;

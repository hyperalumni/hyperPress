<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_banner_sponsor' ) ) :
	function hyperpress_banner_sponsor( $banner ) {
		$hyper_sponsor = 'sponsor';
		if ( is_singular( $hyper_sponsor ) ) {
			$banner['type']     = $hyper_sponsor;
			$banner['subtitle'] = get_theme_mod( 'hyperpress_sponsor_banner_subtitle' );
		}
		if ( is_post_type_archive( $hyper_sponsor ) ) {
			$banner['type']  = $hyper_sponsor;
			$banner['title'] = __( 'Sponsors', 'hyperpress' );
			$banner['subtitle'] = get_theme_mod( 'hyperpress_sponsor_banner_subtitle' );
		}
		if ( is_tax( $hyper_sponsor . '_season' ) ) {
			$banner['type']  = $hyper_sponsor;
			$banner['title'] = __( 'Sponsors', 'hyperpress' );
			$banner['subtitle'] = get_theme_mod( 'hyperpress_sponsor_banner_subtitle' );
		}


		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_sponsor' );
endif;
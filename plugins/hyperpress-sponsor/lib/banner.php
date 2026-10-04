<?php

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_banner_sponsor' ) ) :
	function hyperpress_banner_sponsor( $banner ) {
		$hyper_sponsor = 'sponsor';
		$hyper_season  = 'season';
		if ( is_singular( $hyper_sponsor ) || is_post_type_archive( $hyper_sponsor ) ) {
			$banner['type']            = $hyper_sponsor;
			$subtitle                  = get_theme_mod( 'hyperpress_sponsor_banner_subtitle' );
			$banner['subtitle']        = $subtitle;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_hyper_red' );

			if ( is_post_type_archive( $hyper_sponsor ) ) {
				if ( empty( $banner['title'] ) ) {
					$banner['title'] = __( 'Sponsors', 'hyperpress' );
					if ( is_tax( $hyper_season ) ) {
						if ( ! empty( $subtitle ) ) {
							$banner['subtitle'] = $subtitle . ' in ' . get_term( get_queried_object_id() )->name;
						}
					}
				}
			}
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', '\HYPER_Press_Sponsor\hyperpress_banner_sponsor' );
endif;
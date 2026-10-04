<?php
namespace HYPER_Press_Competition;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_banner_competition' ) ) :
	add_filter( 'hyperpress_banner_content', '\HYPER_Press_Competition\hyperpress_banner_competition' );

	function hyperpress_banner_competition( $banner ) {
		$hyper_competition = 'competition';
		$hyper_season  = 'season';
		if ( is_singular( $hyper_competition ) || is_post_type_archive( $hyper_competition ) ) {
			$banner['type']            = $hyper_competition;
			$subtitle                  = get_theme_mod( 'hyperpress_competition_banner_subtitle' );
			$banner['subtitle']        = $subtitle;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_hyper_green' );

			if ( is_post_type_archive( $hyper_competition ) ) {
				if ( empty( $banner['title'] ) ) {
					$banner['title'] = __( 'Competitions', 'hyperpress' );
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
endif;
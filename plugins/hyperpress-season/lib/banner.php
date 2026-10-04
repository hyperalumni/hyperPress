<?php

namespace HYPER_Press_Season;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Season\hyperpress_banner_season' ) ) :
	add_filter( 'hyperpress_banner_content', '\HYPER_Press_Season\hyperpress_banner_season' );

	function hyperpress_banner_season( $banner ) {
		$hyper_season = 'season';

		if ( is_tax( 'season' ) && ! is_post_type_archive() ) {
			$banner['type']            = $hyper_season;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_gear_blue' );

			if ( empty( $banner['title'] ) ) {
				$banner['title'] = __( 'Season', 'hyperpress' );
				$season          = get_term( get_queried_object_id() )->name;
				if ( ! empty( $season ) ) {
					$banner['title'] = __( $season, 'hyperpress' );
				}
			}

			if ( empty( $banner['subtitle'] ) ) {
				$banner['subtitle'] = get_theme_mod( 'hyperpress_season_banner_subtitle' );
			}
		}


		return $banner;
	}
endif;
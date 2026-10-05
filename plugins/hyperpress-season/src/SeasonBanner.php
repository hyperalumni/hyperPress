<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class SeasonBanner {

	public static function register(): void {
		add_filter( 'hyperpress_banner_content', array( self::class, 'filter' ) );
	}

	public static function filter( $banner ) {
		$hyper_season = 'season';

		if ( is_tax( 'hyper_season' ) && ! is_post_type_archive() ) {
			$banner['type']            = $hyper_season;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_gear_blue' );

			if ( empty( $banner['title'] ) ) {
				$banner['title'] = __( 'Season', 'hyperpress' );
				$term            = get_queried_object();
				if ( $term instanceof \WP_Term && ! empty( $term->name ) ) {
					$banner['title'] = $term->name;
				}
			}

			if ( empty( $banner['subtitle'] ) ) {
				$banner['subtitle'] = get_theme_mod( 'hyperpress_season_banner_subtitle' );
			}
		}

		return $banner;
	}
}

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
				$season          = get_term( get_queried_object_id() )->name;
				if ( ! empty( $season ) ) {
					$banner['title'] = __( $season, 'hyperpress' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- plugins audit
				}
			}

			if ( empty( $banner['subtitle'] ) ) {
				$banner['subtitle'] = get_theme_mod( 'hyperpress_season_banner_subtitle' );
			}
		}

		return $banner;
	}
}

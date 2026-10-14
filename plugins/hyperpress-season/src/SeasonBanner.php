<?php
namespace HyperPress\Season;

use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

final class SeasonBanner {

	public static function register(): void {
		add_filter( 'hyperpress_banner_content', array( self::class, 'filter' ) );
	}

	public static function filter( $banner ) {
		$hyper_season = 'season';

		// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the season itself.
		$term = is_tax() ? RequestTerm::get( 'hyper_season' ) : null;
		if ( $term && ! is_post_type_archive() ) {
			$banner['type']            = $hyper_season;
			$banner['backgroundColor'] = get_theme_mod( 'hyperpress_gear_blue' );

			if ( empty( $banner['title'] ) ) {
				$banner['title'] = __( 'Season', 'hyperpress' );
				if ( ! empty( $term->name ) ) {
					$banner['title'] = $term->name;
				}
			}

			if ( empty( $banner['subtitle'] ) ) {
				$banner['subtitle'] = Settings::banner_subtitle();
			}
		}

		return $banner;
	}
}

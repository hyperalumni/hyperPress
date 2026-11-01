<?php
namespace HyperPress\Season;

use HyperPress\Utils\ArchiveImage;
use HyperPress\Utils\ArchiveRequest;

defined( 'ABSPATH' ) || exit;

final class SeasonBanner {

	public static function register(): void {
		add_filter( 'hyperpress_banner_content', array( self::class, 'filter' ) );
	}

	public static function filter( $banner ) {
		$hyper_season = 'season';

		$term = ArchiveRequest::taxonomy_term( 'hyper_season' );
		if ( $term ) {
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

			if ( empty( $banner['image'] ) ) {
				$image = ArchiveImage::term_image_id( $term->term_id, Settings::BANNER_IMAGE, Settings::banner_image() );
				if ( $image > 0 ) {
					$banner['image'] = $image;
				}
			}
		}

		return $banner;
	}
}

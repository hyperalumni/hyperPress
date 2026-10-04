<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || exit;

use Imagely\NGG\DataMappers\Album as AlbumMapper;
use Imagely\NGG\DataMappers\Gallery as GalleryMapper;

final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'add' ), 20 );
	}

	public static function add( $breadcrumbs ) {
		$photos_page = get_theme_mod( 'hyperpress_nggallery_photos_page' );
		if ( ! empty( $photos_page ) && is_page( $photos_page ) ) {
			$current_url_from_server = $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- plugins audit
			$ngg_slug                = get_option( 'ngg_options' )['router_param_slug'];
			if ( str_contains( $current_url_from_server, $ngg_slug ) ) {
				$regex = '/(' . $ngg_slug . ')\/([^\/]*)\/?([^\/]*)?/';
				preg_match( $regex, $current_url_from_server, $matches );

				$album_slug = $matches[2];
				if ( ! empty( $album_slug ) ) {
					$album_mapper = AlbumMapper::get_instance();
					if ( ! empty( $album_mapper ) ) {
						$album = $album_mapper->get_by_slug( $album_slug );
						if ( ! empty( $album ) ) {
							$breadcrumbs[] = array(
								'title'   => $album->name,
								'url'     => sanitize_url( get_permalink() . $ngg_slug . '/' . $album->slug . '/' ),
								'classes' => array(
									'li'    => array(
										'item-album',
										'item-album-' . $album->slug,
									),
									'bread' => array(
										'bread-album',
										'bread-album-' . $album->slug,
									),
								),
							);
						}

						$gallery_slug = $matches[3];
						if ( ! empty( $gallery_slug ) ) {
							$gallery_mapper = GalleryMapper::get_instance();
							if ( ! empty( $gallery_mapper ) ) {
								$gallery = $gallery_mapper->get_by_slug( $gallery_slug );

								if ( ! empty( $gallery ) ) {
									$breadcrumbs[] = array(
										'title'   => $gallery->title,
										'url'     => sanitize_url( get_permalink() . $ngg_slug . '/' . $album->slug . '/' . $gallery->slug . '/' ),
										'classes' => array(
											'li'    => array(
												'item-gallery',
												'item-gallery-' . $gallery->slug,
											),
											'bread' => array(
												'bread-gallery',
												'bread-gallery-' . $gallery->slug,
											),
										),
									);

								}
							}
						}
					}
				}
			}
		}

		return $breadcrumbs;
	}
}

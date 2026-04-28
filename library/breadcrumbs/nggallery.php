<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Imagely\NGG\DataMappers\Album as AlbumMapper;
use Imagely\NGG\DataMappers\Gallery as GalleryMapper;

if ( ! function_exists( 'hyperpress_breadcrumb_nggallery' ) ) :
	function hyperpress_breadcrumb_nggallery( $breadcrumbs ) {
		$photosPage = get_theme_mod( 'hyperpress_nggallery_photos_page' );
		if ( ! empty( $photosPage ) && is_page( $photosPage ) ) {
			global $page;
			$currentUrlFromServer = $_SERVER['REQUEST_URI'];
			$nggSlug              = get_option( 'ngg_options' )['router_param_slug'];
			if ( str_contains( $currentUrlFromServer, $nggSlug ) ) {
				$regex = '/(' . $nggSlug . ')\/([^\/]*)\/?([^\/]*)?/';
				preg_match( $regex, $currentUrlFromServer, $matches );

				$albumSlug = $matches[2];
				if ( ! empty( $albumSlug ) ) {
					$album_mapper = AlbumMapper::get_instance();
					if ( ! empty( $album_mapper ) ) {
						$album = $album_mapper->get_by_slug( $albumSlug );
						if ( ! empty( $album ) ) {
							$breadcrumbs[] = [
								"title"   => $album->name,
								"url"     => sanitize_url( $page . get_permalink() . $nggSlug . '/' . $album->slug . '/' ),
								"classes" => [
									'li'    => [
										'item-album',
										'item-album-' . $album->slug,
									],
									'bread' => [
										'bread-album',
										'bread-album-' . $album->slug,
									]
								]
							];
						}

						$gallerySlug = $matches[3];
						if ( ! empty( $gallerySlug ) ) {
							$gallery_mapper = GalleryMapper::get_instance();
							if ( ! empty( $gallery_mapper ) ) {
								$gallery = $gallery_mapper->get_by_slug( $gallerySlug );

								if ( ! empty( $gallery ) ) {
									$breadcrumbs[] = [
										"title"   => $gallery->title,
										"url"     => sanitize_url( $page . get_permalink() . $nggSlug . '/' . $album->slug . '/' . $gallery->slug . '/' ),
										"classes" => [
											'li'    => [
												'item-gallery',
												'item-gallery-' . $gallery->slug,
											],
											'bread' => [
												'bread-gallery',
												'bread-gallery-' . $gallery->slug,
											]
										]
									];

								}
							}
						}
					}
				}
			}
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_nggallery', 20 );
endif;

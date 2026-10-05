<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || exit;

use Imagely\NGG\DataMappers\Album as AlbumMapper;
use Imagely\NGG\DataMappers\Gallery as GalleryMapper;

final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'add' ), 20 );
	}

	/**
	 * Reads the album and gallery segments that follow the NextGEN router slug in a URL path.
	 *
	 * @param string $path URL path (no query string).
	 * @param string $slug NextGEN router slug.
	 * @return array{album: string, gallery: string}|null Null when the path has no `<slug>/` segment.
	 */
	public static function parse_path( string $path, string $slug ): ?array {
		if ( '' === $slug ) {
			return null;
		}
		$regex = '#(?:^|/)' . preg_quote( $slug, '#' ) . '/([^/]*)/?([^/]*)#';
		if ( 1 !== preg_match( $regex, $path, $matches ) ) {
			return null;
		}

		return array(
			'album'   => $matches[1] ?? '',
			'gallery' => $matches[2] ?? '',
		);
	}

	public static function add( $breadcrumbs ) {
		$photos_page = get_theme_mod( 'hyperpress_nggallery_photos_page' );
		if ( empty( $photos_page ) || ! is_page( $photos_page ) ) {
			return $breadcrumbs;
		}

		$options  = get_option( 'ngg_options' );
		$ngg_slug = is_array( $options ) ? ( $options['router_param_slug'] ?? '' ) : '';
		if ( ! is_string( $ngg_slug ) || '' === $ngg_slug ) {
			return $breadcrumbs;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only parsed into a path and matched, never output.
		$path        = wp_parse_url( is_string( $request_uri ) ? $request_uri : '', PHP_URL_PATH );
		$segments    = is_string( $path ) ? self::parse_path( $path, $ngg_slug ) : null;
		if ( null === $segments || '' === $segments['album'] ) {
			return $breadcrumbs;
		}

		$album_mapper = AlbumMapper::get_instance();
		if ( empty( $album_mapper ) ) {
			return $breadcrumbs;
		}
		$album = $album_mapper->get_by_slug( $segments['album'] );
		if ( empty( $album ) ) {
			return $breadcrumbs;
		}

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

		if ( '' === $segments['gallery'] ) {
			return $breadcrumbs;
		}
		$gallery_mapper = GalleryMapper::get_instance();
		if ( empty( $gallery_mapper ) ) {
			return $breadcrumbs;
		}
		$gallery = $gallery_mapper->get_by_slug( $segments['gallery'] );
		if ( empty( $gallery ) ) {
			return $breadcrumbs;
		}

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

		return $breadcrumbs;
	}
}

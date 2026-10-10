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
	 * Reads the path segments that follow the NextGEN router slug in a URL path.
	 *
	 * Parsing stops at the first NextGEN reserved segment (page, image, tags, slideshow, thumbnails, imagebrowser or anything containing `--`). Empty segments are dropped.
	 *
	 * @param string $path URL path (no query string).
	 * @param string $slug NextGEN router slug.
	 * @return array<int, array{slug: string, raw: string}>|null Per segment: `slug` is the decoded text run through sanitize_title(), `raw` the decoded text; null when the path has no `<slug>/` segment.
	 */
	public static function parse_path( string $path, string $slug ): ?array {
		if ( '' === $slug ) {
			return null;
		}
		$regex = '#(?:^|/)' . preg_quote( $slug, '#' ) . '/(.*)#s';
		if ( 1 !== preg_match( $regex, $path, $matches ) ) {
			return null;
		}

		$reserved = array( 'page', 'image', 'tags', 'slideshow', 'thumbnails', 'imagebrowser' );
		$segments = array();
		foreach ( explode( '/', $matches[1] ) as $part ) {
			$raw = rawurldecode( $part );
			if ( in_array( strtolower( $raw ), $reserved, true ) || false !== strpos( $raw, '--' ) ) {
				break;
			}
			$segment_slug = sanitize_title( $raw );
			if ( '' === $segment_slug ) {
				continue;
			}
			$segments[] = array(
				'slug' => $segment_slug,
				'raw'  => $raw,
			);
		}

		return $segments;
	}

	/**
	 * Turns parsed path segments into breadcrumbs, in URL order.
	 *
	 * A segment before the last can only be an album. The last one is a gallery first when it follows other segments, an album first when it is alone. Segments that match nothing add no crumb.
	 *
	 * @param array<int, array{slug: string, raw: string}> $segments      Output of parse_path().
	 * @param string                                       $base_url      Permalink plus router slug and a trailing slash.
	 * @param callable                                     $find_album    Takes a slug, returns an object with name and slug, or null.
	 * @param callable                                     $find_gallery  Takes a slug, returns an object with title and slug, or null.
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve( array $segments, string $base_url, callable $find_album, callable $find_gallery ): array {
		$crumbs = array();
		$parts  = array();
		$last   = count( $segments ) - 1;

		foreach ( array_values( $segments ) as $index => $segment ) {
			$album   = null;
			$gallery = null;
			if ( $index < $last ) {
				$album = $find_album( $segment['slug'] );
			} elseif ( $index > 0 ) {
				$gallery = $find_gallery( $segment['slug'] );
				if ( empty( $gallery ) ) {
					$album = $find_album( $segment['slug'] );
				}
			} else {
				$album = $find_album( $segment['slug'] );
				if ( empty( $album ) ) {
					$gallery = $find_gallery( $segment['slug'] );
				}
			}

			if ( ! empty( $gallery ) ) {
				$parts[]  = $gallery->slug;
				$crumbs[] = self::crumb( 'gallery', $gallery->title, $gallery->slug, $base_url . implode( '/', $parts ) . '/' );
			} elseif ( ! empty( $album ) ) {
				$parts[]  = $album->slug;
				$crumbs[] = self::crumb( 'album', $album->name, $album->slug, $base_url . implode( '/', $parts ) . '/' );
			} else {
				$parts[] = rawurlencode( $segment['raw'] );
			}
		}

		return $crumbs;
	}

	private static function crumb( string $type, string $title, string $slug, string $url ): array {
		return array(
			'title'   => $title,
			'url'     => sanitize_url( $url ),
			'classes' => array(
				'li'    => array(
					'item-' . $type,
					'item-' . $type . '-' . $slug,
				),
				'bread' => array(
					'bread-' . $type,
					'bread-' . $type . '-' . $slug,
				),
			),
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

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$segments    = is_string( $path ) ? self::parse_path( $path, $ngg_slug ) : null;
		if ( empty( $segments ) ) {
			return $breadcrumbs;
		}

		$find_album   = static function ( string $slug ) {
			$mapper = AlbumMapper::get_instance();
			return empty( $mapper ) ? null : $mapper->get_by_slug( $slug );
		};
		$find_gallery = static function ( string $slug ) {
			$mapper = GalleryMapper::get_instance();
			return empty( $mapper ) ? null : $mapper->get_by_slug( $slug );
		};

		return array_merge( $breadcrumbs, self::resolve( $segments, get_permalink() . $ngg_slug . '/', $find_album, $find_gallery ) );
	}
}

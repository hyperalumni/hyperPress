<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Builds the NextGEN [ngg] shortcode strings the photos section delegates to.
 */
final class NextGenShortcodes {

	public const SINGLE_TEMPLATE = 'gallery.php';
	public const ALBUM_TEMPLATE  = 'album-compact.php';

	/**
	 * One gallery as a short thumbnail grid with NextGEN's own pagination.
	 *
	 * @param int $id    Gallery ID.
	 * @param int $limit Images on the first page.
	 */
	public static function single( int $id, int $limit = PhotosBlock::LIMIT ): string {
		return '[ngg source="galleries" ids="' . $id . '" display="basic_thumbnail" images_per_page="' . $limit . '"' . self::template( self::SINGLE_TEMPLATE ) . ']';
	}

	/**
	 * Several galleries as an album of tiles (source="albums", never the src alias, which NextGEN reads too late).
	 *
	 * @param array<mixed> $ids Gallery IDs.
	 */
	public static function album( array $ids ): string {
		$clean = array();
		foreach ( $ids as $id ) {
			$id = is_scalar( $id ) ? (int) $id : 0;
			if ( $id > 0 ) {
				$clean[] = $id;
			}
		}

		return '[ngg source="albums" gallery_ids="' . implode( ',', $clean ) . '" display="basic_compact_album"' . self::template( self::ALBUM_TEMPLATE ) . ']';
	}

	/**
	 * The ` template="…"` fragment, or '' unless the parent theme has nggallery/{file} (a missing template makes
	 * NextGEN print "[Not a valid template]").
	 *
	 * @param string $file Template file name.
	 */
	public static function template( string $file ): string {
		$file = (string) apply_filters( 'hyperpress_event_nextgen_template', $file );
		if ( '' === $file || $file !== basename( $file ) ) {
			return '';
		}
		if ( ! is_readable( get_template_directory() . '/nggallery/' . $file ) ) {
			return '';
		}

		return ' template="' . $file . '"';
	}

	/**
	 * Link to the next page of NextGEN's pagination for the shortcode on the given permalink.
	 *
	 * @param string $permalink The event permalink.
	 * @param int    $page      Page number.
	 */
	public static function more_url( string $permalink, int $page = 2 ): string {
		if ( '' !== (string) get_option( 'permalink_structure' ) ) {
			return trailingslashit( $permalink ) . 'nggallery/page/' . $page;
		}

		return add_query_arg( 'nggpage', $page, $permalink );
	}
}

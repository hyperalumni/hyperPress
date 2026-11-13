<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_event_photos] section.
 */
final class Photos {

	public const SHORTCODE      = 'hyperpress_event_photos';
	public const DEFAULT_ANCHOR = 'photos';
	public const STYLE          = PhotosBlock::STYLE;

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler. Prints nothing unless NextGEN is active and the current post is an event.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		if ( ! Integrations::nextgen() ) {
			return '';
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || Plugin::POST_TYPE !== $post->post_type ) {
			return '';
		}

		$ids = get_post_meta( $post->ID, Fields::GALLERIES, false );
		$ids = is_array( $ids ) ? $ids : array();
		// Meta Box saves one row per gallery; tolerate a single serialized list too.
		if ( 1 === count( $ids ) && is_array( $ids[0] ) ) {
			$ids = $ids[0];
		}
		$ids = Galleries::existing( $ids );
		$over = Dates::is_over( $post->ID );
		if ( array() === $ids && $over ) {
			return '';
		}

		$atts = Sections::atts( is_array( $atts ) ? $atts : array(), __( 'Photos', 'hyperpress' ) );
		if ( '' === $atts['anchor'] ) {
			$atts['anchor'] = self::DEFAULT_ANCHOR;
		}

		return PhotosBlock::html(
			$ids,
			$over,
			Sections::banner( $post->ID, $atts ),
			(string) get_permalink( $post ),
			$atts['anchor'],
			self::current_page() <= 1
		);
	}

	/**
	 * The NextGEN page of the request: the `nggallery/page/N` path segment (pretty permalinks) or the `nggpage`
	 * query argument (plain), the two forms NextGenShortcodes::more_url() builds; 1 otherwise.
	 */
	public static function current_page(): int {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( 1 === preg_match( '#/nggallery/page/(\d+)(?:/|$)#', $path, $match ) && (int) $match[1] > 0 ) {
			return (int) $match[1];
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only pagination argument, like NextGEN's own.
		$arg = isset( $_GET['nggpage'] ) && is_scalar( $_GET['nggpage'] ) ? absint( wp_unslash( $_GET['nggpage'] ) ) : 0;

		return max( 1, $arg );
	}
}

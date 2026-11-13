<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_event_photos] section.
 */
final class Photos {

	public const SHORTCODE      = 'hyperpress_event_photos';
	public const DEFAULT_ANCHOR = 'photos';

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
			$atts['anchor']
		);
	}
}

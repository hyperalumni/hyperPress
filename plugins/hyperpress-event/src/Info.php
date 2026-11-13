<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_event_info] section: date, time and venue of the event, read from its own fields.
 */
final class Info {

	public const SHORTCODE      = 'hyperpress_event_info';
	public const DEFAULT_ANCHOR = 'info';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler. Prints nothing (not even the banner) outside an event or when the event has neither a
	 * start nor a venue.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$post = get_post();
		if ( ! $post instanceof \WP_Post || Plugin::POST_TYPE !== $post->post_type ) {
			return '';
		}

		$columns = array(
			__( 'Date', 'hyperpress' )  => self::date( $post->ID ),
			__( 'Time', 'hyperpress' )  => self::time( $post->ID ),
			__( 'Where', 'hyperpress' ) => self::venue( $post->ID ),
		);
		$html    = '';
		foreach ( $columns as $label => $value ) {
			if ( '' !== $value ) {
				$html .= '<div class="cell small-12 medium-4"><strong>' . esc_html( $label ) . '</strong><br>' . $value . '</div>';
			}
		}
		if ( '' === $html ) {
			return '';
		}

		$atts = Sections::atts( is_array( $atts ) ? $atts : array(), __( 'Event Info', 'hyperpress' ) );
		if ( '' === $atts['anchor'] ) {
			$atts['anchor'] = self::DEFAULT_ANCHOR;
		}

		// A `.grid-margin-x` row directly in the content: the theme's full-screen layout centres and pads it.
		return Sections::banner( $post->ID, $atts ) . '<div class="grid-x grid-margin-x">' . $html . '</div>';
	}

	/**
	 * Escaped start date, as a range when an explicit end falls on a later day; '' without a start.
	 *
	 * @param int $post_id Event ID.
	 */
	private static function date( int $post_id ): string {
		$start = Dates::start( $post_id );
		if ( null === $start ) {
			return '';
		}
		$format = (string) get_option( 'date_format' );
		$text   = wp_date( $format, $start->getTimestamp() );
		$end    = Dates::explicit_end( $post_id );
		if ( null !== $end && $end->format( 'Y-m-d' ) !== $start->format( 'Y-m-d' ) ) {
			$text .= ' – ' . wp_date( $format, $end->getTimestamp() );
		}

		return esc_html( (string) $text );
	}

	/**
	 * Escaped start time, as a range when an explicit end falls on the same day; '' without a start.
	 *
	 * @param int $post_id Event ID.
	 */
	private static function time( int $post_id ): string {
		$start = Dates::start( $post_id );
		if ( null === $start ) {
			return '';
		}
		$format = (string) get_option( 'time_format' );
		$text   = wp_date( $format, $start->getTimestamp() );
		$end    = Dates::explicit_end( $post_id );
		if ( null !== $end && $end->format( 'Y-m-d' ) === $start->format( 'Y-m-d' ) ) {
			$text .= ' – ' . wp_date( $format, $end->getTimestamp() );
		}

		return esc_html( (string) $text );
	}

	/**
	 * Escaped venue name with the address below it (line breaks kept); '' without either.
	 *
	 * @param int $post_id Event ID.
	 */
	private static function venue( int $post_id ): string {
		$name    = get_post_meta( $post_id, Fields::VENUE_NAME, true );
		$address = get_post_meta( $post_id, Fields::VENUE_ADDRESS, true );
		$name    = is_string( $name ) ? trim( $name ) : '';
		$address = is_string( $address ) ? trim( $address ) : '';
		$parts   = array();
		if ( '' !== $name ) {
			$parts[] = esc_html( $name );
		}
		if ( '' !== $address ) {
			$parts[] = nl2br( esc_html( $address ), false );
		}

		return implode( '<br>', $parts );
	}
}

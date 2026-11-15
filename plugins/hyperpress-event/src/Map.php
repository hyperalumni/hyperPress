<?php
namespace HyperPress\Event;

use RWMB_OSM_Field;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * A full-width, non-interactive OpenStreetMap of the venue for the Event Info section of the single event page.
 */
final class Map {

	private const DEFAULT_ZOOM = 15;
	private const MAX_ZOOM     = 19;

	/**
	 * The map markup of an event, or '' when RWMB_OSM_Field is missing, the address is empty or the pin is not valid.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function html( int $post_id ): string {
		if ( $post_id <= 0 || ! class_exists( 'RWMB_OSM_Field' ) ) {
			return '';
		}

		$address = \HyperPress\Utils\Meta::get( Fields::VENUE_ADDRESS, $post_id );
		if ( ! is_scalar( $address ) || '' === trim( (string) $address ) ) {
			return '';
		}

		// The stored text, not rwmb_meta(): for an osm field Meta Box returns the rendered map HTML.
		$map = self::parse( get_post_meta( $post_id, Fields::VENUE_MAP, true ) );
		if ( null === $map ) {
			return '';
		}

		$venue = \HyperPress\Utils\Meta::get( Fields::VENUE_NAME, $post_id );
		$venue = is_scalar( $venue ) ? trim( (string) $venue ) : '';
		$label = '' !== $venue
			/* translators: %s: venue name. */
			? sprintf( __( 'Map of %s', 'hyperpress' ), $venue )
			: __( 'Map of the venue', 'hyperpress' );

		$inner = RWMB_OSM_Field::render_map(
			self::decimal( $map['lat'] ) . ',' . self::decimal( $map['lng'] ) . ',' . $map['zoom'],
			array(
				'width'      => '100%',
				'height'     => '300px',
				'marker'     => true,
				'js_options' => array(
					'dragging'        => false,
					'zoomControl'     => false,
					'scrollWheelZoom' => false,
					'doubleClickZoom' => false,
					'touchZoom'       => false,
					'boxZoom'         => false,
					'keyboard'        => false,
				),
			)
		);

		return sprintf(
			'<div class="hyperpress-map alignfull" role="img" aria-label="%s" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">%s</div>',
			esc_attr( $label ),
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by Meta Box.
		);
	}

	/**
	 * Parses "lat,lng" or "lat,lng,zoom".
	 *
	 * @param mixed $value The saved map value.
	 *
	 * @return array{lat:float,lng:float,zoom:int}|null Null when it is not a valid location.
	 */
	public static function parse( mixed $value ): ?array {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$parts = array_map( 'trim', explode( ',', $value ) );
		if ( count( $parts ) < 2 || count( $parts ) > 3 ) {
			return null;
		}
		foreach ( $parts as $part ) {
			if ( ! is_numeric( $part ) ) {
				return null;
			}
		}

		$lat = (float) $parts[0];
		$lng = (float) $parts[1];
		if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
			return null;
		}

		$zoom = isset( $parts[2] ) ? max( 0, min( self::MAX_ZOOM, (int) $parts[2] ) ) : self::DEFAULT_ZOOM;

		return array(
			'lat'  => $lat,
			'lng'  => $lng,
			'zoom' => $zoom,
		);
	}

	/**
	 * Up to seven decimal places, trailing zeros removed (no exponent, no locale separators).
	 *
	 * @param float $value A coordinate.
	 */
	private static function decimal( float $value ): string {
		$text = rtrim( rtrim( number_format( $value, 7, '.', '' ), '0' ), '.' );
		return in_array( $text, array( '', '-0' ), true ) ? '0' : $text;
	}
}

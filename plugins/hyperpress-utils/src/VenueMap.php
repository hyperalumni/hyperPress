<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The one place for venue-map code: parsing and sanitising a Meta Box `osm` pin, rendering the
 * non-interactive OpenStreetMap, reading it from post meta, and the admin autocomplete fix.
 *
 * Used by hyperpress-competition, hyperpress-event and hyperpress-contact.
 */
final class VenueMap {

	public const DEFAULT_ZOOM = 15;
	public const MAX_ZOOM     = 19;

	private const STYLE_HANDLE = 'rwmb-osm';

	/*
	 * The Meta Box `osm` field's address autocomplete menu gets jQuery UI's `ui-front` class, and
	 * `.ui-front { z-index: 100 }` (WordPress and Meta Box core CSS) has the same specificity as, and loads
	 * after, Meta Box's `.ui-autocomplete { z-index: 999999 }`. The menu then sits under the Leaflet map
	 * (panes and controls use z-index 200-1000). A two-class selector wins whatever the load order.
	 */
	private const ADMIN_CSS = '.ui-autocomplete.ui-front{z-index:999999}';

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
	 * Sanitize callback for the `osm` field: keeps only "lat,lng" or "lat,lng,zoom".
	 *
	 * @param mixed $value The submitted value.
	 *
	 * @return string The trimmed value, or an empty string when it is not a location.
	 */
	public static function sanitize( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );

		return 1 === preg_match( '/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?(,\s*\d+)?$/', $value ) ? $value : '';
	}

	/**
	 * A non-interactive OpenStreetMap in the shared wrapper.
	 *
	 * @param array{lat:float,lng:float,zoom:int} $location A parsed location.
	 * @param string                              $label    Accessible name of the map.
	 * @param string                              $height   CSS height.
	 * @param string                              $css_class Extra wrapper classes.
	 * @param string                              $style    Extra inline style, appended after `pointer-events:none;`.
	 *
	 * @return string The HTML, or an empty string when Meta Box is not active.
	 */
	public static function render( array $location, string $label, string $height = '300px', string $css_class = '', string $style = '' ): string {
		if ( ! class_exists( 'RWMB_OSM_Field' ) ) {
			return '';
		}

		$inner = \RWMB_OSM_Field::render_map(
			self::decimal( $location['lat'] ) . ',' . self::decimal( $location['lng'] ) . ',' . $location['zoom'],
			array(
				'width'      => '100%',
				'height'     => $height,
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
			'<div class="%s" role="img" aria-label="%s" style="%s">%s</div>',
			esc_attr( trim( 'hyperpress-map ' . $css_class ) ),
			esc_attr( $label ),
			esc_attr( 'pointer-events:none;' . $style ),
			$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by Meta Box.
		);
	}

	/**
	 * The map of a post whose address, venue name and pin live in post meta.
	 *
	 * @param int    $post_id     The post.
	 * @param string $name_key    Meta key of the venue name.
	 * @param string $address_key Meta key of the venue address.
	 * @param string $map_key     Meta key of the `osm` pin.
	 * @param string $css_class  Extra wrapper classes.
	 * @param string $style       Extra inline style.
	 *
	 * @return string The HTML, or an empty string without an address or a valid pin.
	 */
	public static function for_post( int $post_id, string $name_key, string $address_key, string $map_key, string $css_class = '', string $style = '' ): string {
		if ( $post_id <= 0 || ! class_exists( 'RWMB_OSM_Field' ) ) {
			return '';
		}

		$address = Meta::get( $address_key, $post_id );
		if ( ! is_scalar( $address ) || '' === trim( (string) $address ) ) {
			return '';
		}

		// The stored text, not rwmb_meta(): for an osm field Meta Box returns the rendered map HTML.
		$map = self::parse( get_post_meta( $post_id, $map_key, true ) );
		if ( null === $map ) {
			return '';
		}

		$venue = Meta::get( $name_key, $post_id );
		$venue = is_scalar( $venue ) ? trim( (string) $venue ) : '';
		$label = '' !== $venue
			/* translators: %s: venue name. */
			? sprintf( __( 'Map of %s', 'hyperpress' ), $venue )
			: __( 'Map of the venue', 'hyperpress' );

		return self::render( $map, $label, '300px', $css_class, $style );
	}

	/**
	 * Lifts the address autocomplete menu above the map on the edit screens of a post type.
	 *
	 * @param string $post_type The post type whose add/edit screens need the fix.
	 */
	public static function register_admin_fix( string $post_type ): void {
		// Meta Box enqueues its field styles on admin_enqueue_scripts at priority 10; a later priority
		// guarantees the handle exists when the inline style is attached.
		add_action(
			'admin_enqueue_scripts',
			static function ( string $hook_suffix ) use ( $post_type ): void {
				self::enqueue_admin_fix( $hook_suffix, $post_type );
			},
			20
		);
	}

	/**
	 * Attaches the override to Meta Box's osm stylesheet on the add/edit screens of one post type only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @param string $post_type   The post type.
	 */
	public static function enqueue_admin_fix( string $hook_suffix, string $post_type ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( null === $screen || $post_type !== $screen->post_type ) {
			return;
		}

		wp_add_inline_style( self::STYLE_HANDLE, self::ADMIN_CSS );
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

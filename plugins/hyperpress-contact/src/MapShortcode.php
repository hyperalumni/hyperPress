<?php
namespace HyperPress\Contact;

use HyperPress\Utils\GoogleMapsKey;
use RWMB_Map_Field;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_map height="250"] shortcode: a static Google map of the saved location, drawn by Meta Box.
 */
final class MapShortcode {

	public static function register(): void {
		add_shortcode( 'hyperpress_map', array( self::class, 'render' ) );
	}

	/**
	 * Renders the map, or a comment for administrators when it cannot be shown.
	 *
	 * @param mixed $atts Shortcode attributes.
	 */
	public static function render( $atts ): string {
		$atts   = shortcode_atts( array( 'height' => '250' ), is_array( $atts ) ? $atts : array(), 'hyperpress_map' );
		$height = absint( $atts['height'] );
		$height = 0 === $height ? 250 : max( 100, min( 1000, $height ) );

		$map = Settings::map();
		if ( null === $map ) {
			return self::notice( __( 'no valid map location is set.', 'hyperpress' ) );
		}
		$key = GoogleMapsKey::get();
		if ( '' === $key ) {
			return self::notice( __( 'no Google Maps API key is set.', 'hyperpress' ) );
		}
		if ( ! class_exists( 'RWMB_Map_Field' ) ) {
			return self::notice( __( 'Meta Box is not active.', 'hyperpress' ) );
		}

		// Always pass our key: without one Meta Box falls back to a demo key.
		$inner = RWMB_Map_Field::render_map(
			self::location( $map ),
			array(
				'api_key'    => $key,
				'width'      => '100%',
				'height'     => $height . 'px',
				'marker'     => true,
				'js_options' => array(
					'disableDefaultUI'  => true,
					'gestureHandling'   => 'none',
					'keyboardShortcuts' => false,
					'clickableIcons'    => false,
				),
			)
		);

		return sprintf(
			'<div class="hyperpress-map" role="img" aria-label="%s" style="pointer-events:none;">%s</div>',
			esc_attr( __( 'Map of our location', 'hyperpress' ) ),
			$inner // Escaped by Meta Box.
		);
	}

	/**
	 * The "lat,lng,zoom" string Meta Box expects, as plain decimals (no exponent, no locale separators).
	 *
	 * @param array{lat:float,lng:float,zoom:int} $map The parsed location.
	 */
	private static function location( array $map ): string {
		return self::decimal( $map['lat'] ) . ',' . self::decimal( $map['lng'] ) . ',' . (int) $map['zoom'];
	}

	/**
	 * Up to seven decimal places, trailing zeros removed.
	 *
	 * @param float $value A coordinate.
	 */
	private static function decimal( float $value ): string {
		$text = rtrim( rtrim( number_format( $value, 7, '.', '' ), '0' ), '.' );
		return in_array( $text, array( '', '-0' ), true ) ? '0' : $text;
	}

	/**
	 * An HTML comment for administrators; nothing for visitors.
	 *
	 * @param string $reason Why no map is shown.
	 */
	private static function notice( string $reason ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		$safe = $reason;
		while ( false !== strpos( $safe, '--' ) ) {
			$safe = str_replace( '--', '', $safe );
		}
		return '<!-- hyperpress_map: ' . esc_html( $safe ) . ' -->';
	}
}

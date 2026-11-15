<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_map height="250"] shortcode: a static OpenStreetMap map of the saved location, drawn by the shared `VenueMap`.
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
		if ( ! class_exists( 'RWMB_OSM_Field' ) ) {
			return self::notice( __( 'Meta Box is not active.', 'hyperpress' ) );
		}

		return \HyperPress\Utils\VenueMap::render( $map, __( 'Map of our location', 'hyperpress' ), $height . 'px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside VenueMap.
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

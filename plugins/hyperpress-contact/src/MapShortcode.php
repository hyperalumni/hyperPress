<?php
namespace HyperPress\Contact;

use HyperPress\Utils\GoogleMapsKey;
use HyperPress\Utils\Version;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_map height="250"] shortcode: a static Google map of the saved location.
 */
final class MapShortcode {

	private const MAP_HANDLE    = 'hyperpress-contact-map';
	private const GOOGLE_HANDLE = 'hyperpress-contact-google-maps';

	public static function register(): void {
		add_shortcode( 'hyperpress_map', array( self::class, 'render' ) );
	}

	/**
	 * Renders the map and enqueues its scripts, or a comment for administrators when it cannot be shown.
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

		self::enqueue( $key );

		return sprintf(
			'<div class="hyperpress-map" role="img" aria-label="%s" data-lat="%s" data-lng="%s" data-zoom="%d" style="height:%dpx;width:100%%;pointer-events:none;"></div>',
			esc_attr( __( 'Map of our location', 'hyperpress' ) ),
			esc_attr( (string) $map['lat'] ),
			esc_attr( (string) $map['lng'] ),
			$map['zoom'],
			$height
		);
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

	/**
	 * Registers and enqueues the init script and the Google Maps loader; safe to call repeatedly.
	 *
	 * @param string $key Google Maps API key.
	 */
	private static function enqueue( string $key ): void {
		$file = dirname( __DIR__ ) . '/hyperpress-contact.php';

		wp_register_script(
			self::MAP_HANDLE,
			plugins_url( 'assets/map.js', $file ),
			array(),
			Version::of( $file ),
			// No strategy: a plain footer script runs in order, before the async Google script that depends on it.
			array( 'in_footer' => true )
		);

		$src = add_query_arg(
			array(
				'key'      => rawurlencode( $key ),
				'loading'  => 'async',
				'callback' => 'hyperpressContactInitMaps',
			),
			'https://maps.googleapis.com/maps/api/js'
		);

		wp_register_script(
			self::GOOGLE_HANDLE,
			$src,
			array( self::MAP_HANDLE ),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioned script.
			array(
				'in_footer' => true,
				'strategy'  => 'async',
			)
		);
		wp_enqueue_script( self::GOOGLE_HANDLE );
	}
}

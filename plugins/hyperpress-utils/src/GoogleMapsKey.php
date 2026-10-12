<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Shared accessor for the Google Maps API key.
 *
 * Plugins read the key with `GoogleMapsKey::get()` instead of owning their own setting.
 */
final class GoogleMapsKey {

	public const OPTION = 'hyperpress_google_maps_api_key';

	/**
	 * The API key, or '' when none is configured.
	 */
	public static function get(): string {
		$key = get_option( self::OPTION, '' );
		if ( is_string( $key ) && '' !== trim( $key ) ) {
			return trim( $key );
		}

		// Fallback until the key moves into a global settings field. At that point the
		// competition plugin should switch to this accessor and this fallback can go.
		// The literal is deliberate: utils must not depend on the competition plugin.
		$legacy = get_theme_mod( 'hyperpress_competition_google_maps_api_key', '' );
		return is_string( $legacy ) ? trim( $legacy ) : '';
	}
}

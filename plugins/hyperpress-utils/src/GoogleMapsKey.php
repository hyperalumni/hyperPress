<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Shared accessor for the Google Maps API key.
 *
 * Plugins read the key with `GoogleMapsKey::get()` instead of owning their own setting.
 * The key is saved on the General settings page.
 */
final class GoogleMapsKey {

	/**
	 * The API key, or '' when none is configured.
	 */
	public static function get(): string {
		$settings = get_option( GeneralPage::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			return '';
		}
		$key = $settings[ GeneralPage::FIELD_KEY ] ?? '';
		return is_string( $key ) ? trim( $key ) : '';
	}
}

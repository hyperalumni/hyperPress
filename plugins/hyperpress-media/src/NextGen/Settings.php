<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Media settings page option, its field id and accessor.
 */
final class Settings {

	public const OPTION      = 'hyperpress_media';
	public const PHOTOS_PAGE = 'hyperpress_media_photos_page';

	/**
	 * The saved id of the page that displays NextGen photos; 0 when unset or not a positive integer (or digit string).
	 */
	public static function photos_page(): int {
		$option = get_option( self::OPTION );
		if ( ! is_array( $option ) || ! isset( $option[ self::PHOTOS_PAGE ] ) ) {
			return 0;
		}
		$value = $option[ self::PHOTOS_PAGE ];
		if ( is_int( $value ) && $value > 0 ) {
			return $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return absint( $value );
		}
		return 0;
	}
}

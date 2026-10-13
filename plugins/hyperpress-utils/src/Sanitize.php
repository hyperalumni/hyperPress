<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitize callbacks for Meta Box fields.
 *
 * Meta Box calls a field's sanitize_callback with four arguments
 * ( $value, $field, $old_value, $object_id ), so a bare WordPress function
 * with extra optional parameters (esc_url_raw( $url, $protocols )) receives
 * the field array in one of them and throws a TypeError. These wrappers take
 * the value and ignore the rest.
 */
final class Sanitize {

	/**
	 * A URL for storage: trimmed, with a disallowed protocol removed.
	 *
	 * @param mixed $value Submitted value.
	 * @return string The cleaned URL, or '' when the value is not a string.
	 */
	public static function url( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return esc_url_raw( trim( $value ) );
	}
}

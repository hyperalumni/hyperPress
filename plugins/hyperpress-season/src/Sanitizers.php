<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitizers for the Season settings fields.
 */
final class Sanitizers {

	/**
	 * The slug of an existing season, else an empty string.
	 *
	 * @param mixed $value The submitted value.
	 */
	public static function current( $value ): string {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}
		return get_term_by( 'slug', $value, 'hyper_season' ) ? $value : '';
	}

	/**
	 * The banner subtitle with unsafe markup removed.
	 *
	 * @param mixed $value The submitted value.
	 */
	public static function banner_subtitle( $value ): string {
		return wp_kses_post( is_scalar( $value ) ? (string) $value : '' );
	}

	/**
	 * Registered post types that show a UI, unique.
	 *
	 * @param mixed $value The submitted value.
	 * @return string[]
	 */
	public static function post_types( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$clean = array();
		foreach ( $value as $post_type ) {
			if ( ! is_string( $post_type ) || ! post_type_exists( $post_type ) ) {
				continue;
			}
			$object = get_post_type_object( $post_type );
			if ( $object && $object->show_ui ) {
				$clean[] = $post_type;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}

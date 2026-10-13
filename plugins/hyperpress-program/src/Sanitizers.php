<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitizers for the Program settings fields.
 */
final class Sanitizers {

	/**
	 * Registered public post types, unique.
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
			if ( $object && $object->public ) {
				$clean[] = $post_type;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}

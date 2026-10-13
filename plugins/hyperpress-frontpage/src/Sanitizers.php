<?php
namespace HyperPress\Frontpage;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Sanitizers for the Front Page settings fields.
 */
final class Sanitizers {

	/**
	 * Existing category term ids.
	 *
	 * @param mixed $value The submitted value.
	 * @return int[]
	 */
	public static function categories( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $id ) {
			$id = is_scalar( $id ) ? absint( $id ) : 0;
			if ( $id > 0 && term_exists( $id, 'category' ) ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Registered, public post types only.
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

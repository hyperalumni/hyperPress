<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Meta {

	/**
	 * Read a Meta Box field, or '' when Meta Box is not active.
	 *
	 * @param string   $key     Field id.
	 * @param int|null $post_id Post to read from; null means the current post.
	 * @return mixed
	 */
	public static function get( string $key, $post_id = null ) {
		if ( ! function_exists( 'rwmb_meta' ) ) {
			return '';
		}

		return rwmb_meta( $key, '', $post_id );
	}
}
